<?php

namespace App\Http\Controllers\Api;

use App\Models\Boardex;

class BoardexController extends BaseResourceController
{
    protected string $modelClass = Boardex::class;

    protected array $rules = [
        'year_start' => 'required|string|size:4',
        'year_end'   => 'nullable|string',
        'position'   => 'nullable|string|max:100',
        'workplace'  => 'nullable|string|max:100',
    ];
}