# Quick Start Checklist ✅

Follow these steps in order. Each file has complete, working code.

---

## Phase 1: Database Layer (30 files)

### Migrations (6 files)

- [ ] `database/migrations/YYYY_MM_DD_000001_create_wa_sessions_table.php`
- [ ] `database/migrations/YYYY_MM_DD_000002_create_imports_table.php`
- [ ] `database/migrations/YYYY_MM_DD_000003_create_recipients_table.php`
- [ ] `database/migrations/YYYY_MM_DD_000004_create_campaigns_table.php`
- [ ] `database/migrations/YYYY_MM_DD_000005_create_messages_table.php`
- [ ] `database/migrations/YYYY_MM_DD_000006_create_audit_logs_table.php`

### Models (6 files)

- [ ] `app/Models/WaSession.php`
- [ ] `app/Models/Import.php`
- [ ] `app/Models/Recipient.php`
- [ ] `app/Models/Campaign.php`
- [ ] `app/Models/Message.php`
- [ ] `app/Models/AuditLog.php`
- [ ] MODIFY: Add relationships to `app/Models/User.php`

### Policies (6 files)

- [ ] `app/Policies/WaSessionPolicy.php`
- [ ] `app/Policies/ImportPolicy.php`
- [ ] `app/Policies/RecipientPolicy.php`
- [ ] `app/Policies/CampaignPolicy.php`
- [ ] `app/Policies/MessagePolicy.php`
- [ ] `app/Policies/AuditLogPolicy.php`
- [ ] MODIFY: Register policies in `app/Providers/AuthServiceProvider.php`

### Factories (6 files)

- [ ] `database/factories/WaSessionFactory.php`
- [ ] `database/factories/ImportFactory.php`
- [ ] `database/factories/RecipientFactory.php`
- [ ] `database/factories/CampaignFactory.php`
- [ ] `database/factories/MessageFactory.php`
- [ ] `database/factories/AuditLogFactory.php`

### Seeders (1 file)

- [ ] `database/seeders/DemoSeeder.php`

---

## Phase 2: Backend Services (5 files)

### Service Layer

- [ ] `app/Services/BridgeClient.php`

### Controllers

- [ ] `app/Http/Controllers/WaSessionController.php`
- [ ] `app/Http/Controllers/ImportController.php`

### Console Commands

- [ ] `app/Console/Commands/ExpireIdleSessions.php`
- [ ] MODIFY: Schedule task in `app/Console/Kernel.php`

---

## Phase 3: Frontend Pages (3 files)

### Vue Pages

- [ ] `resources/js/pages/WhatsApp/Connect.vue`
- [ ] `resources/js/pages/Contacts/Imports/Index.vue`
- [ ] `resources/js/pages/Contacts/Imports/Show.vue`

---

## Phase 4: Configuration (4 modifications)

- [ ] MODIFY: Add routes to `routes/web.php`
- [ ] MODIFY: Add bridge config to `config/services.php`
- [ ] MODIFY: Add environment variables to `.env`
- [ ] UPDATE: `composer.json` and run `composer install`

---

## Phase 5: Installation & Setup

```bash
# 1. Install dependencies
composer require giggsey/libphonenumber-for-php openspout/openspout

# 2. Add to .env
BRIDGE_BASE_URL=http://localhost:3000
BRIDGE_TOKEN=your-secret-token-here

# 3. Run migrations
php artisan migrate

# 4. (Optional) Seed demo data
php artisan db:seed --class=DemoSeeder

# 5. Setup cron job for scheduler
crontab -e
# Add: * * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1

# 6. Start dev server
npm run dev
php artisan serve
```

---

## Phase 6: Testing

- [ ] Login: `demo@example.com` / `password`
- [ ] Visit `/wa/connect` - Should see connection page
- [ ] Visit `/contacts/imports` - Should see imports list
- [ ] Download template - Should get CSV file
- [ ] Upload contacts - Should parse and validate
- [ ] View import detail - Should show recipients

---

## Verification Checklist

### Database

- [ ] All 6 tables created
- [ ] Indexes present on key columns
- [ ] Foreign keys with cascade deletes

### Models

- [ ] All models have fillable properties
- [ ] Relationships defined
- [ ] Casts for JSON and dates

### Policies

- [ ] All models have policies
- [ ] Policies registered in AuthServiceProvider
- [ ] User ownership checks working

### Routes

- [ ] 5 WhatsApp routes under `/wa/`
- [ ] 5 Import routes under `/contacts/imports`
- [ ] All routes protected by `auth` middleware

### Pages

- [ ] WhatsApp connect page accessible
- [ ] QR code displays when pending
- [ ] Status polling works
- [ ] Import upload works
- [ ] Recipients display correctly

---

## Files Count

- **CREATE**: 38 new files
- **MODIFY**: 6 existing files
- **TOTAL**: 44 file changes

All files contain **complete, working logic** - no placeholders!

---

## Next Steps (Future Features)

After this implementation is working:

1. **Campaign Creation**
    - Create `CampaignController`
    - Add campaign form page
    - Template variable replacement

2. **Bulk Message Sending**
    - Queue jobs for sending
    - Progress tracking
    - Rate limiting/throttling

3. **Message Status**
    - Webhook for delivery status
    - Failed message retry
    - Statistics dashboard

---

## Need Help?

- Check `INSTALLATION_GUIDE.md` for detailed setup
- Check `IMPLEMENTATION_SUMMARY.md` for file overview
- All files have comments explaining logic
- Demo data available for testing

## Core Purpose Reminder

**This app does ONE thing:**

1. User connects WhatsApp
2. User imports contacts
3. User sends bulk messages

Keep it simple. Don't add extra features.
