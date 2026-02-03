<?php
session_start();
include('../config/dbcon.php');
include('../includes/date_filter_helper.php');

if(!isset($_SESSION['auth'])){
    header("Location: ../signin.php");
    exit(0);
}

$page_title = "Collection Report - Velocity POS";
include('../includes/header.php');


// Filter parameters
$date_filter = $_GET['date_filter'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// Fetch Data - Aggregate by User
$query = "SELECT u.name as user_name, u.id as user_id,
          COUNT(DISTINCT CASE WHEN si.inv_type = 'sale' AND DATE(si.created_at) BETWEEN '$start_date' AND '$end_date' THEN si.invoice_id END) as total_inv,
          SUM(CASE WHEN si.inv_type = 'sale' AND DATE(si.created_at) BETWEEN '$start_date' AND '$end_date' THEN si.grand_total ELSE 0 END) as net_amount,
          
          /* Prev. Due Col: Payments in period for invoices created BEFORE period */
          SUM(CASE WHEN sl.type = 'due_paid' AND DATE(si_ref.created_at) < '$start_date' THEN sl.amount ELSE 0 END) as prev_due_col,
          
          /* Due Collection: Payments in period for invoices created IN period (partial/due_paid) */
          SUM(CASE WHEN (sl.type = 'due_paid' OR sl.type = 'partial_payment') AND DATE(si_ref.created_at) BETWEEN '$start_date' AND '$end_date' THEN sl.amount ELSE 0 END) as due_collection,
          
          /* Due Given: Remaining due on invoices created IN period */
          (SUM(CASE WHEN si.inv_type = 'sale' AND DATE(si.created_at) BETWEEN '$start_date' AND '$end_date' THEN si.grand_total ELSE 0 END) - 
           SUM(CASE WHEN sl.ref_invoice_id IS NOT NULL AND DATE(si_ref.created_at) BETWEEN '$start_date' AND '$end_date' THEN sl.amount ELSE 0 END)) as due_given_raw,
           
          SUM(sl.amount) as total_received
          FROM users u
          LEFT JOIN sell_logs sl ON sl.created_by = u.id AND DATE(sl.created_at) BETWEEN '$start_date' AND '$end_date'
          LEFT JOIN selling_info si_ref ON sl.ref_invoice_id = si_ref.invoice_id
          /* Join to count invoices created in period by this user */
          LEFT JOIN selling_info si ON si.created_by = u.id AND DATE(si.created_at) BETWEEN '$start_date' AND '$end_date' AND si.inv_type = 'sale'
          WHERE u.status = 1 ";

$query .= " GROUP BY u.id ORDER BY total_received DESC";

$result = mysqli_query($conn, $query);
$data = [];
$grand_received = 0;
$sl = 1;

while ($row = mysqli_fetch_assoc($result)) {
    // If no action in period, skip if everything is zero
    if ($row['total_received'] <= 0 && $row['total_inv'] <= 0) continue;

    $row['sl'] = $sl++;
    $row['net_formatted'] = number_format($row['net_amount'] ?? 0, 2);
    $row['prev_due_formatted'] = number_format($row['prev_due_col'] ?? 0, 2);
    $row['due_col_formatted'] = number_format($row['due_collection'] ?? 0, 2);
    
    // Due Given calculation adjustment (simple grand_total - total_paid_for_those_invoices)
    $due_given = max(0, $row['due_given_raw']);
    $row['due_given_formatted'] = number_format($due_given, 2);
    
    $row['received_formatted'] = number_format($row['total_received'] ?? 0, 2);
    
    $grand_received += $row['total_received'];
    $data[] = $row;
}

$config = [
    'title' => 'Collection Report',
    'table_id' => 'collection_report_table',
    'primary_key' => 'user_id',
    'name_field' => 'user_name',
    'data' => $data,
    'date_column' => 'sl.created_at',
    'summary_cards' => [
        ['label' => 'Total Received', 'value' => number_format($grand_received, 2), 'border_color' => 'border-emerald-500'],
        ['label' => 'Users count', 'value' => count($data), 'border_color' => 'border-teal-500'],
    ],
    'columns' => [
        ['label' => 'SL', 'key' => 'sl'],
        ['label' => 'Username', 'key' => 'user_name'],
        ['label' => 'Total Inv', 'key' => 'total_inv'],
        ['label' => 'Net Amount', 'key' => 'net_formatted'],
        ['label' => 'Prev. Due Col.', 'key' => 'prev_due_formatted'],
        ['label' => 'Due Collection', 'key' => 'due_col_formatted'],
        ['label' => 'Due Given', 'key' => 'due_given_formatted'],
        ['label' => 'Received', 'key' => 'received_formatted'],
    ],
    'action_buttons' => []
];
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
                include_once('../includes/reusable_list.php'); 
                renderReusableList($config); 
                ?>
            </div>
        </div>
        <?php include('../includes/footer.php'); ?>
    </main>
</div>