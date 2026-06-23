<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ResearchLevel extends Model
{
    protected $table = 'research_levels';
    protected $primaryKey = 'research_level_id';
    public $timestamps = false;
    protected $fillable = ['name', 'date_add'];
}