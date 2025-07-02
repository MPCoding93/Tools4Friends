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
  updateNavigationLinks();
  
  // Initialize calendar and booking functionality
  if (typeof unavailableRanges !== 'undefined') {
    initializeCalendar(unavailableRanges);
  }
  if (typeof toolId !== 'undefined') {
    initializeBookingFunctionality(toolId, document.documentElement.lang || 'en');
  }
});

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

// Export functions to window
window.switchLanguage = switchLanguage;
window.changeMonth = changeMonth;
window.goToToday = goToToday;
