<?php

namespace App\Http\Controllers\Api;

use App\Models\HasInterest;

class InterestController extends BaseResourceController
{
    protected string $modelClass = HasInterest::class;
    protected string $ownerColumn = 'id_card';

    protected array $rules = [
        'name' => 'required|string',
    ];
}
