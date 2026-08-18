@extends('admin.layout')
@section('title', 'เข้าสู่ระบบ')
@section('content')
<div class="login"><div class="card">
    <h1>เข้าสู่ระบบจัดการข้อมูล</h1>
    <p class="muted">ใช้บัญชี SSO ที่ได้รับสิทธิ์ผู้ดูแลระบบ</p>
    @if($errors->any())<div class="alert error">{{ $errors->first() }}</div>@endif
    <form class="stack" method="POST" action="{{ route('admin.login') }}">
        @csrf
        <div class="field"><label for="email">อีเมล</label><input class="input" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus></div>
        <div class="field"><label for="password">รหัสผ่าน</label><input class="input" id="password" name="password" type="password" autocomplete="current-password" required></div>
        <button class="btn btn-primary" type="submit">เข้าสู่ระบบ</button>
    </form>
</div></div>
@endsection
