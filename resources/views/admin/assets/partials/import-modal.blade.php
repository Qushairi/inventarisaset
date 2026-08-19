@php
    $showImportErrors = old('_import_modal') === 'asset' && $errors->has('import_file');
@endphp

<div class="modal fade" id="adminAssetImportModal" tabindex="-1" aria-labelledby="adminAssetImportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content transaction-modal">
            <div class="modal-header transaction-modal-header">
                <div class="transaction-form-title">
                    <span class="transaction-form-icon">
                        <i class="bi bi-file-earmark-spreadsheet"></i>
                    </span>
                    <div>
                        <h5 class="modal-title" id="adminAssetImportModalLabel">Import Aset dari Excel</h5>
                        <small class="text-muted">Unggah workbook Kartu Inventaris Ruangan (KIR) berformat .xlsx.</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>

            <form action="{{ route('admin.assets.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="_import_modal" value="asset">

                <div class="modal-body transaction-modal-body">
                    @if ($showImportErrors)
                        <div class="alert alert-light-danger color-danger">
                            <i class="bi bi-exclamation-circle me-1"></i>
                            Periksa kembali berkas Excel yang dipilih.
                        </div>
                    @endif

                    <div class="transaction-form-section">
                        <div class="transaction-section-heading">
                            <span><i class="bi bi-upload"></i></span>
                            <div>
                                <h5>Berkas KIR</h5>
                                <small>Pilih workbook; kategori dan lokasi akan ditentukan otomatis.</small>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="form-group transaction-field">
                                    <label for="admin_asset_import_file">Berkas Excel</label>
                                    <div class="transaction-input-shell">
                                        <input
                                            type="file"
                                            id="admin_asset_import_file"
                                            name="import_file"
                                            class="form-control @if($showImportErrors) @error('import_file') is-invalid @enderror @endif"
                                            accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                                            required
                                        >
                                    </div>
                                    @if ($showImportErrors)
                                        @error('import_file')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    @endif
                                    <small class="text-muted">Format .xlsx, maksimal 10 MB.</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="transaction-form-section mb-0">
                        <div class="transaction-section-heading">
                            <span><i class="bi bi-diagram-3"></i></span>
                            <div>
                                <h5>Pemetaan Otomatis</h5>
                                <small>Sistem mengikuti susunan workbook KIR yang diberikan.</small>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6 col-12">
                                <ul class="mb-0 ps-3 text-muted small">
                                    <li>Setiap sheet menjadi lokasi berdasarkan nama dan kode ruangan.</li>
                                    <li>Golongan kode barang otomatis menjadi kategori sesuai referensi kode barang.</li>
                                    <li>Nama, kode, seri, ukuran, bahan, tahun, jumlah, dan harga diambil dari baris detail.</li>
                                </ul>
                            </div>
                            <div class="col-md-6 col-12">
                                <ul class="mb-0 ps-3 text-muted small">
                                    <li>Kondisi Baik, Kurang Baik, dan Rusak Berat dipetakan otomatis.</li>
                                    <li>Merk/model dan keterangan Excel disimpan pada catatan aset.</li>
                                </ul>
                            </div>
                        </div>
                        <div class="alert alert-light-warning color-warning mt-3 mb-0">
                            <i class="bi bi-info-circle me-1"></i>
                            Kode barang yang sama tetap dibuat sebagai baris aset terpisah. Mengunggah workbook yang sama kembali akan menambahkan data kembali.
                        </div>
                    </div>
                </div>

                <div class="modal-footer transaction-modal-footer">
                    <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary icon icon-left">
                        <i class="bi bi-upload"></i><span>Import Sekarang</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@if ($showImportErrors)
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const modalElement = document.getElementById('adminAssetImportModal');

                if (modalElement) {
                    new bootstrap.Modal(modalElement).show();
                }
            });
        </script>
    @endpush
@endif
