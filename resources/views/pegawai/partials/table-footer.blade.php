@php
    $pageSizes = $pageSizes ?? [10, 25, 50];
    $currentPageSize = (int) request()->query('per_page', $paginator->perPage());
    $query = request()->except(['page', 'per_page']);
@endphp

<div class="pegawai-table-footer d-flex justify-content-between align-items-center flex-wrap gap-3 border-top pt-3">
    <form method="GET" class="d-flex align-items-center flex-wrap gap-2 mb-0">
        @foreach ($query as $name => $value)
            @if (is_array($value))
                @foreach ($value as $item)
                    <input type="hidden" name="{{ $name }}[]" value="{{ $item }}">
                @endforeach
            @else
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endif
        @endforeach

        <span class="text-sm text-muted">
            Showing {{ $paginator->firstItem() ?? 0 }} to {{ $paginator->lastItem() ?? 0 }} of {{ $paginator->total() }} records
        </span>
        <select name="per_page" class="form-select form-select-sm pegawai-page-size-select" onchange="this.form.submit()" aria-label="Jumlah data per halaman">
            @foreach ($pageSizes as $pageSize)
                <option value="{{ $pageSize }}" @selected($currentPageSize === $pageSize)>{{ $pageSize }}</option>
            @endforeach
        </select>
    </form>

    <div class="pegawai-pagination">
        {{ $paginator->appends(request()->except('page'))->links('pagination::bootstrap-5') }}
    </div>
</div>
