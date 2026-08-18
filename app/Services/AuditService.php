<?php
namespace App\Services;
use App\Models\AuditLog; use Illuminate\Database\Eloquent\Model; use Illuminate\Http\Request;
class AuditService { public function record(Request $r,string $action,Model $m,?array $before=null):void { $actor=$r->user(); $fresh=$m->fresh(); AuditLog::create(['actor_user_id'=>$actor?$actor->id:null,'action'=>$action,'entity_type'=>$m->getMorphClass(),'entity_id'=>$m->getKey(),'before'=>$before,'after'=>$action==='delete'?null:($fresh?$fresh->toArray():null),'ip_address'=>$r->ip(),'created_at'=>now()]); } }
