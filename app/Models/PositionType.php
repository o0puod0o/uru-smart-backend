<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class PositionType extends Model
{
    protected $table = 'position_types';
    public $timestamps = false;
    protected $fillable = ['name', 'date_add'];

    public function positions()
    {
        return $this->hasMany(Position::class, 'position_type_id');
    }
}