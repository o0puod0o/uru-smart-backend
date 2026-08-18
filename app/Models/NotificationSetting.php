<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationSetting extends Model
{
    use HasFactory;

    public const DEFAULTS = [
        'beforeClass' => true,
        'holiday' => true,
        'gradeDeadline' => true,
        'announcement' => true,
    ];

    protected $fillable = [
        'user_id',
        'settings',
        'platform',
        'app_version',
        'expo_project_id',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isEnabled(string $category): bool
    {
        if (! array_key_exists($category, self::DEFAULTS)) {
            return true;
        }

        return (bool) array_merge(self::DEFAULTS, $this->settings ?? [])[$category];
    }
}
