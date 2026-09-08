@extends('admin.layout')

@section('title', 'ภาพรวมผู้ดูแล')

@section('head')
<style>
    .admin-hero { display:grid; grid-template-columns:minmax(0,1fr) auto; gap:20px; align-items:end; padding:26px; overflow:hidden; color:#fff; border-radius:20px; background:linear-gradient(135deg,#075c45,#087556 58%,#0c906a); box-shadow:0 15px 30px rgba(7,92,69,.19); }
    .admin-hero .eyebrow { color:#c6f5dd; }.admin-hero h1 { margin-bottom:7px; }.admin-hero p { max-width:630px; margin:0; color:#e3f8ed; }.admin-hero .btn { border-color:#fff; color:#075c45; background:#fff; }
    .stats { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:13px; margin:20px 0; }.stat-card { min-height:132px; padding:17px; border:1px solid var(--line); border-radius:16px; background:#fff; box-shadow:var(--shadow); }.stat-label { color:var(--muted); font-size:.86rem; font-weight:650; }.stat-value { margin-top:9px; color:var(--uru-900); font-size:clamp(1.6rem,4vw,2.15rem); font-weight:850; line-height:1; }.stat-card.attention { border-color:#f4c675; background:#fffaf0; }.stat-card.attention .stat-value { color:#9d5a00; }
    .action-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; }.action-card { min-height:112px; display:flex; flex-direction:column; justify-content:space-between; padding:16px; border:1px solid var(--line); border-radius:14px; background:#fff; }.action-card:hover { border-color:var(--uru-700); box-shadow:var(--shadow); }.action-card b { display:block; margin-bottom:3px; }.action-card span { color:var(--muted); font-size:.88rem; }.system-note { padding:13px 15px; border-left:4px solid var(--uru-700); border-radius:0 10px 10px 0; background:var(--uru-100); color:#245443; }
    .info-stats { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:10px; margin-top:14px; }.info-stat { padding:14px; border:1px solid #cfe7da; border-radius:13px; background:#f7fcf9; }.info-stat b { display:block; margin-top:5px; color:var(--uru-900); font-size:1.45rem; line-height:1.1; }.bridge-state { padding:14px; border-radius:12px; background:#fff8e8; color:#805000; }
    @media(max-width:860px){.admin-hero{grid-template-columns:1fr;padding:21px}.admin-hero .btn{width:100%}.stats{grid-template-columns:repeat(2,minmax(0,1fr))}.action-grid{grid-template-columns:1fr}.info-stats{grid-template-columns:repeat(2,minmax(0,1fr))}}
</style>
@endsection

@section('content')
<section class="admin-hero" aria-labelledby="admin-dashboard-title">
    <div>
        <div class="eyebrow">URU Smart · Admin WebView</div>
        <h1 id="admin-dashboard-title">จัดการระบบจากที่เดียว</h1>
        <p>ตรวจคิวอนุมัติ ดูข้อมูลผู้ใช้ และส่งการแจ้งเตือนถึงแอปได้จากหน้าเดียว</p>
    </div>
    <a class="btn" href="{{ route('admin.approvals.index') }}">เปิดคิวอนุมัติ</a>
</section>

<section class="stats" aria-label="สถิติระบบ">
    <article class="stat-card"><div class="stat-label">ผู้ใช้ในแอป</div><div class="stat-value">{{ number_format($statistics['users']) }}</div></article>
    <article class="stat-card"><div class="stat-label">บัญชีที่ใช้งาน</div><div class="stat-value">{{ number_format($statistics['active_users']) }}</div></article>
    <article class="stat-card"><div class="stat-label">อุปกรณ์รับ Push</div><div class="stat-value">{{ number_format($statistics['push_ready_devices']) }}</div></article>
    <article class="stat-card attention"><div class="stat-label">ข้อเสนอรออนุมัติ</div><div class="stat-value">{{ number_format($statistics['pending_proposals']) }}</div></article>
    <article class="stat-card attention"><div class="stat-label">รายงานรออนุมัติ</div><div class="stat-value">{{ number_format($statistics['pending_reports']) }}</div></article>
    <article class="stat-card"><div class="stat-label">บัญชีผู้ดูแลที่เปิดใช้งาน</div><div class="stat-value">{{ number_format($statistics['admins']) }}</div></article>
</section>

<section class="card" aria-labelledby="quick-action-title">
    <div class="eyebrow">ทางลัด</div>
    <h2 id="quick-action-title">งานที่ทำบ่อย</h2>
    <div class="action-grid">
        <a class="action-card" href="{{ route('admin.approvals.index') }}"><div><b>ตรวจและอนุมัติรายการ</b><span>จัดการข้อเสนอและรายงานที่ผู้ใช้ส่งเข้ามา</span></div><span aria-hidden="true">ไปยังคิวอนุมัติ →</span></a>
        <a class="action-card" href="{{ route('admin.users.index') }}"><div><b>ค้นหาผู้ใช้และสิทธิ์ API</b><span>ดูข้อมูลสถานะและปรับสิทธิ์การเรียก API ของผู้ใช้</span></div><span aria-hidden="true">ไปยังผู้ใช้ →</span></a>
        <a class="action-card" href="{{ route('admin.notifications.create') }}"><div><b>ส่งการแจ้งเตือน</b><span>ส่งถึงผู้ใช้รายเดียวหรือผู้ใช้ ACTIVE ทั้งหมด</span></div><span aria-hidden="true">สร้างข้อความ →</span></a>
        @if(auth('admin')->user()->isSuperAdmin())
            <a class="action-card" href="{{ route('admin.accounts.index') }}"><div><b>จัดการบัญชีผู้ดูแล</b><span>สร้าง ปิดใช้งาน หรือกำหนดบทบาทของผู้ดูแลระบบ</span></div><span aria-hidden="true">ไปยังบัญชีผู้ดูแล →</span></a>
        @endif
    </div>
</section>

<section class="card" aria-labelledby="info-data-title">
    <div class="eyebrow">ข้อมูลนักวิจัย</div>
    <h2 id="info-data-title">ข้อมูลโมดูล Expert และ LRD</h2>
    @if($info['available'] ?? false)
        <div class="muted">อัปเดตจากระบบข้อมูลกลาง</div>
        <div class="info-stats">
            <div class="info-stat"><span class="muted">โปรไฟล์ Expert</span><b>{{ number_format((int) data_get($info, 'data.expert.profiles', 0)) }}</b></div>
            <div class="info-stat"><span class="muted">ความสนใจ</span><b>{{ number_format((int) data_get($info, 'data.expert.interests', 0)) }}</b></div>
            <div class="info-stat"><span class="muted">นักวิจัย LRD</span><b>{{ number_format((int) data_get($info, 'data.lrd.researchers', 0)) }}</b></div>
            <div class="info-stat"><span class="muted">โครงการ LRD</span><b>{{ number_format((int) data_get($info, 'data.lrd.projects', 0)) }}</b></div>
        </div>
    @else
        <p class="bridge-state">ยังเชื่อมข้อมูลสรุปจาก Info ไม่ได้ ระบบ Admin ส่วนอื่นยังใช้งานได้ตามปกติ</p>
    @endif
</section>

@endsection
