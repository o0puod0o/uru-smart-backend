<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProfileInterestCache;
use App\Models\User;
use App\Support\CanonicalExpertiseGroups;
use Illuminate\Http\Request;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class ProfileSearchController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'search_from' => ['nullable', 'string', Rule::in($this->allowedSearchModes())],
            'search_by' => ['nullable', 'string', Rule::in($this->allowedSearchModes())],
            'expertise_group' => 'nullable|string|max:500',
            'expertise_group_id' => 'nullable|integer',
            'group_id' => 'nullable|integer',
            'expertise' => 'nullable|string|max:500',
            'interest' => 'nullable|string|max:500',
            'interest_id' => 'nullable|string|max:500',
            'keyword' => 'nullable|string|max:255',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $limit = (int) ($validated['limit'] ?? 100);
        $perPage = (int) ($validated['per_page'] ?? $limit);
        $usesPagination = $request->has('page') || $request->has('per_page');
        $searchFrom = $this->normalizeSearchMode($validated['search_by'] ?? $validated['search_from'] ?? null);
        $expertiseGroup = $this->normalizeNullableString($validated['expertise_group'] ?? null);
        $expertiseGroupId = $validated['expertise_group_id'] ?? $validated['group_id'] ?? null;
        $expertise = $this->normalizeNullableString($validated['expertise'] ?? null);
        $keyword = $this->normalizeNullableString($validated['keyword'] ?? null);

        [$interestId, $interestTerm] = $this->interestFilterInput(
            $searchFrom,
            $keyword,
            $validated['interest'] ?? null,
            $validated['interest_id'] ?? null
        );

        $query = User::query()
            ->when($keyword && $searchFrom !== 'interest', function ($query) use ($keyword, $searchFrom): void {
                $this->applyKeywordSearch($query, $searchFrom, $keyword);
            })
            ->when($expertiseGroupId, function ($query) use ($expertiseGroupId): void {
                $query->whereHas('experts', function ($expertQuery) use ($expertiseGroupId): void {
                    $this->applyExpertiseGroupFilter($expertQuery, (int) $expertiseGroupId);
                });
            })
            ->when($expertise && $expertise !== 'all', function ($query) use ($expertise): void {
                $query->whereHas('experts', function ($expertQuery) use ($expertise): void {
                    $this->applyExpertiseNameFilter($expertQuery, $expertise, false);
                });
            })
            ->when($expertiseGroup && $expertiseGroup !== 'all', function ($query) use ($expertiseGroup): void {
                $query->whereHas('experts', function ($expertQuery) use ($expertiseGroup): void {
                    $this->applyExpertiseNameFilter($expertQuery, $expertiseGroup, true);
                });
            })
            ->orderBy('first_name_th');

        if ($interestId !== null || $interestTerm !== null) {
            $this->applyInterestFilter($query, $interestId, $interestTerm, $request->user());
        }

        if ($usesPagination) {
            $paginator = $query->paginate(
                $perPage,
                $this->profileSearchColumns(),
                'page',
                $validated['page'] ?? null
            );

            return response()->json([
                'data' => $this->profileSearchResources(collect($paginator->items()))->values(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                ],
            ]);
        }

        $users = $query
            ->limit($limit)
            ->get($this->profileSearchColumns());

        return response()->json($this->profileSearchResources($users)->values());
    }

    private function allowedSearchModes(): array
    {
        return [
            'first_name',
            'firstname',
            'firstName',
            'last_name',
            'lastname',
            'lastName',
            'expertise_group',
            'expertiseGroup',
            'expertise',
            'interest',
            'research',
            'proceeding',
            'journal',
        ];
    }

    private function normalizeSearchMode(?string $searchMode): ?string
    {
        if ($searchMode === null) {
            return null;
        }

        return match (strtolower(trim($searchMode))) {
            'firstname' => 'first_name',
            'lastname' => 'last_name',
            'expertisegroup', 'expertise' => 'expertise_group',
            default => $searchMode,
        };
    }

    private function profileSearchColumns(): array
    {
        return [
            'id',
            'citizen_id',
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
        ];
    }

    private function profileSearchResources(Collection $users): Collection
    {
        $expertiseMap = $this->expertisesForUsers($users);
        $interestMap = $this->cachedInterestsForUsers($users);

        return $users->map(function (User $user) use ($expertiseMap, $interestMap): array {
            return $this->profileSearchResource(
                $user,
                $expertiseMap->get((int) $user->id, collect()),
                $interestMap->get((int) $user->id, collect())
            );
        });
    }

    private function profileSearchResource(User $user, Collection $expertises, Collection $interests): array
    {
        $interestItems = $interests
            ->map(fn ($interest): array => $this->interestResource($interest))
            ->filter(fn (array $interest): bool => $interest['name'] !== null && $interest['name'] !== '')
            ->values();

        return [
            'id' => $user->id,
            'full_name_th' => $user->full_name_th,
            'full_name_en' => $user->full_name_en,
            'email' => $user->email,
            'picture' => $user->profile_picture ?: $user->picture,
            'position' => $user->position,
            'department_name_th' => $user->department_name_th,
            'department_name_en' => $user->department_name_en,
            'expertises' => $expertises
                ->map(fn ($expert): array => $this->expertiseResource($expert))
                ->unique(fn (array $expert): string => "{$expert['group_id']}|{$expert['name']}")
                ->values(),
            'interests' => $interestItems,
            'interest_names' => $interestItems->pluck('name')->values(),
        ];
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
                $this->applyExpertiseNameFilter($expertQuery, $keyword, true);
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

    private function applyInterestFilter($query, ?int $interestId, ?string $interestTerm, ?User $preferredUser = null): void
    {
        if ($this->ensureProfileInterestCacheAvailable()) {
            $this->warmProfileInterestCacheIfNeeded($preferredUser);
            $this->applyCachedInterestFilter($query, $interestId, $interestTerm);

            return;
        }

        $userIds = $this->directInterestUserIdsByKnownIdCards($interestId, $interestTerm);

        if ($userIds === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereIn('id', $userIds);
    }

    private function applyCachedInterestFilter($query, ?int $interestId, ?string $interestTerm): void
    {
        if (! $this->profileInterestCacheAvailable()) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereIn('id', function ($interestQuery) use ($interestId, $interestTerm): void {
            $interestQuery
                ->select('user_id')
                ->from('profile_interest_cache');

            if ($interestId !== null) {
                $interestQuery->where('source_interest_id', $interestId);

                return;
            }

            $normalized = ProfileInterestCache::normalizeName($interestTerm);

            $interestQuery->where(function ($nameQuery) use ($normalized): void {
                $nameQuery
                    ->where('interest_name_normalized', $normalized)
                    ->orWhere('interest_name_normalized', 'like', $this->escapeLike($normalized) . '%');
            });
        });
    }

    private function directInterestUserIdsByKnownIdCards(?int $interestId, ?string $interestTerm): array
    {
        $users = User::query()
            ->whereNotNull('citizen_id')
            ->where('citizen_id', '<>', '')
            ->limit(1000)
            ->get(['id', 'citizen_id']);

        $citizenIds = $users
            ->pluck('citizen_id')
            ->map(fn ($citizenId): string => trim((string) $citizenId))
            ->filter()
            ->unique()
            ->values();

        if ($citizenIds->isEmpty()) {
            return [];
        }

        $term = $this->normalizeNullableString($interestTerm);
        $normalizedTerm = $term === null ? null : ProfileInterestCache::normalizeName($term);
        $idCardsByUserId = $users
            ->mapWithKeys(fn (User $user): array => [trim((string) $user->citizen_id) => (int) $user->id]);

        try {
            $rows = DB::connection('expert')
                ->table('has_interest')
                ->select('id', 'id_card', 'name')
                ->whereIn('id_card', $citizenIds->all())
                ->limit(5000)
                ->get();
        } catch (\Throwable $e) {
            Log::warning('Profile search direct interest fallback by id_card failed', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }

        return $rows
            ->filter(function ($row) use ($interestId, $normalizedTerm): bool {
                if ($interestId !== null) {
                    return isset($row->id) && (int) $row->id === $interestId;
                }

                if ($normalizedTerm === null || $normalizedTerm === '') {
                    return false;
                }

                $name = ProfileInterestCache::normalizeName($row->name ?? null);

                return $name === $normalizedTerm || str_starts_with($name, $normalizedTerm);
            })
            ->map(fn ($row): ?int => $idCardsByUserId->get(trim((string) ($row->id_card ?? ''))))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function ensureProfileInterestCacheAvailable(): bool
    {
        if ($this->profileInterestCacheAvailable()) {
            return true;
        }

        try {
            Schema::create('profile_interest_cache', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('citizen_id', 64)->index();
                $table->unsignedBigInteger('source_interest_id')->nullable()->index();
                $table->string('interest_name', 500);
                $table->string('interest_name_normalized', 191)->index();
                $table->dateTime('source_updated_at')->nullable();
                $table->timestamp('synced_at')->nullable()->index();
                $table->timestamps();

                $table->unique(['user_id', 'source_interest_id'], 'pic_user_source_unique');
                $table->index(['user_id', 'interest_name_normalized'], 'pic_user_interest_name_idx');
            });
        } catch (\Throwable $e) {
            if (! Schema::hasTable('profile_interest_cache')) {
                Log::warning('Profile interest cache table create failed', [
                    'error' => $e->getMessage(),
                ]);

                return false;
            }
        }

        return true;
    }

    private function warmProfileInterestCacheIfNeeded(?User $preferredUser = null): void
    {
        try {
            $alreadyCachedUserIds = ProfileInterestCache::query()
                ->distinct()
                ->pluck('user_id')
                ->map(fn ($id): int => (int) $id)
                ->all();
        } catch (\Throwable $e) {
            Log::warning('Profile interest cache warm skipped', [
                'error' => $e->getMessage(),
            ]);

            return;
        }

        $startedAt = microtime(true);
        $users = User::query()
            ->whereNotNull('citizen_id')
            ->where('citizen_id', '<>', '')
            ->limit(1000)
            ->get(['id', 'citizen_id']);

        if ($preferredUser && $preferredUser->citizen_id) {
            $preferred = $users->first(fn (User $user): bool => (int) $user->id === (int) $preferredUser->id);

            if (! $preferred) {
                $preferred = $preferredUser;
            }

            $users = collect([$preferred])
                ->merge($users->reject(fn (User $user): bool => (int) $user->id === (int) $preferred->id))
                ->values();
        }

        foreach ($users as $user) {
            if (in_array((int) $user->id, $alreadyCachedUserIds, true)) {
                continue;
            }

            if ((microtime(true) - $startedAt) > 4.5) {
                break;
            }

            $this->syncProfileInterestCacheForUser($user);
        }
    }

    private function syncProfileInterestCacheForUser(User $user): int
    {
        $citizenId = trim((string) $user->citizen_id);

        if ($citizenId === '') {
            return 0;
        }

        try {
            $rows = DB::connection('expert')
                ->table('has_interest')
                ->select('id', 'id_card', 'name', 'dateAdd')
                ->where('id_card', $citizenId)
                ->orderBy('id')
                ->limit(500)
                ->get();
        } catch (\Throwable $e) {
            Log::warning('Profile interest cache user sync failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return 0;
        }

        $now = now();
        $payload = $rows
            ->map(function ($row) use ($user, $citizenId, $now): ?array {
                $name = trim((string) ($row->name ?? ''));

                if ($name === '') {
                    return null;
                }

                return [
                    'user_id' => (int) $user->id,
                    'citizen_id' => $citizenId,
                    'source_interest_id' => isset($row->id) && is_numeric($row->id) ? (int) $row->id : null,
                    'interest_name' => $name,
                    'interest_name_normalized' => ProfileInterestCache::normalizeName($name),
                    'source_updated_at' => $row->dateAdd ?? null,
                    'synced_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })
            ->filter()
            ->values();

        try {
            DB::transaction(function () use ($user, $payload): void {
                ProfileInterestCache::query()
                    ->where('user_id', $user->id)
                    ->delete();

                if ($payload->isNotEmpty()) {
                    ProfileInterestCache::query()->insert($payload->all());
                }
            });
        } catch (\Throwable $e) {
            Log::warning('Profile interest cache write failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return 0;
        }

        return $payload->count();
    }

    private function applyExpertiseGroupFilter($query, int $groupId): void
    {
        $ids = CanonicalExpertiseGroups::idsMatching($groupId);
        $names = CanonicalExpertiseGroups::namesMatching($groupId);

        $query->where(function ($inner) use ($ids, $names): void {
            $inner->whereIn('has_experts.expert_id', $ids);

            if ($names !== []) {
                $inner->orWhereIn('has_experts.name', $names);
            }
        });
    }

    private function applyExpertiseNameFilter($query, string $name, bool $allowLike): void
    {
        $canonicalId = CanonicalExpertiseGroups::canonicalIdFromName($name);

        if ($canonicalId !== null) {
            $this->applyExpertiseGroupFilter($query, $canonicalId);

            return;
        }

        if ($allowLike) {
            $query->where('has_experts.name', 'like', "%{$name}%");

            return;
        }

        $query->where('has_experts.name', $name);
    }

    private function expertisesForUsers(Collection $users): Collection
    {
        $userIds = $users
            ->pluck('id')
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        if ($userIds->isEmpty()) {
            return collect();
        }

        try {
            return DB::table('users_expert')
                ->join('has_experts', 'users_expert.expert_id', '=', 'has_experts.expert_id')
                ->whereIn('users_expert.user_id', $userIds->all())
                ->orderBy('has_experts.name')
                ->get([
                    'users_expert.user_id',
                    'users_expert.expert_id',
                    'has_experts.name',
                ])
                ->groupBy(fn ($expert): int => (int) $expert->user_id);
        } catch (\Throwable $e) {
            Log::warning('Profile search cached expertises skipped', [
                'error' => $e->getMessage(),
            ]);

            return collect();
        }
    }

    private function cachedInterestsForUsers(Collection $users): Collection
    {
        if (! $this->profileInterestCacheAvailable()) {
            return collect();
        }

        $userIds = $users
            ->pluck('id')
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        if ($userIds->isEmpty()) {
            return collect();
        }

        try {
            return ProfileInterestCache::query()
                ->whereIn('user_id', $userIds->all())
                ->orderBy('interest_name')
                ->get(['id', 'user_id', 'citizen_id', 'source_interest_id', 'interest_name'])
                ->groupBy(fn (ProfileInterestCache $interest): int => (int) $interest->user_id);
        } catch (\Throwable $e) {
            Log::warning('Profile search cached interests skipped', [
                'error' => $e->getMessage(),
            ]);

            return collect();
        }
    }

    private function expertiseResource($expert): array
    {
        $canonical = CanonicalExpertiseGroups::canonicalGroupFor(
            (int) $expert->expert_id,
            $expert->name
        );

        $groupId = $canonical['id'] ?? (int) $expert->expert_id;
        $name = $canonical['name'] ?? $expert->name;

        return [
            'id' => $groupId,
            'group_id' => $groupId,
            'name' => $name,
        ];
    }

    private function interestResource($interest): array
    {
        $id = $interest->source_interest_id ?? $interest->id ?? null;
        $name = $interest->interest_name ?? $interest->name ?? null;

        return [
            'id' => is_numeric($id) ? (int) $id : $id,
            'name' => $name,
        ];
    }

    private function interestFilterInput(?string $searchFrom, ?string $keyword, mixed $interest, mixed $interestId): array
    {
        if ($searchFrom === 'interest') {
            if (is_numeric($interestId)) {
                return [(int) $interestId, null];
            }

            return [
                null,
                $this->normalizeNullableString($keyword ?? $interestId ?? $interest),
            ];
        }

        $interest = $this->normalizeNullableString($interest);

        if ($interest !== null && $interest !== 'all') {
            return [null, $interest];
        }

        return [null, null];
    }

    private function profileInterestCacheAvailable(): bool
    {
        static $available = null;

        if ($available !== null) {
            return $available;
        }

        try {
            return $available = Schema::hasTable('profile_interest_cache');
        } catch (\Throwable $e) {
            Log::warning('Profile interest cache availability check failed', [
                'error' => $e->getMessage(),
            ]);

            return $available = false;
        }
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
