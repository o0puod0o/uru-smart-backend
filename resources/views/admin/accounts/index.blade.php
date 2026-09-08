@extends('admin.layout')
@section('title', 'บัญชีผู้ดูแล')
@section('content')
<div class="card">
    <div class="header-row">
        <div><h1>บัญชีผู้ดูแล</h1><p class="muted">บัญชีชุดนี้แยกจากผู้ใช้ SSO และใช้เข้าสู่ระบบ /admin เท่านั้น</p></div>
        <a class="btn btn-primary" href="{{ route('admin.accounts.create') }}">เพิ่มบัญชีผู้ดูแล</a>
    </div>
    <form class="search" method="GET" action="{{ route('admin.accounts.index') }}" style="margin-bottom:18px">
        <input class="input" name="search" value="{{ $search }}" placeholder="ค้นหาชื่อผู้ใช้ ชื่อ หรืออีเมล">
        <button class="btn" type="submit">ค้นหา</button>
    </form>
    <div class="table-wrap"><table>
        <thead><tr><th>ชื่อผู้ใช้</th><th>ชื่อแสดง</th><th>บทบาท</th><th>สถานะ</th><th>เข้าใช้ล่าสุด</th><th></th></tr></thead>
        <tbody>
        @forelse($admins as $admin)
            <tr>
                <td><strong>{{ $admin->username }}</strong><br><span class="muted">{{ $admin->email ?: '-' }}</span></td>
                <td>{{ $admin->name }}</td>
                <td>{{ $admin->role }}</td>
                <td>{{ $admin->is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน' }}</td>
                <td>{{ $admin->last_login_at?->format('d/m/Y H:i') ?? '-' }}</td>
                <td><a class="btn" href="{{ route('admin.accounts.edit', $admin) }}">แก้ไข</a></td>
            </tr>
        @empty
            <tr><td colspan="6" class="muted">ยังไม่มีบัญชีผู้ดูแล</td></tr>
        @endforelse
        </tbody>
    </table></div>
    @if($admins->hasPages())<div class="pagination">{{ $admins->links() }}</div>@endif
</div>
@endsection
