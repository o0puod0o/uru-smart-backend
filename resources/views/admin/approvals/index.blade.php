@extends('admin.layout')

@section('title', 'คิวอนุมัติ')

@section('head')
<style>
    .approval-layout { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:18px; }.approval-section { margin:0; }.approval-list { display:grid; gap:12px; }.approval-item { padding:16px; border:1px solid var(--line); border-radius:14px; background:#fff; }.approval-item h3 { margin:0 0 5px; font-size:1rem; }.approval-summary { margin:10px 0; color:#52655c; font-size:.92rem; }.approval-meta { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:8px; margin:12px 0 14px; color:var(--muted); font-size:.82rem; }.approval-meta span { min-width:0; }.approval-meta b { display:block; overflow:hidden; color:#2b443a; font-size:.85rem; text-overflow:ellipsis; white-space:nowrap; }.empty-state { padding:25px 12px; color:var(--muted); text-align:center; }.approval-actions { display:flex; gap:8px; }.approval-actions form { flex:1; }.approval-actions .btn { width:100%; }.status-chip { display:inline-flex; align-items:center; min-height:25px; padding:3px 9px; border-radius:999px; color:#855000; background:#fff1d2; font-size:.75rem; font-weight:800; }
    @media(max-width:980px){.approval-layout{grid-template-columns:1fr}}@media(max-width:420px){.approval-actions{flex-direction:column}}
</style>
@endsection

@section('content')
<div class="header-row">
    <div><div class="eyebrow">รายการที่ต้องดำเนินการ</div><h1>คิวอนุมัติ</h1><div class="muted">พิจารณาเฉพาะรายการที่ผู้ใช้ส่งมาแล้ว</div></div>
    <a class="btn" href="{{ route('admin.dashboard') }}">กลับภาพรวม</a>
</div>

<div class="approval-layout">
    <section class="card approval-section" aria-labelledby="proposal-heading">
        <div class="eyebrow">โครงการ</div><h2 id="proposal-heading">ข้อเสนอรออนุมัติ</h2>
        @if (! $hasProposals)
            <p class="empty-state">ยังไม่มีตาราง <code>proposals</code> ในฐานข้อมูล จึงยังเปิดคิวส่วนนี้ไม่ได้</p>
        @else
            <div class="approval-list">
                @forelse($proposals as $proposal)
                    <article class="approval-item">
                        <span class="status-chip">รออนุมัติ</span>
                        <h3>{{ $proposal->title }}</h3>
                        @if($proposal->summary)<p class="approval-summary">{{ IlluminateSupportStr::limit($proposal->summary, 130) }}</p>@endif
                        <div class="approval-meta"><span>ผู้ส่ง<b>{{ $proposal->owner?->full_name_th ?: 'ไม่พบผู้ใช้' }}</b></span><span>ปี<b>{{ $proposal->year ?: '-' }}</b></span><span>ส่งเมื่อ<b>{{ $proposal->created_at?->format('d/m/Y H:i') ?: '-' }}</b></span></div>
                        <div class="approval-actions">
                            <form method="POST" action="{{ route('admin.approvals.proposals.update', $proposal) }}" onsubmit="return confirm('ยืนยันการอนุมัติข้อเสนอนี้?')">@csrf @method('PUT')<input type="hidden" name="status" value="approved"><button class="btn btn-primary" type="submit">อนุมัติ</button></form>
                            <form method="POST" action="{{ route('admin.approvals.proposals.update', $proposal) }}" onsubmit="return confirm('ยืนยันการปฏิเสธข้อเสนอนี้?')">@csrf @method('PUT')<input type="hidden" name="status" value="rejected"><button class="btn btn-danger" type="submit">ปฏิเสธ</button></form>
                        </div>
                    </article>
                @empty
                    <p class="empty-state">ไม่มีข้อเสนอที่รออนุมัติ</p>
                @endforelse
            </div>
            @if($proposals->hasPages())<nav class="pagination" aria-label="หน้าข้อเสนอ">{{ $proposals->links() }}</nav>@endif
        @endif
    </section>

    <section class="card approval-section" aria-labelledby="report-heading">
        <div class="eyebrow">รายงาน</div><h2 id="report-heading">รายงานรออนุมัติ</h2>
        @if (! $hasReports)
            <p class="empty-state">ยังไม่มีตาราง <code>reports</code> ในฐานข้อมูล จึงยังเปิดคิวส่วนนี้ไม่ได้</p>
        @else
            <div class="approval-list">
                @forelse($reports as $report)
                    <article class="approval-item">
                        <span class="status-chip">รออนุมัติ</span>
                        <h3>{{ $report->title }}</h3>
                        <div class="approval-meta"><span>ผู้ส่ง<b>{{ $report->owner?->full_name_th ?: 'ไม่พบผู้ใช้' }}</b></span><span>โครงการ<b>{{ $report->proposal?->title ?: '-' }}</b></span><span>ส่งเมื่อ<b>{{ $report->created_at?->format('d/m/Y H:i') ?: '-' }}</b></span></div>
                        <div class="approval-actions">
                            <form method="POST" action="{{ route('admin.approvals.reports.update', $report) }}" onsubmit="return confirm('ยืนยันการอนุมัติรายงานนี้?')">@csrf @method('PUT')<input type="hidden" name="status" value="approved"><button class="btn btn-primary" type="submit">อนุมัติ</button></form>
                            <form method="POST" action="{{ route('admin.approvals.reports.update', $report) }}" onsubmit="return confirm('ส่งรายงานนี้กลับให้แก้ไข?')">@csrf @method('PUT')<input type="hidden" name="status" value="revision_requested"><button class="btn btn-warning" type="submit">ให้แก้ไข</button></form>
                        </div>
                    </article>
                @empty
                    <p class="empty-state">ไม่มีรายงานที่รออนุมัติ</p>
                @endforelse
            </div>
            @if($reports->hasPages())<nav class="pagination" aria-label="หน้ารายงาน">{{ $reports->links() }}</nav>@endif
        @endif
    </section>
</div>
@endsection
