<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HasJournal extends Model
{
    protected $connection = 'expert';

    protected $table = 'has_journal';

    protected $fillable = [
        'id_card', 'name', 'year', 'journal_type_id', 'dateAdd', 'url',
    ];

    public $timestamps = false;

    protected $casts = [
        'dateAdd' => 'datetime',
    ];

    public function journalType()
    {
        return $this->belongsTo(JournalType::class, 'journal_type_id', 'journal_type_id');
    }
}
