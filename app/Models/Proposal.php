<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Proposal extends Model {
    protected $fillable=['owner_user_id','title','summary','year','status','budget'];
    protected $casts=['budget'=>'decimal:2'];
    public function owner(){ return $this->belongsTo(User::class,'owner_user_id'); }
}
