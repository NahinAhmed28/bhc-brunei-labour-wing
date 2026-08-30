@extends('layouts.app')

@section('title', 'BHC Workers List')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
        <div>
            <div class="page-eyebrow">Applications</div>
            <h1 class="page-title">BHC Workers List</h1>
            <p class="page-lead mb-0">Worker registration, travel and document status in one linked register.</p>
        </div>
        @if(auth()->user()->hasAnyRole('super-admin', 'administrator', 'data-entry'))
            <a class="btn btn-primary" href="{{ route('workers.create') }}"><i class="bi bi-person-plus me-2" aria-hidden="true"></i>Add worker</a>
        @endif
    </div>

    <div class="card">
        <div class="card-body border-bottom">
            <form class="filter-grid" method="get">
                <div class="filter-search">
                    <label class="form-label" for="worker-search">Search</label>
                    <input class="form-control" id="worker-search" name="q" value="{{ request('q') }}" placeholder="Worker, passport, registration, token or BHC">
                </div>
                @foreach([
                    'visa_status' => ['Visa Status', ['pending', 'processing', 'received', 'rejected']],
                    'flight_status' => ['Flight Status', ['pending', 'booked', 'departed', 'cancelled']],
                    'insurance_status' => ['Insurance Status', ['pending', 'received', 'not-required']],
                    'ic_status' => ['IC Status', ['pending', 'received', 'not-required']],
                ] as $field => [$label, $options])
                    <div>
                        <label class="form-label" for="{{ $field }}-filter">{{ $label }}</label>
                        <select class="form-select" id="{{ $field }}-filter" name="{{ $field }}">
                            <option value="">All {{ strtolower($label) }}</option>
                            @foreach($options as $status)
                                <option value="{{ $status }}" @selected(request($field) === $status)>{{ ucwords(str_replace('-', ' ', $status)) }}</option>
                            @endforeach
                        </select>
                    </div>
                @endforeach
                <div class="filter-actions">
                    <button class="btn btn-outline-primary" type="submit"><i class="bi bi-funnel me-2" aria-hidden="true"></i>Apply filters</button>
                    <a class="btn btn-light" href="{{ route('workers.index') }}"><i class="bi bi-x-lg me-2" aria-hidden="true"></i>Clear</a>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                <tr>
                    <th class="ps-4">Worker Info</th>
                    <th>Agency / Company</th>
                    <th>Flight Date</th>
                    <th>Status</th>
                    <th>Registration Date</th>
                    <th>IC / Insurance</th>
                    <th class="pe-4">Actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse($workers as $worker)
                    <tr>
                        <td class="ps-4">
                            <a class="fw-bold text-decoration-none" href="{{ route('workers.show', $worker) }}">{{ $worker->full_name }}</a>
                            <div class="small text-secondary">{{ $worker->passport_number }} · {{ $worker->token->token_number }}</div>
                        </td>
                        <td>{{ $worker->token->agency->name }}<div class="small text-secondary">{{ $worker->token->company->name }}</div></td>
                        <td>{{ optional($worker->flight_date)->format('d M Y') ?: '—' }}<div class="small text-secondary">{{ ucfirst($worker->flight_status) }}</div></td>
                        <td><span class="status {{ $worker->visa_status === 'received' ? 'status-success' : 'status-warning' }}">{{ ucfirst($worker->visa_status) }}</span></td>
                        <td>{{ optional($worker->registration_date)->format('d M Y') ?: '—' }}<div class="small text-secondary">{{ $worker->registration_number ?: 'Not marked' }}</div></td>
                        <td><small>IC: {{ ucfirst($worker->ic_status) }}<br>Insurance: {{ ucfirst($worker->insurance_status) }}</small></td>
                        <td class="pe-4 text-nowrap">
                            <a class="btn btn-sm btn-light" href="{{ route('workers.show', $worker) }}"><i class="bi bi-eye me-1" aria-hidden="true"></i>View</a>
                            @if(auth()->user()->hasAnyRole('super-admin', 'administrator', 'data-entry'))
                                <a class="btn btn-sm btn-light" href="{{ route('workers.edit', $worker) }}"><i class="bi bi-pencil-square me-1" aria-hidden="true"></i>Edit / Mark Reg.</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-secondary py-5">No workers match these filters.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <x-list-pagination :paginator="$workers" />
    </div>
@endsection
