{{-- Page links under long lists (set as the default in AppServiceProvider). --}}
@if ($paginator->hasPages())
    <nav class="pagination" aria-label="Pages">
        <span class="pagination-info">
            Showing {{ $paginator->firstItem() }} to {{ $paginator->lastItem() }} of {{ $paginator->total() }}
        </span>

        <span class="pagination-links">
            @if ($paginator->onFirstPage())
                <span class="page disabled">Previous</span>
            @else
                <a class="page" href="{{ $paginator->previousPageUrl() }}" rel="prev">Previous</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="page disabled">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="page current" aria-current="page">{{ $page }}</span>
                        @else
                            <a class="page" href="{{ $url }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a class="page" href="{{ $paginator->nextPageUrl() }}" rel="next">Next</a>
            @else
                <span class="page disabled">Next</span>
            @endif
        </span>
    </nav>
@endif
