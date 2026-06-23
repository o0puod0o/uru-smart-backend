<?php

namespace App\Http\Controllers\Api;

use App\Models\HasProceeding;

class ProceedingController extends BaseResourceController
{
    protected string $modelClass = HasProceeding::class;

    protected array $rules = [
        'name' => 'required|string|max:500',
        'year' => 'required|string|size:4',
        'url'  => 'nullable|string',
    ];
}