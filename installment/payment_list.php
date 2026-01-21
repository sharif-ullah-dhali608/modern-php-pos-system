<?php
session_start();
$base_path = '../'; 
include($base_path . 'config/dbcon.php');
include($base_path . 'includes/date_filter_helper.php');

// Security Check
if(!isset($_SESSION['auth'])){
    header("Location: /pos/login");
    exit(0);
}

// Filter parameters
$filter_customer = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;
$filter = $_GET['filter'] ?? 'list';
$title = "Installment Payments";
$where = "WHERE 1=1";

// Fetch Customers for filter dropdown
$customers_list = [];
$cust_result = mysqli_query($conn, "SELECT id, name FROM customers WHERE status = 1 ORDER BY name ASC");
while($c = mysqli_fetch_assoc($cust_result)) $customers_list[] = $c;

// Date filter parameters
$date_filter = $_GET['date_filter'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

if($filter === 'today') {
    $where .= " AND DATE(ip.payment_date) = CURDATE() AND ip.payment_status = 'due'";
    $title = "Payments Due Today";
} elseif($filter === 'expired') {
    $where .= " AND DATE(ip.payment_date) < CURDATE() AND ip.payment_status = 'due'";
    $title = "Expired Payments";
} elseif($filter === 'all') {
    $where .= " AND ip.payment_status = 'due'";
    $title = "All Due Payments";
} else {
    $title = "Installment Payment List";
}

// Fetch Installment Payments
$query = "SELECT ip.*, si.customer_id, c.name as customer_name, c.mobile as customer_mobile
          FROM installment_payments ip
          LEFT JOIN selling_info si ON ip.invoice_id = si.invoice_id
          LEFT JOIN customers c ON si.customer_id = c.id
          $where";

// Apply Customer Filter
if($filter_customer > 0) {
    $query .= " AND si.customer_id = $filter_customer ";
}

// Apply date filter
applyDateFilter($query, 'ip.payment_date', $date_filter, $start_date, $end_date);

$query .= " ORDER BY ip.payment_date ASC";

$query_run = mysqli_query($conn, $query);
$items = [];

if($query_run) {
    while($row = mysqli_fetch_assoc($query_run)) {
        $row['formatted_payable'] = number_format($row['payable'], 2);
        $row['formatted_capital'] = number_format($row['capital'], 2);
        $row['formatted_interest'] = number_format($row['interest'], 2);
        $row['formatted_paid'] = number_format($row['paid'], 2);
        $row['formatted_due'] = number_format($row['due'], 2);
        $row['formatted_date'] = date('d M, Y', strtotime($row['payment_date']));
        $items[] = $row;
    }
}

// Build customer filter options
$customer_filter_options = [['label' => 'All Customers', 'url' => '?filter='.$filter.'&customer_id=0', 'active' => $filter_customer == 0]];
foreach($customers_list as $cust) {
    $customer_filter_options[] = [
        'label' => $cust['name'],
        'url' => '?filter='.$filter.'&customer_id='.$cust['id'],
        'active' => $filter_customer == $cust['id']
    ];
}

$list_config = [
    'title' => $title,
    'add_url' => '#',
    'table_id' => 'paymentTable',
    'action_buttons' => ['custom'],
    'custom_actions' => function($row) {
        if ($row['payment_status'] === 'due') {
            $payment_id = $row['id'];
            $invoice_id = $row['invoice_id'];
            $due_amount = $row['due'];
            $customer_id = $row['customer_id'] ?? 0;
            $customer_name = htmlspecialchars($row['customer_name'] ?? 'Customer', ENT_QUOTES);
            
            return '<button onclick="openInstallmentPaymentModal(\''.$invoice_id.'\', '.$payment_id.', '.$due_amount.', '.$customer_id.', \''.$customer_name.'\')" class="p-2 text-green-600 hover:bg-green-50 rounded transition" title="Pay">
                        <i class="fas fa-money-bill-wave"></i>
                    </button>';
        }
        return '';
    },
    'filters' => [
        ['id' => 'filter_customer', 'label' => 'Customer', 'searchable' => true, 'options' => $customer_filter_options]
    ],
    'columns' => [
        ['key' => 'invoice_id', 'label' => 'Invoice ID', 'sortable' => true],
        ['key' => 'customer_name', 'label' => 'Customer', 'sortable' => true],
        ['key' => 'formatted_date', 'label' => 'Due Date', 'sortable' => true],
        ['key' => 'formatted_capital', 'label' => 'Capital', 'sortable' => true],
        ['key' => 'formatted_interest', 'label' => 'Interest', 'sortable' => true],
        ['key' => 'formatted_payable', 'label' => 'Total Payable', 'sortable' => true],
        ['key' => 'formatted_paid', 'label' => 'Paid', 'sortable' => true],
        ['key' => 'formatted_due', 'label' => 'Due', 'badge_class' => 'bg-red-500/20 text-red-400', 'type' => 'badge'],
        ['key' => 'payment_status', 'label' => 'Status', 'type' => 'badge', 'badge_class' => 'bg-orange-500/20 text-orange-400'],
        ['key' => 'actions', 'label' => 'Actions', 'type' => 'actions']
    ],
    'data' => $items,
    'primary_key' => 'id',
    'name_field' => 'invoice_id',
    'date_column' => 'ip.payment_date'
];

$page_title = "$title - Velocity POS";

include($base_path . 'includes/header.php');
?>
<link rel="stylesheet" href="/pos/assets/css/pos.css">


<div class="app-wrapper">
    <?php include($base_path . 'includes/sidebar.php'); ?>
    
    <main id="main-content" class="flex-1 lg:ml-64 flex flex-col h-screen min-w-0 transition-all duration-300">
        <div class="navbar-fixed-top">
            <?php include($base_path . 'includes/navbar.php'); ?>
        </div>
        
        <div class="content-scroll-area custom-scroll h-full overflow-y-auto">
            <div class="p-6">
                <?php 
                include($base_path . 'includes/reusable_list.php'); 
                renderReusableList($list_config); 
                ?>
            </div>
            <?php include($base_path . 'includes/footer.php'); ?>
        </div>
    </main>
</div>

<?php 
// Include payment modal
$payment_methods_result = mysqli_query($conn, "SELECT id, name, code FROM payment_methods WHERE status = 1 ORDER BY sort_order ASC");
include($base_path . 'includes/payment_modal.php');
include($base_path . 'includes/invoice_modal.php'); 
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
<script src="/pos/assets/js/invoice_modal.js"></script>
<script src="/pos/assets/js/payment_logic.js"></script>
<script>
function openInstallmentPaymentModal(invoiceId, paymentId, dueAmount, customerId, customerName) {
    console.log('openInstallmentPaymentModal called with:', {invoiceId, paymentId, dueAmount, customerId, customerName});
    // Open payment modal with isInvoicePayment flag
    window.openPaymentModal({
        totalPayable: parseFloat(dueAmount),
        previousDue: 0,
        customerId: customerId,
        customerName: customerName + ' - ' + invoiceId,
        isInvoicePayment: true, // Hide installment and full due buttons
        onSubmit: function(paymentData) {
            submitInstallmentPayment(paymentId, invoiceId, paymentData);
        }
    });
    
    // Default to Full Payment
    if(window.setPaymentType) window.setPaymentType('full');
}

function submitInstallmentPayment(paymentId, invoiceId, paymentData) {
    // Store invoice ID for use in success callback
    const currentInvoiceId = invoiceId;
    
    // Calculate total amount (Cash portion + Applied payments)
    let totalAmount = parseFloat(paymentData.amountReceived) || 0;
    let transactionIds = [];

    if (paymentData.appliedPayments && paymentData.appliedPayments.length > 0) {
        paymentData.appliedPayments.forEach(p => {
            totalAmount += parseFloat(p.amount);
            if(p.transactionId) transactionIds.push(p.transactionId);
        });
    }

    const formData = new FormData();
    formData.append('action', 'pay_installment');
    formData.append('payment_id', paymentId);
    formData.append('invoice_id', invoiceId);
    formData.append('amount_received', totalAmount.toFixed(2));
    formData.append('payment_method_id', paymentData.selectedPaymentMethod || '');
    // Send comma-separated transaction IDs if available (or pick the first one which is usually relevant for single payments)
    formData.append('transaction_id', transactionIds.join(', '));
    formData.append('note', paymentData.note);

    const btn = document.querySelector('.complete-btn');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    btn.disabled = true;

    fetch('/pos/installment/process_payment.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        console.log('Payment response:', data);
        if(data.success) {
            // Close payment modal first
            closeModal('paymentModal');
            
            console.log('Fetching invoice data for:', currentInvoiceId);
            // Fetch invoice data to display in modal
            fetch(`/pos/invoice/get_invoice_data.php?invoice_id=${currentInvoiceId}`)
                .then(res => {
                    console.log('Invoice API response status:', res.status);
                    return res.json();
                })
                .then(invoiceData => {
                    console.log('Invoice data received:', invoiceData);
                    if(invoiceData.success) {
                        console.log('Opening invoice modal...');
                        // Open invoice modal with fetched data
                        window.openInvoiceModal({
                            invoiceId: invoiceData.invoice_id,
                            store: invoiceData.store,
                            customer: invoiceData.customer,
                            items: invoiceData.items,
                            totals: invoiceData.totals,
                            paymentMethod: invoiceData.payment_method || 'Payment',
                            onClose: () => window.location.reload()
                        });
                    } else {
                        console.error('Invoice data fetch failed:', invoiceData.message);
                        // Fallback: show success and reload
                        Swal.fire({
                            icon: 'success',
                            title: 'Payment Successful',
                            text: data.message + ' (Invoice display unavailable)',
                            confirmButtonColor: '#10b981'
                        }).then(() => {
                            window.location.reload();
                        });
                    }
                })
                .catch(error => {
                    console.error('Error fetching invoice:', error);
                    // Fallback: show success and reload
                    Swal.fire({
                        icon: 'success',
                        title: 'Payment Successful',
                        text: data.message + ' (Invoice display error)',
                        confirmButtonColor: '#10b981'
                    }).then(() => {
                        window.location.reload();
                    });
                });
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: data.message });
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to process payment' });
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}
</script>
