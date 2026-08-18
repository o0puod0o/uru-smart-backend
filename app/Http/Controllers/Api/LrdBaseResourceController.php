<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

abstract class LrdBaseResourceController extends Controller
{
    protected string $modelClass;
    protected array $rules = [];
    protected array $storeRules = [];
    protected array $updateRules = [];
    protected array $searchable = [];

    public function index(Request $request)
    {
        $perPage = min((int) $request->query('per_page', 20), 100);
        $validated = $request->validate([
            'scope' => 'sometimes|in:mine,all',
            'researcher_id' => 'sometimes|integer',
            'q' => 'nullable|string',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $query = $this->modelClass::query();

        if (! isset($validated['researcher_id']) && ($validated['scope'] ?? 'mine') === 'mine') {
            $query->where('researcher_id', $this->researcherId($request));
        }

        if (isset($validated['researcher_id'])) {
            $query->where('researcher_id', $validated['researcher_id']);
        }

        $this->applySearch($query, $validated['q'] ?? null);

        $data = $query->orderByDesc('id')->paginate($perPage);

        return response()->json($data);
    }

    public function store(Request $request)
    {
        $rules = ! empty($this->storeRules) ? $this->storeRules : $this->rules;
        $validated = $request->validate($rules);
        $validated['researcher_id'] = $this->researcherId($request);

        return response()->json($this->modelClass::create($validated), 201);
    }

    public function show(Request $request, $id)
    {
        return response()->json($this->modelClass::where('id', $id)->firstOrFail());
    }

    public function update(Request $request, $id)
    {
        $item = $this->ownedQuery($request, $id)->firstOrFail();
        $rules = ! empty($this->updateRules) ? $this->updateRules : $this->rules;
        $item->update($request->validate($rules));

        return response()->json($item);
    }

    public function destroy(Request $request, $id)
    {
        $item = $this->ownedQuery($request, $id)->firstOrFail();
        $item->delete();

        return response()->json(['message' => 'ลบสำเร็จ']);
    }

    protected function ownedQuery(Request $request, $id)
    {
        return $this->modelClass::where('id', $id)
            ->where('researcher_id', $this->researcherId($request));
    }

    protected function applySearch($query, ?string $keyword): void
    {
        if (! $keyword) {
            return;
        }

        if (empty($this->searchable)) {
            return;
        }

        $columns = $this->searchable;
        $query->where(function ($subQuery) use ($columns, $keyword) {
            foreach ($columns as $column) {
                $subQuery->orWhere($column, 'like', "%{$keyword}%");
            }
        });
    }

    protected function researcherId(Request $request): int
    {
        $user = $request->user();

        if ($user->lrd_researcher_id) {
            return (int) $user->lrd_researcher_id;
        }

        $researcher = DB::connection('lrd')
            ->table('researchers')
            ->where('idcard', $user->citizen_id)
            ->orWhere('codeuser', $user->citizen_id)
            ->orWhere('username', $user->username)
            ->first();

        abort_if(! $researcher, 422, 'LRD researcher mapping not found.');

        return (int) $researcher->id;
    }
}
