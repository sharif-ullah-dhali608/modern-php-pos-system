<?php
include('../config/dbcon.php');

if(isset($_POST['user_id'])) {
    $user_id = (int)$_POST['user_id'];
    
    $query = "SELECT u.*, g.name as group_name, g.permission 
              FROM users u 
              LEFT JOIN user_groups g ON u.group_id = g.id 
              WHERE u.id = '$user_id' LIMIT 1";
    $query_run = mysqli_query($conn, $query);
    
    if(mysqli_num_rows($query_run) > 0) {
        $user = mysqli_fetch_assoc($query_run);
        $permissions = unserialize($user['permission'] ?? '') ?: [];
        $access = $permissions['access'] ?? [];
        
        // Detailed Module and Sub-Permission Mapping with Icons
        $module_definitions = [
            'report' => ['label' => 'Reports', 'icon' => 'fa-chart-pie', 'perms' => ['Recent activities', 'Dashboard Accounting Report', 'Sell Report', 'Overview Report', 'Collec. Report', 'Full Collec. Report', 'Customer Due Collection RPT', 'Suppler Due Paid RPT', 'Read Analytics', 'Purchase Report', 'Purchase Payment Report', 'Purchase Tax Report', 'Sell Payment Report', 'Sell Tax Report', 'Tax Overview Report', 'Stock Report', 'Send Report via Email']],
            'accounting' => ['label' => 'Accounting', 'icon' => 'fa-university', 'perms' => ['Withdraw', 'Deposit', 'Transfer', 'View Bank Account', 'View Bank Account Sheet', 'View Bank Transfer', 'View Bank Transactions', 'View Income Source', 'Create Bank Account', 'Update Bank Account', 'Delete Bank Account', 'Create Income Source', 'Update Income Source', 'Delete Income Source', 'Income Monthwise', 'Income & Expense', 'Profit & Loss', 'Cashbook']],
            'quotation' => ['label' => 'Quotations', 'icon' => 'fa-file-invoice', 'perms' => ['Read Quotation List', 'Create Quotation', 'Update Quotation', 'Delete Quotation']],
            'installment' => ['label' => 'Installments', 'icon' => 'fa-calendar-alt', 'perms' => ['Read Installment List', 'Create Installment', 'Update Installment', 'Delete Installment', 'Installment Payment', 'Installment Overview']],
            'expenditure' => ['label' => 'Expenses', 'icon' => 'fa-wallet', 'perms' => ['Read Expense', 'Create Expense', 'Update Expense', 'Delete Expense', 'Read Expense Category', 'Create Expense Category', 'Update Expense Category', 'Delete Expense Category', 'Expense Monthwise', 'Expense Summary']],
            'sell' => ['label' => 'Sells', 'icon' => 'fa-shopping-cart', 'perms' => ['View Sell Invoice', 'View Sell List', 'Create Sell', 'Update Info', 'Delete Sell', 'Sell Payment', 'Create Due', 'Create Return', 'View Return List', 'Update Return', 'Delete Return', 'Send Sell Invoice via SMS', 'Send Sell Invoice via Email', 'Read Sell Log']],
            'purchase' => ['label' => 'Purchases', 'icon' => 'fa-shopping-bag', 'perms' => ['Create Invoice', 'View Invoice List', 'Update Info', 'Delete Invoice', 'Payment', 'Create Due', 'Create Return', 'View Return List', 'Update Return', 'Delete Return', 'Read Purchase Log', 'Import Stock']],
            'transfer' => ['label' => 'Transfers', 'icon' => 'fa-exchange-alt', 'perms' => ['Read Transfer', 'Add Transfer', 'Update Transfer', 'Cancel Transfer']],
            'giftcard' => ['label' => 'Gift Cards', 'icon' => 'fa-gift', 'perms' => ['Read Giftcard', 'Add Giftcard', 'Update Giftcard', 'Delete Giftcard', 'Giftcard Topup', 'Read Giftcard Topup', 'Delete Giftcard Topup']],
            'product' => ['label' => 'Products', 'icon' => 'fa-box-open', 'perms' => ['Read Product List', 'Create Product', 'Update Product', 'Delete Product', 'Import Product', 'Product Bulk Action', 'Delete All Product', 'Read Category List', 'Create Category', 'Update Category', 'Delete Category', 'Read Stock Alert', 'Read Expired Product List', 'Barcode Print', 'Restore All Product']],
            'supplier' => ['label' => 'Suppliers', 'icon' => 'fa-truck', 'perms' => ['Read Supplier List', 'Create Supplier', 'Update Supplier', 'Delete Supplier', 'Read Supplier Profile']],
            'brand' => ['label' => 'Brands', 'icon' => 'fa-tags', 'perms' => ['Read Brand List', 'Create Brand', 'Update Brand', 'Delete Brand', 'Read Brand Profile']],
            'storebox' => ['label' => 'Boxes', 'icon' => 'fa-archive', 'perms' => ['Read Box', 'Create Box', 'Update Box', 'Delete Box']],
            'unit' => ['label' => 'Units', 'icon' => 'fa-balance-scale', 'perms' => ['Read Unit', 'Create Unit', 'Update Unit', 'Delete Unit']],
            'taxrate' => ['label' => 'Tax Rates', 'icon' => 'fa-percentage', 'perms' => ['Read Taxrate', 'Create Taxrate', 'Update Taxrate', 'Delete Taxrate']],
            'loan' => ['label' => 'Loans', 'icon' => 'fa-hand-holding-usd', 'perms' => ['Read Loan', 'Read Loan Summary', 'Take Loan', 'Update Loan', 'Delete Loan', 'Loan Pay']],
            'customer' => ['label' => 'Customers', 'icon' => 'fa-users', 'perms' => ['Read Customer List', 'Read Customer Profile', 'Create Customer', 'Update Customer', 'Delete Customer', 'Add Balance', 'Substract Balance', 'Read Transaction List']],
            'user' => ['label' => 'Users', 'icon' => 'fa-user-cog', 'perms' => ['Read User List', 'Create User', 'Update User', 'Delete User', 'Change Password']],
            'usergroup' => ['label' => 'User Groups', 'icon' => 'fa-users-cog', 'perms' => ['Read Usergroup List', 'Create Usergroup', 'Update Usergroup', 'Delete Usergroup']],
            'currency' => ['label' => 'Currencies', 'icon' => 'fa-money-bill-wave', 'perms' => ['Read Currency', 'Add Currency', 'Update Currency', 'Change Currency', 'Delete Currency']],
            'filemanager' => ['label' => 'Files', 'icon' => 'fa-folder-open', 'perms' => ['Read Filemanager']],
            'payment_method' => ['label' => 'Payments', 'icon' => 'fa-credit-card', 'perms' => ['Read Payment Method List', 'Create Payment Method', 'Update Payment Method', 'Delete Payment Method', 'Active/Inactive']],
            'store' => ['label' => 'Stores', 'icon' => 'fa-store', 'perms' => ['Read Store List', 'Create Store', 'Update Store', 'Delete Store', 'Active Store', 'Upload Favicon', 'Upload Logo']],
            'printer' => ['label' => 'Printers', 'icon' => 'fa-print', 'perms' => ['View Printer', 'Add Printer', 'Update Printer', 'Delete Printer']],
            'sms' => ['label' => 'SMS/Email', 'icon' => 'fa-envelope-open-text', 'perms' => ['Send SMS', 'View SMS Report', 'View SMS Setting', 'Update SMS Setting', 'Send Email']],
            'settings' => ['label' => 'Settings', 'icon' => 'fa-cogs', 'perms' => ['Receipt Template', 'Read User Preference', 'Update User Preference', 'Filtering', 'Language Sync', 'Database Backup', 'Database Restore', 'Reset']]
        ];

        ?>
        <div class="space-y-6">
            <!-- Header Info -->
            <div class="flex items-center gap-6 pb-6 border-b border-slate-100">
                <img src="<?= !empty($user['user_image']) ? $user['user_image'] : 'https://ui-avatars.com/api/?name='.urlencode($user['name']).'&background=random&size=100'; ?>" 
                     class="w-24 h-24 rounded-2xl object-cover shadow-md border-4 border-white" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($user['name']) ?>&background=random&size=100'">
                <div>
                    <h2 class="text-2xl font-bold text-slate-800"><?= $user['name']; ?></h2>
                    <p class="text-slate-500 font-medium"><?= $user['email']; ?></p>
                    <div class="mt-3 flex items-center gap-2">
                        <span class="px-3 py-1 rounded-full bg-indigo-50 text-indigo-600 text-xs font-black uppercase tracking-wider">
                            <?= $user['group_name'] ?? 'No Group'; ?>
                        </span>
                        <?php if($user['status'] == 1): ?>
                            <span class="px-3 py-1 rounded-full bg-emerald-50 text-emerald-600 text-xs font-black uppercase tracking-wider">Active</span>
                        <?php else: ?>
                            <span class="px-3 py-1 rounded-full bg-red-50 text-red-600 text-xs font-black uppercase tracking-wider">Inactive</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Basic Details Grid -->
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                    <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Contact Number</p>
                    <p class="text-sm font-bold text-slate-700"><?= !empty($user['phone']) ? $user['phone'] : 'N/A'; ?></p>
                </div>
                <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                    <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Joined Date</p>
                    <p class="text-sm font-bold text-slate-700"><?= date('d M, Y', strtotime($user['created_at'])); ?></p>
                </div>
                <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                    <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Date of Birth</p>
                    <p class="text-sm font-bold text-slate-700"><?= !empty($user['dob']) ? date('d M, Y', strtotime($user['dob'])) : 'N/A'; ?></p>
                </div>
                <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                    <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Gender</p>
                    <p class="text-sm font-bold text-slate-700"><?= $user['sex'] == 'M' ? 'Male' : ($user['sex'] == 'F' ? 'Female' : 'Other'); ?></p>
                </div>
            </div>

            <!-- Address -->
            <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                <p class="text-[10px] font-black text-slate-400 uppercase mb-1">Residential Address</p>
                <p class="text-sm font-bold text-slate-700 leading-relaxed"><?= !empty($user['address']) ? $user['address'] : 'No address provided.'; ?></p>
            </div>

            <!-- Permissions / Access -->
            <div>
                <h3 class="text-xs font-black text-slate-400 uppercase mb-3 flex items-center gap-2">
                    <i class="fas fa-shield-alt text-indigo-500"></i> Module Access Permissions
                </h3>
                <div class="grid grid-cols-1 gap-4">
                    <?php 
                    $has_perms = false;
                    foreach($module_definitions as $mod_key => $mod_data): 
                        $mod_label = $mod_data['label'];
                        $mod_icon = $mod_data['icon'];
                        $sub_perms = $mod_data['perms'];
                        
                        $active_sub_perms = [];
                        foreach($sub_perms as $perm_name) {
                            $perm_slug = strtolower(str_replace([' ', '.', '&', '/'], '_', $perm_name)) . "_" . $mod_key;
                            if(isset($access[$perm_slug]) && $access[$perm_slug] == "true") {
                                $active_sub_perms[] = $perm_name;
                            }
                        }

                        if(!empty($active_sub_perms)):
                            $has_perms = true;
                    ?>
                        <div class="p-5 rounded-2xl bg-white border border-slate-100 flex flex-col gap-4 shadow-sm hover:shadow-md transition-all duration-300 relative overflow-hidden group">
                            <!-- Left Accent -->
                            <div class="absolute left-0 top-0 bottom-0 w-1 bg-teal-500 opacity-60"></div>
                            
                            <!-- Header -->
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-teal-50 flex items-center justify-center text-teal-600 transition-transform group-hover:scale-110">
                                        <i class="fas <?= $mod_icon; ?> text-base"></i>
                                    </div>
                                    <h4 class="text-sm font-black uppercase text-slate-800 tracking-wider"><?= $mod_label; ?></h4>
                                </div>
                                <span class="text-[9px] font-black bg-emerald-100 text-emerald-700 px-2 py-1 rounded-lg uppercase"><?= count($active_sub_perms); ?> Active</span>
                            </div>

                            <!-- Badges -->
                            <div class="flex flex-wrap gap-2">
                                <?php foreach($active_sub_perms as $sub): ?>
                                    <div class="px-3 py-1.5 rounded-xl bg-slate-50 border border-slate-100 text-[10px] font-bold text-slate-600 flex items-center gap-2 hover:bg-teal-50 hover:border-teal-100 hover:text-teal-700 transition-all cursor-default">
                                        <div class="w-1.5 h-1.5 rounded-full bg-teal-500/40"></div>
                                        <?= $sub ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php 
                        endif;
                    endforeach; 

                    if(!$has_perms):
                    ?>
                        <div class="col-span-3 text-center py-4 text-slate-400 text-xs italic">
                            No special permissions assigned to this group.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    } else {
        echo '<div class="text-center py-8 text-slate-500">User not found!</div>';
    }
}
