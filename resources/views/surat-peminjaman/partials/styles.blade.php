<style>
    .surat-peminjaman-document {
        font-family: 'Times New Roman', serif;
        font-size: 12pt;
        color: #111;
        line-height: 1.45;
    }

    .surat-peminjaman-document * {
        box-sizing: border-box;
    }

    .surat-peminjaman-document .header {
        width: 100%;
        border-bottom: 3px double #000;
        padding-bottom: 8px;
        margin-bottom: 14px;
    }

    .surat-peminjaman-document .header-table,
    .surat-peminjaman-document .identitas,
    .surat-peminjaman-document .asset-table,
    .surat-peminjaman-document .sign {
        width: 100%;
        border-collapse: collapse;
    }

    .surat-peminjaman-document .header-table td,
    .surat-peminjaman-document .sign td {
        vertical-align: top;
    }

    .surat-peminjaman-document .logo-box {
        width: 86px;
        height: 86px;
        text-align: center;
        vertical-align: middle;
    }

    .surat-peminjaman-document .logo-box img {
        width: 86px;
        height: 86px;
        object-fit: contain;
    }

    .surat-peminjaman-document .center {
        text-align: center;
    }

    .surat-peminjaman-document .title {
        font-size: 18pt;
        font-weight: bold;
        text-transform: uppercase;
        line-height: 1.15;
    }

    .surat-peminjaman-document .sub {
        font-size: 11pt;
        line-height: 1.3;
    }

    .surat-peminjaman-document .doc-title {
        text-align: center;
        font-weight: bold;
        text-decoration: underline;
        font-size: 16pt;
        margin-top: 10px;
    }

    .surat-peminjaman-document .doc-no {
        text-align: center;
        margin-top: 2px;
        margin-bottom: 14px;
        font-size: 14pt;
    }

    .surat-peminjaman-document .paragraph {
        text-align: justify;
        line-height: 1.45;
        margin-bottom: 10px;
    }

    .surat-peminjaman-document .identitas {
        margin-bottom: 8px;
    }

    .surat-peminjaman-document .identitas td {
        padding: 1px 0;
        vertical-align: top;
    }

    .surat-peminjaman-document .id-no {
        width: 24px;
    }

    .surat-peminjaman-document .id-key {
        width: 165px;
    }

    .surat-peminjaman-document .id-sep {
        width: 15px;
    }

    .surat-peminjaman-document .asset-table {
        margin: 10px 0 14px;
    }

    .surat-peminjaman-document .asset-table th,
    .surat-peminjaman-document .asset-table td {
        border: 1px solid #000;
        padding: 6px;
    }

    .surat-peminjaman-document .asset-table th {
        text-align: center;
        font-weight: bold;
    }

    .surat-peminjaman-document .sign {
        margin-top: 24px;
    }

    .surat-peminjaman-document .sign td {
        width: 50%;
        text-align: center;
    }

    .surat-peminjaman-document .signature-shell {
        height: 82px;
        display: flex;
        align-items: flex-end;
        justify-content: center;
        margin: 10px 0 8px;
    }

    .surat-peminjaman-document .signature-shell img {
        max-width: 150px;
        max-height: 72px;
        object-fit: contain;
        display: block;
    }

    .surat-peminjaman-document .signature-placeholder {
        height: 72px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #6b7280;
        font-size: 10pt;
    }

    .surat-peminjaman-document .bold {
        font-weight: bold;
    }

</style>
