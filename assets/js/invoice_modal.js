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
        if (document.getElementById('inv-due')) document.getElementById('inv-due').textContent = (data.totals.due || 0).toFixed(2);
        if (document.getElementById('inv-prev-due')) document.getElementById('inv-prev-due').textContent = (data.totals.previousDue || 0).toFixed(2);
        if (document.getElementById('inv-total-due')) document.getElementById('inv-total-due').textContent = (data.totals.totalDue || 0).toFixed(2);

        // Amount in words
        if (document.getElementById('inv-in-words')) {
            document.getElementById('inv-in-words').textContent = numberToWords(parseFloat(data.totals.grandTotal || 0));
        }
    }

    // Payment Method
    if (document.getElementById('inv-payment-method')) document.getElementById('inv-payment-method').textContent = data.paymentMethod || 'Cash';
    if (document.getElementById('inv-payment-amount')) document.getElementById('inv-payment-amount').textContent = (data.totals?.paid || 0).toFixed(2);

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

        // Add Enter key listener for printing
        const handleEnterPrint = function (e) {
            if (e.key === 'Enter' && modal.classList.contains('active')) {
                e.preventDefault();
                window.printInvoice();
            }
        };
        document.removeEventListener('keydown', window._invoiceEnterHandler); // Remove old if exists
        window._invoiceEnterHandler = handleEnterPrint;
        document.addEventListener('keydown', window._invoiceEnterHandler);
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

