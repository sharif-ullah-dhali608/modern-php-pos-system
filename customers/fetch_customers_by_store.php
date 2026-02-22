<?php
session_start();
require_once('../config/dbcon.php');

header('Content-Type: application/json');

// Security Check
if(!isset($_SESSION['auth'])){
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit(0);
}

$store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// Base Query
// We join with customer_stores_map to filter by store
// If store_id is 'all' (or 0 and user has access to all), we might show all?
// BUT, the requirement is "only display customers belonging to the currently selected store".
// If store_id is 0/all, we probably show all active customers.

$where_clauses = ["c.status = 1"];

// Store Filter
if ($store_id > 0) {
    // Join required
    $join_sql = "INNER JOIN customer_stores_map csm ON c.id = csm.customer_id";
    $where_clauses[] = "csm.store_id = $store_id";
} else {
    // If store_id is 0/All, do we show ALL customers?
    // Usually 'All Stores' means we see everything.
    $join_sql = ""; 
}

// Search Filter
if (!empty($search)) {
    $where_clauses[] = "(c.name LIKE '%$search%' OR c.mobile LIKE '%$search%')";
}

$where_sql = implode(' AND ', $where_clauses);

$query = "SELECT c.id, c.name, c.mobile, c.current_due as credit, c.opening_balance, c.has_installment,
          (SELECT SUM(ip.due) FROM installment_payments ip 
           JOIN selling_info si ON ip.invoice_id = si.invoice_id 
           WHERE si.customer_id = c.id AND ip.payment_status = 'due') as installment_due
          FROM customers c 
          $join_sql 
          WHERE $where_sql 
          GROUP BY c.id, c.name, c.mobile, c.current_due, c.opening_balance, c.has_installment
          ORDER BY c.name ASC 
          LIMIT 50";

$result = mysqli_query($conn, $query);

$customers = [];

if ($result) {
    while($row = mysqli_fetch_assoc($result)) {
        // We also need giftcard balance
        $c_id = $row['id'];
        $gc_query = mysqli_query($conn, "SELECT COALESCE(SUM(balance), 0) as gc_balance FROM giftcards WHERE customer_id = $c_id AND status = 1");
        $gc_row = mysqli_fetch_assoc($gc_query);
        $row['giftcard_balance'] = $gc_row['gc_balance'];
        
        $customers[] = $row;
    }
}

echo json_encode(['success' => true, 'customers' => $customers]);
