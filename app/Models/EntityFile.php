<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class EntityFile extends Model {
    protected $fillable=['owner_user_id','entity_type','entity_id','original_name','stored_name','path','mime_type','size'];
    public function owner(){ return $this->belongsTo(User::class,'owner_user_id'); }
}
