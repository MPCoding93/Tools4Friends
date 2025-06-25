// Global JavaScript functions for Tools4Friends website

// Set current year in footer
document.addEventListener("DOMContentLoaded", function() {
  const yearElement = document.getElementById("year");
  if (yearElement) {
    yearElement.textContent = new Date().getFullYear();
  }
  
  // Initialize language on page load
  initializeLanguage();
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
    window.location.href = `${page}?${currentParams.toString()}`;
  } else {
    // For static HTML files, update current page URL and apply language
    const newUrl = `${currentUrl.pathname}?${currentParams.toString()}`;
    window.history.replaceState({}, '', newUrl);
    setLanguage(lang);
    updateLanguageButtons(lang);
  }
}

// Smart language switching that works for both static and PHP files
function smartLanguageSwitch(lang) {
  const currentPage = window.location.pathname;
  
  // Check if it's a PHP file
  if (currentPage.includes('.php')) {
    switchLanguage(lang, currentPage.split('/').pop());
  } else {
    // Static HTML file
    switchLanguage(lang);
  }
}

// Update navigation links to preserve language
function updateNavigationLinks() {
  const urlParams = new URLSearchParams(window.location.search);
  const currentLang = urlParams.get('lang') || 'en';
  
  // Update all navigation links to include current language
  document.querySelectorAll('nav a').forEach(link => {
    const href = link.getAttribute('href');
    if (href && !href.startsWith('#') && !href.startsWith('mailto:')) {
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
      cell.addEventListener("click", function() {
        selectDate(cellDate);
      });
    }

    calendar.appendChild(cell);
  }
}

function changeMonth(offset) {
  currentDate.setMonth(currentDate.getMonth() + offset);
  renderCalendar(currentDate);
}

function selectDate(date) {
  // Remove previous selections
  document.querySelectorAll(".calendar-day.selected").forEach(cell => {
    cell.classList.remove("selected");
  });

  // Add selection to clicked date
  event.target.classList.add("selected");

  // You can add functionality here to handle date selection
  console.log("Selected date:", date.toISOString().split("T")[0]);

  // Example: Show a confirmation or booking form
  showBookingInfo(date);
}

function showBookingInfo(date) {
  // This function can be expanded to show booking information
  const formattedDate = date.toLocaleDateString();
  alert(
    `You selected ${formattedDate}. Booking functionality can be implemented here.`
  );
}

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

// Initialize everything when DOM is loaded
document.addEventListener("DOMContentLoaded", function() {
  // Update navigation links to preserve language
  setTimeout(updateNavigationLinks, 100);
});

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
