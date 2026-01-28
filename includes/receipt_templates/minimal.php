<!-- Template: Minimal -->
<div id="receipt-minimal" class="receipt-template" style="font-size: 10px; color: #000;">
    <style>
        @media print {
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            #receipt-minimal { font-size: 12px !important; }
            #receipt-minimal .inv-items-list tr { border-bottom: 1px solid #000 !important; }
        }
    </style>
    <div style="text-align: center; border-bottom: 1px solid #000; padding-bottom: 5px; margin-bottom: 10px;">
        <h2 class="inv-store-name" style="margin: 0; font-size: 14px; text-transform: uppercase;"><?= htmlspecialchars($current_store['store_name'] ?? 'Modern POS'); ?></h2>
        <div class="inv-id-val" style="font-weight: bold;">#001</div>
    </div>

    <table style="width: 100%; border-collapse: collapse; margin-bottom: 10px;">
        <tbody class="inv-items-list">
            <!-- Simplified Item list -->
        </tbody>
    </table>

    <div style="border-top: 1px solid #000; padding-top: 5px;">
        <div style="display: flex; justify-content: space-between; font-weight: bold;">
            <span>TOTAL:</span>
            <span class="inv-grand-total-val">0.00</span>
        </div>
    </div>

    <div style="text-align: center; margin-top: 15px; font-size: 9px; letter-spacing: 0.5px;">
        <div class="inv-date-val">--</div>
        <div class="inv-footer-msg" style="margin-top: 5px;">THANK YOU</div>
        <div style="font-size: 8px; margin-top: 2px;">Developed by STS</div>
    </div>
</div>
