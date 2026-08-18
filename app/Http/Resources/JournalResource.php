<?php
namespace App\Http\Resources;
use Illuminate\Http\Resources\Json\JsonResource;
class JournalResource extends JsonResource {
    public function toArray($request){$a=parent::toArray($request); if(!$request->user()->isAdmin()&&$this->user_id!==$request->user()->id)$a['budget']=null; return $a;}
}
