<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HasExpert extends Model
{
    protected $table = 'has_experts';
    protected $primaryKey = 'expert_id';

    protected $fillable = ['name', 'date_add'];

    public $timestamps = false;

    public function users()
    {
        return $this->belongsToMany(
            User::class,    // Model ที่เชื่อม
            'users_expert', // pivot table
            'expert_id',    // FK ของ HasExpert ใน pivot
            'user_id'       // FK ของ User ใน pivot
        );
    }
}