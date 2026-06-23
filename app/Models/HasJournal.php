<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HasJournal extends Model
{
    protected $table = 'has_journals';

    protected $fillable = [
        'user_id', 'name', 'year', 'journal_type_id', 'dateAdd', 'url',
    ];

    public $timestamps = false;

    protected $casts = [
        'dateAdd' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function journalType()
    {
        return $this->belongsTo(JournalType::class, 'journal_type_id', 'journal_type_id');
    }
}