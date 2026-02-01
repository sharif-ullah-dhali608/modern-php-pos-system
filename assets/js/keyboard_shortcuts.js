console.log("Keyboard Shortcuts: File loaded.");

const shortcuts = [
    { keys: 'P', desc: 'Any Page to POS Page' },
    { keys: 'X', desc: 'Focus Product Searchbox' },
    { keys: 'C', desc: 'Focus Customer Searchbox' },
    { keys: 'A', desc: 'Add New Customer' },
    { keys: 'I', desc: 'Focus Discount Field' },
    { keys: 'T', desc: 'Focus Tax Field' },
    { keys: 'S', desc: 'Focus Shipping Charge' },
    { keys: 'O', desc: 'Focus Others Charge' },
    { keys: 'H', desc: 'Hold Order' },
    { keys: 'Z', desc: 'Pay Now' },
    { keys: 'K', desc: 'Keyboard Shortcuts' },
    { keys: 'G', desc: 'Quick Add Product' },
    { keys: 'J', desc: 'Quick Add Giftcard' },
    { keys: 'N', desc: 'New Sale (Reset Cart)' },
    { keys: 'F', desc: 'Toggle Fullscreen' },
    { keys: 'D', desc: 'Go to Dashboard' },
    { keys: 'V', desc: 'Go to Invoices' },
    { keys: 'B', desc: 'Go to Cashbook' },
    { keys: 'R', desc: 'Refresh Products List' },
    { keys: 'W', desc: 'Edit Walking Customer' },
    { keys: '[', desc: 'Previous Category' },
    { keys: ']', desc: 'Next Category' },
    { keys: '←', desc: 'Previous Page (Alt+Left)' },
    { keys: '→', desc: 'Next Page (Alt+Right)' },
    { keys: 'Enter', desc: 'Add 1st Search Result' },
    { keys: 'Q', desc: 'Focus Last Item Qty' }
];

function renderShortcuts() {
    console.log("Keyboard Shortcuts: Rendering modal content.");
    const isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
    const modKey = isMac ? '⌥ ' : 'Alt + ';
    const tbody = document.getElementById('shortcuts-body');

    if (tbody) {
        tbody.innerHTML = shortcuts.map(s => `
            <tr>
                <td><span class="shortcut-key">${modKey}${s.keys}</span></td>
                <td>${s.desc}</td>
            </tr>
        `).join('');
    } else {
        console.warn("Keyboard Shortcuts: 'shortcuts-body' element not found.");
    }
}

// Keyboard Listeners
document.addEventListener('keydown', function (e) {
    try {
        if (e.altKey) {
            const code = e.code; // Physical key (e.g., 'KeyP', 'Digit1')
            const shiftChar = e.shiftKey ? 'Shift + ' : '';
            console.log(`Keyboard Shortcuts: Alt + ${shiftChar}${code} detected.`);

            // Mapping physical codes to logical shortcut keys
            // This bypasses OS-specific character translations (like π on Mac)
            let logicalKey = null;

            if (code.startsWith('Key')) {
                logicalKey = code.replace('Key', ''); // 'KeyP' -> 'P'
            } else if (code.startsWith('Digit')) {
                logicalKey = code.replace('Digit', ''); // 'Digit1' -> '1'
            } else if (code === 'BracketLeft') {
                logicalKey = '[';
            } else if (code === 'BracketRight') {
                logicalKey = ']';
            } else if (code === 'ArrowLeft') {
                logicalKey = 'LEFT';
            } else if (code === 'ArrowRight') {
                logicalKey = 'RIGHT';
            } else if (code === 'Enter' || code === 'NumpadEnter') {
                logicalKey = 'ENTER';
            } else if (code === 'Comma') {
                logicalKey = ','; // Just in case
            } else if (code === 'Period') {
                logicalKey = '.';
            }

            if (!logicalKey) {
                console.log(`Keyboard Shortcuts: No logical mapping for code ${code}`);
                return;
            }

            console.log(`Keyboard Shortcuts: Logical Key: ${logicalKey}`);

            // Prevent default browser behaviors for these shortcuts
            // HandledKeys are 'P', 'C', 'A', etc.
            const handledKeys = shortcuts.map(s => s.keys.toUpperCase());
            const isSpecial = ['[', ']', 'LEFT', 'RIGHT', 'ENTER'].includes(logicalKey);

            if (handledKeys.includes(logicalKey) || isSpecial) {
                // Special check for Enter: only prevent if on search or a modal is needing it
                if (logicalKey === 'ENTER' && document.activeElement.id !== 'product_search') {
                    // Let default Enter happen unless it's the search box
                } else {
                    console.log("Keyboard Shortcuts: Preventing default for", code);
                    e.preventDefault();
                }
            }

            // --- IN-MODAL SHORTCUTS (Payment Modal Only) ---
            const paymentModal = document.getElementById('paymentModal');
            if (paymentModal && (paymentModal.classList.contains('active') || paymentModal.style.display === 'flex')) {
                console.log("Keyboard Shortcuts: Payment modal active.");
                // Alt + 1-9: Select Payment Method
                if (logicalKey >= '1' && logicalKey <= '9') {
                    const methods = document.querySelectorAll('.sidebar-payment-method:not([style*="display: none"])');
                    const index = parseInt(logicalKey) - 1;
                    if (methods[index]) {
                        console.log("Keyboard Shortcuts: Clicking payment method index", index);
                        methods[index].click();
                        return;
                    }
                }
                // Alt + L: Full Payment
                if (logicalKey === 'L') {
                    const btn = document.querySelector('.payment-type-btn[data-type="full"]');
                    if (btn) btn.click();
                    return;
                }
                // Alt + T: Partial Payment
                if (logicalKey === 'T') {
                    const btn = document.querySelector('.payment-type-btn[data-type="partial"]');
                    if (btn) btn.click();
                    return;
                }
                // Alt + U: Full Due
                if (logicalKey === 'U') {
                    const btn = document.querySelector('.payment-type-btn[data-type="due"]');
                    if (btn) btn.click();
                    return;
                }
            }

            console.log("Keyboard Shortcuts: Executing action for logical key", logicalKey);
            switch (logicalKey) {
                case 'P':
                    window.location.href = '/pos/pos/';
                    break;
                case 'X':
                    const pSearch = document.getElementById('product_search');
                    if (pSearch) { pSearch.focus(); pSearch.select(); }
                    break;
                case 'C':
                    if (typeof openModal === 'function') openModal('customerSelectModal');
                    break;
                case 'A':
                    if (typeof openModal === 'function') openModal('addCustomerModal');
                    break;
                case 'I':
                    const disc = document.getElementById('discount-input');
                    if (disc) { disc.focus(); disc.select(); }
                    break;
                case 'T':
                    const tax = document.getElementById('tax-input');
                    if (tax) { tax.focus(); tax.select(); }
                    break;
                case 'S':
                    const ship = document.getElementById('shipping-input');
                    if (ship) { ship.focus(); ship.select(); }
                    break;
                case 'O':
                    const other = document.getElementById('other-input');
                    if (other) { other.focus(); other.select(); }
                    break;
                case 'H':
                    if (typeof prepareHoldModal === 'function') prepareHoldModal();
                    break;
                case 'Z':
                    if (typeof prepareAndOpenPaymentModal === 'function') prepareAndOpenPaymentModal();
                    break;
                case 'G':
                    if (typeof openModal === 'function') openModal('addProductModal');
                    break;
                case 'J':
                    if (typeof openModal === 'function') openModal('giftcardModal');
                    break;
                case 'K':
                    if (typeof openModal === 'function') openModal('keyboardShortcutsModal');
                    break;
                case 'N':
                    // New Sale / Reset Cart
                    Swal.fire({
                        title: 'Clear Cart?',
                        text: 'Are you sure you want to clear the cart and start a new sale?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#10b981',
                        cancelButtonColor: '#ef4444',
                        confirmButtonText: 'Yes, clear it!',
                        cancelButtonText: 'No, keep it'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            if (typeof cart !== 'undefined') {
                                cart = [];
                                if (typeof renderCart === 'function') renderCart();
                                showToast('Cart cleared', 'info');
                            }
                        }
                    });
                    break;
                case 'F':
                    if (typeof toggleFullscreen === 'function') toggleFullscreen();
                    break;
                case 'D':
                    window.location.href = '/pos';
                    break;
                case 'V':
                    window.location.href = '/pos/invoice/list';
                    break;
                case 'B':
                    window.location.href = '/pos/reports/cashbook';
                    break;
                case 'R':
                    if (typeof loadProducts === 'function') loadProducts(1);
                    break;
                case 'W':
                    if (typeof openWalkingCustomerModal === 'function') openWalkingCustomerModal();
                    break;
                case 'Q':
                    const lastQty = document.querySelector('#cart-items .cart-item:last-child .qty-input');
                    if (lastQty) { lastQty.focus(); lastQty.select(); }
                    break;
                case '[':
                    const btnsPrev = document.querySelectorAll('.category-filter button');
                    const activePrev = document.querySelector('.category-filter button.active');
                    const indexPrev = Array.from(btnsPrev).indexOf(activePrev);
                    const prev = btnsPrev[indexPrev - 1] || btnsPrev[btnsPrev.length - 1];
                    if (prev) prev.click();
                    break;
                case ']':
                    const btnsNext = document.querySelectorAll('.category-filter button');
                    const activeNext = document.querySelector('.category-filter button.active');
                    const indexNext = Array.from(btnsNext).indexOf(activeNext);
                    const next = btnsNext[indexNext + 1] || btnsNext[0];
                    if (next) next.click();
                    break;
                case 'LEFT':
                    const prevBtn = document.querySelector('#pagination-controls button:first-child:not([disabled])');
                    if (prevBtn) prevBtn.click();
                    break;
                case 'RIGHT':
                    const nextBtn = document.querySelector('#pagination-controls button:last-child:not([disabled])');
                    if (nextBtn) nextBtn.click();
                    break;
                case 'ENTER':
                    // If focused on search and Enter is pressed with Alt, add first product to cart
                    if (document.activeElement.id === 'product_search') {
                        const firstProduct = document.querySelector('.product-card:not(.out-of-stock-card)');
                        if (firstProduct) {
                            const addBtn = firstProduct.querySelector('.add-btn');
                            if (addBtn) addBtn.click();
                        }
                    }
                    break;
            }
        }

        // Escape to close modals
        if (e.code === 'Escape') {
            const openModalEl = document.querySelector('.pos-modal:not([style*="display: none"])');
            if (openModalEl && typeof closeModal === 'function') {
                console.log("Keyboard Shortcuts: Closing modal via Escape.");
                closeModal(openModalEl.id);
            }
        }
    } catch (err) {
        console.error("Keyboard Shortcuts: Error in keydown handler:", err);
    }
});

$(document).ready(function () {
    console.log("Keyboard Shortcuts: Document ready, initializing.");
    renderShortcuts();
});
