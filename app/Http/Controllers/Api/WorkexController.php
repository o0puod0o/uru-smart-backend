<?php

namespace App\Http\Controllers\Api;

use App\Models\Workex;

class WorkexController extends BaseResourceController
{
    protected string $modelClass = Workex::class;
    protected string $ownerColumn = 'id_card';

    protected array $rules = [
        'year_start' => 'required|string|size:4',
        'year_end'   => 'nullable|string',
        'position'   => 'nullable|string|max:100',
        'workplace'  => 'nullable|string|max:100',
    ];
}
