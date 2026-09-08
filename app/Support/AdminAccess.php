<?php

namespace App\Support;

use App\Models\User;

class AdminAccess
{
    /**
     * The configured email allow-list remains a bootstrap/recovery path.
     * Afterwards an existing administrator may grant WebView access by assigning role=admin.
     */
    public static function allows(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $allowedEmails = config('admin.emails', []);

        if (config('sso.mock')) {
            $allowedEmails[] = config('sso.mock_email');
        }

        $email = strtolower(trim((string) $user->email));
        $allowedEmails = array_map('strtolower', array_filter($allowedEmails));

        return $email !== '' && in_array($email, $allowedEmails, true);
    }
}
