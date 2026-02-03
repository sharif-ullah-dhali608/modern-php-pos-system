<?php
session_start();
include('../config/dbcon.php');
include('../includes/date_filter_helper.php');

// Security Check
if(!isset($_SESSION['auth'])){
    header("Location: /pos/login");
    exit(0);
}

// Filter Parameters
$supplier_id = $_GET['supplier_id'] ?? '';
$date_filter = $_GET['date_filter'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// Build Query
$query = "SELECT pi.*, 
                 s.name as supplier_name,
                 u.name as creator_name,
                 SUM(pi_item.return_quantity) as total_returns
          FROM purchase_info pi
          LEFT JOIN suppliers s ON pi.sup_id = s.id
          LEFT JOIN users u ON pi.created_by = u.id
          LEFT JOIN purchase_item pi_item ON pi.invoice_id = pi_item.invoice_id
          WHERE pi_item.return_quantity > 0";

if(!empty($supplier_id)){
    $sup_id = mysqli_real_escape_string($conn, $supplier_id);
    $query .= " AND pi.sup_id = '$sup_id'";
}

// Apply Date Filter
applyDateFilter($query, 'pi.created_at', $date_filter, $start_date, $end_date);

$query .= " GROUP BY pi.invoice_id ORDER BY pi.created_at DESC";

$query_run = mysqli_query($conn, $query);
$items = [];

// Calculate totals for summary cards
$grand_total_amount = 0;
$grand_total_returns = 0;

if($query_run) {
    while($row = mysqli_fetch_assoc($query_run)) {
        $invoice_id = mysqli_real_escape_string($conn, $row['invoice_id']);
        
        // Calculate totals
        $total_query = "SELECT COALESCE(SUM(item_total), 0) as total_amount FROM purchase_item WHERE invoice_id = '$invoice_id'";
        $total_result = mysqli_query($conn, $total_query);
        $total_data = mysqli_fetch_assoc($total_result);
        $amount = floatval($total_data['total_amount'] ?? 0);
        
        $row['formatted_datetime'] = date('Y-m-d H:i:s', strtotime($row['created_at']));
        $row['supplier_display'] = !empty($row['supplier_name']) ? $row['supplier_name'] : 'No Supplier';
        $row['creator_display'] = !empty($row['creator_name']) ? $row['creator_name'] : 'Your Name';
        $row['amount_display'] = number_format($amount, 2);
        $row['returns_display'] = number_format($row['total_returns'] ?? 0, 2);
        
        $grand_total_amount += $amount;
        $grand_total_returns += floatval($row['total_returns'] ?? 0);
        
        $items[] = $row;
    }
}

// Supplier Filter Options
$sup_opts = [['label' => 'All Suppliers', 'url' => '?supplier_id=', 'active' => empty($supplier_id)]];
$sup_q = mysqli_query($conn, "SELECT id, name FROM suppliers WHERE status=1 ORDER BY name ASC");
while($sup = mysqli_fetch_assoc($sup_q)) {
    // Preserve date params if present
    $url = "?supplier_id={$sup['id']}";
    // Note: reusable_list.php handles merging usually, but explicit is fine or rely on list component smarts.
    // Ideally we want to keep date_filter if set.
    // For now, let's just set the main param, the user can re-apply dates or we rely on the component's smart merging if we refactor options creation logic.
    // But reusable_list.php logic (lines 325-330) attempts to merge existing GET params.
    // So distinct URL params here are good.
    
    $sup_opts[] = [
        'label' => $sup['name'],
        'url' => $url, // reusable_list will merge this with existing params if it starts with ?
        'active' => ($supplier_id == $sup['id'])
    ];
}

$filters = [
    [
        'id' => 'filter_supplier',
        'label' => 'Supplier',
        'searchable' => true,
        'options' => $sup_opts
    ]
];

$list_config = [
    'title' => 'Return List',
    'table_id' => 'returnTable',
    'filters' => $filters,
    'date_column' => 'created_at', // Enables Date Filter Header Button
    'summary_cards' => [
        ['label' => 'Total Return Invoices Amount', 'value' => number_format($grand_total_amount, 2), 'border_color' => 'border-indigo-500'],
        ['label' => 'Total Return Quantity', 'value' => number_format($grand_total_returns, 2), 'border_color' => 'border-orange-500']
    ],
    'columns' => [
        ['key' => 'formatted_datetime', 'label' => 'Datetime', 'sortable' => true],
        ['key' => 'invoice_id', 'label' => 'Invoice Id', 'sortable' => true],
        ['key' => 'supplier_display', 'label' => 'Supplier', 'sortable' => true],
        ['key' => 'creator_display', 'label' => 'Creator', 'sortable' => true],
        ['key' => 'amount_display', 'label' => 'Total Amount', 'sortable' => true, 'class' => 'font-bold'],
        ['key' => 'returns_display', 'label' => 'Return Qty', 'sortable' => true, 'class' => 'font-bold text-orange-600'],
        ['key' => 'actions', 'label' => 'View', 'type' => 'html'] // We will inject custom action button
    ],
    'data' => $items,
    'action_buttons' => ['custom'], // Only show custom buttons (we'll implement View)
    'custom_actions' => function($row) {
        return '<button type="button" onclick="viewReturn(\''.$row['invoice_id'].'\')" class="p-2 text-blue-500 hover:bg-blue-50 rounded transition" title="View">
                    <i class="fas fa-eye"></i>
                </button>';
    }
];

$page_title = "Return List - Velocity POS";
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
            <div id="viewModal" class="fixed inset-0 z-[9999] hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="fixed inset-0 bg-slate-900/70 backdrop-blur-sm transition-opacity" onclick="closeViewModal()"></div>
                <div class="fixed inset-0 z-10 overflow-y-auto">
                    <div class="flex min-h-full items-center justify-center p-2 sm:p-4 text-center">
                        <div class="relative transform overflow-hidden rounded-xl bg-white text-left shadow-2xl transition-all w-full max-w-4xl my-4">
                            <div class="bg-teal-600 px-4 py-3 flex flex-wrap justify-between items-center gap-2 print:hidden">
                                <h3 class="text-sm sm:text-base font-semibold text-white truncate" id="modal-title">
                                    <i class="fas fa-eye mr-2"></i> <span id="viewModalTitle">Return Details</span>
                                </h3>
                                <div class="flex flex-wrap gap-1 sm:gap-2">
                                    <button onclick="printPurchase()" class="p-1.5 sm:p-2 bg-teal-700 text-white rounded hover:bg-teal-800 transition" title="Print">
                                        <i class="fas fa-print"></i>
                                    </button>
                                    <button onclick="closeViewModal()" class="p-1.5 sm:p-2 bg-red-500 text-white rounded hover:bg-red-600 transition" title="Close">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            <div id="viewModalContent" class="overflow-y-auto max-h-[calc(90vh-80px)] p-6">
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
// View Return/Purchase Details via AJAX
function viewReturn(invoiceId) {
    $('#viewModalTitle').text('Return Detail > ' + invoiceId);
    $('#viewModalContent').html('<div class="text-center py-8"><i class="fas fa-spinner fa-spin fa-2x text-teal-600"></i><p class="mt-4 text-slate-600">Loading...</p></div>');
    $('#viewModal').removeClass('hidden');
    
    $.ajax({
        url: '/pos/purchases/save_purchase.php',
        type: 'POST',
        data: { 
            view_purchase_btn: true, 
            invoice_id: invoiceId 
        },
        dataType: 'json',
        success: function(response) {
            if(response.status == 200) {
                $('#viewModalContent').html(response.html);
            } else {
                $('#viewModalContent').html('<div class="text-center py-8 text-red-600">' + response.message + '</div>');
            }
        },
        error: function() {
            $('#viewModalContent').html('<div class="text-center py-8 text-red-600">Error loading details</div>');
        }
    });
}

function closeViewModal() {
    $('#viewModal').addClass('hidden');
}

function printPurchase() {
    var content = document.getElementById('viewModalContent').innerHTML;
    var invoiceId = $('#viewModalTitle').text().split('> ')[1] || 'Return';
    var mywindow = window.open('', 'PRINT', 'height=800,width=1000');
    mywindow.document.write('<html><head><title>Return_' + invoiceId + '</title>');
    mywindow.document.write('<link rel="stylesheet" href="/pos/assets/css/output.css">');
    mywindow.document.write('<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"/>');
    mywindow.document.write('</head><body class="p-4">');
    mywindow.document.write(content);
    mywindow.document.write('</body></html>');
    mywindow.document.close(); 
    mywindow.focus(); 
    setTimeout(function(){ mywindow.print(); mywindow.close(); }, 1500);
}
</script>