@extends('admin.layout')
@section('title', $user->full_name_th)
@section('content')
<div class="card">
    <div class="header-row"><div><h1>{{ $user->full_name_th }}</h1><div class="muted">{{ $user->full_name_en }}</div></div><div class="actions"><a class="btn" href="{{ route('admin.users.index') }}">กลับ</a><a class="btn btn-primary" href="{{ route('admin.users.edit', $user) }}">แก้ไข</a></div></div>
    <dl class="details">
        <dt>รหัสบุคลากร</dt><dd>{{ $user->code }}</dd><dt>อีเมล</dt><dd>{{ $user->email ?: '-' }}</dd><dt>สถานะ</dt><dd>{{ $user->status }}</dd><dt>วันเกิด</dt><dd>{{ $user->birth_date?->format('d/m/Y') ?: '-' }}</dd>
        <dt>เบอร์มือถือ</dt><dd>{{ $user->phone_mobile ?: '-' }}</dd><dt>เบอร์ที่ทำงาน</dt><dd>{{ $user->phone_work ?: '-' }}</dd><dt>LINE</dt><dd>{{ $user->line_id ?: '-' }}</dd><dt>เว็บไซต์</dt><dd>{{ $user->website ?: '-' }}</dd>
        <dt>หน่วยงานหลัก</dt><dd>{{ $user->department_name_th ?: '-' }}</dd><dt>หน่วยงานย่อย</dt><dd>{{ $user->subDepartment?->name ?: '-' }}</dd><dt>ตำแหน่ง</dt><dd>{{ $user->position ?: '-' }}</dd><dt>สาขา</dt><dd>{{ $user->branch ?: '-' }}</dd>
        <dt>ที่อยู่</dt><dd>{{ collect([$user->address, $user->moo ? 'หมู่ '.$user->moo : null, $user->road, $user->tambon, $user->amphoe, $user->province, $user->zipcode])->filter()->join(' ') ?: '-' }}</dd><dt>แนะนำตัว</dt><dd>{{ $user->bio ?: '-' }}</dd>
    </dl>
    <div class="danger-zone"><form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('ยืนยันการลบผู้ใช้นี้? ข้อมูลที่เกี่ยวข้องอาจถูกลบด้วย')">@csrf @method('DELETE')<button class="btn btn-danger" type="submit">ลบผู้ใช้</button></form></div>
</div>
@endsection
