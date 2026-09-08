@extends('admin.layout')

@section('title', 'เข้าสู่ระบบผู้ดูแล')

@section('head')
<style>
    .login-wrap { min-height:calc(100vh - 112px); display:grid; place-items:center; }.login-card { width:min(100%,440px); padding:26px; border:1px solid var(--line); border-radius:20px; background:#fff; box-shadow:0 18px 42px rgba(11,67,49,.13); }.login-mark { width:52px; height:52px; display:grid; place-items:center; margin-bottom:20px; border-radius:16px; color:#fff; background:var(--uru-800); font-weight:850; letter-spacing:-.06em; }.login-card h1 { margin-bottom:8px; }.login-card .btn { width:100%; }.login-note { margin-top:18px; padding-top:15px; border-top:1px solid var(--line); color:var(--muted); font-size:.86rem; }
    @media(max-width:860px){.login-wrap{min-height:calc(100vh - 104px)}.login-card{padding:22px}}
</style>
@endsection

@section('content')
<div class="login-wrap">
    <section class="login-card" aria-labelledby="admin-login-title">
        <div class="login-mark" aria-hidden="true">URU</div>
        <div class="eyebrow">Admin WebView</div>
        <h1 id="admin-login-title">เข้าสู่ระบบผู้ดูแล</h1>
        <p class="muted">ใช้บัญชีผู้ดูแลเฉพาะระบบนี้</p>
        @if($errors->any())<div class="alert error" role="alert">{{ $errors->first() }}</div>@endif
        <form class="stack" method="POST" action="{{ route('admin.login') }}">
            @csrf
            <div class="field"><label for="username">ชื่อผู้ใช้</label><input class="input" id="username" name="username" type="text" value="{{ old('username') }}" autocomplete="username" required autofocus></div>
            <div class="field"><label for="password">รหัสผ่าน</label><input class="input" id="password" name="password" type="password" autocomplete="current-password" required></div>
            <button class="btn btn-primary" type="submit">เข้าสู่ระบบ</button>
        </form>
        <p class="login-note">ไม่ใช้ SSO และไม่ใช้ Bearer token ของผู้ใช้ใน Mobile App</p>
    </section>
</div>
@endsection
