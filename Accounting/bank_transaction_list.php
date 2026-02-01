<?php
session_start();
include('../config/dbcon.php');

// Security Check
if(!isset($_SESSION['auth'])){
    header("Location: /pos/login");
    exit(0);
}

// Fetch Transactions
// Join with bank_accounts to get account name
// Join with bank_transaction_price for amount
// Logic: Credits vs Debits based on type and is_subtract
$query = "SELECT 
            info.info_id, 
            info.transaction_type, 
            info.created_at, 
            info.ref_no,
            acc.account_name,
            acc.initial_balance,
            acc.total_deposit,
            acc.total_withdraw,
            acc.total_transfer_from_other,
            acc.total_transfer_to_other,
            price.amount,
            info.is_substract
          FROM bank_transaction_info info
          LEFT JOIN bank_accounts acc ON info.account_id = acc.id
          LEFT JOIN bank_transaction_price price ON info.info_id = price.info_id
          ORDER BY info.created_at DESC";

$query_run = mysqli_query($conn, $query);

$items = [];
if($query_run) {
    while($row = mysqli_fetch_assoc($query_run)) {
        $row['date_formatted'] = date('Y-m-d', strtotime($row['created_at']));
        
        $amount = floatval($row['amount']);
        if($row['is_substract'] == 1) {
            $row['debit'] = number_format($amount, 2);
            $row['credit'] = '';
        } else {
            $row['credit'] = number_format($amount, 2);
            $row['debit'] = '';
        }
        
        // Calculate Current Account Balance
        $initial = floatval($row['initial_balance']);
        $deposit = floatval($row['total_deposit']);
        $withdraw = floatval($row['total_withdraw']);
        $tr_from = floatval($row['total_transfer_from_other']);
        $tr_to = floatval($row['total_transfer_to_other']);
        
        $current_balance = $initial + $deposit - $withdraw + $tr_from - $tr_to;
        $row['balance'] = number_format($current_balance, 2);
        
        $row['view_btn'] = '<button class="btn btn-sm btn-info"><i class="fas fa-eye"></i></button>'; // Placeholder
        
        $items[] = $row;
    }
}

$list_config = [
    'title' => 'Bank Transaction List',
    'table_id' => 'bankTransactionTable',
    'columns' => [
        ['key' => 'date_formatted', 'label' => 'Date', 'sortable' => true],
        ['key' => 'ref_no', 'label' => 'Id', 'sortable' => true],
        ['key' => 'transaction_type', 'label' => 'Type', 'sortable' => true],
        ['key' => 'account_name', 'label' => 'Account', 'sortable' => true],
        ['key' => 'credit', 'label' => 'Credit', 'sortable' => true],
        ['key' => 'debit', 'label' => 'Debit', 'sortable' => true],
        ['key' => 'balance', 'label' => 'Balance', 'sortable' => false],
        // ['key' => 'view_btn', 'label' => 'View', 'type' => 'custom'] // Reusable list might not support custom HTML in array directly easily without type
    ],
    'data' => $items,
    // No edit/delete URLs generally for transactions in this view unless specified
    'actions' => false, // Disable standard action column
    'primary_key' => 'info_id'
];

$page_title = "Bank Transaction List - Velocity POS";
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
            
            <?php include('../includes/footer.php'); ?>
        </div>
    </main>
</div>
