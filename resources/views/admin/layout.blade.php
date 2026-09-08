<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#075c45">
    <title>@yield('title', 'URU Smart Admin') · URU Smart</title>
    <style>
        :root {
            --uru-900: #064b3a;
            --uru-800: #075c45;
            --uru-700: #087556;
            --uru-100: #e6f5ee;
            --ink: #102a24;
            --muted: #63736e;
            --line: #dce7e1;
            --canvas: #f4f8f6;
            --surface: #ffffff;
            --danger: #b42318;
            --warning: #a15c00;
            --shadow: 0 10px 30px rgba(11, 67, 49, .09);
        }

        * { box-sizing: border-box; }
        html { min-height: 100%; background: var(--canvas); }
        body { min-height: 100vh; margin: 0; color: var(--ink); background: var(--canvas); font-family: "Noto Sans Thai", "Sarabun", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; font-size: 16px; line-height: 1.5; }
        button, input, select, textarea { font: inherit; }
        button, a { -webkit-tap-highlight-color: transparent; }
        a { color: inherit; text-decoration: none; }
        h1, h2, h3, p { margin-top: 0; }
        h1 { margin-bottom: 4px; font-size: clamp(1.45rem, 5vw, 2rem); letter-spacing: -.025em; line-height: 1.25; }
        h2 { margin-bottom: 8px; font-size: 1.08rem; line-height: 1.35; }
        strong { font-weight: 750; }

        .admin-shell { min-height: 100vh; }
        .sidebar { position: fixed; z-index: 10; inset: 0 auto 0 0; width: 264px; padding: 24px 14px; color: #effaf5; background: linear-gradient(160deg, var(--uru-900), #063d31); display: flex; flex-direction: column; }
        .brand { display: flex; align-items: center; gap: 10px; padding: 8px 10px 24px; color: #fff; }
        .brand-mark { width: 38px; height: 38px; border: 2px solid rgba(255,255,255,.72); border-radius: 12px; display: grid; place-items: center; font-weight: 800; letter-spacing: -.05em; }
        .brand span { display: block; color: #bfe9d8; font-size: .75rem; }
        .brand b { display: block; font-size: 1rem; }
        .nav-label { padding: 0 12px; margin: 10px 0 6px; color: #a8d7c4; font-size: .72rem; font-weight: 750; letter-spacing: .08em; text-transform: uppercase; }
        .nav-link { min-height: 46px; display: flex; align-items: center; gap: 12px; margin: 2px 0; padding: 10px 12px; border-radius: 12px; color: #daf2e7; font-weight: 650; }
        .nav-link:hover, .nav-link:focus-visible, .nav-link.is-active { color: #fff; outline: none; background: rgba(255,255,255,.14); }
        .nav-icon { width: 22px; text-align: center; font-size: 1.08rem; }
        .sidebar-footer { margin-top: auto; padding: 16px 10px 4px; border-top: 1px solid rgba(255,255,255,.15); }
        .admin-name { overflow: hidden; color: #fff; font-weight: 700; text-overflow: ellipsis; white-space: nowrap; }
        .admin-role { margin-top: 2px; color: #bfe9d8; font-size: .8rem; }
        .logout { width: 100%; min-height: 44px; margin-top: 12px; padding: 8px 0; border: 0; color: #fff; background: transparent; cursor: pointer; text-align: left; }
        .logout:hover, .logout:focus-visible { color: #bfe9d8; outline: none; }

        .page { min-height: 100vh; margin-left: 264px; }
        .mobile-header { display: none; }
        .content { width: min(1200px, 100%); margin: 0 auto; padding: 34px 36px 56px; }
        .header-row { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 20px; }
        .muted { color: var(--muted); }
        .card { margin-bottom: 18px; padding: 20px; border: 1px solid var(--line); border-radius: 18px; background: var(--surface); box-shadow: var(--shadow); }
        .card > :last-child { margin-bottom: 0; }
        .eyebrow { margin-bottom: 5px; color: var(--uru-700); font-size: .78rem; font-weight: 800; letter-spacing: .07em; text-transform: uppercase; }
        .alert { margin-bottom: 16px; padding: 13px 15px; border-radius: 12px; }
        .alert.success { color: #0b5b37; background: #e7f7ed; border: 1px solid #bfe5ce; }
        .alert.error { color: #922019; background: #fff0ef; border: 1px solid #f7c7c3; }
        .alert ul { margin: 6px 0 0 20px; padding: 0; }

        .btn { min-height: 44px; display: inline-flex; align-items: center; justify-content: center; gap: 7px; padding: 10px 14px; border: 1px solid #cbdad3; border-radius: 11px; color: #23443a; background: #fff; cursor: pointer; font-weight: 750; text-align: center; }
        .btn:hover { border-color: var(--uru-700); color: var(--uru-800); }
        .btn:focus-visible, input:focus-visible, select:focus-visible, textarea:focus-visible, .nav-link:focus-visible { outline: 3px solid rgba(20, 128, 95, .35); outline-offset: 2px; }
        .btn-primary { border-color: var(--uru-800); color: #fff; background: var(--uru-800); }
        .btn-primary:hover { border-color: var(--uru-900); color: #fff; background: var(--uru-900); }
        .btn-danger { border-color: var(--danger); color: #fff; background: var(--danger); }
        .btn-warning { border-color: var(--warning); color: #fff; background: var(--warning); }
        .actions, .quick-actions { display: flex; flex-wrap: wrap; gap: 8px; }
        form.inline { display: inline; }
        .input, select, textarea { width: 100%; min-height: 44px; padding: 10px 12px; border: 1px solid #bdcec6; border-radius: 10px; color: var(--ink); background: #fff; }
        textarea { min-height: 132px; resize: vertical; }
        label { display: inline-block; margin-bottom: 6px; color: #263e35; font-size: .92rem; font-weight: 750; }
        .field { min-width: 0; }
        .field.full { grid-column: 1 / -1; }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .stack { display: grid; gap: 16px; }
        .search { display: flex; align-items: center; gap: 8px; }
        .search .input { min-width: min(360px, 46vw); }
        .table-wrap { overflow-x: auto; margin: 0 -2px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 13px 12px; border-bottom: 1px solid var(--line); vertical-align: top; text-align: left; }
        th { color: #50625a; font-size: .82rem; font-weight: 800; white-space: nowrap; }
        tr:last-child td { border-bottom: 0; }
        .person { display: flex; align-items: center; gap: 10px; min-width: 200px; }
        .avatar { width: 38px; height: 38px; flex: 0 0 auto; overflow: hidden; border-radius: 50%; background: #d7ebe1; object-fit: cover; }
        .details { display:grid; grid-template-columns:180px minmax(0,1fr); gap:0; margin:20px 0 0; border:1px solid var(--line); border-radius:13px; overflow:hidden; }
        .details dt, .details dd { min-width:0; margin:0; padding:11px 13px; border-bottom:1px solid var(--line); }
        .details dt { color:#50625a; background:#f8fbf9; font-weight:750; }.details dd { overflow-wrap:anywhere; }
        .details dt:nth-last-of-type(1), .details dd:last-child { border-bottom:0; }
        .danger-zone { margin-top:24px; padding-top:18px; border-top:1px solid #f1c3bf; }
        .pagination { display: flex; align-items: center; justify-content: center; flex-wrap: wrap; gap: 7px; margin-top: 18px; }
        .pagination a, .pagination span { min-width: 40px; padding: 8px 10px; border: 1px solid var(--line); border-radius: 9px; text-align: center; }
        .pagination .active { color: #fff; border-color: var(--uru-800); background: var(--uru-800); }
        .mobile-nav { display: none; }

        @media (max-width: 860px) {
            .sidebar { display: none; }
            .page { margin-left: 0; padding-bottom: 76px; }
            .mobile-header { min-height: 64px; position: sticky; z-index: 10; top: 0; display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: max(12px, env(safe-area-inset-top)) 18px 12px; color: #fff; background: var(--uru-800); box-shadow: 0 2px 12px rgba(6, 75, 58, .22); }
            .mobile-brand { display: flex; align-items: center; gap: 9px; font-weight: 800; }
            .mobile-brand .brand-mark { width: 30px; height: 30px; border-radius: 9px; font-size: .8rem; }
            .mobile-user { max-width: 42vw; overflow: hidden; color: #cff0e1; font-size: .8rem; font-weight: 700; text-overflow: ellipsis; white-space: nowrap; }
            .content { padding: 20px 16px 28px; }
            .header-row { display: grid; gap: 14px; }
            .header-row > .quick-actions, .header-row > .btn { width: 100%; }
            .header-row > .quick-actions .btn { flex: 1 1 140px; }
            .card { padding: 16px; border-radius: 15px; }
            .mobile-nav { position: fixed; z-index: 20; right: 0; bottom: 0; left: 0; display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); padding: 7px max(8px, env(safe-area-inset-right)) max(7px, env(safe-area-inset-bottom)) max(8px, env(safe-area-inset-left)); border-top: 1px solid var(--line); background: rgba(255,255,255,.97); box-shadow: 0 -8px 22px rgba(19, 57, 44, .08); }
            .mobile-nav a { min-height: 51px; display: grid; place-items: center; gap: 1px; border-radius: 10px; color: #60716a; font-size: .68rem; font-weight: 750; }
            .mobile-nav a .nav-icon { height: 20px; font-size: 1.15rem; }
            .mobile-nav a.is-active { color: var(--uru-800); background: var(--uru-100); }
            .grid { grid-template-columns: 1fr; }
            .search { align-items: stretch; flex-wrap: wrap; }
            .search .input { min-width: 100%; }
            .search .btn { flex: 1; }
            .table-wrap { margin: 0; }
            table { min-width: 620px; }
            .details { grid-template-columns:1fr; }.details dt { padding-bottom:3px; border-bottom:0; }.details dd { padding-top:3px; }
        }

        @media (max-width: 420px) {
            .btn { padding-right: 11px; padding-left: 11px; font-size: .91rem; }
            h1 { font-size: 1.35rem; }
        }
        @media (prefers-reduced-motion: reduce) { *, *::before, *::after { scroll-behavior: auto !important; transition-duration: .01ms !important; animation-duration: .01ms !important; } }
    </style>
    @yield('head')
</head>
<body>
@auth('admin')
    @php($admin = auth('admin')->user())
    @php($adminName = $admin->name ?: $admin->username)
    @php($adminRole = $admin->isSuperAdmin() ? 'ผู้ดูแลระบบสูงสุด' : ($admin->role === 'admin' ? 'ผู้ดูแลระบบ' : 'ผู้ตรวจสอบ'))
    <div class="admin-shell">
        <aside class="sidebar" aria-label="เมนูผู้ดูแล">
            <a class="brand" href="{{ route('admin.dashboard') }}">
                <span class="brand-mark" aria-hidden="true">URU</span>
                <span><b>URU Smart</b><span>Administration</span></span>
            </a>
            <div class="nav-label">จัดการระบบ</div>
            <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}" href="{{ route('admin.dashboard') }}"><span class="nav-icon">⌂</span>ภาพรวม</a>
            <a class="nav-link {{ request()->routeIs('admin.approvals.*') ? 'is-active' : '' }}" href="{{ route('admin.approvals.index') }}"><span class="nav-icon">✓</span>คิวอนุมัติ</a>
            <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'is-active' : '' }}" href="{{ route('admin.users.index') }}"><span class="nav-icon">♙</span>ผู้ใช้และข้อมูล</a>
            <a class="nav-link {{ request()->routeIs('admin.notifications.*') ? 'is-active' : '' }}" href="{{ route('admin.notifications.create') }}"><span class="nav-icon">♧</span>ส่งการแจ้งเตือน</a>
            @if($admin->isSuperAdmin())
                <div class="nav-label">ผู้ดูแลระบบ</div>
                <a class="nav-link {{ request()->routeIs('admin.accounts.*') ? 'is-active' : '' }}" href="{{ route('admin.accounts.index') }}"><span class="nav-icon">⚙</span>บัญชีผู้ดูแล</a>
            @endif
            <div class="sidebar-footer">
                <div class="admin-name">{{ $adminName }}</div>
                <div class="admin-role">{{ $adminRole }}</div>
                <form method="POST" action="{{ route('admin.logout') }}">@csrf<button class="logout" type="submit">ออกจากระบบ</button></form>
            </div>
        </aside>

        <main class="page">
            <header class="mobile-header">
                <a class="mobile-brand" href="{{ route('admin.dashboard') }}"><span class="brand-mark" aria-hidden="true">URU</span><span>Admin</span></a>
                <span class="mobile-user">{{ $adminName }}</span>
            </header>
            <div class="content">
                @if(session('success'))<div class="alert success" role="status">{{ session('success') }}</div>@endif
                @if(session('error'))<div class="alert error" role="alert">{{ session('error') }}</div>@endif
                @yield('content')
            </div>
        </main>

        <nav class="mobile-nav" aria-label="เมนูผู้ดูแลสำหรับมือถือ">
            <a class="{{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}" href="{{ route('admin.dashboard') }}"><span class="nav-icon">⌂</span><span>ภาพรวม</span></a>
            <a class="{{ request()->routeIs('admin.approvals.*') ? 'is-active' : '' }}" href="{{ route('admin.approvals.index') }}"><span class="nav-icon">✓</span><span>อนุมัติ</span></a>
            <a class="{{ request()->routeIs('admin.users.*') ? 'is-active' : '' }}" href="{{ route('admin.users.index') }}"><span class="nav-icon">♙</span><span>ผู้ใช้</span></a>
            <a class="{{ request()->routeIs('admin.notifications.*') ? 'is-active' : '' }}" href="{{ route('admin.notifications.create') }}"><span class="nav-icon">♧</span><span>แจ้งเตือน</span></a>
        </nav>
    </div>
@else
    <main class="page">
        <div class="content">@yield('content')</div>
    </main>
@endauth
@yield('scripts')
</body>
</html>
