
        function setLanguage(lang) {
            document.querySelectorAll("[data-en]").forEach((el) => {
                el.textContent = el.getAttribute(`data-${lang}`);
            });
        }
    
            const unavailableRanges = <?php echo json_encode($unavailable_ranges); ?>;
            let currentDate = new Date();

            function renderCalendar(date) {
                const calendar = document.getElementById('calendar');
                const monthLabel = document.getElementById('calendar-month');
                calendar.innerHTML = '';

                const year = date.getFullYear();
                const month = date.getMonth();
                const firstDay = new Date(year, month, 1);
                const lastDay = new Date(year, month + 1, 0);
                const startDay = firstDay.getDay();

                monthLabel.textContent = date.toLocaleString('default', { month: 'long', year: 'numeric' });

                for (let i = 0; i < startDay; i++) {
                    const emptyCell = document.createElement('div');
                    calendar.appendChild(emptyCell);
                }

                for (let day = 1; day <= lastDay.getDate(); day++) {
                    const cell = document.createElement('div');
                    cell.className = 'calendar-day';
                    const cellDate = new Date(year, month, day);
                    const cellDateStr = cellDate.toISOString().split('T')[0];

                    for (const range of unavailableRanges) {
                        if (cellDateStr >= range.start_date && cellDateStr <= range.end_date) {
                            cell.classList.add('unavailable');
                            break;
                        }
                    }

                    cell.textContent = day;
                    calendar.appendChild(cell);
                }
            }

            function changeMonth(offset) {
                currentDate.setMonth(currentDate.getMonth() + offset);
                renderCalendar(currentDate);
            }

            renderCalendar(currentDate);
        
