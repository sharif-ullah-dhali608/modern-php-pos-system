<?php
include '../config/dbcon.php';

$page = isset($_REQUEST['page']) ? (int)$_REQUEST['page'] : 1;
$items_per_page = 100;
$offset = ($page - 1) * $items_per_page;
$store_id = isset($_REQUEST['store_id']) ? (int)$_REQUEST['store_id'] : 0;
$category_id = isset($_REQUEST['category_id']) ? (int)$_REQUEST['category_id'] : 0;
$search = isset($_REQUEST['search']) ? mysqli_real_escape_string($conn, trim($_REQUEST['search'])) : '';

$where_conditions = ["p.status = 1"];
$join_clause = "";

// Store filter
if($store_id > 0) {
    $join_clause = " JOIN product_store_map psm ON p.id = psm.product_id ";
    $where_conditions[] = "psm.store_id = $store_id";
}

// Category filter
if($category_id > 0) {
    $where_conditions[] = "p.category_id = $category_id";
}

// Search filter
if(!empty($search)) {
    $where_conditions[] = "(p.product_name LIKE '%$search%' OR p.product_code LIKE '%$search%')";
}

$where_clause = " WHERE " . implode(" AND ", $where_conditions);

// --- NEW: Fetch POS Settings for this store ---
$show_images = '1'; // Default
if($store_id > 0) {
    $s_q = mysqli_query($conn, "SELECT setting_value FROM pos_settings WHERE store_id = '$store_id' AND setting_key = 'show_images'");
    if($s_row = mysqli_fetch_assoc($s_q)) {
        $show_images = $s_row['setting_value'];
    }
} else {
    // If global or no store selected, maybe use default store (id=1) settings or session?
    // For now, let's try to get from session if available, or default to 1
    // Actually, get_products is called with store_id param usually.
    // If store_id is 0 (all stores), we might default to 1 or just show images.
    // Let's assume default store properties for 'all' view or check the first active store.
     $s_q = mysqli_query($conn, "SELECT setting_value FROM pos_settings WHERE store_id = 1 AND setting_key = 'show_images'");
    if($s_row = mysqli_fetch_assoc($s_q)) {
        $show_images = $s_row['setting_value'];
    }
}
// ----------------------------------------------

$products = [];

// Build query based on store selection
if($store_id > 0) {
    // Store-specific: Get stock from product_store_map
    $products_query = "SELECT p.*, c.name as category_name, u.unit_name as unit_name,
                      COALESCE(psm.stock, 0) as store_stock,
                      COALESCE(NULLIF(psm.per_customer_limit, 0), p.per_customer_limit, 0) as effective_limit
                      FROM products p 
                      LEFT JOIN categories c ON p.category_id = c.id 
                      LEFT JOIN units u ON p.unit_id = u.id 
                      JOIN product_store_map psm ON p.id = psm.product_id AND psm.store_id = $store_id
                      $where_clause
                      ORDER BY p.product_name ASC 
                      LIMIT $offset, $items_per_page";
} else {
    // All Stores: Calculate total stock across all stores from product_store_map
    // Use GREATEST to ensure stock is never negative
    // Also get store-wise breakdown for tooltip display
    $products_query = "SELECT p.*, c.name as category_name, u.unit_name as unit_name,
                      GREATEST(0, COALESCE(SUM(psm.stock), 0)) as store_stock,
                      p.per_customer_limit as effective_limit,
                      GROUP_CONCAT(
                          CONCAT(s.store_name, ':', COALESCE(psm.stock, 0))
                          ORDER BY s.store_name
                          SEPARATOR '|'
                      ) as store_breakdown
                      FROM products p 
                      LEFT JOIN categories c ON p.category_id = c.id 
                      LEFT JOIN units u ON p.unit_id = u.id 
                      LEFT JOIN product_store_map psm ON p.id = psm.product_id
                      LEFT JOIN stores s ON psm.store_id = s.id AND s.status = 1
                      $where_clause
                      GROUP BY p.id
                      ORDER BY p.product_name ASC 
                      LIMIT $offset, $items_per_page";
}

$products_result = mysqli_query($conn, $products_query);

if($products_result && mysqli_num_rows($products_result) > 0){
    while($item = mysqli_fetch_assoc($products_result)){
        $products[] = $item;
    }
}

// Total products for pagination calculation
$total_products_query = "SELECT COUNT(*) as total FROM products p $join_clause $where_clause";
$total_products_result = mysqli_query($conn, $total_products_query);
$total_products = mysqli_fetch_assoc($total_products_result)['total'] ?? 0;
$total_pages = max(1, ceil($total_products / $items_per_page));


$response = [
    'html' => '',
    'pagination_html' => ''
];

// Generate Product Grid HTML
ob_start();
foreach($products as $product): 
    $stock = floatval($product['store_stock'] ?? $product['opening_stock']);
    $alert_qty = floatval($product['alert_quantity'] ?? 5);
    $is_out_of_stock = $stock <= 0;
    $is_low_stock = $stock > 0 && $stock <= $alert_qty;
?>
    <div class="product-card clickable-product <?= $is_out_of_stock ? 'out-of-stock-card' : '' ?>" 
         data-id="<?= $product['id']; ?>" 
         data-stock="<?= $stock; ?>"
         data-name="<?= htmlspecialchars($product['product_name']); ?>"
         data-price="<?= $product['selling_price']; ?>"
         data-limit="<?= $product['effective_limit']; ?>"
         style="position: relative;">
        
        <?php if($product['effective_limit'] > 0): ?>
            <div style="position: absolute; top: 0; left: 0; background: #f43f5e; color: white; font-size: 10px; padding: 2px 6px; border-bottom-right-radius: 8px; font-weight: bold; z-index: 5;">
                Limit: <?= $product['effective_limit']; ?>
            </div>
        <?php endif; ?>
        <?php if($is_out_of_stock): ?>
            <div class="stock-badge out-of-stock">Out of Stock</div>
        <?php elseif($is_low_stock): ?>
            <div class="stock-badge low-stock">Low Stock</div>
        <?php endif; ?>
        <?php 
        // Only check and render images if enabled in settings
        if($show_images == '1'):
            // Determine the image to display
            $display_image = '';
            $thumbnail = $product['thumbnail'] ?? '';
            
            // Method 1: Check products.thumbnail column
            if (!empty($thumbnail)) {
                // Handle paths that start with /pos/
                if (strpos($thumbnail, '/pos/') === 0) {
                    $relative_path = '..' . $thumbnail;
                } else {
                    $relative_path = '../' . $thumbnail;
                }
                if (file_exists($relative_path)) {
                    $display_image = $thumbnail;
                }
            }
            
            // Method 2: Check product_images table for first image
            if (empty($display_image)) {
                $img_query = "SELECT image_path FROM product_images WHERE product_id = " . intval($product['id']) . " ORDER BY sort_order ASC, id ASC LIMIT 1";
                $img_result = mysqli_query($conn, $img_query);
                if ($img_result && mysqli_num_rows($img_result) > 0) {
                    $img_row = mysqli_fetch_assoc($img_result);
                    if (!empty($img_row['image_path'])) {
                        $display_image = $img_row['image_path'];
                    }
                }
            }
            
            // Method 3: Check legacy 'image' column
            if (empty($display_image) && !empty($product['image'])) {
                if (file_exists("../uploads/" . $product['image'])) {
                    $display_image = "/pos/uploads/" . $product['image'];
                }
            }
        ?>
            <!-- --- NEW: Fetch Store Currency --- -->
            <?php
            $currency_symbol = "৳"; // Default
            if($store_id > 0) {
                $curr_q = mysqli_query($conn, "SELECT c.symbol_left, c.symbol_right FROM stores s JOIN currencies c ON s.currency_id = c.id WHERE s.id = $store_id");
                if($curr = mysqli_fetch_assoc($curr_q)) {
                    $currency_symbol = $curr['symbol_left'] ? $curr['symbol_left'] : $curr['symbol_right'];
                }
            }
            ?>
            <!-- --------------------------------- -->
            
            <?php if(!empty($display_image)): ?>
                <img src="<?= $display_image; ?>" alt="<?= htmlspecialchars($product['product_name']); ?>">
            <?php else: ?>
                <img src="../assets/images/no-image.png" alt="No Image">
            <?php endif; ?>
        <?php else: ?>
            <!-- Images Hidden, but we still need currency calculation for internal logic if needed -->
             <?php
            $currency_symbol = "৳"; // Default
            if($store_id > 0) {
                $curr_q = mysqli_query($conn, "SELECT c.symbol_left, c.symbol_right FROM stores s JOIN currencies c ON s.currency_id = c.id WHERE s.id = $store_id");
                if($curr = mysqli_fetch_assoc($curr_q)) {
                    $currency_symbol = $curr['symbol_left'] ? $curr['symbol_left'] : $curr['symbol_right'];
                }
            }
            ?>
        <?php endif; // End show_images check ?>

        <div class="info">
            <div class="name"><?= htmlspecialchars($product['product_name']); ?></div>
            <div class="price"><?= $currency_symbol; ?><?= number_format($product['selling_price'], 2); ?></div>
            <div class="stock" style="position: relative;">
                Stock: <?= number_format($stock, 0); ?>
            </div>
        </div>
        <?php if($is_out_of_stock): ?>
            <button class="add-btn out-of-stock-btn" disabled>
                <i class="fas fa-ban"></i> Out of Stock
            </button>
        <?php else: ?>
            <button class="add-btn" onclick="event.stopPropagation(); addToCart(<?= $product['id']; ?>, '<?= htmlspecialchars($product['product_name'], ENT_QUOTES); ?>', <?= $product['selling_price']; ?>)">
                <i class="fas fa-cart-plus"></i> Add To Cart
            </button>
        <?php endif; ?>
    </div>
<?php endforeach;
$response['html'] = ob_get_clean();

// Generate Pagination HTML
ob_start();
?>
    <?php if($page > 1): ?>
        <button onclick="loadProducts(<?= $page - 1; ?>)">
            <i class="fas fa-chevron-left"></i> Previous
        </button>
    <?php else: ?>
        <button disabled><i class="fas fa-chevron-left"></i> Previous</button>
    <?php endif; ?>
    
    <span style="padding: 8px 14px; font-weight: 600; color: #64748b;">
        Page <?= (int)$page; ?> of <?= (int)$total_pages; ?>
    </span>
    
    <?php if($page < $total_pages): ?>
        <button onclick="loadProducts(<?= $page + 1; ?>)">
            Next <i class="fas fa-chevron-right"></i>
        </button>
    <?php else: ?>
        <button disabled>Next <i class="fas fa-chevron-right"></i></button>
    <?php endif; ?>
<?php
$response['pagination_html'] = ob_get_clean();

header('Content-Type: application/json');
echo json_encode($response);
?>
