<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller; use App\Http\Resources\ReportResource; use App\Models\Report; use App\Services\{AuditService,NotificationDeliveryService}; use App\Support\ApiPagination; use Illuminate\Http\Request;
class ReportController extends Controller {
 private AuditService $audit; private NotificationDeliveryService $notify;
 public function __construct(AuditService $audit,NotificationDeliveryService $notify){$this->audit=$audit;$this->notify=$notify;}
 private function rules(bool $u=false){return ['proposal_id'=>['nullable','exists:proposals,id'],'title'=>[$u?'sometimes':'required','string','max:255'],'content'=>['nullable','string'],'status'=>['sometimes','in:draft,submitted,revision_requested,approved']];}
 public function index(Request $r){$v=$r->validate(['scope'=>'sometimes|in:mine,all','q'=>'nullable|string','status'=>'nullable|string','per_page'=>'sometimes|integer|min:1|max:100']);$q=Report::query();if(($v['scope']??'mine')==='mine')$q->where('owner_user_id',$r->user()->id);$q->when($v['q']??null,fn($x,$s)=>$x->where('title','like',"%$s%"))->when($v['status']??null,fn($x,$s)=>$x->where('status',$s));$p=$q->latest()->paginate($v['per_page']??20);return ApiPagination::response($p,ReportResource::collection($p->getCollection()));}
 public function store(Request $r){$d=$r->validate($this->rules());$d['owner_user_id']=$r->user()->id;$d['status']=$d['status']??'draft';$m=Report::create($d);$this->audit->record($r,'create',$m);return (new ReportResource($m))->response()->setStatusCode(201);}
 public function show(Request $r,Report $report){$this->authorize('view',$report);return new ReportResource($report);}
 public function update(Request $r,Report $report){$this->authorize('update',$report);$before=$report->toArray();$d=$r->validate($this->rules(true));if(isset($d['status'])&&in_array($d['status'],['revision_requested','approved'])&&!$r->user()->isAdmin())abort(403);$old=$report->status;$report->update($d);$this->audit->record($r,'update',$report,$before);if($old!==$report->status)$this->notify->deliverToUser($report->owner,'สถานะรายงานเปลี่ยนแล้ว',$report->title,'report_status',['entity_type'=>'report','entity_id'=>$report->id,'route'=>'/reports/'.$report->id]);return new ReportResource($report);}
 public function destroy(Request $r,Report $report){$this->authorize('delete',$report);$b=$report->toArray();$report->delete();$this->audit->record($r,'delete',$report,$b);return response()->json(['message'=>'ลบสำเร็จ']);}
}
