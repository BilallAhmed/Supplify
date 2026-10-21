@if ($paginator->hasPages())
    <nav class="shop-pager" role="navigation" aria-label="{{ __('Pagination Navigation') }}">
        <p class="shop-pager__summary">
            @if ($paginator->firstItem())
                Showing
                <span class="shop-pager__strong">{{ $paginator->firstItem() }}</span>
                to
                <span class="shop-pager__strong">{{ $paginator->lastItem() }}</span>
                of
                <span class="shop-pager__strong">{{ $paginator->total() }}</span>
                results
            @else
                Showing
                <span class="shop-pager__strong">{{ $paginator->count() }}</span>
                results
            @endif
        </p>

        <ul class="shop-pager__list">
            <li>
                @if ($paginator->onFirstPage())
                    <span class="shop-pager__btn is-disabled" aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
                        <span aria-hidden="true">&lsaquo;</span>
                    </span>
                @else
                    <a class="shop-pager__btn" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="{{ __('pagination.previous') }}">
                        <span aria-hidden="true">&lsaquo;</span>
                    </a>
                @endif
            </li>

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li>
                        <span class="shop-pager__ellipsis" aria-hidden="true">{{ $element }}</span>
                    </li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li>
                            @if ($page == $paginator->currentPage())
                                <span class="shop-pager__btn is-current" aria-current="page">{{ $page }}</span>
                            @else
                                <a class="shop-pager__btn" href="{{ $url }}" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">
                                    {{ $page }}
                                </a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach

            <li>
                @if ($paginator->hasMorePages())
                    <a class="shop-pager__btn" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="{{ __('pagination.next') }}">
                        <span aria-hidden="true">&rsaquo;</span>
                    </a>
                @else
                    <span class="shop-pager__btn is-disabled" aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                        <span aria-hidden="true">&rsaquo;</span>
                    </span>
                @endif
            </li>
        </ul>
    </nav>
@endif
