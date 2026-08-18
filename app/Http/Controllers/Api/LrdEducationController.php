<?php

namespace App\Http\Controllers\Api;

use App\Models\LrdEducation;

class LrdEducationController extends LrdBaseResourceController
{
    protected string $modelClass = LrdEducation::class;
    protected array $searchable = ['level', 'course', 'branch', 'institution'];

    protected array $rules = [
        'level' => 'nullable|string|max:50',
        'year' => 'nullable|string|size:4',
        'course' => 'nullable|string|max:50',
        'branch' => 'nullable|string|max:50',
        'institution' => 'nullable|string|max:50',
        'status' => 'nullable|integer',
    ];
}
