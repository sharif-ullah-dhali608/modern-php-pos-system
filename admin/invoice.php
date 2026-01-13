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

// Fetch Data
// net_amount: Total after returns
// paid_amount: Total from sell_logs (payments)
$query = "SELECT si.*, c.name as customer_name,
          (SELECT SUM(qty_sold - return_item) FROM selling_item WHERE invoice_id = si.invoice_id) as item_count,
          (si.grand_total - IFNULL((SELECT SUM(grand_total) FROM selling_info WHERE ref_invoice_id = si.invoice_id AND inv_type = 'return'), 0)) as net_amount,
          IFNULL((SELECT SUM(amount) FROM sell_logs WHERE ref_invoice_id = si.invoice_id AND type = 'payment'), 0) as paid_amount
          FROM selling_info si 
          LEFT JOIN customers c ON si.customer_id = c.id 
          WHERE si.inv_type = 'sale' 
          HAVING item_count > 0
          ORDER BY si.created_at DESC";

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
         $row['pay_btn'] = '<button onclick="openPaymentModal(\''.$row['invoice_id'].'\', '.$row['current_due'].', '.$row['info_id'].', '.$row['store_id'].', '.($row['customer_id'] ?: '0').')" class="inline-block p-2 text-emerald-600 hover:text-emerald-800 bg-emerald-50 hover:bg-emerald-100 rounded-lg transition" title="Pay"><i class="fas fa-money-bill"></i></button>';
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

// Prepare Config
$config = [
    'title' => 'Invoices List',
    'table_id' => 'invoice_table',
    'add_url' => '/pos/pos', 
    'edit_url' => '/pos/admin/invoice_edit.php',
    'delete_url' => '/pos/admin/invoice_delete.php',
    'view_url' => '/pos/invoice/view',
    'primary_key' => 'info_id',
    'name_field' => 'invoice_id',
    'data' => $data,
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

<!-- Payment Modal -->
<div id="paymentModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closePaymentModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-emerald-600 px-4 py-3 flex justify-between items-center">
                <h3 class="text-lg font-bold text-white"><i class="fas fa-money-bill-wave mr-2"></i> Register Payment</h3>
                <button onclick="closePaymentModal()" class="text-white hover:text-gray-200"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-6">
                <form id="paymentForm">
                    <input type="hidden" id="pay_invoice_id" name="invoice_id">
                    <input type="hidden" id="pay_info_id" name="info_id">
                    <input type="hidden" id="pay_store_id" name="store_id">
                    <input type="hidden" id="pay_customer_id" name="customer_id">

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Invoice ID</label>
                        <input type="text" id="display_invoice_id" class="w-full bg-gray-50 border border-gray-300 rounded-md px-3 py-2 text-gray-900" readonly>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Due Amount</label>
                            <input type="text" id="display_due_amount" class="w-full bg-gray-50 border border-gray-300 rounded-md px-3 py-2 text-red-600 font-bold" readonly>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Paying Amount</label>
                            <input type="number" id="pay_amount" name="amount" step="0.01" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500 font-bold" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
                        <select id="pay_pmethod_id" name="payment_method_id" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500" required>
                            <option value="">Select Method</option>
                            <?php foreach($payment_methods as $pm): ?>
                                <option value="<?= $pm['id']; ?>"><?= htmlspecialchars($pm['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Note (Optional)</label>
                        <textarea id="pay_note" name="note" rows="2" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500" placeholder="Payment reference, etc."></textarea>
                    </div>

                    <div class="flex justify-end gap-3">
                        <button type="button" onclick="closePaymentModal()" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition">Cancel</button>
                        <button type="submit" class="px-6 py-2 bg-emerald-600 text-white rounded-md hover:bg-emerald-700 transition font-bold flex items-center gap-2">
                             <span id="payBtnText">Complete Payment</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function openPaymentModal(invoiceId, dueAmount, infoId, storeId, customerId) {
    document.getElementById('pay_invoice_id').value = invoiceId;
    document.getElementById('display_invoice_id').value = invoiceId;
    document.getElementById('display_due_amount').value = parseFloat(dueAmount).toFixed(2);
    document.getElementById('pay_amount').value = parseFloat(dueAmount).toFixed(2);
    document.getElementById('pay_info_id').value = infoId;
    document.getElementById('pay_store_id').value = storeId;
    document.getElementById('pay_customer_id').value = customerId;
    
    document.getElementById('paymentModal').classList.remove('hidden');
    document.getElementById('pay_amount').focus();
}

function closePaymentModal() {
    document.getElementById('paymentModal').classList.add('hidden');
    document.getElementById('paymentForm').reset();
}

document.getElementById('paymentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const btnText = document.getElementById('payBtnText');
    const originalText = btnText.innerHTML;
    btnText.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    btnText.parentElement.disabled = true;

    const formData = new FormData(this);
    formData.append('action', 'register_payment');

    fetch('/pos/pos/save_sale.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: 'Payment recorded successfully',
                confirmButtonColor: '#059669'
            }).then(() => {
                window.location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'Error recording payment',
                confirmButtonColor: '#059669'
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Server error occurred',
            confirmButtonColor: '#059669'
        });
    })
    .finally(() => {
        btnText.innerHTML = originalText;
        btnText.parentElement.disabled = false;
    });
});
</script>
