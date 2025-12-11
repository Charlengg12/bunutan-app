# 🎄 Bunutan - Online Christmas Gift Exchange

A modern, minimalist web application for organizing secret gift exchanges (also known as "Secret Santa" or "Bunutan"). Features an admin panel for managing participants and a clean, anonymous interface for revealing gift partners.

## ✨ Features

- **Admin Panel**: Easy-to-use interface for managing the gift exchange
  - Add participants by name
  - Set gift value rules visible to all participants
  - Generate random draw with no self-assignments
  - View and share unique participant links
  - Track who has revealed their partner

- **Participant Interface**: Anonymous, clean reveal experience
  - Click-to-reveal mechanism for secret gift partner
  - Display gift value guidelines
  - Smooth animations and modern design
  - Mobile-responsive layout

- **Security & Privacy**
  - Unique tokens for each participant
  - Protected data directory
  - JSON-based data storage
  - No database required

## 📋 Requirements

- **PHP**: Version 7.0 or higher
- **Web Server**: Apache, Nginx, or any PHP-compatible server
- **Browser**: Modern browser with JavaScript enabled

## 🚀 Installation

### Quick Setup

1. **Extract the files** to your web server directory:
   ```
   /var/www/html/bunutan_app/
   ```
   or your hosting provider's public_html directory.

2. **Set permissions** for the data directory:
   ```bash
   chmod 755 bunutan_app/data
   ```

3. **Access the application**:
   - Admin panel: `http://yourdomain.com/bunutan_app/admin.php`
   - Participant view: `http://yourdomain.com/bunutan_app/index.php?token=xxx`

### Detailed Setup

#### For Apache Server

1. Ensure `.htaccess` files are enabled in your Apache configuration
2. The data directory is automatically protected by the included `.htaccess` file
3. No additional configuration needed

#### For Nginx Server

Add this to your Nginx configuration to protect the data directory:

```nginx
location ~ /bunutan_app/data/ {
    deny all;
    return 403;
}
```

#### For Shared Hosting

1. Upload all files via FTP or cPanel File Manager
2. Ensure the `data` directory is writable
3. Access via your domain or subdomain

## 📖 Usage Guide

### For Administrators

#### Step 1: Add Participants

1. Open `admin.php` in your browser
2. Enter participant names one by one
3. Click "Add Participant" for each name
4. You can add as many participants as needed (minimum 2)

#### Step 2: Set Gift Value Rules

1. In the "Gift Value Rules" section, enter guidelines such as:
   - "Gift value should be between $10 - $30"
   - "Handmade gifts are encouraged"
   - "Maximum budget: $25"
2. Click "Save Rules"
3. These rules will be visible to all participants after they reveal their partner

#### Step 3: Generate the Draw

1. Once all participants are added, click "Generate Draw"
2. **Important**: After generating the draw, you cannot add more participants
3. The system ensures no one is assigned to themselves

#### Step 4: Share Links

1. After the draw is generated, you'll see a list of participants with their unique links
2. Copy each link and send it to the corresponding participant via:
   - Email
   - WhatsApp
   - SMS
   - Any messaging platform
3. Each link is unique and can only reveal one specific assignment

#### Step 5: Monitor Progress

- The admin panel shows which participants have revealed their partners
- Green checkmark (✓) = Revealed
- Gray circle (○) = Not revealed yet

### For Participants

1. **Receive your link**: You'll get a unique link from the organizer
2. **Open the link**: Click or paste it in your browser
3. **Click to reveal**: Press the "Click to Reveal Your Secret Partner" button
4. **See your assignment**: Your secret gift partner will be displayed
5. **View gift rules**: Check the gift value guidelines if provided
6. **Keep it secret**: Don't share who you're buying for!

## 🔧 Configuration

### Basic Settings

Edit `config.php` to customize:

```php
// Application name
define('APP_NAME', 'Bunutan - Christmas Gift Exchange');

// Timezone
define('TIMEZONE', 'UTC');
```

### Data Storage

All data is stored in JSON files in the `data/` directory:
- `participants.json` - List of participants
- `draws.json` - Draw results and tokens
- `settings.json` - Gift rules and configuration

## 🎨 Customization

### Styling

Edit `css/style.css` to customize colors and appearance:

```css
:root {
    --primary-color: #2d5f3f;      /* Main green color */
    --secondary-color: #c41e3a;    /* Red accent color */
    --success-color: #28a745;      /* Success messages */
    --danger-color: #dc3545;       /* Danger/warning */
}
```

### Branding

1. Update the header in `admin.php` and `index.php`
2. Change the emoji icons (🎄, 🎁) to match your theme
3. Modify text content to fit your event

## 🔒 Security Notes

- The `data/` directory is protected by `.htaccess` (Apache) or should be protected via server configuration (Nginx)
- Tokens are generated using cryptographically secure random bytes
- No sensitive data is exposed to participants
- Each participant can only see their own assignment

## 🔄 Resetting the Application

To start a new gift exchange:

1. Go to the admin panel
2. Scroll to the "Danger Zone" section
3. Click "Reset All Data"
4. Confirm the action
5. All participants, draws, and settings will be cleared

**Alternative**: Manually delete the JSON files in the `data/` directory:
```bash
rm bunutan_app/data/*.json
```

## 🐛 Troubleshooting

### Issue: "Permission denied" errors

**Solution**: Ensure the data directory is writable:
```bash
chmod 755 bunutan_app/data
```

### Issue: Blank page or errors

**Solution**: 
1. Check PHP error logs
2. Ensure PHP version is 7.0 or higher
3. Verify all files were uploaded correctly

### Issue: .htaccess not working

**Solution**:
1. Ensure `mod_rewrite` is enabled in Apache
2. Check that `.htaccess` files are allowed in your Apache configuration
3. For Nginx, add the protection rules manually

### Issue: Participant link doesn't work

**Solution**:
1. Ensure the token parameter is included in the URL
2. Check that the draw has been generated
3. Verify the token hasn't been modified

## 📱 Browser Compatibility

- ✅ Chrome/Edge (latest)
- ✅ Firefox (latest)
- ✅ Safari (latest)
- ✅ Mobile browsers (iOS Safari, Chrome Mobile)

## 🎯 Best Practices

1. **Test first**: Run a test with 2-3 participants before your actual event
2. **Backup data**: Save the JSON files before resetting
3. **Secure admin access**: Don't share the admin.php URL publicly
4. **Send links privately**: Use private messaging to send participant links
5. **Set a deadline**: Tell participants when to reveal their assignment

## 📄 File Structure

```
bunutan_app/
├── index.php           # Participant reveal interface
├── admin.php           # Admin management panel
├── api.php             # Backend API endpoints
├── config.php          # Configuration settings
├── css/
│   └── style.css       # Stylesheet
├── js/
│   └── main.js         # JavaScript functionality
├── data/               # Data storage (protected)
│   └── .htaccess       # Access protection
└── README.md           # This file
```

## 🆘 Support

For issues or questions:
1. Check the troubleshooting section above
2. Review the PHP error logs
3. Ensure all requirements are met

## 📝 License

This application is provided as-is for personal and commercial use. Feel free to modify and redistribute.

## 🎉 Credits

Built with modern web technologies:
- PHP for backend logic
- Vanilla JavaScript for interactivity
- CSS3 for styling and animations
- JSON for data storage

---

**Happy Gift Exchanging! 🎁**

Made with ❤️ for spreading holiday cheer
