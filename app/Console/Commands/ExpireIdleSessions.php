<?php

namespace App\Console\Commands;

use App\Models\WaSession;
use Illuminate\Console\Command;

class ExpireIdleSessions extends Command
{
    protected $signature = 'wa:expire-sessions';
    protected $description = 'Expire idle WhatsApp sessions';

    public function handle(): int
    {
        $expiredCount = WaSession::where('status', 'pending')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);

        $this->info("Expired {$expiredCount} idle session(s)");

        return 0;
    }
}
