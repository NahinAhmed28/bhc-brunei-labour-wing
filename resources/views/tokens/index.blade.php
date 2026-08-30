@extends('layouts.app')

@section('title', 'Token Submissions')

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
            <span class="stat-pill"><i class="bi bi-ticket-perforated" aria-hidden="true"></i> {{ number_format($tokens->total()) }} total</span>
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
<div class="card mb-4">
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
                <label class="form-label" for="desk-filter">Desk</label>
                <select class="form-select" id="desk-filter" name="desk_id">
                    <option value="">All desks</option>
                    @foreach($desks as $d)
                        <option value="{{ $d->id }}" @selected(request('desk_id') == $d->id)>{{ $d->name }}</option>
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
                <a class="btn btn-light" href="{{ route('tokens.index') }}">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </a>
            </div>

        </form>
    </div>

    {{-- ══════════════════════════════════════════════
         DESKTOP / MID TABLE VIEW  (≥768 px)
    ══════════════════════════════════════════════ --}}
    <div class="token-table-view token-table-wrap">
        <table class="table table-hover token-table mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-4">Token</th>
                    <th>Category</th>
                    <th>Agency</th>
                    <th>Company</th>
                    {{-- Hidden on md, visible on lg+ --}}
                    <th class="col-hide-md">Received On</th>
                    <th>Demanded / VA</th>
                    <th class="col-hide-md">Approved</th>
                    <th class="col-hide-md">BHC No.</th>
                    <th>BOESL</th>
                    <th class="pe-4 text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($tokens as $token)
                @php
                    $isVA = strtoupper($token->category->code ?? '') === 'VA';
                    $demandVal = $isVA
                        ? ($token->required_visa_attestation ?? '—')
                        : ($token->demanded_workers ?? '—');
                    $demandLabel = $isVA ? 'VA' : null;
                @endphp
                <tr class="token-row" tabindex="0" role="button"
                    aria-haspopup="dialog" aria-controls="tokenDetailsModal"
                    aria-label="View details for {{ $token->token_number }}"
                    data-token-modal-url="{{ route('tokens.modal', $token) }}">

                    <td class="ps-4">
                        <button class="token-row-title" type="button"
                            data-token-modal-url="{{ route('tokens.modal', $token) }}">
                            {{ $token->token_number }}
                        </button>
                        <div class="small text-secondary">{{ $token->applicants_count }} applicants</div>
                        @if($token->pre_selected)
                            <span class="badge bg-info-subtle text-info-emphasis" style="font-size:.68rem">Pre-selected</span>
                        @endif
                    </td>

                    <td>
                        <span class="text-nowrap">{{ $token->category->name ?? '—' }}</span>
                    </td>

                    <td>{{ $token->agency->name ?? '—' }}</td>

                    <td>{{ $token->company->name ?? '—' }}</td>

                    <td class="col-hide-md">
                        {{ $token->received_on ? $token->received_on->format('d M Y') : '—' }}
                    </td>

                    <td>
                        <span>{{ $demandVal }}</span>
                        @if($demandLabel)
                            <span class="badge bg-secondary-subtle text-secondary-emphasis ms-1" style="font-size:.65rem">{{ $demandLabel }}</span>
                        @endif
                    </td>

                    <td class="col-hide-md">
                        <div>{{ $token->approved_workers ?? '—' }}</div>
                        <button class="btn btn-sm btn-light mt-1 token-applicants-button" type="button"
                            data-token-modal-url="{{ route('tokens.applicants.modal', $token) }}">
                            <i class="bi bi-people me-1" aria-hidden="true"></i>Applicants ({{ $token->applicants_count }})
                        </button>
                    </td>

                    <td class="col-hide-md">{{ $token->bhc_number ?: '—' }}</td>

                    <td>
                        <span class="status {{ match($token->boesl_status) {
                            'submitted'    => 'status-success',
                            'returned'     => 'status-danger',
                            'not-required' => 'status-neutral',
                            default        => 'status-warning',
                        } }}">{{ ucwords(str_replace('-', ' ', $token->boesl_status)) }}</span>
                    </td>

                    <td class="text-nowrap text-end pe-4">
                        <button class="btn btn-sm btn-light" type="button"
                            data-token-modal-url="{{ route('tokens.modal', $token) }}">
                            <i class="bi bi-eye me-1" aria-hidden="true"></i>View
                        </button>
                        @if(auth()->user()->hasAnyRole('super-admin', 'administrator'))
                            <a class="btn btn-sm btn-light" href="{{ route('tokens.edit', $token) }}">
                                <i class="bi bi-pencil-square me-1" aria-hidden="true"></i>Edit
                            </a>
                        @endif
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
                $isVA = strtoupper($token->category->code ?? '') === 'VA';
                $demandVal   = $isVA ? ($token->required_visa_attestation ?? '—') : ($token->demanded_workers ?? '—');
                $demandTitle = $isVA ? 'Visa Attestation' : 'Demanded Workers';
            @endphp
            <div class="token-card">
                <div class="token-card-header">
                    <div>
                        <div class="token-card-title">{{ $token->token_number }}</div>
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
                        <dt>Applicants</dt>
                        <dd>{{ $token->applicants_count }}</dd>
                    </div>
                </dl>

                <div class="token-card-actions">
                    <button class="btn btn-sm btn-light w-100" type="button"
                        data-token-modal-url="{{ route('tokens.applicants.modal', $token) }}">
                        <i class="bi bi-people me-1"></i>Applicants ({{ $token->applicants_count }})
                    </button>
                    <button class="btn btn-sm btn-light w-100" type="button"
                        data-token-modal-url="{{ route('tokens.modal', $token) }}">
                        <i class="bi bi-eye me-1"></i>View Details
                    </button>
                    @if(auth()->user()->hasAnyRole('super-admin', 'administrator'))
                        <a class="btn btn-sm btn-light flex-fill" href="{{ route('tokens.edit', $token) }}">
                            <i class="bi bi-pencil-square me-1"></i>Edit
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
