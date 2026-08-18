<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller; use App\Models\User; use App\Services\AuditService; use App\Support\ApiPagination; use Illuminate\Http\Request; use Illuminate\Validation\Rule;
class AdminUserApiController extends Controller {
 private AuditService $audit;
 public function __construct(AuditService $audit){$this->audit=$audit;}
 public function index(Request $r){$v=$r->validate(['q'=>'nullable|string','role'=>'nullable|in:user,admin','per_page'=>'sometimes|integer|min:1|max:100']);$q=User::query()->when($v['q']??null,fn($x,$s)=>$x->where(fn($y)=>$y->where('email','like',"%$s%")->orWhere('first_name_th','like',"%$s%")->orWhere('last_name_th','like',"%$s%")))->when($v['role']??null,fn($x,$s)=>$x->where('role',$s));$p=$q->paginate($v['per_page']??20);return ApiPagination::response($p,$p->items());}
 public function show(User $user){return response()->json(['data'=>$user]);}
 public function update(Request $r,User $user){$b=$user->toArray();$d=$r->validate(['email'=>'sometimes|nullable|email','status'=>'sometimes|string','role'=>'sometimes|in:user,admin']);$user->update($d);$this->audit->record($r,'admin_update',$user,$b);return response()->json(['data'=>$user]);}
 public function role(Request $r,User $user){$b=$user->toArray();$user->update($r->validate(['role'=>'required|in:user,admin']));$this->audit->record($r,'role_change',$user,$b);return response()->json(['data'=>$user]);}
 public function destroy(Request $r,User $user){abort_if($r->user()->is($user),422,'Cannot delete current admin.');$b=$user->toArray();$user->delete();$this->audit->record($r,'delete',$user,$b);return response()->json(['message'=>'ลบสำเร็จ']);}
}
