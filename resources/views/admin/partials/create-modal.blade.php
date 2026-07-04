@php
    $config = match ($resource) {
        'category' => [
            'id' => 'adminCategoryCreateModal',
            'title' => 'Tambah Kategori',
            'subtitle' => 'Masukkan informasi kategori aset baru.',
            'icon' => 'tags',
            'action' => route('admin.categories.store'),
        ],
        'location' => [
            'id' => 'adminLocationCreateModal',
            'title' => 'Tambah Lokasi',
            'subtitle' => 'Masukkan informasi lokasi penyimpanan aset.',
            'icon' => 'geo-alt',
            'action' => route('admin.locations.store'),
        ],
        'asset' => [
            'id' => 'adminAssetCreateModal',
            'title' => 'Tambah Aset',
            'subtitle' => 'Lengkapi identitas dan informasi inventaris aset.',
            'icon' => 'box-seam',
            'action' => route('admin.assets.store'),
        ],
        'employee' => [
            'id' => 'adminEmployeeCreateModal',
            'title' => 'Tambah Pegawai',
            'subtitle' => 'Buat akun pegawai baru untuk mengakses sistem.',
            'icon' => 'person-plus',
            'action' => route('admin.employees.store'),
        ],
        'loan' => [
            'id' => 'adminLoanCreateModal',
            'title' => 'Tambah Peminjaman',
            'subtitle' => 'Catat aset, pegawai, periode, dan status peminjaman.',
            'icon' => 'box-arrow-up-right',
            'action' => route('admin.loans.store'),
        ],
        'return' => [
            'id' => 'adminReturnCreateModal',
            'title' => 'Tambah Pengembalian',
            'subtitle' => 'Catat pengembalian aset, kondisi, dan berita acara.',
            'icon' => 'arrow-return-left',
            'action' => route('admin.returns.store'),
        ],
    };

    $showCreateErrors = old('_create_modal') === $resource && $errors->any();
    $openCreateModal = $showCreateErrors || request()->boolean('create');
@endphp

<div class="modal fade" id="{{ $config['id'] }}" tabindex="-1" aria-labelledby="{{ $config['id'] }}Label" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content transaction-modal {{ $resource === 'return' ? 'is-return' : 'is-loan' }}">
            <div class="modal-header transaction-modal-header">
                <div class="transaction-form-title">
                    <span class="transaction-form-icon">
                        <i class="bi bi-{{ $config['icon'] }}"></i>
                    </span>
                    <div>
                        <h5 class="modal-title" id="{{ $config['id'] }}Label">{{ $config['title'] }}</h5>
                        <small class="text-muted">{{ $config['subtitle'] }}</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ $config['action'] }}" method="POST" @if ($resource === 'asset') enctype="multipart/form-data" @endif>
                @csrf
                <input type="hidden" name="_create_modal" value="{{ $resource }}">

                <div class="modal-body transaction-modal-body">
                    @if ($showCreateErrors)
                        <div class="alert alert-light-danger color-danger">
                            <i class="bi bi-exclamation-circle me-1"></i>{{ $errors->first() }}
                        </div>
                    @endif

                    @if ($resource === 'category')
                        <div class="transaction-form-section">
                            <div class="transaction-section-heading">
                                <span><i class="bi bi-clipboard-data"></i></span>
                                <div><h5>Data Kategori</h5><small>Nama, kode, dan deskripsi kategori</small></div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6 col-12">
                                    <div class="form-group transaction-field">
                                        <label for="admin_category_name">Nama Kategori</label>
                                        <div class="transaction-input-shell">
                                            <span class="transaction-input-icon"><i class="bi bi-tag"></i></span>
                                            <input type="text" id="admin_category_name" name="name" class="form-control @if($showCreateErrors) @error('name') is-invalid @enderror @endif" value="{{ $showCreateErrors ? old('name') : '' }}" placeholder="Nama kategori" required>
                                        </div>
                                        @if($showCreateErrors) @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror @endif
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="form-group transaction-field">
                                        <label for="admin_category_code">Kode Kategori</label>
                                        <div class="transaction-input-shell">
                                            <span class="transaction-input-icon"><i class="bi bi-hash"></i></span>
                                            <input type="text" id="admin_category_code" name="code" class="form-control @if($showCreateErrors) @error('code') is-invalid @enderror @endif" value="{{ $showCreateErrors ? old('code') : '' }}" placeholder="Kode kategori" required>
                                        </div>
                                        @if($showCreateErrors) @error('code')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror @endif
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group transaction-field">
                                        <label for="admin_category_description">Deskripsi</label>
                                        <div class="transaction-input-shell">
                                            <span class="transaction-input-icon"><i class="bi bi-card-text"></i></span>
                                            <input type="text" id="admin_category_description" name="description" class="form-control @if($showCreateErrors) @error('description') is-invalid @enderror @endif" value="{{ $showCreateErrors ? old('description') : '' }}" placeholder="Deskripsi kategori" required>
                                        </div>
                                        @if($showCreateErrors) @error('description')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="transaction-form-section mb-0">
                            <div class="transaction-section-heading">
                                <span><i class="bi bi-chat-left-text"></i></span>
                                <div><h5>Catatan</h5><small>Informasi tambahan kategori</small></div>
                            </div>
                            <div class="form-group transaction-field mb-0">
                                <label for="admin_category_note">Catatan</label>
                                <div class="transaction-input-shell transaction-textarea-shell">
                                    <span class="transaction-input-icon"><i class="bi bi-pencil-square"></i></span>
                                    <textarea id="admin_category_note" name="note" class="form-control" rows="4" placeholder="Catatan tambahan kategori">{{ $showCreateErrors ? old('note') : '' }}</textarea>
                                </div>
                            </div>
                        </div>
                    @elseif ($resource === 'location')
                        <div class="transaction-form-section">
                            <div class="transaction-section-heading">
                                <span><i class="bi bi-geo-alt"></i></span>
                                <div><h5>Identitas Lokasi</h5><small>Nama dan kode lokasi aset</small></div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6 col-12">
                                    <div class="form-group transaction-field">
                                        <label for="admin_location_name">Nama Lokasi</label>
                                        <div class="transaction-input-shell"><span class="transaction-input-icon"><i class="bi bi-building"></i></span><input type="text" id="admin_location_name" name="name" class="form-control @if($showCreateErrors) @error('name') is-invalid @enderror @endif" value="{{ $showCreateErrors ? old('name') : '' }}" placeholder="Nama lokasi" required></div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="form-group transaction-field">
                                        <label for="admin_location_code">Kode Lokasi</label>
                                        <div class="transaction-input-shell"><span class="transaction-input-icon"><i class="bi bi-hash"></i></span><input type="text" id="admin_location_code" name="code" class="form-control @if($showCreateErrors) @error('code') is-invalid @enderror @endif" value="{{ $showCreateErrors ? old('code') : '' }}" placeholder="Kode lokasi" required></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="transaction-form-section">
                            <div class="transaction-section-heading">
                                <span><i class="bi bi-map"></i></span>
                                <div><h5>Alamat</h5><small>Alamat dan petunjuk lokasi</small></div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6 col-12">
                                    <div class="form-group transaction-field">
                                        <label for="admin_location_address">Alamat</label>
                                        <div class="transaction-input-shell"><span class="transaction-input-icon"><i class="bi bi-pin-map"></i></span><input type="text" id="admin_location_address" name="address" class="form-control @if($showCreateErrors) @error('address') is-invalid @enderror @endif" value="{{ $showCreateErrors ? old('address') : '' }}" placeholder="Alamat lokasi" required></div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="form-group transaction-field">
                                        <label for="admin_location_address_note">Catatan Alamat</label>
                                        <div class="transaction-input-shell"><span class="transaction-input-icon"><i class="bi bi-signpost"></i></span><input type="text" id="admin_location_address_note" name="address_note" class="form-control" value="{{ $showCreateErrors ? old('address_note') : '' }}" placeholder="Petunjuk tambahan"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="transaction-form-section mb-0">
                            <div class="transaction-section-heading">
                                <span><i class="bi bi-chat-left-text"></i></span>
                                <div><h5>Keterangan</h5><small>Deskripsi dan catatan lokasi</small></div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6 col-12"><div class="form-group transaction-field"><label for="admin_location_description">Deskripsi</label><div class="transaction-input-shell transaction-textarea-shell"><span class="transaction-input-icon"><i class="bi bi-card-text"></i></span><textarea id="admin_location_description" name="description" class="form-control" rows="4" required>{{ $showCreateErrors ? old('description') : '' }}</textarea></div></div></div>
                                <div class="col-md-6 col-12"><div class="form-group transaction-field"><label for="admin_location_note">Catatan</label><div class="transaction-input-shell transaction-textarea-shell"><span class="transaction-input-icon"><i class="bi bi-pencil-square"></i></span><textarea id="admin_location_note" name="note" class="form-control" rows="4">{{ $showCreateErrors ? old('note') : '' }}</textarea></div></div></div>
                            </div>
                        </div>
                    @elseif ($resource === 'employee')
                        <div class="transaction-form-section">
                            <div class="transaction-section-heading">
                                <span><i class="bi bi-person-badge"></i></span>
                                <div><h5>Data Pegawai</h5><small>Identitas akun pegawai</small></div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6 col-12"><div class="form-group transaction-field"><label for="admin_employee_name">Nama Pegawai</label><div class="transaction-input-shell"><span class="transaction-input-icon"><i class="bi bi-person"></i></span><input type="text" id="admin_employee_name" name="name" class="form-control @if($showCreateErrors) @error('name') is-invalid @enderror @endif" value="{{ $showCreateErrors ? old('name') : '' }}" placeholder="Nama pegawai" required></div></div></div>
                                <div class="col-md-6 col-12"><div class="form-group transaction-field"><label for="admin_employee_nip">NIP</label><div class="transaction-input-shell"><span class="transaction-input-icon"><i class="bi bi-hash"></i></span><input type="text" inputmode="numeric" maxlength="30" id="admin_employee_nip" name="nip" class="form-control @if($showCreateErrors) @error('nip') is-invalid @enderror @endif" value="{{ $showCreateErrors ? old('nip') : '' }}" placeholder="Nomor Induk Pegawai" required></div></div></div>
                                <div class="col-md-6 col-12"><div class="form-group transaction-field"><label for="admin_employee_email">Email</label><div class="transaction-input-shell"><span class="transaction-input-icon"><i class="bi bi-envelope"></i></span><input type="email" id="admin_employee_email" name="email" class="form-control @if($showCreateErrors) @error('email') is-invalid @enderror @endif" value="{{ $showCreateErrors ? old('email') : '' }}" placeholder="pegawai@example.com" required></div></div></div>
                            </div>
                        </div>
                        <div class="transaction-form-section mb-0">
                            <div class="transaction-section-heading">
                                <span><i class="bi bi-shield-lock"></i></span>
                                <div><h5>Keamanan Akun</h5><small>Password untuk login pegawai</small></div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6 col-12"><div class="form-group transaction-field"><label for="admin_employee_password">Password</label><div class="transaction-input-shell"><span class="transaction-input-icon"><i class="bi bi-key"></i></span><input type="password" id="admin_employee_password" name="password" class="form-control @if($showCreateErrors) @error('password') is-invalid @enderror @endif" placeholder="Minimal 8 karakter" required></div></div></div>
                                <div class="col-md-6 col-12"><div class="form-group transaction-field"><label for="admin_employee_password_confirmation">Konfirmasi Password</label><div class="transaction-input-shell"><span class="transaction-input-icon"><i class="bi bi-check2-shield"></i></span><input type="password" id="admin_employee_password_confirmation" name="password_confirmation" class="form-control" placeholder="Ulangi password" required></div></div></div>
                            </div>
                        </div>
                    @elseif ($resource === 'asset')
                        <div class="transaction-form-section">
                            <div class="transaction-section-heading"><span><i class="bi bi-clipboard-data"></i></span><div><h5>Identitas Aset</h5><small>Nama, kode, kategori, dan lokasi</small></div></div>
                            <div class="row g-3">
                                <div class="col-md-6 col-12"><div class="form-group transaction-field"><label for="admin_asset_name">Nama Aset</label><div class="transaction-input-shell"><span class="transaction-input-icon"><i class="bi bi-box"></i></span><input type="text" id="admin_asset_name" name="name" class="form-control" value="{{ $showCreateErrors ? old('name') : '' }}" required></div></div></div>
                                <div class="col-md-6 col-12"><div class="form-group transaction-field"><label for="admin_asset_code">Kode Aset</label><div class="transaction-input-shell"><span class="transaction-input-icon"><i class="bi bi-hash"></i></span><input type="text" id="admin_asset_code" name="code" class="form-control" value="{{ $showCreateErrors ? old('code') : '' }}" required></div></div></div>
                                <div class="col-md-6 col-12"><div class="form-group transaction-field"><label for="admin_asset_category">Kategori</label><div class="transaction-input-shell"><span class="transaction-input-icon"><i class="bi bi-tags"></i></span><select id="admin_asset_category" name="category_id" class="form-select" required><option value="">Pilih kategori</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected($showCreateErrors && old('category_id') == $category->id)>{{ $category->name }} ({{ $category->code }})</option>@endforeach</select></div></div></div>
                                <div class="col-md-6 col-12"><div class="form-group transaction-field"><label for="admin_asset_location">Lokasi</label><div class="transaction-input-shell"><span class="transaction-input-icon"><i class="bi bi-geo-alt"></i></span><select id="admin_asset_location" name="location_id" class="form-select" required><option value="">Pilih lokasi</option>@foreach($locations as $location)<option value="{{ $location->id }}" @selected($showCreateErrors && old('location_id') == $location->id)>{{ $location->name }} ({{ $location->code }})</option>@endforeach</select></div></div></div>
                            </div>
                        </div>
                        <div class="transaction-form-section">
                            <div class="transaction-section-heading"><span><i class="bi bi-sliders"></i></span><div><h5>Spesifikasi</h5><small>Kondisi, status, jumlah, dan detail fisik</small></div></div>
                            <div class="row g-3">
                                <div class="col-md-3 col-6"><div class="form-group transaction-field"><label for="admin_asset_condition">Kondisi</label><div class="transaction-input-shell"><select id="admin_asset_condition" name="condition" class="form-select" required>@foreach($conditions as $condition)<option value="{{ $condition }}" @selected(($showCreateErrors ? old('condition', 'Baik') : 'Baik') === $condition)>{{ $condition }}</option>@endforeach</select></div></div></div>
                                <div class="col-md-3 col-6"><div class="form-group transaction-field"><label for="admin_asset_status">Status</label><div class="transaction-input-shell"><select id="admin_asset_status" name="status" class="form-select" required>@foreach($statuses as $status)<option value="{{ $status }}" @selected(($showCreateErrors ? old('status', 'Tersedia') : 'Tersedia') === $status)>{{ $status }}</option>@endforeach</select></div></div></div>
                                <div class="col-md-3 col-6"><div class="form-group transaction-field"><label for="admin_asset_quantity">Jumlah</label><div class="transaction-input-shell"><input type="number" min="1" id="admin_asset_quantity" name="quantity" class="form-control" value="{{ $showCreateErrors ? old('quantity', 1) : 1 }}" required></div></div></div>
                                <div class="col-md-3 col-6"><div class="form-group transaction-field"><label for="admin_asset_year">Tahun</label><div class="transaction-input-shell"><input type="number" min="1900" max="{{ now()->addYear()->year }}" id="admin_asset_year" name="acquisition_year" class="form-control" value="{{ $showCreateErrors ? old('acquisition_year') : '' }}"></div></div></div>
                                <div class="col-md-4 col-12"><div class="form-group transaction-field"><label for="admin_asset_serial">Nomor Seri</label><div class="transaction-input-shell"><input type="text" id="admin_asset_serial" name="serial_number" class="form-control" value="{{ $showCreateErrors ? old('serial_number') : '' }}"></div></div></div>
                                <div class="col-md-4 col-6"><div class="form-group transaction-field"><label for="admin_asset_size">Ukuran</label><div class="transaction-input-shell"><input type="text" id="admin_asset_size" name="size" class="form-control" value="{{ $showCreateErrors ? old('size') : '' }}"></div></div></div>
                                <div class="col-md-4 col-6"><div class="form-group transaction-field"><label for="admin_asset_material">Bahan</label><div class="transaction-input-shell"><input type="text" id="admin_asset_material" name="material" class="form-control" value="{{ $showCreateErrors ? old('material') : '' }}"></div></div></div>
                            </div>
                        </div>
                        <div class="transaction-form-section mb-0">
                            <div class="transaction-section-heading"><span><i class="bi bi-cash-stack"></i></span><div><h5>Perolehan dan Dokumen</h5><small>Nilai, gambar, dan catatan aset</small></div></div>
                            <div class="row g-3">
                                <div class="col-md-6 col-12"><div class="form-group transaction-field"><label for="admin_asset_price">Nilai Perolehan</label><div class="transaction-input-shell"><span class="transaction-input-icon"><i class="bi bi-cash"></i></span><input type="number" min="0" step="0.01" id="admin_asset_price" name="acquisition_price" class="form-control" value="{{ $showCreateErrors ? old('acquisition_price') : '' }}" required></div></div></div>
                                <div class="col-md-6 col-12"><div class="form-group transaction-field"><label for="admin_asset_image">Upload Gambar</label><div class="transaction-input-shell"><input type="file" id="admin_asset_image" name="image_file" class="form-control" accept=".jpg,.jpeg,.png,.webp"></div></div></div>
                                <div class="col-12"><div class="form-group transaction-field"><label for="admin_asset_note">Catatan</label><div class="transaction-input-shell transaction-textarea-shell"><span class="transaction-input-icon"><i class="bi bi-pencil-square"></i></span><textarea id="admin_asset_note" name="note" class="form-control" rows="4">{{ $showCreateErrors ? old('note') : '' }}</textarea></div></div></div>
                            </div>
                        </div>
                    @elseif ($resource === 'loan')
                        <div class="transaction-form-section">
                            <div class="transaction-section-heading"><span><i class="bi bi-clipboard-data"></i></span><div><h5>Data Utama</h5><small>Aset dan pegawai peminjam</small></div></div>
                            <div class="row g-3">
                                <div class="col-md-6 col-12"><div class="form-group transaction-field"><label for="admin_loan_asset">Aset</label><div class="transaction-input-shell"><span class="transaction-input-icon"><i class="bi bi-archive"></i></span><select id="admin_loan_asset" name="asset_id" class="form-select" required><option value="">Pilih aset</option>@foreach($createAssets as $asset)<option value="{{ $asset->id }}" @selected($showCreateErrors && old('asset_id') == $asset->id)>{{ $asset->name }} ({{ $asset->code }}) - Stok {{ $asset->quantity }}</option>@endforeach</select></div></div></div>
                                <div class="col-md-6 col-12"><div class="form-group transaction-field"><label for="admin_loan_employee">Pegawai</label><div class="transaction-input-shell"><span class="transaction-input-icon"><i class="bi bi-person-badge"></i></span><select id="admin_loan_employee" name="user_id" class="form-select" required><option value="">Pilih pegawai</option>@foreach($createEmployees as $employee)<option value="{{ $employee->id }}" @selected($showCreateErrors && old('user_id') == $employee->id)>{{ $employee->name }} ({{ $employee->email }})</option>@endforeach</select></div></div></div>
                            </div>
                        </div>
                        <div class="transaction-form-section">
                            <div class="transaction-section-heading"><span><i class="bi bi-calendar-week"></i></span><div><h5>Periode</h5><small>Tanggal, jumlah, dan status peminjaman</small></div></div>
                            <div class="row g-3">
                                <div class="col-md-3 col-6"><div class="form-group transaction-field"><label for="admin_loan_date">Tanggal Pinjam</label><div class="transaction-input-shell"><input type="date" id="admin_loan_date" name="loan_date" class="form-control" value="{{ $showCreateErrors ? old('loan_date') : now()->format('Y-m-d') }}" required></div></div></div>
                                <div class="col-md-3 col-6"><div class="form-group transaction-field"><label for="admin_loan_return">Rencana Kembali</label><div class="transaction-input-shell"><input type="date" id="admin_loan_return" name="planned_return_date" class="form-control" value="{{ $showCreateErrors ? old('planned_return_date') : '' }}"></div></div></div>
                                <div class="col-md-3 col-6"><div class="form-group transaction-field"><label for="admin_loan_quantity">Jumlah</label><div class="transaction-input-shell"><input type="number" min="1" id="admin_loan_quantity" name="quantity" class="form-control" value="{{ $showCreateErrors ? old('quantity', 1) : 1 }}" required></div></div></div>
                                <div class="col-md-3 col-6"><div class="form-group transaction-field"><label for="admin_loan_status">Status</label><div class="transaction-input-shell"><select id="admin_loan_status" name="status" class="form-select" required>@foreach($createStatuses as $status)<option value="{{ $status }}" @selected(($showCreateErrors ? old('status', 'Menunggu') : 'Menunggu') === $status)>{{ $status }}</option>@endforeach</select></div></div></div>
                            </div>
                        </div>
                        <div class="transaction-form-section mb-0">
                            <div class="transaction-section-heading"><span><i class="bi bi-chat-left-text"></i></span><div><h5>Keterangan</h5><small>Catatan status peminjaman</small></div></div>
                            <div class="form-group transaction-field mb-0"><label for="admin_loan_note">Keterangan</label><div class="transaction-input-shell transaction-textarea-shell"><span class="transaction-input-icon"><i class="bi bi-pencil-square"></i></span><textarea id="admin_loan_note" name="status_note" class="form-control" rows="4">{{ $showCreateErrors ? old('status_note') : '' }}</textarea></div></div>
                        </div>
                    @elseif ($resource === 'return')
                        <div class="transaction-form-section">
                            <div class="transaction-section-heading"><span><i class="bi bi-clipboard-data"></i></span><div><h5>Data Pengembalian</h5><small>Aset, pegawai, dan peminjaman terkait</small></div></div>
                            <div class="row g-3">
                                <div class="col-md-4 col-12"><div class="form-group transaction-field"><label for="admin_return_loan">Peminjaman Terkait</label><div class="transaction-input-shell"><select id="admin_return_loan" name="loan_id" class="form-select"><option value="">Opsional</option>@foreach($createLoans as $loan)<option value="{{ $loan->id }}" @selected($showCreateErrors && old('loan_id') == $loan->id)>{{ $loan->asset?->name }} - {{ $loan->user?->name }} - {{ optional($loan->loan_date)->format('d/m/Y') }}</option>@endforeach</select></div></div></div>
                                <div class="col-md-4 col-12"><div class="form-group transaction-field"><label for="admin_return_asset">Aset</label><div class="transaction-input-shell"><select id="admin_return_asset" name="asset_id" class="form-select" required><option value="">Pilih aset</option>@foreach($createAssets as $asset)<option value="{{ $asset->id }}" @selected($showCreateErrors && old('asset_id') == $asset->id)>{{ $asset->name }} ({{ $asset->code }})</option>@endforeach</select></div></div></div>
                                <div class="col-md-4 col-12"><div class="form-group transaction-field"><label for="admin_return_employee">Pegawai</label><div class="transaction-input-shell"><select id="admin_return_employee" name="user_id" class="form-select" required><option value="">Pilih pegawai</option>@foreach($createEmployees as $employee)<option value="{{ $employee->id }}" @selected($showCreateErrors && old('user_id') == $employee->id)>{{ $employee->name }} ({{ $employee->email }})</option>@endforeach</select></div></div></div>
                            </div>
                        </div>
                        <div class="transaction-form-section">
                            <div class="transaction-section-heading"><span><i class="bi bi-clipboard-check"></i></span><div><h5>Pemeriksaan</h5><small>Tanggal kembali dan kondisi aset</small></div></div>
                            <div class="row g-3">
                                <div class="col-md-4 col-12"><div class="form-group transaction-field"><label for="admin_return_date">Tanggal Pengembalian</label><div class="transaction-input-shell"><input type="date" id="admin_return_date" name="returned_at" class="form-control" value="{{ $showCreateErrors ? old('returned_at') : now()->format('Y-m-d') }}" required></div></div></div>
                                <div class="col-md-4 col-12"><div class="form-group transaction-field"><label for="admin_return_condition">Kondisi</label><div class="transaction-input-shell"><select id="admin_return_condition" name="condition" class="form-select" required>@foreach($conditions as $condition)<option value="{{ $condition }}" @selected(($showCreateErrors ? old('condition', 'Baik') : 'Baik') === $condition)>{{ $condition }}</option>@endforeach</select></div></div></div>
                                <div class="col-md-4 col-12"><div class="form-group transaction-field"><label for="admin_return_verified">Catatan Verifikasi</label><div class="transaction-input-shell"><input type="text" id="admin_return_verified" name="verified_note" class="form-control" value="{{ $showCreateErrors ? old('verified_note') : '' }}"></div></div></div>
                            </div>
                        </div>
                        <div class="transaction-form-section mb-0">
                            <div class="transaction-section-heading"><span><i class="bi bi-file-earmark-text"></i></span><div><h5>Berita Acara</h5><small>Nomor dan catatan dokumen pengembalian</small></div></div>
                            <div class="row g-3">
                                <div class="col-md-6 col-12"><div class="form-group transaction-field"><label for="admin_return_report_number">Nomor Berita Acara</label><div class="transaction-input-shell"><input type="text" id="admin_return_report_number" name="report_number" class="form-control" value="{{ $showCreateErrors ? old('report_number') : '' }}" required></div></div></div>
                                <div class="col-md-6 col-12"><div class="form-group transaction-field"><label for="admin_return_status_note">Keterangan Status</label><div class="transaction-input-shell"><input type="text" id="admin_return_status_note" name="status_note" class="form-control" value="{{ $showCreateErrors ? old('status_note') : '' }}"></div></div></div>
                                <div class="col-12"><div class="form-group transaction-field"><label for="admin_return_report_note">Catatan Berita Acara</label><div class="transaction-input-shell transaction-textarea-shell"><textarea id="admin_return_report_note" name="report_note" class="form-control" rows="4">{{ $showCreateErrors ? old('report_note') : '' }}</textarea></div></div></div>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="modal-footer transaction-modal-footer">
                    <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary icon icon-left">
                        <i class="bi bi-check-circle"></i><span>Simpan</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@if ($openCreateModal)
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const modalElement = document.getElementById(@json($config['id']));

                if (modalElement) {
                    new bootstrap.Modal(modalElement).show();
                }
            });
        </script>
    @endpush
@endif
