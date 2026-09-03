<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatbotHistory extends Model
{
    protected $fillable = [
        'user_id',
        'message',
        'reply',
        'provider',
        'model',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
