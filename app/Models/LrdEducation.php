<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LrdEducation extends Model
{
    protected $connection = 'lrd';

    protected $table = 'educations';

    protected $fillable = [
        'researcher_id',
        'level',
        'year',
        'course',
        'branch',
        'institution',
        'status',
    ];
}
