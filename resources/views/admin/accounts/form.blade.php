@extends('admin.layout')
@section('title', $admin->exists ? 'แก้ไขบัญชีผู้ดูแล' : 'เพิ่มบัญชีผู้ดูแล')
@section('content')
<div class="card" style="max-width:760px">
    <div class="header-row"><div><h1>{{ $admin->exists ? 'แก้ไขบัญชีผู้ดูแล' : 'เพิ่มบัญชีผู้ดูแล' }}</h1><p class="muted">บัญชีนี้ใช้กับหน้า /admin เท่านั้น</p></div><a class="btn" href="{{ route('admin.accounts.index') }}">กลับ</a></div>
    @if($errors->any())<div class="alert error">{{ $errors->first() }}</div>@endif
    <form class="stack" method="POST" action="{{ $admin->exists ? route('admin.accounts.update', $admin) : route('admin.accounts.store') }}">
        @csrf
        @if($admin->exists)@method('PUT')@endif
        <div class="grid">
            <div class="field"><label for="username">ชื่อผู้ใช้</label><input class="input" id="username" name="username" value="{{ old('username', $admin->username) }}" required autocomplete="username"></div>
            <div class="field"><label for="name">ชื่อแสดง</label><input class="input" id="name" name="name" value="{{ old('name', $admin->name) }}" required></div>
            <div class="field full"><label for="email">อีเมล (ถ้ามี)</label><input class="input" id="email" name="email" type="email" value="{{ old('email', $admin->email) }}"></div>
            <div class="field"><label for="role">บทบาท</label><select id="role" name="role"><option value="super_admin" @selected(old('role', $admin->role ?: 'admin') === 'super_admin')>super_admin</option><option value="admin" @selected(old('role', $admin->role ?: 'admin') === 'admin')>admin</option><option value="editor" @selected(old('role', $admin->role ?: 'admin') === 'editor')>editor</option></select></div>
            <div class="field"><label for="password">{{ $admin->exists ? 'รหัสผ่านใหม่ (เว้นว่างหากไม่เปลี่ยน)' : 'รหัสผ่าน' }}</label><input class="input" id="password" name="password" type="password" {{ $admin->exists ? '' : 'required' }} autocomplete="new-password"></div>
            <div class="field"><label for="password_confirmation">ยืนยันรหัสผ่าน</label><input class="input" id="password_confirmation" name="password_confirmation" type="password" {{ $admin->exists ? '' : 'required' }} autocomplete="new-password"></div>
            <div class="field"><label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $admin->exists ? $admin->is_active : true))> เปิดใช้งานบัญชีนี้</label></div>
        </div>
        <div class="actions"><button class="btn btn-primary" type="submit">บันทึก</button><a class="btn" href="{{ route('admin.accounts.index') }}">ยกเลิก</a></div>
    </form>
</div>
@endsection
