<div class="modal-header token-modal-header">
    <div>
        <div class="page-eyebrow">Applicant roster</div>
        <h2 class="modal-title" id="tokenDetailsModalLabel">{{ $token->token_number }}</h2>
        <p class="mb-0">{{ $token->company->name }} &middot; {{ $token->agency->name }}</p>
    </div>
    <button class="btn-close btn-close-white" type="button" data-bs-dismiss="modal" aria-label="Close applicant list"></button>
</div>

<div class="modal-body token-modal-body">
    <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
        <div>
            <h3 class="token-modal-section-title mb-1">Applicants</h3>
            <p class="small text-secondary mb-0">Select an applicant name to open their details.</p>
        </div>
        <span class="token-modal-count">{{ $token->applicants->count() }}</span>
    </div>

    @forelse($token->applicants as $applicant)
        <div class="token-related-row">
            <div>
                <a class="fw-semibold text-decoration-none" href="{{ route('applicants.show', $applicant) }}">
                    {{ $applicant->full_name }}
                </a>
                <div class="small text-secondary">
                    Passport {{ $applicant->passport_number }}
                    @if($applicant->registration_number)
                        &middot; Registration {{ $applicant->registration_number }}
                    @endif
                </div>
            </div>
            <span class="status {{ $applicant->tracking_status === 'complete' ? 'status-success' : 'status-warning' }}">
                {{ ucwords(str_replace('-', ' ', $applicant->tracking_status)) }}
            </span>
        </div>
    @empty
        <div class="text-center text-secondary py-5">
            <i class="bi bi-people fs-2 d-block mb-2" aria-hidden="true"></i>
            No applicants are attached to this token.
        </div>
    @endforelse
</div>

<div class="modal-footer bg-white">
    <button class="btn btn-light" type="button" data-bs-dismiss="modal">Close</button>
    @if(auth()->user()->hasAnyRole('super-admin', 'administrator', 'data-entry'))
        <a class="btn btn-primary" href="{{ route('applicants.create', ['token_id' => $token->id]) }}">
            <i class="bi bi-person-plus me-1" aria-hidden="true"></i>Add applicant
        </a>
    @endif
</div>
