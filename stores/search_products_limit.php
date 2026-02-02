<?php
include('../config/dbcon.php');

$q = $_GET['q'] ?? '';
if(strlen($q) < 1) exit;

$q = mysqli_real_escape_string($conn, $q);

$store_id = $_GET['store_id'] ?? 'global';

if($store_id === 'global') {
    $sql = "SELECT id, product_name, product_code, store_stock, per_customer_limit 
        FROM products p 
        LEFT JOIN (SELECT product_id, SUM(stock) as store_stock FROM product_store_map GROUP BY product_id) psm ON p.id = psm.product_id
        WHERE p.product_name LIKE '%$q%' OR p.product_code LIKE '%$q%' LIMIT 20";
} else {
    $store_id = intval($store_id);
    // INNER JOIN: শুধুমাত্র ঐ store-এর products দেখাবে
    $sql = "SELECT p.id, p.product_name, p.product_code, 
            psm.stock as store_stock, 
            psm.per_customer_limit 
        FROM products p 
        INNER JOIN product_store_map psm ON p.id = psm.product_id AND psm.store_id = '$store_id'
        WHERE (p.product_name LIKE '%$q%' OR p.product_code LIKE '%$q%') LIMIT 20";
}

$res = mysqli_query($conn, $sql);

if(mysqli_num_rows($res) > 0) {
    while($row = mysqli_fetch_assoc($res)) {
        ?>
        <tr class="hover:bg-slate-50 transition-colors border-b border-slate-50">
            <td class="p-4 font-medium text-slate-700"><?= htmlspecialchars($row['product_name']); ?></td>
            <td class="p-4 text-slate-500 text-sm"><?= htmlspecialchars($row['product_code']); ?></td>
            <td class="p-4 text-slate-500 text-sm"><?= number_format($row['store_stock'] ?? 0); ?></td>
            <td class="p-4 text-center">
                <input type="number" id="limit_input_<?= $row['id']; ?>" value="<?= $row['per_customer_limit']; ?>" onclick="this.select()" class="no-spin w-24 p-2 border border-slate-300 rounded-lg text-center font-bold text-slate-700 focus:ring-2 focus:ring-teal-500 outline-none" min="0">
            </td>
            <td class="p-4 text-center">
                <button id="save_btn_<?= $row['id']; ?>" onclick="updateLimit(<?= $row['id']; ?>)" class="px-4 py-2 bg-slate-200 hover:bg-teal-500 hover:text-white text-slate-600 rounded-lg font-bold text-xs transition-all">Set</button>
            </td>
        </tr>
        <?php
    }
} else {
    echo '<tr><td colspan="5" class="p-8 text-center text-slate-400">No products found.</td></tr>';
}
?>
