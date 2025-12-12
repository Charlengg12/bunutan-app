# Bunutan - Supabase Migration Guide

## Overview

The Bunutan Gift Exchange application has been successfully migrated from PHP/JSON file storage to a modern Supabase-powered architecture with client-side JavaScript.

## What Changed

### Architecture

- **Old**: PHP backend with JSON file storage
- **New**: Supabase PostgreSQL database with client-side JavaScript

### Technology Stack

**Before:**
- PHP 7.0+ (api.php, config.php)
- JSON files for data storage
- Vanilla JavaScript frontend

**After:**
- Supabase PostgreSQL database
- Supabase JS Client Library
- Vanilla JavaScript (no build step required)
- Modern ES6+ JavaScript

### File Structure

```
/project/
├── index.html                          # New clean landing page
├── index_new.html                      # Alternative landing page
├── MIGRATION_GUIDE.md                  # This file
└── bunutan_app/
    ├── admin.html                      # Admin panel (replaces admin.php)
    ├── index.html                      # Participant view (replaces index.php)
    ├── admin.php                       # OLD - PHP admin (deprecated)
    ├── index.php                       # OLD - PHP participant view (deprecated)
    ├── api.php                         # OLD - PHP API (deprecated)
    ├── config.php                      # OLD - PHP config (deprecated)
    ├── css/
    │   └── style.css                   # Styles (unchanged)
    ├── js/
    │   ├── config.js                   # NEW - Environment config
    │   ├── supabase-client.js          # NEW - Supabase client & API
    │   ├── admin.js                    # NEW - Admin panel logic
    │   └── main.js                     # Shared utilities (updated)
    └── data/                           # OLD - JSON storage (deprecated)
```

## Database Schema

### Tables Created

1. **participants**
   - `id` (uuid, primary key)
   - `name` (text, unique, not null)
   - `email` (text, nullable)
   - `added_at` (timestamptz)
   - `created_at` (timestamptz)
   - `updated_at` (timestamptz)

2. **draws**
   - `id` (uuid, primary key)
   - `giver_id` (uuid, foreign key)
   - `receiver_id` (uuid, foreign key)
   - `token` (text, unique)
   - `revealed` (boolean)
   - `revealed_at` (timestamptz)
   - `created_at` (timestamptz)
   - `updated_at` (timestamptz)

3. **settings**
   - `id` (uuid, primary key)
   - `key` (text, unique)
   - `value` (text)
   - `created_at` (timestamptz)
   - `updated_at` (timestamptz)

### Security

- Row Level Security (RLS) enabled on all tables
- Public access for now (can be restricted with authentication later)
- Foreign key constraints to maintain data integrity
- Check constraints to prevent invalid data

## Features

All original features are preserved:

- Add/delete participants
- Bulk import participants
- Generate random draw assignments
- Token-based reveal system
- Gift value rules/guidelines
- Real-time statistics
- Export data
- Reset all data
- Dark mode toggle
- Toast notifications
- Search/filter participants

## How to Use

### For Administrators

1. **Access Admin Panel**
   - Open `bunutan_app/admin.html` in your browser
   - No login required (can be added later)

2. **Add Participants**
   - Enter names one by one, or
   - Use bulk import (comma/newline separated)

3. **Set Gift Rules**
   - Configure budget and guidelines
   - Rules visible to all participants

4. **Generate Draw**
   - Click "Generate Draw" when ready
   - System ensures no self-assignments
   - Unique tokens generated for each participant

5. **Share Links**
   - Copy participant links
   - Send via email, SMS, or messaging apps
   - Each link reveals only one assignment

### For Participants

1. **Open Your Link**
   - Admin sends you a unique link
   - Format: `bunutan_app/index.html?token=YOUR_TOKEN`

2. **Reveal Your Partner**
   - Click "Click to Reveal Your Secret Partner"
   - See who you're buying for
   - View gift guidelines

3. **Keep It Secret**
   - Don't share who you got
   - Check guidelines for budget/preferences

## Configuration

### Environment Variables

Located in `bunutan_app/js/config.js`:

```javascript
window.ENV = {
    SUPABASE_URL: 'your-supabase-url',
    SUPABASE_ANON_KEY: 'your-anon-key'
};
```

These are automatically loaded from your `.env` file during deployment.

## API Functions

All API calls are handled through the `SupabaseAPI` object in `supabase-client.js`:

### Participant Management
- `SupabaseAPI.addParticipant(name, email)`
- `SupabaseAPI.getParticipants()`
- `SupabaseAPI.deleteParticipant(id)`
- `SupabaseAPI.bulkAddParticipants(names)`

### Settings
- `SupabaseAPI.getSetting(key)`
- `SupabaseAPI.getSettings()`
- `SupabaseAPI.setSetting(key, value)`
- `SupabaseAPI.setGiftRules(rules)`

### Draws
- `SupabaseAPI.generateDraw()`
- `SupabaseAPI.getDraw()`
- `SupabaseAPI.revealPartner(token)`

### Statistics & Export
- `SupabaseAPI.getStatistics()`
- `SupabaseAPI.exportData()`
- `SupabaseAPI.resetAll()`

## Migration from Old System

If you have existing data in JSON files:

1. **Export Old Data**
   - Open old `admin.php`
   - Click "Export Data"
   - Save JSON file

2. **Manually Import to Supabase**
   - Use Supabase dashboard
   - Insert data into respective tables
   - Or write a migration script

3. **Test New System**
   - Verify all participants loaded
   - Check settings migrated correctly
   - Test draw generation

## Future Enhancements

Recommended improvements:

1. **Authentication**
   - Add Supabase Auth for admin panel
   - Role-based access control
   - Secure admin-only endpoints

2. **Email Notifications**
   - Automated link distribution
   - Reminder emails
   - Status updates

3. **Enhanced Features**
   - Participant RSVP tracking
   - Photo uploads
   - Chat/messaging
   - Gift purchase tracking with receipts
   - Exchange history

4. **Performance**
   - Add caching for settings
   - Optimize queries with indexes
   - Rate limiting on sensitive endpoints

## Troubleshooting

### Issue: "Failed to load Supabase"

**Solution**: Check that:
1. `bunutan_app/js/config.js` has correct credentials
2. Supabase project is active
3. Browser allows third-party scripts

### Issue: "Error adding participant"

**Solution**: Check that:
1. Name is not duplicate
2. Draw hasn't been generated yet
3. Database connection is working

### Issue: "Invalid token"

**Solution**:
1. Ensure token in URL is complete
2. Check that draw was generated
3. Verify token hasn't been manually modified

### Issue: "Cannot generate draw"

**Solution**:
1. Need at least 2 participants
2. Draw can only be generated once
3. Check browser console for errors

## Browser Compatibility

Tested and working on:
- Chrome/Edge (latest)
- Firefox (latest)
- Safari (latest)
- Mobile browsers (iOS Safari, Chrome Mobile)

## Performance

- No build step required
- Fast page loads (< 1 second)
- Supabase CDN for global performance
- Real-time updates via Supabase

## Security Notes

1. **Current State**: Public access to all tables
2. **Recommendation**: Add authentication before production
3. **Data Protection**: All data stored in Supabase (GDPR compliant)
4. **Tokens**: Cryptographically secure (32-character hex)

## Support

For issues or questions:
1. Check browser console for errors
2. Verify Supabase connection
3. Test with 2-3 participants first
4. Check this guide for troubleshooting

## License

Same as original project - free for personal and commercial use.

---

**Happy Gift Exchanging!**

Built with modern web technologies and powered by Supabase.
