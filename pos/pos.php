<?php
session_start();
include('../config/dbcon.php');

// Security Check
if(!isset($_SESSION['auth'])){
    header("Location: /pos/login");
    exit(0);
}

// Get current user and store info
$user_id = $_SESSION['auth_user']['id'] ?? 1;
$user_name = $_SESSION['auth_user']['name'] ?? 'Admin';





// Fetch active stores
$stores_query = "SELECT * FROM stores WHERE status=1 ORDER BY store_name ASC";
$stores_result = mysqli_query($conn, $stores_query);
$stores = [];
while($store = mysqli_fetch_assoc($stores_result)) {
    $stores[] = $store;
}
$current_store = $stores[0] ?? ['id' => 1, 'store_name' => 'Default Store'];

// Pagination settings
$items_per_page = 20;
$page_no = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page_no - 1) * $items_per_page;

// Get total product count
$count_query = "SELECT COUNT(*) as total FROM products WHERE status = 1";
$count_result = mysqli_query($conn, $count_query);
$total_products = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_products / $items_per_page);

// Fetch products for display
$products_query = "SELECT p.*, c.name as category_name, u.unit_name 
                   FROM products p 
                   LEFT JOIN categories c ON p.category_id = c.id 
                   LEFT JOIN units u ON p.unit_id = u.id 
                   WHERE p.status = 1 
                   ORDER BY p.product_name ASC 
                   LIMIT $items_per_page OFFSET $offset";
$products_result = mysqli_query($conn, $products_query);

// Fetch categories for filter
$categories_query = "SELECT id, name FROM categories WHERE status=1 ORDER BY name ASC";
$categories_result = mysqli_query($conn, $categories_query);

// Fetch customers with credit and giftcard info
$customers_query = "SELECT c.id, c.name, c.mobile, c.current_due as credit, c.opening_balance,
                    COALESCE(SUM(g.balance), 0) as giftcard_balance
                    FROM customers c
                    LEFT JOIN giftcards g ON c.id = g.customer_id AND g.status = 1
                    WHERE c.status=1 
                    GROUP BY c.id, c.name, c.mobile, c.current_due, c.opening_balance
                    ORDER BY c.name ASC";
$customers_result = mysqli_query($conn, $customers_query);

// Fetch payment methods
$payment_methods_query = "SELECT id, name, code FROM payment_methods WHERE status=1 ORDER BY sort_order ASC";
$payment_methods_result = mysqli_query($conn, $payment_methods_query);

// Fetch additional data for Quick Add Product Modal
$brands = mysqli_query($conn, "SELECT id, name FROM brands WHERE status='1' ORDER BY name ASC");
$units = mysqli_query($conn, "SELECT id, unit_name as name, code as short_name FROM units WHERE status='1'");
$taxes = mysqli_query($conn, "SELECT id, name, taxrate as rate FROM taxrates WHERE status='1'");
$boxes = mysqli_query($conn, "SELECT id, box_name as name FROM boxes WHERE status='1'");
$currencies = mysqli_query($conn, "SELECT id, currency_name as name, code FROM currencies WHERE status='1'");
$suppliers = mysqli_query($conn, "SELECT id, name FROM suppliers WHERE status='1' ORDER BY name ASC");

// Initialize cart session
if(!isset($_SESSION['pos_cart'])) {
    $_SESSION['pos_cart'] = [];
}

$page_title = "POS - Velocity POS";
include('../includes/header.php');
?>
<link rel="stylesheet" href="/pos/assets/css/pos.css">
<style>
    /* Completely hide sidebar on POS page */
#sidebar {
    display: none !important;
}

#sidebar-toggle-container {
    display: none !important;
}

#main-content {
    margin-left: 0 !important;
    width: 100% !important;
}

.app-wrapper {
    display: block !important;
}

</style>
<div class="app-wrapper">
    <?php include('../includes/sidebar.php'); ?>
    
    <main id="main-content" class="flex-1 lg:ml-64 flex flex-col h-screen min-w-0 transition-all duration-300 overflow-hidden">
        <div class="navbar-fixed-top">
        </div>
        
        <!-- POS Header Bar (Full Width Navbar) -->
        <div class="pos-header-bar">
            <select id="store_select" class="store-select">
                <option value="all">All Stores</option>
                <?php 
                $first = true;
                foreach($stores as $store): 
                ?>
                    <option value="<?= $store['id']; ?>" <?= $first ? 'data-default="true"' : ''; ?>>
                        <?= htmlspecialchars($store['store_name']); ?>
                    </option>
                <?php 
                    $first = false;
                endforeach; 
                ?>
            </select>
            
            <div class="nav-links">
                <a href="/pos/pos/" class="active"><i class="fas fa-cash-register"></i> POS</a>
                <a href="#"><i class="fas fa-book"></i> CASHBOOK</a>
                <a href="/pos/invoice/list"><i class="fas fa-file-invoice"></i> INVOICE</a>
                <a href="#" onclick="openHeldOrdersModal()"><i class="fas fa-pause-circle"></i> HOLD ORDER</a>
                <a href="#" onclick="openModal('addProductModal')"><i class="fas fa-plus"></i> Product</a>
                <a href="#" onclick="openModal('addCustomerModal')"><i class="fas fa-user-plus"></i> Customer</a>
                <a href="#" onclick="openModal('giftcardModal')"><i class="fas fa-gift"></i> Giftcard</a>
                <a href="/pos/products/stock_alert" class="relative group">
                    <i class="fas fa-exclamation-triangle"></i> Stock Alert
                    <?php if($alert_count > 0): ?>
                        <span class="stock-alert-badge"><?= $alert_count; ?></span>
                    <?php endif; ?>
                </a>
                <a href="/pos/admin/reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
                <a href="#" onclick="lockScreen()"><i class="fas fa-lock"></i> Lockscreen</a>
            </div>
            
            <!-- Fullscreen Toggle Button -->
            <button id="fullscreen-btn" onclick="toggleFullscreen()" class="fullscreen-btn" title="Toggle Fullscreen">
                <i class="fas fa-expand" id="fullscreen-icon"></i>
            </button>
        </div>
        
        <!-- Main Content Below Header -->
        <div class="pos-container">
            <!-- Left Panel: Products -->
            <div class="pos-left">
                <!-- Search Bar -->
                <div class="search-bar">
                    <input type="text" id="product_search" placeholder="Search/Barcode Scan..." autocomplete="off" autofocus>
                </div>
                
                <!-- Category Filter -->
                <div class="category-filter">
                    <button class="active" data-category="all">View All</button>
                    <?php 
                    mysqli_data_seek($categories_result, 0);
                    while($cat = mysqli_fetch_assoc($categories_result)): ?>
                        <button data-category="<?= $cat['id']; ?>"><?= htmlspecialchars($cat['name']); ?></button>
                    <?php endwhile; ?>
                </div>
                
                <!-- Product Grid -->
                <div class="product-grid" id="product-grid">
                    <?php while($product = mysqli_fetch_assoc($products_result)): 
                        $stock = floatval($product['opening_stock']);
                        $alert_qty = floatval($product['alert_quantity'] ?? 5);
                        $is_out_of_stock = $stock <= 0;
                        $is_low_stock = $stock > 0 && $stock <= $alert_qty;
                    ?>
                        <div class="product-card <?= $is_out_of_stock ? 'out-of-stock-card' : '' ?>" data-id="<?= $product['id']; ?>" data-name="<?= htmlspecialchars($product['product_name']); ?>" data-price="<?= $product['selling_price']; ?>" data-category="<?= $product['category_id']; ?>" data-stock="<?= $stock; ?>">
                            <?php if($is_out_of_stock): ?>
                                <div class="stock-badge out-of-stock">Out of Stock</div>
                            <?php elseif($is_low_stock): ?>
                                <div class="stock-badge low-stock">Low Stock</div>
                            <?php endif; ?>
                            <?php if(!empty($product['thumbnail'])): ?>
                                <img src="<?= $product['thumbnail']; ?>" alt="<?= htmlspecialchars($product['product_name']); ?>">
                            <?php else: ?>
                                <img src="../assets/images/no-image.png" alt="No Image">
                            <?php endif; ?>
                            <div class="info">
                                <div class="name"><?= htmlspecialchars($product['product_name']); ?></div>
                                <div class="price">৳<?= number_format($product['selling_price'], 2); ?></div>
                                <div class="stock">Stock: <?= $product['opening_stock']; ?> <?= $product['unit_name'] ?? 'pcs'; ?></div>
                            </div>
                            <?php if($is_out_of_stock): ?>
                                <button class="add-btn out-of-stock-btn" disabled>
                                    <i class="fas fa-ban"></i> Out of Stock
                                </button>
                            <?php else: ?>
                                <button class="add-btn" onclick="addToCart(<?= $product['id']; ?>, '<?= htmlspecialchars($product['product_name'], ENT_QUOTES); ?>', <?= $product['selling_price']; ?>)">
                                    <i class="fas fa-cart-plus"></i> Add To Cart
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                </div>

                <!-- Pagination -->
                <div class="pagination-wrapper" id="pagination-controls">
                    <?php if($page_no > 1): ?>
                        <button onclick="loadProducts(<?= $page_no - 1; ?>)">
                            <i class="fas fa-chevron-left"></i><span class="pagination-text"> Previous</span>
                        </button>
                    <?php else: ?>
                        <button disabled><i class="fas fa-chevron-left"></i><span class="pagination-text"> Previous</span></button>
                    <?php endif; ?>
                    
                    <span class="pagination-info" style="padding: 8px 14px; font-weight: 600; color: #64748b;">
                        <span class="page-label">Page </span><?= (int)$page_no; ?><span class="page-of"> of </span><?= (int)$total_pages; ?>
                    </span>
                    
                    <?php if($page_no < $total_pages): ?>
                        <button onclick="loadProducts(<?= $page_no + 1; ?>)">
                            <span class="pagination-text">Next </span><i class="fas fa-chevron-right"></i>
                        </button>
                    <?php else: ?>
                        <button disabled><span class="pagination-text">Next </span><i class="fas fa-chevron-right"></i></button>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Right Panel: Cart -->
            <div class="pos-right">
                <!-- Customer Section -->
                <div class="customer-section">
                    <div class="customer-box" onclick="openModal('customerSelectModal')" style="cursor: pointer;">
                        <div class="avatar"><i class="fas fa-user"></i></div>
                        <div class="details">
                            <div class="name" id="selected-customer-name">Walking Customer</div>
                            <div class="phone" id="selected-customer-phone">0170000000000</div>
                        </div>
                        <div id="selected-customer-due-display" style="margin-left: auto; margin-right: 10px; display: none;">
                            <span style="background: #fee2e2; color: #dc2626; padding: 2px 8px; border-radius: 20px; font-size: 12px; font-weight: 600; border: 1px solid #fecaca;">Due: <span id="selected-customer-due-amount">0.00</span></span>
                        </div>
                        <i class="fas fa-pen" style="color: #10b981; cursor: pointer; padding: 4px;" onclick="event.stopPropagation(); openWalkingCustomerModal()"></i>
                        <button type="button" onclick="event.stopPropagation(); openModal('addCustomerModal')" style="background: #10b981; color: white; border: none; padding: 4px 6px; border-radius: 6px; cursor: pointer;">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                    <input type="hidden" id="selected_customer_id" value="">
                    <input type="hidden" id="customer_total_balance" value="0">
                    <input type="hidden" id="customer_credit" value="0">
                    <input type="hidden" id="customer_due_balance" value="0">
                    <input type="hidden" id="customer_giftcard_balance" value="0">
                    <input type="hidden" id="customer_opening_balance" value="0">
                </div>
                
                <!-- Cart Items -->
                <div class="cart-section">
                    <div class="cart-header">
                        <span>QTY</span>
                        <span>PRODUCT</span>
                        <span>PRICE</span>
                        <span>SUBTOTAL</span>
                    </div>
                    <div id="cart-items">
                        <div class="empty-cart" id="empty-cart">
                            <i class="fas fa-shopping-cart"></i>
                            <p>Cart is empty. Add products to begin.</p>
                        </div>
                    </div>
                </div>
                
                <!-- Totals Section -->
                <div class="totals-section">
                    <div class="totals-row">
                        <span>TOTAL ITEM</span>
                        <span id="total-items">0 (0)</span>
                        <span>TOTAL</span>
                        <span id="cart-total">0.00</span>
                    </div>
                    <div class="totals-row split-row">
                        <div class="input-group">
                            <label>DISCOUNT</label>
                            <input type="number" id="discount-input" value="0" min="0" step="0.01">
                        </div>
                        <div class="input-group">
                            <label>TAX AMOUNT (%)</label>
                            <input type="number" id="tax-input" value="0" min="0" step="0.01">
                        </div>
                    </div>
                    <div class="totals-row split-row">
                        <div class="input-group">
                            <label>SHIPPING CHARGE</label>
                            <input type="number" id="shipping-input" value="0" min="0" step="0.01">
                        </div>
                        <div class="input-group">
                            <label>OTHER CHARGE</label>
                            <input type="number" id="other-input" value="0" min="0" step="0.01">
                        </div>
                    </div>
                    <div class="totals-row total">
                        <span>TOTAL PAYABLE</span>
                        <span id="grand-total">৳0.00</span>
                    </div>
                </div>
                
                <!-- Footer Actions -->
                <div class="pos-footer">
                    <div class="total-display">
                        <div class="label">TOTAL</div>
                        <div class="amount" id="footer-total">৳0.00</div>
                    </div>
                    <div class="footer-controls">
                        <input type="date" class="date-input" id="sale-date" value="<?= date('Y-m-d'); ?>">
                        <button class="hold-btn" onclick="prepareHoldModal()">
                            <i class="fas fa-pause"></i> HOLD
                        </button>
                        <button class="pay-btn" onclick="prepareAndOpenPaymentModal()">
                            <i class="fas fa-credit-card"></i> PAY NOW
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Payment Modal -->
<?php include('../includes/payment_modal.php'); ?>

<!-- Hold Order Modal (Premium Teal Redesign) -->
<?php include('../includes/order_hold_modal.php'); ?>



<!-- Customer Select Modal -->
<div class="pos-modal" id="customerSelectModal">
    <div class="pos-modal-content" style="max-width: 500px;">
        <div class="pos-modal-header">
            <h3><i class="fas fa-user-plus"></i> Quick Select Customer</h3>
            <button class="close-btn" onclick="closeModal('customerSelectModal')" style="display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-times" style="font-size: 16px;"></i>
            </button>
        </div>
        <div class="pos-modal-body" style="padding: 20px;">
            <div style="position: relative; margin-bottom: 20px;">
                <i class="fas fa-search" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 14px;"></i>
                <input type="text" id="customer-search-input" placeholder="Search customer by name or phone..." 
                       style="width: 100%; padding: 12px 12px 12px 42px; border: 2px solid #e2e8f0; border-radius: 12px; font-size: 14px; transition: all 0.2s; background: #f8fafc; outline: none;"
                       onfocus="this.style.borderColor='#10b981'; this.style.backgroundColor='#fff'; this.style.boxShadow='0 0 0 4px rgba(16, 185, 129, 0.1)';"
                       onblur="this.style.borderColor='#e2e8f0'; this.style.backgroundColor='#f8fafc'; this.style.boxShadow='none';">
            </div>
            <div id="customer-list" style="max-height: 380px; overflow-y: auto; padding-right: 4px;" class="custom-scrollbar">
                <div class="customer-option" onclick="selectCustomer(0, 'Walking Customer', '0170000000000', 0, 0, 0)" 
                     style="padding: 14px 16px; border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 10px; cursor: pointer; display: flex; align-items: center; gap: 12px; transition: all 0.2s;"
                     onmouseover="this.style.borderColor='#10b981'; this.style.background='#f0fdf4';" onmouseout="this.style.borderColor='#e2e8f0'; this.style.background='white';">
                    <div style="width: 40px; height: 40px; border-radius: 50%; background: #f1f5f9; display: flex; align-items: center; justify-content: center; color: #64748b;">
                        <i class="fas fa-user-clock"></i>
                    </div>
                    <div>
                        <strong style="display: block; color: #1e293b; font-size: 14px;">Walking Customer</strong>
                        <span style="color: #64748b; font-size: 12px;">0170000000000</span>
                    </div>
                </div>
                <?php 
                mysqli_data_seek($customers_result, 0);
                while($customer = mysqli_fetch_assoc($customers_result)): 
                    $credit = floatval($customer['credit']);
                    $gc_balance = floatval($customer['giftcard_balance']);
                    $opening_bal = floatval($customer['opening_balance']);
                ?>
                    <div class="customer-option" onclick="selectCustomer(<?= $customer['id']; ?>, '<?= htmlspecialchars($customer['name'], ENT_QUOTES); ?>', '<?= $customer['mobile']; ?>', <?= $credit; ?>, <?= $gc_balance; ?>, <?= $opening_bal; ?>)" 
                         style="padding: 14px 16px; border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 10px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; transition: all 0.2s;"
                         onmouseover="this.style.borderColor='#10b981'; this.style.background='#f0fdf4';" onmouseout="this.style.borderColor='#e2e8f0'; this.style.background='white';">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 40px; height: 40px; border-radius: 50%; background: #ecfdf5; display: flex; align-items: center; justify-content: center; color: #10b981;">
                                <i class="fas fa-user"></i>
                            </div>
                            <div>
                                <strong style="display: block; color: #1e293b; font-size: 14px;"><?= htmlspecialchars($customer['name']); ?></strong>
                                <span style="color: #64748b; font-size: 12px;"><?= $customer['mobile']; ?></span>
                            </div>
                        </div>
                        <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 4px;">
                            <?php if($opening_bal > 0): ?>
                                <span style="background: #e0f2fe; color: #0369a1; padding: 2px 8px; border-radius: 20px; font-size: 10px; font-weight: 600; border: 1px solid #bae6fd;">Wallet: <?= number_format($opening_bal, 2); ?></span>
                            <?php endif; ?>
                            <?php if($credit > 0): ?>
                                <span style="background: #fee2e2; color: #dc2626; padding: 2px 8px; border-radius: 20px; font-size: 10px; font-weight: 600; border: 1px solid #fecaca;">Due: <?= number_format($credit, 2); ?></span>
                            <?php endif; ?>
                            <?php if($gc_balance > 0): ?>
                                <span style="background: #f0fdf4; color: #10b981; padding: 2px 8px; border-radius: 20px; font-size: 10px; font-weight: 600; border: 1px solid #d1fae5;">GC: <?= number_format($gc_balance, 2); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</div>





<!-- Walking Customer Edit Modal -->
<div class="pos-modal" id="walkingCustomerModal">
    <div class="pos-modal-content" style="max-width: 450px;">
        <div class="pos-modal-header">
            <h3><i class="fas fa-pen"></i> Walking Customer (<span id="walking-display-phone">0170000000000</span>)</h3>
            <button class="close-btn" onclick="closeModal('walkingCustomerModal')" style="display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-times" style="font-size: 16px;"></i>
            </button>
        </div>
        <div class="pos-modal-body">
            <div class="payment-input-group">
                <input type="text" id="walking-mobile-input" placeholder="Mobile Number..." style="text-align: left;">
            </div>
            <div style="display: flex; gap: 12px;">
                <button type="button" onclick="closeModal('walkingCustomerModal')" style="flex: 1; padding: 12px; background: #f59e0b; color: white; border: none; border-radius: 8px; font-weight: 700; cursor: pointer;">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="button" onclick="saveWalkingCustomer()" style="flex: 1; padding: 12px; background: #10b981; color: white; border: none; border-radius: 8px; font-weight: 700; cursor: pointer;">
                    <i class="fas fa-check"></i> Ok
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Add Customer Modal -->
<div class="pos-modal" id="addCustomerModal">
    <div class="pos-modal-content" style="max-width: 900px; max-height: 90vh; overflow-y: auto;">
        <div class="pos-modal-header">
            <h3><i class="fas fa-user-plus"></i> Quick Add Customer</h3>
            <button class="close-btn" onclick="closeModal('addCustomerModal')" style="display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-times" style="font-size: 16px;"></i>
            </button>
        </div>
        <div class="pos-modal-body">
            <form id="quickCustomerForm" enctype="multipart/form-data">
                <!-- Identity & Classification -->
                <div class="modal-section-header">
                    <i class="fas fa-id-card"></i>
                    <span>Identity & Classification</span>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 2fr 2fr; gap: 12px; margin-bottom: 16px;">
                    <div class="payment-input-group">
                        <label>Code *</label>
                        <input type="text" name="code_name" id="modal_code_name" required placeholder="CUST-001">
                        <span class="error-msg-text" id="error-modal_code_name">Required</span>
                    </div>
                    <div class="payment-input-group">
                        <label>Full Name *</label>
                        <input type="text" name="name" id="modal_name" required placeholder="e.g. John Doe">
                        <span class="error-msg-text" id="error-modal_name">Required</span>
                    </div>
                    <div class="payment-input-group">
                        <label>Company</label>
                        <input type="text" name="company_name" placeholder="Company Name (Optional)">
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 20px;">
                    <div class="payment-input-group">
                        <label>Customer Group *</label>
                        <select name="customer_group" id="modal_customer_group" required>
                            <option value="">Select Group</option>
                            <option value="Retail">Retail</option>
                            <option value="Wholesale">Wholesale</option>
                            <option value="VIP">VIP</option>
                            <option value="Corporate">Corporate</option>
                        </select>
                        <span class="error-msg-text" id="error-modal_customer_group">Required</span>
                    </div>
                    <div class="payment-input-group">
                        <label>Membership Level *</label>
                        <select name="membership_level" id="modal_membership_level" required>
                            <option value="">Select Level</option>
                            <option value="Standard">Standard</option>
                            <option value="Silver">Silver</option>
                            <option value="Gold">Gold</option>
                            <option value="Platinum">Platinum</option>
                        </select>
                        <span class="error-msg-text" id="error-modal_membership_level">Required</span>
                    </div>
                </div>

                <!-- Contact & Personal -->
                <div class="modal-section-header">
                    <i class="fas fa-address-book"></i>
                    <span>Contact & Personal</span>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                    <div class="payment-input-group">
                        <label>Phone *</label>
                        <input type="tel" name="mobile" id="modal_mobile" required placeholder="017...">
                        <span class="error-msg-text" id="error-modal_mobile">Required</span>
                    </div>
                    <div class="payment-input-group">
                        <label>Email Address</label>
                        <input type="email" name="email" id="modal_email" placeholder="email@example.com">
                        <span class="error-msg-text" id="error-modal_email">Invalid email</span>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-bottom: 20px;">
                    <div class="payment-input-group">
                        <label>Date of Birth</label>
                        <input type="date" name="dob">
                    </div>
                    <div class="payment-input-group">
                        <label>Sex *</label>
                        <select name="sex" required>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="payment-input-group">
                        <label>Age</label>
                        <input type="number" name="age" placeholder="Age">
                    </div>
                </div>

                <!-- Financial Status -->
                <div class="modal-section-header">
                    <i class="fas fa-money-bill-wave"></i>
                    <span>Financial Status</span>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-bottom: 20px;">
                    <div class="payment-input-group">
                        <label>Opening Balance *</label>
                        <input type="number" step="0.01" name="opening_balance" id="modal_opening_balance" value="0" required placeholder="0.00">
                        <span class="error-msg-text" id="error-modal_opening_balance">Required</span>
                    </div>
                    <div class="payment-input-group">
                        <label>Credit Limit *</label>
                        <input type="number" step="0.01" name="credit_limit" id="modal_credit_limit" value="0" required placeholder="0.00">
                        <span class="error-msg-text" id="error-modal_credit_limit">Required</span>
                    </div>
                    <div class="payment-input-group">
                        <label>Reward Points</label>
                        <input type="number" name="reward_points" value="0" placeholder="0">
                    </div>
                </div>

                <!-- Location Details -->
                <div class="modal-section-header">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>Location Details</span>
                </div>
                <div class="payment-input-group" style="margin-bottom: 12px;">
                    <label>Full Address</label>
                    <textarea name="address" rows="2" style="resize: none;" placeholder="Street, Area, etc."></textarea>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-bottom: 20px;">
                    <div class="payment-input-group">
                        <label>City *</label>
                        <input type="text" name="city" id="modal_city" required placeholder="City">
                        <span class="error-msg-text" id="error-modal_city">Required</span>
                    </div>
                    <div class="payment-input-group">
                        <label>State *</label>
                        <input type="text" name="state" id="modal_state" required placeholder="State">
                        <span class="error-msg-text" id="error-modal_state">Required</span>
                    </div>
                    <div class="payment-input-group">
                        <label>Country *</label>
                        <select name="country" id="modal_country" required>
                            <option value="">Select Country</option>
                            <option value="Bangladesh" selected>Bangladesh</option>
                            <option value="India">India</option>
                            <option value="Pakistan">Pakistan</option>
                            <option value="United States">United States</option>
                            <option value="United Kingdom">United Kingdom</option>
                            <option value="Canada">Canada</option>
                            <option value="Australia">Australia</option>
                        </select>
                        <span class="error-msg-text" id="error-modal_country">Required</span>
                    </div>
                </div>

                <!-- Additional Fields -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 20px;">
                    <div class="payment-input-group">
                        <label>Profile Picture</label>
                        <input type="file" name="image" accept="image/*" style="font-size: 13px;">
                        <small style="color: #64748b; font-size: 11px;">Max 2MB (JPG, PNG)</small>
                    </div>
                    <div class="payment-input-group">
                        <label>Sort Order *</label>
                        <input type="number" name="sort_order" id="modal_sort_order" value="0" required min="0">
                        <span class="error-msg-text" id="error-modal_sort_order">Required</span>
                    </div>
                </div>

                <!-- Status -->
                <div class="modal-section-header">
                    <i class="fas fa-toggle-on"></i>
                    <span>Customer Status</span>
                </div>
                <div style="margin-bottom: 20px;">
                    <?php
                        $current_status = '1';
                        $status_title = 'Customer';
                        $card_id = 'modal-customer-status-card';
                        $label_id = 'modal-customer-status-label';
                        $input_id = 'modal_customer_status';
                        $toggle_id = 'modal-customer-status-toggle';
                        include('../includes/status_card.php');
                    ?>
                </div>

                <div style="display: flex; justify-content: center; margin-top: 10px;">
                    <button type="submit" style="
                        background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
                        color: white;
                        border: none;
                        padding: 12px 40px;
                        border-radius: 50px;
                        font-weight: 700;
                        font-size: 15px;
                        cursor: pointer;
                        display: flex;
                        align-items: center;
                        gap: 10px;
                        box-shadow: 0 4px 15px rgba(13, 148, 136, 0.3);
                        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                        position: relative;
                        overflow: hidden;
                    " onmouseover="this.style.transform='translateY(-2px) scale(1.02)'; this.style.boxShadow='0 8px 20px rgba(13, 148, 136, 0.4)';" onmouseout="this.style.transform='translateY(0) scale(1)'; this.style.boxShadow='0 4px 15px rgba(13, 148, 136, 0.3)';">
                        <i class="fas fa-save" style="font-size: 18px;"></i>
                        <span>Save Customer</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Product Modal -->
<!-- Add Product Modal -->
<div class="pos-modal" id="addProductModal">
    <div class="pos-modal-content" style="max-width: 900px; max-height: 90vh; display: flex; flex-direction: column;">
        <div class="pos-modal-header">
            <h3><i class="fas fa-user-plus"></i> Quick Add Product</h3>
            <button class="close-btn" onclick="closeModal('addProductModal')" style="display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-times" style="font-size: 16px;"></i>
            </button>
        </div>
        <div class="pos-modal-body no-scrollbar" style="overflow-y: auto; flex: 1; padding: 20px;">
            <form id="quickProductForm" enctype="multipart/form-data" onsubmit="handleQuickAddProduct(event)">
                
                <!-- Basic Info -->
                <div class="section-title text-sm font-bold text-teal-700 mb-2 border-b border-teal-100 pb-1">Basic Information</div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div class="payment-input-group">
                        <label>Product Name *</label>
                        <input type="text" name="product_name" required placeholder="Enter product name">
                    </div>
                    <div class="payment-input-group">
                        <label>Product Code *</label>
                        <div class="flex">
                            <input type="text" name="product_code" id="modal_product_code" required placeholder="SKU / Barcode" class="rounded-r-none">
                            <button type="button" onclick="generateModalCode()" class="bg-teal-700 text-white px-3 rounded-r-lg hover:bg-teal-800"><i class="fas fa-random"></i></button>
                        </div>
                    </div>
                    <div class="payment-input-group">
                        <label>Barcode Symbology</label>
                        <select name="barcode_symbology">
                            <option value="code128">Code 128</option>
                            <option value="code39">Code 39</option>
                            <option value="ean13">EAN-13</option>
                            <option value="upc">UPC</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div class="payment-input-group">
                        <label>Category *</label>
                        <select name="category_id" required>
                            <option value="">Select Category</option>
                            <?php 
                            mysqli_data_seek($categories_result, 0);
                            while($cat = mysqli_fetch_assoc($categories_result)): 
                            ?>
                                <option value="<?= $cat['id']; ?>"><?= htmlspecialchars($cat['name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="payment-input-group">
                        <label>Brand</label>
                        <select name="brand_id">
                            <option value="">Select Brand</option>
                            <?php while($brand = mysqli_fetch_assoc($brands)): ?>
                                <option value="<?= $brand['id']; ?>"><?= htmlspecialchars($brand['name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="payment-input-group">
                        <label>Unit *</label>
                        <select name="unit_id" required>
                            <option value="">Select Unit</option>
                            <?php while($unit = mysqli_fetch_assoc($units)): ?>
                                <option value="<?= $unit['id']; ?>"><?= htmlspecialchars($unit['name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <!-- Pricing -->
                <div class="section-title text-sm font-bold text-teal-700 mb-2 border-b border-teal-100 pb-1 mt-4">Pricing & Tax</div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                    <div class="payment-input-group">
                        <label>Purchase Price</label>
                        <input type="number" name="purchase_price" id="modal_purchase_price" step="0.01" placeholder="0.00" oninput="calculateModalPrices()">
                    </div>
                    <div class="payment-input-group">
                        <label>Tax Rate</label>
                        <select name="tax_rate_id" id="modal_tax_rate_id" onchange="calculateModalPrices()">
                            <option value="" data-rate="0">No Tax</option>
                            <?php while($tax = mysqli_fetch_assoc($taxes)): ?>
                                <option value="<?= $tax['id']; ?>" data-rate="<?= $tax['rate']; ?>"><?= htmlspecialchars($tax['name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="payment-input-group">
                        <label>Tax Method</label>
                        <select name="tax_method" id="modal_tax_method" onchange="calculateModalPrices()">
                            <option value="exclusive">Exclusive</option>
                            <option value="inclusive">Inclusive</option>
                        </select>
                    </div>
                    <div class="payment-input-group">
                        <label>Selling Price *</label>
                        <input type="number" name="selling_price" id="modal_selling_price" required step="0.01" placeholder="0.00" class="font-bold text-teal-700">
                    </div>
                </div>

                <!-- Inventory & Extras -->
                <div class="section-title text-sm font-bold text-teal-700 mb-2 border-b border-teal-100 pb-1 mt-4">Inventory & Details</div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div class="payment-input-group">
                        <label>Opening Stock</label>
                        <input type="number" name="opening_stock" value="0">
                    </div>
                    <div class="payment-input-group">
                        <label>Alert Quantity</label>
                        <input type="number" name="alert_quantity" value="5">
                    </div>
                     <div class="payment-input-group">
                        <label>Product Image</label>
                        <input type="file" name="product_image" accept="image/*" class="text-xs">
                    </div>
                </div>

                 <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div class="payment-input-group">
                        <label>Description</label>
                        <textarea name="description" rows="2" class="w-full border rounded p-2 text-sm" placeholder="Product details..."></textarea>
                    </div>
                     <div class="payment-input-group">
                        <label>Expire Date</label>
                        <input type="date" name="expire_date">
                    </div>
                </div>

                <div class="sticky bottom-0 bg-white pt-4 border-t mt-4 flex justify-center gap-3">
                    <button type="button" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300" onclick="closeModal('addProductModal')">Cancel</button>
                    <button type="submit" class="complete-btn" style="width: auto; padding: 10px 24px;">
                        <i class="fas fa-save"></i> Save Product
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- Add Giftcard Modal -->
<div class="pos-modal" id="giftcardModal">
    <div class="pos-modal-content" style="max-width: 1000px; max-height: 90vh; overflow-y: auto;">
        <div class="pos-modal-header">
            <h3><i class="fas fa-user-plus"></i> Quick Add Giftcard</h3>
            <button class="close-btn" onclick="closeModal('giftcardModal')" style="display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-times" style="font-size: 16px;"></i>
            </button>
        </div>
        <div class="pos-modal-body no-scrollbar" style="padding: 20px;">
            <form id="quickGiftcardForm" enctype="multipart/form-data">
                
                <!-- Card Information Section -->
                <div class="modal-section-header">
                    <i class="fas fa-credit-card"></i>
                    <span>Card Information</span>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                    <div class="payment-input-group">
                        <label>Card Number *</label>
                        <div style="display: flex; gap: 8px;">
                            <input type="text" name="card_no" id="modal_card_no" required placeholder="Scan or Enter Code" style="flex: 1;" maxlength="64">
                            <button type="button" onclick="generateModalCardNumber()" class="bg-teal-700 hover:bg-teal-800 text-white px-3 rounded-lg transition-all" title="Generate Card Number">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                        <span class="error-msg-text" id="error-modal_card_no">Card Number is required</span>
                    </div>

                    <div class="payment-input-group" style="position: relative;">
                        <label>Customer</label>
                        <input type="text" id="modal_customer_search" placeholder="Search customer..." autocomplete="off">
                        <input type="hidden" name="customer_id" id="modal_customer_id">
                        <!-- Customer Dropdown -->
                        <div id="modal_customer_dropdown" class="absolute left-0 right-0 mt-1 bg-white border border-teal-500 rounded-lg overflow-y-auto hidden shadow-2xl z-[9999]" style="position: absolute; width: 100%; max-height: 200px;">
                            <?php 
                            mysqli_data_seek($customers_result, 0);
                            while($customer = mysqli_fetch_assoc($customers_result)): 
                            ?>
                                <div class="modal-customer-option px-4 py-3 hover:bg-teal-50 cursor-pointer transition-colors border-b border-slate-100 last:border-b-0" 
                                     data-id="<?= $customer['id']; ?>" 
                                     data-name="<?= htmlspecialchars($customer['name']); ?>">
                                    <span class="font-medium text-slate-800"><?= htmlspecialchars($customer['name']); ?></span>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                    <div class="payment-input-group">
                        <label>Card Value (USD) *</label>
                        <input type="number" step="0.01" name="value" id="modal_value" required placeholder="0.00" min="0">
                        <span class="error-msg-text" id="error-modal_value">Card Value is required</span>
                    </div>

                    <div class="payment-input-group">
                        <label>Current Balance (USD)</label>
                        <input type="number" step="0.01" name="balance" id="modal_balance" placeholder="0.00" min="0" readonly style="background: #f1f5f9;">
                        <small style="color: #64748b; font-size: 11px;">Auto-set to card value</small>
                    </div>

                    <div class="payment-input-group">
                        <label>Expiry Date *</label>
                        <input type="date" name="expiry_date" id="modal_expiry_date" required>
                        <span class="error-msg-text" id="error-modal_expiry_date">Expiry Date is required</span>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 20px;">
                    <div class="payment-input-group">
                        <label>Card Logo</label>
                        <input type="file" name="image" id="modal_image" accept="image/*" style="font-size: 13px;">
                        <small style="color: #64748b; font-size: 11px;">Max 2MB (JPG, PNG)</small>
                    </div>

                    <!-- Status Card -->
                    <div>
                        <?php
                            $current_status = '1';
                            $status_title = 'Giftcard';
                            $card_id = 'modal-giftcard-status-card';
                            $label_id = 'modal-giftcard-status-label';
                            $input_id = 'modal-giftcard-status-input';
                            $toggle_id = 'modal-giftcard-status-toggle';
                            include('../includes/status_card.php');
                        ?>
                    </div>
                </div>

                <!-- Card Preview -->
                <div class="modal-section-header">
                    <i class="fas fa-eye"></i>
                    <span>Card Preview</span>
                </div>
                <div class="bg-gradient-to-br from-emerald-600 to-teal-700 rounded-2xl p-6 text-white relative overflow-hidden" style="margin-bottom: 20px;">
                    <div class="relative z-10">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 40px;">
                            <div>
                                <p style="font-size: 12px; opacity: 0.8;">Giftcard</p>
                                <p id="modal-preview-card-no" style="font-size: 16px; font-weight: bold; margin-top: 4px;">••••••••••••••••</p>
                            </div>
                            <div id="modal-preview-logo" style="width: 48px; height: 48px; background: rgba(255,255,255,0.1); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-image" style="opacity: 0.5;"></i>
                            </div>
                        </div>
                        <div style="text-align: center; margin-bottom: 30px;">
                            <p id="modal-preview-value" style="font-size: 32px; font-weight: bold;">USD 0.00</p>
                            <p style="font-size: 10px; opacity: 0.8; margin-top: 4px; text-transform: uppercase; letter-spacing: 1px;">Card Balance</p>
                        </div>
                        <div style="background: white; border-radius: 8px; padding: 12px; margin: 0 auto; width: 60%; height: 80px; display: flex; align-items: center; justify-content: center;">
                            <svg id="modal-barcode-container" width="100%" height="60" style="display: block;"></svg>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-top: 20px;">
                            <div>
                                <p style="font-size: 10px; opacity: 0.8;">Card Holder</p>
                                <p style="font-weight: 600;">GiftCard</p>
                            </div>
                            <div style="text-align: right;">
                                <p style="font-size: 10px; opacity: 0.8;">Expiry</p>
                                <p id="modal-preview-expiry" style="font-weight: 600;">MM/YY</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div style="display: flex; gap: 12px; justify-content: center;">
                    <!-- <button type="button" onclick="closeModal('giftcardModal')" style="padding: 12px 24px; background: #e2e8f0; color: #475569; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                        <i class="fas fa-times"></i> Cancel
                    </button> -->
                    <button type="submit" class="complete-btn" style="width: auto; padding: 12px 24px;">
                        <i class="fas fa-save"></i> Save Gift Card
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- Invoice Modal -->
<?php include('../includes/invoice_modal.php'); ?>

<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
<script src="/pos/assets/js/invoice_modal.js"></script>
<script src="/pos/assets/js/payment_logic.js"></script>
<script>
    const stores = <?= json_encode($stores); ?>;
</script>
<script src="/pos/assets/js/pos.js"></script>

