<?php

namespace App\Http\Controllers\Api;

use App\Models\HasTraining;

class TrainingController extends BaseResourceController
{
    protected string $modelClass = HasTraining::class;

    protected array $rules = [
        'name' => 'required|string',
        'year' => 'required|string|size:4',
    ];
}