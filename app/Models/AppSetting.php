<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $fillable = [
        'module',
        'key',
        'value',
        'description',
        'updated_by',
    ];

    protected $casts = [
        'value' => 'array',
    ];
}
