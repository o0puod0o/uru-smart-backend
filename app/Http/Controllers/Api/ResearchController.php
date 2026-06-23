<?php

namespace App\Http\Controllers\Api;

use App\Models\HasResearch;
use Illuminate\Http\Request;

class ResearchController extends BaseResourceController
{
    protected string $modelClass = HasResearch::class;

    protected array $rules = [
        'name'                 => 'required|string',
        'year'                 => 'required|string|size:4',
        'research_type_id'     => 'required|integer',
        'research_PMU_type_id' => 'nullable|integer',
        'research_level_id'    => 'nullable|integer',
    ];

    public function store(Request $request)
    {
        $request->merge($this->normalizeNullableInts($request->all()));
        return parent::store($request);
    }

    public function update(Request $request, $id)
    {
        $request->merge($this->normalizeNullableInts($request->all()));
        return parent::update($request, $id);
    }

    private function normalizeNullableInts(array $data): array
    {
        $data['research_PMU_type_id'] = $data['research_PMU_type_id'] ?? 0;
        $data['research_level_id']    = $data['research_level_id'] ?? 0;
        return $data;
    }
}