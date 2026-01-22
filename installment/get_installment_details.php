<?php
session_start();
include('../config/dbcon.php');

if (!isset($_SESSION['auth'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit;
}

// Fetch Installment Order Details
$query = "SELECT io.*, si.grand_total as sale_total, si.created_at as sale_date, 
          c.name as customer_name, c.id as customer_id
          FROM installment_orders io
          JOIN selling_info si ON io.invoice_id = si.invoice_id
          LEFT JOIN customers c ON si.customer_id = c.id
          WHERE io.id = $id";
          
$res = mysqli_query($conn, $query);
$installment = mysqli_fetch_assoc($res);

if (!$installment) {
    echo json_encode(['success' => false, 'message' => 'Not Found']);
    exit;
}

$invoice_id = $installment['invoice_id'];

// Fetch Payments
$pay_query = "SELECT * FROM installment_payments WHERE invoice_id = '$invoice_id' ORDER BY payment_date ASC";
$pay_res = mysqli_query($conn, $pay_query);
$payments = [];
while ($row = mysqli_fetch_assoc($pay_res)) {
    $payments[] = $row;
}

// Calculate/Format Data
$due = $installment['sale_total'] - $installment['initial_amount']; // Base due logic
// Note: Actual due logic logic might vary based on payments, but the view seems to rely on static calculation + payment table sum.
// Let's stick to returning what the view needs.

// Generate Payments HTML
ob_start();
foreach ($payments as $pay) {
    $dueAmount = $pay['payable'] - $pay['paid'];
    if($dueAmount < 0.05) $dueAmount = 0;
    $displayStatus = ($dueAmount <= 0) ? 'paid' : $pay['payment_status'];
    
    $payment_id = $pay['id'];
    $customer_id = $installment['customer_id'] ?? 0;
    $customer_name = htmlspecialchars($installment['customer_name'] ?? 'Customer', ENT_QUOTES);
    $invoice_id_safe = $invoice_id;
    
    ?>
    <tr>
        <td><?= date('d/m/Y', strtotime($pay['payment_date'])); ?></td>
        <td class="text-right"><?= number_format($pay['interest'], 2); ?></td>
        <td class="text-right" style="font-weight: 600;"><?= number_format($pay['payable'], 2); ?></td>
        <td class="text-right" style="font-weight: 600; color: #10b981;"><?= number_format($pay['paid'], 2); ?></td>
        <td class="text-right" style="font-weight: 600; color: #ef4444;"><?= number_format($dueAmount, 2); ?></td>
        <td class="text-center" style="font-weight: 700;">
            <?= strtoupper($displayStatus); ?>
        </td>
        <td class="text-center no-print">
            <?php if ($displayStatus !== 'paid'): ?>
            <button class="pay-btn" onclick="openInstallmentPaymentModal('<?= $invoice_id_safe; ?>', <?= $payment_id; ?>, <?= $dueAmount; ?>, <?= $customer_id; ?>, '<?= $customer_name; ?>')">
                <i class="fas fa-money-bill-wave"></i>
            </button>
            <?php endif; ?>
        </td>
    </tr>
    <?php
}
$payments_html = ob_get_clean();

// Respond
echo json_encode([
    'success' => true,
    'payments_html' => $payments_html,
    'details' => [
        'payment_status' => strtoupper($installment['payment_status']),
        'last_installment_date' => $installment['last_installment_date'] ? date('d/m/Y', strtotime($installment['last_installment_date'])) : 'N/A',
        // Add other dynamic fields if needed
    ]
]);
