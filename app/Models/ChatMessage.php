<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    protected $fillable = [
        'conversation_id',
        'user_id',
        'role',
        'content',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function conversation()
    {
        return $this->belongsTo(ChatConversation::class, 'conversation_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
