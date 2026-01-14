<!-- Payment Modal -->
<div class="pos-modal" id="paymentModal">
    <div class="pos-modal-content" style="max-width: 900px;">
        <div class="pos-modal-header" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white;">
            <h3><i class="fas fa-credit-card"></i> Payment - <span id="payment-customer-name">Walking Customer</span> (<span id="payment-customer-id">0170000000000</span>)</h3>
            <button class="close-btn" onclick="closeModal('paymentModal')" style="display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-times" style="font-size: 16px;"></i>
            </button>        
        </div>
        <div class="pos-modal-body" style="padding: 0;">
            <div style="display: grid; grid-template-columns: 250px 1fr; min-height: 500px;">
                <!-- Left Sidebar - Payment Methods -->
                <div style="background: #f8fafc; border-right: 1px solid #e2e8f0; padding: 20px;">
                    <h4 style="margin: 0 0 15px 0; font-size: 14px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Payment Method:</h4>
                    <div class="payment-methods-grid">
                        <!-- Special Payment Methods (Hidden by default) -->
                        <div id="pm-opening-balance" class="sidebar-payment-method" data-id="opening_balance" onclick="selectPaymentMethod(this, 'opening_balance')" style="display: none;">
                            <div class="pm-content">
                                <i class="fas fa-wallet"></i>
                                <span class="pm-name">Opening Balance</span>
                                <span class="pm-balance">Bal: <span id="pm-opening-balance-value">0.00</span></span>
                            </div>
                        </div>

                        <div id="pm-giftcard" class="sidebar-payment-method" data-id="giftcard" onclick="selectPaymentMethod(this, 'giftcard')" style="display: none;">
                            <div class="pm-content">
                                <i class="fas fa-gift"></i>
                                <span class="pm-name">Gift Card</span>
                                <span class="pm-balance">Bal: <span id="pm-giftcard-balance">0.00</span></span>
                            </div>
                        </div>

                        <div id="pm-credit" class="sidebar-payment-method" data-id="credit" onclick="selectPaymentMethod(this, 'credit')" style="display: none;">
                             <div class="pm-content">
                                <i class="fas fa-hand-holding-usd"></i>
                                <span class="pm-name">Pay Later</span>
                                <span class="pm-balance">Due: <span id="pm-credit-balance">0.00</span></span>
                            </div>
                        </div>
                        <?php 
                        mysqli_data_seek($payment_methods_result, 0);
                        while($pm = mysqli_fetch_assoc($payment_methods_result)): 
                            $icon = 'fa-money-bill';
                            if(strtolower($pm['code']) == 'cash') $icon = 'fa-money-bill-wave';
                            elseif(strtolower($pm['code']) == 'card') $icon = 'fa-credit-card';
                            elseif(strtolower($pm['code']) == 'bank') $icon = 'fa-university';
                            elseif(strtolower($pm['code']) == 'bkash' || strtolower($pm['code']) == 'mobile') $icon = 'fa-mobile-alt';
                            elseif(strtolower($pm['code']) == 'giftcard') $icon = 'fa-gift';
                        ?>
                            <div class="sidebar-payment-method" data-id="<?= $pm['id']; ?>" onclick="selectPaymentMethod(this, <?= $pm['id']; ?>)">
                                <div class="pm-content">
                                    <i class="fas <?= $icon; ?>"></i>
                                    <span class="pm-name"><?= htmlspecialchars($pm['name']); ?></span>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
                
                <!-- Right Panel - Order Details -->
                <div style="padding: 20px;">
                    <!-- Payment Method Buttons -->
                    <div style="text-align: center; margin-bottom: 20px;">
                        <h4 style="margin: 0 0 15px 0; font-size: 14px; color: #64748b;">Payment Method:</h4>
                        <div style="display: inline-flex; gap: 10px; background: #f1f5f9; padding: 4px; border-radius: 8px;">
                            <button class="payment-type-btn active" data-type="full" onclick="setPaymentType('full')" style="padding: 10px 30px; border: none; background: #10b981; color: white; border-radius: 6px; cursor: pointer; font-weight: 500; transition: all 0.2s;">
                                <i class="fas fa-check-circle"></i> FULL PAYMENT
                            </button>
                            <button class="payment-type-btn" data-type="due" onclick="setPaymentType('due')" style="padding: 10px 30px; border: none; background: transparent; color: #64748b; border-radius: 6px; cursor: pointer; font-weight: 500; transition: all 0.2s;">
                                <i class="fas fa-minus-circle"></i> FULL DUE
                            </button>
                        </div>
                        <div style="margin-top: 10px;">
                            <label style="display: inline-flex; align-items: center; gap: 8px; font-size: 13px; color: #64748b; cursor: pointer;">
                                <input type="checkbox" id="sell-with-installment" style="width: 16px; height: 16px;">
                                <i class="fas fa-sync-alt"></i> Sell With Installment
                            </label>
                        </div>
                    </div>
                    
                    <!-- Order Details Section -->
                    <div style="display: grid; grid-template-columns: 1fr 300px; gap: 20px;">
                        <!-- Left: Payment Input -->
                        <div>
                            <div style="display: flex; gap: 10px; margin-bottom: 15px;">
                                <button class="tab-btn active" onclick="switchTab('pay')" style="flex: 1; padding: 10px; border: none; background: #e0f2fe; color: #0369a1; border-radius: 6px; cursor: pointer; font-weight: 500;">Pay Amount</button>
                                <button class="tab-btn" onclick="switchTab('input')" style="flex: 1; padding: 10px; border: none; background: #f1f5f9; color: #64748b; border-radius: 6px; cursor: pointer; font-weight: 500;">Input Amount</button>
                            </div>
                            
                            <!-- Pay Amount View (Default) -->
                            <div id="tab-pay-amount" style="margin-bottom: 15px; background: #e0f2fe; padding: 15px; border-radius: 8px; text-align: center;">
                                <div style="font-size: 13px; color: #0369a1; margin-bottom: 5px;">Total Payable</div>
                                <div style="font-size: 24px; font-weight: bold; color: #0369a1;" id="display-payable-amount">0.00</div>
                            </div>
                            
                            <!-- Input Amount View (Hidden) -->
                            <div id="tab-input-amount" style="margin-bottom: 15px; display: none;">
                                <label style="display: block; margin-bottom: 8px; color: #475569; font-weight: 500;">Received Amount</label>
                                <input type="number" id="amount-received" step="0.01" oninput="calculateChange()" style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 16px; font-weight: 500;" placeholder="0.00">
                                <div style="margin-top: 5px; font-size: 13px; color: #64748b;">Change Return: <span id="change-amount" style="font-weight: 600; color: #0d9488;">0.00</span></div>
                            </div>
                            
                            <div class="payment-input-group" style="margin-bottom: 15px;">
                                <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px; color: #475569; font-weight: 500;">
                                    <i class="fas fa-pencil-alt"></i> Note Here
                                </label>
                                <textarea id="payment-note" rows="3" style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 6px; resize: vertical;" placeholder="Add payment note..."></textarea>
                            </div>
                        </div>
                        
                        <!-- Right: Order Summary -->
                        <div>
                            <h4 style="margin: 0 0 15px 0; font-size: 14px; color: #64748b; text-align: right;">Order Details</h4>
                            <div id="payment-cart-items" style="margin-bottom: 15px; max-height: 100px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px; background: #f8fafc;">
                                <!-- Cart items will be inserted here -->
                            </div>
                            
                            <div id="applied-payments-section" style="margin-bottom: 12px; display: none; background: #f0fdf4; border: 1px dashed #10b981; border-radius: 8px; padding: 12px;">
                                <h4 style="margin: 0 0 8px 0; font-size: 11px; color: #065f46; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700;">Applied Payments:</h4>
                                <div id="applied-payments-list" style="display: flex; flex-direction: column; gap: 6px;">
                                    <!-- Applied payments will appear here -->
                                </div>
                                <div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid rgba(16, 185, 129, 0.2); display: flex; justify-content: space-between; font-weight: 700; color: #065f46; font-size: 12px;">
                                    <span>Total Applied:</span>
                                    <span id="total-applied-amount">৳0.00</span>
                                </div>
                            </div>
                            
                            <div style="background: #f8fafc; padding: 15px; border-radius: 8px; font-size: 13px;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                    <span style="color: #64748b;">Subtotal</span>
                                    <span id="payment-subtotal" style="color: #1e293b; font-weight: 500;">0.00</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                    <span style="color: #64748b;">Discount</span>
                                    <span id="payment-discount" style="color: #1e293b; font-weight: 500;">0.00</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                    <span style="color: #64748b;">Order Tax</span>
                                    <span id="payment-tax" style="color: #1e293b; font-weight: 500;">0.00</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                    <span style="color: #64748b;">Shipping Charge</span>
                                    <span id="payment-shipping" style="color: #1e293b; font-weight: 500;">0.00</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                    <span style="color: #64748b;">Other Charge</span>
                                    <span id="payment-other" style="color: #1e293b; font-weight: 500;">0.00</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                    <span style="color: #64748b;">Interest Amount</span>
                                    <span id="payment-interest" style="color: #1e293b; font-weight: 500;">0.00</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                    <span style="color: #64748b; font-weight: 600;">Payable Amount</span>
                                    <span id="payment-payable" style="color: #1e293b; font-weight: 600;">0.00</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                    <span style="color: #64748b;">Previous Due</span>
                                    <span id="payment-previous-due" style="color: #1e293b; font-weight: 500;">0.00</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 12px; border-top: 1px solid #e2e8f0; padding-top: 8px; margin-top: 4px;">
                                    <span style="color: #1e293b; font-weight: 700;">Total Payable Amount</span>
                                    <span id="payment-total-payable" style="color: #0d9488; font-weight: 700;">0.00</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                    <span style="color: #64748b;">Current Paid Amount</span>
                                    <span id="payment-paid" style="color: #10b981; font-weight: 600;">0.00</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                    <span style="color: #64748b;">Due Amount</span>
                                    <span id="payment-due" style="color: #ef4444; font-weight: 600;">0.00</span>
                                </div>
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: #64748b;">Balance</span>
                                    <span id="payment-balance" style="color: #1e293b; font-weight: 500;">0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Bottom Buttons -->
                    <div style="display: flex; gap: 10px; margin-top: 20px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
                        <button onclick="closeModal('paymentModal')" style="flex: 1; padding: 12px; border: none; background: #ef4444; color: white; border-radius: 6px; cursor: pointer; font-weight: 500; font-size: 14px;">
                            <i class="fas fa-times"></i> Close
                        </button>
                        <button class="complete-btn" onclick="completeSale()" style="flex: 1; padding: 12px; border: none; background: #10b981; color: white; border-radius: 6px; cursor: pointer; font-weight: 500; font-size: 14px;">
                            <i class="fas fa-check-circle"></i> Checkout
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Payment Modal -->
<div class="pos-modal" id="paymentModal">
    <div class="pos-modal-content" style="max-width: 900px;">
        <div class="pos-modal-header" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white;">
            <h3><i class="fas fa-credit-card"></i> Payment - <span id="payment-customer-name">Walking Customer</span> (<span id="payment-customer-id">0170000000000</span>)</h3>
            <button class="close-btn" onclick="closeModal('paymentModal')" style="display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-times" style="font-size: 16px;"></i>
            </button>        
        </div>
        <div class="pos-modal-body" style="padding: 0;">
            <div style="display: grid; grid-template-columns: 250px 1fr; min-height: 500px;">
                <!-- Left Sidebar - Payment Methods -->
                <div style="background: #f8fafc; border-right: 1px solid #e2e8f0; padding: 20px;">
                    <h4 style="margin: 0 0 15px 0; font-size: 14px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Payment Method:</h4>
                    <div class="payment-methods-grid">
                        <!-- Special Payment Methods (Hidden by default) -->
                        <div id="pm-opening-balance" class="sidebar-payment-method" data-id="opening_balance" onclick="selectPaymentMethod(this, 'opening_balance')" style="display: none;">
                            <div class="pm-content">
                                <i class="fas fa-wallet"></i>
                                <span class="pm-name">Opening Balance</span>
                                <span class="pm-balance">Bal: <span id="pm-opening-balance-value">0.00</span></span>
                            </div>
                        </div>

                        <div id="pm-giftcard" class="sidebar-payment-method" data-id="giftcard" onclick="selectPaymentMethod(this, 'giftcard')" style="display: none;">
                            <div class="pm-content">
                                <i class="fas fa-gift"></i>
                                <span class="pm-name">Gift Card</span>
                                <span class="pm-balance">Bal: <span id="pm-giftcard-balance">0.00</span></span>
                            </div>
                        </div>

                        <div id="pm-credit" class="sidebar-payment-method" data-id="credit" onclick="selectPaymentMethod(this, 'credit')" style="display: none;">
                             <div class="pm-content">
                                <i class="fas fa-hand-holding-usd"></i>
                                <span class="pm-name">Pay Later</span>
                                <span class="pm-balance">Due: <span id="pm-credit-balance">0.00</span></span>
                            </div>
                        </div>
                        <?php 
                        mysqli_data_seek($payment_methods_result, 0);
                        while($pm = mysqli_fetch_assoc($payment_methods_result)): 
                            $icon = 'fa-money-bill';
                            if(strtolower($pm['code']) == 'cash') $icon = 'fa-money-bill-wave';
                            elseif(strtolower($pm['code']) == 'card') $icon = 'fa-credit-card';
                            elseif(strtolower($pm['code']) == 'bank') $icon = 'fa-university';
                            elseif(strtolower($pm['code']) == 'bkash' || strtolower($pm['code']) == 'mobile') $icon = 'fa-mobile-alt';
                            elseif(strtolower($pm['code']) == 'giftcard') $icon = 'fa-gift';
                        ?>
                            <div class="sidebar-payment-method" data-id="<?= $pm['id']; ?>" onclick="selectPaymentMethod(this, <?= $pm['id']; ?>)">
                                <div class="pm-content">
                                    <i class="fas <?= $icon; ?>"></i>
                                    <span class="pm-name"><?= htmlspecialchars($pm['name']); ?></span>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
                
                <!-- Right Panel - Order Details -->
                <div style="padding: 20px;">
                    <!-- Payment Method Buttons -->
                    <div style="text-align: center; margin-bottom: 20px;">
                        <h4 style="margin: 0 0 15px 0; font-size: 14px; color: #64748b;">Payment Method:</h4>
                        <div style="display: inline-flex; gap: 10px; background: #f1f5f9; padding: 4px; border-radius: 8px;">
                            <button class="payment-type-btn active" data-type="full" onclick="setPaymentType('full')" style="padding: 10px 30px; border: none; background: #10b981; color: white; border-radius: 6px; cursor: pointer; font-weight: 500; transition: all 0.2s;">
                                <i class="fas fa-check-circle"></i> FULL PAYMENT
                            </button>
                            <button class="payment-type-btn" data-type="due" onclick="setPaymentType('due')" style="padding: 10px 30px; border: none; background: transparent; color: #64748b; border-radius: 6px; cursor: pointer; font-weight: 500; transition: all 0.2s;">
                                <i class="fas fa-minus-circle"></i> FULL DUE
                            </button>
                        </div>
                        <div style="margin-top: 10px;">
                            <label style="display: inline-flex; align-items: center; gap: 8px; font-size: 13px; color: #64748b; cursor: pointer;">
                                <input type="checkbox" id="sell-with-installment" style="width: 16px; height: 16px;">
                                <i class="fas fa-sync-alt"></i> Sell With Installment
                            </label>
                        </div>
                    </div>
                    
                    <!-- Order Details Section -->
                    <div style="display: grid; grid-template-columns: 1fr 300px; gap: 20px;">
                        <!-- Left: Payment Input -->
                        <div>
                            <div style="display: flex; gap: 10px; margin-bottom: 15px;">
                                <button class="tab-btn active" onclick="switchTab('pay')" style="flex: 1; padding: 10px; border: none; background: #e0f2fe; color: #0369a1; border-radius: 6px; cursor: pointer; font-weight: 500;">Pay Amount</button>
                                <button class="tab-btn" onclick="switchTab('input')" style="flex: 1; padding: 10px; border: none; background: #f1f5f9; color: #64748b; border-radius: 6px; cursor: pointer; font-weight: 500;">Input Amount</button>
                            </div>
                            
                            <!-- Pay Amount View (Default) -->
                            <div id="tab-pay-amount" style="margin-bottom: 15px; background: #e0f2fe; padding: 15px; border-radius: 8px; text-align: center;">
                                <div style="font-size: 13px; color: #0369a1; margin-bottom: 5px;">Total Payable</div>
                                <div style="font-size: 24px; font-weight: bold; color: #0369a1;" id="display-payable-amount">0.00</div>
                            </div>
                            
                            <!-- Input Amount View (Hidden) -->
                            <div id="tab-input-amount" style="margin-bottom: 15px; display: none;">
                                <label style="display: block; margin-bottom: 8px; color: #475569; font-weight: 500;">Received Amount</label>
                                <input type="number" id="amount-received" step="0.01" oninput="calculateChange()" style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 16px; font-weight: 500;" placeholder="0.00">
                                <div style="margin-top: 5px; font-size: 13px; color: #64748b;">Change Return: <span id="change-amount" style="font-weight: 600; color: #0d9488;">0.00</span></div>
                            </div>
                            
                            <div class="payment-input-group" style="margin-bottom: 15px;">
                                <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px; color: #475569; font-weight: 500;">
                                    <i class="fas fa-pencil-alt"></i> Note Here
                                </label>
                                <textarea id="payment-note" rows="3" style="width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 6px; resize: vertical;" placeholder="Add payment note..."></textarea>
                            </div>
                        </div>
                        
                        <!-- Right: Order Summary -->
                        <div>
                            <h4 style="margin: 0 0 15px 0; font-size: 14px; color: #64748b; text-align: right;">Order Details</h4>
                            <div id="payment-cart-items" style="margin-bottom: 15px; max-height: 100px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px; background: #f8fafc;">
                                <!-- Cart items will be inserted here -->
                            </div>
                            
                            <div id="applied-payments-section" style="margin-bottom: 12px; display: none; background: #f0fdf4; border: 1px dashed #10b981; border-radius: 8px; padding: 12px;">
                                <h4 style="margin: 0 0 8px 0; font-size: 11px; color: #065f46; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700;">Applied Payments:</h4>
                                <div id="applied-payments-list" style="display: flex; flex-direction: column; gap: 6px;">
                                    <!-- Applied payments will appear here -->
                                </div>
                                <div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid rgba(16, 185, 129, 0.2); display: flex; justify-content: space-between; font-weight: 700; color: #065f46; font-size: 12px;">
                                    <span>Total Applied:</span>
                                    <span id="total-applied-amount">৳0.00</span>
                                </div>
                            </div>
                            
                            <div style="background: #f8fafc; padding: 15px; border-radius: 8px; font-size: 13px;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                    <span style="color: #64748b;">Subtotal</span>
                                    <span id="payment-subtotal" style="color: #1e293b; font-weight: 500;">0.00</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                    <span style="color: #64748b;">Discount</span>
                                    <span id="payment-discount" style="color: #1e293b; font-weight: 500;">0.00</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                    <span style="color: #64748b;">Order Tax</span>
                                    <span id="payment-tax" style="color: #1e293b; font-weight: 500;">0.00</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                    <span style="color: #64748b;">Shipping Charge</span>
                                    <span id="payment-shipping" style="color: #1e293b; font-weight: 500;">0.00</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                    <span style="color: #64748b;">Other Charge</span>
                                    <span id="payment-other" style="color: #1e293b; font-weight: 500;">0.00</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                    <span style="color: #64748b;">Interest Amount</span>
                                    <span id="payment-interest" style="color: #1e293b; font-weight: 500;">0.00</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                    <span style="color: #64748b; font-weight: 600;">Payable Amount</span>
                                    <span id="payment-payable" style="color: #1e293b; font-weight: 600;">0.00</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                    <span style="color: #64748b;">Previous Due</span>
                                    <span id="payment-previous-due" style="color: #1e293b; font-weight: 500;">0.00</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 12px; border-top: 1px solid #e2e8f0; padding-top: 8px; margin-top: 4px;">
                                    <span style="color: #1e293b; font-weight: 700;">Total Payable Amount</span>
                                    <span id="payment-total-payable" style="color: #0d9488; font-weight: 700;">0.00</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                    <span style="color: #64748b;">Current Paid Amount</span>
                                    <span id="payment-paid" style="color: #10b981; font-weight: 600;">0.00</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                    <span style="color: #64748b;">Due Amount</span>
                                    <span id="payment-due" style="color: #ef4444; font-weight: 600;">0.00</span>
                                </div>
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="color: #64748b;">Balance</span>
                                    <span id="payment-balance" style="color: #1e293b; font-weight: 500;">0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Bottom Buttons -->
                    <div style="display: flex; gap: 10px; margin-top: 20px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
                        <button onclick="closeModal('paymentModal')" style="flex: 1; padding: 12px; border: none; background: #ef4444; color: white; border-radius: 6px; cursor: pointer; font-weight: 500; font-size: 14px;">
                            <i class="fas fa-times"></i> Close
                        </button>
                        <button class="complete-btn" onclick="completeSale()" style="flex: 1; padding: 12px; border: none; background: #10b981; color: white; border-radius: 6px; cursor: pointer; font-weight: 500; font-size: 14px;">
                            <i class="fas fa-check-circle"></i> Checkout
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

