<?php

namespace App\Http\Controllers\Api;

use App\Models\HasInterest;
use App\Services\ProfileInterestCacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InterestController extends BaseResourceController
{
    protected string $modelClass = HasInterest::class;
    protected string $ownerColumn = 'id_card';

    protected array $rules = [
        'name' => 'required|string',
    ];

    public function store(Request $request)
    {
        $response = parent::store($request);
        $this->syncInterestCache($request);

        return $response;
    }

    public function update(Request $request, $id)
    {
        $response = parent::update($request, $id);
        $this->syncInterestCache($request);

        return $response;
    }

    public function destroy(Request $request, $id)
    {
        $response = parent::destroy($request, $id);
        $this->syncInterestCache($request);

        return $response;
    }

    private function syncInterestCache(Request $request): void
    {
        try {
            app(ProfileInterestCacheService::class)->syncUser($request->user());
        } catch (\Throwable $e) {
            Log::warning('Interest cache sync after resource change failed', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
