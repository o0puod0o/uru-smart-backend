@extends('admin.layout')

@section('title', 'แดชบอร์ดผู้ดูแล')

@section('content')
<style>
    .stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;margin-bottom:22px}.stat-card{background:#fff;border:1px solid #dce3ed;border-radius:12px;padding:18px;box-shadow:0 4px 18px rgba(31,50,78,.06)}.stat-label{color:#667085;font-size:14px}.stat-value{font-size:30px;font-weight:750;margin-top:5px}.quick-actions{display:flex;gap:10px;flex-wrap:wrap}.hint{border-left:4px solid #1769c2;background:#eff6ff;padding:12px 14px;border-radius:7px;color:#264767;margin:14px 0}@media(max-width:720px){.stats{grid-template-columns:1fr 1fr}}
</style>
<div class="header-row">
    <div><h1>แดชบอร์ดผู้ดูแล</h1><div class="muted">จัดการผู้ใช้ สิทธิ์ การอนุมัติ และการแจ้งเตือนจากที่เดียว</div></div>
    <div class="quick-actions"><a class="btn btn-primary" href="{{ route('admin.notifications.create') }}">ส่งการแจ้งเตือน</a><a class="btn" href="{{ route('admin.approvals.index') }}">ดูคิวอนุมัติ</a></div>
</div>

<div class="stats">
    <div class="stat-card"><div class="stat-label">ผู้ใช้ทั้งหมด</div><div class="stat-value">{{ number_format($statistics['users']) }}</div></div>
    <div class="stat-card"><div class="stat-label">บัญชีที่ใช้งานอยู่</div><div class="stat-value">{{ number_format($statistics['active_users']) }}</div></div>
    <div class="stat-card"><div class="stat-label">บัญชีผู้ดูแลที่เปิดใช้งาน</div><div class="stat-value">{{ number_format($statistics['admins']) }}</div></div>
    <div class="stat-card"><div class="stat-label">ผู้ใช้ที่เปิด Push</div><div class="stat-value">{{ number_format($statistics['push_enabled_users']) }}</div></div>
    <div class="stat-card"><div class="stat-label">ข้อเสนอรออนุมัติ</div><div class="stat-value">{{ number_format($statistics['pending_proposals']) }}</div></div>
    <div class="stat-card"><div class="stat-label">รายงานรออนุมัติ</div><div class="stat-value">{{ number_format($statistics['pending_reports']) }}</div></div>
</div>

<div class="card">
    <h2 style="margin-top:0">ขอบเขต Admin Web</h2>
    <div class="hint">หน้านี้ใช้ session ของบัญชีในตาราง admin_accounts เท่านั้น จึงไม่ใช้ SSO หรือ Bearer token ของผู้ใช้ mobile การปรับ “สิทธิ์ API” ของผู้ใช้ในเมนูผู้ใช้ ไม่ได้ทำให้ผู้ใช้นั้นเข้าหน้า /admin ได้</div>
    <div class="quick-actions"><a class="btn" href="{{ route('admin.users.index') }}">จัดการข้อมูลผู้ใช้และสิทธิ์ API</a><a class="btn" href="{{ route('admin.approvals.index') }}">อนุมัติข้อเสนอ/รายงาน</a><a class="btn" href="{{ route('admin.notifications.create') }}">ส่ง Push และกล่องข้อความในแอป</a>@if(auth('admin')->user()->isSuperAdmin())<a class="btn" href="{{ route('admin.accounts.index') }}">จัดการบัญชีผู้ดูแล</a>@endif</div>
    @if (! $hasProposals || ! $hasReports || ! $hasRoleColumn || ! $hasPushTokens)
        <p class="muted" style="margin-bottom:0">บางโมดูลยังไม่มีตารางหรือคอลัมน์ที่ต้องใช้ในฐานข้อมูลชุดนี้ จึงแสดงสถิติเป็นศูนย์จนกว่าจะรัน migration ของโมดูลนั้น</p>
    @endif
</div>
@endsection
