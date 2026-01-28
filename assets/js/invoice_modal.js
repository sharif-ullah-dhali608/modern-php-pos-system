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
    // 1. Determine Receipt Template (Default to classic)
    let receiptTemplate = 'classic';
    const storeSelect = document.getElementById('store_select');

    if (storeSelect && window.storeSettingsMap) {
        const currentStoreId = storeSelect.value;
        const storeSettings = window.storeSettingsMap[currentStoreId] || window.storeSettingsMap[parseInt(currentStoreId)];
        if (storeSettings && storeSettings.receipt_template) {
            receiptTemplate = storeSettings.receipt_template;
        }
    } else if (window.posSettings && window.posSettings.receipt_template) {
        receiptTemplate = window.posSettings.receipt_template;
    }

    // 2. Hide all templates and show active one
    const allTpls = document.querySelectorAll('.design-tpl');
    allTpls.forEach(t => t.classList.add('hidden'));

    const activeTpl = document.getElementById('tpl-' + receiptTemplate) || document.getElementById('tpl-classic');
    if (activeTpl) activeTpl.classList.remove('hidden');

    // 3. Helper to set text in active template
    const setTplText = (selector, text) => {
        const el = activeTpl.querySelector(selector);
        if (el) el.textContent = text;
    };

    // 4. Populate Static Data
    setTplText('.inv-id-val', data.invoiceId || '');
    setTplText('.inv-date-val', new Date().toLocaleString());

    if (data.store) {
        setTplText('.inv-store-name', data.store.name || '');
        setTplText('.inv-store-address', data.store.address || '');
        setTplText('.inv-store-city', data.store.city || '');
        setTplText('.inv-store-contact', `Mobile: ${data.store.phone || ''}, Email: ${data.store.email || ''}`);

        const binEl = activeTpl.querySelector('.inv-store-bin');
        if (binEl) {
            const binNumber = data.store.vat_number || data.store.bin_number || '';
            binEl.textContent = binNumber ? `BIN: ${binNumber}` : '';
        }

        // Update logo if available
        const logoImg = activeTpl.querySelector('.inv-logo');
        if (logoImg && data.store.logo) logoImg.src = data.store.logo;
    }

    if (data.customer) {
        setTplText('.inv-customer-val', data.customer.name || 'Walking Customer');
        setTplText('.inv-phone-val', data.customer.phone || '');
    }

    // 5. Populate Items
    const itemsBody = activeTpl.querySelector('.inv-items-list');
    if (itemsBody && data.items) {
        itemsBody.innerHTML = '';
        data.items.forEach((item, index) => {
            const total = (parseFloat(item.price) * parseFloat(item.qty)).toFixed(2);

            // Design-specific item row logic
            if (receiptTemplate === 'classic') {
                itemsBody.innerHTML += `
                    <tr style="border-bottom: 1px dashed #000;">
                        <td style="text-align: left; padding: 2px 0; width: 40%; word-break: break-word;">${item.name}</td>
                        <td style="text-align: center; padding: 2px 0; width: 15%;">${item.qty}</td>
                        <td style="text-align: right; padding: 2px 0; width: 20%;">${parseFloat(item.price).toFixed(2)}</td>
                        <td style="text-align: right; padding: 2px 0; width: 25%;">${total}</td>
                    </tr>
                `;
            } else if (receiptTemplate === 'minimal') {
                itemsBody.innerHTML += `
                    <tr>
                        <td style="padding: 2px 0;">${item.name} x ${item.qty}</td>
                        <td style="text-align: right; padding: 2px 0;">${total}</td>
                    </tr>
                `;
            } else {
                // Modern (Default)
                itemsBody.innerHTML += `
                    <tr style="border-bottom: 1px dashed #eee;">
                        <td style="padding: 8px 0;">
                            <div style="font-weight: 600;">${item.name}</div>
                            <div style="font-size: 10px; color: #666;">${item.qty} x ${parseFloat(item.price).toFixed(2)}</div>
                        </td>
                        <td style="text-align: right; font-weight: 700;">${total}</td>
                    </tr>
                `;
            }
        });
    }

    // 6. Totals
    if (data.totals) {
        setTplText('.inv-subtotal-val', (data.totals.subtotal || 0).toFixed(2));
        setTplText('.inv-discount-val', (data.totals.discount || 0).toFixed(2));
        setTplText('.inv-tax-val', (data.totals.tax || 0).toFixed(2));
        setTplText('.inv-shipping-val', (data.totals.shipping || 0).toFixed(2));
        setTplText('.inv-grand-total-val', (data.totals.grandTotal || 0).toFixed(2));
        setTplText('.inv-paid-val', (data.totals.paid || 0).toFixed(2));

        const inWordsEl = activeTpl.querySelector('.inv-in-words');
        if (inWordsEl) inWordsEl.textContent = numberToWords(parseFloat(data.totals.grandTotal || 0));
    }

    const due = data.totals.due || 0;
    const change = Math.max(0, (data.totals.paid || 0) - (data.totals.grandTotal || 0)); // Re-calculate safely
    const isInstallment = data.totals.isInstallment || false;

    // Helper to toggle display
    const setDisplay = (selector, show) => {
        const el = activeTpl.querySelector(selector);
        if (el) el.style.display = show ? (el.tagName === 'TR' ? 'table-row' : 'flex') : 'none'; // Flex for div rows, table-row for tr
    };
    const setText = (selector, val) => {
        const el = activeTpl.querySelector(selector);
        if (el) el.textContent = val;
    };

    // Reset Rows
    setDisplay('.inv-row-change', true);
    setDisplay('.inv-row-due', false);
    setDisplay('.inv-row-installment', false);

    // 1. Installment Case
    if (isInstallment) {
        setDisplay('.inv-row-installment', true);
        setText('.inv-installment-val', due.toFixed(2));

        setDisplay('.inv-row-due', false);

        if (change > 0) {
            setDisplay('.inv-row-change', true);
            setText('.inv-change-val', change.toFixed(2));
        } else {
            setDisplay('.inv-row-change', false);
        }
    }
    // 2. Regular Due Case
    else if (due > 0.01) {
        setDisplay('.inv-row-due', true);
        setText('.inv-due-val', due.toFixed(2));

        setDisplay('.inv-row-change', false);
    }
    // 3. Paid/Change Case (No Due)
    else {
        setDisplay('.inv-row-change', true);
        setText('.inv-change-val', change.toFixed(2));
        setDisplay('.inv-row-due', false);
    }

    if (activeTpl.querySelector('.inv-prev-due')) activeTpl.querySelector('.inv-prev-due').textContent = (data.totals.previousDue || 0).toFixed(2);
    if (activeTpl.querySelector('.inv-total-due')) activeTpl.querySelector('.inv-total-due').textContent = (data.totals.totalDue || 0).toFixed(2);

    // Amount in words
    if (activeTpl.querySelector('.inv-in-words')) {
        activeTpl.querySelector('.inv-in-words').textContent = numberToWords(parseFloat(data.totals.grandTotal || 0));
    }

    // 7. Payment Methods - Support for multiple payments
    const paymentListBody = activeTpl.querySelector('.inv-payment-list');
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

    // 8. Footer Info
    const supportEl = activeTpl.querySelector('.inv-support-store');
    if (supportEl && data.store) {
        supportEl.textContent = data.store.name || 'ALL STORES';
    }

    // 9. Barcode
    const barcodeSvg = activeTpl.querySelector('.inv-barcode-svg');
    if (typeof JsBarcode !== 'undefined' && data.invoiceId && barcodeSvg) {
        try {
            JsBarcode(barcodeSvg, data.invoiceId, {
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

    // 10. Show modal
    window._invoiceModalOnClose = data.onClose || null;
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

window.printInvoice = function () {
    // 1. Get Printer Hardware Profile
    let hardwareProfile = 'thermal_80mm'; // Default
    const storeSelect = document.getElementById('store_select');

    console.log('--- Print Trace ---');
    console.log('Initial profile:', hardwareProfile);

    if (storeSelect && window.storeSettingsMap && window.printerProfiles) {
        let storeId = storeSelect.value;
        console.log('Active Store ID:', storeId);

        // If 'all', try to get the first available store with settings
        if (storeId === 'all') {
            const keys = Object.keys(window.storeSettingsMap);
            if (keys.length > 0) storeId = keys[0];
            console.log('Fallover Store ID:', storeId);
        }

        const storeSettings = window.storeSettingsMap[storeId];
        if (storeSettings && storeSettings.receipt_printer) {
            const printerId = storeSettings.receipt_printer;
            console.log('Mapped Printer ID:', printerId);

            const profile = window.printerProfiles[printerId];
            console.log('Resolved Profile from DB:', profile);

            if (profile) {
                // Support both specific and generic names
                if (profile === 'thermal' || profile === 'thermal_80mm') hardwareProfile = 'thermal_80mm';
                else if (profile === 'thermal_58mm') hardwareProfile = 'thermal_58mm';
                else if (profile === 'a4' || profile === 'standard') hardwareProfile = 'a4';
                else hardwareProfile = profile;
            }
        }
    }

    console.log('Final hardwareProfile used:', hardwareProfile);
    console.log('-------------------');

    // 2. Open New Window for Printing
    const printWindow = window.open('', '_blank', 'width=800,height=900');
    if (!printWindow) {
        alert('Please allow popups to print the receipt.');
        return;
    }

    // 3. Prepare styles for the new window
    let css = '';
    if (hardwareProfile === 'thermal_58mm') {
        css = `
            @page { size: 58mm auto; margin: 0mm; }
            body { 
                width: 58mm; margin: 0 auto; padding: 0; 
                font-family: 'Courier New', Courier, monospace; 
                display: flex; justify-content: center;
                -webkit-print-color-adjust: exact;
            }
            .receipt-template { width: 58mm; padding: 2mm; box-sizing: border-box; display: block !important; }
        `;
    } else if (hardwareProfile === 'thermal_80mm') {
        css = `
            @page { size: 80mm auto; margin: 0mm; }
            body { 
                width: 80mm; margin: 0 auto; padding: 0; 
                font-family: 'Courier New', Courier, monospace; 
                display: flex; justify-content: center;
                -webkit-print-color-adjust: exact;
            }
            .receipt-template { width: 80mm; padding: 4mm; box-sizing: border-box; display: block !important; }
        `;
    } else {
        // Professional A4 Style (Matching about:blank System Style)
        css = `
            @page { size: A4; margin: 15mm; }
            body { 
                width: 100%; margin: 0; padding: 0; 
                background: #fff; 
                font-family: 'Inter', system-ui, -apple-system, sans-serif;
                display: flex; justify-content: center;
                -webkit-print-color-adjust: exact;
            }
            .receipt-template { 
                width: 180mm; 
                padding: 10mm; 
                box-sizing: border-box; 
                border: 1px solid #eee;
                display: block !important;
            }
        `;
    }

    // Common styles for both
    css += `
        * { box-sizing: border-box; }
        img { max-width: 100%; height: auto; }
        table { width: 100%; border-collapse: collapse; }
        .inv-barcode-container svg { max-width: 100%; height: auto; }
        .inv-row-installment { color: #a00 !important; }
    `;

    // 4. Get active template content
    const activeTpl = document.querySelector('.receipt-template:not([style*="display: none"])');
    if (!activeTpl) {
        printWindow.close();
        return;
    }
    const receiptHtml = activeTpl.outerHTML;

    // 5. Construct the print document
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Print Receipt - ${document.querySelector('.inv-id-val')?.textContent || 'Invoice'}</title>
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
            <style>${css}</style>
        </head>
        <body>
            ${receiptHtml}
            <script>
                // Wait for any images/barcodes to load
                window.onload = function() {
                    setTimeout(() => {
                        window.print();
                        window.close();
                    }, 250);
                };
            </script>
        </body>
        </html>
    `);

    printWindow.document.close();

    // Automatically close the modal on original page
    setTimeout(() => {
        window.closeInvoiceModal();
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

// Standard Receipt Printer (Used for AJAX Modals)
window.printInvoiceReceipt = function () {
    const printWindow = window.open('', '_blank', 'width=800,height=900');
    if (!printWindow) {
        alert('Please allow popups to print.');
        return;
    }

    const receiptContainer = document.getElementById('printableArea');
    if (!receiptContainer) {
        console.error('Printable area not found');
        printWindow.close();
        return;
    }

    // Clone and remove the no-print section for the new window content
    const clone = receiptContainer.cloneNode(true);
    const noPrint = clone.querySelector('.no-print');
    if (noPrint) noPrint.remove();

    const content = clone.innerHTML;
    // Try to find invoice ID from various potential sources in the modal
    const invoiceId = document.querySelector('.font-bold.text-sm')?.textContent || 'Invoice';

    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Print Receipt - ${invoiceId}</title>
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
            <style>
                body { 
                    width: 100%; margin: 0; padding: 0; 
                    background: #fff !important; 
                    font-family: 'Inter', sans-serif;
                    display: flex; justify-content: center;
                    -webkit-print-color-adjust: exact;
                }
                .receipt-container { 
                    width: 480px; 
                    padding: 10mm; 
                    box-sizing: border-box; 
                    border: 1px solid #eee;
                    display: block !important;
                }
                table { width: 100%; border-collapse: collapse; }
                th { text-align: left; border-bottom: 1px dashed #000; padding: 5px 0; font-weight: bold; }
                td { padding: 4px 0; vertical-align: top; }
                .text-right { text-align: right; }
                .text-center { text-align: center; }
                .font-bold { font-weight: bold; }
                .uppercase { text-transform: uppercase; }
                .mb-4 { margin-bottom: 1rem; }
                .mb-3 { margin-bottom: 0.75rem; }
                .mb-2 { margin-bottom: 0.5rem; }
                .mb-1 { margin-bottom: 0.25rem; }
                .mt-1 { margin-top: 0.25rem; }
                .mt-2 { margin-top: 0.5rem; }
                .mt-4 { margin-top: 1rem; }
                .mt-6 { margin-top: 1.5rem; }
                .pt-1 { padding-top: 0.25rem; }
                .py-1 { padding-top: 0.25rem; padding-bottom: 0.25rem; }
                .text-xs { font-size: 0.75rem; }
                .text-sm { font-size: 0.875rem; }
                .text-gray-500 { color: #6b7280; }
                .text-gray-600 { color: #4b5563; }
                .italic { font-style: italic; }
                .border-top-dashed { border-top: 1px dashed #000; }
                .border-bottom-dashed { border-bottom: 1px dashed #000; }
                .flex { display: flex; }
                .justify-center { justify-content: center; }
            </style>
        </head>
        <body>
            <div class="receipt-container">
                ${content}
            </div>
            <script>
                window.onload = function() {
                    setTimeout(() => {
                        window.print();
                        window.close();
                    }, 300);
                };
            <\/script>
        </body>
        </html>
    `);
    printWindow.document.close();
};

