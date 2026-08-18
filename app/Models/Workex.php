<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Workex extends Model
{
    protected $connection = 'expert';

    protected $table = 'workex';

    protected $fillable = [
        'id_card', 'year_start', 'year_end',
        'position', 'workplace', 'dateAdd',
    ];

    public $timestamps = false;

    protected $casts = ['dateAdd' => 'datetime'];

}
