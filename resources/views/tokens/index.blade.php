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
            <span class="stat-pill"><i class="bi bi-ticket-perforated" aria-hidden="true"></i> {{ number_format($tokens->total()) }} matching tokens</span>
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
        <form class="filter-grid" method="get">

            <div class="filter-search">
                <label class="form-label" for="token-search">Search</label>
                <input class="form-control" id="token-search" name="q" value="{{ request('q') }}"
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
                <label class="form-label" for="holder-filter">File Holder</label>
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
                <select class="form-select" id="bhc-filter" name="bhc_status">
                    <option value="">All</option>
                    <option value="pending"  @selected(request('bhc_status') === 'pending')>Pending</option>
                    <option value="assigned" @selected(request('bhc_status') === 'assigned')>Assigned</option>
                </select>
            </div>

            <div>
                <label class="form-label" for="created-filter">Date</label>
                <select class="form-select" id="created-filter" name="created">
                    <option value="">All dates</option>
                    <option value="today" @selected(request('created') === 'today')>Today</option>
                </select>
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
                <a class="btn btn-light" href="{{ route('tokens.index') }}" aria-label="Clear token filters" title="Clear filters">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </a>
            </div>

        </form>
    </div>

    {{-- ══════════════════════════════════════════════
         DESKTOP / MID TABLE VIEW  (≥768 px)
    ══════════════════════════════════════════════ --}}
    <div class="px-4 py-3 border-bottom bg-body-tertiary" aria-live="polite">
        <strong>{{ number_format($tokens->total()) }}</strong> token {{ $tokens->total() === 1 ? 'record' : 'records' }} found for the current filters.
    </div>

    <div class="token-table-view token-table-wrap">
        <table class="table token-table mb-0">
            <thead>
                <tr>
                    <th class="ps-4" scope="col"><span class="token-column-label"><i class="bi bi-hash" aria-hidden="true"></i>Token</span></th>
                    <th scope="col"><span class="token-column-label"><i class="bi bi-tag" aria-hidden="true"></i>Category</span></th>
                    <th scope="col"><span class="token-column-label"><i class="bi bi-briefcase" aria-hidden="true"></i>Agency</span></th>
                    <th scope="col"><span class="token-column-label"><i class="bi bi-buildings" aria-hidden="true"></i>Company</span></th>
                    {{-- Hidden on md, visible on lg+ --}}
                    <th class="col-hide-md" scope="col"><span class="token-column-label"><i class="bi bi-calendar3" aria-hidden="true"></i>Received</span></th>
                    <th scope="col"><span class="token-column-label"><i class="bi bi-person-lines-fill" aria-hidden="true"></i>Required</span></th>
                    <th class="col-hide-md" scope="col"><span class="token-column-label"><i class="bi bi-person-check" aria-hidden="true"></i>Approved</span></th>
                    <th class="col-hide-md" scope="col"><span class="token-column-label"><i class="bi bi-file-earmark-check" aria-hidden="true"></i>BHC No.</span></th>
                    <th scope="col"><span class="token-column-label"><i class="bi bi-send-check" aria-hidden="true"></i>BOESL</span></th>
                    <th class="pe-4 text-end" scope="col"><span class="token-column-label justify-content-end"><i class="bi bi-command" aria-hidden="true"></i>Actions</span></th>
                </tr>
            </thead>
            <tbody>
            @forelse($tokens as $token)
                @php
                    $demandVal = match (true) {
                        $token->isVA() => $token->required_visa_attestation ?? '—',
                        $token->isChangePreWorker() => $token->required_worker_changes ?? '—',
                        default => $token->demanded_workers ?? '—',
                    };
                    $demandLabel = match (true) {
                        $token->isVA() => 'VA',
                        $token->isChangePreWorker() => 'Change',
                        default => null,
                    };
                @endphp
                <tr class="token-row" tabindex="0" role="button"
                    aria-haspopup="dialog" aria-controls="tokenDetailsModal"
                    aria-label="View details for {{ $token->token_number }}"
                    data-token-modal-url="{{ route('tokens.modal', $token) }}">

                    <td class="ps-4">
                        <button class="token-row-title" type="button"
                            aria-label="Open token {{ $token->token_number }} details"
                            data-token-modal-url="{{ route('tokens.modal', $token) }}">
                            <span class="token-reference-prefix">REF</span>
                            <span>{{ $token->token_number }}</span>
                        </button>
                        <div class="token-row-meta"><i class="bi bi-people" aria-hidden="true"></i>{{ $token->workers_count }} workers</div>
                        @if($token->pre_selected)
                            <span class="token-flag"><i class="bi bi-check2-circle" aria-hidden="true"></i>Pre-selected</span>
                        @endif
                    </td>

                    <td>
                        <span class="token-category">{{ $token->category->name ?? '—' }}</span>
                    </td>

                    <td><span class="token-entity">{{ $token->agency->name ?? '—' }}</span></td>

                    <td><span class="token-entity">{{ $token->company->name ?? '—' }}</span></td>

                    <td class="col-hide-md">
                        @if($token->received_on)
                            <time class="token-date" datetime="{{ $token->received_on->format('Y-m-d') }}">{{ $token->received_on->format('d M Y') }}</time>
                        @else
                            <span class="token-empty">Not recorded</span>
                        @endif
                    </td>

                    <td>
                        <span class="token-quantity">{{ $demandVal }}</span>
                        @if($demandLabel)
                            <span class="token-type-badge">{{ $demandLabel }}</span>
                        @endif
                    </td>

                    <td class="col-hide-md">
                        <div class="token-approval-value">{{ $token->approved_workers ?? '—' }}</div>
                        <button class="token-workers-button" type="button"
                            data-token-modal-url="{{ route('tokens.workers.modal', $token) }}">
                            <i class="bi bi-people" aria-hidden="true"></i><span>Workers</span><strong>{{ $token->workers_count }}</strong>
                        </button>
                    </td>

                    <td class="col-hide-md">
                        @if($token->bhc_number)
                            <span class="bhc-reference">{{ $token->bhc_number }}</span>
                        @else
                            <span class="bhc-pending"><i class="bi bi-clock" aria-hidden="true"></i>Pending</span>
                        @endif
                    </td>

                    <td>
                        <span class="status {{ match($token->boesl_status) {
                            'submitted'    => 'status-success',
                            'returned'     => 'status-danger',
                            'not-required' => 'status-neutral',
                            default        => 'status-warning',
                        } }}" aria-label="BOESL status: {{ ucwords(str_replace('-', ' ', $token->boesl_status)) }}">{{ ucwords(str_replace('-', ' ', $token->boesl_status)) }}</span>
                    </td>

                    <td class="text-end pe-4">
                        <div class="token-row-actions">
                        <button class="token-action token-action-view" type="button"
                            data-token-modal-url="{{ route('tokens.modal', $token) }}">
                            <i class="bi bi-eye" aria-hidden="true"></i><span>View</span>
                        </button>
                        @if(auth()->user()->hasAnyRole('super-admin', 'administrator'))
                            <a class="token-action token-action-edit" href="{{ route('tokens.edit', $token) }}">
                                <i class="bi bi-pencil-square" aria-hidden="true"></i><span>Edit</span>
                            </a>
                        @endif
                        </div>
                    </td>

                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center text-secondary py-5">
                        No token submissions match these filters.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{-- ══════════════════════════════════════════════
         MOBILE CARD VIEW  (<768 px)
    ══════════════════════════════════════════════ --}}
    <div class="token-card-view p-3">
        @forelse($tokens as $token)
            @php
                $demandVal = match (true) {
                    $token->isVA() => $token->required_visa_attestation ?? '—',
                    $token->isChangePreWorker() => $token->required_worker_changes ?? '—',
                    default => $token->demanded_workers ?? '—',
                };
                $demandTitle = match (true) {
                    $token->isVA() => 'Visa Attestations',
                    $token->isChangePreWorker() => 'Workers Requiring Change',
                    default => 'Demanded Workers',
                };
            @endphp
            <div class="token-card token-register-mobile-card">
                <div class="token-card-header">
                    <div>
                        <div class="token-card-title"><span class="token-reference-prefix">REF</span>{{ $token->token_number }}</div>
                        <div class="small text-secondary">{{ $token->category->name ?? '—' }}</div>
                    </div>
                    <span class="status {{ match($token->boesl_status) {
                        'submitted'    => 'status-success',
                        'returned'     => 'status-danger',
                        'not-required' => 'status-neutral',
                        default        => 'status-warning',
                    } }}">{{ ucwords(str_replace('-', ' ', $token->boesl_status)) }}</span>
                </div>

                <dl class="mb-0">
                    <div class="token-card-row">
                        <dt>Agency</dt>
                        <dd>{{ $token->agency->name ?? '—' }}</dd>
                    </div>
                    <div class="token-card-row">
                        <dt>Company</dt>
                        <dd>{{ $token->company->name ?? '—' }}</dd>
                    </div>
                    <div class="token-card-row">
                        <dt>Received On</dt>
                        <dd>{{ $token->received_on ? $token->received_on->format('d M Y') : '—' }}</dd>
                    </div>
                    <div class="token-card-row">
                        <dt>{{ $demandTitle }}</dt>
                        <dd>{{ $demandVal }}</dd>
                    </div>
                    <div class="token-card-row">
                        <dt>Approved Workers</dt>
                        <dd>{{ $token->approved_workers ?? '—' }}</dd>
                    </div>
                    <div class="token-card-row">
                        <dt>BHC No.</dt>
                        <dd>{{ $token->bhc_number ?: '—' }}</dd>
                    </div>
                    @if($token->pre_selected)
                    <div class="token-card-row">
                        <dt>Pre-selected</dt>
                        <dd><span class="badge bg-info-subtle text-info-emphasis">Yes</span></dd>
                    </div>
                    @endif
                    <div class="token-card-row">
                        <dt>Workers</dt>
                        <dd>{{ $token->workers_count }}</dd>
                    </div>
                </dl>

                <div class="token-card-actions">
                    <button class="token-action token-action-workers" type="button"
                        data-token-modal-url="{{ route('tokens.workers.modal', $token) }}">
                        <i class="bi bi-people"></i>Workers <strong>{{ $token->workers_count }}</strong>
                    </button>
                    <button class="token-action token-action-view" type="button"
                        data-token-modal-url="{{ route('tokens.modal', $token) }}">
                        <i class="bi bi-eye"></i>View details
                    </button>
                    @if(auth()->user()->hasAnyRole('super-admin', 'administrator'))
                        <a class="token-action token-action-edit" href="{{ route('tokens.edit', $token) }}">
                            <i class="bi bi-pencil-square"></i>Edit
                        </a>
                    @endif
                </div>
            </div>
        @empty
            <div class="text-center text-secondary py-5">
                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                No token submissions match these filters.
            </div>
        @endforelse
    </div>

    <x-list-pagination :paginator="$tokens" />
</div>

@endsection
