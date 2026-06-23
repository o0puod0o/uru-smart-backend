<?php

namespace App\Http\Controllers\Api;

use App\Models\HasBook;

class BookController extends BaseResourceController
{
    protected string $modelClass = HasBook::class;

    protected array $rules = [
        'name' => 'required|string|max:500',
        'year' => 'required|string|size:4',
        'img'  => 'nullable|string',
    ];
}