/**
 * Invoice Modal JavaScript Functions
 * Reusable invoice modal logic for displaying sale receipts
 */

// Number to words helper
function numberToWords(num) {
    const ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
        'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
        'Seventeen', 'Eighteen', 'Nineteen'];
    const tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

    if (num === 0) return 'Zero ' + (window.currencyName || 'Taka') + ' Only';

    function convertGroup(n) {
        if (n < 20) return ones[n];
        if (n < 100) return tens[Math.floor(n / 10)] + (n % 10 ? ' ' + ones[n % 10] : '');
        return ones[Math.floor(n / 100)] + ' Hundred' + (n % 100 ? ' ' + convertGroup(n % 100) : '');
    }

    let result = '';
    const crore = Math.floor(num / 10000000);
    const lakh = Math.floor((num % 10000000) / 100000);
    const thousand = Math.floor((num % 100000) / 1000);
    const remainder = Math.floor(num % 1000);
    const paisa = Math.round((num - Math.floor(num)) * 100);

    if (crore) result += convertGroup(crore) + ' Crore ';
    if (lakh) result += convertGroup(lakh) + ' Lakh ';
    if (thousand) result += convertGroup(thousand) + ' Thousand ';
    if (remainder) result += convertGroup(remainder);

    result = result.trim() + ' ' + (window.currencyName || 'Taka');
    if (paisa) result += ' and ' + convertGroup(paisa) + ' ' + (window.currencyPaisaName || 'Cents');
    result += ' Only';

    return result;
}

/**
 * Open Invoice Modal with invoice data
 * @param {Object} data - Invoice data object
 * @param {string} data.invoiceId - Invoice ID
 * @param {Object} data.store - Store info {name, address, city, phone, email}
 * @param {Object} data.customer - Customer info {name, phone}
 * @param {Array} data.items - Cart items [{name, qty, price, unit}]
 * @param {Object} data.totals - Totals {subtotal, discount, tax, shipping, grandTotal, paid, due, previousDue, totalDue}
 * @param {string} data.paymentMethod - Payment method name
 * @param {Function} data.onClose - Callback when modal closes (optional)
 */
window.openInvoiceModal = function (data) {
    // Apply Receipt Template Class
    let receiptTemplate = '';
    // 1. Try to get template from currently selected store in map (Most Reliable)
    const storeSelect = document.getElementById('store_select');
    if (storeSelect && window.storeSettingsMap) {
        const currentStoreId = storeSelect.value;
        if (window.storeSettingsMap[currentStoreId]) {
            receiptTemplate = window.storeSettingsMap[currentStoreId].receipt_template;
        }
    }
    // 2. Fallback
    if (!receiptTemplate && window.posSettings) {
        receiptTemplate = window.posSettings.receipt_template;
    }

    if (receiptTemplate) {
        document.body.classList.remove('template-thermal_58mm', 'template-thermal_80mm', 'template-a4');
        document.body.classList.add('template-' + receiptTemplate);
    }

    // Set Invoice ID
    if (document.getElementById('inv-id')) document.getElementById('inv-id').textContent = data.invoiceId || '';
    if (document.getElementById('inv-modal-id')) document.getElementById('inv-modal-id').textContent = data.invoiceId || '';
    if (document.getElementById('inv-date')) document.getElementById('inv-date').textContent = new Date().toLocaleString();

    // Store Info
    if (data.store) {
        if (document.getElementById('inv-store-name')) document.getElementById('inv-store-name').textContent = data.store.name || '';
        if (document.getElementById('inv-store-address')) document.getElementById('inv-store-address').textContent = data.store.address || '';
        if (document.getElementById('inv-store-city')) document.getElementById('inv-store-city').textContent = data.store.city || '';
        if (document.getElementById('inv-store-contact')) document.getElementById('inv-store-contact').textContent = `Mobile: ${data.store.phone || ''}, Email: ${data.store.email || ''}`;

        // BIN/VAT Number
        if (document.getElementById('inv-store-bin')) {
            const binNumber = data.store.vat_number || data.store.bin_number || '';
            if (binNumber) {
                document.getElementById('inv-store-bin').textContent = `BIN: ${binNumber}`;
            } else {
                document.getElementById('inv-store-bin').textContent = '';
            }
        }
    }

    // Customer Info
    if (data.customer) {
        if (document.getElementById('inv-customer')) document.getElementById('inv-customer').textContent = data.customer.name || 'Walking Customer';
        if (document.getElementById('inv-phone')) document.getElementById('inv-phone').textContent = data.customer.phone || '';
    }

    // Items
    const itemsBody = document.getElementById('inv-items');
    if (itemsBody && data.items) {
        itemsBody.innerHTML = '';
        data.items.forEach((item, index) => {
            const total = (parseFloat(item.price) * parseFloat(item.qty)).toFixed(2);
            itemsBody.innerHTML += `
                <tr style="border-bottom: 1px dotted #e5e7eb;">
                    <td style="text-align: left; padding: 5px 0;">
                        ${index + 1}. ${item.name}
                    </td>
                    <td style="text-align: right; padding: 5px 0;">${item.qty} ${item.unit || ''}</td>
                    <td style="text-align: right; padding: 5px 0;">${parseFloat(item.price).toFixed(2)}</td>
                    <td style="text-align: right; padding: 5px 0;">${total}</td>
                </tr>
            `;
        });
    }

    // Totals
    if (data.totals) {
        if (document.getElementById('inv-subtotal')) document.getElementById('inv-subtotal').textContent = (data.totals.subtotal || 0).toFixed(2);
        if (document.getElementById('inv-discount')) document.getElementById('inv-discount').textContent = (data.totals.discount || 0).toFixed(2);
        if (document.getElementById('inv-tax')) document.getElementById('inv-tax').textContent = (data.totals.tax || 0).toFixed(2);
        if (document.getElementById('inv-shipping')) document.getElementById('inv-shipping').textContent = (data.totals.shipping || 0).toFixed(2);
        if (document.getElementById('inv-grand-total')) document.getElementById('inv-grand-total').textContent = (data.totals.grandTotal || 0).toFixed(2);
        if (document.getElementById('inv-paid')) document.getElementById('inv-paid').textContent = (data.totals.paid || 0).toFixed(2);

        const due = data.totals.due || 0;
        const change = Math.max(0, (data.totals.paid || 0) - (data.totals.grandTotal || 0)); // Re-calculate safely
        const isInstallment = data.totals.isInstallment || false;

        // Reset Rows
        if (document.getElementById('row-change')) document.getElementById('row-change').style.display = 'table-row';
        if (document.getElementById('row-due')) document.getElementById('row-due').style.display = 'none';
        if (document.getElementById('row-installment')) document.getElementById('row-installment').style.display = 'none';

        // 1. Installment Case
        if (isInstallment) {
            // Show Installment Row instead of Due
            if (document.getElementById('row-installment')) {
                document.getElementById('row-installment').style.display = 'table-row';
                if (document.getElementById('inv-installment')) document.getElementById('inv-installment').textContent = due.toFixed(2);
            }
            // Hide standard Due row
            if (document.getElementById('row-due')) document.getElementById('row-due').style.display = 'none';
            // Show Change row if there is change, otherwise can hide or show 0
            if (document.getElementById('row-change')) {
                document.getElementById('label-change').textContent = 'Change:';
                document.getElementById('inv-change').textContent = change.toFixed(2);
            }
        }
        // 2. Regular Due Case
        else if (due > 0.01) {
            // Show Due Row
            if (document.getElementById('row-due')) {
                document.getElementById('row-due').style.display = 'table-row';
                if (document.getElementById('inv-due')) document.getElementById('inv-due').textContent = due.toFixed(2);
            }
            // Hide Change row (or repurpose) - User said "jodi due hoi tahole Deu: te dekhabe"
            // We can hide Change row to be clean, or show 0. Let's hide it to emphasize Due.
            if (document.getElementById('row-change')) document.getElementById('row-change').style.display = 'none';
        }
        // 3. Paid/Change Case (No Due)
        else {
            // Standard Change display
            if (document.getElementById('row-change')) {
                document.getElementById('row-change').style.display = 'table-row';
                document.getElementById('label-change').textContent = 'Change:';
                document.getElementById('inv-change').textContent = change.toFixed(2);
            }
            if (document.getElementById('row-due')) document.getElementById('row-due').style.display = 'none';
        }

        if (document.getElementById('inv-prev-due')) document.getElementById('inv-prev-due').textContent = (data.totals.previousDue || 0).toFixed(2);
        if (document.getElementById('inv-total-due')) document.getElementById('inv-total-due').textContent = (data.totals.totalDue || 0).toFixed(2);

        // Amount in words
        if (document.getElementById('inv-in-words')) {
            document.getElementById('inv-in-words').textContent = numberToWords(parseFloat(data.totals.grandTotal || 0));
        }
    }

    // Payment Methods - Support for multiple payments
    const paymentListBody = document.getElementById('inv-payment-list');
    if (paymentListBody) {
        paymentListBody.innerHTML = ''; // Clear existing rows

        // Check if we have multiple payment methods
        if (data.payments && Array.isArray(data.payments) && data.payments.length > 0) {
            // Multiple payments
            data.payments.forEach((payment, index) => {
                const paymentDate = payment.date ? new Date(payment.date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : new Date().toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });

                paymentListBody.innerHTML += `
                    <tr style="border-bottom: 1px dashed #ddd;">
                        <td style="padding: 5px 0;">${index + 1}</td>
                        <td style="padding: 5px 0;">
                            <div style="font-weight: 600; font-size: 10px;">${payment.type || 'Full Payment'}</div>
                            <div style="font-size: 8px; color: #666;">${paymentDate}</div>
                        </td>
                        <td style="padding: 5px 0;">
                            <div style="font-weight: 600; font-size: 10px;">${payment.method || 'Cash on Hand'}</div>
                        </td>
                        <td style="text-align: right; padding: 5px 0; font-weight: 600; color: #10b981; font-size: 10px;">${parseFloat(payment.amount || 0).toFixed(2)}</td>
                    </tr>
                `;
            });
        } else {
            // Single payment (backward compatibility)
            const paymentMethod = data.paymentMethod || 'Cash on Hand';
            const paymentAmount = data.totals?.paid || 0;
            const paymentDate = new Date().toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });

            paymentListBody.innerHTML = `
                <tr style="border-bottom: 1px dashed #ddd;">
                    <td style="padding: 5px 0;">1</td>
                    <td style="padding: 5px 0;">
                        <div style="font-weight: 600; font-size: 10px;">Full Payment</div>
                        <div style="font-size: 8px; color: #666;">${paymentDate}</div>
                    </td>
                    <td style="padding: 5px 0;">
                        <div style="font-weight: 600; font-size: 10px;">${paymentMethod}</div>
                    </td>
                    <td style="text-align: right; padding: 5px 0; font-weight: 600; color: #10b981; font-size: 10px;">${parseFloat(paymentAmount).toFixed(2)}</td>
                </tr>
            `;
        }
    }

    // Update support store name in footer
    if (document.getElementById('inv-support-store') && data.store) {
        document.getElementById('inv-support-store').textContent = data.store.name || 'ALL STORES';
    }

    // Barcode
    if (typeof JsBarcode !== 'undefined' && data.invoiceId) {
        try {
            JsBarcode("#inv-barcode", data.invoiceId, {
                format: "CODE128",
                width: 2,
                height: 40,
                displayValue: true,
                fontSize: 10,
                margin: 0
            });
        } catch (e) {
            console.warn('Barcode generation failed:', e);
        }
    }

    // Store onClose callback
    window._invoiceModalOnClose = data.onClose || null;

    // Show modal
    const modal = document.getElementById('invoiceModal');
    if (modal) {
        modal.style.display = 'flex';
        modal.classList.add('active');

        // Auto-print disabled per user request
        // setTimeout(() => {
        //     window.printInvoice();
        // }, 500);

        // Enter key listener for printing is handled globally in pos.js
        // Removing local listener here to prevent double print dialogs
        // document.addEventListener('keydown', window._invoiceEnterHandler); // legacy
    }
};

// Print Invoice Logic
window.printInvoice = function () {
    // Inject dynamic @page styles
    const styleId = 'dynamic-print-styles';
    let styleEl = document.getElementById(styleId);
    if (styleEl) styleEl.remove(); // Force recreate to ensure update matches

    styleEl = document.createElement('style');
    styleEl.id = styleId;
    document.head.appendChild(styleEl);

    let finalReceiptTemplate = '';

    // 1. Try to get template from currently selected store in map (Most Reliable)
    const storeSelect = document.getElementById('store_select');
    if (storeSelect && window.storeSettingsMap) {
        const currentStoreId = storeSelect.value;
        // Check both string and int keys to be safe
        let storeSettings = window.storeSettingsMap[currentStoreId] || window.storeSettingsMap[parseInt(currentStoreId)];
        if (storeSettings) {
            finalReceiptTemplate = storeSettings.receipt_template;
        }
    }

    // 2. Fallback to global window.posSettings
    if (!finalReceiptTemplate && window.posSettings) {
        finalReceiptTemplate = window.posSettings.receipt_template;
    }

    let css = '@page { size: auto; margin: 0mm; }'; // Default with zero margin

    if (finalReceiptTemplate) {
        if (finalReceiptTemplate === 'thermal_58mm') {
            css = `
                @page { size: 58mm auto; margin: 0mm; }
                html, body { width: 58mm !important; min-width: 58mm !important; max-width: 58mm !important; }
                #invoiceModal .pos-modal-content { width: 100% !important; border: none !important; margin: 0 !important; padding: 0 !important; }
            `;
        } else if (finalReceiptTemplate === 'thermal_80mm') {
            css = `
                @page { size: 80mm auto; margin: 0mm; }
                html, body { width: 80mm !important; min-width: 80mm !important; max-width: 80mm !important; }
                #invoiceModal .pos-modal-content { width: 100% !important; border: none !important; margin: 0 !important; padding: 0 !important; }
            `;
        }
    }
    styleEl.innerHTML = css;

    window.print();

    // Automatically close the modal after printing or cancelling
    setTimeout(() => {
        window.closeInvoiceModal();
        // Clean up styles
        if (styleEl) styleEl.remove();
    }, 500);
};

// Close Invoice Modal
window.closeInvoiceModal = function () {
    const modal = document.getElementById('invoiceModal');
    if (modal) {
        modal.style.display = 'none';
        modal.classList.remove('active');
    }
    document.body.classList.remove('template-thermal_58mm', 'template-thermal_80mm', 'template-a4');

    // Also explicitly ensure payment modal is closed
    const paymentModal = document.getElementById('paymentModal');
    if (paymentModal) {
        paymentModal.style.display = 'none';
        paymentModal.classList.remove('active');
    }

    // Call onClose callback if provided
    if (window._invoiceModalOnClose && typeof window._invoiceModalOnClose === 'function') {
        window._invoiceModalOnClose();
        window._invoiceModalOnClose = null; // Clear it after use
    }
};

