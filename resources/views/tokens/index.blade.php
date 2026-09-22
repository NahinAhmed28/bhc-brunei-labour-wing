@extends('layouts.app')

@section('title', 'Token Submissions')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/token-list.css') }}">
@endpush

@section('content')

<style>
/* ── Page header ── */
.page-eyebrow { font-size: .75rem; text-transform: uppercase; letter-spacing: .08em; color: var(--bs-secondary); }
.page-title   { font-size: 1.75rem; font-weight: 700; margin: .25rem 0; }
.page-lead    { color: var(--bs-secondary); }

/* ── Stat pills ── */
.stat-pill {
    display: inline-flex; align-items: center; gap: .4rem;
    padding: .35rem .85rem; border-radius: 999px; font-size: .8rem; font-weight: 600;
    background: var(--bs-light); border: 1px solid var(--bs-border-color);
}

/* ── Status badge ── */
.status          { display: inline-block; padding: .25em .7em; border-radius: .375rem; font-size: .75rem; font-weight: 600; text-transform: capitalize; }
.status-success  { background: #d1fae5; color: #065f46; }
.status-warning  { background: #fef3c7; color: #92400e; }
.status-neutral  { background: #f1f5f9; color: #475569; }
.status-danger   { background: #fee2e2; color: #991b1b; }

/* ── Filter form grid ── */
.filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: .75rem;
    align-items: end;
}
.filter-search   { grid-column: span 2; }
.filter-actions  { display: flex; gap: .5rem; align-items: flex-end; }

/* ── Responsive table — desktop / mid ── */
.token-table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
.token-table th   { white-space: nowrap; font-size: .78rem; text-transform: uppercase; letter-spacing: .04em; padding: .85rem 1rem; }
.token-table td   { vertical-align: middle; padding: .75rem 1rem; }

/* ── Token number button ── */
.token-row-title {
    background: none; border: none; padding: 0;
    font-weight: 600; color: var(--bs-link-color, #2563eb); text-decoration: underline; cursor: pointer;
}
.token-row-title:hover { color: var(--bs-link-hover-color); }

/* ── Mobile card list ── */
.token-card {
    border: 1px solid var(--bs-border-color);
    border-radius: .5rem; padding: 1rem; margin-bottom: .75rem;
    background: var(--bs-body-bg);
}
.token-card-header {
    display: flex; justify-content: space-between; align-items: flex-start; gap: .5rem;
    margin-bottom: .6rem; flex-wrap: wrap;
}
.token-card-title   { font-weight: 700; font-size: 1rem; }
.token-card-body    { font-size: .85rem; color: var(--bs-secondary-color, #6c757d); }
.token-card-row     { display: flex; justify-content: space-between; gap: .5rem; padding: .25rem 0; border-bottom: 1px solid var(--bs-border-color); }
.token-card-row:last-child { border-bottom: none; }
.token-card-row dt  { font-weight: 600; color: var(--bs-body-color); min-width: 130px; }
.token-card-row dd  { margin: 0; text-align: right; }
.token-card-actions { display: flex; gap: .4rem; flex-wrap: wrap; margin-top: .75rem; }

/* ── Responsive visibility ── */
@media (max-width: 767.98px) {
    .token-table-view { display: none; }
    .token-card-view  { display: block; }
    .filter-search    { grid-column: span 1; }
}
@media (min-width: 768px) {
    .token-table-view { display: block; }
    .token-card-view  { display: none; }
}

/* ── Mid-size table: hide lower-priority columns ── */
@media (min-width: 768px) and (max-width: 1199.98px) {
    .col-hide-md { display: none; }
}
</style>

{{-- ── Page header ── --}}
<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div>
        <div class="page-eyebrow">Token operations</div>
        <h1 class="page-title">Token Submissions</h1>
        <p class="page-lead mb-0">
            <span class="stat-pill"><i class="bi bi-ticket-perforated" aria-hidden="true"></i> <span id="token-matching-count">{{ number_format($tokens->total()) }} matching tokens</span></span>
            <span class="stat-pill ms-1"><i class="bi bi-person-check" aria-hidden="true"></i> {{ number_format($preSelectedCount) }} pre-selected</span>
        </p>
    </div>
    @if(auth()->user()->hasAnyRole('super-admin', 'administrator'))
        <a class="btn btn-primary" href="{{ route('tokens.create') }}">
            <i class="bi bi-plus-lg me-2" aria-hidden="true"></i>Create Token
        </a>
    @endif
</div>

{{-- ── Filter card ── --}}
<div class="card mb-4 token-register-card">
    <div class="card-body border-bottom">
        <form class="filter-grid" id="token-filter-form" method="get" action="{{ route('tokens.index') }}">

            <div class="filter-search">
                <label class="form-label" for="token-search">Search</label>
                <input class="form-control" id="token-search" name="q" type="search" value="{{ request('q') }}"
                       placeholder="Token no., BHC no., company or agency">
            </div>

            <div>
                <label class="form-label" for="company-filter">Company</label>
                <input class="form-control" id="company-filter" name="company_name" type="search"
                       list="company-filter-options" value="{{ request('company_name') }}"
                       placeholder="Type company name" autocomplete="off">
                <datalist id="company-filter-options">
                    @foreach($companies as $c)
                        <option value="{{ $c->name }}"></option>
                    @endforeach
                </datalist>
            </div>

            <div>
                <label class="form-label" for="agency-filter">Agency</label>
                <input class="form-control" id="agency-filter" name="agency_name" type="search"
                       list="agency-filter-options" value="{{ request('agency_name') }}"
                       placeholder="Type agency name" autocomplete="off">
                <datalist id="agency-filter-options">
                    @foreach($agencies as $a)
                        <option value="{{ $a->name }}"></option>
                    @endforeach
                </datalist>
            </div>

            <div>
                <label class="form-label" for="category-filter">Category</label>
                <select class="form-select" id="category-filter" name="category_id">
                    <option value="">All categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" @selected(request('category_id') == $cat->id)>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="form-label" for="creator-filter">Created by</label>
                <select class="form-select" id="creator-filter" name="created_by">
                    <option value="">All users</option>
                    @foreach($users as $userOption)
                        <option value="{{ $userOption->id }}" @selected(request('created_by') == $userOption->id)>{{ $userOption->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="form-label" for="holder-filter">Assigned to</label>
                <select class="form-select" id="holder-filter" name="holder_id">
                    <option value="">All users</option>
                    @foreach($users as $userOption)
                        <option value="{{ $userOption->id }}" @selected(request('holder_id') == $userOption->id)>{{ $userOption->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="form-label" for="boesl-filter">BOESL Status</label>
                <select class="form-select" id="boesl-filter" name="boesl_status">
                    <option value="">All statuses</option>
                    @foreach(['pending', 'submitted', 'returned', 'not-required'] as $s)
                        <option value="{{ $s }}" @selected(request('boesl_status') === $s)>{{ ucwords(str_replace('-', ' ', $s)) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="form-label" for="bhc-filter">BHC No.</label>
                <input class="form-control" id="bhc-filter" name="bhc_number" type="text"
                       value="{{ request('bhc_number') }}" placeholder="Type BHC number" maxlength="100">
            </div>

            <div>
                <label class="form-label" for="from-date-filter">Received Date From</label>
                <input class="form-control" id="from-date-filter" name="from_date" type="date"
                       value="{{ request('from_date') }}" title="Received on or after this date">
            </div>

            <div>
                <label class="form-label" for="to-date-filter">Received Date To</label>
                <input class="form-control" id="to-date-filter" name="to_date" type="date"
                       value="{{ request('to_date') }}" title="Received on or before this date">
            </div>

            <div>
                <label class="form-label" for="preselected-filter">Pre-selected</label>
                <select class="form-select" id="preselected-filter" name="pre_selected">
                    <option value="">All</option>
                    <option value="1" @selected(request('pre_selected') === '1')>Yes</option>
                    <option value="0" @selected(request('pre_selected') === '0')>No</option>
                </select>
            </div>

            <div class="filter-actions">
                <button class="btn btn-outline-primary" type="submit">
                    <i class="bi bi-funnel me-1" aria-hidden="true"></i>Filter
                </button>
                <a class="btn btn-light" id="token-filter-clear" href="{{ route('tokens.index') }}" aria-label="Clear token filters" title="Clear filters">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </a>
            </div>

        </form>
        <p class="text-secondary mt-3 mb-0" id="token-filter-status" role="status" hidden>Updating tokens...</p>
        <p class="alert alert-danger mt-3 mb-0" id="token-filter-error" role="alert" hidden></p>
    </div>

    {{-- ══════════════════════════════════════════════
         DESKTOP / MID TABLE VIEW  (≥768 px)
    ══════════════════════════════════════════════ --}}
    <div id="token-results" aria-label="Token results" aria-busy="false">
        @include('tokens.results', ['tokens' => $tokens])
    </div>
</div>

@endsection

@push('scripts')
<script src="{{ asset('assets/js/token-filters.js') }}"></script>
@endpush
