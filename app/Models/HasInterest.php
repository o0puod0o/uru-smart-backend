<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HasInterest extends Model
{
    protected $connection = 'expert';

    protected $table = 'has_interest';

    protected $fillable = ['id_card', 'name', 'dateAdd'];

    public $timestamps = false;

    protected $casts = ['dateAdd' => 'datetime'];

}
