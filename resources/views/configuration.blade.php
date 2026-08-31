@extends('layouts.app')

@section('title', 'Configuration')

@section('content')
    <div class="mb-4">
        <div class="page-eyebrow">System administration</div>
        <h1 class="page-title">Configuration</h1>
        <p class="page-lead">Maintain the controlled file desks that drive the operational workflow.</p>
    </div>

    <div class="card">
        <div class="card-header bg-white p-4">
            <h2 class="section-title">Desk register</h2>
            <form method="post" action="{{ route('configuration.desks') }}" class="row g-2">
                @csrf
                <div class="col-7"><input class="form-control" name="name" placeholder="Desk name" required></div>
                <div class="col-3"><input class="form-control" name="code" placeholder="Code" required></div>
                <div class="col-2"><button class="btn btn-primary w-100" aria-label="Add desk"><i class="bi bi-plus-lg"></i></button></div>
            </form>
        </div>
        <div class="list-group list-group-flush">
            @foreach($desks as $item)
                <div class="list-group-item px-4 py-3 d-flex justify-content-between">
                    <span><strong>{{ $item->name }}</strong><small class="text-secondary d-block">{{ $item->code }}</small></span>
                    <span class="status {{ $item->is_active ? 'status-success' : 'status-danger' }}">{{ $item->is_active ? 'Active' : 'Inactive' }}</span>
                </div>
            @endforeach
        </div>
    </div>
@endsection
