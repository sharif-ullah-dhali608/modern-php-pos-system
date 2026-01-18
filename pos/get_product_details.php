<?php
session_start();
include('../config/dbcon.php');

header('Content-Type: application/json');

if (!isset($_SESSION['auth'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$store_id = isset($_GET['store_id']) ? $_GET['store_id'] : 'all';

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit;
}

// Fetch product with all related data
$query = "SELECT p.*, 
                 c.name as category_name,
                 b.name as brand_name,
                 u.unit_name,
                 t.name as tax_name,
                 t.taxrate as tax_rate,
                 bx.box_name as location
          FROM products p
          LEFT JOIN categories c ON p.category_id = c.id
          LEFT JOIN brands b ON p.brand_id = b.id
          LEFT JOIN units u ON p.unit_id = u.id
          LEFT JOIN taxrates t ON p.tax_rate_id = t.id
          LEFT JOIN boxes bx ON p.box_id = bx.id
          WHERE p.id = ?";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'i', $product_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit;
}

$product = mysqli_fetch_assoc($result);

// Get product images from product_images table
$images = [];

// First, get all images from product_images table
$gallery_query = "SELECT image_path FROM product_images WHERE product_id = ? ORDER BY sort_order ASC, id ASC";
$gallery_stmt = mysqli_prepare($conn, $gallery_query);
mysqli_stmt_bind_param($gallery_stmt, 'i', $product_id);
mysqli_stmt_execute($gallery_stmt);
$gallery_result = mysqli_stmt_get_result($gallery_stmt);

while ($img_row = mysqli_fetch_assoc($gallery_result)) {
    if (!empty($img_row['image_path'])) {
        $images[] = $img_row['image_path'];
    }
}

// Add thumbnail as the first image if it exists and not already in the array
if (!empty($product['thumbnail']) && !in_array($product['thumbnail'], $images)) {
    array_unshift($images, $product['thumbnail']);
}

// Check for legacy product_image column
if (!empty($product['product_image']) && !in_array($product['product_image'], $images)) {
    $images[] = $product['product_image'];
}

// Check for gallery_images JSON column (legacy support)
if (!empty($product['gallery_images'])) {
    $gallery = json_decode($product['gallery_images'], true);
    if (is_array($gallery)) {
        foreach ($gallery as $img) {
            if (!empty($img) && !in_array($img, $images)) {
                $images[] = $img;
            }
        }
    }
}

// Remove duplicates and empty values
$images = array_unique(array_filter($images));

// If no images found, use default no-image
if (empty($images)) {
    $images = ['/pos/assets/images/no-image.png'];
}

// Get stock info per store using product_store_map table
$stock_info = [];
$stock_query = "SELECT s.id as store_id, s.store_name, 
                       COALESCE(psm.stock, 0) as stock
                FROM stores s
                LEFT JOIN product_store_map psm ON psm.store_id = s.id AND psm.product_id = ?
                WHERE s.status = 1
                ORDER BY s.store_name";

$stock_stmt = mysqli_prepare($conn, $stock_query);
$opening_stock = $product['opening_stock'] ?? 0;
mysqli_stmt_bind_param($stock_stmt, 'i', $product_id);
mysqli_stmt_execute($stock_stmt);
$stock_result = mysqli_stmt_get_result($stock_stmt);

while ($row = mysqli_fetch_assoc($stock_result)) {
    $stock_info[] = $row;
}

// If no stores found, use default stock from opening_stock
if (empty($stock_info)) {
    $stores_query = "SELECT id as store_id, store_name FROM stores WHERE status = 1";
    $stores_result = mysqli_query($conn, $stores_query);
    while ($store = mysqli_fetch_assoc($stores_result)) {
        $stock_info[] = [
            'store_id' => $store['store_id'],
            'store_name' => $store['store_name'],
            'stock' => $product['opening_stock'] ?? 0
        ];
    }
}

// Prepare response
$response = [
    'success' => true,
    'product' => [
        'id' => $product['id'],
        'name' => $product['product_name'],
        'code' => $product['product_code'],
        'barcode_symbology' => $product['barcode_symbology'] ?? 'code128',
        'category' => $product['category_name'] ?? 'Uncategorized',
        'brand' => $product['brand_name'] ?? 'N/A',
        'unit' => $product['unit_name'] ?? 'pcs',
        'purchase_price' => floatval($product['purchase_price'] ?? 0),
        'selling_price' => floatval($product['selling_price']),
        'discount' => floatval($product['discount'] ?? 0),
        'tax_name' => $product['tax_name'] ?? 'No Tax',
        'tax_rate' => floatval($product['tax_rate'] ?? 0),
        'tax_method' => $product['tax_method'] ?? 'exclusive',
        'stock' => floatval($product['opening_stock'] ?? 0),
        'alert_quantity' => floatval($product['alert_quantity'] ?? 5),
        'description' => $product['description'] ?? '',
        'expire_date' => $product['expire_date'] ?? null,
        'status' => $product['status'],
        'created_at' => $product['created_at'] ?? null,
        'images' => array_values($images),
        'location' => $product['location'] ?? '',
        'stock_by_store' => $stock_info
    ],
    'related_products' => []
];

// Fetch Related Products (Same category)
if (!empty($product['category_id'])) {
    $related_query = "SELECT id, product_name, selling_price, thumbnail FROM products WHERE category_id = ? AND id != ? AND status = 1 LIMIT 5";
    $related_stmt = mysqli_prepare($conn, $related_query);
    mysqli_stmt_bind_param($related_stmt, 'ii', $product['category_id'], $product_id);
    mysqli_stmt_execute($related_stmt);
    $related_result = mysqli_stmt_get_result($related_stmt);
    
    while ($related_row = mysqli_fetch_assoc($related_result)) {
        if (empty($related_row['thumbnail'])) {
            $related_row['thumbnail'] = '/pos/assets/images/no-image.png';
        }
        $response['related_products'][] = $related_row;
    }
}


echo json_encode($response);
?>
