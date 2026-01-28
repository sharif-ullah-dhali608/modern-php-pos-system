<?php
session_start();
include('../config/dbcon.php');

if(!isset($_SESSION['auth'])){ exit(0); }

// Helper to handle multiple file uploads
function uploadExpenseFiles($files) {
    $targetDir = "../uploads/expenses/";
    if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
    
    $uploadedPaths = [];
    
    // Check if it's a multiple upload format (arrays)
    if(is_array($files['name'])) {
        $count = count($files['name']);
        for($i = 0; $i < $count; $i++) {
            if(!empty($files['name'][$i])) {
                $fileName = basename($files['name'][$i]);
                $ext = pathinfo($fileName, PATHINFO_EXTENSION);
                $newFileName = uniqid() . '_' . $i . '_exp.' . $ext; // Unique name
                $targetFilePath = $targetDir . $newFileName;
                
                if(move_uploaded_file($files['tmp_name'][$i], $targetFilePath)) {
                    $uploadedPaths[] = "/pos/uploads/expenses/" . $newFileName;
                }
            }
        }
    } else {
        // Single file fallback
        if(!empty($files['name'])) {
            $fileName = basename($files['name']);
            $ext = pathinfo($fileName, PATHINFO_EXTENSION);
            $newFileName = uniqid() . '_exp.' . $ext;
            $targetFilePath = $targetDir . $newFileName;
            
            if(move_uploaded_file($files['tmp_name'], $targetFilePath)) {
                $uploadedPaths[] = "/pos/uploads/expenses/" . $newFileName;
            }
        }
    }
    
    return implode(',', $uploadedPaths);
}

// 1. SAVE NEW EXPENSE
if(isset($_POST['save_expense_btn'])) {
    // Stores is now an array
    $stores = isset($_POST['stores']) ? $_POST['stores'] : [];
    
    $category_id = (int)$_POST['category_id'];
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $amount = (float)$_POST['amount'];
    $reference_no = mysqli_real_escape_string($conn, $_POST['reference_no']); // Might need unique ref for each? Keeping same is fine for tracking group
    $note = mysqli_real_escape_string($conn, $_POST['note']);
    $returnable = isset($_POST['returnable']) ? 1 : 0;
    $created_by = $_SESSION['auth_user']['user_id'] ?? 1;

    // Handle Attachments
    $attachment = "";
    if(isset($_FILES['attachment'])) {
        $attachment = uploadExpenseFiles($_FILES['attachment']);
    }

    if($title != "" && $amount > 0 && !empty($stores)) {
        $success_count = 0;
        
        // Loop through each selected store and insert expense
        foreach($stores as $store_id) {
            $sid = (int)$store_id;
            // Generate unique reference if needed, or append store id? 
            // Better to keep same ref to show they are related, or append -1, -2
            // Let's keep original reference primarily
            
            $query = "INSERT INTO expenses (store_id, reference_no, category_id, title, amount, returnable, note, attachment, created_by) 
                      VALUES ('$sid', '$reference_no', '$category_id', '$title', '$amount', '$returnable', '$note', '$attachment', '$created_by')";
            
            if(mysqli_query($conn, $query)) {
                $success_count++;
            }
        }
        
        if($success_count > 0) {
            $_SESSION['message'] = "Expense Recorded for $success_count Store(s)!";
            $_SESSION['msg_type'] = "success";
            header("Location: /pos/expenditure/expense_list");
        } else {
            $_SESSION['message'] = "Error: " . mysqli_error($conn);
            $_SESSION['msg_type'] = "error";
            header("Location: /pos/expenditure/expense_add");
        }
    } else {
        $_SESSION['message'] = "Fill all required fields including Store!";
        header("Location: /pos/expenditure/expense_add");
    }
    exit(0);
}

// 2. UPDATE EXPENSE
if(isset($_POST['update_expense_btn'])) {
    $id = (int)$_POST['expense_id'];
    
    // In edit mode, usually we edit one record. 
    // But UI might allow changing store. 
    // If 'stores' array is passed, we might need to update the store_id of this record.
    // Simplifying: If multiple stores selected in edit, it's ambiguous. 
    // We will take the FIRST selected store if multiple, or just the one.
    
    $stores = isset($_POST['stores']) ? $_POST['stores'] : [];
    $store_id = !empty($stores) ? (int)$stores[0] : 0; // Take first one
    
    $category_id = (int)$_POST['category_id'];
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $amount = (float)$_POST['amount'];
    $note = mysqli_real_escape_string($conn, $_POST['note']);
    $returnable = isset($_POST['returnable']) ? 1 : 0;
    
    // Attachments: Merge old and new? Or Replace?
    // Logic: If new files uploaded, append to old? Or user deleted old in UI (not handled yet in backend)?
    // User request: "delete kora jabe". The UI removes it from the input. 
    // But for existing files (viewed in edit), if user deletes them, we need to know.
    // The Update UI shows existing files. If user wants to delete existing, we need a mechanism.
    // Current UI doesn't explicitly send "deleted_files".
    // However, for new uploads, we just append or replace?
    // Standard primitive logic: Keep old, add new.
    
    $old_attachment = $_POST['old_attachment'];
    $new_attachment = "";
    if(isset($_FILES['attachment'])) {
         $new_attachment = uploadExpenseFiles($_FILES['attachment']);
    }
    
    // Combine
    $final_attachment = $old_attachment;
    if(!empty($new_attachment)) {
        if(!empty($final_attachment)) $final_attachment .= "," . $new_attachment;
        else $final_attachment = $new_attachment;
    }

    $update_query = "UPDATE expenses SET 
                        store_id='$store_id', 
                        category_id='$category_id', 
                        title='$title', 
                        amount='$amount', 
                        returnable='$returnable', 
                        note='$note', 
                        attachment='$final_attachment' 
                    WHERE id='$id'";
    
    if(mysqli_query($conn, $update_query)) {
        $_SESSION['message'] = "Expense Updated Successfully!";
        $_SESSION['msg_type'] = "success";
        header("Location: /pos/expenditure/expense_list");
    } else {
        $_SESSION['message'] = "Error: " . mysqli_error($conn);
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/expenditure/expense_edit/$id");
    }
    exit(0);
}

// 3. DELETE EXPENSE
if(isset($_POST['delete_btn'])) {
    $id = (int)$_POST['delete_id'];
    $query = "DELETE FROM expenses WHERE id='$id'";
    if(mysqli_query($conn, $query)) {
        $_SESSION['message'] = "Expense Deleted!";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['message'] = "Error!";
        $_SESSION['msg_type'] = "error";
    }
    header("Location: /pos/expenditure/expense_list");
    exit(0);
}

header("Location: /pos/expenditure/expense_list");
exit(0);
?>
