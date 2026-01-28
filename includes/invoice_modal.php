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
        <div class="pos-modal-body" style="padding: 20px; text-align: center; color: #000;">
            <div id="invoice-content" class="receipt-wrapper">
                <!-- Modular Templates Included Here -->
                <div id="tpl-classic" class="design-tpl hidden">
                    <?php include(__DIR__ . '/receipt_templates/classic.php'); ?>
                </div>
                <div id="tpl-modern" class="design-tpl hidden">
                    <?php include(__DIR__ . '/receipt_templates/modern.php'); ?>
                </div>
                <div id="tpl-minimal" class="design-tpl hidden">
                    <?php include(__DIR__ . '/receipt_templates/minimal.php'); ?>
                </div>
                
                <!-- Fallback for legacy support if JS fails -->
                <div id="tpl-legacy" class="design-tpl">
                    <div style="font-family: 'Courier New', Courier, monospace; font-size: 12px;">
                         Loading Receipt Design...
                    </div>
                </div>
            </div>
            
            <div class="no-print" style="margin-top: 30px; display: flex; flex-direction: row; gap: 12px; justify-content: center; flex-wrap: wrap; padding: 10px; border-top: 1px solid #f1f5f9; background: #fafafa; margin-left: -20px; margin-right: -20px; border-radius: 0 0 24px 24px;">
                <button onclick="window.printInvoice()" style="background: #0ea5e9; color: white; border: none; padding: 12px 24px; border-radius: 12px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; font-size: 14px; font-weight: 700; shadow: 0 4px 6px rgba(14, 165, 233, 0.2);">
                    <i class="fas fa-print"></i> Print Receipt
                </button>
                <button onclick="closeInvoiceModal()" style="background: white; color: #64748b; border: 1px solid #e2e8f0; padding: 12px 24px; border-radius: 12px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; font-size: 14px; font-weight: 700;">
                    <i class="fas fa-times"></i> Close
                </button>
            </div>
        </div>
    </div>
</div>

</style>

