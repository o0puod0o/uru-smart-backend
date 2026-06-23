<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ResearchType extends Model
{
    protected $table = 'research_types';
    protected $primaryKey = 'research_type_id';
    public $timestamps = false;
    protected $fillable = ['name', 'orders', 'date_add'];
}