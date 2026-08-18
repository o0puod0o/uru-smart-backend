<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HasResearch extends Model
{
    protected $connection = 'expert';

    protected $table = 'has_research';

    protected $fillable = [
        'id_card', 'name', 'year',
        'research_type_id', 'research_PMU_type_id',
        'research_level_id', 'dateAdd',
    ];

    public $timestamps = false;

    protected $casts = [
        'dateAdd' => 'datetime',
    ];

    public function researchType()
    {
        return $this->belongsTo(ResearchType::class, 'research_type_id', 'research_type_id');
    }

    public function researchPmuType()
    {
        return $this->belongsTo(ResearchPmuType::class, 'research_PMU_type_id', 'research_PMU_type_id');
    }

    public function researchLevel()
    {
        return $this->belongsTo(ResearchLevel::class, 'research_level_id', 'research_level_id');
    }
}
