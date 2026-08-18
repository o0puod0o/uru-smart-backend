<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LrdProject extends Model
{
    protected $connection = 'lrd';

    protected $table = 'projects';

    protected $fillable = [
        'year_id',
        'work_id',
        'projectname',
        'keyword',
        'budget',
        'researcher_id',
        'area_id',
        'type_id',
        'focus',
        'output',
        'output_type',
        'bcg',
        'sdg',
        'engage',
        'organize',
        'problem',
    ];
}
