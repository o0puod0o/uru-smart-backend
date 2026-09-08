@extends('admin.layout')

@section('title', 'ผู้ใช้และข้อมูล')

@section('head')
<style>
    .user-summary { margin-bottom:20px; }.user-search { display:flex; gap:8px; }.user-search .input { flex:1; }.user-list { display:grid; gap:10px; }.user-row { display:grid; grid-template-columns:minmax(220px,1.7fr) minmax(100px,.6fr) minmax(140px,1fr) auto; gap:14px; align-items:center; padding:13px 0; border-bottom:1px solid var(--line); }.user-row:last-child { border-bottom:0; }.user-secondary { color:var(--muted); font-size:.84rem; }.status { display:inline-flex; align-items:center; width:max-content; min-height:26px; padding:3px 9px; border-radius:999px; background:#eef2f0; color:#53635d; font-size:.76rem; font-weight:800; }.status.active { background:#e2f5e9; color:#087545; }.user-actions { display:flex; gap:7px; }.user-actions .btn { min-width:51px; }
    @media(max-width:860px){.user-search{flex-wrap:wrap}.user-search .input{flex-basis:100%}.user-search .btn{flex:1}.user-row{grid-template-columns:1fr auto;gap:10px;padding:14px 0}.user-row > .user-secondary:nth-of-type(1),.user-row > .user-secondary:nth-of-type(2){grid-column:1 / -1}.user-actions{grid-column:2;grid-row:1;align-self:center}.user-actions .btn{min-width:44px;padding:10px}.user-row .person{min-width:0}}
</style>
@endsection

@section('content')
<div class="header-row user-summary"><div><div class="eyebrow">ข้อมูลส่วนกลาง</div><h1>ผู้ใช้และข้อมูล</h1><div class="muted">ทั้งหมด {{ number_format($users->total()) }} รายการ</div></div><a class="btn" href="{{ route('admin.dashboard') }}">กลับภาพรวม</a></div>

<section class="card" aria-label="ค้นหาผู้ใช้">
    <form class="user-search" method="GET"><input class="input" name="search" value="{{ $search }}" placeholder="ค้นหาชื่อ อีเมล หรือรหัสผู้ใช้" aria-label="คำค้นหาผู้ใช้"><button class="btn btn-primary" type="submit">ค้นหา</button>@if($search)<a class="btn" href="{{ route('admin.users.index') }}">ล้าง</a>@endif</form>
</section>

<section class="card" aria-label="รายชื่อผู้ใช้">
    <div class="user-list">
        @forelse($users as $user)
            <article class="user-row">
                <div class="person">
                    @if($user->display_picture)<img class="avatar" src="{{ $user->display_picture }}" alt="รูป {{ $user->full_name_th }}">@else<div class="avatar" aria-hidden="true"></div>@endif
                    <div style="min-width:0"><strong style="display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $user->full_name_th }}</strong><div class="user-secondary" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $user->email ?: '-' }}</div></div>
                </div>
                <div class="user-secondary"><strong>รหัส</strong><br>{{ $user->code ?: '-' }}</div>
                <div class="user-secondary"><strong>หน่วยงาน</strong><br>{{ $user->department_name_th ?: '-' }}</div>
                <div class="user-actions"><span class="status {{ $user->status === 'ACTIVE' ? 'active' : '' }}" title="สถานะ">{{ $user->status ?: '-' }}</span><a class="btn" href="{{ route('admin.users.show', $user) }}">ดู</a><a class="btn btn-primary" href="{{ route('admin.users.edit', $user) }}">แก้ไข</a></div>
            </article>
        @empty
            <p class="muted" style="margin:16px 0;text-align:center">ไม่พบข้อมูลผู้ใช้</p>
        @endforelse
    </div>
    @if($users->hasPages())
        <nav class="pagination" aria-label="หน้าแสดงผู้ใช้">
            @if($users->onFirstPage())<span>ก่อนหน้า</span>@else<a href="{{ $users->previousPageUrl() }}">ก่อนหน้า</a>@endif
            <span class="active">{{ $users->currentPage() }} / {{ $users->lastPage() }}</span>
            @if($users->hasMorePages())<a href="{{ $users->nextPageUrl() }}">ถัดไป</a>@else<span>ถัดไป</span>@endif
        </nav>
    @endif
</section>
@endsection
