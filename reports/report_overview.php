<?php
session_start();
include('../config/dbcon.php');

if(!isset($_SESSION['auth'])){
    header("Location: /pos/signin.php");
    exit(0);
}

$page_title = "Overview Report - Velocity POS";
include('../includes/header.php');

// --- 1. FILTER LOGIC & TAB SELECTION ---

// Defaults
$start_date = date('Y-m-d');
$end_date   = date('Y-m-d');
$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'sell';
$date_filter = isset($_GET['date_filter']) ? $_GET['date_filter'] : '';

// 1. Process Date Presets
if ($date_filter) {
    switch ($date_filter) {
        case 'today':
            $start_date = date('Y-m-d');
            $end_date = date('Y-m-d');
            break;
        case 'yesterday':
            $start_date = date('Y-m-d', strtotime('-1 day'));
            $end_date = date('Y-m-d', strtotime('-1 day'));
            break;
        case '7days':
            $start_date = date('Y-m-d', strtotime('-6 days'));
            $end_date = date('Y-m-d');
            break;
        case '30days':
            $start_date = date('Y-m-d', strtotime('-29 days'));
            $end_date = date('Y-m-d');
            break;
        case 'this_month':
            $start_date = date('Y-m-01');
            $end_date = date('Y-m-t');
            break;
        case 'last_month':
            $start_date = date('Y-m-01', strtotime('last month'));
            $end_date = date('Y-m-t', strtotime('last month'));
            break;
        case '3_months':
            $start_date = date('Y-m-d', strtotime('-3 months'));
            $end_date = date('Y-m-d');
            break;
    }
} elseif (isset($_GET['start_date']) && isset($_GET['end_date'])) {
    $start_date = $_GET['start_date'];
    $end_date   = $_GET['end_date'];
}

$store_filter = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;

// Calculate number of days for the trend loop
$date1 = new DateTime($start_date);
$date2 = new DateTime($end_date);
$interval = $date1->diff($date2);
$days_count = $interval->days + 1; // +1 to include today

// --- 1.1 Previous Period Calculation (for comparison) ---
$prev_end_date = date('Y-m-d', strtotime($start_date . ' -1 day'));
$prev_start_date = date('Y-m-d', strtotime($prev_end_date . ' -' . ($days_count - 1) . ' days'));


// --- 1.2 Build Filter Query String (for link persistence) ---

// --- 1.2 Build Filter Query String (for link persistence) ---
$params = $_GET;
// We'll surgically update this in links
function buildUrl($extra = []) {
    $p = array_merge($_GET, $extra);
    return '/pos/reports/overview?' . http_build_query($p);
}


// --- Helper Functions for Data Fetching ---

function getMetricTotal($conn, $query) {
    $result = mysqli_query($conn, $query);
    $data = mysqli_fetch_assoc($result);
    return $data['total'] ?? 0;
}

function getDailyTrend($conn, $table, $dateCol, $sumCol, $start, $end, $store_id = 0) {
    $data = [];
    $period = new DatePeriod(
         new DateTime($start),
         new DateInterval('P1D'),
         (new DateTime($end))->modify('+1 day')
    );

    $storeSql = ($store_id > 0) ? "AND store_id = '$store_id'" : "";
    
    // Check if table contains store_id column to avoid SQL errors
    // Simple check: purchase_info, selling_info, sell_logs, purchase_logs have it. 
    // If not sure, we can suppress or be specific. Assuming standard tables have it.

    foreach ($period as $date) {
        $currentDate = $date->format('Y-m-d');
        // Check if sumCol already contains an aggregate function like COUNT(*)
        $selectExpr = (strpos($sumCol, '(') !== false) ? $sumCol : "SUM($sumCol)";
        $q = "SELECT $selectExpr as total FROM $table WHERE DATE($dateCol) = '$currentDate' $storeSql";
        $res = mysqli_query($conn, $q);
        $row = mysqli_fetch_assoc($res);
        $data[] = $row['total'] ?? 0;
    }
    return $data;
}

// Hourly Trend Helper (for Today/Yesterday)
function getHourlyTrend($conn, $table, $dateCol, $sumCol, $date, $store_id = 0) {
    if(empty($date)) $date = date('Y-m-d');
    $data = [];
    $storeSql = ($store_id > 0) ? "AND store_id = '$store_id'" : "";
    $selectExpr = (strpos($sumCol, '(') !== false) ? $sumCol : "SUM($sumCol)";

    for ($hour = 0; $hour < 24; $hour++) {
        $q = "SELECT $selectExpr as total FROM $table 
              WHERE DATE($dateCol) = '$date' 
              AND HOUR($dateCol) = $hour 
              $storeSql";
        $res = mysqli_query($conn, $q);
        $row = mysqli_fetch_assoc($res);
        $data[] = floatval($row['total'] ?? 0);
    }
    return $data;
}

// Special case for Selling Item Tax Trend (Calculated)
function getItemTaxTrend($conn, $start, $end, $store_id = 0) {
    if ($start === $end) {
        $data = [];
        $storeSql = ($store_id > 0) ? "AND store_id = '$store_id'" : "";
        for ($hour = 0; $hour < 24; $hour++) {
            $q = "SELECT price_sold, qty_sold, tax_rate, tax_type FROM selling_item 
                  WHERE DATE(created_at) = '$start' AND HOUR(created_at) = $hour $storeSql";
            $res = mysqli_query($conn, $q);
            $hrTax = 0;
            while($row = mysqli_fetch_assoc($res)){
                $amt = $row['price_sold'] * $row['qty_sold'];
                if($row['tax_type'] == 'inclusive') $hrTax += $amt - ($amt / (1 + ($row['tax_rate'] / 100)));
                else $hrTax += $amt * ($row['tax_rate'] / 100);
            }
            $data[] = $hrTax;
        }
        return $data;
    } else { // Daily trend for item tax
        $data = [];
        $period = new DatePeriod(
             new DateTime($start),
             new DateInterval('P1D'),
             (new DateTime($end))->modify('+1 day')
        );
        
        $storeSql = ($store_id > 0) ? "AND store_id = '$store_id'" : "";

        foreach ($period as $date) {
            $currentDate = $date->format('Y-m-d');
            $q = "SELECT price_sold, qty_sold, tax_rate, tax_type FROM selling_item WHERE DATE(created_at) = '$currentDate' $storeSql";
            $res = mysqli_query($conn, $q);
            $dailyTax = 0;
            while($row = mysqli_fetch_assoc($res)){
                $amount = $row['price_sold'] * $row['qty_sold'];
                $rate = $row['tax_rate'];
                if($row['tax_type'] == 'inclusive'){
                    $tax = $amount - ($amount / (1 + ($rate / 100)));
                } else {
                    $tax = $amount * ($rate / 100);
                }
                $dailyTax += $tax;
            }
            $data[] = number_format($dailyTax, 2, '.', '');
        }
        return $data;
    }
}

// Special case for Purchase Item Tax Trend
function getPurchaseItemTaxTrend($conn, $start, $end, $store_id = 0) {
    $data = [];
    $period = new DatePeriod(
         new DateTime($start),
         new DateInterval('P1D'),
         (new DateTime($end))->modify('+1 day')
    );
    $storeSql = ($store_id > 0) ? "AND pin.store_id = '$store_id'" : "";

    foreach ($period as $date) {
        $currentDate = $date->format('Y-m-d');
        $q = "SELECT SUM(pi.item_tax) as total 
              FROM purchase_item pi 
              JOIN purchase_info pin ON pi.invoice_id = pin.invoice_id
              WHERE DATE(pin.created_at) = '$currentDate' $storeSql";
        $res = mysqli_query($conn, $q);
        $row = mysqli_fetch_assoc($res);
        $data[] = $row['total'] ?? 0;
    }
    return $data;
}

function getDynamicRangeText($start, $end) {
    if ($start === $end) {
        if ($start === date('Y-m-d')) return 'Today';
        if ($start === date('Y-m-d', strtotime('-1 day'))) return 'Yesterday';
        return date('d M Y', strtotime($start));
    }

    $d1 = new DateTime($start);
    $d2 = new DateTime($end);
    $d2->modify('+1 day'); // Inclusive
    $diff = $d1->diff($d2);

    $parts = [];
    if ($diff->y > 0) $parts[] = $diff->y . ' ' . ($diff->y > 1 ? 'Years' : 'Year');
    if ($diff->m > 0) $parts[] = $diff->m . ' ' . ($diff->m > 1 ? 'Months' : 'Month');
    if ($diff->d > 0) $parts[] = $diff->d . ' ' . ($diff->d > 1 ? 'Days' : 'Day');

    return empty($parts) ? 'Today' : implode(' ', $parts);
}

$dynamic_range_label = getDynamicRangeText($start_date, $end_date);

// --- 2. FETCH DATA BASED ON TAB ---

if ($current_tab == 'sell') {
    // === SELL OVERVIEW METRICS ===
    
    $date_sql_selling = "WHERE DATE(created_at) BETWEEN '$start_date' AND '$end_date'";
    $date_sql_logs    = "WHERE DATE(created_at) BETWEEN '$start_date' AND '$end_date'";
    
    $prev_date_sql_selling = "WHERE DATE(created_at) BETWEEN '$prev_start_date' AND '$prev_end_date'";
    $prev_date_sql_logs    = "WHERE DATE(created_at) BETWEEN '$prev_start_date' AND '$prev_end_date'";

    if($store_filter > 0) {
        $date_sql_selling .= " AND store_id = '$store_filter'";
        $date_sql_logs    .= " AND store_id = '$store_filter'";
        $prev_date_sql_selling .= " AND store_id = '$store_filter'";
        $prev_date_sql_logs    .= " AND store_id = '$store_filter'";
    }

    // Metric Totals
    $invoiceAmount = getMetricTotal($conn, "SELECT SUM(grand_total) as total FROM selling_info $date_sql_selling");
    $prevInvoiceAmount = getMetricTotal($conn, "SELECT SUM(grand_total) as total FROM selling_info $prev_date_sql_selling");
    $discountAmount = getMetricTotal($conn, "SELECT SUM(discount_amount) as total FROM selling_info $date_sql_selling");
    $prevDiscountAmount = getMetricTotal($conn, "SELECT SUM(discount_amount) as total FROM selling_info $prev_date_sql_selling");
    $shippingCharge = getMetricTotal($conn, "SELECT SUM(shipping_charge) as total FROM selling_info $date_sql_selling");
    $prevShippingCharge = getMetricTotal($conn, "SELECT SUM(shipping_charge) as total FROM selling_info $prev_date_sql_selling");
    $othersCharge = getMetricTotal($conn, "SELECT SUM(other_charge) as total FROM selling_info $date_sql_selling");
    $prevOthersCharge = getMetricTotal($conn, "SELECT SUM(other_charge) as total FROM selling_info $prev_date_sql_selling");
    $orderTax = getMetricTotal($conn, "SELECT SUM(tax_amount) as total FROM selling_info $date_sql_selling");
    $prevOrderTax = getMetricTotal($conn, "SELECT SUM(tax_amount) as total FROM selling_info $prev_date_sql_selling");

    $totalCollectedVal = getMetricTotal($conn, "SELECT SUM(amount) as total FROM sell_logs $date_sql_logs"); 
    $prevTotalCollectedVal = getMetricTotal($conn, "SELECT SUM(amount) as total FROM sell_logs $prev_date_sql_logs");
    $dueCollection = $totalCollectedVal;
    $prevDueCollection = $prevTotalCollectedVal;

    $dueGiven = max(0, $invoiceAmount - $totalCollectedVal);
    $prevDueGiven = max(0, $prevInvoiceAmount - $prevTotalCollectedVal);

    $itemTax = 0;
    $qIT = mysqli_query($conn, "SELECT price_sold, qty_sold, tax_rate, tax_type FROM selling_item $date_sql_selling");
    while($r = mysqli_fetch_assoc($qIT)) {
        $amt = $r['price_sold'] * $r['qty_sold'];
        if($r['tax_type'] == 'inclusive') $itemTax += $amt - ($amt / (1 + ($r['tax_rate'] / 100)));
        else $itemTax += $amt * ($r['tax_rate'] / 100);
    }
    $prevItemTax = 0;
    $pqIT = mysqli_query($conn, "SELECT price_sold, qty_sold, tax_rate, tax_type FROM selling_item $prev_date_sql_selling");
    while($r = mysqli_fetch_assoc($pqIT)) {
        $amt = $r['price_sold'] * $r['qty_sold'];
        if($r['tax_type'] == 'inclusive') $prevItemTax += $amt - ($amt / (1 + ($r['tax_rate'] / 100)));
        else $prevItemTax += $amt * ($r['tax_rate'] / 100);
    }

    // Trends
    if ($days_count <= 2) {
        // Hourly (If 2 days, use just the last selected day for cleaner visual or concat? Let's use current selection)
        // Usually, Today/Yesterday means single day focus.
        $target_date = $end_date; 
        $invoiceTrend = getHourlyTrend($conn, 'selling_info', 'created_at', 'grand_total', $target_date, $store_filter);
        $discountTrend = getHourlyTrend($conn, 'selling_info', 'created_at', 'discount_amount', $target_date, $store_filter);
        $collectionTrend = getHourlyTrend($conn, 'sell_logs', 'created_at', 'amount', $target_date, $store_filter);
        $shippingTrend = getHourlyTrend($conn, 'selling_info', 'created_at', 'shipping_charge', $target_date, $store_filter);
        $othersTrend = getHourlyTrend($conn, 'selling_info', 'created_at', 'other_charge', $target_date, $store_filter);
        $orderTaxTrend = getHourlyTrend($conn, 'selling_info', 'created_at', 'tax_amount', $target_date, $store_filter);
        $ordersCountTrend = getHourlyTrend($conn, 'selling_info', 'created_at', 'COUNT(*)', $target_date, $store_filter);
        $itemTaxTrend = getItemTaxTrend($conn, $start_date, $end_date, $store_filter);
    } else {
        // Daily
        $invoiceTrend = getDailyTrend($conn, 'selling_info', 'created_at', 'grand_total', $start_date, $end_date, $store_filter);
        $discountTrend = getDailyTrend($conn, 'selling_info', 'created_at', 'discount_amount', $start_date, $end_date, $store_filter);
        $collectionTrend = getDailyTrend($conn, 'sell_logs', 'created_at', 'amount', $start_date, $end_date, $store_filter);
        $shippingTrend = getDailyTrend($conn, 'selling_info', 'created_at', 'shipping_charge', $start_date, $end_date, $store_filter);
        $othersTrend = getDailyTrend($conn, 'selling_info', 'created_at', 'other_charge', $start_date, $end_date, $store_filter);
        $orderTaxTrend = getDailyTrend($conn, 'selling_info', 'created_at', 'tax_amount', $start_date, $end_date, $store_filter);
        $ordersCountTrend = getDailyTrend($conn, 'selling_info', 'created_at', 'COUNT(*)', $start_date, $end_date, $store_filter);
        $itemTaxTrend = getItemTaxTrend($conn, $start_date, $end_date, $store_filter);
    }

    $totalOrdersCount = array_sum($ordersCountTrend);
    // Prev period for count comparison
    $prev_orders_count_val = getMetricTotal($conn, "SELECT COUNT(*) as total FROM selling_info $prev_date_sql_selling");
    $prevTotalOrdersCount = $prev_orders_count_val;

    // Calculate Due Trend
    $dueTrend = [];
    $cnt = count($invoiceTrend);
    for($i=0; $i<$cnt; $i++) {
        $dueTrend[] = max(0, ($invoiceTrend[$i] ?? 0) - ($collectionTrend[$i] ?? 0));
    }
} else {
    // === PURCHASE OVERVIEW METRICS ===

    $date_sql_purch = "WHERE DATE(created_at) BETWEEN '$start_date' AND '$end_date'";
    $date_sql_logs  = "WHERE DATE(created_at) BETWEEN '$start_date' AND '$end_date' AND type='purchase'";
    
    $prev_date_sql_purch = "WHERE DATE(created_at) BETWEEN '$prev_start_date' AND '$prev_end_date'";
    $prev_date_sql_logs  = "WHERE DATE(created_at) BETWEEN '$prev_start_date' AND '$prev_end_date' AND type='purchase'";

    if($store_filter > 0) {
        $date_sql_purch .= " AND store_id = '$store_filter'";
        $date_sql_logs  .= " AND store_id = '$store_filter'";
        $prev_date_sql_purch .= " AND store_id = '$store_filter'";
        $prev_date_sql_logs  .= " AND store_id = '$store_filter'";
    }

    // Metric Totals
    $purchaseAmount = getMetricTotal($conn, "SELECT SUM(total_sell) as total FROM purchase_info $date_sql_purch");
    $prevPurchaseAmount = getMetricTotal($conn, "SELECT SUM(total_sell) as total FROM purchase_info $prev_date_sql_purch");
    $pDiscountAmount = getMetricTotal($conn, "SELECT SUM(discount_amount) as total FROM purchase_info $date_sql_purch");
    $prevPDiscountAmount = getMetricTotal($conn, "SELECT SUM(discount_amount) as total FROM purchase_info $prev_date_sql_purch");
    $pPaidAmount = getMetricTotal($conn, "SELECT SUM(amount) as total FROM purchase_logs $date_sql_logs");
    $prevPPaidAmount = getMetricTotal($conn, "SELECT SUM(amount) as total FROM purchase_logs $prev_date_sql_logs");
    $pShippingCharge = getMetricTotal($conn, "SELECT SUM(shipping_charge) as total FROM purchase_info $date_sql_purch");
    $prevPShippingCharge = getMetricTotal($conn, "SELECT SUM(shipping_charge) as total FROM purchase_info $prev_date_sql_purch");
    
    // Others Charge (not available in purchase_info table, set to 0)
    $pOthersCharge = 0;
    $prevPOthersCharge = 0;
    
    $pDueTaken = max(0, $purchaseAmount - $pPaidAmount);
    $prevPDueTaken = max(0, $prevPurchaseAmount - $prevPPaidAmount);

    $qReturn = "SELECT SUM(pi.return_quantity * pi.item_purchase_price) as total FROM purchase_item pi JOIN purchase_info pin ON pi.invoice_id = pin.invoice_id $date_sql_purch";
    $pReturnAmount = getMetricTotal($conn, $qReturn);
    $pqReturn = "SELECT SUM(pi.return_quantity * pi.item_purchase_price) as total FROM purchase_item pi JOIN purchase_info pin ON pi.invoice_id = pin.invoice_id $prev_date_sql_purch";
    $prevPReturnAmount = getMetricTotal($conn, $pqReturn);

    $pItemTax = getMetricTotal($conn, "SELECT SUM(pi.item_tax) as total FROM purchase_item pi JOIN purchase_info pin ON pi.invoice_id = pin.invoice_id $date_sql_purch");
    $prevPItemTax = getMetricTotal($conn, "SELECT SUM(pi.item_tax) as total FROM purchase_item pi JOIN purchase_info pin ON pi.invoice_id = pin.invoice_id $prev_date_sql_purch");

    $qOrderTaxTotal = "SELECT SUM((SELECT SUM(item_total) FROM purchase_item WHERE invoice_id = pin.invoice_id) * pin.order_tax / 100) as total FROM purchase_info pin $date_sql_purch";
    $pOrderTax = getMetricTotal($conn, $qOrderTaxTotal);
    $pqOrderTaxTotal = "SELECT SUM((SELECT SUM(item_total) FROM purchase_item WHERE invoice_id = pin.invoice_id) * pin.order_tax / 100) as total FROM purchase_info pin $prev_date_sql_purch";
    $prevPOrderTax = getMetricTotal($conn, $pqOrderTaxTotal);

    if ($days_count <= 2) {
        $target_date = $end_date;
        $purchaseTrend = getHourlyTrend($conn, 'purchase_info', 'created_at', 'total_sell', $target_date, $store_filter);
        $pDiscountTrend = getHourlyTrend($conn, 'purchase_info', 'created_at', 'discount_amount', $target_date, $store_filter);
        $pPaidTrend = getHourlyTrend($conn, 'purchase_logs', 'created_at', 'amount', $target_date, $store_filter);
        $pShippingTrend = getHourlyTrend($conn, 'purchase_info', 'created_at', 'shipping_charge', $target_date, $store_filter);
        $pOrdersCountTrend = getHourlyTrend($conn, 'purchase_info', 'created_at', 'COUNT(*)', $target_date, $store_filter);
        
        $pItemTaxTrend = [];
        $pReturnTrend = [];
        for ($h=0; $h<24; $h++) {
            $hq = "SELECT SUM(pi.item_tax) as tax, SUM(pi.return_quantity * pi.item_purchase_price) as ret 
                   FROM purchase_item pi JOIN purchase_info pin ON pi.invoice_id=pin.invoice_id 
                   WHERE DATE(pin.created_at)='$target_date' AND HOUR(pin.created_at)=$h " . ($store_filter ? "AND pin.store_id=$store_filter" : "");
            $hr = mysqli_query($conn, $hq);
            $ha = mysqli_fetch_assoc($hr);
            $pItemTaxTrend[] = $ha['tax'] ?? 0;
            $pReturnTrend[] = $ha['ret'] ?? 0;
        }
    } else {
        $purchaseTrend = getDailyTrend($conn, 'purchase_info', 'created_at', 'total_sell', $start_date, $end_date, $store_filter);
        $pDiscountTrend = getDailyTrend($conn, 'purchase_info', 'created_at', 'discount_amount', $start_date, $end_date, $store_filter);
        $pPaidTrend = getDailyTrend($conn, 'purchase_logs', 'created_at', 'amount', $start_date, $end_date, $store_filter);
        $pShippingTrend = getDailyTrend($conn, 'purchase_info', 'created_at', 'shipping_charge', $start_date, $end_date, $store_filter);
        $pOrdersCountTrend = getDailyTrend($conn, 'purchase_info', 'created_at', 'COUNT(*)', $start_date, $end_date, $store_filter);
        $pItemTaxTrend = getPurchaseItemTaxTrend($conn, $start_date, $end_date, $store_filter);
        
        $pReturnTrend = [];
        $period = new DatePeriod(new DateTime($start_date), new DateInterval('P1D'), (new DateTime($end_date))->modify('+1 day'));
        foreach ($period as $date) {
            $dStr = $date->format('Y-m-d');
            $dq = "SELECT SUM(pi.return_quantity * pi.item_purchase_price) as total 
                   FROM purchase_item pi JOIN purchase_info pin ON pi.invoice_id = pin.invoice_id 
                   WHERE DATE(pin.created_at) = '$dStr' " . ($store_filter ? "AND pin.store_id='$store_filter'" : "");
            $dr = mysqli_query($conn, $dq);
            $da = mysqli_fetch_assoc($dr);
            $pReturnTrend[] = $da['total'] ?? 0;
        }
    }

    $totalPOrdersCount = array_sum($pOrdersCountTrend);
    $prevPOrdersCountTrendSql = getMetricTotal($conn, "SELECT COUNT(*) as total FROM purchase_info $prev_date_sql_purch");
    $prevPTotalOrdersCount = $prevPOrdersCountTrendSql;

    $pDueTakenTrend = [];
    $count = count($purchaseTrend);
    for($i=0; $i<$count; $i++) {
        $val = ($purchaseTrend[$i] ?? 0) - ($pPaidTrend[$i] ?? 0);
        $pDueTakenTrend[] = max(0, $val);
    }
    
    $pOthersTrend = array_fill(0, count($purchaseTrend), 0);
    $pOrderTaxTrend = array_fill(0, count($purchaseTrend), 0);
}

// Total Tax Trend Logic
if($current_tab == 'sell') {
    $totalTax = $orderTax + $itemTax;
    $totalTaxTrend = []; 
    $t_cnt = count($orderTaxTrend);
    for($i=0; $i<$t_cnt; $i++) {
        $totalTaxTrend[] = ($orderTaxTrend[$i] ?? 0) + ($itemTaxTrend[$i] ?? 0);
    }
} else {
    $totalTax = $pOrderTax + $pItemTax;
    $totalTaxTrend = [];
    $t_cnt = count($pOrderTaxTrend);
    for($i=0; $i<$t_cnt; $i++) {
        $totalTaxTrend[] = ($pOrderTaxTrend[$i] ?? 0) + ($pItemTaxTrend[$i] ?? 0);
    }
}
?>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">


<style>
    /* Premium Look Styles */
    body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
    .custom-scroll::-webkit-scrollbar { width: 6px; }
    .custom-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
    
    /* Card Hover Animation */
    .stat-card {
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.3s ease;
    }
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
        border-color: #e2e8f0;
    }

    /* Date Picker Styling */
    .date-input {
        background: #fff;
        border: 1px solid #e2e8f0;
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        outline: none;
        transition: border-color 0.2s;
    }
    .date-input:focus { border-color: #3b82f6; ring: 2px solid #3b82f6; }
</style>

<div class="app-wrapper">
    <?php include('../includes/sidebar.php'); ?>

    <main id="main-content" class="flex-1 lg:ml-64 flex flex-col h-screen min-w-0 transition-all duration-300 bg-gray-50">
        <div class="navbar-fixed-top">
            <?php include('../includes/navbar.php'); ?>
        </div>

        <div class="content-scroll-area custom-scroll h-full overflow-y-auto">
            <div class="p-6">
                
                <!-- Page Header (Using Reusable Component) -->
                <?php
                include_once('../includes/reusable_list.php');

                // Build Store Filter Options
                $store_options = [['label' => 'All Stores', 'url' => buildUrl(['store_id' => 0]), 'active' => $store_filter == 0]];
                $s_q = mysqli_query($conn, "SELECT id, store_name FROM stores WHERE status=1");
                while($s = mysqli_fetch_assoc($s_q)) {
                    $store_options[] = [
                        'label' => $s['store_name'],
                        'url' => buildUrl(['store_id' => $s['id']]),
                        'active' => $store_filter == $s['id']
                    ];
                }

                ?>
                <div class="w-full animate-fade-in">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
                        <!-- Title Section -->
                        <div>
                            <h1 class="text-3xl font-black text-slate-800 mb-1">Overview Report</h1>
                            <div class="flex items-center gap-2 text-xs font-bold text-slate-400 uppercase tracking-widest">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                <span><?= ($current_tab == 'sell') ? 'Sell Dashboard' : 'Purchase Dashboard'; ?></span>
                            </div>
                        </div>

                        <!-- Filters Section -->
                        <div class="flex flex-wrap items-center gap-2 md:gap-3 w-full lg:w-auto lg:justify-end">
                             
                             <!-- Store Filter -->
                             <?php
                             $active_store_label = 'All Stores';
                             $is_store_active = false;
                             foreach($store_options as $opt) { 
                                if($opt['active'] && $opt['url'] !== '?store_id=0') { // Assuming ID 0 is clean/inactive
                                    $active_store_label = $opt['label'];
                                    $is_store_active = ($opt['label'] !== 'All Stores');
                                }
                             }
                             // Correction: if ID > 0, it is active
                             if($store_filter > 0) $is_store_active = true;
                             ?>
                             <div class="relative w-full md:w-auto">
                                 <button type="button" onclick="toggleFilterDropdown('store_filter')" class="inline-flex items-center justify-center gap-2 px-5 py-3 <?= $is_store_active ? 'bg-teal-600 text-white' : 'bg-slate-100 text-slate-700'; ?> hover:bg-teal-700 hover:text-white font-bold rounded-lg shadow transition-all w-full md:w-auto">
                                     <i class="fas fa-store"></i>
                                     <span><?= $active_store_label; ?></span>
                                     <i class="fas fa-chevron-down text-xs"></i>
                                 </button>
                                 <div id="store_filter" class="hidden absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-xl border border-slate-200 z-50 overflow-hidden">
                                     <div class="p-2 border-b border-slate-100">
                                        <input type="text" placeholder="Search..." class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-teal-500" onkeyup="filterDropdownOptions(this, 'store_filter')">
                                     </div>
                                     <div class="max-h-60 overflow-y-auto filter-options">
                                         <?php 
                                         $store_count = 0;
                                         foreach($store_options as $opt): 
                                             $store_count++;
                                             // Hide items after the first 6 (All Stores + 5 Stores)
                                             $hidden_class = ($store_count > 6) ? 'hidden-initially' : '';
                                         ?>
                                             <a href="<?= $opt['url']; ?>" class="flex items-center gap-2 w-full px-4 py-2.5 text-sm text-slate-700 hover:bg-teal-50 hover:text-teal-700 transition-colors <?= $opt['active'] ? 'bg-teal-50 text-teal-700 font-semibold' : ''; ?> <?= $hidden_class; ?>" data-text="<?= strtolower($opt['label']); ?>">
                                                 <?= $opt['label']; ?>
                                             </a>
                                         <?php endforeach; ?>
                                     </div>
                                 </div>
                             </div>

                             <!-- Date Filter -->
                               <?php
                               // Determine active filter label
                               $filter_label = 'Date';
                               if($date_filter) {
                                   $filter_label = ucwords(str_replace('_', ' ', $date_filter));
                                   if($date_filter == '7days') $filter_label = 'Last 7 Days';
                                   if($date_filter == '30days') $filter_label = 'Last 30 Days';
                               } elseif($start_date && $end_date) {
                                   if($start_date === $end_date && $start_date === date('Y-m-d')) $filter_label = 'Today';
                                   else $filter_label = date('d M Y', strtotime($start_date)) . ' - ' . date('d M Y', strtotime($end_date));
                               }
                               // Is active?
                                $is_date_active = !($start_date === $end_date && $start_date === date('Y-m-d'));
                                ?>
                               <div class="relative w-full md:w-auto">
                                    <button type="button" onclick="toggleFilterDropdown('filter_date')" class="inline-flex items-center justify-center gap-2 px-5 py-3 <?= $is_date_active ? 'bg-teal-600 text-white' : 'bg-slate-100 text-slate-700'; ?> hover:bg-teal-700 hover:text-white font-bold rounded-lg shadow transition-all w-full md:min-w-[180px]">
                                        <i class="fas fa-calendar-alt"></i>
                                        <span><?= $filter_label; ?></span>
                                        <i class="fas fa-chevron-down text-xs"></i>
                                    </button>
                                    <div id="filter_date" class="hidden absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-xl border border-slate-200 z-50 overflow-hidden">
                                        <!-- Selected Date Range Display -->
                                        <?php if($start_date && $end_date): ?>
                                        <div class="p-4 border-b border-slate-100 bg-slate-50">
                                            <div class="text-center text-sm font-semibold text-slate-700">
                                                <?= date('d M Y', strtotime($start_date)); ?> - <?= date('d M Y', strtotime($end_date)); ?>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <!-- Preset Filters -->
                                        <div id="preset-filters-list" class="max-h-60 overflow-y-auto">
                                            <?php 
                                            // Presets adapted for Report Overview
                                            $ranges = [
                                                'today' => 'Today',
                                                'yesterday' => 'Yesterday',
                                                '7days' => 'Last 7 Days',
                                                '30days' => 'Last 30 Days',
                                                'this_month' => 'This Month',
                                                'last_month' => 'Last Month',
                                                '3_months' => 'Last 3 Months'
                                            ];
                                            foreach($ranges as $key => $label): 
                                                $active = ($date_filter == $key);
                                            ?>
                                                <a href="<?= buildUrl(['date_filter' => $key, 'start_date' => null, 'end_date' => null]); ?>" 
                                                   class="block w-full px-4 py-3 text-sm text-slate-700 hover:bg-slate-50 transition-colors <?= ($date_filter == $key) ? 'bg-slate-50 font-semibold' : ''; ?>">
                                                    <?= $label; ?>
                                                </a>
                                            <?php endforeach; ?>
                                            
                                            <!-- Custom Option -->
                                            <button type="button" onclick="event.stopPropagation(); showCustomCalendar()" class="block w-full px-4 py-3 text-sm text-teal-600 hover:bg-slate-50 transition-colors text-left font-semibold">
                                                Custom
                                            </button>
                                        </div>

                                        <!-- Custom Calendar Picker (Hidden by default) -->
                                        <div id="custom-calendar-picker" class="hidden border-t border-slate-200 bg-white p-4">
                                            <!-- Month/Year Navigation -->
                                            <div class="flex items-center justify-between mb-4">
                                                <button type="button" onclick="event.stopPropagation(); changeCalendarMonth(-1)" class="p-1 hover:bg-slate-100 rounded text-slate-600">
                                                    <i class="fas fa-chevron-left"></i>
                                                </button>
                                                <div class="relative">
                                                     <button type="button" onclick="event.stopPropagation(); toggleYearMonthSelector()" id="calendar-month-display" class="font-bold text-slate-800 hover:text-teal-600 transition-colors">
                                                         <?= date('M Y'); ?>
                                                     </button>
                                                     <!-- Year/Month Selector -->
                                                     <div id="year-month-selector" class="hidden absolute top-full left-1/2 transform -translate-x-1/2 mt-2 bg-white shadow-xl border border-slate-200 rounded-lg p-3 z-50 w-48">
                                                         <div class="flex gap-2">
                                                             <select id="month-select" class="w-1/2 p-2 border border-slate-200 rounded text-sm outline-none focus:border-teal-500">
                                                                 <?php 
                                                                 $months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                                                                 foreach($months as $i => $m) echo "<option value='$i'>$m</option>";
                                                                 ?>
                                                             </select>
                                                             <select id="year-select" class="w-1/2 p-2 border border-slate-200 rounded text-sm outline-none focus:border-teal-500">
                                                                 <!-- Populated by JS -->
                                                             </select>
                                                         </div>
                                                         <button onclick="event.stopPropagation(); applyYearMonthSelection()" class="w-full mt-2 bg-teal-600 text-white text-xs font-bold py-2 rounded hover:bg-teal-700">Apply</button>
                                                     </div>
                                                </div>
                                                <button type="button" onclick="event.stopPropagation(); changeCalendarMonth(1)" class="p-1 hover:bg-slate-100 rounded text-slate-600">
                                                    <i class="fas fa-chevron-right"></i>
                                                </button>
                                            </div>
                                            
                                            <!-- Days Header -->
                                            <div class="grid grid-cols-7 gap-1 text-center mb-2">
                                                <?php foreach(['S','M','T','W','T','F','S'] as $d): ?>
                                                    <div class="text-[10px] font-bold text-slate-400"><?= $d; ?></div>
                                                <?php endforeach; ?>
                                            </div>
                                            
                                            <!-- Calendar Grid -->
                                            <div id="calendar-grid" class="grid grid-cols-7 gap-1">
                                                <!-- Populated by JS -->
                                            </div>
                                            
                                            <!-- Selected Range Display -->
                                            <div class="mt-4 pt-4 border-t border-slate-100 text-center">
                                                <span class="text-xs text-slate-500">Selected:</span>
                                                <div id="calendar-selected-range" class="font-bold text-teal-600 text-sm mt-1">Select date range</div>
                                            </div>
                                        </div>

                                    </div>
                               </div>
                                <?php if($store_filter > 0 || $date_filter || (isset($_GET['start_date']) && isset($_GET['end_date']))): ?>
                                    <a href="/pos/reports/overview?tab=<?= $current_tab; ?>" class="w-full md:w-auto inline-flex items-center justify-center gap-2 px-5 py-3 bg-red-100 hover:bg-red-200 text-red-700 font-bold rounded-lg border border-red-200 transition-all" title="Clear All Filters">
                                        <i class="fas fa-undo text-xs"></i>
                                        <span>Reset</span>
                                    </a>
                                <?php endif; ?>
                         </div>
                    </div>
                </div>
                <?php
                ?>

                <!-- Tabs -->
                <div class="mb-8 border-b border-slate-200 mt-6">
                    <nav class="-mb-px flex space-x-8">
                        <a href="<?= buildUrl(['tab' => 'sell']); ?>" 
                           class="<?= ($current_tab == 'sell') ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'; ?> whitespace-nowrap pb-4 px-1 border-b-2 font-semibold text-sm transition-colors">
                            Sell Overview
                        </a>
                        <a href="<?= buildUrl(['tab' => 'purchase']); ?>" 
                           class="<?= ($current_tab == 'purchase') ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'; ?> whitespace-nowrap pb-4 px-1 border-b-2 font-semibold text-sm transition-colors">
                            Purchase Overview
                        </a>
                    </nav>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 mb-8">
                    <?php if ($current_tab == 'sell'): ?>
                        <?php renderModernCard("Invoice Amount", $invoiceAmount, $prevInvoiceAmount, "fa-file-invoice-dollar", "blue", "chart-invoice", $dynamic_range_label); ?>
                        <?php renderModernCard("Discount Amount", $discountAmount, $prevDiscountAmount, "fa-tags", "emerald", "chart-discount", $dynamic_range_label); ?>
                        <?php renderModernCard("Due Given", $dueGiven, $prevDueGiven, "fa-hand-holding-usd", "rose", "chart-due", $dynamic_range_label); ?>
                        <?php renderModernCard("Due Collection", $dueCollection, $prevDueCollection, "fa-coins", "teal", "chart-collection", $dynamic_range_label); ?>
                        <?php renderModernCard("Shipping Charge", $shippingCharge, $prevShippingCharge, "fa-truck-fast", "indigo", "chart-shipping", $dynamic_range_label); ?>
                        <?php renderModernCard("Others Charge", $othersCharge, $prevOthersCharge, "fa-receipt", "orange", "chart-others", $dynamic_range_label); ?>
                    
                    <?php else: ?>
                        <!-- Purchase Overview Cards -->
                        <?php renderModernCard("Purchase Amount", $purchaseAmount, $prevPurchaseAmount, "fa-shopping-cart", "blue", "chart-p-amount", $dynamic_range_label); ?>
                        <?php renderModernCard("Discount Amount", $pDiscountAmount, $prevPDiscountAmount, "fa-tags", "emerald", "chart-p-discount", $dynamic_range_label); ?>
                        <?php renderModernCard("Due Taken", $pDueTaken, $prevPDueTaken, "fa-hand-holding-usd", "rose", "chart-p-due", $dynamic_range_label); ?>
                        <?php renderModernCard("Due Paid", $pPaidAmount, $prevPPaidAmount, "fa-money-bill-wave", "teal", "chart-p-paid", $dynamic_range_label); ?>
                        <?php renderModernCard("Shipping Charge", $pShippingCharge, $prevPShippingCharge, "fa-truck-fast", "indigo", "chart-p-shipping", $dynamic_range_label); ?>
                        <?php renderModernCard("Others Charge", $pOthersCharge, $prevPOthersCharge, "fa-receipt", "orange", "chart-p-others", $dynamic_range_label); ?>
                        <?php renderModernCard("Total Paid", $pPaidAmount, $prevPPaidAmount, "fa-check-circle", "purple", "chart-p-total-paid", $dynamic_range_label); ?>
                        <?php renderModernCard("Return Amount", $pReturnAmount, $prevPReturnAmount, "fa-undo", "rose", "chart-p-return", $dynamic_range_label); ?>
                    <?php endif; ?>
                </div>

                <!-- Interactive Trend Analytics Section -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-xl p-8 mb-8 slide-in relative overflow-hidden group">
                    <!-- Dynamic Background Gradients (Subtle) -->
                    <div class="absolute top-0 right-0 w-64 h-64 bg-teal-500/5 rounded-full -mr-32 -mt-32 blur-3xl group-hover:bg-teal-500/10 transition-colors"></div>
                    <div class="absolute bottom-0 left-0 w-64 h-64 bg-blue-500/5 rounded-full -ml-32 -mb-32 blur-3xl group-hover:bg-blue-500/10 transition-colors"></div>

                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8 relative z-10">
                        <div>
                            <div class="flex items-center gap-3 mb-1">
                                <div class="w-10 h-10 rounded-xl bg-slate-900 flex items-center justify-center text-teal-400 shadow-lg">
                                    <i class="fas fa-chart-line text-lg"></i>
                                </div>
                                <h3 class="text-2xl font-black text-slate-800 tracking-tight">Performance Trend</h3>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-2 bg-slate-50 p-1.5 rounded-xl border border-slate-200">
                             <button onclick="switchTrendData('revenue')" id="trend-revenue-btn" class="px-4 py-2 text-xs font-black bg-white text-slate-800 shadow-sm rounded-lg uppercase tracking-wider transition-all">
                                <?= ($current_tab == 'sell') ? 'Sell' : 'Purchase'; ?>
                             </button>
                             <button onclick="switchTrendData('orders')" id="trend-orders-btn" class="px-4 py-2 text-xs font-bold text-slate-400 hover:text-slate-600 rounded-lg uppercase tracking-wider transition-all">
                                Orders
                             </button>
                        </div>
                    </div>

                    <div class="h-[400px] w-full" id="main-trend-chart"></div>
                    
                    <div id="chart-growth-info" class="mt-8 flex flex-wrap gap-8 pt-8 border-t border-slate-50">
                        <div class="flex items-center gap-4">
                            <div class="w-1.5 h-10 rounded-full bg-blue-500"></div>
                            <div>
                                <p id="trend-volume-label" class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Total Volume</p>
                                <h4 id="trend-volume-value" class="text-xl font-black text-slate-800 tracking-tight"><?= number_format($current_tab == 'sell' ? $invoiceAmount : $purchaseAmount, 2); ?></h4>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 border-l border-slate-100 pl-8">
                            <div class="w-1.5 h-10 rounded-full bg-teal-500"></div>
                            <div>
                                <p id="trend-avg-label" class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Avg Daily</p>
                                <h4 id="trend-avg-value" class="text-xl font-black text-slate-800 tracking-tight"><?= number_format(($current_tab == 'sell' ? $invoiceAmount : $purchaseAmount) / max(1, $days_count), 2); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tax Breakdown -->
                <div class="bg-white rounded-xl border border-slate-100 shadow-lg p-6 mb-6 slide-in" style="animation-delay: 0.1s;">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center text-white shadow-md">
                            <i class="fas fa-calculator"></i>
                        </div>
                        <h3 class="text-xl font-bold text-slate-800">Tax Breakdown</h3>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <?php if ($current_tab == 'sell'): ?>
                            <?php renderMiniCard("Order Tax", $orderTax, $prevOrderTax, "purple", "chart-ordertax"); ?>
                            <?php renderMiniCard("Item Tax", $itemTax, $prevItemTax, "fuchsia", "chart-itemtax"); ?>
                            <?php renderMiniCard("Total Tax", $totalTax, ($prevOrderTax + $prevItemTax), "violet", "chart-totaltax"); ?>
                        <?php else: ?>
                            <?php renderMiniCard("Order Tax", $pOrderTax, $prevPOrderTax, "purple", "chart-p-ordertax"); ?>
                            <?php renderMiniCard("Item Tax", $pItemTax, $prevPItemTax, "fuchsia", "chart-p-itemtax"); ?>
                            <?php renderMiniCard("Total Tax", $totalTax, ($prevPOrderTax + $prevPItemTax), "violet", "chart-p-totaltax"); ?>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
            
            <?php include('../includes/footer.php'); ?>
        </div>
    </main>
</div>

<?php
// --- UI Render Functions ---

function renderModernCard($title, $value, $prevValue, $icon, $color, $chartId, $range) {
    $themes = [
        'blue'    => ['from' => 'from-blue-500',    'to' => 'to-blue-600',    'text' => 'text-blue-600',    'bg_soft' => 'bg-blue-50'],
        'emerald' => ['from' => 'from-emerald-500', 'to' => 'to-emerald-600', 'text' => 'text-emerald-600', 'bg_soft' => 'bg-emerald-50'],
        'rose'    => ['from' => 'from-rose-500',    'to' => 'to-rose-600',    'text' => 'text-rose-600',    'bg_soft' => 'bg-rose-50'],
        'teal'    => ['from' => 'from-teal-500',    'to' => 'to-teal-600',    'text' => 'text-teal-600',    'bg_soft' => 'bg-teal-50'],
        'indigo'  => ['from' => 'from-indigo-500',  'to' => 'to-indigo-600',  'text' => 'text-indigo-600',  'bg_soft' => 'bg-indigo-50'],
        'orange'  => ['from' => 'from-orange-500',  'to' => 'to-orange-600',  'text' => 'text-orange-600',  'bg_soft' => 'bg-orange-50'],
        'purple'  => ['from' => 'from-purple-500',  'to' => 'to-purple-600',  'text' => 'text-purple-600',  'bg_soft' => 'bg-purple-50'],
    ];
    $t = $themes[$color] ?? $themes['blue'];

    // Calculation comparison
    $diff = $value - $prevValue;
    $percent = ($prevValue > 0) ? ($diff / $prevValue) * 100 : 100;
    $isUp = ($diff >= 0);
    if($prevValue == 0 && $value == 0) $percent = 0;

    echo '
    <div class="bg-white rounded-2xl p-6 shadow-lg border border-slate-100 slide-in relative overflow-hidden group hover:shadow-2xl transition-all duration-300">
        <div class="flex justify-between items-start mb-4">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br '.$t['from'].' '.$t['to'].' flex items-center justify-center text-white shadow-lg group-hover:rotate-6 transition-transform">
                    <i class="fas '.$icon.' text-2xl"></i>
                </div>
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">'.$title.'</p>
                    <h3 class="text-2xl font-black text-slate-800 mt-1">'.number_format($value, 2).'</h3>
                </div>
            </div>
            <div class="flex flex-col items-end">
                <span class="flex items-center gap-1 text-[10px] font-black '.($isUp ? 'text-emerald-500' : 'text-rose-500').' '.$t['bg_soft'].' px-2 py-1 rounded-full">
                    <i class="fas fa-arrow-'.($isUp ? 'up' : 'down').'"></i>
                    '.number_format(abs($percent), 1).'%
                </span>
            </div>
        </div>
        
        <div class="relative h-[80px] w-full -mx-2">
             <div id="'.$chartId.'"></div>
        </div>
        
        <div class="flex items-center justify-between mt-4 pt-4 border-t border-slate-50">
             <div class="flex items-center gap-2">
                <span class="w-1.5 h-1.5 rounded-full '.($isUp ? 'bg-emerald-500' : 'bg-rose-500').'"></span>
                <span class="text-[10px] font-bold text-slate-400">'.$range.'</span>
             </div>
             <a href="#" class="text-[10px] font-black '.$t['text'].' uppercase tracking-wider hover:underline">Analytics <i class="fas fa-chevron-right ml-1"></i></a>
        </div>
    </div>
    ';
}

function renderMiniCard($title, $value, $prevValue, $color, $chartId) {
    $themes = [
        'purple'  => ['bg' => 'bg-purple-50', 'text' => 'text-purple-700', 'dot' => 'bg-purple-500'],
        'fuchsia' => ['bg' => 'bg-fuchsia-50', 'text' => 'text-fuchsia-700', 'dot' => 'bg-fuchsia-500'],
        'violet'  => ['bg' => 'bg-violet-50', 'text' => 'text-violet-700', 'dot' => 'bg-violet-500'],
    ];
    $t = $themes[$color] ?? $themes['purple'];

    $diff = $value - $prevValue;
    $isUp = ($diff >= 0);

    echo '
    <div class="bg-white rounded-xl border border-slate-100 p-5 shadow-sm hover:shadow-md transition-all">
        <div class="flex justify-between items-center mb-3">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full '.$t['dot'].'"></span>
                <span class="text-xs font-bold text-slate-500 uppercase">'.$title.'</span>
            </div>
            <i class="fas fa-arrow-'.($isUp ? 'up text-emerald-400' : 'down text-rose-400').' text-[10px]"></i>
        </div>
        <div class="flex items-end justify-between">
            <h4 class="text-2xl font-black text-slate-800">'.number_format($value, 2).'</h4>
            <div class="w-24 h-10 -mr-2">
                <div id="'.$chartId.'"></div>
            </div>
        </div>
    </div>
    ';
}
?>

<script>
    // --- Advanced Chart Engine ---
    const renderCardChart = (id, data, colorHex, isMini = false) => {
        if(!document.querySelector("#" + id)) return;
        if(!data || !Array.isArray(data)) data = [];
        
        const isSinglePoint = data.length <= 1;
        const chartType = isSinglePoint ? 'bar' : 'area';

        var options = {
            series: [{ name: 'Value', data: data }],
            chart: {
                type: chartType,
                height: isMini ? 40 : 80,
                sparkline: { enabled: true },
                animations: { enabled: true, easing: 'easeinout', speed: 800 },
                dropShadow: { enabled: !isSinglePoint, top: 2, left: 0, blur: 4, opacity: 0.1 }
            },
            stroke: { curve: 'smooth', width: 3, lineCap: 'round' },
            plotOptions: { bar: { columnWidth: '50%', borderRadius: 4 } },
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.45,
                    opacityTo: 0.05,
                    stops: [0, 100]
                }
            },
            colors: [colorHex],
            tooltip: {
                theme: 'dark',
                fixed: { enabled: false },
                x: { show: false },
                y: { title: { formatter: () => '' } },
                marker: { show: true }
            },
            markers: { size: 0, hover: { size: 5 } }
        };
        new ApexCharts(document.querySelector("#" + id), options).render();
    };

    let mainTrendChart = null;
    const renderMainTrend = (id, currentData, colorHex, unit = '$') => {
        if(!document.querySelector("#" + id)) return;
        if(!currentData || !Array.isArray(currentData)) currentData = [];
        
        const isHourly = currentData.length === 24;
        const isSmallSet = currentData.length <= 2;
        const chartType = (isHourly || isSmallSet) ? 'bar' : 'area';

        let labels = [];
        if(isHourly) {
            labels = Array.from({length: 24}, (_, i) => {
                let h = i % 12;
                if(h === 0) h = 12;
                return h + (i < 12 ? ' AM' : ' PM');
            });
        } else {
            labels = Array.from({length: currentData.length}, (_, i) => 'Day ' + (i + 1));
        }

        var options = {
            series: [{
                name: 'Current Period',
                data: currentData
            }],
            chart: {
                type: chartType,
                height: 400,
                toolbar: { show: false },
                zoom: { enabled: false },
                fontFamily: 'Inter, sans-serif',
                dropShadow: { enabled: (chartType === 'area'), top: 10, left: 0, blur: 10, opacity: 0.1 }
            },
            plotOptions: { 
                bar: { 
                    columnWidth: isHourly ? '70%' : '30%', 
                    borderRadius: isHourly ? 4 : 8,
                    distributed: true
                } 
            },
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 4, lineCap: 'round' },
            colors: [colorHex],
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.3,
                    opacityTo: 0.0,
                    stops: [0, 90, 100]
                }
            },
            xaxis: {
                categories: labels,
                axisBorder: { show: false },
                axisTicks: { show: false },
                labels: { 
                    style: { colors: '#94a3b8', fontWeight: 600, fontSize: '10px' },
                    rotate: isHourly ? -45 : 0,
                    hideOverlappingLabels: true
                }
            },
            yaxis: {
                labels: { 
                    style: { colors: '#94a3b8', fontWeight: 600 },
                    formatter: (val) => val.toLocaleString()
                }
            },
            grid: {
                borderColor: '#f1f5f9',
                strokeDashArray: 4,
                padding: { left: 20, right: 20, bottom: 0 }
            },
            markers: {
                size: 0,
                hover: { size: 6, strokeColors: '#fff', strokeWidth: 3 }
            },
            tooltip: {
                theme: 'dark',
                x: { show: true },
                y: { formatter: (val) => unit + ' ' + (typeof val === 'number' ? val.toLocaleString(undefined, {minimumFractionDigits: 2}) : val) }
            },
            legend: { show: false }
        };
        
        if (mainTrendChart) mainTrendChart.destroy();
        mainTrendChart = new ApexCharts(document.querySelector("#" + id), options);
        mainTrendChart.render();
    };

    // Global Trend Data
    const revenueData = <?= json_encode($current_tab == 'sell' ? $invoiceTrend : $purchaseTrend); ?>;
    const ordersData = <?= json_encode($current_tab == 'sell' ? $ordersCountTrend : $pOrdersCountTrend); ?>;
    const revenueVolume = '<?= number_format(($current_tab == 'sell' ? $invoiceAmount : $purchaseAmount), 2); ?>';
    const ordersVolume = '<?= number_format(($current_tab == 'sell' ? (isset($totalOrdersCount) ? $totalOrdersCount : 0) : (isset($totalPOrdersCount) ? $totalPOrdersCount : 0)), 0); ?>';
    const revenueAvg = '<?= number_format(($current_tab == 'sell' ? $invoiceAmount : $purchaseAmount) / max(1, $days_count), 2); ?>';
    const ordersAvg = '<?= number_format((($current_tab == 'sell' ? (isset($totalOrdersCount) ? $totalOrdersCount : 0) : (isset($totalPOrdersCount) ? $totalPOrdersCount : 0)) / max(1, $days_count)), 1); ?>';

    function switchTrendData(type) {
        // Toggle Classes
        const revBtn = document.getElementById('trend-revenue-btn');
        const ordBtn = document.getElementById('trend-orders-btn');
        
        if(type === 'revenue') {
            revBtn.classList.add('bg-white', 'text-slate-800', 'shadow-sm', 'font-black');
            revBtn.classList.remove('text-slate-400', 'font-bold');
            ordBtn.classList.remove('bg-white', 'text-slate-800', 'shadow-sm', 'font-black');
            ordBtn.classList.add('text-slate-400', 'font-bold');
            
            document.getElementById('trend-volume-label').textContent = 'Total Volume';
            document.getElementById('trend-volume-value').textContent = revenueVolume;
            document.getElementById('trend-avg-label').textContent = 'Avg Daily';
            document.getElementById('trend-avg-value').textContent = revenueAvg;
            
            renderMainTrend('main-trend-chart', revenueData, '#3b82f6', '$');
        } else {
            ordBtn.classList.add('bg-white', 'text-slate-800', 'shadow-sm', 'font-black');
            ordBtn.classList.remove('text-slate-400', 'font-bold');
            revBtn.classList.remove('bg-white', 'text-slate-800', 'shadow-sm', 'font-black');
            revBtn.classList.add('text-slate-400', 'font-bold');
            
            document.getElementById('trend-volume-label').textContent = 'Total Orders';
            document.getElementById('trend-volume-value').textContent = ordersVolume;
            document.getElementById('trend-avg-label').textContent = 'Avg Orders';
            document.getElementById('trend-avg-value').textContent = ordersAvg;
            
            renderMainTrend('main-trend-chart', ordersData, '#10b981', '');
        }
    }

    <?php if($current_tab == 'sell'): ?>
    // Card Analytics
    renderCardChart('chart-invoice',    <?= json_encode($invoiceTrend); ?>, '#3b82f6');
    renderCardChart('chart-discount',   <?= json_encode($discountTrend); ?>, '#10b981');
    renderCardChart('chart-due',        <?= json_encode($dueTrend); ?>, '#f43f5e');
    renderCardChart('chart-collection', <?= json_encode($collectionTrend); ?>, '#14b8a6');
    renderCardChart('chart-shipping',   <?= json_encode($shippingTrend); ?>, '#6366f1');
    renderCardChart('chart-others',     <?= json_encode($othersTrend); ?>, '#f97316');
    renderCardChart('chart-ordertax',   <?= json_encode($orderTaxTrend); ?>, '#a855f7', true);
    renderCardChart('chart-itemtax',    <?= json_encode($itemTaxTrend); ?>, '#d946ef', true);
    renderCardChart('chart-totaltax',   <?= json_encode($totalTaxTrend); ?>, '#8b5cf6', true);
    
    // Main Trend Init
    renderMainTrend('main-trend-chart', revenueData, '#3b82f6', '$');
    
    <?php else: ?>
    // Card Analytics
    renderCardChart('chart-p-amount',     <?= json_encode($purchaseTrend); ?>, '#3b82f6');
    renderCardChart('chart-p-discount',   <?= json_encode($pDiscountTrend); ?>, '#10b981');
    renderCardChart('chart-p-due',        <?= json_encode($pDueTakenTrend); ?>, '#f43f5e');
    renderCardChart('chart-p-paid',       <?= json_encode($pPaidTrend); ?>, '#14b8a6');
    renderCardChart('chart-p-shipping',   <?= json_encode($pShippingTrend); ?>, '#6366f1');
    renderCardChart('chart-p-others',     <?= json_encode($pOthersTrend); ?>, '#f97316');
    renderCardChart('chart-p-total-paid', <?= json_encode($pPaidTrend); ?>, '#a855f7');
    renderCardChart('chart-p-return',     <?= json_encode($pReturnTrend); ?>, '#f43f5e');
    renderCardChart('chart-p-ordertax',   <?= json_encode($pOrderTaxTrend); ?>, '#a855f7', true);
    renderCardChart('chart-p-itemtax',    <?= json_encode($pItemTaxTrend); ?>, '#d946ef', true);
    renderCardChart('chart-p-totaltax',   <?= json_encode($totalTaxTrend); ?>, '#8b5cf6', true);

    // Main Trend Init
    renderMainTrend('main-trend-chart', revenueData, '#3b82f6', '$');
    <?php endif; ?>
</script>


<style>
    /* Premium Look Styles */
    #sidebar .custom-scroll::-webkit-scrollbar { width: 0px !important; background: transparent !important; }
    #sidebar .custom-scroll { scrollbar-width: none !important; -ms-overflow-style: none !important; }
    .filter-options .hidden-initially { display: none !important; }
    .animate-fade-in { animation: fadeIn 0.4s ease-out; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
</style>

<script>
// --- Dashboard UI Logic ---

function toggleFilterDropdown(id) {
    const dropdown = document.getElementById(id);
    const allDropdowns = document.querySelectorAll('[id^="filter_"], [id^="store_filter"]');
    allDropdowns.forEach(dd => { if(dd.id !== id) dd.classList.add('hidden'); });
    dropdown.classList.toggle('hidden');
}

function filterDropdownOptions(input, dropdownId) {
    const filter = input.value.toLowerCase();
    const dropdown = document.getElementById(dropdownId);
    const options = dropdown.querySelectorAll('.filter-options a');
    if(filter.length > 0) {
        options.forEach(opt => {
            const text = opt.getAttribute('data-text') || opt.textContent.toLowerCase();
            opt.classList.remove('hidden-initially'); 
            opt.style.display = text.includes(filter) ? '' : 'none';
        });
    } else {
        options.forEach((opt, index) => {
            opt.style.display = ''; 
            if(index > 5) opt.classList.add('hidden-initially');
            else opt.classList.remove('hidden-initially');
        });
    }
}

document.addEventListener('click', function(e) {
    if(!e.target.closest('[onclick*="toggleFilterDropdown"]') && !e.target.closest('[id^="filter_"]') && !e.target.closest('[id^="store_filter"]') && !e.target.closest('#custom-calendar-picker')) {
        document.querySelectorAll('[id^="filter_"], [id="store_filter"], [id="filter_date"]').forEach(dd => dd.classList.add('hidden'));
    }
});

// --- Custom Calendar Logic ---
let calendarCurrentDate = new Date();
let calendarStartDate = '<?= $start_date; ?>'; 
let calendarEndDate = '<?= $end_date; ?>';     

function showCustomCalendar() {
    document.getElementById('preset-filters-list').classList.add('hidden');
    document.getElementById('custom-calendar-picker').classList.remove('hidden');
    renderCustomCalendar();
    populateYearSelector();
}

function toggleYearMonthSelector() { document.getElementById('year-month-selector').classList.toggle('hidden'); }

function populateYearSelector() {
    const yearSelect = document.getElementById('year-select');
    const currentYear = new Date().getFullYear();
    yearSelect.innerHTML = '';
    for(let year = currentYear - 10; year <= currentYear + 10; year++) {
        const option = document.createElement('option');
        option.value = year;
        option.textContent = year;
        if(year === calendarCurrentDate.getFullYear()) option.selected = true;
        yearSelect.appendChild(option);
    }
    document.getElementById('month-select').value = calendarCurrentDate.getMonth();
}

function applyYearMonthSelection() {
    const year = parseInt(document.getElementById('year-select').value);
    const month = parseInt(document.getElementById('month-select').value);
    calendarCurrentDate = new Date(year, month, 1);
    renderCustomCalendar();
    document.getElementById('year-month-selector').classList.add('hidden');
}

function changeCalendarMonth(direction) {
    calendarCurrentDate.setMonth(calendarCurrentDate.getMonth() + direction);
    renderCustomCalendar();
}

function renderCustomCalendar() {
    const year = calendarCurrentDate.getFullYear();
    const month = calendarCurrentDate.getMonth();
    const monthNames = ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'];
    document.getElementById('calendar-month-display').textContent = `${monthNames[month]} ${year}`;
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const grid = document.getElementById('calendar-grid');
    grid.innerHTML = '';
    for(let i = 0; i < firstDay; i++) grid.appendChild(document.createElement('div'));
    for(let day = 1; day <= daysInMonth; day++) {
        const dayCellWrapper = document.createElement('div');
        dayCellWrapper.className = 'flex items-center justify-center w-full relative h-9';
        const dayBtn = document.createElement('button');
        dayBtn.type = 'button';
        dayBtn.textContent = day;
        const currentDate = new Date(year, month, day);
        const dateStr = formatCalendarDate(currentDate);
        let btnClasses = 'w-8 h-8 flex items-center justify-center text-sm z-10 relative transition-colors duration-200 rounded-full ';
        if(calendarStartDate && calendarEndDate) {
             const start = new Date(calendarStartDate);
             const end = new Date(calendarEndDate);
             if(currentDate > start && currentDate < end) {
                 dayCellWrapper.classList.add('bg-teal-50');
                 btnClasses += 'text-teal-900 font-medium';
             } else if(dateStr === formatCalendarDate(start)) {
                 dayCellWrapper.style.background = 'linear-gradient(to right, transparent 50%, #ccfbf1 50%)';
                 btnClasses += 'bg-teal-600 text-white font-bold';
             } else if(dateStr === formatCalendarDate(end)) {
                 dayCellWrapper.style.background = 'linear-gradient(to left, transparent 50%, #ccfbf1 50%)';
                 btnClasses += 'bg-teal-600 text-white font-bold';
             } else btnClasses += 'hover:bg-slate-100 text-slate-700';
        } else if (calendarStartDate && dateStr === formatCalendarDate(new Date(calendarStartDate))) btnClasses += 'bg-teal-600 text-white font-bold';
        else btnClasses += 'hover:bg-slate-100 text-slate-700';
        dayBtn.className = btnClasses;
        dayBtn.onclick = (e) => { e.stopPropagation(); selectCalendarDateRange(currentDate); };
        dayCellWrapper.appendChild(dayBtn);
        grid.appendChild(dayCellWrapper);
    }
    updateCalendarRangeDisplay();
}

function selectCalendarDateRange(date) {
    if(!calendarStartDate || (calendarStartDate && calendarEndDate)) {
        calendarStartDate = formatCalendarDate(date);
        calendarEndDate = null;
        renderCustomCalendar();
    } else {
        const start = new Date(calendarStartDate);
        if(date < start) { calendarEndDate = calendarStartDate; calendarStartDate = formatCalendarDate(date); }
        else calendarEndDate = formatCalendarDate(date);
        renderCustomCalendar();
        setTimeout(() => {
            const url = new URL(window.location.origin + '/pos/reports/overview');
            url.searchParams.set('start_date', calendarStartDate);
            url.searchParams.set('end_date', calendarEndDate);
            url.searchParams.delete('date_filter');
            window.location.href = url.toString();
        }, 300);
    }
}

function updateCalendarRangeDisplay() {
    const display = document.getElementById('calendar-selected-range');
    if(!display) return;
    if(calendarStartDate && calendarEndDate) display.textContent = `${calendarStartDate} - ${calendarEndDate}`;
    else if(calendarStartDate) display.textContent = 'Select end date...';
    else display.textContent = 'Select date range';
}

function formatCalendarDate(date) {
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
}
</script>
