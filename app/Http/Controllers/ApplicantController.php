<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApplicantRequest;
use App\Models\Applicant;
use App\Models\ApplicantStatusHistory;
use App\Models\Token;
use App\Services\AuditService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApplicantController extends Controller
{
    public function index(Request $r)
    {
        $applicants = Applicant::with('token.company', 'token.agency')->when($r->q, function ($q, $v) {
            $q->where(function ($s) use ($v) {
                $s->where('full_name', 'like', "%$v%")->orWhere('passport_number', 'like', "%$v%")->orWhere('registration_number', 'like', "%$v%")->orWhereHas('token', fn ($x) => $x->where('token_number', 'like', "%$v%")->orWhere('bhc_number', 'like', "%$v%"));
            });
        })->when($r->visa_status, fn ($q, $v) => $q->where('visa_status', $v))->when($r->flight_status, fn ($q, $v) => $q->where('flight_status', $v))->when($r->insurance_status, fn ($q, $v) => $q->where('insurance_status', $v))->when($r->ic_status, fn ($q, $v) => $q->where('ic_status', $v))->latest()->paginate((int) $r->input('per_page', 15))->withQueryString();

        return view('applicants.index', compact('applicants'));
    }

    public function create(Request $r)
    {
        $applicant = new Applicant(['token_id' => $r->token_id, 'nationality' => 'Bangladeshi', 'tracking_status' => 'pending', 'visa_status' => 'pending', 'flight_status' => 'pending', 'insurance_status' => 'pending', 'ic_status' => 'pending', 'medical_status' => 'pending', 'boesl_status' => 'pending']);

        return view('applicants.form', ['applicant' => $applicant, 'tokens' => Token::with(['company', 'agency'])->where('file_status', '!=', 'cancelled')->latest()->get()]);
    }

    public function store(ApplicantRequest $r)
    {
        $token = Token::findOrFail($r->token_id);
        $limit = $token->approved_workers ?: $token->demanded_workers;
        if ($token->applicants()->count() >= $limit && ! ($r->user()->isSuperAdmin() && $r->boolean('override_limit'))) {
            return back()->withErrors(['token_id' => 'This token has reached its approved worker limit.'])->withInput();
        }$data = $r->validated();
        $data['pre_selected'] = $r->boolean('pre_selected');
        $applicant = Applicant::create($data + ['created_by' => $r->user()->id, 'updated_by' => $r->user()->id]);
        AuditService::record('create', 'applicants', $applicant, [], $applicant->toArray(), $r->input('override_reason'));

        return redirect()->route('applicants.show', $applicant)->with('success', 'Applicant linked to token.');
    }

    public function show(Applicant $applicant)
    {
        $applicant->load(['token.company', 'token.agency', 'documents', 'statusHistories.user']);

        return view('applicants.show', compact('applicant'));
    }

    public function edit(Applicant $applicant)
    {
        return view('applicants.form', ['applicant' => $applicant, 'tokens' => Token::with(['company', 'agency'])->where('file_status', '!=', 'cancelled')->latest()->get()]);
    }

    public function update(ApplicantRequest $r, Applicant $applicant)
    {
        $data = $r->validated();
        if (! $r->user()->isSuperAdmin()) {
            unset($data['token_id']);
        }$data['pre_selected'] = $r->boolean('pre_selected');
        $old = $applicant->toArray();
        DB::transaction(function () use ($r, $applicant, $data, $old) {
            $applicant->update($data + ['updated_by' => $r->user()->id]);
            foreach (['tracking_status', 'visa_status', 'flight_status', 'insurance_status', 'ic_status', 'medical_status', 'boesl_status'] as $field) {
                if (($old[$field] ?? null) !== $applicant->$field) {
                    ApplicantStatusHistory::create(['applicant_id' => $applicant->id, 'field' => $field, 'previous_value' => $old[$field] ?? null, 'new_value' => $applicant->$field, 'changed_by' => $r->user()->id]);
                }
            }AuditService::record('update', 'applicants', $applicant, $old, $applicant->fresh()->toArray());
        });

        return redirect()->route('applicants.show', $applicant)->with('success', 'Applicant details updated.');
    }

    public function letter(Applicant $applicant, string $type)
    {
        abort_unless(in_array($type, ['d1', 'confirmation']), 404);
        $applicant->load('token.company', 'token.agency');
        AuditService::record('generate-letter', 'applicants', $applicant, [], ['type' => $type]);

        return Pdf::loadView('pdf.letter',compact('applicant','type'))->setPaper('a4')->download(strtoupper($type).'-'.$applicant->passport_number.'.pdf');
    }
}
