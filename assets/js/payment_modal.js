/**
 * Payment Modal JavaScript Functions
 * Reusable payment modal logic extracted from pos.php
 */

// Global variables for payment modal
let appliedPayments = window.appliedPayments || [];
let selectedPaymentMethod = window.selectedPaymentMethod || null;
let customerId = window.customerId || 0;
let onPaymentSubmit = null; // Callback for custom submission

/**
 * Open Payment Modal with configuration
 * @param {Object} config - Configuration object
 * @param {number} config.totalPayable - Total amount payable
 * @param {number} config.previousDue - Previous due amount (optional)
 * @param {number} config.customerId - Customer ID
 * @param {string} config.customerName - Customer name for display
 * @param {Function} config.onSubmit - Callback function when payment is submitted
 */
window.openPaymentModal = function (config) {
    // Reset state
    appliedPayments = [];
    selectedPaymentMethod = null;
    customerId = config.customerId || 0;
    onPaymentSubmit = config.onSubmit || null;

    const totalPayable = config.totalPayable || 0;
    const previousDue = config.previousDue || 0;
    const customerName = config.customerName || 'Customer';

    // Update modal fields
    const payableEl = document.getElementById('payment-payable');
    const totalPayableEl = document.getElementById('payment-total-payable');
    const displayPayableEl = document.getElementById('display-payable-amount');
    const previousDueEl = document.getElementById('payment-previous-due');
    const customerNameEl = document.getElementById('payment-customer-name');
    const paidEl = document.getElementById('payment-paid');
    const dueEl = document.getElementById('payment-due');
    const balanceEl = document.getElementById('payment-balance');

    if (payableEl) payableEl.textContent = totalPayable.toFixed(2);
    if (totalPayableEl) totalPayableEl.textContent = (totalPayable + previousDue).toFixed(2);
    if (displayPayableEl) displayPayableEl.textContent = (totalPayable + previousDue).toFixed(2);
    if (previousDueEl) previousDueEl.textContent = previousDue.toFixed(2);
    if (customerNameEl) customerNameEl.textContent = customerName;
    if (paidEl) paidEl.textContent = '0.00';
    if (dueEl) dueEl.textContent = (totalPayable + previousDue).toFixed(2);
    if (balanceEl) balanceEl.textContent = '0.00';

    // Reset amount input
    const amountInput = document.getElementById('amount-received');
    if (amountInput) amountInput.value = totalPayable.toFixed(2);

    // Show modal
    const modal = document.getElementById('paymentModal');
    if (modal) {
        modal.style.display = 'flex';
        modal.classList.add('active');
    }

    // Update applied payments UI
    updateAppliedPaymentsUI();
};

// Close modal helper
window.closeModal = function (modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        modal.classList.remove('active');
    }
};

// Complete Sale / Submit Payment - calls the onSubmit callback if provided
window.completeSale = function () {
    if (onPaymentSubmit && typeof onPaymentSubmit === 'function') {
        // Get amount from input
        let amountReceived = parseFloat(document.getElementById('amount-received')?.value) || 0;

        // Calculate total from applied payments
        const totalApplied = appliedPayments.reduce((sum, p) => sum + p.amount, 0);

        // Only auto-fill the full payable amount if:
        // 1. Amount input is 0
        // 2. AND there are NO applied payments (user hasn't manually added any payment method)
        // This prevents overwriting 0 when user intentionally paid via applied methods only
        if (amountReceived === 0 && totalApplied === 0) {
            const displayedAmount = parseFloat(document.getElementById('display-payable-amount')?.textContent) || 0;
            const totalPayable = parseFloat(document.getElementById('payment-total-payable')?.textContent) || 0;
            amountReceived = displayedAmount || totalPayable;
        }

        const paymentData = {
            amountReceived: amountReceived,
            appliedPayments: appliedPayments,
            selectedPaymentMethod: selectedPaymentMethod,
            note: document.getElementById('payment-note')?.value || ''
        };
        onPaymentSubmit(paymentData);
    } else {
        console.warn('No onSubmit callback provided to payment modal');
    }
};

function selectPaymentMethod(element, id) {
    // Determine balance and source for special methods
    let balance = 999999999; // Unlimited for Cash/Card
    let label = '';
    let icon = '';
    let isSpecial = false;

    if (id === 'opening_balance') {
        balance = parseFloat(document.getElementById('customer_opening_balance').value) || 0;
        label = 'Wallet';
        icon = 'fa-wallet';
        isSpecial = true;
    } else if (id === 'giftcard') {
        balance = parseFloat(document.getElementById('customer_giftcard_balance').value) || 0;
        label = 'Gift Card';
        icon = 'fa-gift';
        isSpecial = true;
    } else if (id === 'credit') {
        // Pay Later case: Keep legacy behavior for now or unify? 
        // User said "Pay Later" is credit. Let's keep it as is for now unless specifically asked to apply amount.
        // Actually, user said "Apply Wallet modal er motw jotw payment method ache sob method eii Apply Wallet modal er motw modal dekhabe"
        // So EVERY method including cash/nagad/etc needs a modal.
        document.querySelectorAll('.sidebar-payment-method').forEach(pm => {
            pm.style.background = 'white';
            pm.style.borderColor = '#e2e8f0';
        });
        element.style.background = '#e0f2fe';
        element.style.borderColor = '#0d9488';
        selectedPaymentMethod = id;
        updatePaymentCalculations();
        return;
    } else {
        // Standard methods (Cash, Nagad, etc.)
        const pmEl = element.querySelector('.pm-name');
        label = pmEl ? pmEl.textContent : 'Payment';
        const iconEl = element.querySelector('i');
        icon = iconEl ? Array.from(iconEl.classList).find(c => c.startsWith('fa-')) : 'fa-money-bill';
    }

    if (isSpecial && balance <= 0) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: `Insufficient ${label} balance`,
            target: document.getElementById('paymentModal')
        });
        return;
    }

    const currentApplied = appliedPayments.find(p => p.type === id);
    const totalPayable = parseFloat(document.getElementById('payment-total-payable').textContent) || 0;
    const alreadyAppliedTotal = appliedPayments.reduce((sum, p) => sum + (p.type !== id ? p.amount : 0), 0);
    const maxCanApply = Math.min(balance, totalPayable - alreadyAppliedTotal);

    if (maxCanApply <= 0 && !currentApplied) {
        Swal.fire({
            icon: 'info',
            title: 'Info',
            text: 'Remaining balance is already covered',
            target: document.getElementById('paymentModal')
        });
        return;
    }

    const initialValue = currentApplied ? currentApplied.amount : maxCanApply;

    Swal.fire({
        title: `<div style="display: flex; align-items: center; justify-content: center; gap: 10px; color: #0d9488;">
                    <i class="fas ${icon}"></i>
                    <span>Apply ${label}</span>
                </div>`,
        html: `
            <div style="padding: 10px 0;">
                ${isSpecial ? `
                <div style="background: #f0fdfa; border: 1px solid #ccfbf1; border-radius: 8px; padding: 10px; margin-bottom: 20px;">
                    <span style="display: block; font-size: 13px; color: #14b8a6; font-weight: 600;">Available Balance</span>
                    <span style="font-size: 20px; font-weight: 700; color: #0f766e;">৳${balance.toFixed(2)}</span>
                </div>` : ''}
                <label style="display: block; text-align: left; font-size: 12px; color: #64748b; margin-bottom: 6px; font-weight: 600;">ENTER AMOUNT</label>
                <input type="number" id="swal-input-amount" class="swal2-input" 
                    value="${initialValue.toFixed(2)}" 
                    step="0.01" min="0.01" max="${balance}"
                    style="width: 100%; margin: 0; text-align: center; font-size: 24px; font-weight: 700; color: #1e293b; border: 2px solid #e2e8f0; border-radius: 10px; transition: border-color 0.2s;">
            </div>`,
        showCancelButton: true,
        confirmButtonText: 'Apply Amount',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#0d9488',
        cancelButtonColor: '#64748b',
        target: document.getElementById('paymentModal'),
        didOpen: () => {
            const input = document.getElementById('swal-input-amount');
            input.focus();
            input.select();
            input.onfocus = () => input.style.borderColor = '#14b8a6';
            input.onblur = () => input.style.borderColor = '#e2e8f0';
        },
        preConfirm: () => {
            const value = parseFloat(document.getElementById('swal-input-amount').value);
            if (isNaN(value) || value <= 0) {
                Swal.showValidationMessage('Please enter a valid amount');
                return false;
            }
            if (value > balance) {
                Swal.showValidationMessage('Amount exceeds available balance');
                return false;
            }
            if (value > (totalPayable - alreadyAppliedTotal + 0.01)) {
                Swal.showValidationMessage('Amount exceeds total payable');
                return false;
            }
            return value;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const amount = result.value;
            if (currentApplied) {
                currentApplied.amount = amount;
            } else {
                appliedPayments.push({ type: id, amount: amount, label: label });
            }

            // For universal mode, we select the method as the "primary" if it's the only one, 
            // or just keep selectedPaymentMethod if it was already set.
            if (!selectedPaymentMethod || selectedPaymentMethod === 'credit') {
                selectedPaymentMethod = id;
                // Highlight the element
                document.querySelectorAll('.sidebar-payment-method').forEach(pm => {
                    pm.style.background = 'white';
                    pm.style.borderColor = '#e2e8f0';
                });
                element.style.background = '#e0f2fe';
                element.style.borderColor = '#0d9488';
            }

            updateAppliedPaymentsUI();
            updatePaymentCalculations();
        }
    });
}

function updateAppliedPaymentsUI() {
    const container = document.getElementById('applied-payments-section');
    const list = document.getElementById('applied-payments-list');
    const totalLabel = document.getElementById('total-applied-amount');

    if (appliedPayments.length === 0) {
        container.style.display = 'none';
        return;
    }

    container.style.display = 'block';
    list.innerHTML = '';
    let total = 0;

    appliedPayments.forEach((p, index) => {
        total += p.amount;
        list.innerHTML += `
            <div style="display: flex; justify-content: space-between; align-items: center; background: white; padding: 6px 10px; border-radius: 6px; border: 1px solid rgba(16, 185, 129, 0.3);">
                <span style="color: #065f46; font-size: 12px; font-weight: 600;">${p.label}: ৳${p.amount.toFixed(2)}</span>
                <button onclick="removeAppliedPayment(${index})" style="background: none; border: none; color: #ef4444; cursor: pointer; padding: 0 4px; font-size: 14px;">
                    <i class="fas fa-times-circle"></i>
                </button>
            </div>
        `;
    });

    totalLabel.textContent = '৳' + total.toFixed(2);
}

function removeAppliedPayment(index) {
    appliedPayments.splice(index, 1);
    updateAppliedPaymentsUI();
    updatePaymentCalculations();
}

function setPaymentType(type) {
    document.querySelectorAll('.payment-type-btn').forEach(btn => {
        btn.classList.remove('active');
        btn.style.background = 'transparent';
        btn.style.background = 'transparent'; // Reset background
        btn.style.color = '#64748b'; // Reset color
    });

    const activeBtn = document.querySelector(`.payment-type-btn[data-type="${type}"]`);
    if (activeBtn) {
        activeBtn.classList.add('active');
        activeBtn.style.background = type === 'full' ? '#10b981' : '#ef4444'; // Apply active background
        activeBtn.style.color = 'white'; // Apply active color
    }

    // Logic for Full Payment vs Due
    const grandTotal = parseFloat(document.getElementById('payment-payable').textContent) || 0;
    const amountInput = document.getElementById('amount-received');

    if (type === 'full') {
        if (amountInput) amountInput.value = grandTotal.toFixed(2);
        updatePaymentCalculations(grandTotal);

        // Ensure Pay Amount tab is visible
        switchTab('pay');
    } else {
        // Due payment - intention is to pay LATER, so paid amount is 0?
        // Or pay PARTIAL? Usually "Full Due" in this context might mean "Credit Sale" (Pay 0 now)
        if (amountInput) amountInput.value = '0.00';
        updatePaymentCalculations(0);

        // Ensure Input Amount tab is visible to let them change it if they want
        switchTab('input');
    }
}

function switchTab(tab) {
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
        btn.style.background = '#f1f5f9';
        btn.style.color = '#64748b';
    });

    // Highlight active tab - find button by text content or index? 
    // Simplified: relying on DOM order is flaky, but for now assuming event.target works if called from click
    // When called programmatically (e.g., from setPaymentType), event is undefined.
    // We need to find the button by its data-tab attribute or similar.
    const activeBtn = document.querySelector(`.tab-btn[data-tab="${tab}"]`);
    if (activeBtn) {
        activeBtn.classList.add('active');
        activeBtn.style.background = '#e0f2fe';
        activeBtn.style.color = '#0369a1';
    }

    // Toggle Views
    const payView = document.getElementById('tab-pay-amount');
    const inputView = document.getElementById('tab-input-amount');

    if (tab === 'pay') {
        if (payView) payView.style.display = 'block';
        if (inputView) inputView.style.display = 'none';

        // When switching to Pay Amount, we imply Full Payment usually, 
        // but let's NOT override value unless user explicitly clicked "Full Payment".
        // Just hide the input.
    } else {
        if (payView) payView.style.display = 'none';
        if (inputView) inputView.style.display = 'block';
        // Focus input
        setTimeout(() => {
            const input = document.getElementById('amount-received');
            if (input) input.focus();
        }, 100);
    }
}

function updatePaymentSummary() {
    let subtotal = 0;
    cart.forEach(item => {
        subtotal += item.price * item.qty;
    });

    const discount = parseFloat(document.getElementById('discount-input').value) || 0;
    const taxPercent = parseFloat(document.getElementById('tax-input').value) || 0;
    const shipping = parseFloat(document.getElementById('shipping-input').value) || 0;
    const other = parseFloat(document.getElementById('other-input').value) || 0;

    const taxAmount = (subtotal - discount) * (taxPercent / 100);
    const grandTotal = subtotal - discount + taxAmount + shipping + other;

    // Update all payment fields
    document.getElementById('payment-subtotal').textContent = subtotal.toFixed(2);
    document.getElementById('payment-discount').textContent = discount.toFixed(2);
    document.getElementById('payment-tax').textContent = taxAmount.toFixed(2);
    document.getElementById('payment-shipping').textContent = shipping.toFixed(2);
    document.getElementById('payment-other').textContent = other.toFixed(2);
    document.getElementById('payment-interest').textContent = '0.00';
    const totalBalance = parseFloat(document.getElementById('customer_total_balance').value) || 0;
    const currentDue = totalBalance > 0 ? totalBalance : 0;
    const totalPayableWithDue = grandTotal + currentDue;

    document.getElementById('payment-previous-due').textContent = currentDue.toFixed(2);
    document.getElementById('payment-payable').textContent = grandTotal.toFixed(2);
    document.getElementById('payment-total-payable').textContent = totalPayableWithDue.toFixed(2);

    if (document.getElementById('display-payable-amount')) {
        document.getElementById('display-payable-amount').textContent = totalPayableWithDue.toFixed(2);
    }

    // Update cart items display
    const cartItemsHtml = cart.map((item, index) => `
        <div style="display: flex; justify-content: space-between; padding: 5px 0; border-bottom: 1px solid #e2e8f0;">
            <div style="flex:1">
                 <span style="color: #475569; font-size: 12px; display:block;">${index + 1}. ${item.name}</span>
                 <span style="color: #64748b; font-size: 11px;">${item.qty} x ${parseFloat(item.price).toFixed(2)}</span>
            </div>
            <span style="color: #1e293b; font-weight: 500; font-size: 12px;">${(item.price * item.qty).toFixed(2)}</span>
        </div>
    `).join('');
    document.getElementById('payment-cart-items').innerHTML = cartItemsHtml || '<div style="text-align: center; color: #94a3b8; padding: 20px;">No items</div>';

    // Update customer info in header
    const customerName = document.getElementById('selected-customer-name').textContent || 'Walking Customer';
    const customerPhone = document.getElementById('selected-customer-phone').textContent || '0170000000000';
    document.getElementById('payment-customer-name').textContent = customerName;
    document.getElementById('payment-customer-id').textContent = customerPhone;

    // Maintain current payment state calculation
    // If input amount has value, use it
    const inputAmount = parseFloat(document.getElementById('amount-received').value) || 0;
    if (inputAmount > 0) {
        updatePaymentCalculations(inputAmount);
    } else {
        // Default to full payment if not manually entered? 
        // Or just show total due.
        updatePaymentCalculations(grandTotal);
    }
}

function updatePaymentCalculations(paidAmount) {
    const totalPayable = parseFloat(document.getElementById('payment-total-payable').textContent) || 0;
    const totalApplied = appliedPayments.reduce((sum, p) => sum + p.amount, 0);
    const amountToPay = totalPayable - totalApplied;

    // Fallback if paidAmount not provided: get from input
    let received = (paidAmount !== undefined) ? paidAmount : (parseFloat(document.getElementById('amount-received').value) || 0);

    // Auto-fill remaining amount if just opening modal or totalApplied changed
    if (paidAmount === undefined && (document.getElementById('amount-received').value === '' || parseFloat(document.getElementById('amount-received').value) > amountToPay)) {
        received = amountToPay;
        document.getElementById('amount-received').value = received.toFixed(2);
    }

    const totalPaid = received + totalApplied;
    const due = Math.max(0, totalPayable - totalPaid);
    const balance = Math.max(0, totalPaid - totalPayable);

    document.getElementById('payment-paid').textContent = totalPaid.toFixed(2);
    document.getElementById('payment-due').textContent = due.toFixed(2);
    document.getElementById('payment-balance').textContent = balance.toFixed(2);

    // Update checkout button state for Walking Customer (who can't have due)
    const completeBtn = document.querySelector('.complete-btn');
    if (completeBtn) {
        if (due > 0.01 && customerId === 0) {
            completeBtn.style.opacity = '0.5';
            completeBtn.style.cursor = 'not-allowed';
            completeBtn.disabled = true;
        } else {
            completeBtn.style.opacity = '1';
            completeBtn.style.cursor = 'pointer';
            completeBtn.disabled = false;
        }
    }
}

function calculateChange() {
    const received = parseFloat(document.getElementById('amount-received').value) || 0;
    const grandTotal = parseFloat(document.getElementById('payment-payable').textContent) || 0;
    updatePaymentCalculations(received);
}
