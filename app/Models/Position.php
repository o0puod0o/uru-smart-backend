<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Position extends Model
{
    protected $fillable = ['name', 'position_type_id', 'date_add'];
    public $timestamps = false;

    public function positionType()
    {
        return $this->belongsTo(PositionType::class, 'position_type_id');
    }
}