<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class ProfileSearchController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'search_from' => 'nullable|string|in:first_name,last_name,expertise_group,interest,research,proceeding,journal',
            'expertise_group' => 'nullable|string|max:500',
            'expertise_group_id' => 'nullable|integer',
            'group_id' => 'nullable|integer',
            'expertise' => 'nullable|string|max:500',
            'interest' => 'nullable|string|max:500',
            'keyword' => 'nullable|string|max:255',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $limit = $validated['limit'] ?? 20;
        $searchFrom = $validated['search_from'] ?? null;
        $expertiseGroup = $validated['expertise_group'] ?? null;
        $expertiseGroupId = $validated['expertise_group_id'] ?? $validated['group_id'] ?? null;
        $expertise = $validated['expertise'] ?? null;
        $interest = $validated['interest'] ?? null;
        $keyword = $validated['keyword'] ?? null;

        // Eager load expertises และ interests เพื่อแสดงใน card
        $users = User::query()
            ->with(['experts:expert_id,name', 'interests:id,name'])
            ->when($keyword, function ($query) use ($keyword, $searchFrom): void {
                $this->applyKeywordSearch($query, $searchFrom, $keyword);
            })
            ->when($expertiseGroupId, function ($query) use ($expertiseGroupId): void {
                $query->whereHas('experts', function ($expertQuery) use ($expertiseGroupId): void {
                    $expertQuery->where('has_experts.expert_id', $expertiseGroupId);
                });
            })
            ->when($expertise && $expertise !== 'all', function ($query) use ($expertise): void {
                $query->whereHas('experts', function ($expertQuery) use ($expertise): void {
                    $expertQuery->where('has_experts.name', $expertise);
                });
            })
            ->when($expertiseGroup && $expertiseGroup !== 'all', function ($query) use ($expertiseGroup): void {
                $query->whereHas('experts', function ($expertQuery) use ($expertiseGroup): void {
                    $expertQuery->where('has_experts.name', 'like', "%{$expertiseGroup}%");
                });
            })
            ->when($interest && $interest !== 'all', function ($query) use ($interest): void {
                $query->whereHas('interests', function ($interestQuery) use ($interest): void {
                    $interestQuery->where('name', 'like', "%{$interest}%");
                });
            })
            ->orderBy('first_name_th')
            ->limit($limit)
            ->get([
                'id',
                'prefix_th',
                'first_name_th',
                'last_name_th',
                'prefix_en',
                'first_name_en',
                'last_name_en',
                'email',
                'picture',
                'profile_picture',
                'position',
                'department_name_th',
                'department_name_en',
            ])
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'full_name_th' => $user->full_name_th,
                'full_name_en' => $user->full_name_en,
                'email' => $user->email,
                'picture' => $user->profile_picture ?: $user->picture,
                'position' => $user->position,
                'department_name_th' => $user->department_name_th,
                'department_name_en' => $user->department_name_en,
                'expertises' => $user->experts
                    ->map(fn ($expert): array => [
                        'id' => $expert->expert_id,
                        'group_id' => $expert->expert_id,
                        'name' => $expert->name,
                    ])
                    ->values(),
                'interests' => $user->interests->pluck('name')->values(),
            ]);

        return response()->json($users);
    }

    private function applyKeywordSearch($query, ?string $searchFrom, string $keyword): void
    {
        if ($searchFrom === 'first_name') {
            $query->where(function ($inner) use ($keyword): void {
                $inner->where('first_name_th', 'like', "%{$keyword}%")
                    ->orWhere('first_name_en', 'like', "%{$keyword}%");
            });
        } elseif ($searchFrom === 'last_name') {
            $query->where(function ($inner) use ($keyword): void {
                $inner->where('last_name_th', 'like', "%{$keyword}%")
                    ->orWhere('last_name_en', 'like', "%{$keyword}%");
            });
        } elseif ($searchFrom === 'expertise_group') {
            $query->whereHas('experts', function ($expertQuery) use ($keyword): void {
                $expertQuery->where('has_experts.name', 'like', "%{$keyword}%");
            });
        } elseif ($searchFrom === 'interest') {
            $query->whereHas('interests', function ($interestQuery) use ($keyword): void {
                $interestQuery->where('name', 'like', "%{$keyword}%");
            });
        } elseif ($searchFrom === 'research') {
            $query->whereHas('researches', function ($researchQuery) use ($keyword): void {
                $researchQuery->where('name', 'like', "%{$keyword}%");
            });
        } elseif ($searchFrom === 'proceeding') {
            $query->whereHas('proceedings', function ($proceedingQuery) use ($keyword): void {
                $proceedingQuery->where('name', 'like', "%{$keyword}%");
            });
        } elseif ($searchFrom === 'journal') {
            $query->whereHas('journals', function ($journalQuery) use ($keyword): void {
                $journalQuery->where('name', 'like', "%{$keyword}%");
            });
        } else {
            $query->where(function ($inner) use ($keyword): void {
                $inner->where('first_name_th', 'like', "%{$keyword}%")
                    ->orWhere('last_name_th', 'like', "%{$keyword}%")
                    ->orWhere('first_name_en', 'like', "%{$keyword}%")
                    ->orWhere('last_name_en', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%");
            });
        }
    }
}
