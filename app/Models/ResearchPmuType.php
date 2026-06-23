<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ResearchPmuType extends Model
{
    protected $table = 'research_pmu_types';
    protected $primaryKey = 'research_PMU_type_id';
    public $timestamps = false;
    protected $fillable = ['name', 'date_add'];
}