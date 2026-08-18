<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LrdResearch extends Model
{
    protected $connection = 'lrd';

    protected $table = 'researchs';

    protected $fillable = [
        'taggroup_id',
        'isced_id',
        'type',
        'fundtype_id',
        'fund_id',
        'researcher_id',
        'title_th',
        'title_eng',
        'propose',
        'keyword',
        'abstract',
        'contributor',
        'expert',
        'createyear',
        'budget',
        'linkapp',
        'picture',
        'public',
        'status',
        'w1',
        'w2',
        'proposal_id',
    ];
}
