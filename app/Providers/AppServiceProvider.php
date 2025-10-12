<?php

namespace App\Providers;

use App\Models\AuditLog;
use App\Models\Campaign;
use App\Models\Import;
use App\Models\Message;
use App\Models\Recipient;
use App\Models\WaSession;
use App\Policies\AuditLogPolicy;
use App\Policies\CampaignPolicy;
use App\Policies\ImportPolicy;
use App\Policies\MessagePolicy;
use App\Policies\RecipientPolicy;
use App\Policies\WaSessionPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        WaSession::class => WaSessionPolicy::class,
        Import::class => ImportPolicy::class,
        Recipient::class => RecipientPolicy::class,
        Campaign::class => CampaignPolicy::class,
        Message::class => MessagePolicy::class,
        AuditLog::class => AuditLogPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}
