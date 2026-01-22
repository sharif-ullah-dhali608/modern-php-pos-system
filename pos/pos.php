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





// Fetch active stores with currency symbols and names
$stores_query = "SELECT s.*, c.symbol_left, c.symbol_right, c.currency_name as currency_full_name 
                FROM stores s 
                LEFT JOIN currencies c ON s.currency_id = c.id 
                WHERE s.status=1 ORDER BY s.store_name ASC";
$stores_result = mysqli_query($conn, $stores_query);
$stores = [];
while($store = mysqli_fetch_assoc($stores_result)) {
    // Determine the symbol
    $store['currency_symbol'] = $store['symbol_left'] ?: ($store['symbol_right'] ?: '৳');
    $stores[] = $store;
}
$current_store = $stores[0] ?? ['id' => 1, 'store_name' => 'Default Store'];

// --- NEW: Fetch Store Currency ---
$currency_symbol = "৳"; // Default
$currency_code = "USD";
$currency_name = "Taka";
if(!empty($current_store['currency_id'])) {
    $curr_q = mysqli_query($conn, "SELECT * FROM currencies WHERE id = '{$current_store['currency_id']}'");
    if($curr = mysqli_fetch_assoc($curr_q)) {
        $currency_symbol = $curr['symbol_left'] ? $curr['symbol_left'] : $curr['symbol_right'];
        $currency_code = $curr['code'];
        $currency_name = $curr['currency_name'];
    }
}

// ---------------------------------

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
$products_query = "SELECT p.*, c.name as category_name, u.unit_name,
                   GREATEST(0, COALESCE(SUM(psm.stock), 0)) as store_stock
                   FROM products p 
                   LEFT JOIN categories c ON p.category_id = c.id 
                   LEFT JOIN units u ON p.unit_id = u.id 
                   LEFT JOIN product_store_map psm ON p.id = psm.product_id
                   WHERE p.status = 1 
                   GROUP BY p.id
                   ORDER BY p.product_name ASC 
                   LIMIT $items_per_page OFFSET $offset";
$products_result = mysqli_query($conn, $products_query);

// Fetch categories for filter
$categories_query = "SELECT id, name FROM categories WHERE status=1 ORDER BY name ASC";
$categories_result = mysqli_query($conn, $categories_query);

// Fetch customers with credit, giftcard info, and installment details
$customers_query = "SELECT c.id, c.name, c.mobile, c.current_due as credit, c.opening_balance, c.has_installment,
                    COALESCE(SUM(g.balance), 0) as giftcard_balance,
                    (SELECT SUM(ip.due) FROM installment_payments ip 
                     JOIN selling_info si ON ip.invoice_id = si.invoice_id 
                     WHERE si.customer_id = c.id AND ip.payment_status = 'due') as installment_due
                    FROM customers c
                    LEFT JOIN giftcards g ON c.id = g.customer_id AND g.status = 1
                    WHERE c.status=1 
                    GROUP BY c.id, c.name, c.mobile, c.current_due, c.opening_balance, c.has_installment
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

    /* Invisible Scrollbar but scrollable */
    .invisible-scrollbar::-webkit-scrollbar {
        display: none;
    }
    .invisible-scrollbar {
        -ms-overflow-style: none; /* IE and Edge */
        scrollbar-width: none; /* Firefox */
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
                <a href="/pos"><i class="fas fa-home "></i> DASHBOARD</a>
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
                        $stock = floatval($product['store_stock']);
                        $alert_qty = floatval($product['alert_quantity'] ?? 5);
                        $is_out_of_stock = $stock <= 0;
                        $is_low_stock = $stock > 0 && $stock <= $alert_qty;
                    ?>
                        <div class="product-card <?= $is_out_of_stock ? 'out-of-stock-card' : '' ?>" data-id="<?= $product['id']; ?>" data-name="<?= htmlspecialchars($product['product_name']); ?>" data-price="<?= $product['selling_price']; ?>" data-category="<?= $product['category_id']; ?>" data-stock="<?= $stock; ?>" onclick="openProductDetails(<?= $product['id']; ?>)" style="cursor: pointer;">
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
                                <div class="price"><?= $currency_symbol; ?><?= number_format($product['selling_price'], 2); ?></div>
                                <div class="stock">Stock: <?= number_format($stock, 0); ?></div>
                            </div>
                            <?php if($is_out_of_stock): ?>
                                <button class="add-btn out-of-stock-btn" disabled>
                                    <i class="fas fa-ban"></i> Out of Stock
                                </button>
                            <?php else: ?>
                                <button class="add-btn" onclick="openProductDetails(<?= $product['id']; ?>)">
                                    <i class="fas fa-eye"></i> View Details
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
                        <span id="grand-total"><?= $currency_symbol; ?>0.00</span>
                    </div>
                </div>
                
                <!-- Footer Actions -->
                <div class="pos-footer">
                    <div class="total-display">
                        <div class="label">TOTAL</div>
                        <div class="amount" id="footer-total"><?= $currency_symbol; ?>0.00</div>
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



<!-- Product Details Modal (Unique Design) -->
<style>
    .pd-grid-layout {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
        align-items: start;
    }
    @media (max-width: 768px) {
        .pd-grid-layout {
            grid-template-columns: 1fr;
            gap: 20px;
        }
        #productDetailsModal .pos-modal-content {
            height: 95vh;
            max-height: 95vh;
        }

            /* Mobile Layout Override: Strict Grid (No Scroll) */
            @media (max-width: 480px) {
                .pd-gallery-wrapper {
                    position: static !important;
                    top: auto !important;
                }

                .pd-mobile-scroll-container {
                    display: block !important;
                    overflow: visible !important;
                    padding: 0;
                    margin: 0;
                }
                
                #pd-main-image-container {
                    width: 100% !important;
                    flex: none !important;
                    height: auto !important;
                    aspect-ratio: 1/1;
                    margin-bottom: 15px !important;
                }
                
                /* Gallery Grid 4 Cols */
                #pd-gallery {
                    display: grid !important;
                    grid-template-columns: repeat(4, 1fr) !important;
                    gap: 10px !important;
                    overflow: visible !important;
                    white-space: normal !important;
                    padding-bottom: 0 !important;
                    justify-content: start !important;
                }
                
                #pd-gallery img {
                    width: 100% !important;
                    height: auto !important;
                    aspect-ratio: 1/1;
                    flex: none !important;
                    margin: 0 !important;
                }
                
                #pd-gallery button { display: none !important; }

            /* --- NEW MOBILE LAYOUT OVERRIDES --- */
            /* Force rows on mobile (overrides pos.css global stacking) */
            .pd-mobile-row {
                flex-direction: row !important;
                align-items: center !important;
                gap: 10px !important;
                flex-wrap: nowrap !important;
            }

            /* Header specific: ensure justification works */
            .pd-header-mobile.pd-mobile-row {
                justify-content: space-between !important;
            }

            /* Extra Info: strict 2 Cols (Match Image) */
            .pd-extra-info-grid {
                grid-template-columns: 1fr 1fr !important;
                gap: 10px !important;
                font-size: 10px !important;
            }
            
            /* Cart Actions: Stacked 100% width */
            .pd-cart-actions.pd-mobile-row {
                flex-direction: column !important;
                width: 100%;
                gap: 10px !important;
                margin-bottom: 25px !important;
            }
            .pd-cart-actions > div, 
            .pd-cart-actions > button {
                width: 100% !important;
                justify-content: center;
            }
            .pd-cart-actions > div {
                display: flex !important;
                flex-direction: row !important;
                align-items: center !important;
                justify-content: space-between !important; 
                padding: 10px !important;
            }

            /* Stock Info: Flex Row (Name Left, Qty/Loc Right) */
            .pd-stock-row {
                display: flex !important;
                justify-content: space-between !important;
                align-items: center !important;
                border-bottom: 1px dashed #f1f5f9;
                padding: 10px 0;
                width: 100%;
                gap: 10px;
            }
            /* Reset Grid Children Styles */
            .pd-stock-row > * {
                display: block !important; 
                width: auto !important;
            }
        }
    }
</style>
<div class="pos-modal" id="productDetailsModal" style="display: none; align-items: center; justify-content: center; backdrop-filter: blur(8px); background: rgba(15, 23, 42, 0.7); padding: 10px;">
    <div class="pos-modal-content" style="width: 95%; max-width: 900px; max-height: 90vh; border-radius: 24px; overflow: hidden; box-shadow: 0 0 0 1px rgba(255,255,255,0.1), 0 20px 40px -10px rgba(0,0,0,0.5); display: flex; flex-direction: column; background: #fff; margin: auto;">
        
        <!-- Header -->
        <div class="pd-header-mobile pd-mobile-row" style="padding: 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #fff;">
             <div>
                <span id="pd-category" style="font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; color: #0d9488; background: #ecfdf5; padding: 4px 10px; border-radius: 4px;">Category</span>
             </div>
             <button onclick="closeModal('productDetailsModal')" style="width: 32px; height: 32px; background: #f1f5f9; border: none; border-radius: 50%; color: #64748b; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s;">
                <i class="fas fa-times" style="font-size: 14px;"></i>
            </button>
        </div>

         <div class="pos-modal-body no-scrollbar" style="padding: 30px; overflow-y: auto; flex: 1; background: #fff;">
            
            <div class="pd-grid-layout">
                
                <!-- Left Column: Gallery -->
                <div class="pd-gallery-wrapper" style="background: #f8fafc; border-radius: 20px; padding: 20px; display: flex; flex-direction: column; align-items: center; position: sticky; top: 0;">
                     
                     <div class="pd-mobile-scroll-container">
                         <div id="pd-main-image-container" style="width: 100%; aspect-ratio: 1/1; background: white; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); overflow: hidden; display: flex; align-items: center; justify-content: center; position: relative; margin-bottom: 20px;">
                             <img id="pd-image" src="" style="width: 100%; height: 100%; object-fit: cover;">
                             <div id="pd-stock-badge" style="position: absolute; top: 10px; right: 10px; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; color: white; background: #ef4444; display: none;">Out of Stock</div>
                             
                             <!-- Gallery Controls -->
                             <button id="pd-prev-btn" onclick="changeGalleryImage(-1)" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); width: 32px; height: 32px; background: #0d9488; border: 1px solid #0d9488; border-radius: 50%; display: none; align-items: center; justify-content: center; cursor: pointer; color: #ffffff;">
                                <i class="fas fa-chevron-left"></i>
                             </button>
                             <button id="pd-next-btn" onclick="changeGalleryImage(1)" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); width: 32px; height: 32px; background: #0d9488; border: 1px solid #0d9488; border-radius: 50%; display: none; align-items: center; justify-content: center; cursor: pointer; color: #ffffff;">
                                <i class="fas fa-chevron-right"></i>
                             </button>
                        </div>
                        <!-- Thumbnails Strip -->
                        <div id="pd-gallery" style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                            <!-- Thumbnails injected here -->
                        </div>
                    </div>

                </div>

                <!-- Right Column: Details & Extra Info -->
                <div>
                     <h2 id="pd-name" style="font-size: 24px; font-weight: 800; color: #1e293b; margin: 0 0 10px 0; line-height: 1.2;">Product Name</h2>
                     
                     <div class="pd-mobile-row" style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px;">
                        <span id="pd-price" style="font-size: 28px; font-weight: 800; color: #0d9488;"><?= $global_symbol; ?>0.00</span>
                        <span id="pd-unit" style="font-size: 13px; font-weight: 600; color: #94a3b8; background: #f1f5f9; padding: 4px 10px; border-radius: 20px;">Unit</span>
                    </div>

                    <p id="pd-description" style="color: #64748b; font-size: 14px; line-height: 1.6; margin-bottom: 25px; padding-bottom: 25px; border-bottom: 1px dashed #e2e8f0;">
                        Product description...
                    </p>
                    
                    <!-- Extra Info Grid -->
                    <div class="pd-extra-info-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; background: #f8fafc; padding: 15px; border-radius: 12px; margin-bottom: 20px; border: 1px dashed #e2e8f0;">
                       <div>
                           <span style="display: block; font-size: 10px; font-weight: 700; color: #94a3b8; text-transform: uppercase; margin-bottom: 4px;">Brand</span>
                           <span id="pd-brand" style="font-size: 13px; font-weight: 600; color: #334155;">-</span>
                       </div>
                       <div>
                           <span style="display: block; font-size: 10px; font-weight: 700; color: #94a3b8; text-transform: uppercase; margin-bottom: 4px;">Product Code</span>
                           <span id="pd-code" style="font-size: 13px; font-weight: 600; color: #334155;">-</span>
                       </div>
                       <div>
                           <span style="display: block; font-size: 10px; font-weight: 700; color: #94a3b8; text-transform: uppercase; margin-bottom: 4px;">Barcode Type</span>
                           <span id="pd-barcode" style="font-size: 13px; font-weight: 600; color: #334155;">-</span>
                       </div>
                        <div>
                           <span style="display: block; font-size: 10px; font-weight: 700; color: #94a3b8; text-transform: uppercase; margin-bottom: 4px;">Tax</span>
                           <span id="pd-tax" style="font-size: 13px; font-weight: 600; color: #334155;">-</span>
                       </div>
                       <div>
                           <span style="display: block; font-size: 10px; font-weight: 700; color: #94a3b8; text-transform: uppercase; margin-bottom: 4px;">Tax Method</span>
                           <span id="pd-tax-method" style="font-size: 13px; font-weight: 600; color: #334155;">-</span>
                       </div>
                        <div>
                           <span style="display: block; font-size: 10px; font-weight: 700; color: #94a3b8; text-transform: uppercase; margin-bottom: 4px;">Alert Quantity</span>
                           <span id="pd-alert-qty" style="font-size: 13px; font-weight: 600; color: #334155;">-</span>
                       </div>
                    </div>

                    <!-- Stock Info -->
                    <div style="background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 15px; margin-bottom: 25px;">
                        <h4 class="pd-mobile-row" style="margin: 0 0 10px 0; font-size: 13px; font-weight: 700; color: #334155; display: flex; align-items: center; gap: 6px;">
                            <i class="fas fa-warehouse text-teal-600"></i> Stock Availability
                        </h4>
                        <div id="pd-stock-list" class="invisible-scrollbar" style="max-height: 150px; overflow-y: auto;"></div>
                    </div>

                    <!-- Cart Actions -->
                    <div class="pd-cart-actions pd-mobile-row" style="display: flex; gap: 12px; margin-bottom: 30px;">
                         <div style="display: flex; align-items: center; background: #f1f5f9; border-radius: 10px; padding: 4px;">
                            <button onclick="adjustModalQty(-1)" style="width: 36px; height: 36px; border: none; background: white; border-radius: 8px; cursor: pointer; font-size: 14px; color: #334155;">-</button>
                            <input type="number" id="pd-qty-input" value="1" min="1" onclick="this.select()" style="width: 50px; text-align: center; border: none; background: transparent; font-size: 16px; font-weight: 700; outline: none;">
                            <button onclick="adjustModalQty(1)" style="width: 36px; height: 36px; border: none; background: white; border-radius: 8px; cursor: pointer; font-size: 14px; color: #334155;">+</button>
                        </div>
                        <button id="pd-add-btn" onclick="addToCartFromModal()" style="flex: 1; border: none; background: #0d9488; color: white; border-radius: 10px; font-size: 15px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;">
                            <i class="fas fa-cart-shopping"></i> Add to Cart
                        </button>
                    </div>

                    <!-- Related Items (Moved here) -->
                    <div style="border-top: 1px solid #f1f5f9; padding-top: 20px;">
                        <h4 style="font-size: 14px; font-weight: 700; color: #334155; margin-bottom: 15px;">Related Items</h4>
                        <div id="pd-related-list" style="display: flex; flex-direction: column; gap: 12px;">
                            <!-- Related Products Injected Here -->
                             <div style="text-align: center; padding: 20px; color: #94a3b8; font-size: 12px;">Loading...</div>
                        </div>
                    </div>

                </div>
            </div>
         </div>
    </div>
</div>

<!-- Payment Modal -->
<?php include('../includes/payment_modal.php'); ?>

<!-- Hold Order Modal (Premium Teal Redesign) -->
<?php include('../includes/order_hold_modal.php'); ?>




<!-- Fullscreen Lightbox Modal -->
<div class="pos-modal" id="lightboxModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 10000; align-items: center; justify-content: center;">
    
    <!-- Close Button -->
    <button onclick="closeModal('lightboxModal')" style="position: absolute; top: 30px; right: 30px; background: #0d9488; border: none; color: white; width: 40px; height: 40px; border-radius: 50%; font-size: 18px; cursor: pointer; z-index: 10002; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 6px rgba(0,0,0,0.3);">
        <i class="fas fa-times"></i>
    </button>
    
    <!-- Nav Left -->
    <button onclick="changeLightboxImage(-1)" style="position: absolute; left: 5%; background: #0d9488; border: none; color: white; width: 50px; height: 50px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; z-index: 10002; box-shadow: 0 4px 6px rgba(0,0,0,0.3);">
        <i class="fas fa-chevron-left" style="font-size: 20px;"></i>
    </button>
    
    <!-- Image Container (70% Screen) -->
    <div style="width: 70vw; height: 70vh; display: flex; align-items: center; justify-content: center; position: relative; overflow: hidden;">
        <img id="lightbox-image" src="" style="width: 100%; height: 100%; object-fit: contain; transition: transform 0.1s ease-out; cursor: grab;">
        
        <!-- Caption Bar -->
        <div id="lightbox-caption" style="position: absolute; bottom: 0; left: 0; width: 100%; background: rgba(0,0,0,0.7); padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; color: white; backdrop-filter: blur(4px);">
            <span id="lightbox-title" style="font-weight: 600; font-size: 14px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 80%;">Product Name</span>
            <span id="lightbox-counter" style="font-weight: 400; font-size: 12px; opacity: 0.9;">1 of 1</span>
        </div>
    </div>

    <!-- Nav Right -->
    <button onclick="changeLightboxImage(1)" style="position: absolute; right: 5%; background: #0d9488; border: none; color: white; width: 50px; height: 50px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; z-index: 10002; box-shadow: 0 4px 6px rgba(0,0,0,0.3);">
        <i class="fas fa-chevron-right" style="font-size: 20px;"></i>
    </button>
</div>

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
                    $has_installment = intval($customer['has_installment'] ?? 0);
                    $installment_due = floatval($customer['installment_due'] ?? 0);
                ?>
                    <div class="customer-option" onclick="selectCustomer(<?= $customer['id']; ?>, '<?= htmlspecialchars($customer['name'], ENT_QUOTES); ?>', '<?= $customer['mobile']; ?>', <?= $credit; ?>, <?= $gc_balance; ?>, <?= $opening_bal; ?>, <?= $has_installment; ?>)" 
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
                            <?php if($has_installment == 1 && $installment_due > 0): ?>
                                <span style="background: #fef3c7; color: #d97706; padding: 2px 8px; border-radius: 20px; font-size: 10px; font-weight: 600; border: 1px solid #fde68a;">Installment: <?= number_format($installment_due, 2); ?></span>
                            <?php endif; ?>
                            <?php if($credit > 0): ?>
                                <span style="background: #fee2e2; color: #dc2626; padding: 2px 8px; border-radius: 20px; font-size: 10px; font-weight: 600; border: 1px solid #fecaca;">Due: <?= number_format($credit, 2); ?></span>
                            <?php endif; ?>
                            <?php if($gc_balance > 0): ?>
                                <span style="background: #f0fdf4; color: #10b981; padding: 2px 8px; border-radius: 20px; font-size: 10px; font-weight: 600; border: 1px solid #d1fae5;">GiftCard: <?= number_format($gc_balance, 2); ?></span>
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


<!-- Welcome Modal (Informational) -->
<div class="pos-modal" id="welcomeModal" onclick="closeWelcomeModal(); event.preventDefault(); event.stopPropagation();" style="display: flex; align-items: center; justify-content: center; backdrop-filter: blur(2px); background: rgba(0,0,0,0.8); z-index: 9999; padding: 20px; position: fixed !important; top: 0; left: 0; width: 100vw; height: 100vh;">
    <div class="pos-modal-content" onclick="closeWelcomeModal(); event.preventDefault(); event.stopPropagation();" style="width: 95%; max-width: 480px; border-radius: 24px; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); background: #ffffff; animation: modal-pop 0.3s ease-out; margin: auto; cursor: pointer;">
        <div style="padding: 40px; text-align: center;">
            <div style="width: 80px; height: 80px; background: #ccfbf1; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px auto;">
                <i class="fas fa-store" style="font-size: 32px; color: #0d9488;"></i>
            </div>
            
            <h2 style="color: #0f172a; font-size: 24px; font-weight: 800; margin-bottom: 12px; letter-spacing: -0.5px;">Welcome to Point of Sale</h2>
            
            <p style="color: #64748b; font-size: 16px; line-height: 1.6; margin-bottom: 30px;">
                To begin processing sales, please select your 
                <strong style="color: #0d9488;">Store</strong> and 
                <strong style="color: #0d9488;">Customer</strong> 
                from the dashboard.
            </p>
            <div style="background: #f8fafc; border-radius: 16px; padding: 20px; text-align: left; margin-bottom: 30px; border: 1px dashed #cbd5e1;">
                <div class="welcome-step-item">
                    <div style="width: 24px; height: 24px; background: #0d9488; border-radius: 50%; color: white; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold; flex-shrink: 0;">
                        <i class="fas fa-store" style="font-size: 10px;"></i>
                    </div>
                    <span class="welcome-step-text">Select active store from top menu</span>
                </div>
                <div class="welcome-step-item" style="margin-bottom: 0;">
                     <div style="width: 24px; height: 24px; background: #0d9488; border-radius: 50%; color: white; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold; flex-shrink: 0;">
                        <i class="fas fa-user" style="font-size: 10px;"></i>
                     </div>
                    <span class="welcome-step-text">Choose "Walking Customer" or find one</span>
                </div>
            </div>

            <button onclick="closeWelcomeModal(); event.stopPropagation();" style="width: 100%; padding: 16px; background: #0d9488; color: white; border: none; border-radius: 16px; font-size: 16px; font-weight: 700; cursor: pointer; transition: transform 0.1s, background 0.2s; margin-top: 10px;" onmouseover="this.style.background='#0f766e'" onmouseout="this.style.background='#0d9488'">
                 Dismiss & Start Selling
            </button>
            
            <p style="margin-top: 20px; font-size: 12px; color: #94a3b8;">
                Press <span style="background: #e2e8f0; padding: 2px 6px; border-radius: 4px; font-weight: 600;">ESC</span> or <span style="background: #e2e8f0; padding: 2px 6px; border-radius: 4px; font-weight: 600;">ENTER</span> to close
            </p>
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
    window.currencySymbol = "<?= $currency_symbol; ?>"; // Dynamic Currency for JS
    window.currencyName = "<?= $currency_name; ?>"; // Dynamic Currency Name for JS
</script>
<script src="/pos/assets/js/pos.js"></script>

