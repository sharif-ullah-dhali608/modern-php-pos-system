<?php
session_start();
include('../config/dbcon.php');
include('../includes/date_filter_helper.php');

if(!isset($_SESSION['auth'])){
    header("Location: /pos/login");
    exit(0);
}

// Filter Parameters
$supplier_id = $_GET['supplier_id'] ?? '';
$creator_id  = $_GET['created_by'] ?? '';
$source_type = $_GET['source'] ?? '';
$date_filter = $_GET['date_filter'] ?? '';
$start_date  = $_GET['start_date'] ?? '';
$end_date    = $_GET['end_date'] ?? '';

// Build Query
$query = "SELECT pl.*, 
                 s.name as supplier_name,
                 pm.name as pmethod_name,
                 u.name as creator_name
          FROM purchase_logs pl
          LEFT JOIN suppliers s ON pl.sup_id = s.id
          LEFT JOIN payment_methods pm ON pl.pmethod_id = pm.id
          LEFT JOIN users u ON pl.created_by = u.id
          WHERE 1=1";

if(!empty($supplier_id)) {
    $sup = mysqli_real_escape_string($conn, $supplier_id);
    $query .= " AND pl.sup_id = '$sup'";
}
if(!empty($creator_id)) {
    $uid = mysqli_real_escape_string($conn, $creator_id);
    $query .= " AND pl.created_by = '$uid'";
}
if(!empty($source_type)) {
    $src = mysqli_real_escape_string($conn, $source_type);
    if($src == 'return') {
        $query .= " AND pl.type = 'return'";
    } elseif ($src == 'purchase') {
        $query .= " AND pl.type = 'purchase'";
    } elseif ($src == 'payment') {
        // Logically payment is a purchase with payment, or separate entry if they have payment logs.
        $query .= " AND (pl.type = 'purchase' AND (pl.description LIKE '%paid%' OR pl.pmethod_id IS NOT NULL AND pl.pmethod_id != 0))";
    }
}

// Apply Date Filter
applyDateFilter($query, 'pl.created_at', $date_filter, $start_date, $end_date);

$query .= " ORDER BY pl.created_at DESC";

$query_run = mysqli_query($conn, $query);
$items = [];
$total_amount = 0;

if($query_run) {
    while($row = mysqli_fetch_assoc($query_run)) {
        // Formatting
        $row['formatted_datetime'] = date('d M Y, h:i A', strtotime($row['created_at']));
        $row['formatted_amount'] = number_format($row['amount'], 2);
        
        // Logical Chips/Badges
        $type = strtolower($row['type']);
        $desc = strtolower($row['description']);
        
        // Define Source Badge
        if($type == 'return') {
            $row['source_badge'] = '<span class="inline-flex px-2 py-1 rounded text-[10px] font-black uppercase tracking-wider bg-orange-100 text-orange-700 border border-orange-200">Returned</span>';
        } elseif(strpos($desc, 'edit') !== false || strpos($desc, 'updated') !== false) {
            $row['source_badge'] = '<span class="inline-flex px-2 py-1 rounded text-[10px] font-black uppercase tracking-wider bg-blue-100 text-blue-700 border border-blue-200">Edited</span>';
        } elseif($type == 'purchase' && (strpos($desc, 'paid') !== false || !empty($row['pmethod_id']))) {
            $row['source_badge'] = '<span class="inline-flex px-2 py-1 rounded text-[10px] font-black uppercase tracking-wider bg-emerald-100 text-emerald-700 border border-emerald-200">Payment</span>';
        } else {
            $row['source_badge'] = '<span class="inline-flex px-2 py-1 rounded text-[10px] font-black uppercase tracking-wider bg-slate-100 text-slate-700 border border-slate-200">Added</span>';
        }
        
        // Ref Invoice Styling
        $row['ref_link'] = '<span class="text-xs font-black text-indigo-600 font-mono italic">#'.$row['ref_invoice_id'].'</span>';

        $row['supplier_display'] = !empty($row['supplier_name']) ? $row['supplier_name'] : 'N/A';
        $row['pmethod_display'] = !empty($row['pmethod_name']) ? '<span class="uppercase font-bold text-xs">'.$row['pmethod_name'].'</span>' : '<span class="text-slate-400 text-xs">N/A</span>';
        
        $items[] = $row;
        $total_amount += $row['amount'];
    }
}

// ------ Build Filters ------

// 1. Source Filter
$source_opts = [
    ['label' => 'All Sources', 'url' => '?source=', 'active' => empty($source_type)],
    ['label' => 'Purchases', 'url' => '?source=purchase', 'active' => $source_type == 'purchase'],
    ['label' => 'Returns', 'url' => '?source=return', 'active' => $source_type == 'return'],
    ['label' => 'Payments', 'url' => '?source=payment', 'active' => $source_type == 'payment'],
];

// 2. Supplier Filter
$sup_opts = [['label' => 'All Suppliers', 'url' => '?supplier_id=', 'active' => empty($supplier_id)]];
$sup_q = mysqli_query($conn, "SELECT id, name FROM suppliers WHERE status=1 ORDER BY name ASC");
while($s = mysqli_fetch_assoc($sup_q)){
    $sup_opts[] = [
        'label' => $s['name'],
        'url' => "?supplier_id={$s['id']}",
        'active' => ($supplier_id == $s['id'])
    ];
}

// 3. User Filter
$user_opts = [['label' => 'All Users', 'url' => '?created_by=', 'active' => empty($creator_id)]];
$user_q = mysqli_query($conn, "SELECT id, name FROM users WHERE status=1 ORDER BY name ASC");
while($u = mysqli_fetch_assoc($user_q)){
    $user_opts[] = [
        'label' => $u['name'],
        'url' => "?created_by={$u['id']}",
        'active' => ($creator_id == $u['id'])
    ];
}

$filters = [
    ['id' => 'f_source', 'label' => 'Source', 'options' => $source_opts],
    ['id' => 'f_supplier', 'label' => 'Supplier', 'searchable' => true, 'options' => $sup_opts],
    ['id' => 'f_user', 'label' => 'User', 'searchable' => true, 'options' => $user_opts],
];

// List Configuration
$list_config = [
    'title' => 'Purchase Activity Logs',
    'table_id' => 'logTable',
    'filters' => $filters,
    'date_column' => 'created_at',
    'summary_cards' => [
        ['label' => 'Total Transactions Amount', 'value' => number_format($total_amount, 2), 'border_color' => 'border-indigo-500']
    ],
    'columns' => [
        ['key' => 'formatted_datetime', 'label' => 'Date & Time', 'sortable' => true],
        ['key' => 'source_badge', 'label' => 'Source', 'type' => 'html', 'align' => 'center'],
        ['key' => 'ref_link', 'label' => 'Reference Invoice', 'type' => 'html'],
        ['key' => 'supplier_display', 'label' => 'Supplier'],
        ['key' => 'pmethod_display', 'label' => 'Method', 'type' => 'html'],
        ['key' => 'formatted_amount', 'label' => 'Amount', 'class' => 'font-bold'],
        ['key' => 'custom', 'label' => 'Action', 'type' => 'html']
    ],
    'data' => $items,
    'action_buttons' => ['custom'],
    'custom_actions' => function($row) {
        return '<button onclick="viewLogDetail(\''.$row['ref_invoice_id'].'\')" class="p-2 text-indigo-600 hover:bg-indigo-50 rounded transition shadow-sm border border-indigo-100" title="View Detail">
                    <i class="fas fa-eye"></i>
                </button>';
    }
];

$page_title = "Purchase Activity Logs - Velocity POS";
include('../includes/header.php');
?>

<div class="app-wrapper">
    <?php include('../includes/sidebar.php'); ?>
    
    <main id="main-content" class="flex-1 lg:ml-64 flex flex-col h-screen min-w-0 transition-all duration-300">
        <div class="navbar-fixed-top">
            <?php include('../includes/navbar.php'); ?>
        </div>
        
        <div class="content-scroll-area custom-scroll h-full overflow-y-auto bg-slate-50/50">
            <div class="p-6 max-w-full mx-auto w-full">
                <?php 
                include('../includes/reusable_list.php'); 
                renderReusableList($list_config); 
                ?>
            </div>
            
            <!-- View Modal -->
            <div id="viewModal" class="fixed inset-0 z-[9999] hidden" role="dialog" aria-modal="true">
                <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity" onclick="closeViewModal()"></div>
                <div class="fixed inset-0 z-10 overflow-y-auto">
                    <div class="flex min-h-full items-center justify-center p-4">
                        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-5xl overflow-hidden border border-slate-200 transform transition-all">
                            <div class="bg-indigo-600 px-6 py-4 flex justify-between items-center text-white">
                                <h3 class="text-sm font-black uppercase tracking-widest flex items-center gap-3">
                                    <i class="fas fa-file-invoice"></i> <span id="viewModalTitle">Activity Detail</span>
                                </h3>
                                <button onclick="closeViewModal()" class="w-8 h-8 rounded-full bg-indigo-500/50 flex items-center justify-center hover:bg-red-500 transition-all">
                                    <i class="fas fa-times text-xs"></i>
                                </button>
                            </div>
                            <div id="viewModalContent" class="p-8 max-h-[85vh] overflow-y-auto custom-scroll bg-white">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php include('../includes/footer.php'); ?>
    </main>
</div>

<script>
function viewLogDetail(invoiceId) {
    $('#viewModalTitle').text('Purchase Detail > ' + invoiceId);
    $('#viewModalContent').html('<div class="text-center py-20"><i class="fas fa-circle-notch fa-spin fa-3x text-indigo-600 mb-4"></i><p class="text-slate-500 font-bold tracking-tight">Fetching Secure Data...</p></div>');
    $('#viewModal').removeClass('hidden'); 
    
    $.ajax({
        url: '/pos/purchases/save_purchase.php',
        type: 'POST',
        data: { view_purchase_btn: true, invoice_id: invoiceId },
        dataType: 'json',
        success: function(response) {
            if(response.status == 200) {
                $('#viewModalContent').html(response.html);
            } else {
                $('#viewModalContent').html('<div class="p-10 text-center text-red-500 font-bold bg-red-50 rounded-2xl"><i class="fas fa-exclamation-triangle text-2xl mb-2"></i><br>Error: ' + response.message + '</div>');
            }
        },
        error: function() {
            $('#viewModalContent').html('<div class="p-10 text-center text-red-500 font-bold bg-red-50 rounded-2xl">Could not connect to server.</div>');
        }
    });
}
function closeViewModal() { $('#viewModal').addClass('hidden'); }
</script>