
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
    $stores_query = mysqli_query($conn, "SELECT id, store_name FROM stores WHERE status='1' ORDER BY store_name ASC");
    while($st = mysqli_fetch_assoc($stores_query)) $all_stores[] = $st;
} else {
    $stores_query = mysqli_query($conn, "SELECT s.id, s.store_name 
                                        FROM stores s 
                                        JOIN user_store_map usm ON s.id = usm.store_id 
                                        WHERE s.status='1' AND usm.user_id='$user_id' 
                                        ORDER BY s.store_name ASC");
    while($st = mysqli_fetch_assoc($stores_query)) $all_stores[] = $st;
}

// Store Selection Logic
if (!$isAdmin && empty($store_id)) {
    if (!empty($all_stores) && count($all_stores) == 1) {
        $store_id = $all_stores[0]['id'];
    }
} elseif (!$isAdmin && $store_id) {
    $allowed = false;
    foreach($all_stores as $s) { if($s['id'] == $store_id) $allowed = true; }
    if(!$allowed && !empty($all_stores)) $store_id = $all_stores[0]['id'];
}

// Filter Logic Construction
$where_store = "";
if ($store_id) {
    $where_store = " AND info.store_id='$store_id'";
} else {
    if (!$isAdmin) {
        $assigned_ids = array_column($all_stores, 'id');
        if (!empty($assigned_ids)) {
            $ids_str = implode(',', $assigned_ids);
            $where_store = " AND info.store_id IN ($ids_str)";
        } else {
            $where_store = " AND 1=0"; 
        }
    }
}

// Resolve Dynamic Currency Symbol
$currency_symbol = '';
if($store_id) {
    $curr_q = mysqli_query($conn, "SELECT c.symbol_left, c.symbol_right FROM stores s JOIN currencies c ON s.currency_id = c.id WHERE s.id='$store_id'");
    if($curr_row = mysqli_fetch_assoc($curr_q)) { 
        $currency_symbol = $curr_row['symbol_left'] ?: $curr_row['symbol_right']; 
    }
}

// Fetch Expense Categories (Dynamic: Only used categories from BOTH tables)
$date_where = "";
$date_where_expenses = "";
if ($is_custom_range) {
    $date_where = "AND DATE(info.created_at) BETWEEN '$start_date' AND '$end_date'";
    $date_where_expenses = "AND DATE(e.created_at) BETWEEN '$start_date' AND '$end_date'";
} elseif ($is_month_view) {
    $date_where = "AND YEAR(info.created_at)='$year' AND MONTH(info.created_at)='$month'";
    $date_where_expenses = "AND YEAR(e.created_at)='$year' AND MONTH(e.created_at)='$month'";
} else {
    $date_where = "AND YEAR(info.created_at)='$year'";
    $date_where_expenses = "AND YEAR(e.created_at)='$year'";
}

// Build where clause for expenses table
$where_store_expenses = "";
if ($store_id) {
    $where_store_expenses = " AND e.store_id='$store_id'";
} else {
    if (!$isAdmin) {
        $assigned_ids = array_column($all_stores, 'id');
        if (!empty($assigned_ids)) {
            $ids_str = implode(',', $assigned_ids);
            $where_store_expenses = " AND e.store_id IN ($ids_str)";
        } else {
            $where_store_expenses = " AND 1=0"; 
        }
    }
}

// Fetch categories from BOTH tables
$cat_query = "SELECT DISTINCT c.category_id, c.category_name, c.sort_order 
              FROM expense_category c 
              WHERE c.status='1' 
                AND (
                    c.category_id IN (
                        SELECT DISTINCT info.exp_category_id 
                        FROM bank_transaction_info info 
                        WHERE info.transaction_type='withdraw' 
                          $date_where
                          $where_store
                    )
                    OR c.category_id IN (
                        SELECT DISTINCT e.category_id 
                        FROM expenses e 
                        WHERE 1=1 
                          $date_where_expenses
                          $where_store_expenses
                    )
                )
              ORDER BY c.sort_order ASC";
$cat_res = mysqli_query($conn, $cat_query);
$categories = [];
while($c = mysqli_fetch_assoc($cat_res)) $categories[] = $c;

$month_names = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
$short_months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

// Fetch Recent Expenses (Top 5) - Combined from BOTH tables
// UNION query to combine bank_transaction_info and expenses tables
$where_store_expenses = "";
if ($store_id) {
    $where_store_expenses = " AND e.store_id='$store_id'";
} else {
    if (!$isAdmin) {
        $assigned_ids = array_column($all_stores, 'id');
        if (!empty($assigned_ids)) {
            $ids_str = implode(',', $assigned_ids);
            $where_store_expenses = " AND e.store_id IN ($ids_str)";
        } else {
            $where_store_expenses = " AND 1=0"; 
        }
    }
}

$recent_query = "
    (SELECT 
        info.created_at,
        info.ref_no as reference_no,
        price.amount,
        cat.category_name,
        s.store_name,
        CONCAT('Bank Ref: ', info.ref_no) as title,
        'Bank Transaction' as source_type
     FROM bank_transaction_info info 
     JOIN bank_transaction_price price ON info.info_id = price.info_id
     LEFT JOIN expense_category cat ON info.exp_category_id = cat.category_id 
     LEFT JOIN stores s ON info.store_id = s.id
     WHERE info.transaction_type='withdraw' $where_store)
    
    UNION ALL
    
    (SELECT 
        e.created_at,
        e.reference_no,
        e.amount,
        c.category_name,
        s.store_name,
        e.title,
        'Expense' as source_type
     FROM expenses e
     JOIN expense_category c ON e.category_id = c.category_id
     JOIN stores s ON e.store_id = s.id
     WHERE 1=1 $where_store_expenses)
    
    ORDER BY created_at DESC 
    LIMIT 5
";

$recent_res = mysqli_query($conn, $recent_query);
$recent_expenses = [];
while($re = mysqli_fetch_assoc($recent_res)) $recent_expenses[] = $re;

$report_data = [];
$totals_line = [];
$grand_total = 0;

if ($is_custom_range) {
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $end->modify('+1 day');
    $period = new DatePeriod($start, new DateInterval('P1D'), $end);
    
    // Initialize Data Structure
    $data_map = []; // [date][category_id] => amount
    $totals_line = array_fill_keys(array_column($categories, 'category_id'), 0);

    // 1. Fetch Expenses (Optimized GROUP BY)
    $q_exp = "SELECT DATE(e.created_at) as log_date, e.category_id, SUM(e.amount) as total 
              FROM expenses e 
              WHERE e.created_at >= '$start_date 00:00:00' 
                AND e.created_at <= '$end_date 23:59:59' 
                $where_store_expenses 
              GROUP BY DATE(e.created_at), e.category_id";
    $r_exp = mysqli_query($conn, $q_exp);
    while($row = mysqli_fetch_assoc($r_exp)) {
        $data_map[$row['log_date']][$row['category_id']] = (float)$row['total'];
    }

    // 2. Fetch Bank Withdrawals (Optimized GROUP BY)
    $q_bank = "SELECT DATE(info.created_at) as log_date, info.exp_category_id, SUM(price.amount) as total 
               FROM bank_transaction_info info 
               JOIN bank_transaction_price price ON info.info_id = price.info_id
               WHERE info.transaction_type='withdraw' 
                 AND info.created_at >= '$start_date 00:00:00' 
                 AND info.created_at <= '$end_date 23:59:59' 
                 $where_store 
               GROUP BY DATE(info.created_at), info.exp_category_id";
    $r_bank = mysqli_query($conn, $q_bank);
    while($row = mysqli_fetch_assoc($r_bank)) {
        if(!isset($data_map[$row['log_date']][$row['exp_category_id']])) $data_map[$row['log_date']][$row['exp_category_id']] = 0;
        $data_map[$row['log_date']][$row['exp_category_id']] += (float)$row['total'];
    }

    // 3. Build Report Data
    foreach($period as $dt) {
        $curr_date = $dt->format('Y-m-d');
        $display_date = $dt->format('d M');
        
        $row = ['day' => $display_date, 'full_date' => $curr_date, 'cells' => []];
        $day_total = 0;
        
        foreach($categories as $cat) {
            $cid = $cat['category_id'];
            $val = $data_map[$curr_date][$cid] ?? 0;
            
            $row['cells'][$cid] = $val;
            $totals_line[$cid] += $val;
            $day_total += $val;
        }
        $row['total'] = $day_total;
        $grand_total += $day_total;
        $report_data[] = $row;
    }

} elseif (!$is_month_view) {
    // YEARLY VIEW OPTIMIZATION
    $totals_line = array_fill(1, 12, 0);
    $data_map = []; // [month][category_id] => total

    // 1. Fetch Expenses
    $q_exp = "SELECT MONTH(e.created_at) as m, e.category_id, SUM(e.amount) as total 
              FROM expenses e 
              WHERE YEAR(e.created_at)='$year' 
                $where_store_expenses 
              GROUP BY MONTH(e.created_at), e.category_id";
    $r_exp = mysqli_query($conn, $q_exp);
    while($row = mysqli_fetch_assoc($r_exp)) {
        $data_map[$row['m']][$row['category_id']] = (float)$row['total'];
    }

    // 2. Fetch Bank Withdrawals
    $q_bank = "SELECT MONTH(info.created_at) as m, info.exp_category_id, SUM(price.amount) as total 
               FROM bank_transaction_info info 
               JOIN bank_transaction_price price ON info.info_id = price.info_id
               WHERE info.transaction_type='withdraw' 
                 AND YEAR(info.created_at)='$year' 
                 $where_store 
               GROUP BY MONTH(info.created_at), info.exp_category_id";
    $r_bank = mysqli_query($conn, $q_bank);
    while($row = mysqli_fetch_assoc($r_bank)) {
        if(!isset($data_map[$row['m']][$row['exp_category_id']])) $data_map[$row['m']][$row['exp_category_id']] = 0;
        $data_map[$row['m']][$row['exp_category_id']] += (float)$row['total'];
    }

    // 3. Build Report
    foreach($categories as $cat) {
        $cid = $cat['category_id'];
        $row = ['id' => $cid, 'name' => $cat['category_name'], 'cells' => []];
        $cat_year_total = 0;
        
        for($m = 1; $m <= 12; $m++) {
            $val = $data_map[$m][$cid] ?? 0;
            
            $row['cells'][$m] = $val;
            $totals_line[$m] += $val;
            $cat_year_total += $val;
        }
        $row['total'] = $cat_year_total;
        $grand_total += $cat_year_total;
        $report_data[] = $row;
    }

} else {
    // MONTHLY VIEW OPTIMIZATION
    $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    $totals_line = array_fill_keys(array_column($categories, 'category_id'), 0);
    $data_map = []; // [day][category_id] => total
    
    // 1. Fetch Expenses
    $q_exp = "SELECT DAY(e.created_at) as d, e.category_id, SUM(e.amount) as total 
              FROM expenses e 
              WHERE YEAR(e.created_at)='$year' AND MONTH(e.created_at)='$month' 
                $where_store_expenses 
              GROUP BY DAY(e.created_at), e.category_id";
    $r_exp = mysqli_query($conn, $q_exp);
    while($row = mysqli_fetch_assoc($r_exp)) {
        $data_map[$row['d']][$row['category_id']] = (float)$row['total'];
    }

    // 2. Fetch Bank Withdrawals
    $q_bank = "SELECT DAY(info.created_at) as d, info.exp_category_id, SUM(price.amount) as total 
               FROM bank_transaction_info info 
               JOIN bank_transaction_price price ON info.info_id = price.info_id
               WHERE info.transaction_type='withdraw' 
                 AND YEAR(info.created_at)='$year' AND MONTH(info.created_at)='$month' 
                 $where_store 
               GROUP BY DAY(info.created_at), info.exp_category_id";
    $r_bank = mysqli_query($conn, $q_bank);
    while($row = mysqli_fetch_assoc($r_bank)) {
        if(!isset($data_map[$row['d']][$row['exp_category_id']])) $data_map[$row['d']][$row['exp_category_id']] = 0;
        $data_map[$row['d']][$row['exp_category_id']] += (float)$row['total'];
    }

    // 3. Build Report
    for($d = 1; $d <= $days_in_month; $d++) {
        $row = ['day' => $d, 'cells' => []];
        $day_total = 0;
        
        foreach($categories as $cat) {
            $cid = $cat['category_id'];
            $val = $data_map[$d][$cid] ?? 0;
            
            $row['cells'][$cid] = $val;
            $totals_line[$cid] += $val;
            $day_total += $val;
        }
        $row['total'] = $day_total;
        $grand_total += $day_total;
        $report_data[] = $row;
    }
}

// Highest Expense Category Logic
$max_val = 0; $max_name = 'N/A';
if(!$is_month_view && !$is_custom_range) {
    foreach($report_data as $r) { if($r['total'] > $max_val) { $max_val = $r['total']; $max_name = $r['name']; } }
} else {
    foreach($totals_line as $cid => $v) { if($v > $max_val) { $max_val = $v; foreach($categories as $c) if($c['category_id'] == $cid) $max_name = $c['category_name']; } }
}

// DATA PREPARATION FOR GRAPH
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

// AJAX partial response
if(isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    ob_end_clean();
    ?>
    <textarea id="chart-data-provider" class="hidden"><?= $chart_json ?></textarea>
    <style>
        .glass-card table thead tr th { background-color: #9f1239 !important; color: white !important; }
        .glass-card table thead tr th:last-child { background-color: #020617 !important; }
    </style>
    <?php include('../includes/expense_monthwise_content.php'); exit; }
?>

<link rel="stylesheet" href="/pos/assets/css/expenditureCss/expense_monthwise.css">

<div class="app-wrapper">
    <div class="no-print"><?php include('../includes/sidebar.php'); ?></div>
    
    <main id="main-content" class="flex-1 lg:ml-64 flex flex-col h-screen min-w-0 transition-all duration-300">
        <div class="navbar-fixed-top no-print"><?php include('../includes/navbar.php'); ?></div> 
        
        <div class="content-scroll-area custom-scroll h-full overflow-y-auto" id="report-page-container">
            <div id="ajax-container" class="p-6">
                 <textarea id="chart-data-provider" class="hidden"><?= $chart_json ?></textarea>
                 <div class="print-only-header hidden">
                     <div class="text-center mb-8 border-b-2 border-slate-900 pb-6">
                         <h1 class="text-4xl font-black text-slate-900 mb-2">Velocity POS - Expense Report</h1>
                         <div class="flex items-center justify-center gap-6 text-sm font-bold text-slate-600 uppercase tracking-widest">
                             <span>Type: <?= $is_month_view ? 'Monthly Breakdown' : 'Yearly Overview' ?></span>
                             <span>Period: <?= $is_month_view ? $month_names[$month-1] . ', ' . $year : 'Year ' . $year ?></span>
                         </div>
                     </div>
                 </div>

                <?php include('../includes/expense_monthwise_content.php'); ?>
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
<script src="/pos/assets/js/expenditureJs/expense_monthwise.js"></script>
