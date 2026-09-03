<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HasExpert;
use App\Support\CanonicalExpertiseGroups;
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
            ])
            ->map(fn ($item): array => $this->expertiseResource($item))
            ->values();

        return response()->json($items);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'expert_id' => 'nullable|integer',
            'group_id' => 'nullable|integer',
            'name' => 'nullable|string|max:500',
        ]);

        $expertId = $this->resolveExpertId($validated);

        $existingId = $this->existingCanonicalPivotId($request->user()->id, $expertId);

        $pivotId = $existingId ?: DB::table('users_expert')->insertGetId([
                'user_id' => $request->user()->id,
                'expert_id' => $expertId,
            ]);

        return response()->json([
            'message' => 'Expertise saved successfully',
            'data' => $this->findUserExpertise($request->user()->id, (int) $pivotId),
        ], 201);
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

        return response()->json($this->expertiseResource($item));
    }

    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'expert_id' => 'nullable|integer',
            'group_id' => 'nullable|integer',
            'name' => 'nullable|string|max:500',
        ]);

        $expertId = $this->resolveExpertId($validated);

        $updated = DB::table('users_expert')
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->update(['expert_id' => $expertId]);

        abort_if($updated === 0, 404);

        return response()->json([
            'message' => 'Expertise updated successfully',
            'data' => $this->findUserExpertise($request->user()->id, $id),
        ]);
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
        if (! empty($validated['group_id'])) {
            return $this->canonicalExpertIdFromInput((int) $validated['group_id']);
        }

        if (! empty($validated['expert_id'])) {
            return $this->canonicalExpertIdFromInput((int) $validated['expert_id']);
        }

        abort_if(empty($validated['name']), 422, 'The name field is required when expert_id is not provided.');

        $canonicalId = CanonicalExpertiseGroups::canonicalIdFromName($validated['name']);
        abort_if($canonicalId === null, 422, 'The selected expertise group is invalid.');

        $canonical = CanonicalExpertiseGroups::canonicalGroupFor($canonicalId);
        abort_if($canonical === null, 422, 'The selected expertise group is invalid.');

        return $this->ensureCanonicalExpertGroup($canonical);
    }

    private function canonicalExpertIdFromInput(int $expertId): int
    {
        $expert = HasExpert::query()
            ->where('expert_id', $expertId)
            ->first(['expert_id', 'name']);

        $canonical = CanonicalExpertiseGroups::canonicalGroupFor(
            $expert ? (int) $expert->expert_id : $expertId,
            $expert ? $expert->name : null
        );

        abort_if($canonical === null, 422, 'The selected expertise group is invalid.');

        return $this->ensureCanonicalExpertGroup($canonical);
    }

    private function ensureCanonicalExpertGroup(array $canonical): int
    {
        $existing = HasExpert::query()
            ->where('expert_id', $canonical['id'])
            ->first(['expert_id', 'name']);

        if (! $existing) {
            DB::table('has_experts')->insert([
                'expert_id' => $canonical['id'],
                'name' => $canonical['name'],
                'date_add' => now(),
            ]);

            return (int) $canonical['id'];
        }

        if ($existing->name !== $canonical['name']) {
            DB::table('has_experts')
                ->where('expert_id', $canonical['id'])
                ->update(['name' => $canonical['name']]);
        }

        return (int) $canonical['id'];
    }

    private function existingCanonicalPivotId(int $userId, int $expertId): ?int
    {
        $ids = CanonicalExpertiseGroups::idsMatching($expertId);
        $names = CanonicalExpertiseGroups::namesMatching($expertId);

        return DB::table('users_expert')
            ->join('has_experts', 'users_expert.expert_id', '=', 'has_experts.expert_id')
            ->where('users_expert.user_id', $userId)
            ->where(function ($query) use ($ids, $names): void {
                $query->whereIn('users_expert.expert_id', $ids);

                if ($names !== []) {
                    $query->orWhereIn('has_experts.name', $names);
                }
            })
            ->value('users_expert.id');
    }

    private function findUserExpertise(int $userId, int $id): array
    {
        $item = DB::table('users_expert')
            ->join('has_experts', 'users_expert.expert_id', '=', 'has_experts.expert_id')
            ->where('users_expert.user_id', $userId)
            ->where('users_expert.id', $id)
            ->first([
                'users_expert.id',
                'users_expert.expert_id',
                'has_experts.name',
            ]);

        abort_if(! $item, 404);

        return $this->expertiseResource($item);
    }

    private function expertiseResource(object $item): array
    {
        $canonical = CanonicalExpertiseGroups::canonicalGroupFor(
            (int) $item->expert_id,
            $item->name
        );

        $groupId = $canonical['id'] ?? (int) $item->expert_id;
        $name = $canonical['name'] ?? $item->name;

        return [
            'id' => (int) $item->id,
            'expert_id' => $groupId,
            'group_id' => $groupId,
            'name' => $name,
        ];
    }
}
