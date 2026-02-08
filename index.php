<?php
session_start();
include('config/dbcon.php');

// Handle AJAX requests for Chart Data (Placed early to intercept)
if(isset($_POST['get_chart_data'])) {
    header('Content-Type: application/json');
    $get_year = intval($_POST['year']);
    
    // Store Context
    $store_id_sess = $_SESSION['store_id'] ?? $_SESSION['auth_user']['store_id'] ?? null;
    $storeFilter = $store_id_sess ? " AND store_id = '$store_id_sess'" : "";

    $incomeArr = [];
    $expenseArr = [];
    $months = [];

    // Loop through 12 months for the selected year
    for ($m = 1; $m <= 12; $m++) {
        $months[] = date('M', mktime(0, 0, 0, $m, 1));

        // Income
        $qIn = mysqli_query($conn, "SELECT SUM(grand_total) as val FROM selling_info WHERE YEAR(created_at) = '$get_year' AND MONTH(created_at) = '$m' $storeFilter");
        $incomeArr[] = floatval(mysqli_fetch_assoc($qIn)['val'] ?? 0);

        // Expense
        $qEx = mysqli_query($conn, "SELECT SUM(total_sell) as val FROM purchase_info WHERE YEAR(purchase_date) = '$get_year' AND MONTH(purchase_date) = '$m' $storeFilter");
        $expenseArr[] = floatval(mysqli_fetch_assoc($qEx)['val'] ?? 0);
    }

    // YoY Growth Calculation
    $lastYear = $get_year - 1;
    $qY1 = mysqli_query($conn, "SELECT SUM(grand_total) as t FROM selling_info WHERE YEAR(created_at) = '$get_year' $storeFilter");
    $salesThis = floatval(mysqli_fetch_assoc($qY1)['t'] ?? 0);
    
    $qY2 = mysqli_query($conn, "SELECT SUM(grand_total) as t FROM selling_info WHERE YEAR(created_at) = '$lastYear' $storeFilter");
    $salesLast = floatval(mysqli_fetch_assoc($qY2)['t'] ?? 0);

    $growth = 0;
    if ($salesLast == 0) {
        $growth = ($salesThis > 0) ? 100 : 0;
    } else {
        $growth = (($salesThis - $salesLast) / $salesLast) * 100;
    }

    echo json_encode([
        'status' => 200,
        'income' => $incomeArr,
        'expense' => $expenseArr,
        'categories' => $months,
        'growth' => round($growth, 1),
        'totalIncome' => $salesThis,
        'totalExpense' => array_sum($expenseArr)
    ]);
    exit;
}

if(!isset($_SESSION['auth'])){
    header("Location: /pos/login");
    exit(0);
}

$page_title = "Dashboard - Velocity POS";
include('includes/header.php');
?>
<style>
    :root {
        --tab-active: #0d9488;
        --tab-inactive: #94a3b8;
    }
    .tab-trigger { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); cursor: pointer; position: relative; }
    .tab-trigger.active-tab { color: var(--tab-active) !important; filter: drop-shadow(0 0 8px rgba(13, 148, 136, 0.2)); }
    .tab-trigger.inactive-tab { color: var(--tab-inactive) !important; }
    .tab-trigger:hover { color: #0f172a !important; transform: translateY(-1px); }
    
    .tab-underline { height: 3px; background: linear-gradient(to right, #0d9488, #5eead4) !important; position: absolute; bottom: 0; left: 0; transition: all 0.5s cubic-bezier(0.68, -0.55, 0.27, 1.55); border-radius: 10px; box-shadow: 0 2px 10px rgba(13, 148, 136, 0.3); }

    /* Premium Button Styles inspired by add_printer.php */
    .btn-premium {
        position: relative;
        overflow: hidden;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
        font-weight: 800;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }
    .btn-premium:hover {
        transform: translateY(-3px) scale(1.02);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }
    .btn-premium .btn-overlay {
        position: absolute;
        inset: 0;
        background: rgba(255, 255, 255, 0.2);
        transform: translateY(100%);
        transition: transform 0.5s ease;
    }
    .btn-premium:hover .btn-overlay { transform: translateY(0); }
    .btn-premium i { transition: all 0.3s ease; }
    .btn-premium:hover i { transform: scale(1.2); }

    .btn-cyan { background: linear-gradient(to right, #0891b2, #06b6d4) !important; }
    .btn-lime { background: linear-gradient(to right, #4d7c0f, #84cc16) !important; }
    .btn-orange { background: linear-gradient(to right, #ea580c, #f59e0b) !important; }
    .btn-teal { background: linear-gradient(to right, #0d9488, #059669) !important; }

    .animate-chart-rise { animation: chartRise 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) forwards; }
    @keyframes chartRise {
        0% { transform: translateY(30px); opacity: 0; filter: blur(5px); }
        100% { transform: translateY(0); opacity: 1; filter: blur(0); }
    }
</style>

<div class="app-wrapper">
    <?php include('includes/sidebar.php'); ?>

    <?php
    // --- 1. GLOBAL SETTINGS & CONTEXT ---
    $active_store_id = $_SESSION['store_id'] ?? 0;
    
    // Leverage Global Currency from header.php
    $currency = isset($global_symbol) ? $global_symbol : '৳'; 
    
    $open_time = '09:00:00';
    $close_time = '20:00:00';

    // Fetch Store Time Details if specific store selected
    if($active_store_id > 0) {
        $store_details_query = "SELECT open_time, close_time FROM stores WHERE id = '$active_store_id' LIMIT 1";
        $store_result = mysqli_query($conn, $store_details_query);
        if($store_data = mysqli_fetch_assoc($store_result)){
            $open_time = $store_data['open_time'] ?? '09:00:00';
            $close_time = $store_data['close_time'] ?? '20:00:00';
        }
    } else {
        // Fallback for "All Stores"
        $open_time = '09:00:00';
        $close_time = '20:00:00';
    }

    // --- 2. FINANCIAL DATA FETCHING (Store Filtered) ---
    
    // Store Filter Clause Helper
    $storeFilter = ($active_store_id > 0) ? "AND store_id = '$active_store_id'" : "";
    
    // Function: Monthly Trend
    function getMonthlyTrend($conn, $table, $dateCol, $sumCol, $months = 6, $storeFilter = "") {
        $data = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $monthStart = date('Y-m-01', strtotime("-$i months"));
            $monthEnd   = date('Y-m-t', strtotime("-$i months"));
            
            $colQuery = (strpos($sumCol, '(') !== false) ? $sumCol : "SUM($sumCol)";
            
            // Note: Ensure $table has 'store_id' column. selling_info and purchase_info usually do.
            $q = "SELECT $colQuery as total FROM $table WHERE DATE($dateCol) BETWEEN '$monthStart' AND '$monthEnd' $storeFilter";
            $r = mysqli_query($conn, $q);
            $d = mysqli_fetch_assoc($r);
            $data[] = floatval($d['total'] ?? 0);
        }
        return $data;
    }

    function timeAgo($timestamp) {
        if(!is_numeric($timestamp)) $timestamp = strtotime($timestamp);
        $diff = time() - $timestamp;
        if ($diff < 1) return 'now';
        if ($diff < 60) return $diff . 's ago';
        if ($diff < 3600) return floor($diff / 60) . 'm ago';
        if ($diff < 86400) return floor($diff / 3600) . 'h ago';
        if ($diff < 604800) return floor($diff / 86400) . 'd ago';
        return date('d M', $timestamp);
    }

    // Fetch Trends
    $monthsLabels = [];
    for ($i = 11; $i >= 0; $i--) {
        $monthsLabels[] = date('M', strtotime("-$i months"));
    }

    $incomeTrend   = getMonthlyTrend($conn, 'selling_info', 'created_at', 'grand_total', 12, $storeFilter);
    $expenseTrend  = getMonthlyTrend($conn, 'purchase_info', 'created_at', 'total_sell', 12, $storeFilter);
    
    // Profit Trend
    $profitTrend = [];
    for($j=0; $j<12; $j++) {
        $profitTrend[] = max(0, $incomeTrend[$j] - $expenseTrend[$j]);
    }

    // Total Balance (All Time)
    $qS = mysqli_query($conn, "SELECT SUM(grand_total) as t FROM selling_info WHERE 1 $storeFilter");
    $dS = mysqli_fetch_assoc($qS);
    $totalSalesAll = floatval($dS['t'] ?? 0);

    $qP = mysqli_query($conn, "SELECT SUM(total_sell) as t FROM purchase_info WHERE 1 $storeFilter");
    $dP = mysqli_fetch_assoc($qP);
    $totalPurchaseAll = floatval($dP['t'] ?? 0);

    $netBalance = $totalSalesAll - $totalPurchaseAll;

    // --- NEW METRICS FETCHING ---
    
    // 1. Total Orders
    $qOrders = mysqli_query($conn, "SELECT COUNT(*) as c FROM selling_info WHERE 1 $storeFilter");
    $dOrders = mysqli_fetch_assoc($qOrders);
    $totalOrders = $dOrders['c'] ?? 0;

    // 2. Total Customers (Global)
    $qCust = mysqli_query($conn, "SELECT COUNT(*) as c FROM customers WHERE status = 1");
    $dCust = mysqli_fetch_assoc($qCust);
    $totalCustomers = $dCust['c'] ?? 0;

    // 3. Products Sold
    // Join with selling_info to respect Store Filter. QUALIFY store_id to avoid ambiguity.
    $prodStoreFilter = ($active_store_id > 0) ? "AND s.store_id = '$active_store_id'" : "";
    $qProd = mysqli_query($conn, "SELECT SUM(si.qty_sold - si.return_item) as t 
                                  FROM selling_item si 
                                  JOIN selling_info s ON si.invoice_id = s.invoice_id 
                                  WHERE 1 $prodStoreFilter");
    $dProd = mysqli_fetch_assoc($qProd);
    $totalProducts = floatval($dProd['t'] ?? 0);

    // 4. Today's Sales (New for Daily Target)
    $todayDate = date('Y-m-d');
    $qToday = mysqli_query($conn, "SELECT SUM(grand_total) as t FROM selling_info WHERE DATE(created_at) = '$todayDate' $storeFilter");
    $dToday = mysqli_fetch_assoc($qToday);
    $totalSalesToday = floatval($dToday['t'] ?? 0);

    // --- TREND CALCULATION (Today vs Yesterday) ---
    $yesterdayDate = date('Y-m-d', strtotime('-1 day'));

    // A. Revenue Trend
    $qRevYest = mysqli_query($conn, "SELECT SUM(grand_total) as t FROM selling_info WHERE DATE(created_at) = '$yesterdayDate' $storeFilter");
    $revYesterday = floatval(mysqli_fetch_assoc($qRevYest)['t'] ?? 0);
    
    // B. Orders Trend
    $qOrdToday = mysqli_query($conn, "SELECT COUNT(*) as c FROM selling_info WHERE DATE(created_at) = '$todayDate' $storeFilter");
    $ordToday = floatval(mysqli_fetch_assoc($qOrdToday)['c'] ?? 0);

    $qOrdYest = mysqli_query($conn, "SELECT COUNT(*) as c FROM selling_info WHERE DATE(created_at) = '$yesterdayDate' $storeFilter");
    $ordYesterday = floatval(mysqli_fetch_assoc($qOrdYest)['c'] ?? 0);

    // C. Customers Trend (New Registrations)
    // Note: Customers are global usually, but if store_id exists we could filter. Assuming global for now per earlier query.
    $qCustToday = mysqli_query($conn, "SELECT COUNT(*) as c FROM customers WHERE DATE(created_at) = '$todayDate'");
    $custToday = floatval(mysqli_fetch_assoc($qCustToday)['c'] ?? 0);

    $qCustYest = mysqli_query($conn, "SELECT COUNT(*) as c FROM customers WHERE DATE(created_at) = '$yesterdayDate'");
    $custYesterday = floatval(mysqli_fetch_assoc($qCustYest)['c'] ?? 0);

    // D. Products Sold Trend
    // This is complex join, keep it efficient.
    $qProdToday = mysqli_query($conn, "SELECT SUM(si.qty_sold - si.return_item) as t 
                                       FROM selling_item si 
                                       JOIN selling_info s ON si.invoice_id = s.invoice_id 
                                       WHERE DATE(s.created_at) = '$todayDate' $prodStoreFilter");
    $prodToday = floatval(mysqli_fetch_assoc($qProdToday)['t'] ?? 0);

    $qProdYest = mysqli_query($conn, "SELECT SUM(si.qty_sold - si.return_item) as t 
                                      FROM selling_item si 
                                      JOIN selling_info s ON si.invoice_id = s.invoice_id 
                                      WHERE DATE(s.created_at) = '$yesterdayDate' $prodStoreFilter");
    $prodYesterday = floatval(mysqli_fetch_assoc($qProdYest)['t'] ?? 0);

    // Helper to calculate % change
    function calculateTrend($current, $previous) {
        if ($previous == 0) {
            return ($current > 0) ? 100 : 0; // 100% growth if prev was 0 and now we have something
        }
        return (($current - $previous) / $previous) * 100;
    }

    $trendRev  = calculateTrend($totalSalesToday, $revYesterday);
    $trendOrd  = calculateTrend($ordToday, $ordYesterday);
    $trendCust = calculateTrend($custToday, $custYesterday);
    $trendProd = calculateTrend($prodToday, $prodYesterday);

    // --- NEW GROWTH METRICS FOR RADIAL CHART ---
    // 1. Year-Over-Year (YoY)
    $currentYear = date('Y');
    $lastYear    = date('Y', strtotime('-1 year'));
    
    $qY1 = mysqli_query($conn, "SELECT SUM(grand_total) as t FROM selling_info WHERE YEAR(created_at) = '$currentYear' $storeFilter");
    $salesThisYear = floatval(mysqli_fetch_assoc($qY1)['t'] ?? 0);

    $qY2 = mysqli_query($conn, "SELECT SUM(grand_total) as t FROM selling_info WHERE YEAR(created_at) = '$lastYear' $storeFilter");
    $salesLastYear = floatval(mysqli_fetch_assoc($qY2)['t'] ?? 0);

    $growthYoY = calculateTrend($salesThisYear, $salesLastYear);

    // 2. Month-Over-Month (MoM)
    $currentMonth = date('Y-m');
    $lastMonth    = date('Y-m', strtotime('-1 month'));

    $qM1 = mysqli_query($conn, "SELECT SUM(grand_total) as t FROM selling_info WHERE DATE_FORMAT(created_at, '%Y-%m') = '$currentMonth' $storeFilter");
    $salesThisMonth = floatval(mysqli_fetch_assoc($qM1)['t'] ?? 0);

    $qM2 = mysqli_query($conn, "SELECT SUM(grand_total) as t FROM selling_info WHERE DATE_FORMAT(created_at, '%Y-%m') = '$lastMonth' $storeFilter");
    $salesLastMonth = floatval(mysqli_fetch_assoc($qM2)['t'] ?? 0);

    $growthMoM = calculateTrend($salesThisMonth, $salesLastMonth);

    // Helper for Styling
    function getTrendStyle($percent) {
        if ($percent > 0) {
            return [
                'icon' => 'fa-arrow-up', 
                'text' => 'text-emerald-600', 
                'bg' => 'bg-emerald-50', 
                'val' => '+' . number_format($percent, 1) . '%'
            ];
        } elseif ($percent < 0) {
            return [
                'icon' => 'fa-arrow-down', 
                'text' => 'text-rose-600', 
                'bg' => 'bg-rose-50', 
                'val' => number_format($percent, 1) . '%' // Negative sign included in number
            ];
        } else {
            return [
                'icon' => 'fa-minus', 
                'text' => 'text-slate-500', 
                'bg' => 'bg-slate-100', 
                'val' => '0.0%'
            ];
        }
    }

    $sRev  = getTrendStyle($trendRev);
    $sOrd  = getTrendStyle($trendOrd);
    $sCust = getTrendStyle($trendCust);
    $sProd = getTrendStyle($trendProd);


    // Weekly Income Comparison (Keep existing logic below)
    $thisWeekStart = date('Y-m-d', strtotime('monday this week'));
    $thisWeekEnd   = date('Y-m-d', strtotime('sunday this week'));
    $lastWeekStart = date('Y-m-d', strtotime('monday last week'));
    $lastWeekEnd   = date('Y-m-d', strtotime('sunday last week'));

    $qW1 = mysqli_query($conn, "SELECT SUM(grand_total) as t FROM selling_info WHERE DATE(created_at) BETWEEN '$thisWeekStart' AND '$thisWeekEnd' $storeFilter");
    $incThisWeek = floatval(mysqli_fetch_assoc($qW1)['t'] ?? 0);

    $qW2 = mysqli_query($conn, "SELECT SUM(grand_total) as t FROM selling_info WHERE DATE(created_at) BETWEEN '$lastWeekStart' AND '$lastWeekEnd' $storeFilter");
    $incLastWeek = floatval(mysqli_fetch_assoc($qW2)['t'] ?? 0);
    
    $weekDiff = $incThisWeek - $incLastWeek;
    $weekDiffText = ($weekDiff >= 0) 
        ? $currency . number_format($weekDiff) . " more than last week" 
        : $currency . number_format(abs($weekDiff)) . " less than last week";
    
    // JS Arrays
    $jsIncome  = json_encode($incomeTrend);
    $jsExpense = json_encode($expenseTrend);
    $jsProfit  = json_encode($profitTrend);
    $jsMonths  = json_encode($monthsLabels);

    // --- 4 SECTION GRID DATA (NEW) ---

    // 1. Weekly Daily Sales (For Sells This Week Bar Chart)
    $weekSales = [0, 0, 0, 0, 0, 0, 0];
    $thisWeekMonday = date('Y-m-d', strtotime('monday this week'));
    for($i=0; $i<7; $i++) {
        $targetDate = date('Y-m-d', strtotime("$thisWeekMonday +$i days"));
        $q = mysqli_query($conn, "SELECT SUM(grand_total) as t FROM selling_info WHERE DATE(created_at) = '$targetDate' $storeFilter");
        $row = mysqli_fetch_assoc($q);
        $weekSales[$i] = floatval($row['t'] ?? 0);
    }
    $jsWeekSales = json_encode($weekSales);

    // --- 3. RECENT ACTIVITIES DATA ---
    $recentSells = [];
    $qLimit = 5;
    $sQ = "SELECT s.*, c.name as customer_name 
           FROM selling_info s 
           LEFT JOIN customers c ON s.customer_id = c.id 
           WHERE 1 $storeFilter 
           ORDER BY s.info_id DESC LIMIT $qLimit"; // Changed from s.id to s.info_id to match original
    $sR = mysqli_query($conn, $sQ);
    while($row = mysqli_fetch_assoc($sR)) $recentSells[] = $row;

    $recentQuotations = [];
    $qQ = "SELECT q.*, 
            CASE 
                WHEN q.supplier_id != 0 THEN (SELECT name FROM suppliers WHERE id = q.supplier_id)
                WHEN q.customer_id = 1 THEN 'Walk-in Customer'
                WHEN q.customer_id = 2 THEN 'Regular Customer'
                ELSE (SELECT name FROM customers WHERE id = q.customer_id)
            END as related_party
           FROM quotations q 
           ORDER BY q.id DESC LIMIT $qLimit";
    $qR = mysqli_query($conn, $qQ);
    if($qR) while($row = mysqli_fetch_assoc($qR)) $recentQuotations[] = $row;

    $recentPurchases = [];
    $pQ = "SELECT p.*, s.name as supplier_name 
           FROM purchase_info p 
           LEFT JOIN suppliers s ON p.sup_id = s.id 
           WHERE 1 $storeFilter 
           ORDER BY p.info_id DESC LIMIT $qLimit";
    $pR = mysqli_query($conn, $pQ);
    if($pR) while($row = mysqli_fetch_assoc($pR)) $recentPurchases[] = $row;

    $recentTransfers = [];
    $tQ = "SELECT t.*, 
            (SELECT store_name FROM stores WHERE id = t.from_store_id) as from_store,
            (SELECT store_name FROM stores WHERE id = t.to_store_id) as to_store
           FROM transfers t 
           ORDER BY t.id DESC LIMIT $qLimit";
    $tR = mysqli_query($conn, $tQ);
    if($tR) while($row = mysqli_fetch_assoc($tR)) $recentTransfers[] = $row;

    $recentCustomers = [];
    $cQ = "SELECT * FROM customers ORDER BY id DESC LIMIT $qLimit";
    $cR = mysqli_query($conn, $cQ);
    if($cR) while($row = mysqli_fetch_assoc($cR)) $recentCustomers[] = $row;

    $recentSuppliers = [];
    $supQ = "SELECT * FROM suppliers ORDER BY id DESC LIMIT $qLimit";
    $supR = mysqli_query($conn, $supQ);
    if($supR) while($row = mysqli_fetch_assoc($supR)) $recentSuppliers[] = $row;

    // Financial Overview for Sales Tab (Today's summaries)
    $today = date('Y-m-d');
    $qSalesSum = mysqli_query($conn, "SELECT SUM(grand_total) as s, SUM(discount_amount) as d FROM selling_info WHERE DATE(created_at) = '$today' $storeFilter");
    $salesSum = mysqli_fetch_assoc($qSalesSum);
    $todaySalesAmt = $salesSum['s'] ?? 0;
    $todayDiscount = $salesSum['d'] ?? 0;

    $qRecSum = mysqli_query($conn, "SELECT SUM(amount) as r FROM sell_logs WHERE DATE(created_at) = '$today' AND type IN ('full_payment','partial_payment','payment','due_paid') $storeFilter");
    $todayReceived = mysqli_fetch_assoc($qRecSum)['r'] ?? 0;
    $todayDue = max(0, $todaySalesAmt - $todayReceived);

    // --- 4. TOP PRODUCTS (Unchanged) ---
    // 3. Top Products (By Quantity)
    $qTop = mysqli_query($conn, "SELECT p.product_name, p.selling_price, SUM(si.qty_sold) as total_sold, p.thumbnail 
                                 FROM selling_item si 
                                 JOIN products p ON si.item_id = p.id 
                                 JOIN selling_info s ON si.invoice_id = s.invoice_id
                                 WHERE 1 $prodStoreFilter
                                 GROUP BY si.item_id 
                                 ORDER BY total_sold DESC LIMIT 5");
    $topProducts = [];
    while($t = mysqli_fetch_assoc($qTop)) {
        $topProducts[] = $t;
    }
    ?>

    <main id="main-content" class="lg:ml-64 flex flex-col h-screen">
        <div class="navbar-fixed-top">
            <?php include('includes/navbar.php'); ?>
        </div>
        
        <div class="content-scroll-area custom-scroll">
            <div class="p-6">
                <!-- Active Store Time & Calendar Cards -->
                <!-- (Logic moved to top) -->
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-2 mb-4 transform transition-all duration-300">
                    <!-- Card 1: Entry Time -->
                    <div class="bg-white rounded-2xl p-2 shadow-sm border border-slate-100 hover:shadow-md transition-shadow relative overflow-hidden group">
                        <div class="absolute top-0 right-0 w-24 h-24 bg-blue-50 rounded-bl-full -mr-4 -mt-4 opacity-50 group-hover:scale-110 transition-transform"></div>
                        <h4 class="text-slate-500 font-bold text-sm mb-1 relative z-10">Time</h4>
                        <div class="text-center relative z-10">
                            <h3 class="text-blue-600 font-black text-2xl mb-1"><?= date('h:i:s A', strtotime($open_time)); ?></h3>
                            <p class="text-xs font-bold text-blue-400 uppercase tracking-wider">Entry Time</p>
                            <span class="inline-block mt-2 px-2 py-0.5 bg-blue-50 text-blue-600 text-[10px] font-bold rounded">Scheduled Open</span>
                        </div>
                    </div>

                    <!-- Card 2: Exit Time -->
                    <div class="bg-white rounded-2xl p-2 shadow-sm border border-slate-100 hover:shadow-md transition-shadow relative overflow-hidden group">
                        <div class="absolute top-0 right-0 w-24 h-24 bg-orange-50 rounded-bl-full -mr-4 -mt-4 opacity-50 group-hover:scale-110 transition-transform"></div>
                        <h4 class="text-slate-500 font-bold text-sm mb-1 relative z-10">Time</h4>
                        <div class="text-center relative z-10">
                            <h3 class="text-orange-500 font-black text-2xl mb-1"><?= date('h:i:s A', strtotime($close_time)); ?></h3>
                            <p class="text-xs font-bold text-orange-400 uppercase tracking-wider">Exit Time</p>
                             <span class="inline-block mt-2 px-2 py-0.5 bg-orange-50 text-orange-600 text-[10px] font-bold rounded">Scheduled Close</span>
                        </div>
                    </div>

                    <!-- Card 3: Duration -->
                    <div class="bg-white rounded-2xl p-2 shadow-sm border border-slate-100 hover:shadow-md transition-shadow relative overflow-hidden group">
                         <div class="absolute top-0 right-0 w-24 h-24 bg-purple-50 rounded-bl-full -mr-4 -mt-4 opacity-50 group-hover:scale-110 transition-transform"></div>
                        <h4 class="text-slate-500 font-bold text-sm mb-1 relative z-10">Duration</h4>
                        <div class="text-center relative z-10">
                            <h3 id="live_duration_counter" class="text-purple-600 font-black text-2xl mb-1">00:00:00</h3>
                            <p class="text-xs font-bold text-purple-400 uppercase tracking-wider">Today Spent</p>
                             <span class="inline-block mt-2 px-2 py-0.5 bg-purple-50 text-purple-600 text-[10px] font-bold rounded">Active Since Open</span>
                        </div>
                    </div>

                    <!-- Card 4: Calendar -->
                    <div class="bg-white rounded-2xl p-2 shadow-sm border border-slate-100 hover:shadow-md transition-shadow relative overflow-hidden group">
                        <div class="absolute top-0 right-0 w-24 h-24 bg-emerald-50 rounded-bl-full -mr-4 -mt-4 opacity-50 group-hover:scale-110 transition-transform"></div>
                        
                        <div class="relative z-10 flex items-center justify-between mb-1">
                             <h4 class="text-slate-500 font-bold text-sm">Calendar</h4>
                             <p id="live_clock_sm" class="text-teal-600 text-[10px] font-mono font-bold bg-teal-50 px-2 py-0.5 rounded">--:--:--</p>
                        </div>

                        <div class="text-center relative z-10">
                            <h3 id="cal_day_name" class="text-emerald-500 font-bold text-lg mb-0"><?= date('l'); ?></h3>
                            <h2 id="cal_day_num" class="text-4xl font-black text-slate-700 mb-0 leading-none"><?= date('d'); ?></h2>
                            <p id="cal_month_name" class="text-emerald-500 font-bold text-sm mb-0"><?= date('F'); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions Row -->
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-4 mb-4">
                    <!-- POS -->
                    <a href="/pos/pos/" class="bg-white p-4 rounded-xl shadow-sm border border-slate-100 flex flex-col items-center justify-center gap-2 hover:shadow-md transition-all group">
                        <i class="fas fa-shopping-cart text-blue-500 text-xl group-hover:scale-110 transition-transform"></i>
                        <span class="text-xs font-bold text-slate-600">Pos</span>
                    </a>
                    <!-- Sell List -->
                    <a href="/pos/sell/list" class="bg-white p-4 rounded-xl shadow-sm border border-slate-100 flex flex-col items-center justify-center gap-2 hover:shadow-md transition-all group">
                        <i class="fas fa-file-invoice text-slate-500 text-xl group-hover:scale-110 transition-transform"></i>
                        <span class="text-xs font-bold text-slate-600">Sell List</span>
                    </a>
                    <!-- Overview -->
                    <a href="/pos/reports/overview" class="bg-white p-4 rounded-xl shadow-sm border border-slate-100 flex flex-col items-center justify-center gap-2 hover:shadow-md transition-all group">
                        <i class="fas fa-flag text-cyan-500 text-xl group-hover:scale-110 transition-transform"></i>
                        <span class="text-xs font-bold text-slate-600">Overview</span>
                    </a>
                    <!-- Sell Rep -->
                    <a href="/pos/reports/sell" class="bg-white p-4 rounded-xl shadow-sm border border-slate-100 flex flex-col items-center justify-center gap-2 hover:shadow-md transition-all group">
                        <i class="fas fa-money-bill-wave text-emerald-500 text-xl group-hover:scale-110 transition-transform"></i>
                        <span class="text-xs font-bold text-slate-600">Sell Rep</span>
                    </a>
                    <!-- Purchase -->
                    <a href="/pos/purchases/list" class="bg-white p-4 rounded-xl shadow-sm border border-slate-100 flex flex-col items-center justify-center gap-2 hover:shadow-md transition-all group">
                        <i class="fas fa-file-invoice-dollar text-orange-500 text-xl group-hover:scale-110 transition-transform"></i>
                        <span class="text-xs font-bold text-slate-600">Purchase</span>
                    </a>
                    <!-- Stock -->
                    <a href="/pos/reports/stock" class="bg-white p-4 rounded-xl shadow-sm border border-slate-100 flex flex-col items-center justify-center gap-2 hover:shadow-md transition-all group">
                        <i class="fas fa-square text-red-500 text-xl group-hover:scale-110 transition-transform"></i>
                        <span class="text-xs font-bold text-slate-600">Stock</span>
                    </a>
                    <!-- Expired (Mapped to Stock Alert) -->
                    <a href="/pos/products/stock_alert" class="bg-white p-4 rounded-xl shadow-sm border border-slate-100 flex flex-col items-center justify-center gap-2 hover:shadow-md transition-all group">
                        <i class="fas fa-bell text-amber-500 text-xl group-hover:scale-110 transition-transform"></i>
                        <span class="text-xs font-bold text-slate-600">Expired</span>
                    </a>
                    <!-- Backup -->
                    <!-- <a href="/pos/system/backup" class="bg-white p-4 rounded-xl shadow-sm border border-slate-100 flex flex-col items-center justify-center gap-2 hover:shadow-md transition-all group">
                        <i class="fas fa-database text-purple-500 text-xl group-hover:scale-110 transition-transform"></i>
                        <span class="text-xs font-bold text-slate-600">Backup</span>
                    </a> -->
                    <!-- Stores -->
                    <a href="/pos/stores/list" class="bg-white p-4 rounded-xl shadow-sm border border-slate-100 flex flex-col items-center justify-center gap-2 hover:shadow-md transition-all group">
                        <i class="fas fa-store text-lime-500 text-xl group-hover:scale-110 transition-transform"></i>
                        <span class="text-xs font-bold text-slate-600">Stores</span>
                    </a>
                </div>

                  <!-- Congratulations Card & Stats Row -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8 slide-in" style="animation-delay: 0.1s;">
                    <!-- Left Column: Congratulations & Stats (Flex Container for Equal Height) -->
                    <div class="lg:col-span-2 flex flex-col gap-6">
                        
                            <!-- 1. Congratulations Card (Image 1 Style) -->
                        <div class="relative overflow-hidden rounded-2xl shadow-sm border border-slate-100 p-8 bg-white flex items-center justify-between">
                            <div class="relative z-10 max-w-lg">
                                <?php
                                    // Calculate Daily Target Percentage & Context
                                    // 1. Fetch Target & Store Info
                                    $targetQuery = mysqli_query($conn, "SELECT daily_target, store_name, city_zip FROM stores WHERE id='$active_store_id' LIMIT 1");
                                    $targetRow = mysqli_fetch_assoc($targetQuery);
                                    
                                    $dailyTarget = floatval($targetRow['daily_target'] ?? 0);
                                    $storeName   = $targetRow['store_name'] ?? 'Generic Store';
                                    $storeCity   = $targetRow['city_zip'] ?? '';

                                    // 2. Calculate %
                                    $targetPct = 0;
                                    if($dailyTarget > 0) {
                                        $targetPct = ($totalSalesToday / $dailyTarget) * 100;
                                    }
                                    
                                    // 3. Current Sales vs Target Text
                                    $salesTodayFormatted = number_format($totalSalesToday, 2);
                                    $targetFormatted = number_format($dailyTarget, 2);
                                ?>
                                <h3 class="text-2xl font-bold text-slate-800 mb-2">
                                    Congratulations <span class="text-indigo-600"><?= isset($_SESSION['auth_user']['name']) ? htmlspecialchars(explode(' ', $_SESSION['auth_user']['name'])[0]) : 'User'; ?></span>! 🎉
                                </h3>
                                
                                <p class="text-teal-600 text-sm leading-relaxed mb-6">
                                    You have done <span class="font-bold text-teal-600"><?= number_format($targetPct, 1); ?>%</span> of your daily target for <span class="font-bold text-slate-700"><?= htmlspecialchars($storeName); ?></span>. <br>
                                    Daily Target: <?= $currency . $targetFormatted; ?> | Achieved: <?= $currency . $salesTodayFormatted; ?>
                                    <?php if(!empty($storeCity)): ?>
                                        <br><span class="text-xs text-slate-400 mt-1 inline-block"><i class="fas fa-map-marker-alt mr-1"></i> <?= htmlspecialchars($storeCity); ?></span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <!-- User Image (Dynamic) -->
                            <div class="hidden md:block relative">
                                <?php
                                    $userName = isset($_SESSION['auth_user']['name']) ? $_SESSION['auth_user']['name'] : 'User';
                                    $userImg = isset($_SESSION['auth_user']['image']) ? $_SESSION['auth_user']['image'] : '';
                                    
                                    // Use image directly if available, otherwise use avatar
                                    $displayImg = !empty($userImg) ? $userImg : "https://ui-avatars.com/api/?name=" . urlencode($userName) . "&background=6366f1&color=fff&size=128";
                                ?>
                                <img 
                                    src="<?= $displayImg; ?>" 
                                    alt="User Profile" 
                                    class="w-32 h-32 rounded-2xl object-cover shadow-md border-4 border-slate-50 hover:scale-105 transition-transform duration-500"
                                    onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($userName); ?>&background=6366f1&color=fff&size=128';"
                                >
                                <!-- Decoration -->
                                <div class="absolute -right-2 -bottom-2 w-10 h-10 bg-indigo-500 rounded-full border-4 border-white flex items-center justify-center shadow-sm">
                                    <i class="fas fa-crown text-white text-xs"></i>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Stats Grid (Image 2 Style) -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <!-- Revenue -->
                            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 flex flex-col items-start hover:shadow-md transition-all">
                                <div class="flex justify-between items-start w-full mb-3">
                                    <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                                        <i class="fas fa-dollar-sign text-lg"></i>
                                    </div>
                                    <div class="flex items-center gap-1 text-[10px] font-bold <?= $sRev['text']; ?> <?= $sRev['bg']; ?> px-2 py-0.5 rounded-full">
                                        <i class="fas <?= $sRev['icon']; ?> text-[9px]"></i> <?= $sRev['val']; ?>
                                    </div>
                                </div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Total Revenue</p>
                                <h3 class="text-2xl font-black text-slate-800 mb-2"><?= $currency; ?><?= number_format($totalSalesAll, 2); ?></h3>
                            </div>

                            <!-- Orders -->
                            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 flex flex-col items-start hover:shadow-md transition-all">
                                <div class="flex justify-between items-start w-full mb-3">
                                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                                        <i class="fas fa-shopping-cart text-lg"></i>
                                    </div>
                                    <div class="flex items-center gap-1 text-[10px] font-bold <?= $sOrd['text']; ?> <?= $sOrd['bg']; ?> px-2 py-0.5 rounded-full">
                                        <i class="fas <?= $sOrd['icon']; ?> text-[9px]"></i> <?= $sOrd['val']; ?>
                                    </div>
                                </div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Total Orders</p>
                                <h3 class="text-2xl font-black text-slate-800 mb-2"><?= number_format($totalOrders); ?></h3>
                            </div>

                            <!-- Customers -->
                            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 flex flex-col items-start hover:shadow-md transition-all">
                                <div class="flex justify-between items-start w-full mb-3">
                                    <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                                        <i class="fas fa-users text-lg"></i>
                                    </div>
                                    <div class="flex items-center gap-1 text-[10px] font-bold <?= $sCust['text']; ?> <?= $sCust['bg']; ?> px-2 py-0.5 rounded-full">
                                        <i class="fas <?= $sCust['icon']; ?> text-[9px]"></i> <?= $sCust['val']; ?>
                                    </div>
                                </div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Total Customers</p>
                                <h3 class="text-2xl font-black text-slate-800 mb-2"><?= number_format($totalCustomers); ?></h3>
                            </div>

                            <!-- Products -->
                            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 flex flex-col items-start hover:shadow-md transition-all">
                                <div class="flex justify-between items-start w-full mb-3">
                                    <div class="w-10 h-10 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center">
                                        <i class="fas fa-box-open text-lg"></i>
                                    </div>
                                    <div class="flex items-center gap-1 text-[10px] font-bold <?= $sProd['text']; ?> <?= $sProd['bg']; ?> px-2 py-0.5 rounded-full">
                                        <i class="fas <?= $sProd['icon']; ?> text-[9px]"></i> <?= $sProd['val']; ?>
                                    </div>
                                </div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Products Sold</p>
                                <h3 class="text-2xl font-black text-slate-800 mb-2"><?= number_format($totalProducts); ?></h3>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Financial Report Card (Height Match) -->
                    <div class="bg-white rounded-2xl shadow-sm border border-teal-100 flex flex-col justify-between relative overflow-hidden h-full">
                        <!-- Header / Tabs -->
                        <div class="p-6 pb-0">
                            <div class="flex items-center justify-between mb-6">
                                <div class="flex bg-teal-100 p-1 rounded-lg">
                                    <button onclick="updateChart('income', this)" class="chart-tab active px-4 py-1.5 text-xs font-bold rounded-md bg-teal-500 text-white shadow-sm transition-all">Income</button>
                                    <button onclick="updateChart('expenses', this)" class="chart-tab px-4 py-1.5 text-xs font-bold rounded-md text-teal-500 hover:text-teal-700 transition-all">Expenses</button>
                                    <button onclick="updateChart('profit', this)" class="chart-tab px-4 py-1.5 text-xs font-bold rounded-md text-teal-500 hover:text-teal-700 transition-all">Profit</button>
                                </div>
                            </div>

                            <!-- Main Stats -->
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-10 h-10 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-500">
                                    <i class="fas fa-wallet"></i>
                                </div>
                                <div>
                                    <p id="balanceLabel" class="text-xs text-slate-400 font-medium">Total Income</p>
                                    <div class="flex items-center gap-2">
                                        <h3 id="balanceAmount" class="text-xl font-black text-slate-800"><?= $currency; ?> <?= number_format($totalSalesAll, 2); ?></h3>
                                        <span class="text-[10px] font-bold text-slate-400 bg-slate-50 px-1.5 py-0.5 rounded flex items-center gap-1">All Time</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Chart Container -->
                        <div id="financialChart" class="w-full h-28 mb-2 flex-grow"></div>

                        <!-- Bottom Stats (Weekly Income) -->
                        <div class="p-6 pt-0">
                            <div class="flex items-center justify-between border-t border-slate-50 pt-4">
                                <div class="flex items-center gap-3">
                                    <div class="relative w-10 h-10">
                                        <!-- Simple SVG Radial Progress Mockup -->
                                        <svg class="w-full h-full transform -rotate-90">
                                            <circle cx="20" cy="20" r="16" stroke="currentColor" stroke-width="3" fill="transparent" class="text-slate-100" />
                                            <circle cx="20" cy="20" r="16" stroke="currentColor" stroke-width="3" fill="transparent" stroke-dasharray="100" stroke-dashoffset="35" class="text-indigo-500" />
                                        </svg>
                                        <span class="absolute inset-0 flex items-center justify-center text-[8px] font-bold text-slate-700">W</span>
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold text-slate-700">Income this week: <?= $currency; ?> <?= number_format($incThisWeek, 0); ?></p>
                                        <span class="text-[10px] text-slate-400 bg-slate-100 px-1.5 rounded"><?= $weekDiffText; ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 mb-6 overflow-hidden recent-activities-card flex flex-col">
                    <div class="p-6 pb-0 border-b border-slate-50">
                        <h3 class="text-base font-bold text-slate-700 mb-6">Recent Activities</h3>
                        <div class="flex items-center justify-between relative" id="activityTabs">
                            <div class="tab-trigger flex-1 text-center pb-4 text-xs font-bold active-tab group" onclick="switchActTab('sales', this)">
                                <span>Sales</span>
                                <div class="absolute bottom-0 left-0 w-full h-0.5 bg-teal-500/10 transition-all group-hover:bg-teal-500/20"></div>
                            </div>
                            <div class="tab-trigger flex-1 text-center pb-4 text-xs font-medium inactive-tab group" onclick="switchActTab('quotations', this)">
                                <span>Quotations</span>
                                <div class="absolute bottom-0 left-0 w-full h-0.5 bg-teal-500/10 transition-all group-hover:bg-teal-500/20"></div>
                            </div>
                            <div class="tab-trigger flex-1 text-center pb-4 text-xs font-medium inactive-tab group" onclick="switchActTab('purchases', this)">
                                <span>Purchases</span>
                                <div class="absolute bottom-0 left-0 w-full h-0.5 bg-teal-500/10 transition-all group-hover:bg-teal-500/20"></div>
                            </div>
                            <div class="tab-trigger flex-1 text-center pb-4 text-xs font-medium inactive-tab group" onclick="switchActTab('transfers', this)">
                                <span>Transfers</span>
                                <div class="absolute bottom-0 left-0 w-full h-0.5 bg-teal-500/10 transition-all group-hover:bg-teal-500/20"></div>
                            </div>
                            <div class="tab-trigger flex-1 text-center pb-4 text-xs font-medium inactive-tab group" onclick="switchActTab('customers', this)">
                                <span>Customers</span>
                                <div class="absolute bottom-0 left-0 w-full h-0.5 bg-teal-500/10 transition-all group-hover:bg-teal-500/20"></div>
                            </div>
                            <div class="tab-trigger flex-1 text-center pb-4 text-xs font-medium inactive-tab group" onclick="switchActTab('suppliers', this)">
                                <span>Suppliers</span>
                                <div class="absolute bottom-0 left-0 w-full h-0.5 bg-teal-500/10 transition-all group-hover:bg-teal-500/20"></div>
                            </div>
                            <div class="tab-underline" id="tabLine"></div>
                        </div>
                    </div>
                    
                    <div class="flex-grow p-6 relative">
                        <!-- SALES TAB -->
                        <div id="act-sales" class="act-content grid grid-cols-1 lg:grid-cols-12 gap-6">
                            <div class="lg:col-span-8">
                                <div class="overflow-x-auto rounded-xl border border-slate-50">
                                    <table class="w-full text-left">
                                        <thead>
                                            <tr class="bg-slate-50/50 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                                <th class="py-3 px-4">Invoice ID</th>
                                                <th class="py-3 px-4">Created At</th>
                                                <th class="py-3 px-4">Customer</th>
                                                <th class="py-3 px-4">Amount</th>
                                                <th class="py-3 px-4">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-50 text-[11px]">
                                            <?php foreach($recentSells as $s): ?>
                                            <tr class="hover:bg-slate-50/50 transition-colors">
                                                <td class="py-3 px-4 font-bold text-slate-700">#<?= $s['invoice_id']; ?></td>
                                                <td class="py-3 px-4 text-slate-500"><?= timeAgo($s['created_at']); ?></td>
                                                <td class="py-3 px-4 font-medium text-slate-600"><?= $s['customer_name'] ?: 'Walk-in'; ?></td>
                                                <td class="py-3 px-4 font-black text-slate-800"><?= $currency . number_format($s['grand_total'], 2); ?></td>
                                                <td class="py-3 px-4">
                                                    <span class="px-2 py-0.5 rounded-full font-bold text-[9px] <?= $s['status']=='paid'?'bg-emerald-50 text-emerald-600':'bg-amber-50 text-amber-600'; ?>"><?= ucfirst($s['status']); ?></span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-8 flex items-center gap-4">
                                    <a href="pos" class="px-8 py-3 btn-premium btn-teal text-white text-[11px] font-black rounded-2xl group relative overflow-hidden">
                                        <div class="btn-overlay"></div>
                                        <i class="fas fa-plus relative z-10 transition-transform group-hover:rotate-90"></i> <span class="relative z-10">Add Sale</span>
                                    </a>
                                    <a href="sell/list" class="px-8 py-3 btn-premium btn-lime text-white text-[11px] font-black rounded-2xl group relative overflow-hidden">
                                        <div class="btn-overlay"></div>
                                        <i class="fas fa-list relative z-10"></i> <span class="relative z-10">List Sales</span>
                                    </a>
                                </div>
                            </div>
                            <div class="lg:col-span-4 bg-slate-50/50 rounded-2xl p-6 border border-slate-100">
                                <div class="space-y-4">
                                    <div class="flex justify-between items-center py-2 border-b border-slate-200/50">
                                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Sales Amount</span>
                                        <span class="text-sm font-black text-slate-800"><?= number_format($todaySalesAmt, 2); ?></span>
                                    </div>
                                    <div class="flex justify-between items-center py-2 border-b border-slate-200/50">
                                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Discount Given</span>
                                        <span class="text-sm font-black text-slate-800"><?= number_format($todayDiscount, 2); ?></span>
                                    </div>
                                    <div class="flex justify-between items-center py-2 border-b border-slate-200/50">
                                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Due Given</span>
                                        <span class="text-sm font-black text-rose-600"><?= number_format($todayDue, 2); ?></span>
                                    </div>
                                    <div class="flex justify-between items-center py-2 border-b border-slate-200/50">
                                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Received Amount</span>
                                        <span class="text-sm font-black text-emerald-600"><?= number_format($todayReceived, 2); ?></span>
                                    </div>
                                    <a href="/pos/reports/sell" class="w-full py-4 mt-6 btn-premium btn-teal text-white text-[10px] font-black uppercase tracking-widest rounded-2xl group relative overflow-hidden">
                                        <div class="btn-overlay"></div>
                                        <span class="relative z-10">Overview Report</span>
                                        <i class="fas fa-arrow-right relative z-10 group-hover:translate-x-1 transition-transform"></i>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- QUOTATIONS TAB -->
                        <div id="act-quotations" class="act-content hidden">
                            <div class="overflow-x-auto rounded-xl border border-slate-50">
                                <table class="w-full text-left">
                                    <thead>
                                        <tr class="bg-slate-50/50 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                            <th class="py-3 px-4">Ref No</th>
                                            <th class="py-3 px-4">Date</th>
                                            <th class="py-3 px-4">Customer</th>
                                            <th class="py-3 px-4">Total</th>
                                            <th class="py-3 px-4">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-50 text-[11px]">
                                        <?php if(empty($recentQuotations)): ?>
                                        <tr><td colspan="5" class="py-10 text-center text-slate-400">No Quotations Found</td></tr>
                                        <?php else: foreach($recentQuotations as $q): ?>
                                        <tr class="hover:bg-slate-50/50 transition-colors">
                                            <td class="py-3 px-4 font-bold text-slate-700">#<?= $q['ref_no']; ?></td>
                                            <td class="py-3 px-4 text-slate-500"><?= date('d M, Y', strtotime($q['date'])); ?></td>
                                            <td class="py-3 px-4 font-medium text-slate-600"><?= $q['related_party'] ?: 'N/A'; ?></td>
                                            <td class="py-3 px-4 font-black text-slate-800"><?= $currency . number_format($q['grand_total'], 2); ?></td>
                                            <td class="py-3 px-4">
                                                <span class="px-2 py-0.5 rounded-full font-bold text-[9px] <?= ($q['status']=='sent'||$q['status']=='1')?'bg-indigo-50 text-indigo-600':'bg-slate-50 text-slate-500'; ?>"><?= ($q['status']=='sent'||$q['status']=='1')?'Sent':'Pending'; ?></span>
                                            </td>
                                        </tr>
                                        <?php endforeach; endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-8 flex items-center gap-4">
                                <a href="quotations/add_quotation.php" class="px-8 py-3 btn-premium btn-teal text-white text-[11px] font-black rounded-2xl group relative overflow-hidden">
                                    <div class="btn-overlay"></div>
                                    <i class="fas fa-plus relative z-10"></i> <span class="relative z-10">Add Quotation</span>
                                </a>
                                <a href="quotations/quotation_list.php" class="px-8 py-3 btn-premium btn-lime text-white text-[11px] font-black rounded-2xl group relative overflow-hidden">
                                    <div class="btn-overlay"></div>
                                    <i class="fas fa-list relative z-10"></i> <span class="relative z-10">List Quotations</span>
                                </a>
                            </div>
                        </div>

                        <!-- PURCHASES TAB -->
                        <div id="act-purchases" class="act-content hidden">
                            <div class="overflow-x-auto rounded-xl border border-slate-50">
                                <table class="w-full text-left">
                                    <thead>
                                        <tr class="bg-slate-50/50 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                            <th class="py-3 px-4">Ref No</th>
                                            <th class="py-3 px-4">Date</th>
                                            <th class="py-3 px-4">Supplier</th>
                                            <th class="py-3 px-4">Total</th>
                                            <th class="py-3 px-4">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-50 text-[11px]">
                                        <?php if(empty($recentPurchases)): ?>
                                        <tr><td colspan="5" class="py-10 text-center text-slate-400">No Purchases Found</td></tr>
                                        <?php else: foreach($recentPurchases as $p): ?>
                                        <tr class="hover:bg-slate-50/50 transition-colors">
                                            <td class="py-3 px-4 font-bold text-slate-700">#<?= $p['invoice_id']; ?></td>
                                            <td class="py-3 px-4 text-slate-500"><?= date('d M, Y', strtotime($p['purchase_date'])); ?></td>
                                            <td class="py-3 px-4 font-medium text-slate-600"><?= $p['supplier_name'] ?: 'N/A'; ?></td>
                                            <td class="py-3 px-4 font-black text-slate-800"><?= $currency . number_format($p['total_sell'], 2); ?></td>
                                            <td class="py-3 px-4">
                                                <span class="px-2 py-0.5 rounded-full font-bold text-[9px] bg-emerald-50 text-emerald-600">Completed</span>
                                            </td>
                                        </tr>
                                        <?php endforeach; endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-8 flex items-center gap-4">
                                <a href="purchases/add_purchase.php" class="px-8 py-3 btn-premium btn-teal text-white text-[11px] font-black rounded-2xl group relative overflow-hidden">
                                    <div class="btn-overlay"></div>
                                    <i class="fas fa-plus relative z-10"></i> <span class="relative z-10">Add Purchase</span>
                                </a>
                                <a href="purchases/purchase_list.php" class="px-8 py-3 btn-premium btn-lime text-white text-[11px] font-black rounded-2xl group relative overflow-hidden">
                                    <div class="btn-overlay"></div>
                                    <i class="fas fa-list relative z-10"></i> <span class="relative z-10">List Purchases</span>
                                </a>
                            </div>
                        </div>

                        <!-- TRANSFERS TAB -->
                        <div id="act-transfers" class="act-content hidden">
                            <div class="overflow-x-auto rounded-xl border border-slate-50">
                                <table class="w-full text-left">
                                    <thead>
                                        <tr class="bg-slate-50/50 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                            <th class="py-3 px-4">Date</th>
                                            <th class="py-3 px-4">Ref No</th>
                                            <th class="py-3 px-4">From</th>
                                            <th class="py-3 px-4">To</th>
                                            <th class="py-3 px-4">Items</th>
                                            <th class="py-3 px-4">Total Qty</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-50 text-[11px]">
                                        <?php if(empty($recentTransfers)): ?>
                                        <tr><td colspan="6" class="py-10 text-center text-slate-400">No Transfers Found</td></tr>
                                        <?php else: foreach($recentTransfers as $t): ?>
                                        <tr class="hover:bg-slate-50/50 transition-colors">
                                            <td class="py-3 px-4 text-slate-500"><?= date('d M, Y', strtotime($t['created_at'])); ?></td>
                                            <td class="py-3 px-4 font-bold text-slate-700">#<?= $t['ref_no']; ?></td>
                                            <td class="py-3 px-4 font-medium"><?= $t['from_store']; ?></td>
                                            <td class="py-3 px-4 font-medium"><?= $t['to_store'] ?: 'N/A'; ?></td>
                                            <td class="py-3 px-4"><?= $t['total_item']; ?></td>
                                            <td class="py-3 px-4 font-bold"><?= $t['total_quantity']; ?></td>
                                        </tr>
                                        <?php endforeach; endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-8 flex items-center gap-4">
                                <a href="transfer/stock_transfer.php" class="px-8 py-3 btn-premium btn-teal text-white text-[11px] font-black rounded-2xl group relative overflow-hidden">
                                    <div class="btn-overlay"></div>
                                    <i class="fas fa-plus relative z-10"></i> <span class="relative z-10">Add Transfer</span>
                                </a>
                                <a href="transfer/transfer_list.php" class="px-8 py-3 btn-premium btn-lime text-white text-[11px] font-black rounded-2xl group relative overflow-hidden">
                                    <div class="btn-overlay"></div>
                                    <i class="fas fa-list relative z-10"></i> <span class="relative z-10">List Transfers</span>
                                </a>
                            </div>
                        </div>

                        <!-- CUSTOMERS TAB -->
                        <div id="act-customers" class="act-content hidden">
                            <div class="overflow-x-auto rounded-xl border border-slate-50">
                                <table class="w-full text-left">
                                    <thead>
                                        <tr class="bg-slate-50/50 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                            <th class="py-3 px-4">Name</th>
                                            <th class="py-3 px-4">Phone</th>
                                            <th class="py-3 px-4">Email</th>
                                            <th class="py-3 px-4">City</th>
                                            <th class="py-3 px-4">Created</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-50 text-[11px]">
                                        <?php foreach($recentCustomers as $c): ?>
                                        <tr class="hover:bg-slate-50/50 transition-colors">
                                            <td class="py-3 px-4 font-bold text-slate-700"><?= $c['name']; ?></td>
                                            <td class="py-3 px-4 text-slate-500"><?= $c['mobile']; ?></td>
                                            <td class="py-3 px-4 text-slate-500"><?= $c['email']; ?></td>
                                            <td class="py-3 px-4"><?= $c['city']; ?></td>
                                            <td class="py-3 px-4 opacity-50"><?= date('d M, Y', strtotime($c['created_at'])); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-8 flex items-center gap-4">
                                <a href="customers/add_customer.php" class="px-8 py-3 btn-premium btn-teal text-white text-[11px] font-black rounded-2xl group relative overflow-hidden">
                                    <div class="btn-overlay"></div>
                                    <i class="fas fa-plus relative z-10"></i> <span class="relative z-10">Add Customer</span>
                                </a>
                                <a href="customers/customer_list.php" class="px-8 py-3 btn-premium btn-lime text-white text-[11px] font-black rounded-2xl group relative overflow-hidden">
                                    <div class="btn-overlay"></div>
                                    <i class="fas fa-list relative z-10"></i> <span class="relative z-10">List Customers</span>
                                </a>
                            </div>
                        </div>

                        <!-- SUPPLIERS TAB -->
                        <div id="act-suppliers" class="act-content hidden">
                            <div class="overflow-x-auto rounded-xl border border-slate-50">
                                <table class="w-full text-left">
                                    <thead>
                                        <tr class="bg-slate-50/50 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                            <th class="py-3 px-4">Supplier Name</th>
                                            <th class="py-3 px-4">Phone</th>
                                            <th class="py-3 px-4">Email</th>
                                            <th class="py-3 px-4">Address</th>
                                            <th class="py-3 px-4">Created</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-50 text-[11px]">
                                        <?php if(empty($recentSuppliers)): ?>
                                        <tr><td colspan="5" class="py-10 text-center text-slate-400">No Supplier Found</td></tr>
                                        <?php else: foreach($recentSuppliers as $sup): ?>
                                        <tr class="hover:bg-slate-50/50 transition-colors">
                                            <td class="py-3 px-4 font-bold text-slate-700 text-indigo-500"><?= $sup['name']; ?></td>
                                            <td class="py-3 px-4 text-slate-500"><?= $sup['mobile']; ?></td>
                                            <td class="py-3 px-4 text-slate-500"><?= $sup['email']; ?></td>
                                            <td class="py-3 px-4 text-slate-500 text-[10px]"><?= $sup['city'] ?: 'USA'; ?></td>
                                            <td class="py-3 px-4 text-slate-400"><?= date('Y-m-d H:i:s', strtotime($sup['created_at'])); ?></td>
                                        </tr>
                                        <?php endforeach; endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-8 flex items-center gap-4">
                                <a href="suppliers/add_supplier.php" class="px-8 py-3 btn-premium btn-teal text-white text-[11px] font-black rounded-2xl group relative overflow-hidden">
                                    <div class="btn-overlay"></div>
                                    <i class="fas fa-plus relative z-10"></i> <span class="relative z-10">Add Supplier</span>
                                </a>
                                <a href="suppliers/supplier_list.php" class="px-8 py-3 btn-premium btn-lime text-white text-[11px] font-black rounded-2xl group relative overflow-hidden">
                                    <div class="btn-overlay"></div>
                                    <i class="fas fa-list relative z-10"></i> <span class="relative z-10">List Suppliers</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dashboard Extended Grid (Charts) -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                    <!-- 1. Sales Overview (Split Layout: Chart + Growth) -->
                    <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-100 flex flex-col md:flex-row overflow-hidden relative group/chart">
                        
                        <!-- Left Stats & Chart (65%) -->
                        <div class="flex-grow md:w-2/3 p-6 pr-2 border-r border-slate-50 relative z-10">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-base font-bold text-slate-800">Sales Overview</h3>
                                <div class="flex items-center gap-4 text-[10px] font-bold">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full bg-indigo-500"></span><span class="text-slate-500">Income</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full bg-cyan-400"></span><span class="text-slate-500">Expense</span>
                                    </div>
                                </div>
                            </div>
                            <div id="overallSalesChart" class="w-full h-64"></div>
                        </div>

                        <!-- Right Growth Circular (35%) -->
                        <div class="md:w-1/3 p-6 flex flex-col items-center justify-center bg-slate-50/30 relative z-10">
                            <!-- Year Selector -->
                            <div class="w-full flex justify-end mb-2 relative">
                                <div class="relative inline-block text-left" id="yearDropdownContainer">
                                    <button type="button" onclick="toggleYearDropdown()" class="inline-flex items-center justify-between w-24 px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-600 rounded-lg text-xs font-bold transition-all border border-indigo-100/50 shadow-sm" id="yearSelectorBtn">
                                        <span id="selectedYearDisplay"><?= date('Y'); ?></span>
                                        <i class="fas fa-chevron-down text-[8px] ml-2 transition-transform duration-300" id="yearChevron"></i>
                                    </button>
                                    <!-- Dropdown Menu -->
                                    <div id="yearDropdownMenu" class="hidden absolute right-0 mt-2 w-24 origin-top-right bg-white border border-slate-100 rounded-xl shadow-xl z-[100] overflow-hidden">
                                        <div class="py-1 max-h-48 overflow-y-auto">
                                            <?php 
                                            $currYear = date('Y');
                                            for($y = $currYear; $y >= $currYear - 4; $y--): ?>
                                            <a href="javascript:void(0)" onclick="handleYearSelection('<?= $y; ?>')" class="block px-4 py-2 text-[11px] font-bold text-slate-600 hover:bg-indigo-50 hover:text-indigo-600 transition-colors">
                                                <?= $y; ?>
                                            </a>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Radial Chart -->
                            <div id="growthRadialChart" class="relative"></div>
                            <p class="text-sm font-bold text-slate-700 mt-2 text-center">Company Growth</p>
                            
                            <!-- Mini Totals -->
                            <div class="w-full grid grid-cols-2 gap-3 mt-6">
                                <div class="bg-indigo-50 rounded-lg p-3 flex flex-col items-center text-center">
                                    <div class="w-8 h-8 rounded-full bg-white text-indigo-500 flex items-center justify-center mb-1 shadow-sm"><i class="fas fa-dollar-sign text-xs"></i></div>
                                    <span class="text-[10px] uppercase font-bold text-slate-400">Income</span>
                                    <span id="yearIncomeTotal" class="text-xs font-black text-slate-700"><?= number_format($totalSalesAll/1000, 1); ?>k</span>
                                </div>
                                <div class="bg-cyan-50 rounded-lg p-3 flex flex-col items-center text-center">
                                    <div class="w-8 h-8 rounded-full bg-white text-cyan-500 flex items-center justify-center mb-1 shadow-sm"><i class="fas fa-wallet text-xs"></i></div>
                                    <span class="text-[10px] uppercase font-bold text-slate-400">Expense</span>
                                    <span id="yearExpenseTotal" class="text-xs font-black text-slate-700"><?= number_format($totalPurchaseAll/1000, 1); ?>k</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Sells This Week (Bar Chart) -->
                    <div class="lg:col-span-1 bg-white rounded-2xl shadow-sm border border-slate-100 flex flex-col overflow-hidden">
                        <div class="p-6 pb-2 flex items-center justify-between">
                            <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Sells This Week</h3>
                            <span class="text-[10px] font-bold text-indigo-600 bg-indigo-50 px-2 py-1 rounded">Daily Progress</span>
                        </div>
                        <div id="weeklySellsChart" class="w-full h-64"></div>
                    </div>
                </div>

                <!-- Dashboard Lower Grid (Recent Sell & Top Products) -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                    <!-- 3. Recent Sell (Table) -->
                    <div class="lg:col-span-2 bg-white rounded-2xl p-6 shadow-sm border border-slate-100 flex flex-col">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Recent Sell</h3>
                            <a href="sell/list" class="text-[10px] font-bold text-indigo-600 hover:text-indigo-800 transition-colors bg-indigo-50 px-3 py-1 rounded-full">View All <i class="fas fa-arrow-right ml-1 text-[8px]"></i></a>
                        </div>
                        <div class="p-6 overflow-x-auto">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="text-[10px] font-bold text-slate-400 uppercase tracking-wider border-b border-slate-50">
                                        <th class="pb-3 px-2">Order ID</th>
                                        <th class="pb-3 px-2">Customer</th>
                                        <th class="pb-3 px-2">Amount</th>
                                        <th class="pb-3 px-2">Status</th>
                                        <th class="pb-3 px-2 text-right">Time</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50">
                                    <?php if(empty($recentSells)): ?>
                                        <tr><td colspan="5" class="py-8 text-center text-xs text-slate-400">No recent transactions found</td></tr>
                                    <?php else: ?>
                                        <?php foreach(array_slice($recentSells, 0, 5) as $sale): ?>
                                            <tr class="hover:bg-slate-50/50 transition-colors">
                                                <td class="py-4 px-2 text-xs font-bold text-slate-700">#<?= $sale['invoice_id']; ?></td>
                                                <td class="py-4 px-2 text-xs text-slate-600"><?= !empty($sale['customer_name']) ? htmlspecialchars($sale['customer_name']) : 'Walking Customer'; ?></td>
                                                <td class="py-4 px-2 text-xs font-black text-slate-800"><?= $currency . number_format($sale['grand_total'], 2); ?></td>
                                        <td class="py-4 px-2">
                                                    <span class="text-[9px] font-bold px-2 py-1 rounded-full <?= ($sale['status'] == 'completed' || $sale['status'] == 'paid') ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600'; ?>">
                                                        <?= ucfirst($sale['status']); ?>
                                                    </span>
                                        </td>
                                                <?php 
                                    $time_to_show = strtotime($sale['created_at']);
                                    // If created_at has no time (it's exactly midnight), try updated_at if it's the same day
                                    if (date('H:i:s', $time_to_show) === '00:00:00') {
                                        $updated_time = strtotime($sale['updated_at']);
                                        if (date('Y-m-d', $time_to_show) === date('Y-m-d', $updated_time)) {
                                            $time_to_show = $updated_time;
                                        }
                                    }
                                ?>
                                <td class="py-4 px-2 text-[10px] font-medium text-slate-400 text-right" title="<?= date('h:i A', $time_to_show); ?>"><?= timeAgo($time_to_show); ?></td>
                                    </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- 4. Top Products (List) -->
                    <div class="lg:col-span-1 bg-white rounded-2xl p-6 shadow-sm border border-slate-100 flex flex-col">
                         <div class="flex items-center justify-between mb-6">
                            <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Top Products</h3>
                            <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-500 flex items-center justify-center">
                                <i class="fas fa-crown text-sm"></i>
                        </div>
                        </div>
                        <div class="space-y-5 flex-grow">
                            <?php if(empty($topProducts)): ?>
                                <div class="h-full flex flex-col items-center justify-center text-center opacity-50">
                                    <i class="fas fa-box-open text-3xl text-slate-300 mb-2"></i>
                                    <p class="text-[10px] font-bold text-slate-400">No products sold yet</p>
                                </div>
                                        <?php else: ?>
                                <?php foreach($topProducts as $prod): 
                                    $thumb = $prod['thumbnail'];
                                    // Handle path logic: if it starts with / or uploads/, use as is. 
                                    // Otherwise, prefix with uploads/products/ for legacy support.
                                    if(!empty($thumb) && !str_starts_with($thumb, '/') && !str_starts_with($thumb, 'uploads/')) {
                                        $thumb = "uploads/products/" . $thumb;
                                    }
                                ?>
                                    <div class="flex items-center justify-between group cursor-pointer">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-xl bg-slate-50 flex items-center justify-center overflow-hidden border border-slate-100 shadow-sm group-hover:border-indigo-100 transition-all">
                                                <?php if(!empty($thumb)): ?>
                                                    <img src="<?= $thumb; ?>" class="w-full h-full object-cover">
                                                <?php else: ?>
                                                    <i class="fas fa-box text-slate-300 text-sm"></i>
                                        <?php endif; ?>
                                    </div>
                                            <div>
                                                <p class="text-[11px] font-bold text-slate-700 group-hover:text-indigo-600 transition-colors line-clamp-1 italic"><?= htmlspecialchars($prod['product_name']); ?></p>
                                                <p class="text-[10px] font-medium text-slate-400"><?= number_format($prod['total_sold']); ?> units sold</p>
                                    </div>
                                    </div>
                                        <div class="flex flex-col items-end">
                                            <p class="text-[11px] font-black text-slate-800"><?= $currency; ?><?= number_format($prod['selling_price'], 2); ?></p>
                                            <div class="text-[10px] font-bold text-indigo-500 opacity-0 group-hover:opacity-100 transition-all transform translate-x-2 group-hover:translate-x-0">
                                                <i class="fas fa-arrow-right"></i>
                                </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
                <script>
                function switchActTab(tabId, el) {
                    // Hide all content
                    document.querySelectorAll('.act-content').forEach(c => c.classList.add('hidden'));
                    // Show target
                    document.getElementById('act-' + tabId).classList.remove('hidden');
                    
                    // Update tab styling
                    document.querySelectorAll('.tab-trigger').forEach(t => {
                        t.classList.remove('active-tab', 'font-bold');
                        t.classList.add('inactive-tab', 'font-medium');
                    });
                    el.classList.remove('inactive-tab', 'font-medium');
                    el.classList.add('active-tab', 'font-bold');
                    
                    // Move underline
                    const tabLine = document.getElementById('tabLine');
                    tabLine.style.width = el.offsetWidth + 'px';
                    tabLine.style.left = el.offsetLeft + 'px';
                }

                document.addEventListener('DOMContentLoaded', function () {
                    // Initialize Tab Underline position
                    const activeTab = document.querySelector('.tab-trigger.active-tab');
                    if(activeTab) {
                        const tabLine = document.getElementById('tabLine');
                        tabLine.style.width = activeTab.offsetWidth + 'px';
                        tabLine.style.left = activeTab.offsetLeft + 'px';
                    }
                    // Inherit PHP Arrays
                    const incomeData  = <?= $jsIncome; ?>;
                    const expenseData = <?= $jsExpense; ?>;
                    const profitData  = <?= $jsProfit; ?>;
                    const weekSalesData = <?= $jsWeekSales; ?>;
                    const monthLabels = <?= $jsMonths; ?>;
                    const currencySym = "<?= $currency; ?>";

                    // Inherit PHP Totals
                    const totalIncome  = <?= $totalSalesAll; ?>;
                    const totalExpense = <?= floatval($totalPurchaseAll ?? 0); ?>;
                    const totalProfit  = <?= floatval($netBalance ?? 0); ?>;

                    // New Growth Metrics
                    const growthYoY = <?= round($growthYoY, 1); ?>;
                    const growthMoM = <?= round($growthMoM, 1); ?>;

                    // 1. Financial Report Chart (Apex Area)
                    var finOptions = {
                        series: [{ name: 'Income', data: incomeData }],
                        chart: { height: 120, type: 'area', toolbar: { show: false }, zoom: { enabled: false }, sparkline: { enabled: false } },
                        dataLabels: { enabled: false },
                        stroke: { curve: 'smooth', width: 2, colors: ['#6366f1'] },
                        tooltip: { enabled: true, y: { formatter: function(val) { return currencySym + " " + val } } },
                        grid: { show: true, borderColor: '#f1f5f9', strokeDashArray: 4, padding: { left: 10, right: 10, top: 0, bottom: -10 } },
                        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05, stops: [0, 90, 100], colorStops: [{ offset: 0, color: '#6366f1', opacity: 0.4 }, { offset: 100, color: '#6366f1', opacity: 0 }] } },
                        xaxis: { 
                            categories: monthLabels, 
                            tickAmount: 11,
                            tickPlacement: 'on',
                            labels: { 
                                show: true,
                                rotate: -45,
                                rotateAlways: true,
                                hideOverlappingLabels: false,
                                trim: false,
                                style: { fontSize: '7px', colors: '#94a3b8', fontWeight: 600 },
                                offsetY: -5
                            }, 
                            axisBorder: { show: false }, 
                            axisTicks: { show: false } 
                        },
                        yaxis: { show: false }
                    };
                    var finChart = new ApexCharts(document.querySelector("#financialChart"), finOptions);
                    finChart.render();

                    // 2. Sales Overview (Diverging Bar Chart - Split Style)
                    // Logic: If a value is > 0 but extremely small (< 2% of max), we give it a visual boost.
                    const maxOverallData = Math.max(...incomeData, ...expenseData.map(Math.abs));
                    const minOverallThreshold = maxOverallData * 0.02;

                    const incomeDisplay = incomeData.map(v => (v > 0 && v < minOverallThreshold) ? minOverallThreshold : v);
                    const expenseDisplay = expenseData.map(v => (v > 0 && v < minOverallThreshold) ? minOverallThreshold : v);
                    const expenseNegative = expenseDisplay.map(val => -val);

                    var overallOptions = {
                        series: [
                            { name: 'Income', data: incomeDisplay },
                            { name: 'Expense', data: expenseNegative }
                        ],
                        chart: { 
                            type: 'bar', 
                            height: 280, 
                            stacked: true, 
                            toolbar: { show: false } 
                        },
                        colors: ['#6366f1', '#22d3ee'], // Indigo for Income, Cyan for Expense
                        plotOptions: {
                            bar: {
                                horizontal: false,
                                columnWidth: '20%',
                                borderRadius: 5,
                                borderRadiusApplication: 'end', // Rounds the end of the bars
                                borderRadiusWhenStacked: 'all'  // Ensures even stacked bars look rounded
                            },
                        },
                        dataLabels: { enabled: false },
                        stroke: { width: 0 }, // No stroke for cleaner bar look
                        grid: {
                            borderColor: '#e2e8f0',
                            strokeDashArray: 5,
                            xaxis: { lines: { show: false } },
                            yaxis: { lines: { show: true } },
                            padding: { top: 0, right: 0, bottom: 0, left: 10 }
                        },
                        yaxis: {
                            labels: {
                                formatter: function (y) {
                                    return Math.abs(y).toFixed(0); // Show positive numbers for Y axis
                                },
                                style: { colors: '#94a3b8', fontSize: '10px', fontWeight: 600 },
                                offsetX: -10
                            }
                        },
                        xaxis: {
                            categories: monthLabels,
                            axisBorder: { show: false },
                            axisTicks: { show: false },
                            labels: { style: { colors: '#94a3b8', fontSize: '10px', fontWeight: 600 }, offsetY: 0 }
                        },
                        legend: { show: false }, // Custom legend used in HTML
                        tooltip: {
                            shared: true,
                            intersect: false,
                            y: {
                                formatter: function (y) {
                                    if (typeof y !== "undefined") {
                                        return currencySym + Math.abs(y).toFixed(2);
                                    }
                                    return y;
                                }
                            }
                        }
                    };
                    window.overallChart = new ApexCharts(document.querySelector("#overallSalesChart"), overallOptions);
                    window.overallChart.render();

                    // 3. Growth Radial Chart
                    // Use real Year-Over-Year growth calculated in PHP
                    let growthPercent = growthYoY;
                    if(growthPercent < 0) growthPercent = 0; // Don't show negative in radial for now

                    var growthOptions = {
                        series: [Math.round(growthPercent)],
                        chart: { type: 'radialBar', height: 260, sparkline: { enabled: true } },
                        plotOptions: {
                            radialBar: {
                                startAngle: -90,
                                endAngle: 90,
                                track: { 
                                    background: "#e2e8f0", 
                                    strokeWidth: '97%', 
                                    margin: 5, 
                                    dropShadow: { enabled: false } 
                                },
                                dataLabels: {
                                    name: { show: false },
                                    value: { offsetY: -2, fontSize: '22px', fontWeight: 700, color: '#1e293b', formatter: function(val) { return val + "%"; } }
                                }
                            }
                        },
                        grid: { padding: { top: -10 } },
                        fill: {
                            type: 'gradient',
                            gradient: {
                                shade: 'light',
                                type: 'horizontal',
                                shadeIntensity: 0.5,
                                gradientToColors: ['#6366f1'],
                                inverseColors: true,
                                opacityFrom: 1,
                                opacityTo: 1,
                                stops: [0, 100]
                            }
                        },
                        colors: ['#22d3ee'], // Cyan to Indigo gradient
                        stroke: { lineCap: 'round' }
                    };
                    window.growthChart = new ApexCharts(document.querySelector("#growthRadialChart"), growthOptions);
                    window.growthChart.render();

                    // 4. Weekly Sells Chart (Bar)
                    var weekOptions = {
                        series: [{ name: 'Daily Sales', data: weekSalesData }],
                        chart: { type: 'bar', height: 260, toolbar: { show: false } },
                        plotOptions: { bar: { borderRadius: 6, columnWidth: '45%', distributed: true } },
                        colors: ['#6366f1', '#818cf8', '#a5b4fc', '#c7d2fe', '#818cf8', '#6366f1', '#4f46e5'],
                        dataLabels: { enabled: false },
                        legend: { show: false },
                        xaxis: { categories: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'], labels: { style: { colors: '#94a3b8', fontSize: '10px' } } },
                        grid: { borderColor: '#f8fafc', strokeDashArray: 2 }
                    };
                    var weekChart = new ApexCharts(document.querySelector("#weeklySellsChart"), weekOptions);
                    weekChart.render();


                    // Tab Switching Logic (Updated to use finChart)
                    window.updateChart = function(type, btn) {
                        document.querySelectorAll('.chart-tab').forEach(t => {
                            t.classList.remove('bg-teal-500', 'text-white', 'shadow-sm');
                            t.classList.add('text-slate-500');
                        });
                        btn.classList.remove('text-slate-500');
                        btn.classList.add('bg-teal-500', 'text-white', 'shadow-sm');

                        let newData = (type === 'income') ? incomeData : (type === 'expenses' ? expenseData : profitData);
                        let newLabel = (type === 'income') ? "Total Income" : (type === 'expenses' ? "Total Expenses" : "Net Profit (Est.)");
                        let newAmount = (type === 'income') ? totalIncome : (type === 'expenses' ? totalExpense : totalProfit);

                        finChart.updateSeries([{ name: type.charAt(0).toUpperCase() + type.slice(1), data: newData }]);
                        document.getElementById('balanceLabel').textContent = newLabel;
                        document.getElementById('balanceAmount').textContent = currencySym + ' ' + new Intl.NumberFormat('en-US').format(newAmount);
                    };
                });
                </script>
                </div>


                <!-- Stats merged into Congratulations Card above -->

                <!-- Congratulations Card Check -->
                <!-- If it was here, restore it. It seems to have been separate. -->
                
                <!-- Financial Report Card -->
                <script>
                // Global Toast Helper (unchanged)
                window.showToast = function(message, icon = 'success') {
                    const Toast = Swal.mixin({
                        toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true,
                        didOpen: (toast) => { toast.addEventListener('mouseenter', Swal.stopTimer); toast.addEventListener('mouseleave', Swal.resumeTimer); }
                    });
                    Toast.fire({ icon: icon, title: message, background: icon === 'success' ? '#059669' : (icon === 'error' ? '#ef4444' : '#3b82f6'), color: '#fff' });
                }

                // Global Function for Year Filter
                window.fetchSalesData = function(year) {
                    $.ajax({
                        url: 'index.php',
                        type: 'POST',
                        data: { get_chart_data: true, year: year },
                        dataType: 'json',
                        success: function(response) {
                            if(response.status == 200) {
                                // 1. Update Diverging Bar Chart
                                // Logic: If a value is > 0 but extremely small (< 2% of max), 
                                // we give it a visual boost to ensure it's visible.
                                const maxVal = Math.max(...response.income, ...response.expense.map(Math.abs));
                                const minDisplayThreshold = maxVal * 0.02; // 2% of max

                                const incomeDisplay = response.income.map(v => (v > 0 && v < minDisplayThreshold) ? minDisplayThreshold : v);
                                const expenseDisplay = response.expense.map(v => (v > 0 && v < minDisplayThreshold) ? minDisplayThreshold : v);
                                const negExpenses = expenseDisplay.map(val => -Math.abs(val));

                                if(window.overallChart) {
                                    window.overallChart.updateOptions({
                                        xaxis: { categories: response.categories }
                                    });

                                    window.overallChart.updateSeries([
                                        { name: 'Income', data: incomeDisplay },
                                        { name: 'Expense', data: negExpenses }
                                    ]);
                                }

                                // 2. Update Growth Radial Chart
                                let growthVal = response.growth;
                                let radialVal = growthVal < 0 ? 0 : (growthVal > 100 ? 100 : growthVal); 
                                if(window.growthChart) {
                                    // Trigger rising animation for container
                                    const growthEl = document.getElementById('growthRadialChart');
                                    if(growthEl) {
                                        growthEl.classList.remove('animate-chart-rise');
                                        void growthEl.offsetWidth; // Trigger reflow
                                        growthEl.classList.add('animate-chart-rise');
                                    }

                                    // Trigger "Start to End" animation for the gauge
                                    window.growthChart.updateSeries([0], false); // Reset instantly
                                    setTimeout(() => {
                                        window.growthChart.updateSeries([Math.round(radialVal)], true); // Animate to target
                                    }, 100);
                                    
                                    // Update the internal label to show the REAL growth value
                                    let dynamicFontSize = growthVal > 9999 ? '14px' : (growthVal > 999 ? '18px' : '22px');
                                    window.growthChart.updateOptions({
                                        plotOptions: {
                                            radialBar: {
                                                dataLabels: {
                                                    value: {
                                                        fontSize: dynamicFontSize,
                                                        formatter: function() {
                                                            return Math.round(growthVal) + "%";
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    });
                                }

                                // 3. Update Mini Totals
                                const formatVal = (v) => (v / 1000).toFixed(1) + 'k';
                                if(document.getElementById('yearIncomeTotal')) {
                                    document.getElementById('yearIncomeTotal').innerText = formatVal(response.totalIncome);
                                }
                                if(document.getElementById('yearExpenseTotal')) {
                                    document.getElementById('yearExpenseTotal').innerText = formatVal(response.totalExpense);
                                }
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error("Chart Data Error:", error);
                        }
                    });
                };

                // --- Custom Year Dropdown Logic ---
                window.toggleYearDropdown = function() {
                    const menu = document.getElementById('yearDropdownMenu');
                    const chevron = document.getElementById('yearChevron');
                    const isHidden = menu.classList.contains('hidden');

                    if (isHidden) {
                        menu.classList.remove('hidden');
                        // Small timeout to allow transition
                        setTimeout(() => {
                            menu.classList.remove('scale-95', 'opacity-0');
                            menu.classList.add('scale-100', 'opacity-100');
                        }, 10);
                        chevron.classList.add('rotate-180');
                    } else {
                        menu.classList.remove('scale-100', 'opacity-100');
                        menu.classList.add('scale-95', 'opacity-0');
                        chevron.classList.remove('rotate-180');
                        setTimeout(() => menu.classList.add('hidden'), 200);
                    }
                };

                window.handleYearSelection = function(year) {
                    // Update UI
                    document.getElementById('selectedYearDisplay').innerText = year;
                    
                    // Trigger the existing chart update logic
                    window.fetchSalesData(year);
                    
                    // Close dropdown
                    window.toggleYearDropdown();
                };

                // Click outside to close
                window.addEventListener('click', function(e) {
                    const container = document.getElementById('yearDropdownContainer');
                    const menu = document.getElementById('yearDropdownMenu');
                    if (container && !container.contains(e.target) && !menu.classList.contains('hidden')) {
                        window.toggleYearDropdown();
                    }
                });

                document.addEventListener('DOMContentLoaded', function() {
                    // 1. Live Clock & Calendar Update
                    function updateClock() {
                        const now = new Date();
                        
                        // Time
                        const timeString = now.toLocaleTimeString('en-US', { hour12: true, hour: '2-digit', minute: '2-digit', second: '2-digit' });
                        const clockEl = document.getElementById('live_clock_sm');
                        if(clockEl) clockEl.textContent = "Time: " + timeString;

                        // Date (Update text in case of midnight crossover)
                        // Use Intl.DateTimeFormat for consistent naming
                        const dayName = new Intl.DateTimeFormat('en-US', { weekday: 'long' }).format(now);
                        const dayNum = new Intl.DateTimeFormat('en-US', { day: '2-digit' }).format(now);
                        const monthName = new Intl.DateTimeFormat('en-US', { month: 'long' }).format(now);

                        const dNameEl = document.getElementById('cal_day_name');
                        const dNumEl = document.getElementById('cal_day_num');
                        const mNameEl = document.getElementById('cal_month_name');

                        if(dNameEl && dNameEl.textContent !== dayName) dNameEl.textContent = dayName;
                        if(dNumEl && dNumEl.textContent !== dayNum) dNumEl.textContent = dayNum;
                        if(mNameEl && mNameEl.textContent !== monthName) mNameEl.textContent = monthName;
                    }
                    setInterval(updateClock, 1000);
                    updateClock();

                    // 2. Live Duration Counter
                    const openTimeStr = "<?= date('H:i:s', strtotime($open_time)); ?>"; 
                    function updateDuration() {
                        const now = new Date();
                        const [hours, minutes, seconds] = openTimeStr.split(':');
                        const openDate = new Date();
                        openDate.setHours(parseInt(hours), parseInt(minutes), parseInt(seconds), 0);
                        
                        let diff = now - openDate;
                        
                        // Handle Midnight Crossing (Night Shift)
                        // If now (e.g. 5 AM) is less than openDate (e.g. 9 PM today), it means we likely opened yesterday.
                        if (diff < 0) {
                            const yesterdayOpen = new Date(openDate);
                            yesterdayOpen.setDate(yesterdayOpen.getDate() - 1);
                            diff = now - yesterdayOpen;
                        }

                        if (diff < 0) diff = 0; // Fallback

                        const totalSeconds = Math.floor(diff / 1000);
                        const h = Math.floor(totalSeconds / 3600);
                        const m = Math.floor((totalSeconds % 3600) / 60);
                        const s = totalSeconds % 60;

                        const hDisplay = h < 10 ? "0" + h : h;
                        const mDisplay = m < 10 ? "0" + m : m;
                        const sDisplay = s < 10 ? "0" + s : s;

                        const durationEl = document.getElementById('live_duration_counter');
                        if(durationEl) durationEl.textContent = `${hDisplay}:${mDisplay}:${sDisplay}`;
                    }
                    setInterval(updateDuration, 1000);
                    updateDuration();
                });
                </script>
            </div> 
            <?php include('includes/footer.php'); ?>
        </div> 
    </main>
</div>