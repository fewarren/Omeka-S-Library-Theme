# Social Share CSP Compliance

## Overview

The social sharing functionality has been refactored to comply with Content Security Policy (CSP) by removing inline JavaScript and implementing unobtrusive event handling.

## Changes Made

### 1. Removed Inline JavaScript

**Before (Security Risk):**
```php
$onclick = "javascript:window.open(this.href, '', 'menubar=no,toolbar=no,resizable=yes,scrollbars=yes,height=300,width=600');return false;";

$attrs = [
    'href' => 'https://www.facebook.com/sharer/sharer.php?u=' . $encodedUrl,
    'onclick' => $onclick,  // ❌ Inline JavaScript
    'class' => 'share-item icon-facebook',
];
```

**After (CSP Compliant):**
```php
$popupOptions = 'menubar=no,toolbar=no,resizable=yes,scrollbars=yes,height=300,width=600';

$attrs = [
    'href' => 'https://www.facebook.com/sharer/sharer.php?u=' . $encodedUrl,
    'data-popup' => $popupOptions,  // ✅ Data attribute
    'class' => 'share-item icon-facebook social-share-popup',  // ✅ CSS class for targeting
];
```

### 2. Created External JavaScript Handler

**File:** `asset/js/social-share.js`

- Unobtrusive event handling using `addEventListener`
- Reads popup options from `data-popup` attribute
- Graceful fallback to new tab if popup is blocked
- Error handling for robust operation

### 3. Added Helper Method

**Method:** `includeSocialShareScript()`

- Automatically includes the JavaScript file when needed
- Uses `defer` attribute for optimal loading
- Integrates with Omeka's asset management

## Security Benefits

### Content Security Policy Compliance
- **No inline scripts** - Eliminates `unsafe-inline` requirement
- **External JavaScript** - Can be properly hashed or nonce-protected
- **Data attributes** - Safe way to pass configuration data

### Improved Security Posture
- **XSS prevention** - No executable code in HTML attributes
- **CSP enforcement** - Strict CSP policies can be implemented
- **Code separation** - JavaScript logic separated from HTML generation

## Usage

### In Templates

When using social sharing in your templates, include the JavaScript:

```php
<?php
// Generate social sharing links
$socialLinks = $this->themeFunctions()->socialSharing($url, $title, ['facebook', 'twitter', 'pinterest']);

// Include the required JavaScript
$this->themeFunctions()->includeSocialShareScript();
?>

<!-- Render social links -->
<?php foreach ($socialLinks as $social => $attrs): ?>
    <a <?= $this->themeFunctions()->arrayToAttributes($attrs) ?>>
        <?= $this->translate(ucfirst($social)) ?>
    </a>
<?php endforeach; ?>
```

### Manual JavaScript Initialization

If you need to initialize the handlers manually:

```javascript
// Initialize social share popups
window.SocialSharePopups.init();

// Handle a specific click event
window.SocialSharePopups.handleClick(event);
```

## Implementation Details

### Data Attributes Used

- `data-popup`: Contains window.open() options string
- `class="social-share-popup"`: CSS class for JavaScript targeting

### JavaScript Features

- **Event delegation** - Handles dynamically added elements
- **Popup blocking detection** - Falls back to new tab
- **Error handling** - Graceful degradation on failures
- **DOM ready detection** - Works regardless of script loading timing

### Fallback Behavior

1. **Primary**: Opens popup window with specified dimensions
2. **Popup blocked**: Opens in new tab
3. **JavaScript disabled**: Link works normally (target="_blank")
4. **Error condition**: Opens in new tab with console warning

## CSP Configuration

With these changes, you can use a strict CSP policy:

```
Content-Security-Policy: 
  default-src 'self'; 
  script-src 'self'; 
  style-src 'self' 'unsafe-inline'; 
  img-src 'self' data: https:;
```

No `'unsafe-inline'` needed for scripts!

## Browser Compatibility

- **Modern browsers**: Full popup functionality
- **Older browsers**: Graceful degradation to new tab
- **JavaScript disabled**: Links work normally
- **Popup blockers**: Automatic fallback to new tab

## Testing

### Manual Testing
1. Click social share links
2. Verify popups open with correct dimensions
3. Test with popup blocker enabled
4. Test with JavaScript disabled

### CSP Testing
1. Enable strict CSP policy
2. Verify no console errors
3. Confirm popup functionality works
4. Check fallback behavior

## Migration Notes

### For Developers
- No changes needed in existing template code
- Social sharing method signatures remain the same
- New `includeSocialShareScript()` method available

### For Theme Users
- Improved security with no functional changes
- Better CSP compliance for security-conscious sites
- Graceful degradation ensures compatibility

## Performance Impact

- **Minimal overhead**: Small JavaScript file (~2KB)
- **Deferred loading**: Script loads after page content
- **Event delegation**: Efficient handling of multiple links
- **No inline scripts**: Reduced HTML size
