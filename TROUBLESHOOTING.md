# 🔧 Tools4Friends - Troubleshooting Guide

## 🚨 Current Issue: 500 Internal Server Error

### Quick Diagnosis Steps

1. **Run the diagnostic script:**
   ```
   https://tools4friends.kvalitne.cz/diagnostic.php
   ```
   This will show you exactly what's wrong.

2. **Check PHP error logs:**
   - Look in your hosting control panel (cPanel, Plesk, etc.)
   - Check `/var/log/apache2/error.log` or similar
   - Look for the most recent error when accessing the site

3. **Check Apache error logs:**
   - Usually in `/var/log/apache2/error.log`
   - Or in your hosting control panel

---

## 🔍 Common Issues & Solutions

### Issue 1: 500 Error - "Failed opening required"

**Error Message:**
```
Failed opening required '/path/to/config/env_loader.php'
```

**Cause:** .htaccess is blocking access to the config directory

**Solution:**
1. Open `Tools4Friends/.htaccess`
2. Remove or comment out this block:
```apache
<DirectoryMatch "^.*/config">
    Order allow,deny
    Deny from all
</DirectoryMatch>
```

**Status:** ✅ FIXED (already removed)

---

### Issue 2: 500 Error - "Call to undefined function sanitizeOutput()"

**Error Message:**
```
Fatal error: Uncaught Error: Call to undefined function sanitizeOutput()
```

**Cause:** `security.php` not included before using the function

**Solution:**
1. Check `cart_icon.php` - should use `htmlspecialchars()` instead
2. Ensure `security.php` is included at the top of every PHP file

**Status:** ✅ FIXED (cart_icon.php updated)

---

### Issue 3: Database Connection Failed

**Error Message:**
```
Database connection error. Please contact administrator.
```

**Possible Causes:**
1. `.env` file missing
2. Wrong database credentials in `.env`
3. Database server not running
4. Database user doesn't have permissions

**Solution:**
1. Check if `.env` file exists in `Tools4Friends/` directory
2. Verify credentials:
   ```
   DB_HOST=localhost
   DB_USER=your_actual_username
   DB_PASS=your_actual_password
   DB_NAME=your_actual_database
   ```
3. Test database connection using diagnostic.php

---

### Issue 4: Blank Page (No Error)

**Cause:** PHP errors are hidden

**Solution:**
1. Enable error display temporarily:
   - Add to top of `index.php`:
   ```php
   error_reporting(E_ALL);
   ini_set('display_errors', 1);
   ```
2. Check PHP error logs
3. Run diagnostic.php

---

### Issue 5: "Headers already sent" Error

**Error Message:**
```
Warning: Cannot modify header information - headers already sent
```

**Cause:** Output before header() calls (whitespace, echo, etc.)

**Solution:**
1. Check for whitespace before `<?php` tags
2. Check for `echo` statements before redirects
3. Ensure no BOM (Byte Order Mark) in files

---

### Issue 6: Session Issues

**Error Message:**
```
Warning: session_start(): Cannot start session
```

**Possible Causes:**
1. Session directory not writable
2. Session already started
3. Headers already sent

**Solution:**
1. Check session directory permissions
2. Use `startSecureSession()` from security.php
3. Don't call `session_start()` multiple times

---

### Issue 7: Email Not Sending

**Symptoms:**
- Forgot password doesn't send email
- No error message

**Possible Causes:**
1. PHPMailer not installed
2. Wrong SMTP credentials
3. Gmail blocking access

**Solution:**
1. Install PHPMailer:
   ```bash
   cd Tools4Friends/public
   composer require phpmailer/phpmailer
   ```
2. Check SMTP credentials in `config/config_credentials.php`
3. Use Gmail App Password (not regular password)
4. Enable "Less secure app access" or use App Password

---

### Issue 8: File Upload Issues

**Error Message:**
```
Failed to move uploaded file
```

**Cause:** Upload directory not writable

**Solution:**
1. Create directory: `mkdir -p Tools4Friends/public/uploads`
2. Set permissions: `chmod 755 Tools4Friends/public/uploads`
3. Check `.htaccess` in uploads directory

---

### Issue 9: CSS/JS Not Loading

**Symptoms:**
- Page loads but looks broken
- No styling

**Possible Causes:**
1. Wrong file paths
2. .htaccess blocking access
3. Files don't exist

**Solution:**
1. Check browser console for 404 errors
2. Verify file paths in HTML
3. Check .htaccess rules

---

### Issue 10: "Too many redirects" Error

**Cause:** Redirect loop in code

**Solution:**
1. Check for circular redirects in PHP files
2. Look for redirect rules in .htaccess
3. Clear browser cookies/cache

---

## 📋 Configuration Checklist

### Required Files:
- [ ] `.env` (database credentials)
- [ ] `config/env_loader.php`
- [ ] `config/config_credentials.php` (SMTP settings)
- [ ] `app/security.php`
- [ ] `app/db_connect.php`
- [ ] `.htaccess`

### Required Directories:
- [ ] `logs/` (writable)
- [ ] `public/uploads/` (writable)
- [ ] `public/vendor/` (if using Composer)

### Required PHP Extensions:
- [ ] mysqli
- [ ] session
- [ ] json
- [ ] mbstring
- [ ] openssl (for HTTPS)

### File Permissions:
```bash
# Directories
chmod 755 Tools4Friends/
chmod 755 Tools4Friends/config/
chmod 755 Tools4Friends/app/
chmod 755 Tools4Friends/public/
chmod 777 Tools4Friends/logs/
chmod 755 Tools4Friends/public/uploads/

# Files
chmod 644 Tools4Friends/.env
chmod 644 Tools4Friends/.htaccess
chmod 644 Tools4Friends/config/*.php
chmod 644 Tools4Friends/app/*.php
chmod 644 Tools4Friends/public/*.php
```

---

## 🔐 Security Checklist

- [ ] `.env` file not accessible via web
- [ ] `config/` directory not accessible via web (but PHP can include files)
- [ ] `logs/` directory not accessible via web
- [ ] Database credentials not in public files
- [ ] SMTP password not in public files
- [ ] Error display disabled in production
- [ ] HTTPS enabled
- [ ] Session security configured

---

## 🧪 Testing Procedure

### 1. Test Diagnostic Script
```
https://tools4friends.kvalitne.cz/diagnostic.php
```
All tests should show ✓ OK

### 2. Test Homepage
```
https://tools4friends.kvalitne.cz/
```
Should load without errors

### 3. Test Login Page
```
https://tools4friends.kvalitne.cz/public/login.php
```
Should show login form

### 4. Test Database Connection
- Try logging in with valid credentials
- Should redirect to homepage

### 5. Test Email Functionality
- Use "Forgot Password" feature
- Should receive email

### 6. Test Tools Page
```
https://tools4friends.kvalitne.cz/public/tools.php
```
Should show list of tools

---

## 📞 Getting Help

If you're still experiencing issues:

1. **Run diagnostic.php** and note which tests fail
2. **Check error logs** for specific error messages
3. **Provide this information:**
   - PHP version
   - Server type (Apache/Nginx)
   - Hosting provider
   - Exact error message
   - Which page is failing
   - Results from diagnostic.php

---

## 🔄 Recent Changes Made

### Fixed Issues:
1. ✅ Removed config directory block from .htaccess
2. ✅ Fixed cart_icon.php to use htmlspecialchars()
3. ✅ Removed duplicate DB credentials from config_credentials.php
4. ✅ Fixed FILTER_SANITIZE_STRING deprecation (PHP 8.1+)

### Current Configuration:
- Database credentials: `.env` file
- SMTP credentials: `config/config_credentials.php`
- Base path: `/Tools4Friends/`
- Session timeout: 30 minutes
- Environment: Production

---

## 📝 Next Steps

1. **Access diagnostic.php** to see current system status
2. **Check which tests fail** in the diagnostic report
3. **Fix any red ✗ errors** shown in the report
4. **Test the homepage** after fixes
5. **Report back** with diagnostic results if still having issues
