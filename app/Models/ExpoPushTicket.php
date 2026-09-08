<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpoPushTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'push_token_id',
        'ticket_id',
        'ticket_status',
        'ticket_details',
        'receipt_status',
        'receipt_details',
        'receipt_checked_at',
    ];

    protected $casts = [
        'ticket_details' => 'array',
        'receipt_details' => 'array',
        'receipt_checked_at' => 'datetime',
    ];

    public function pushToken()
    {
        return $this->belongsTo(PushToken::class);
    }
}
