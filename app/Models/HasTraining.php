<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HasTraining extends Model
{
    protected $table = 'has_trainings';

    protected $fillable = ['user_id', 'name', 'year', 'dateAdd'];

    public $timestamps = false;

    protected $casts = ['dateAdd' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}