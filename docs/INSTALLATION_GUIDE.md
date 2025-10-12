# WhatsApp Bulk Messaging System - Installation Guide

## Overview
This system allows users to:
1. Connect their WhatsApp account
2. Import contact lists (CSV/Excel)
3. Send bulk messages to contacts

## Installation Steps

### 1. Install PHP Dependencies

```bash
composer require giggsey/libphonenumber-for-php openspout/openspout
```

### 2. Run Migrations

```bash
php artisan migrate
```

This will create the following tables:
- `wa_sessions` - WhatsApp connection sessions
- `imports` - Contact import records
- `recipients` - Individual contacts
- `campaigns` - Bulk messaging campaigns
- `messages` - Individual messages sent
- `audit_logs` - Activity logs

### 3. Configure Environment

Add to your `.env` file:

```env
BRIDGE_BASE_URL=http://localhost:3000
BRIDGE_TOKEN=your-secret-token-here
```

### 4. Seed Demo Data (Optional)

```bash
php artisan db:seed --class=DemoSeeder
```

This creates:
- Demo user: `demo@example.com` / `password`
- Sample import with 50 contacts
- Sample campaign with messages

### 5. Schedule Task Runner

Add to your cron:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

This runs the session expiry task every 5 minutes.

### 6. Register Policies

Add to `App\Providers\AuthServiceProvider`:

```php
protected $policies = [
    WaSession::class => WaSessionPolicy::class,
    Import::class => ImportPolicy::class,
    Recipient::class => RecipientPolicy::class,
    Campaign::class => CampaignPolicy::class,
    Message::class => MessagePolicy::class,
    AuditLog::class => AuditLogPolicy::class,
];
```

## Node.js Bridge Server

The WhatsApp connection is handled by a separate Node.js server.

### Expected API Endpoints

The Node.js server should implement:

#### 1. Create Session / Generate QR
```
POST /api/sessions
Headers: X-BRIDGE-TOKEN: your-secret-token
Body: { "user_id": 1 }
Response: { "qr": "data:image/png;base64,...", "status": "pending" }
```

#### 2. Get Session Status
```
GET /api/sessions/{userId}/status
Headers: X-BRIDGE-TOKEN: your-secret-token
Response: {
  "status": "connected|pending|expired|disconnected",
  "meta": {
    "phone": "+1234567890",
    "name": "John Doe",
    "avatar": "https://..."
  }
}
```

#### 3. Refresh QR Code
```
POST /api/sessions/{userId}/refresh
Headers: X-BRIDGE-TOKEN: your-secret-token
Response: { "qr": "data:image/png;base64,...", "status": "pending" }
```

#### 4. Disconnect Session
```
DELETE /api/sessions/{userId}
Headers: X-BRIDGE-TOKEN: your-secret-token
Response: { "status": "disconnected" }
```

#### 5. Send Message
```
POST /api/messages/send
Headers: X-BRIDGE-TOKEN: your-secret-token
Body: {
  "user_id": 1,
  "phone": "+1234567890",
  "message": "Hello World"
}
Response: { "success": true, "message_id": "..." }
```

## File Structure

```
app/
├── Console/Commands/
│   └── ExpireIdleSessions.php
├── Http/Controllers/
│   ├── ImportController.php
│   └── WaSessionController.php
├── Models/
│   ├── AuditLog.php
│   ├── Campaign.php
│   ├── Import.php
│   ├── Message.php
│   ├── Recipient.php
│   ├── User.php
│   └── WaSession.php
├── Policies/
│   ├── AuditLogPolicy.php
│   ├── CampaignPolicy.php
│   ├── ImportPolicy.php
│   ├── MessagePolicy.php
│   ├── RecipientPolicy.php
│   └── WaSessionPolicy.php
└── Services/
    └── BridgeClient.php

database/
├── factories/
│   ├── AuditLogFactory.php
│   ├── CampaignFactory.php
│   ├── ImportFactory.php
│   ├── MessageFactory.php
│   ├── RecipientFactory.php
│   └── WaSessionFactory.php
├── migrations/
│   ├── YYYY_MM_DD_create_wa_sessions_table.php
│   ├── YYYY_MM_DD_create_imports_table.php
│   ├── YYYY_MM_DD_create_recipients_table.php
│   ├── YYYY_MM_DD_create_campaigns_table.php
│   ├── YYYY_MM_DD_create_messages_table.php
│   └── YYYY_MM_DD_create_audit_logs_table.php
└── seeders/
    └── DemoSeeder.php

resources/js/pages/
├── Contacts/Imports/
│   ├── Index.vue
│   └── Show.vue
└── WhatsApp/
    └── Connect.vue
```

## Usage

### 1. Connect WhatsApp
- Navigate to `/wa/connect`
- Click "Generate QR Code"
- Scan with WhatsApp mobile app
- Status updates automatically via polling

### 2. Import Contacts
- Navigate to `/contacts/imports`
- Download template (CSV or Excel)
- Fill in contact details (phone required)
- Upload file
- System validates and normalizes phone numbers

### 3. View Import Details
- Click on any import to see details
- Shows valid/invalid contacts
- Preview first 50 recipients

### 4. Create Campaign (Coming Soon)
- Select an import
- Compose message with variables
- Start sending bulk messages

## Contact File Format

Required column:
- `phone` - Phone number (will be normalized to E.164 format)

Optional columns:
- `first_name`
- `last_name`
- `email`
- Any additional columns (stored in `extra_json`)

Example:
```csv
phone,first_name,last_name,email,company,city
+1234567890,John,Doe,john@example.com,Acme Corp,New York
+9876543210,Jane,Smith,jane@example.com,Tech Ltd,London
```

## Security

- All resources are scoped to authenticated users
- Policies enforce user ownership
- Phone numbers are validated and normalized
- File uploads are limited to 10MB
- Rate limiting on API endpoints (via Laravel throttle)

## Troubleshooting

### QR Code not appearing
- Check `BRIDGE_BASE_URL` and `BRIDGE_TOKEN` in `.env`
- Verify Node.js bridge server is running
- Check Laravel logs: `storage/logs/laravel.log`

### Import failing
- Ensure CSV/Excel file has `phone` column
- Check file size (max 10MB)
- Verify phone numbers are in valid format

### Session expiring
- Sessions expire after 5 minutes if not scanned
- Click "Refresh QR Code" to generate new one
- Idle sessions are cleaned up automatically

## Next Steps

To implement the campaign/messaging system:
1. Create `CampaignController`
2. Add campaign creation form
3. Implement job queue for bulk sending
4. Add message status tracking
5. Display campaign progress/statistics
