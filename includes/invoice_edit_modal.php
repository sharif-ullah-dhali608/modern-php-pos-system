<!-- Edit Invoice Modal -->
<div class="pos-modal" id="editInvoiceModal" style="display: none; align-items: center; justify-content: center; background: rgba(0, 0, 0, 0.6); backdrop-filter: blur(3px);">
    <div class="pos-modal-content" style="width: 100%; max-width: 700px; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);">
        
        <!-- Header -->
        <div class="pos-modal-header" style="background: #84cc16; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #65a30d;">
            <h3 style="color: white; font-size: 18px; font-weight: 600; margin: 0; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-edit"></i> <span id="edit-invoice-title">Edit Invoice > ...</span>
            </h3>
            <button class="close-btn" onclick="closeModal('editInvoiceModal')" style="background: transparent; border: none; color: white; font-size: 20px; cursor: pointer; opacity: 0.8; transition: opacity 0.2s;">
                <i class="fas fa-times-circle"></i>
            </button>
        </div>

        <!-- Body -->
        <div class="pos-modal-body" style="padding: 20px;">
            <form id="editInvoiceForm" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <input type="hidden" id="edit_invoice_id" name="invoice_id">

                <!-- CARD 1: Read-Only Info -->
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; display: flex; flex-direction: column;">
                    <div style="padding: 12px 15px; border-bottom: 1px solid #e2e8f0; background: #f1f5f9; font-weight: 700; color: #475569; font-size: 14px; text-transform: uppercase;">
                        Invoice Summary
                    </div>
                    <div style="padding: 15px; flex: 1;">
                         <table style="width: 100%; border-collapse: separate; border-spacing: 0 8px;">
                            <tbody>
                                <tr>
                                    <td style="font-weight: 600; color: #64748b; font-size: 13px;">Customer</td>
                                    <td style="text-align: right; font-weight: 700; color: #1e293b;" id="edit_display_customer">-</td>
                                </tr>
                                <tr>
                                    <td style="font-weight: 600; color: #64748b; font-size: 13px;">Subtotal</td>
                                    <td style="text-align: right; font-weight: 700; color: #1e293b;" id="edit_display_subtotal">0.00</td>
                                </tr>
                                <tr>
                                    <td style="font-weight: 600; color: #64748b; font-size: 13px;">Discount</td>
                                    <td style="text-align: right; font-weight: 700; color: #1e293b;" id="edit_display_discount">0.00</td>
                                </tr>
                                <tr style="border-top: 1px dashed #cbd5e1;">
                                    <td style="font-weight: 700; color: #0d9488; font-size: 14px; padding-top: 8px;">Inv. Amount</td>
                                    <td style="text-align: right; font-weight: 800; color: #0d9488; font-size: 14px; padding-top: 8px;" id="edit_display_grand_total">0.00</td>
                                </tr>
                                <tr>
                                    <td style="font-weight: 600; color: #64748b; font-size: 13px;">Paid Amount</td>
                                    <td style="text-align: right; font-weight: 700; color: #059669;" id="edit_display_paid">0.00</td>
                                </tr>
                                <tr>
                                    <td style="font-weight: 600; color: #64748b; font-size: 13px;">Due</td>
                                    <td style="text-align: right; font-weight: 700; color: #ef4444;" id="edit_display_due">0.00</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- CARD 2: Editable Fields -->
                <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; display: flex; flex-direction: column;">
                    <div style="padding: 12px 15px; border-bottom: 1px solid #e2e8f0; background: #f1f5f9; font-weight: 700; color: #475569; font-size: 14px; text-transform: uppercase;">
                        Edit Info
                    </div>
                    <div style="padding: 15px; display: flex; flex-direction: column; gap: 15px; flex: 1;">
                        
                        <!-- Customer Mobile -->
                        <div>
                            <label style="display: block; font-weight: 600; color: #475569; margin-bottom: 5px; font-size: 13px;">Customer Mobile</label>
                            <input type="text" id="edit_customer_mobile" name="customer_mobile" placeholder="Enter mobile number"
                                   style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; outline: none; transition: border 0.2s; font-size: 14px;">
                        </div>

                        <!-- Invoice Note -->
                        <div>
                            <label style="display: block; font-weight: 600; color: #475569; margin-bottom: 5px; font-size: 13px;">Invoice Note</label>
                            <textarea id="edit_invoice_note" name="invoice_note" rows="3" placeholder="Add a note..."
                                      style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; outline: none; resize: vertical; transition: border 0.2s; font-size: 14px;"></textarea>
                        </div>

                        <!-- Status -->
                        <div>
                            <label style="display: block; font-weight: 600; color: #475569; margin-bottom: 5px; font-size: 13px;">Status</label>
                            <div style="position: relative;">
                                 <select id="edit_status" name="status" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; outline: none; background: white; appearance: none; font-size: 14px;">
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                                <i class="fas fa-chevron-down" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none; font-size: 12px;"></i>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div style="margin-top: auto; padding-top: 10px;">
                            <button type="submit" id="edit_invoice_submit_btn" style="width: 100%; background: teal; color: white; border: none; padding: 12px; border-radius: 6px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: background 0.2s; box-shadow: 0 4px 6px -1px rgba(14, 165, 233, 0.2);">
                                <i class="fas fa-save"></i> Update Changes
                            </button>
                        </div>
                    </div>
                </div>

            </form>
        </div>
    </div>
</div>
