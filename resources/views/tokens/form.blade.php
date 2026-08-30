@extends('layouts.app')
@section('title', $token->exists ? 'Edit Token Submission' : 'Create Token')
@section('content')

<div class="mb-4">
    <a class="small text-decoration-none" href="{{ $token->exists ? route('tokens.show', $token) : route('tokens.index') }}">
        <i class="bi bi-arrow-left me-1"></i>Back
    </a>
    <div class="page-eyebrow mt-3">{{ $token->exists ? $token->token_number : 'New controlled record' }}</div>
    <h1 class="page-title">{{ $token->exists ? 'Edit Token Submission' : 'Create Token' }}</h1>
    <p class="page-lead">Protected company, agency and demand values can only be changed by a Super Administrator and require a reason.</p>
</div>

@php
    $vaCategoryIds = $categories->filter(fn($c) => strtoupper($c->code) === 'VA')->pluck('id')->values();
    $currentCategoryIsVA = $token->exists && $vaCategoryIds->contains($token->token_category_id);
@endphp

<form method="post" action="{{ $token->exists ? route('tokens.update', $token) : route('tokens.store') }}">
    @csrf
    @if($token->exists) @method('put') @endif

    <div class="card mb-4">
        <div class="card-header bg-white p-4">
            <h2 class="section-title mb-0">Core submission</h2>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">

                <div class="col-md-4">
                    <label class="form-label">Company Name *</label>
                    <select class="form-select" name="company_id" required
                        @disabled($token->exists && !auth()->user()->isSuperAdmin())>
                        <option value="">Select Company</option>
                        @foreach($companies as $x)
                            <option value="{{ $x->id }}" @selected(old('company_id', $token->company_id) == $x->id)>{{ $x->name }}</option>
                        @endforeach
                    </select>
                    @if($token->exists && !auth()->user()->isSuperAdmin())
                        <input type="hidden" name="company_id" value="{{ $token->company_id }}">
                    @endif
                    @error('company_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">Agency Name *</label>
                    <select class="form-select" name="agency_id" required
                        @disabled($token->exists && !auth()->user()->isSuperAdmin())>
                        <option value="">Select Agency</option>
                        @foreach($agencies as $x)
                            <option value="{{ $x->id }}" @selected(old('agency_id', $token->agency_id) == $x->id)>{{ $x->name }}</option>
                        @endforeach
                    </select>
                    @if($token->exists && !auth()->user()->isSuperAdmin())
                        <input type="hidden" name="agency_id" value="{{ $token->agency_id }}">
                    @endif
                    @error('agency_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">Category *</label>
                    <select class="form-select" name="token_category_id" id="tokenCategorySelect" required>
                        <option value="">Select Category</option>
                        @foreach($categories as $x)
                            <option value="{{ $x->id }}"
                                data-is-va="{{ strtoupper($x->code) === 'VA' ? '1' : '0' }}"
                                @selected(old('token_category_id', $token->token_category_id) == $x->id)>
                                {{ $x->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('token_category_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                {{-- ── boesel-visa process: Demanded Workers (DL categories) ── --}}
                <div class="col-md-4" id="demandedWorkersGroup" @if($currentCategoryIsVA) style="display:none" @endif>
                    <label class="form-label">Demanded Workers *</label>
                    <input class="form-control" type="number" min="1" name="demanded_workers"
                           id="demandedWorkersInput"
                           value="{{ old('demanded_workers', $token->demanded_workers) }}"
                           @readonly($token->exists && !auth()->user()->isSuperAdmin())>
                    @error('demanded_workers') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                {{-- ── boesel-visa process: Required Visa Attestation (VA categories) ── --}}
                <div class="col-md-4" id="visaAttestationGroup" @if(!$currentCategoryIsVA) style="display:none" @endif>
                    <label class="form-label">Required Visa Attestation *</label>
                    <input class="form-control" type="number" min="1" name="required_visa_attestation"
                           id="visaAttestationInput"
                           value="{{ old('required_visa_attestation', $token->required_visa_attestation) }}"
                           @readonly($token->exists && !auth()->user()->isSuperAdmin())>
                    @error('required_visa_attestation') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">Received On *</label>
                    <input class="form-control" type="date" name="received_on"
                           value="{{ old('received_on', optional($token->received_on)->format('Y-m-d')) }}" required>
                    @error('received_on') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">Agent / Representative</label>
                    <input class="form-control" name="agent_name" value="{{ old('agent_name', $token->agent_name) }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Amount</label>
                    <input class="form-control" type="number" step="0.01" min="0" name="amount"
                           value="{{ old('amount', $token->amount) }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Receipt Number</label>
                    <input class="form-control" name="receipt_number" value="{{ old('receipt_number', $token->receipt_number) }}">
                </div>

                <div class="col-md-4 d-flex align-items-end pb-2">
                    <div class="form-check form-switch">
                        <input type="hidden" name="pre_selected" value="0">
                        <input class="form-check-input" type="checkbox" name="pre_selected" value="1"
                               id="pre" @checked(old('pre_selected', $token->pre_selected))>
                        <label class="form-check-label" for="pre">Pre Selected Applicant</label>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-white p-4">
            <h2 class="section-title mb-0">Processing and desk</h2>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">

                <div class="col-md-4">
                    <label class="form-label">BHC No.</label>
                    <input class="form-control" name="bhc_number" value="{{ old('bhc_number', $token->bhc_number) }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Approved Workers</label>
                    <input class="form-control" type="number" min="0" name="approved_workers"
                           value="{{ old('approved_workers', $token->approved_workers ?? 0) }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Received By</label>
                    <input class="form-control" value="{{ $token->received_by ?: 'Not recorded' }}" disabled>
                    <div class="form-text">This imported record value cannot be changed.</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Send to BOESL</label>
                    <select class="form-select" name="boesl_status">
                        @foreach(['pending', 'submitted', 'returned', 'not-required'] as $x)
                            <option value="{{ $x }}" @selected(old('boesl_status', $token->boesl_status) == $x)>
                                {{ ucwords(str_replace('-', ' ', $x)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">BOESL Date</label>
                    <input class="form-control" type="date" name="boesl_date"
                           value="{{ old('boesl_date', optional($token->boesl_date)->format('Y-m-d')) }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Desk Status</label>
                    <select class="form-select" name="current_desk_id">
                        <option value="">Unassigned</option>
                        @foreach($desks as $x)
                            <option value="{{ $x->id }}" @selected(old('current_desk_id', $token->current_desk_id) == $x->id)>
                                {{ $x->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Visa Processing</label>
                    <select class="form-select" name="visa_status">
                        @foreach(['pending', 'processing', 'received', 'rejected'] as $x)
                            <option value="{{ $x }}" @selected(old('visa_status', $token->visa_status) == $x)>
                                {{ ucfirst($x) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">File Status</label>
                    <select class="form-select" name="file_status">
                        @foreach(['active', 'on-hold', 'completed', 'cancelled'] as $x)
                            <option value="{{ $x }}" @selected(old('file_status', $token->file_status) == $x)>
                                {{ ucwords(str_replace('-', ' ', $x)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">BOESL / Desk Change Reason</label>
                    <input class="form-control" name="change_reason" value="{{ old('change_reason') }}"
                           placeholder="Required for protected changes">
                </div>

                <div class="col-12">
                    <label class="form-label">Remarks</label>
                    <textarea class="form-control" rows="3" name="remarks">{{ old('remarks', $token->remarks) }}</textarea>
                </div>

            </div>
        </div>
        <div class="card-footer bg-white p-3 text-end">
            <a class="btn btn-light" href="{{ route('tokens.index') }}">Cancel</a>
            <button class="btn btn-primary ms-2">{{ $token->exists ? 'Save changes' : 'Create Token' }}</button>
        </div>
    </div>
</form>

@if($token->exists)
    <div class="card">
        <div class="card-header bg-white p-4">
            <h2 class="section-title mb-0">Desk Status History</h2>
        </div>
        <div class="card-body">
            <div class="timeline">
                @forelse($token->deskHistories()->with(['newDesk','user'])->get() as $history)
                    <div class="timeline-item">
                        <strong>{{ $history->newDesk->name }}</strong>
                        <div class="small text-secondary">
                            {{ $history->arrived_at->format('d M Y, H:i') }} · {{ $history->user->name }}
                        </div>
                        <div class="small">{{ $history->remarks }}</div>
                    </div>
                @empty
                    <p class="text-secondary">No desk movement recorded.</p>
                @endforelse
            </div>
        </div>
    </div>
@endif

@push('scripts')
<script>
(function () {
    var categorySelect = document.getElementById('tokenCategorySelect');
    var dlGroup        = document.getElementById('demandedWorkersGroup');
    var vaGroup        = document.getElementById('visaAttestationGroup');
    var dlInput        = document.getElementById('demandedWorkersInput');
    var vaInput        = document.getElementById('visaAttestationInput');

    function applyCategory() {
        var opt   = categorySelect.options[categorySelect.selectedIndex];
        var isVA  = opt && opt.getAttribute('data-is-va') === '1';

        if (isVA) {
            dlGroup.style.display = 'none';
            vaGroup.style.display = '';
            dlInput.removeAttribute('required');
            dlInput.value = '';
            vaInput.setAttribute('required', '');
        } else {
            vaGroup.style.display = 'none';
            dlGroup.style.display = '';
            vaInput.removeAttribute('required');
            vaInput.value = '';
            dlInput.setAttribute('required', '');
        }
    }

    if (categorySelect) {
        categorySelect.addEventListener('change', applyCategory);
        applyCategory();
    }
})();
</script>
@endpush

@endsection
