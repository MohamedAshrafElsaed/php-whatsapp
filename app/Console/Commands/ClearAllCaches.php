<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ClearAllCaches extends Command
{
    protected $signature = 'cache:clear-all';
    protected $description = 'Clear all caches and restart services';

    public function handle(): int
    {
        $this->info('🧹 Clearing all caches...');

        // Clear all caches
        $this->call('optimize:clear');
        $this->call('config:clear');
        $this->call('cache:clear');
        $this->call('route:clear');
        $this->call('view:clear');
        $this->call('event:clear');

        $this->info('📦 Rebuilding optimizations...');

        // Rebuild caches
        $this->call('config:cache');
        $this->call('route:cache');
        $this->call('view:cache');

        // Clear OPcache if available
        if (function_exists('opcache_reset')) {
            opcache_reset();
            $this->info('✨ OPcache cleared');
        } else {
            $this->warn('⚠️  OPcache not available or already cleared by PHP-FPM restart');
        }

        $this->info('🔄 Restarting services...');

        // Restart PHP-FPM
        exec('sudo systemctl restart php8.4-fpm', $output, $return);
        if ($return === 0) {
            $this->info('✅ PHP-FPM restarted');
        } else {
            $this->error('❌ Failed to restart PHP-FPM (check sudo permissions)');
        }

        // Restart Nginx
        exec('sudo systemctl restart nginx', $output, $return);
        if ($return === 0) {
            $this->info('✅ Nginx restarted');
        } else {
            $this->error('❌ Failed to restart Nginx (check sudo permissions)');
        }

        $this->info('');
        $this->info('🎉 All caches cleared and services restarted successfully!');
        $this->info('💡 Don\'t forget to:');
        $this->info('   1. Purge Cloudflare cache');
        $this->info('   2. Hard refresh browser (Ctrl+Shift+R)');

        return Command::SUCCESS;
    }
}
