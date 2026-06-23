<?php

namespace App\Http\Controllers\Api;

use App\Models\HasAward;

class AwardController extends BaseResourceController
{
    protected string $modelClass = HasAward::class;

    protected array $rules = [
        'name' => 'required|string',
        'year' => 'required|string|size:4',
        'img'  => 'nullable|string',
    ];
}