<?php

namespace App\Http\Controllers\Api;

use App\Models\Education;

class EducationController extends BaseResourceController
{
    protected string $modelClass = Education::class;
    protected string $ownerColumn = 'id_card';

    protected array $rules = [
        'degree'     => 'required|integer',
        'course'     => 'required|string|max:200',
        'university' => 'required|string|max:500',
        'year'       => 'required|string|size:4',
    ];
}
