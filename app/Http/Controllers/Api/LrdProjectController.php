<?php

namespace App\Http\Controllers\Api;

use App\Models\LrdProject;

class LrdProjectController extends LrdBaseResourceController
{
    protected string $modelClass = LrdProject::class;
    protected array $searchable = ['projectname', 'keyword', 'focus', 'output'];

    protected array $rules = [
        'year_id' => 'nullable|integer',
        'work_id' => 'nullable|integer',
        'projectname' => 'required|string|max:500',
        'keyword' => 'nullable|string|max:500',
        'budget' => 'nullable|integer',
        'area_id' => 'nullable|string|max:500',
        'type_id' => 'nullable|string|max:100',
        'focus' => 'nullable|string|max:500',
        'output' => 'nullable|string|max:500',
        'output_type' => 'nullable|string|max:100',
        'bcg' => 'nullable|string',
        'sdg' => 'nullable|string',
        'engage' => 'nullable|string|max:100',
        'organize' => 'nullable|string|max:500',
        'problem' => 'nullable|string',
    ];
}
