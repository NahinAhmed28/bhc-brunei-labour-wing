@extends('layouts.app')

@section('title', 'Token Categories')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
        <div>
            <div class="page-eyebrow">Master data</div>
            <h1 class="page-title">Token Categories</h1>
            <p class="page-lead mb-0">Control the submission categories available when creating a token.</p>
        </div>
        <a class="btn btn-primary" href="{{ route('token-categories.create') }}"><i class="bi bi-plus-lg me-2" aria-hidden="true"></i>Add category</a>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th class="ps-4">Category</th><th>Code</th><th>Tokens</th><th>Status</th><th class="text-end pe-4">Actions</th></tr></thead>
                <tbody>
                    @forelse($categories as $category)
                        <tr>
                            <td class="ps-4"><strong>{{ $category->name }}</strong><div class="small text-secondary">{{ $category->description ?: 'No description' }}</div></td>
                            <td>{{ $category->code }}</td>
                            <td>{{ $category->tokens_count }}</td>
                            <td><span class="status {{ $category->is_active ? 'status-success' : 'status-danger' }}">{{ $category->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td class="text-end pe-4">
                                <a class="btn btn-sm btn-light" href="{{ route('token-categories.edit', $category) }}"><i class="bi bi-pencil-square me-1" aria-hidden="true"></i>Edit</a>
                                @if($category->is_active && auth()->user()->isSuperAdmin())
                                    <form class="d-inline" method="post" action="{{ route('token-categories.destroy', $category) }}" data-confirm="Deactivate this token category?">
                                        @csrf
                                        @method('delete')
                                        <button class="btn btn-sm btn-link text-danger"><i class="bi bi-slash-circle me-1" aria-hidden="true"></i>Deactivate</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-secondary py-5">No token categories found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-list-pagination :paginator="$categories" />
    </div>
@endsection
