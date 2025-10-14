<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\Import;
use App\Models\Message;
use App\Models\Recipient;
use App\Models\User;
use App\Models\WaSession;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // Create demo user
        $user = User::create([
            'name' => 'Demo User',
            'email' => 'demo@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        // Create WhatsApp session
        WaSession::factory()->connected()->create([
            'user_id' => $user->id,
        ]);

        // Create import with recipients
        $import = Import::factory()->ready()->create([
            'user_id' => $user->id,
            'filename' => 'demo_contacts.xlsx',
            'total_rows' => 50,
            'valid_rows' => 45,
            'invalid_rows' => 5,
        ]);

        // Create valid recipients
        Recipient::factory()->count(45)->create([
            'user_id' => $user->id,
            'import_id' => $import->id,
        ]);

        // Create invalid recipients
        Recipient::factory()->invalid()->count(5)->create([
            'user_id' => $user->id,
            'import_id' => $import->id,
        ]);

        // Create campaign
        $campaign = Campaign::factory()->running()->create([
            'user_id' => $user->id,
            'import_id' => $import->id,
            'name' => 'Welcome Campaign',
        ]);

        // Create messages for the campaign
        $recipients = $import->recipients()->where('is_valid', true)->take(10)->get();
        foreach ($recipients as $recipient) {
            Message::factory()->sent()->create([
                'user_id' => $user->id,
                'campaign_id' => $campaign->id,
                'recipient_id' => $recipient->id,
                'phone_e164' => $recipient->phone_e164,
            ]);
        }

        $this->command->info('Demo user created: demo@example.com / password');
    }
}
