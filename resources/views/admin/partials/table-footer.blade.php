<div class="table-footer-meta d-flex justify-content-between align-items-center flex-wrap gap-3 border-top pt-3">
    <p class="mb-0 text-sm text-muted">
        Menampilkan {{ $from ?? 1 }} - {{ $to }} dari <strong>{{ $total }}</strong> {{ $label }}.
    </p>

    @isset($paginator)
        @if ($paginator->hasPages())
            @php
                $paginator->appends(request()->except('page'));
                $currentPage = $paginator->currentPage();
                $lastPage = $paginator->lastPage();
                $pageWindow = collect([
                    1,
                    2,
                    $currentPage - 1,
                    $currentPage,
                    $currentPage + 1,
                    $lastPage - 1,
                    $lastPage,
                ])
                    ->filter(fn ($page) => $page >= 1 && $page <= $lastPage)
                    ->unique()
                    ->sort()
                    ->values();
                $previousRenderedPage = null;
            @endphp

            <nav class="admin-pagination" aria-label="Navigasi halaman">
                <ul class="pagination pagination-sm mb-0">
                    @if ($paginator->onFirstPage())
                        <li class="page-item disabled" aria-disabled="true" aria-label="Sebelumnya">
                            <span class="page-link">&lsaquo;</span>
                        </li>
                    @else
                        <li class="page-item">
                            <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Sebelumnya">&lsaquo;</a>
                        </li>
                    @endif

                    @foreach ($pageWindow as $page)
                        @if ($previousRenderedPage && $page > $previousRenderedPage + 1)
                            <li class="page-item disabled" aria-disabled="true">
                                <span class="page-link">...</span>
                            </li>
                        @endif

                        @if ($page === $currentPage)
                            <li class="page-item active" aria-current="page">
                                <span class="page-link">{{ $page }}</span>
                            </li>
                        @else
                            <li class="page-item">
                                <a class="page-link" href="{{ $paginator->url($page) }}">{{ $page }}</a>
                            </li>
                        @endif

                        @php
                            $previousRenderedPage = $page;
                        @endphp
                    @endforeach

                    @if ($paginator->hasMorePages())
                        <li class="page-item">
                            <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Berikutnya">&rsaquo;</a>
                        </li>
                    @else
                        <li class="page-item disabled" aria-disabled="true" aria-label="Berikutnya">
                            <span class="page-link">&rsaquo;</span>
                        </li>
                    @endif
                </ul>
            </nav>
        @endif
    @endisset
</div>
