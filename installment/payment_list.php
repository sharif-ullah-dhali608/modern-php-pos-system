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
        $due = floatval($row['due']);
        if($due < 0.05) $due = 0;
        
        $row['formatted_payable'] = number_format($row['payable'], 2);
        $row['formatted_capital'] = number_format($row['capital'], 2);
        $row['formatted_interest'] = number_format($row['interest'], 2);
        $row['formatted_paid'] = number_format($row['paid'], 2);
        $row['formatted_due'] = number_format($due, 2);
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
            
            // Show Success Notification
            Swal.fire({
                icon: 'success',
                title: 'Payment Successful',
                text: 'Processing invoice...',
                timer: 1500,
                showConfirmButton: false
            });
            
            // Open invoice view modal
            setTimeout(() => {
                openInvoiceViewModal(currentInvoiceId);
            }, 1600);

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

// Function to open invoice view modal (Ported from view_installment.php)
function openInvoiceViewModal(invoiceId) {
    fetch('/pos/admin/invoice_content.php?id=' + invoiceId)
        .then(res => res.text())
        .then(html => {
            // Create modal container if not exists
            let modalContainer = document.getElementById('invoice-view-modal-container');
            if (!modalContainer) {
                modalContainer = document.createElement('div');
                modalContainer.id = 'invoice-view-modal-container';
                document.body.appendChild(modalContainer);
            }
            
            // Wrap invoice content in modal structure
            const modalHTML = `
                <div class="pos-modal active" id="invoiceViewModal" style="display: flex; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.5); z-index: 1050; align-items: center; justify-content: center; padding: 10px;">
                    <div style="background: white; border-radius: 12px; max-width: 480px; width: 100%; max-height: 98vh; display: flex; flex-direction: column; box-shadow: 0 20px 60px rgba(0,0,0,0.3); position: relative;">
                        <div style="flex: 1; overflow-y: auto; overflow-x: hidden;">
                            ${html}
                        </div>
                    </div>
                </div>
            `;
            
            // Insert modal HTML
            modalContainer.innerHTML = modalHTML;
            
            // Trigger Barcode Generation
            setTimeout(() => {
                if(window.JsBarcode && document.getElementById('barcode-modal')) {
                    JsBarcode("#barcode-modal", invoiceId, {
                        format: "CODE128",
                        lineColor: "#000",
                        width: 2,
                        height: 40,
                        displayValue: true,
                        fontSize: 10
                    });
                }
            }, 100);

            // Trigger Number to Words
            setTimeout(() => {
                const totalEl = document.getElementById('base-grand-total');
                const wordsEl = document.getElementById('in-words');
                if(totalEl && wordsEl) {
                    const amount = parseFloat(totalEl.value || 0);
                    // Use numberToWords from invoice_modal.js
                    if(typeof numberToWords === 'function') {
                        wordsEl.textContent = numberToWords(amount);
                    }
                }
            }, 100);
        })
        .catch(error => {
            console.error('Error loading invoice:', error);
            Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to load invoice' });
        });
}

// Ensure the onClose of this modal reloads the page
function closeInvoiceModal() {
    window.location.reload();
}

// Global Enter Key Handler for Payment and Invoice Modals
document.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        // 1. If Invoice Modal is open, Print
        const invoiceModal = document.getElementById('invoiceViewModal');
        if (invoiceModal && invoiceModal.classList.contains('active')) {
            e.preventDefault();
            window.print();
            return;
        }

        // 2. If Payment Modal is open, Checkout
        const paymentModal = document.getElementById('paymentModal');
        if (paymentModal && paymentModal.classList.contains('active')) {
            // Only trigger if no other modal (like a Swal alert) is on top
            if (!document.querySelector('.swal2-container')) {
                e.preventDefault();
                const checkoutBtn = document.querySelector('.complete-btn');
                if (checkoutBtn && !checkoutBtn.disabled) {
                    checkoutBtn.click();
                }
            }
        }
    }
});
</script>
