<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body {
            color: #1f2937;
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            line-height: 1.4;
        }

        h1 {
            font-size: 20px;
            margin: 0 0 4px;
            text-align: center;
        }

        .meta {
            color: #6b7280;
            margin-bottom: 18px;
            text-align: center;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            border: 1px solid #d1d5db;
            padding: 7px 8px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #eef2ff;
            color: #111827;
            font-weight: 700;
        }

        tbody tr:nth-child(even) td {
            background: #f9fafb;
        }

        .empty {
            color: #6b7280;
            padding: 22px;
            text-align: center;
        }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <div class="meta">
        Diunduh pada {{ now()->timezone(config('app.timezone'))->format('d/m/Y H:i') }}
        @if (!empty($filterSummary))
            <br>Filter: {{ implode(' | ', $filterSummary) }}
        @endif
    </div>

    <table>
        <thead>
            <tr>
                @foreach ($columns as $column)
                    <th>{{ $column }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach ($row as $value)
                        <td>{{ $value }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td class="empty" colspan="{{ count($columns) }}">Belum ada data untuk laporan ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
