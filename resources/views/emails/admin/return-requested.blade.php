<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Pemberitahuan Pengajuan Pengembalian Aset Baru - Dinas Pendidikan Kabupaten Bengkalis</title>
    <style>
        body {
            font-family: 'Segoe UI', Helvetica, Arial, sans-serif;
            background-color: #f4f7fb;
            color: #1e293b;
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }
        .email-wrapper {
            max-width: 620px;
            margin: 30px auto;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.06);
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }
        .email-header {
            background: linear-gradient(135deg, #0f766e 0%, #14b8a6 100%);
            color: #ffffff;
            padding: 28px 30px;
            text-align: center;
        }
        .email-header h2 {
            margin: 0 0 4px 0;
            font-size: 20px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .email-header p {
            margin: 0;
            font-size: 13px;
            opacity: 0.9;
        }
        .email-body {
            padding: 30px;
        }
        .status-badge {
            display: inline-block;
            background: #ccfbf1;
            color: #0f766e;
            font-weight: 700;
            font-size: 13px;
            padding: 6px 14px;
            border-radius: 20px;
            margin-bottom: 20px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: #f8fafc;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #edf2f7;
        }
        .info-table td {
            padding: 12px 16px;
            font-size: 14px;
            border-bottom: 1px solid #edf2f7;
        }
        .info-table td.label {
            color: #64748b;
            font-weight: 600;
            width: 38%;
        }
        .info-table td.value {
            color: #0f172a;
            font-weight: 700;
        }
        .item-box {
            background: #f1f5f9;
            border-left: 4px solid #14b8a6;
            padding: 12px 16px;
            margin: 15px 0;
            border-radius: 0 8px 8px 0;
        }
        .btn-action {
            display: inline-block;
            background: #0d9488;
            color: #ffffff !important;
            font-weight: 700;
            font-size: 14px;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            margin: 20px 0 10px 0;
            text-align: center;
        }
        .email-footer {
            background: #f8fafc;
            padding: 20px 30px;
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-header">
            <h2>DINAS PENDIDIKAN KABUPATEN BENGKALIS</h2>
            <p>Sistem Informasi Inventaris & Manajemen Aset</p>
        </div>

        <div class="email-body">
            <div class="status-badge">🔄 PENGAJUAN PENGEMBALIAN ASET BARU</div>

            <p style="font-size: 15px; margin-top: 0;">Yth. <strong>Admin Pengelola BMD</strong>,</p>

            <p>Seorang pegawai baru saja mengajukan <strong>pengembalian aset kedinasan</strong> di sistem inventaris. Silakan periksa kondisi fisik barang sebelum menyetujui pengembalian di sistem.</p>

            @php
                $loan = $assetReturn->loan;
                $asset = $assetReturn->asset ?? $loan?->asset;
            @endphp

            <table class="info-table">
                <tr>
                    <td class="label">Nama Pegawai</td>
                    <td class="value">{{ $assetReturn->user?->name ?? 'Pegawai' }}</td>
                </tr>
                <tr>
                    <td class="label">NIP Pegawai</td>
                    <td class="value">{{ $assetReturn->user?->nip ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label">Nama Aset</td>
                    <td class="value">{{ $asset?->name ?? 'Aset Inventaris' }}</td>
                </tr>
                <tr>
                    <td class="label">Kode Barang</td>
                    <td class="value">{{ $asset?->code ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label">Tanggal Pengembalian</td>
                    <td class="value">{{ optional($assetReturn->returned_at)->translatedFormat('d F Y') }}</td>
                </tr>
                <tr>
                    <td class="label">Kondisi Fisik Barang</td>
                    <td class="value">{{ $assetReturn->condition ?: 'Baik' }}</td>
                </tr>
                <tr>
                    <td class="label">Catatan Pegawai</td>
                    <td class="value">{{ $assetReturn->report_note ?: ($assetReturn->status_note ?: '-') }}</td>
                </tr>
            </table>

            <div style="text-align: center;">
                <a href="{{ route('admin.returns.index') }}" class="btn-action">
                    📋 Buka & Verifikasi Pengembalian
                </a>
            </div>
        </div>

        <div class="email-footer">
            &copy; {{ date('Y') }} Dinas Pendidikan Kabupaten Bengkalis. Hak Cipta Dilindungi.<br>
            Surat elektronik ini dikirim secara otomatis oleh Sistem Informasi Inventaris Aset.
        </div>
    </div>
</body>
</html>
