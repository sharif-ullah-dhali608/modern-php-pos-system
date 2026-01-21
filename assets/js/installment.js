
var installmentMode = false;

function toggleInstallment() {
    const section = document.getElementById('installment-details-section');
    const btn = document.getElementById('installment-toggle-btn');
    const amountInput = document.getElementById('amount-received');
    const totalPayableEl = document.getElementById('payment-total-payable');
    const previousDueEl = document.getElementById('payment-previous-due');
    const downPaymentLabel = document.getElementById('down-payment-label');

    // Check if customer already has installment
    const hasInstallment = parseInt(document.getElementById('customer_has_installment')?.value || 0);
    const customerId = parseInt(document.getElementById('selected_customer_id')?.value || 0);

    if (!installmentMode && hasInstallment === 1 && customerId !== 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Installment Not Allowed',
            text: 'This customer already has an active installment. Please complete the existing installment before creating a new one.',
            confirmButtonColor: '#0d9488',
            target: document.getElementById('paymentModal')
        });
        return;
    }

    installmentMode = !installmentMode;

    if (installmentMode) {
        section.style.display = 'block';
        btn.classList.add('active');

        // Show DOWN PAYMENT 30% label
        if (downPaymentLabel) {
            downPaymentLabel.style.display = 'block';
        }

        updateInstallmentCalculations();

        // Auto select Cash by default for Down Payment
        // Look for Cash element in the grid
        const cashMethod = document.querySelector('.sidebar-payment-method[data-id="1"]') ||
            document.querySelector('.sidebar-payment-method[onclick*="cash"]');
        if (cashMethod) {
            cashMethod.click();
        }
    } else {
        section.style.display = 'none';
        btn.classList.remove('active');

        // Hide DOWN PAYMENT 30% label
        if (downPaymentLabel) {
            downPaymentLabel.style.display = 'none';
        }

        // Reset amount to full total payable (grand total + previous due) if installment turned off
        const total = parseFloat(totalPayableEl ? totalPayableEl.textContent : 0) || 0;
        if (amountInput) {
            amountInput.value = total.toFixed(2);
            // Trigger payment_logic.js calculation
            if (typeof updatePaymentCalculations === 'function') {
                updatePaymentCalculations();
            }
        }
    }
}

function resetInstallment() {
    installmentMode = false;
    const section = document.getElementById('installment-details-section');
    const btn = document.getElementById('installment-toggle-btn');
    const downPaymentLabel = document.getElementById('down-payment-label');

    if (section) section.style.display = 'none';
    if (btn) btn.classList.remove('active');

    // Hide DOWN PAYMENT 30% label
    if (downPaymentLabel) {
        downPaymentLabel.style.display = 'none';
    }
}

function updateInstallmentCalculations() {
    if (!installmentMode) return;

    const totalPayableEl = document.getElementById('payment-payable'); // Just the current sale grand total
    const grandTotal = parseFloat(totalPayableEl ? totalPayableEl.textContent : 0) || 0;

    // Down payment is 30% of CURRENT SALE
    const downPayment = grandTotal * 0.3;
    const remainingBalance = grandTotal * 0.7;

    const interestPercent = parseFloat(document.getElementById('inst-interest-percent').value) || 0;
    const interestAmount = (remainingBalance * interestPercent) / 100;

    const interestAmountEl = document.getElementById('inst-interest-amount');
    const summaryInterestEl = document.getElementById('payment-interest');
    if (interestAmountEl) {
        const formattedInterest = interestAmount.toFixed(2);
        interestAmountEl.value = formattedInterest;
        if (summaryInterestEl) {
            summaryInterestEl.textContent = formattedInterest;
        }
    }

    // Update the big blue box with down payment
    const amountInput = document.getElementById('amount-received');
    if (amountInput) {
        amountInput.value = downPayment.toFixed(2);
        // Trigger payment_logic.js to update summary (Paid, Due, Balance)
        if (typeof updatePaymentCalculations === 'function') {
            updatePaymentCalculations();
        }
    }
}

// Global listener for changes in installment fields
document.addEventListener('DOMContentLoaded', function () {
    // We attach listeners to the fields inside the modal. 
    // Since the modal content is static in PHP, we can wait for DOMContentLoaded if it's included via include.
    // However, if the modal is injected, we might need to use delegation.

    document.addEventListener('input', function (e) {
        if (e.target.id && ['inst-duration', 'inst-interval', 'inst-count', 'inst-interest-percent'].includes(e.target.id)) {
            updateInstallmentCalculations();
        }
    });
});
