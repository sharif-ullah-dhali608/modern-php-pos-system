<?php
include '../config/dbcon.php';

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$items_per_page = 20;
$offset = ($page - 1) * $items_per_page;
$store_id = isset($_GET['store_id']) ? (int)$_GET['store_id'] : 0;

$where_clause = "";
$join_clause = "";

if($store_id > 0) {
    $join_clause = " JOIN product_store_map psm ON p.id = psm.product_id ";
    $where_clause = " WHERE psm.store_id = $store_id ";
}

$products = [];
$products_query = "SELECT p.*, c.name as category_name, u.unit_name as unit_name 
                  FROM products p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  LEFT JOIN units u ON p.unit_id = u.id 
                  $join_clause
                  $where_clause
                  ORDER BY p.id DESC 
                  LIMIT $offset, $items_per_page";
$products_result = mysqli_query($conn, $products_query);

if(mysqli_num_rows($products_result) > 0){
    while($item = mysqli_fetch_assoc($products_result)){
        $products[] = $item;
    }
}

// Total products for pagination calculation
$total_products_query = "SELECT COUNT(*) as total FROM products p $join_clause $where_clause";
$total_products_result = mysqli_query($conn, $total_products_query);
$total_products = mysqli_fetch_assoc($total_products_result)['total'];
$total_pages = ceil($total_products / $items_per_page);

$response = [
    'html' => '',
    'pagination_html' => ''
];

// Generate Product Grid HTML
ob_start();
foreach($products as $product): ?>
    <div class="product-card" onclick="addToCart(<?= $product['id']; ?>, '<?= htmlspecialchars($product['product_name'], ENT_QUOTES); ?>', <?= $product['selling_price']; ?>)">
        <?php if(!empty($product['image']) && file_exists("../uploads/".$product['image'])): ?>
            <img src="../uploads/<?= $product['image']; ?>" alt="<?= htmlspecialchars($product['product_name']); ?>">
        <?php else: ?>
            <img src="../assets/images/no-image.png" alt="No Image">
        <?php endif; ?>
        <div class="info">
            <div class="name"><?= htmlspecialchars($product['product_name']); ?></div>
            <div class="price">৳<?= number_format($product['selling_price'], 2); ?></div>
            <div class="stock">Stock: <?= $product['opening_stock']; ?> <?= $product['unit_name'] ?? 'pcs'; ?></div>
        </div>
        <button class="add-btn" onclick="event.stopPropagation(); addToCart(<?= $product['id']; ?>, '<?= htmlspecialchars($product['product_name'], ENT_QUOTES); ?>', <?= $product['selling_price']; ?>)">
            <i class="fas fa-cart-plus"></i> Add To Cart
        </button>
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
