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
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : null;

$is_month_view = ($month !== null && $month >= 1 && $month <= 12);
$is_custom_range = (!empty($start_date) && !empty($end_date));
$is_print_mode = isset($_GET['print_mode']) && $_GET['print_mode'] == '1';

// Access Control Logic
$user_role = $_SESSION['auth_user']['role_as'] ?? 'user';
$user_id = $_SESSION['auth_user']['user_id'] ?? 0;
$isAdmin = ($user_role === 'admin');

// Fetch Stores based on Permissions
$all_stores = [];
if ($isAdmin) {
    // Admin Strategy: Fetch All Active Stores
    $stores_query = mysqli_query($conn, "SELECT id, store_name FROM stores WHERE status='1' ORDER BY store_name ASC");
    while($st = mysqli_fetch_assoc($stores_query)) $all_stores[] = $st;
} else {
    // User Strategy: Fetch Assigned Stores Only
    $stores_query = mysqli_query($conn, "SELECT s.id, s.store_name 
                                        FROM stores s 
                                        JOIN user_store_map usm ON s.id = usm.store_id 
                                        WHERE s.status='1' AND usm.user_id='$user_id' 
                                        ORDER BY s.store_name ASC");
    while($st = mysqli_fetch_assoc($stores_query)) $all_stores[] = $st;
}

// Store Selection Logic
// If user requires specific store enforcement (e.g., only 1 store assigned), force select it.
if (!$isAdmin && empty($store_id)) {
    if (!empty($all_stores) && count($all_stores) == 1) {
        $store_id = $all_stores[0]['id'];
    }
} elseif (!$isAdmin && $store_id) {
    // Verify Access: Is this store_id allowed?
    $allowed = false;
    foreach($all_stores as $s) { if($s['id'] == $store_id) $allowed = true; }
    if(!$allowed && !empty($all_stores)) $store_id = $all_stores[0]['id'];
}

// Filter Logic Construction
$where_store = "";
if ($store_id) {
    $where_store = " AND info.store_id='$store_id'";
} else {
    // "All Stores" selected or implied
    if (!$isAdmin) {
        // Non-admin sees aggregate of ONLY their assigned stores
        $assigned_ids = array_column($all_stores, 'id');
        if (!empty($assigned_ids)) {
            $ids_str = implode(',', $assigned_ids);
            $where_store = " AND info.store_id IN ($ids_str)";
        } else {
            // No stores assigned? Hide everything.
            $where_store = " AND 1=0"; 
        }
    }
    // Admin sees everything (no filter needed)
}


// Resolve Dynamic Currency Symbol
$currency_symbol = ''; // Default for "All Stores"
if($store_id) {
    // Specific Store Currency
    $curr_q = mysqli_query($conn, "SELECT c.symbol_left, c.symbol_right FROM stores s JOIN currencies c ON s.currency_id = c.id WHERE s.id='$store_id'");
    if($curr_row = mysqli_fetch_assoc($curr_q)) { 
        $currency_symbol = $curr_row['symbol_left'] ?: $curr_row['symbol_right']; 
    }
}

// Fetch Income Sources (Dynamic: Only used sources)
$date_where = "";
if ($is_custom_range) {
    $date_where = "AND DATE(info.created_at) BETWEEN '$start_date' AND '$end_date'";
} elseif ($is_month_view) {
    $date_where = "AND YEAR(info.created_at)='$year' AND MONTH(info.created_at)='$month'";
} else {
    $date_where = "AND YEAR(info.created_at)='$year'";
}

$src_query = "SELECT DISTINCT s.source_id, s.source_name, s.sort_order 
              FROM income_sources s 
              JOIN bank_transaction_info info ON s.source_id = info.source_id 
              WHERE s.status='1' 
                AND info.transaction_type='deposit' 
                $date_where
                $where_store
              ORDER BY s.sort_order ASC";
$src_res = mysqli_query($conn, $src_query);
$sources = [];
while($s = mysqli_fetch_assoc($src_res)) $sources[] = $s;

$month_names = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
$short_months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

// Fetch Recent Incomes (Top 5)
$recent_query = "SELECT info.*, price.amount, src.source_name 
                 FROM bank_transaction_info info 
                 JOIN bank_transaction_price price ON info.info_id = price.info_id
                 LEFT JOIN income_sources src ON info.source_id = src.source_id 
                 WHERE info.transaction_type='deposit' $where_store 
                 ORDER BY info.created_at DESC LIMIT 5";
$recent_res = mysqli_query($conn, $recent_query);
$recent_incomes = [];
while($re = mysqli_fetch_assoc($recent_res)) $recent_incomes[] = $re;

$report_data = [];
$totals_line = [];
$grand_total = 0;

if ($is_custom_range) {
    /**
     * CUSTOM RANGE VIEW: Rows = Specific Dates in Range
     */
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $end->modify('+1 day'); // Include end date
    $period = new DatePeriod($start, new DateInterval('P1D'), $end);
    
    $totals_line = array_fill_keys(array_column($sources, 'source_id'), 0);
    
    foreach($period as $dt) {
        $curr_date = $dt->format('Y-m-d');
        $display_date = $dt->format('d M');
        
        $row = ['day' => $display_date, 'full_date' => $curr_date, 'cells' => []];
        $day_total = 0;
        
        foreach($sources as $src) {
            $sid = $src['source_id'];
            $q = "SELECT SUM(price.amount) as total 
                  FROM bank_transaction_info info 
                  JOIN bank_transaction_price price ON info.info_id = price.info_id
                  WHERE info.transaction_type='deposit' 
                    AND info.source_id='$sid' 
                    AND DATE(info.created_at)='$curr_date' 
                    $where_store";
            $r = mysqli_query($conn, $q);
            $val = mysqli_fetch_assoc($r)['total'] ?? 0;
            
            $row['cells'][$sid] = $val;
            $totals_line[$sid] += $val;
            $day_total += $val;
        }
        $row['total'] = $day_total;
        $grand_total += $day_total;
        $report_data[] = $row;
    }

} elseif (!$is_month_view) {
    /** 
     * YEARLY VIEW: Rows = Sources, Cols = 12 Months
     */
    $totals_line = array_fill(1, 12, 0);
    foreach($sources as $src) {
        $sid = $src['source_id'];
        $row = ['id' => $sid, 'name' => $src['source_name'], 'cells' => []];
        $src_year_total = 0;
        
        for($m = 1; $m <= 12; $m++) {
            // Sum deposits for this source in this month
            // Note: $src_query already filtered by YEAR. But here we query specifically by MONTH.
            // We can reuse the YEAR constraint from global context or repeat it.
            $q = "SELECT SUM(price.amount) as total 
                  FROM bank_transaction_info info 
                  JOIN bank_transaction_price price ON info.info_id = price.info_id
                  WHERE info.transaction_type='deposit' 
                    AND info.source_id='$sid' 
                    AND YEAR(info.created_at)='$year' 
                    AND MONTH(info.created_at)='$m' 
                    $where_store";
            $r = mysqli_query($conn, $q);
            $val = mysqli_fetch_assoc($r)['total'] ?? 0;
            
            $row['cells'][$m] = $val;
            $totals_line[$m] += $val;
            $src_year_total += $val;
        }
        $row['total'] = $src_year_total;
        $grand_total += $src_year_total;
        $report_data[] = $row;
    }
} else {
    /**
     * MONTHLY VIEW: Rows = Days (1-31), Cols = Sources
     */
    $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    $totals_line = array_fill_keys(array_column($sources, 'source_id'), 0);
    
    for($d = 1; $d <= $days_in_month; $d++) {
        $row = ['day' => $d, 'cells' => []];
        $day_total = 0;
        
        foreach($sources as $src) {
            $sid = $src['source_id'];
            $q = "SELECT SUM(price.amount) as total 
                  FROM bank_transaction_info info 
                  JOIN bank_transaction_price price ON info.info_id = price.info_id
                  WHERE info.transaction_type='deposit' 
                    AND info.source_id='$sid' 
                    AND YEAR(info.created_at)='$year' 
                    AND MONTH(info.created_at)='$month' 
                    AND DAY(info.created_at)='$d' 
                    $where_store";
            $r = mysqli_query($conn, $q);
            $val = mysqli_fetch_assoc($r)['total'] ?? 0;
            
            $row['cells'][$sid] = $val;
            $totals_line[$sid] += $val;
            $day_total += $val;
        }
        $row['total'] = $day_total;
        $grand_total += $day_total;
        $report_data[] = $row;
    }
}

// Highest Income Source Logic
$max_val = 0; $max_name = 'N/A';
if(!$is_month_view && !$is_custom_range) {
    foreach($report_data as $r) { if($r['total'] > $max_val) { $max_val = $r['total']; $max_name = $r['name']; } }
} else {
    // Logic for Monthly AND Custom Range is same (totals_line holds source totals)
    foreach($totals_line as $sid => $v) { if($v > $max_val) { $max_val = $v; foreach($sources as $s) if($s['source_id'] == $sid) $max_name = $s['source_name']; } }
}

// --- DATA PREPARATION FOR GRAPH ---
$chart_labels = [];
$chart_values = [];
if ($is_custom_range) {
    foreach($report_data as $rd) {
        $chart_labels[] = $rd['day'];
        $chart_values[] = (float)$rd['total'];
    }
} elseif(!$is_month_view) {
    $chart_labels = $short_months;
    for($m=1; $m<=12; $m++) $chart_values[] = (float)($totals_line[$m] ?? 0);
} else {
    for($d=1; $d<=$days_in_month; $d++) $chart_labels[] = "Day $d";
    foreach($report_data as $rd) $chart_values[] = (float)$rd['total'];
}
$chart_json = json_encode(['labels' => $chart_labels, 'data' => $chart_values]);

include('../includes/header.php');

// AJAX partial response (Reuse structure)
if(isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    ob_end_clean();
    ?>
    <textarea id="chart-data-provider" class="hidden"><?= $chart_json ?></textarea>
    <style>
        .glass-card table thead tr th { background-color: #134e4a !important; color: white !important; }
        .glass-card table thead tr th:last-child { background-color: #020617 !important; }
    </style>
    <div class="mb-8 header-controls no-print">
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
            <div class="space-y-2">
                <nav class="flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-slate-400">
                    <a href="javascript:void(0)" onclick="loadReport('/pos/accounting/income-monthwise/<?= $year ?>')" class="hover:text-teal-600 transition-colors">Income</a>
                    <?php if($is_month_view): ?>
                        <i class="fas fa-chevron-right text-[8px]"></i>
                        <span class="text-slate-800"><?= $month_names[$month-1] ?></span>
                    <?php endif; ?>
                </nav>
                <h1 class="text-4xl font-black text-slate-900 tracking-tight">
                    <?= $is_month_view ? $month_names[$month-1] . ', ' . $year : 'Yearly Income Report ' . $year ?>
                </h1>
                <p class="text-slate-500 font-medium max-w-xl">
                    <?= $is_month_view ? "Daily breakdown of all income sources for this month." : "Comprehensive overview of all income sources across 12 months." ?>
                </p>
            </div>
            
            <div class="controls-wrapper relative z-50">
                <div class="flex items-center gap-4 bg-white/50 backdrop-blur-md p-2 rounded-2xl border border-white/50 shadow-sm">
                    <div class="flex flex-nowrap items-center bg-slate-100 rounded-xl p-1 gap-2">
                        <button onclick="navigateReport('prev')" class="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-lg hover:bg-white hover:text-teal-600 transition-all text-slate-500"><i class="fas fa-chevron-left"></i></button>
                        
                        <!-- Custom Store Selector Standalone -->
                        <div class="relative group w-[280px] flex-shrink-0 bg-white border-2 border-slate-100 focus-within:border-teal-500 rounded-2xl shadow-sm transition-all" id="store_selector_container">
                            <div class="flex items-center px-4 py-2 text-sm font-bold text-slate-800 outline-none">
                                <div class="pr-3 text-slate-400"><i class="fas fa-store"></i></div>
                                <input type="text" id="store_search_input" 
                                       class="w-full bg-transparent border-none outline-none font-bold text-slate-700 placeholder-slate-400 text-sm"
                                       placeholder="Search Store..."
                                       value="<?= $store_id ? htmlspecialchars($all_stores[array_search($store_id, array_column($all_stores, 'id'))]['store_name']) : 'All Stores' ?>"
                                       autocomplete="off">
                                <div class="pl-2 text-slate-300"><i class="fas fa-chevron-down text-[10px]"></i></div>
                            </div>
                            <input type="hidden" id="store-select" value="<?= $store_id ?>">
                            
                            <div id="store_dropdown" class="absolute left-0 top-full mt-2 w-full bg-white border-2 border-teal-500 rounded-2xl hidden shadow-2xl z-[50] overflow-hidden custom-scroll">
                                <div id="store_results_container" class="p-1">
                                    <?php if($isAdmin || count($all_stores) > 1): ?>
                                    <div class="store-option px-4 py-3 hover:bg-teal-50 cursor-pointer transition-colors border-b border-slate-50 flex items-center gap-3 rounded-xl" data-id="" data-name="All Stores">
                                        <div class="w-8 h-8 rounded-lg bg-teal-50 flex items-center justify-center text-teal-600 font-bold text-xs"><i class="fas fa-layer-group"></i></div>
                                        <div class="font-bold text-slate-700 text-sm">All Stores</div>
                                    </div>
                                    <?php endif; ?>
                                    <?php foreach($all_stores as $s): ?>
                                        <div class="store-option px-4 py-3 hover:bg-teal-50 cursor-pointer transition-colors border-b border-slate-50 flex items-center gap-3 rounded-xl" 
                                             data-id="<?= $s['id'] ?>" data-name="<?= htmlspecialchars($s['store_name']) ?>">
                                            <div class="w-8 h-8 rounded-lg bg-teal-50 flex items-center justify-center text-teal-600 font-black text-xs"><?= strtoupper(substr($s['store_name'], 0, 1)) ?></div>
                                            <div class="font-bold text-slate-700 text-sm"><?= htmlspecialchars($s['store_name']) ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="selects-container flex-shrink-0 flex flex-nowrap items-center bg-white rounded-lg shadow-sm border border-slate-200 px-2 divide-x divide-slate-100 h-[46px]" id="default-selects">
                            <select id="month-select" class="bg-transparent border-0 px-3 py-2 text-sm font-bold outline-none text-slate-800 h-full">
                                <option value="" <?= !$is_month_view ? 'selected' : '' ?>>Yearly Overview</option>
                                <?php foreach($month_names as $m_idx => $m_name): ?>
                                    <option value="<?= str_pad($m_idx+1, 2, '0', STR_PAD_LEFT) ?>" <?= ($is_month_view && (int)$month == ($m_idx+1)) ? 'selected' : '' ?>><?= $m_name ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select id="year-select" class="bg-transparent border-0 px-4 py-2 text-sm font-bold outline-none text-slate-800 h-full">
                                <?php for($i = date('Y'); $i >= 2020; $i--): ?>
                                    <option value="<?= $i ?>" <?= $i == $year ? 'selected' : '' ?>><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="relative">
                            <button onclick="toggleCustomCalendar()" class="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-lg hover:bg-white hover:text-teal-600 transition-all text-slate-500" title="Custom Date Range">
                                <i class="far fa-calendar-alt"></i>
                            </button>

                            <!-- Custom Calendar Picker Modal -->
                            <div id="custom-date-picker-modal" class="hidden absolute top-full left-0 mt-2 w-80 bg-white rounded-2xl shadow-2xl border border-slate-200 z-[60] p-4 overflow-hidden">
                                <!-- Selected Range Display -->
                                <div class="mb-4 p-3 bg-teal-50 rounded-xl text-center border border-teal-100">
                                    <div class="text-sm font-bold text-teal-800" id="cal-selected-range">
                                        Select date range
                                    </div>
                                </div>
                                
                                <!-- Calendar Header -->
                                <div class="flex items-center justify-between mb-4">
                                    <button type="button" onclick="changeCalMonth(-1)" class="w-8 h-8 flex items-center justify-center hover:bg-slate-100 rounded-full transition text-slate-500">
                                        <i class="fas fa-chevron-left text-xs"></i>
                                    </button>
                                    <div class="relative">
                                        <button type="button" onclick="toggleCalYearMonth()" class="font-bold text-slate-800 uppercase text-sm px-3 py-1 hover:bg-slate-100 rounded-lg transition flex items-center gap-2" id="cal-month-display">
                                            MONTH YEAR
                                        </button>
                                        
                                        <!-- Year/Month Selector -->
                                        <div id="cal-year-month-selector" class="hidden absolute top-full left-1/2 transform -translate-x-1/2 mt-2 bg-white border border-slate-200 rounded-xl shadow-xl z-50 p-3 w-64">
                                            <div class="mb-3">
                                                <label class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1 block">Year</label>
                                                <select id="cal-year-select" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm font-bold focus:outline-none focus:ring-2 focus:ring-teal-500"></select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1 block">Month</label>
                                                <select id="cal-month-select" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm font-bold focus:outline-none focus:ring-2 focus:ring-teal-500">
                                                    <option value="0">January</option>
                                                    <option value="1">February</option>
                                                    <option value="2">March</option>
                                                    <option value="3">April</option>
                                                    <option value="4">May</option>
                                                    <option value="5">June</option>
                                                    <option value="6">July</option>
                                                    <option value="7">August</option>
                                                    <option value="8">September</option>
                                                    <option value="9">October</option>
                                                    <option value="10">November</option>
                                                    <option value="11">December</option>
                                                </select>
                                            </div>
                                            <button type="button" onclick="applyCalYearMonth()" class="w-full px-4 py-2 bg-teal-600 text-white rounded-lg text-sm font-bold hover:bg-teal-700 transition-all shadow-lg shadow-teal-200">
                                                Apply
                                            </button>
                                        </div>
                                    </div>
                                    <button type="button" onclick="changeCalMonth(1)" class="w-8 h-8 flex items-center justify-center hover:bg-slate-100 rounded-full transition text-slate-500">
                                        <i class="fas fa-chevron-right text-xs"></i>
                                    </button>
                                </div>
                                
                                <!-- Weekday Headers -->
                                <div class="grid grid-cols-7 mb-2 text-center">
                                    <div class="text-xs font-bold text-slate-400">S</div>
                                    <div class="text-xs font-bold text-slate-400">M</div>
                                    <div class="text-xs font-bold text-slate-400">T</div>
                                    <div class="text-xs font-bold text-slate-400">W</div>
                                    <div class="text-xs font-bold text-slate-400">T</div>
                                    <div class="text-xs font-bold text-slate-400">F</div>
                                    <div class="text-xs font-bold text-slate-400">S</div>
                                </div>

                                <!-- Calendar Grid -->
                                <div id="cal-grid" class="grid grid-cols-7 gap-y-1 place-items-center"></div>
                            </div>
                        </div>

                        <?php if($month || $start_date || $end_date): ?>
                        <button onclick="resetFilters()" class="flex-shrink-0 flex items-center gap-2 px-3 py-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition-colors font-bold text-sm">
                            <i class="fas fa-undo"></i> Reset
                        </button>
                        <?php endif; ?>

                        <button onclick="navigateReport('next')" class="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-lg hover:bg-white hover:text-teal-600 transition-all text-slate-500"><i class="fas fa-chevron-right"></i></button>
                    </div>
                    <?php if($is_month_view): ?>
                        <a href="javascript:void(0)" onclick="loadReport('/pos/accounting/income-monthwise/<?= $year ?>')" class="bg-teal-600 hover:bg-teal-700 text-white px-5 py-3 rounded-xl text-sm font-bold transition-all shadow-lg shadow-teal-200 flex items-center gap-2 group">
                            <i class="fas fa-th group-hover:scale-110 transition-transform"></i> Yearly
                        </a>
                    <?php endif; ?>
                    <button onclick="resetReport()" class="bg-rose-50 hover:bg-rose-100 text-rose-600 px-4 py-3 rounded-xl text-sm font-bold border border-rose-100 transition-all flex items-center gap-2"><i class="fas fa-undo"></i> Reset</button>
                    <button onclick="openPrintTab()" class="bg-teal-600 hover:bg-teal-700 text-white px-6 py-3 rounded-xl text-sm font-bold shadow-lg shadow-teal-100 transition-all flex items-center gap-2"><i class="fas fa-print"></i> Export</button>
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
                        <th class="p-6 text-xs font-black uppercase tracking-widest sticky left-0 bg-teal-900 z-20 border-r border-teal-800 min-w-[200px]"><?= $is_month_view ? 'Days \ Sources' : 'Sources \ Months' ?></th>
                        <?php if(!$is_month_view): ?>
                            <?php foreach($short_months as $index => $m): ?>
                                <th class="p-4 text-xs font-black uppercase tracking-widest text-center border-r border-slate-800/50 cursor-pointer hover:bg-teal-600 transition-colors" onclick="loadReport('/pos/accounting/income-monthwise/<?= $year ?>/<?= str_pad($index+1, 2, '0', STR_PAD_LEFT) ?>')"><?= $m ?></th>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <?php foreach($sources as $cat): ?>
                                <th class="p-4 text-xs font-black uppercase tracking-widest text-center border-r border-slate-800/50"><?= htmlspecialchars($cat['source_name']) ?></th>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <th class="p-6 text-xs font-black uppercase tracking-widest text-right bg-slate-950">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                    <?php if(!$is_month_view): ?>
                        <?php foreach($report_data as $row): ?>
                            <tr class="hover:bg-teal-50/30 transition-colors group">
                                <td class="p-5 text-sm font-bold text-slate-800 sticky left-0 bg-white group-hover:bg-slate-50/50 border-r border-slate-100 z-10"><?= htmlspecialchars($row['name']) ?></td>
                                <?php for($m=1; $m<=12; $m++): $val = $row['cells'][$m]; $cellClass=$val>0?'cell-active font-black':'text-slate-200/50'; ?>
                                    <td class="p-5 text-sm text-center border-r border-slate-50 <?= $cellClass ?> cursor-pointer hover:bg-white transition-all shadow-inner" onclick="loadReport('/pos/accounting/income-monthwise/<?= $year ?>/<?= str_pad($m, 2, '0', STR_PAD_LEFT) ?>')"><?= $val > 0 ? number_format($val, 0) : '-' ?></td>
                                <?php endfor; ?>
                                <td class="p-5 text-sm font-black text-right text-slate-900 bg-slate-50/50"><?= number_format($row['total'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php foreach($report_data as $row): ?>
                            <tr class="hover:bg-emerald-50/30 transition-colors group">
                                <td class="p-4 text-sm font-black text-slate-500 text-center sticky left-0 bg-slate-50 group-hover:bg-slate-100 border-r border-slate-200 z-10 w-16"><?= $row['day'] ?></td>
                                <?php foreach($sources as $cat): $val = $row['cells'][$cat['source_id']]; $cellClass=$val>0?'bg-emerald-50 text-emerald-700 font-black':'text-slate-200/30'; ?>
                                    <td class="p-4 text-sm text-center border-r border-slate-50 <?= $cellClass ?>"><?= $val > 0 ? number_format($val, 0) : '-' ?></td>
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
                                <td class="p-6 text-sm font-black text-center border-r border-white italic text-teal-700"><?= $totals_line[$m] > 0 ? number_format($totals_line[$m], 0) : '-' ?></td>
                            <?php endfor; ?>
                        <?php else: ?>
                            <?php foreach($sources as $cat): ?>
                                <td class="p-6 text-sm font-black text-center border-r border-white italic text-teal-700"><?= $totals_line[$cat['source_id']] > 0 ? number_format($totals_line[$cat['source_id']], 0) : '-' ?></td>
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
        <div class="flex-1 bg-white p-8 rounded-[2.5rem] shadow-xl border border-slate-100 relative overflow-hidden">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h3 class="text-xl font-black text-slate-900">Income Analytics</h3>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Visual Trends Overview</p>
                </div>
                <div class="flex items-center gap-2 bg-teal-50 px-4 py-2 rounded-xl border border-teal-100">
                    <span class="w-2 h-2 rounded-full bg-teal-500 animate-pulse"></span>
                    <span class="text-[10px] font-black text-teal-700 uppercase">Live Data</span>
                </div>
            </div>
            <div class="h-64 relative"><canvas id="expenditureChart"></canvas></div>
        </div>

        <div class="lg:w-96 space-y-6">
            <div class="bg-teal-900 p-8 rounded-[2.5rem] shadow-2xl shadow-teal-100 text-white border border-teal-800 relative overflow-hidden group">
                <div class="absolute -right-4 -bottom-4 opacity-10 group-hover:scale-110 transition-transform duration-500 text-9xl"><i class="fas fa-wallet"></i></div>
                <p class="text-xs font-bold uppercase tracking-widest opacity-60 mb-2">Total Overview</p>
                <h3 class="text-3xl font-black tracking-tighter"><?= $currency_symbol ?> <?= number_format($grand_total, 2) ?></h3>
                <p class="text-[10px] font-bold mt-4 px-3 py-1 bg-teal-500/20 text-teal-400 rounded-full inline-block uppercase tracking-widest">Income Confirmed</p>
            </div>

            <div class="bg-white p-6 rounded-[2.5rem] shadow-xl border border-slate-100 flex flex-col">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h4 class="text-lg font-black text-slate-900 tracking-tight">Recent Incomes</h4>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Last 5 Deposits</p>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center text-lg"><i class="fas fa-history"></i></div>
                </div>

                <div class="space-y-4 flex-1">
                    <?php if(empty($recent_incomes)): ?>
                        <div class="flex flex-col items-center justify-center h-full text-center py-10">
                            <i class="fas fa-ghost text-slate-200 text-4xl mb-3"></i>
                            <p class="text-sm font-bold text-slate-400">No recent incomes</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($recent_incomes as $re): ?>
                            <div class="group flex items-center gap-4 p-3 rounded-2xl hover:bg-teal-50/50 border border-transparent hover:border-teal-100 transition-all cursor-pointer">
                                <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-500 group-hover:bg-teal-600 group-hover:text-white flex items-center justify-center transition-all">
                                    <i class="fas fa-receipt text-xs"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h5 class="text-sm font-black text-slate-800 truncate leading-tight"><?= htmlspecialchars($re['title']) ?></h5>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span class="text-[9px] font-bold text-teal-600 uppercase bg-teal-50 px-1.5 py-0.5 rounded"><?= htmlspecialchars($re['source_name'] ?? 'N/A') ?></span>
                                        <span class="text-[9px] font-bold text-slate-400 flex items-center gap-1"><i class="far fa-clock"></i> <?= date('h:i A', strtotime($re['created_at'])) ?></span>
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

                <div class="mt-6 pt-4 border-t border-slate-50 text-center">
                    <a href="/pos/accounting/bank/transaction-list" class="inline-flex items-center justify-center gap-2 w-full py-3 bg-slate-50 hover:bg-teal-50 text-slate-600 hover:text-teal-700 font-bold rounded-xl transition-all group">
                        View All Incomes <i class="fas fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                    </a>
                </div>
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
                <!-- Content Loaded via AJAX (initial load uses same structure but I will include the block above again or simple refactor? 
                     Actually simpler to just set defaults and include same block or just copy paste for now to ensure consistency)
                -->
                <!-- Initial Load Content (Duplicate of AJAX block for simplicity in this context) -->
                 <textarea id="chart-data-provider" class="hidden"><?= $chart_json ?></textarea>
                 <!-- Print Header -->
                <div class="print-only-header hidden">
                    <div class="text-center mb-8 border-b-2 border-slate-900 pb-6">
                        <h1 class="text-4xl font-black text-slate-900 mb-2">Velocity POS - Income Report</h1>
                        <div class="flex items-center justify-center gap-6 text-sm font-bold text-slate-600 uppercase tracking-widest">
                            <span>Type: <?= $is_month_view ? 'Monthly Breakdown' : 'Yearly Overview' ?></span>
                            <span>Period: <?= $is_month_view ? $month_names[$month-1] . ', ' . $year : 'Year ' . $year ?></span>
                        </div>
                    </div>
                </div>

                <div class="mb-8 header-controls no-print">
                    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
                        <div class="space-y-2">
                            <nav class="flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-slate-400">
                                <a href="javascript:void(0)" onclick="loadReport('/pos/accounting/income-monthwise/<?= $year ?>')" class="hover:text-teal-600 transition-colors">Income</a>
                                <?php if($is_month_view): ?>
                                    <i class="fas fa-chevron-right text-[8px]"></i>
                                    <span class="text-slate-800"><?= $month_names[$month-1] ?></span>
                                <?php endif; ?>
                            </nav>
                            <h1 class="text-4xl font-black text-slate-900 tracking-tight">
                                <?= $is_month_view ? $month_names[$month-1] . ', ' . $year : 'Yearly Income Report ' . $year ?>
                            </h1>
                            <p class="text-slate-500 font-medium max-w-xl">
                                <?= $is_month_view ? "Daily breakdown of all income sources for this month." : "Comprehensive overview of all income sources across 12 months." ?>
                            </p>
                        </div>
                        
                        <div class="controls-wrapper relative z-50">
                            <div class="flex items-center gap-4 bg-white/50 backdrop-blur-md p-2 rounded-2xl border border-white/50 shadow-sm">
                                <!-- Main Report Navigation Controls -->
                                <div class="flex flex-nowrap items-center bg-slate-100 rounded-xl p-1 gap-2">
                                    <button onclick="navigateReport('prev')" class="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-lg hover:bg-white hover:text-teal-600 transition-all text-slate-500"><i class="fas fa-chevron-left"></i></button>
                                    
                                    <!-- Custom Store Selector Standalone -->
                                    <div class="relative group w-[280px] flex-shrink-0 bg-white border-2 border-slate-100 focus-within:border-teal-500 rounded-2xl shadow-sm transition-all" id="store_selector_container">
                                        <div class="flex items-center px-4 py-2 text-sm font-bold text-slate-800 outline-none">
                                            <div class="pr-3 text-slate-400"><i class="fas fa-store"></i></div>
                                            <input type="text" id="store_search_input" 
                                                   class="w-full bg-transparent border-none outline-none font-bold text-slate-700 placeholder-slate-400 text-sm"
                                                   placeholder="Search Store..."
                                                   value="<?= $store_id ? htmlspecialchars($all_stores[array_search($store_id, array_column($all_stores, 'id'))]['store_name']) : 'All Stores' ?>"
                                                   autocomplete="off"
                                                   onclick="this.select()">
                                            <div class="pl-2 text-slate-300"><i class="fas fa-chevron-down text-[10px]"></i></div>
                                        </div>
                                        <input type="hidden" id="store-select" value="<?= $store_id ?>">
                                        
                                        <div id="store_dropdown" class="absolute left-0 top-full mt-2 w-full bg-white border-2 border-teal-500 rounded-2xl hidden shadow-2xl z-[50] overflow-hidden custom-scroll">
                                            <div id="store_results_container" class="p-1">
                                                <?php if($isAdmin || count($all_stores) > 1): ?>
                                                <div class="store-option px-4 py-3 hover:bg-teal-50 cursor-pointer transition-colors border-b border-slate-50 flex items-center gap-3 rounded-xl" data-id="" data-name="All Stores">
                                                    <div class="w-8 h-8 rounded-lg bg-teal-50 flex items-center justify-center text-teal-600 font-bold text-xs"><i class="fas fa-layer-group"></i></div>
                                                    <div class="font-bold text-slate-700 text-sm">All Stores</div>
                                                </div>
                                                <?php endif; ?>
                                                <?php foreach($all_stores as $s): ?>
                                                    <div class="store-option px-4 py-3 hover:bg-teal-50 cursor-pointer transition-colors border-b border-slate-50 flex items-center gap-3 rounded-xl" 
                                                         data-id="<?= $s['id'] ?>" data-name="<?= htmlspecialchars($s['store_name']) ?>">
                                                        <div class="w-8 h-8 rounded-lg bg-teal-50 flex items-center justify-center text-teal-600 font-black text-xs"><?= strtoupper(substr($s['store_name'], 0, 1)) ?></div>
                                                        <div class="font-bold text-slate-700 text-sm"><?= htmlspecialchars($s['store_name']) ?></div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="selects-container flex-shrink-0 flex flex-nowrap items-center bg-white rounded-lg shadow-sm border border-slate-200 px-2 divide-x divide-slate-100 h-[46px]" id="default-selects">
                                        <select id="month-select" class="bg-transparent border-0 px-3 py-2 text-sm font-bold outline-none text-slate-800 h-full">
                                            <option value="" <?= !$is_month_view ? 'selected' : '' ?>>Yearly Overview</option>
                                            <?php foreach($month_names as $m_idx => $m_name): ?>
                                                <option value="<?= str_pad($m_idx+1, 2, '0', STR_PAD_LEFT) ?>" <?= ($is_month_view && (int)$month == ($m_idx+1)) ? 'selected' : '' ?>><?= $m_name ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <select id="year-select" class="bg-transparent border-0 px-4 py-2 text-sm font-bold outline-none text-slate-800 h-full">
                                            <?php for($i = date('Y'); $i >= 2020; $i--): ?>
                                                <option value="<?= $i ?>" <?= $i == $year ? 'selected' : '' ?>><?= $i ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>

                                    <div class="relative">
                                        <button onclick="toggleCustomCalendar()" class="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-lg hover:bg-white hover:text-teal-600 transition-all text-slate-500" title="Custom Date Range">
                                            <i class="far fa-calendar-alt"></i>
                                        </button>

                                        <!-- Custom Calendar Picker Modal -->
                                        <div id="custom-date-picker-modal" class="hidden absolute top-full right-0 mt-2 w-80 bg-white rounded-2xl shadow-2xl border border-slate-200 z-[60] p-4 overflow-hidden">
                                            <!-- Selected Range Display -->
                                            <div class="mb-4 p-3 bg-teal-50 rounded-xl text-center border border-teal-100">
                                                <div class="text-sm font-bold text-teal-800" id="cal-selected-range">
                                                    Select date range
                                                </div>
                                            </div>
                                            
                                            <!-- Calendar Header -->
                                            <div class="flex items-center justify-between mb-4">
                                                <button type="button" onclick="changeCalMonth(-1)" class="w-8 h-8 flex items-center justify-center hover:bg-slate-100 rounded-full transition text-slate-500">
                                                    <i class="fas fa-chevron-left text-xs"></i>
                                                </button>
                                                <div class="relative">
                                                    <button type="button" onclick="toggleCalYearMonth()" class="font-bold text-slate-800 uppercase text-sm px-3 py-1 hover:bg-slate-100 rounded-lg transition flex items-center gap-2" id="cal-month-display">
                                                        MONTH YEAR
                                                    </button>
                                                    
                                                    <!-- Year/Month Selector -->
                                                    <div id="cal-year-month-selector" class="hidden absolute top-full left-1/2 transform -translate-x-1/2 mt-2 bg-white border border-slate-200 rounded-xl shadow-xl z-50 p-3 w-64">
                                                        <div class="mb-3">
                                                            <label class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1 block">Year</label>
                                                            <select id="cal-year-select" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm font-bold focus:outline-none focus:ring-2 focus:ring-teal-500"></select>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1 block">Month</label>
                                                            <select id="cal-month-select" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm font-bold focus:outline-none focus:ring-2 focus:ring-teal-500">
                                                                <option value="0">January</option>
                                                                <option value="1">February</option>
                                                                <option value="2">March</option>
                                                                <option value="3">April</option>
                                                                <option value="4">May</option>
                                                                <option value="5">June</option>
                                                                <option value="6">July</option>
                                                                <option value="7">August</option>
                                                                <option value="8">September</option>
                                                                <option value="9">October</option>
                                                                <option value="10">November</option>
                                                                <option value="11">December</option>
                                                            </select>
                                                        </div>
                                                        <button type="button" onclick="applyCalYearMonth()" class="w-full px-4 py-2 bg-teal-600 text-white rounded-lg text-sm font-bold hover:bg-teal-700 transition-all shadow-lg shadow-teal-200">
                                                            Apply
                                                        </button>
                                                    </div>
                                                </div>
                                                <button type="button" onclick="changeCalMonth(1)" class="w-8 h-8 flex items-center justify-center hover:bg-slate-100 rounded-full transition text-slate-500">
                                                    <i class="fas fa-chevron-right text-xs"></i>
                                                </button>
                                            </div>
                                            
                                            <!-- Weekday Headers -->
                                            <div class="grid grid-cols-7 mb-2 text-center">
                                                <div class="text-xs font-bold text-slate-400">S</div>
                                                <div class="text-xs font-bold text-slate-400">M</div>
                                                <div class="text-xs font-bold text-slate-400">T</div>
                                                <div class="text-xs font-bold text-slate-400">W</div>
                                                <div class="text-xs font-bold text-slate-400">T</div>
                                                <div class="text-xs font-bold text-slate-400">F</div>
                                                <div class="text-xs font-bold text-slate-400">S</div>
                                            </div>

                                            <!-- Calendar Grid -->
                                            <div id="cal-grid" class="grid grid-cols-7 gap-y-1 place-items-center"></div>
                                        </div>
                                    </div>

                                    <?php if($month || $start_date || $end_date): ?>
                                    <button onclick="resetFilters()" class="flex-shrink-0 flex items-center gap-2 px-3 py-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition-colors font-bold text-sm">
                                        <i class="fas fa-undo"></i> Reset
                                    </button>
                                    <?php endif; ?>

                                    <button onclick="navigateReport('next')" class="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-lg hover:bg-white hover:text-teal-600 transition-all text-slate-500"><i class="fas fa-chevron-right"></i></button>
                                </div>
                                <?php if($is_month_view): ?>
                                    <a href="javascript:void(0)" onclick="loadReport('/pos/accounting/income-monthwise/<?= $year ?>')" class="bg-teal-600 hover:bg-teal-700 text-white px-5 py-3 rounded-xl text-sm font-bold transition-all shadow-lg shadow-teal-200 flex items-center gap-2 group">
                                        <i class="fas fa-th group-hover:scale-110 transition-transform"></i> Yearly
                                    </a>
                                <?php endif; ?>
                                <button onclick="openPrintTab()" class="bg-teal-600 hover:bg-teal-700 text-white px-6 py-3 rounded-xl text-sm font-bold shadow-lg shadow-teal-100 transition-all flex items-center gap-2"><i class="fas fa-print"></i> Export</button>
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
                                    <th class="p-6 text-xs font-black uppercase tracking-widest sticky left-0 bg-teal-900 z-20 border-r border-teal-800 min-w-[200px]"><?= ($is_month_view || $is_custom_range) ? 'Date \ Sources' : 'Sources \ Months' ?></th>
                                    <?php if(!$is_month_view && !$is_custom_range): ?>
                                        <?php foreach($short_months as $index => $m): ?>
                                            <th class="p-4 text-xs font-black uppercase tracking-widest text-center border-r border-slate-800/50 cursor-pointer hover:bg-teal-600 transition-colors" onclick="loadReport('/pos/accounting/income-monthwise/<?= $year ?>/<?= str_pad($index+1, 2, '0', STR_PAD_LEFT) ?>')"><?= $m ?></th>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <?php foreach($sources as $cat): ?>
                                            <th class="p-4 text-xs font-black uppercase tracking-widest text-center border-r border-slate-800/50"><?= htmlspecialchars($cat['source_name']) ?></th>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    <th class="p-6 text-xs font-black uppercase tracking-widest text-right bg-slate-950">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                                <?php if(!$is_month_view && !$is_custom_range): ?>
                                    <?php foreach($report_data as $row): ?>
                                        <tr class="hover:bg-teal-50/30 transition-colors group">
                                            <td class="p-5 text-sm font-bold text-slate-800 sticky left-0 bg-white group-hover:bg-slate-50/50 border-r border-slate-100 z-10"><?= htmlspecialchars($row['name']) ?></td>
                                            <?php for($m=1; $m<=12; $m++): $val = $row['cells'][$m]; $cellClass=$val>0?'cell-active font-black':'text-slate-200/50'; ?>
                                                <td class="p-5 text-sm text-center border-r border-slate-50 <?= $cellClass ?> cursor-pointer hover:bg-white transition-all shadow-inner" onclick="loadReport('/pos/accounting/income-monthwise/<?= $year ?>/<?= str_pad($m, 2, '0', STR_PAD_LEFT) ?>')"><?= $val > 0 ? number_format($val, 0) : '-' ?></td>
                                            <?php endfor; ?>
                                            <td class="p-5 text-sm font-black text-right text-slate-900 bg-slate-50/50"><?= number_format($row['total'], 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <?php foreach($report_data as $row): ?>
                                        <tr class="hover:bg-emerald-50/30 transition-colors group">
                                            <td class="p-4 text-sm font-black text-slate-500 text-center sticky left-0 bg-slate-50 group-hover:bg-slate-100 border-r border-slate-200 z-10 w-16"><?= $row['day'] ?></td>
                                            <?php foreach($sources as $cat): $val = $row['cells'][$cat['source_id']]; $cellClass=$val>0?'bg-emerald-50 text-emerald-700 font-black':'text-slate-200/30'; ?>
                                                <td class="p-4 text-sm text-center border-r border-slate-50 <?= $cellClass ?>"><?= $val > 0 ? number_format($val, 0) : '-' ?></td>
                                            <?php endforeach; ?>
                                            <td class="p-4 text-sm font-black text-right text-slate-900 bg-slate-50/50 border-l border-emerald-100"><?= number_format($row['total'], 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                            <tfoot>
                                <tr class="bg-teal-50 backdrop-blur-md">
                                    <td class="p-6 text-sm font-black uppercase tracking-widest text-teal-900 sticky left-0 bg-teal-100/50 border-r border-teal-200 z-10">Grand Total</td>
                                    <?php if(!$is_month_view && !$is_custom_range): ?>
                                        <?php for($m=1; $m<=12; $m++): ?>
                                            <td class="p-6 text-sm font-black text-center border-r border-white italic text-teal-700"><?= $totals_line[$m] > 0 ? number_format($totals_line[$m], 0) : '-' ?></td>
                                        <?php endfor; ?>
                                    <?php else: ?>
                                        <?php foreach($sources as $cat): ?>
                                            <td class="p-6 text-sm font-black text-center border-r border-white italic text-teal-700"><?= $totals_line[$cat['source_id']] > 0 ? number_format($totals_line[$cat['source_id']], 0) : '-' ?></td>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    <td class="p-6 text-lg font-black text-right text-teal-900 bg-teal-200/30 border-l-2 border-teal-200"><?= $currency_symbol ?> <?= number_format($grand_total, 2) ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Footer Summary (Duplicate key parts) -->
                 <div class="flex flex-col lg:flex-row gap-8 mt-10 no-print">
                    <div class="flex-1 bg-white p-8 rounded-[2.5rem] shadow-xl border border-slate-100 relative overflow-hidden">
                        <div class="flex items-center justify-between mb-8">
                            <div>
                                <h3 class="text-xl font-black text-slate-900">Income Analytics</h3>
                            </div>
                        </div>
                         <div class="h-64 relative"><canvas id="expenditureChartInitial"></canvas></div>
                    </div>

                    <div class="lg:w-96 space-y-6">
                        <div class="bg-teal-900 p-8 rounded-[2.5rem] shadow-2xl shadow-teal-100 text-white border border-teal-800 relative overflow-hidden group">
                            <div class="absolute -right-4 -bottom-4 opacity-10 group-hover:scale-110 transition-transform duration-500 text-9xl"><i class="fas fa-wallet"></i></div>
                            <p class="text-xs font-bold uppercase tracking-widest opacity-60 mb-2">Total Overview</p>
                            <h3 class="text-3xl font-black tracking-tighter"><?= $currency_symbol ?> <?= number_format($grand_total, 2) ?></h3>
                            <p class="text-[10px] font-bold mt-4 px-3 py-1 bg-teal-500/20 text-teal-400 rounded-full inline-block uppercase tracking-widest">Income Confirmed</p>
                        </div>

                        <div class="bg-white p-6 rounded-[2.5rem] shadow-xl border border-slate-100 flex flex-col">
                            <div class="flex items-center justify-between mb-6">
                                <div>
                                    <h4 class="text-lg font-black text-slate-900 tracking-tight">Recent Incomes</h4>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Last 5 Deposits</p>
                                </div>
                                <div class="w-10 h-10 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center text-lg"><i class="fas fa-history"></i></div>
                            </div>

                            <div class="space-y-4 flex-1">
                                <?php if(empty($recent_incomes)): ?>
                                    <div class="flex flex-col items-center justify-center h-full text-center py-10">
                                        <i class="fas fa-ghost text-slate-200 text-4xl mb-3"></i>
                                        <p class="text-sm font-bold text-slate-400">No recent incomes</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach($recent_incomes as $re): ?>
                                        <div class="group flex items-center gap-4 p-3 rounded-2xl hover:bg-teal-50/50 border border-transparent hover:border-teal-100 transition-all cursor-pointer">
                                            <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-500 group-hover:bg-teal-600 group-hover:text-white flex items-center justify-center transition-all">
                                                <i class="fas fa-receipt text-xs"></i>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <h5 class="text-sm font-black text-slate-800 truncate leading-tight"><?= htmlspecialchars($re['title']) ?></h5>
                                                <div class="flex items-center gap-2 mt-0.5">
                                                    <span class="text-[9px] font-bold text-teal-600 uppercase bg-teal-50 px-1.5 py-0.5 rounded"><?= htmlspecialchars($re['source_name'] ?? 'N/A') ?></span>
                                                    <span class="text-[9px] font-bold text-slate-400 flex items-center gap-1"><i class="far fa-clock"></i> <?= date('h:i A', strtotime($re['created_at'])) ?></span>
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

                            <div class="mt-6 pt-4 border-t border-slate-50 text-center">
                                <a href="/pos/accounting/bank/transaction-list" class="inline-flex items-center justify-center gap-2 w-full py-3 bg-slate-50 hover:bg-teal-50 text-slate-600 hover:text-teal-700 font-bold rounded-xl transition-all group">
                                    View All Incomes <i class="fas fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php include('../includes/footer.php'); ?>
    </main>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="/pos/assets/css/expenditureCss/select2_custom.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<!-- Reuse existing JS or duplicate/adapt? Reusing might cause URL issues if JS hardcodes URL path. 
     I'll inspect JS in next step but for now using inline or simple adapter script might be better. 
     Actually, let's include the existing JS but I need to handle URL overriding.
     The existing JS `expense_monthwiseJs.js` likely has hardcoded path `/pos/expenditure/monthwise`.
     I should duplicate the JS and change the path to `/pos/accounting/income-monthwise`.
-->
<script src="/pos/assets/js/accounting/income_monthwise.js"></script>
<?php if($is_print_mode): ?>
<!-- Print Template similar to expense -->
<?php endif; ?>
