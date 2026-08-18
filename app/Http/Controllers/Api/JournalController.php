<?php

namespace App\Http\Controllers\Api;

use App\Models\HasJournal;

class JournalController extends BaseResourceController
{
    protected string $modelClass = HasJournal::class;
    protected string $ownerColumn = 'id_card';

    protected array $rules = [
        'name' => 'required|string',
        'year' => 'required|string|size:4',
        'journal_type_id' => 'required|integer',
        'url' => 'nullable|url',
    ];
}
