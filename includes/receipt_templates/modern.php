<?php
// PHP variable $current_store is available in the including file (pos.php or receipt_templates.php)
?>
<!-- Template: Modern Edge (Unique Premium Design) -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<div id="receipt-modern" class="receipt-template" style="font-family: 'Inter', sans-serif; padding: 0; background: #fff; color: #1e293b; line-height: 1.5; max-width: 400px; margin: auto;">
    <style>
        @media print {
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            #receipt-modern { width: 100% !important; max-width: 100% !important; box-shadow: none !important; border: none !important; }
            #receipt-modern .premium-header { background: #f8fafc !important; border-left: 10px solid #10b981 !important; }
            #receipt-modern .premium-total-box { background: #10b981 !important; color: white !important; }
            #receipt-modern .mono { font-family: 'Space Mono', monospace !important; }
            #receipt-modern .status-tag { border: 1px solid #10b981 !important; color: #10b981 !important; }
        }
        #receipt-modern .mono { font-family: 'Space Mono', monospace; }
        #receipt-modern .premium-divider { border-top: 2px dashed #e2e8f0; margin: 15px 0; width: 100%; }
        #receipt-modern .status-tag { display: inline-block; background: #ecfdf5; color: #059669; font-size: 10px; font-weight: 800; padding: 2px 10px; border-radius: 99px; text-transform: uppercase; margin-top: 5px; }
    </style>

    <!-- Asymmetric Header -->
    <div class="premium-header" style="background: #f8fafc; border-left: 10px solid #10b981; padding: 25px 20px; position: relative;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 15px;">
            <div style="flex: 2;">
                <h2 class="inv-store-name" style="margin: 0; font-size: 22px; font-weight: 800; color: #10b981; letter-spacing: -1px; text-transform: uppercase; line-height: 1.1;">
                    <?= htmlspecialchars($current_store['store_name'] ?? 'Modern POS'); ?>
                </h2>
                <div class="inv-store-address" style="color: #64748b; font-size: 11px; font-weight: 600; margin-top: 6px;">
                    <?= htmlspecialchars($current_store['address'] ?? 'STORE ADDRESS'); ?>
                </div>
                <div class="status-tag">e-Receipt verified</div>
            </div>
            <div style="flex: 1; text-align: right;">
                <img src="<?= htmlspecialchars($current_store['logo'] ?? '/pos/assets/images/logo-fav.png'); ?>" class="inv-logo" style="max-height: 48px; border-radius: 8px; margin-bottom: 5px; display: block; margin-left: auto;">
            </div>
        </div>
        
        <div style="margin-top: 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 15px; border-top: 1px solid rgba(13, 212, 96, 1); padding-top: 15px;">
            <div>
                <div style="font-size: 8px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px;">Contact Details</div>
                <div class="inv-store-contact" style="color: #475569; font-size: 10px; font-weight: 600; margin-top: 2px;">
                    <?= htmlspecialchars($current_store['phone'] ?? '--'); ?>
                </div>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 8px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px;">Store Ident</div>
                <div class="inv-store-bin" style="color: #475569; font-size: 10px; font-weight: 600; margin-top: 2px;">
                    <?= !empty($current_store['vat_number']) ? 'BIN: ' . htmlspecialchars($current_store['vat_number']) : 'POS-ID: 001'; ?>
                </div>
            </div>
        </div>
    </div>

    <div style="padding: 20px;">
        <!-- Meta Row -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding: 10px 15px; background: #fff; border: 1px solid #f1f5f9; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);">
            <div>
                <span style="font-size: 9px; font-weight: 800; color: #94a3b8; text-transform: uppercase; display: block;">Invoice ID</span>
                <span class="inv-id-val mono" style="font-size: 14px; font-weight: 700; color: #10b981;">#--</span>
            </div>
            <div style="text-align: right;">
                <span style="font-size: 9px; font-weight: 800; color: #94a3b8; text-transform: uppercase; display: block;">Issuance Date</span>
                <span class="inv-date-val mono" style="font-size: 11px; font-weight: 600; color: #10b981;">--</span>
            </div>
        </div>

        <!-- Billed Section -->
        <div style="margin-top: 10px; border-left: 3px solid #10b981; padding-left: 12px; margin-bottom: 25px;">
            <div style="font-size: 9px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px;">Billed To</div>
            <div class="inv-customer-val" style="font-size: 15px; font-weight: 800; color: #10b981;">Walking Customer</div>
            <div class="inv-phone-val mono" style="font-size: 10px; color: #64748b; margin-top: 2px;">--</div>
        </div>

        <!-- Items Grid -->
        <table style="width: 100%; border-collapse: separate; border-spacing: 0 5px; margin-bottom: 20px;">
            <thead>
                <tr>
                    <th style="text-align: left; font-size: 10px; font-weight: 800; color: #94a3b8; text-transform: uppercase; padding: 0 5px 5px 5px;">Item / Description</th>
                    <th style="text-align: right; font-size: 10px; font-weight: 800; color: #94a3b8; text-transform: uppercase; padding: 0 5px 5px 5px;">Line Total</th>
                </tr>
            </thead>
            <tbody class="inv-items-list">
                <!-- Data injected via JS -->
                 <!-- Sample Row Structure: 
                 <tr style="background: #f8fafc; color: #1e293b;">
                    <td style="padding: 10px; border-radius: 8px 0 0 8px; font-size: 12px; font-weight: 600;">Product Label</td>
                    <td style="padding: 10px; text-align: right; border-radius: 0 8px 8px 0; font-size: 12px; font-weight: 700;" class="mono">0.00</td>
                 </tr>
                 -->
            </tbody>
        </table>

        <!-- Summary -->
        <div style="padding: 15px; border-top: 2px solid #f8fafc; margin-top: 10px;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                <span style="font-size: 11px; font-weight: 600; color: #64748b;">Subtotal Gross</span>
                <span class="inv-subtotal-val mono" style="font-size: 12px; font-weight: 600; color: #1e293b;">0.00</span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                <span style="font-size: 11px; font-weight: 600; color: #64748b;">Discount (Applied)</span>
                <span class="inv-discount-val mono" style="font-size: 12px; font-weight: 600; color: #ef4444;">-0.00</span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                <span style="font-size: 11px; font-weight: 600; color: #64748b;">Tax Component</span>
                <span class="inv-tax-val mono" style="font-size: 12px; font-weight: 600; color: #10b981;">0.00</span>
            </div>
        </div>

        <!-- Premium Total Box -->
        <div class="premium-total-box" style="background: #10b981; border-radius: 12px; padding: 22px 20px; color: white; display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; box-shadow: 0 12px 20px -5px rgba(15, 23, 42, 0.3);">
            <div>
                <div style="font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 2px; opacity: 0.7; margin-bottom: 2px;">Grand Total</div>
                <div style="font-size: 10px; font-weight: 500; opacity: 0.5;">Amount Payable</div>
            </div>
            <div class="inv-grand-total-val mono" style="font-size: 26px; font-weight: 800; letter-spacing: -1px;">0.00</div>
        </div>

        <!-- Meta Summary -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 30px;">
            <div style="background: #ecfdf5; border-radius: 10px; padding: 12px; border: 1px solid #d1fae5;">
                <div style="font-size: 8px; font-weight: 800; color: #059669; text-transform: uppercase; margin-bottom: 2px;">Cash Received</div>
                <div class="inv-paid-val mono" style="font-size: 13px; font-weight: 700; color: #10b981;">0.00</div>
            </div>
            <div style="background: #fff1f2; border-radius: 10px; padding: 12px; border: 1px solid #ffe4e6;">
                <div style="font-size: 8px; font-weight: 800; color: #e11d48; text-transform: uppercase; margin-bottom: 2px;">Balance Due</div>
                <div class="inv-total-due mono" style="font-size: 13px; font-weight: 700; color: #9f1239;">0.00</div>
            </div>
        </div>

        <div style="text-align: center; font-size: 9px; color: #94a3b8; font-style: italic; margin-bottom: 30px; letter-spacing: 0.3px;">
            " <span class="inv-in-words">Zero amount only</span> "
        </div>

        <!-- Stylized Footer -->
        <div style="text-align: center; border-top: 1px dashed #e2e8f0; padding-top: 25px; margin-bottom: 15px;">
            <div style="display: flex; justify-content: center; margin-bottom: 20px;">
                <svg class="inv-barcode-svg" style="max-height: 42px; width: 100%; opacity: 0.7;"></svg>
            </div>
            <p class="inv-footer-msg" style="font-size: 12px; font-weight: 800; color: #10b981; margin: 0;">THANK YOU FOR YOUR CHOICE</p>
            <p style="font-size: 9px; color: #64748b; margin-top: 5px; font-weight: 500;">Support Desk: <span class="inv-support-store">--</span></p>
            <div style="font-size: 8px; color: #cbd5e1; font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase;">Verified by STS POS</div>
            
            <!-- <div style="margin-top: 25px; display: flex; align-items: center; justify-content: center; gap: 5px;">
                <div style="height: 1px; flex: 1; background: #f1f5f9;"></div>
                <div style="font-size: 8px; color: #cbd5e1; font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase;">Verified by STS POS</div>
            </div> -->
        </div>
    </div>
</div>
