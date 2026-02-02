<?php
session_start();
include('../config/dbcon.php');

// Helper to redirect with message
function redirect($url, $msg, $type = 'success') {
    $_SESSION['message'] = $msg;
    $_SESSION['msg_type'] = $type;
    header("Location: $url");
    exit(0);
}

// AJAX: ADD LOAN SOURCE
if(isset($_GET['action']) && $_GET['action'] == 'generate_ref') {
    function generateRandomString($length = 6) {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    do {
        $ref = generateRandomString();
        $check = mysqli_query($conn, "SELECT loan_id FROM loans WHERE ref_no = '$ref'");
    } while(mysqli_num_rows($check) > 0);

    echo json_encode(['status' => 200, 'ref_no' => $ref]);
    exit;
}

// AJAX: ADD LOAN SOURCE
// AJAX: ADD LOAN SOURCE
if(isset($_POST['add_loan_source_ajax'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name'] ?? '');
    $type = mysqli_real_escape_string($conn, $_POST['type'] ?? 'Others');
    $phone = mysqli_real_escape_string($conn, $_POST['phone'] ?? '');
    $address = mysqli_real_escape_string($conn, $_POST['address'] ?? '');
    $details = mysqli_real_escape_string($conn, $_POST['details'] ?? '');

    if(empty($name)) {
        echo json_encode(['status' => 400, 'message' => 'Source Name is required']);
        exit;
    }

    $query = "INSERT INTO loan_sources (name, type, phone, address, details) VALUES ('$name', '$type', '$phone', '$address', '$details')";
    if(mysqli_query($conn, $query)) {
        $id = mysqli_insert_id($conn);
        echo json_encode(['status' => 200, 'message' => 'Source added successfully', 'id' => $id, 'name' => $name]);
    } else {
        echo json_encode(['status' => 500, 'message' => 'Database error: ' . mysqli_error($conn) . ' | Query: ' . $query]);
    }
    exit;
}

// SAVE LOAN
if(isset($_POST['save_loan_btn'])) {
    // Parse Range: "YYYY-MM-DD to YYYY-MM-DD"
    $range = mysqli_real_escape_string($conn, $_POST['transaction_range']);
    $range_parts = explode(' ---- To ---- ', $range);
    
    $date = $range_parts[0] . ' ' . date('H:i:s'); // Now time
    $deadline_date = isset($range_parts[1]) ? $range_parts[1] : $range_parts[0];
    $deadline_time = '23:59:59'; // End of day

    $ref_no = mysqli_real_escape_string($conn, $_POST['ref_no']);

    // Check Duplicate Reference
    $dup_check = mysqli_query($conn, "SELECT loan_id FROM loans WHERE ref_no = '$ref_no'");
    if(mysqli_num_rows($dup_check) > 0) {
        redirect("/pos/loan/add", "Reference No already exists. Please choose another.", "error");
    }
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $amount = (float)mysqli_real_escape_string($conn, $_POST['amount']);
    $interest = (float)mysqli_real_escape_string($conn, $_POST['interest']);
    $payable = (float)mysqli_real_escape_string($conn, $_POST['payable']);
    $details = mysqli_real_escape_string($conn, $_POST['details'] ?? '');
    $status = isset($_POST['status']) ? mysqli_real_escape_string($conn, $_POST['status']) : '1';
    $created_by = $_SESSION['auth_user']['user_id'] ?? 1;

    // File Upload
    $attachment = NULL;
    if(isset($_FILES['attachment']['name']) && $_FILES['attachment']['name'] != "") {
        $filename = time() . "_" . $_FILES['attachment']['name'];
        $path = "../uploads/loans/";
        if(!is_dir($path)) mkdir($path, 0777, true);
        
        if(move_uploaded_file($_FILES['attachment']['tmp_name'], $path . $filename)) {
            $attachment = "uploads/loans/" . $filename;
        }
    }

    $loan_from_id = mysqli_real_escape_string($conn, $_POST['loan_from_id']);
    
    // Get Source Name
    $src_q = mysqli_query($conn, "SELECT name FROM loan_sources WHERE id='$loan_from_id'");
    $loan_from = mysqli_fetch_assoc($src_q)['name'] ?? 'Others';

    $query = "INSERT INTO loans (created_at, loan_from_id, loan_from, ref_no, title, amount, interest, payable, due, details, attachment, status, deadline_date, deadline_time, created_by) 
              VALUES ('$date', '$loan_from_id', '$loan_from', '$ref_no', '$title', '$amount', '$interest', '$payable', '$payable', '$details', '$attachment', '$status', '$deadline_date', '$deadline_time', '$created_by')";
    
    if(mysqli_query($conn, $query)) {
        redirect("/pos/loan/list", "Loan Added Successfully");
    } else {
        redirect("/pos/loan/add", "Something Went Wrong: " . mysqli_error($conn), "error");
    }
}

// UPDATE LOAN
if(isset($_POST['update_loan_btn'])) {
    $loan_id = mysqli_real_escape_string($conn, $_POST['loan_id']);
    $loan_from_id = mysqli_real_escape_string($conn, $_POST['loan_from_id']);
    
    // Get Source Name
    $src_q = mysqli_query($conn, "SELECT name FROM loan_sources WHERE id='$loan_from_id'");
    $loan_from = mysqli_fetch_assoc($src_q)['name'] ?? 'Others';

    // Parse Range: "YYYY-MM-DD to YYYY-MM-DD"
    $range = mysqli_real_escape_string($conn, $_POST['transaction_range']);
    $range_parts = explode(' ---- To ---- ', $range);
    
    $date = $range_parts[0] . ' ' . date('H:i:s');
    $deadline_date = isset($range_parts[1]) ? $range_parts[1] : $range_parts[0];
    $deadline_time = '23:59:59';

    $ref_no = mysqli_real_escape_string($conn, $_POST['ref_no']);

    // Check Duplicate Reference (excluding current)
    $dup_check = mysqli_query($conn, "SELECT loan_id FROM loans WHERE ref_no = '$ref_no' AND loan_id != '$loan_id'");
    if(mysqli_num_rows($dup_check) > 0) {
        redirect("/pos/loan/edit/$loan_id", "Reference No already exists. Please choose another.", "error");
    }
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $amount = (float)mysqli_real_escape_string($conn, $_POST['amount']);
    $interest = (float)mysqli_real_escape_string($conn, $_POST['interest']);
    $payable = (float)mysqli_real_escape_string($conn, $_POST['payable']);
    $details = mysqli_real_escape_string($conn, $_POST['details'] ?? '');
    $status = isset($_POST['status']) ? mysqli_real_escape_string($conn, $_POST['status']) : '1';

    // Re-calculate Due: Payable - Paid
    $check_q = mysqli_query($conn, "SELECT paid FROM loans WHERE loan_id='$loan_id'");
    $paid = mysqli_fetch_assoc($check_q)['paid'] ?? 0;
    $due = $payable - $paid;

    // File Upload (Update)
    $attachment_query_part = "";
    if(isset($_FILES['attachment']['name']) && $_FILES['attachment']['name'] != "") {
        $filename = time() . "_" . $_FILES['attachment']['name'];
        $path = "../uploads/loans/";
        if(!is_dir($path)) mkdir($path, 0777, true);
        
        if(move_uploaded_file($_FILES['attachment']['tmp_name'], $path . $filename)) {
            $new_attachment = "uploads/loans/" . $filename;
            $attachment_query_part = ", attachment='$new_attachment'";
        }
    } else {
    }

    $query = "UPDATE loans SET 
              created_at='$date', 
              loan_from_id='$loan_from_id',
              loan_from='$loan_from', 
              ref_no='$ref_no', 
              title='$title', 
              amount='$amount', 
              interest='$interest', 
              payable='$payable', 
              due='$due', 
              details='$details',
              status='$status',
              deadline_date='$deadline_date',
              deadline_time='$deadline_time'
              $attachment_query_part 
              WHERE loan_id='$loan_id'";

    if(mysqli_query($conn, $query)) {
        redirect("/pos/loan/list", "Loan Updated Successfully");
    } else {
        redirect("/pos/loan/edit/$loan_id", "Update Failed: " . mysqli_error($conn), "error");
    }
}

// DELETE LOAN (from confirmDelete in list)
if(isset($_POST['delete_btn'])) {
    $id = mysqli_real_escape_string($conn, $_POST['delete_id']);
    
    // First delete related payments
    mysqli_query($conn, "DELETE FROM loan_payments WHERE loan_id='$id'");
    
    // Then delete the loan
    $query = "DELETE FROM loans WHERE loan_id='$id'";
    if(mysqli_query($conn, $query)) {
        redirect("/pos/loan/list", "Loan Deleted Successfully");
    } else {
        redirect("/pos/loan/list", "Delete Failed", "error");
    }
}

// SAVE PAYMENT (For List View Modal)
if(isset($_POST['save_payment_btn'])) {
    $loan_id = mysqli_real_escape_string($conn, $_POST['loan_id']);
    $pay_amount = (float)mysqli_real_escape_string($conn, $_POST['pay_amount']);
    $note = mysqli_real_escape_string($conn, $_POST['note']);
    $created_by = $_SESSION['auth_user']['user_id'] ?? 1;
    
    // 1. Check current status
    $loan_q = mysqli_query($conn, "SELECT payable, paid, due FROM loans WHERE loan_id='$loan_id'");
    $loan = mysqli_fetch_assoc($loan_q);
    
    if($pay_amount > $loan['due']) {
        redirect("/pos/loan/list", "Payment amount exceeds due!", "error");
    }

    // 2. Insert Payment Record
    $date = date('Y-m-d H:i:s');
    $ins_q = "INSERT INTO loan_payments (loan_id, paid, note, created_at, created_by) VALUES ('$loan_id', '$pay_amount', '$note', '$date', '$created_by')";
    mysqli_query($conn, $ins_q);

    // 3. Update Loan Totals
    $new_paid = $loan['paid'] + $pay_amount;
    $new_due = $loan['payable'] - $new_paid;
    
    $upd_q = "UPDATE loans SET paid='$new_paid', due='$new_due' WHERE loan_id='$loan_id'";
    
    if(mysqli_query($conn, $upd_q)) {
        redirect("/pos/loan/list", "Payment Added Successfully");
    } else {
        redirect("/pos/loan/list", "Payment Failed", "error");
    }
}
?>
