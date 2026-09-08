@extends('admin.layout')

@section('title', 'ส่งการแจ้งเตือน')

@section('content')
<div class="card">
    <div class="header-row"><div><h1>ส่งการแจ้งเตือน</h1><div class="muted">บันทึกเข้ากล่องข้อความในแอป และส่ง Expo Push ให้เครื่องที่ผู้ใช้เปิดรับไว้</div></div><a class="btn" href="{{ route('admin.dashboard') }}">กลับแดชบอร์ด</a></div>

    @if($errors->any())
        <div class="alert error"><strong>ยังส่งไม่ได้</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <form method="POST" action="{{ route('admin.notifications.store') }}" id="notification-form">
        @csrf
        <div class="grid">
            <div class="field full">
                <label>ผู้รับ</label>
                <select name="recipient" id="recipient" required>
                    <option value="user" @selected(old('recipient', 'user') === 'user')>ผู้ใช้หนึ่งคน</option>
                    <option value="all" @selected(old('recipient') === 'all')>ผู้ใช้ที่มีสถานะ ACTIVE ทุกคน</option>
                </select>
            </div>
            <div class="field full" id="recipient-user-field">
                <label for="user_id">เลือกผู้ใช้</label>
                <select name="user_id" id="user_id">
                    <option value="">-- เลือกผู้รับ --</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" @selected((string) old('user_id') === (string) $user->id)>#{{ $user->id }} · {{ $user->full_name_th }}{{ $user->email ? ' · '.$user->email : '' }}</option>
                    @endforeach
                </select>
                <div class="muted">แสดงรายชื่อสูงสุด 1,000 คน; หากไม่พบ ให้ค้นหาจากหน้า “ผู้ใช้และสิทธิ์” แล้วใช้รหัสผู้ใช้</div>
            </div>
            <div class="field full"><label for="title">หัวข้อ</label><input class="input" id="title" name="title" maxlength="120" value="{{ old('title') }}" required></div>
            <div class="field full"><label for="body">ข้อความ</label><textarea id="body" name="body" rows="6" maxlength="1000">{{ old('body') }}</textarea></div>
            <div class="field full" id="broadcast-confirmation"><label style="display:flex;align-items:flex-start;gap:8px"><input type="checkbox" name="confirm_broadcast" value="1" @checked(old('confirm_broadcast')) style="width:auto;margin-top:4px"><span>ยืนยันว่าได้ตรวจข้อความแล้ว และต้องการส่งถึงผู้ใช้ ACTIVE ทุกคน</span></label></div>
        </div>
        <div style="border-left:4px solid #1769c2;background:#eff6ff;padding:12px 14px;border-radius:7px;color:#264767;margin:14px 0">ระบบจะไม่รับ `user_id` จากข้อความ Push เพื่อกำหนดสิทธิ์ใด ๆ; ผู้ดูแลต้องเข้าสู่ระบบ WebView นี้ก่อนส่งเสมอ</div>
        <button class="btn btn-primary" type="submit" onclick="return confirm('ยืนยันส่งการแจ้งเตือนตามผู้รับที่เลือก?')">ส่งการแจ้งเตือน</button>
    </form>
</div>

<script>
    const recipient = document.getElementById('recipient');
    const userField = document.getElementById('recipient-user-field');
    const confirmation = document.getElementById('broadcast-confirmation');
    function updateRecipientFields() {
        const all = recipient.value === 'all';
        userField.hidden = all;
        confirmation.hidden = !all;
        document.getElementById('user_id').required = !all;
    }
    recipient.addEventListener('change', updateRecipientFields);
    updateRecipientFields();
</script>
@endsection
