<?php

namespace App\Providers;

use App\Models\{Proposal,Report,HasJournal,EntityFile};
use App\Policies\{ProposalPolicy,ReportPolicy,JournalPolicy,EntityFilePolicy};

// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Proposal::class => ProposalPolicy::class,
        Report::class => ReportPolicy::class,
        HasJournal::class => JournalPolicy::class,
        EntityFile::class => EntityFilePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}
