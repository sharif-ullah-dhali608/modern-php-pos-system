<?php
session_start();
include('../config/dbcon.php');

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Security Check
if(!isset($_SESSION['auth_user'])){
    header("Location: /pos/signin.php");
    exit(0);
}

// Get user ID
$user_id = $_SESSION['auth_user']['user_id'] ?? $_SESSION['auth_user']['id'] ?? 1;
if(!$user_id) {
    $_SESSION['message'] = "Unable to identify user";
    $_SESSION['msg_type'] = "error";
    header("Location: /pos/giftcard/list");
    exit(0);
}

// save.php file-er shuru-te thakbe:
if(isset($_POST['action']) && $_POST['action'] == 'get_balance') {
    $id = intval($_POST['id']);
    $query = "SELECT value, balance FROM giftcards WHERE id=$id LIMIT 1";
    $result = mysqli_query($conn, $query);
    if($row = mysqli_fetch_assoc($result)) {
        $remaining = floatval($row['value']) - floatval($row['balance']);
        echo json_encode([
            'balance' => number_format($row['balance'], 2),
            'remaining' => number_format($remaining, 2)
        ]);
    }
    exit; // AJAX response er por exit korte hobe
}
// ==========================================
// HANDLE AJAX REQUESTS
// ==========================================
if(isset($_GET['action'])) {
    $action = $_GET['action'];
    $giftcard_id = intval($_GET['id'] ?? 0);
    
    // Get Giftcard Details (for view modal)
    if($action === 'get_details') {
        $query = "SELECT g.*, c.name as customer_name FROM giftcards g LEFT JOIN customers c ON g.customer_id = c.id WHERE g.id=$giftcard_id LIMIT 1";
        $result = mysqli_query($conn, $query);
        
        if(mysqli_num_rows($result) > 0) {
            $card = mysqli_fetch_assoc($result);
            ?>
            <div class="space-y-4">
                <!-- Giftcard Visual Display -->
                <div class="bg-gradient-to-br from-emerald-600 to-teal-700 rounded-2xl p-6 text-white relative overflow-hidden">
                    <div class="absolute top-0 right-0 opacity-10">
                        <i class="fas fa-credit-card text-6xl"></i>
                    </div>
                    <div class="relative z-10">
                        <!-- Card Header -->
                        <div class="flex justify-between items-start mb-8">
                            <div>
                                <p class="text-sm font-semibold opacity-80">Giftcard</p>
                                <p class="text-lg font-bold mt-1"><?= htmlspecialchars($card['card_no']); ?></p>
                            </div>
                            <?php if(!empty($card['image'])): ?>
                                <img src="<?= $card['image']; ?>" alt="Logo" class="w-12 h-12 bg-white/10 rounded-lg flex items-center justify-center object-contain" onerror="this.style.display='none'">
                            <?php endif; ?>
                        </div>

                        <!-- Card Value -->
                        <div class="text-center mb-8">
                            <p class="text-4xl font-bold">USD <?= number_format($card['value'], 2); ?></p>
                            <p class="text-xs opacity-80 mt-2 uppercase tracking-wider">Card Value</p>
                        </div>

                        <!-- Barcode -->
                        <div class="bg-white rounded-lg mb-6 flex items-center justify-center p-2">
                            <svg id="barcode-display-<?= $card['id']; ?>" width="100%" height="50" style="max-width: 100%;"></svg>
                        </div>

                        <!-- Card Footer -->
                        <div class="flex justify-between items-end">
                            <div>
                                <p class="text-xs opacity-80">Card Holder</p>
                                <p class="font-semibold"><?= htmlspecialchars($card['customer_name'] ?? 'GiftCard'); ?></p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs opacity-80">Expiry</p>
                                <p class="font-semibold"><?= htmlspecialchars($card['expiry_date'] ?? 'N/A'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Details Section -->
                <div class="bg-slate-50 rounded-xl p-4 space-y-3">
                    <div class="border-b border-slate-200 pb-2">
                        <p class="text-xs text-slate-500 uppercase font-bold">Current Balance</p>
                        <p class="text-lg font-bold text-emerald-600">USD <?= number_format($card['balance'], 2); ?></p>
                    </div>
                    
                    <div class="border-b border-slate-200 pb-2">
                        <p class="text-xs text-slate-500 uppercase font-bold">Status</p>
                        <p class="text-sm">
                            <?php 
                            if($card['status'] == 1) {
                                echo '<span class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs font-bold bg-emerald-100 text-emerald-700"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Active</span>';
                            } else {
                                echo '<span class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs font-bold bg-amber-100 text-amber-700"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>Inactive</span>';
                            }
                            ?>
                        </p>
                    </div>
                </div>
            </div>

            <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
            <script>
                JsBarcode("#barcode-display-<?= $card['id']; ?>", "<?= htmlspecialchars($card['card_no']); ?>", {
                    format: "CODE128",
                    width: 2,
                    height: 50,
                    displayValue: false,
                    margin: 0
                });
            </script>
            <?php
        }
        exit(0);
    }
    
    // Get Giftcard Balance (for topup modal)
    if($action === 'get_balance') {
        $query = "SELECT value, balance FROM giftcards WHERE id=$giftcard_id LIMIT 1";
        $result = mysqli_query($conn, $query);
        
        if(mysqli_num_rows($result) > 0) {
            $card = mysqli_fetch_assoc($result);
            $remaining = $card['value'] - $card['balance'];
            echo json_encode([
                'balance' => number_format($card['balance'], 2),
                'remaining' => number_format($remaining, 2)
            ]);
        }
        exit(0);
    }
}

// ==========================================
// HANDLE TOPUP REQUEST
// ==========================================
// Topup processing logic
// save.php logic
if(isset($_POST['topup_giftcard_btn'])) {
    $id = intval($_POST['giftcard_id']);
    $amount = floatval($_POST['topup_amount']);
    
    // Sudhu balance noy, card-er total value-keo update korun jate limit na atkay
    $query = "UPDATE giftcards SET 
              balance = balance + $amount, 
              value = value + $amount 
              WHERE id=$id";
    mysqli_query($conn, $query);
    
    // Topup log entry
    mysqli_query($conn, "INSERT INTO giftcard_topups (giftcard_id, amount, created_by) VALUES ($id, $amount, $user_id)");
    
    // Set success message and redirect
    $_SESSION['message'] = "Topup of USD " . number_format($amount, 2) . " added successfully";
    $_SESSION['msg_type'] = "success";
    header("Location: /pos/giftcard/list");
    exit(0);
}

// ==========================================
// HANDLE STATUS TOGGLE REQUEST
// ==========================================
if(isset($_POST['toggle_status_btn'])) {
    $item_id = intval($_POST['item_id'] ?? 0);
    $new_status = intval($_POST['status'] ?? 0);
    
    if($item_id > 0) {
        $query = "UPDATE giftcards SET status='$new_status', updated_at=NOW() WHERE id='$item_id'";
        
        if(mysqli_query($conn, $query)) {
            $status_text = $new_status == 1 ? 'activated' : 'deactivated';
            $_SESSION['message'] = "Giftcard $status_text successfully";
            $_SESSION['msg_type'] = "success";
        } else {
            $_SESSION['message'] = "Error updating status: " . mysqli_error($conn);
            $_SESSION['msg_type'] = "error";
        }
    } else {
        $_SESSION['message'] = "Invalid giftcard ID";
        $_SESSION['msg_type'] = "error";
    }
    
    header("Location: /pos/giftcard/list");
    exit(0);
}

// ==========================================
// HANDLE DELETE TOPUP REQUEST
// ==========================================
if(isset($_POST['delete_btn']) && isset($_POST['delete_id'])) {
    $topup_id = intval($_POST['delete_id']);
    
    // First check if this is a topup entry (not a giftcard)
    $check_query = "SELECT t.*, g.id as gc_id FROM giftcard_topups t 
                    LEFT JOIN giftcards g ON t.giftcard_id = g.id 
                    WHERE t.id = $topup_id LIMIT 1";
    $check_result = mysqli_query($conn, $check_query);
    
    if(mysqli_num_rows($check_result) > 0) {
        $topup = mysqli_fetch_assoc($check_result);
        $amount = (float)$topup['amount'];
        $giftcard_id = $topup['giftcard_id'];
        
        // Subtract amount from giftcard balance
        $update_query = "UPDATE giftcards SET 
                         balance = balance - $amount, 
                         value = value - $amount 
                         WHERE id = $giftcard_id";
        mysqli_query($conn, $update_query);
        
        // Delete topup entry
        $delete_query = "DELETE FROM giftcard_topups WHERE id = $topup_id";
        
        if(mysqli_query($conn, $delete_query)) {
            $_SESSION['message'] = "Topup entry deleted and USD " . number_format($amount, 2) . " deducted from balance";
            $_SESSION['msg_type'] = "success";
        } else {
            $_SESSION['message'] = "Error deleting topup: " . mysqli_error($conn);
            $_SESSION['msg_type'] = "error";
        }
        
        header("Location: /pos/giftcard/popup-list");
        exit(0);
    }
    // If not a topup, continue to check if it's a giftcard delete below
}

// ==========================================
// HANDLE DELETE GIFTCARD REQUEST
// ==========================================
// Support both generic (delete_btn/delete_id) and specific (delete_giftcard_btn/giftcard_id) parameters
if(isset($_POST['delete_giftcard_btn']) || (isset($_POST['delete_btn']) && !isset($_POST['_topup_deleted']))) {
    // Get the ID from either parameter name
    $giftcard_id = mysqli_real_escape_string($conn, $_POST['giftcard_id'] ?? $_POST['delete_id'] ?? '');
    
    if(!$giftcard_id) {
        $_SESSION['message'] = "Giftcard ID required for deletion";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/giftcard/list");
        exit(0);
    }
    
    // Fetch giftcard to get image path
    $query = "SELECT id, image FROM giftcards WHERE id='$giftcard_id' LIMIT 1";
    $result = mysqli_query($conn, $query);
    
    if(mysqli_num_rows($result) > 0) {
        $giftcard = mysqli_fetch_assoc($result);
        
        // Delete image file if exists
        if(!empty($giftcard['image']) && file_exists($_SERVER['DOCUMENT_ROOT'] . $giftcard['image'])) {
            unlink($_SERVER['DOCUMENT_ROOT'] . $giftcard['image']);
        }
        
        // Delete giftcard record (cascades delete topups via foreign key)
        $delete_query = "DELETE FROM giftcards WHERE id='$giftcard_id'";
        
        if(mysqli_query($conn, $delete_query)) {
            $_SESSION['message'] = "Giftcard deleted successfully";
            $_SESSION['msg_type'] = "success";
        } else {
            $_SESSION['message'] = "Error deleting giftcard: " . mysqli_error($conn);
            $_SESSION['msg_type'] = "error";
        }
    } else {
        $_SESSION['message'] = "Giftcard not found";
        $_SESSION['msg_type'] = "error";
    }
    
    header("Location: /pos/giftcard/list");
    exit(0);
}

// ==========================================
// VALIDATE FORM DATA
// ==========================================
$card_no = mysqli_real_escape_string($conn, trim($_POST['card_no'] ?? ''));
$value = floatval($_POST['value'] ?? 0);
$balance = floatval($_POST['balance'] ?? 0);
$customer_id = intval($_POST['customer_id'] ?? 0);
$expiry_date = mysqli_real_escape_string($conn, $_POST['expiry_date'] ?? '');
$status = mysqli_real_escape_string($conn, $_POST['status'] ?? '1');
$giftcard_id = intval($_POST['giftcard_id'] ?? 0);

// Validate required fields
$errors = [];

if(empty($card_no)) {
    $errors[] = "Card Number is required";
}

if($value <= 0) {
    $errors[] = "Card Value must be greater than 0";
}

// Handle errors
if(!empty($errors)) {
    $_SESSION['message'] = implode(", ", $errors);
    $_SESSION['msg_type'] = "error";
    
    if($giftcard_id) {
        header("Location: /pos/giftcard/add?id=$giftcard_id");
    } else {
        header("Location: /pos/giftcard/add");
    }
    exit(0);
}

// ==========================================
// HANDLE IMAGE UPLOAD
// ==========================================
$image_path = '';

if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
    $file_name = $_FILES['image']['name'];
    $file_tmp = $_FILES['image']['tmp_name'];
    $file_size = $_FILES['image']['size'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    // Validate file
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
    $max_size = 2 * 1024 * 1024; // 2MB
    
    if(!in_array($file_ext, $allowed_extensions)) {
        $_SESSION['message'] = "Invalid file format. Allowed: JPG, PNG, GIF";
        $_SESSION['msg_type'] = "error";
        
        if($giftcard_id) {
            header("Location: /pos/giftcard/add?id=$giftcard_id");
        } else {
            header("Location: /pos/giftcard/add");
        }
        exit(0);
    }
    
    if($file_size > $max_size) {
        $_SESSION['message'] = "File size exceeds 2MB limit";
        $_SESSION['msg_type'] = "error";
        
        if($giftcard_id) {
            header("Location: /pos/giftcard/add?id=$giftcard_id");
        } else {
            header("Location: /pos/giftcard/add");
        }
        exit(0);
    }
    
    // Create upload directory if not exists
    $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/pos/uploads/giftcards/';
    if(!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Generate unique file name
    $new_file_name = 'giftcard_' . time() . '_' . uniqid() . '.' . $file_ext;
    $image_path = '/pos/uploads/giftcards/' . $new_file_name;
    
    // Move file to upload directory
    if(!move_uploaded_file($file_tmp, $upload_dir . $new_file_name)) {
        $_SESSION['message'] = "Error uploading file";
        $_SESSION['msg_type'] = "error";
        
        if($giftcard_id) {
            header("Location: /pos/giftcard/add?id=$giftcard_id");
        } else {
            header("Location: /pos/giftcard/add");
        }
        exit(0);
    }
    
    // Delete old image if editing and new image uploaded
    if($giftcard_id) {
        $old_image = $_POST['old_image'] ?? '';
        if(!empty($old_image) && file_exists($_SERVER['DOCUMENT_ROOT'] . $old_image)) {
            unlink($_SERVER['DOCUMENT_ROOT'] . $old_image);
        }
    }
}

// ==========================================
// CREATE MODE - NEW GIFTCARD
// ==========================================
if(!$giftcard_id) {
    // Check if card_no already exists
    $check_query = "SELECT id FROM giftcards WHERE card_no='$card_no' LIMIT 1";
    $check_result = mysqli_query($conn, $check_query);
    
    if(mysqli_num_rows($check_result) > 0) {
        $_SESSION['message'] = "Card Number already exists";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/giftcard/add");
        exit(0);
    }
    
    // On creation, balance = value
    $balance = $value;
    
    // Insert giftcard
    $insert_query = "INSERT INTO giftcards (card_no, value, balance, customer_id, expiry_date, image, created_by, status, created_at, updated_at) 
                     VALUES ('$card_no', $value, $balance, $customer_id, '$expiry_date', '$image_path', $user_id, '$status', NOW(), NOW())";
    
    if(mysqli_query($conn, $insert_query)) {
        $new_giftcard_id = mysqli_insert_id($conn);
        
        $_SESSION['message'] = "Giftcard created successfully (Card #: $card_no)";
        $_SESSION['msg_type'] = "success";
        
        header("Location: /pos/giftcard/list");
        exit(0);
    } else {
        $_SESSION['message'] = "Error creating giftcard: " . mysqli_error($conn);
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/giftcard/add");
        exit(0);
    }
}

// ==========================================
// EDIT MODE - UPDATE GIFTCARD
// ==========================================
else {
    // Fetch current giftcard
    $fetch_query = "SELECT * FROM giftcards WHERE id='$giftcard_id' LIMIT 1";
    $fetch_result = mysqli_query($conn, $fetch_query);
    
    if(mysqli_num_rows($fetch_result) == 0) {
        $_SESSION['message'] = "Giftcard not found";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/giftcard/list");
        exit(0);
    }
    
    $current_giftcard = mysqli_fetch_assoc($fetch_result);
    
    // Check if card_no is changed and if new card_no already exists
    if($card_no != $current_giftcard['card_no']) {
        $check_query = "SELECT id FROM giftcards WHERE card_no='$card_no' AND id != '$giftcard_id' LIMIT 1";
        $check_result = mysqli_query($conn, $check_query);
        
        if(mysqli_num_rows($check_result) > 0) {
            $_SESSION['message'] = "Card Number already exists";
            $_SESSION['msg_type'] = "error";
            header("Location: /pos/giftcard/add?id=$giftcard_id");
            exit(0);
        }
    }
    
    // If image not updated, use old image
    if(empty($image_path)) {
        $image_path = $current_giftcard['image'];
    }
    
    // Track balance change for logging
    $old_balance = $current_giftcard['balance'];
    $balance_changed = $old_balance != $balance;
    
    // Update giftcard
    $update_query = "UPDATE giftcards 
                     SET card_no='$card_no', 
                         value=$value, 
                         balance=$balance, 
                         customer_id=$customer_id, 
                         expiry_date='$expiry_date', 
                         image='$image_path', 
                         status='$status', 
                         updated_at=NOW() 
                     WHERE id=$giftcard_id";
    
    if(mysqli_query($conn, $update_query)) {
        // Log balance change if it occurred
        if($balance_changed) {
            $amount_changed = $balance - $old_balance;
            $note = $amount_changed > 0 ? "Manual topup" : "Manual adjustment";
            
            $log_query = "INSERT INTO giftcard_topups (giftcard_id, amount, note, created_by, timestamp) 
                         VALUES ('$giftcard_id', '$amount_changed', '$note', '$user_id', NOW())";
            
            mysqli_query($conn, $log_query);
        }
        
        $_SESSION['message'] = "Giftcard updated successfully";
        $_SESSION['msg_type'] = "success";
        
        header("Location: /pos/giftcard/list");
        exit(0);
    } else {
        $_SESSION['message'] = "Error updating giftcard: " . mysqli_error($conn);
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/giftcard/add?id=$giftcard_id");
        exit(0);
    }
}
?>
