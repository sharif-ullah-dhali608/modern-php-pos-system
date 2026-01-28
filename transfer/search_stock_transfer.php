<?php
session_start();
include('../config/dbcon.php');

if (!isset($_SESSION['auth'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$q = isset($_GET['q']) ? mysqli_real_escape_string($conn, $_GET['q']) : '';
$store_id = isset($_GET['store_id']) ? mysqli_real_escape_string($conn, $_GET['store_id']) : 0;

if (!$store_id) {
    echo '<div class="p-4 text-center text-rose-500 font-bold">Please select a source store first!</div>';
    exit;
}

$query = "SELECT p.id, p.product_name, p.product_code, psm.stock 
          FROM products p
          JOIN product_store_map psm ON p.id = psm.product_id
          WHERE psm.store_id = '$store_id' AND p.status = '1' ";

if ($q !== '') {
    $query .= " AND (p.product_name LIKE '%$q%' OR p.product_code LIKE '%$q%')";
}

$query .= " ORDER BY p.product_name ASC LIMIT 5";

$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        ?>
        <div class="product-option px-6 py-4 hover:bg-teal-50 cursor-pointer transition-all border-b border-slate-50 last:border-b-0 flex items-center justify-between group"
             data-id="<?= $row['id']; ?>"
             data-name="<?= htmlspecialchars($row['product_name']); ?>"
             data-code="<?= htmlspecialchars($row['product_code']); ?>"
             data-stock="<?= $row['stock']; ?>">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-slate-50 flex items-center justify-center text-slate-400 font-black text-xs group-hover:bg-white group-hover:text-teal-600 transition-colors shadow-sm">
                    <i class="fas fa-box"></i>
                </div>
                <div>
                    <div class="font-black text-slate-700 text-sm group-hover:text-teal-900"><?= htmlspecialchars($row['product_name']); ?></div>
                    <div class="text-[10px] text-slate-400 font-bold uppercase tracking-widest"><?= htmlspecialchars($row['product_code']); ?></div>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="text-[10px] text-slate-300 font-bold uppercase tracking-tighter">Stock</div>
                <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-700 font-black text-[10px] group-hover:bg-teal-500 group-hover:text-white transition-all shadow-sm" id="stock_qty_val_<?= $row['id']; ?>">
                    <?= number_format($row['stock'], 0); ?>
                </div>
            </div>
        </div>
        <?php
    }
} else {
    echo '<div class="p-8 text-center">';
    echo '  <div class="text-slate-300 mb-2"><i class="fas fa-search text-3xl"></i></div>';
    echo '  <div class="text-slate-400 font-bold">No products found in this store.</div>';
    echo '</div>';
}
?>
