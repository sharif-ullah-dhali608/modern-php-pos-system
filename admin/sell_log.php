<?php
session_start();
include('../config/dbcon.php');

if(!isset($_SESSION['auth'])){
    header("Location: ../signin.php");
    exit(0);
}

$page_title = "Sell Log Details";
include('../includes/header.php');
include('../includes/reusable_list.php');

// Fetch Data - exclude hidden 'payment' type entries (used for invoice calculations only)
$query = "SELECT sl.*, c.name as customer_name, pm.name as pmethod_name, u.name as created_by_name 
          FROM sell_logs sl 
          LEFT JOIN customers c ON sl.customer_id = c.id 
          LEFT JOIN payment_methods pm ON sl.pmethod_id = pm.id 
          LEFT JOIN users u ON sl.created_by = u.id 
          WHERE sl.type != 'payment'
          ORDER BY sl.created_at DESC";

$result = mysqli_query($conn, $query);
$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    // Format type display with color-coded badges
    $type = $row['type'];
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
    
    $data[] = $row;
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
    'columns' => [
        ['label' => 'Created At', 'key' => 'created_at'],
        ['label' => 'Type', 'key' => 'type_display', 'type' => 'html'],
        ['label' => 'Customer', 'key' => 'customer_display'],
        ['label' => 'Payment Method', 'key' => 'payment_display', 'type' => 'html'],
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
