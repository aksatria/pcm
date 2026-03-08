<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Terjadi Kesalahan</title>
    <style>
        body { margin:0; font-family: Arial, sans-serif; background:#f8fafc; color:#0f172a; min-height:100vh; display:flex; align-items:center; justify-content:center; }
        .card { width:min(560px,92vw); background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:24px; box-shadow:0 8px 20px rgba(15,23,42,.06); }
        h1 { margin:0 0 8px 0; font-size:28px; }
        p { margin:0; color:#475569; line-height:1.5; }
        .actions { margin-top:18px; display:flex; gap:10px; flex-wrap:wrap; }
        a { text-decoration:none; border-radius:8px; padding:10px 14px; font-size:14px; font-weight:600; }
        .btn-primary { background:#2563eb; color:#fff; }
        .btn-secondary { border:1px solid #cbd5e1; color:#334155; background:#fff; }
    </style>
</head>
<body>
    <div class="card">
        <h1>500</h1>
        <p>Terjadi kesalahan pada sistem. Silakan coba beberapa saat lagi.</p>
        <div class="actions">
            <a class="btn-primary" href="{{ url('/') }}">Ke Beranda</a>
            <a class="btn-secondary" href="{{ url()->previous() }}">Kembali</a>
        </div>
    </div>
</body>
</html>
