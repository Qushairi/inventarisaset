@php
    $config = match ($resource) {
        'category' => [
            'id' => 'adminCategoryEditModal-'.$record['id'],
            'title' => 'Edit Kategori',
            'subtitle' => 'Perbarui informasi kategori aset.',
            'icon' => 'pencil-square',
            'action' => route('admin.categories.update', $record['code']),
        ],
        'location' => [
            'id' => 'adminLocationEditModal-'.$record['id'],
            'title' => 'Edit Lokasi',
            'subtitle' => 'Perbarui informasi lokasi penyimpanan aset.',
            'icon' => 'pencil-square',
            'action' => route('admin.locations.update', $record['code']),
        ],
        'asset' => [
            'id' => 'adminAssetEditModal-'.$record['id'],
            'title' => 'Edit Aset',
            'subtitle' => 'Perbarui identitas dan informasi inventaris aset.',
            'icon' => 'pencil-square',
            'action' => route('admin.assets.update', $record['id']),
        ],
        'employee' => [
            'id' => 'adminEmployeeEditModal-'.$record['id'],
            'title' => 'Edit Pegawai',
            'subtitle' => 'Perbarui identitas dan akses akun pegawai.',
            'icon' => 'pencil-square',
            'action' => route('admin.employees.update', $record['id']),
        ],
        'loan' => [
            'id' => 'adminLoanEditModal-'.$record['id'],
            'title' => 'Edit Peminjaman',
            'subtitle' => 'Perbarui aset, peminjam, periode, dan status peminjaman.',
            'icon' => 'pencil-square',
            'action' => route('admin.loans.update', $record['id']),
        ],
    };

    $fieldPrefix = 'admin_edit_'.$resource.'_'.$record['id'];
    $showEditErrors = old('_edit_modal') === $resource
        && (string) old('_edit_key') === (string) $record['id']
        && $errors->any();
    $openEditModal = $showEditErrors
        || (string) request('edit') === (string) $record['id'];
@endphp

<div class="modal fade" id="{{ $config['id'] }}" tabindex="-1" aria-labelledby="{{ $config['id'] }}Label" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content transaction-modal is-loan">
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
                @method('PUT')
                <input type="hidden" name="_edit_modal" value="{{ $resource }}">
                <input type="hidden" name="_edit_key" value="{{ $record['id'] }}">

                <div class="modal-body transaction-modal-body">
                    @if ($showEditErrors)
                        <div class="alert alert-light-danger color-danger">
                            <i class="bi bi-exclamation-circle me-1"></i>{{ $errors->first() }}
                        </div>
                    @endif

                    @if ($resource === 'category')
                        <div class="transaction-form-section">
                            <div class="row g-3">
                                <div class="col-md-6 col-12">
                                    <div class="form-group transaction-field">
                                        <label for="{{ $fieldPrefix }}_name">Nama Kategori</label>
                                        <div class="transaction-input-shell">
                                            <input type="text" id="{{ $fieldPrefix }}_name" name="name" class="form-control {{ $showEditErrors && $errors->has('name') ? 'is-invalid' : '' }}" placeholder="Nama kategori" value="{{ $showEditErrors ? old('name') : $record['name'] }}">
                                        </div>
                                        @if ($showEditErrors && $errors->has('name'))<div class="invalid-feedback d-block">{{ $errors->first('name') }}</div>@endif
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="form-group transaction-field">
                                        <label for="{{ $fieldPrefix }}_code">Kode Kategori</label>
                                        <div class="transaction-input-shell">
                                            <input type="text" id="{{ $fieldPrefix }}_code" name="code" class="form-control {{ $showEditErrors && $errors->has('code') ? 'is-invalid' : '' }}" placeholder="Kode kategori" value="{{ $showEditErrors ? old('code') : $record['code'] }}">
                                        </div>
                                        @if ($showEditErrors && $errors->has('code'))<div class="invalid-feedback d-block">{{ $errors->first('code') }}</div>@endif
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group transaction-field">
                                        <label for="{{ $fieldPrefix }}_description">Deskripsi</label>
                                        <div class="transaction-input-shell">
                                            <input type="text" id="{{ $fieldPrefix }}_description" name="description" class="form-control {{ $showEditErrors && $errors->has('description') ? 'is-invalid' : '' }}" placeholder="Deskripsi" value="{{ $showEditErrors ? old('description') : $record['description'] }}">
                                        </div>
                                        @if ($showEditErrors && $errors->has('description'))<div class="invalid-feedback d-block">{{ $errors->first('description') }}</div>@endif
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group transaction-field mb-0">
                                        <label for="{{ $fieldPrefix }}_note">Catatan</label>
                                        <div class="transaction-input-shell">
                                            <textarea id="{{ $fieldPrefix }}_note" name="note" class="form-control {{ $showEditErrors && $errors->has('note') ? 'is-invalid' : '' }}" rows="4" placeholder="Catatan tambahan kategori">{{ $showEditErrors ? old('note') : $record['edit_note'] }}</textarea>
                                        </div>
                                        @if ($showEditErrors && $errors->has('note'))<div class="invalid-feedback d-block">{{ $errors->first('note') }}</div>@endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @elseif ($resource === 'location')
                        <div class="transaction-form-section">
                            <div class="row g-3">
                                <div class="col-md-6 col-12">
                                    <div class="form-group transaction-field">
                                        <label for="{{ $fieldPrefix }}_name">Nama Lokasi</label>
                                        <div class="transaction-input-shell"><input type="text" id="{{ $fieldPrefix }}_name" name="name" class="form-control {{ $showEditErrors && $errors->has('name') ? 'is-invalid' : '' }}" placeholder="Nama lokasi" value="{{ $showEditErrors ? old('name') : $record['name'] }}"></div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="form-group transaction-field">
                                        <label for="{{ $fieldPrefix }}_code">Kode Lokasi</label>
                                        <div class="transaction-input-shell"><input type="text" id="{{ $fieldPrefix }}_code" name="code" class="form-control {{ $showEditErrors && $errors->has('code') ? 'is-invalid' : '' }}" placeholder="Kode lokasi" value="{{ $showEditErrors ? old('code') : $record['code'] }}"></div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="form-group transaction-field">
                                        <label for="{{ $fieldPrefix }}_address">Alamat</label>
                                        <div class="transaction-input-shell"><input type="text" id="{{ $fieldPrefix }}_address" name="address" class="form-control {{ $showEditErrors && $errors->has('address') ? 'is-invalid' : '' }}" placeholder="Alamat lokasi" value="{{ $showEditErrors ? old('address') : $record['address'] }}"></div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="form-group transaction-field">
                                        <label for="{{ $fieldPrefix }}_address_note">Catatan Alamat</label>
                                        <div class="transaction-input-shell"><input type="text" id="{{ $fieldPrefix }}_address_note" name="address_note" class="form-control {{ $showEditErrors && $errors->has('address_note') ? 'is-invalid' : '' }}" placeholder="Catatan alamat" value="{{ $showEditErrors ? old('address_note') : $record['edit_address_note'] }}"></div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="form-group transaction-field">
                                        <label for="{{ $fieldPrefix }}_description">Deskripsi</label>
                                        <div class="transaction-input-shell"><textarea id="{{ $fieldPrefix }}_description" name="description" class="form-control {{ $showEditErrors && $errors->has('description') ? 'is-invalid' : '' }}" rows="4" placeholder="Deskripsi lokasi">{{ $showEditErrors ? old('description') : $record['description'] }}</textarea></div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="form-group transaction-field mb-0">
                                        <label for="{{ $fieldPrefix }}_note">Catatan</label>
                                        <div class="transaction-input-shell"><textarea id="{{ $fieldPrefix }}_note" name="note" class="form-control {{ $showEditErrors && $errors->has('note') ? 'is-invalid' : '' }}" rows="4" placeholder="Catatan tambahan">{{ $showEditErrors ? old('note') : $record['edit_note'] }}</textarea></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @elseif ($resource === 'employee')
                        <div class="transaction-form-section">
                            <div class="row g-3">
                                <div class="col-md-6 col-12">
                                    <div class="form-group transaction-field">
                                        <label for="{{ $fieldPrefix }}_name">Nama Pegawai</label>
                                        <div class="transaction-input-shell"><input type="text" id="{{ $fieldPrefix }}_name" name="name" class="form-control {{ $showEditErrors && $errors->has('name') ? 'is-invalid' : '' }}" placeholder="Nama pegawai" value="{{ $showEditErrors ? old('name') : $record['name'] }}"></div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="form-group transaction-field">
                                        <label for="{{ $fieldPrefix }}_email">Email</label>
                                        <div class="transaction-input-shell"><input type="email" id="{{ $fieldPrefix }}_email" name="email" class="form-control {{ $showEditErrors && $errors->has('email') ? 'is-invalid' : '' }}" placeholder="pegawai@example.com" value="{{ $showEditErrors ? old('email') : $record['email'] }}"></div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="form-group transaction-field">
                                        <label for="{{ $fieldPrefix }}_nip">NIP</label>
                                        <div class="transaction-input-shell"><input type="text" inputmode="numeric" maxlength="30" id="{{ $fieldPrefix }}_nip" name="nip" class="form-control {{ $showEditErrors && $errors->has('nip') ? 'is-invalid' : '' }}" placeholder="Nomor Induk Pegawai" value="{{ $showEditErrors ? old('nip') : $record['nip'] }}"></div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="form-group transaction-field">
                                        <label for="{{ $fieldPrefix }}_password">Password Baru</label>
                                        <div class="transaction-input-shell"><input type="password" id="{{ $fieldPrefix }}_password" name="password" class="form-control {{ $showEditErrors && $errors->has('password') ? 'is-invalid' : '' }}" placeholder="Kosongkan jika tidak diubah"></div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="form-group transaction-field mb-0">
                                        <label for="{{ $fieldPrefix }}_password_confirmation">Konfirmasi Password Baru</label>
                                        <div class="transaction-input-shell"><input type="password" id="{{ $fieldPrefix }}_password_confirmation" name="password_confirmation" class="form-control" placeholder="Ulangi password baru"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @elseif ($resource === 'loan')
                        <div class="transaction-form-section">
                            <div class="row g-3">
                                <div class="col-md-6 col-12">
                                    <div class="form-group transaction-field">
                                        <label for="{{ $fieldPrefix }}_asset">Aset</label>
                                        <div class="transaction-input-shell">
                                            <select id="{{ $fieldPrefix }}_asset" name="asset_id" class="form-select {{ $showEditErrors && $errors->has('asset_id') ? 'is-invalid' : '' }}">
                                                <option value="">Pilih aset</option>
                                                @foreach ($editAssets as $asset)
                                                    <option value="{{ $asset->id }}" @selected(($showEditErrors ? old('asset_id') : $record['asset_id']) == $asset->id)>{{ $asset->name }} ({{ $asset->code }}) - Stok {{ $asset->quantity }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="form-group transaction-field">
                                        <label for="{{ $fieldPrefix }}_employee">Peminjam</label>
                                        <div class="transaction-input-shell">
                                            <select id="{{ $fieldPrefix }}_employee" name="user_id" class="form-select {{ $showEditErrors && $errors->has('user_id') ? 'is-invalid' : '' }}">
                                                <option value="">Pilih peminjam</option>
                                                @foreach ($editEmployees as $employee)
                                                    <option value="{{ $employee->id }}" @selected(($showEditErrors ? old('user_id') : $record['user_id']) == $employee->id)>{{ $employee->name }} ({{ $employee->email }}) - {{ ucfirst($employee->role) }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6">
                                    <div class="form-group transaction-field">
                                        <label for="{{ $fieldPrefix }}_loan_date">Tanggal Pinjam</label>
                                        <div class="transaction-input-shell"><input type="date" id="{{ $fieldPrefix }}_loan_date" name="loan_date" class="form-control {{ $showEditErrors && $errors->has('loan_date') ? 'is-invalid' : '' }}" value="{{ $showEditErrors ? old('loan_date') : $record['edit_loan_date'] }}" data-admin-loan-date></div>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6">
                                    <div class="form-group transaction-field">
                                        <label for="{{ $fieldPrefix }}_return_date">Rencana Kembali</label>
                                        <div class="transaction-input-shell"><input type="date" id="{{ $fieldPrefix }}_return_date" name="planned_return_date" class="form-control {{ $showEditErrors && $errors->has('planned_return_date') ? 'is-invalid' : '' }}" value="{{ $showEditErrors ? old('planned_return_date') : $record['planned_return_date'] }}" min="{{ $showEditErrors ? old('loan_date') : $record['edit_loan_date'] }}" data-admin-loan-return></div>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6">
                                    <div class="form-group transaction-field">
                                        <label for="{{ $fieldPrefix }}_quantity">Jumlah</label>
                                        <div class="transaction-input-shell"><input type="number" min="1" step="1" id="{{ $fieldPrefix }}_quantity" name="quantity" class="form-control {{ $showEditErrors && $errors->has('quantity') ? 'is-invalid' : '' }}" value="{{ $showEditErrors ? old('quantity') : $record['quantity'] }}"></div>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6">
                                    <div class="form-group transaction-field">
                                        <label for="{{ $fieldPrefix }}_status">Status</label>
                                        <div class="transaction-input-shell">
                                            <select id="{{ $fieldPrefix }}_status" name="status" class="form-select {{ $showEditErrors && $errors->has('status') ? 'is-invalid' : '' }}">
                                                <option value="">Pilih status</option>
                                                @foreach ($editStatuses as $status)
                                                    <option value="{{ $status }}" @selected(($showEditErrors ? old('status') : $record['status']) === $status)>{{ $status }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group transaction-field mb-0">
                                        <label for="{{ $fieldPrefix }}_note">Keterangan</label>
                                        <div class="transaction-input-shell"><textarea id="{{ $fieldPrefix }}_note" name="status_note" class="form-control {{ $showEditErrors && $errors->has('status_note') ? 'is-invalid' : '' }}" rows="4" placeholder="Keterangan status">{{ $showEditErrors ? old('status_note') : $record['status_note'] }}</textarea></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @elseif ($resource === 'asset')
                        <div class="transaction-form-section">
                            <div class="row g-3">
                                <div class="col-md-6 col-12">
                                    <div class="form-group transaction-field"><label for="{{ $fieldPrefix }}_name">Nama Aset</label><div class="transaction-input-shell"><input type="text" id="{{ $fieldPrefix }}_name" name="name" class="form-control {{ $showEditErrors && $errors->has('name') ? 'is-invalid' : '' }}" placeholder="Nama aset" value="{{ $showEditErrors ? old('name') : $record['name'] }}"></div></div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="form-group transaction-field"><label for="{{ $fieldPrefix }}_code">Kode Aset</label><div class="transaction-input-shell"><input type="text" id="{{ $fieldPrefix }}_code" name="code" class="form-control {{ $showEditErrors && $errors->has('code') ? 'is-invalid' : '' }}" placeholder="Kode aset" value="{{ $showEditErrors ? old('code') : $record['code'] }}"></div></div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="form-group transaction-field">
                                        <label for="{{ $fieldPrefix }}_category">Kategori</label>
                                        <div class="transaction-input-shell">
                                            <select id="{{ $fieldPrefix }}_category" name="category_id" class="form-select {{ $showEditErrors && $errors->has('category_id') ? 'is-invalid' : '' }}">
                                                <option value="">Pilih kategori</option>
                                                @foreach ($categories as $category)
                                                    <option value="{{ $category->id }}" @selected(($showEditErrors ? old('category_id') : $record['category_id']) == $category->id)>{{ $category->name }} ({{ $category->code }})</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="form-group transaction-field">
                                        <label for="{{ $fieldPrefix }}_location">Lokasi</label>
                                        <div class="transaction-input-shell">
                                            <select id="{{ $fieldPrefix }}_location" name="location_id" class="form-select {{ $showEditErrors && $errors->has('location_id') ? 'is-invalid' : '' }}">
                                                <option value="">Pilih lokasi</option>
                                                @foreach ($locations as $location)
                                                    <option value="{{ $location->id }}" @selected(($showEditErrors ? old('location_id') : $record['location_id']) == $location->id)>{{ $location->name }} ({{ $location->code }})</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6">
                                    <div class="form-group transaction-field"><label for="{{ $fieldPrefix }}_condition">Kondisi</label><div class="transaction-input-shell"><select id="{{ $fieldPrefix }}_condition" name="condition" class="form-select {{ $showEditErrors && $errors->has('condition') ? 'is-invalid' : '' }}"><option value="">Pilih kondisi</option>@foreach ($conditions as $condition)<option value="{{ $condition }}" @selected(($showEditErrors ? old('condition') : $record['edit_condition']) === $condition)>{{ $condition }}</option>@endforeach</select></div></div>
                                </div>
                                <div class="col-md-3 col-6">
                                    <div class="form-group transaction-field"><label for="{{ $fieldPrefix }}_status">Status</label><div class="transaction-input-shell"><select id="{{ $fieldPrefix }}_status" name="status" class="form-select {{ $showEditErrors && $errors->has('status') ? 'is-invalid' : '' }}"><option value="">Pilih status</option>@foreach ($statuses as $status)<option value="{{ $status }}" @selected(($showEditErrors ? old('status') : $record['edit_status']) === $status)>{{ $status }}</option>@endforeach</select></div></div>
                                </div>
                                <div class="col-md-3 col-6">
                                    <div class="form-group transaction-field"><label for="{{ $fieldPrefix }}_quantity">Jumlah Aset</label><div class="transaction-input-shell"><input type="number" min="1" step="1" id="{{ $fieldPrefix }}_quantity" name="quantity" class="form-control {{ $showEditErrors && $errors->has('quantity') ? 'is-invalid' : '' }}" value="{{ $showEditErrors ? old('quantity') : $record['quantity'] }}"></div></div>
                                </div>
                                <div class="col-md-3 col-6">
                                    <div class="form-group transaction-field"><label for="{{ $fieldPrefix }}_year">Tahun</label><div class="transaction-input-shell"><input type="number" min="1900" max="{{ now()->addYear()->year }}" id="{{ $fieldPrefix }}_year" name="acquisition_year" class="form-control {{ $showEditErrors && $errors->has('acquisition_year') ? 'is-invalid' : '' }}" placeholder="Contoh: 2021" value="{{ $showEditErrors ? old('acquisition_year') : $record['acquisition_year'] }}"></div></div>
                                </div>
                                <div class="col-md-4 col-12">
                                    <div class="form-group transaction-field"><label for="{{ $fieldPrefix }}_brand_model">Merk/Model</label><div class="transaction-input-shell"><input type="text" id="{{ $fieldPrefix }}_brand_model" name="brand_model" class="form-control {{ $showEditErrors && $errors->has('brand_model') ? 'is-invalid' : '' }}" placeholder="Merk atau model" value="{{ $showEditErrors ? old('brand_model') : $record['brand_model'] }}"></div></div>
                                </div>
                                <div class="col-md-4 col-12">
                                    <div class="form-group transaction-field"><label for="{{ $fieldPrefix }}_serial">No. Seri Pabrik</label><div class="transaction-input-shell"><input type="text" id="{{ $fieldPrefix }}_serial" name="serial_number" class="form-control {{ $showEditErrors && $errors->has('serial_number') ? 'is-invalid' : '' }}" placeholder="Nomor seri pabrik" value="{{ $showEditErrors ? old('serial_number') : $record['serial_number'] }}"></div></div>
                                </div>
                                <div class="col-md-4 col-6">
                                    <div class="form-group transaction-field"><label for="{{ $fieldPrefix }}_size">Ukuran</label><div class="transaction-input-shell"><input type="text" id="{{ $fieldPrefix }}_size" name="size" class="form-control {{ $showEditErrors && $errors->has('size') ? 'is-invalid' : '' }}" placeholder="Ukuran" value="{{ $showEditErrors ? old('size') : $record['size'] }}"></div></div>
                                </div>
                                <div class="col-md-4 col-6">
                                    <div class="form-group transaction-field"><label for="{{ $fieldPrefix }}_material">Bahan</label><div class="transaction-input-shell"><input type="text" id="{{ $fieldPrefix }}_material" name="material" class="form-control {{ $showEditErrors && $errors->has('material') ? 'is-invalid' : '' }}" placeholder="Bahan" value="{{ $showEditErrors ? old('material') : $record['material'] }}"></div></div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="form-group transaction-field"><label for="{{ $fieldPrefix }}_price">Nilai Perolehan</label><div class="transaction-input-shell"><input type="number" step="0.01" id="{{ $fieldPrefix }}_price" name="acquisition_price" class="form-control {{ $showEditErrors && $errors->has('acquisition_price') ? 'is-invalid' : '' }}" placeholder="0" value="{{ $showEditErrors ? old('acquisition_price') : $record['acquisition_price'] }}"></div></div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="form-group transaction-field"><label for="{{ $fieldPrefix }}_image">Upload Gambar</label><div class="transaction-input-shell"><input type="file" id="{{ $fieldPrefix }}_image" name="image_file" class="form-control {{ $showEditErrors && $errors->has('image_file') ? 'is-invalid' : '' }}" accept=".jpg,.jpeg,.png,.webp"></div><small class="text-muted">Kosongkan jika tidak ingin mengganti gambar.</small></div>
                                </div>
                                @if ($record['has_image'])
                                    <div class="col-12">
                                        <div class="form-group transaction-field">
                                            <label>Gambar Saat Ini</label>
                                            <div class="d-flex align-items-center gap-3 flex-wrap">
                                                <img src="{{ $record['image_url'] }}" alt="{{ $record['name'] }}" class="rounded" style="width: 96px; height: 96px; object-fit: cover;">
                                                <div class="form-check">
                                                    <input type="checkbox" id="{{ $fieldPrefix }}_remove_image" name="remove_image" value="1" class="form-check-input" @checked($showEditErrors && old('remove_image'))>
                                                    <label for="{{ $fieldPrefix }}_remove_image" class="form-check-label">Hapus gambar saat ini</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                <div class="col-12">
                                    <div class="form-group transaction-field mb-0"><label for="{{ $fieldPrefix }}_note">Keterangan</label><div class="transaction-input-shell"><textarea id="{{ $fieldPrefix }}_note" name="note" class="form-control {{ $showEditErrors && $errors->has('note') ? 'is-invalid' : '' }}" rows="4" placeholder="Keterangan sesuai KIR">{{ $showEditErrors ? old('note') : $record['note'] }}</textarea></div></div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="modal-footer transaction-modal-footer">
                    <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary icon icon-left">
                        <i class="bi bi-check-circle"></i><span>Simpan Perubahan</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@if ($openEditModal)
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
