<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Pemberitahuan Pengajuan Peminjaman Aset Baru - Dinas Pendidikan Kabupaten Bengkalis</title>
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
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
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
            background: #fef3c7;
            color: #d97706;
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
            border-left: 4px solid #f59e0b;
            padding: 12px 16px;
            margin: 15px 0;
            border-radius: 0 8px 8px 0;
        }
        .btn-action {
            display: inline-block;
            background: #2563eb;
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
            <div class="status-badge">🔔 PENGAJUAN PEMINJAMAN ASET BARU</div>

            <p style="font-size: 15px; margin-top: 0;">Yth. <strong>Admin Pengelola BMD</strong>,</p>

            <p>Seorang pegawai baru saja mengajukan <strong>peminjaman aset kedinasan baru</strong> di sistem inventaris. Berikut detail pengajuannya:</p>

            <table class="info-table">
                <tr>
                    <td class="label">Nama Pegawai</td>
                    <td class="value">{{ $loan->user?->name ?? 'Pegawai' }}</td>
                </tr>
                <tr>
                    <td class="label">NIP Pegawai</td>
                    <td class="value">{{ $loan->user?->nip ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label">Tanggal Peminjaman</td>
                    <td class="value">{{ optional($loan->loan_date)->translatedFormat('d F Y') }}</td>
                </tr>
                <tr>
                    <td class="label">Rencana Pengembalian</td>
                    <td class="value">{{ optional($loan->planned_return_date)->translatedFormat('d F Y') }}</td>
                </tr>
                <tr>
                    <td class="label">Catatan / Keperluan</td>
                    <td class="value">{{ $loan->status_note ?: ($loan->reason ?: 'Pelaksanaan Tugas Kedinasan') }}</td>
                </tr>
            </table>

            <div class="item-box">
                <strong style="font-size: 13px; color: #475569; display: block; margin-bottom: 6px;">Daftar Aset Yang Diajukan:</strong>
                @php
                    $itemList = $loan->getItemList();
                @endphp
                <ul style="margin: 0; padding-left: 20px; font-size: 14px;">
                    @foreach ($itemList as $it)
                        <li><strong>{{ $it['asset']?->name ?? 'Aset' }}</strong> ({{ $it['asset']?->code ?? '-' }}) — {{ $it['quantity'] }} Unit</li>
                    @endforeach
                </ul>
            </div>

            <div style="text-align: center;">
                <a href="{{ route('admin.loans.index') }}" class="btn-action">
                    📋 Buka & Setujui di Sistem Admin
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
