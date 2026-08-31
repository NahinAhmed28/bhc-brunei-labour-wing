@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="d-flex flex-wrap justify-content-between gap-3 align-items-end mb-4">
        <div>
            <div class="page-eyebrow">Operations overview</div>
            <h1 class="page-title">Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 18 ? 'afternoon' : 'evening') }}</h1>
            <p class="page-lead mb-0">Live visa, token and worker movement across the mission.</p>
        </div>
        <a class="btn btn-primary" href="{{ route('tokens.create') }}">
            <i class="bi bi-plus-lg me-2" aria-hidden="true"></i>Create token
        </a>
    </div>

    <div class="row g-3 mb-4">
        @foreach($metrics as $metric)
            <div class="col-6 col-xl-3">
                <a class="card metric-card metric-card-link" href="{{ $metric['url'] }}" aria-label="View {{ $metric['label'] }}">
                    <div class="card-body">
                        <div class="metric-card-heading">
                            <span class="metric-card-icon"><i class="bi bi-{{ $metric['icon'] }}" aria-hidden="true"></i></span>
                            <i class="bi bi-arrow-up-right metric-card-arrow" aria-hidden="true"></i>
                        </div>
                        <div class="metric-value">{{ number_format($metric['value']) }}</div>
                        <div class="metric-label">{{ $metric['label'] }}</div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>

    <div class="row g-4">
        <div class="{{ $showRecentActivity ? 'col-xl-7' : 'col-12' }}">
            <div class="card">
                <div class="card-header bg-white py-3"><h2 class="section-title mb-0">File count by user</h2></div>
                <div class="card-body">
                    @forelse($holderCounts as $holder)
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between small mb-1">
                                    <a class="text-decoration-none" href="{{ route('tokens.index', ['holder_id' => $holder->id]) }}">{{ $holder->name }}</a><strong>{{ $holder->held_tokens_count }}</strong>
                                </div>
                                <div class="progress" style="height: 7px">
                                    <div class="progress-bar bg-success" style="width: {{ min(100, $holder->held_tokens_count * 10) }}%"></div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-secondary mb-0">Create active users to begin assigning files.</p>
                    @endforelse
                </div>
            </div>
        </div>
        @if($showRecentActivity)
            <div class="col-xl-5">
                <div class="card">
                    <div class="card-header bg-white py-3"><h2 class="section-title mb-0">Recent activity</h2></div>
                    <div class="list-group list-group-flush">
                        @forelse($recent as $log)
                            <div class="list-group-item px-4 py-3">
                                <div class="d-flex justify-content-between gap-2">
                                    <strong class="small">{{ ucfirst($log->action) }} · {{ str_replace('-', ' ', $log->module) }}</strong>
                                    <span class="text-secondary small">{{ $log->created_at->diffForHumans() }}</span>
                                </div>
                                <div class="text-secondary small mt-1">{{ $log->user?->name ?? 'System' }}</div>
                            </div>
                        @empty
                            <div class="p-4 text-secondary">No activity recorded yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
