<?php
ob_start();
session_start();
include('../config/dbcon.php');

if(!isset($_SESSION['auth'])){
    header("Location: /pos/login");
    exit(0);
}

// Params
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : null;
$store_id = isset($_GET['store_id']) ? (int)$_GET['store_id'] : null;
$is_month_view = ($month !== null && $month >= 1 && $month <= 12);
$is_print_mode = isset($_GET['print_mode']) && $_GET['print_mode'] == '1';

// Filter
$where_store = $store_id ? " AND e.store_id='$store_id'" : "";

// Fetch All Stores for Selector
$stores_query = mysqli_query($conn, "SELECT id, store_name FROM stores WHERE status='1' ORDER BY store_name ASC");
$all_stores = [];
while($st = mysqli_fetch_assoc($stores_query)) $all_stores[] = $st;

// Resolve Dynamic Currency Symbol
$currency_symbol = ''; // Default for "All Stores"
if($store_id) {
    // Specific Store Currency
    $curr_q = mysqli_query($conn, "SELECT c.symbol_left, c.symbol_right FROM stores s JOIN currencies c ON s.currency_id = c.id WHERE s.id='$store_id'");
    if($curr_row = mysqli_fetch_assoc($curr_q)) { 
        $currency_symbol = $curr_row['symbol_left'] ?: $curr_row['symbol_right']; 
    }
}

// Fetch Categories (Always needed as headers or rows)
$cat_query = "SELECT category_id, category_name FROM expense_category WHERE status='1' ORDER BY sort_order ASC";
$cat_res = mysqli_query($conn, $cat_query);
$categories = [];
while($c = mysqli_fetch_assoc($cat_res)) $categories[] = $c;

$month_names = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
$short_months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

// Fetch Recent Expenditures (Top 5)
$recent_exp_query = "SELECT e.*, c.category_name FROM expenses e 
                    LEFT JOIN expense_category c ON e.category_id = c.category_id 
                    WHERE 1=1 $where_store 
                    ORDER BY e.created_at DESC LIMIT 5";
$recent_exp_res = mysqli_query($conn, $recent_exp_query);
$recent_expenses = [];
while($re = mysqli_fetch_assoc($recent_exp_res)) $recent_expenses[] = $re;

$report_data = [];
$totals_line = [];
$grand_total = 0;

if(!$is_month_view) {
    /** 
     * YEARLY VIEW: Rows = Categories, Cols = 12 Months
     */
    $totals_line = array_fill(1, 12, 0);
    foreach($categories as $cat) {
        $cid = $cat['category_id'];
        $row = ['id' => $cid, 'name' => $cat['category_name'], 'cells' => []];
        $cat_year_total = 0;
        
        for($m = 1; $m <= 12; $m++) {
            $q = "SELECT SUM(amount) as total FROM expenses e 
                  WHERE e.category_id='$cid' AND YEAR(e.created_at)='$year' AND MONTH(e.created_at)='$m' $where_store";
            $r = mysqli_query($conn, $q);
            $val = mysqli_fetch_assoc($r)['total'] ?? 0;
            
            $row['cells'][$m] = $val;
            $totals_line[$m] += $val;
            $cat_year_total += $val;
        }
        $row['total'] = $cat_year_total;
        $grand_total += $cat_year_total;
        $report_data[] = $row;
    }
} else {
    /**
     * MONTHLY VIEW: Rows = Days (1-31), Cols = Categories
     */
    $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    $totals_line = array_fill_keys(array_column($categories, 'category_id'), 0);
    
    for($d = 1; $d <= $days_in_month; $d++) {
        $row = ['day' => $d, 'cells' => []];
        $day_total = 0;
        
        foreach($categories as $cat) {
            $cid = $cat['category_id'];
            $q = "SELECT SUM(amount) as total FROM expenses e 
                  WHERE e.category_id='$cid' AND YEAR(e.created_at)='$year' AND MONTH(e.created_at)='$month' AND DAY(e.created_at)='$d' $where_store";
            $r = mysqli_query($conn, $q);
            $val = mysqli_fetch_assoc($r)['total'] ?? 0;
            
            $row['cells'][$cid] = $val;
            $totals_line[$cid] += $val;
            $day_total += $val;
        }
        $row['total'] = $day_total;
        $grand_total += $day_total;
        $report_data[] = $row;
    }
}

// Highest Category Logic
$max_cat_val = 0; $max_cat_name = 'N/A';
if(!$is_month_view) {
    foreach($report_data as $r) { if($r['total'] > $max_cat_val) { $max_cat_val = $r['total']; $max_cat_name = $r['name']; } }
} else {
    foreach($totals_line as $cid => $v) { if($v > $max_cat_val) { $max_cat_val = $v; foreach($categories as $c) if($c['category_id'] == $cid) $max_cat_name = $c['category_name']; } }
}

// --- DATA PREPARATION FOR GRAPH ---
$chart_labels = [];
$chart_values = [];
if(!$is_month_view) {
    $chart_labels = $short_months;
    for($m=1; $m<=12; $m++) $chart_values[] = (float)($totals_line[$m] ?? 0);
} else {
    for($d=1; $d<=$days_in_month; $d++) $chart_labels[] = "Day $d";
    foreach($report_data as $rd) $chart_values[] = (float)$rd['total'];
}
$chart_json = json_encode(['labels' => $chart_labels, 'data' => $chart_values]);

include('../includes/header.php');

// AJAX partial response
if(isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    ob_end_clean(); // Discard anything from header.php
    ?>
    <!-- Hidden Chart Data (Refreshes on AJAX) -->
    <textarea id="chart-data-provider" class="hidden"><?= $chart_json ?></textarea>

    <style>
        /* Force table header background to fix invisible column */
        .glass-card table thead tr th {
            background-color: #134e4a !important; /* teal-900 */
            color: white !important;
        }
        .glass-card table thead tr th:last-child {
            background-color: #020617 !important; /* slate-950 */
        }
    </style>

    <!-- Premium Header (Hidden on Print) -->
    <div class="mb-8 header-controls no-print">
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
            <div class="space-y-2">
                <nav class="flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-slate-400">
                    <a href="javascript:void(0)" onclick="loadReport('/pos/expenditure/monthwise/<?= $year ?>')" class="hover:text-teal-600 transition-colors">Expenditure</a>
                    <?php if($is_month_view): ?>
                        <i class="fas fa-chevron-right text-[8px]"></i>
                        <span class="text-slate-800"><?= $month_names[$month-1] ?></span>
                    <?php endif; ?>
                </nav>
                <h1 class="text-4xl font-black text-slate-900 tracking-tight">
                    <?= $is_month_view ? $month_names[$month-1] . ', ' . $year : 'Yearly Report ' . $year ?>
                </h1>
                <p class="text-slate-500 font-medium max-w-xl">
                    <?= $is_month_view ? "Daily breakdown of all expense categories for this month." : "Comprehensive overview of all categories across 12 months." ?>
                </p>
            </div>
            
            <div class="controls-wrapper">
                <div class="flex items-center gap-4 bg-white/50 backdrop-blur-md p-2 rounded-2xl border border-white/50 shadow-sm">
                    <!-- Navigation Group -->
                    <div class="flex flex-wrap items-center bg-slate-100 rounded-xl p-1 gap-1">
                        <button onclick="navigateReport('prev')" class="w-10 h-10 flex items-center justify-center rounded-lg hover:bg-white hover:text-teal-600 transition-all text-slate-500" title="Previous">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        
                        <div class="selects-container flex flex-wrap items-center bg-white rounded-lg shadow-sm border border-slate-200 px-2 divide-x divide-slate-100">
                            <!-- Store Selector -->
                            <select id="store-select" class="bg-transparent border-0 px-3 py-2 text-sm font-bold outline-none focus:ring-0 transition-all min-w-[160px] text-slate-800">
                                <option value="">All Stores</option>
                                <?php foreach($all_stores as $s): ?>
                                    <option value="<?= $s['id'] ?>" <?= $s['id'] == $store_id ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($s['store_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <select id="month-select" class="bg-transparent border-0 px-3 py-2 text-sm font-bold outline-none focus:ring-0 transition-all min-w-[140px] text-slate-800">
                                <option value="" <?= !$is_month_view ? 'selected' : '' ?>>Yearly Overview</option>
                                <?php foreach($month_names as $m_idx => $m_name): ?>
                                    <option value="<?= str_pad($m_idx+1, 2, '0', STR_PAD_LEFT) ?>" <?= ($is_month_view && (int)$month == ($m_idx+1)) ? 'selected' : '' ?>>
                                        <?= $m_name ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <select id="year-select" class="bg-transparent border-0 px-4 py-2 text-sm font-bold outline-none focus:ring-0 transition-all min-w-[100px] text-slate-800">
                                <?php for($i = date('Y'); $i >= 2020; $i--): ?>
                                    <option value="<?= $i ?>" <?= $i == $year ? 'selected' : '' ?>><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <button onclick="navigateReport('next')" class="w-10 h-10 flex items-center justify-center rounded-lg hover:bg-white hover:text-teal-600 transition-all text-slate-500" title="Next">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                    
                    <?php if($is_month_view): ?>
                        <a href="javascript:void(0)" onclick="loadReport('/pos/expenditure/monthwise/<?= $year ?>')" class="bg-teal-600 hover:bg-teal-700 text-white px-5 py-3 rounded-xl text-sm font-bold transition-all shadow-lg shadow-teal-200 flex items-center gap-2 group">
                            <i class="fas fa-th group-hover:scale-110 transition-transform"></i> Yearly
                        </a>
                    <?php endif; ?>

                    <button onclick="resetReport()" class="bg-rose-50 hover:bg-rose-100 text-rose-600 px-4 py-3 rounded-xl text-sm font-bold border border-rose-100 transition-all flex items-center gap-2 transform active:scale-95" title="Reset All Filters">
                        <i class="fas fa-undo"></i> <span>Reset</span>
                    </button>

                    <button onclick="openPrintTab()" class="bg-teal-600 hover:bg-teal-700 text-white px-6 py-3 rounded-xl text-sm font-bold shadow-lg shadow-teal-100 transition-all flex items-center gap-2 transform active:scale-95">
                        <i class="fas fa-print"></i> <span>Export</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Report Table -->
    <div class="glass-card rounded-3xl shadow-2xl border border-white/40 overflow-hidden bg-white/80 backdrop-blur-xl">
        <div class="overflow-x-auto custom-scroll">
            <table class="w-full text-left border-collapse min-w-[1200px]">
                <thead>
                    <tr class="bg-teal-900 text-white border-b border-teal-800">
                        <th class="p-6 text-xs font-black uppercase tracking-widest sticky left-0 bg-teal-900 z-20 border-r border-teal-800 min-w-[200px]">
                            <?= $is_month_view ? 'Days \ Categories' : 'Categories \ Months' ?>
                        </th>
                        <?php if(!$is_month_view): ?>
                            <?php foreach($short_months as $index => $m): ?>
                                <th class="p-4 text-xs font-black uppercase tracking-widest text-center border-r border-slate-800/50 cursor-pointer hover:bg-teal-600 transition-colors" onclick="loadReport('/pos/expenditure/monthwise/<?= $year ?>/<?= str_pad($index+1, 2, '0', STR_PAD_LEFT) ?>')">
                                    <?= $m ?>
                                </th>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <?php foreach($categories as $cat): ?>
                                <th class="p-4 text-xs font-black uppercase tracking-widest text-center border-r border-slate-800/50">
                                    <?= htmlspecialchars($cat['category_name']) ?>
                                </th>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <th class="p-6 text-xs font-black uppercase tracking-widest text-right bg-slate-950">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                    <?php if(!$is_month_view): ?>
                        <!-- YEARLY BODY -->
                        <?php foreach($report_data as $row): ?>
                            <tr class="hover:bg-teal-50/30 transition-colors group">
                                <td class="p-5 text-sm font-bold text-slate-800 sticky left-0 bg-white group-hover:bg-slate-50/50 border-r border-slate-100 z-10"><?= htmlspecialchars($row['name']) ?></td>
                                <?php for($m=1; $m<=12; $m++): 
                                    $val = $row['cells'][$m];
                                    $cellClass = $val > 0 ? 'cell-active font-black' : 'text-slate-200/50';
                                ?>
                                    <td class="p-5 text-sm text-center border-r border-slate-50 <?= $cellClass ?> cursor-pointer hover:bg-white transition-all shadow-inner" onclick="loadReport('/pos/expenditure/monthwise/<?= $year ?>/<?= str_pad($m, 2, '0', STR_PAD_LEFT) ?>')">
                                        <?= $val > 0 ? number_format($val, 0) : '-' ?>
                                    </td>
                                <?php endfor; ?>
                                <td class="p-5 text-sm font-black text-right text-slate-900 bg-slate-50/50"><?= number_format($row['total'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- MONTHLY BODY -->
                        <?php foreach($report_data as $row): ?>
                            <tr class="hover:bg-emerald-50/30 transition-colors group">
                                <td class="p-4 text-sm font-black text-slate-500 text-center sticky left-0 bg-slate-50 group-hover:bg-slate-100 border-r border-slate-200 z-10 w-16">
                                    <?= $row['day'] ?>
                                </td>
                                <?php foreach($categories as $cat): 
                                    $val = $row['cells'][$cat['category_id']];
                                    $cellClass = $val > 0 ? 'bg-emerald-50 text-emerald-700 font-black' : 'text-slate-200/30';
                                ?>
                                    <td class="p-4 text-sm text-center border-r border-slate-50 <?= $cellClass ?>">
                                        <?= $val > 0 ? number_format($val, 0) : '-' ?>
                                    </td>
                                <?php endforeach; ?>
                                <td class="p-4 text-sm font-black text-right text-slate-900 bg-slate-50/50 border-l border-emerald-100"><?= number_format($row['total'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr class="bg-teal-50 backdrop-blur-md">
                        <td class="p-6 text-sm font-black uppercase tracking-widest text-teal-900 sticky left-0 bg-teal-100/50 border-r border-teal-200 z-10">Grand Total</td>
                        <?php if(!$is_month_view): ?>
                            <?php for($m=1; $m<=12; $m++): ?>
                                <td class="p-6 text-sm font-black text-center border-r border-white italic text-teal-700">
                                    <?= $totals_line[$m] > 0 ? number_format($totals_line[$m], 0) : '-' ?>
                                </td>
                            <?php endfor; ?>
                        <?php else: ?>
                            <?php foreach($categories as $cat): ?>
                                <td class="p-6 text-sm font-black text-center border-r border-white italic text-teal-700">
                                    <?= $totals_line[$cat['category_id']] > 0 ? number_format($totals_line[$cat['category_id']], 0) : '-' ?>
                                </td>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <td class="p-6 text-lg font-black text-right text-teal-900 bg-teal-200/30 border-l-2 border-teal-200"><?= $currency_symbol ?> <?= number_format($grand_total, 2) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Footer Summary & Analytics -->
    <div class="flex flex-col lg:flex-row gap-8 mt-10 no-print">
        <!-- Analytics Graph Card -->
        <div class="flex-1 bg-white p-8 rounded-[2.5rem] shadow-xl border border-slate-100 relative overflow-hidden">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h3 class="text-xl font-black text-slate-900">Expenditure Analytics</h3>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Visual Trends Overview</p>
                </div>
                <div class="flex items-center gap-2 bg-teal-50 px-4 py-2 rounded-xl border border-teal-100">
                    <span class="w-2 h-2 rounded-full bg-teal-500 animate-pulse"></span>
                    <span class="text-[10px] font-black text-teal-700 uppercase">Live Data</span>
                </div>
            </div>
            <div class="h-64 relative">
                <canvas id="expenditureChart"></canvas>
            </div>
        </div>

        <!-- Summary Tiles Column -->
        <div class="lg:w-96 space-y-6">
            <div class="bg-teal-900 p-8 rounded-[2.5rem] shadow-2xl shadow-teal-100 text-white border border-teal-800 relative overflow-hidden group">
                <div class="absolute -right-4 -bottom-4 opacity-10 group-hover:scale-110 transition-transform duration-500 text-9xl">
                    <i class="fas fa-wallet"></i>
                </div>
                <p class="text-xs font-bold uppercase tracking-widest opacity-60 mb-2">Total Overview</p>
                <h3 class="text-3xl font-black tracking-tighter"><?= $currency_symbol ?> <?= number_format($grand_total, 2) ?></h3>
                <p class="text-[10px] font-bold mt-4 px-3 py-1 bg-teal-500/20 text-teal-400 rounded-full inline-block uppercase tracking-widest">Expenditure Confirmed</p>
            </div>

            <div class="bg-white p-6 rounded-[2.5rem] shadow-xl border border-slate-100 flex flex-col">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h4 class="text-lg font-black text-slate-900 tracking-tight">Recent Expenses</h4>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Last 5 Transactions</p>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center text-lg">
                        <i class="fas fa-history"></i>
                    </div>
                </div>

                <div class="space-y-4 flex-1">
                    <?php if(empty($recent_expenses)): ?>
                        <div class="flex flex-col items-center justify-center h-full text-center py-10">
                            <i class="fas fa-ghost text-slate-200 text-4xl mb-3"></i>
                            <p class="text-sm font-bold text-slate-400">No recent expenses</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($recent_expenses as $re): ?>
                            <div class="group flex items-center gap-4 p-3 rounded-2xl hover:bg-teal-50/50 border border-transparent hover:border-teal-100 transition-all cursor-pointer">
                                <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-500 group-hover:bg-teal-600 group-hover:text-white flex items-center justify-center transition-all">
                                    <i class="fas fa-receipt text-xs"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h5 class="text-sm font-black text-slate-800 truncate leading-tight"><?= htmlspecialchars($re['title']) ?></h5>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span class="text-[9px] font-bold text-teal-600 uppercase bg-teal-50 px-1.5 py-0.5 rounded"><?= htmlspecialchars($re['category_name']) ?></span>
                                        <span class="text-[9px] font-bold text-slate-400 flex items-center gap-1">
                                            <i class="far fa-clock"></i> <?= date('h:i A', strtotime($re['created_at'])) ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-black text-slate-900"><?= $currency_symbol ?> <?= number_format($re['amount'], 0) ?></p>
                                    <p class="text-[9px] font-bold text-slate-400"><?= date('d M', strtotime($re['created_at'])) ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <a href="/pos/expenditure/expense_list" class="mt-6 w-full py-3 rounded-xl bg-slate-50 hover:bg-teal-600 text-slate-500 hover:text-white text-[10px] font-black uppercase tracking-widest text-center transition-all border border-slate-100 hover:border-teal-600">
                    View All Expenses <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
        </div>
    </div>
    <?php
    exit;
}
?>

<link rel="stylesheet" href="/pos/assets/css/expenditureCss/expense_monthwiseCss.css">

<div class="app-wrapper">
    <div class="no-print"><?php include('../includes/sidebar.php'); ?></div>
    
    <main id="main-content" class="flex-1 lg:ml-64 flex flex-col h-screen min-w-0 transition-all duration-300">
        <div class="navbar-fixed-top no-print"><?php include('../includes/navbar.php'); ?></div> 
        
        <div class="content-scroll-area custom-scroll h-full overflow-y-auto" id="report-page-container">
            <div id="ajax-container" class="p-6">
                <!-- Hidden Chart Data (Refreshes on AJAX) -->
                <textarea id="chart-data-provider" class="hidden"><?= $chart_json ?></textarea>
                <!-- Dedicated Print Header (Only visible on print) -->
                <div class="print-only-header hidden">
                    <div class="text-center mb-8 border-b-2 border-slate-900 pb-6">
                        <h1 class="text-4xl font-black text-slate-900 mb-2">Velocity POS - Expenditure Report</h1>
                        <div class="flex items-center justify-center gap-6 text-sm font-bold text-slate-600 uppercase tracking-widest">
                            <span>Report Type: <?= $is_month_view ? 'Monthly Breakdown' : 'Yearly Overview' ?></span>
                            <span>•</span>
                            <span>Period: <?= $is_month_view ? $month_names[$month-1] . ', ' . $year : 'Fiscal Year ' . $year ?></span>
                            <span>•</span>
                            <span>Generated: <?= date('d M Y, h:i A') ?></span>
                        </div>
                    </div>
                </div>

                <!-- Premium Header (Hidden on Print) -->
                <div class="mb-8 header-controls no-print">
                    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
                        <div class="space-y-2">
                            <nav class="flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-slate-400">
                                <a href="javascript:void(0)" onclick="loadReport('/pos/expenditure/monthwise/<?= $year ?>')" class="hover:text-teal-600 transition-colors">Expenditure</a>
                                <?php if($is_month_view): ?>
                                    <i class="fas fa-chevron-right text-[8px]"></i>
                                    <span class="text-slate-800"><?= $month_names[$month-1] ?></span>
                                <?php endif; ?>
                            </nav>
                            <h1 class="text-4xl font-black text-slate-900 tracking-tight">
                                <?= $is_month_view ? $month_names[$month-1] . ', ' . $year : 'Yearly Report ' . $year ?>
                            </h1>
                            <p class="text-slate-500 font-medium max-w-xl">
                                <?= $is_month_view ? "Daily breakdown of all expense categories for this month." : "Comprehensive overview of all categories across 12 months." ?>
                            </p>
                        </div>
                        
                        <div class="controls-wrapper">
                            <div class="flex items-center gap-4 bg-white/50 backdrop-blur-md p-2 rounded-2xl border border-white/50 shadow-sm">
                                <!-- Navigation Group -->
                                <div class="flex flex-wrap items-center bg-slate-100 rounded-xl p-1 gap-1">
                                    <button onclick="navigateReport('prev')" class="w-10 h-10 flex items-center justify-center rounded-lg hover:bg-white hover:text-teal-600 transition-all text-slate-500" title="Previous">
                                        <i class="fas fa-chevron-left"></i>
                                    </button>
                                    
                                    <div class="selects-container flex flex-wrap items-center bg-white rounded-lg shadow-sm border border-slate-200 px-2 divide-x divide-slate-100">
                                        <!-- Store Selector -->
                                        <select id="store-select" class="bg-transparent border-0 px-3 py-2 text-sm font-bold outline-none focus:ring-0 transition-all min-w-[160px] text-slate-800">
                                            <option value="">All Stores</option>
                                            <?php foreach($all_stores as $s): ?>
                                                <option value="<?= $s['id'] ?>" <?= $s['id'] == $store_id ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($s['store_name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>

                                        <select id="month-select" class="bg-transparent border-0 px-3 py-2 text-sm font-bold outline-none focus:ring-0 transition-all min-w-[140px] text-slate-800">
                                            <option value="" <?= !$is_month_view ? 'selected' : '' ?>>Yearly Overview</option>
                                            <?php foreach($month_names as $m_idx => $m_name): ?>
                                                <option value="<?= str_pad($m_idx+1, 2, '0', STR_PAD_LEFT) ?>" <?= ($is_month_view && (int)$month == ($m_idx+1)) ? 'selected' : '' ?>>
                                                    <?= $m_name ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>

                                        <select id="year-select" class="bg-transparent border-0 px-4 py-2 text-sm font-bold outline-none focus:ring-0 transition-all min-w-[100px] text-slate-800">
                                            <?php for($i = date('Y'); $i >= 2020; $i--): ?>
                                                <option value="<?= $i ?>" <?= $i == $year ? 'selected' : '' ?>><?= $i ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>

                                    <button onclick="navigateReport('next')" class="w-10 h-10 flex items-center justify-center rounded-lg hover:bg-white hover:text-teal-600 transition-all text-slate-500" title="Next">
                                        <i class="fas fa-chevron-right"></i>
                                    </button>
                                </div>
                                
                                <?php if($is_month_view): ?>
                                    <a href="javascript:void(0)" onclick="loadReport('/pos/expenditure/monthwise/<?= $year ?>')" class="bg-teal-600 hover:bg-teal-700 text-white px-5 py-3 rounded-xl text-sm font-bold transition-all shadow-lg shadow-teal-200 flex items-center gap-2 group">
                                        <i class="fas fa-th group-hover:scale-110 transition-transform"></i> Yearly
                                    </a>
                                <?php endif; ?>

                                <button onclick="resetReport()" class="bg-rose-50 hover:bg-rose-100 text-rose-600 px-4 py-3 rounded-xl text-sm font-bold border border-rose-100 transition-all flex items-center gap-2 transform active:scale-95" title="Reset All Filters">
                                    <i class="fas fa-undo"></i> <span>Reset</span>
                                </button>

                                <button onclick="openPrintTab()" class="bg-teal-600 hover:bg-teal-700 text-white px-6 py-3 rounded-xl text-sm font-bold shadow-lg shadow-teal-100 transition-all flex items-center gap-2 transform active:scale-95">
                                    <i class="fas fa-print"></i> <span>Export</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Report Table -->
                <div class="glass-card rounded-3xl shadow-2xl border border-white/40 overflow-hidden bg-white/80 backdrop-blur-xl">
                    <div class="overflow-x-auto custom-scroll">
                        <table class="w-full text-left border-collapse min-w-[1200px]">
                            <thead>
                                <tr class="bg-teal-900 text-white border-b border-teal-800">
                                    <th class="p-6 text-xs font-black uppercase tracking-widest sticky left-0 bg-teal-900 z-20 border-r border-teal-800 min-w-[200px]">
                                        <?= $is_month_view ? 'Days \ Categories' : 'Categories \ Months' ?>
                                    </th>
                                    <?php if(!$is_month_view): ?>
                                        <?php foreach($short_months as $index => $m): ?>
                                            <th class="p-4 text-xs font-black uppercase tracking-widest text-center border-r border-slate-800/50 cursor-pointer hover:bg-teal-600 transition-colors" onclick="loadReport('/pos/expenditure/monthwise/<?= $year ?>/<?= str_pad($index+1, 2, '0', STR_PAD_LEFT) ?>')">
                                                <?= $m ?>
                                            </th>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <?php foreach($categories as $cat): ?>
                                            <th class="p-4 text-xs font-black uppercase tracking-widest text-center border-r border-slate-800/50">
                                                <?= htmlspecialchars($cat['category_name']) ?>
                                            </th>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    <th class="p-6 text-xs font-black uppercase tracking-widest text-right bg-slate-950">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                                <?php if(!$is_month_view): ?>
                                    <!-- YEARLY BODY -->
                                    <?php foreach($report_data as $row): ?>
                                        <tr class="hover:bg-teal-50/30 transition-colors group">
                                            <td class="p-5 text-sm font-bold text-slate-800 sticky left-0 bg-white group-hover:bg-slate-50/50 border-r border-slate-100 z-10"><?= htmlspecialchars($row['name']) ?></td>
                                            <?php for($m=1; $m<=12; $m++): 
                                                $val = $row['cells'][$m];
                                                $cellClass = $val > 0 ? 'cell-active font-black' : 'text-slate-200/50';
                                            ?>
                                                <td class="p-5 text-sm text-center border-r border-slate-50 <?= $cellClass ?> cursor-pointer hover:bg-white transition-all shadow-inner" onclick="loadReport('/pos/expenditure/monthwise/<?= $year ?>/<?= str_pad($m, 2, '0', STR_PAD_LEFT) ?>')">
                                                    <?= $val > 0 ? number_format($val, 0) : '-' ?>
                                                </td>
                                            <?php endfor; ?>
                                            <td class="p-5 text-sm font-black text-right text-slate-900 bg-slate-50/50"><?= number_format($row['total'], 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <!-- MONTHLY BODY -->
                                    <?php foreach($report_data as $row): ?>
                                        <tr class="hover:bg-emerald-50/30 transition-colors group">
                                            <td class="p-4 text-sm font-black text-slate-500 text-center sticky left-0 bg-slate-50 group-hover:bg-slate-100 border-r border-slate-200 z-10 w-16">
                                                <?= $row['day'] ?>
                                            </td>
                                            <?php foreach($categories as $cat): 
                                                $val = $row['cells'][$cat['category_id']];
                                                $cellClass = $val > 0 ? 'bg-emerald-50 text-emerald-700 font-black' : 'text-slate-200/30';
                                            ?>
                                                <td class="p-4 text-sm text-center border-r border-slate-50 <?= $cellClass ?>">
                                                    <?= $val > 0 ? number_format($val, 0) : '-' ?>
                                                </td>
                                            <?php endforeach; ?>
                                            <td class="p-4 text-sm font-black text-right text-slate-900 bg-slate-50/50 border-l border-emerald-100"><?= number_format($row['total'], 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                            <tfoot>
                                <tr class="bg-teal-50 backdrop-blur-md">
                                    <td class="p-6 text-sm font-black uppercase tracking-widest text-teal-900 sticky left-0 bg-teal-100/50 border-r border-teal-200 z-10">Grand Total</td>
                                    <?php if(!$is_month_view): ?>
                                        <?php for($m=1; $m<=12; $m++): ?>
                                            <td class="p-6 text-sm font-black text-center border-r border-white italic text-teal-700">
                                                <?= $totals_line[$m] > 0 ? number_format($totals_line[$m], 0) : '-' ?>
                                            </td>
                                        <?php endfor; ?>
                                    <?php else: ?>
                                        <?php foreach($categories as $cat): ?>
                                            <td class="p-6 text-sm font-black text-center border-r border-white italic text-teal-700">
                                                <?= $totals_line[$cat['category_id']] > 0 ? number_format($totals_line[$cat['category_id']], 0) : '-' ?>
                                            </td>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    <td class="p-6 text-lg font-black text-right text-teal-900 bg-teal-200/30 border-l-2 border-teal-200"><?= $currency_symbol ?> <?= number_format($grand_total, 2) ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Footer Summary & Analytics -->
                <div class="flex flex-col lg:flex-row gap-8 mt-10 no-print">
                    <!-- Analytics Graph Card -->
                    <div class="flex-1 bg-white p-8 rounded-[2.5rem] shadow-xl border border-slate-100 relative overflow-hidden">
                        <div class="flex items-center justify-between mb-8">
                            <div>
                                <h3 class="text-xl font-black text-slate-900">Expenditure Analytics</h3>
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Visual Trends Overview</p>
                            </div>
                            <div class="flex items-center gap-2 bg-teal-50 px-4 py-2 rounded-xl border border-teal-100">
                                <span class="w-2 h-2 rounded-full bg-teal-500 animate-pulse"></span>
                                <span class="text-[10px] font-black text-teal-700 uppercase">Live Data</span>
                            </div>
                        </div>
                        <div class="h-64 relative">
                            <canvas id="expenditureChart"></canvas>
                        </div>
                    </div>

                    <!-- Summary Tiles Column -->
                    <div class="lg:w-96 space-y-6">
                        <div class="bg-teal-900 p-8 rounded-[2.5rem] shadow-2xl shadow-teal-100 text-white border border-teal-800 relative overflow-hidden group">
                            <div class="absolute -right-4 -bottom-4 opacity-10 group-hover:scale-110 transition-transform duration-500 text-9xl">
                                <i class="fas fa-wallet"></i>
                            </div>
                            <p class="text-xs font-bold uppercase tracking-widest opacity-60 mb-2">Total Overview</p>
                            <h3 class="text-3xl font-black tracking-tighter"><?= $currency_symbol ?> <?= number_format($grand_total, 2) ?></h3>
                            <p class="text-[10px] font-bold mt-4 px-3 py-1 bg-teal-500/20 text-teal-400 rounded-full inline-block uppercase tracking-widest">Expenditure Confirmed</p>
                        </div>

                <div class="bg-white p-6 rounded-[2.5rem] shadow-xl border border-slate-100 flex flex-col">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h4 class="text-lg font-black text-slate-900 tracking-tight">Recent Expenses</h4>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Last 5 Transactions</p>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center text-lg">
                            <i class="fas fa-history"></i>
                        </div>
                    </div>

                    <div class="space-y-4 flex-1">
                        <?php if(empty($recent_expenses)): ?>
                            <div class="flex flex-col items-center justify-center h-full text-center py-10">
                                <i class="fas fa-ghost text-slate-200 text-4xl mb-3"></i>
                                <p class="text-sm font-bold text-slate-400">No recent expenses</p>
                            </div>
                        <?php else: ?>
                            <?php foreach($recent_expenses as $re): ?>
                                <div class="group flex items-center gap-4 p-3 rounded-2xl hover:bg-teal-50/50 border border-transparent hover:border-teal-100 transition-all cursor-pointer">
                                    <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-500 group-hover:bg-teal-600 group-hover:text-white flex items-center justify-center transition-all">
                                        <i class="fas fa-receipt text-xs"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h5 class="text-sm font-black text-slate-800 truncate leading-tight"><?= htmlspecialchars($re['title']) ?></h5>
                                        <div class="flex items-center gap-2 mt-0.5">
                                            <span class="text-[9px] font-bold text-teal-600 uppercase bg-teal-50 px-1.5 py-0.5 rounded"><?= htmlspecialchars($re['category_name']) ?></span>
                                            <span class="text-[9px] font-bold text-slate-400 flex items-center gap-1">
                                                <i class="far fa-clock"></i> <?= date('h:i A', strtotime($re['created_at'])) ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-black text-slate-900"><?= $currency_symbol ?> <?= number_format($re['amount'], 0) ?></p>
                                        <p class="text-[9px] font-bold text-slate-400"><?= date('d M', strtotime($re['created_at'])) ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <a href="/pos/expenditure/expense_list" class="mt-6 w-full py-3 rounded-xl bg-slate-50 hover:bg-teal-600 text-slate-500 hover:text-white text-[10px] font-black uppercase tracking-widest text-center transition-all border border-slate-100 hover:border-teal-600">
                        View All Expenses <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
        </div>
            </div>
        </div>
    </main>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Select2 for Searchable Dropdowns -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="/pos/assets/css/expenditureCss/select2_custom.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="/pos/assets/js/expenditureJs/expense_monthwiseJs.js"></script>
<?php include('../includes/footer.php'); 

// --- Specialized Print Mode HTML ---
if($is_print_mode):
    ob_end_clean(); // Clear any previously buffered content from header/sidebar
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Expense Monthwise - Print</title>
    <link rel="icon" type="image/x-icon" href="/pos/assets/images/logo.png" />
    <link rel="stylesheet" href="/pos/assets/css/expenditureCss/print_accounting.css">
</head>
<body onload="window.print()">
    <div class="print-container">
        <div class="report-header">
            <div class="report-title">Expense Monthwise</div>
            <div class="report-subtitle">
                <?= $is_month_view ? $month_names[$month-1] . ', ' . $year : 'Yearly Report ' . $year ?>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th class="sl-col">SL.</th>
                    <th class="text-left"><?= $is_month_view ? 'Days' : 'Categories' ?></th>
                    <?php if(!$is_month_view): ?>
                        <?php foreach($short_months as $m): ?><th><?= $m ?></th><?php endforeach; ?>
                    <?php else: ?>
                        <?php foreach($categories as $cat): ?><th><?= htmlspecialchars($cat['category_name']) ?></th><?php endforeach; ?>
                    <?php endif; ?>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if(!$is_month_view): ?>
                    <?php $sl=1; foreach($report_data as $row): ?>
                        <tr>
                            <td class="sl-col"><?= $sl++ ?></td>
                            <td class="text-left font-bold"><?= htmlspecialchars($row['name']) ?></td>
                            <?php for($m=1; $m<=12; $m++): ?><td><?= $row['cells'][$m] > 0 ? number_format($row['cells'][$m], 0) : '-' ?></td><?php endfor; ?>
                            <td class="text-right font-bold"><?= number_format($row['total'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <?php foreach($report_data as $row): ?>
                        <tr>
                            <td class="sl-col"><?= $row['day'] ?></td>
                            <td class="text-left font-bold">Day <?= $row['day'] ?></td>
                            <?php foreach($categories as $cat): 
                                $val = $row['cells'][$cat['category_id']];
                            ?>
                                <td><?= $val > 0 ? number_format($val, 0) : '-' ?></td>
                            <?php endforeach; ?>
                            <td class="text-right font-bold"><?= number_format($row['total'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="2" class="text-right font-bold">GRAND TOTAL</td>
                    <?php if(!$is_month_view): ?>
                        <?php for($m=1; $m<=12; $m++): ?>
                            <td class="font-bold text-center"><?= $totals_line[$m] > 0 ? number_format($totals_line[$m], 0) : '-' ?></td>
                        <?php endfor; ?>
                    <?php else: ?>
                        <?php foreach($categories as $cat): ?>
                            <td class="font-bold text-center"><?= $totals_line[$cat['category_id']] > 0 ? number_format($totals_line[$cat['category_id']], 0) : '-' ?></td>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <td class="text-right font-bold"><?= $currency_symbol ?> <?= number_format($grand_total, 2) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</body>
</html>
<?php 
exit; // End the script here for print mode
endif; ?>