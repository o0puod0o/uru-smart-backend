<?php

namespace App\Http\Controllers;

use App\Models\Proposal;
use App\Models\PushToken;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(): View
    {
        $hasProposals = Schema::hasTable('proposals');
        $hasReports = Schema::hasTable('reports');
        $hasRoleColumn = Schema::hasColumn('users', 'role');
        $hasPushTokens = Schema::hasTable('push_tokens');

        $statistics = [
            'users' => User::query()->count(),
            'active_users' => User::query()->where('status', 'ACTIVE')->count(),
            'admins' => $hasRoleColumn ? User::query()->where('role', 'admin')->count() : 0,
            'push_enabled_users' => $hasPushTokens ? PushToken::query()
                ->where('provider', 'expo')
                ->where('is_active', true)
                ->distinct('user_id')
                ->count('user_id') : 0,
            'pending_proposals' => $hasProposals
                ? Proposal::query()->where('status', 'submitted')->count()
                : 0,
            'pending_reports' => $hasReports
                ? Report::query()->where('status', 'submitted')->count()
                : 0,
        ];

        return view('admin.dashboard', compact('statistics', 'hasProposals', 'hasReports', 'hasRoleColumn', 'hasPushTokens'));
    }
}
