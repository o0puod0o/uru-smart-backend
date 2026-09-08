@extends('admin.layout')
@section('title', 'เข้าสู่ระบบผู้ดูแล')
@section('content')
<div class="login"><div class="card">
    <h1>เข้าสู่ระบบผู้ดูแล</h1>
    <p class="muted">ใช้บัญชีผู้ดูแลเฉพาะระบบนี้ ไม่เชื่อมต่อกับ SSO ของมหาวิทยาลัย</p>
    @if($errors->any())<div class="alert error">{{ $errors->first() }}</div>@endif
    <form class="stack" method="POST" action="{{ route('admin.login') }}">
        @csrf
        <div class="field"><label for="username">ชื่อผู้ใช้</label><input class="input" id="username" name="username" type="text" value="{{ old('username') }}" autocomplete="username" required autofocus></div>
        <div class="field"><label for="password">รหัสผ่าน</label><input class="input" id="password" name="password" type="password" autocomplete="current-password" required></div>
        <button class="btn btn-primary" type="submit">เข้าสู่ระบบ</button>
    </form>
</div></div>
@endsection
