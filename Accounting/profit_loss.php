<?php
// Output Buffering
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('../config/dbcon.php');

if(!isset($_SESSION['auth'])){
    header("Location: /pos/login");
    exit(0);
}

include('../includes/header.php');

date_default_timezone_set('Asia/Dhaka');

// --- DATE FILTER LOGIC ---
$date_filter = $_GET['date_filter'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// Default: Today
if(!$date_filter && !$start_date) {
    $date_filter = 'today';
}

if($date_filter) {
    switch($date_filter) {
        case 'today':
            $start_date = date('Y-m-d');
            $end_date = date('Y-m-d');
            break;
        case 'yesterday':
            $start_date = date('Y-m-d', strtotime('-1 day'));
            $end_date = date('Y-m-d', strtotime('-1 day'));
            break;
        case '3_days':
            $start_date = date('Y-m-d', strtotime('-3 days'));
            $end_date = date('Y-m-d');
            break;
        case '1_week':
            $start_date = date('Y-m-d', strtotime('-1 week'));
            $end_date = date('Y-m-d');
            break;
        case '1_month':
            $start_date = date('Y-m-d', strtotime('-1 month'));
            $end_date = date('Y-m-d');
            break;
    }
}

// Ensure end_date is set if only start_date exists
if($start_date && !$end_date) {
    $end_date = $start_date;
}

$current_month = date('m', strtotime($start_date));
$current_year = date('Y', strtotime($start_date));

// ... DATA FETCHING LOGIC REMAINS ...
// PROFIT (Income Sources)
$profit_query = "SELECT 
                    src.source_id,
                    src.source_name as title,
                    SUM(CASE WHEN MONTH(info.created_at) = '$current_month' AND YEAR(info.created_at) = '$current_year' THEN price.amount ELSE 0 END) as month_total,
                    SUM(CASE WHEN YEAR(info.created_at) = '$current_year' THEN price.amount ELSE 0 END) as year_total,
                    SUM(price.amount) as all_time_total
                 FROM income_sources src
                 LEFT JOIN bank_transaction_info info ON src.source_id = info.source_id AND info.transaction_type='deposit'
                 LEFT JOIN bank_transaction_price price ON info.info_id = price.info_id
                 GROUP BY src.source_id
                 ORDER BY all_time_total DESC";
$profit_res = mysqli_query($conn, $profit_query);
$profit_data = [];
$totals_profit = ['month' => 0, 'year' => 0, 'all' => 0];
while($row = mysqli_fetch_assoc($profit_res)) {
    if($row['all_time_total'] > 0) {
        $profit_data[] = $row;
        $totals_profit['month'] += $row['month_total'];
        $totals_profit['year']  += $row['year_total'];
        $totals_profit['all']   += $row['all_time_total'];
    }
}

// LOSS (Expense Categories)
$loss_query = "SELECT 
                    cat.category_id,
                    cat.category_name as title,
                    SUM(CASE WHEN MONTH(info.created_at) = '$current_month' AND YEAR(info.created_at) = '$current_year' THEN price.amount ELSE 0 END) as month_total,
                    SUM(CASE WHEN YEAR(info.created_at) = '$current_year' THEN price.amount ELSE 0 END) as year_total,
                    SUM(price.amount) as all_time_total
                 FROM expense_category cat
                 LEFT JOIN bank_transaction_info info ON cat.category_id = info.exp_category_id AND info.transaction_type='withdraw'
                 LEFT JOIN bank_transaction_price price ON info.info_id = price.info_id
                 GROUP BY cat.category_id
                 ORDER BY all_time_total DESC";
$loss_res = mysqli_query($conn, $loss_query);
$loss_data = [];
$totals_loss = ['month' => 0, 'year' => 0, 'all' => 0];
while($row = mysqli_fetch_assoc($loss_res)) {
    if($row['all_time_total'] > 0) {
        $loss_data[] = $row;
        $totals_loss['month'] += $row['month_total'];
        $totals_loss['year']  += $row['year_total'];
        $totals_loss['all']   += $row['all_time_total'];
    }
}

// Net Profit (For selected range)
$today_profit_query = "SELECT SUM(price.amount) as total FROM bank_transaction_info info JOIN bank_transaction_price price ON info.info_id=price.info_id WHERE info.transaction_type='deposit' AND DATE(info.created_at) BETWEEN '$start_date' AND '$end_date'";
$today_loss_query = "SELECT SUM(price.amount) as total FROM bank_transaction_info info JOIN bank_transaction_price price ON info.info_id=price.info_id WHERE info.transaction_type='withdraw' AND DATE(info.created_at) BETWEEN '$start_date' AND '$end_date'";
$today_profit = mysqli_fetch_assoc(mysqli_query($conn, $today_profit_query))['total'] ?? 0;
$today_loss = mysqli_fetch_assoc(mysqli_query($conn, $today_loss_query))['total'] ?? 0;
$net_profit = $today_profit - $today_loss;

?>

<div class="app-wrapper">
    <?php include('../includes/sidebar.php'); ?>
    
    <main id="main-content" class="flex-1 lg:ml-64 flex flex-col h-screen min-w-0 transition-all duration-300">
        <div class="navbar-fixed-top"><?php include('../includes/navbar.php'); ?></div>

        <div class="content-scroll-area custom-scroll h-full overflow-y-auto">
            <div class="p-6">
                <!-- Header -->
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8 mt-16 lg:mt-0">
                    <div>
                        <div class="flex items-center gap-3 mb-1">
                            <h1 class="text-3xl font-black text-slate-900 tracking-tight">Profit vs Loss</h1>
                            <span class="bg-teal-100 text-teal-800 border border-teal-200 px-3 py-1 rounded-full text-xs font-black uppercase tracking-wide">
                                <?php 
                                    if($start_date === $end_date) {
                                        echo date('d M Y', strtotime($start_date));
                                    } else {
                                        echo date('d M', strtotime($start_date)) . ' - ' . date('d M Y', strtotime($end_date));
                                    }
                                ?>
                            </span>
                        </div>
                        <p class="text-slate-500 font-medium text-sm">Analyze your business performance and net margins</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                         <?php 
                            // Determine active filter label for UI
                            $filter_label = 'Date';
                            $is_filtering = false;

                            if($date_filter) {
                                $filter_label = ucwords(str_replace('_', ' ', $date_filter));
                                if($date_filter !== 'today') {
                                    $is_filtering = true;
                                }
                            } elseif($start_date && $end_date) {
                                if($start_date === $end_date) {
                                    if($start_date !== date('Y-m-d')) {
                                        $is_filtering = true;
                                    }
                                    $filter_label = date('d M Y', strtotime($start_date));
                                } else {
                                    $is_filtering = true;
                                    $filter_label = date('d M Y', strtotime($start_date)) . ' - ' . date('d M Y', strtotime($end_date));
                                }
                            }
                         ?>

                         <?php if($is_filtering): ?>
                         <a href="<?= strtok($_SERVER['REQUEST_URI'], '?'); ?>" class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-rose-50 hover:bg-rose-100 text-rose-600 font-bold rounded-xl border border-rose-200 transition-all shadow-sm">
                            <i class="fas fa-undo text-xs"></i>
                            <span>Reset</span>
                         </a>
                         <?php endif; ?>

                         <!-- Advanced Date Filter Dropdown -->
                         <div class="relative w-full md:w-auto">
                            <button type="button" onclick="toggleFilterDropdown('filter_date')" class="inline-flex items-center justify-center gap-2 px-5 py-3 <?= $is_filtering ? 'bg-teal-600 text-white' : 'bg-white text-slate-700 border border-slate-200'; ?> hover:bg-teal-700 hover:text-white font-bold rounded-xl shadow-sm transition-all w-full md:min-w-[180px]">
                                <i class="fas fa-calendar-alt"></i>
                                <span><?= $filter_label; ?></span>
                                <i class="fas fa-chevron-down text-xs"></i>
                            </button>
                            
                            <div id="filter_date" class="hidden absolute right-0 mt-2 w-80 bg-white rounded-2xl shadow-2xl border border-slate-100 z-[110] overflow-hidden text-left">
                                <!-- Preset Filters -->
                                <div id="preset-filters-list" class="max-h-60 overflow-y-auto">
                                    <?php
                                    $base_url = strtok($_SERVER['REQUEST_URI'], '?');
                                    $presets = [
                                        ['value' => 'today', 'label' => 'Today'],
                                        ['value' => 'yesterday', 'label' => 'Yesterday'],
                                        ['value' => '3_days', 'label' => 'Last 3 Days'],
                                        ['value' => '1_week', 'label' => 'Last 1 Week'],
                                        ['value' => '1_month', 'label' => 'Last 1 Month'],
                                    ];
                                    foreach($presets as $p):
                                        $act = ($date_filter === $p['value']);
                                    ?>
                                        <a href="<?= $base_url; ?>?date_filter=<?= $p['value']; ?>" class="block w-full px-5 py-3.5 text-sm text-slate-700 hover:bg-slate-50 transition-colors <?= $act ? 'bg-teal-50 text-teal-700 font-black' : 'font-bold'; ?>">
                                            <?= $p['label']; ?>
                                        </a>
                                    <?php endforeach; ?>
                                    
                                    <button type="button" onclick="event.stopPropagation(); showCustomCalendar()" class="block w-full px-5 py-3.5 text-sm text-teal-600 hover:bg-slate-50 transition-colors text-left font-black border-t border-slate-50">
                                        <i class="fas fa-plus-circle mr-2"></i>Custom Range
                                    </button>

                                    <?php if($is_filtering): ?>
                                    <a href="<?= $base_url; ?>" class="block w-full px-5 py-3.5 text-sm text-rose-600 hover:bg-rose-50 transition-colors font-black border-t border-slate-50">
                                        <i class="fas fa-times-circle mr-2"></i>Clear Filter
                                    </a>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Custom Calendar Picker -->
                                <div id="custom-calendar-picker" class="hidden border-t border-slate-100 bg-white p-5">
                                    <div class="mb-4 p-3 bg-slate-50 rounded-xl text-center">
                                        <span id="calendar-selected-range" class="text-xs font-black text-slate-600 uppercase tracking-widest">Select range</span>
                                    </div>
                                    
                                    <div class="flex items-center justify-between mb-4">
                                        <button type="button" onclick="event.stopPropagation(); changeCalendarMonth(-1)" class="p-2 hover:bg-slate-100 rounded-lg text-slate-600"><i class="fas fa-chevron-left"></i></button>
                                        <button type="button" id="calendar-month-display" onclick="event.stopPropagation(); toggleYearMonthSelector()" class="font-black text-slate-800 hover:text-teal-600 transition-colors uppercase tracking-widest text-xs">January 2026</button>
                                        <button type="button" onclick="event.stopPropagation(); changeCalendarMonth(1)" class="p-2 hover:bg-slate-100 rounded-lg text-slate-600"><i class="fas fa-chevron-right"></i></button>
                                    </div>
                                    
                                    <div class="grid grid-cols-7 gap-1 mb-2">
                                        <?php foreach(['Su','Mo','Tu','We','Th','Fr','Sa'] as $day): ?>
                                            <div class="text-[10px] font-black text-slate-400 text-center uppercase"><?= $day ?></div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div id="calendar-days-grid" class="grid grid-cols-7 gap-y-1"></div>
                                </div>
                            </div>
                         </div>
        
                         <button onclick="window.print()" class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl border border-slate-300 transition-all shadow-sm">
                            <i class="fas fa-print text-slate-500 text-xs"></i> 
                            <span>Export</span>
                         </button>
                    </div>
                </div> <!-- Closing header -->

        <!-- NEW DESIGN: Summary Cards (Today Context) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            <!-- Total Profit Card -->
            <div class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-[2rem] p-8 shadow-xl shadow-emerald-200/50 relative overflow-hidden group">
                <div class="relative z-10 text-white">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-12 h-12 bg-white/20 backdrop-blur-md rounded-2xl flex items-center justify-center">
                            <i class="fas fa-arrow-down text-xl rotate-180"></i>
                        </div>
                        <span class="text-sm font-black uppercase tracking-widest opacity-80">Total Profit</span>
                    </div>
                    <div class="text-4xl font-black mb-1"><?= number_format($today_profit, 2) ?></div>
                    <div class="text-xs font-bold opacity-70">Gross earnings for selected range</div>
                </div>
                <div class="absolute -right-4 -bottom-4 text-white/5 text-8xl font-black italic group-hover:scale-110 transition-transform duration-500">PROFIT</div>
            </div>

            <!-- Total Loss Card -->
            <div class="bg-gradient-to-br from-rose-500 to-pink-600 rounded-[2rem] p-8 shadow-xl shadow-rose-200/50 relative overflow-hidden group">
                <div class="relative z-10 text-white">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-12 h-12 bg-white/20 backdrop-blur-md rounded-2xl flex items-center justify-center">
                            <i class="fas fa-arrow-down text-xl"></i>
                        </div>
                        <span class="text-sm font-black uppercase tracking-widest opacity-80">Total Loss</span>
                    </div>
                    <div class="text-4xl font-black mb-1"><?= number_format($today_loss, 2) ?></div>
                    <div class="text-xs font-bold opacity-70">Expenses incurred for selected range</div>
                </div>
                <div class="absolute -right-4 -bottom-4 text-white/5 text-8xl font-black italic group-hover:scale-110 transition-transform duration-500">LOSS</div>
            </div>

            <!-- Net Profit Card -->
            <div class="bg-white rounded-[2rem] p-8 shadow-xl border border-slate-100 relative overflow-hidden group">
                <div class="relative z-10">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="<?= $net_profit >= 0 ? 'bg-indigo-100 text-indigo-600' : 'bg-rose-100 text-rose-600'; ?> w-12 h-12 rounded-2xl flex items-center justify-center shadow-inner">
                            <i class="fas fa-chart-line text-xl"></i>
                        </div>
                        <span class="text-sm font-black uppercase tracking-widest text-slate-400">Net Profit</span>
                    </div>
                    <div class="text-4xl font-black mb-1 <?= $net_profit >= 0 ? 'text-indigo-600' : 'text-rose-600'; ?>"><?= number_format($net_profit, 2) ?></div>
                    <div class="text-xs font-bold text-slate-400 italic">Net margin after all deductions</div>
                </div>
                <div class="absolute -right-4 -bottom-4 text-slate-50 text-8xl font-black italic group-hover:scale-110 transition-transform duration-500">NET</div>
            </div>
        </div>

        <!-- Tables Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 items-start gap-8 mb-10">
            
            <!-- Loss Table -->
            <div class="bg-white rounded-[2.5rem] shadow-xl border border-slate-100 overflow-hidden">
                <div class="p-6 border-b border-slate-100 bg-rose-50/30 flex items-center justify-between">
                    <h3 class="font-black text-slate-900 text-lg flex items-center gap-2">
                        <span class="w-2 h-8 bg-rose-500 rounded-full"></span>
                        Loss / Expenses
                    </h3>
                </div>
                <!-- Filters/Search for Table (Static for now) -->
                <!-- <div class="p-4 flex gap-2"> Search ... </div> -->
                
                <div class="flex-1 overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-100/50 text-slate-500 text-[10px] uppercase tracking-widest font-bold border-b border-slate-200">
                                <th class="p-4">SL</th>
                                <th class="p-4">Title</th>
                                <th class="p-4 text-right">This Month</th>
                                <th class="p-4 text-right">This Year</th>
                                <th class="p-4 text-right">Till Now</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 text-sm">
                            <?php if(empty($loss_data)): ?>
                                <tr><td colspan="5" class="p-6 text-center text-slate-400 italic">No data available in table</td></tr>
                            <?php else: ?>
                                <?php foreach($loss_data as $i => $row): ?>
                                <tr class="hover:bg-slate-50">
                                    <td class="p-4 text-slate-400 font-bold"><?= $i+1 ?></td>
                                    <td class="p-4 font-bold text-slate-700"><?= $row['title'] ?></td>
                                    <td class="p-4 text-right font-medium text-slate-600"><?= number_format($row['month_total'], 2) ?></td>
                                    <td class="p-4 text-right font-medium text-slate-600"><?= number_format($row['year_total'], 2) ?></td>
                                    <td class="p-4 text-right font-bold text-slate-800"><?= number_format($row['all_time_total'], 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot class="bg-slate-100/50 font-black text-slate-800 text-xs uppercase tracking-widest">
                            <tr>
                                <td colspan="2" class="p-4 text-center">Total</td>
                                <td class="p-4 text-right"><?= number_format($totals_loss['month'], 2) ?></td>
                                <td class="p-4 text-right"><?= number_format($totals_loss['year'], 2) ?></td>
                                <td class="p-4 text-right"><?= number_format($totals_loss['all'], 2) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Profit Table -->
            <div class="bg-white rounded-[2.5rem] shadow-xl border border-slate-100 overflow-hidden">
                 <div class="p-6 border-b border-slate-100 bg-emerald-50/30 flex items-center justify-between">
                    <h3 class="font-black text-slate-900 text-lg flex items-center gap-2">
                        <span class="w-2 h-8 bg-emerald-500 rounded-full"></span>
                        Profit / Income
                    </h3>
                </div>
                <div class="flex-1 overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-100/50 text-slate-500 text-[10px] uppercase tracking-widest font-bold border-b border-slate-200">
                                <th class="p-4">SL</th>
                                <th class="p-4">Title</th>
                                <th class="p-4 text-right">This Month</th>
                                <th class="p-4 text-right">This Year</th>
                                <th class="p-4 text-right">Till Now</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 text-sm">
                            <?php if(empty($profit_data)): ?>
                                <tr><td colspan="5" class="p-6 text-center text-slate-400 italic">No data available in table</td></tr>
                            <?php else: ?>
                                <?php foreach($profit_data as $i => $row): ?>
                                <tr class="hover:bg-slate-50">
                                    <td class="p-4 text-slate-400 font-bold"><?= $i+1 ?></td>
                                    <td class="p-4 font-bold text-slate-700"><?= $row['title'] ?></td>
                                    <td class="p-4 text-right font-medium text-slate-600"><?= number_format($row['month_total'], 2) ?></td>
                                    <td class="p-4 text-right font-medium text-slate-600"><?= number_format($row['year_total'], 2) ?></td>
                                    <td class="p-4 text-right font-bold text-slate-800"><?= number_format($row['all_time_total'], 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot class="bg-slate-100/50 font-black text-slate-800 text-xs uppercase tracking-widest">
                            <tr>
                                <td colspan="2" class="p-4 text-center">Total</td>
                                <td class="p-4 text-right"><?= number_format($totals_profit['month'], 2) ?></td>
                                <td class="p-4 text-right"><?= number_format($totals_profit['year'], 2) ?></td>
                                <td class="p-4 text-right"><?= number_format($totals_profit['all'], 2) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

        </div>


        </div>
        <?php include('../includes/footer.php'); ?>
    </main>
</div>

<script>
// --- Ported Advanced Date Filter Scripts ---
let calendarCurrentDate = new Date();
let calendarStartDate = '<?= $start_date ?>';
let calendarEndDate = '<?= $end_date ?>';

function toggleFilterDropdown(id) {
    const el = document.getElementById(id);
    el.classList.toggle('hidden');
    event.stopPropagation();
}

function showCustomCalendar() {
    document.getElementById('preset-filters-list').classList.add('hidden');
    document.getElementById('custom-calendar-picker').classList.remove('hidden');
    renderCustomCalendar();
}

function renderCustomCalendar() {
    const grid = document.getElementById('calendar-days-grid');
    const display = document.getElementById('calendar-month-display');
    grid.innerHTML = '';
    
    const year = calendarCurrentDate.getFullYear();
    const month = calendarCurrentDate.getMonth();
    
    display.innerText = new Intl.DateTimeFormat('en-US', { month: 'long', year: 'numeric' }).format(calendarCurrentDate);
    
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    
    // Empty days
    for (let i = 0; i < firstDay; i++) {
        grid.innerHTML += '<div></div>';
    }
    
    // Actual days
    for (let d = 1; d <= daysInMonth; d++) {
        const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
        const isSelected = dateStr === calendarStartDate || dateStr === calendarEndDate;
        const isInRange = dateStr > calendarStartDate && dateStr < calendarEndDate;
        
        const dayClass = isSelected ? 'bg-teal-600 text-white font-black' : (isInRange ? 'bg-teal-50 text-teal-700' : 'hover:bg-slate-50 text-slate-600');
        
        grid.innerHTML += `
            <div onclick="selectCalendarDate('${dateStr}')" class="h-9 w-9 flex items-center justify-center rounded-lg cursor-pointer text-xs transition-all ${dayClass}">
                ${d}
            </div>
        `;
    }
}

function selectCalendarDate(date) {
    if (!calendarStartDate || (calendarStartDate && calendarEndDate)) {
        calendarStartDate = date;
        calendarEndDate = '';
        document.getElementById('calendar-selected-range').innerText = 'Select end date';
        renderCustomCalendar();
    } else {
        if (date < calendarStartDate) {
            calendarEndDate = calendarStartDate;
            calendarStartDate = date;
        } else {
            calendarEndDate = date;
        }
        
        document.getElementById('calendar-selected-range').innerText = `${calendarStartDate} - ${calendarEndDate}`;
        renderCustomCalendar();
        
        // Redirect
        setTimeout(() => {
            window.location.href = `${window.location.pathname}?start_date=${calendarStartDate}&end_date=${calendarEndDate}`;
        }, 300);
    }
}

function changeCalendarMonth(offset) {
    calendarCurrentDate.setMonth(calendarCurrentDate.getMonth() + offset);
    renderCustomCalendar();
}

function toggleYearMonthSelector() {
    // Basic implementation if needed, otherwise skip for brevity
}

// Close dropdown on outside click
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('filter_date');
    if (dropdown && !dropdown.contains(event.target)) {
        dropdown.classList.add('hidden');
    }
});
</script>

