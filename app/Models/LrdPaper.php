<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LrdPaper extends Model
{
    protected $connection = 'lrd';

    protected $table = 'papers';

    protected $fillable = [
        'researcher_id',
        'paperindex_id',
        'fund_id',
        'title_th',
        'title_eng',
        'abstract',
        'keyword',
        'contributor',
        'source',
        'publicyear',
        'file',
        'url',
        'reference',
        'public',
        'status',
    ];
}
