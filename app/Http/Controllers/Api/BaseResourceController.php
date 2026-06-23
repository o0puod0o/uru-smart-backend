<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;

abstract class BaseResourceController extends Controller
{
    // กำหนดใน subclass
    protected string $modelClass;
    protected array $rules = [];
    protected array $storeRules = [];
    protected array $updateRules = [];

    // GET /api/xxx — ดึงเฉพาะของ user ที่ login
    public function index(Request $request)
    {
        $perPage = min((int) $request->query('per_page', 20), 100);

        $data = $this->modelClass::where('user_id', $request->user()->id)
            ->orderBy('id', 'desc')
            ->paginate($perPage);

        return response()->json($data);
    }

    // POST /api/xxx — เพิ่มข้อมูล
    public function store(Request $request)
    {
        $rules = !empty($this->storeRules) ? $this->storeRules : $this->rules;
        $validated = $request->validate($rules);

        $validated['user_id'] = $request->user()->id;
        $validated['dateAdd'] = now();

        $item = $this->modelClass::create($validated);

        return response()->json($item, 201);
    }

    // GET /api/xxx/{id} — ดูรายละเอียด
    public function show(Request $request, $id)
    {
        $item = $this->modelClass::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return response()->json($item);
    }

    // PUT /api/xxx/{id} — แก้ไข
    public function update(Request $request, $id)
    {
        $item = $this->modelClass::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $rules = !empty($this->updateRules) ? $this->updateRules : $this->rules;
        $validated = $request->validate($rules);

        $item->update($validated);

        return response()->json($item);
    }

    // DELETE /api/xxx/{id} — ลบ
    public function destroy(Request $request, $id)
    {
        $item = $this->modelClass::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $item->delete();

        return response()->json(['message' => 'ลบสำเร็จ']);
    }
}