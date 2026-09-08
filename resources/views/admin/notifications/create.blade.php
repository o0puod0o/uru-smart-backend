@extends('admin.layout')

@section('title', 'ส่งการแจ้งเตือน')

@section('head')
<style>
    .notification-wrap { width:min(760px,100%); margin:0 auto; }.broadcast-warning { margin-top:14px; padding:14px; border:1px solid #f2cf91; border-radius:12px; color:#764800; background:#fff8e9; }.broadcast-warning[hidden], #recipient-user-field[hidden] { display:none; }.check-row { display:flex; align-items:flex-start; gap:10px; margin-top:13px; }.check-row input { width:19px; height:19px; margin-top:3px; accent-color:var(--uru-800); }.form-footer { display:flex; justify-content:flex-end; gap:10px; margin-top:20px; }.form-footer .btn { min-width:180px; }@media(max-width:860px){.form-footer .btn{width:100%}}
</style>
@endsection

@section('content')
<div class="notification-wrap">
    <div class="header-row"><div><div class="eyebrow">การแจ้งเตือน</div><h1>ส่งข้อความ</h1><div class="muted">พร้อมส่ง Push ไปยัง {{ number_format($pushReadyDevices) }} อุปกรณ์</div></div><a class="btn" href="{{ route('admin.dashboard') }}">กลับ</a></div>

    <section class="card" aria-labelledby="notification-form-title">
        <h2 id="notification-form-title">สร้างข้อความ</h2>
        @if($errors->any())
            <div class="alert error" role="alert"><strong>ยังส่งไม่ได้</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        <form class="stack" method="POST" action="{{ route('admin.notifications.store') }}" id="notification-form">
            @csrf
            <div class="field"><label for="recipient">ผู้รับ</label><select name="recipient" id="recipient" required><option value="user" @selected(old('recipient', 'user') === 'user')>ผู้ใช้หนึ่งคน</option><option value="all" @selected(old('recipient') === 'all')>ผู้ใช้ ACTIVE ทุกคน</option></select></div>
            <div class="field" id="recipient-user-field"><label for="user_id">เลือกผู้ใช้</label><select name="user_id" id="user_id"><option value="">-- เลือกผู้รับ --</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected((string) old('user_id') === (string) $user->id)>#{{ $user->id }} · {{ $user->full_name_th }}{{ $user->email ? ' · '.$user->email : '' }}</option>@endforeach</select></div>
            <div class="field"><label for="title">หัวข้อ</label><input class="input" id="title" name="title" maxlength="120" value="{{ old('title') }}" required></div>
            <div class="field"><label for="body">ข้อความ <span class="muted" style="font-weight:500">(ไม่บังคับ)</span></label><textarea id="body" name="body" rows="6" maxlength="1000">{{ old('body') }}</textarea></div>
            <div class="broadcast-warning" id="broadcast-confirmation" hidden>
                <strong>กำลังส่งถึงผู้ใช้ในแอปที่ใช้งานอยู่ทั้งหมด</strong>
                <label class="check-row"><input type="checkbox" name="confirm_broadcast" value="1" @checked(old('confirm_broadcast'))><span>ยืนยันส่งข้อความและ Push ไปยังอุปกรณ์ที่ลงทะเบียนไว้</span></label>
            </div>
            <div class="form-footer"><button class="btn btn-primary" type="submit" onclick="return confirm('ยืนยันส่งการแจ้งเตือนตามผู้รับที่เลือก?')">ส่งการแจ้งเตือน</button></div>
        </form>
    </section>
</div>
@endsection

@section('scripts')
<script>
    const recipient = document.getElementById('recipient');
    const userField = document.getElementById('recipient-user-field');
    const confirmation = document.getElementById('broadcast-confirmation');
    const userId = document.getElementById('user_id');
    function updateRecipientFields() {
        const isBroadcast = recipient.value === 'all';
        userField.hidden = isBroadcast;
        confirmation.hidden = !isBroadcast;
        userId.required = !isBroadcast;
    }
    recipient.addEventListener('change', updateRecipientFields);
    updateRecipientFields();
</script>
@endsection
