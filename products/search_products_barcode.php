<?php
session_start();
include('../config/dbcon.php');

if (!isset($_SESSION['auth'])) {
    exit('Unauthorized');
}

$q = isset($_GET['q']) ? mysqli_real_escape_string($conn, $_GET['q']) : '';
$store_id = isset($_GET['store_id']) ? (int)$_GET['store_id'] : (int)($_SESSION['store_id'] ?? 0);

if ($store_id <= 0) {
    echo '<div class="p-4 text-center text-rose-500 font-bold text-xs uppercase tracking-widest">Store Access Required</div>';
    exit;
}

// Fetch products based on search query (name or code)
// Limit to 5 initial results as per user request if no query, else show matches
$limit = empty($q) ? "LIMIT 5" : "LIMIT 15";
$where = !empty($q) ? "AND (p.product_name LIKE '%$q%' OR p.product_code LIKE '%$q%')" : "";

$query = "SELECT p.id, p.product_name as name, p.product_code as code, p.thumbnail as image, 
                 c.name as category_name, u.unit_name as unit_name, p.selling_price as price,
                 psm.stock as total_qty
          FROM products p
          INNER JOIN product_store_map psm ON p.id = psm.product_id AND psm.store_id = '$store_id'
          LEFT JOIN categories c ON p.category_id = c.id
          LEFT JOIN units u ON p.unit_id = u.id
          WHERE p.status = '1' $where 
          ORDER BY p.product_name ASC $limit";

$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $img = !empty($row['image']) ? $row['image'] : '/pos/assets/images/no-image.png';
        $stock = number_format($row['total_qty'] ?? 0, 0);
        ?>
        <div class="product-option px-4 py-3 hover:bg-teal-50 cursor-pointer transition-colors border-b border-slate-100 last:border-b-0 flex items-center gap-3" 
             data-id="<?= $row['id']; ?>" 
             data-name="<?= htmlspecialchars($row['name']); ?>"
             data-code="<?= htmlspecialchars($row['code']); ?>"
             data-price="<?= htmlspecialchars($row['price']); ?>"
             data-image="<?= htmlspecialchars($img); ?>"
             data-category="<?= htmlspecialchars($row['category_name'] ?? 'General'); ?>"
             data-unit="<?= htmlspecialchars($row['unit_name'] ?? 'Piece'); ?>"
             data-stock="<?= $row['total_qty'] ?? 0; ?>">
            <div class="relative w-10 h-10 rounded-lg bg-slate-100 shrink-0 border border-slate-200">
                <img src="<?= $img; ?>" class="w-full h-full object-cover rounded-lg">
                <div class="absolute -top-2 -right-2 bg-black text-white text-[8px] font-bold px-1.5 py-0.5 rounded-full shadow-lg border border-white/20">
                    <?= $stock; ?>
                </div>
            </div>
            <div class="flex-1">
                <div class="font-bold text-slate-800 text-sm leading-tight"><?= htmlspecialchars($row['name']); ?></div>
                <div class="flex items-center gap-2 mt-0.5">
                    <span class="text-[9px] font-black text-teal-600 uppercase tracking-tighter bg-teal-50 px-1.5 py-0.5 rounded">Code: <?= htmlspecialchars($row['code']); ?></span>
                    <span class="text-[9px] font-bold text-slate-400"><?= htmlspecialchars($row['category_name'] ?? 'General'); ?></span>
                </div>
            </div>
            <div class="text-right">
                <div class="text-[10px] font-black text-slate-900"><?= number_format($row['price'], 2); ?></div>
                <div class="text-[8px] font-bold text-slate-400 uppercase tracking-widest"><?= htmlspecialchars($row['unit_name'] ?? 'Unit'); ?></div>
            </div>
        </div>
        <?php
    }
} else {
    echo '<div class="p-6 text-center">
            <i class="fas fa-search text-slate-200 text-3xl mb-2"></i>
            <div class="text-slate-400 text-sm font-bold">No products found matching "'.$q.'"</div>
          </div>';
}
?>
