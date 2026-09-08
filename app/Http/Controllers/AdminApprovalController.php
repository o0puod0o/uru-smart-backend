<?php

namespace App\Http\Controllers;

use App\Models\Proposal;
use App\Models\Report;
use App\Services\AdminAuditService;
use App\Services\NotificationDeliveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminApprovalController extends Controller
{
    public function __construct(
        private readonly AdminAuditService $audit,
        private readonly NotificationDeliveryService $notifications,
    ) {
    }

    public function index(): View
    {
        $hasProposals = Schema::hasTable('proposals');
        $hasReports = Schema::hasTable('reports');

        $proposals = $hasProposals
            ? Proposal::query()
                ->with('owner')
                ->where('status', 'submitted')
                ->latest()
                ->paginate(20, ['*'], 'proposal_page')
            : collect();
        $reports = $hasReports
            ? Report::query()
                ->with(['owner', 'proposal'])
                ->where('status', 'submitted')
                ->latest()
                ->paginate(20, ['*'], 'report_page')
            : collect();

        return view('admin.approvals.index', compact('hasProposals', 'hasReports', 'proposals', 'reports'));
    }

    public function updateProposal(Request $request, Proposal $proposal): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected'])],
        ]);

        if ($proposal->status !== 'submitted') {
            return back()->with('error', 'รายการนี้ไม่ได้อยู่ระหว่างรออนุมัติแล้ว');
        }

        $before = $proposal->toArray();
        $proposal->update(['status' => $data['status']]);
        $this->audit->recordModel($request, 'proposal_'.$data['status'], $proposal, $before);

        if ($proposal->owner) {
            $this->notifications->deliverToUser(
                $proposal->owner,
                'สถานะข้อเสนอเปลี่ยนแล้ว',
                $proposal->title,
                'proposal_status',
                ['entity_type' => 'proposal', 'entity_id' => $proposal->id, 'route' => '/proposals/'.$proposal->id],
            );
        }

        return back()->with('success', 'บันทึกผลการพิจารณาข้อเสนอแล้ว');
    }

    public function updateReport(Request $request, Report $report): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['approved', 'revision_requested'])],
        ]);

        if ($report->status !== 'submitted') {
            return back()->with('error', 'รายการนี้ไม่ได้อยู่ระหว่างรออนุมัติแล้ว');
        }

        $before = $report->toArray();
        $report->update(['status' => $data['status']]);
        $this->audit->recordModel($request, 'report_'.$data['status'], $report, $before);

        if ($report->owner) {
            $this->notifications->deliverToUser(
                $report->owner,
                'สถานะรายงานเปลี่ยนแล้ว',
                $report->title,
                'report_status',
                ['entity_type' => 'report', 'entity_id' => $report->id, 'route' => '/reports/'.$report->id],
            );
        }

        return back()->with('success', 'บันทึกผลการพิจารณารายงานแล้ว');
    }
}
