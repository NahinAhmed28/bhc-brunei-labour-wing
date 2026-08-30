@props(['paginator'])

@if($paginator->hasPages())
    <div {{ $attributes->class(['card-footer', 'bg-white', 'list-pagination']) }}>
        <p class="pagination-summary mb-0">
            Showing <strong>{{ number_format($paginator->firstItem()) }}</strong>
            to <strong>{{ number_format($paginator->lastItem()) }}</strong>
            of <strong>{{ number_format($paginator->total()) }}</strong> results
        </p>
        {{ $paginator->onEachSide(1)->links() }}
    </div>
@endif
