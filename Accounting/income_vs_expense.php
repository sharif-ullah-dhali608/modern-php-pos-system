<?php
// Output Buffering & Session Check
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('../config/dbcon.php');

// Security Check
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

// Default: Today (Synchronized with P&L/Cashbook)
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

// Ensure end_date is set
if($start_date && !$end_date) {
    $end_date = $start_date;
}

// --- DATA FETCHING ---
// 1. Income (Deposits)
$income_query = "SELECT 
                    info.info_id,
                    info.details,
                    info.created_at,
                    price.amount,
                    src.source_name as title
                 FROM bank_transaction_info info 
                 JOIN bank_transaction_price price ON info.info_id = price.info_id
                 LEFT JOIN income_sources src ON info.source_id = src.source_id
                 WHERE info.transaction_type='deposit' 
                   AND DATE(info.created_at) BETWEEN '$start_date' AND '$end_date'
                 ORDER BY info.created_at DESC";
$income_res = mysqli_query($conn, $income_query);
$incomes = [];
$total_income = 0;
while($row = mysqli_fetch_assoc($income_res)) {
    $incomes[] = $row;
    $total_income += $row['amount'];
}

// 2. Expenses (Withdraws)
$expense_query = "SELECT 
                    info.info_id,
                    info.details,
                    info.created_at,
                    price.amount,
                    cat.category_name as title
                  FROM bank_transaction_info info 
                  JOIN bank_transaction_price price ON info.info_id = price.info_id
                  LEFT JOIN expense_category cat ON info.exp_category_id = cat.category_id
                  WHERE info.transaction_type='withdraw' 
                    AND DATE(info.created_at) BETWEEN '$start_date' AND '$end_date'
                  ORDER BY info.created_at DESC";
$expense_res = mysqli_query($conn, $expense_query);
$expenses = [];
$total_expense = 0;
while($row = mysqli_fetch_assoc($expense_res)) {
    $expenses[] = $row;
    $total_expense += $row['amount'];
}

$balance = $total_income - $total_expense;

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
                            <h1 class="text-3xl font-black text-slate-900 tracking-tight">Income vs Expense</h1>
                            <span class="bg-indigo-100 text-indigo-800 border border-indigo-200 px-3 py-1 rounded-full text-xs font-black uppercase tracking-wide">
                                <?php 
                                    if($start_date === $end_date) {
                                        echo date('d M Y', strtotime($start_date));
                                    } else {
                                        echo date('d M', strtotime($start_date)) . ' - ' . date('d M Y', strtotime($end_date));
                                    }
                                ?>
                            </span>
                        </div>
                        <p class="text-slate-500 font-medium text-sm">Compare your inflows and outflows for better budgeting</p>
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
                            <button type="button" onclick="toggleFilterDropdown('filter_date')" class="inline-flex items-center justify-center gap-2 px-5 py-3 <?= $is_filtering ? 'bg-indigo-600 text-white' : 'bg-white text-slate-700 border border-slate-200'; ?> hover:bg-indigo-700 hover:text-white font-bold rounded-xl shadow-sm transition-all w-full md:min-w-[180px]">
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
                                        <a href="<?= $base_url; ?>?date_filter=<?= $p['value']; ?>" class="block w-full px-5 py-3.5 text-sm text-slate-700 hover:bg-slate-50 transition-colors <?= $act ? 'bg-indigo-50 text-indigo-700 font-black' : 'font-bold'; ?>">
                                            <?= $p['label']; ?>
                                        </a>
                                    <?php endforeach; ?>
                                    
                                    <button type="button" onclick="event.stopPropagation(); showCustomCalendar()" class="block w-full px-5 py-3.5 text-sm text-indigo-600 hover:bg-slate-50 transition-colors text-left font-black border-t border-slate-50">
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
                                        <button type="button" id="calendar-month-display" onclick="event.stopPropagation(); toggleYearMonthSelector()" class="font-black text-slate-800 hover:text-indigo-600 transition-colors uppercase tracking-widest text-xs">January 2026</button>
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
                </div>

                <!-- NEW DESIGN: Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
                    <!-- Total Income Card -->
                    <div class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-[2rem] p-8 shadow-xl shadow-emerald-200/50 relative overflow-hidden group">
                        <div class="relative z-10 text-white">
                            <div class="flex items-center gap-4 mb-4">
                                <div class="w-12 h-12 bg-white/20 backdrop-blur-md rounded-2xl flex items-center justify-center">
                                    <i class="fas fa-arrow-down text-xl rotate-180"></i>
                                </div>
                                <span class="text-sm font-black uppercase tracking-widest opacity-80">Total Income</span>
                            </div>
                            <div class="text-4xl font-black mb-1"><?= number_format($total_income, 2) ?></div>
                            <div class="text-xs font-bold opacity-70">Credit transactions for selected range</div>
                        </div>
                        <div class="absolute -right-4 -bottom-4 text-white/5 text-8xl font-black italic group-hover:scale-110 transition-transform duration-500">CREDIT</div>
                    </div>

                    <!-- Total Expense Card -->
                    <div class="bg-gradient-to-br from-rose-500 to-pink-600 rounded-[2rem] p-8 shadow-xl shadow-rose-200/50 relative overflow-hidden group">
                        <div class="relative z-10 text-white">
                            <div class="flex items-center gap-4 mb-4">
                                <div class="w-12 h-12 bg-white/20 backdrop-blur-md rounded-2xl flex items-center justify-center">
                                    <i class="fas fa-arrow-up text-xl"></i>
                                </div>
                                <span class="text-sm font-black uppercase tracking-widest opacity-80">Total Expense</span>
                            </div>
                            <div class="text-4xl font-black mb-1"><?= number_format($total_expense, 2) ?></div>
                            <div class="text-xs font-bold opacity-70">Debit transactions for selected range</div>
                        </div>
                        <div class="absolute -right-4 -bottom-4 text-white/5 text-8xl font-black italic group-hover:scale-110 transition-transform duration-500">DEBIT</div>
                    </div>

                    <!-- Net Balance Card -->
                    <div class="bg-white rounded-[2rem] p-8 shadow-xl border border-slate-100 relative overflow-hidden group">
                        <div class="relative z-10">
                            <div class="flex items-center gap-4 mb-4">
                                <div class="<?= $balance >= 0 ? 'bg-indigo-100 text-indigo-600' : 'bg-rose-100 text-rose-600'; ?> w-12 h-12 rounded-2xl flex items-center justify-center shadow-inner">
                                    <i class="fas fa-wallet text-xl"></i>
                                </div>
                                <span class="text-sm font-black uppercase tracking-widest text-slate-400">Net Balance</span>
                            </div>
                            <div class="text-4xl font-black mb-1 <?= $balance >= 0 ? 'text-indigo-600' : 'text-rose-600'; ?>"><?= number_format($balance, 2) ?></div>
                            <div class="text-xs font-bold text-slate-400 italic">Remaining balance after expenses</div>
                        </div>
                        <div class="absolute -right-4 -bottom-4 text-slate-50 text-8xl font-black italic group-hover:scale-110 transition-transform duration-500">NET</div>
                    </div>
                </div>

        <!-- Tables Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 lg:items-start gap-8">
            
            <!-- Income Table -->
            <div class="bg-white rounded-[2.5rem] shadow-xl border border-slate-100 overflow-hidden">
                <div class="p-6 border-b border-slate-100 bg-emerald-50/30 flex items-center justify-between">
                    <h3 class="font-black text-slate-900 text-lg flex items-center gap-2">
                        <span class="w-2 h-8 bg-emerald-500 rounded-full"></span>
                        Credit / Income
                    </h3>
                    <span class="bg-emerald-100 text-emerald-700 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider"><?= count($incomes) ?> Entries</span>
                </div>
                <div class="flex-1 overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-widest font-bold border-b border-slate-100">
                                <th class="p-4">Date</th>
                                <th class="p-4">Title</th>
                                <th class="p-4 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <?php if(empty($incomes)): ?>
                                <tr><td colspan="3" class="p-8 text-center text-slate-400 italic font-medium">No income records found</td></tr>
                            <?php else: ?>
                                <?php foreach($incomes as $inc): ?>
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="p-4 text-sm font-bold text-slate-600 whitespace-nowrap"><?= date('d M', strtotime($inc['created_at'])) ?></td>
                                        <td class="p-4 text-sm font-bold text-slate-800"><?= $inc['title'] ?: $inc['details'] ?></td>
                                        <td class="p-4 text-sm font-black text-teal-600 text-right"><?= number_format($inc['amount'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot class="bg-slate-50 font-black text-slate-800">
                            <tr>
                                <td colspan="2" class="p-4 text-right uppercase text-xs tracking-widest">Total Income</td>
                                <td class="p-4 text-right text-teal-700"><?= number_format($total_income, 2) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Expense Table -->
            <div class="bg-white rounded-[2.5rem] shadow-xl border border-slate-100 overflow-hidden">
                <div class="p-6 border-b border-slate-100 bg-rose-50/30 flex items-center justify-between">
                    <h3 class="font-black text-slate-900 text-lg flex items-center gap-2">
                        <span class="w-2 h-8 bg-rose-500 rounded-full"></span>
                        Debit / Expense
                    </h3>
                    <span class="bg-rose-100 text-rose-700 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider"><?= count($expenses) ?> Entries</span>
                </div>
                <div class="flex-1 overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-widest font-bold border-b border-slate-100">
                                <th class="p-4">Date</th>
                                <th class="p-4">Title</th>
                                <th class="p-4 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <?php if(empty($expenses)): ?>
                                <tr><td colspan="3" class="p-8 text-center text-slate-400 italic font-medium">No expense records found</td></tr>
                            <?php else: ?>
                                <?php foreach($expenses as $exp): ?>
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="p-4 text-sm font-bold text-slate-600 whitespace-nowrap"><?= date('d M', strtotime($exp['created_at'])) ?></td>
                                        <td class="p-4 text-sm font-bold text-slate-800"><?= $exp['title'] ?: $exp['details'] ?></td>
                                        <td class="p-4 text-sm font-black text-rose-600 text-right"><?= number_format($exp['amount'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot class="bg-slate-50 font-black text-slate-800">
                            <tr>
                                <td colspan="2" class="p-4 text-right uppercase text-xs tracking-widest">Total Expense</td>
                                <td class="p-4 text-right text-rose-700"><?= number_format($total_expense, 2) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

        </div>

            <!-- End Content -->
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
        
        const dayClass = isSelected ? 'bg-indigo-600 text-white font-black' : (isInRange ? 'bg-indigo-50 text-indigo-700' : 'hover:bg-slate-50 text-slate-600');
        
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
    // Basic implementation if needed
}

// Close dropdown on outside click
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('filter_date');
    if (dropdown && !dropdown.contains(event.target)) {
        dropdown.classList.add('hidden');
    }
});
</script>
