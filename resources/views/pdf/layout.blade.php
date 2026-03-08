<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'Dokumen')</title>
    <style>
        @page { size: A4; margin: 12mm 10mm 16mm; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #111; }
        table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: top; }
        .doc-title { font-size: 16px; font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase; }
        .doc-subtitle { font-size: 10px; color: #444; margin-top: 2px; }
        .meta-table td { padding: 3px 4px; vertical-align: top; }
        .meta-label { width: 24%; color: #444; }
        .meta-value { width: 26%; }
        .section-title { font-size: 12px; font-weight: 700; margin: 10px 0 6px; }
        .items-table th,
        .items-table td { border: 1px solid #111; padding: 4px; }
        .items-table th { background: #f2f2f2; font-size: 10px; text-transform: uppercase; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .sign-table td {
            border: 1px solid #111;
            height: 65px;
            text-align: center;
            vertical-align: bottom;
            padding-bottom: 6px;
        }
        .note { font-size: 10px; color: #555; }
        .mt-6 { margin-top: 6px; }
        .mt-10 { margin-top: 10px; }
        .doc-box { border: 1px solid #111; padding: 6px 8px; }
        .doc-title-center { font-size: 14px; font-weight: 700; text-align: center; text-transform: uppercase; color: #0f2f5f; }
        .doc-subtitle-center { font-size: 11px; text-align: center; margin-top: 2px; }
        .doc-meta { width: 100%; border-collapse: collapse; margin-top: 6px; }
        .doc-meta td { padding: 3px 4px; vertical-align: top; font-size: 11px; }
        .doc-meta .label { font-weight: 700; text-transform: uppercase; }
        .meta-box { border: 1px solid #111; padding: 6px 8px; background: #fbfbfd; }
        .meta-grid { width: 100%; border-collapse: collapse; }
        .meta-grid td { padding: 2px 3px; vertical-align: top; font-size: 11px; }
        .meta-label { width: 18%; font-weight: 700; text-transform: uppercase; color: #0f2f5f; }
        .meta-sep { width: 2%; text-align: center; }
        .meta-value { width: 30%; }
        .doc-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .doc-table th, .doc-table td { border: 1px solid #111; padding: 4px 5px; font-size: 10px; }
        .doc-table th { text-transform: uppercase; font-size: 9px; font-weight: 700; background: #e9eff7; }
        .doc-table tbody tr:nth-child(even) td { background: #f6f8fb; }
        .doc-sign { width: 100%; border-collapse: collapse; margin-top: 16px; }
        .doc-sign td { border: 1px solid #111; height: 70px; vertical-align: top; font-size: 10px; padding: 6px; }
        .doc-sign .doc-sign-label { color: #0f2f5f; }
        .doc-sign-label { font-size: 10px; font-weight: 700; text-transform: uppercase; }
        .meta-line { font-size: 11px; }
        .note-box { border: 1px solid #111; padding: 6px 8px; font-size: 10px; line-height: 1.35; background: #f7f8fa; }
        .form-table th, .form-table td { border: 1px solid #111; padding: 4px; font-size: 10px; }
        .form-table th { background: #f5f5f5; text-transform: uppercase; font-size: 9px; }
        .sign-block td { border: 1px solid #111; height: 70px; vertical-align: top; font-size: 10px; }
        .sign-label { font-size: 10px; font-weight: 700; }
        .sign-role { font-size: 9px; color: #333; }
        .doc-header-box { border: 1px solid #111; padding: 6px 8px; }
        .doc-header-line { width: 100%; border-collapse: collapse; }
        .doc-header-logo { text-align: left; width: 20%; }
        .doc-header-title { text-align: right; font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px; width: 80%; }
        .doc-header-spacer { width: 0%; }
        .doc-logo { height: 28px; max-height: 28px; }
        .doc-header-rule {
            margin-top: 4px;
            height: 0;
            border-top: 1px solid #111;
        }
        .doc-footer {
            position: fixed;
            bottom: -10mm;
            left: 0;
            right: 0;
            font-size: 9px;
            color: #333;
        }
        .doc-footer-table { width: 100%; border-collapse: collapse; }
        .doc-footer-left { text-align: left; }
        .doc-footer-right { text-align: right; }
        .page-number:after { content: counter(page) " / " counter(pages); }
    </style>
</head>
<body>
    @yield('content')
    <div class="doc-footer">
        <table class="doc-footer-table">
            <tr>
                <td class="doc-footer-left">Printed at: {{ now()->format('d M Y H:i') }}</td>
                <td class="doc-footer-right">Page <span class="page-number"></span></td>
            </tr>
        </table>
    </div>
</body>
</html>
