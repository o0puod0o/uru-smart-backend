<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Report extends Model {
    protected $fillable=['owner_user_id','proposal_id','title','content','status'];
    public function owner(){ return $this->belongsTo(User::class,'owner_user_id'); }
    public function proposal(){ return $this->belongsTo(Proposal::class); }
}
