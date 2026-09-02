<?php

namespace App\Http\Controllers;

use App\Http\Requests\WorkerRequest;
use App\Models\Token;
use App\Models\Worker;
use App\Models\WorkerStatusHistory;
use App\Services\AuditService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkerController extends Controller
{
    public function index(Request $r)
    {
        $search = trim((string) $r->query('q', ''));
        $normalizedReference = preg_replace('/[^A-Za-z0-9]/', '', $search) ?? '';

        $workers = Worker::with('token.company', 'token.agency')->when($search !== '', function ($query) use ($normalizedReference, $search) {
            $query->where(function ($workerQuery) use ($normalizedReference, $search) {
                $workerQuery
                    ->where('full_name', 'like', "%{$search}%")
                    ->orWhere('passport_number', 'like', "%{$search}%")
                    ->orWhere('registration_number', 'like', "%{$search}%")
                    ->orWhere('demand_reference', 'like', "%{$search}%")
                    ->orWhereHas('token', function ($tokenQuery) use ($normalizedReference, $search) {
                        $tokenQuery
                            ->where('token_number', 'like', "%{$search}%")
                            ->orWhere('bhc_number', 'like', "%{$search}%")
                            ->when($normalizedReference !== '', function ($referenceQuery) use ($normalizedReference) {
                                $referenceQuery
                                    ->orWhereRaw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(token_number, '-', ''), '/', ''), ' ', ''), '.', ''), '_', '') LIKE ?", ["%{$normalizedReference}%"])
                                    ->orWhereRaw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(bhc_number, '-', ''), '/', ''), ' ', ''), '.', ''), '_', '') LIKE ?", ["%{$normalizedReference}%"]);
                            });
                    });
            });
        })->when($r->visa_status, fn ($q, $v) => $q->where('visa_status', $v))->when($r->flight_status, fn ($q, $v) => $q->where('flight_status', $v))->when($r->insurance_status, fn ($q, $v) => $q->where('insurance_status', $v))->when($r->ic_status, fn ($q, $v) => $q->where('ic_status', $v))->latest()->paginate((int) $r->input('per_page', 15))->withQueryString();

        return view('workers.index', compact('workers'));
    }

    public function create(Request $r)
    {
        $worker = new Worker(['token_id' => $r->token_id, 'nationality' => 'Bangladeshi', 'tracking_status' => 'pending', 'visa_status' => 'pending', 'flight_status' => 'pending', 'insurance_status' => 'pending', 'ic_status' => 'pending', 'medical_status' => 'pending', 'boesl_status' => 'pending']);

        return view('workers.form', ['worker' => $worker, 'tokens' => Token::with(['company', 'agency'])->withCount('workers')->where('file_status', '!=', 'cancelled')->latest()->get()]);
    }

    public function store(WorkerRequest $r)
    {
        $token = Token::findOrFail($r->token_id);
        $limit = $token->approved_workers ?: ($token->demanded_workers ?? $token->required_visa_attestation ?? $token->required_worker_changes);
        if ($limit !== null && $token->workers()->count() >= $limit && ! ($r->user()->isSuperAdmin() && $r->boolean('override_limit'))) {
            return back()->withErrors(['token_id' => 'This token has reached its approved worker limit.'])->withInput();
        }

        $data = $r->validated();
        $data['pre_selected'] = $r->boolean('pre_selected');
        $worker = Worker::create($data + ['created_by' => $r->user()->id, 'updated_by' => $r->user()->id]);
        AuditService::record('create', 'workers', $worker, [], $worker->toArray(), $r->input('override_reason'));

        return redirect()->route('workers.show', $worker)->with('success', 'Worker linked to token.');
    }

    public function show(Worker $worker)
    {
        $worker->load(['token.company', 'token.agency', 'documents', 'statusHistories.user']);

        return view('workers.show', compact('worker'));
    }

    public function edit(Worker $worker)
    {
        return view('workers.form', ['worker' => $worker, 'tokens' => Token::with(['company', 'agency'])->withCount('workers')->where('file_status', '!=', 'cancelled')->latest()->get()]);
    }

    public function update(WorkerRequest $r, Worker $worker)
    {
        $data = $r->validated();
        if (! $r->user()->isSuperAdmin()) {
            unset($data['token_id']);
        }

        $data['pre_selected'] = $r->boolean('pre_selected');
        $old = $worker->toArray();
        DB::transaction(function () use ($r, $worker, $data, $old) {
            $worker->update($data + ['updated_by' => $r->user()->id]);
            foreach (['tracking_status', 'visa_status', 'flight_status', 'insurance_status', 'ic_status', 'medical_status', 'boesl_status'] as $field) {
                if (($old[$field] ?? null) !== $worker->$field) {
                    WorkerStatusHistory::create(['worker_id' => $worker->id, 'field' => $field, 'previous_value' => $old[$field] ?? null, 'new_value' => $worker->$field, 'changed_by' => $r->user()->id]);
                }
            }

            AuditService::record('update', 'workers', $worker, $old, $worker->fresh()->toArray());
        });

        return redirect()->route('workers.show', $worker)->with('success', 'Worker details updated.');
    }

    public function letter(Worker $worker, string $type)
    {
        abort_unless(in_array($type, ['d1', 'confirmation']), 404);
        $worker->load('token.company', 'token.agency');
        AuditService::record('generate-letter', 'workers', $worker, [], ['type' => $type]);

        return Pdf::loadView('pdf.letter', compact('worker', 'type'))->setPaper('a4')->download(strtoupper($type).'-'.$worker->passport_number.'.pdf');
    }
}
