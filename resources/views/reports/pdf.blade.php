<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="utf-8">
    <title>{{ $report['title'] }}</title>
    <style>
        body { font-family: 'dejavusans', sans-serif; direction: rtl; text-align: right; padding: 10px; }
        h2 { text-align: center; color: #005f8a; margin-bottom: 4px; }
        .meta { text-align: center; color: #64748b; font-size: 11px; margin-bottom: 18px; }
        table { width: 100%; border-collapse: collapse; direction: rtl; }
        th, td { border: 1px solid #cbd5e1; padding: 6px; text-align: right; font-size: 10px; }
        th { background-color: #f1f5f9; color: #334155; font-weight: bold; }
        tr:nth-child(even) td { background-color: #f8fafc; }
    </style>
</head>
<body>
    <h2>{{ $report['title'] }}</h2>
    <div class="meta">{{ $report['period'] }} — {{ count($report['rows']) }} سجل — {{ now()->format('Y-m-d H:i') }}</div>
    <table>
        <thead>
            <tr>
                @foreach($report['headings'] as $heading)
                    <th>{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($report['rows'] as $row)
                <tr>
                    @foreach($row as $cell)
                        <td>{{ $cell }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
