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

    $terbilang = function ($number) use (&$terbilang) {
        $number = (int) $number;
        $words = [
            '',
            'satu',
            'dua',
            'tiga',
            'empat',
            'lima',
            'enam',
            'tujuh',
            'delapan',
            'sembilan',
            'sepuluh',
            'sebelas',
        ];

        if ($number < 12) {
            return $words[$number];
        }

        if ($number < 20) {
            return trim($terbilang($number - 10) . ' belas');
        }

        if ($number < 100) {
            return trim($terbilang(intdiv($number, 10)) . ' puluh ' . $terbilang($number % 10));
        }

        if ($number < 200) {
            return trim('seratus ' . $terbilang($number - 100));
        }

        if ($number < 1000) {
            return trim($terbilang(intdiv($number, 100)) . ' ratus ' . $terbilang($number % 100));
        }

        if ($number < 2000) {
            return trim('seribu ' . $terbilang($number - 1000));
        }

        return trim($terbilang(intdiv($number, 1000)) . ' ribu ' . $terbilang($number % 1000));
    };

    $formatTanggal = function ($date) use ($hari, $bulan, $terbilang) {
        if (!$date) {
            return '-';
        }

        return sprintf(
            '%s %s %s %s',
            $hari[$date->dayOfWeek] ?? '-',
            $terbilang($date->day),
            $bulan[$date->month] ?? '-',
            $terbilang($date->year)
        );
    };

    $formatTanggalSingkat = function ($date) use ($bulan) {
        if (!$date) {
            return '-';
        }

        return sprintf('%02d %s %d', $date->day, $bulan[$date->month] ?? '-', $date->year);
    };

    $printedDate = $printedAt ?? now();
    $loanDate = $loan?->loan_date;
    $returnedAt = $returnRecord->returned_at;
    $statusLabel = strtoupper($returnRecord->status ?? 'MENUNGGU VERIFIKASI');
    $combinedNote = collect([$returnRecord->report_note, $returnRecord->verified_note])
        ->filter()
        ->implode(' ');
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

    <div class="doc-title">BERITA ACARA SERAH TERIMA ASET</div>
    <div class="doc-no">Nomor : {{ $returnRecord->report_number }}</div>

    <div class="paragraph">
        Pada hari ini {{ strtolower($formatTanggal($printedDate)) }}, telah dilaksanakan serah terima pengembalian aset
        inventaris antara pihak-pihak yang bertandatangan di bawah ini:
    </div>

    <table class="identitas">
        <tr>
            <td class="id-no">1.</td>
            <td class="id-key">Nama</td>
            <td class="id-sep">:</td>
            <td class="bold">{{ strtoupper($approver?->name ?? 'ADMIN DINAS') }}</td>
        </tr>
        <tr>
            <td></td>
            <td>Jabatan</td>
            <td>:</td>
            <td>PIHAK PERTAMA / Admin Pengelola Aset</td>
        </tr>
        <tr>
            <td></td>
            <td>Instansi</td>
            <td>:</td>
            <td>{{ $office['agency_name'] }}, selanjutnya disebut <span class="bold">PIHAK PERTAMA</span></td>
        </tr>
    </table>

    <table class="identitas">
        <tr>
            <td class="id-no">2.</td>
            <td class="id-key">Nama</td>
            <td class="id-sep">:</td>
            <td class="bold">{{ strtoupper($pegawai?->name ?? 'PEGAWAI') }}</td>
        </tr>
        <tr>
            <td></td>
            <td>NIP</td>
            <td>:</td>
            <td>{{ $pegawai?->nip ?: '-' }}</td>
        </tr>
        <tr>
            <td></td>
            <td>Jabatan</td>
            <td>:</td>
            <td>PIHAK KEDUA / Pegawai Pengguna Aset</td>
        </tr>
        <tr>
            <td></td>
            <td>Instansi</td>
            <td>:</td>
            <td>{{ $office['agency_name'] }}, selanjutnya disebut <span class="bold">PIHAK KEDUA</span></td>
        </tr>
    </table>

    <div class="paragraph">
        <span class="bold">PIHAK KEDUA</span> telah menyerahkan kepada <span class="bold">PIHAK PERTAMA</span> dan <span class="bold">PIHAK PERTAMA</span> telah menerima penyerahan barang
        inventaris aset yang sebelumnya dipinjam untuk kebutuhan kedinasan dengan rincian sebagai berikut:
    </div>

    <table class="asset-table">
        <thead>
            <tr>
                <th style="width: 35px;">No</th>
                <th>Nama Barang</th>
                <th>Merk / Type</th>
                <th>Kategori</th>
                <th>Tanggal Peminjaman</th>
                <th>Tanggal Pengembalian</th>
                <th style="width: 90px;">Kondisi</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($assetRows as $row)
                @php($rowAsset = $row['asset'])
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td>{{ strtoupper($rowAsset?->name ?? 'ASET INVENTARIS') }}</td>
                    <td>{{ $rowAsset?->brand_model ?: '-' }}</td>
                    <td>{{ strtoupper($rowAsset?->category?->name ?? '-') }}</td>
                    <td>{{ optional($row['loan_date'])->format('d/m/Y') ?: '-' }}</td>
                    <td>{{ optional($row['returned_at'])->format('d/m/Y') ?: '-' }}</td>
                    <td class="center">{{ strtoupper($row['condition']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="paragraph">
        Dengan ditandatangangannya Berita Acara Pinjam Pakai ini, maka berpindahlah hak dan tanggung jawab terhadap pemakaian/pemeliharaan barang inventaris kepada
        <span class="bold">PIHAK PERTAMA</span> dan <span class="bold">PIHAK KEDUA</span> telah mengembalikan barang inventaris yang dipinjam pakai tersebut kepada <span class="bold">PIHAK PERTAMA</span>
        di Dinas Pendidikan Kabupaten Bengkalis.
    </div>

    <div class="paragraph">
        Demikian berita acara pinjam pakai ini dibuat dalam rangkap 2(dua) dan ditandatangani di Bengkalis pada hari dan tanggal sebagaimana tersebut diatas.
    </div>

    <div class="paragraph signature-date">
        Bengkalis, {{ $formatTanggalSingkat($printedDate) }}
    </div>

    <table class="sign">
        <tr>
            <td>
                <div class="bold">PIHAK KEDUA</div>
                <div>Yang Menyerahkan,</div>
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
                <div>Yang Menerima,</div>
                <div class="signature-shell">
                    @if ($approverSignatureDataUri)
                        <img src="{{ $approverSignatureDataUri }}" alt="Tanda tangan {{ $approver?->name }}">
                    @else
                        <div class="signature-placeholder">
                            {{ $returnRecord->status === 'Terverifikasi' ? 'Tanda tangan belum tersedia' : 'Menunggu verifikasi admin' }}
                        </div>
                    @endif
                </div>
                <div class="bold">{{ strtoupper($approver?->name ?? 'ADMIN DINAS') }}</div>
            </td>
        </tr>
    </table>
</div>
