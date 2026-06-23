<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class JournalType extends Model
{
    protected $table = 'journal_types';
    protected $primaryKey = 'journal_type_id';
    public $timestamps = false;
    protected $fillable = ['name', 'orders', 'date_add'];
}