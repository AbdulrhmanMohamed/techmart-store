# Free Deployment Guide for TechMart Store Portfolio Demo

## 🆓 Option 1: 000webhost (Recommended)

### Step 1: Sign Up
1. Go to [000webhost.com](https://000webhost.com)
2. Click "Free Sign Up"
3. Create account (no credit card needed)

### Step 2: Create Website
1. Click "Build Website" → "Upload Own Website"
2. Choose a subdomain name (e.g., `techmart-demo.000webhostapp.com`)
3. Wait for setup completion

### Step 3: Upload Files
1. Go to "File Manager" in your control panel
2. Navigate to `public_html` folder
3. Upload all your project files EXCEPT:
   - `docker-compose.yml`
   - `Dockerfile`
   - `.dockerignore`
   - `data/` folder
   - `database_backups/` folder

### Step 4: Setup Database
1. Go to "MySQL Databases" in control panel
2. Create new database (note the details)
3. Go to "phpMyAdmin"
4. Import your `database/init_updated.sql` file

### Step 5: Update Database Config
Update `config/database.php` with your 000webhost database details:
```php
$host = 'localhost';
$dbname = 'your_database_name';  // From 000webhost panel
$username = 'your_db_username';  // From 000webhost panel
$password = 'your_db_password';  // From 000webhost panel
```

### Step 6: Test Your Demo
Visit your subdomain: `https://yoursite.000webhostapp.com`

---

## 🆓 Option 2: InfinityFree

### Similar process to 000webhost:
1. Sign up at [infinityfree.net](https://infinityfree.net)
2. Create hosting account
3. Upload files via File Manager
4. Setup MySQL database
5. Update database config

---

## 🆓 Option 3: GitHub Pages (Static Demo)

If you want to showcase the frontend only:
1. Remove PHP dependencies
2. Convert to static HTML/CSS/JS
3. Use JSON files for demo data
4. Deploy to GitHub Pages

---

## 📝 Notes for Portfolio Demo

- **000webhost** is perfect for showing full functionality
- Free subdomains work great for portfolio demos
- No need for custom domains for demo purposes
- All features will work exactly like on localhost
- Perfect for showing to potential employers/clients

## 🚀 Quick Start (000webhost)

1. Sign up → Create website → Get subdomain
2. Upload files (exclude Docker files)
3. Create MySQL database → Import SQL file
4. Update database config with new credentials
5. Your demo is live!

**Total time: ~15 minutes**
**Cost: $0 forever**