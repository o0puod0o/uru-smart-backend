<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Education extends Model
{
    protected $connection = 'expert';

    protected $table = 'education';

    protected $fillable = [
        'id_card', 'degree', 'course', 'university', 'year', 'dateAdd',
    ];

    public $timestamps = false;

    protected $casts = ['dateAdd' => 'datetime'];

}
