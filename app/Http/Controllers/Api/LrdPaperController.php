<?php

namespace App\Http\Controllers\Api;

use App\Models\LrdPaper;

class LrdPaperController extends LrdBaseResourceController
{
    protected string $modelClass = LrdPaper::class;
    protected array $searchable = ['title_th', 'title_eng', 'keyword', 'source'];

    protected array $rules = [
        'paperindex_id' => 'nullable|string|max:50',
        'fund_id' => 'nullable|integer',
        'title_th' => 'required|string|max:500',
        'title_eng' => 'nullable|string|max:500',
        'abstract' => 'nullable|string',
        'keyword' => 'nullable|string|max:500',
        'contributor' => 'nullable|string|max:500',
        'source' => 'nullable|string|max:500',
        'publicyear' => 'nullable|integer',
        'file' => 'nullable|string|max:255',
        'url' => 'nullable|url|max:500',
        'reference' => 'nullable|string',
        'public' => 'nullable|integer',
        'status' => 'nullable|integer',
    ];
}
