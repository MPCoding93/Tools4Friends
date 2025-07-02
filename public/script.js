// Global JavaScript functions for Tools4Friends website

// Set current year in footer
document.addEventListener("DOMContentLoaded", function() {
  const yearElement = document.getElementById("year");
  if (yearElement) {
    yearElement.textContent = new Date().getFullYear();
  }
  
  // Initialize language on page load
  initializeLanguage();
  
  // Update navigation links immediately after language initialization
  updateNavigationLinks(); 
});

// Initialize language based on URL parameter or default to English
function initializeLanguage() {
  const urlParams = new URLSearchParams(window.location.search);
  const langFromUrl = urlParams.get('lang');
  
  if (langFromUrl && (langFromUrl === 'en' || langFromUrl === 'cs')) {
    setLanguage(langFromUrl);
    updateLanguageButtons(langFromUrl);
  } else {
    // Default to English and update URL
    setLanguage('en');
    updateLanguageButtons('en');
    updateUrlWithLanguage('en');
  }
}

// Enhanced language switching functionality
function setLanguage(lang) {
  document.querySelectorAll("[data-en]").forEach(el => {
    const text = el.getAttribute(`data-${lang}`);
    if (text) {
      el.textContent = text;
    }
  });
  
  // Update HTML lang attribute
  document.documentElement.lang = lang;
}

// Update language button states
function updateLanguageButtons(activeLang) {
  const buttons = document.querySelectorAll('.language-toggle button');
  buttons.forEach(button => {
    button.classList.remove('active');
    if ((activeLang === 'en' && button.textContent === 'English') ||
        (activeLang === 'cs' && button.textContent === 'Čeština')) {
      button.classList.add('active');
    }
  });
}

// Update URL with language parameter without page reload
function updateUrlWithLanguage(lang) {
  const url = new URL(window.location);
  url.searchParams.set('lang', lang);
  window.history.replaceState({}, '', url);
}

// Enhanced language switching with URL parameters
function switchLanguage(lang, page = null) {
  const currentUrl = new URL(window.location.href);
  const currentParams = new URLSearchParams(currentUrl.search);

  // Update or add language parameter
  currentParams.set("lang", lang);

  // If specific page is provided, navigate to that page
  if (page) {
    // Construct the new URL with the correct path and parameters
    const newPath = page.startsWith('/') ? page : `/${page}`; // Ensure path starts with / if it's not already absolute
    window.location.href = `${currentUrl.origin}${newPath}?${currentParams.toString()}`;
  } else {
    // For static HTML files, update current page URL and apply language
    const newUrl = `${currentUrl.pathname}?${currentParams.toString()}`;
    window.history.replaceState({}, '', newUrl);
    setLanguage(lang);
    updateLanguageButtons(lang);
  }
}

// Smart language switching that works for both static and PHP files
// This function is no longer needed as switchLanguage handles both cases.
// If you still need it for some reason, consider its purpose carefully.
function smartLanguageSwitch(lang) {
  switchLanguage(lang, window.location.pathname.split('/').pop());
}

// Update navigation links to preserve language
function updateNavigationLinks() {
  const urlParams = new URLSearchParams(window.location.search);
  const currentLang = urlParams.get('lang') || 'en';
  
  // Update all navigation links to include current language
  document.querySelectorAll('nav a').forEach(link => {
    const href = link.getAttribute('href'); // Get the original href
    if (href && !href.startsWith('#') && !href.startsWith('mailto:') && !href.startsWith('javascript:')) { // Exclude javascript: links
      const url = new URL(href, window.location.origin);
      url.searchParams.set('lang', currentLang);
      link.href = url.toString();
    }
  });
}

// Calendar functionality for tool availability
let currentDate = new Date();
let unavailableRanges = [];

// Initialize calendar with data from PHP
function initializeCalendar(ranges) {
  unavailableRanges = ranges || [];
  renderCalendar(currentDate);
}

function renderCalendar(date) {
  const calendar = document.getElementById("calendar");
  const monthLabel = document.getElementById("calendar-month");

  if (!calendar || !monthLabel) return;

  calendar.innerHTML = "";

  const year = date.getFullYear();
  const month = date.getMonth();
  const firstDay = new Date(year, month, 1);
  const lastDay = new Date(year, month + 1, 0);
  const startDay = (firstDay.getDay() + 6) % 7; // Convert to Monday start

  monthLabel.textContent = date.toLocaleString("default", {
    month: "long",
    year: "numeric"
  });

  // Add day headers (Monday first)
  const dayHeaders = ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"];
  dayHeaders.forEach(day => {
    const header = document.createElement("div");
    header.className = "calendar-header";
    header.textContent = day;
    calendar.appendChild(header);
  });

  // Add empty cells for days before the first day of the month
  for (let i = 0; i < startDay; i++) {
    const emptyCell = document.createElement("div");
    emptyCell.className = "calendar-day empty";
    calendar.appendChild(emptyCell);
  }

  // Add days of the month
  for (let day = 1; day <= lastDay.getDate(); day++) {
    const cell = document.createElement("div");
    cell.className = "calendar-day";
    const cellDate = new Date(year, month, day);
    const cellDateStr = cellDate.toISOString().split("T")[0];

    // Check if this date is unavailable
    let isUnavailable = false;
    for (const range of unavailableRanges) {
      if (cellDateStr >= range.start_date && cellDateStr <= range.end_date) {
        cell.classList.add("unavailable");
        isUnavailable = true;
        break;
      }
    }

    if (!isUnavailable) {
      cell.classList.add("available");
    }

    // Add today highlight
    const today = new Date();
    if (cellDate.toDateString() === today.toDateString()) {
      cell.classList.add("today");
    }

    // Add past date styling
    if (cellDate < today && cellDate.toDateString() !== today.toDateString()) {
      cell.classList.add("past");
    }

    cell.textContent = day;

    // Add click functionality for future available dates
    if (!isUnavailable && cellDate >= today) {
      cell.style.cursor = "pointer";
      cell.addEventListener("click", function(event) { // Pass event to selectDate
        selectDate(cellDate, event); // Pass event object
      });
    }

    calendar.appendChild(cell);
  }
}

// Function to change month in the calendar
function changeMonth(offset) {
  currentDate.setMonth(currentDate.getMonth() + offset);
  renderCalendar(currentDate);
}

function selectDate(date, event) { // Receive event object
  // Remove previous selections
  document.querySelectorAll(".calendar-day.selected").forEach(cell => {
    cell.classList.remove("selected");
  });

  // Add selection to clicked date
  event.target.classList.add("selected");
  event.stopPropagation(); // Prevent potential issues if nested

  // You can add functionality here to handle date selection
  console.log("Selected date:", date.toISOString().split("T")[0]);

  // Example: Show a confirmation or booking form
  // showBookingInfo(date); // Commented out to prevent intrusive alerts
}

// Placeholder for booking information display
function showBookingInfo(date) {
  // This function can be expanded to show booking information
  const formattedDate = date.toLocaleDateString();
  alert(
    `You selected ${formattedDate}. Booking functionality can be implemented here.`
  );
}

// Function to navigate to today's date in the calendar
// Utility function to go to today
function goToToday() {
  currentDate = new Date();
  renderCalendar(currentDate);
}

// Enhanced navigation with year/month selection
function goToMonth(year, month) {
  currentDate = new Date(year, month, 1);
  renderCalendar(currentDate);
}

// Initialize everything when DOM is loaded (already handled by the main DOMContentLoaded listener at the top)

// Export functions for global use
window.setLanguage = setLanguage;
window.switchLanguage = switchLanguage;
window.smartLanguageSwitch = smartLanguageSwitch;
window.changeMonth = changeMonth;
window.initializeCalendar = initializeCalendar;
window.goToToday = goToToday;
window.goToMonth = goToMonth;

// CSS for active language button (inject into head)
const style = document.createElement('style');
style.textContent = `
  .nav-right button.active {
    background: linear-gradient(135deg, #4a90e2 0%, #1F2D5A 100%) !important;
    color: white !important;
    box-shadow: 0 4px 15px rgba(74, 144, 226, 0.4) !important;
  }
`;
document.head.appendChild(style);

// Initialize calendar when page loads
document.addEventListener('DOMContentLoaded', function () {
    const unavailableRanges = <?php echo json_encode($unavailable_ranges); ?>;
    const lang = '<?php echo $lang; ?>';
    const toolId = <?php echo $tool_id; ?>;
    
    initializeCalendar(unavailableRanges, lang);
    initializeBookingFunctionality(toolId, lang);
});

// Booking functionality
function initializeBookingFunctionality(toolId, lang) {
    const startDateInput = document.getElementById('start-date');
    const endDateInput = document.getElementById('end-date');
    const addToCartBtn = document.getElementById('add-to-cart');
    const selectedDatesInfo = document.getElementById('selected-dates-info');
    const selectedPeriod = document.getElementById('selected-period');
    const bookingAlert = document.getElementById('booking-alert');

    let selectedStartDate = null;
    let selectedEndDate = null;
    let isSelectingRange = false;

    // Handle date input changes
    startDateInput.addEventListener('change', function() {
        selectedStartDate = this.value;
        updateDateSelection();
        updateCalendarHighlight();
    });

    endDateInput.addEventListener('change', function() {
        selectedEndDate = this.value;
        updateDateSelection();
        updateCalendarHighlight();
    });

    // Handle calendar clicks for date selection
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('day') && e.target.classList.contains('available')) {
            const clickedDate = e.target.dataset.date;
            
            if (!selectedStartDate || (selectedStartDate && selectedEndDate)) {
                // Start new selection
                selectedStartDate = clickedDate;
                selectedEndDate = null;
                startDateInput.value = clickedDate;
                endDateInput.value = '';
                isSelectingRange = true;
            } else if (selectedStartDate && !selectedEndDate) {
                // Set end date
                if (clickedDate >= selectedStartDate) {
                    selectedEndDate = clickedDate;
                    endDateInput.value = clickedDate;
                } else {
                    // If clicked date is before start date, make it the new start date
                    selectedEndDate = selectedStartDate;
                    selectedStartDate = clickedDate;
                    startDateInput.value = clickedDate;
                    endDateInput.value = selectedEndDate;
                }
                isSelectingRange = false;
            }
            
            updateDateSelection();
            updateCalendarHighlight();
        }
    });

    // Add to cart functionality
    addToCartBtn.addEventListener('click', function() {
        if (!selectedStartDate || !selectedEndDate) {
            showAlert('error', lang === 'cs' ? 'Vyberte prosím datum začátku a konce.' : 'Please select start and end dates.');
            return;
        }

        const formData = new FormData();
        formData.append('action', 'add_to_cart');
        formData.append('start_date', selectedStartDate);
        formData.append('end_date', selectedEndDate);

        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('success', data.message);
                // Update cart count
                updateCartCount(data.cart_count);
                // Clear selection
                clearSelection();
            } else {
                showAlert('error', data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('error', lang === 'cs' ? 'Došlo k chybě při přidávání do košíku.' : 'An error occurred while adding to cart.');
        });
    });

    function updateDateSelection() {
        if (selectedStartDate && selectedEndDate) {
            const start = new Date(selectedStartDate);
            const end = new Date(selectedEndDate);
            const days = Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1;
            
            selectedPeriod.textContent = `${formatDate(start, lang)} - ${formatDate(end, lang)} (${days} ${lang === 'cs' ? 'dní' : 'days'})`;
            selectedDatesInfo.style.display = 'block';
            addToCartBtn.disabled = false;
        } else {
            selectedDatesInfo.style.display = 'none';
            addToCartBtn.disabled = true;
        }
    }

    function updateCalendarHighlight() {
        // Remove existing highlights
        document.querySelectorAll('.day').forEach(day => {
            day.classList.remove('range-start', 'range-end', 'range-middle');
        });

        if (selectedStartDate && selectedEndDate) {
            const start = new Date(selectedStartDate);
            const end = new Date(selectedEndDate);
            
            document.querySelectorAll('.day').forEach(day => {
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

    function clearSelection() {
        selectedStartDate = null;
        selectedEndDate = null;
        startDateInput.value = '';
        endDateInput.value = '';
        selectedDatesInfo.style.display = 'none';
        addToCartBtn.disabled = true;
        updateCalendarHighlight();
    }

    function showAlert(type, message) {
        bookingAlert.className = `alert ${type}`;
        bookingAlert.textContent = message;
        bookingAlert.style.display = 'block';
        setTimeout(() => {
            bookingAlert.style.display = 'none';
        }, 5000);
    }

    function updateCartCount(count) {
        const cartCountElement = document.querySelector('.cart-count');
        if (cartCountElement) {
            cartCountElement.textContent = count;
        } else if (count > 0) {
            const cartLink = document.querySelector('.cart-link');
            cartLink.innerHTML += `<span class="cart-count">${count}</span>`;
        }
    }

    function formatDate(date, lang) {
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        return date.toLocaleDateString(lang === 'cs' ? 'cs-CZ' : 'en-US', options);
    }
}

