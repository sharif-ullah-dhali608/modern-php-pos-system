<?php
session_start();
include('../config/dbcon.php');

// Security Check
if(!isset($_SESSION['auth'])){
    header("Location: /pos/login");
    exit(0);
}

// Handle CSV import
if(isset($_POST['import_btn']) && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];
    
    if($file['error'] == 0) {
        $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        if($file_ext == 'csv') {
            $handle = fopen($file['tmp_name'], 'r');
            
            mysqli_begin_transaction($conn);
            try {
                $row_count = 0;
                $success_count = 0;

                while(($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
                    if($row_count == 0) {
                        $row_count++;
                        continue; // Skip header row (product_code, quantity, etc.)
                    }
                    
                    // CSV mapping based on your file:
                    // $data[0] = product_code, $data[1] = quantity, $data[2] = purchase_price
                    if(count($data) >= 2) {
                        $product_code = mysqli_real_escape_string($conn, trim($data[0]));
                        $quantity = floatval($data[1]);
                        $purchase_price = isset($data[2]) ? floatval($data[2]) : 0;
                        $selling_price = isset($data[3]) ? floatval($data[3]) : 0;

                        if($quantity > 0 && !empty($product_code)) {
                            // ১. স্টক আপডেট কোয়েরি
                            $update_query = "UPDATE products SET 
                                            opening_stock = opening_stock + $quantity,
                                            purchase_price = CASE WHEN $purchase_price > 0 THEN $purchase_price ELSE purchase_price END,
                                            selling_price = CASE WHEN $selling_price > 0 THEN $selling_price ELSE selling_price END
                                            WHERE product_code = '$product_code'";
                            
                            $run_update = mysqli_query($conn, $update_query);
                            
                            if(mysqli_affected_rows($conn) > 0) {
                                $success_count++;
                            }
                        }
                    }
                    $row_count++;
                }
                fclose($handle);
                
                if($success_count > 0) {
                    mysqli_commit($conn);
                    $_SESSION['message'] = "Successfully updated stock for $success_count products!";
                    $_SESSION['msg_type'] = "success";
                    header("Location: /pos/purchases/list");
                    exit(0);
                } else {
                    mysqli_rollback($conn);
                    $_SESSION['message'] = "No products found with the provided codes.";
                    $_SESSION['msg_type'] = "error";
                }

            } catch(Exception $e) {
                mysqli_rollback($conn);
                $_SESSION['message'] = "Error: " . $e->getMessage();
                $_SESSION['msg_type'] = "error";
            }
        } else {
            $_SESSION['message'] = "Invalid file format. Please upload a CSV.";
            $_SESSION['msg_type'] = "error";
        }
    } else {
        $_SESSION['message'] = "File upload failed!";
        $_SESSION['msg_type'] = "error";
    }
}

// Handle Export
if(isset($_POST['export_btn'])) {
    $product_type = mysqli_real_escape_string($conn, $_POST['product_type'] ?? 'all');
    $query = "SELECT product_code, product_name, opening_stock, purchase_price, selling_price FROM products WHERE status = 1";
    if($product_type == 'low_stock') {
        $query .= " AND opening_stock <= alert_quantity";
    }
    
    $result = mysqli_query($conn, $query);
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="stock_export_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['product_code', 'quantity', 'purchase_price', 'selling_price', 'tax']);
    
    while($row = mysqli_fetch_assoc($result)) {
        fputcsv($output, [$row['product_code'], $row['opening_stock'], $row['purchase_price'], $row['selling_price'], 0]);
    }
    fclose($output);
    exit;
}

$page_title = "Stock Import - Velocity POS";
include('../includes/header.php');
?>

<div class="app-wrapper">
    <?php include('../includes/sidebar.php'); ?>
    <main id="main-content" class="flex-1 lg:ml-64 flex flex-col h-screen min-w-0 transition-all duration-300">
        <div class="navbar-fixed-top"><?php include('../includes/navbar.php'); ?></div>
        
        <div class="content-scroll-area custom-scroll h-full overflow-y-auto bg-slate-50/50">
            <div class="p-6 max-w-full mx-auto w-full">
                
                <div class="mb-6">
                    <h1 class="text-3xl font-bold text-slate-800 mb-2">Stock Import</h1>
                    <?php if(isset($_SESSION['message'])): ?>
                        <div class="p-4 mb-4 rounded-lg bg-<?= $_SESSION['msg_type'] == 'success' ? 'green' : 'red' ?>-100 text-<?= $_SESSION['msg_type'] == 'success' ? 'green' : 'red' ?>-700">
                            <?= $_SESSION['message']; unset($_SESSION['message']); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-6">
                        <h3 class="text-lg font-bold text-amber-800 mb-2">Instruction</h3>
                        <p class="text-sm text-amber-700">CSV must follow this order: <b>product_code, quantity, purchase_price, selling_price, tax</b></p>
                    </div>
                    
                    <form method="POST" enctype="multipart/form-data" class="space-y-6">
                        <div class="space-y-4">
                            <h3 class="text-lg font-bold text-slate-800">Export Current Stock</h3>
                            <div class="flex items-center gap-4">
                                <select name="product_type" class="flex-1 max-w-xs border border-slate-300 rounded-lg p-3">
                                    <option value="all">All Products</option>
                                    <option value="low_stock">Low Stock</option>
                                </select>
                                <button type="submit" name="export_btn" class="px-6 py-3 bg-teal-600 hover:bg-teal-700 text-white font-semibold rounded-lg flex items-center gap-2">
                                    <i class="fas fa-download"></i> Export CSV
                                </button>
                            </div>
                        </div>
                        
                        <div class="border-t border-slate-200 pt-6">
                            <h3 class="text-lg font-bold text-slate-800 mb-4">Import New Stock</h3>
                            <div class="flex items-center gap-4">
                                <input type="file" name="csv_file" accept=".csv" required class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-teal-50 file:text-teal-700">
                                <button type="submit" name="import_btn" class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg flex items-center gap-2">
                                    <i class="fas fa-upload"></i> Import
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <?php include('../includes/footer.php'); ?>
        </div>
    </main>
</div>

<?php include('../includes/footer_scripts.php'); ?>