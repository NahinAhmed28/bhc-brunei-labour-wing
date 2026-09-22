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
