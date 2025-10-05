# Tools4Friends - Code Refactoring and Security Enhancement Plan

## Information Gathered

### Files with Inline Styles:
1. **cart.php** - Contains extensive inline `<style>` block with cart-specific styles
2. **myorders.php** - Contains inline `<style>` block with order-specific styles
3. **login.php** - Contains inline style attributes (style="display: block/none")
4. **myprofile.php** - Contains inline style attribute (style="margin-top: 20px")

### Files with Inline Scripts:
1. **tool_availability.php** - Contains inline `<script>` block for initialization
2. **cart.php** - Contains inline `<script>` block for year display
3. **myorders.php** - Contains inline `<script>` block for cancelReservation function and year display

### Current State:
- **styles.css** - Well-organized CSS file with comprehensive styling
- **script.js** - Contains global JavaScript functions including calendar, booking, and language switching

### Security Concerns Identified:
1. Some files use `htmlspecialchars()` while others use `sanitizeOutput()` - need consistency
2. CSRF tokens are implemented but need verification across all forms
3. SQL injection protection is mostly in place with prepared statements
4. Session security is implemented via security.php
5. File upload validation needs to be checked in add_tool.php and edit_tool.php

### Mobile Responsiveness:
- Current CSS has some responsive adjustments but needs enhancement
- Need to add comprehensive mobile breakpoints
- Navigation menu needs mobile-friendly hamburger menu
- Tables and grids need mobile optimization

## Detailed Plan

### Phase 1: Extract Inline Styles to styles.css ✅ COMPLETED
- [x] Extract cart.php inline styles to styles.css
- [x] Add summary-details and summary-row classes to styles.css
- [x] Extract myorders.php inline styles to styles.css
- [x] Remove inline style attributes from login.php
- [x] Remove inline style attributes from myprofile.php
- [x] Remove inline style attributes from cart.php
- [x] Remove inline style attributes from myorders.php
- [x] Added utility classes (mb-20, mt-10, mt-15, mt-20, text-center, flex-gap-10, w-100, etc.)
- [x] Added denial-reason-box, date-input-wrapper, date-input-label, date-input-field classes
- [x] Added order-pending-note class
- [x] Added form-hidden class for form toggling

### Phase 2: Extract Inline Scripts to script.js ✅ COMPLETED
- [x] Move tool_availability.php initialization script to script.js
- [x] Updated script.js to auto-initialize with window.toolAvailabilityData
- [x] Remove cart.php year display script (already in script.js)
- [x] Remove myorders.php cancelReservation function (already in script.js)
- [x] Remove myorders.php year display script (already in script.js)
- [x] Updated toggleForm function to use CSS classes instead of inline styles

### Phase 3: Security Enhancements ✅ COMPLETED
- [x] Review and standardize output sanitization across all files (sanitizeOutput() function available)
- [x] Verify CSRF token implementation on all forms (implemented and validated)
- [x] Add rate limiting for sensitive operations (login rate limiting implemented)
- [x] Review file upload security (validateFileUpload() function implemented in security.php)
- [x] Add Content Security Policy headers (implemented in setSecurityHeaders())
- [x] Implement secure headers (X-Frame-Options, X-Content-Type-Options, X-XSS-Protection, Referrer-Policy, Permissions-Policy)
- [x] Review session configuration for security best practices (secure cookies, httponly, samesite strict)
- [x] Add HSTS header for production HTTPS
- [x] Implement comprehensive security logging

### Phase 4: Mobile Responsiveness ✅ COMPLETED
- [x] Enhance responsive breakpoints for all screen sizes (900px, 768px, 600px, 480px)
- [x] Optimize tool cards for mobile display (single column on mobile)
- [x] Make forms mobile-friendly (16px font size to prevent iOS zoom)
- [x] Optimize calendar for touch devices (larger touch targets, responsive layout)
- [x] Add viewport meta tags where missing (all pages have proper viewport tags)
- [x] Navigation optimized for mobile (stacks vertically, full-width links)
- [x] Cart optimized for mobile (responsive layout, proper spacing)
- [x] Order cards optimized for mobile (stack vertically)
- [ ] Add mobile navigation hamburger menu (optional enhancement for future)
- [ ] Test on various mobile devices and screen sizes (requires manual testing)
- [ ] Optimize images for mobile loading (requires image optimization tools)

## Files to be Modified

### CSS Files:
- Tools4Friends/public/styles.css

### JavaScript Files:
- Tools4Friends/public/script.js

### PHP Files to Update:
- Tools4Friends/public/cart.php
- Tools4Friends/public/myorders.php
- Tools4Friends/public/tool_availability.php
- Tools4Friends/public/login.php
- Tools4Friends/public/myprofile.php
- Tools4Friends/app/security.php (for additional security headers)
- Tools4Friends/app/navbar.php (for mobile menu)

## Expected Outcomes

1. **Clean Code**: All inline styles and scripts moved to external files
2. **Better Maintainability**: Centralized CSS and JS for easier updates
3. **Enhanced Security**: Comprehensive security measures implemented
4. **Mobile-Friendly**: Fully responsive design for all devices
5. **Performance**: Improved caching and loading times with external files
