<?php

namespace App\Http\Controllers;

use App\Http\Requests\TokenRequest;
use App\Models\Agency;
use App\Models\Company;
use App\Models\Token;
use App\Models\TokenCategory;
use App\Models\TokenTransferHistory;
use App\Models\User;
use App\Services\AuditService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class TokenController extends Controller
{
    public function index(Request $r)
    {
        $tokens = Token::with(['company', 'agency', 'category', 'currentHolder.role'])->withCount('applicants')->when($r->q, function ($q, $v) {
            $q->where(function ($s) use ($v) {
                $s->where('token_number', 'like', "%$v%")->orWhere('bhc_number', 'like', "%$v%")->orWhereHas('company', fn ($x) => $x->where('name', 'like', "%$v%"))->orWhereHas('agency', fn ($x) => $x->where('name', 'like', "%$v%"));
            });
        })->when($r->company_id, fn ($q, $v) => $q->where('company_id', $v))->when($r->agency_id, fn ($q, $v) => $q->where('agency_id', $v))->when($r->company_name, fn ($q, $v) => $q->whereHas('company', fn ($companyQuery) => $companyQuery->where('name', 'like', "%$v%")))->when($r->agency_name, fn ($q, $v) => $q->whereHas('agency', fn ($agencyQuery) => $agencyQuery->where('name', 'like', "%$v%")))->when($r->category_id, fn ($q, $v) => $q->where('token_category_id', $v))->when($r->holder_id, fn ($q, $v) => $q->where('current_holder_id', $v))->when($r->boesl_status, fn ($q, $v) => $q->where('boesl_status', $v))->when($r->bhc_status === 'pending', fn ($q) => $q->whereNull('bhc_number'))->when($r->bhc_status === 'assigned', fn ($q) => $q->whereNotNull('bhc_number'))->when($r->created === 'today', fn ($q) => $q->whereDate('created_at', today()))->when($r->pre_selected !== null && $r->pre_selected !== '', fn ($q) => $q->where('pre_selected', $r->boolean('pre_selected')))->latest('received_on')->paginate(15)->withQueryString();

        return view('tokens.index', ['tokens' => $tokens, 'preSelectedCount' => Token::where('pre_selected', true)->count(), 'companies' => Company::orderBy('name')->get(), 'agencies' => Agency::orderBy('name')->get(), 'categories' => TokenCategory::orderBy('name')->get(), 'users' => User::with('role')->where('is_active', true)->orderBy('name')->get()]);
    }

    public function create(Request $request)
    {
        return view('tokens.form', $this->formData(new Token([
            'received_on' => today(),
            'received_by' => $request->user()->name,
            'current_holder_id' => $request->user()->id,
            'boesl_status' => 'pending',
            'visa_status' => 'pending',
            'file_status' => 'active',
        ])));
    }

    public function store(TokenRequest $r)
    {
        $data = $r->safe()->except(['change_reason']);
        $data['pre_selected'] = $r->boolean('pre_selected');
        $data['site_visit_required'] = $r->boolean('site_visit_required');

        // --- boesel-visa process: category-conditional field logic ---
        $category = TokenCategory::find($data['token_category_id']);
        $isVA = $category && strtoupper($category->code) === 'VA';

        if ($isVA) {
            // VA category: requires visa-attestation count, not a worker demand
            if (empty($data['required_visa_attestation'])) {
                return back()->withErrors(['required_visa_attestation' => 'The required visa attestation field is required for this category.'])->withInput();
            }
            $data['demanded_workers'] = null;
        } else {
            // All other categories: requires worker demand, not visa attestation
            if (empty($data['demanded_workers'])) {
                return back()->withErrors(['demanded_workers' => 'The demanded workers field is required for this category.'])->withInput();
            }
            $data['required_visa_attestation'] = null;
        }

        $token = DB::transaction(function () use ($r, $data, $category, $isVA) {
            $data['token_number'] = $this->nextNumber($category, $isVA);
            $data['created_by'] = $r->user()->id;
            $data['updated_by'] = $r->user()->id;
            $data['received_by'] = $r->user()->name;
            $data['current_holder_id'] = $r->user()->id;
            $t = Token::create($data);
            if ($t->current_holder_id) {
                TokenTransferHistory::create(['token_id' => $t->id, 'new_holder_id' => $t->current_holder_id, 'transferred_by' => $r->user()->id, 'transferred_at' => now(), 'remarks' => 'Initial file assignment']);
            }
            AuditService::record('create', 'tokens', $t, [], $t->toArray());

            return $t;
        });

        return redirect()->route('tokens.show', $token)->with('success', 'Token created. Printable PDF is ready.');
    }

    public function show(Token $token)
    {
        $token->load(['company', 'agency', 'category', 'currentHolder.role', 'creator', 'applicants', 'transferHistories.previousHolder', 'transferHistories.newHolder', 'transferHistories.transferredBy', 'documents']);

        return view('tokens.show', compact('token'));
    }

    public function modal(Token $token)
    {
        $token->load([
            'company',
            'agency',
            'category',
            'currentHolder.role',
            'creator',
            'updater',
            'applicants',
            'transferHistories.previousHolder',
            'transferHistories.newHolder',
            'transferHistories.transferredBy',
            'documents' => fn ($query) => $query
                ->whereNull('applicant_id')
                ->whereIn('type', ['confirmation-letter', 'demand-letter'])
                ->orderByDesc('version')
                ->orderByDesc('id'),
        ]);

        $tokenDocuments = $token->documents->unique('type')->keyBy('type');

        return view('tokens.modal', compact('token', 'tokenDocuments'));
    }

    public function applicantsModal(Token $token): View
    {
        $token->load([
            'company',
            'agency',
            'applicants' => fn ($query) => $query->orderBy('full_name'),
        ]);

        return view('tokens.applicants-modal', compact('token'));
    }

    public function edit(Token $token)
    {
        $token->load(['transferHistories.previousHolder', 'transferHistories.newHolder', 'transferHistories.transferredBy']);
        $tokenDocuments = $token->documents()
            ->whereNull('applicant_id')
            ->whereIn('type', ['confirmation-letter', 'demand-letter'])
            ->orderByDesc('version')
            ->orderByDesc('id')
            ->get()
            ->unique('type')
            ->keyBy('type');

        return view('tokens.form', $this->formData($token) + compact('tokenDocuments'));
    }

    public function update(TokenRequest $r, Token $token)
    {
        $data = $r->safe()->except(['change_reason']);
        $changeReason = $r->validated('change_reason');
        $protected = ['company_id', 'agency_id', 'demanded_workers', 'required_visa_attestation'];
        if (! $r->user()->isSuperAdmin()) {
            $data = Arr::except($data, $protected);
        } else {
            foreach ($protected as $field) {
                if (array_key_exists($field, $data) && (string) $token->$field !== (string) $data[$field] && ! $r->filled('change_reason')) {
                    return back()->withErrors(['change_reason' => 'A reason is required when protected token fields change.'])->withInput();
                }
            }
        }

        $data['pre_selected'] = $r->boolean('pre_selected');
        $data['site_visit_required'] = $r->boolean('site_visit_required');

        // --- boesel-visa process: category-conditional field logic on update ---
        $categoryId = $data['token_category_id'] ?? $token->token_category_id;
        $category = TokenCategory::find($categoryId);
        $isVA = $category && strtoupper($category->code) === 'VA';

        if ($r->user()->isSuperAdmin()) {
            if ($isVA) {
                if (empty($data['required_visa_attestation'])) {
                    return back()->withErrors(['required_visa_attestation' => 'The required visa attestation field is required for this category.'])->withInput();
                }
                $data['demanded_workers'] = null;
            } else {
                if (empty($data['demanded_workers'])) {
                    return back()->withErrors(['demanded_workers' => 'The demanded workers field is required for this category.'])->withInput();
                }
                $data['required_visa_attestation'] = null;
            }
        }

        $old = $token->toArray();
        DB::transaction(function () use ($r, $token, $data, $old, $changeReason) {
            $previousHolder = $token->current_holder_id;
            $token->update($data + ['updated_by' => $r->user()->id]);
            if ((string) $previousHolder !== (string) $token->current_holder_id) {
                TokenTransferHistory::create(['token_id' => $token->id, 'previous_holder_id' => $previousHolder, 'new_holder_id' => $token->current_holder_id, 'transferred_by' => $r->user()->id, 'transferred_at' => now(), 'remarks' => $changeReason]);
            }
            AuditService::record('update', 'tokens', $token, $old, $token->fresh()->toArray(), $changeReason);
        });

        return redirect()->route('tokens.edit', $token)->with('success', 'Token updated and transfer history recorded.');
    }

    public function pdf(Token $token)
    {
        $token->load(['company', 'agency', 'category', 'currentHolder']);
        AuditService::record('download-pdf', 'tokens', $token);

        return Pdf::loadView('pdf.token', compact('token'))->setPaper('a4')->download($token->token_number.'.pdf');
    }

    public function cancel(Request $r, Token $token)
    {
        abort_unless($r->user()->isSuperAdmin(), 403);
        $data = $r->validate(['reason' => 'required|max:1000']);
        $old = $token->toArray();
        $token->update(['file_status' => 'cancelled', 'cancelled_at' => now(), 'updated_by' => $r->user()->id]);
        AuditService::record('cancel', 'tokens', $token, $old, $token->fresh()->toArray(), $data['reason']);

        return back()->with('success', 'Token cancelled and retained for audit.');
    }

    private function formData(Token $token): array
    {
        return ['token' => $token, 'companies' => Company::where('is_active', true)->orderBy('name')->get(), 'agencies' => Agency::where('is_active', true)->orderBy('name')->get(), 'categories' => TokenCategory::where('is_active', true)->orderBy('display_order')->get(), 'users' => User::with('role')->where('is_active', true)->orderBy('name')->get()];
    }

    /**
     * Generate the next sequential token number following the boesel-visa process:
     * VA categories get a "VA-" prefix; all others get "BHC-".
     * Format: {PREFIX}{YYMM}-{NNNNN}  e.g. BHC-2608-00001 | VA-2608-00001
     */
    private function nextNumber(?TokenCategory $category = null, bool $isVA = false): string
    {
        $prefix = ($isVA ? 'VA-' : 'BHC-').now()->format('ym').'-';
        $last = Token::withTrashed()->where('token_number', 'like', $prefix.'%')->lockForUpdate()->orderByDesc('id')->value('token_number');
        $seq = $last ? ((int) substr($last, -5) + 1) : 1;

        return $prefix.str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
    }
}
