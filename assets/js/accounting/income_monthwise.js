
// Income Monthwise Report JS

document.addEventListener('DOMContentLoaded', function () {
    // Initialize Chart
    const ctx = document.getElementById('expenditureChart'); // ID kept same as HTML/PHP logic reused
    const ctxInitial = document.getElementById('expenditureChartInitial');

    // Initial Data
    const rawData = document.getElementById('chart-data-provider').value;
    const chartData = JSON.parse(rawData);

    const chartConfig = {
        type: 'line',
        data: {
            labels: chartData.labels,
            datasets: [{
                label: 'Total Income',
                data: chartData.data,
                borderColor: '#0d9488', // Teal-600
                backgroundColor: 'rgba(13, 148, 136, 0.1)',
                borderWidth: 3,
                tension: 0.4,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#0d9488',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.9)',
                    padding: 12,
                    titleFont: { size: 13 },
                    bodyFont: { size: 13, weight: 'bold' },
                    displayColors: false,
                    callbacks: {
                        label: function (context) {
                            return ' ' + window.currencySymbol + ' ' + context.parsed.y.toLocaleString();
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)',
                        borderDash: [5, 5]
                    },
                    ticks: {
                        font: { size: 11 },
                        color: '#64748b'
                    },
                    border: { display: false }
                },
                x: {
                    grid: { display: false },
                    ticks: {
                        font: { size: 11 },
                        color: '#64748b'
                    },
                    border: { display: false }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index'
            }
        }
    };

    if (ctx) new Chart(ctx, chartConfig);
    if (ctxInitial) new Chart(ctxInitial, chartConfig);

    // Initial Select2
    // $('#store-select').select2({ minimumResultsForSearch: Infinity }); // Removed store-select select2
    $('#month-select').select2({ minimumResultsForSearch: Infinity });
    $('#year-select').select2({ minimumResultsForSearch: Infinity });

    // --- Custom Store Selector Logic ---
    // Use delegation for inputs/dropdowns since they might be replaced by AJAX
    $(document).on('focus', '#store_search_input', function () {
        $('#store_dropdown').removeClass('hidden');
    });

    $(document).on('click', function (e) {
        if (!$(e.target).closest('#store_selector_container').length) {
            $('#store_dropdown').addClass('hidden');
        }
    });

    $(document).on('input', '#store_search_input', function () {
        const val = $(this).val().toLowerCase();
        let hasMatch = false;
        $('.store-option').each(function () {
            const name = $(this).data('name').toLowerCase();
            if (name.includes(val)) {
                $(this).removeClass('hidden');
                hasMatch = true;
            } else {
                $(this).addClass('hidden');
            }
        });
        if (!hasMatch) {
            if ($('.no-match').length === 0) {
                $('#store_results_container').append('<div class="no-match p-2 text-center text-xs text-slate-400 font-bold">No match found</div>');
            }
        } else {
            $('.no-match').remove();
        }
    });

    $(document).on('click', '.store-option', function () {
        const id = $(this).data('id');
        const name = $(this).data('name');

        $('#store-select').val(id);
        $('#store_search_input').val(name);
        $('#store_dropdown').addClass('hidden');

        updateReport(); // Trigger report update
    });

    // Event Listeners
    // $('#store-select').on('change', function () { updateReport(); }); // Managed manually
    $('#month-select').on('change', function () { updateReport(); });
    $('#year-select').on('change', function () { updateReport(); });
});

// Custom Calendar Logic
const urlParams = new URLSearchParams(window.location.search);
let calendarCurrentDate = urlParams.has('start_date') ? new Date(urlParams.get('start_date')) : new Date();
let calendarStartDate = urlParams.get('start_date') || null;
let calendarEndDate = urlParams.get('end_date') || null;

window.toggleCustomCalendar = function () {
    const modal = $('#custom-date-picker-modal');
    modal.toggleClass('hidden');

    if (!modal.hasClass('hidden')) {
        // Initialize if first open
        renderCustomCalendar();
        populateCalYearSelect();
    }
}

// Close calendar when clicking outside
$(document).on('click', function (e) {
    if (!$(e.target).closest('#custom-date-picker-modal').length && !$(e.target).closest('[onclick="toggleCustomCalendar()"]').length) {
        $('#custom-date-picker-modal').addClass('hidden');
    }
});

window.changeCalMonth = function (direction) {
    calendarCurrentDate.setMonth(calendarCurrentDate.getMonth() + direction);
    renderCustomCalendar();
}

window.toggleCalYearMonth = function () {
    $('#cal-year-month-selector').toggleClass('hidden');
}

window.populateCalYearSelect = function () {
    const yearSelect = $('#cal-year-select');
    const currentYear = new Date().getFullYear();
    yearSelect.empty();

    for (let year = currentYear - 10; year <= currentYear + 10; year++) {
        let option = $('<option>', { value: year, text: year });
        if (year === calendarCurrentDate.getFullYear()) option.attr('selected', 'selected');
        yearSelect.append(option);
    }
    $('#cal-month-select').val(calendarCurrentDate.getMonth());
}

window.applyCalYearMonth = function () {
    const year = parseInt($('#cal-year-select').val());
    const month = parseInt($('#cal-month-select').val());
    calendarCurrentDate = new Date(year, month, 1);
    renderCustomCalendar();
    $('#cal-year-month-selector').addClass('hidden');
}

window.renderCustomCalendar = function () {
    const year = calendarCurrentDate.getFullYear();
    const month = calendarCurrentDate.getMonth();

    const monthNames = ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'];
    $('#cal-month-display').text(`${monthNames[month]} ${year}`);

    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();

    const grid = $('#cal-grid');
    grid.empty();

    // Empty cells
    for (let i = 0; i < firstDay; i++) {
        grid.append('<div></div>');
    }

    // Days
    for (let day = 1; day <= daysInMonth; day++) {
        const currentDate = new Date(year, month, day);
        const dateStr = formatCalDate(currentDate);

        let btnClass = "w-8 h-8 flex items-center justify-center text-sm rounded-full transition-colors relative z-10 ";
        let cellClass = "flex items-center justify-center w-full relative h-8 ";
        let bgStyle = "";

        // Selection Logic
        if (calendarStartDate && calendarEndDate) {
            const start = new Date(calendarStartDate);
            const end = new Date(calendarEndDate);

            if (currentDate > start && currentDate < end) {
                cellClass += "bg-teal-50";
                btnClass += "text-teal-900 font-medium";
            } else if (dateStr === formatCalDate(start)) {
                cellClass += "bg-teal-50 rounded-l-full";
                btnClass += "bg-teal-600 text-white font-bold";
                bgStyle = "background: linear-gradient(to right, transparent 50%, #f0fdfa 50%);";
            } else if (dateStr === formatCalDate(end)) {
                cellClass += "bg-teal-50 rounded-r-full";
                btnClass += "bg-teal-600 text-white font-bold";
                bgStyle = "background: linear-gradient(to left, transparent 50%, #f0fdfa 50%);";
            } else {
                btnClass += "hover:bg-slate-100 text-slate-700";
            }
        } else if (calendarStartDate && dateStr === formatCalDate(new Date(calendarStartDate))) {
            btnClass += "bg-teal-600 text-white font-bold";
        } else {
            btnClass += "hover:bg-slate-100 text-slate-700";
        }

        if (calendarStartDate && calendarEndDate && calendarStartDate === calendarEndDate && dateStr === formatCalDate(new Date(calendarStartDate))) {
            bgStyle = "background: transparent;";
            cellClass = "flex items-center justify-center w-full relative h-8";
        }

        const btn = $(`<button type="button" class="${btnClass}">${day}</button>`);
        const cell = $(`<div class="${cellClass}" style="${bgStyle}"></div>`).append(btn);

        btn.click((e) => {
            e.stopPropagation();
            selectCalDate(currentDate);
        });

        grid.append(cell);
    }

    updateCalRangeDisplay();
}

window.selectCalDate = function (date) {
    const formatted = formatCalDate(date);

    if (!calendarStartDate || (calendarStartDate && calendarEndDate)) {
        calendarStartDate = formatted;
        calendarEndDate = null;
        renderCustomCalendar();
    } else {
        const start = new Date(calendarStartDate);
        if (date < start) {
            calendarEndDate = calendarStartDate;
            calendarStartDate = formatted;
        } else {
            calendarEndDate = formatted;
        }
        renderCustomCalendar();

        // Redirect after brief delay
        setTimeout(() => {
            const url = new URL(window.location.href);
            url.searchParams.set('start_date', calendarStartDate);
            url.searchParams.set('end_date', calendarEndDate);
            url.searchParams.delete('month');
            url.searchParams.delete('year');
            const store = $('#store-select').val();
            if (store) url.searchParams.set('store_id', store);

            window.location.href = url.toString();
        }, 300);
    }
}

window.updateCalRangeDisplay = function () {
    const display = $('#cal-selected-range');
    if (calendarStartDate && calendarEndDate) {
        display.text(`${formatCalDisplay(new Date(calendarStartDate))} - ${formatCalDisplay(new Date(calendarEndDate))}`);
    } else if (calendarStartDate) {
        display.text("Select end date...");
    } else {
        display.text("Select date range");
    }
}

function formatCalDate(date) {
    return date.toISOString().split('T')[0];
}

function formatCalDisplay(date) {
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    return `${String(date.getDate()).padStart(2, '0')} ${months[date.getMonth()]} ${date.getFullYear()}`;
}

window.resetFilters = function () {
    const url = new URL(window.location.href);
    url.search = '';
    window.location.href = url.toString();
}

function updateReport() {
    const year = $('#year-select').val();
    const month = $('#month-select').val();
    const store = $('#store-select').val();

    let url = `/pos/accounting/income-monthwise/${year}`;
    if (month) url += `/${month}`;

    if (store) url += `?store_id=${store}`; // Append store as query param

    window.location.href = url;
}

function loadReport(url) {
    const store = $('#store-select').val();
    if (store) {
        // Check if query param already exists
        if (url.includes('?')) url += `&store_id=${store}`;
        else url += `?store_id=${store}`;
    }
    window.location.href = url;
}

function navigateReport(direction) {
    const monthSelect = document.getElementById('month-select');
    const yearSelect = document.getElementById('year-select');
    let year = parseInt(yearSelect.value);
    let month = monthSelect.value ? parseInt(monthSelect.value) : 0; // 0 = Yearly view

    if (direction === 'prev') {
        if (month === 0) {
            year--;
        } else {
            month--;
            if (month === 0) {
                // If moving back from Jan, go to Yearly view of same year? Or prev year Dec?
                // Logic: Prev month from Jan is Dec of prev year.
                // Prev from Yearly view is Prev Year.
                month = 12;
                year--;
            }
        }
    } else {
        if (month === 0) {
            year++;
        } else {
            month++;
            if (month > 12) {
                month = 1;
                year++;
            }
        }
    }

    // Update Dropdowns logic not needed as we redirect
    let nextUrl = `/pos/accounting/income-monthwise/${year}`;
    if (month > 0) nextUrl += `/${String(month).padStart(2, '0')}`;
    loadReport(nextUrl);
}

function resetReport() {
    window.location.href = '/pos/accounting/income-monthwise';
}

function openPrintTab() {
    const url = window.location.href + (window.location.href.includes('?') ? '&' : '?') + 'print_mode=1';
    window.open(url, '_blank');
}
