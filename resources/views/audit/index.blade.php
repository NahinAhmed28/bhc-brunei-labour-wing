@extends('layouts.app')

@section('title', 'Audit trail')

@section('content')
    <div class="mb-4">
        <div class="page-eyebrow">Immutable activity record</div>
        <h1 class="page-title">Audit trail</h1>
        <p class="page-lead">Sensitive actions, protected changes, downloads and exports with actor and source address.</p>
    </div>

    <div class="card">
        <div class="card-body border-bottom">
            <form class="filter-grid filter-grid-compact" method="get">
                <div class="filter-search">
                    <label class="form-label" for="audit-search">Search</label>
                    <input class="form-control" id="audit-search" name="q" value="{{ request('q') }}" placeholder="Search module or action">
                </div>
                <div class="filter-actions">
                    <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search me-2" aria-hidden="true"></i>Search</button>
                    <a class="btn btn-light" href="{{ route('audit.index') }}"><i class="bi bi-x-lg me-2" aria-hidden="true"></i>Clear</a>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                <tr>
                    <th class="ps-4">Time</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Module</th>
                    <th>Record</th>
                    <th class="pe-4">IP address</th>
                </tr>
                </thead>
                <tbody>
                @foreach($logs as $log)
                    <tr>
                        <td class="ps-4 text-nowrap">{{ $log->created_at->format('d M Y, H:i:s') }}</td>
                        <td>{{ $log->user?->name ?: 'System' }}</td>
                        <td><strong>{{ ucfirst($log->action) }}</strong></td>
                        <td>{{ str_replace('-', ' ', $log->module) }}</td>
                        <td>{{ $log->record_id ?: '—' }}</td>
                        <td class="pe-4">{{ $log->ip_address ?: '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <x-list-pagination :paginator="$logs" />
    </div>
@endsection
