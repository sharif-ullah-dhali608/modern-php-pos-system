<!-- Style Block for Hold Modals -->
<style>
    /* Base Modal Styling */
    .hold-modal-content {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        overflow: hidden;
        width: 95%;
        margin: auto;
        position: relative;
    }

    .hold-modal-header {
        background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);
        padding: 16px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        color: white;
    }

    .hold-modal-header h3 {
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 18px;
        font-weight: 700;
        letter-spacing: 0.5px;
    }

    .hold-modal-header .close-btn {
        background: rgba(0,0,0,0.1);
        border: 1px solid rgba(255,255,255,0.2);
        width: 30px;
        height: 30px;
        border-radius: 50%;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
    }

    .hold-modal-body {
        padding: 15px;
        background: #fdfdfd;
    }

    /* Reference Input */
    .hold-ref-container {
        display: flex;
        align-items: center;
        background: white;
        border: 2px solid #f1f5f9;
        border-radius: 10px;
        overflow: hidden;
        margin-bottom: 15px;
    }

    .hold-ref-container .icon {
        background: #f8fafc;
        padding: 10px 15px;
        border-right: 2px solid #f1f5f9;
        color: #0d9488;
    }

    .hold-ref-container input {
        flex: 1;
        border: none;
        padding: 10px 12px;
        font-size: 14px;
        outline: none;
    }

    /* Breakdown Area */
    .hold-breakdown-box {
        border: 2px solid #f1f5f9;
        border-radius: 12px;
        background: white;
        overflow: hidden;
        margin-bottom: 15px;
    }

    .hold-breakdown-header {
        background: #f8fafc;
        padding: 8px 15px;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-weight: 700;
        font-size: 12px;
        color: #334155;
    }

    .hold-breakdown-header .badge {
        background: #0d9488;
        color: white;
        padding: 2px 8px;
        border-radius: 20px;
        font-size: 10px;
    }

    .hold-item-table-container {
        max-height: 250px;
        overflow-y: auto;
    }

    /* Totals Grid */
    .hold-totals-grid {
        background: #fbfcfd;
        border-top: 2px solid #f1f5f9;
        padding: 12px 15px;
    }

    .hold-total-row {
        display: flex;
        gap: 10px;
        margin-bottom: 8px;
    }

    .hold-total-item {
        flex: 1;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #fff;
        padding: 8px 10px;
        border-radius: 8px;
        border: 1px solid #f1f5f9;
        font-size: 12px;
    }

    .hold-payable-box {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 10px;
        padding: 10px 12px;
        background: #f0fdfa;
        border-radius: 10px;
        border: 1px solid #ccfbf1;
    }

    .hold-payable-box .label {
        font-weight: 800;
        color: #0f766e;
        font-size: 13px;
    }

    .hold-payable-box .amount {
        font-size: 18px;
        font-weight: 900;
        color: #0d9488;
    }

    /* Footer Actions */
    .hold-modal-footer {
        padding: 12px 20px;
        background: white;
        border-top: 1px solid #f1f5f9;
        display: flex;
        justify-content: center;
        gap: 10px;
    }

    /* History Modal Sidebar & Content */
    .held-history-body {
        display: flex;
        height: 75vh;
    }

    .held-history-sidebar {
        width: 320px;
        border-right: 1px solid #e2e8f0;
        display: flex;
        flex-direction: column;
        background: #fff;
    }

    .held-history-content {
        flex: 1;
        display: flex;
        flex-direction: column;
        background: #fdfdfd;
        overflow: hidden;
    }

    .held-item-card {
        padding: 15px;
        border-bottom: 1px solid #f1f5f9;
        cursor: pointer;
        transition: background 0.2s;
    }

    .held-item-card:hover {
        background: #f8fafc;
    }

    .held-item-card.active {
        background: #f0fdfa;
        border-left: 4px solid #0d9488;
    }

    /* Mobile Adjustments */
    @media (max-width: 768px) {
        .held-history-body {
            flex-direction: column;
        }

        .held-history-sidebar {
            width: 100%;
            height: 200px;
            border-right: none;
            border-bottom: 1px solid #e2e8f0;
        }

        .hold-total-row {
            flex-direction: column;
            gap: 5px;
        }
        
        .hold-modal-header h3 span {
            font-size: 14px;
        }
    }

    @media (max-width: 480px) {
        .hold-modal-header {
            padding: 12px 15px;
        }
        
        .hold-modal-body {
            padding: 10px;
        }

        .hold-payable-box .amount {
            font-size: 16px;
        }

        .hold-modal-footer button {
            padding: 8px 15px !important;
            font-size: 13px !important;
        }
    }
</style>

<!-- Hold Modal -->
<div class="pos-modal" id="holdModal">
    <div class="hold-modal-content" style="max-width: 650px;">
        <div class="hold-modal-header">
            <h3>
                <i class="fas fa-save"></i> 
                <span>HOLD CURRENT ORDER</span>
            </h3>
            <button class="close-btn" onclick="closeModal('holdModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="hold-modal-body">
            <div class="hold-ref-container">
                <div class="icon"><i class="fas fa-pen-alt"></i></div>
                <input type="text" id="hold-reference" placeholder="Order note or reference...">
            </div>

            <div class="hold-breakdown-box">
                <div class="hold-breakdown-header">
                    <span>ITEMIZED BREAKDOWN</span>
                    <span class="badge"><span id="hold-item-count">0</span> Items</span>
                </div>

                <div class="hold-item-table-container custom-scroll">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tbody id="hold-order-items"></tbody>
                    </table>
                </div>

                <div class="hold-totals-grid">
                    <div class="hold-total-row">
                        <div class="hold-total-item">
                            <span style="color: #64748b; font-weight: 600;">Subtotal</span>
                            <span id="hold-subtotal" style="font-weight: 700;">0.00</span>
                        </div>
                        <div class="hold-total-item">
                            <span style="color: #cc4d4d; font-weight: 600;">Discount</span>
                            <span id="hold-discount" style="font-weight: 700; color: #cc4d4d;">0.00</span>
                        </div>
                    </div>
                    <div class="hold-total-row">
                        <div class="hold-total-item">
                            <span style="color: #64748b; font-weight: 600;">Tax</span>
                            <span id="hold-tax" style="font-weight: 700;">0.00</span>
                        </div>
                        <div class="hold-total-item">
                            <span style="color: #64748b; font-weight: 600;">Charges</span>
                            <span style="font-weight: 700;"><span id="hold-shipping">0.00</span> + <span id="hold-other">0.00</span></span>
                        </div>
                    </div>
                    <div class="hold-payable-box">
                        <span class="label">NET PAYABLE</span>
                        <span id="hold-payable" class="amount">0.00</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="hold-modal-footer">
            <button onclick="closeModal('holdModal')" style="padding: 10px 20px; border-radius: 8px; border: 1px solid #e2e8f0; background: white; color: #64748b; font-weight: 600; cursor: pointer;">Cancel</button>
            <button onclick="holdOrder()" style="padding: 10px 30px; border-radius: 8px; border: none; background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%); color: white; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-check-circle"></i>
                <span>SAVE HOLD</span>
            </button>
        </div>
    </div>
</div>

<!-- Held Orders List Modal -->
<div class="pos-modal" id="heldOrdersModal">
    <div class="hold-modal-content" style="max-width: 1000px; width: 95%;">
        <div class="hold-modal-header">
            <h3>
                <i class="fas fa-history"></i>
                <span>HELD ORDERS HISTORY</span>
                <span id="held-order-title-ref" class="hidden sm:inline-block" style="font-weight: 400; font-size: 12px; background: rgba(255,255,255,0.15); padding: 2px 10px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.2); margin-left: 10px;">Select Order</span>
            </h3>
            <button class="close-btn" onclick="closeModal('heldOrdersModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="held-history-body">
            <!-- Sidebar -->
            <div class="held-history-sidebar">
                <div style="padding: 15px; border-bottom: 1px solid #f1f5f9;">
                    <div style="position: relative;">
                        <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #0d9488; font-size: 12px;"></i>
                        <input type="text" id="held-order-search" placeholder="Search..." oninput="filterHeldOrders()" style="width: 100%; padding: 10px 10px 10px 35px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 13px; outline: none; background: #fdfdfd;">
                    </div>
                </div>
                <div id="held-orders-list" class="custom-scroll" style="flex: 1; overflow-y: auto;">
                    <!-- List items injected here -->
                </div>
            </div>

            <!-- Content Area -->
            <div id="held-order-detail-view" class="held-history-content">
                <div id="held-order-placeholder" style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #cbd5e1; padding: 40px;">
                    <i class="fas fa-receipt" style="font-size: 40px; opacity: 0.3; margin-bottom: 15px;"></i>
                    <p style="font-size: 13px; font-weight: 600;">SELECT ORDER TO VIEW</p>
                </div>
                
                <div id="held-order-content" style="display: none; flex: 1; flex-direction: column; overflow: hidden;">
                    <div style="padding: 20px; flex: 1; overflow-y: auto;">
                        <div style="flex-wrap: wrap; display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; margin-bottom: 20px;">
                            <div>
                                <h4 style="margin: 0 0 5px 0; font-size: 16px; color: #334155; font-weight: 800;">ORDER DETAILS</h4>
                                <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 10px; font-size: 12px; color: #64748b;">
                                    <span><i class="fas fa-user-circle"></i> <span id="held-detail-customer"></span></span>
                                    <span><i class="fas fa-clock"></i> <span id="held-detail-date"></span></span>
                                </div>
                            </div>
                        </div>

                        <!-- Product Table -->
                        <div class="hold-breakdown-box" style="margin-bottom: 20px;">
                            <div class="hold-item-table-container custom-scroll">
                                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                                    <thead>
                                        <tr style="background: #f8fafc; border-bottom: 1px solid #f1f5f9;">
                                            <th style="padding: 10px; text-align: left; color: #94a3b8; font-size: 10px;">Item</th>
                                            <th style="padding: 10px; text-align: right; width: 80px; color: #94a3b8; font-size: 10px;">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody id="held-detail-items"></tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Totals Section -->
                        <div class="hold-totals-grid" style="border: 1px solid #f1f5f9; border-radius: 12px;">
                            <div class="hold-total-row">
                                <div class="hold-total-item">
                                    <span style="color: #64748b; font-weight: 600;">Subtotal</span>
                                    <span id="held-detail-subtotal" style="font-weight: 700;">0.00</span>
                                </div>
                                <div class="hold-total-item">
                                    <span style="color: #cc4d4d; font-weight: 600;">Discount</span>
                                    <span id="held-detail-discount" style="font-weight: 700; color: #cc4d4d;">0.00</span>
                                </div>
                            </div>
                            <div class="hold-total-row">
                                <div class="hold-total-item">
                                    <span style="color: #64748b; font-weight: 600;">Tax</span>
                                    <span id="held-detail-tax" style="font-weight: 700;">0.00</span>
                                </div>
                                <div class="hold-total-item">
                                    <span style="color: #64748b; font-weight: 600;">Others</span>
                                    <span style="font-weight: 700;"><span id="held-detail-shipping">0.00</span> + <span id="held-detail-other">0.00</span></span>
                                </div>
                            </div>
                            <div class="hold-payable-box" style="background: #0d9488; border: none; color: white;">
                                <span class="label" style="color: white; opacity: 0.9;">TOTAL PAYABLE</span>
                                <span id="held-detail-payable" class="amount" style="color: white;">0.00</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="hold-modal-footer">
                        <button onclick="closeModal('heldOrdersModal')" style="padding: 10px 20px; border-radius: 8px; border: 1px solid #e2e8f0; background: white; color: #64748b; font-weight: 600; cursor: pointer;">Close</button>
                        <button id="btn-resume-held" style="padding: 10px 30px; border-radius: 8px; border: none; background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%); color: white; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-edit"></i>
                            <span>RESUME</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>