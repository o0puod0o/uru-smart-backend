<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HasProceeding extends Model
{
    protected $table = 'has_proceedings';

    protected $fillable = ['user_id', 'name', 'year', 'dateAdd', 'url'];

    public $timestamps = false;

    protected $casts = ['dateAdd' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}