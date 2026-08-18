<?php

namespace App\Http\Controllers\Api;

use App\Models\LrdProposal;

class LrdProposalController extends LrdBaseResourceController
{
    protected string $modelClass = LrdProposal::class;
    protected array $searchable = ['title_th', 'title_eng', 'keyword', 'abstract'];

    protected array $rules = [
        'taggroup_id' => 'nullable|integer',
        'isced_id' => 'nullable|integer',
        'type' => 'nullable|string|max:50',
        'fundtype_id' => 'nullable|integer',
        'fund_id' => 'nullable|integer',
        'faculty_id' => 'nullable|integer',
        'title_th' => 'required|string',
        'title_eng' => 'nullable|string',
        'propose' => 'nullable|string',
        'keyword' => 'nullable|string',
        'abstract' => 'nullable|string',
        'contributor' => 'nullable|string',
        'expert' => 'nullable|string',
        'createyear' => 'nullable|string|size:4',
        'budget' => 'nullable|integer',
        'linkapp' => 'nullable|string|max:50',
        'picture' => 'nullable|string|max:50',
        'public' => 'nullable|integer',
        'status' => 'nullable|integer',
        'w1' => 'nullable|numeric',
        'w2' => 'nullable|numeric',
        'progress_id' => 'nullable|integer',
    ];
}
