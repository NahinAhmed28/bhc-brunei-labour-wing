@extends('layouts.app')

@section('title', $token->token_number)

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
        <div>
            <a class="small text-decoration-none" href="{{ route('tokens.index') }}"><i class="bi bi-arrow-left me-1"></i>Token Submissions</a>
            <div class="page-eyebrow mt-3">Token record</div>
            <h1 class="page-title">{{ $token->token_number }}</h1>
            <p class="page-lead mb-0">{{ $token->company->name }} &middot; {{ $token->agency->name }}</p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-primary" href="{{ route('tokens.pdf', $token) }}" target="_blank" rel="noopener"><i class="bi bi-file-pdf me-2"></i>View PDF</a>
            <a class="btn btn-outline-primary" href="{{ route('tokens.pdf', [$token, 'download' => 1]) }}"><i class="bi bi-download me-2" aria-hidden="true"></i>Download PDF</a>
            @if(auth()->user()->hasAnyRole('super-admin', 'administrator'))
                <a class="btn btn-primary" href="{{ route('tokens.edit', $token) }}">Edit</a>
            @endif
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card mb-4">
                <div class="card-header bg-white p-4 d-flex justify-content-between">
                    <h2 class="section-title mb-0">Submission details</h2>
                    <span class="status {{ $token->file_status === 'active' ? 'status-success' : ($token->file_status === 'cancelled' ? 'status-danger' : 'status-warning') }}">{{ ucwords(str_replace('-', ' ', $token->file_status)) }}</span>
                </div>
                <div class="card-body p-4">
                    <div class="row g-4">
                        @foreach([
                            ['Token Category', $token->category->name],
                            ['Company', $token->company->name],
                            ['Agency', $token->agency->name],
                            ['Received On', $token->received_on->format('d M Y')],
                            [$token->isVA() ? 'Required Visa Attestations' : ($token->isChangePreWorker() ? 'Workers Requiring Change' : 'Demanded Workers'), $token->required_visa_attestation ?? $token->required_worker_changes ?? $token->demanded_workers],
                            ['Approved Workers', $token->approved_workers],
                            ['BHC No.', $token->bhc_number ?: 'Not assigned'],
                            ['Send to BOESL', ucfirst($token->boesl_status)],
                            ['File Holder', $token->currentHolder?->name ?: 'Unassigned'],
                            ['Pre Selected', $token->pre_selected ? 'Yes' : 'No'],
                            ['Agent', $token->agent_name ?: '—'],
                            ['Created By', $token->creator->name],
                        ] as [$label, $value])
                            <div class="col-sm-6 col-lg-4"><div class="detail-label">{{ $label }}</div><div class="detail-value">{{ $value }}</div></div>
                        @endforeach
                    </div>
                    @if($token->remarks)
                        <hr><div class="detail-label">Remarks</div><p class="mb-0">{{ $token->remarks }}</p>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-white p-4 d-flex justify-content-between align-items-center">
                    <div><h2 class="section-title mb-0">Attached workers</h2><small class="text-secondary">{{ $token->workers->count() }} of {{ $token->approved_workers ?: ($token->demanded_workers ?? $token->required_visa_attestation ?? $token->required_worker_changes ?? 'unlimited') }} permitted</small></div>
                    @if(auth()->user()->hasAnyRole('super-admin', 'administrator', 'data-entry'))
                        <a class="btn btn-sm btn-primary" href="{{ route('workers.create', ['token_id' => $token->id]) }}">Add worker</a>
                    @endif
                </div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead><tr><th class="ps-4">Worker</th><th>Passport</th><th>Registration</th><th>Visa</th><th class="pe-4">Action</th></tr></thead>
                        <tbody>
                            @forelse($token->workers as $worker)
                                <tr><td class="ps-4"><strong>{{ $worker->full_name }}</strong></td><td>{{ $worker->passport_number }}</td><td>{{ $worker->registration_number ?: '—' }}</td><td><span class="status">{{ ucfirst($worker->visa_status) }}</span></td><td class="pe-4"><a class="btn btn-sm btn-light" href="{{ route('workers.show', $worker) }}">View</a></td></tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-secondary py-4">No workers linked yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card mb-4">
                <div class="card-header bg-white p-4"><h2 class="section-title mb-0">File Transfer History</h2></div>
                <div class="card-body p-4">
                    <div class="timeline">
                        @forelse($token->transferHistories as $history)
                            <div class="timeline-item">
                                <strong>{{ $history->previousHolder?->name ?: 'Unassigned' }} → {{ $history->newHolder?->name ?: 'Unassigned' }}</strong>
                                <div class="small text-secondary">{{ $history->transferred_at->format('d M Y, H:i') }}</div>
                                <div class="small">Transferred by {{ $history->transferredBy->name }}</div>
                                @if($history->remarks)<div class="small mt-1">{{ $history->remarks }}</div>@endif
                            </div>
                        @empty
                            <p class="text-secondary mb-0">No file transfer has been recorded.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            @if(auth()->user()->isSuperAdmin() && $token->file_status !== 'cancelled')
                <div class="card border-danger-subtle">
                    <div class="card-body">
                        <h2 class="section-title text-danger">Cancel token</h2>
                        <p class="small text-secondary">The token remains searchable and its history is preserved.</p>
                        <form method="post" action="{{ route('tokens.cancel', $token) }}" data-confirm="Cancel this token?">
                            @csrf
                            <input class="form-control mb-2" name="reason" placeholder="Reason for cancellation" required>
                            <button class="btn btn-outline-danger w-100">Cancel token</button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
