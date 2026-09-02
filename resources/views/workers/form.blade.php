@extends('layouts.app')

@section('title', $worker->exists ? 'Edit worker' : 'Add worker')

@section('content')
    <div class="mb-4">
        <a class="small text-decoration-none" href="{{ $worker->exists ? route('workers.show', $worker) : route('workers.index') }}">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
        <div class="page-eyebrow mt-3">BHC application</div>
        <h1 class="page-title">{{ $worker->exists ? 'Update worker' : 'Add worker against token' }}</h1>
        <p class="page-lead">Company and agency are inherited from the selected token. Worker limits are enforced when saving.</p>
    </div>

    <form method="post" action="{{ $worker->exists ? route('workers.update', $worker) : route('workers.store') }}">
        @csrf
        @if($worker->exists)
            @method('put')
        @endif

        <div class="card mb-4">
            <div class="card-header bg-white p-4">
                <h2 class="section-title mb-0">Token and identity</h2>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-12">
                        @php($selectedToken = $tokens->firstWhere('id', old('token_id', $worker->token_id)))
                        <label class="form-label" for="token-lookup">BHC No. / Token Number *</label>
                        <input
                            class="form-control"
                            id="token-lookup"
                            type="search"
                            list="token-lookup-options"
                            value="{{ $selectedToken?->token_number }}"
                            placeholder="Type a BHC or token number"
                            autocomplete="off"
                            required
                            data-token-lookup
                            @readonly($worker->exists && ! auth()->user()->isSuperAdmin())
                        >
                        <datalist id="token-lookup-options">
                            @foreach($tokens as $tokenOption)
                                <option
                                    value="{{ $tokenOption->token_number }}"
                                    data-token-id="{{ $tokenOption->id }}"
                                    data-bhc-number="{{ $tokenOption->bhc_number }}"
                                >BHC {{ $tokenOption->bhc_number ?: 'pending' }} &middot; {{ $tokenOption->company->name }} / {{ $tokenOption->agency->name }} &middot; {{ $tokenOption->workers_count }}/{{ $tokenOption->approved_workers ?: ($tokenOption->demanded_workers ?? $tokenOption->required_visa_attestation ?? $tokenOption->required_worker_changes ?? 'unlimited') }}</option>
                            @endforeach
                        </datalist>
                        <input type="hidden" name="token_id" value="{{ old('token_id', $worker->token_id) }}" data-token-lookup-value>
                        <div class="form-text">Start typing, then select the matching authorized token.</div>
                    </div>

                    <div class="col-md-6"><label class="form-label">Full name *</label><input class="form-control" name="full_name" value="{{ old('full_name', $worker->full_name) }}" required></div>
                    <div class="col-md-6"><label class="form-label">Passport *</label><input class="form-control" name="passport_number" value="{{ old('passport_number', $worker->passport_number) }}" required></div>
                    <div class="col-md-4"><label class="form-label">Father's name</label><input class="form-control" name="father_name" value="{{ old('father_name', $worker->father_name) }}"></div>
                    <div class="col-md-4"><label class="form-label">Mother's name</label><input class="form-control" name="mother_name" value="{{ old('mother_name', $worker->mother_name) }}"></div>
                    <div class="col-md-4"><label class="form-label">Date of birth</label><input class="form-control" type="date" name="date_of_birth" value="{{ old('date_of_birth', optional($worker->date_of_birth)->format('Y-m-d')) }}"></div>
                    <div class="col-md-4">
                        <label class="form-label">Gender</label>
                        <select class="form-select" name="gender">
                            <option value="">Select</option>
                            @foreach(['Male', 'Female', 'Other'] as $gender)
                                <option @selected(old('gender', $worker->gender) === $gender)>{{ $gender }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4"><label class="form-label">Nationality *</label><input class="form-control" name="nationality" value="{{ old('nationality', $worker->nationality) }}" required></div>
                    <div class="col-md-4"><label class="form-label">National ID</label><input class="form-control" name="national_id" value="{{ old('national_id', $worker->national_id) }}"></div>
                    <div class="col-md-4"><label class="form-label">Passport issue date</label><input class="form-control" type="date" name="passport_issue_date" value="{{ old('passport_issue_date', optional($worker->passport_issue_date)->format('Y-m-d')) }}"></div>
                    <div class="col-md-4"><label class="form-label">Passport expiry date</label><input class="form-control" type="date" name="passport_expiry_date" value="{{ old('passport_expiry_date', optional($worker->passport_expiry_date)->format('Y-m-d')) }}"></div>
                    <div class="col-md-4"><label class="form-label">Issuing authority</label><input class="form-control" name="passport_authority" value="{{ old('passport_authority', $worker->passport_authority) }}"></div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-white p-4"><h2 class="section-title mb-0">Update Details</h2></div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label">Phone Number</label><input class="form-control" name="phone" value="{{ old('phone', $worker->phone) }}"></div>
                    <div class="col-md-4"><label class="form-label">Email</label><input class="form-control" type="email" name="email" value="{{ old('email', $worker->email) }}"></div>
                    <div class="col-md-4"><label class="form-label">Job category</label><input class="form-control" name="job_category" value="{{ old('job_category', $worker->job_category) }}"></div>
                    <div class="col-md-4"><label class="form-label">Registration No.</label><input class="form-control" name="registration_number" value="{{ old('registration_number', $worker->registration_number) }}"></div>
                    <div class="col-md-4"><label class="form-label">Registration date</label><input class="form-control" type="date" name="registration_date" value="{{ old('registration_date', optional($worker->registration_date)->format('Y-m-d')) }}"></div>
                    <div class="col-md-4"><label class="form-label">Flight Date</label><input class="form-control" type="date" name="flight_date" value="{{ old('flight_date', optional($worker->flight_date)->format('Y-m-d')) }}"></div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-white p-4"><h2 class="section-title mb-0">Update Tracking Status</h2></div>
            <div class="card-body p-4">
                <div class="row g-3">
                    @foreach([
                        'tracking_status' => ['pending', 'in-progress', 'complete'],
                        'visa_status' => ['pending', 'processing', 'received', 'rejected'],
                        'flight_status' => ['pending', 'booked', 'departed', 'cancelled'],
                        'insurance_status' => ['pending', 'received', 'not-required'],
                        'ic_status' => ['pending', 'received', 'not-required'],
                        'medical_status' => ['pending', 'cleared', 'failed'],
                        'boesl_status' => ['pending', 'submitted', 'returned', 'not-required'],
                    ] as $field => $options)
                        <div class="col-md-4">
                            <label class="form-label">{{ ucwords(str_replace('_', ' ', $field)) }}</label>
                            <select class="form-select" name="{{ $field }}">
                                @foreach($options as $option)
                                    <option value="{{ $option }}" @selected(old($field, $worker->$field) === $option)>{{ ucwords(str_replace('-', ' ', $option)) }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach
                    <div class="col-md-4"><label class="form-label">Insurance date</label><input class="form-control" type="date" name="insurance_date" value="{{ old('insurance_date', optional($worker->insurance_date)->format('Y-m-d')) }}"></div>
                    <div class="col-12"><label class="form-label">Remarks</label><textarea class="form-control" rows="3" name="remarks">{{ old('remarks', $worker->remarks) }}</textarea></div>

                    @if(auth()->user()->isSuperAdmin())
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="override_limit" value="1" id="override">
                                <label class="form-check-label" for="override">Authorize worker-limit override</label>
                            </div>
                            <input class="form-control mt-2" name="override_reason" placeholder="Override reason">
                        </div>
                    @endif
                </div>
            </div>
            <div class="card-footer bg-white text-end p-3">
                <a class="btn btn-light" href="{{ route('workers.index') }}">Cancel</a>
                <button class="btn btn-primary ms-2">{{ $worker->exists ? 'Update Info' : 'Save worker' }}</button>
            </div>
        </div>
    </form>
@endsection
