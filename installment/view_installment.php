<?php
session_start();
include('../config/dbcon.php');

if (!isset($_SESSION['auth'])) {
    header("Location: /pos/login");
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    $query = "SELECT io.*, si.grand_total as sale_total, si.created_at as sale_date, 
                     c.name as customer_name, c.mobile as customer_mobile, c.address as customer_address,
                     s.store_name, s.address as store_address, s.phone as store_phone, s.email as store_email, s.vat_number
              FROM installment_orders io
              JOIN selling_info si ON io.invoice_id = si.invoice_id
              LEFT JOIN customers c ON si.customer_id = c.id
              JOIN stores s ON io.store_id = s.id
              WHERE io.id = $id";
              
    $res = mysqli_query($conn, $query);
    $installment = mysqli_fetch_assoc($res);

    if (!$installment) {
        $_SESSION['message'] = "Installment not found";
        header("Location: /pos/installment/list");
        exit;
    }
    
    $invoice_id = $installment['invoice_id'];
    
    $pay_query = "SELECT * FROM installment_payments WHERE invoice_id = '$invoice_id' ORDER BY payment_date ASC";
    $pay_res = mysqli_query($conn, $pay_query);
    $payments = [];
    while ($row = mysqli_fetch_assoc($pay_res)) {
        $payments[] = $row;
    }
} else {
    $_SESSION['message'] = "Invalid ID";
    header("Location: /pos/installment/list");
    exit;
}

$page_title = "Installment - " . $installment['invoice_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 15px;
        }
        .invoice-container {
            background: white;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 0 30px rgba(0,0,0,0.15);
        }
        .invoice-header {
            background: #84cc16;
            color: white;
            padding: 10px 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .close-btn {
            background: transparent;
            border: none;
            color: white;
            cursor: pointer;
            font-size: 16px;
        }
        .invoice-body {
            padding: 25px 30px;
            color: #000;
        }
        .text-center { text-align: center; }
        .company-logo {
            max-height: 55px;
            margin: 0 auto 10px;
            display: block;
        }
        .company-name {
            font-size: 18px;
            font-weight: 800;
            margin: 0 0 4px 0;
            text-transform: uppercase;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 100px 1fr;
            gap: 5px;
            font-size: 12px;
            margin: 18px 0;
            text-align: left;
        }
        .info-label { color: #64748b; }
        .info-value { font-weight: 600; }
        .section-title {
            text-align: center;
            font-weight: 800;
            border-bottom: 2px solid #000;
            padding-bottom: 5px;
            margin: 18px 0 12px;
            font-size: 13px;
            letter-spacing: 1.2px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            margin-bottom: 15px;
        }
        thead tr { border-bottom: 1px dashed #000; }
        th {
            padding: 6px 4px;
            text-align: left;
            font-weight: 700;
            font-size: 10px;
            text-transform: uppercase;
        }
        th.text-right, td.text-right { text-align: right; }
        th.text-center, td.text-center { text-align: center; }
        tbody tr { border-bottom: 1px dashed #e2e8f0; }
        td { padding: 8px 4px; }
        
        .details-section {
            margin-top: 15px;
        }
        .details-title {
            font-weight: 700;
            font-size: 12px;
            margin-bottom: 8px;
            padding-bottom: 4px;
            border-bottom: 1px dashed #cbd5e1;
        }
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px 15px;
            font-size: 11px;
        }
        .detail-item {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .detail-label { color: #64748b; }
        .detail-value { font-weight: 600; text-align: right; }
        
        .footer-text {
            text-align: center;
            font-size: 10px;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px dashed #cbd5e1;
            color: #64748b;
            line-height: 1.6;
        }
        .action-buttons {
            display: flex;
            gap: 8px;
            justify-content: center;
            padding: 15px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            transition: all 0.2s;
        }
        .btn-print { background: #0ea5e9; color: white; }
        .btn-print:hover { background: #0284c7; }
        .btn-email { background: #10b981; color: white; }
        .btn-email:hover { background: #059669; }
        .btn-back { background: #64748b; color: white; }
        .btn-back:hover { background: #475569; }
        .pay-btn {
            background: #10b981;
            color: white;
            border: none;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 10px;
            cursor: pointer;
            font-weight: 600;
        }
        .pay-btn:hover { background: #059669; }
        
        @media print {
            @page { 
                size: A4;
                margin: 10mm;
            }
            body { 
                background: white; 
                padding: 0;
                display: block;
            }
            .invoice-container { 
                box-shadow: none; 
                max-width: 100%;
                width: 100%;
            }
            .invoice-header, .action-buttons, .no-print { 
                display: none !important; 
            }
            .invoice-body { 
                padding: 0;
            }
            table { page-break-inside: avoid; }
            .details-section { page-break-inside: avoid; }
        }
    </style>
    <style>
       /* Payment Modal - Sidebar Payment Methods Grid */
.payment-methods-grid {
    display: grid !important;
    grid-template-columns: repeat(2, 1fr) !important;
    gap: 10px !important;
}

.sidebar-payment-method {
    padding: 12px 8px !important;
    border: 2px solid #e2e8f0 !important;
    border-radius: 10px !important;
    cursor: pointer !important;
    transition: all 0.2s !important;
    background: white !important;
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
    text-align: center !important;
    gap: 6px !important;
}

.sidebar-payment-method:hover {
    border-color: #0d9488 !important;
    background: #f0fdfa !important;
}

.sidebar-payment-method.selected {
    border-color: #0d9488 !important;
    background: #ccfbf1 !important;
}

.sidebar-payment-method i {
    font-size: 24px !important;
    color: #0d9488 !important;
    margin-bottom: 4px !important;
}

.sidebar-payment-method .pm-name,
.sidebar-payment-method span {
    font-size: 11px !important;
    font-weight: 600 !important;
    color: #1e293b !important;
}

.sidebar-payment-method .pm-balance {
    font-size: 10px !important;
    color: #64748b !important;
}

/* Right Panel Background Fix */
#paymentModal .pos-modal-body>div>div:last-child {
    background: white !important;
}

/* Fix close icon position in payment modal header */
#paymentModal .pos-modal-header {
    position: relative !important;
}

#paymentModal .pos-modal-header .close-btn {
    position: absolute !important;
    right: 16px !important;
    top: 50% !important;
    transform: translateY(-50%) !important;
}

/* Installment Payment Modal Enhancements */
#paymentModal .pos-modal-header {
    min-height: 70px !important;
    padding: 18px 25px !important;
}

#paymentModal .pos-modal-header h3 {
    font-size: 16px !important;
    font-weight: 600 !important;
}

/* Enhanced payment method selection visibility */
.payment-method-btn.selected,
.sidebar-payment-method.selected {
    border: 3px solid #10b981 !important;
    background: #d1fae5 !important;
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3) !important;
}

.payment-method-btn,
.sidebar-payment-method {
    transition: all 0.2s ease !important;
}

/* Partial payment input styling */
#paymentModal #amountReceived {
    font-size: 16px !important;
    font-weight: 600 !important;
    border: 2px solid #e5e7eb !important;
}

#paymentModal #amountReceived:focus {
    border-color: #10b981 !important;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1) !important;
    outline: none !important;
}

/* Payment type buttons */
.payment-type-btn {
    padding: 10px 20px !important;
    font-weight: 600 !important;
    transition: all 0.2s ease !important;
}

.payment-type-btn.active {
    background: #10b981 !important;
    color: white !important;
}
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="invoice-header">
            <div style="font-size: 13px; font-weight: 600;">
                <i class="fas fa-file-invoice"></i> Installment > <?= htmlspecialchars($installment['invoice_id']); ?>
            </div>
            <button onclick="window.location.href='/pos/installment/list'" class="close-btn">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="invoice-body">
            <div class="text-center">
                <img src="/pos/assets/images/logo-fav.png" class="company-logo" alt="Logo">
                <h1 class="company-name"><?= htmlspecialchars($installment['store_name']); ?></h1>
                <div style="font-size: 11px; margin-top: 3px;"><?= htmlspecialchars($installment['store_address'] ?? 'Main Branch'); ?></div>
                <div style="font-size: 10px; color: #334155; margin-top: 2px;">
                    Mobile: <?= htmlspecialchars($installment['store_phone'] ?? '--'); ?>, 
                    Email: <?= htmlspecialchars($installment['store_email'] ?? '--'); ?>
                </div>
                <?php if (!empty($installment['vat_number'])): ?>
                <div style="font-size: 10px; color: #334155; margin-top: 2px;">
                    BIN Number: <?= htmlspecialchars($installment['vat_number']); ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="info-grid">
                <div class="info-label">Invoice ID:</div>
                <div class="info-value"><?= htmlspecialchars($installment['invoice_id']); ?></div>
                
                <div class="info-label">Date:</div>
                <div class="info-value"><?= date('d/m/Y, h:i A', strtotime($installment['sale_date'])); ?></div>
                
                <div class="info-label">Customer:</div>
                <div class="info-value"><?= htmlspecialchars($installment['customer_name'] ?? 'Walking Customer'); ?></div>
                
                <div class="info-label">Phone:</div>
                <div class="info-value"><?= htmlspecialchars($installment['customer_mobile'] ?? '--'); ?></div>
                
                <div class="info-label">Address:</div>
                <div class="info-value"><?= htmlspecialchars($installment['customer_address'] ?? '-'); ?></div>
            </div>

            <div class="section-title">INSTALLMENT</div>

            <div style="margin-bottom: 15px;">
                <div style="font-weight: 700; font-size: 11px; margin-bottom: 8px;">Payments</div>
                <table>
                    <thead>
                        <tr>
                            <th>DATE</th>
                            <th class="text-right">INTEREST</th>
                            <th class="text-right">PAYABLE</th>
                            <th class="text-right">PAID</th>
                            <th class="text-right">DUE</th>
                            <th class="text-center">STATUS</th>
                            <th class="text-center no-print">ACTION</th>
                        </tr>
                    </thead>
                    <tbody id="payments-table-body">
                        <?php foreach ($payments as $pay): 
                            $dueAmount = $pay['payable'] - $pay['paid'];
                            if($dueAmount < 0.05) $dueAmount = 0;
                            $displayStatus = ($dueAmount <= 0) ? 'paid' : $pay['payment_status'];
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
                                <?php if ($displayStatus !== 'paid'): 
                                    $payment_id = $pay['id'];
                                    $customer_id = $installment['customer_id'] ?? 0;
                                    $customer_name = htmlspecialchars($installment['customer_name'] ?? 'Customer', ENT_QUOTES);
                                ?>
                                <button class="pay-btn" onclick="openInstallmentPaymentModal('<?= $invoice_id; ?>', <?= $payment_id; ?>, <?= $dueAmount; ?>, <?= $customer_id; ?>, '<?= $customer_name; ?>')">
                                    <i class="fas fa-money-bill-wave"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="details-section">
                <div class="details-title">Installment Details</div>
                <div class="details-grid">
                    <div class="detail-item">
                        <span class="detail-label">Invoice Id</span>
                        <span class="detail-value"><?= htmlspecialchars($installment['invoice_id']); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Payable Amount</span>
                        <span class="detail-value"><?= number_format($installment['sale_total'], 2); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Initial Payment</span>
                        <span class="detail-value" style="color: #10b981;"><?= number_format($installment['initial_amount'], 2); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Due</span>
                        <span class="detail-value"><?= number_format($installment['sale_total'] - $installment['initial_amount'], 2); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Interest (%)</span>
                        <span class="detail-value"><?= number_format($installment['interest_percentage'], 2); ?>%</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Interest Amount</span>
                        <span class="detail-value"><?= number_format($installment['interest_amount'], 2); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Duration</span>
                        <span class="detail-value"><?= $installment['duration']; ?> days</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Interval Count</span>
                        <span class="detail-value"><?= $installment['interval_count']; ?> days</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Installment Count</span>
                        <span class="detail-value"><?= $installment['installment_count']; ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Payment Status</span>
                        <span class="detail-value" id="detail-payment-status"><?= strtoupper($installment['payment_status']); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Last Installment Date</span>
                        <span class="detail-value" id="detail-last-installment"><?= $installment['last_installment_date'] ? date('d/m/Y', strtotime($installment['last_installment_date'])) : 'N/A'; ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Installment End Date</span>
                        <span class="detail-value"><?= $installment['installment_end_date'] ? date('d/m/Y', strtotime($installment['installment_end_date'])) : 'N/A'; ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Created At</span>
                        <span class="detail-value"><?= date('d M Y h:i A', strtotime($installment['created_at'])); ?></span>
                    </div>
                </div>
            </div>

            <div class="footer-text">
                Thank you for choosing us!<br>
                For Support: <?= htmlspecialchars($installment['store_name']); ?> | Developed by STS
            </div>
        </div>

        <div class="action-buttons">
            <button onclick="window.print()" class="btn btn-print">
                <i class="fas fa-print"></i> Print
            </button>
            <button class="btn btn-email">
                <i class="fas fa-envelope"></i> Email
            </button>
            <button onclick="window.location.href='/pos/installment/list'" class="btn btn-back">
                <i class="fas fa-arrow-left"></i> Back
            </button>
        </div>
    </div>

    <?php 
    // Include payment modal
    $payment_methods_result = mysqli_query($conn, "SELECT id, name, code FROM payment_methods WHERE status = 1 ORDER BY sort_order ASC");
    include('../includes/payment_modal.php'); 
    ?>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <script src="/pos/assets/js/payment_logic.js"></script>
    <script>
        // CRITICAL: Hide payment modal immediately on load
        (function() {
            const hideModal = function() {
                const modal = document.getElementById('paymentModal');
                if (modal) {
                    modal.style.setProperty('display', 'none', 'important');
                    modal.classList.remove('active');
                }
            };
            
            // Hide immediately
            hideModal();
            
            // Hide on DOMContentLoaded
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', hideModal);
            }
            
            // Hide after a short delay to catch any late scripts
            setTimeout(hideModal, 100);
            setTimeout(hideModal, 500);
        })();
        // Print on Enter key, BUT ONLY IF Payment Modal is NOT open
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                const paymentModal = document.getElementById('paymentModal');
                const isPaymentModalActive = paymentModal && (paymentModal.classList.contains('active') || paymentModal.style.display !== 'none');
                
                if (isPaymentModalActive) {
                    // If payment modal is open, Enter should submit the payment (Checkout)
                    e.preventDefault();
                    
                    // Trigger the checkout button click
                    const checkoutBtn = document.querySelector('.complete-btn');
                    if (checkoutBtn && !checkoutBtn.disabled) {
                        checkoutBtn.click();
                    }
                } else {
                    // Only print if payment modal is NOT open
                    e.preventDefault();
                    window.print();
                }
            }
        });

        // Redirect to list after print dialog closes
        window.onafterprint = function() {
            window.location.href = '/pos/installment/list';
        };

        // Payment modal function
        function openInstallmentPaymentModal(invoiceId, paymentId, dueAmount, customerId, customerName) {
            // Hide the invoice container when payment modal opens
            const invoiceContainer = document.querySelector('.invoice-container');
            if (invoiceContainer) {
                invoiceContainer.style.display = 'none';
            }
            
            // Set body background to modal overlay color
            document.body.style.background = 'rgba(0, 0, 0, 0.5)';
            
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
            
            // Enhanced installment payment modal features
            setTimeout(() => {
                // 1. Enable partial payment option
                const partialBtn = document.querySelector('[data-type="partial"]');
                if(partialBtn) {
                    partialBtn.disabled = false;
                    partialBtn.style.display = 'inline-block';
                    partialBtn.style.opacity = '1';
                    partialBtn.style.pointerEvents = 'auto';
                }
                
                // 2. Make amount input editable for partial payments
                const amountInput = document.getElementById('amountReceived');
                if(amountInput) {
                    amountInput.readOnly = false;
                    amountInput.setAttribute('max', dueAmount);
                    amountInput.setAttribute('step', '0.01');
                    amountInput.setAttribute('min', '0.01');
                }
                
                // 3. Payment method selection persistence
                let selectedMethodId = null;
                const methodButtons = document.querySelectorAll('.payment-method-btn, .sidebar-payment-method');
                
                methodButtons.forEach(btn => {
                    btn.addEventListener('click', function(e) {
                        e.stopPropagation();
                        selectedMethodId = this.dataset.methodId || this.dataset.id;
                        
                        // Visual feedback - keep selection highlighted
                        methodButtons.forEach(b => b.classList.remove('selected'));
                        this.classList.add('selected');
                        
                        // Store in window for access by payment_logic.js
                        window.selectedPaymentMethod = selectedMethodId;
                    });
                });
                
                // 4. Auto-select default cash method
                const cashButton = Array.from(methodButtons).find(btn => {
                    const name = (btn.querySelector('.pm-name') || btn.querySelector('span'))?.textContent || '';
                    return name.toLowerCase().includes('cash');
                });
                
                if(cashButton) {
                    cashButton.click();
                }
                
                // 5. Maintain selection when switching payment types
                const paymentTypeBtns = document.querySelectorAll('.payment-type-btn');
                paymentTypeBtns.forEach(typeBtn => {
                    typeBtn.addEventListener('click', function() {
                        setTimeout(() => {
                            // Re-select the previously selected method
                            if(selectedMethodId) {
                                const selectedBtn = document.querySelector(
                                    `[data-method-id="${selectedMethodId}"], [data-id="${selectedMethodId}"]`
                                );
                                if(selectedBtn) {
                                    selectedBtn.classList.add('selected');
                                }
                            }
                        }, 50);
                    });
                });
                
            }, 150); // Increased timeout to ensure modal is fully rendered
        }

        function submitInstallmentPayment(paymentId, invoiceId, paymentData) {
            // Calculate total amount from applied payments or use amountReceived
            let totalAmount = paymentData.amountReceived || 0;
            
            // If there are applied payments, use their total
            if (paymentData.appliedPayments && paymentData.appliedPayments.length > 0) {
                totalAmount = paymentData.appliedPayments.reduce((sum, p) => sum + p.amount, 0);
            }
            
            // Get primary payment method
            let primaryMethodId = paymentData.selectedPaymentMethod || '';
            
            // If no selected method but has applied payments, use the first one
            if (!primaryMethodId && paymentData.appliedPayments && paymentData.appliedPayments.length > 0) {
                primaryMethodId = paymentData.appliedPayments[0].type;
            }
            
            // Get transaction ID from applied payments
            let transactionId = '';
            if (paymentData.appliedPayments && paymentData.appliedPayments.length > 0) {
                const paymentWithTxn = paymentData.appliedPayments.find(p => p.transactionId);
                if (paymentWithTxn) {
                    transactionId = paymentWithTxn.transactionId;
                }
            }
            
            console.log('Payment Data:', {
                totalAmount: totalAmount,
                primaryMethodId: primaryMethodId,
                transactionId: transactionId,
                appliedPayments: paymentData.appliedPayments,
                selectedMethod: paymentData.selectedPaymentMethod
            });
            
            const formData = new FormData();
            formData.append('action', 'pay_installment');
            formData.append('payment_id', paymentId);
            formData.append('invoice_id', invoiceId);
            formData.append('amount_received', totalAmount);
            formData.append('payment_method_id', primaryMethodId);
            formData.append('transaction_id', transactionId);
            formData.append('note', paymentData.note || '');

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
                if(data.success) {
                    // Close payment modal
                    window.closeModal('paymentModal');
                    
                    // Show invoice container back
                    const invoiceContainer = document.querySelector('.invoice-container');
                    if (invoiceContainer) {
                        invoiceContainer.style.display = 'block';
                    }
                    
                    // Reset body background
                    document.body.style.background = '';
                    
                    // Show success message
                    Swal.fire({
                        icon: 'success',
                        title: 'Payment Successful',
                        text: data.message,
                        confirmButtonColor: '#10b981',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    
                    // Open invoice modal after short delay
                    setTimeout(() => {
                        openInvoiceViewModal(invoiceId);
                    }, 2100);
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
        
        // Function to open invoice view modal
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
                            wordsEl.textContent = numberToWords(amount);
                        }
                    }, 100);
                })
                .catch(error => {
                    console.error('Error loading invoice:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to load invoice'
                    });
                });
        }
        
        // Number to Words Converter
        function numberToWords(num) {
            const single = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine'];
            const double = ['Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
            const tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
            const formatTrio = (trio) => {
                let res = '';
                if (trio[0] !== '0') {
                    res += single[parseInt(trio[0])] + ' Hundred ';
                }
                if (trio[1] === '1') {
                    res += double[parseInt(trio[2])];
                } else {
                    res += tens[parseInt(trio[1])] + (trio[1] !== '0' && trio[2] !== '0' ? '-' : '') + single[parseInt(trio[2])];
                }
                return res.trim();
            };

            let [integer, decimal] = num.toFixed(2).split('.');
            integer = integer.padStart(12, '0');
            const units = ['Billion', 'Million', 'Thousand', ''];
            let result = '';

            for (let i = 0; i < 4; i++) {
                let trio = integer.substring(i * 3, i * 3 + 3);
                let word = formatTrio(trio);
                if (word) {
                    result += word + ' ' + units[i] + ' ';
                }
            }

            result = result.trim() || 'Zero';
            
            if (decimal !== '00') {
                let decimalWord = formatTrio('0' + decimal);
                result += ' and ' + decimalWord + ' Cents';
            }
            
            return result + ' Only';
        }
        
        // Function to close invoice modal
        function closeInvoiceModal() {
            const modalContainer = document.getElementById('invoice-view-modal-container');
            if (modalContainer) {
                modalContainer.innerHTML = '';
            }
            // Update data dynamically without reload
            refreshInstallmentView(); 
        }

        // Function to refresh installment view data
        function refreshInstallmentView() {
            // Get current installment order ID
            const installmentId = <?= $id; ?>;
            
            fetch('/pos/installment/get_installment_details.php?id=' + installmentId)
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        // Update Payments Table
                        const tbody = document.getElementById('payments-table-body');
                        if(tbody) tbody.innerHTML = data.payments_html;
                        
                        // Update Details
                        const statusEl = document.getElementById('detail-payment-status');
                        if(statusEl) statusEl.textContent = data.details.payment_status;
                        
                        const lastDateEl = document.getElementById('detail-last-installment');
                        if(lastDateEl) lastDateEl.textContent = data.details.last_installment_date;
                        
                        // Optional: Show simplified toast
                        /*
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: 'View Updated',
                            showConfirmButton: false,
                            timer: 1500
                        });
                        */
                    }
                })
                .catch(err => console.error('Failed to refresh data:', err));
        }
    </script>
</body>
</html>
