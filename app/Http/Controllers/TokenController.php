<?php

namespace App\Http\Controllers;

use App\Http\Requests\TokenRequest;
use App\Models\Agency;
use App\Models\Company;
use App\Models\Desk;
use App\Models\Token;
use App\Models\TokenCategory;
use App\Models\TokenDeskHistory;
use App\Services\AuditService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class TokenController extends Controller
{
    public function index(Request $r)
    {
        $tokens = Token::with(['company', 'agency', 'category', 'currentDesk'])->withCount('applicants')->when($r->q, function ($q, $v) {
            $q->where(function ($s) use ($v) {
                $s->where('token_number', 'like', "%$v%")->orWhere('bhc_number', 'like', "%$v%")->orWhereHas('company', fn ($x) => $x->where('name', 'like', "%$v%"))->orWhereHas('agency', fn ($x) => $x->where('name', 'like', "%$v%"));
            });
        })->when($r->company_id, fn ($q, $v) => $q->where('company_id', $v))->when($r->agency_id, fn ($q, $v) => $q->where('agency_id', $v))->when($r->category_id, fn ($q, $v) => $q->where('token_category_id', $v))->when($r->desk_id, fn ($q, $v) => $q->where('current_desk_id', $v))->when($r->boesl_status, fn ($q, $v) => $q->where('boesl_status', $v))->when($r->pre_selected !== null && $r->pre_selected !== '', fn ($q) => $q->where('pre_selected', $r->boolean('pre_selected')))->latest('received_on')->paginate(15)->withQueryString();

        return view('tokens.index', ['tokens' => $tokens, 'companies' => Company::orderBy('name')->get(), 'agencies' => Agency::orderBy('name')->get(), 'categories' => TokenCategory::orderBy('name')->get(), 'desks' => Desk::orderBy('display_order')->get()]);
    }

    public function create()
    {
        return view('tokens.form', $this->formData(new Token(['received_on' => today(), 'boesl_status' => 'pending', 'visa_status' => 'pending', 'file_status' => 'active'])));
    }

    public function store(TokenRequest $r)
    {
        $data = $r->validated();
        $data['pre_selected'] = $r->boolean('pre_selected');
        $data['site_visit_required'] = $r->boolean('site_visit_required');
        $token = DB::transaction(function () use ($r, $data) {
            $data['token_number'] = $this->nextNumber();
            $data['created_by'] = $r->user()->id;
            $data['updated_by'] = $r->user()->id;
            $t = Token::create($data);
            if ($t->current_desk_id) {
                TokenDeskHistory::create(['token_id' => $t->id, 'new_desk_id' => $t->current_desk_id, 'changed_by' => $r->user()->id, 'arrived_at' => now(), 'remarks' => 'Initial desk assignment']);
            }AuditService::record('create', 'tokens', $t, [], $t->toArray());

            return $t;
        });

        return redirect()->route('tokens.show', $token)->with('success', 'Token created. Printable PDF is ready.');
    }

    public function show(Token $token)
    {
        $token->load(['company', 'agency', 'category', 'currentDesk', 'creator', 'applicants', 'deskHistories.previousDesk', 'deskHistories.newDesk', 'deskHistories.user', 'documents']);

        return view('tokens.show', compact('token'));
    }

    public function edit(Token $token)
    {
        return view('tokens.form', $this->formData($token));
    }

    public function update(TokenRequest $r, Token $token)
    {
        $data = $r->validated();
        $protected = ['company_id', 'agency_id', 'demanded_workers'];
        if (! $r->user()->isSuperAdmin()) {
            $data = Arr::except($data, $protected);
        } else {
            foreach ($protected as $field) {
                if (array_key_exists($field, $data) && (string) $token->$field !== (string) $data[$field] && ! $r->filled('change_reason')) {
                    return back()->withErrors(['change_reason' => 'A reason is required when protected token fields change.'])->withInput();
                }
            }
        }$data['pre_selected'] = $r->boolean('pre_selected');
        $data['site_visit_required'] = $r->boolean('site_visit_required');
        $old = $token->toArray();
        DB::transaction(function () use ($r, $token, $data, $old) {
            $previousDesk = $token->current_desk_id;
            $token->update($data + ['updated_by' => $r->user()->id]);
            if ((string) $previousDesk !== (string) $token->current_desk_id && $token->current_desk_id) {
                TokenDeskHistory::where('token_id', $token->id)->whereNull('departed_at')->update(['departed_at' => now()]);
                TokenDeskHistory::create(['token_id' => $token->id, 'previous_desk_id' => $previousDesk, 'new_desk_id' => $token->current_desk_id, 'changed_by' => $r->user()->id, 'arrived_at' => now(), 'remarks' => $r->input('change_reason')]);
            }AuditService::record('update', 'tokens', $token, $old, $token->fresh()->toArray(), $r->input('change_reason'));
        });

        return redirect()->route('tokens.show', $token)->with('success', 'Token updated and history recorded.');
    }

    public function pdf(Token $token)
    {
        $token->load(['company', 'agency', 'category', 'currentDesk']);
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
        return ['token' => $token, 'companies' => Company::where('is_active', true)->orderBy('name')->get(), 'agencies' => Agency::where('is_active', true)->orderBy('name')->get(), 'categories' => TokenCategory::where('is_active', true)->orderBy('display_order')->get(), 'desks' => Desk::where('is_active', true)->orderBy('display_order')->get()];
    }

    private function nextNumber(): string
    {
        $prefix = 'BHC-'.now()->format('ym').'-';
        $last = Token::withTrashed()->where('token_number','like',$prefix.'%')->lockForUpdate()->orderByDesc('id')->value('token_number');
        $seq = $last ? ((int) substr($last,-5) + 1) : 1;

        return $prefix.str_pad((string) $seq,5,'0',STR_PAD_LEFT);
    }
}
