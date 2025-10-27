# Tools4Friends - Architecture & File Dependencies

## 📊 File Connection Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                         ROOT LEVEL                               │
│  Tools4Friends/                                                  │
│  ├── .env (Environment variables: DB credentials, SMTP, etc.)   │
│  ├── .htaccess (Apache configuration)                           │
│  └── index.php (Homepage - Entry point)                         │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ├─── includes ───┐
                              │                │
                              ▼                ▼
┌──────────────────────────────────┐  ┌──────────────────────────┐
│     app/ (Core Functions)        │  │   public/ (User Pages)   │
│  ├── security.php                │  │  ├── login.php           │
│  │   └── Session management      │  │  ├── tools.php           │
│  │   └── CSRF protection         │  │  ├── cart.php            │
│  │   └── sanitizeOutput()        │  │  ├── myprofile.php       │
│  │   └── validateEmail()         │  │  ├── myorders.php        │
│  │   └── checkLoginAttempts()    │  │  ├── forgot_password.php │
│  │                                │  │  ├── reset_password.php  │
│  ├── db_connect.php               │  │  ├── add_tool.php        │
│  │   └── MySQL connection        │  │  ├── edit_tool.php       │
│  │   └── Uses env_loader.php     │  │  ├── admin_orders.php    │
│  │                                │  │  └── contacts.php        │
│  ├── navbar.php                   │  └──────────────────────────┘
│  │   └── Navigation menu          │
│  │                                │
│  ├── cart_icon.php                │
│  │   └── Shopping cart display    │
│  │                                │
│  └── email_functions.php          │
│      └── Email utilities          │
└──────────────────────────────────┘
                │
                ├─── requires ───┐
                │                │
                ▼                ▼
┌──────────────────────────────────┐  ┌──────────────────────────┐
│   config/ (Configuration)        │  │   vendor/ (Dependencies) │
│  ├── env_loader.php              │  │  └── PHPMailer/          │
│  │   └── Loads .env file         │  │      └── PHPMailer.php   │
│  │   └── Defines constants       │  └──────────────────────────┘
│  │                                │
│  └── config_credentials.php       │
│      └── SMTP settings            │
│      └── Email configuration      │
└──────────────────────────────────┘
```

## 🔄 Execution Flow

### 1. Homepage Load (index.php)
```
index.php
  │
  ├─→ app/security.php
  │     ├─→ startSecureSession()
  │     ├─→ setSecurityHeaders()
  │     └─→ checkSessionTimeout()
  │
  ├─→ app/cart_icon.php
  │     └─→ Displays cart icon
  │
  └─→ app/navbar.php
        └─→ Navigation menu
```

### 2. Login Page (public/login.php)
```
public/login.php
  │
  ├─→ app/security.php
  │     └─→ Session & CSRF
  │
  ├─→ app/db_connect.php
  │     │
  │     └─→ config/env_loader.php
  │           │
  │           └─→ .env file
  │                 ├─→ DB_HOST
  │                 ├─→ DB_USER
  │                 ├─→ DB_PASS
  │                 └─→ DB_NAME
  │
  └─→ app/navbar.php
```

### 3. Forgot Password (public/forgot_password.php)
```
public/forgot_password.php
  │
  ├─→ app/security.php
  │
  ├─→ app/db_connect.php
  │     └─→ config/env_loader.php → .env
  │
  ├─→ config/config_credentials.php
  │     ├─→ SMTP_HOST
  │     ├─→ SMTP_USERNAME
  │     ├─→ SMTP_PASSWORD
  │     ├─→ SMTP_PORT
  │     └─→ SMTP_ENCRYPTION
  │
  └─→ vendor/autoload.php
        └─→ PHPMailer
```

### 4. Tools Page (public/tools.php)
```
public/tools.php
  │
  ├─→ app/security.php
  │
  ├─→ app/db_connect.php
  │     └─→ Database queries
  │
  ├─→ app/cart_icon.php
  │
  └─→ app/navbar.php
```

### 5. My Orders (public/myorders.php)
```
public/myorders.php
  │
  ├─→ app/security.php
  │     └─→ requireLogin()
  │
  ├─→ app/db_connect.php
  │     └─→ Fetch user orders
  │
  └─→ app/navbar.php
```

## 🔑 Critical Dependencies

### Every Page Requires:
1. **app/security.php** - MUST be first
   - Starts session
   - Sets security headers
   - Provides sanitizeOutput()

2. **app/db_connect.php** - For database access
   - Requires config/env_loader.php
   - Requires .env file

3. **app/navbar.php** - For navigation
   - Requires $lang variable
   - Requires session to be started

### Special Requirements:

**Email Functionality:**
- config/config_credentials.php (SMTP settings)
- vendor/autoload.php (PHPMailer)
- PHPMailer library installed

**Cart Functionality:**
- Session must be started
- $_SESSION['cart'] array

## ⚠️ Common Issues & Solutions

### Issue 1: 500 Error on Homepage
**Cause:** .htaccess blocking config directory
**Solution:** Remove config directory block from .htaccess

### Issue 2: Undefined Function sanitizeOutput()
**Cause:** security.php not included before use
**Solution:** Always include security.php first

### Issue 3: Database Connection Failed
**Cause:** .env file missing or incorrect credentials
**Solution:** Check .env file exists and has correct values

### Issue 4: Email Not Sending
**Cause:** PHPMailer not installed or SMTP settings wrong
**Solution:** Run `composer require phpmailer/phpmailer`

### Issue 5: Session Issues
**Cause:** Session not started before use
**Solution:** Call startSecureSession() at top of file

## 📁 File Inclusion Order (CRITICAL!)

```php
// CORRECT ORDER:
<?php
// 1. Security FIRST (starts session, provides functions)
require_once __DIR__ . '/../app/security.php';

// 2. Start session
startSecureSession();

// 3. Database connection (if needed)
require_once __DIR__ . '/../app/db_connect.php';

// 4. Other includes
require_once __DIR__ . '/../config/config_credentials.php';

// 5. Set variables BEFORE including navbar/cart_icon
$lang = $_GET['lang'] ?? 'en';
$loggedIn = isset($_SESSION['user_id']);

// 6. Include UI components LAST
include __DIR__ . '/../app/navbar.php';
include __DIR__ . '/../app/cart_icon.php';
?>
```

## 🔍 Debugging Checklist

When encountering 500 errors, check in this order:

1. ✅ .htaccess not blocking config directory
2. ✅ .env file exists and is readable
3. ✅ security.php included before any function calls
4. ✅ Session started before accessing $_SESSION
5. ✅ Database credentials correct in .env
6. ✅ PHPMailer installed (if using email)
7. ✅ All file paths correct (relative paths)
8. ✅ PHP error logs for specific error messages

## 📝 Environment Variables (.env)

Required variables:
```
DB_HOST=localhost
DB_USER=your_db_user
DB_PASS=your_db_password
DB_NAME=your_db_name

SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your_email@gmail.com
SMTP_PASSWORD=your_app_password
SMTP_ENCRYPTION=tls

COMPANY_EMAIL=your_email@gmail.com
COMPANY_NAME=Tools4Friends
```

## 🎯 Current Configuration

**Base Path:** `/Tools4Friends/`
**Public URL:** `https://tools4friends.kvalitne.cz/`
**Document Root:** Should point to Tools4Friends directory

**Important:** All internal links use `/Tools4Friends/` prefix because the application expects to be in a subdirectory, not at domain root.
