@extends('admin.layout')

@section('title', 'คิวอนุมัติ')

@section('content')
<div class="header-row"><div><h1>คิวอนุมัติ</h1><div class="muted">แสดงเฉพาะรายการที่ผู้ใช้ส่งเพื่อพิจารณาแล้ว</div></div><a class="btn" href="{{ route('admin.dashboard') }}">กลับแดชบอร์ด</a></div>

<div class="card" style="margin-bottom:20px">
    <h2 style="margin-top:0">ข้อเสนอโครงการ</h2>
    @if (! $hasProposals)
        <p class="muted">ยังไม่มีตาราง proposals ในฐานข้อมูล จึงยังเปิดใช้คิวอนุมัติส่วนนี้ไม่ได้</p>
    @else
        <div class="table-wrap"><table><thead><tr><th>หัวข้อ</th><th>ผู้ส่ง</th><th>ปี</th><th>ส่งเมื่อ</th><th>ดำเนินการ</th></tr></thead><tbody>
        @forelse($proposals as $proposal)
            <tr><td><strong>{{ $proposal->title }}</strong><div class="muted">{{ \Illuminate\Support\Str::limit($proposal->summary, 100) }}</div></td><td>{{ $proposal->owner?->full_name_th ?: 'ไม่พบผู้ใช้' }}</td><td>{{ $proposal->year }}</td><td>{{ $proposal->created_at?->format('d/m/Y H:i') }}</td><td><div class="actions"><form class="inline" method="POST" action="{{ route('admin.approvals.proposals.update', $proposal) }}" onsubmit="return confirm('อนุมัติข้อเสนอนี้?')">@csrf @method('PUT')<input type="hidden" name="status" value="approved"><button class="btn" style="background:#1f8a4d;color:#fff">อนุมัติ</button></form><form class="inline" method="POST" action="{{ route('admin.approvals.proposals.update', $proposal) }}" onsubmit="return confirm('ปฏิเสธข้อเสนอนี้?')">@csrf @method('PUT')<input type="hidden" name="status" value="rejected"><button class="btn btn-danger">ปฏิเสธ</button></form></div></td></tr>
        @empty
            <tr><td colspan="5" class="muted">ไม่มีข้อเสนอที่รออนุมัติ</td></tr>
        @endforelse
        </tbody></table></div>
        @if($proposals->hasPages())<nav class="pagination">{{ $proposals->links() }}</nav>@endif
    @endif
</div>

<div class="card">
    <h2 style="margin-top:0">รายงาน</h2>
    @if (! $hasReports)
        <p class="muted">ยังไม่มีตาราง reports ในฐานข้อมูล จึงยังเปิดใช้คิวอนุมัติส่วนนี้ไม่ได้</p>
    @else
        <div class="table-wrap"><table><thead><tr><th>หัวข้อ</th><th>ผู้ส่ง</th><th>ข้อเสนอที่เกี่ยวข้อง</th><th>ส่งเมื่อ</th><th>ดำเนินการ</th></tr></thead><tbody>
        @forelse($reports as $report)
            <tr><td><strong>{{ $report->title }}</strong></td><td>{{ $report->owner?->full_name_th ?: 'ไม่พบผู้ใช้' }}</td><td>{{ $report->proposal?->title ?: '-' }}</td><td>{{ $report->created_at?->format('d/m/Y H:i') }}</td><td><div class="actions"><form class="inline" method="POST" action="{{ route('admin.approvals.reports.update', $report) }}" onsubmit="return confirm('อนุมัติรายงานนี้?')">@csrf @method('PUT')<input type="hidden" name="status" value="approved"><button class="btn" style="background:#1f8a4d;color:#fff">อนุมัติ</button></form><form class="inline" method="POST" action="{{ route('admin.approvals.reports.update', $report) }}" onsubmit="return confirm('ส่งกลับให้แก้ไขรายงานนี้?')">@csrf @method('PUT')<input type="hidden" name="status" value="revision_requested"><button class="btn" style="background:#c46e00;color:#fff">ให้แก้ไข</button></form></div></td></tr>
        @empty
            <tr><td colspan="5" class="muted">ไม่มีรายงานที่รออนุมัติ</td></tr>
        @endforelse
        </tbody></table></div>
        @if($reports->hasPages())<nav class="pagination">{{ $reports->links() }}</nav>@endif
    @endif
</div>
@endsection
