<?php
session_start();
include('../config/dbcon.php');

// Security Check
if(!isset($_SESSION['auth'])){
    header("Location: /pos/login");
    exit(0);
}

// Fetch Transfers
// Filter by transaction_type usually 'transfer' or check internal logic
// Assuming 'transfer' type exists
$query = "SELECT 
            info.info_id, 
            info.created_at, 
            info.ref_no,
            from_acc.account_name as from_account,
            to_acc.account_name as to_account,
            price.amount
          FROM bank_transaction_info info
          LEFT JOIN bank_accounts from_acc ON info.from_account_id = from_acc.id
          LEFT JOIN bank_accounts to_acc ON info.account_id = to_acc.id
          LEFT JOIN bank_transaction_price price ON info.info_id = price.info_id
          WHERE info.transaction_type LIKE '%transfer%' 
             OR (info.from_account_id IS NOT NULL AND info.from_account_id > 0)
          ORDER BY info.created_at DESC";

$query_run = mysqli_query($conn, $query);

$items = [];
if($query_run) {
    while($row = mysqli_fetch_assoc($query_run)) {
        $row['date_formatted'] = date('Y-m-d', strtotime($row['created_at']));
        $row['amount_formatted'] = number_format($row['amount'], 2);
        
        $items[] = $row;
    }
}

$list_config = [
    'title' => 'Bank Transfer List',
    'table_id' => 'bankTransferTable',
    'columns' => [
        ['key' => 'ref_no', 'label' => 'Id', 'sortable' => true],
        ['key' => 'date_formatted', 'label' => 'Date', 'sortable' => true],
        ['key' => 'from_account', 'label' => 'From Account', 'sortable' => true],
        ['key' => 'to_account', 'label' => 'To Account', 'sortable' => true],
        ['key' => 'amount_formatted', 'label' => 'Amount', 'sortable' => true]
    ],
    'data' => $items,
    'actions' => false,
    'primary_key' => 'info_id'
];

$page_title = "Bank Transfer List - Velocity POS";
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
