@php
    $hari = [
        0 => 'Minggu',
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
    ];
    $bulan = [
        1 => 'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember',
    ];

    $formatTanggal = function ($date) use ($hari, $bulan) {
        if (! $date) {
            return '-';
        }

        return sprintf(
            '%s, %02d %s %d',
            $hari[$date->dayOfWeek] ?? '-',
            $date->day,
            $bulan[$date->month] ?? '-',
            $date->year
        );
    };

    $formatTanggalSingkat = function ($date) use ($bulan) {
        if (! $date) {
            return '-';
        }

        return sprintf('%02d %s %d', $date->day, $bulan[$date->month] ?? '-', $date->year);
    };

    $rupiah = function ($nominal) {
        if ($nominal === null) {
            return '-';
        }

        return 'Rp ' . number_format((float) $nominal, 0, ',', '.');
    };

    $assetName = strtoupper($asset?->name ?? 'ASET INVENTARIS');
    $assetType = $asset?->note ?: ($asset?->code ?: '-');
    $assetPrice = $rupiah($asset?->acquisition_price);
    $printedDate = $printedAt ?? now();
@endphp

<div class="surat-peminjaman-document">
    <table class="header-table header">
        <tr>
            <td style="width: 95px;">
                <div class="logo-box">
                    @if ($logoDataUri)
                        <img src="{{ $logoDataUri }}" alt="Logo {{ $office['agency_name'] }}">
                    @endif
                </div>
            </td>
            <td class="center">
                <div class="title">{{ $office['government_name'] }}<br>{{ $office['agency_name'] }}</div>
                <div class="sub">
                    {{ $office['address'] }}<br>
                    Telepon : {{ $office['phone'] }} Fax {{ $office['fax'] }} E-Mail : {{ $office['email'] }}<br>
                    Website : {{ $office['website'] }}
                </div>
            </td>
        </tr>
    </table>

    <div class="doc-title">SURAT PEMINJAMAN ASET</div>
    <div class="doc-no">Nomor : {{ $suratPeminjaman->number }}</div>

    <div class="paragraph">
        Pada hari ini {{ strtolower($formatTanggal($printedDate)) }}, yang bertandatangan di bawah ini menyatakan bahwa peminjaman aset inventaris telah disetujui untuk dipergunakan sesuai kebutuhan kedinasan dengan rincian sebagai berikut:
    </div>

    <table class="identitas">
        <tr><td class="id-no">1.</td><td class="id-key">Nama</td><td class="id-sep">:</td><td class="bold">{{ strtoupper($approver?->name ?? 'ADMIN DINAS') }}</td></tr>
        <tr><td></td><td>Jabatan</td><td>:</td><td>PIHAK PERTAMA / Admin Pengelola Aset</td></tr>
        <tr><td></td><td>Instansi</td><td>:</td><td>{{ $office['agency_name'] }}, selanjutnya disebut <span class="bold">PIHAK PERTAMA</span></td></tr>
    </table>

    <table class="identitas">
        <tr><td class="id-no">2.</td><td class="id-key">Nama</td><td class="id-sep">:</td><td class="bold">{{ strtoupper($pegawai?->name ?? 'PEGAWAI') }}</td></tr>
        <tr><td></td><td>Jabatan</td><td>:</td><td>PIHAK KEDUA / Pegawai Peminjam</td></tr>
        <tr><td></td><td>Instansi</td><td>:</td><td>{{ $office['agency_name'] }}, selanjutnya disebut <span class="bold">PIHAK KEDUA</span></td></tr>
    </table>

    <div class="paragraph">
        <span class="bold">PIHAK PERTAMA</span> memberikan persetujuan kepada <span class="bold">PIHAK KEDUA</span> untuk meminjam aset inventaris dengan rincian sebagai berikut:
    </div>

    <table class="asset-table">
        <thead>
            <tr>
                <th style="width: 50px;">No</th>
                <th>Jenis Barang</th>
                <th>Merk / Type</th>
                <th>Asal Pengadaan</th>
                <th>Harga</th>
                <th style="width: 100px;">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="center">1</td>
                <td>{{ $assetName }}</td>
                <td>{{ $assetType }}</td>
                <td>{{ $asalPengadaan }}</td>
                <td>{{ $assetPrice }}</td>
                <td class="center">1 Unit</td>
            </tr>
        </tbody>
    </table>

    <div class="paragraph">
        {{ $suratPeminjaman->handover_statement }}
    </div>

    <div class="paragraph">
        {{ $suratPeminjaman->closing_statement }}
    </div>

    <div class="paragraph">
        {{ $suratPeminjaman->location }}, {{ $formatTanggalSingkat($printedDate) }}
    </div>

    <table class="sign">
        <tr>
            <td>
                <div class="bold">PIHAK KEDUA</div>
                <div>,</div>
                <div class="signature-shell">
                    @if ($pegawaiSignatureDataUri)
                        <img src="{{ $pegawaiSignatureDataUri }}" alt="Tanda tangan {{ $pegawai?->name }}">
                    @else
                        <div class="signature-placeholder">Tanda tangan belum tersedia</div>
                    @endif
                </div>
                <div class="bold">{{ strtoupper($pegawai?->name ?? 'PEGAWAI') }}</div>
            </td>
            <td>
                <div class="bold">PIHAK PERTAMA</div>
                <div>Yang Menyetujui,</div>
                <div class="signature-shell">
                    @if ($approverSignatureDataUri)
                        <img src="{{ $approverSignatureDataUri }}" alt="Tanda tangan {{ $approver?->name }}">
                    @else
                        <div class="signature-placeholder">Tanda tangan belum tersedia</div>
                    @endif
                </div>
                <div class="bold">{{ strtoupper($approver?->name ?? 'ADMIN DINAS') }}</div>
            </td>
        </tr>
    </table>
</div>
