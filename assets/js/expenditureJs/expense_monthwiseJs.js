/**
 * Enhanced Monthwise Report Interaction Logic
 */
$(document).ready(function () {
    // Track all store options
    var allStoresData = [];
    var limitedStoresData = [];

    function buildStoreData() {
        allStoresData = [];
        $('#store-select option').each(function () {
            allStoresData.push({
                id: $(this).val(),
                text: $(this).text()
            });
        });
        // Limit to first 6 (All Stores + 5 stores)
        limitedStoresData = allStoresData.slice(0, 6);
    }

    buildStoreData();

    // Initialize Select2 for Store Dropdown with Search
    function initSelect2() {
        // Build data array with original index for filtering
        var storesData = [];
        $('#store-select option').each(function (index) {
            storesData.push({
                id: $(this).val(),
                text: $(this).text(),
                originalIndex: index // Keep track of position (0=All, 1-5=Visible, 6+=Hidden)
            });
        });

        // Destroy if already initialized
        if ($('#store-select').hasClass("select2-hidden-accessible")) {
            $('#store-select').select2('destroy');
        }

        $('#store-select').select2({
            placeholder: 'All Stores',
            allowClear: true,
            width: '100%',
            minimumResultsForSearch: 0,
            theme: 'default',
            dropdownCssClass: 'select2-custom-dropdown',
            data: storesData,
            // Custom matcher logic: Show only first 6 if no search, else show all matches
            matcher: function (params, data) {
                // If there IS a search term, use default fuzzy search
                if ($.trim(params.term) !== '') {
                    if (data.text.toUpperCase().indexOf(params.term.toUpperCase()) > -1) {
                        return data;
                    }
                    return null;
                }

                // If NO search term, only return items with index <= 5
                // (Index 0 is "All Stores", 1-5 represent the first 5 stores)
                if (data.originalIndex <= 5) {
                    return data;
                }

                // Hide everything else
                return null;
            }
        });
    }

    // Initialize on page load
    initSelect2();

    // Re-initialize after AJAX load
    $(document).on('ajaxComplete', function () {
        buildStoreData();
        isShowingAll = false;
        $(document).off('input.select2search');
        $('#store-select').select2('destroy');
        initSelect2();
    });

    // 1. Dual Selector Synchronization (Now with Store Support)
    $(document).on('change', '#year-select, #month-select, #store-select', function () {
        const year = $('#year-select').val();
        const month = $('#month-select').val();
        const store = $('#store-select').val();

        let targetUrl = `/pos/expenditure/monthwise/${year}`;
        if (month) {
            targetUrl += `/${month}`;
        }

        // Add store filter as query param
        if (store) {
            targetUrl += `?store_id=${store}`;
        }

        loadReport(targetUrl);
    });

    // 2. AJAX Partial Loader
    window.loadReport = function (url) {
        // Show loading state
        $('#ajax-container').addClass('opacity-50 pointer-events-none');

        const ajaxUrl = url + (url.includes('?') ? '&' : '?') + 'ajax=1';

        $.ajax({
            url: ajaxUrl,
            type: 'GET',
            success: function (response) {
                $('#ajax-container').html(response);
                $('#ajax-container').removeClass('opacity-50 pointer-events-none');

                // Update Browser URL
                window.history.pushState({ path: url }, '', url);

                // Re-initialize Chart
                initExpenditureChart();
            },
            error: function () {
                alert('Error loading report');
                $('#ajax-container').removeClass('opacity-50 pointer-events-none');
            }
        });
    };

    /**
     * 3. Handle Browser Back/Forward
     */
    window.onpopstate = function (e) {
        if (e.state && e.state.path) {
            loadReport(e.state.path);
        }
    };

    /**
     * 4. Smart Navigation Logic (Prev/Next Arrows)
     */
    window.navigateReport = function (direction) {
        let year = parseInt($('#year-select').val());
        let month = $('#month-select').val() ? parseInt($('#month-select').val()) : null;
        let store = $('#store-select').val();

        let targetUrl = '';
        if (month) {
            if (direction === 'prev') {
                month--;
                if (month < 1) { month = 12; year--; }
            } else {
                month++;
                if (month > 12) { month = 1; year++; }
            }
            targetUrl = `/pos/expenditure/monthwise/${year}/${month.toString().padStart(2, '0')}`;
        } else {
            if (direction === 'prev') year--;
            else year++;
            targetUrl = `/pos/expenditure/monthwise/${year}`;
        }

        // Preserve store filter
        if (store) {
            targetUrl += `?store_id=${store}`;
        }

        loadReport(targetUrl);
    };

    // 5. Click Feedback Micro-Interactions
    $(document).on('mousedown', 'td.cell-active', function () {
        $(this).addClass('scale-[0.98] transition-transform duration-75');
    }).on('mouseup mouseleave', 'td.cell-active', function () {
        $(this).removeClass('scale-[0.98]');
    });

    /**
     * 6. Open Specialized Print Tab
     */
    window.openPrintTab = function () {
        const currentUrl = window.location.href;
        const separator = currentUrl.includes('?') ? '&' : '?';
        const printUrl = currentUrl + separator + 'print_mode=1';
        window.open(printUrl, '_blank');
    };

    /**
     * 7. Reset All Filters
     */
    window.resetReport = function () {
        // Reset Selector Values
        $('#store-select').val('').trigger('change');
        $('#month-select').val('');
        $('#year-select').val(new Date().getFullYear());

        // Load Default Report
        const defaultUrl = '/pos/expenditure/monthwise/' + new Date().getFullYear();
        loadReport(defaultUrl);
    };

    /**
     * 7. Interactive Expenditure Analytics Graph
     */
    let expenditureChart = null;

    window.initExpenditureChart = function () {
        // Target the specific data provider inside the AJAX container for sync
        const $provider = $('#ajax-container #chart-data-provider');
        if (!$provider.length) return;

        const ctx = document.getElementById('expenditureChart');
        if (!ctx) return;

        try {
            const rawData = JSON.parse($provider.val());
            if (expenditureChart) expenditureChart.destroy();

            expenditureChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: rawData.labels,
                    datasets: [{
                        label: 'Expenditure',
                        data: rawData.data,
                        fill: true,
                        backgroundColor: 'rgba(13, 148, 136, 0.1)',
                        borderColor: '#0d9488',
                        borderWidth: 4,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#0d9488',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        duration: 500, // Balanced snappiness
                        easing: 'easeOutQuart'
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            titleFont: { size: 10, weight: 'bold' },
                            bodyFont: { size: 14, weight: '900' },
                            padding: 12,
                            cornerRadius: 12,
                            displayColors: false,
                            callbacks: {
                                label: function (context) {
                                    return context.parsed.y.toLocaleString() + ' ৳';
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { font: { size: 10, weight: 'bold' }, color: '#94a3b8' }
                        },
                        y: {
                            beginAtZero: true,
                            grid: { borderDash: [5, 5], color: 'rgba(203, 213, 225, 0.5)' },
                            ticks: { font: { size: 10, weight: 'bold' }, color: '#94a3b8' }
                        }
                    }
                }
            });
        } catch (e) {
            console.error("Chart parsing error:", e);
        }
    };

    // Initialize on Page Load
    initExpenditureChart();
});
