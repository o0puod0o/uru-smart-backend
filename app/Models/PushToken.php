<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PushToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'push_token',
        'provider',
        'platform',
        'app_version',
        'app_ownership',
        'device_name',
        'device_model',
        'device_brand',
        'os_name',
        'os_version',
        'expo_project_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
