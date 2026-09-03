@extends('admin.layout')
@section('title', 'เข้าสู่ระบบ')
@section('content')
<div class="login"><div class="card">
    <h1>เข้าสู่ระบบจัดการข้อมูล</h1>
    <p class="muted">ใช้บัญชี SSO ที่ได้รับสิทธิ์ผู้ดูแลระบบ</p>
    @if($errors->any())<div class="alert error">{{ $errors->first() }}</div>@endif
    <div class="stack" style="margin-bottom:18px">
        <a class="btn btn-primary" href="{{ route('admin.sso.redirect') }}">เข้าสู่ระบบด้วย SSO มหาวิทยาลัย</a>
        <div class="muted">แนะนำให้ใช้ปุ่มนี้สำหรับ SSO จริง เพราะจะเป็น flow เดียวกับแอป</div>
    </div>
    <hr style="border:0;border-top:1px solid #e5e9f0;margin:18px 0">
    <div class="muted" style="margin-bottom:12px">หรือกรอก email/password เฉพาะกรณีระบบรองรับ password login</div>
    <form class="stack" method="POST" action="{{ route('admin.login') }}">
        @csrf
        <div class="field"><label for="email">อีเมล</label><input class="input" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus></div>
        <div class="field"><label for="password">รหัสผ่าน</label><input class="input" id="password" name="password" type="password" autocomplete="current-password" required></div>
        <button class="btn btn-primary" type="submit">เข้าสู่ระบบ</button>
    </form>
</div></div>
@endsection
