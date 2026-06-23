<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HasPatent extends Model
{
    protected $table = 'has_patents';

    protected $fillable = [
        'user_id', 'name', 'year', 'link', 'dateAdd',
    ];

    public $timestamps = false;

    protected $casts = ['dateAdd' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}