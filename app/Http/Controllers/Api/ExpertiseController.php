<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HasExpert;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExpertiseController extends Controller
{
    public function index(Request $request)
    {
        $items = DB::table('users_expert')
            ->join('has_experts', 'users_expert.expert_id', '=', 'has_experts.expert_id')
            ->where('users_expert.user_id', $request->user()->id)
            ->orderBy('has_experts.name')
            ->get([
                'users_expert.id',
                'users_expert.expert_id',
                'has_experts.name',
            ]);

        return response()->json($items);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'expert_id' => 'nullable|integer|exists:has_experts,expert_id',
            'name' => 'nullable|string|max:500',
        ]);

        $expertId = $this->resolveExpertId($validated);

        $exists = DB::table('users_expert')
            ->where('user_id', $request->user()->id)
            ->where('expert_id', $expertId)
            ->exists();

        if (! $exists) {
            DB::table('users_expert')->insert([
                'user_id' => $request->user()->id,
                'expert_id' => $expertId,
            ]);
        }

        return response()->json(['message' => 'Expertise saved successfully'], 201);
    }

    public function show(Request $request, int $id)
    {
        $item = DB::table('users_expert')
            ->join('has_experts', 'users_expert.expert_id', '=', 'has_experts.expert_id')
            ->where('users_expert.user_id', $request->user()->id)
            ->where('users_expert.id', $id)
            ->first([
                'users_expert.id',
                'users_expert.expert_id',
                'has_experts.name',
            ]);

        abort_if(! $item, 404);

        return response()->json($item);
    }

    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'expert_id' => 'nullable|integer|exists:has_experts,expert_id',
            'name' => 'nullable|string|max:500',
        ]);

        $expertId = $this->resolveExpertId($validated);

        $updated = DB::table('users_expert')
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->update(['expert_id' => $expertId]);

        abort_if($updated === 0, 404);

        return response()->json(['message' => 'Expertise updated successfully']);
    }

    public function destroy(Request $request, int $id)
    {
        $deleted = DB::table('users_expert')
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->delete();

        abort_if($deleted === 0, 404);

        return response()->json(['message' => 'Expertise deleted successfully']);
    }

    private function resolveExpertId(array $validated): int
    {
        if (! empty($validated['expert_id'])) {
            return $validated['expert_id'];
        }

        abort_if(empty($validated['name']), 422, 'The name field is required when expert_id is not provided.');

        $expert = HasExpert::firstOrCreate(
            ['name' => $validated['name']],
            ['date_add' => now()]
        );

        return $expert->expert_id;
    }
}
