# FPDF Installation Instructions

## What is FPDF?
FPDF is a PHP library that allows you to generate PDF documents without using the PDFlib extension.

## Installation Steps

### Option 1: Manual Download (Recommended)

1. **Download FPDF:**
   - Visit: http://www.fpdf.org/
   - Download the latest version (currently 1.86)
   - Or direct download: http://www.fpdf.org/en/download/fpdf186.zip

2. **Extract and Place:**
   - Extract the downloaded ZIP file
   - Create directory structure: `Tools4Friends/vendor/fpdf/`
   - Copy `fpdf.php` to: `Tools4Friends/vendor/fpdf/fpdf.php`
   - Copy `font` folder to: `Tools4Friends/vendor/fpdf/font/`

3. **Verify Installation:**
   - Check that file exists: `Tools4Friends/vendor/fpdf/fpdf.php`
   - The invoice generation will now work

### Option 2: Using Composer (If Available)

If you have Composer installed on your server:

```bash
cd Tools4Friends
composer require setasign/fpdf
```

Then update `generate_invoice.php` line 15 to:
```php
$fpdf_path = __DIR__ . '/../vendor/setasign/fpdf/fpdf.php';
```

### Alternative: Use TCPDF Instead

If you prefer TCPDF (more features but larger):

1. Download from: https://tcpdf.org/
2. Or via Composer: `composer require tecnickcom/tcpdf`
3. Update `generate_invoice.php` to use TCPDF class instead of FPDF

## Directory Structure After Installation

```
Tools4Friends/
├── vendor/
│   └── fpdf/
│       ├── fpdf.php          ← Main FPDF file
│       └── font/             ← Font directory
│           ├── courier.php
│           ├── helvetica.php
│           ├── times.php
│           └── ...
├── public/
│   ├── generate_invoice.php  ← Uses FPDF
│   └── ...
└── ...
```

## Testing

After installation, test by:
1. Login as admin
2. Approve an order
3. Click "Download Invoice" (when implemented)
4. PDF should download successfully

## Troubleshooting

**Error: "FPDF library not found"**
- Check file path: `Tools4Friends/vendor/fpdf/fpdf.php`
- Ensure file permissions are correct (644 for files, 755 for directories)

**Error: "Font not found"**
- Ensure `font` directory exists in `vendor/fpdf/`
- Check that font files (.php) are present

**PDF doesn't display correctly:**
- Check PHP memory limit (increase if needed)
- Verify image paths are correct
- Ensure banner image exists at: `public/images/banners/tools4friends_dark_Banner_2000x400.png`

## License

FPDF is free to use for both personal and commercial projects.
License: Freeware
