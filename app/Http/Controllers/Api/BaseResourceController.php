<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

abstract class BaseResourceController extends Controller
{
    // Defined by each resource controller.
    protected string $modelClass;
    protected array $rules = [];
    protected array $storeRules = [];
    protected array $updateRules = [];
    protected string $ownerColumn = 'user_id';

    // GET /api/xxx - list only records owned by the logged-in user.
    public function index(Request $request)
    {
        $perPage = min((int) $request->query('per_page', 20), 100);

        $data = $this->modelClass::where($this->ownerColumn, $this->ownerValue($request))
            ->orderBy('id', 'desc')
            ->paginate($perPage);

        return response()->json($data);
    }

    // POST /api/xxx - create a new owned record.
    public function store(Request $request)
    {
        $rules = !empty($this->storeRules) ? $this->storeRules : $this->rules;
        $validated = $request->validate($rules);

        $validated[$this->ownerColumn] = $this->ownerValue($request);
        $validated['dateAdd'] = now();

        $item = $this->modelClass::create($validated);

        return response()->json($item, 201);
    }

    // GET /api/xxx/{id} - show one owned record.
    public function show(Request $request, $id)
    {
        $item = $this->modelClass::where('id', $id)
            ->where($this->ownerColumn, $this->ownerValue($request))
            ->firstOrFail();

        return response()->json($item);
    }

    // PUT /api/xxx/{id} - update one owned record.
    public function update(Request $request, $id)
    {
        $item = $this->modelClass::where('id', $id)
            ->where($this->ownerColumn, $this->ownerValue($request))
            ->firstOrFail();

        $rules = !empty($this->updateRules) ? $this->updateRules : $this->rules;
        $validated = $request->validate($rules);

        $item->update($validated);

        return response()->json($item);
    }

    // DELETE /api/xxx/{id} - delete one owned record.
    public function destroy(Request $request, $id)
    {
        $item = $this->modelClass::where('id', $id)
            ->where($this->ownerColumn, $this->ownerValue($request))
            ->firstOrFail();

        $item->delete();

        return response()->json(['message' => 'ลบสำเร็จ']);
    }

    protected function ownerValue(Request $request): mixed
    {
        return $this->ownerColumn === 'id_card'
            ? $request->user()->citizen_id
            : $request->user()->id;
    }
}
