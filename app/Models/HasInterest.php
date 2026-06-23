<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HasInterest extends Model
{
    protected $table = 'has_interests';

    protected $fillable = ['user_id', 'name', 'dateAdd'];

    public $timestamps = false;

    protected $casts = ['dateAdd' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}