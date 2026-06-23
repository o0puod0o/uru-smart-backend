<?php

namespace App\Http\Controllers\Api;

use App\Models\HasHsp;

class HspController extends BaseResourceController
{
    protected string $modelClass = HasHsp::class;

    protected array $rules = [
        'name' => 'required|string',
        'year' => 'required|string|size:4',
        'link' => 'nullable|string',
    ];
}