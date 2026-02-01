<?php
session_start();
include('../config/dbcon.php');

// Security Check
if(!isset($_SESSION['auth'])){
    header("Location: /pos/login");
    exit(0);
}

// Fetch Bank Accounts for Balance Sheet
$query = "SELECT * FROM bank_accounts ORDER BY sort_order ASC, id DESC";
$query_run = mysqli_query($conn, $query);

$items = [];
$total_credit = 0;
$total_debit = 0;
$total_transfer_to = 0;
$total_transfer_from = 0;
$total_balance = 0;

if($query_run) {
    while($row = mysqli_fetch_assoc($query_run)) {
        
        $credit = floatval($row['total_deposit']);
        $debit = floatval($row['total_withdraw']);
        $tr_to = floatval($row['total_transfer_to_other']);
        $tr_from = floatval($row['total_transfer_from_other']);
        $initial = floatval($row['initial_balance']);
        
        $balance = $initial + $credit - $debit + $tr_from - $tr_to;
        
        // Sanitize Account Name for HTML safety since we are enabling HTML output
        $row['account_name'] = htmlspecialchars($row['account_name']);
        
        $row['credit_formatted'] = number_format($credit, 2);
        $row['debit_formatted'] = number_format($debit, 2);
        $row['tr_to_formatted'] = number_format($tr_to, 2);
        $row['tr_from_formatted'] = number_format($tr_from, 2);
        $row['balance_formatted'] = number_format($balance, 2);
        
        // Accumulate totals for footer if reusable list supports it (or just last row)
        // Reusable list doesn't natively support footer totals row easily yet without custom injection.
        // But the screenshot shows "Total". 
        // I will add a "Total" row as the last item in data array manually if needed, 
        // OR rely on reusable list features if exists. 
        // Current reusable list doesn't seem to calculate totals automatically. 
        // I'll append a "fake" row for Total.
        
        $total_credit += $credit;
        $total_debit += $debit;
        $total_transfer_to += $tr_to;
        $total_transfer_from += $tr_from;
        $total_balance += $balance;
        
        $items[] = $row;
    }
}

$list_config = [
    'title' => 'Balance Sheet Details',
    'table_id' => 'balanceSheetTable',
    'columns' => [
        ['key' => 'id', 'label' => 'Account Id', 'sortable' => true],
        ['key' => 'account_name', 'label' => 'Account Name', 'sortable' => true],
        ['key' => 'credit_formatted', 'label' => 'Credit', 'sortable' => true],
        ['key' => 'debit_formatted', 'label' => 'Debit', 'sortable' => true],
        ['key' => 'tr_to_formatted', 'label' => 'Transfer To Other', 'sortable' => true],
        ['key' => 'tr_from_formatted', 'label' => 'Transfer From Other', 'sortable' => true],
        ['key' => 'balance_formatted', 'label' => 'Balance', 'sortable' => true]
    ],
    'data' => $items,
    'footer' => [
        'id' => '',
        'account_name' => 'Total',
        'credit_formatted' => number_format($total_credit, 2),
        'debit_formatted' => number_format($total_debit, 2),
        'tr_to_formatted' => number_format($total_transfer_to, 2),
        'tr_from_formatted' => number_format($total_transfer_from, 2),
        'balance_formatted' => number_format($total_balance, 2)
    ],
    'actions' => false,
    'primary_key' => 'id'
];

$page_title = "Balance Sheet Details - Velocity POS";
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
