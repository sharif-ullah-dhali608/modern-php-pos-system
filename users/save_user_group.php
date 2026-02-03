<?php
include('../config/dbcon.php');

/**
 * 1. FETCH GROUPS FOR TABLE LIST
 * Renders rows for the User Group management table
 */
if(isset($_POST['fetch_groups'])) {
    // Join users table to count members correctly
    $query = "SELECT ug.*, (SELECT COUNT(u.id) FROM users u WHERE u.group_id = ug.id) as member_count 
              FROM user_groups ug ORDER BY ug.id DESC";
    $query_run = mysqli_query($conn, $query);
    
    if(mysqli_num_rows($query_run) > 0) {
        foreach($query_run as $row) {
            ?>
            <tr class="hover:bg-indigo-50/30 transition-colors group">
                <td class="p-4 text-xs text-slate-400 font-mono"><?= $row['id']; ?></td>
                <td class="p-4 text-sm font-bold text-slate-700"><?= $row['name']; ?></td>
                <td class="p-4">
                    <span class="px-3 py-1 rounded-full bg-indigo-50 text-indigo-600 text-xs font-black">
                        <?= $row['member_count']; ?> </span>
                </td>
                <td class="p-4 text-center">
                    <button onclick="openPermission(<?= $row['id']; ?>, '<?= $row['name']; ?>')" class="w-10 h-10 inline-flex items-center justify-center text-indigo-600 bg-indigo-50 rounded-xl hover:bg-indigo-600 hover:text-white transition-all">
                        <i class="fas fa-user-lock"></i>
                    </button>                            
                </td>
                <td class="p-4 text-center">
                    <button onclick="deleteGroup(<?= $row['id']; ?>)" class="w-10 h-10 inline-flex items-center justify-center text-red-600 bg-red-50 rounded-xl hover:bg-red-600 hover:text-white transition-all">
                        <i class="fas fa-trash-alt"></i>
                    </button>                            
                </td>
            </tr>
            <?php
        }
    }
    exit;
}

/**
 * 2. FETCH MODULAR PERMISSIONS FOR MODAL
 * Generates the modular UI with Select All and Search for each category
 */
if(isset($_POST['fetch_permissions'])) {
    $group_id = (int)$_POST['group_id'];
    
    // Retrieve existing serialized permissions
    $res = mysqli_query($conn, "SELECT permission FROM user_groups WHERE id = '$group_id' LIMIT 1");
    $data = mysqli_fetch_assoc($res);
    $existing_perms = unserialize($data['permission'] ?? '') ?: [];

    // Full Module and Sub-Permission Mapping based on your provided requirements
    $modules = [
        // DASHBOARD & ANALYTICS
        'dashboard' => ['View Dashboard', 'View Revenue Card', 'View Profit Card', 'View Recent Sales', 'View Best Selling Product', 'View Stock Alert Widget', 'View Monthly Goals'],

        // CORE MODULES
        'sell' => ['View Sell Invoice', 'View Sell List', 'Create Sell', 'Update Info', 'Delete Sell', 'Sell Payment', 'Create Due', 'Create Return', 'View Return List', 'Update Return', 'Delete Return', 'Send Sell Invoice via SMS', 'Send Sell Invoice via Email', 'Read Sell Log', 'View Profit in Sell List'],
        'quotation' => ['Read Quotation List', 'Create Quotation', 'Update Quotation', 'Delete Quotation', 'Convert to Sell'],
        'purchase' => ['Create Invoice', 'View Invoice List', 'Update Info', 'Delete Invoice', 'Payment', 'Create Due', 'Create Return', 'View Return List', 'Update Return', 'Delete Return', 'Read Purchase Log', 'Import Stock', 'View Cost Price'],

        // PRODUCTS & STOCK
        'product' => ['Read Product List', 'Create Product', 'Update Product', 'Delete Product', 'Import Product', 'Product Bulk Action', 'Delete All Product', 'Read Category List', 'Create Category', 'Update Category', 'Delete Category', 'Read Stock Alert', 'Read Expired Product List', 'Barcode Print', 'Restore All Product', 'View Product Cost', 'View Product Supplier', 'View Product Profit'],
        'brand' => ['Read Brand List', 'Create Brand', 'Update Brand', 'Delete Brand', 'Read Brand Profile'],
        'unit' => ['Read Unit', 'Create Unit', 'Update Unit', 'Delete Unit'],
        'storebox' => ['Read Box', 'Create Box', 'Update Box', 'Delete Box'],
        
        // CONTACTS
        'customer' => ['Read Customer List', 'Read Customer Profile', 'Create Customer', 'Update Customer', 'Delete Customer', 'Add Balance', 'Substract Balance', 'Read Transaction List'],
        'supplier' => ['Read Supplier List', 'Create Supplier', 'Update Supplier', 'Delete Supplier', 'Read Supplier Profile'],
        'user' => ['Read User List', 'Create User', 'Update User', 'Delete User', 'Change Password', 'View User Activity Log'],
        'usergroup' => ['Read Usergroup List', 'Create Usergroup', 'Update Usergroup', 'Delete Usergroup'],

        // ACCOUNTING & FINANCE
        'accounting' => ['Withdraw', 'Deposit', 'Transfer', 'View Bank Account', 'View Bank Account Sheet', 'View Bank Transfer', 'View Bank Transactions', 'View Income Source', 'Create Bank Account', 'Update Bank Account', 'Delete Bank Account', 'Create Income Source', 'Update Income Source', 'Delete Income Source', 'Income Monthwise', 'Income & Expense', 'Profit & Loss', 'Cashbook'],
        'expenditure' => ['Read Expense', 'Create Expense', 'Update Expense', 'Delete Expense', 'Read Expense Category', 'Create Expense Category', 'Update Expense Category', 'Delete Expense Category', 'Expense Monthwise', 'Expense Summary'],
        'loan' => ['Read Loan', 'Read Loan Summary', 'Take Loan', 'Update Loan', 'Delete Loan', 'Loan Pay'],
        'installment' => ['Read Installment List', 'Create Installment', 'Update Installment', 'Delete Installment', 'Installment Payment', 'Installment Overview'],

        // TRANSFERS & GIFTCARDS
        'transfer' => ['Read Transfer', 'Add Transfer', 'Update Transfer', 'Cancel Transfer', 'Read Receive List'],
        'giftcard' => ['Read Giftcard', 'Add Giftcard', 'Update Giftcard', 'Delete Giftcard', 'Giftcard Topup', 'Read Giftcard Topup', 'Delete Giftcard Topup'],

        // REPORTS
        'report' => ['Overview Report', 'Collection Report', 'Due Collection Report', 'Due Paid Report', 'Sell Report', 'Purchase Report', 'Sell Payment Report', 'Purchase Payment Report', 'Sell Tax Report', 'Purchase Tax Report', 'Tax Overview Report', 'Stock Report', 'Customer Due Report', 'Supplier Due Report', 'Profit Loss Report'],

        // SETTINGS & SYSTEM
        'settings' => ['View General Settings', 'View Store Settings', 'View Email Settings', 'Receipt Template', 'Read User Preference', 'Update User Preference', 'Filtering', 'Language Sync', 'Database Backup', 'Database Restore', 'Reset System'],
        'payment_method' => ['Read Payment Method List', 'Create Payment Method', 'Update Payment Method', 'Delete Payment Method', 'Active/Inactive'],
        'currency' => ['Read Currency', 'Add Currency', 'Update Currency', 'Change Currency', 'Delete Currency'],
        'taxrate' => ['Read Taxrate', 'Create Taxrate', 'Update Taxrate', 'Delete Taxrate'],
        'printer' => ['View Printer', 'Add Printer', 'Update Printer', 'Delete Printer'],
        'store' => ['Read Store List', 'Create Store', 'Update Store', 'Delete Store', 'Active Store', 'Upload Favicon', 'Upload Logo'],
        'sms' => ['Send SMS', 'View SMS Report', 'View SMS Setting', 'Update SMS Setting', 'Send Email'],
        'language' => ['View Language', 'Add Language', 'Edit Language Info', 'Delete Language', 'Language Translation', 'Delete Language key'],
        'filemanager' => ['Read Filemanager']
    ];
    
    foreach($modules as $title => $permissions) {
        $mod_id = "mod_" . $title;
        echo '<div class="bg-slate-50 p-5 rounded-2xl border border-slate-200 hover:border-indigo-300 transition-all permission-card flex flex-col">';
        
        // Header Section (Module Name + Select All)
        echo '<div class="flex items-center justify-between mb-4 border-b pb-2 border-slate-200 shrink-0">';
        echo '  <div class="flex items-center gap-2">';
        echo '      <input type="checkbox" class="select-all-mod w-4 h-4 rounded border-slate-300 text-indigo-600 cursor-pointer" data-target="'.$mod_id.'">';
        echo '      <h4 class="text-[11px] font-black uppercase text-indigo-600 tracking-widest">'.ucwords(str_replace('_', ' ', $title)).'</h4>';
        echo '  </div>';
        echo '  <i class="fas fa-shield-alt text-slate-300 text-xs"></i>';
        echo '</div>';
        
        // Search input (Shrink-0 prevents it from scrolling away)
        echo '<div class="mb-3 relative shrink-0">';
        echo '  <input type="text" placeholder="Search..." class="perm-search w-full text-[10px] px-2 py-1 rounded border border-slate-200 outline-none focus:border-indigo-400">';
        echo '</div>';
        
        // SCROLLABLE AREA: Set fixed height and enable scrolling
        echo '<div class="permission-list space-y-1 overflow-y-auto custom-scroll pr-2" id="'.$mod_id.'" style="max-height: 250px;">';
        foreach($permissions as $perm) {
            $perm_slug = strtolower(str_replace([' ', '.', '&', '/'], '_', $perm)) . "_" . $title;
            $checked = (isset($existing_perms['access'][$perm_slug]) && $existing_perms['access'][$perm_slug] == "true") ? 'checked' : '';
            
            echo '<label class="flex items-center gap-3 py-1 cursor-pointer group perm-item">';
            echo '  <input type="checkbox" name="access['.$perm_slug.']" value="true" '.$checked.' class="perm-check w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">';
            echo '  <span class="text-[10px] font-bold text-slate-600 group-hover:text-indigo-600 transition-colors">'.$perm.'</span>';
            echo '</label>';
        }
        echo '</div>'; // End of scrollable list
        
        echo '</div>'; // End of card
    }
    exit;
}

/**
 * 3. SAVE NEW USER GROUP
 */
if(isset($_POST['add_group_btn'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $slug = mysqli_real_escape_string($conn, $_POST['slug']);

    $check = mysqli_query($conn, "SELECT * FROM user_groups WHERE slug='$slug'");
    if(mysqli_num_rows($check) > 0) {
        echo json_encode(['status'=>400, 'message'=>'Slug already exists!']);
    } else {
        $query = "INSERT INTO user_groups (name, slug) VALUES ('$name','$slug')";
        if(mysqli_query($conn, $query)) {
            echo json_encode(['status'=>200, 'message'=>'User Group Created Successfully!']);
        } else {
            echo json_encode(['status'=>500, 'message'=>'Database Error: '.mysqli_error($conn)]);
        }
    }
    exit;
}

/**
 * 4. UPDATE PERMISSIONS (SERIALIZED STORAGE)
 */
if(isset($_POST['update_permissions_btn'])) {
    $group_id = (int)$_POST['group_id'];
    $access_data = ['access' => $_POST['access'] ?? []];
    $serialized_perms = serialize($access_data);

    $query = "UPDATE user_groups SET permission = '$serialized_perms' WHERE id = '$group_id'";
    if(mysqli_query($conn, $query)) {
        echo json_encode(['status'=>200, 'message'=>'Permissions Updated Successfully']);
    } else {
        echo json_encode(['status'=>500, 'message'=>'Update Failed']);
    }
    exit;
}

/**
 * 5. DELETE GROUP
 */
if(isset($_POST['delete_group_btn'])) {
    $id = (int)$_POST['group_id'];
    mysqli_query($conn, "DELETE FROM user_groups WHERE id = '$id'");
    echo json_encode(['status'=>200]);
    exit;
}
?>