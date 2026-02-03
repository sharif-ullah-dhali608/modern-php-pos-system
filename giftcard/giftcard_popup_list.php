<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('../config/dbcon.php');
include('../includes/date_filter_helper.php');

// Filter Parameters
$filter_customer = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;
$date_filter = $_GET['date_filter'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// Fetch Customers List
$customers_list = [];
$cust_q = mysqli_query($conn, "SELECT id, name FROM customers WHERE status = 1 ORDER BY name ASC");
if($cust_q) {
    while($c = mysqli_fetch_assoc($cust_q)) $customers_list[] = $c;
}

// Check login
if(!isset($_SESSION['auth'])) {
    header("Location: /pos/login");
    exit(0);
}

// Fetch topup history with giftcard and user info
// Fetch topup history with giftcard and user info
$query = "SELECT 
            t.id,
            t.giftcard_id,
            t.amount,
            t.note,
            t.created_at,
            g.card_no,
            c.name as customer_name,
            u.name as created_by
          FROM giftcard_topups t
          LEFT JOIN giftcards g ON t.giftcard_id = g.id
          LEFT JOIN users u ON t.created_by = u.id
          LEFT JOIN customers c ON g.customer_id = c.id
          WHERE 1=1 ";

// Apply Filter
if($filter_customer > 0) {
    $query .= " AND g.customer_id = '$filter_customer' ";
}

applyDateFilter($query, 't.created_at', $date_filter, $start_date, $end_date);

$query .= " ORDER BY t.created_at DESC";
$result = mysqli_query($conn, $query);

$items = [];
if($result && mysqli_num_rows($result) > 0) {
    while($row = mysqli_fetch_assoc($result)) {
        // Format date
        $row['date_formatted'] = date('d M, Y h:i A', strtotime($row['created_at']));
        
        // Format amount
        $row['amount_formatted'] = number_format((float)$row['amount'], 2);
        
        // Delete button
        $row['delete_btn'] = '<button type="button" onclick="confirmDelete('.$row['id'].', \'Topup #'.$row['id'].'\', \'/pos/giftcard/save_giftcard.php\')" 
                                class="p-2 text-red-500 hover:bg-red-50 rounded transition" title="Delete">
                                <i class="fas fa-trash-alt"></i>
                            </button>';
        
        $items[] = $row;
    }
}

// Configure the list
$list_config = [
    'title' => 'Giftcard Topup List',
    'table_id' => 'giftcard_topup_table',
    'date_column' => 'created_at',
    'filters' => [
        [
            'name' => 'customer',
            'label' => 'Customer',
            'id' => 'customerFilter',
            'searchable' => true,
            'options' => array_merge(
                [['label' => 'All Customers', 'url' => '?customer_id=0', 'active' => $filter_customer == 0]],
                array_map(function($c) use ($filter_customer) {
                    return [
                        'label' => $c['name'],
                        'url' => '?customer_id=' . $c['id'],
                        'active' => $filter_customer == $c['id']
                    ];
                }, $customers_list)
            )
        ]
    ],
    'primary_key' => 'id',
    'name_field' => 'card_no',
    'add_url' => '#', // No add button
    'delete_url' => '/pos/giftcard/save_giftcard.php',
    'columns' => [
        ['key' => 'date_formatted', 'label' => 'Date', 'type' => 'text'],
        ['key' => 'card_no', 'label' => 'Card No.', 'type' => 'text'],
        ['key' => 'customer_name', 'label' => 'Customer', 'type' => 'text'],
        ['key' => 'amount_formatted', 'label' => 'Amount', 'type' => 'text'],
        ['key' => 'created_by', 'label' => 'Created By', 'type' => 'text'],
        ['key' => 'delete_btn', 'label' => 'Delete', 'type' => 'html'],
    ],
    'data' => $items,
];

include('../includes/header.php');
?>
<div class="app-wrapper">
    <?php include('../includes/sidebar.php'); ?>
    
    <main id="main-content" class="flex-1 lg:ml-64 flex flex-col h-screen min-w-0 transition-all duration-300">
        <div class="navbar-fixed-top">
            <?php include('../includes/navbar.php'); ?>
        </div>
        
        <div class="content-scroll-area custom-scroll h-full overflow-y-auto">
            <div class="p-6">
                <?php 
                include('../includes/reusable_list.php'); 
                renderReusableList($list_config); 
                ?>
            </div>
        </div>
        <?php include('../includes/footer.php'); ?>
    </main> 
</div>
