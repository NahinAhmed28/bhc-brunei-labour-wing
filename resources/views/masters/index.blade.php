@extends('layouts.app')

@section('title', $title)

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
        <div>
            <div class="page-eyebrow">Master data</div>
            <h1 class="page-title">{{ $title }}</h1>
            <p class="page-lead mb-0">Controlled records used by every new token.</p>
        </div>
        <a class="btn btn-primary" href="{{ route($type.'.create') }}"><i class="bi bi-plus-lg me-2" aria-hidden="true"></i>Add {{ $type === 'companies' ? 'company' : 'agency' }}</a>
    </div>

    <div class="card">
        <div class="card-body border-bottom">
            <form class="filter-grid filter-grid-compact" method="get">
                <div class="filter-search">
                    <label class="form-label" for="master-search">Search</label>
                    <input class="form-control" id="master-search" name="q" value="{{ request('q') }}" placeholder="Search by title, email or phone">
                </div>
                <div class="filter-actions">
                    <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search me-2" aria-hidden="true"></i>Search</button>
                    <a class="btn btn-light" href="{{ route($type.'.index') }}"><i class="bi bi-x-lg me-2" aria-hidden="true"></i>Clear</a>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                <tr>
                    <th class="ps-4">Title</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th class="text-end pe-4">Actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse($items as $item)
                    <tr>
                        <td class="ps-4"><strong>{{ $item->name }}</strong><div class="small text-secondary">{{ $item->registration_no ?? $item->license_no ?? 'No registration reference' }}</div></td>
                        <td>{{ $item->email ?: '—' }}</td>
                        <td>{{ $item->phone ?: '—' }}</td>
                        <td><span class="status {{ $item->is_active ? 'status-success' : 'status-danger' }}">{{ $item->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td class="text-end pe-4">
                            <a class="btn btn-sm btn-light" href="{{ route($type.'.edit', $item) }}"><i class="bi bi-pencil-square me-1" aria-hidden="true"></i>Edit</a>
                            @if(auth()->user()->isSuperAdmin())
                                <form class="d-inline" method="post" action="{{ route($type.'.destroy', $item) }}" data-confirm="Deactivate this record?">
                                    @csrf
                                    @method('delete')
                                    <button class="btn btn-sm btn-link text-danger"><i class="bi bi-slash-circle me-1" aria-hidden="true"></i>Deactivate</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-secondary py-5">No records found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <x-list-pagination :paginator="$items" />
    </div>
@endsection
