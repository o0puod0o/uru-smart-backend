<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HasAcademic extends Model
{
    protected $table = 'has_academics';

    protected $fillable = [
        'user_id', 'name', 'year', 'picture', 'link', 'dateAdd',
    ];

    public $timestamps = false;

    protected $casts = ['dateAdd' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}