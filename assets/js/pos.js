// Force sidebar to be collapsed on POS page
(function () {
    // Set localStorage to unlock (collapsed) state
    localStorage.setItem('sidebarLocked', 'false');

    // Apply collapsed state immediately
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('main-content');
    const toggleIcon = document.getElementById('toggle-icon');

    if (sidebar) {
        sidebar.classList.add('collapsed');
    }
    if (mainContent) {
        mainContent.style.marginLeft = '80px';
    }
    if (toggleIcon) {
        toggleIcon.classList.add('rotate-180');
    }
})();

// Global keydown listener for POS shortcuts
document.addEventListener('keydown', function (e) {
    if (e.key === 'Enter') {
        const paymentModal = document.getElementById('paymentModal');
        const invoiceModal = document.getElementById('invoiceModal');
        const customerSelectModal = document.getElementById('customerSelectModal');

        // If searching product, let it handle its own logic (usually barcode scan or search trigger)
        if (document.activeElement.id === 'product_search') return;

        // If editing quantity, do not open payment modal
        if (document.activeElement.classList.contains('qty-input')) return;

        // Check for specific modals explicitly
        if (document.getElementById('invoiceModal').classList.contains('active')) {
            e.preventDefault();
            if (window.printInvoice) window.printInvoice();
            return;
        }

        const holdModal = document.getElementById('holdModal');
        if (holdModal && (holdModal.classList.contains('active') || holdModal.style.display === 'flex')) {
            console.log('Hold Modal Enter Key Triggered');
            e.preventDefault();
            e.stopImmediatePropagation(); // Ensure no other listeners handle this
            holdOrder();
            return;
        }

        const heldOrdersModal = document.getElementById('heldOrdersModal');
        if (heldOrdersModal && (heldOrdersModal.classList.contains('active') || heldOrdersModal.style.display === 'flex')) {
            e.preventDefault();
            e.stopImmediatePropagation();
            const resumeBtn = document.getElementById('btn-resume-held');
            if (resumeBtn) resumeBtn.click();
            return;
        }

        if (document.getElementById('paymentModal').classList.contains('active')) {
            e.preventDefault();
            if (window.completeSale) window.completeSale();
            return;
        }

        // Generic check for other modals (like customer select) - simply block "Pay Now"
        if (document.querySelector('.pos-modal.active')) {
            return;
        }

        // Default: Pay Now if cart has items and NO modal is open
        if (cart.length > 0) {
            e.preventDefault();

            // FIRST: Validate actual stock in cart (most important check)
            let hasStockIssue = false;
            for (const item of cart) {
                if (item.qty > item.stock) {
                    showToast(`Stock Low - ${item.name}: Only ${item.stock} available. Please adjust quantity.`, 'error');
                    window.lastStockErrorTime = Date.now();
                    hasStockIssue = true;
                    break;
                }
            }

            // If stock issue found, stop immediately - do NOT open modal
            if (hasStockIssue) {
                return;
            }

            // SECOND: Check if a stock error just occurred (within last 1.5 seconds)
            const timeSinceLastError = Date.now() - (window.lastStockErrorTime || 0);
            if (timeSinceLastError < 1500) {
                showToast('Please adjust quantity before proceeding to payment', 'warning');
                return;
            }

            // All validations passed - safe to open modal
            prepareAndOpenPaymentModal();
        }
    }

    // Escape key to close any active modal
    if (e.key === 'Escape') {
        const activeModal = document.querySelector('.pos-modal.active');
        if (activeModal) {
            closeModal(activeModal.id);
        }
    }
});

// Cart data
var cart = [];

var customerId = 0; // Current customer ID

// Track last stock error to prevent modal opening immediately after stock alert
// Make it globally accessible on window object to ensure shared state
window.lastStockErrorTime = 0;

// Modal functions
function openModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;

    modal.style.display = 'flex';
    modal.classList.add('active');

    if (id === 'paymentModal') {
        updatePaymentSummary();
    }
    if (id === 'customerSelectModal') {
        const searchInput = document.getElementById('customer-search-input');
        if (searchInput) {
            searchInput.value = '';
            // Trigger input event to reset the list filtered state
            searchInput.dispatchEvent(new Event('input'));
            setTimeout(() => searchInput.focus(), 100);
        }
    }
}

function closeModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.classList.remove('active');
        modal.style.display = 'none';
    }
}

// Close modal on backdrop click
// --- QUICK ADD PRODUCT LOGIC ---

// 1. Generate Random Code
function generateModalCode() {
    const min = 10000000;
    const max = 99999999;
    document.getElementById('modal_product_code').value = Math.floor(Math.random() * (max - min + 1)) + min;
}

// 2. Calculate Prices
function calculateModalPrices() {
    const pInput = document.getElementById('modal_purchase_price');
    const sInput = document.getElementById('modal_selling_price');
    const taxSelect = document.getElementById('modal_tax_rate_id');
    const taxMethodSelect = document.getElementById('modal_tax_method');

    let purchase = parseFloat(pInput.value) || 0;

    // Get Tax Rate
    let taxRate = 0;
    if (taxSelect && taxSelect.value) {
        taxRate = parseFloat(taxSelect.options[taxSelect.selectedIndex].getAttribute('data-rate')) || 0;
    }

    let method = taxMethodSelect ? taxMethodSelect.value : 'exclusive';

    let finalSellingPrice = purchase;

    // Simple Logic: selling = purchase * (1 + tax/100) if exclusive.
    if (method === 'exclusive') {
        finalSellingPrice = purchase * (1 + (taxRate / 100));
    }

    sInput.value = finalSellingPrice.toFixed(2);
}

// 3. Handle AJAX Submission
function handleQuickAddProduct(e) {
    e.preventDefault();
    const form = document.getElementById('quickProductForm');
    const formData = new FormData(form);

    // Add current store ID context if needed
    const storeId = document.getElementById('store_select').value;
    formData.append('current_store_id', storeId);

    // Show loading state
    const btn = form.querySelector('button[type="submit"]');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    btn.disabled = true;

    fetch('ajax_add_product.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showToast('Success: ' + data.message, 'success');
                closeModal('addProductModal');
                form.reset();

                // Reload products to show new item
                loadProducts(1);

                // Optional: Auto-add to cart logic could go here
            } else {
                showToast('Error: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Server Error', 'error');
        })
        .finally(() => {
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
}


document.querySelectorAll('.pos-modal').forEach(modal => {
    modal.addEventListener('click', function (e) {
        if (e.target === this) {
            this.classList.remove('active');
        }
    });
});


// Store Selection with localStorage Persistence
document.addEventListener('DOMContentLoaded', function () {
    const storeSelect = document.getElementById('store_select');
    const savedStoreId = localStorage.getItem('pos_selected_store');

    // Restore saved store or select first available store (never "All Stores")
    if (savedStoreId && savedStoreId !== 'all') {
        // Check if saved store still exists in options
        const savedOption = storeSelect.querySelector(`option[value="${savedStoreId}"]`);
        if (savedOption) {
            storeSelect.value = savedStoreId;
        } else {
            // Saved store no longer exists, select first available store
            selectFirstAvailableStore(storeSelect);
        }
    } else {
        // No saved store or was "All Stores", select first available store
        selectFirstAvailableStore(storeSelect);
    }

    // Load products with selected store and update currency
    const currentId = storeSelect.value;
    const selectedStoreObj = stores.find(s => s.id == currentId);
    if (selectedStoreObj) {
        window.currencySymbol = selectedStoreObj.currency_symbol || '৳';
        window.currencyName = selectedStoreObj.currency_full_name || 'Taka';
    }
    loadProducts(1);
});

// Helper function to select first available store (not "All Stores")
function selectFirstAvailableStore(storeSelect) {
    const firstStoreOption = storeSelect.querySelector('option[data-default="true"]');
    if (firstStoreOption) {
        storeSelect.value = firstStoreOption.value;
        localStorage.setItem('pos_selected_store', firstStoreOption.value);
    } else {
        // Fallback: select first non-"all" option
        const options = storeSelect.querySelectorAll('option');
        for (let option of options) {
            if (option.value !== 'all') {
                storeSelect.value = option.value;
                localStorage.setItem('pos_selected_store', option.value);
                break;
            }
        }
    }
}

// Store Selection Listener - Save to localStorage when changed
// Store Selection Listener - Save to localStorage when changed
// Use 'mousedown' to track previous value before change
let previousStoreId = document.getElementById('store_select').value;
document.getElementById('store_select').addEventListener('mousedown', function () {
    previousStoreId = this.value;
});

document.getElementById('store_select').addEventListener('change', function () {
    const selectedStore = this.value;

    // Check if cart has items
    if (cart.length > 0) {
        Swal.fire({
            title: 'Clear Cart?',
            text: "Switching stores will clear your current cart. Do you want to proceed?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, switch & clear',
            cancelButtonText: 'No, stay here'
        }).then((result) => {
            if (result.isConfirmed) {
                // Clear cart
                cart = [];
                renderCart();

                // Proceed with store change
                localStorage.setItem('pos_selected_store', selectedStore);

                // --- NEW: Update Currency Symbol on Confirmed Change ---
                const selectedStoreObj = stores.find(s => s.id == selectedStore);
                if (selectedStoreObj) {
                    window.currencySymbol = selectedStoreObj.currency_symbol || '৳';
                    window.currencyName = selectedStoreObj.currency_full_name || 'Taka';

                    // --- UPDATE SETTINGS ON SWITCH ---
                    if (window.storeSettingsMap && window.storeSettingsMap[selectedStore]) {
                        window.posSettings = window.storeSettingsMap[selectedStore];
                    } else {
                        window.posSettings = {}; // Reset if no settings found
                    }
                    // ---------------------------------
                }
                // -------------------------------------------------------

                loadProducts(1);
                showToast('Cart cleared and store switched', 'info');
            } else {
                // Revert selection
                this.value = previousStoreId;
            }
        });
    } else {
        // Cart is empty, proceed normally
        localStorage.setItem('pos_selected_store', selectedStore);

        // --- NEW: Update Currency Symbol on Change ---
        const selectedStoreObj = stores.find(s => s.id == selectedStore);
        if (selectedStoreObj) {
            window.currencySymbol = selectedStoreObj.currency_symbol || '৳';
            window.currencyName = selectedStoreObj.currency_full_name || 'Taka';

            // --- UPDATE SETTINGS ON SWITCH ---
            if (window.storeSettingsMap && window.storeSettingsMap[selectedStore]) {
                window.posSettings = window.storeSettingsMap[selectedStore];
            } else {
                window.posSettings = {}; // Reset if no settings found
            }
            // ---------------------------------
        }
        // ----------------------------------------------

        loadProducts(1); // Reload from page 1 when store changes
    }
});

// Track current filters
let currentCategory = 'all';
let currentSearch = '';

// Load Products via AJAX
function loadProducts(page) {
    const grid = document.querySelector('.product-grid');
    grid.style.opacity = '0.5';

    // Get selected store
    const storeId = document.getElementById('store_select').value;
    const categoryId = currentCategory === 'all' ? 0 : currentCategory;
    const searchQuery = encodeURIComponent(currentSearch);

    // Prevent default button behavior
    if (event) event.preventDefault();

    let url = `get_products.php?page=${page}&store_id=${storeId}`;
    if (categoryId > 0) url += `&category_id=${categoryId}`;
    if (searchQuery) url += `&search=${searchQuery}`;

    fetch(url)
        .then(response => response.json())
        .then(data => {
            grid.innerHTML = data.html;
            document.getElementById('pagination-controls').innerHTML = data.pagination_html;
            grid.style.opacity = '1';

            // Scroll to top of grid
            grid.scrollTop = 0;
        })
        .catch(error => {
            console.error('Error loading products:', error);
            grid.style.opacity = '1';
        });
}

// Add to cart with strict stock validation and max limit check
function addToCart(id, name, price, stock = null, qtyToAdd = 1) {
    id = parseInt(id); // Ensure ID is a number

    // Check if "All Stores" is selected
    const storeSelect = document.getElementById('store_select');
    if (!storeSelect || storeSelect.value === 'all' || storeSelect.value === '0') {
        showToast('Please select a specific store to add products to cart', 'warning');
        // Highlight store dropdown
        if (storeSelect) {
            storeSelect.style.border = '2px solid #f59e0b';
            storeSelect.style.boxShadow = '0 0 0 3px rgba(245, 158, 11, 0.2)';
            setTimeout(() => {
                storeSelect.style.border = '';
                storeSelect.style.boxShadow = '';
            }, 2000);
        }
        return;
    }

    // Get stock and limit from product card
    let limit = 0;
    const productCard = document.querySelector(`.product-card[data-id="${id}"]`);

    if (stock === null) {
        stock = productCard ? parseFloat(productCard.dataset.stock) || 0 : 0;
    }

    if (productCard) {
        limit = parseInt(productCard.dataset.limit) || 0;
    }

    const existingItem = cart.find(item => item.id === id);
    const currentQty = existingItem ? existingItem.qty : 0;
    const newTotalQty = currentQty + qtyToAdd;

    // Check if adding would exceed stock
    if (newTotalQty > stock) {
        showToast(`Cannot add! Stock available: ${stock}`, 'error');
        return;
    }

    // Check max sell limit
    if (limit > 0 && newTotalQty > limit) {
        showToast(`Limit Exceeded! Max ${limit} per customer.`, 'error');
        return;
    }

    if (existingItem) {
        existingItem.qty = newTotalQty;
        existingItem.stock = stock;
        existingItem.limit = limit; // Update limit info
    } else {
        cart.push({
            id: id,
            name: name,
            price: parseFloat(price),
            qty: qtyToAdd,
            stock: stock,
            limit: limit
        });
    }

    renderCart();
    showToast('Added to cart: ' + name);
}

// Update quantity with stock validation and limit
function updateQty(id, change) {
    id = parseInt(id);
    const item = cart.find(item => item.id === id);
    if (item) {
        const newQty = item.qty + change;
        const limit = item.limit || 0;

        if (change > 0) {
            if (newQty > item.stock) {
                showToast(`Cannot exceed stock! Only ${item.stock} available.`, 'error');
                window.lastStockErrorTime = Date.now();
                return;
            }
            if (limit > 0 && newQty > limit) {
                showToast(`Limit Exceeded! Max ${limit} per customer.`, 'error');
                return;
            }
        }

        if (newQty <= 0) {
            removeFromCart(id);
        } else {
            item.qty = newQty;
            renderCart();
        }
    }
}

// Set quantity directly with stock validation and limit
function setQty(id, newQty) {
    id = parseInt(id);
    const item = cart.find(item => item.id === id);
    if (item) {
        newQty = parseInt(newQty) || 0;
        const limit = item.limit || 0;

        if (newQty > item.stock) {
            showToast(`Cannot exceed stock! Only ${item.stock} available.`, 'error');
            window.lastStockErrorTime = Date.now();
            item.qty = item.stock;
            // Also check limit if stock allows but limit doesn't (though usually limit < stock)
            if (limit > 0 && item.qty > limit) item.qty = limit;
            renderCart();
            return;
        }

        if (limit > 0 && newQty > limit) {
            showToast(`Limit Exceeded! Max ${limit} per customer.`, 'error');
            item.qty = limit;
            renderCart();
            return;
        }

        if (newQty <= 0) {
            removeFromCart(id);
        } else {
            item.qty = newQty;
            renderCart();
        }
    }
}

// Remove from cart
function removeFromCart(id) {
    id = parseInt(id);
    cart = cart.filter(item => item.id !== id);
    renderCart();
}

// Render cart
function renderCart() {
    const container = document.getElementById('cart-items');

    if (cart.length === 0) {
        container.innerHTML = '<div class="empty-cart"><i class="fas fa-shopping-cart"></i><p>Cart is empty. Add products to begin.</p></div>';
        updateTotals();
        return;
    }

    let html = '';
    cart.forEach(item => {
        const subtotal = item.price * item.qty;
        html += `
            <div class="cart-item">
                <div class="qty-control">
                    <button onclick="updateQty(${item.id}, -1)">-</button>
                    <input type="number" class="qty-input" value="${item.qty}" min="1" 
                        onchange="setQty(${item.id}, this.value)" 
                        onfocus="this.select()"
                        onkeydown="if(event.key === 'Enter') { this.blur(); }">
                    <button onclick="updateQty(${item.id}, 1)">+</button>
                </div>
                <div class="product-name">${item.name}</div>
                <div class="price">${window.currencySymbol}${item.price.toFixed(2)}</div>
                <div class="subtotal">
                    ${window.currencySymbol}${subtotal.toFixed(2)}
                    <span class="remove-btn" onclick="removeFromCart(${item.id})"><i class="fas fa-trash"></i></span>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
    updateTotals();

    // Auto-focus and select last added item
    const inputs = container.querySelectorAll('.qty-input');
    if (inputs.length > 0) {
        const lastInput = inputs[inputs.length - 1];
        lastInput.focus();
        lastInput.select();
    }
}

// Update totals
function updateTotals() {
    let subtotal = 0;
    let totalQty = 0;

    cart.forEach(item => {
        subtotal += item.price * item.qty;
        totalQty += item.qty;
    });

    const discount = parseFloat(document.getElementById('discount-input').value) || 0;
    const taxPercent = parseFloat(document.getElementById('tax-input').value) || 0;
    const shipping = parseFloat(document.getElementById('shipping-input').value) || 0;
    const other = parseFloat(document.getElementById('other-input').value) || 0;

    const taxAmount = (subtotal - discount) * (taxPercent / 100);
    let rawGrandTotal = subtotal - discount + taxAmount + shipping + other;

    // --- Rounding Logic ---
    let roundedGrandTotal = Math.round(rawGrandTotal);
    let roundingAdjustment = roundedGrandTotal - rawGrandTotal;

    // Prevent -0.00 display issues
    if (Math.abs(roundingAdjustment) < 0.001) roundingAdjustment = 0;

    document.getElementById('total-items').textContent = `${cart.length} (${totalQty})`;
    document.getElementById('cart-total').textContent = subtotal.toFixed(2);

    // Display Rounded Total
    document.getElementById('grand-total').innerHTML = window.currencySymbol + roundedGrandTotal.toFixed(2) +
        `<small style="font-size: 11px; color: #64748b; display: block;">(Adj: ${roundingAdjustment > 0 ? '+' : ''}${roundingAdjustment.toFixed(2)})</small>`;

    document.getElementById('footer-total').textContent = window.currencySymbol + roundedGrandTotal.toFixed(2);

    // Store adjustment in hidden field if needed, or just calculate on submission
    // We'll create a hidden input if it doesn't exist to store this for easy access
    let adjInput = document.getElementById('rounding-adjustment-input');
    if (!adjInput) {
        adjInput = document.createElement('input');
        adjInput.type = 'hidden';
        adjInput.id = 'rounding-adjustment-input';
        document.body.appendChild(adjInput);
    }
    adjInput.value = roundingAdjustment.toFixed(2);
}

// Recalculate on input change
['discount-input', 'tax-input', 'shipping-input', 'other-input'].forEach(id => {
    document.getElementById(id).addEventListener('input', updateTotals);
});

// Payment functions
function prepareAndOpenPaymentModal() {
    // Check if cart is empty
    if (cart.length === 0) {
        showToast('Cart is empty', 'error');
        return;
    }

    // --- NEW: Validate Limits Before Checkout ---
    for (const item of cart) {
        if (item.limit > 0 && item.qty > item.limit) {
            Swal.fire({
                icon: 'error',
                title: 'Limit Exceeded',
                text: `You cannot sell more than ${item.limit} of "${item.name}" per customer.`,
                confirmButtonColor: '#f43f5e'
            });
            // Also show toast just in case
            showToast(`Limit Exceeded: Max ${item.limit} for ${item.name}`, 'error');
            return; // STOP execution
        }
    }
    // --------------------------------------------

    // Prevent opening modal if a stock error just occurred (within last 1.5 seconds)
    const timeSinceLastError = Date.now() - window.lastStockErrorTime;
    if (timeSinceLastError < 1500) {
        showToast('Please adjust quantity before proceeding to payment', 'warning');
        return;
    }

    // Check for stock availability before opening payment modal
    for (const item of cart) {
        if (item.qty > item.stock) {
            showToast(`Stock Low - ${item.name}: Only ${item.stock} available. Please adjust quantity.`, 'error');
            window.lastStockErrorTime = Date.now(); // Mark error time
            return; // Stop here, do not open modal
        }
    }

    // Toggle Opening Balance Wallet visibility based on updated UI
    const openingBal = parseFloat(document.getElementById('customer_opening_balance').value) || 0;
    const pmOpeningBal = document.getElementById('pm-opening-balance');
    const pmOpeningBalVal = document.getElementById('pm-opening-balance-value');
    if (openingBal > 0) {
        if (pmOpeningBal) pmOpeningBal.style.display = 'flex';
        if (pmOpeningBalVal) pmOpeningBalVal.textContent = openingBal.toFixed(2);
    } else {
        if (pmOpeningBal) pmOpeningBal.style.display = 'none';
        if (pmOpeningBal) pmOpeningBal.classList.remove('selected');
    }

    // Toggle Gift Card method visibility
    const giftcardBal = parseFloat(document.getElementById('customer_giftcard_balance').value) || 0;
    const pmGiftcard = document.getElementById('pm-giftcard');
    const pmGiftcardBal = document.getElementById('pm-giftcard-balance');
    if (giftcardBal > 0) {
        if (pmGiftcard) pmGiftcard.style.display = 'flex';
        if (pmGiftcardBal) pmGiftcardBal.textContent = giftcardBal.toFixed(2);
    } else {
        if (pmGiftcard) pmGiftcard.style.display = 'none';
        if (pmGiftcard) pmGiftcard.classList.remove('selected');
    }

    // Calculate current totals using the shared function to populate global elements first
    if (window.updatePaymentSummary) window.updatePaymentSummary();

    // Gets the rounded total from the footer or re-calculates
    // We used rounded total in updateTotals, so footer-total has the rounded value
    const grandTotalStr = document.getElementById('footer-total').textContent.replace(window.currencySymbol, '');
    const grandTotal = parseFloat(grandTotalStr) || 0;
    const currentCustomerId = parseInt(document.getElementById('selected_customer_id').value) || 0;
    const customerName = document.getElementById('selected-customer-name').textContent || 'Walking Customer';

    // Open the shared payment modal
    window.openPaymentModal({
        totalPayable: grandTotal,
        previousDue: parseFloat(document.getElementById('customer_due_balance').value) || 0,
        customerId: currentCustomerId,
        customerName: customerName,
        onSubmit: submitPosSale
    });

    // Default to Full Payment view
    if (window.setPaymentType) window.setPaymentType('full');
}



// Toast notification
function showToast(message, type = 'success') {
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 2000,
        timerProgressBar: true
    });
    Toast.fire({
        icon: type,
        title: message
    });
}

// Toggle Fullscreen Mode
function toggleFullscreen() {
    const icon = document.getElementById('fullscreen-icon');

    if (!document.fullscreenElement && !document.webkitFullscreenElement && !document.mozFullScreenElement && !document.msFullscreenElement) {
        // Enter fullscreen
        const elem = document.documentElement;
        if (elem.requestFullscreen) {
            elem.requestFullscreen();
        } else if (elem.webkitRequestFullscreen) {
            elem.webkitRequestFullscreen();
        } else if (elem.mozRequestFullScreen) {
            elem.mozRequestFullScreen();
        } else if (elem.msRequestFullscreen) {
            elem.msRequestFullscreen();
        }
        icon.classList.remove('fa-expand');
        icon.classList.add('fa-compress');
    } else {
        // Exit fullscreen
        if (document.exitFullscreen) {
            document.exitFullscreen();
        } else if (document.webkitExitFullscreen) {
            document.webkitExitFullscreen();
        } else if (document.mozCancelFullScreen) {
            document.mozCancelFullScreen();
        } else if (document.msExitFullscreen) {
            document.msExitFullscreen();
        }
        icon.classList.remove('fa-compress');
        icon.classList.add('fa-expand');
    }
}

// Listen for fullscreen change to update icon
document.addEventListener('fullscreenchange', updateFullscreenIcon);
document.addEventListener('webkitfullscreenchange', updateFullscreenIcon);
document.addEventListener('mozfullscreenchange', updateFullscreenIcon);
document.addEventListener('MSFullscreenChange', updateFullscreenIcon);

function updateFullscreenIcon() {
    const icon = document.getElementById('fullscreen-icon');
    if (document.fullscreenElement || document.webkitFullscreenElement || document.mozFullScreenElement || document.msFullscreenElement) {
        icon.classList.remove('fa-expand');
        icon.classList.add('fa-compress');
    } else {
        icon.classList.remove('fa-compress');
        icon.classList.add('fa-expand');
    }
}

// Lock Screen Function
function lockScreen() {
    Swal.fire({
        title: '<i class="fas fa-lock" style="font-size: 48px; color: #0d9488; margin-bottom: 15px;"></i><br>Screen Locked',
        html: `
            <p style="color: #64748b; margin-bottom: 20px;">Enter your password to unlock</p>
            <input type="password" id="unlock-password" class="swal2-input" placeholder="Password" style="margin: 0;">
        `,
        showCancelButton: false,
        confirmButtonText: '<i class="fas fa-unlock"></i> Unlock',
        confirmButtonColor: '#0d9488',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showCloseButton: false,
        backdrop: `
            rgba(15, 23, 42, 0.95)
            left top
            no-repeat
        `,
        customClass: {
            popup: 'lock-screen-popup'
        },
        preConfirm: () => {
            const password = document.getElementById('unlock-password').value;
            if (!password) {
                Swal.showValidationMessage('Please enter your password');
                return false;
            }
            // You can add actual password validation here via AJAX
            // For now, we just check if password is entered
            return password;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            showToast('Screen Unlocked', 'success');
        }
    });

    // Focus on password input after modal opens
    setTimeout(() => {
        document.getElementById('unlock-password')?.focus();
    }, 100);
}

// Convert number to words (for invoice)
function numberToWords(num) {
    const ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
        'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
        'Seventeen', 'Eighteen', 'Nineteen'];
    const tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

    if (num === 0) return 'Zero ' + (window.currencyName || 'Taka') + ' Only';

    function convertGroup(n) {
        if (n < 20) return ones[n];
        if (n < 100) return tens[Math.floor(n / 10)] + (n % 10 ? ' ' + ones[n % 10] : '');
        return ones[Math.floor(n / 100)] + ' Hundred' + (n % 100 ? ' ' + convertGroup(n % 100) : '');
    }

    let result = '';
    const crore = Math.floor(num / 10000000);
    const lakh = Math.floor((num % 10000000) / 100000);
    const thousand = Math.floor((num % 100000) / 1000);
    const remainder = Math.floor(num % 1000);
    const paisa = Math.round((num - Math.floor(num)) * 100);

    if (crore) result += convertGroup(crore) + ' Crore ';
    if (lakh) result += convertGroup(lakh) + ' Lakh ';
    if (thousand) result += convertGroup(thousand) + ' Thousand ';
    if (remainder) result += convertGroup(remainder);

    result = result.trim() + ' ' + (window.currencyName || 'Taka');
    if (paisa) result += ' and ' + convertGroup(paisa) + ' ' + (window.currencyPaisaName || 'Paisa');
    result += ' Only';

    return result;
}

// Complete sale - acts as the callback for the shared payment modal
function submitPosSale(paymentData) {
    if (cart.length === 0) {
        showToast('Cart is empty!', 'error');
        return;
    }

    // Original pos.php used store_select from the modal DOM, but shared modal might not have it inside the modal form?
    // Wait, the store select is usually in the MAIN POS header, not the modal.
    // Let's check where store_select is. Usually in header.
    const storeSelect = document.getElementById('store_select');
    if (!storeSelect || !storeSelect.value || storeSelect.value === 'all') {
        closeModal('paymentModal');
        Swal.fire({
            icon: 'warning',
            title: 'Store Selection Required',
            text: 'Please select a specific store to proceed with the sale.',
            confirmButtonColor: '#0d9488'
        });

        if (storeSelect) {
            storeSelect.style.border = '2px solid red';
            setTimeout(() => storeSelect.style.border = '', 2000);
        }
        return;
    }

    // Walking customer check is handled by payment_modal.js UI logic (disable button), 
    // but we can double check here or just rely on server.
    // Server side walking customer check is already fixed.

    const formData = new FormData();
    formData.append('action', 'process_payment');
    formData.append('cart', JSON.stringify(cart));
    formData.append('customer_id', document.getElementById('selected_customer_id').value || 0);
    formData.append('store_id', storeSelect.value);
    formData.append('payment_method_id', paymentData.selectedPaymentMethod);
    formData.append('discount', document.getElementById('discount-input').value || 0);
    formData.append('tax_percent', document.getElementById('tax-input').value || 0);
    formData.append('shipping', document.getElementById('shipping-input').value || 0);
    formData.append('other_charge', document.getElementById('other-input').value || 0);

    // Add Rounding Adjustment
    const roundingAdj = document.getElementById('rounding-adjustment-input') ? document.getElementById('rounding-adjustment-input').value : 0;
    formData.append('rounding_adjustment', roundingAdj);

    formData.append('amount_received', paymentData.amountReceived);

    // Ensure payments are formatted correctly for PHP
    // paymentData.appliedPayments is array of objects { type, amount, label }
    // PHP expects exact structure? 
    // PHP decoding: is_array($payments). 
    formData.append('payments', JSON.stringify(paymentData.appliedPayments));

    formData.append('sale_date', document.getElementById('sale-date').value);
    formData.append('walking_customer_mobile', document.getElementById('selected-customer-phone').textContent || '');

    const previousDue = parseFloat(document.getElementById('payment-previous-due')?.textContent) || 0;
    formData.append('previous_due', previousDue);

    // Installment Data
    if (window.installmentMode) {
        formData.append('is_installment', 1);
        formData.append('inst_duration', document.getElementById('inst-duration').value);
        formData.append('inst_interval', document.getElementById('inst-interval').value);
        formData.append('inst_count', document.getElementById('inst-count').value);
        formData.append('inst_interest_percent', document.getElementById('inst-interest-percent').value);
        formData.append('inst_interest_amount', document.getElementById('inst-interest-amount').value);
    }

    const btn = document.querySelector('.complete-btn'); // Shared modal button class
    if (btn) {
        var originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        btn.disabled = true;
    }

    fetch('/pos/pos/save_sale.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                closeModal('paymentModal');
                // Reset shared modal state via window global if needed, or openPaymentModal resets it next time.

                // Update customer balance in UI
                if (data.new_balance !== undefined) {
                    const totalBal = parseFloat(data.new_balance);
                    document.getElementById('customer_total_balance').value = totalBal;

                    const currentDue = totalBal > 0 ? totalBal : 0;
                    const spendableCredit = totalBal < 0 ? Math.abs(totalBal) : 0;

                    document.getElementById('customer_due_balance').value = currentDue;
                    document.getElementById('customer_credit').value = spendableCredit;

                    const dueDisplay = document.getElementById('selected-customer-due-display');
                    const dueAmount = document.getElementById('selected-customer-due-amount');
                    if (dueDisplay && dueAmount) {
                        if (currentDue > 0) {
                            dueDisplay.style.display = 'block';
                            dueAmount.textContent = currentDue.toFixed(2);
                        } else {
                            dueDisplay.style.display = 'none';
                        }
                    }
                }

                // Populate Invoice (Safeguarded)
                const invIdEl = document.getElementById('inv-id');
                if (invIdEl) invIdEl.textContent = data.invoice_id;

                const invModalIdEl = document.getElementById('inv-modal-id');
                if (invModalIdEl) invModalIdEl.textContent = data.invoice_id;

                const invDateEl = document.getElementById('inv-date');
                if (invDateEl) invDateEl.textContent = new Date().toLocaleString();

                // Store Info
                const currentStore = stores.find(s => s.id == storeSelect.value) || stores[0];

                // Prepare customer data
                const customerNameEl = document.getElementById('selected-customer-name');
                const customerName = customerNameEl ? customerNameEl.textContent : 'Walking Customer';
                const customerPhoneEl = document.getElementById('selected-customer-phone');
                const customerPhone = customerPhoneEl ? customerPhoneEl.textContent : '';

                // Prepare payment methods array
                const paymentMethods = [];
                const paymentMethodName = paymentData.selectedPaymentMethodName || 'Cash on Hand';

                // Determine Payment Type Label
                let paymentTypeLabel = 'Full Payment';
                const isInstallment = window.installmentMode || false;
                const totalPaidVal = parseFloat(paymentData.amountReceived || 0) + paymentData.appliedPayments.reduce((s, p) => s + p.amount, 0);
                const payableVal = parseFloat(document.getElementById('payment-payable').textContent) || 0;

                if (isInstallment) {
                    paymentTypeLabel = 'Down Payment';
                } else if (totalPaidVal < payableVal - 0.01) {
                    if (totalPaidVal <= 0.01) paymentTypeLabel = 'Full Due';
                    else paymentTypeLabel = 'Partial Payment';
                } else if (totalPaidVal > payableVal + 0.01) {
                    // Change given, still Full Payment
                    paymentTypeLabel = 'Full Payment';
                }

                // Add primary payment if amount received > 0 OR if it's Full Due
                if (paymentData.amountReceived > 0 || paymentTypeLabel === 'Full Due') {
                    paymentMethods.push({
                        type: paymentTypeLabel,
                        method: paymentMethodName,
                        amount: paymentData.amountReceived,
                        date: new Date().toISOString()
                    });
                }

                // Add applied payments (bKash, Nagad, Gift Card, etc.)
                if (paymentData.appliedPayments && paymentData.appliedPayments.length > 0) {
                    paymentData.appliedPayments.forEach(p => {
                        paymentMethods.push({
                            type: paymentTypeLabel, // Same type for all in this transaction
                            method: p.label || p.type,
                            amount: p.amount,
                            date: new Date().toISOString()
                        });
                    });
                }

                // Calculate totals
                const payable = parseFloat(document.getElementById('payment-payable').textContent) || 0;
                const totalPaid = parseFloat(paymentData.amountReceived || 0) + paymentData.appliedPayments.reduce((s, p) => s + p.amount, 0);
                const change = Math.max(0, totalPaid - payable);
                const due = Math.max(0, payable - totalPaid);
                const prevDueVal = parseFloat(document.getElementById('payment-previous-due').textContent) || 0;
                const totalDueVal = data.new_balance !== undefined ? parseFloat(data.new_balance) : (due + prevDueVal);

                // Open invoice modal using the proper function
                try {
                    window.openInvoiceModal({
                        invoiceId: data.invoice_id,
                        store: {
                            name: currentStore.store_name,
                            address: currentStore.address || '',
                            city: currentStore.city_zip || '',
                            phone: currentStore.phone || '',
                            email: currentStore.email || '',
                            vat_number: currentStore.vat_number || currentStore.bin_number || ''
                        },
                        customer: {
                            name: customerName,
                            phone: customerPhone
                        },
                        items: cart.map((item, index) => ({
                            name: item.name,
                            qty: item.qty,
                            price: item.price,
                            unit: item.unit || ''
                        })),
                        totals: {
                            subtotal: parseFloat(document.getElementById('payment-subtotal').textContent) || 0,
                            discount: parseFloat(document.getElementById('payment-discount').textContent) || 0,
                            tax: parseFloat(document.getElementById('payment-tax').textContent) || 0,
                            shipping: parseFloat(document.getElementById('payment-shipping').textContent) || 0,
                            grandTotal: payable,
                            paid: totalPaid,
                            due: due,
                            previousDue: prevDueVal,
                            totalDue: totalDueVal,
                            // Installment info
                            isInstallment: isInstallment,
                            installmentCount: isInstallment ? document.getElementById('inst-count').value : 0,
                            installmentDuration: isInstallment ? document.getElementById('inst-duration').value : 0,
                            installmentInterval: isInstallment ? document.getElementById('inst-interval').value : 0
                        },
                        payments: paymentMethods,
                        paymentMethod: paymentMethodName
                    });
                } catch (e) {
                    console.error('Invoice Modal Error:', e);
                    Swal.fire({
                        icon: 'error',
                        title: 'Invoice Display Error',
                        text: 'Sale was saved, but receipt could not be displayed: ' + e.message
                    });
                }

                // Reload-less UI Sync
                const resetPOS = () => {
                    // 1. Clear Cart
                    cart = [];
                    renderCart();

                    // 2. Reset Totals
                    document.getElementById('discount-input').value = 0;
                    document.getElementById('tax-input').value = 0;
                    document.getElementById('shipping-input').value = 0;
                    document.getElementById('other-input').value = 0;
                    if (document.getElementById('payment-note')) document.getElementById('payment-note').value = '';
                    updateTotals();

                    // 3. Refresh Stock Grid
                    loadProducts(1);

                    // 4. Update Customer Info if applicable
                    if (data.new_balance !== undefined) {
                        // Data from backend: new_balance, opening_balance, giftcard_balance
                        const customerId = document.getElementById('selected_customer_id').value;
                        if (customerId > 0) {
                            document.getElementById('customer_total_balance').value = data.new_balance;
                            document.getElementById('customer_due_balance').value = data.new_balance > 0 ? data.new_balance : 0;
                            document.getElementById('customer_opening_balance').value = data.opening_balance;
                            document.getElementById('customer_giftcard_balance').value = data.giftcard_balance;

                            // Update display badges
                            const dueDisplay = document.getElementById('selected-customer-due-display');
                            const dueAmount = document.getElementById('selected-customer-due-amount');
                            if (dueDisplay && dueAmount) {
                                if (data.new_balance > 0) {
                                    dueDisplay.style.display = 'block';
                                    dueAmount.textContent = parseFloat(data.new_balance).toFixed(2);
                                } else {
                                    dueDisplay.style.display = 'none';
                                }
                            }
                        }
                    }

                    // 5. Update Stock Alert Count
                    const badge = document.querySelector('.stock-alert-badge');
                    if (badge) {
                        if (data.alert_count > 0) {
                            badge.textContent = data.alert_count;
                            badge.style.display = 'block';
                        } else {
                            badge.style.display = 'none';
                        }
                    }
                };

                // Set callback for invoice close
                window._invoiceModalOnClose = resetPOS;

                // Show success toast
                // showToast('Sale Completed Successfully', 'success');
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message
                });
                if (btn) {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            }
        })
        .catch(error => {
            console.error('Error in submitPosSale:', error);
            Swal.fire({
                icon: 'error',
                title: 'Submission Error',
                text: 'See console for details. ' + error.message
            });
            if (btn) {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        });
}

let allHeldOrders = [];
function openHeldOrdersModal() {
    allHeldOrders = []; // Clear before fetching fresh data
    const storeId = document.getElementById('store_select').value;
    const formData = new FormData();
    formData.append('action', 'get_held_orders');
    formData.append('store_id', storeId === 'all' ? 0 : storeId);
    formData.append('_t', Date.now()); // Cache buster

    fetch('/pos/pos/save_sale.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                allHeldOrders = data.orders;
                renderHeldOrdersList(allHeldOrders);
                // Hide detail content, show placeholder
                document.getElementById('held-order-content').style.display = 'none';
                document.getElementById('held-order-placeholder').style.display = 'flex';
                document.getElementById('held-order-title-ref').textContent = 'Select an order';
                openModal('heldOrdersModal');
            } else {
                showToast('Error fetching held orders', 'error');
            }
        });
}


function renderHeldOrdersList(orders) {
    const listBody = document.getElementById('held-orders-list');
    listBody.innerHTML = '';

    if (orders.length === 0) {
        listBody.innerHTML = '<div style="padding: 30px; text-align: center; color: #cbd5e1; font-size: 14px;"><i class="fas fa-folder-open" style="font-size: 40px; display: block; margin-bottom: 15px; opacity: 0.3;"></i>No orders found</div>';
        return;
    }

    orders.forEach(order => {
        const item = document.createElement('div');
        item.className = 'held-order-item';
        item.dataset.id = order.invoice_id;
        item.style.cssText = 'padding: 15px 20px; border-bottom: 1px solid #f1f5f9; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; justify-content: space-between; position: relative; border-radius: 12px; margin-bottom: 8px;';
        item.onclick = () => showHeldOrderSummary(order.invoice_id);

        item.innerHTML = `
            <div style="display: flex; flex-direction: column; gap: 4px;">
                <span class="order-ref" style="font-weight: 800; color: #334155; font-size: 14px;">${order.invoice_note || order.invoice_id}</span>
                <span style="font-size: 11px; color: #94a3b8; font-weight: 600;">${new Date(order.created_at).toLocaleDateString()} • <span style="color: #0d9488;">${order.total_items} Items</span></span>
            </div>
            <div style="display: flex; align-items: center; gap: 15px;">
                <span style="font-weight: 800; color: #0d9488; font-size: 14px;">${parseFloat(order.grand_total).toFixed(2)}</span>
                <i class="fas fa-trash-alt btn-delete-held" style="color: #cbd5e1; cursor: pointer; padding: 8px; transition: all 0.2s;" onmouseover="this.style.color=\'#f43f5e\'" onmouseout="this.style.color=\'#cbd5e1\'" onclick="event.stopPropagation(); deleteHeldOrder(\'${order.invoice_id}\')"></i>
            </div>
        `;

        listBody.appendChild(item);
    });
}

function showHeldOrderSummary(invoiceId) {
    const order = allHeldOrders.find(o => o.invoice_id === invoiceId);
    if (!order) return;

    document.querySelectorAll('.held-order-item').forEach(el => {
        el.style.background = 'transparent';
        el.style.borderLeft = 'none';
        el.style.boxShadow = 'none';
    });
    const selectedItem = document.querySelector(`.held-order-item[data-id="${invoiceId}"]`);
    if (selectedItem) {
        selectedItem.style.background = '#f0fdfa';
        selectedItem.style.borderLeft = '4px solid #0d9488';
        selectedItem.style.boxShadow = '0 4px 6px -1px rgba(0,0,0,0.05)';
    }

    // Update Detail View
    document.getElementById('held-order-title-ref').textContent = order.invoice_note || order.invoice_id;
    document.getElementById('held-detail-customer').textContent = order.customer_name || 'Walking Customer';
    document.getElementById('held-detail-date').textContent = new Date(order.created_at).toLocaleString();

    const itemsBody = document.getElementById('held-detail-items');
    itemsBody.innerHTML = '';

    order.items.forEach((item, index) => {
        itemsBody.innerHTML += `
            <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s;">
                <td style="padding: 12px 15px; text-align: center; color: #94a3b8; font-weight: 700;">${index + 1}</td>
                <td style="padding: 12px 15px; color: #334155; font-weight: 600;">${item.item_name}</td>
                <td style="padding: 12px 15px; text-align: center; color: #64748b;">${parseFloat(item.item_price).toFixed(2)}</td>
                <td style="padding: 12px 15px; text-align: center; color: #64748b;"><span style="background: #f1f5f9; padding: 2px 8px; border-radius: 4px; font-weight: 700;">${parseFloat(item.item_quantity).toFixed(2)}</span></td>
                <td style="padding: 12px 15px; text-align: right; color: #0d9488; font-weight: 800;">${parseFloat(item.item_total).toFixed(2)}</td>
            </tr>
        `;
    });

    document.getElementById('held-detail-subtotal').textContent = parseFloat(order.subtotal).toFixed(2);
    document.getElementById('held-detail-discount').textContent = parseFloat(order.discount_amount).toFixed(2);
    document.getElementById('held-detail-tax').textContent = parseFloat(order.item_tax).toFixed(2);
    document.getElementById('held-detail-shipping').textContent = parseFloat(order.shipping_amount).toFixed(2);
    document.getElementById('held-detail-other').textContent = parseFloat(order.others_charge).toFixed(2);
    document.getElementById('held-detail-payable').textContent = parseFloat(order.grand_total).toFixed(2);

    document.getElementById('btn-resume-held').onclick = () => resumeHeldOrder(invoiceId);

    document.getElementById('held-order-placeholder').style.display = 'none';
    document.getElementById('held-order-content').style.display = 'flex';
}

function resumeHeldOrder(invoiceId) {
    const formData = new FormData();
    formData.append('action', 'resume_held_order');
    formData.append('invoice_id', invoiceId);

    fetch('/pos/pos/save_sale.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const order = data.order;
                const items = data.items;

                // Check for stock warnings and show toast notifications
                if (data.stock_warnings && data.stock_warnings.length > 0) {
                    data.stock_warnings.forEach(warning => {
                        showToast(
                            `Stock Low - ${warning.product_name}: Only ${warning.available_stock} units available (held: ${warning.held_qty})`,
                            'warning'
                        );
                    });
                }

                // Load cart
                cart = items;
                renderCart();

                // Set Customer
                selectCustomer(
                    order.customer_id || 0,
                    order.customer_name,
                    order.customer_phone,
                    order.customer_balance,
                    0 // giftcardBalance not stored in hold, default 0
                );

                // Set Totals Inputs
                document.getElementById('discount-input').value = order.discount_amount;
                document.getElementById('tax-input').value = order.tax_percent;
                document.getElementById('shipping-input').value = order.shipping_charge;
                document.getElementById('other-input').value = order.other_charge;

                updateTotals();
                closeModal('heldOrdersModal');
                showToast('Order resumed successfully', 'success');
            } else {
                showToast(data.message || 'Error resuming order', 'error');
            }
        });
}

function deleteHeldOrder(invoiceId) {
    closeModal('heldOrdersModal');
    Swal.fire({
        title: 'Are you sure?',
        text: "This will permanently delete the held order!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#f43f5e',
        cancelButtonColor: '#94a3b8',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('action', 'delete_held_order');
            formData.append('invoice_id', invoiceId);

            fetch('/pos/pos/save_sale.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        allHeldOrders = allHeldOrders.filter(o => o.invoice_id !== invoiceId);
                        renderHeldOrdersList(allHeldOrders);
                        document.getElementById('held-order-content').style.display = 'none';
                        document.getElementById('held-order-placeholder').style.display = 'flex';
                        showToast('Deleted successfully', 'success');
                    } else {
                        showToast(data.message || 'Error deleting', 'error');
                    }
                });
        }
    });
}


function filterHeldOrders() {
    const q = document.getElementById('held-order-search').value.toLowerCase();
    const filtered = allHeldOrders.filter(o =>
        (o.invoice_note && o.invoice_note.toLowerCase().includes(q)) ||
        (o.customer_name && o.customer_name.toLowerCase().includes(q)) ||
        o.invoice_id.toLowerCase().includes(q)
    );
    renderHeldOrdersList(filtered);
}

// Prepare and open Hold Order Modal
function prepareHoldModal() {
    if (cart.length === 0) {
        showToast('Cart is empty!', 'error');
        return;
    }

    // Prevent opening hold modal if a stock error just occurred (within last 1.5 seconds)
    const timeSinceLastError = Date.now() - window.lastStockErrorTime;
    if (timeSinceLastError < 1500) {
        showToast('Please adjust quantity before holding order', 'warning');
        return;
    }

    // Check for stock availability before opening hold modal
    for (const item of cart) {
        if (item.qty > item.stock) {
            showToast(`Stock Low - ${item.name}: Only ${item.stock} available. Please adjust quantity.`, 'error');
            window.lastStockErrorTime = Date.now(); // Mark error time
            return; // Stop here, do not open modal
        }
    }

    const itemsBody = document.getElementById('hold-order-items');
    itemsBody.innerHTML = '';

    let subtotal = 0;
    cart.forEach((item, index) => {
        const itemTotal = item.price * item.qty;
        subtotal += itemTotal;
        itemsBody.innerHTML += `
            <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s ease;">
                <td style="padding: 15px 20px; text-align: center; color: #64748b; font-weight: 600;">${index + 1}</td>
                <td style="padding: 15px 10px;">
                    <div style="font-weight: 700; color: #334155; font-size: 14px;">${item.name}</div>
                    <div style="font-size: 12px; color: #94a3b8; margin-top: 2px;">
                        <span style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px;">Qty: ${item.qty}</span> × 
                        <span style="color: #64748b;">${parseFloat(item.price).toFixed(2)}</span>
                    </div>
                </td>
                <td style="padding: 15px 20px; text-align: right; font-weight: 700; color: #1e293b; font-size: 14px;">${itemTotal.toFixed(2)}</td>
            </tr>
        `;
    });

    const discount = parseFloat(document.getElementById('discount-input').value) || 0;
    const taxPercent = parseFloat(document.getElementById('tax-input').value) || 0;
    const shipping = parseFloat(document.getElementById('shipping-input').value) || 0;
    const other = parseFloat(document.getElementById('other-input').value) || 0;

    const taxAmount = (subtotal - discount) * (taxPercent / 100);
    const grandTotal = subtotal - discount + taxAmount + shipping + other;

    document.getElementById('hold-subtotal').textContent = subtotal.toFixed(2);
    document.getElementById('hold-discount').textContent = discount.toFixed(2);
    document.getElementById('hold-tax').textContent = taxAmount.toFixed(2);
    document.getElementById('hold-shipping').textContent = shipping.toFixed(2);
    document.getElementById('hold-other').textContent = other.toFixed(2);
    document.getElementById('hold-payable').textContent = grandTotal.toFixed(2);
    document.getElementById('hold-item-count').textContent = cart.length;

    document.getElementById('hold-reference').value = '';
    openModal('holdModal');
    setTimeout(() => document.getElementById('hold-reference').focus(), 100);
}

// Hold order flag to prevent double submission
let isHoldingOrder = false;
function holdOrder() {
    if (isHoldingOrder) return;

    const reference = document.getElementById('hold-reference').value;
    if (!reference.trim()) {
        showToast('Please enter an order reference', 'error');
        return;
    }

    isHoldingOrder = true;

    // Calculate totals to send
    let subtotal = 0;
    cart.forEach(item => subtotal += (item.price * item.qty));

    const discount = parseFloat(document.getElementById('discount-input').value) || 0;
    const taxPercent = parseFloat(document.getElementById('tax-input').value) || 0;
    const shipping = parseFloat(document.getElementById('shipping-input').value) || 0;
    const other = parseFloat(document.getElementById('other-input').value) || 0;

    const taxAmount = (subtotal - discount) * (taxPercent / 100);
    const grandTotal = subtotal - discount + taxAmount + shipping + other;

    const formData = new FormData();
    formData.append('action', 'hold_order');
    formData.append('cart', JSON.stringify(cart));
    formData.append('customer_id', document.getElementById('selected_customer_id').value || 0);
    const storeId = document.getElementById('store_select').value;
    if (storeId === 'all') {
        showToast('Please select a specific store before holding an order', 'error');
        return;
    }

    formData.append('store_id', storeId);
    formData.append('note', reference);

    // Extra fields
    formData.append('discount', discount);
    formData.append('tax_percent', taxPercent);
    formData.append('tax_amount', taxAmount);
    formData.append('shipping', shipping);
    formData.append('other_charge', other);
    formData.append('grand_total', grandTotal);

    fetch('/pos/pos/save_sale.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Order held successfully!', 'success');
                cart = [];
                renderCart();
                // Reset totals in UI as well
                updateTotals();
                closeModal('holdModal');
                document.getElementById('hold-reference').value = '';

                // Add new order to local list and re-render if possible
                if (data.order && typeof allHeldOrders !== 'undefined') {
                    allHeldOrders.push(data.order);
                    // Sort by newest first (optional, but good UX)
                    allHeldOrders.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));

                    const listBody = document.getElementById('held-orders-list');
                    if (listBody && typeof renderHeldOrdersList === 'function') {
                        renderHeldOrdersList(allHeldOrders);
                    }
                }

                showToast('Order held successfully!', 'success');
            } else {
                showToast(data.message || 'Error holding order', 'error');
            }
        })
        .catch(error => {
            showToast('Error holding order', 'error');
            console.error(error);
        })
        .finally(() => {
            isHoldingOrder = false;
        });
}

// Customer selection
// Customer selection
function selectCustomer(id, name, phone, balance = 0, giftcardBalance = 0, openingBalance = 0, hasInstallment = 0) {
    customerId = id;
    document.getElementById('selected_customer_id').value = id;
    document.getElementById('selected-customer-name').textContent = name;
    document.getElementById('selected-customer-phone').textContent = phone;

    // Store balances
    const currentDue = parseFloat(balance);
    const giftcardBal = parseFloat(giftcardBalance);
    const openingBal = parseFloat(openingBalance);

    document.getElementById('customer_due_balance').value = currentDue;
    document.getElementById('customer_total_balance').value = currentDue;
    document.getElementById('customer_credit').value = 0; // Legacy field
    document.getElementById('customer_giftcard_balance').value = giftcardBal;
    document.getElementById('customer_opening_balance').value = openingBal;

    // Store has_installment flag
    if (!document.getElementById('customer_has_installment')) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.id = 'customer_has_installment';
        document.body.appendChild(input);
    }
    document.getElementById('customer_has_installment').value = hasInstallment;

    // Update due display in POS UI
    const dueDisplay = document.getElementById('selected-customer-due-display');
    const dueAmount = document.getElementById('selected-customer-due-amount');
    if (currentDue > 0) {
        dueDisplay.style.display = 'block';
        dueAmount.textContent = currentDue.toFixed(2);
    } else {
        dueDisplay.style.display = 'none';
    }

    closeModal('customerSelectModal');
    showToast('Customer selected: ' + name);
}

// Open Walking Customer Edit Modal
function openWalkingCustomerModal() {
    const currentPhone = document.getElementById('selected-customer-phone').textContent;
    document.getElementById('walking-mobile-input').value = currentPhone;
    document.getElementById('walking-display-phone').textContent = currentPhone;
    openModal('walkingCustomerModal');
}

// Save Walking Customer
function saveWalkingCustomer() {
    const mobile = document.getElementById('walking-mobile-input').value.trim();
    if (mobile) {
        document.getElementById('selected-customer-name').textContent = 'Walking Customer';
        document.getElementById('selected-customer-phone').textContent = mobile;
        document.getElementById('selected_customer_id').value = '';
        closeModal('walkingCustomerModal');
        showToast('Walking Customer updated');
    } else {
        showToast('Please enter a mobile number', 'error');
    }
}

// Product search with barcode scanning
let searchTimeout = null;
document.getElementById('product_search').addEventListener('input', function () {
    currentSearch = this.value.trim();

    // Debounce search to avoid too many requests
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        loadProducts(1); // Reload products with new search
    }, 300); // Wait 300ms after user stops typing
});

// Barcode scanning - Enter key to add product to cart
document.getElementById('product_search').addEventListener('keypress', function (e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        const barcode = this.value.trim();

        if (barcode) {
            // Search for product by barcode
            handleBarcodeSearch(barcode);
        }
    }
});

// Handle barcode search and auto-add to cart
// Handle barcode search and auto-add to cart
function handleBarcodeSearch(barcode) {
    const formData = new FormData();
    formData.append('search', barcode);
    formData.append('store_id', document.getElementById('store_select').value === 'all' ? 0 : document.getElementById('store_select').value);
    formData.append('category_id', 0);
    formData.append('page', 1);

    fetch('/pos/pos/get_products.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.html && data.html.trim() !== '') {
                // Parse the HTML to extract product data
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = data.html;
                const productCards = tempDiv.querySelectorAll('.product-card');

                if (productCards.length > 0) {
                    let targetProduct = null;
                    const searchLower = barcode.toLowerCase().trim();

                    // 1. Try to find an Exact Name Match
                    for (let i = 0; i < productCards.length; i++) {
                        const name = productCards[i].dataset.name;
                        if (name && name.toLowerCase().trim() === searchLower) {
                            targetProduct = productCards[i];
                            break;
                        }
                    }

                    // 2. Fallback: If no exact match, take the first one
                    if (!targetProduct) {
                        targetProduct = productCards[0];
                    }

                    if (targetProduct) {
                        const productId = targetProduct.dataset.id;
                        const productName = targetProduct.dataset.name;
                        const productPrice = targetProduct.dataset.price;
                        const productStock = parseFloat(targetProduct.dataset.stock);

                        // Stock Validation
                        if (productStock <= 0) {
                            showToast('Stock not available', 'error');
                            // Clear search field even on error to allow new search
                            document.getElementById('product_search').value = '';
                            currentSearch = '';
                            return; // Stop here, do not add to cart
                        }

                        // Add to cart
                        addToCart(productId, productName, productPrice, productStock);

                        // Clear search field
                        document.getElementById('product_search').value = '';
                        currentSearch = '';

                        showToast('Product added to cart', 'success');
                    }
                } else {
                    showToast('Product not found', 'error');
                }
            } else {
                showToast('Product not found', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error searching product', 'error');
        });
}


// Category filter
document.querySelectorAll('.category-filter button').forEach(btn => {
    btn.addEventListener('click', function () {
        document.querySelectorAll('.category-filter button').forEach(b => b.classList.remove('active'));
        this.classList.add('active');

        currentCategory = this.dataset.category;
        loadProducts(1); // Reload products with new category filter
    });
});

// Quick add customer form
document.getElementById('quickCustomerForm').addEventListener('submit', function (e) {
    e.preventDefault();

    let isValid = true;
    const errors = [];

    // Helper function to show/hide errors
    const showError = (id, show) => {
        const el = document.getElementById(id);
        const errEl = document.getElementById('error-' + id);
        if (el) {
            if (show) {
                el.classList.add('error-border');
                if (errEl) errEl.style.display = 'block';
                errors.push(el.previousElementSibling.textContent + ' is required.'); // Assuming label is previous sibling
                isValid = false;
            } else {
                el.classList.remove('error-border');
                if (errEl) errEl.style.display = 'none';
            }
        }
    };

    // Clear all previous errors
    document.querySelectorAll('.error-border').forEach(el => el.classList.remove('error-border'));
    document.querySelectorAll('.error-msg-text').forEach(el => el.style.display = 'none');

    // Validate required fields
    showError('modal_code_name', document.getElementById('modal_code_name').value.trim() === "");
    showError('modal_name', document.getElementById('modal_name').value.trim() === "");
    showError('modal_customer_group', document.getElementById('modal_customer_group').value === "");
    showError('modal_membership_level', document.getElementById('modal_membership_level').value === "");
    showError('modal_mobile', document.getElementById('modal_mobile').value.trim() === "");
    showError('modal_opening_balance', document.getElementById('modal_opening_balance').value === "");
    showError('modal_credit_limit', document.getElementById('modal_credit_limit').value === "");
    showError('modal_city', document.getElementById('modal_city').value.trim() === "");
    showError('modal_state', document.getElementById('modal_state').value.trim() === "");
    showError('modal_country', document.getElementById('modal_country').value === "");
    showError('modal_sort_order', document.getElementById('modal_sort_order').value === "");

    // Email validation (optional but must be valid if entered)
    const email = document.getElementById('modal_email');
    if (email.value.trim() !== "") {
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        showError('modal_email', !emailPattern.test(email.value));
    }

    if (!isValid) {
        Swal.fire({
            icon: 'error',
            title: 'Validation Error',
            html: errors.join('<br>'),
            confirmButtonColor: '#0d9488'
        });
        return;
    }

    // If validation passes, submit the form
    const formData = new FormData(this);
    formData.append('save_customer_btn', 'true');

    // Automatically add the currently selected store from POS
    const currentStoreId = document.getElementById('store_select').value;
    formData.append('store_ids[]', currentStoreId);

    // Show loading state
    const btn = this.querySelector('button[type="submit"]');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    btn.disabled = true;

    fetch('/pos/customers/save_customer.php', {
        method: 'POST',
        body: formData
    })
        .then(response => {
            // Check if response is redirect or JSON
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                return response.json();
            }
            // If it's a redirect, consider it success
            return { success: true, message: 'Customer added successfully' };
        })
        .then(data => {
            if (data.success || data.msg_type === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: data.message || 'Customer added successfully',
                    confirmButtonColor: '#0d9488',
                    timer: 2000
                });
                closeModal('addCustomerModal');
                this.reset();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'Error adding customer',
                    confirmButtonColor: '#0d9488'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Server error occurred',
                confirmButtonColor: '#0d9488'
            });
        })
        .finally(() => {
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
});



// ==========================================
// GIFT CARD MODAL HANDLERS
// ==========================================

// Generate random card number (16 digits)
function generateModalCardNumber() {
    let cardNo = '';
    for (let i = 0; i < 16; i++) {
        cardNo += Math.floor(Math.random() * 10);
    }
    document.getElementById('modal_card_no').value = cardNo;
    document.getElementById('modal-preview-card-no').textContent = cardNo;
    generateModalBarcode(cardNo);
}

// Generate barcode for modal
function generateModalBarcode(cardNo) {
    if (cardNo && cardNo.length > 0 && typeof JsBarcode !== 'undefined') {
        const barcodeContainer = document.getElementById('modal-barcode-container');
        if (barcodeContainer) {
            JsBarcode("#modal-barcode-container", cardNo, {
                format: "CODE128",
                width: 2,
                height: 60,
                displayValue: false,
                margin: 0
            });
            barcodeContainer.style.display = 'block';
        }
    }
}

// Initialize Gift Card Modal
function initGiftCardModal() {
    // Card number input - update preview and barcode
    const cardNoInput = document.getElementById('modal_card_no');
    if (cardNoInput) {
        cardNoInput.addEventListener('input', function () {
            document.getElementById('modal-preview-card-no').textContent = this.value || '••••••••••••••••';
            if (this.value) {
                generateModalBarcode(this.value);
            }
        });
    }

    // Value input - auto-set balance and update preview
    const valueInput = document.getElementById('modal_value');
    if (valueInput) {
        valueInput.addEventListener('input', function () {
            const value = parseFloat(this.value) || 0;
            document.getElementById('modal_balance').value = value.toFixed(2);
            document.getElementById('modal-preview-value').textContent = 'USD ' + value.toFixed(2);
        });
    }

    // Expiry date - update preview
    const expiryInput = document.getElementById('modal_expiry_date');
    if (expiryInput) {
        expiryInput.addEventListener('change', function () {
            document.getElementById('modal-preview-expiry').textContent = this.value || 'MM/YY';
        });
    }

    // Image upload - update preview
    const imageInput = document.getElementById('modal_image');
    if (imageInput) {
        imageInput.addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (event) {
                    document.getElementById('modal-preview-logo').innerHTML = '\u003cimg src="' + event.target.result + '" alt="Logo" style="width: 100%; height: 100%; object-fit: contain; border-radius: 8px;"\u003e';
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // Customer search dropdown
    const customerSearch = document.getElementById('modal_customer_search');
    const customerDropdown = document.getElementById('modal_customer_dropdown');
    const customerOptions = document.querySelectorAll('.modal-customer-option');
    const customerIdInput = document.getElementById('modal_customer_id');

    if (customerSearch && customerDropdown) {
        // Show dropdown on focus
        customerSearch.addEventListener('focus', () => {
            customerDropdown.classList.remove('hidden');
        });

        // Filter customers as user types
        customerSearch.addEventListener('input', function () {
            const searchTerm = this.value.toLowerCase();
            customerOptions.forEach(option => {
                const customerName = option.getAttribute('data-name').toLowerCase();
                option.style.display = customerName.includes(searchTerm) || searchTerm === '' ? 'block' : 'none';
            });
        });

        // Select customer
        customerOptions.forEach(option => {
            option.addEventListener('click', function () {
                const customerId = this.getAttribute('data-id');
                const customerName = this.getAttribute('data-name');
                customerSearch.value = customerName;
                customerIdInput.value = customerId;
                customerDropdown.classList.add('hidden');
            });
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function (e) {
            if (!e.target.closest('#modal_customer_search') && !e.target.closest('#modal_customer_dropdown')) {
                customerDropdown.classList.add('hidden');
            }
        });
    }

    // Initialize status toggle for modal (Safety Check)
    if (document.getElementById('modal-giftcard-status-card')) {
        initStatusToggle('modal-giftcard-status-card', 'modal-giftcard-status-toggle', 'modal-giftcard-status-input', 'modal-giftcard-status-label');
    }
}

// Gift Card Form Submission
document.getElementById('quickGiftcardForm')?.addEventListener('submit', function (e) {
    e.preventDefault();

    let isValid = true;
    const errors = [];

    // Clear previous errors
    document.querySelectorAll('.error-msg-text').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.error-border').forEach(el => el.classList.remove('error-border'));

    // Validate Card Number
    const cardNo = document.getElementById('modal_card_no').value.trim();
    if (!cardNo) {
        document.getElementById('error-modal_card_no').style.display = 'block';
        document.getElementById('modal_card_no').classList.add('error-border');
        errors.push('Card Number is required');
        isValid = false;
    }

    // Validate Card Value
    const value = parseFloat(document.getElementById('modal_value').value);
    if (!value || value <= 0) {
        document.getElementById('error-modal_value').style.display = 'block';
        document.getElementById('modal_value').classList.add('error-border');
        errors.push('Card Value must be greater than 0');
        isValid = false;
    }

    // Validate Expiry Date
    const expiryDate = document.getElementById('modal_expiry_date').value;
    if (!expiryDate) {
        document.getElementById('error-modal_expiry_date').style.display = 'block';
        document.getElementById('modal_expiry_date').classList.add('error-border');
        errors.push('Expiry Date is required');
        isValid = false;
    }

    if (!isValid) {
        Swal.fire({
            icon: 'error',
            title: 'Validation Error',
            html: errors.join('\u003cbr\u003e'),
            confirmButtonColor: '#0d9488'
        });
        return;
    }

    // If validation passes, submit the form
    const formData = new FormData(this);
    formData.append('save_giftcard_btn', 'true');

    // Show loading state
    const btn = this.querySelector('button[type="submit"]');
    const originalText = btn.innerHTML;
    btn.innerHTML = '\u003ci class="fas fa-spinner fa-spin"\u003e\u003c/i\u003e Saving...';
    btn.disabled = true;

    fetch('/pos/giftcard/save', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
        .then(response => response.json ? response.json() : response.text())
        .then(data => {
            // Handle both JSON and redirect responses
            if (typeof data === 'string') {
                // If it's a redirect or HTML response, consider it success
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Gift card created successfully',
                    confirmButtonColor: '#0d9488',
                    timer: 2000
                });
                closeModal('giftcardModal');
                this.reset();
                // Reset preview
                document.getElementById('modal-preview-card-no').textContent = '••••••••••••••••';
                document.getElementById('modal-preview-value').textContent = 'USD 0.00';
                document.getElementById('modal-preview-expiry').textContent = 'MM/YY';
                document.getElementById('modal-preview-logo').innerHTML = '\u003ci class="fas fa-image" style="opacity: 0.5;"\u003e\u003c/i\u003e';
                document.getElementById('modal-barcode-container').style.display = 'none';
            } else if (data.success || data.msg_type === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: data.message || 'Gift card created successfully',
                    confirmButtonColor: '#0d9488',
                    timer: 2000
                });
                closeModal('giftcardModal');
                this.reset();
                // Reset preview
                document.getElementById('modal-preview-card-no').textContent = '••••••••••••••••';
                document.getElementById('modal-preview-value').textContent = 'USD 0.00';
                document.getElementById('modal-preview-expiry').textContent = 'MM/YY';
                document.getElementById('modal-preview-logo').innerHTML = '\u003ci class="fas fa-image" style="opacity: 0.5;"\u003e\u003c/i\u003e';
                document.getElementById('modal-barcode-container').style.display = 'none';
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'Error creating gift card',
                    confirmButtonColor: '#0d9488'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Server error occurred',
                confirmButtonColor: '#0d9488'
            });
        })
        .finally(() => {
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
});

// Initialize gift card modal on page load
document.addEventListener('DOMContentLoaded', function () {
    initGiftCardModal();

    // Initialize customer status toggle
    initStatusToggle('modal-customer-status-card', 'modal-customer-status-toggle', 'modal_customer_status', 'modal-customer-status-label');
});

// Keyboard shortcuts and Enter key navigation
document.addEventListener('keydown', function (e) {
    // F2 - Focus search
    if (e.key === 'F2') {
        e.preventDefault();
        document.getElementById('product_search').focus();
    }

    // F4 - Open payment
    if (e.key === 'F4') {
        e.preventDefault();
        if (cart.length > 0) {
            prepareAndOpenPaymentModal();
        }
    }

    // Enter key - Navigate through workflow
    // DISABLED: This duplicate handler was bypassing stock validation
    // The main Enter key handler at the top of the file (line 24) handles this properly with stock validation
    /* 
    if (e.key === 'Enter') {
        const activeModal = getActiveModal();

        // If no modal is open and we're on POS page
        if (!activeModal) {
            // Check if search field is focused
            const searchField = document.getElementById('product_search');
            if (document.activeElement === searchField) {
                // Let the barcode search handler handle this
                return;
            }

            // If cart has items, open payment modal
            if (cart.length > 0) {
                e.preventDefault();
                prepareAndOpenPaymentModal();
            }
        }
        // If payment modal is open
        else if (activeModal === 'paymentModal') {
            // Check if we're not in an input field
            if (!['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName)) {
                e.preventDefault();
                // Trigger payment processing
                const completeBtn = document.querySelector('#paymentModal .complete-btn');
                if (completeBtn && !completeBtn.disabled) {
                    completeBtn.click();
                }
            }
        }
        // If invoice modal is open
        else if (activeModal === 'invoiceModal') {
            // Check if we're not in an input field
            if (!['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName)) {
                e.preventDefault();
                // Trigger print
                triggerAutoPrint();
            }
        }
    }
    */

    // Escape - Close modals
    if (e.key === 'Escape') {
        document.querySelectorAll('.pos-modal.active').forEach(modal => {
            modal.classList.remove('active');
        });
    }
});

// Get currently active modal
function getActiveModal() {
    const activeModal = document.querySelector('.pos-modal.active');
    return activeModal ? activeModal.id : null;
}

// Trigger auto-print and handle post-print cleanup
function triggerAutoPrint() {
    // Trigger browser print
    window.print();

    // Listen for afterprint event
    const handleAfterPrint = function () {
        // Close invoice modal
        closeModal('invoiceModal');

        // Clear cart and reset POS
        clearCartAndReset();

        // Remove the listener
        window.removeEventListener('afterprint', handleAfterPrint);
    };

    window.addEventListener('afterprint', handleAfterPrint);
}

// Clear cart and reset POS without reload
function clearCartAndReset() {
    // Clear cart
    cart = [];
    renderCart();

    // Reset all input fields to empty (parseFloat will handle as 0)
    document.getElementById('discount-input').value = '';
    document.getElementById('tax-input').value = '';
    document.getElementById('shipping-input').value = '';
    document.getElementById('other-input').value = '';

    // Reset customer to walking customer
    selectCustomer(0, 'Walking Customer', '0170000000000', 0, 0, 0);

    // Update totals
    updateTotals();

    // Reload products to show updated stock
    loadProducts(1);

    // Show success message
    showToast('Sale completed successfully!', 'success');

    // Focus back to search field
    setTimeout(() => {
        document.getElementById('product_search').focus();
    }, 500);
}

// Reset Cart and Form
function resetCart() {
    cart = [];
    renderCart();

    // Reset payment inputs
    document.getElementById('discount-input').value = '';
    document.getElementById('tax-input').value = '';
    document.getElementById('shipping-input').value = '';
    document.getElementById('other-input').value = '';
    document.getElementById('amount-received').value = '';
    document.getElementById('change-amount').textContent = '৳0.00';

    // Reset selection
    selectedPaymentMethod = null;
    document.querySelectorAll('.payment-method').forEach(pm => pm.classList.remove('selected'));
    document.querySelectorAll('.sidebar-payment-method').forEach(pm => {
        pm.style.background = 'white';
        pm.style.borderColor = '#e2e8f0';
    });

    // Reset customer to default (Walking Customer)
    selectCustomer(0, 'Walking Customer', '0170000000000', 0, 0);

    updateTotals();
}

// Quick Select Customer Search Logic
// Quick Select Customer Search Logic
const searchInput = document.getElementById('customer-search-input');
if (searchInput) {
    searchInput.setAttribute('autocomplete', 'off'); // Unique design req: no auto suggestion

    function filterCustomers() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        const customerList = document.getElementById('customer-list');
        if (!customerList) return;

        const options = customerList.querySelectorAll('.customer-option');
        let visibleCount = 0;
        let hasResults = false;

        // Remove existing no-results msg
        const existingMsg = document.getElementById('customer-no-results');
        if (existingMsg) existingMsg.remove();

        options.forEach((option, index) => {
            const text = option.textContent.toLowerCase();

            // Special case: Walking Customer (always first) - usually we want to keep it or treat as normal?
            // Assuming normal list processing

            if (searchTerm === '') {
                // Show only first 5
                if (visibleCount < 5) {
                    option.style.display = 'flex';
                    visibleCount++;
                    hasResults = true;
                } else {
                    option.style.display = 'none';
                }
            } else {
                if (text.includes(searchTerm)) {
                    option.style.display = 'flex';
                    hasResults = true;
                } else {
                    option.style.display = 'none';
                }
            }
        });

        // Handle no results
        if (!hasResults && searchTerm !== '') {
            const noResultsEl = document.createElement('div');
            noResultsEl.id = 'customer-no-results';
            noResultsEl.style.padding = '20px';
            noResultsEl.style.textAlign = 'center';
            noResultsEl.style.color = '#94a3b8';
            noResultsEl.innerHTML = '<i class="fas fa-user-slash" style="font-size: 24px; display: block; margin-bottom: 8px;"></i> No customers found';
            customerList.appendChild(noResultsEl);
        }
    }

    searchInput.addEventListener('input', filterCustomers);
    searchInput.addEventListener('focus', filterCustomers);

    // Trigger once to set initial state
    setTimeout(filterCustomers, 500);
}


// ===========================================
// WELCOME MODAL LOGIC (New Feature)
// ===========================================

// ===========================================
// WELCOME MODAL LOGIC (New Feature)
// ===========================================

document.addEventListener('DOMContentLoaded', function () {
    console.log('POS JS Loaded - DOMContentLoaded');

    // Show Welcome Modal on Load
    // Reset welcomeShown for debugging if needed, but keeping logic as is for now
    // sessionStorage.removeItem('welcomeShown'); // Uncomment to force show every time for testing

    // FORCE SHOW WELCOME MODAL ALWAYS (Removed session check as requested)
    const welcomeModal = document.getElementById('welcomeModal');
    if (welcomeModal) {
        console.log('Opening Welcome Modal (Force Show)...');
        welcomeModal.classList.add('active');
        welcomeModal.style.display = 'flex';
    } else {
        console.error('Welcome Modal element not found in DOM');
    }

    // Bind Stock Alert to Main Store Select
    const mainStoreSelect = document.getElementById('store_select');
    if (mainStoreSelect) {
        mainStoreSelect.addEventListener('change', function () {
            checkStoreStockAlert(this.value);
        });

        // Check initial if not "all"
        if (mainStoreSelect.value && mainStoreSelect.value !== 'all') {
            checkStoreStockAlert(mainStoreSelect.value);
        }
    }
    // Delegate Click Handler for Product Cards (Robust Fix)
    document.addEventListener('click', function (e) {
        const card = e.target.closest('.product-card');
        if (card) {
            const productId = card.getAttribute('data-id');
            if (productId) {
                console.log('Delegated Click on Product Card:', productId);
                if (typeof window.openProductDetails === 'function') {
                    window.openProductDetails(productId);
                } else {
                    console.error('window.openProductDetails is not defined!');
                }
            }
        }
    });

});

window.closeWelcomeModal = function () {
    const modal = document.getElementById('welcomeModal');
    if (modal) {
        modal.classList.remove('active');
        modal.style.display = 'none';
        sessionStorage.setItem('welcomeShown', 'true');

        // Check stock of currently selected store after dismiss
        const mainStoreSelect = document.getElementById('store_select');
        if (mainStoreSelect && mainStoreSelect.value && mainStoreSelect.value !== 'all') {
            checkStoreStockAlert(mainStoreSelect.value);
        }
    }
}

window.confirmWelcomeStats = function () {
    window.closeWelcomeModal();
}

window.checkStoreStockAlert = function (storeId) {
    if (storeId === 'all') return;

    fetch('get_stock_alert_count.php?store_id=' + storeId)
        .then(res => res.json())
        .then(data => {
            if (data.count > 0) {
                showToast(`Alert: This store has ${data.count} products with low stock!`, 'warning');
            }
        })
        .catch(err => console.error(err));
}

// Close Welcome Modal on Enter or OK (covered by confirmWelcomeStats)
document.addEventListener('keydown', function (e) {
    const welcomeModal = document.getElementById('welcomeModal');
    if (welcomeModal && welcomeModal.classList.contains('active')) {
        if (e.key === 'Enter' || e.key === 'Escape') {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            confirmWelcomeStats(); // or closeWelcomeModal
        }
        // If welcome modal is active, do not process other keys
        return;
    }

    // Product Details Modal Enter Key
    const pdModal = document.getElementById('productDetailsModal');
    if (pdModal && pdModal.classList.contains('active')) {
        if (e.key === 'Enter') {
            e.preventDefault();
            addToCartFromModal();
        }
    }
});


// ===========================================
// PRODUCT DETAILS MODAL LOGIC (New Feature)
// ===========================================

// Product Details Modal Logic
let currentProductDetailsId = null;
let currentProductName = ''; // Store product name for lightbox
let currentGalleryImages = [];
let currentGalleryIndex = 0;

// Pagination State
let galleryThumbnailPage = 0;
const THUMBNAILS_PER_PAGE = 4;

// Attach to window explicitly
window.changeGalleryImage = function (direction) {
    if (currentGalleryImages.length <= 1) return;

    currentGalleryIndex += direction;

    // Circular navigation
    if (currentGalleryIndex >= currentGalleryImages.length) {
        currentGalleryIndex = 0;
    } else if (currentGalleryIndex < 0) {
        currentGalleryIndex = currentGalleryImages.length - 1;
    }

    const imgEl = document.getElementById('pd-image');
    if (imgEl && currentGalleryImages[currentGalleryIndex]) {
        // Simple Fade Effect
        imgEl.style.opacity = '0.5';
        imgEl.src = currentGalleryImages[currentGalleryIndex];
        setTimeout(() => imgEl.style.opacity = '1', 150);

        // Update active thumbnail border
        renderGalleryThumbnails();
    }
};

// Render Pagination Thumbnails
function renderGalleryThumbnails() {
    const galleryContainer = document.getElementById('pd-gallery');
    if (!galleryContainer) return;

    galleryContainer.innerHTML = '';

    // Pagination Logic
    const startIdx = galleryThumbnailPage * THUMBNAILS_PER_PAGE;
    const endIdx = startIdx + THUMBNAILS_PER_PAGE;
    const pageImages = currentGalleryImages.slice(startIdx, endIdx);

    // Prev Page Button
    if (galleryThumbnailPage > 0) {
        const prevBtn = document.createElement('button');
        prevBtn.innerHTML = '<i class="fas fa-chevron-left"></i>';
        prevBtn.style.border = 'none';
        prevBtn.style.background = '#f1f5f9';
        prevBtn.style.borderRadius = '8px';
        prevBtn.style.width = '40px';
        prevBtn.style.height = '50px';
        prevBtn.style.cursor = 'pointer';
        prevBtn.style.marginRight = '5px';
        prevBtn.onclick = () => {
            galleryThumbnailPage--;
            renderGalleryThumbnails();
        };
        galleryContainer.appendChild(prevBtn);
    }

    pageImages.forEach((imgSrc, i) => {
        const realIdx = startIdx + i;
        const thumb = document.createElement('img');
        thumb.src = imgSrc;
        thumb.style.width = '50px';
        thumb.style.height = '50px';
        thumb.style.objectFit = 'cover';
        thumb.style.borderRadius = '8px';
        thumb.style.cursor = 'pointer';
        thumb.style.border = (realIdx === currentGalleryIndex) ? '2px solid #0d9488' : '2px solid transparent';
        thumb.style.transition = 'all 0.2s';

        thumb.onclick = () => {
            currentGalleryIndex = realIdx;
            const imgEl = document.getElementById('pd-image');
            if (imgEl) {
                imgEl.style.opacity = '0.5';
                imgEl.src = currentGalleryImages[currentGalleryIndex];
                setTimeout(() => imgEl.style.opacity = '1', 150);
            }
            renderGalleryThumbnails(); // Re-render to update border
        };

        galleryContainer.appendChild(thumb);
    });

    // Next Page Button
    if (endIdx < currentGalleryImages.length) {
        const nextBtn = document.createElement('button');
        nextBtn.innerHTML = '<i class="fas fa-chevron-right"></i>';
        nextBtn.style.border = 'none';
        nextBtn.style.background = '#f1f5f9';
        nextBtn.style.borderRadius = '8px';
        nextBtn.style.width = '40px';
        nextBtn.style.height = '50px';
        nextBtn.style.cursor = 'pointer';
        nextBtn.style.marginLeft = '5px';
        nextBtn.onclick = () => {
            galleryThumbnailPage++;
            renderGalleryThumbnails();
        };
        galleryContainer.appendChild(nextBtn);
    }
}

// Lightbox Zoom State
let lightboxZoomLevel = 1;

// Helper to update caption
function updateLightboxCaption() {
    const titleEl = document.getElementById('lightbox-title');
    const counterEl = document.getElementById('lightbox-counter');
    if (titleEl) titleEl.textContent = currentProductName || 'Product Image';
    if (counterEl) counterEl.textContent = `${currentGalleryIndex + 1} of ${currentGalleryImages.length}`;
}

// Lightbox Logic
window.openLightbox = function () {
    const modal = document.getElementById('lightboxModal');
    const img = document.getElementById('lightbox-image');
    if (modal && img) {
        lightboxZoomLevel = 1; // Reset zoom
        img.style.transform = `scale(${lightboxZoomLevel})`;
        img.src = currentGalleryImages[currentGalleryIndex];

        updateLightboxCaption(); // Update caption

        modal.style.display = 'flex';
        // Ensure high z-index
        modal.style.zIndex = '10000';

        // Add Wheel Zoom Listener
        img.onwheel = function (e) {
            e.preventDefault();
            const delta = Math.sign(e.deltaY) * -0.1;
            const newZoom = Math.min(Math.max(0.5, lightboxZoomLevel + delta), 4);
            lightboxZoomLevel = newZoom;
            img.style.transform = `scale(${lightboxZoomLevel})`;
        };
    }
}

window.changeLightboxImage = function (direction) {
    if (currentGalleryImages.length <= 1) return;

    currentGalleryIndex += direction;
    if (currentGalleryIndex >= currentGalleryImages.length) {
        currentGalleryIndex = 0;
    } else if (currentGalleryIndex < 0) {
        currentGalleryIndex = currentGalleryImages.length - 1;
    }

    const img = document.getElementById('lightbox-image');
    if (img) {
        lightboxZoomLevel = 1; // Reset zoom on change
        img.style.transform = `scale(${lightboxZoomLevel})`;
        img.src = currentGalleryImages[currentGalleryIndex];
        updateLightboxCaption(); // Update caption
    }
    // Sync main modal image too
    const mainImg = document.getElementById('pd-image');
    if (mainImg) mainImg.src = currentGalleryImages[currentGalleryIndex];
    renderGalleryThumbnails();
}


// Attach to window explicitly
window.openProductDetails = function (productId) {
    console.log('openProductDetails called with ID:', productId);

    currentProductDetailsId = productId;
    const modal = document.getElementById('productDetailsModal');
    if (!modal) {
        console.error('Product Details Modal not found in DOM');
        return;
    }

    modal.style.display = 'flex';
    modal.style.zIndex = '9999';
    setTimeout(() => modal.classList.add('active'), 10);

    const nameEl = document.getElementById('pd-name');
    if (nameEl) nameEl.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';

    const prevBtn = document.getElementById('pd-prev-btn');
    const nextBtn = document.getElementById('pd-next-btn');
    if (prevBtn) prevBtn.style.display = 'none';
    if (nextBtn) nextBtn.style.display = 'none';

    const currentStoreId = document.getElementById('store_select').value;
    fetch('get_product_details.php?id=' + productId + '&store_id=' + currentStoreId)
        .then(res => res.json())
        .then(resp => {
            if (resp.success) {
                const p = resp.product;
                currentProductName = p.name;

                const safeSet = (id, val) => {
                    const el = document.getElementById(id);
                    if (el) el.textContent = val;
                };

                safeSet('pd-name', p.name);
                safeSet('pd-category', p.category);
                safeSet('pd-price', (window.currencySymbol || '৳') + parseFloat(p.selling_price).toFixed(2));
                safeSet('pd-unit', p.unit);
                let desc = p.description || 'No description available.';
                if (window.currencyName) desc = desc.replace(/Taka/g, window.currencyName);
                if (window.currencySymbol) desc = desc.replace(/৳/g, window.currencySymbol);
                safeSet('pd-description', desc);

                safeSet('pd-brand', p.brand);
                safeSet('pd-code', p.code);
                safeSet('pd-barcode', p.barcode_symbology);
                const taxStr = (p.tax_name || 'No Tax') + ' (' + (parseFloat(p.tax_rate) || 0) + '%)';
                safeSet('pd-tax', taxStr);
                safeSet('pd-tax-method', p.tax_method);
                safeSet('pd-alert-qty', p.alert_quantity);
                safeSet('pd-limit', (p.effective_limit > 0) ? p.effective_limit : 'Unlimited');

                currentGalleryImages = (p.images && p.images.length > 0) ? p.images : ['/pos/assets/images/no-image.png'];
                currentGalleryIndex = 0;
                galleryThumbnailPage = 0;

                const imgEl = document.getElementById('pd-image');
                if (imgEl) {
                    imgEl.src = currentGalleryImages[0];
                    imgEl.style.opacity = '1';
                    imgEl.style.cursor = 'zoom-in';
                    imgEl.onclick = () => window.openLightbox();
                }

                if (currentGalleryImages.length > 1) {
                    if (prevBtn) prevBtn.style.display = 'flex';
                    if (nextBtn) nextBtn.style.display = 'flex';
                }

                renderGalleryThumbnails();

                // Stock List Breakdown
                const stockList = document.getElementById('pd-stock-list');
                const storeSelect = document.getElementById('store_select');
                const selectedStoreId = storeSelect ? storeSelect.value : null;

                if (stockList) {
                    stockList.innerHTML = '';

                    // Container for list items (so we can update just this part on search)
                    const listContainer = document.createElement('div');
                    listContainer.id = 'pd-stock-items-container';
                    stockList.appendChild(listContainer);

                    // Function to render items
                    const renderStockItems = (items) => {
                        listContainer.innerHTML = '';
                        if (items && items.length > 0) {
                            items.forEach(store => {
                                const sQty = parseFloat(store.stock);
                                const row = document.createElement('div');
                                const isCurrent = selectedStoreId && store.store_id == selectedStoreId;

                                row.className = 'pd-mobile-row';
                                row.style.display = 'flex';
                                row.style.justifyContent = 'space-between';
                                row.style.padding = '10px 12px';
                                row.style.fontSize = '12px';
                                row.style.borderBottom = '1px dashed #f1f5f9';
                                row.style.alignItems = 'center';
                                row.style.borderRadius = '8px';

                                if (isCurrent) {
                                    row.style.background = '#f0fdfa';
                                    row.style.border = '1px solid #ccfbf1';
                                }

                                const isLow = sQty <= p.alert_quantity;
                                const isOut = sQty <= 0;
                                const color = isOut ? '#ef4444' : (isLow ? '#f59e0b' : '#10b981');

                                row.innerHTML = `
                                    <span style="color: ${isCurrent ? '#0d9488' : '#64748b'}; font-weight: ${isCurrent ? '700' : '500'};">
                                        ${store.store_name} ${isCurrent ? ' <span style="font-size: 9px; opacity: 0.7;">(Current Store)</span>' : ''}
                                    </span>
                                    <div style="display: flex; align-items: center; gap: 4px;">
                                        <span style="font-weight: 700; color: ${color}; font-size: 14px;">${sQty}</span>
                                        ${store.location ? `<span style="font-weight: 600; color: #475569; font-size: 11px; background: #f1f5f9; padding: 2px 6px; border-radius: 4px;"><i class="fas fa-map-marker-alt" style="font-size: 10px; margin-right: 3px;"></i>${store.location}</span>` : ''}
                                    </div>
                                `;
                                listContainer.appendChild(row);
                            });
                        } else {
                            listContainer.innerHTML = '<div style="padding: 10px; text-align: center; color: #94a3b8; font-size: 12px;">No stores found</div>';
                        }
                    };

                    // Initial Render
                    renderStockItems(p.stock_by_store);

                    // Add Search Input
                    const searchContainer = document.createElement('div');
                    searchContainer.style.marginTop = '10px';
                    searchContainer.style.padding = '0 5px';
                    searchContainer.innerHTML = `
                        <div style="position: relative;">
                            <i class="fas fa-search" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 12px;"></i>
                            <input type="text" id="pd-stock-search-input" placeholder="Search other stores..." 
                                style="width: 100%; padding: 8px 10px 8px 30px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 12px; outline: none; transition: border-color 0.2s;">
                        </div>
                    `;
                    stockList.appendChild(searchContainer);

                    // Search Logic
                    const searchInput = searchContainer.querySelector('input');
                    let searchTimeout;

                    searchInput.addEventListener('input', function () {
                        const term = this.value.trim();
                        clearTimeout(searchTimeout);

                        searchTimeout = setTimeout(() => {
                            // Show loading state in list?
                            listContainer.style.opacity = '0.5';

                            let url = `get_product_details.php?id=${p.id}&store_id=${currentStoreId}`;
                            if (term.length > 0) {
                                url += `&search_store=${encodeURIComponent(term)}`;
                            }

                            fetch(url)
                                .then(res => res.json())
                                .then(data => {
                                    if (data.success && data.product && data.product.stock_by_store) {
                                        renderStockItems(data.product.stock_by_store);
                                    }
                                    listContainer.style.opacity = '1';
                                })
                                .catch(err => {
                                    console.error(err);
                                    listContainer.style.opacity = '1';
                                });
                        }, 300); // 300ms debounce
                    });

                    searchInput.addEventListener('focus', function () {
                        this.style.borderColor = '#0d9488';
                    });
                    searchInput.addEventListener('blur', function () {
                        this.style.borderColor = '#e2e8f0';
                    });
                }

                // Badge Logic for Modal Image
                const stockBadge = document.getElementById('pd-stock-badge');
                const limitBadge = document.getElementById('pd-limit-badge');
                const primaryStock = p.stock || 0;
                const alertQty = p.alert_quantity || 5;
                const effectiveLimit = p.effective_limit || 0;

                // 1. Stock Badge (Top Right)
                if (stockBadge) {
                    if (primaryStock <= 0) {
                        stockBadge.textContent = 'Out of Stock';
                        stockBadge.style.background = '#ef4444';
                        stockBadge.style.display = 'block';
                    } else if (primaryStock <= alertQty) {
                        stockBadge.textContent = 'Low Stock';
                        stockBadge.style.background = '#f59e0b';
                        stockBadge.style.display = 'block';
                    } else {
                        stockBadge.style.display = 'none';
                    }
                }

                // 2. Limit Badge (Top Left)
                if (limitBadge) {
                    if (effectiveLimit > 0) {
                        limitBadge.textContent = 'Limit: ' + effectiveLimit;
                        limitBadge.style.display = 'block';
                    } else {
                        limitBadge.style.display = 'none';
                    }
                }

                // 3. Add Button State
                const addBtn = document.getElementById('pd-add-btn');
                if (addBtn) {
                    if (primaryStock <= 0) {
                        addBtn.disabled = true;
                        addBtn.style.opacity = '0.5';
                        addBtn.style.cursor = 'not-allowed';
                    } else {
                        addBtn.disabled = false;
                        addBtn.style.opacity = '1';
                        addBtn.style.cursor = 'pointer';
                    }
                }

                // Related Products
                const relatedList = document.getElementById('pd-related-list');
                if (relatedList) {
                    relatedList.innerHTML = '';
                    if (resp.related_products && resp.related_products.length > 0) {
                        resp.related_products.forEach(rp => {
                            const item = document.createElement('div');
                            item.className = 'val-related-item';
                            item.style = 'display: flex; align-items: center; gap: 10px; padding: 8px; background: white; border: 1px solid #e2e8f0; border-radius: 10px; cursor: pointer; transition: all 0.2s;';
                            item.onclick = () => window.openProductDetails(rp.id);
                            item.onmouseover = () => { item.style.borderColor = '#0d9488'; item.style.transform = 'translateY(-2px)'; };
                            item.onmouseout = () => { item.style.borderColor = '#e2e8f0'; item.style.transform = 'translateY(0)'; };
                            item.innerHTML = `
                                <img src="${rp.thumbnail}" style="width: 40px; height: 40px; object-fit: cover; border-radius: 6px;">
                                <div style="flex: 1; display: flex; flex-direction: row; justify-content: space-between; align-items: center; gap: 8px;">
                                    <div style="font-size: 12px; font-weight: 600; color: #1e293b; line-height: 1.2; margin-bottom: 2px;">${rp.product_name}</div>
                                    <div style="font-size: 11px; font-weight: 700; color: #0d9488; white-space: nowrap;">৳${parseFloat(rp.selling_price).toFixed(2)}</div>
                                </div>
                            `;
                            relatedList.appendChild(item);
                        });
                    } else {
                        relatedList.innerHTML = '<div style="text-align: center; color: #94a3b8; font-size: 12px; padding: 20px;">No related products found.</div>';
                    }
                }

                document.getElementById('pd-qty-input').value = 1;
            } else {
                showToast('Error loading product details: ' + (resp.message || 'Unknown'), 'error');
                closeModal('productDetailsModal');
            }
        })
        .catch(err => {
            console.error(err);
            showToast('Failed to load product details network error', 'error');
            closeModal('productDetailsModal');
        });
};

function adjustModalQty(delta) {
    const input = document.getElementById('pd-qty-input');
    let val = parseInt(input.value) || 1;
    val += delta;
    if (val < 1) val = 1;
    input.value = val;
}

// Robust price parsing helper
function parseFloatPrice(str) {
    if (!str) return 0;
    // Remove everything except numbers and dots
    const cleanStr = str.replace(/[^\d.]/g, '');
    return parseFloat(cleanStr) || 0;
}

function addToCartFromModal() {
    if (!currentProductDetailsId) return;

    const qty = parseInt(document.getElementById('pd-qty-input').value) || 1;
    const name = document.getElementById('pd-name').textContent;
    const priceStr = document.getElementById('pd-price').textContent;
    const price = parseFloatPrice(priceStr);

    // Get stock from the main product grid card to ensure consistency with store selection
    const productCard = document.querySelector(`.product-card[data-id="${currentProductDetailsId}"]`);
    let stock = 0;

    if (productCard) {
        stock = parseFloat(productCard.dataset.stock) || 0;
    } else {
        // Fallback for search or error
        console.warn('Product card not found for stock check');
    }

    // Call shared addToCart with Quantity
    addToCart(currentProductDetailsId, name, price, stock, qty);

    closeModal('productDetailsModal');
}

// ===============================
// KEYBOARD & SALE HANDLING LOGIC (ADDED)
// ===============================

// Helper to check active modal
function getActiveModal() {
    const modals = document.querySelectorAll('.modal');
    for (let modal of modals) {
        if ((modal.style.display === 'flex' || modal.classList.contains('active')) && modal.style.display !== 'none') {
            return modal.id;
        }
    }
    return null;
}

// Global Keyboard Handler
document.addEventListener('keydown', function (e) {
    const activeModal = getActiveModal();

    // 1. Payment Modal Logic
    if (activeModal === 'paymentModal') {
        if (e.key === 'Enter') {
            e.preventDefault();
            completeSale();
        } else if (e.key === 'Escape') {
            e.preventDefault();
            closeModal('paymentModal');
        }
    }
    // 2. Invoice Modal Logic
    else if (activeModal === 'invoiceModal') {
        if (e.key === 'Enter') {
            e.preventDefault();
            triggerAutoPrint();
        } else if (e.key === 'Escape') {
            e.preventDefault();
            closeInvoiceModal(false); // No reload
        }
    }
    // 3. POS Page Logic
    else if (!activeModal) {
        if (e.key === 'F2') {
            e.preventDefault();
            document.getElementById('product_search').focus();
        } else if (e.key === 'F4') {
            e.preventDefault();
            if (cart.length > 0) {
                openPaymentModal({
                    totalPayable: parseFloatPrice(document.getElementById('grand-total').textContent),
                    previousDue: parseFloat(document.getElementById('customer_due_balance').value) || 0,
                    customerId: document.getElementById('selected_customer_id').value || 0,
                    customerName: document.getElementById('selected-customer-name').textContent,
                    onSubmit: function (paymentData) {
                        submitPosSale(paymentData);
                    }
                });
            } else {
                showToast('Cart is empty', 'error');
            }
        } else if (e.key === 'Enter') {
            // DISABLED: Duplicate Enter Handler
            // This logic is already handled by the main Enter key listener at line 23
            /*
            if (document.activeElement.id === 'product_search') return;
            if (cart.length > 0) {
                e.preventDefault();
                openPaymentModal({
                    totalPayable: parseFloatPrice(document.getElementById('grand-total').textContent),
                    previousDue: parseFloat(document.getElementById('customer_due_balance').value) || 0,
                    customerId: document.getElementById('selected_customer_id').value || 0,
                    customerName: document.getElementById('selected-customer-name').textContent,
                    onSubmit: function (paymentData) {
                        submitPosSale(paymentData);
                    }
                });
            }
            */
        }
    }
});

function triggerAutoPrint() {
    const printBtn = document.querySelector('#invoiceModal .btn-primary');
    if (printBtn) {
        printBtn.click();
    } else {
        window.print();
    }
}

function handleSaleSuccess(response) {
    if (!response || !response.success) return;

    closeModal('paymentModal');

    // Reset payment modal to defaults (Full Payment + Cash)
    if (typeof resetPaymentModal === 'function') {
        resetPaymentModal();
    }

    // Reset Data silently
    cart = [];
    updateCartUI();

    document.getElementById('discount-input').value = 0;
    document.getElementById('tax-input').value = 0;
    document.getElementById('shipping-input').value = 0;
    document.getElementById('other-input').value = 0;

    if (typeof appliedPayments !== 'undefined') appliedPayments = [];
    if (typeof selectedPaymentMethod !== 'undefined') selectedPaymentMethod = null;

    const currentCustomerId = document.getElementById('selected_customer_id').value;
    if (currentCustomerId && currentCustomerId == response.customer_id) {
        document.getElementById('customer_due_balance').value = response.new_balance;
        document.getElementById('customer_opening_balance').value = response.new_wallet_balance;

        let giftBalance = document.getElementById('customer_giftcard_balance');
        if (giftBalance && response.new_giftcard_balance !== undefined) {
            giftBalance.value = response.new_giftcard_balance;
        }

        const dueDisplay = document.getElementById('selected-customer-due-display');
        const dueAmount = document.getElementById('selected-customer-due-amount');
        if (response.new_balance > 0) {
            dueDisplay.style.display = 'block';
            dueAmount.textContent = parseFloat(response.new_balance).toFixed(2);
        } else {
            dueDisplay.style.display = 'none';
        }
    }

    if (typeof loadProducts === 'function') {
        loadProducts(currentPage);
    }

    document.getElementById('product_search').value = '';
    showToast('Sale completed successfully', 'success');
}

// Auto-select content on focus for Totals Inputs
document.addEventListener('DOMContentLoaded', function () {
    ['discount-input', 'tax-input', 'shipping-input', 'other-input'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('focus', function () {
                this.select();
            });
        }
    });
});

// --- NEW: Dynamic Currency Helper ---
function updateCurrencyFromStore() {
    const storeSelect = document.getElementById('store_select');
    if (storeSelect && typeof stores !== 'undefined') {
        const storeId = storeSelect.value;
        if (storeId && storeId !== 'all') {
            const selectedStore = stores.find(s => s.id == storeId);
            if (selectedStore && selectedStore.currency_symbol) {
                // Strictly use the store's currency symbol
                window.currencySymbol = selectedStore.currency_symbol;

                // Update specific elements directly
                const grandTotalEl = document.getElementById('grand-total');
                if (grandTotalEl) updateTotals();
                // Check if cart is defined before using it
                if (typeof cart !== 'undefined' && cart.length > 0) renderCart();
            }
        }
    }
}

// Attach listeners
document.addEventListener('DOMContentLoaded', function () {
    // Initial check
    setTimeout(updateCurrencyFromStore, 500);

    // If store selector changes
    const storeSelect = document.getElementById('store_select');
    if (storeSelect) {
        storeSelect.addEventListener('change', updateCurrencyFromStore);
    }
});

// Toggle POS Settings (Sound, Images, Auto Print)
window.togglePosSetting = function (key, isChecked) {
    const value = isChecked ? 1 : 0;

    // Immediate UI Effect
    if (key === 'show_images') {
        const productGrid = document.getElementById('product-grid');
        if (isChecked) {
            productGrid.classList.remove('hide-images');
            location.reload(); // Reload to fetch images if they were not loaded
        } else {
            productGrid.classList.add('hide-images');
        }
    }

    // Save to Server
    const formData = new FormData();
    formData.append('update_setting', 'true');
    formData.append('key', key);
    formData.append('value', value);

    // Get store ID from select if available, else standard
    const storeSelect = document.getElementById('store_select');
    if (storeSelect) {
        formData.append('store_id', storeSelect.value);
    }

    fetch('/pos/stores/save_store_settings.php', {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                showToast('Setting updated', 'success');
                // Reload if auto print is toggled to ensure state
                if (key === 'auto_print' || key === 'enable_sound') {
                    // No reload needed for sound/print usually, just variable update if we had global vars
                    // For now, simpler to reload or just let it be saved
                }
            } else {
                showToast('Failed to save setting', 'error');
            }
        })
        .catch(err => console.error(err));
};
