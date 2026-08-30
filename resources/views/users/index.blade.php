@extends('layouts.app')

@section('title', 'Users')

@section('content')
    <div class="d-flex justify-content-between align-items-end mb-4">
        <div>
            <div class="page-eyebrow">Access control</div>
            <h1 class="page-title">User accounts</h1>
            <p class="page-lead mb-0">Assign operational roles and deactivate access without deleting history.</p>
        </div>
        <a class="btn btn-primary" href="{{ route('users.create') }}">
            <i class="bi bi-person-plus me-2" aria-hidden="true"></i>Add user
        </a>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                <tr>
                    <th class="ps-4">User</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Last login</th>
                    <th class="pe-4">Action</th>
                </tr>
                </thead>
                <tbody>
                @foreach($users as $user)
                    <tr>
                        <td class="ps-4">
                            <strong>{{ $user->name }}</strong>
                            <small class="d-block text-secondary">{{ $user->email }}</small>
                        </td>
                        <td>{{ $user->role?->label }}</td>
                        <td><span class="status {{ $user->is_active ? 'status-success' : 'status-danger' }}">{{ $user->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td>{{ $user->last_login_at?->format('d M Y, H:i') ?: 'Never' }}</td>
                        <td class="pe-4"><a class="btn btn-sm btn-light" href="{{ route('users.edit', $user) }}">Edit</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <x-list-pagination :paginator="$users" />
    </div>
@endsection
