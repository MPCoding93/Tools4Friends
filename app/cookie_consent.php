<?php
/**
 * Cookie Consent Modal Component
 * GDPR-compliant cookie consent banner
 */

// This file assumes cookie_functions.php has been included
// and $lang variable is set
?>

<?php if (shouldShowCookieConsent()): ?>
<div id="cookieConsentModal" class="cookie-consent-modal">
    <div class="cookie-consent-content">
        <h3><?php echo $lang === 'cs' ? 'Používání cookies' : 'Cookie Usage'; ?></h3>
        <p>
            <?php echo $lang === 'cs' 
                ? 'Tento web používá cookies pro zlepšení vašeho zážitku a zapamatování vašich preferencí, jako je preferovaný jazyk. Kliknutím na "Přijmout" souhlasíte s používáním cookies.' 
                : 'This website uses cookies to improve your experience and remember your preferences, such as your preferred language. By clicking "Accept", you consent to the use of cookies.'; ?>
        </p>
        <div class="cookie-consent-buttons">
            <button onclick="acceptCookies()" class="btn btn-blue">
                <?php echo $lang === 'cs' ? 'Přijmout' : 'Accept'; ?>
            </button>
            <button onclick="declineCookies()" class="btn btn-secondary">
                <?php echo $lang === 'cs' ? 'Odmítnout' : 'Decline'; ?>
            </button>
        </div>
        <p class="cookie-consent-info">
            <small>
                <?php echo $lang === 'cs' 
                    ? 'Více informací o našem používání cookies najdete v našich ' 
                    : 'Learn more about our cookie usage in our '; ?>
                <a href="<?php echo $inPublicFolder ? './contacts.php' : './public/contacts.php'; ?>?lang=<?php echo sanitizeOutput($lang); ?>">
                    <?php echo $lang === 'cs' ? 'kontaktech' : 'contacts'; ?>
                </a>.
            </small>
        </p>
    </div>
</div>

<script>
function acceptCookies() {
    fetch('<?php echo $inPublicFolder ? "./set_cookie_consent.php" : "./public/set_cookie_consent.php"; ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'consent=accepted&lang=<?php echo sanitizeOutput($lang); ?>'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('cookieConsentModal').style.display = 'none';
            // Set language cookie if user has a preference
            const urlParams = new URLSearchParams(window.location.search);
            const currentLang = urlParams.get('lang') || '<?php echo $lang; ?>';
            if (currentLang) {
                document.cookie = `preferred_language=${currentLang}; path=/; max-age=${365*24*60*60}; secure; samesite=Lax`;
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('cookieConsentModal').style.display = 'none';
    });
}

function declineCookies() {
    fetch('<?php echo $inPublicFolder ? "./set_cookie_consent.php" : "./public/set_cookie_consent.php"; ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'consent=declined&lang=<?php echo sanitizeOutput($lang); ?>'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('cookieConsentModal').style.display = 'none';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('cookieConsentModal').style.display = 'none';
    });
}
</script>
<?php endif; ?>
