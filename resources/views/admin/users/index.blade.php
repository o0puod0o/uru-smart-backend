@extends('admin.layout')
@section('title', 'จัดการผู้ใช้')
@section('content')
<div class="card">
    <div class="header-row"><div><h1>จัดการผู้ใช้</h1><div class="muted">ทั้งหมด {{ number_format($users->total()) }} รายการ</div></div>
        <form class="search" method="GET"><input class="input" name="search" value="{{ $search }}" placeholder="ชื่อ อีเมล หรือรหัส"><button class="btn btn-primary">ค้นหา</button>@if($search)<a class="btn" href="{{ route('admin.users.index') }}">ล้าง</a>@endif</form>
    </div>
    <div class="table-wrap"><table><thead><tr><th>ผู้ใช้</th><th>รหัส</th><th>หน่วยงาน</th><th>สถานะ</th><th></th></tr></thead><tbody>
    @forelse($users as $user)
        <tr><td><div class="person">@if($user->display_picture)<img class="avatar" src="{{ $user->display_picture }}" alt="">@else<div class="avatar"></div>@endif<div><strong>{{ $user->full_name_th }}</strong><div class="muted">{{ $user->email }}</div></div></div></td><td>{{ $user->code }}</td><td>{{ $user->department_name_th ?: '-' }}</td><td>{{ $user->status }}</td><td><div class="actions"><a class="btn" href="{{ route('admin.users.show', $user) }}">ดู</a><a class="btn btn-primary" href="{{ route('admin.users.edit', $user) }}">แก้ไข</a></div></td></tr>
    @empty<tr><td colspan="5" class="muted">ไม่พบข้อมูลผู้ใช้</td></tr>@endforelse
    </tbody></table></div>
    @if($users->hasPages())<nav class="pagination">@if($users->onFirstPage())<span>ก่อนหน้า</span>@else<a href="{{ $users->previousPageUrl() }}">ก่อนหน้า</a>@endif<span class="active">หน้า {{ $users->currentPage() }} / {{ $users->lastPage() }}</span>@if($users->hasMorePages())<a href="{{ $users->nextPageUrl() }}">ถัดไป</a>@else<span>ถัดไป</span>@endif</nav>@endif
</div>
@endsection
