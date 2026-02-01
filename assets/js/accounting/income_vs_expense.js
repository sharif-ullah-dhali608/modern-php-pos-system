$(document).ready(function () {
    // --- CALENDAR LOGIC ---
    let calendarCurrentDate = new Date();
    let calendarStartDate = null;
    let calendarEndDate = null;

    // Initialize from URL if present
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('start_date')) {
        calendarStartDate = new Date(urlParams.get('start_date'));
        // If end_date is missing, maybe just start date?
    }
    if (urlParams.has('end_date')) {
        calendarEndDate = new Date(urlParams.get('end_date'));
    }
    // Set current view to start date if exists
    if (calendarStartDate) {
        calendarCurrentDate = new Date(calendarStartDate);
    }

    window.toggleCustomCalendar = function () {
        $('#custom-date-picker-modal').toggleClass('hidden');
        if (!$('#custom-date-picker-modal').hasClass('hidden')) {
            renderCustomCalendar();
        }
    }

    // Bind Button Click
    $('#date-range-picker-btn').on('click', function (e) {
        e.stopPropagation();
        toggleCustomCalendar();
    });

    window.changeCalMonth = function (offset) {
        calendarCurrentDate.setMonth(calendarCurrentDate.getMonth() + offset);
        renderCustomCalendar();
    }

    window.renderCustomCalendar = function () {
        const year = calendarCurrentDate.getFullYear();
        const month = calendarCurrentDate.getMonth();

        // Update Header
        const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
        $('#cal-month-year').text(`${monthNames[month]} ${year}`);

        // Generate Grid
        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        const daysInMonth = lastDay.getDate();
        const startDayIndex = firstDay.getDay(); // 0 = Sunday

        const grid = $('#calendar-grid');
        grid.empty();

        // Empty cells for previous month
        for (let i = 0; i < startDayIndex; i++) {
            grid.append('<div class="h-8"></div>');
        }

        // Days
        for (let d = 1; d <= daysInMonth; d++) {
            const dateObj = new Date(year, month, d);
            const dateStr = formatDate(dateObj); // YYYY-MM-DD

            let classes = "h-8 flex items-center justify-center text-xs font-bold rounded cursor-pointer hover:bg-slate-100 text-slate-700 transition-colors";

            // Highlight Selection
            if (calendarStartDate && calendarEndDate) {
                if (dateObj >= calendarStartDate && dateObj <= calendarEndDate) {
                    classes += " bg-teal-100 text-teal-700 hover:bg-teal-200";
                }
                if (isSameDate(dateObj, calendarStartDate) || isSameDate(dateObj, calendarEndDate)) {
                    classes += " bg-teal-600 text-white hover:bg-teal-700";
                }
            } else if (calendarStartDate && isSameDate(dateObj, calendarStartDate)) {
                classes += " bg-teal-600 text-white hover:bg-teal-700";
            }

            const dayEl = $(`<div class="${classes}">${d}</div>`);
            dayEl.on('click', () => selectCalDate(dateObj));
            grid.append(dayEl);
        }

        // Update Display Text
        if (calendarStartDate && calendarEndDate) {
            $('#cal-selected-display').text(`${formatDisplayDate(calendarStartDate)} - ${formatDisplayDate(calendarEndDate)}`);
        } else if (calendarStartDate) {
            $('#cal-selected-display').text(`${formatDisplayDate(calendarStartDate)} - Select End Date`);
        } else {
            $('#cal-selected-display').text('Select date range');
        }
    }

    window.selectCalDate = function (date) {
        if (!calendarStartDate || (calendarStartDate && calendarEndDate)) {
            // Start new selection
            calendarStartDate = date;
            calendarEndDate = null;
        } else {
            // End selection
            if (date < calendarStartDate) {
                calendarEndDate = calendarStartDate;
                calendarStartDate = date;
            } else {
                calendarEndDate = date;
            }
        }
        renderCustomCalendar();
    }

    window.applyDateRange = function () {
        if (calendarStartDate && calendarEndDate) {
            const startStr = formatDate(calendarStartDate);
            const endStr = formatDate(calendarEndDate);

            // Update URL
            const url = new URL(window.location.href);
            url.searchParams.set('start_date', startStr);
            url.searchParams.set('end_date', endStr);
            window.location.href = url.toString();
        }
    }

    // Helpers
    function formatDate(date) {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    }

    function formatDisplayDate(date) {
        const monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
        const d = String(date.getDate()).padStart(2, '0');
        return `${d} ${monthNames[date.getMonth()]} ${date.getFullYear()}`;
    }

    function isSameDate(d1, d2) {
        return d1.getFullYear() === d2.getFullYear() &&
            d1.getMonth() === d2.getMonth() &&
            d1.getDate() === d2.getDate();
    }
});
