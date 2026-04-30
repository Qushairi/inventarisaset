@extends('layouts.app')

@section('title', 'Data Lokasi Aset')

@section('content')
    <div class="page-heading">
        @include('admin.partials.page-header', [
            'title' => 'Data Lokasi Aset',
            'subtitle' => 'Kelola penempatan lokasi inventaris.',
            'breadcrumb' => 'Lokasi',
        ])

        <section class="section">
            <div class="row">
                <div class="col-12 col-md-4">
                    <div class="card">
                        <div class="card-body py-4-5">
                            <div class="row">
                                <div class="col-8 d-flex flex-column justify-content-center">
                                    <h6 class="text-muted font-semibold">Total Lokasi</h6>
                                    <h3 class="font-extrabold mb-0">{{ count($locations) }}</h3>
                                </div>
                                <div class="col-4">
                                    <div class="stats-icon blue">
                                        <i class="bi bi-geo-alt-fill"></i>
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
                        <h4 class="card-title mb-1">Daftar Lokasi</h4>
                        <p class="mb-0 text-muted">Atur lokasi penempatan inventaris agar pelacakan aset lebih mudah dilakukan.</p>
                    </div>
                    <a href="javascript:void(0)" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Tambah Lokasi
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-lg">
                            <thead>
                                <tr>
                                    <th>Lokasi</th>
                                    <th>Alamat</th>
                                    <th>Keterangan</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($locations as $location)
                                    <tr>
                                        <td>
                                            <h6 class="mb-0">{{ $location['name'] }}</h6>
                                            <small class="text-muted">{{ $location['code'] }}</small>
                                        </td>
                                        <td>
                                            <div>{{ $location['address'] }}</div>
                                            <small class="text-muted">{{ $location['address_note'] }}</small>
                                        </td>
                                        <td>
                                            <div>{{ $location['description'] }}</div>
                                            <small class="text-muted">{{ $location['note'] }}</small>
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
                        'to' => count($locations),
                        'total' => count($locations),
                        'label' => 'lokasi',
                    ])
                </div>
            </div>
        </section>
    </div>
@endsection
