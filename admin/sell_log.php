<?php
session_start();
include('../config/dbcon.php');
include('../includes/date_filter_helper.php');

if(!isset($_SESSION['auth'])){
    header("Location: ../signin.php");
    exit(0);
}

$page_title = "Sell Log Details";
include('../includes/header.php');
include('../includes/reusable_list.php');

// Filter parameters
$filter_customer = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;
$filter_type = $_GET['type'] ?? 'all';
$filter_pmethod = isset($_GET['pmethod_id']) ? intval($_GET['pmethod_id']) : 0;

$date_filter = $_GET['date_filter'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// Fetch Customers for filter dropdown
$customers_list = [];
$cust_result = mysqli_query($conn, "SELECT id, name FROM customers WHERE status = 1 ORDER BY name ASC");
while($c = mysqli_fetch_assoc($cust_result)) $customers_list[] = $c;

// Fetch Payment Methods for filter dropdown
$pm_list = [];
$pm_result = mysqli_query($conn, "SELECT id, name FROM payment_methods WHERE status = 1");
while($pm = mysqli_fetch_assoc($pm_result)) $pm_list[] = $pm;

// Fetch Data - exclude hidden 'payment' type entries (used for invoice calculations only)
$query = "SELECT sl.*, c.name as customer_name, pm.name as pmethod_name, u.name as created_by_name 
          FROM sell_logs sl 
          LEFT JOIN customers c ON sl.customer_id = c.id 
          LEFT JOIN payment_methods pm ON sl.pmethod_id = pm.id 
          LEFT JOIN users u ON sl.created_by = u.id 
          WHERE sl.type != 'payment'";

// Apply Customer Filter
if($filter_customer > 0) {
    $query .= " AND sl.customer_id = $filter_customer ";
}

// Apply Type Filter
if($filter_type != 'all') {
    if($filter_type === 'installment') {
        $query .= " AND sl.is_installment = 1 ";
    } else {
        $filter_type = mysqli_real_escape_string($conn, $filter_type);
        $query .= " AND sl.type = '$filter_type' ";
    }
}

// Apply Payment Method Filter
if($filter_pmethod > 0) {
    $query .= " AND sl.pmethod_id = $filter_pmethod ";
}



// Apply date filter
applyDateFilter($query, 'sl.created_at', $date_filter, $start_date, $end_date);

$query .= " ORDER BY sl.created_at DESC";

$result = mysqli_query($conn, $query);
$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    // Format type display with color-coded badges
    $type = $row['type'];
    if (isset($row['is_installment']) && $row['is_installment'] == 1) {
        $row['type_display'] = '<span class="inline-block px-3 py-1 rounded-full text-xs font-bold bg-teal-500 text-white">INSTALLMENT</span>';
    } else {
        switch($type) {
        case 'full_payment':
            $row['type_display'] = '<span class="inline-block px-3 py-1 rounded-full text-xs font-bold bg-teal-100 text-teal-700">FULL PAYMENT</span>';
            break;
        case 'full_due':
            $row['type_display'] = '<span class="inline-block px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-700">FULL DUE</span>';
            break;
        case 'due_paid':
            $row['type_display'] = '<span class="inline-block px-3 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-700">DUE PAID</span>';
            break;
        case 'partial_payment':
            $row['type_display'] = '<span class="inline-block px-3 py-1 rounded-full text-xs font-bold bg-purple-100 text-purple-700">PARTIAL</span>';
            break;
        case 'partial_due':
            $row['type_display'] = '<span class="inline-block px-3 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-700">PARTIAL DUE</span>';
            break;
        default:
            $row['type_display'] = '<span class="inline-block px-3 py-1 rounded-full text-xs font-bold bg-gray-100 text-gray-700">' . strtoupper($type) . '</span>';
        }
    }
    
    // Payment method - show just method name(s) from description
    // Description format: "Cash: 500.00" or "bKash: 200.00, Cash: 300.00" or "Pay Later"
    if ($row['description'] === 'Pay Later') {
        $row['payment_display'] = '<span class="text-amber-600 font-medium">Pay Later</span>';
    } elseif (!empty($row['description'])) {
        // Extract method names from description (e.g., "Cash: 500.00, bKash: 200.00" -> "Cash, bKash")
        $methods = [];
        $parts = explode(', ', $row['description']);
        foreach ($parts as $part) {
            $colonPos = strpos($part, ':');
            if ($colonPos !== false) {
                $methods[] = trim(substr($part, 0, $colonPos));
            }
        }
        $row['payment_display'] = !empty($methods) ? implode(', ', $methods) : ($row['pmethod_name'] ?? 'Cash');
    } else {
        $row['payment_display'] = $row['pmethod_name'] ?? 'Cash';
    }
    
    // Customer display - show "Walking Customer" if no customer name
    $row['customer_display'] = !empty($row['customer_name']) ? $row['customer_name'] : 'Walking Customer';
    
    // Transaction ID handling
    $transaction_id_display = $row['transaction_id'] ?? '-';
    if (($transaction_id_display === '-' || empty($transaction_id_display)) && !empty($row['description'])) {
         // Attempt to extract from description if backward compatible
         if (preg_match('/: ([a-zA-Z0-9]+)$/', $row['description'], $matches)) {
             // Basic check, might match amount format, so use cautiously or skip
             // Better to rely on the new column
         }
    }
    $row['transaction_id_display'] = $transaction_id_display;
    
    $data[] = $row;
}

// Build customer filter options
$customer_filter_options = [['label' => 'All Customers', 'url' => '?customer_id=0&type='.$filter_type.'&pmethod_id='.$filter_pmethod, 'active' => $filter_customer == 0]];
foreach($customers_list as $cust) {
    $customer_filter_options[] = [
        'label' => $cust['name'],
        'url' => '?customer_id='.$cust['id'].'&type='.$filter_type.'&pmethod_id='.$filter_pmethod,
        'active' => $filter_customer == $cust['id']
    ];
}

// Build Type filter options
$type_filter_options = [
    ['label' => 'All Types', 'url' => '?type=all&customer_id='.$filter_customer.'&pmethod_id='.$filter_pmethod, 'active' => $filter_type == 'all'],
    ['label' => 'Full Payment', 'url' => '?type=full_payment&customer_id='.$filter_customer.'&pmethod_id='.$filter_pmethod, 'active' => $filter_type == 'full_payment'],
    ['label' => 'Full Due', 'url' => '?type=full_due&customer_id='.$filter_customer.'&pmethod_id='.$filter_pmethod, 'active' => $filter_type == 'full_due'],
    ['label' => 'Due Paid', 'url' => '?type=due_paid&customer_id='.$filter_customer.'&pmethod_id='.$filter_pmethod, 'active' => $filter_type == 'due_paid'],
    ['label' => 'Partial Payment', 'url' => '?type=partial_payment&customer_id='.$filter_customer.'&pmethod_id='.$filter_pmethod, 'active' => $filter_type == 'partial_payment'],
    ['label' => 'Partial Due', 'url' => '?type=partial_due&customer_id='.$filter_customer.'&pmethod_id='.$filter_pmethod, 'active' => $filter_type == 'partial_due'],
    ['label' => 'Installment', 'url' => '?type=installment&customer_id='.$filter_customer.'&pmethod_id='.$filter_pmethod, 'active' => $filter_type == 'installment'],
];

// Build Payment Method filter options
$pm_filter_options = [['label' => 'All Payment Methods', 'url' => '?pmethod_id=0&customer_id='.$filter_customer.'&type='.$filter_type, 'active' => $filter_pmethod == 0]];
foreach($pm_list as $pm) {
    $pm_filter_options[] = [
        'label' => $pm['name'],
        'url' => '?pmethod_id='.$pm['id'].'&customer_id='.$filter_customer.'&type='.$filter_type,
        'active' => $filter_pmethod == $pm['id']
    ];
}

// Prepare Config
$config = [
    'title' => 'Sell Log Details',
    'table_id' => 'sell_log_table',
    'add_url' => '#', 
    'edit_url' => '#',
    'delete_url' => '#',
    'view_url' => '/pos/admin/sell_log_view.php',
    'primary_key' => 'id',
    'name_field' => 'reference_no',
    'data' => $data,
    'action_buttons' => ['view'], // Only view button
    'date_column' => 'sl.created_at',
    // New: Filters
    'filters' => [
        ['id' => 'filter_type', 'label' => 'Type', 'options' => $type_filter_options],
        ['id' => 'filter_pmethod', 'label' => 'Payment Method', 'options' => $pm_filter_options],
        ['id' => 'filter_customer', 'label' => 'Customer', 'searchable' => true, 'options' => $customer_filter_options]
    ],
    'columns' => [
        ['label' => 'Created At', 'key' => 'created_at'],
        ['label' => 'Type', 'key' => 'type_display', 'type' => 'html'],
        ['label' => 'Customer', 'key' => 'customer_display'],
        ['label' => 'Payment Method', 'key' => 'payment_display', 'type' => 'html'],
        ['label' => 'Transaction ID', 'key' => 'transaction_id_display'],
        ['label' => 'Created By', 'key' => 'created_by_name'],
        ['label' => 'Amount', 'key' => 'amount'],
        ['label' => 'Actions', 'key' => 'actions', 'type' => 'actions'] 
    ]
];
?>

<div class="app-wrapper">
    <?php include('../includes/sidebar.php'); ?>

    <main id="main-content" class="lg:ml-64 flex flex-col h-screen">
        <div class="navbar-fixed-top">
            <?php include('../includes/navbar.php'); ?>
        </div>
        
        <div class="content-scroll-area custom-scroll">
            <div class="p-6">
                <?php renderReusableList($config); ?>
            </div>
            <?php include('../includes/footer.php'); ?>
        </div>
    </main>
</div>
