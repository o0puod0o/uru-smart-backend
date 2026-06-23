<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $table = 'departments';
    protected $primaryKey = 'dep_id';
    public $timestamps = false;
    protected $fillable = ['dep_id', 'name', 'sort', 'name_en'];

    public function subDepartments()
    {
        return $this->hasMany(SubDepartment::class, 'dep_id', 'dep_id');
    }
}