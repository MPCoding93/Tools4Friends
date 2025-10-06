// Global JavaScript functions for Tools4Friends website - updated version

// Consolidated DOMContentLoaded listener
document.addEventListener("DOMContentLoaded", function() {
  // Set current year in footer
  const yearElement = document.getElementById("year");
  if (yearElement) {
    yearElement.textContent = new Date().getFullYear();
  }
  
  // Initialize language
  initializeLanguage();
  
  // Initialize calendar and booking functionality
  if (typeof unavailableRanges !== 'undefined') {
    initializeCalendar(unavailableRanges);
  }
  if (typeof toolId !== 'undefined') {
    initializeBookingFunctionality(toolId, document.documentElement.lang || 'en');
  }
  
  // Initialize tool availability page if data is available
  if (window.toolAvailabilityData) {
    const { toolId, lang, unavailableRanges, csrfToken } = window.toolAvailabilityData;
    initializeCalendar(unavailableRanges);
    initializeToolAvailabilityPage(toolId, lang, unavailableRanges, csrfToken);
  }
  
  // Initialize category hamburger menu
  initializeCategoryMenu();
});

// Category hamburger menu functionality
function initializeCategoryMenu() {
  const hamburger = document.getElementById('categoryToggle');
  const categoryNav = document.getElementById('categoryNav');
  const closeBtn = document.getElementById('categoryClose');
  
  if (!hamburger || !categoryNav) return;
  
  // Create overlay element
  const overlay = document.createElement('div');
  overlay.className = 'category-overlay';
  overlay.id = 'categoryOverlay';
  document.body.appendChild(overlay);
  
  // Toggle menu function
  function toggleMenu() {
    const isActive = categoryNav.classList.contains('active');
    
    if (isActive) {
      closeMenu();
    } else {
      openMenu();
    }
  }
  
  function openMenu() {
    categoryNav.classList.add('active');
    hamburger.classList.add('active');
    overlay.classList.add('active');
    document.body.style.overflow = 'hidden'; // Prevent scrolling when menu is open
  }
  
  function closeMenu() {
    categoryNav.classList.remove('active');
    hamburger.classList.remove('active');
    overlay.classList.remove('active');
    document.body.style.overflow = ''; // Restore scrolling
  }
  
  // Event listeners
  hamburger.addEventListener('click', toggleMenu);
  
  if (closeBtn) {
    closeBtn.addEventListener('click', closeMenu);
  }
  
  overlay.addEventListener('click', closeMenu);
  
  // Close menu when clicking a category link
  categoryNav.querySelectorAll('a').forEach(link => {
    link.addEventListener('click', closeMenu);
  });
  
  // Close menu on escape key
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && categoryNav.classList.contains('active')) {
      closeMenu();
    }
  });
  
  // Close menu when window is resized to desktop size
  window.addEventListener('resize', function() {
    if (window.innerWidth > 900 && categoryNav.classList.contains('active')) {
      closeMenu();
    }
  });
}

// Language functions
function initializeLanguage() {
  const urlParams = new URLSearchParams(window.location.search);
  const langFromUrl = urlParams.get('lang');
  const supportedLanguages = ['en', 'cs'];
  
  if (supportedLanguages.includes(langFromUrl)) {
    setLanguage(langFromUrl);
    updateLanguageButtons(langFromUrl);
  } else {
    const defaultLang = 'en';
    setLanguage(defaultLang);
    updateLanguageButtons(defaultLang);
    updateUrlWithLanguage(defaultLang);
  }
}

function setLanguage(lang) {
  // Update all translatable elements
  document.querySelectorAll("[data-translate]").forEach(el => {
    const translationKey = el.getAttribute('data-translate');
    const translation = translations[lang]?.[translationKey] || el.textContent;
    if (translation) el.textContent = translation;
  });
  
  // Update HTML lang attribute
  document.documentElement.lang = lang;
}

function updateLanguageButtons(activeLang) {
  document.querySelectorAll('.language-toggle button').forEach(button => {
    button.classList.toggle('active', 
      (activeLang === 'en' && button.textContent === 'EN') ||
      (activeLang === 'cs' && button.textContent === 'ČS')
    );
  });
}

function updateUrlWithLanguage(lang) {
  const url = new URL(window.location);
  url.searchParams.set('lang', lang);
  window.history.replaceState({}, '', url);
}

function switchLanguage(lang, page = null) {
  const url = new URL(window.location);
  url.searchParams.set('lang', lang);
  
  if (page) {
    window.location.href = `${url.origin}/${page}?${url.searchParams.toString()}`;
  } else {
    window.history.replaceState({}, '', url);
    setLanguage(lang);
    updateLanguageButtons(lang);
  }
}

// Calendar functions
let currentDate = new Date();
let unavailableDates = [];

function initializeCalendar(dates) {
  unavailableDates = dates || [];
  renderCalendar(currentDate);
}

function renderCalendar(date) {
  const calendarEl = document.getElementById("calendar");
  const monthLabelEl = document.getElementById("calendar-month");

  if (!calendarEl || !monthLabelEl) return;

  calendarEl.innerHTML = "";

  // Create month label
  monthLabelEl.textContent = date.toLocaleDateString(document.documentElement.lang, {
    month: 'long',
    year: 'numeric'
  });

  // Create day headers
  const daysOfWeek = ['Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'Su'];
  daysOfWeek.forEach(day => {
    const dayEl = document.createElement("div");
    dayEl.className = "calendar-header";
    dayEl.textContent = day;
    calendarEl.appendChild(dayEl);
  });

  // Calculate days to show
  const year = date.getFullYear();
  const month = date.getMonth();
  const firstDay = new Date(year, month, 1);
  const lastDay = new Date(year, month + 1, 0);
  const daysInMonth = lastDay.getDate();
  const startOffset = (firstDay.getDay() + 6) % 7; // Monday first

  // Add empty cells for offset
  for (let i = 0; i < startOffset; i++) {
    calendarEl.appendChild(createDayElement(null, 'empty'));
  }

  // Add days of month
  const today = new Date();
  today.setHours(0, 0, 0, 0);

  for (let i = 1; i <= daysInMonth; i++) {
    const dayDate = new Date(year, month, i);
    const dateStr = dayDate.toISOString().split('T')[0];
    const isUnavailable = unavailableDates.some(range => 
      dateStr >= range.start_date && dateStr <= range.end_date
    );

    const dayEl = createDayElement(i, [
      'calendar-day',
      isUnavailable ? 'unavailable' : 'available',
      dayDate < today && !isSameDay(dayDate, today) ? 'past' : '',
      isSameDay(dayDate, today) ? 'today' : ''
    ].filter(Boolean).join(' '));

    if (!isUnavailable && dayDate >= today) {
      dayEl.dataset.date = dateStr;
      dayEl.style.cursor = 'pointer';
    }

    calendarEl.appendChild(dayEl);
  }
}

function createDayElement(dayNumber, className) {
  const el = document.createElement("div");
  el.className = className;
  if (dayNumber) el.textContent = dayNumber;
  return el;
}

function isSameDay(date1, date2) {
  return date1.getFullYear() === date2.getFullYear() &&
         date1.getMonth() === date2.getMonth() &&
         date1.getDate() === date2.getDate();
}

function changeMonth(offset) {
  currentDate.setMonth(currentDate.getMonth() + offset);
  renderCalendar(currentDate);
}

function goToToday() {
  currentDate = new Date();
  renderCalendar(currentDate);
}

// Booking functionality
function initializeBookingFunctionality(toolId, lang) {
  const translations = {
    en: {
      select_dates: "Please select start and end dates",
      date_range: "{0} - {1} ({2} days)",
      added_to_cart: "Added to cart successfully"
    },
    cs: {
      select_dates: "Prosím vyberte datum začátku a konce",
      date_range: "{0} - {1} ({2} dní)",
      added_to_cart: "Úspěšně přidáno do košíku"
    }
  };

  const startDateInput = document.getElementById('start-date');
  const endDateInput = document.getElementById('end-date');
  const addToCartBtn = document.getElementById('add-to-cart');
  const selectedDatesInfo = document.getElementById('selected-dates-info');
  const bookingAlert = document.getElementById('booking-alert');

  let selectedStartDate = null;
  let selectedEndDate = null;

  // Event delegation for calendar clicks
  document.getElementById('calendar')?.addEventListener('click', e => {
    if (!e.target.classList.contains('calendar-day') || 
        !e.target.classList.contains('available') ||
        e.target.classList.contains('unavailable')) {
      return;
    }

    const clickedDate = e.target.dataset.date;
    if (!selectedStartDate || (selectedStartDate && selectedEndDate)) {
      // New selection
      selectedStartDate = clickedDate;
      selectedEndDate = null;
    } else if (new Date(clickedDate) >= new Date(selectedStartDate)) {
      // Set end date
      selectedEndDate = clickedDate;
    } else {
      // Swap dates if clicked date is before start date
      selectedEndDate = selectedStartDate;
      selectedStartDate = clickedDate;
    }

    updateDateSelection();
    updateCalendarHighlight();
  });

  function updateDateSelection() {
    if (startDateInput) startDateInput.value = selectedStartDate || '';
    if (endDateInput) endDateInput.value = selectedEndDate || '';
    
    if (selectedStartDate && selectedEndDate) {
      const start = new Date(selectedStartDate);
      const end = new Date(selectedEndDate);
      const days = Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1;
      
      if (selectedDatesInfo) {
        selectedDatesInfo.textContent = translations[lang].date_range
          .replace('{0}', formatDate(start, lang))
          .replace('{1}', formatDate(end, lang))
          .replace('{2}', days);
        selectedDatesInfo.style.display = 'block';
      }
      
      if (addToCartBtn) {
        addToCartBtn.disabled = false;
      }
    } else {
      if (selectedDatesInfo) selectedDatesInfo.style.display = 'none';
      if (addToCartBtn) addToCartBtn.disabled = true;
    }
  }

  function updateCalendarHighlight() {
    document.querySelectorAll('.calendar-day').forEach(day => {
      day.classList.remove('selected', 'range-start', 'range-end', 'range-middle');
    });

    if (selectedStartDate && selectedEndDate) {
      const start = new Date(selectedStartDate);
      const end = new Date(selectedEndDate);
      
      document.querySelectorAll('.calendar-day').forEach(day => {
        if (!day.dataset.date) return;
        
        const dayDate = new Date(day.dataset.date);
        if (dayDate >= start && dayDate <= end) {
          const dayClass = isSameDay(dayDate, start) ? 'range-start' :
                          isSameDay(dayDate, end) ? 'range-end' : 'range-middle';
          day.classList.add(dayClass);
        }
      });
    }
  }

  if (addToCartBtn) {
    addToCartBtn.addEventListener('click', async () => {
      if (!selectedStartDate || !selectedEndDate) {
        showAlert('error', translations[lang].select_dates);
        return;
      }

      try {
        const response = await fetch('/api/cart', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            toolId,
            startDate: selectedStartDate,
            endDate: selectedEndDate
          })
        });

        if (!response.ok) throw new Error('Failed to add to cart');
        
        showAlert('success', translations[lang].added_to_cart);
        updateCartCount(await response.json().then(data => data.cartCount));
      } catch (error) {
        console.error('Error:', error);
        showAlert('error', error.message);
      }
    });
  }

  function showAlert(type, message) {
    if (!bookingAlert) return;
    bookingAlert.className = `alert ${type}`;
    bookingAlert.textContent = message;
    bookingAlert.style.display = 'block';
    setTimeout(() => bookingAlert.style.display = 'none', 5000);
  }

  function updateCartCount(count) {
    const cartCountEl = document.querySelector('.cart-count');
    if (cartCountEl) {
      cartCountEl.textContent = count;
    }
  }

  function formatDate(date, lang) {
    return date.toLocaleDateString(lang === 'cs' ? 'cs-CZ' : 'en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    });
  }
}

// Login/Register form toggle function
function toggleForm(action) {
  const loginForm = document.getElementById('login-form');
  const registerForm = document.getElementById('register-form');
  const loginButton = document.querySelector('.form-toggle button:nth-child(1)');
  const registerButton = document.querySelector('.form-toggle button:nth-child(2)');

  if (action === 'login') {
    loginForm.classList.remove('form-hidden');
    registerForm.classList.add('form-hidden');
    loginButton.classList.add('active');
    registerButton.classList.remove('active');
  } else {
    loginForm.classList.add('form-hidden');
    registerForm.classList.remove('form-hidden');
    loginButton.classList.remove('active');
    registerButton.classList.add('active');
  }
}

// Initialize login/register form toggle on page load
document.addEventListener('DOMContentLoaded', function() {
  const loginForm = document.getElementById('login-form');
  if (loginForm) {
    if (!loginForm.classList.contains('form-hidden')) {
      const loginBtn = document.querySelector('.form-toggle button:nth-child(1)');
      if (loginBtn) loginBtn.classList.add('active');
    } else {
      const registerBtn = document.querySelector('.form-toggle button:nth-child(2)');
      if (registerBtn) registerBtn.classList.add('active');
    }
  }
});

// Tool availability page - date selection functionality
function initializeToolAvailabilityPage(toolId, lang, unavailableRanges, csrfToken) {
  let selectedStartDate = null;
  let selectedEndDate = null;

  const calendar = document.getElementById('calendar');
  const startDateInput = document.getElementById('start-date');
  const endDateInput = document.getElementById('end-date');
  const addToCartBtn = document.getElementById('add-to-cart-btn');
  const dateSelectionInfo = document.getElementById('date-selection-info');
  const selectedDatesText = document.getElementById('selected-dates-text');
  const bookingForm = document.getElementById('booking-form');
  const bookingAlert = document.getElementById('booking-alert');

  if (!calendar) return;

  // Handle calendar day clicks
  calendar.addEventListener('click', function(e) {
    const dayEl = e.target;
    
    if (!dayEl.classList.contains('calendar-day') || 
        !dayEl.classList.contains('available') ||
        dayEl.classList.contains('unavailable') ||
        dayEl.classList.contains('past')) {
      return;
    }

    const clickedDate = dayEl.dataset.date;
    if (!clickedDate) return;

    if (!selectedStartDate || (selectedStartDate && selectedEndDate)) {
      // Start new selection
      selectedStartDate = clickedDate;
      selectedEndDate = null;
    } else {
      // Set end date
      const clickedDateTime = new Date(clickedDate);
      const startDateTime = new Date(selectedStartDate);
      
      if (clickedDateTime >= startDateTime) {
        selectedEndDate = clickedDate;
      } else {
        // Swap if clicked before start
        selectedEndDate = selectedStartDate;
        selectedStartDate = clickedDate;
      }
    }

    updateDateSelection();
    updateCalendarHighlight();
  });

  function updateDateSelection() {
    if (startDateInput) startDateInput.value = selectedStartDate || '';
    if (endDateInput) endDateInput.value = selectedEndDate || '';

    if (selectedStartDate && selectedEndDate) {
      const start = new Date(selectedStartDate);
      const end = new Date(selectedEndDate);
      const days = Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1;
      
      const startFormatted = formatDate(start, lang);
      const endFormatted = formatDate(end, lang);
      
      if (selectedDatesText) {
        selectedDatesText.textContent = lang === 'cs' 
          ? `${startFormatted} - ${endFormatted} (${days} ${days === 1 ? 'den' : days < 5 ? 'dny' : 'dní'})`
          : `${startFormatted} - ${endFormatted} (${days} ${days === 1 ? 'day' : 'days'})`;
      }
      
      if (dateSelectionInfo) dateSelectionInfo.classList.add('active');
      if (addToCartBtn) addToCartBtn.disabled = false;
    } else {
      if (dateSelectionInfo) dateSelectionInfo.classList.remove('active');
      if (addToCartBtn) addToCartBtn.disabled = true;
    }
  }

  function updateCalendarHighlight() {
    // Remove all selection classes
    document.querySelectorAll('.calendar-day').forEach(day => {
      day.classList.remove('range-start', 'range-end', 'range-middle');
    });

    if (selectedStartDate && selectedEndDate) {
      const start = new Date(selectedStartDate);
      const end = new Date(selectedEndDate);
      
      document.querySelectorAll('.calendar-day').forEach(day => {
        if (!day.dataset.date) return;
        
        const dayDate = new Date(day.dataset.date);
        if (dayDate >= start && dayDate <= end) {
          if (dayDate.getTime() === start.getTime()) {
            day.classList.add('range-start');
          } else if (dayDate.getTime() === end.getTime()) {
            day.classList.add('range-end');
          } else {
            day.classList.add('range-middle');
          }
        }
      });
    }
  }

  // Handle form submission
  if (bookingForm) {
    bookingForm.addEventListener('submit', async function(e) {
      e.preventDefault();

      if (!selectedStartDate || !selectedEndDate) {
        showAlert('error', lang === 'cs' ? 'Vyberte prosím datum začátku a konce.' : 'Please select start and end dates.');
        return;
      }

      const formData = new FormData(bookingForm);

      try {
        const response = await fetch(window.location.href, {
          method: 'POST',
          body: formData
        });

        const result = await response.json();

        if (result.success) {
          showAlert('success', result.message);
          
          // Update cart count
          const cartCountEl = document.querySelector('.cart-count');
          if (cartCountEl) {
            cartCountEl.textContent = result.cart_count;
          } else if (result.cart_count > 0) {
            // Create cart count badge if it doesn't exist
            const cartLink = document.querySelector('.cart-link');
            if (cartLink) {
              const badge = document.createElement('span');
              badge.className = 'cart-count';
              badge.textContent = result.cart_count;
              cartLink.appendChild(badge);
            }
          }
          
          // Reset selection
          selectedStartDate = null;
          selectedEndDate = null;
          updateDateSelection();
          updateCalendarHighlight();
        } else {
          showAlert('error', result.message);
        }
      } catch (error) {
        console.error('Error:', error);
        showAlert('error', lang === 'cs' ? 'Došlo k chybě. Zkuste to prosím znovu.' : 'An error occurred. Please try again.');
      }
    });
  }

  function showAlert(type, message) {
    if (!bookingAlert) return;
    bookingAlert.className = `alert ${type}`;
    bookingAlert.textContent = message;
    bookingAlert.style.display = 'block';
    setTimeout(() => {
      bookingAlert.style.display = 'none';
    }, 5000);
  }

  function formatDate(date, lang) {
    return date.toLocaleDateString(lang === 'cs' ? 'cs-CZ' : 'en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    });
  }
}

// My Orders page - cancel order function
function cancelOrder(availabilityId, lang) {
  const confirmMessage = lang === 'cs' 
    ? 'Opravdu chcete zrušit tuto objednávku? Tato akce je nevratná.' 
    : 'Are you sure you want to cancel this order? This action cannot be undone.';
    
  if (confirm(confirmMessage)) {
    // Show loading state (optional - you could add a loading indicator)
    const button = event.target;
    const originalText = button.textContent;
    button.disabled = true;
    button.textContent = lang === 'cs' ? 'Ruším...' : 'Cancelling...';
    
    fetch('cancel_order.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: `availability_id=${availabilityId}&lang=${lang}`
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        alert(data.message);
        location.reload();
      } else {
        alert(data.message);
        // Re-enable button on error
        button.disabled = false;
        button.textContent = originalText;
      }
    })
    .catch(error => {
      console.error('Error:', error);
      const errorMessage = lang === 'cs' 
        ? 'Chyba při komunikaci se serverem. Zkuste to prosím znovu.' 
        : 'Error communicating with server. Please try again.';
      alert(errorMessage);
      // Re-enable button on error
      button.disabled = false;
      button.textContent = originalText;
    });
  }
}

// Legacy function for backwards compatibility
function cancelReservation(availabilityId, lang) {
  cancelOrder(availabilityId, lang);
}

// Export functions to window
window.switchLanguage = switchLanguage;
window.changeMonth = changeMonth;
window.goToToday = goToToday;
window.toggleForm = toggleForm;
window.initializeToolAvailabilityPage = initializeToolAvailabilityPage;
window.cancelOrder = cancelOrder;
window.cancelReservation = cancelReservation;
