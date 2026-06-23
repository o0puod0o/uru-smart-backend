<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class SubDepartment extends Model
{
    protected $table = 'sub_departments';
    protected $primaryKey = 'sub_dep_id';
    public $timestamps = false;
    protected $fillable = ['dep_id', 'name', 'name_en'];

    public function department()
    {
        return $this->belongsTo(Department::class, 'dep_id', 'dep_id');
    }
}