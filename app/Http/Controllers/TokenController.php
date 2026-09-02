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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TokenController extends Controller
{
    public function index(Request $r)
    {
        $tokens = Token::with(['company', 'agency', 'category', 'currentHolder.role'])->withCount('workers')->when($r->q, function ($q, $v) {
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
        $requestedTokenNumber = trim((string) ($data['token_number'] ?? ''));
        unset($data['token_number']);
        $data['site_visit_required'] = $r->boolean('site_visit_required');
        $category = TokenCategory::findOrFail($data['token_category_id']);
        $isVA = $category->isVisaAttestation();
        $isChangePreWorker = $category->isChangePreWorker();
        $data['pre_selected'] = $category->isDemandLetterSubmission() && $r->boolean('pre_selected');
        $data['demanded_workers'] = $category->isDemandLetterSubmission() ? $data['demanded_workers'] : null;
        $data['required_visa_attestation'] = $isVA ? $data['required_visa_attestation'] : null;
        $data['required_worker_changes'] = $isChangePreWorker ? $data['required_worker_changes'] : null;

        $token = DB::transaction(function () use ($r, $data, $category, $isVA, $requestedTokenNumber) {
            $data['token_number'] = $requestedTokenNumber !== ''
                ? $requestedTokenNumber
                : $this->nextNumber($category, $isVA);
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
        $token->load(['company', 'agency', 'category', 'currentHolder.role', 'creator', 'workers', 'transferHistories.previousHolder', 'transferHistories.newHolder', 'transferHistories.transferredBy', 'documents']);

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
            'workers',
            'transferHistories.previousHolder',
            'transferHistories.newHolder',
            'transferHistories.transferredBy',
            'documents' => fn ($query) => $query
                ->whereNull('worker_id')
                ->whereIn('type', ['confirmation-letter', 'demand-letter'])
                ->orderByDesc('version')
                ->orderByDesc('id'),
        ]);

        $tokenDocuments = $this->latestTokenDocumentCollections($token->documents);

        return view('tokens.modal', compact('token', 'tokenDocuments'));
    }

    public function workersModal(Token $token): View
    {
        $token->load([
            'company',
            'agency',
            'workers' => fn ($query) => $query->orderBy('full_name'),
        ]);

        return view('tokens.workers-modal', compact('token'));
    }

    public function edit(Token $token)
    {
        $token->load(['transferHistories.previousHolder', 'transferHistories.newHolder', 'transferHistories.transferredBy']);
        $tokenDocuments = $this->latestTokenDocumentCollections($token->documents()
            ->whereNull('worker_id')
            ->whereIn('type', ['confirmation-letter', 'demand-letter'])
            ->orderByDesc('version')
            ->orderByDesc('id')
            ->get());

        return view('tokens.form', $this->formData($token) + compact('tokenDocuments'));
    }

    public function update(TokenRequest $r, Token $token)
    {
        $data = $r->safe()->except(['change_reason']);
        if (trim((string) ($data['token_number'] ?? '')) === '') {
            unset($data['token_number']);
        }
        $changeReason = $r->validated('change_reason');
        $protected = ['token_number', 'company_id', 'agency_id', 'demanded_workers', 'required_visa_attestation', 'required_worker_changes'];
        if (! $r->user()->isSuperAdmin()) {
            $data = Arr::except($data, $protected);
        } else {
            foreach ($protected as $field) {
                if (array_key_exists($field, $data) && (string) $token->$field !== (string) $data[$field] && ! $r->filled('change_reason')) {
                    return back()->withErrors(['change_reason' => 'A reason is required when protected token fields change.'])->withInput();
                }
            }
        }

        $data['site_visit_required'] = $r->boolean('site_visit_required');

        $categoryId = $data['token_category_id'] ?? $token->token_category_id;
        $category = TokenCategory::findOrFail($categoryId);
        $isVA = $category->isVisaAttestation();
        $isChangePreWorker = $category->isChangePreWorker();

        if ($r->user()->isSuperAdmin()) {
            $data['demanded_workers'] = $category->isDemandLetterSubmission() ? $data['demanded_workers'] : null;
            $data['required_visa_attestation'] = $isVA ? $data['required_visa_attestation'] : null;
            $data['required_worker_changes'] = $isChangePreWorker ? $data['required_worker_changes'] : null;
        }

        $data['pre_selected'] = $category->isDemandLetterSubmission() && $r->boolean('pre_selected');

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

    public function pdf(Request $request, Token $token)
    {
        $token->load(['company', 'agency', 'category', 'currentHolder']);
        $shouldDownload = $request->boolean('download');
        AuditService::record($shouldDownload ? 'download-pdf' : 'view-pdf', 'tokens', $token);
        $safeTokenNumber = (string) Str::of($token->token_number)
            ->replaceMatches('/[^A-Za-z0-9._-]+/', '-')
            ->trim('-');
        $filename = ($safeTokenNumber !== '' ? $safeTokenNumber : 'token-'.$token->id).'.pdf';
        $pdf = Pdf::loadView('pdf.token', compact('token'))->setPaper('a4');

        return $shouldDownload ? $pdf->download($filename) : $pdf->stream($filename);
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
        $categories = TokenCategory::query()
            ->where(fn ($query) => $query->where('is_active', true)->when(
                $token->token_category_id,
                fn ($categoryQuery, $categoryId) => $categoryQuery->orWhereKey($categoryId),
            ))
            ->orderBy('display_order')
            ->get();

        return ['token' => $token, 'companies' => Company::where('is_active', true)->orderBy('name')->get(), 'agencies' => Agency::where('is_active', true)->orderBy('name')->get(), 'categories' => $categories, 'users' => User::with('role')->where('is_active', true)->orderBy('name')->get()];
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

    private function latestTokenDocumentCollections(Collection $documents): Collection
    {
        return $documents
            ->groupBy('type')
            ->map(fn (Collection $typeDocuments) => $typeDocuments
                ->unique('collection_key')
                ->values());
    }
}
