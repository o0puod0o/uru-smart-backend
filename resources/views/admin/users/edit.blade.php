@extends('admin.layout')
@section('title', 'แก้ไข '.$user->full_name_th)
@section('content')
<div class="card">
    <div class="header-row"><div><h1>แก้ไขข้อมูลผู้ใช้</h1><div class="muted">{{ $user->full_name_th }}</div></div><a class="btn" href="{{ route('admin.users.show', $user) }}">ยกเลิก</a></div>
    @if($errors->any())<div class="alert error"><strong>กรุณาตรวจสอบข้อมูล</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="POST" action="{{ route('admin.users.update', $user) }}">@csrf @method('PUT')
        <h2>ข้อมูลทั่วไป</h2><div class="grid">
            <div class="field"><label>รหัสบุคลากร</label><input class="input" name="code" value="{{ old('code', $user->code) }}" required></div><div class="field"><label>อีเมล</label><input class="input" type="email" name="email" value="{{ old('email', $user->email) }}"></div>
            <div class="field"><label>คำนำหน้าภาษาไทย</label><input class="input" name="prefix_th" value="{{ old('prefix_th', $user->prefix_th) }}"></div><div class="field"><label>สถานะ</label><input class="input" name="status" value="{{ old('status', $user->status) }}" required></div>
            <div class="field"><label>ชื่อภาษาไทย</label><input class="input" name="first_name_th" value="{{ old('first_name_th', $user->first_name_th) }}" required></div><div class="field"><label>นามสกุลภาษาไทย</label><input class="input" name="last_name_th" value="{{ old('last_name_th', $user->last_name_th) }}" required></div>
            <div class="field"><label>คำนำหน้าภาษาอังกฤษ</label><input class="input" name="prefix_en" value="{{ old('prefix_en', $user->prefix_en) }}"></div><div></div>
            <div class="field"><label>ชื่อภาษาอังกฤษ</label><input class="input" name="first_name_en" value="{{ old('first_name_en', $user->first_name_en) }}" required></div><div class="field"><label>นามสกุลภาษาอังกฤษ</label><input class="input" name="last_name_en" value="{{ old('last_name_en', $user->last_name_en) }}" required></div>
            <div class="field"><label>วันเกิด</label><input class="input" type="date" name="birth_date" value="{{ old('birth_date', $user->birth_date?->format('Y-m-d')) }}"></div>
        </div>
        <h2>การติดต่อ</h2><div class="grid">
            <div class="field"><label>เบอร์มือถือ</label><input class="input" name="phone_mobile" value="{{ old('phone_mobile', $user->phone_mobile) }}"></div><div class="field"><label>เบอร์ที่ทำงาน</label><input class="input" name="phone_work" value="{{ old('phone_work', $user->phone_work) }}"></div>
            <div class="field"><label>LINE ID</label><input class="input" name="line_id" value="{{ old('line_id', $user->line_id) }}"></div><div class="field"><label>Facebook</label><input class="input" name="facebook" value="{{ old('facebook', $user->facebook) }}"></div>
            <div class="field full"><label>เว็บไซต์</label><input class="input" type="url" name="website" value="{{ old('website', $user->website) }}"></div><div class="field full"><label>แนะนำตัว</label><textarea name="bio" rows="4">{{ old('bio', $user->bio) }}</textarea></div>
        </div>
        <h2>หน่วยงานและตำแหน่ง</h2><div class="grid">
            <div class="field"><label>หน่วยงานหลัก</label><select name="department_id"><option value="">- ไม่ระบุ -</option>@foreach($departments as $department)<option value="{{ $department->dep_id }}" @selected((string)old('department_id', $user->department_id)===(string)$department->dep_id)>{{ $department->name }}</option>@endforeach</select></div>
            <div class="field"><label>หน่วยงานย่อย</label><select name="sub_dep_id"><option value="">- ไม่ระบุ -</option>@foreach($subDepartments as $sub)<option value="{{ $sub->sub_dep_id }}" @selected((string)old('sub_dep_id', $user->sub_dep_id)===(string)$sub->sub_dep_id)>{{ $sub->name }}</option>@endforeach</select></div>
            <div class="field"><label>ตำแหน่ง</label><input class="input" name="position" value="{{ old('position', $user->position) }}"></div><div class="field"><label>สาขา</label><input class="input" name="branch" value="{{ old('branch', $user->branch) }}"></div>
        </div>
        <h2>ที่อยู่</h2><div class="grid">
            <div class="field full"><label>ที่อยู่</label><input class="input" name="address" value="{{ old('address', $user->address) }}"></div><div class="field"><label>หมู่</label><input class="input" name="moo" value="{{ old('moo', $user->moo) }}"></div><div class="field"><label>ถนน</label><input class="input" name="road" value="{{ old('road', $user->road) }}"></div>
            <div class="field"><label>ตำบล</label><input class="input" name="tambon" value="{{ old('tambon', $user->tambon) }}"></div><div class="field"><label>อำเภอ</label><input class="input" name="amphoe" value="{{ old('amphoe', $user->amphoe) }}"></div><div class="field"><label>จังหวัด</label><input class="input" name="province" value="{{ old('province', $user->province) }}"></div><div class="field"><label>รหัสไปรษณีย์</label><input class="input" name="zipcode" value="{{ old('zipcode', $user->zipcode) }}"></div>
        </div>
        <div style="margin-top:22px"><button class="btn btn-primary" type="submit">บันทึกข้อมูล</button></div>
    </form>
</div>
@endsection
