<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HasBook extends Model
{
    protected $table = 'has_books';

    protected $fillable = [
        'user_id', 'name', 'year', 'dateAdd', 'img',
    ];

    public $timestamps = false;

    protected $casts = ['dateAdd' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}