<?php
session_start();
include('../config/dbcon.php');
include('../includes/date_filter_helper.php');

if (!isset($_SESSION['auth'])) {
    header("Location: /pos/login");
    exit(0);
}

$page_title = "Transfer List";
include('../includes/header.php');
include('../includes/reusable_list.php');

$user_id = $_SESSION['auth_user']['user_id'];
$user_role = $_SESSION['auth_user']['role_as'];

// Filters
$filter_status = $_GET['status'] ?? 'all';
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
$users_q = mysqli_query($conn, "SELECT id, name FROM users ORDER BY name ASC");
$user_options = [['label' => 'All Users', 'url' => '?created_by=all', 'active' => $filter_created_by == 'all']];
while($u = mysqli_fetch_assoc($users_q)) {
    $user_options[] = ['label' => $u['name'], 'url' => '?created_by='.$u['id'], 'active' => $filter_created_by == $u['id']];
}

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
          WHERE 1=1 ";

// If not admin, only show transfers related to their stores
if ($user_role !== 'admin') {
    $query .= " AND (t.from_store_id IN (SELECT store_id FROM user_store_map WHERE user_id = '$user_id') 
                 OR t.to_store_id IN (SELECT store_id FROM user_store_map WHERE user_id = '$user_id')) ";
}

if ($filter_status !== 'all') {
    $query .= " AND t.status = '$filter_status' ";
}
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

applyDateFilter($query, 't.created_at', $date_filter, $start_date, $end_date);

$query .= " ORDER BY t.created_at DESC";

$result = mysqli_query($conn, $query);
$data = [];

while ($row = mysqli_fetch_assoc($result)) {
    // Status Badge
    $status = $row['status'];
    $badge_class = 'bg-slate-100 text-slate-600';
    if ($status == 'Sent') $badge_class = 'bg-blue-100 text-blue-700';
    if ($status == 'Received') $badge_class = 'bg-emerald-100 text-emerald-700';
    if ($status == 'Cancelled') $badge_class = 'bg-rose-100 text-rose-700';
    
    $row['status_badge'] = "<span class='status-badge $badge_class'>$status</span>";
    
    // Attachment link (Image Preview)
    if ($row['attachment']) {
        $ext = strtolower(pathinfo($row['attachment'], PATHINFO_EXTENSION));
        $is_image = in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg']);
        
        if ($is_image) {
            $row['attachment_link'] = "
                <a href='/pos/{$row['attachment']}' target='_blank' class='group relative block w-10 h-10'>
                    <img src='/pos/{$row['attachment']}' class='w-10 h-10 rounded-lg object-cover border border-slate-200 shadow-sm transition-transform group-hover:scale-110'>
                    <div class='absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 transition-opacity rounded-lg flex items-center justify-center'>
                        <i class='fas fa-search-plus text-white text-xs'></i>
                    </div>
                </a>";
        } else {
            $row['attachment_link'] = "
                <a href='/pos/{$row['attachment']}' target='_blank' class='w-10 h-10 rounded-lg bg-teal-50 border border-teal-100 flex items-center justify-center text-teal-600 hover:bg-teal-600 hover:text-white transition-all shadow-sm'>
                    <i class='fas fa-file-pdf'></i>
                </a>";
        }
    } else {
        $row['attachment_link'] = "<span class='text-slate-300'>-</span>";
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
    'title' => 'Transfer List',
    'table_id' => 'transfer_table',
    'add_url' => '/pos/transfer/stock_transfer',
    'primary_key' => 'id',
    'data' => $data,
    'filters' => [
        ['id' => 'filter_status', 'label' => 'Status', 'options' => [
            ['label' => 'All', 'url' => '?status=all', 'active' => $filter_status == 'all'],
            ['label' => 'Sent', 'url' => '?status=Sent', 'active' => $filter_status == 'Sent'],
            ['label' => 'Received', 'url' => '?status=Received', 'active' => $filter_status == 'Received'],
            ['label' => 'Cancelled', 'url' => '?status=Cancelled', 'active' => $filter_status == 'Cancelled'],
        ]],
        ['id' => 'filter_from_store', 'label' => 'Source Store', 'searchable' => true, 'options' => $store_options],
        ['id' => 'filter_to_store', 'label' => 'Target Store', 'searchable' => true, 'options' => $to_store_options],
        ['id' => 'filter_product', 'label' => 'Product', 'searchable' => true, 'options' => $product_options],
        ['id' => 'filter_created_by', 'label' => 'Created By', 'searchable' => true, 'options' => $user_options],
    ],
    'columns' => [
        ['label' => 'File', 'key' => 'attachment_link', 'type' => 'html'],
        ['label' => 'Ref No', 'key' => 'ref_no'],
        ['label' => 'Products', 'key' => 'product_display', 'type' => 'html'],
        ['label' => 'Date', 'key' => 'created_at'],
        ['label' => 'From Store', 'key' => 'from_store'],
        ['label' => 'To Store', 'key' => 'to_store'],
        ['label' => 'Items', 'key' => 'total_item'],
        ['label' => 'Qty', 'key' => 'total_quantity'],
        ['label' => 'Created By', 'key' => 'creator_name'],
        ['label' => 'Status', 'key' => 'status_badge', 'type' => 'html'],
        ['label' => 'Actions', 'key' => 'actions', 'type' => 'actions']
    ],
    'date_column' => 'created_at',
    'view_url' => 'view_transfer',
    'view_type' => 'modal',
    'action_buttons' => ['view', 'custom'],
    'custom_actions' => function($row) {
        $btns = '';
        if ($row['status'] === 'Sent') {
            $btns .= "
                <button type='button' onclick='confirmCancel({$row['id']}, \"{$row['ref_no']}\")' 
                        class='p-2 text-rose-500 hover:bg-rose-50 rounded transition' title='Cancel Transfer'>
                    <i class='fas fa-times-circle'></i>
                </button>";
        }
        return $btns;
    }
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
            <?php include('../includes/footer.php'); ?>
        </div>
    </main>
</div>

<script>
function openViewModal(id, url) {
    Swal.fire({
        title: 'Fetching Transfer Details...',
        didOpen: () => { Swal.showLoading(); }
    });

    fetch(`get_transfer_details?id=${id}`)
    .then(res => res.json())
    .then(data => {
        if(data.status === 200) {
            const t = data.transfer;
            const items = data.items;
            
            let itemsHtml = `
                <div class="mt-4 overflow-hidden rounded-2xl border border-slate-100 shadow-sm">
                    <table class="w-full text-left border-collapse bg-white">
                        <thead>
                            <tr class="bg-slate-50 text-[10px] uppercase font-black tracking-widest text-slate-500">
                                <th class="p-4 border-b border-slate-100">Product</th>
                                <th class="p-4 border-b border-slate-100 text-center">Qty</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">`;
            
            items.forEach(item => {
                itemsHtml += `
                    <tr>
                        <td class="p-4 text-sm font-bold text-slate-700">${item.product_name}</td>
                        <td class="p-4 text-sm font-black text-teal-600 text-center">${item.quantity}</td>
                    </tr>`;
            });
            
            itemsHtml += `</tbody></table></div>`;

            let footerHtml = '';
            if(t.note) {
                footerHtml = `<div class="mt-6 p-4 bg-amber-50 rounded-2xl border border-amber-100 text-left">
                    <span class="text-[10px] font-black uppercase text-amber-600 tracking-wider block mb-1">Note</span>
                    <p class="text-sm text-amber-800 font-medium">${t.note}</p>
                </div>`;
            }

            Swal.fire({
                title: `<div class="text-left"><span class="text-3xl font-black text-slate-900">Transfer #${t.ref_no}</span></div>`,
                html: `
                    <div class="space-y-6">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="p-4 bg-slate-50 rounded-2xl border border-white text-left shadow-sm">
                                <span class="text-[10px] font-black uppercase text-slate-400 tracking-widest block mb-1">Source Store</span>
                                <span class="text-sm font-bold text-slate-700">${t.from_store}</span>
                            </div>
                            <div class="p-4 bg-teal-50 rounded-2xl border border-white text-left shadow-sm">
                                <span class="text-[10px] font-black uppercase text-teal-500 tracking-widest block mb-1">Target Store</span>
                                <span class="text-sm font-bold text-teal-700">${t.to_store}</span>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between px-2 text-slate-400 font-bold text-[10px] uppercase tracking-widest">
                            <span>Status: ${t.status}</span>
                            <span>Date: ${t.created_at}</span>
                        </div>

                        ${itemsHtml}
                        ${footerHtml}
                    </div>
                `,
                width: '600px',
                showConfirmButton: false,
                showCloseButton: true,
                customClass: {
                    container: 'premium-swal-container',
                    popup: 'premium-swal-popup rounded-[3rem]'
                }
            });
        } else {
            Swal.fire('Error', data.message || 'Details not found', 'error');
        }
    })
    .catch(err => {
        console.error(err);
        Swal.fire('Error', 'Failed to fetch data', 'error');
    });
}

function confirmCancel(id, ref) {
    Swal.fire({
        title: 'Cancel Transfer?',
        text: `Are you sure you want to cancel transfer ${ref}? This will reverse the stock movement and cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#f43f5e',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Yes, Cancel it!',
        cancelButtonText: 'No, Keep it'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Cancelling...',
                didOpen: () => { Swal.showLoading(); }
            });

            fetch('cancel_transfer', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id=${id}`
            })
            .then(res => res.json())
            .then(data => {
                if(data.status === 200) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Cancelled!',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(err => {
                console.error(err);
                Swal.fire('Error', 'Network error or request failed', 'error');
            });
        }
    });
}
</script>

<style>
.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
}
.premium-swal-popup {
    padding: 3rem !important;
}
</style>
