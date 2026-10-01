<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="utf-8">
    <title>{{ $pageTitle }}</title>
    <style>
        /* DejaVu Sans مضمّن مع mPDF ويدعم العربية بالكامل (لا يحتاج اتصالاً بالإنترنت) */
        body {
            font-family: 'dejavusans', sans-serif;
            direction: rtl; 
            text-align: right; 
            padding: 20px;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 20px; 
            direction: rtl;
        }
        th, td { 
            border: 1px solid #cbd5e1; 
            padding: 10px; 
            text-align: right; 
            font-size: 14px;
        }
        th { 
            background-color: #f1f5f9; 
            color: #334155;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f8fafc;
        }
        h2 { 
            text-align: center; 
            color: #005f8a; 
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <h2>{{ $pageTitle }}</h2>
    <table>
        <thead>
            <tr>
                <th>الرقم</th>
                <th>الاسم (عربي)</th>
                <th>الاسم (إنكليزي)</th>
                <th>الرصيد الأساسي</th>
                <th>الرصيد الحالي</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
            <tr>
                <td>{{ $item->id }}</td>
                <td>{{ $item->name_ar ?? 'غير محدد' }}</td>
                <td>{{ $item->name_en }}</td>
                <td>{{ $item->initial_balance }}</td>
                <td>{{ $item->current_stock }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>