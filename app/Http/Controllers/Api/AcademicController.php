<?php

namespace App\Http\Controllers\Api;

use App\Models\HasAcademic;

class AcademicController extends BaseResourceController
{
    protected string $modelClass = HasAcademic::class;

    protected array $rules = [
        'name'    => 'required|string',
        'year'    => 'required|string|size:4',
        'picture' => 'nullable|string',
        'link'    => 'nullable|string',
    ];
}