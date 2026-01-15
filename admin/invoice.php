<?php
session_start();
include('../config/dbcon.php');

if(!isset($_SESSION['auth'])){
    header("Location: ../signin.php");
    exit(0);
}

$page_title = "Invoices List";
include('../includes/header.php');
include('../includes/reusable_list.php');
?>
<!-- <link rel="stylesheet" href="/pos/assets/css/payment_modal.css"> -->
<link rel="stylesheet" href="/pos/assets/css/pos.css">

<?php

// Filter parameters
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';
$filter_customer = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;

// Fetch Customers for filter dropdown
$customers_list = [];
$cust_result = mysqli_query($conn, "SELECT id, name FROM customers WHERE status = 1 ORDER BY name ASC");
while($c = mysqli_fetch_assoc($cust_result)) $customers_list[] = $c;

// Today's date for filter
$today = date('Y-m-d');

// Fetch Data
// net_amount: Total after returns
// paid_amount: Total from sell_logs (all payment types)
$query = "SELECT si.*, COALESCE(c.name, 'Walking Customer') as customer_name,
          (SELECT SUM(qty_sold - return_item) FROM selling_item WHERE invoice_id = si.invoice_id) as item_count,
          (si.grand_total - IFNULL((SELECT SUM(grand_total) FROM selling_info WHERE ref_invoice_id = si.invoice_id AND inv_type = 'return'), 0)) as net_amount,
          IFNULL((SELECT SUM(amount) FROM sell_logs WHERE ref_invoice_id = si.invoice_id AND type IN ('payment', 'full_payment', 'partial_payment', 'due_paid')), 0) as paid_amount
          FROM selling_info si 
          LEFT JOIN customers c ON si.customer_id = c.id 
          WHERE si.inv_type = 'sale' ";

// Apply filters
if($filter_customer > 0) {
    $query .= " AND si.customer_id = $filter_customer ";
}
if($filter_status == 'today') {
    $query .= " AND DATE(si.created_at) = '$today' ";
}

$query .= "HAVING item_count > 0 ";

// Filter by payment status after HAVING
if($filter_status == 'due') {
    $query .= " AND (net_amount - paid_amount) > 0.01 ";
} elseif($filter_status == 'paid') {
    $query .= " AND (net_amount - paid_amount) <= 0.01 ";
}

$query .= "ORDER BY si.created_at DESC";

$result = mysqli_query($conn, $query);

// Fetch Payment Methods
$pm_result = mysqli_query($conn, "SELECT id, name FROM payment_methods WHERE status = 1");
$payment_methods = [];
while($pm = mysqli_fetch_assoc($pm_result)) $payment_methods[] = $pm;

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    // Calculate current due
    $row['net_amount'] = round($row['net_amount'], 2);
    $row['paid_amount'] = round($row['paid_amount'], 2);
    $row['current_due'] = max(0, round($row['net_amount'] - $row['paid_amount'], 2));
    
    // Status Logic: If net_amount equals paid_amount, it's paid.
    // If net_amount > paid_amount, it's due.
    $display_status = ($row['paid_amount'] >= $row['net_amount'] - 0.01) ? 'paid' : 'due';
    $row['payment_status'] = $display_status;
    
    // Pay Button
    if($display_status != 'paid') {
         // JavaScript Triggered Pay Icon
         $row['pay_btn'] = '<button onclick="openInvoicePaymentModal(\''.$row['invoice_id'].'\', '.$row['current_due'].', '.$row['info_id'].', '.$row['store_id'].', '.($row['customer_id'] ?: '0').')" class="inline-block p-2 text-emerald-600 hover:text-emerald-800 bg-emerald-50 hover:bg-emerald-100 rounded-lg transition" title="Pay"><i class="fas fa-money-bill"></i></button>';
    } else {
        $row['pay_btn'] = '<span class="text-slate-400">-</span>';
    }
    
    // Return Button
    $row['return_btn'] = '<a href="/pos/admin/sell_return_create.php?id='.$row['invoice_id'].'" class="inline-flex items-center justify-center w-8 h-8 bg-amber-500 hover:bg-amber-600 text-white rounded shadow-sm transition" title="Return"><i class="fas fa-undo"></i></a>';
    
    // Item Count formatting
    $row['item_count_formatted'] = number_format($row['item_count'] ?? 0, 0);
    $row['amount_formatted'] = number_format($row['net_amount'], 2);

    $data[] = $row;
}

// Calculate Summary Totals
$summary_grand_total = 0;
$summary_total_paid = 0;
$summary_total_due = 0;
$summary_total_invoices = count($data);

foreach($data as $invoice) {
    $summary_grand_total += $invoice['net_amount'];
    $summary_total_paid += $invoice['paid_amount'];
    $summary_total_due += $invoice['current_due'];
}

// Build filter options
$status_filter_options = [
    ['label' => 'Today Invoice', 'url' => '?status=today' . ($filter_customer ? '&customer_id='.$filter_customer : ''), 'active' => $filter_status == 'today'],
    ['label' => 'All Invoice', 'url' => '?status=all' . ($filter_customer ? '&customer_id='.$filter_customer : ''), 'active' => $filter_status == 'all'],
    ['label' => 'Due Invoice', 'url' => '?status=due' . ($filter_customer ? '&customer_id='.$filter_customer : ''), 'active' => $filter_status == 'due'],
    ['label' => 'Paid Invoice', 'url' => '?status=paid' . ($filter_customer ? '&customer_id='.$filter_customer : ''), 'active' => $filter_status == 'paid'],
];

$customer_filter_options = [['label' => 'All Customers', 'url' => '?status='.$filter_status, 'active' => $filter_customer == 0]];
foreach($customers_list as $cust) {
    $customer_filter_options[] = [
        'label' => $cust['name'],
        'url' => '?status='.$filter_status.'&customer_id='.$cust['id'],
        'active' => $filter_customer == $cust['id']
    ];
}

// Prepare Config
$config = [
    'title' => 'Invoices List',
    'table_id' => 'invoice_table',
    'add_url' => '/pos/pos', 
    'edit_url' => '/pos/admin/edit',
    'delete_url' => '/pos/admin/delete',
    'view_url' => '/pos/invoice/view',
    'primary_key' => 'info_id',
    'name_field' => 'invoice_id',
    'data' => $data,
    
    // New: Extra Buttons
    'extra_buttons' => [
        ['label' => 'Pay All', 'icon' => 'fas fa-dollar-sign', 'onclick' => 'payAllDue()', 'class' => 'inline-flex items-center gap-2 px-5 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg shadow transition-all']
    ],
    
    // New: Filters
    'filters' => [
        ['id' => 'filter_status', 'label' => 'Filter', 'options' => $status_filter_options],
        ['id' => 'filter_customer', 'label' => 'Customer', 'searchable' => true, 'options' => $customer_filter_options]
    ],
    
    // New: Summary Cards
    'summary_cards' => [
        ['label' => 'Grand Total', 'value' => number_format($summary_grand_total, 2), 'border_color' => 'border-teal-500'],
        ['label' => 'Total Paid', 'value' => number_format($summary_total_paid, 2), 'border_color' => 'border-emerald-500'],
        ['label' => 'Total Due', 'value' => number_format($summary_total_due, 2), 'border_color' => 'border-amber-500'],
        ['label' => 'Total Invoices', 'value' => number_format($summary_total_invoices), 'border_color' => 'border-red-500']
    ],
    
    'columns' => [
        ['label' => 'Invoice Id', 'key' => 'invoice_id'],
        ['label' => 'Date Time', 'key' => 'created_at'],
        ['label' => 'Customer Name', 'key' => 'customer_name'],
        ['label' => 'Items', 'key' => 'item_count_formatted'],
        ['label' => 'Amount', 'key' => 'amount_formatted'],
        ['label' => 'Status', 'key' => 'payment_status', 'type' => 'badge', 'badge_class' => 'bg-green-100 text-green-800'],
        ['label' => 'Current Due', 'key' => 'current_due', 'type' => 'number'],
        ['label' => 'Pay', 'key' => 'pay_btn', 'type' => 'html'], 
        ['label' => 'Return', 'key' => 'return_btn', 'type' => 'html'],
        ['label' => 'Actions', 'key' => 'actions', 'type' => 'actions'] 
    ]
];
// ... Columns Config ...
// ...
// ['label' => 'Pay', 'key' => 'pay_btn', 'type' => 'html'],
// ['label' => 'Return', 'key' => 'return_btn', 'type' => 'html'],
// ...

?>

<div class="app-wrapper">
    <?php include('../includes/sidebar.php'); ?>

    <main id="main-content" class="lg:ml-64 flex flex-col h-screen">
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

<!-- Hidden Balance Fields (Legacy Compatibility) -->
<input type="hidden" id="customer_opening_balance" value="0">
<input type="hidden" id="customer_giftcard_balance" value="0">

<?php 
// Prepare data for payment modal - reset the result pointer for the loop
$payment_methods_result = mysqli_query($conn, "SELECT id, name, code FROM payment_methods WHERE status = 1 ORDER BY sort_order ASC");
include('../includes/payment_modal.php'); 
include('../includes/invoice_modal.php'); 
?>

<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
<script src="/pos/assets/js/invoice_modal.js"></script>
<script src="/pos/assets/js/payment_logic.js"></script>
<script>
// Global variables for submission
let currentInvoiceId = '';
let currentInfoId = '';
let currentStoreId = '';
let currentCustomerId = '';

function openInvoicePaymentModal(invoiceId, dueAmount, infoId, storeId, customerId) {
    currentInvoiceId = invoiceId;
    currentInfoId = infoId;
    currentStoreId = storeId;
    currentCustomerId = customerId;

    // Call shared initialization
    window.openPaymentModal({
        totalPayable: parseFloat(dueAmount),
        previousDue: 0, // In invoice list, we are paying a specific invoice's current due
        customerId: customerId,
        customerName: `Invoice ${invoiceId}`, // Could fetch name from row if needed
        onSubmit: submitInvoicePayment
    });
    
    // Default to Full Payment view
    if(window.setPaymentType) window.setPaymentType('full');
}

function submitInvoicePayment(paymentData) {
    const formData = new FormData();
    formData.append('action', 'register_payment');
    formData.append('invoice_id', currentInvoiceId);
    formData.append('info_id', currentInfoId);
    formData.append('store_id', currentStoreId);
    formData.append('customer_id', currentCustomerId);
    formData.append('amount_received', paymentData.amountReceived);
    formData.append('payment_method_id', paymentData.selectedPaymentMethod || '');
    formData.append('payments', JSON.stringify(paymentData.appliedPayments));
    formData.append('note', paymentData.note);

    const btn = document.querySelector('.complete-btn');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    btn.disabled = true;

    fetch('/pos/pos/save_sale.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            // Close payment modal
            closeModal('paymentModal');
            
            // Show Invoice Modal with the payment data
            window.openInvoiceModal({
                invoiceId: data.invoice_id || currentInvoiceId,
                store: {
                    name: data.store_name || 'Modern POS',
                    address: data.store_address || '',
                    city: data.store_city || '',
                    phone: data.store_phone || '',
                    email: data.store_email || ''
                },
                customer: {
                    name: data.customer_name || 'Customer',
                    phone: data.customer_phone || ''
                },
                items: data.items || [],
                totals: {
                    subtotal: parseFloat(data.subtotal) || 0,
                    discount: parseFloat(data.discount) || 0,
                    tax: parseFloat(data.tax) || 0,
                    shipping: parseFloat(data.shipping) || 0,
                    grandTotal: parseFloat(data.grand_total) || 0,
                    paid: parseFloat(data.paid) || 0,
                    due: parseFloat(data.due) || 0,
                    previousDue: 0,
                    totalDue: parseFloat(data.due) || 0
                },
                paymentMethod: 'Payment',
                onClose: () => window.location.reload()
            });
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: data.message });
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}

// Pay All Due Invoices
function payAllDue() {
    const totalDue = <?= $summary_total_due; ?>;
    
    if(totalDue <= 0) {
        Swal.fire({
            icon: 'info',
            title: 'No Due Amount',
            text: 'All invoices are already paid!',
            confirmButtonColor: '#059669'
        });
        return;
    }
    
    Swal.fire({
        title: 'Pay All Due Invoices?',
        html: `<div class="text-left">
            <p class="mb-2">Total Due Amount: <strong class="text-red-600">৳${totalDue.toLocaleString('en-US', {minimumFractionDigits: 2})}</strong></p>
            <p class="text-sm text-slate-500">This will open individual payment modals for each due invoice.</p>
        </div>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Proceed',
        confirmButtonColor: '#059669',
        cancelButtonColor: '#64748b'
    }).then((result) => {
        if(result.isConfirmed) {
            // Find first due invoice and open modal
            const firstDueBtn = document.querySelector('button[onclick*="openInvoicePaymentModal"]');
            if(firstDueBtn) {
                firstDueBtn.click();
            } else {
                Swal.fire({
                    icon: 'info',
                    title: 'No Due Invoice',
                    text: 'All visible invoices are paid.',
                    confirmButtonColor: '#059669'
                });
            }
        }
    });
}
</script>
