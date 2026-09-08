<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'ระบบจัดการผู้ใช้') · URU Smart</title>
    <style>
        :root{font-family:system-ui,-apple-system,"Segoe UI",sans-serif;color:#172033;background:#f3f6fb}*{box-sizing:border-box}body{margin:0}a{color:#1258a8;text-decoration:none}.topbar{background:#123b6d;color:#fff;padding:14px 0}.wrap{width:min(1180px,calc(100% - 32px));margin:auto}.topbar .wrap{display:flex;align-items:center;justify-content:space-between;gap:20px}.brand{font-weight:700;font-size:19px}.nav{display:flex;align-items:center;gap:14px}.nav a{color:#fff}.page{padding:28px 0 48px}.card{background:#fff;border:1px solid #dce3ed;border-radius:12px;box-shadow:0 4px 18px rgba(31,50,78,.06);padding:22px}.header-row,.actions,.search{display:flex;align-items:center;gap:10px}.header-row{justify-content:space-between;margin-bottom:18px}h1{font-size:24px;margin:0}h2{font-size:18px;margin:22px 0 12px}.muted{color:#667085}.btn{display:inline-flex;align-items:center;justify-content:center;border:0;border-radius:8px;padding:9px 14px;cursor:pointer;background:#e8edf5;color:#172033;font-weight:600}.btn-primary{background:#1769c2;color:#fff}.btn-danger{background:#c93434;color:#fff}.btn-link{background:transparent;color:#fff;padding:6px}.field{display:flex;flex-direction:column;gap:6px}.field label{font-weight:600;font-size:14px}.input,select,textarea{width:100%;border:1px solid #cbd5e1;border-radius:8px;padding:10px 11px;background:#fff;font:inherit}.input:focus,select:focus,textarea:focus{outline:2px solid #acd2fa;border-color:#1769c2}.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:15px}.grid .full{grid-column:1/-1}.table-wrap{overflow:auto}table{width:100%;border-collapse:collapse}th,td{text-align:left;padding:12px 10px;border-bottom:1px solid #e5e9f0;vertical-align:middle}th{font-size:13px;color:#566176;background:#f8fafc}.avatar{width:42px;height:42px;border-radius:50%;object-fit:cover;background:#dde5ef}.person{display:flex;align-items:center;gap:10px;min-width:220px}.alert{border-radius:8px;padding:11px 14px;margin-bottom:16px}.success{background:#e5f7ec;color:#17653a}.error{background:#fdeaea;color:#912828}.login{width:min(430px,calc(100% - 32px));margin:9vh auto}.login .card{padding:30px}.stack{display:flex;flex-direction:column;gap:15px}.pagination{display:flex;gap:6px;align-items:center;margin-top:18px;flex-wrap:wrap}.pagination a,.pagination span{padding:7px 10px;border:1px solid #d7deea;border-radius:7px;background:#fff}.pagination .active{background:#1769c2;color:#fff}.details{display:grid;grid-template-columns:180px 1fr;gap:0}.details dt,.details dd{margin:0;padding:10px;border-bottom:1px solid #e8ecf2}.details dt{font-weight:600;color:#596579}.inline{display:inline}.danger-zone{margin-top:28px;padding-top:18px;border-top:1px solid #e6eaf0}@media(max-width:720px){.grid{grid-template-columns:1fr}.details{grid-template-columns:1fr}.details dt{padding-bottom:2px;border:0}.details dd{padding-top:2px}.header-row{align-items:flex-start;flex-direction:column}.topbar .wrap{align-items:flex-start}.nav{flex-wrap:wrap;justify-content:flex-end}}
    </style>
</head>
<body>
@auth
    <header class="topbar"><div class="wrap"><div class="brand">URU Smart Admin</div><nav class="nav"><a href="{{ route('admin.users.index') }}">จัดการผู้ใช้</a><span>{{ auth()->user()->full_name_th }}</span><form method="POST" action="{{ route('admin.logout') }}">@csrf<button class="btn btn-link" type="submit">ออกจากระบบ</button></form></nav></div></header>
@endauth
<main class="@auth page @endauth"><div class="@auth wrap @endauth">
    @auth
        <nav style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px">
            <a class="btn" href="{{ route('admin.dashboard') }}">แดชบอร์ด</a>
            <a class="btn" href="{{ route('admin.users.index') }}">ผู้ใช้และสิทธิ์</a>
            <a class="btn" href="{{ route('admin.approvals.index') }}">รออนุมัติ</a>
            <a class="btn" href="{{ route('admin.notifications.create') }}">ส่งแจ้งเตือน</a>
        </nav>
    @endauth
    @if(session('success'))<div class="alert success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert error">{{ session('error') }}</div>@endif
    @yield('content')
</div></main>
</body>
</html>
