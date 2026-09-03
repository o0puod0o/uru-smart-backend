<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfileInterestCache extends Model
{
    protected $table = 'profile_interest_cache';

    protected $fillable = [
        'user_id',
        'citizen_id',
        'source_interest_id',
        'interest_name',
        'interest_name_normalized',
        'source_updated_at',
        'synced_at',
    ];

    protected $casts = [
        'source_updated_at' => 'datetime',
        'synced_at' => 'datetime',
    ];

    public static function normalizeName(?string $name): string
    {
        $name = html_entity_decode(trim((string) $name), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return mb_strtolower($name, 'UTF-8');
    }
}
