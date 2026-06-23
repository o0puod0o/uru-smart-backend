<?php

namespace App\Http\Controllers\Api;

use App\Models\HasLecturer;

class LecturerController extends BaseResourceController
{
    protected string $modelClass = HasLecturer::class;

    protected array $rules = [
        'name' => 'required|string',
        'year' => 'required|string|size:4',
    ];
}