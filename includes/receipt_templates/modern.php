<!-- Template: Modern -->
<div id="receipt-modern" class="receipt-template" style="font-family: 'Inter', sans-serif;">
    <style>
        @media print {
            #receipt-modern .inv-header-box { background: none !important; border-bottom: 2px solid #0f172a !important; padding: 0 !important; margin-bottom: 10px !important; }
            #receipt-modern .inv-store-name { color: #000 !important; }
            #receipt-modern .inv-grand-total-box { background: #eee !important; color: #000 !important; border: 1px solid #000 !important; }
            #receipt-modern .inv-footer-msg { color: #000 !important; }
        }
    </style>
    <div class="inv-header-box" style="background: #f8fafc; padding: 20px; text-align: center; border-radius: 15px; margin-bottom: 20px;">
        <img src="/pos/assets/images/logo-fav.png" class="inv-logo" style="max-height: 40px; margin-bottom: 10px;">
        <h2 class="inv-store-name" style="margin: 0; font-size: 20px; font-weight: 800; color: #0f172a; letter-spacing: -0.5px;">Modern POS</h2>
        <div class="inv-store-address" style="color: #64748b; font-size: 11px; font-weight: 600;">STORE ADDRESS</div>
        <div class="inv-store-contact" style="color: #64748b; font-size: 10px; margin-top: 5px;">Mobile: --, Email: --</div>
        <div class="inv-store-bin" style="color: #64748b; font-size: 10px; margin-top: 2px;"></div>
    </div>

    <div style="padding: 0 10px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 20px; border-bottom: 2px solid #f1f5f9; padding-bottom: 15px;">
            <div>
                <div style="color: #94a3b8; font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;">Invoice No</div>
                <div class="inv-id-val" style="font-weight: 700; color: #0f172a;">#001</div>
                <div style="margin-top: 5px; font-size: 10px;">
                     <span style="color: #94a3b8;">Customer:</span> <span class="inv-customer-val" style="font-weight: 600;">Walking</span>
                </div>
            </div>
            <div style="text-align: right;">
                <div style="color: #94a3b8; font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;">Date</div>
                <div class="inv-date-val" style="font-weight: 600; color: #334155;">--</div>
                 <div style="margin-top: 5px; font-size: 10px;">
                     <span class="inv-phone-val" style="color: #64748b;">01700000000</span>
                </div>
            </div>
        </div>

        <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
            <thead>
                <tr style="background: #f8fafc;">
                    <th style="text-align: left; padding: 10px; font-size: 10px; color: #64748b; text-transform: uppercase; border-radius: 8px 0 0 8px;">Product</th>
                    <th style="text-align: right; padding: 10px; font-size: 10px; color: #64748b; text-transform: uppercase; border-radius: 0 8px 8px 0;">Total</th>
                </tr>
            </thead>
            <tbody class="inv-items-list" style="font-size: 11px;">
                <!-- Items populated by JS -->
            </tbody>
        </table>

         <div style="border-top: 2px dashed #f1f5f9; padding-top: 15px; margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 11px;">
                <span style="color: #64748b;">Subtotal</span>
                <span class="inv-subtotal-val" style="font-weight: 600;">0.00</span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 11px;">
                <span style="color: #64748b;">Tax</span>
                <span class="inv-tax-val" style="font-weight: 600;">0.00</span>
            </div>
             <div style="display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 11px;">
                <span style="color: #64748b;">Shipping</span>
                <span class="inv-shipping-val" style="font-weight: 600;">0.00</span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 11px;">
                <span style="color: #64748b;">Discount</span>
                <span class="inv-discount-val" style="font-weight: 600;">0.00</span>
            </div>
        </div>

        <div class="inv-grand-total-box" style="background: #0f172a; color: white; padding: 15px; border-radius: 12px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <span style="font-size: 11px; opacity: 0.8; font-weight: 600;">Grand Total</span>
            <span class="inv-grand-total-val" style="font-size: 18px; font-weight: 800;">0.00</span>
        </div>

        <!-- Payments Breakdown -->
         <div style="margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 11px; color: #10b981; font-weight: 600;">
                <span>Paid Amount</span>
                <span class="inv-paid-val">0.00</span>
            </div>
             <div class="inv-row-change" style="display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 11px; color: #64748b;">
                <span>Change</span>
                <span class="inv-change-val">0.00</span>
            </div>
            <div class="inv-row-due" style="display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 11px; color: #ef4444; font-weight: 700; display: none;">
                <span>Total Due</span>
                <span class="inv-due-val">0.00</span>
            </div>
            <div class="inv-row-installment" style="display: none; justify-content: space-between; margin-bottom: 5px; font-size: 11px; color: #f59e0b; font-weight: 700;">
                <span>Installment Due</span>
                <span class="inv-installment-val">0.00</span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 11px; color: #64748b;">
                <span>Previous Due</span>
                <span class="inv-prev-due">0.00</span>
            </div>
             <div style="display: flex; justify-content: space-between; margin-top: 5px; border-top: 1px solid #e2e8f0; padding-top: 5px; font-size: 12px; font-weight: 700; color: #ef4444;">
                <span>Total Outstanding</span>
                <span class="inv-total-due">0.00</span>
            </div>
        </div>
        
        <div style="text-align: center; font-size: 9px; color: #64748b; margin-bottom: 15px; font-style: italic;">
            In Words: <span class="inv-in-words"></span>
        </div>

        <!-- Payment Methods List -->
        <div style="margin-top: 20px; border-top: 2px dashed #f1f5f9; padding-top: 10px;">
            <div style="font-size: 10px; font-weight: 700; color: #334155; margin-bottom: 8px; text-transform: uppercase;">Payment History</div>
            <table style="width: 100%; border-collapse: collapse; font-size: 9px;">
                <thead>
                    <tr style="color: #64748b; text-align: left;">
                        <th style="padding: 4px 0;">SL</th>
                        <th style="padding: 4px 0;">Type</th>
                        <th style="padding: 4px 0;">Method</th>
                        <th style="text-align: right; padding: 4px 0;">Amount</th>
                    </tr>
                </thead>
                <tbody class="inv-payment-list">
                    <!-- Populated by JS -->
                </tbody>
            </table>
        </div>

        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #f1f5f9;">
            <div class="inv-footer-msg" style="color: #64748b; font-size: 11px; font-weight: 500;">We appreciate your business!</div>
            <div style="margin-top: 5px; font-size: 9px; color: #94a3b8;">For Support: <span class="inv-support-store"></span></div>
            <div style="margin-top: 15px; opacity: 0.7; display: flex; justify-content: center;"><svg class="inv-barcode-svg" style="max-width: 100%; height: auto;"></svg></div>
        </div>
    </div>
</div>
