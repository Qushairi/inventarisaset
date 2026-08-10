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

    $originLabel = function ($acquiredAt) {
        if (! $acquiredAt) {
            return 'Pembelian APBD';
        }

        return 'Pengadaan ' . optional($acquiredAt)->format('Y');
    };

    $assetName = strtoupper($asset?->name ?? 'ASET INVENTARIS');
    $assetType = $asset?->note ?: ($asset?->code ?: '-');
    $assetPrice = $rupiah($asset?->acquisition_price);
    $printedDate = $printedAt ?? now();
@endphp

<div class="surat-peminjaman-document asset-return-letter-document">
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
        Pada hari ini <span class="bold">{{ $formatTanggal($printedDate) }}</span>, yang bertandatangan di bawah ini menyatakan bahwa peminjaman aset inventaris Barang Milik Daerah (BMD) telah disetujui untuk dipergunakan dalam rangka pelaksanaan tugas kedinasan dengan rincian sebagai berikut:
    </div>

    <table class="identitas">
        <tr><td class="id-no">1.</td><td class="id-key">Nama</td><td class="id-sep">:</td><td class="bold">{{ strtoupper($approver?->name ?? 'ADMIN DINAS') }}</td></tr>
        <tr><td></td><td>Jabatan</td><td>:</td><td>PIHAK PERTAMA / Admin Pengelola Barang Milik Daerah</td></tr>
        <tr><td></td><td>Instansi</td><td>:</td><td>{{ $office['agency_name'] }}, selanjutnya disebut <span class="bold">PIHAK PERTAMA</span></td></tr>
    </table>

    <table class="identitas">
        <tr><td class="id-no">2.</td><td class="id-key">Nama</td><td class="id-sep">:</td><td class="bold">{{ strtoupper($pegawai?->name ?? 'PEGAWAI') }}</td></tr>
        <tr><td></td><td>NIP</td><td>:</td><td>{{ $pegawai?->nip ?: '-' }}</td></tr>
        <tr><td></td><td>Jabatan / Peran</td><td>:</td><td>PIHAK KEDUA / Pegawai Peminjam</td></tr>
        <tr><td></td><td>Instansi</td><td>:</td><td>{{ $office['agency_name'] }}, selanjutnya disebut <span class="bold">PIHAK KEDUA</span></td></tr>
    </table>

    <div class="paragraph">
        <span class="bold">PIHAK PERTAMA</span> memberikan persetujuan penggunaan barang inventaris kepada <span class="bold">PIHAK KEDUA</span> dengan rincian barang sebagai berikut:
    </div>

    <table class="asset-table">
        <thead>
            <tr>
                <th style="width: 35px;">No</th>
                <th>Kode Barang</th>
                <th>Nama & Jenis Barang</th>
                <th>Spesifikasi / Nomor Seri</th>
                <th style="width: 75px;">Jumlah</th>
                <th style="width: 85px;">Kondisi</th>
            </tr>
        </thead>
        <tbody>
            @php
                $itemsToRender = isset($itemList) && count($itemList) > 0 ? $itemList : collect([['asset' => $asset, 'quantity' => $loan->quantity ?? 1]]);
            @endphp
            @foreach ($itemsToRender as $index => $itemRow)
                @php
                    $itemAsset = is_array($itemRow) ? ($itemRow['asset'] ?? null) : ($itemRow->asset ?? null);
                    $qty = is_array($itemRow) ? ($itemRow['quantity'] ?? 1) : ($itemRow->quantity ?? 1);
                    $spec = array_filter([
                        $itemAsset?->material ? 'Bahan: ' . $itemAsset->material : null,
                        $itemAsset?->size ? 'Ukuran: ' . $itemAsset->size : null,
                        $itemAsset?->serial_number ? 'S/N: ' . $itemAsset->serial_number : null,
                        $itemAsset?->note ?: null,
                    ]);
                    $specText = count($spec) > 0 ? implode(' | ', $spec) : '-';
                @endphp
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td class="bold center">{{ $itemAsset?->code ?: '-' }}</td>
                    <td>{{ strtoupper($itemAsset?->name ?? $assetName) }}</td>
                    <td>{{ $specText }}</td>
                    <td class="center bold">{{ $qty }} Unit</td>
                    <td class="center">{{ $itemAsset?->condition ?: 'Baik' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="identitas" style="margin-top: 15px;">
        <tr><td style="width: 160px;" class="bold">Jangka Waktu Peminjaman</td><td style="width: 15px;">:</td><td><span class="bold">{{ $formatTanggalSingkat($loan->loan_date ?? now()) }}</span> s.d. <span class="bold">{{ $formatTanggalSingkat($loan->return_date ?? now()->addDays(7)) }}</span></td></tr>
        <tr><td class="bold">Maksud / Keperluan</td><td>:</td><td>{{ $loan->reason ?? 'Pelaksanaan Tugas Kedinasan' }}</td></tr>
    </table>

    <div class="paragraph" style="margin-top: 15px;">
        <span class="bold">Ketentuan & Kewajiban Peminjam:</span>
        <ol style="margin-top: 5px; margin-bottom: 5px; padding-left: 20px;">
            <li>PIHAK KEDUA wajib memelihara dan menjaga kondisi barang inventaris yang dipinjam dengan sebaik-baiknya.</li>
            <li>Apabila terjadi kerusakan akibat kelalaian atau kehilangan barang, PIHAK KEDUA bertanggung jawab penuh untuk memperbaiki atau mengganti barang sesuai ketentuan yang berlaku.</li>
            <li>Barang yang dipinjam wajib dikembalikan tepat waktu kepada PIHAK PERTAMA sesuai jadwal yang telah disepakati.</li>
        </ol>
    </div>

    <div class="paragraph">
        {{ $suratPeminjaman->closing_statement }}
    </div>

    <div class="paragraph" style="text-align: right; margin-top: 20px; margin-bottom: 10px;">
        {{ $suratPeminjaman->location }}, {{ $formatTanggalSingkat($printedDate) }}
    </div>

    <table class="sign">
        <tr>
            <td>
                <div class="bold">PIHAK KEDUA</div>
                <div>Pegawai Peminjam,</div>
                <div class="signature-shell">
                    @if ($pegawaiSignatureDataUri)
                        <img src="{{ $pegawaiSignatureDataUri }}" alt="Tanda tangan {{ $pegawai?->name }}">
                    @else
                        <div class="signature-placeholder">Tanda tangan belum tersedia</div>
                    @endif
                </div>
                <div class="bold">{{ strtoupper($pegawai?->name ?? 'PEGAWAI') }}</div>
                <div>NIP. {{ $pegawai?->nip ?: '-' }}</div>
            </td>
            <td>
                <div class="bold">PIHAK PERTAMA</div>
                <div>Yang Menyetujui / Pengelola Aset,</div>
                <div class="signature-shell">
                    @if ($approverSignatureDataUri)
                        <img src="{{ $approverSignatureDataUri }}" alt="Tanda tangan {{ $approver?->name }}">
                    @else
                        <div class="signature-placeholder">Tanda tangan belum tersedia</div>
                    @endif
                </div>
                <div class="bold">{{ strtoupper($approver?->name ?? 'ADMIN DINAS') }}</div>
                <div>NIP. {{ $approver?->nip ?: '-' }}</div>
            </td>
        </tr>
    </table>
</div>
