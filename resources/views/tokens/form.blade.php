@extends('layouts.app')
@section('title', $token->exists ? 'Edit Token Submission' : 'Create Token')
@section('content')

<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div>
        <a class="small text-decoration-none" href="{{ $token->exists ? route('tokens.show', $token) : route('tokens.index') }}">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
        <div class="page-eyebrow mt-3">{{ $token->exists ? $token->token_number : 'New controlled record' }}</div>
        <h1 class="page-title">{{ $token->exists ? 'Edit Token Submission' : 'Create Token' }}</h1>
        <p class="page-lead mb-0">Protected company, agency and demand values can only be changed by a Super Administrator and require a reason.</p>
    </div>
    @if($token->exists)
        <a class="btn btn-outline-primary" href="{{ route('tokens.pdf', $token) }}" target="_blank" rel="noopener">
            <i class="bi bi-file-pdf me-2" aria-hidden="true"></i>View Token PDF
        </a>
    @endif
</div>

@php
    $selectedCategoryId = (int) old('token_category_id', $token->token_category_id);
    $selectedCategory = $categories->firstWhere('id', $selectedCategoryId);
    $currentCategoryIsDLS = $selectedCategory?->isDemandLetterSubmission() ?? false;
    $currentCategoryIsVA = $selectedCategory?->isVisaAttestation() ?? false;
    $currentCategoryIsCPA = $selectedCategory?->isChangePreWorker() ?? false;
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
                    <label class="form-label">Reference No.</label>
                    <input class="form-control" name="token_number" value="{{ old('token_number', $token->token_number) }}"
                        @readonly($token->exists && !auth()->user()->isSuperAdmin())>
                    @unless($token->exists)
                        <div class="form-text">Leave blank to generate the next reference automatically. Existing reference numbers may be reused.</div>
                    @endunless
                    @error('token_number') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

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
                                data-category-code="{{ strtoupper($x->code) }}"
                                @selected(old('token_category_id', $token->token_category_id) == $x->id)>
                                {{ $x->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('token_category_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4" id="demandedWorkersGroup" @if(!$currentCategoryIsDLS) style="display:none" @endif>
                    <label class="form-label">Demanded Workers *</label>
                    <input class="form-control" type="number" min="1" name="demanded_workers"
                           id="demandedWorkersInput"
                           value="{{ old('demanded_workers', $token->demanded_workers) }}"
                           @readonly($token->exists && !auth()->user()->isSuperAdmin())>
                    @error('demanded_workers') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4" id="visaAttestationGroup" @if(!$currentCategoryIsVA) style="display:none" @endif>
                    <label class="form-label">Required Visa Attestation *</label>
                    <input class="form-control" type="number" min="1" name="required_visa_attestation"
                           id="visaAttestationInput"
                           value="{{ old('required_visa_attestation', $token->required_visa_attestation) }}"
                           @readonly($token->exists && !auth()->user()->isSuperAdmin())>
                    @error('required_visa_attestation') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4" id="workerChangesGroup" @if(!$currentCategoryIsCPA) style="display:none" @endif>
                    <label class="form-label">Workers Requiring Change *</label>
                    <input class="form-control" type="number" min="1" name="required_worker_changes"
                           id="workerChangesInput"
                           value="{{ old('required_worker_changes', $token->required_worker_changes) }}"
                           @readonly($token->exists && !auth()->user()->isSuperAdmin())>
                    @error('required_worker_changes') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
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

                <div class="col-md-4 align-items-end pb-2" id="preSelectedGroup" @if($currentCategoryIsDLS) style="display:flex" @else style="display:none" @endif>
                    <div class="form-check form-switch">
                        <input type="hidden" name="pre_selected" value="0">
                        <input class="form-check-input" type="checkbox" name="pre_selected" value="1"
                               id="pre" @checked(old('pre_selected', $token->pre_selected))>
                        <label class="form-check-label" for="pre">Pre Selected Worker</label>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-white p-4">
            <h2 class="section-title mb-0">Processing and file assignment</h2>
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
                    <input class="form-control" value="{{ $token->exists ? ($token->received_by ?: 'Not recorded') : auth()->user()->name }}" disabled>
                    <div class="form-text">
                        {{ $token->exists ? 'This imported record value cannot be changed.' : 'Automatically set to the user creating this token.' }}
                    </div>
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
                    <label class="form-label">File Assigned To</label>
                    @if($token->exists)
                    <select class="form-select" name="current_holder_id">
                        <option value="">Unassigned</option>
                        @foreach($users as $userOption)
                            <option value="{{ $userOption->id }}" @selected(old('current_holder_id', $token->current_holder_id) == $userOption->id)>
                                {{ $userOption->name }} · {{ $userOption->role?->label ?: 'No role' }}
                            </option>
                        @endforeach
                    </select>
                    @error('current_holder_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    @else
                        <input class="form-control" value="{{ auth()->user()->name }}" disabled>
                        <div class="form-text">The file is initially assigned to the user creating it.</div>
                    @endif
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
                    <label class="form-label">Transfer / Protected Change Reason</label>
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
    <div class="card mb-4">
        <div class="card-header bg-white p-4">
            <div class="d-flex align-items-center justify-content-between gap-3">
                <div>
                    <div class="page-eyebrow">Official attachments</div>
                    <h2 class="section-title mb-0">Confirmation and demand letters</h2>
                </div>
                <span class="badge text-bg-light">PDF or image · max 10 MB</span>
            </div>
        </div>
        <div class="card-body p-4">
            <div class="row g-4">
                @foreach(['confirmation-letter' => 'Confirmation Letter', 'demand-letter' => 'Demand Letter'] as $type => $label)
                    @php($documents = $tokenDocuments->get($type, collect()))
                    <div class="col-lg-6">
                        <div class="border rounded-3 p-3 h-100">
                            <h3 class="h6 mb-3">{{ $type === 'confirmation-letter' ? 'Confirmation Letters' : 'Demand Letter' }}</h3>

                            @forelse($documents as $document)
                                <div class="border rounded-3 p-3 mb-3">
                                    <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                                        <div>
                                            <strong>{{ $document->original_name }}</strong>
                                            <div class="small text-secondary">Version {{ $document->version }}</div>
                                        </div>
                                        <a class="btn btn-sm btn-light" href="{{ route('documents.preview', $document) }}" target="_blank" rel="noopener">
                                            <i class="bi bi-eye me-1" aria-hidden="true"></i>Preview
                                        </a>
                                    </div>

                                    <form method="post" enctype="multipart/form-data" action="{{ route('tokens.documents.update', [$token, $document]) }}">
                                        @csrf
                                        @method('put')
                                        <input type="hidden" name="type" value="{{ $type }}">
                                        <label class="form-label" for="{{ $type }}-{{ $document->id }}-file">Upload a new version</label>
                                        <div class="input-group">
                                            <input class="form-control" id="{{ $type }}-{{ $document->id }}-file" type="file" name="file"
                                                accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" required>
                                            <button class="btn btn-outline-primary" type="submit">Update</button>
                                        </div>
                                    </form>
                                </div>
                            @empty
                                <p class="small text-secondary">No file uploaded yet.</p>
                            @endforelse

                            @if($type === 'confirmation-letter')
                                <button class="btn btn-outline-primary" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#add-confirmation-letter" aria-expanded="{{ old('type') === $type && $errors->has('file') ? 'true' : 'false' }}"
                                    aria-controls="add-confirmation-letter">
                                    <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Add another confirmation letter
                                </button>
                                <div class="collapse mt-3{{ old('type') === $type && $errors->has('file') ? ' show' : '' }}" id="add-confirmation-letter">
                                    <form method="post" enctype="multipart/form-data" action="{{ route('tokens.documents.store', $token) }}">
                                        @csrf
                                        <input type="hidden" name="type" value="{{ $type }}">
                                        <label class="form-label" for="{{ $type }}-file">Confirmation letter file</label>
                                        <div class="input-group">
                                            <input class="form-control" id="{{ $type }}-file" type="file" name="file"
                                                accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" required>
                                            <button class="btn btn-outline-primary" type="submit">Save letter</button>
                                        </div>
                                        @if(old('type') === $type)
                                            @error('file') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                        @endif
                                    </form>
                                </div>
                            @elseif($documents->isEmpty())
                                <form method="post" enctype="multipart/form-data" action="{{ route('tokens.documents.store', $token) }}">
                                    @csrf
                                    <input type="hidden" name="type" value="{{ $type }}">
                                    <label class="form-label" for="{{ $type }}-file">Demand letter file</label>
                                    <div class="input-group">
                                        <input class="form-control" id="{{ $type }}-file" type="file" name="file"
                                            accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" required>
                                        <button class="btn btn-outline-primary" type="submit">Save letter</button>
                                    </div>
                                    @if(old('type') === $type)
                                        @error('file') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                    @endif
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-white p-4">
            <h2 class="section-title mb-0">File Transfer History</h2>
        </div>
        <div class="card-body">
            <div class="timeline">
                @forelse($token->transferHistories as $history)
                    <div class="timeline-item">
                        <strong>{{ $history->previousHolder?->name ?: 'Unassigned' }} → {{ $history->newHolder?->name ?: 'Unassigned' }}</strong>
                        <div class="small text-secondary">
                            {{ $history->transferred_at->format('d M Y, H:i') }} &middot; Transferred by {{ $history->transferredBy->name }}
                        </div>
                        <div class="small">{{ $history->remarks }}</div>
                    </div>
                @empty
                    <p class="text-secondary">No file transfer has been recorded.</p>
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
    var workerChangesGroup = document.getElementById('workerChangesGroup');
    var preGroup       = document.getElementById('preSelectedGroup');
    var dlInput        = document.getElementById('demandedWorkersInput');
    var vaInput        = document.getElementById('visaAttestationInput');
    var workerChangesInput = document.getElementById('workerChangesInput');
    var preInput       = document.getElementById('pre');

    function applyCategory() {
        var opt   = categorySelect.options[categorySelect.selectedIndex];
        var code  = opt ? opt.getAttribute('data-category-code') : '';
        var isDLS = code === 'DLS';
        var isVA  = code === 'VA';
        var isCPA = code === 'CPA';

        dlGroup.style.display = isDLS ? '' : 'none';
        preGroup.style.display = isDLS ? 'flex' : 'none';
        vaGroup.style.display = isVA ? '' : 'none';
        workerChangesGroup.style.display = isCPA ? '' : 'none';

        if (isDLS) {
            dlInput.setAttribute('required', '');
        } else {
            dlInput.removeAttribute('required');
            dlInput.value = '';
            preInput.checked = false;
        }

        if (isVA) {
            vaInput.setAttribute('required', '');
        } else {
            vaInput.removeAttribute('required');
            vaInput.value = '';
        }

        if (isCPA) {
            workerChangesInput.setAttribute('required', '');
        } else {
            workerChangesInput.removeAttribute('required');
            workerChangesInput.value = '';
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
