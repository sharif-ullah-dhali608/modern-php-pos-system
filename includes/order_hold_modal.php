<div class="pos-modal" id="holdModal">
    <div class="pos-modal-content" style="max-width: 750px; border-radius: 12px; border: none; padding: 0; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); overflow: visible;">
        <!-- Header with unique layout -->
        <div class="pos-modal-header" style="background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%); padding: 18px 25px; position: relative; border-radius: 12px 12px 0 0; border-bottom: 2px solid rgba(255,255,255,0.1);">
            <h3 style="color: white; margin: 0; display: flex; align-items: center; gap: 12px; font-size: 20px; font-weight: 700; letter-spacing: 0.5px;">
                <i class="fas fa-save" style="font-size: 22px;"></i> 
                <span>HOLD CURRENT ORDER</span>
            </h3>
            <button class="close-btn" onclick="closeModal('holdModal')" style="display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.1); border: 1px solid rgba(255,255,255,0.2); width: 32px; height: 32px; border-radius: 50%; color: white; transition: all 0.3s ease;">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="pos-modal-body" style="padding: 20px; background: #fdfdfd;">
            <!-- Reference Input -->
            <div style="margin-bottom: 20px;">
                <div style="display: flex; align-items: center; background: white; border: 2px solid #f1f5f9; border-radius: 10px; overflow: hidden; box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);">
                    <div style="background: #f8fafc; padding: 12px 18px; border-right: 2px solid #f1f5f9; color: #0d9488;">
                        <i class="fas fa-pen-alt"></i>
                    </div>
                    <input type="text" id="hold-reference" style="flex: 1; border: none; padding: 12px 15px; font-size: 15px; outline: none; color: #1e293b;" placeholder="Add order note or reference here...">
                </div>
            </div>

            <!-- Scrollable Order Details -->
            <div style="border: 2px solid #f1f5f9; border-radius: 12px; background: white; overflow: hidden; margin-bottom: 15px;">
                <div style="background: #f8fafc; padding: 10px 20px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between;">
                    <span style="font-weight: 700; color: #334155; font-size: 13px;">ITEMIZED BREAKDOWN</span>
                    <span style="background: #0d9488; color: white; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 700;"><span id="hold-item-count">0</span> Items</span>
                </div>

                <div style="max-height: 280px; overflow-y: auto; scrollbar-width: thin;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tbody id="hold-order-items">
                            <!-- Items will be injected here -->
                        </tbody>
                    </table>
                </div>

                <!-- Redesigned Footer with 2 rows for totals as requested -->
                <div style="background: #fbfcfd; border-top: 2px solid #f1f5f9; padding: 15px 20px;">
                    <!-- Row 1: Subtotal & Discount -->
                    <div style="display: flex; justify-content: space-between; gap: 20px; margin-bottom: 10px;">
                        <div style="flex: 1; display: flex; justify-content: space-between; align-items: center; background: #fff; padding: 8px 12px; border-radius: 8px; border: 1px solid #f1f5f9;">
                            <span style="font-size: 13px; color: #64748b; font-weight: 600;">Subtotal</span>
                            <span id="hold-subtotal" style="font-weight: 700; color: #1e293b;">0.00</span>
                        </div>
                        <div style="flex: 1; display: flex; justify-content: space-between; align-items: center; background: #fff; padding: 8px 12px; border-radius: 8px; border: 1px solid #f1f5f9;">
                            <span style="font-size: 13px; color: #cc4d4d; font-weight: 600;">Discount</span>
                            <span id="hold-discount" style="font-weight: 700; color: #cc4d4d;">0.00</span>
                        </div>
                    </div>
                    <!-- Row 2: Tax & Charges -->
                    <div style="display: flex; justify-content: space-between; gap: 20px; margin-bottom: 10px;">
                        <div style="flex: 1; display: flex; justify-content: space-between; align-items: center; background: #fff; padding: 8px 12px; border-radius: 8px; border: 1px solid #f1f5f9;">
                            <span style="font-size: 13px; color: #64748b; font-weight: 600;">Tax</span>
                            <span id="hold-tax" style="font-weight: 700; color: #1e293b;">0.00</span>
                        </div>
                        <div style="flex: 1; display: flex; justify-content: space-between; align-items: center; background: #fff; padding: 8px 12px; border-radius: 8px; border: 1px solid #f1f5f9;">
                            <span style="font-size: 13px; color: #64748b; font-weight: 600;">Charges</span>
                            <span style="font-weight: 700; color: #1e293b;"><span id="hold-shipping">0.00</span> + <span id="hold-other">0.00</span></span>
                        </div>
                    </div>
                    <!-- Net Payable -->
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 5px; padding: 12px; background: #f0fdfa; border-radius: 10px; border: 1px solid #ccfbf1;">
                        <span style="font-weight: 800; color: #0f766e; font-size: 15px; text-transform: uppercase; letter-spacing: 1px;">Net Payable</span>
                        <span id="hold-payable" style="font-size: 22px; font-weight: 900; color: #0d9488; font-family: 'Inter', sans-serif;">0.00</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Styled Teal Save Button -->
        <div style="padding: 15px 25px; text-align: right; background: white; border-top: 1px solid #f1f5f9; display: flex; justify-content: center; gap: 12px; border-radius: 0 0 12px 12px;">
            <button onclick="closeModal('holdModal')" style="padding: 10px 20px; border-radius: 8px; border: 1px solid #e2e8f0; background: white; color: #64748b; font-weight: 600; cursor: pointer; transition: all 0.2s;">
                Cancel
            </button>
            <button onclick="holdOrder()" style="padding: 10px 45px; border-radius: 8px; border: none; background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%); color: white; font-weight: 700; cursor: pointer; box-shadow: 0 4px 12px rgba(20, 184, 166, 0.3); display: flex; align-items: center; gap: 10px; transition: all 0.2s; font-size: 16px;">
                <i class="fas fa-check-circle"></i>
                <span>SAVE HOLD</span>
            </button>
        </div>
    </div>
</div>

<!-- Held Orders List Modal -->
<div class="pos-modal" id="heldOrdersModal">
    <div class="pos-modal-content" style="max-width: 1050px; width: 95%; height: 85vh; border-radius: 12px; overflow: hidden; border: none; padding: 0; display: flex; flex-direction: column; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);">
        <!-- Premium Header -->
        <div class="pos-modal-header" style="background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%); padding: 15px 25px; flex-shrink: 0; display: flex; align-items: center; border-bottom: none; position: relative;">
            <h3 style="color: white; margin: 0; display: flex; align-items: center; gap: 12px; font-size: 18px; font-weight: 700; letter-spacing: 0.5px;">
                <i class="fas fa-history" style="font-size: 20px;"></i>
                <span>HELD ORDERS HISTORY</span>
                <span id="held-order-title-ref" style="font-weight: 400; font-size: 14px; background: rgba(255,255,255,0.15); padding: 4px 12px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.2); margin-left: 10px;">Select an order</span>
            </h3>
            <button class="close-btn" onclick="closeModal('heldOrdersModal')" style="position: absolute; right: 20px; top: 50%; transform: translateY(-50%); background: rgba(0,0,0,0.2); color: white; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,0.3); font-size: 14px; cursor: pointer; transition: all 0.3s ease;">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="pos-modal-body" style="padding: 0; background: #f8fafc; flex: 1; display: flex; overflow: hidden;">
            <!-- Modern Sidebar -->
            <div style="width: 350px; border-right: 1px solid #e2e8f0; display: flex; flex-direction: column; background: #fff; box-shadow: 10px 0 15px -10px rgba(0,0,0,0.05);">
                <div style="padding: 20px; border-bottom: 1px solid #f1f5f9;">
                    <div style="position: relative;">
                        <i class="fas fa-search" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #0d9488;"></i>
                        <input type="text" id="held-order-search" placeholder="Search by name or reference..." oninput="filterHeldOrders()" style="width: 100%; padding: 12px 15px 12px 40px; border: 2px solid #f1f5f9; border-radius: 10px; font-size: 14px; outline: none; background: #fdfdfd; transition: all 0.3s ease;">
                    </div>
                </div>
                <div id="held-orders-list" class="custom-scroll" style="flex: 1; overflow-y: auto; padding: 10px;">
                    <!-- List items injected here -->
                </div>
            </div>

            <!-- Enhanced Content Area -->
            <div id="held-order-detail-view" style="flex: 1; display: flex; flex-direction: column; background: #fdfdfd;">
                <div id="held-order-placeholder" style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #cbd5e1;">
                    <div style="width: 120px; height: 120px; background: #f1f5f9; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 20px;">
                        <i class="fas fa-receipt" style="font-size: 50px; opacity: 0.3;"></i>
                    </div>
                    <p style="font-size: 16px; font-weight: 600; letter-spacing: 0.5px;">SELECT AN ORDER TO VIEW DETAILS</p>
                </div>
                
                <div id="held-order-content" style="display: none; flex: 1; flex-direction: column; overflow: hidden;">
                    <div style="padding: 25px; flex: 1; overflow-y: auto;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 25px;">
                            <div>
                                <h4 style="margin: 0 0 5px 0; font-size: 20px; color: #334155; font-weight: 800;">ORDER DETAILS</h4>
                                <div style="display: flex; align-items: center; gap: 15px; font-size: 13px; color: #64748b;">
                                    <span><i class="fas fa-user-circle"></i> <span id="held-detail-customer"></span></span>
                                    <span><i class="fas fa-clock"></i> <span id="held-detail-date"></span></span>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <div style="background: #f0fdfa; color: #0d9488; padding: 6px 15px; border-radius: 20px; font-size: 12px; font-weight: 700; border: 1px solid #ccfbf1;">
                                    ACTIVE ORDER
                                </div>
                            </div>
                        </div>

                        <!-- Product Table -->
                        <div style="background: white; border: 1px solid #f1f5f9; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="background: #f8fafc; border-bottom: 1px solid #f1f5f9;">
                                        <th style="padding: 15px; text-align: center; width: 50px; color: #94a3b8; font-size: 11px; font-weight: 800; text-transform: uppercase;">#</th>
                                        <th style="padding: 15px; text-align: left; color: #94a3b8; font-size: 11px; font-weight: 800; text-transform: uppercase;">Product</th>
                                        <th style="padding: 15px; text-align: center; width: 120px; color: #94a3b8; font-size: 11px; font-weight: 800; text-transform: uppercase;">Price</th>
                                        <th style="padding: 15px; text-align: center; width: 100px; color: #94a3b8; font-size: 11px; font-weight: 800; text-transform: uppercase;">Qty</th>
                                        <th style="padding: 15px; text-align: right; width: 130px; color: #94a3b8; font-size: 11px; font-weight: 800; text-transform: uppercase;">Total</th>
                                    </tr>
                                </thead>
                                <tbody id="held-detail-items">
                                </tbody>
                            </table>
                        </div>

                        <!-- Restructured Totals into Two Rows as requested -->
                        <div style="margin-top: 25px; background: #fbfcfd; border-top: 2px solid #f1f5f9; padding: 20px; border-radius: 0 0 12px 12px; border: 1px solid #f1f5f9; border-top: none;">
                            <!-- Row 1: Subtotal & Discount -->
                            <div style="display: flex; justify-content: space-between; gap: 20px; margin-bottom: 12px;">
                                <div style="flex: 1; display: flex; justify-content: space-between; align-items: center; background: #fff; padding: 10px 15px; border-radius: 10px; border: 1px solid #f1f5f9;">
                                    <span style="font-size: 13px; color: #64748b; font-weight: 600;">Subtotal</span>
                                    <span id="held-detail-subtotal" style="font-weight: 700; color: #1e293b;">0.00</span>
                                </div>
                                <div style="flex: 1; display: flex; justify-content: space-between; align-items: center; background: #fff; padding: 10px 15px; border-radius: 10px; border: 1px solid #f1f5f9;">
                                    <span style="font-size: 13px; color: #cc4d4d; font-weight: 600;">Discount</span>
                                    <span id="held-detail-discount" style="font-weight: 700; color: #cc4d4d;">0.00</span>
                                </div>
                            </div>
                            <!-- Row 2: Tax & Charges -->
                            <div style="display: flex; justify-content: space-between; gap: 20px; margin-bottom: 12px;">
                                <div style="flex: 1; display: flex; justify-content: space-between; align-items: center; background: #fff; padding: 10px 15px; border-radius: 10px; border: 1px solid #f1f5f9;">
                                    <span style="font-size: 13px; color: #64748b; font-weight: 600;">Tax Amount</span>
                                    <span id="held-detail-tax" style="font-weight: 700; color: #1e293b;">0.00</span>
                                </div>
                                <div style="flex: 1; display: flex; justify-content: space-between; align-items: center; background: #fff; padding: 10px 15px; border-radius: 10px; border: 1px solid #f1f5f9;">
                                    <span style="font-size: 13px; color: #64748b; font-weight: 600;">Other Charges</span>
                                    <span style="font-weight: 700; color: #1e293b;"><span id="held-detail-shipping">0.00</span> + <span id="held-detail-other">0.00</span></span>
                                </div>
                            </div>
                            <!-- Net Payable Highlight -->
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px; padding: 15px 20px; background: #0d9488; border-radius: 12px; box-shadow: 0 10px 15px -3px rgba(13, 148, 136, 0.2);">
                                <span style="font-weight: 800; color: white; font-size: 16px; text-transform: uppercase; letter-spacing: 1px;">NET PAYABLE AMOUNT</span>
                                <span id="held-detail-payable" style="font-size: 26px; font-weight: 900; color: white; font-family: 'Inter', sans-serif;">0.00</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Detail Footer Actions -->
                    <div style="padding: 20px 25px; text-align: right; background: white; border-top: 1px solid #f1f5f9; display: flex; justify-content: center; gap: 15px;">
                        <button onclick="closeModal('heldOrdersModal')" style="padding: 12px 25px; border-radius: 10px; border: 1px solid #e2e8f0; background: white; color: #64748b; font-weight: 600; cursor: pointer; transition: all 0.2s;">
                            Close Window
                        </button>
                        <button id="btn-resume-held" style="padding: 12px 40px; border-radius: 10px; border: none; background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%); color: white; font-weight: 700; cursor: pointer; box-shadow: 0 4px 12px rgba(20, 184, 166, 0.3); display: flex; align-items: center; gap: 10px; transition: all 0.2s; font-size: 15px;">
                            <i class="fas fa-edit"></i>
                            <span>RESUME THIS ORDER</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>