<!-- Template: Classic (Thermal Standard) -->
<div id="receipt-classic" class="receipt-template" style="font-family: 'Courier New', Courier, monospace; font-size: 11px; color: #000; width: 100%; max-width: 100%;">
    
    <!-- Header -->
    <div style="text-align: center; margin-bottom: 10px;">
        <img src="<?= htmlspecialchars($current_store['logo'] ?? '/pos/assets/images/logo-fav.png'); ?>" class="inv-logo" style="max-height: 40px; display: block; margin: 0 auto 5px;">
        <h2 class="inv-store-name" style="margin: 0; font-weight: bold; font-size: 16px; color: #000; text-transform: uppercase;"><?= htmlspecialchars($current_store['store_name'] ?? 'Modern POS'); ?></h2>
        <div class="inv-store-address" style="font-size: 11px; margin-top: 2px;"><?= htmlspecialchars($current_store['address'] ?? 'STORE ADDRESS'); ?></div>
        <div class="inv-store-contact" style="font-size: 11px; margin-top: 2px;">Mobile: <?= htmlspecialchars($current_store['phone'] ?? '--'); ?>, Email: <?= htmlspecialchars($current_store['email'] ?? '--'); ?></div>
        <div class="inv-store-bin" style="font-size: 11px; margin-top: 2px; font-weight: bold;">
             <?php if(!empty($current_store['vat_number'])): ?>BIN: <?= htmlspecialchars($current_store['vat_number']); ?><?php endif; ?>
        </div>
    </div>

    <!-- Invoice Details -->
    <div style="text-align: center; margin-bottom: 10px; font-size: 11px; line-height: 1.4;">
        <div>Invoice ID: <span class="inv-id-val" style="font-weight: bold;">--</span></div>
        <div>Date: <span class="inv-date-val">--</span></div>
        <div>Customer: <span class="inv-customer-val">Walking Customer</span></div>
        <div>Phone: <span class="inv-phone-val">--</span></div>
    </div>

    <div style="text-align: center; font-weight: bold; margin-bottom: 5px; text-transform: uppercase; font-size: 12px;">INVOICE</div>
    <div style="border-bottom: 1px dashed #000; margin-bottom: 5px;"></div>

    <!-- Items Table -->
    <table style="width: 100%; border-collapse: collapse; font-size: 11px;">
        <thead>
            <tr style="border-bottom: 1px dashed #000;">
                <th style="text-align: left; padding: 2px 0; width: 40%;">Item</th>
                <th style="text-align: center; padding: 2px 0; width: 15%;">Qty</th>
                <th style="text-align: right; padding: 2px 0; width: 20%;">Price</th>
                <th style="text-align: right; padding: 2px 0; width: 25%;">Total</th>
            </tr>
        </thead>
        <tbody class="inv-items-list" style="vertical-align: top;">
            <!-- JS Populated -->
        </tbody>
    </table>
    <div style="border-bottom: 1px dashed #000; margin-top: 5px; margin-bottom: 5px;"></div>

    <!-- Totals -->
    <div style="margin-left: auto; width: 100%; max-width: 100%; font-size: 11px;">
        <div style="display: flex; justify-content: space-between; margin-bottom: 3px;">
            <span style="text-align: right; width: 60%;">Subtotal:</span>
            <span class="inv-subtotal-val" style="text-align: right; width: 40%;">0.00</span>
        </div>
        <div style="display: flex; justify-content: space-between; margin-bottom: 3px;">
            <span style="text-align: right; width: 60%;">Discount:</span>
            <span class="inv-discount-val" style="text-align: right; width: 40%;">0.00</span>
        </div>
        <div style="display: flex; justify-content: space-between; margin-bottom: 3px;">
            <span style="text-align: right; width: 60%;">Tax:</span>
            <span class="inv-tax-val" style="text-align: right; width: 40%;">0.00</span>
        </div>
        <div style="display: flex; justify-content: space-between; margin-bottom: 3px;">
            <span style="text-align: right; width: 60%;">Shipping:</span>
            <span class="inv-shipping-val" style="text-align: right; width: 40%;">0.00</span>
        </div>
        <!-- Grand Total Line -->
        <div style="border-top: 1px solid #000; border-bottom: 1px dashed #000; margin: 5px 0; padding: 5px 0;">
            <div style="display: flex; justify-content: space-between; font-weight: bold; font-size: 13px;">
                <span style="text-align: right; width: 60%;">Grand Total:</span>
                <span class="inv-grand-total-val" style="text-align: right; width: 40%;">0.00</span>
            </div>
        </div>
    </div>

    <!-- Payment Stats -->
    <div style="font-size: 11px; margin-bottom: 10px;">
        <div style="display: flex; justify-content: space-between; margin-bottom: 3px;">
             <span style="text-align: right; width: 60%;">Paid Amount:</span>
             <span class="inv-paid-val" style="text-align: right; width: 40%;">0.00</span>
        </div>
        <div class="inv-row-change" style="display: flex; justify-content: space-between; margin-bottom: 3px;">
             <span style="text-align: right; width: 60%;">Change:</span>
             <span class="inv-change-val" style="text-align: right; width: 40%;">0.00</span>
        </div>
        <div style="display: flex; justify-content: space-between; margin-bottom: 3px;">
             <span style="text-align: right; width: 60%;">Previous Due:</span>
             <span class="inv-prev-due" style="text-align: right; width: 40%;">0.00</span>
        </div>
        <div class="inv-row-due" style="display: none; justify-content: space-between; margin-bottom: 3px;">
             <span style="text-align: right; width: 60%;">Current Due:</span>
             <span class="inv-due-val" style="text-align: right; width: 40%;">0.00</span>
        </div>
        <div class="inv-row-installment" style="display: none; justify-content: space-between; margin-bottom: 3px; font-weight: bold; color: #a00;">
             <span style="text-align: right; width: 60%;">Installment Due:</span>
             <span class="inv-installment-val" style="text-align: right; width: 40%;">0.00</span>
        </div>
        <div style="border-top: 1px solid #000; padding-top: 3px; display: flex; justify-content: space-between; font-weight: bold; color: #a00;">
             <span style="text-align: right; width: 60%;">Total Due:</span>
             <span class="inv-total-due" style="text-align: right; width: 40%;">0.00</span>
        </div>
    </div>

    <!-- Amount in Words -->
    <div style="text-align: center; margin: 10px 0; font-size: 10px; font-style: italic;">
        In Text: <span class="inv-in-words" style="font-weight: bold;"></span>
    </div>

    <!-- Payment Breakdown Section -->
    <div style="margin-top: 10px;">
        <div style="border-bottom: 1px dashed #000; margin-bottom: 5px;"></div>
        <div style="text-align: center; font-weight: bold; margin-bottom: 5px; text-transform: uppercase;">PAYMENTS</div>
        <div style="border-bottom: 1px dashed #000; margin-bottom: 5px;"></div>
        
        <table style="width: 100%; border-collapse: collapse; font-size: 10px;">
            <thead>
                <tr style="border-bottom: 1px dashed #000;">
                    <th style="text-align: left;">SL</th>
                    <th style="text-align: left;">Type</th>
                    <th style="text-align: left;">Method</th>
                    <th style="text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody class="inv-payment-list">
                <!-- JS Populated -->
            </tbody>
        </table>
    </div>

    <!-- Footer -->
    <div style="text-align: center; margin-top: 20px; font-size: 11px;">
        <div style="margin-bottom: 5px; display: flex; justify-content: center;"><svg class="inv-barcode-svg" style="max-width: 100%; height: auto;"></svg></div>
        <div class="inv-id-val" style="font-size: 10px; margin-bottom: 5px;">--</div>
        <div class="inv-footer-msg" style="font-weight: bold; margin-bottom: 3px;">Thank you for choosing us!</div>
        <div style="font-size: 9px;">For Support: <span class="inv-support-store"></span></div>
        <div style="font-size: 8px; margin-top: 2px;">Developed by STS</div>
    </div>
</div>
