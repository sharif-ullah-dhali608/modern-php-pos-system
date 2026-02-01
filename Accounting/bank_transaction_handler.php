<?php
include '../config/dbcon.php';
session_start();

// Helper Function: Upload Image
function upload_image($file, $folder) {
    if(isset($file['name']) && $file['name'] != '') {
        $filename = time() . '_' . $file['name'];
        $path = $folder . '/' . $filename;
        if(move_uploaded_file($file['tmp_name'], '../' . $path)) {
            return $path;
        }
    }
    return NULL;
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $action = $_POST['action'] ?? '';
    // Store ID - Assuming single store context from session or fallback to 1 for now (User context usually sets this)
    $store_id = $_SESSION['store_id'] ?? 1; 
    $user_id = $_SESSION['auth_user']['user_id'] ?? 1; // Fallback to 1 if no auth

    // --- ADD OPENING BALANCE ---
    if($action == 'add_opening_balance')
    {
        $date = $_POST['date'];
        $amount = $_POST['amount'];
        $details = mysqli_real_escape_string($conn, $_POST['details'] ?? 'Opening Balance');
        $redirect = $_POST['redirect'] ?? '/pos/accounting/cashbook';
        
        // 1. Get or Create 'Opening Balance' Income Source
        $check_src = mysqli_query($conn, "SELECT source_id FROM income_sources WHERE slug='opening_balance'");
        if(mysqli_num_rows($check_src) > 0) {
            $source_id = mysqli_fetch_assoc($check_src)['source_id'];
        } else {
            // Create it
            $slug = 'opening_balance';
            $name = 'Opening Balance';
            $ins_src = "INSERT INTO income_sources (source_name, slug, status) VALUES ('$name', '$slug', 1)";
            mysqli_query($conn, $ins_src);
            $source_id = mysqli_insert_id($conn);
        }

        // 2. CHECK IF OPENING BALANCE ALREADY EXISTS FOR THIS DATE
        $check_exist = "SELECT info.info_id FROM bank_transaction_info info 
                        WHERE info.title='Opening Balance' 
                        AND DATE(info.created_at) = '$date'";
        $exist_res = mysqli_query($conn, $check_exist);

        if(mysqli_num_rows($exist_res) > 0) {
            // UPDATE EXISTING
            $existing_row = mysqli_fetch_assoc($exist_res);
            $existing_id = $existing_row['info_id'];
            
            // Update Price
            $upd_price = "UPDATE bank_transaction_price SET amount='$amount' WHERE info_id='$existing_id'";
            mysqli_query($conn, $upd_price);
            
            // Update Details if needed
            $upd_info = "UPDATE bank_transaction_info SET details='$details' WHERE info_id='$existing_id'";
            mysqli_query($conn, $upd_info); // Optional but good for consistency

            $_SESSION['message'] = "Opening Balance Updated Successfully";
            $_SESSION['msg_type'] = "success";

        } else {
            // INSERT NEW
            $store_id = 1; // Default store
            $default_account_id = 1; // Default Bank Account ID (Fixed for Opening Balance to avoid FK error)

            $query = "INSERT INTO bank_transaction_info 
                    (store_id, transaction_type, account_id, source_id, title, details, created_by, created_at) 
                    VALUES ('$store_id', 'deposit', '$default_account_id', '$source_id', 'Opening Balance', '$details', '$user_id', '$date 00:00:00')";

            if(mysqli_query($conn, $query))
            {
                $info_id = mysqli_insert_id($conn);
                $price_query = "INSERT INTO bank_transaction_price (store_id, info_id, amount) VALUES ('$store_id', '$info_id', '$amount')";
                mysqli_query($conn, $price_query);
                
                $_SESSION['message'] = "Opening Balance Added Successfully";
                $_SESSION['msg_type'] = "success";
            }
            else
            {
                $_SESSION['message'] = "Failed to Add: " . mysqli_error($conn);
                $_SESSION['msg_type'] = "error";
            }
        }

        header("Location: $redirect");
        exit(0);
    }
    // --- END OPENING BALANCE ---

    if($action == 'deposit') {
        $account_id = $_POST['account_id'];
        $source_id = $_POST['source_id'];
        $ref_no = $_POST['ref_no'];
        $title = $_POST['title'];
        $amount = $_POST['amount'];
        $details = $_POST['details'];
        
        $image_path = upload_image($_FILES['attachment'], 'assets/images/bank');

        // 1. Insert into bank_transaction_info
        $queryInfo = "INSERT INTO bank_transaction_info (store_id, transaction_type, account_id, source_id, ref_no, title, details, image, created_by, is_substract) 
                      VALUES ('$store_id', 'deposit', '$account_id', '$source_id', '$ref_no', '$title', '$details', '$image_path', '$user_id', 0)";
        
        if(mysqli_query($conn, $queryInfo)) {
            $info_id = mysqli_insert_id($conn);

            // 2. Insert into bank_transaction_price
            $queryPrice = "INSERT INTO bank_transaction_price (store_id, info_id, ref_no, amount) 
                           VALUES ('$store_id', '$info_id', '$ref_no', '$amount')";
            mysqli_query($conn, $queryPrice);

            // 3. Update bank_account_to_store (Deposit +)
            // Check if mapping exists
            $checkMap = mysqli_query($conn, "SELECT ba2s_id FROM bank_account_to_store WHERE account_id='$account_id' AND store_id='$store_id'");
            if(mysqli_num_rows($checkMap) > 0) {
                mysqli_query($conn, "UPDATE bank_account_to_store SET deposit = deposit + $amount WHERE account_id='$account_id' AND store_id='$store_id'");
            } else {
                mysqli_query($conn, "INSERT INTO bank_account_to_store (store_id, account_id, deposit) VALUES ('$store_id', '$account_id', '$amount')");
            }
            
            // 4. Update bank_accounts (total_deposit +)
            mysqli_query($conn, "UPDATE bank_accounts SET total_deposit = total_deposit + $amount WHERE id='$account_id'");

            $_SESSION['message'] = "Deposit Successful";
        } else {
            $_SESSION['message'] = "Something Went Wrong"; // simplistic error handling
        }

    } elseif ($action == 'withdraw') {
        $account_id = $_POST['account_id'];
        $exp_category_id = $_POST['exp_category_id'];
        $ref_no = $_POST['ref_no'];
        $title = $_POST['title'];
        $amount = $_POST['amount'];
        $details = $_POST['details'];
        
        $image_path = upload_image($_FILES['attachment'], 'assets/images/bank');

        // 1. Insert info
        $queryInfo = "INSERT INTO bank_transaction_info (store_id, transaction_type, account_id, exp_category_id, ref_no, title, details, image, created_by, is_substract) 
                      VALUES ('$store_id', 'withdraw', '$account_id', '$exp_category_id', '$ref_no', '$title', '$details', '$image_path', '$user_id', 1)"; // is_substract = 1
        
        if(mysqli_query($conn, $queryInfo)) {
            $info_id = mysqli_insert_id($conn);
            // 2. Insert price
            $queryPrice = "INSERT INTO bank_transaction_price (store_id, info_id, ref_no, amount) 
                           VALUES ('$store_id', '$info_id', '$ref_no', '$amount')";
            mysqli_query($conn, $queryPrice);

            // 3. Update bank_account_to_store (Withdraw +)
            $checkMap = mysqli_query($conn, "SELECT ba2s_id FROM bank_account_to_store WHERE account_id='$account_id' AND store_id='$store_id'");
            if(mysqli_num_rows($checkMap) > 0) {
                mysqli_query($conn, "UPDATE bank_account_to_store SET withdraw = withdraw + $amount WHERE account_id='$account_id' AND store_id='$store_id'");
            } else {
                mysqli_query($conn, "INSERT INTO bank_account_to_store (store_id, account_id, withdraw) VALUES ('$store_id', '$account_id', '$amount')");
            }

            // 4. Update bank_accounts (total_withdraw +)
            mysqli_query($conn, "UPDATE bank_accounts SET total_withdraw = total_withdraw + $amount WHERE id='$account_id'");
            
            $_SESSION['message'] = "Withdraw Successful";
        } else {
             $_SESSION['message'] = "Error: " . mysqli_error($conn);
        }

    } elseif ($action == 'transfer') {
        $from_account_id = $_POST['from_account_id'];
        $to_account_id = $_POST['account_id']; // Target Account
        $ref_no = $_POST['ref_no'];
        $title = $_POST['title'];
        $amount = $_POST['amount'];
        $details = $_POST['details'];

        if($from_account_id == $to_account_id) {
            $_SESSION['message'] = "Source and Target accounts cannot be same";
            header("Location: bank_list.php");
            exit(0);
        }

        // 1. Insert info for TRANSFER (Record under From Account as 'transfer')
        // We might need two records or one. Let's create one main record.
        // Or one for OUT (from) and one for IN (to)? 
        // Simple approach: One record in transaction_info linking both.
        
        $queryInfo = "INSERT INTO bank_transaction_info (store_id, transaction_type, account_id, from_account_id, ref_no, title, details, created_by, is_substract) 
                      VALUES ('$store_id', 'transfer', '$to_account_id', '$from_account_id', '$ref_no', '$title', '$details', '$user_id', 0)";
        
        if(mysqli_query($conn, $queryInfo)) {
            $info_id = mysqli_insert_id($conn);
            $queryPrice = "INSERT INTO bank_transaction_price (store_id, info_id, ref_no, amount) 
                           VALUES ('$store_id', '$info_id', '$ref_no', '$amount')";
            mysqli_query($conn, $queryPrice);

            // 3. Update Balances
            
            // FROM Account (Source): transfer_to_other increases
            $checkFrom = mysqli_query($conn, "SELECT ba2s_id FROM bank_account_to_store WHERE account_id='$from_account_id' AND store_id='$store_id'");
            if(mysqli_num_rows($checkFrom) > 0) {
                 mysqli_query($conn, "UPDATE bank_account_to_store SET transfer_to_other = transfer_to_other + $amount WHERE account_id='$from_account_id' AND store_id='$store_id'");
            } else {
                 mysqli_query($conn, "INSERT INTO bank_account_to_store (store_id, account_id, transfer_to_other) VALUES ('$store_id', '$from_account_id', '$amount')");
            }
             mysqli_query($conn, "UPDATE bank_accounts SET total_transfer_to_other = total_transfer_to_other + $amount WHERE id='$from_account_id'");

            // TO Account (Target): transfer_from_other increases
            $checkTo = mysqli_query($conn, "SELECT ba2s_id FROM bank_account_to_store WHERE account_id='$to_account_id' AND store_id='$store_id'");
            if(mysqli_num_rows($checkTo) > 0) {
                 mysqli_query($conn, "UPDATE bank_account_to_store SET transfer_from_other = transfer_from_other + $amount WHERE account_id='$to_account_id' AND store_id='$store_id'");
            } else {
                 mysqli_query($conn, "INSERT INTO bank_account_to_store (store_id, account_id, transfer_from_other) VALUES ('$store_id', '$to_account_id', '$amount')");
            }
            mysqli_query($conn, "UPDATE bank_accounts SET total_transfer_from_other = total_transfer_from_other + $amount WHERE id='$to_account_id'");

            $_SESSION['message'] = "Transfer Successful";
        }
    }
    
    // Redirect back to list
    header("Location: bank_list.php");
    exit(0);
}
?>
