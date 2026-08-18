<?php

namespace App\Http\Controllers\Api;

use App\Models\HasResearch;
use Illuminate\Http\Request;

class ResearchController extends BaseResourceController
{
    protected string $modelClass = HasResearch::class;
    protected string $ownerColumn = 'id_card';

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

        $rules = !empty($this->storeRules) ? $this->storeRules : $this->rules;
        $validated = $request->validate($rules);

        $validated['id_card'] = $request->user()->citizen_id;
        $validated['dateAdd'] = now();

        return response()->json(HasResearch::create($validated), 201);
    }

    public function update(Request $request, $id)
    {
        $request->merge($this->normalizeNullableInts($request->all()));
        return parent::update($request, $id);
    }

    public function destroy(Request $request, $id)
    {
        $query = HasResearch::where('id', $id)
            ->where('id_card', $request->user()->citizen_id);

        $item = $query->firstOrFail();
        $item->delete();

        return response()->json(['message' => 'ลบสำเร็จ']);
    }

    private function normalizeNullableInts(array $data): array
    {
        $data['research_PMU_type_id'] = $data['research_PMU_type_id'] ?? 0;
        $data['research_level_id']    = $data['research_level_id'] ?? 0;
        return $data;
    }
}
