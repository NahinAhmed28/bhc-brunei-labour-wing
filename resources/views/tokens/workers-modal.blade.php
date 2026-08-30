<div class="modal-header token-modal-header">
    <div>
        <div class="page-eyebrow">Worker roster</div>
        <h2 class="modal-title" id="tokenDetailsModalLabel">{{ $token->token_number }}</h2>
        <p class="mb-0">{{ $token->company->name }} &middot; {{ $token->agency->name }}</p>
    </div>
    <button class="btn-close btn-close-white" type="button" data-bs-dismiss="modal" aria-label="Close worker list"></button>
</div>

<div class="modal-body token-modal-body">
    <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
        <div>
            <h3 class="token-modal-section-title mb-1">Workers</h3>
            <p class="small text-secondary mb-0">Select a worker name to open their details.</p>
        </div>
        <span class="token-modal-count">{{ $token->workers->count() }}</span>
    </div>

    @forelse($token->workers as $worker)
        <div class="token-related-row">
            <div>
                <a class="fw-semibold text-decoration-none" href="{{ route('workers.show', $worker) }}">
                    {{ $worker->full_name }}
                </a>
                <div class="small text-secondary">
                    Passport {{ $worker->passport_number }}
                    @if($worker->registration_number)
                        &middot; Registration {{ $worker->registration_number }}
                    @endif
                </div>
            </div>
            <span class="status {{ $worker->tracking_status === 'complete' ? 'status-success' : 'status-warning' }}">
                {{ ucwords(str_replace('-', ' ', $worker->tracking_status)) }}
            </span>
        </div>
    @empty
        <div class="text-center text-secondary py-5">
            <i class="bi bi-people fs-2 d-block mb-2" aria-hidden="true"></i>
            No workers are attached to this token.
        </div>
    @endforelse
</div>

<div class="modal-footer bg-white">
    <button class="btn btn-light" type="button" data-bs-dismiss="modal">Close</button>
    @if(auth()->user()->hasAnyRole('super-admin', 'administrator', 'data-entry'))
        <a class="btn btn-primary" href="{{ route('workers.create', ['token_id' => $token->id]) }}">
            <i class="bi bi-person-plus me-1" aria-hidden="true"></i>Add worker
        </a>
    @endif
</div>
