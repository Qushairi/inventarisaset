@extends('layouts.app')

@section('title', 'Data Kategori Aset')

@section('content')
    <div class="page-heading">
        @include('admin.partials.page-header', [
            'title' => 'Data Kategori Aset',
            'subtitle' => 'Kelola kategori untuk setiap aset.',
            'breadcrumb' => 'Kategori',
        ])
    </div>

    <div class="page-content">
        <section class="section">
            @if (session('success'))
                <div class="alert alert-light-success color-success">
                    <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-light-danger color-danger">
                    <i class="bi bi-exclamation-circle me-1"></i>{{ session('error') }}
                </div>
            @endif

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h4 class="card-title mb-1">Daftar Kategori</h4>
                        <p class="mb-0 text-muted">Kelompokkan aset dengan kategori yang jelas agar pencatatan inventaris lebih rapi.</p>
                    </div>
                    <a href="{{ route('admin.categories.create') }}" class="btn btn-primary btn-sm icon icon-left">
                        <i class="bi bi-plus-circle"></i><span>Tambah Kategori</span>
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-lg">
                            <thead>
                                <tr>
                                    <th>Kategori</th>
                                    <th>Deskripsi</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($categories as $category)
                                    <tr>
                                        <td>
                                            <h6 class="mb-0">{{ $category['name'] }}</h6>
                                            <small class="text-muted">{{ $category['code'] }}</small>
                                        </td>
                                        <td>
                                            <div>{{ $category['description'] }}</div>
                                            <small class="text-muted">{{ $category['note'] }}</small>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-inline-flex flex-nowrap gap-2">
                                                <a href="{{ route('admin.categories.edit', $category['code']) }}" class="btn btn-sm btn-light-primary icon icon-left">
                                                    <i class="bi bi-pencil-square"></i><span>Edit</span>
                                                </a>
                                                <form action="{{ route('admin.categories.destroy', $category['code']) }}" method="POST" class="d-inline-block">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-light-danger icon icon-left" onclick="return confirm('Hapus kategori ini?')">
                                                        <i class="bi bi-trash"></i><span>Hapus</span>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">Belum ada data kategori.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @include('admin.partials.table-footer', [
                        'from' => $categories->firstItem() ?? 0,
                        'to' => $categories->lastItem() ?? 0,
                        'total' => $categories->total(),
                        'label' => 'kategori',
                    ])
                </div>
            </div>
        </section>
    </div>
@endsection
