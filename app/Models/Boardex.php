<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Boardex extends Model
{
    protected $table = 'boardexes';

    protected $fillable = [
        'user_id', 'year_start', 'year_end',
        'position', 'workplace', 'dateAdd',
    ];

    public $timestamps = false;

    protected $casts = ['dateAdd' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}