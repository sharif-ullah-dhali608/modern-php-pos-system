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
        
        // Modules mapping (simplified for display)
        $display_modules = [
            'report' => 'Reports',
            'accounting' => 'Accounting',
            'quotation' => 'Quotations',
            'installment' => 'Installments',
            'expenditure' => 'Expenses',
            'sell' => 'Sells',
            'purchase' => 'Purchases',
            'transfer' => 'Transfers',
            'giftcard' => 'Gift Cards',
            'product' => 'Products',
            'supplier' => 'Suppliers',
            'brand' => 'Brands',
            'storebox' => 'Boxes',
            'unit' => 'Units',
            'taxrate' => 'Tax Rates',
            'loan' => 'Loans',
            'customer' => 'Customers',
            'user' => 'Users',
            'usergroup' => 'User Groups',
            'currency' => 'Currencies',
            'filemanager' => 'Files',
            'payment_method' => 'Payments',
            'store' => 'Stores',
            'printer' => 'Printers',
            'sms' => 'SMS/Email',
            'settings' => 'Settings'
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
                <div class="grid grid-cols-3 gap-2">
                    <?php 
                    $has_perms = false;
                    foreach($display_modules as $mod_key => $mod_val): 
                        // Check if ANY sub-permission for this module is true
                        $mod_access = false;
                        foreach($access as $perm_key => $perm_val) {
                            if(strpos($perm_key, "_".$mod_key) !== false && $perm_val == "true") {
                                $mod_access = true;
                                break;
                            }
                        }
                        if($mod_access):
                            $has_perms = true;
                    ?>
                        <div class="flex items-center gap-2 p-2 rounded-lg bg-emerald-50/50 border border-emerald-100">
                            <i class="fas fa-check-circle text-emerald-500 text-[10px]"></i>
                            <span class="text-[10px] font-bold text-slate-600"><?= $mod_val; ?></span>
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
