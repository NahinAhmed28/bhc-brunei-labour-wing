@extends('layouts.app')

@section('title', $tokenCategory->exists ? 'Edit Token Category' : 'Create Token Category')

@section('content')
    <div class="mb-4">
        <a class="small text-decoration-none" href="{{ route('token-categories.index') }}"><i class="bi bi-arrow-left me-1"></i>Back to categories</a>
        <div class="page-eyebrow mt-3">Master data</div>
        <h1 class="page-title">{{ $tokenCategory->exists ? 'Edit Token Category' : 'Create Token Category' }}</h1>
        <p class="page-lead">Use code DLS for Demand Letter Submission and VA for Visa Attestation.</p>
    </div>

    <form method="post" action="{{ $tokenCategory->exists ? route('token-categories.update', $tokenCategory) : route('token-categories.store') }}">
        @csrf
        @if($tokenCategory->exists) @method('put') @endif
        <div class="card">
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label" for="category-name">Name *</label>
                        <input class="form-control" id="category-name" name="name" value="{{ old('name', $tokenCategory->name) }}" required>
                        @error('name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="category-code">Code *</label>
                        <input class="form-control text-uppercase" id="category-code" name="code" value="{{ old('code', $tokenCategory->code) }}" required>
                        @error('code') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="category-description">Description</label>
                        <textarea class="form-control" id="category-description" name="description" rows="3">{{ old('description', $tokenCategory->description) }}</textarea>
                        @error('description') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="category-fee">Default fee</label>
                        <input class="form-control" id="category-fee" type="number" min="0" step="0.01" name="default_fee" value="{{ old('default_fee', $tokenCategory->default_fee) }}">
                        @error('default_fee') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="category-order">Display order</label>
                        <input class="form-control" id="category-order" type="number" min="0" name="display_order" value="{{ old('display_order', $tokenCategory->display_order ?? 0) }}">
                        @error('display_order') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input type="hidden" name="is_active" value="0">
                            <input class="form-check-input" id="category-active" type="checkbox" name="is_active" value="1" @checked(old('is_active', $tokenCategory->exists ? $tokenCategory->is_active : true))>
                            <label class="form-check-label" for="category-active">Active and available for token creation</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white p-3 text-end">
                <a class="btn btn-light" href="{{ route('token-categories.index') }}">Cancel</a>
                <button class="btn btn-primary ms-2">Save category</button>
            </div>
        </div>
    </form>
@endsection
