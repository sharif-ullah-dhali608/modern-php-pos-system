<!-- Invoice Modal - Reusable Component -->
<div class="pos-modal" id="invoiceModal">
    <div class="pos-modal-content" style="max-width: 400px; padding: 0; border-radius: 0;">
        <div class="pos-modal-header" style="background: #84cc16; color: white; padding: 10px 15px; border-radius: 0; justify-content: space-center;">
            <div style="font-size: 14px; font-weight: 500;">
                <i class="fas fa-eye"></i> <span id="inv-modal-id">12026/00000008</span>
            </div>
            <button class="close-btn" onclick="closeInvoiceModal()" style="display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-times" style="font-size: 16px;"></i>
            </button>   
        </div>
        <div class="pos-modal-body" style="padding: 20px; text-align: center; font-family: 'Courier New', Courier, monospace; font-size: 12px; color: #000;">
            <div id="invoice-content">
                <div style="margin-bottom: 10px;">
                    <img src="/pos/assets/images/logo-fav.png" style="max-height: 50px; display: block; margin: 0 auto 5px; ">
                    <h2 id="inv-store-name" style="margin: 0; font-weight: bold; font-size: 18px; color: #ea580c;">Modern POS</h2>
                    <h3 id="inv-store-address" style="margin: 5px 0 0 0; font-size: 14px;">STORE 01</h3>
                    <div id="inv-store-city">Earth</div>
                    <div id="inv-store-contact">Mobile: --, Email: --</div>
                    <div id="inv-store-bin" style="font-size: 11px; margin-top: 2px;"></div>
                </div>
                
                <div style="text-align: center; margin-bottom: 15px; line-height: 1.4;">
                    <div>Invoice ID: <span id="inv-id"></span></div>
                    <div>Date: <span id="inv-date"></span></div>
                    <div>Customer: <span id="inv-customer"></span></div>
                    <div>Phone: <span id="inv-phone"></span></div>
                </div>
                
                <div style="text-align: center; margin: 10px 0; font-weight: bold; border-bottom: 1px solid #000; padding-bottom: 5px;">INVOICE</div>
                
                <table style="width: 100%; margin-bottom: 15px; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 1px dashed #000;">
                            <th style="text-align: left; padding: 5px 0;">Item</th>
                            <th style="text-align: right; padding: 5px 0;">Qty</th>
                            <th style="text-align: right; padding: 5px 0;">Price</th>
                            <th style="text-align: right; padding: 5px 0;">Total</th>
                        </tr>
                    </thead>
                    <tbody id="inv-items">
                        <!-- Items will be populated here -->
                    </tbody>
                </table>
                
                <div style="display: flex; justify-content: flex-end; margin-bottom: 15px;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr><td style="text-align: right; padding: 2px;">Subtotal:</td><td style="text-align: right; padding: 2px;" id="inv-subtotal">0.00</td></tr>
                        <tr><td style="text-align: right; padding: 2px;">Discount:</td><td style="text-align: right; padding: 2px;" id="inv-discount">0.00</td></tr>
                        <tr><td style="text-align: right; padding: 2px;">Tax:</td><td style="text-align: right; padding: 2px;" id="inv-tax">0.00</td></tr>
                        <tr><td style="text-align: right; padding: 2px;">Shipping:</td><td style="text-align: right; padding: 2px;" id="inv-shipping">0.00</td></tr>
                        <tr style="border-top: 1px solid #000; font-weight: bold;"><td style="text-align: right; padding: 5px 2px;">Grand Total:</td><td style="text-align: right; padding: 5px 2px;" id="inv-grand-total">0.00</td></tr>
                        <tr style="border-top: 1px dashed #000;"><td style="text-align: right; padding: 5px 2px;">Paid Amount:</td><td style="text-align: right; padding: 5px 2px;" id="inv-paid">0.00</td></tr>
                        
                        <!-- Dynamic Change/Due Row -->
                        <tr id="row-change"><td style="text-align: right; padding: 2px;" id="label-change">Change:</td><td style="text-align: right; padding: 2px;" id="inv-change">0.00</td></tr>
                        
                        <!-- Installment Row (Hidden by default) -->
                        <tr id="row-installment" style="display: none;"><td style="text-align: right; padding: 2px;">Installment:</td><td style="text-align: right; padding: 2px;" id="inv-installment">0.00</td></tr>
                        
                        <!-- Dedicated Due Row (Hidden if using dynamic row, or kept for specific logic) -->
                        <tr id="row-due" style="display: none;"><td style="text-align: right; padding: 2px;">Due:</td><td style="text-align: right; padding: 2px;" id="inv-due">0.00</td></tr>

                        <tr><td style="text-align: right; padding: 2px;">Previous Due:</td><td style="text-align: right; padding: 2px;" id="inv-prev-due">0.00</td></tr>
                        <tr style="border-top: 1px solid #000; font-weight: bold; color: #dc2626;"><td style="text-align: right; padding: 5px 2px;">Total Due:</td><td style="text-align: right; padding: 5px 2px;" id="inv-total-due">0.00</td></tr>
                    </table>
                </div>
                
                <div style="font-style: italic; font-size: 10px; margin-bottom: 10px;">In Text: <span id="inv-in-words">Amount in words here</span></div>
                
                <!-- Payment methods -->
                <div style="margin-bottom: 10px; margin-top: 15px;">
                     <div style="font-weight: bold; border-top: 1px dashed #000; border-bottom: 1px dashed #000; padding: 5px 0; text-align: center; text-transform: uppercase;">Payments</div>
                     <table style="width: 100%; font-size: 10px; margin-top: 8px; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 1px dashed #000;">
                                <th style="text-align: left; padding: 5px 0; width: 8%; text-transform: uppercase;">SL</th>
                                <th style="text-align: left; padding: 5px 0; width: 42%; text-transform: uppercase;">TYPE</th>
                                <th style="text-align: left; padding: 5px 0; width: 30%; text-transform: uppercase;">METHOD</th>
                                <th style="text-align: right; padding: 5px 0; width: 20%; text-transform: uppercase;">AMOUNT</th>
                            </tr>
                        </thead>
                        <tbody id="inv-payment-list">
                            <!-- Payment rows will be populated here dynamically -->
                            <tr style="border-bottom: 1px dashed #ddd;">
                                <td style="padding: 5px 0;">1</td>
                                <td style="padding: 5px 0;">
                                    <div style="font-weight: 600; font-size: 10px;">Full Payment</div>
                                    <div style="font-size: 8px; color: #666;">22 Jan 2026</div>
                                </td>
                                <td style="padding: 5px 0;">
                                    <div style="font-weight: 600; font-size: 10px;" id="inv-payment-method">Cash on Hand</div>
                                </td>
                                <td style="text-align: right; padding: 5px 0; font-weight: 600; color: #10b981; font-size: 10px;" id="inv-payment-amount">0.00</td>
                            </tr>
                        </tbody>
                     </table>
                </div>


                <!-- Footer -->
                <div style="text-align: center; margin-top: 20px;">
                    <!-- Barcode -->
                    <div style="margin: 10px 0; display: flex; justify-content: center; width: 100%;">
                        <svg id="inv-barcode"></svg>
                    </div>
                    
                    <!-- Thank you message -->
                    <div style="font-size: 11px; font-weight: 600; margin-top: 8px; margin-bottom: 4px;">
                        Thank you for choosing us!
                    </div>
                    
                    <!-- Support info -->
                    <div style="font-size: 10px; color: #666;">
                        For Support: <span id="inv-support-store">ALL STORES</span>
                    </div>
                    
                    <!-- Developer credit -->
                    <div style="font-size: 9px; color: #999; margin-top: 4px;">Developed by STS</div>
                </div>
            </div>
            
            <div class="no-print" style="margin-top: 20px; display: flex; flex-direction: row; gap: 10px; justify-content: center; flex-wrap: wrap; padding: 10px;">
                <button onclick="window.printInvoice()" style="background: #0ea5e9; color: white; border: none; padding: 10px 15px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 5px; font-size: 13px;">
                    <i class="fas fa-print"></i> Print
                </button>
                <button style="background: #10b981; color: white; border: none; padding: 10px 15px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 5px; font-size: 13px;">
                    <i class="fas fa-envelope"></i> Email
                </button>
                <button onclick="closeInvoiceModal()" style="background: #64748b; color: white; border: none; padding: 10px 15px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 5px; font-size: 13px;">
                    <i class="fas fa-times"></i> Close
                </button>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    html, body {
        height: auto !important;
        margin: 0 !important;
        padding: 0 !important;
        background: white !important;
    }
    .app-wrapper, .navbar-fixed-top, #sidebar, #main-content > *:not(#invoiceModal), .pos-header-bar, .search-bar, .category-filter, .product-grid-wrapper, .pos-sidebar, .pos-footer {
        display: none !important;
    }
    #invoiceModal {
        display: block !important;
        position: static !important;
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        overflow: visible !important;
    }
    
    /* Dynamic Page Size */
    /* Dynamic Page Size handled by JS printInvoice() */
    @page { size: auto; margin: 0; }

    #invoiceModal .pos-modal-content {
        box-shadow: none !important;
        border: none !important;
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 !important;
    }
    
    /* Thermal 58mm Specifics */
    body.template-thermal_58mm #invoiceModal .pos-modal-content {
        width: 58mm !important;
        max-width: 58mm !important; 
        font-size: 9px !important;
        padding: 0 !important;
    }
    body.template-thermal_58mm #invoiceModal .pos-modal-body {
        padding: 0 !important;
    }
    body.template-thermal_58mm h2 { font-size: 14px !important; }
    body.template-thermal_58mm h3 { font-size: 11px !important; }
    body.template-thermal_58mm table th, 
    body.template-thermal_58mm table td {
        padding: 2px 0 !important;
        font-size: 9px !important;
    }

    /* Thermal 80mm Specifics */
    body.template-thermal_80mm #invoiceModal .pos-modal-content {
        width: 78mm !important; /* Slightly less than 80mm to be safe */
        max-width: 80mm !important;
    }

    .pos-modal-header, .pos-modal-body > div:last-child { 
        display: none !important; 
    }
}
</style>
