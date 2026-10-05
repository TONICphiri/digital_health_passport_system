@if ($paginator->hasPages())
    <nav class="flex flex-wrap items-center justify-between gap-3 border-t border-line px-5 py-3 text-sm" aria-label="Pages">
        <p class="text-muted">
            Showing {{ $paginator->firstItem() }} to {{ $paginator->lastItem() }} of {{ $paginator->total() }}
        </p>
        <div class="flex">
            @if ($paginator->onFirstPage())
                <span class="border border-line bg-paper px-3 py-1.5 text-muted">Previous</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="border border-line bg-white px-3 py-1.5 hover:border-brand-600">Previous</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="-ml-px border border-line bg-white px-3 py-1.5 text-muted">{{ $element }}</span>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="-ml-px border border-brand-700 bg-brand-700 px-3 py-1.5 text-white" aria-current="page">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="-ml-px border border-line bg-white px-3 py-1.5 hover:border-brand-600">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="-ml-px border border-line bg-white px-3 py-1.5 hover:border-brand-600">Next</a>
            @else
                <span class="-ml-px border border-line bg-paper px-3 py-1.5 text-muted">Next</span>
            @endif
        </div>
    </nav>
@endif
