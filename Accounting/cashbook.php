<?php
// Output Buffering
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Dhaka');
include('../config/dbcon.php');

if(!isset($_SESSION['auth'])){
    header("Location: /pos/login");
    exit(0);
}

include('../includes/header.php');

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
        case 'tomorrow':
            $start_date = date('Y-m-d', strtotime('+1 day'));
            $end_date = date('Y-m-d', strtotime('+1 day'));
            break;
        case 'yesterday':
            $start_date = date('Y-m-d', strtotime('-1 day'));
            $end_date = date('Y-m-d', strtotime('-1 day'));
            break;
        case '3_days':
            $start_date = date('Y-m-d', strtotime('-2 days'));
            $end_date = date('Y-m-d');
            break;
        case '1_week':
            $start_date = date('Y-m-d', strtotime('-6 days'));
            $end_date = date('Y-m-d');
            break;
        case '1_month':
            $start_date = date('Y-m-d', strtotime('-29 days'));
            $end_date = date('Y-m-d');
            break;
        case '3_months':
            $start_date = date('Y-m-d', strtotime('-89 days'));
            $end_date = date('Y-m-d');
            break;
        case '6_months':
            $start_date = date('Y-m-d', strtotime('-179 days'));
            $end_date = date('Y-m-d');
            break;
    }
}

// For UI consistency with the manual entry logic, we keep a "selected_date" 
// which is primarily the start date if a range is selected.
$selected_date = $start_date; 
$prev_date = date('Y-m-d', strtotime($selected_date . ' -1 day'));

// --- LOGIC ---

// 1. OPENING BALANCE (Fetched for the START DATE of the range)
$op_query = "SELECT SUM(price.amount) as total FROM bank_transaction_info info 
             JOIN bank_transaction_price price ON info.info_id=price.info_id 
             WHERE info.transaction_type='deposit' 
               AND info.title='Opening Balance' 
               AND DATE(info.created_at) = '$start_date'";

$opening_balance = mysqli_fetch_assoc(mysqli_query($conn, $op_query))['total'] ?? 0;

// 2. TRANSACTIONS WITHIN RANGE
// Income
$tod_inc_query = "SELECT 
                    info.info_id,
                    info.details,
                    price.amount,
                    src.source_name as title
                  FROM bank_transaction_info info 
                  JOIN bank_transaction_price price ON info.info_id = price.info_id
                  LEFT JOIN income_sources src ON info.source_id = src.source_id
                  WHERE info.transaction_type='deposit' 
                    AND info.title != 'Opening Balance'
                    AND DATE(info.created_at) BETWEEN '$start_date' AND '$end_date'
                  ORDER BY info.created_at ASC";
$tod_inc_res = mysqli_query($conn, $tod_inc_query);
$today_incomes = [];
$today_inc_total = 0;
while($row = mysqli_fetch_assoc($tod_inc_res)) {
    $today_incomes[] = $row;
    $today_inc_total += $row['amount'];
}

// Expense
$today_expenses = [];
$today_exp_total = 0;

// A. From Bank Transactions (Withdrawals)
$tod_exp_query = "SELECT 
                    info.info_id,
                    info.details,
                    price.amount,
                    cat.category_name as title,
                    'banking' as type
                  FROM bank_transaction_info info 
                  JOIN bank_transaction_price price ON info.info_id = price.info_id
                  LEFT JOIN expense_category cat ON info.exp_category_id = cat.category_id
                  WHERE info.transaction_type='withdraw' 
                    AND DATE(info.created_at) BETWEEN '$start_date' AND '$end_date'
                  ORDER BY info.created_at ASC";
$tod_exp_res = mysqli_query($conn, $tod_exp_query);
while($row = mysqli_fetch_assoc($tod_exp_res)) {
    $today_expenses[] = $row;
    $today_exp_total += $row['amount'];
}

// B. From Generic Expenses Table
$gen_exp_query = "SELECT 
                    e.id as info_id,
                    e.note as details,
                    e.amount,
                    cat.category_name as title,
                    'generic' as type
                  FROM expenses e
                  LEFT JOIN expense_category cat ON e.category_id = cat.category_id
                  WHERE DATE(e.created_at) BETWEEN '$start_date' AND '$end_date'";
$gen_exp_res = mysqli_query($conn, $gen_exp_query);
while($row = mysqli_fetch_assoc($gen_exp_res)) {
    $today_expenses[] = $row;
    $today_exp_total += $row['amount'];
}

// 3. CLOSING BALANCE
$total_income_day = $today_inc_total; 
$closing_balance = $opening_balance + $total_income_day - $today_exp_total;

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
                            <h1 class="text-3xl font-black text-slate-900 tracking-tight">Cashbook Details</h1>
                            <span class="bg-teal-100 text-teal-800 border border-teal-200 px-3 py-1 rounded-full text-xs font-black uppercase tracking-wide">
                                <?php 
                                    if($start_date === $end_date) {
                                        echo date('F Y', strtotime($start_date));
                                    } else {
                                        echo date('d M', strtotime($start_date)) . ' - ' . date('d M Y', strtotime($end_date));
                                    }
                                ?>
                            </span>
                        </div>
                        <p class="text-slate-500 font-medium text-sm">Manage your daily cash flow and transactions</p>
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
                            
                            <div id="filter_date" class="hidden absolute right-0 mt-2 w-80 bg-white rounded-2xl shadow-2xl border border-slate-100 z-[110] overflow-hidden">
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
                                
                                <!-- Custom Calendar Picker (Same as reusable_list) -->
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
                </div> <!-- Closing header flex container -->

                <!-- NEW DESIGN: 7-Card Grid Summary -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">
                    
                    <!-- 1. Opening Balance (Clickable) -->
                    <div onclick="openOpeningBalanceModal()" class="relative group bg-white p-6 rounded-[2rem] border border-slate-100 shadow-xl hover:shadow-2xl transition-all cursor-pointer overflow-hidden">
                        <div class="absolute top-0 right-0 p-4 opacity-50 group-hover:opacity-100 transition-opacity">
                            <i class="fas fa-edit text-slate-400 group-hover:text-teal-500"></i>
                        </div>
                        <div class="flex items-center gap-4 mb-3">
                            <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-500 flex items-center justify-center text-xl group-hover:bg-teal-50 group-hover:text-teal-600 transition-colors">
                                <i class="fas fa-wallet"></i>
                            </div>
                            <span class="font-bold text-slate-500 uppercase text-xs tracking-widest">Opening Balance</span>
                        </div>
                        <h3 class="text-3xl font-black text-slate-700 group-hover:text-teal-700 transition-colors ml-1"><?= number_format($opening_balance, 2) ?></h3>
                    </div>

                    <!-- 2. Today Income -->
                    <div class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-xl hover:shadow-2xl transition-all">
                        <div class="flex items-center gap-4 mb-3">
                            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-500 flex items-center justify-center text-xl">
                                <i class="fas fa-arrow-down"></i>
                            </div>
                            <span class="font-bold text-slate-500 uppercase text-xs tracking-widest">Today Income</span>
                        </div>
                        <h3 class="text-3xl font-black text-emerald-600 ml-1">+ <?= number_format($today_inc_total, 2) ?></h3>
                    </div>

                    <!-- 3. Total Income (Opening + Today) -->
                    <div class="bg-gradient-to-br from-emerald-500 to-teal-600 p-6 rounded-[2rem] shadow-xl shadow-emerald-200 hover:shadow-2xl transition-all text-white">
                        <div class="flex items-center gap-4 mb-3">
                            <div class="w-12 h-12 rounded-2xl bg-white/20 text-white flex items-center justify-center text-xl">
                                <i class="fas fa-coins"></i>
                            </div>
                            <span class="font-bold text-emerald-100 uppercase text-xs tracking-widest">Total Income</span>
                        </div>
                        <h3 class="text-3xl font-black ml-1"><?= number_format($total_income_day, 2) ?></h3>
                    </div>

                    <!-- 4. Today Expense -->
                    <div class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-xl hover:shadow-2xl transition-all">
                        <div class="flex items-center gap-4 mb-3">
                            <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-500 flex items-center justify-center text-xl">
                                <i class="fas fa-arrow-up"></i>
                            </div>
                            <span class="font-bold text-slate-500 uppercase text-xs tracking-widest">Today Expense</span>
                        </div>
                        <h3 class="text-3xl font-black text-rose-600 ml-1">- <?= number_format($today_exp_total, 2) ?></h3>
                    </div>

                    <!-- 5. Net Profit/Change (Income - Expense) -->
                    <div class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-xl hover:shadow-2xl transition-all">
                        <div class="flex items-center gap-4 mb-3">
                            <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-500 flex items-center justify-center text-xl">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <span class="font-bold text-slate-500 uppercase text-xs tracking-widest">Net Change</span>
                        </div>
                        <h3 class="text-3xl font-black text-indigo-600 ml-1"><?= number_format($today_inc_total - $today_exp_total, 2) ?></h3>
                    </div>

                    <!-- 6. Cash In Hand (Balance) -->
                    <div class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-xl hover:shadow-2xl transition-all">
                        <div class="flex items-center gap-4 mb-3">
                            <div class="w-12 h-12 rounded-2xl bg-sky-50 text-sky-500 flex items-center justify-center text-xl">
                                <i class="fas fa-hand-holding-usd"></i>
                            </div>
                            <span class="font-bold text-slate-500 uppercase text-xs tracking-widest">Cash In Hand</span>
                        </div>
                        <h3 class="text-3xl font-black text-sky-600 ml-1"><?= number_format($closing_balance, 2) ?></h3>
                    </div>

                    <!-- 7. Closing Balance (Highlighted) - Spans 3 columns on LG -->
                    <div class="lg:col-span-3 bg-gradient-to-r from-orange-400 to-pink-500 p-8 rounded-[2rem] shadow-xl shadow-orange-300 hover:shadow-2xl transition-all text-white flex flex-col md:flex-row items-center justify-between gap-6 relative overflow-hidden">
                        <div class="absolute -right-10 -top-10 w-48 h-48 bg-white/10 rounded-full blur-3xl z-0 pointer-events-none"></div>
                        <div class="absolute -left-10 -bottom-10 w-48 h-48 bg-black/5 rounded-full blur-3xl z-0 pointer-events-none"></div>
                        
                        <div class="flex items-center gap-6 relative z-20">
                            <div class="w-16 h-16 rounded-3xl bg-white text-orange-500 flex items-center justify-center text-3xl shadow-lg">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div>
                                <h2 class="text-lg font-bold text-orange-100 uppercase tracking-widest mb-1">Final Closing Balance</h2>
                                <p class="text-orange-50 text-sm font-medium">Verified cash position for <?= date('d M Y', strtotime($selected_date)) ?></p>
                            </div>
                        </div>
                        <div class="text-5xl font-black relative z-50 tracking-tight drop-shadow-md">
                            <?= number_format($closing_balance, 2) ?>
                        </div>
                    </div>

                </div>

        <!-- Tables Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-10 items-start">
            
            <div class="bg-white rounded-[2rem] shadow-xl border border-slate-100 overflow-hidden flex flex-col">
                 <div class="p-6 border-b border-slate-100 bg-emerald-50/50 flex items-center justify-between">
                    <h3 class="font-black text-slate-900 text-lg">Credit / Income</h3>
                     <!-- Search field -->
                    <div class="relative w-32">
                        <input type="text" id="incomeSearch" placeholder="Search" class="w-full bg-white border border-slate-200 rounded-lg px-3 py-1 text-xs font-bold outline-none focus:border-emerald-500">
                    </div>
                </div>
                <div class="flex-1 overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-100/50 text-slate-500 text-[10px] uppercase tracking-widest font-bold border-b border-slate-200">
                                <th class="p-4 w-16 text-center">SL</th>
                                <th class="p-4">Title</th>
                                <th class="p-4 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody id="incomeTableBody" class="divide-y divide-slate-50 text-sm">
                            <?php if(empty($today_incomes)): ?>
                                <tr><td colspan="3" class="p-6 text-center text-slate-400 italic">No income transactions today</td></tr>
                            <?php else: ?>
                                <?php foreach($today_incomes as $i => $row): ?>
                                <tr class="hover:bg-slate-50">
                                    <td class="p-4 text-center text-slate-400 font-bold"><?= $i+1 ?></td>
                                    <td class="p-4 font-bold text-slate-700"><?= $row['title'] ?: $row['details'] ?></td>
                                    <td class="p-4 text-right font-bold text-emerald-600"><?= number_format($row['amount'], 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot class="bg-gray-100 font-black text-slate-800 text-xs uppercase tracking-widest">
                            <tr>
                                <td colspan="2" class="p-4 text-center">Total</td>
                                <td class="p-4 text-right"><?= number_format($today_inc_total, 2) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="bg-white rounded-[2rem] shadow-xl border border-slate-100 overflow-hidden flex flex-col">
                 <div class="p-6 border-b border-slate-100 bg-rose-50/50 flex items-center justify-between">
                    <h3 class="font-black text-slate-900 text-lg">Debit / Expense</h3>
                    <!-- Search field -->
                    <div class="relative w-32">
                        <input type="text" id="expenseSearch" placeholder="Search" class="w-full bg-white border border-slate-200 rounded-lg px-3 py-1 text-xs font-bold outline-none focus:border-rose-500">
                    </div>
                </div>
                <div class="flex-1 overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-100/50 text-slate-500 text-[10px] uppercase tracking-widest font-bold border-b border-slate-200">
                                <th class="p-4 w-16 text-center">SL</th>
                                <th class="p-4">Title</th>
                                <th class="p-4 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody id="expenseTableBody" class="divide-y divide-slate-50 text-sm">
                            <?php if(empty($today_expenses)): ?>
                                <tr><td colspan="3" class="p-6 text-center text-slate-400 italic">No expense transactions today</td></tr>
                            <?php else: ?>
                                <?php foreach($today_expenses as $i => $row): ?>
                                <tr class="hover:bg-slate-50">
                                    <td class="p-4 text-center text-slate-400 font-bold"><?= $i+1 ?></td>
                                    <td class="p-4 font-bold text-slate-700"><?= $row['title'] ?: $row['details'] ?></td>
                                    <td class="p-4 text-right font-bold text-rose-600"><?= number_format($row['amount'], 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot class="bg-gray-100 font-black text-slate-800 text-xs uppercase tracking-widest">
                            <tr>
                                <td colspan="2" class="p-4 text-center">Total</td>
                                <td class="p-4 text-right"><?= number_format($today_exp_total, 2) ?></td>
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

<!-- Unique Opening Balance Modal -->
<div id="openingBalanceModal" class="hidden fixed inset-0 z-[100] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <!-- Backdrop with blur -->
    <div class="fixed inset-0 bg-slate-600/60 backdrop-blur-md transition-opacity" onclick="closeOpeningBalanceModal()"></div>

    <div class="flex items-center justify-center min-h-screen p-4 text-center">
        <!-- Modal Panel -->
        <div class="relative bg-white rounded-[2.5rem] shadow-2xl w-full max-w-md overflow-hidden transform transition-all scale-100 p-8">
            
            <!-- Close Button -->
            <button onclick="closeOpeningBalanceModal()" class="absolute top-4 right-4 w-10 h-10 bg-slate-100 hover:bg-slate-200 text-slate-500 rounded-full flex items-center justify-center transition-all shadow-sm z-50">
                <i class="fas fa-times text-lg"></i>
            </button>

            <!-- Icon Header -->
            <div class="flex justify-center mb-6 mt-4">
                <div class="w-20 h-20 bg-white rounded-3xl flex items-center justify-center shadow-[0_8px_30px_rgb(0,0,0,0.06)] border border-slate-50">
                    <i class="fas fa-wallet text-4xl text-emerald-500"></i>
                </div>
            </div>

            <div class="text-center relative">
                <h3 class="text-3xl font-black text-slate-900 mb-2 tracking-tight">Opening Balance</h3>
                <p class="text-slate-400 text-sm font-bold mb-10">Set cash amount for <span class="text-emerald-500"><?= date('M d, Y', strtotime($selected_date)) ?></span></p>

                <form action="bank_transaction_handler.php" method="POST" id="opBalanceForm" autocomplete="off">
                    <input type="hidden" name="action" value="add_opening_balance">
                    <input type="hidden" name="redirect" value="/pos/accounting/cashbook?date=<?= $selected_date ?>">
                    <input type="hidden" name="date" value="<?= $selected_date ?>">

                    <!-- Amount Input (Unique & Large) -->
                    <style>
                        /* Force hide spinners for this specific input */
                        #amountInput::-webkit-outer-spin-button,
                        #amountInput::-webkit-inner-spin-button {
                            -webkit-appearance: none;
                            margin: 0;
                        }
                        #amountInput {
                            -moz-appearance: textfield;
                        }
                    </style>
                    <div class="relative mb-8 group">
                        <div class="absolute inset-x-0 top-1/2 -translate-y-1/2 h-[1px] bg-slate-100 -z-10 group-hover:bg-emerald-100 transition-colors"></div>
                        <div class="relative flex items-center justify-center">
                            <span class="text-4xl font-bold text-slate-300 mr-2">$</span>
                            <input type="number" step="0.01" name="amount" id="amountInput" required placeholder="0.00" autofocus autocomplete="off"
                                   class="block w-48 text-center text-5xl font-black text-slate-800 bg-transparent border-b-4 border-slate-200 focus:border-emerald-500 outline-none placeholder-slate-200 transition-all p-2">
                        </div>
                        <p class="text-xs text-slate-400 mt-2 font-bold uppercase tracking-widest">Enter Amount</p>
                    </div>

                    <!-- Details (Subtle) -->
                    <div class="mb-8">
                         <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-pen text-slate-300"></i>
                            </div>
                            <input type="text" name="details" value="Opening Balance" placeholder="Note (Optional)"
                                   class="pl-10 w-full bg-slate-50 border-none rounded-xl py-3 text-slate-600 font-bold text-sm focus:ring-2 focus:ring-emerald-100 transition-all text-center">
                        </div>
                    </div>

                    <button type="submit" class="w-full py-4 bg-slate-500 hover:bg-emerald-600 text-white rounded-2xl font-black text-lg shadow-xl shadow-slate-200 hover:shadow-emerald-200 transform hover:-translate-y-1 transition-all flex items-center justify-center gap-3">
                        <i class="fas fa-check-circle"></i>
                        <span>Save Opening Balance</span>
                    </button>
                    
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function openOpeningBalanceModal() {
    const modal = document.getElementById('openingBalanceModal');
    modal.classList.remove('hidden');
    // Animate in
    const panel = modal.querySelector('.relative.bg-white');
    panel.classList.remove('scale-95', 'opacity-0');
    panel.classList.add('scale-100', 'opacity-100');
    
    // Focus amount
    setTimeout(() => {
        modal.querySelector('input[name="amount"]').focus();
    }, 100);
}

function closeOpeningBalanceModal() {
    const modal = document.getElementById('openingBalanceModal');
    // Animate out (optional, simple hide for now to prevent issues)
    modal.classList.add('hidden');
}

// Live Search Functionality
function setupSearch(inputId, tableBodyId) {
    const searchInput = document.getElementById(inputId);
    const tableBody = document.getElementById(tableBodyId);
    
    if (searchInput && tableBody) {
        searchInput.addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
            const rows = tableBody.getElementsByTagName('tr');
            
            for (let i = 0; i < rows.length; i++) {
                const textContent = rows[i].textContent || rows[i].innerText;
                if (textContent.toLowerCase().indexOf(filter) > -1) {
                    rows[i].style.display = "";
                } else {
                    rows[i].style.display = "none";
                }
            }
        });
    }
}

document.addEventListener('DOMContentLoaded', function() {
    setupSearch('incomeSearch', 'incomeTableBody');
    setupSearch('expenseSearch', 'expenseTableBody');
});
// --- Advanced Date Filter Scripts ---
let calendarCurrentDate = new Date();
let calendarStartDate = '<?= $start_date ?>';
let calendarEndDate = '<?= $end_date ?>';

function toggleFilterDropdown(id) {
    const dropdown = document.getElementById(id);
    const isHidden = dropdown.classList.contains('hidden');
    
    // Close all other dropdowns
    document.querySelectorAll('.relative > div[id]').forEach(d => d.classList.add('hidden'));
    
    if(isHidden) {
        dropdown.classList.remove('hidden');
    }
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
    const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
    
    display.textContent = `${monthNames[month]} ${year}`;
    
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    
    for(let i = 0; i < firstDay; i++) {
        grid.appendChild(document.createElement('div'));
    }
    
    for(let day = 1; day <= daysInMonth; day++) {
        const dateObj = new Date(year, month, day);
        const dateStr = formatCalendarDate(dateObj);
        
        const dayCellWrapper = document.createElement('div');
        dayCellWrapper.className = 'flex items-center justify-center w-full relative h-9';
        
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.textContent = day;
        let btnClasses = 'w-8 h-8 flex items-center justify-center text-[11px] font-bold rounded-full relative z-10 transition-all ';
        
        if(calendarStartDate && calendarEndDate) {
            const start = new Date(calendarStartDate);
            const end = new Date(calendarEndDate);
            if(dateObj > start && dateObj < end) {
                dayCellWrapper.classList.add('bg-teal-50');
                btnClasses += 'text-teal-700';
            } else if(dateStr === calendarStartDate) {
                btnClasses += 'bg-teal-600 text-white';
                dayCellWrapper.style.background = 'linear-gradient(to right, transparent 50%, #f0fdfa 50%)';
            } else if(dateStr === calendarEndDate) {
                btnClasses += 'bg-teal-600 text-white';
                dayCellWrapper.style.background = 'linear-gradient(to left, transparent 50%, #f0fdfa 50%)';
            } else {
                btnClasses += 'text-slate-600 hover:bg-slate-100';
            }
        } else if(calendarStartDate && dateStr === calendarStartDate) {
            btnClasses += 'bg-teal-600 text-white';
        } else {
            btnClasses += 'text-slate-600 hover:bg-slate-100';
        }
        
        btn.className = btnClasses;
        btn.onclick = (e) => {
            e.stopPropagation();
            selectCalendarDate(dateObj);
        };
        
        dayCellWrapper.appendChild(btn);
        grid.appendChild(dayCellWrapper);
    }
    updateCalendarRangeDisplay();
}

function selectCalendarDate(date) {
    const dateStr = formatCalendarDate(date);
    if(!calendarStartDate || (calendarStartDate && calendarEndDate)) {
        calendarStartDate = dateStr;
        calendarEndDate = null;
    } else {
        if(new Date(dateStr) < new Date(calendarStartDate)) {
            calendarEndDate = calendarStartDate;
            calendarStartDate = dateStr;
        } else {
            calendarEndDate = dateStr;
        }
        setTimeout(() => {
            window.location.href = `${window.location.pathname}?start_date=${calendarStartDate}&end_date=${calendarEndDate}`;
        }, 300);
    }
    renderCustomCalendar();
}

function changeCalendarMonth(offset) {
    calendarCurrentDate.setMonth(calendarCurrentDate.getMonth() + offset);
    renderCustomCalendar();
}

function formatCalendarDate(date) {
    return date.toISOString().split('T')[0];
}

function updateCalendarRangeDisplay() {
    const display = document.getElementById('calendar-selected-range');
    if(calendarStartDate && calendarEndDate) {
        display.textContent = `${calendarStartDate} - ${calendarEndDate}`;
    } else if(calendarStartDate) {
        display.textContent = 'Select end date';
    }
}

// Close dropdown on outside click
document.addEventListener('click', function(e) {
    const dropdown = document.getElementById('filter_date');
    if(dropdown && !dropdown.classList.contains('hidden') && !e.target.closest('.relative')) {
        dropdown.classList.add('hidden');
    }
});

// Reuse Search Logic
document.addEventListener('DOMContentLoaded', function() {
    setupSearch('incomeSearch', 'incomeTableBody');
    setupSearch('expenseSearch', 'expenseTableBody');
});
</script>

