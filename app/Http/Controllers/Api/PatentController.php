<?php

namespace App\Http\Controllers\Api;

use App\Models\HasPatent;

class PatentController extends BaseResourceController
{
    protected string $modelClass = HasPatent::class;

    protected array $rules = [
        'name' => 'required|string',
        'year' => 'required|string|size:4',
        'link' => 'nullable|string',
    ];
}