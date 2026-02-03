<?php
session_start();
include('../config/dbcon.php');
include('../includes/date_filter_helper.php');

if (!isset($_SESSION['auth'])) {
    header("Location: /pos/login");
    exit(0);
}

$page_title = "Receive List";
include('../includes/header.php');
include('../includes/reusable_list.php');

$user_id = $_SESSION['auth_user']['user_id'];
$user_role = $_SESSION['auth_user']['role_as'];

// Base Query
$query = "SELECT t.*, 
          fs.store_name as from_store, 
          ts.store_name as to_store,
          u.name as creator_name,
          (SELECT GROUP_CONCAT(product_name SEPARATOR ', ') FROM transfer_items WHERE transfer_id = t.id) as product_names
          FROM transfers t
          JOIN stores fs ON t.from_store_id = fs.id
          JOIN stores ts ON t.to_store_id = ts.id
          JOIN users u ON t.created_by = u.id
          WHERE t.status IN ('Sent', 'Received') ";

// Only show transfers where user has access to the destination store
if ($user_role !== 'admin') {
    $query .= " AND t.to_store_id IN (SELECT store_id FROM user_store_map WHERE user_id = '$user_id') ";
}

$date_filter = $_GET['date_filter'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$filter_from_store = $_GET['from_store'] ?? 'all';
$filter_to_store = $_GET['to_store'] ?? 'all';
$filter_created_by = $_GET['created_by'] ?? 'all';
$filter_product = $_GET['product'] ?? 'all';

// Fetch all stores for filters
$stores_q = mysqli_query($conn, "SELECT id, store_name FROM stores ORDER BY store_name ASC");
$store_options = [['label' => 'All Source', 'url' => '?from_store=all', 'active' => $filter_from_store == 'all']];
$to_store_options = [['label' => 'All Target', 'url' => '?to_store=all', 'active' => $filter_to_store == 'all']];
while($s = mysqli_fetch_assoc($stores_q)) {
    $store_options[] = ['label' => $s['store_name'], 'url' => '?from_store='.$s['id'], 'active' => $filter_from_store == $s['id']];
    $to_store_options[] = ['label' => $s['store_name'], 'url' => '?to_store='.$s['id'], 'active' => $filter_to_store == $s['id']];
}

// Fetch all products for filter
$products_q = mysqli_query($conn, "SELECT id, product_name FROM products ORDER BY product_name ASC");
$product_options = [['label' => 'All Products', 'url' => '?product=all', 'active' => $filter_product == 'all']];
while($p = mysqli_fetch_assoc($products_q)) {
    $product_options[] = ['label' => $p['product_name'], 'url' => '?product='.$p['id'], 'active' => $filter_product == $p['id']];
}

// Fetch all users for "Created By" filter

// Fetch all users for "Created By" filter
$users_q = mysqli_query($conn, "SELECT id, name FROM users ORDER BY name ASC");
$user_options = [['label' => 'All Users', 'url' => '?created_by=all', 'active' => $filter_created_by == 'all']];
while($u = mysqli_fetch_assoc($users_q)) {
    $user_options[] = ['label' => $u['name'], 'url' => '?created_by='.$u['id'], 'active' => $filter_created_by == $u['id']];
}

applyDateFilter($query, 't.created_at', $date_filter, $start_date, $end_date);

if ($filter_from_store !== 'all') {
    $query .= " AND t.from_store_id = '$filter_from_store' ";
}
if ($filter_to_store !== 'all') {
    $query .= " AND t.to_store_id = '$filter_to_store' ";
}
if ($filter_created_by !== 'all') {
    $query .= " AND t.created_by = '$filter_created_by' ";
}
if ($filter_product !== 'all') {
    $query .= " AND t.id IN (SELECT transfer_id FROM transfer_items WHERE product_id = '$filter_product') ";
}

$query .= " ORDER BY t.created_at DESC";

$result = mysqli_query($conn, $query);
$data = [];

while ($row = mysqli_fetch_assoc($result)) {
    // Status Badge & Action Button logic
    if ($row['status'] === 'Sent') {
        $row['status_badge'] = "<span class='status-badge bg-blue-100 text-blue-700'>SENT</span>";
        $row['receive_btn'] = "<button onclick='receiveTransfer({$row['id']})' class='btn-premium bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-xl font-bold text-[10px] uppercase transition-all shadow-lg shadow-emerald-100 flex items-center gap-2'><i class='fas fa-check-circle'></i> Receive</button>";
    } else {
        $row['status_badge'] = "<span class='status-badge bg-emerald-100 text-emerald-700'>RECEIVED</span>";
        $row['receive_btn'] = "<span class='text-slate-400 text-xs font-bold italic'><i class='fas fa-check-double mr-1'></i> Processed</span>";
    }

    // Product names formatting (truncated)
    $products = $row['product_names'];
    if (strlen($products) > 30) {
        $row['product_display'] = "<span title='{$products}'>" . substr($products, 0, 30) . "...</span>";
    } else {
        $row['product_display'] = $products;
    }

    $data[] = $row;
}

// Config for Reusable List
$config = [
    'title' => 'Receive Incoming Stock',
    'table_id' => 'receive_table',
    'primary_key' => 'id',
    'data' => $data,
    'columns' => [
        ['label' => 'Ref No', 'key' => 'ref_no'],
        ['label' => 'Products', 'key' => 'product_display', 'type' => 'html'],
        ['label' => 'Sent Date', 'key' => 'created_at'],
        ['label' => 'From Store', 'key' => 'from_store'],
        ['label' => 'Items', 'key' => 'total_item'],
        ['label' => 'Qty', 'key' => 'total_quantity'],
        ['label' => 'Status', 'key' => 'status_badge', 'type' => 'html'],
        ['label' => 'Action', 'key' => 'receive_btn', 'type' => 'html']
    ],
    'filters' => [
        ['id' => 'filter_from_store', 'label' => 'Source Store', 'searchable' => true, 'options' => $store_options],
        ['id' => 'filter_to_store', 'label' => 'Target Store', 'searchable' => true, 'options' => $to_store_options],
        ['id' => 'filter_product', 'label' => 'Product', 'searchable' => true, 'options' => $product_options],
        ['id' => 'filter_created_by', 'label' => 'Created By', 'searchable' => true, 'options' => $user_options],
    ],
    'date_column' => 'created_at'
];
?>

<div class="app-wrapper">
    <?php include('../includes/sidebar.php'); ?>
    
    <main id="main-content" class="flex-1 lg:ml-64 main-content flex flex-col h-screen min-w-0 transition-all duration-300 bg-[#f8fafc]">        
        <div class="navbar-fixed-top">
            <?php include('../includes/navbar.php'); ?>
        </div>
        
        <div class="content-scroll-area custom-scroll">
            <div class="p-6">
                <?php renderReusableList($config); ?>
            </div>
        </div>
        <?php include('../includes/footer.php'); ?>
    </main>
</div>

<script>
function receiveTransfer(id) {
    Swal.fire({
        title: 'Receive Stock?',
        text: "This will add the items to your current store's inventory.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#059669',
        cancelButtonText: 'Not Now',
        confirmButtonText: 'Yes, Receive!'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Processing...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            fetch('receive', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id=${id}`
            })
            .then(res => res.json())
            .then(data => {
                if(data.status === 200) {
                    Swal.fire('Success!', data.message, 'success').then(() => window.location.reload());
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(err => {
                console.error(err);
                Swal.fire('Error', 'Connection failed', 'error');
            });
        }
    });
}
</script>
