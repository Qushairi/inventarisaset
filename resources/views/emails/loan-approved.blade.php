<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Persetujuan Peminjaman Aset - Dinas Pendidikan Kabupaten Bengkalis</title>
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
            background: #dcfce7;
            color: #15803d;
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
            border-left: 4px solid #3b82f6;
            padding: 12px 16px;
            margin: 15px 0;
            border-radius: 0 8px 8px 0;
        }
        .attachment-notice {
            background: #eff6ff;
            border: 1px dashed #60a5fa;
            border-radius: 8px;
            padding: 14px 18px;
            margin: 24px 0 10px 0;
            font-size: 13px;
            color: #1e40af;
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
            <div class="status-badge">✓ PERSETUJUAN PEMINJAMAN ASET</div>

            <p style="font-size: 15px; margin-top: 0;">Yth. Bapak/Ibu <strong>{{ $loan->user?->name ?? 'Pegawai' }}</strong>,</p>

            <p>Pengajuan peminjaman aset kedinasan Anda telah <strong>DISETUJUI</strong> oleh Admin Pengelola Barang Milik Daerah (BMD) Dinas Pendidikan Kabupaten Bengkalis.</p>

            <table class="info-table">
                <tr>
                    <td class="label">Nomor Dokumen</td>
                    <td class="value">{{ $suratPeminjaman?->number ?? ('#LOAN-' . $loan->id) }}</td>
                </tr>
                <tr>
                    <td class="label">Tanggal Pinjam</td>
                    <td class="value">{{ optional($loan->loan_date)->translatedFormat('d F Y') }}</td>
                </tr>
                <tr>
                    <td class="label">Rencana Pengembalian</td>
                    <td class="value">{{ optional($loan->planned_return_date)->translatedFormat('d F Y') }}</td>
                </tr>
                <tr>
                    <td class="label">Keperluan</td>
                    <td class="value">{{ $loan->status_note ?: ($loan->reason ?: 'Pelaksanaan Tugas Kedinasan') }}</td>
                </tr>
            </table>

            <div class="item-box">
                <strong style="font-size: 13px; color: #475569; display: block; margin-bottom: 6px;">Daftar Aset Yang Dipinjam:</strong>
                @php
                    $itemList = $loan->getItemList();
                @endphp
                <ul style="margin: 0; padding-left: 20px; font-size: 14px;">
                    @foreach ($itemList as $it)
                        <li><strong>{{ $it['asset']?->name ?? 'Aset' }}</strong> ({{ $it['asset']?->code ?? '-' }}) — {{ $it['quantity'] }} Unit</li>
                    @endforeach
                </ul>
            </div>

            <div class="attachment-notice">
                📄 <strong>Lampiran Dokumen PDF:</strong> Surat Peminjaman Aset resmi (dilengkapi Tanda Tangan Digital & NIP) telah terlampir secara otomatis pada email ini. Anda dapat mengunduh atau mencetaknya secara langsung.
            </div>
        </div>

        <div class="email-footer">
            &copy; {{ date('Y') }} Dinas Pendidikan Kabupaten Bengkalis. Hak Cipta Dilindungi.<br>
            Surat elektronik ini dikirim secara otomatis oleh Sistem Informasi Inventaris Aset.
        </div>
    </div>
</body>
</html>
