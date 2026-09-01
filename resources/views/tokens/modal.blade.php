<div class="modal-header token-modal-header">
    <div>
        <div class="page-eyebrow">Token dossier</div>
        <h2 class="modal-title" id="tokenDetailsModalLabel">{{ $token->token_number }}</h2>
        <p class="mb-0">{{ $token->company->name }} · {{ $token->agency->name }}</p>
    </div>
    <button class="btn-close btn-close-white" type="button" data-bs-dismiss="modal" aria-label="Close token details"></button>
</div>

<div class="modal-body p-0">
    <div class="row g-0 token-modal-layout">
        <div class="col-lg-7 token-modal-details">
            <section class="token-modal-section">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                    <h3 class="token-modal-section-title mb-0">Submission details</h3>
                    <span class="status {{ $token->file_status === 'active' ? 'status-success' : ($token->file_status === 'cancelled' ? 'status-danger' : 'status-warning') }}">
                        {{ ucwords(str_replace('-', ' ', $token->file_status)) }}
                    </span>
                </div>
                <dl class="token-detail-grid mb-0">
                    @foreach([
                        ['Token category', $token->category->name],
                        ['Company', $token->company->name],
                        ['Agency', $token->agency->name],
                        ['File holder', $token->currentHolder?->name ?: 'Unassigned'],
                        ['Agent or representative', $token->agent_name ?: 'Not recorded'],
                        ['Received on', $token->received_on->format('d M Y')],
                        ['Demanded workers', number_format($token->demanded_workers)],
                        ['Approved workers', number_format($token->approved_workers)],
                        ['Pre selected worker', $token->pre_selected ? 'Yes' : 'No'],
                        ['BHC number', $token->bhc_number ?: 'Pending'],
                    ] as [$label, $value])
                        <div>
                            <dt>{{ $label }}</dt>
                            <dd>{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>

            <section class="token-modal-section">
                <h3 class="token-modal-section-title">Processing details</h3>
                <dl class="token-detail-grid mb-0">
                    @foreach([
                        ['BOESL status', ucwords(str_replace('-', ' ', $token->boesl_status))],
                        ['BOESL date', $token->boesl_date?->format('d M Y') ?: 'Not recorded'],
                        ['Received by', $token->received_by ?: 'Not recorded'],
                        ['Visa status', ucwords(str_replace('-', ' ', $token->visa_status))],
                        ['Site visit required', $token->site_visit_required ? 'Yes' : 'No'],
                        ['Site visit date', $token->site_visit_date?->format('d M Y') ?: 'Not recorded'],
                        ['Site visit by', $token->site_visit_by ?: 'Not recorded'],
                        ['Cancelled at', $token->cancelled_at?->format('d M Y, H:i') ?: 'Not cancelled'],
                    ] as [$label, $value])
                        <div>
                            <dt>{{ $label }}</dt>
                            <dd>{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
                <div class="token-modal-note mt-3">
                    <span>Remarks</span>
                    <p class="mb-0">{{ $token->remarks ?: 'No remarks recorded.' }}</p>
                </div>
            </section>

            <section class="token-modal-section">
                <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                    <h3 class="token-modal-section-title mb-0">Workers</h3>
                    <span class="token-modal-count">{{ $token->workers->count() }}</span>
                </div>
                <p class="text-secondary">View the worker roster connected to this token.</p>
                <button class="btn btn-sm btn-light" type="button"
                    data-token-modal-url="{{ route('tokens.workers.modal', $token) }}">
                    <i class="bi bi-people me-1" aria-hidden="true"></i>View workers
                </button>
            </section>

            <section class="token-modal-section">
                <h3 class="token-modal-section-title">File transfer history</h3>
                @forelse($token->transferHistories as $history)
                    <div class="token-history-item">
                        <span class="token-history-marker" aria-hidden="true"></span>
                        <div>
                            <strong>{{ $history->previousHolder?->name ?: 'Unassigned' }} to {{ $history->newHolder?->name ?: 'Unassigned' }}</strong>
                            <div class="small text-secondary">{{ $history->transferred_at->format('d M Y, H:i') }} &middot; Transferred by {{ $history->transferredBy->name }}</div>
                            @if($history->remarks)<div class="small mt-1">{{ $history->remarks }}</div>@endif
                        </div>
                    </div>
                @empty
                    <p class="text-secondary mb-0">No file transfer has been recorded.</p>
                @endforelse
            </section>

            <section class="token-modal-section">
                <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                    <h3 class="token-modal-section-title mb-0">Documents</h3>
                    <span class="token-modal-count">{{ $token->documents->count() }}</span>
                </div>
                @forelse($token->documents as $document)
                    <div class="token-related-row">
                        <div>
                            <strong>{{ $document->original_name }}</strong>
                            <div class="small text-secondary">{{ ucwords(str_replace('-', ' ', $document->type)) }} · Version {{ $document->version }}</div>
                        </div>
                        <a class="btn btn-sm btn-light" href="{{ route('documents.download', $document) }}"><i class="bi bi-download me-1" aria-hidden="true"></i>Download</a>
                    </div>
                @empty
                    <p class="text-secondary mb-0">No token documents have been uploaded.</p>
                @endforelse
            </section>

            <section class="token-modal-section token-modal-audit">
                <h3 class="token-modal-section-title">Record information</h3>
                <dl class="token-detail-grid mb-0">
                    <div><dt>Created by</dt><dd>{{ $token->creator->name }}</dd></div>
                    <div><dt>Created at</dt><dd>{{ $token->created_at->format('d M Y, H:i') }}</dd></div>
                    <div><dt>Last updated by</dt><dd>{{ $token->updater?->name ?: 'System' }}</dd></div>
                    <div><dt>Last updated at</dt><dd>{{ $token->updated_at->format('d M Y, H:i') }}</dd></div>
                </dl>
            </section>
        </div>

        <aside class="col-lg-5 token-document-pane" aria-label="Official document previews">
            <div class="token-document-pane-header">
                <span class="detail-label">Official attachments</span>
                <strong>Confirmation and demand letters</strong>
                <p class="mb-0">Preview or download the letters attached to this token.</p>
            </div>

            @foreach(['confirmation-letter' => 'Confirmation Letter', 'demand-letter' => 'Demand Letter'] as $type => $label)
                @php($documents = $tokenDocuments->get($type, collect()))

                @forelse($documents as $document)
                    <section class="token-document-slot">
                        <div class="token-document-slot-header">
                            <div>
                                <span class="token-document-kicker">Version {{ $document->version }}</span>
                                <h3>{{ $label }}</h3>
                            </div>
                            <a class="btn btn-sm btn-light" href="{{ route('documents.download', $document) }}">
                                <i class="bi bi-download me-1" aria-hidden="true"></i>Download
                            </a>
                        </div>

                        <div class="token-document-preview">
                            @if(str_starts_with($document->mime_type, 'image/'))
                                <img src="{{ route('documents.preview', $document) }}" alt="Preview of {{ $label }} for {{ $token->token_number }}">
                            @else
                                <iframe src="{{ route('documents.preview', $document) }}" title="Preview of {{ $label }} for {{ $token->token_number }}"></iframe>
                            @endif
                        </div>
                        <p class="token-document-filename mb-0">{{ $document->original_name }} · {{ number_format($document->size / 1024, 1) }} KB</p>
                    </section>
                @empty
                    <section class="token-document-slot">
                        <div class="token-document-empty">
                            <i class="bi bi-file-earmark-arrow-up" aria-hidden="true"></i>
                            <p class="mb-0">No {{ strtolower($label) }} has been uploaded.</p>
                        </div>
                    </section>
                @endforelse

            @endforeach
        </aside>
    </div>
</div>

<div class="modal-footer">
    <button class="btn btn-light" type="button" data-bs-dismiss="modal">Close</button>
    <a class="btn btn-outline-primary" href="{{ route('tokens.pdf', $token) }}" target="_blank" rel="noopener">
        <i class="bi bi-file-pdf me-2" aria-hidden="true"></i>View PDF
    </a>
    @if(auth()->user()->hasAnyRole('super-admin', 'administrator'))
        <a class="btn btn-primary" href="{{ route('tokens.edit', $token) }}"><i class="bi bi-pencil-square me-2" aria-hidden="true"></i>Edit token</a>
    @endif
</div>
