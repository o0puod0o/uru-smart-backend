<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LrdReferenceController extends Controller
{
    public function facultys(Request $request)
    {
        return response()->json([
            'data' => DB::connection('lrd')
                ->table('facultys')
                ->orderBy('id')
                ->get(),
        ]);
    }

    public function branchs(Request $request)
    {
        $query = DB::connection('lrd')->table('branchs');

        if ($request->filled('faculty_id')) {
            $query->where('faculty_id', $request->query('faculty_id'));
        }

        return response()->json([
            'data' => $query->orderBy('id')->get(),
        ]);
    }

    public function paperindexs()
    {
        return response()->json([
            'data' => DB::connection('lrd')
                ->table('paperindexs')
                ->orderBy('id')
                ->get(),
        ]);
    }
}
