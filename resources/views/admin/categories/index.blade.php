@extends('layouts.app')

@section('title', 'Data Kategori Aset')

@section('content')
    <div class="page-heading">
        @include('admin.partials.page-header', [
            'title' => 'Data Kategori Aset',
            'subtitle' => 'Kelola kategori untuk setiap aset.',
            'breadcrumb' => 'Kategori',
        ])

        <section class="section">
            <div class="row">
                <div class="col-12 col-md-4">
                    <div class="card">
                        <div class="card-body py-4-5">
                            <div class="row">
                                <div class="col-8 d-flex flex-column justify-content-center">
                                    <h6 class="text-muted font-semibold">Total Kategori</h6>
                                    <h3 class="font-extrabold mb-0">{{ count($categories) }}</h3>
                                </div>
                                <div class="col-4">
                                    <div class="stats-icon purple">
                                        <i class="bi bi-tags-fill"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="card-title mb-1">Daftar Kategori</h4>
                        <p class="mb-0 text-muted">Kelompokkan aset dengan kategori yang jelas agar pencatatan inventaris lebih rapi.</p>
                    </div>
                    <a href="javascript:void(0)" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Tambah Kategori
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-lg">
                            <thead>
                                <tr>
                                    <th>Kategori</th>
                                    <th>Deskripsi</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($categories as $category)
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
                                            <a href="javascript:void(0)" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i> Hapus
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @include('admin.partials.table-footer', [
                        'to' => count($categories),
                        'total' => count($categories),
                        'label' => 'kategori',
                    ])
                </div>
            </div>
        </section>
    </div>
@endsection
