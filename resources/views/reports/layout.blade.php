<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2937; }
        .header { background: #1e40af; color: white; padding: 12px 20px; margin-bottom: 16px; }
        .header h1 { font-size: 16px; font-weight: bold; }
        .header p  { font-size: 9px; opacity: 0.8; margin-top: 2px; }
        .meta { padding: 0 20px 10px; font-size: 9px; color: #6b7280; border-bottom: 1px solid #e5e7eb; margin-bottom: 12px; }
        .content { padding: 0 20px; }
        table { width: 100%; border-collapse: collapse; font-size: 9px; }
        th { background: #1e40af; color: white; padding: 5px 6px; text-align: left; font-weight: 600; }
        td { padding: 4px 6px; border-bottom: 1px solid #f3f4f6; }
        tr:nth-child(even) td { background: #f9fafb; }
        .footer { position: fixed; bottom: 10px; left: 20px; right: 20px; font-size: 8px; color: #9ca3af; border-top: 1px solid #e5e7eb; padding-top: 4px; display: flex; justify-content: space-between; }
        .badge { display: inline-block; padding: 1px 5px; border-radius: 9px; font-size: 8px; font-weight: 600; }
        .badge-green { background: #d1fae5; color: #065f46; }
        .badge-red { background: #fee2e2; color: #991b1b; }
        .badge-blue { background: #dbeafe; color: #1e40af; }
        .badge-gray { background: #f3f4f6; color: #374151; }
        .summary { display: flex; gap: 12px; margin-bottom: 14px; }
        .summary-card { flex: 1; border: 1px solid #e5e7eb; border-radius: 4px; padding: 8px 10px; }
        .summary-card .val { font-size: 18px; font-weight: bold; color: #1e40af; }
        .summary-card .lbl { font-size: 8px; color: #6b7280; margin-top: 1px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>SLP — Sustainable Livelihood Program</h1>
        <p>@yield('subtitle')</p>
    </div>
    <div class="meta">
        Generated: {{ now()->format('F j, Y  h:i A') }} &nbsp;|&nbsp; Total Records: {{ $total ?? 0 }}
        @if(!empty($filters)) &nbsp;|&nbsp; Filters: {{ $filters }} @endif
    </div>
    <div class="content">
        @yield('body')
    </div>
    <div class="footer">
        <span>SLP Beneficiary Information Management System</span>
        <span>Confidential</span>
    </div>
</body>
</html>

