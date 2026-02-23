<?php
// Get current page to highlight active menu item
$current_page = basename($_SERVER['PHP_SELF']);
$current_uri = $_SERVER['REQUEST_URI'];

// Count stock alerts
$alert_count = 0;
// Default Logo
$sidebar_logo = '/pos/assets/images/logo.png';

if(isset($conn)) {
    $alert_query = "SELECT COUNT(*) as alert_count 
                    FROM products p
                    JOIN product_store_map psm ON p.id = psm.product_id
                    WHERE p.status = 1 AND psm.stock <= p.alert_quantity";
    $alert_res = mysqli_query($conn, $alert_query);
    if($alert_res) {
        $alert_data = mysqli_fetch_assoc($alert_res);
        $alert_count = (int)$alert_data['alert_count'];
    }

    // Fetch Dynamic Logo
    $sid = 1; // Default store ID
    
    // 1. Try Session
    if(isset($_SESSION['store_id'])) { $sid = $_SESSION['store_id']; }
    
    // 2. Prefer Local Context (e.g. inside settings.php where $store_id is defined)
    if(isset($store_id) && is_numeric($store_id)) { 
        $sid = $store_id; 
    }
    
    // First try specific store
    $logo_types = mysqli_query($conn, "SELECT setting_value FROM pos_settings WHERE store_id = '$sid' AND setting_key = 'logo'");
    $found_logo = false;
    
    if($logo_types && mysqli_num_rows($logo_types) > 0) {
        $l_row = mysqli_fetch_assoc($logo_types);
        if(!empty($l_row['setting_value'])) {
             $db_logo = $l_row['setting_value'];
             
             // Check if file actually exists on server
             $server_path = $_SERVER['DOCUMENT_ROOT'] . $db_logo;
             
             // Also check default logo constraint
             if(strpos($db_logo, 'logo.png') !== false || !file_exists($server_path)) {
                 $found_logo = false; 
             } else {
                 $sidebar_logo = $db_logo;
                 $found_logo = true;
             }
        }
    }
    
    // Fallback: If no logo found (or file missing), scan for LATEST uploaded logo
    if(!$found_logo) {
         // Use __DIR__ for reliable path finding
         $upload_dir = __DIR__ . '/../uploads/branding/';
         
         if(is_dir($upload_dir)) {
             $files = glob($upload_dir . 'logo_*.*');
             if($files && count($files) > 0) {
                 // Sort by modification time, newest first
                 usort($files, function($a, $b) {
                     return filemtime($b) - filemtime($a);
                 });
                 
                 $latest_file = $files[0];
                 $filename = basename($latest_file);
                 
                 $sidebar_logo = '/pos/uploads/branding/' . $filename;
             }
         }
    }
}

// Helper to check if current uri matches
function uri_has($needle, $uri) {
    return strpos($uri, $needle) !== false;
}

// Menu items configuration
$menu_items = [
    [
        'title' => 'Dashboards',
        'icon' => 'fa-tachometer-alt', 
        'link' => '/pos',
        'active' => ($current_page == 'index.php' || $current_uri == '/pos'),
        'permission' => 'view_dashboard_dashboard' // Required Permission
    ],
    [
        'title' => 'Point of Sale',
        'icon' => 'fa-cash-register',
        'link' => '/pos/pos/',
        'active' => (uri_has('/pos/pos', $current_uri)),
        'permission' => 'create_sell_sell' // POS links to creating a sell
    ],
    [
        'title' => 'Sell',
        'icon' => 'fa-cash-register',
        'link' => '#',
        'submenu' => [ 
            ['title' => 'Sell List', 'link' => '/pos/sell/list', 'permission' => 'view_sell_list_sell'],
            ['title' => 'Return List', 'link' => '/pos/sell/return', 'permission' => 'view_return_list_sell'],
            ['title' => 'Sell Log', 'link' => '/pos/sell/log', 'permission' => 'read_sell_log_sell'],
        ],
        'active' => (uri_has('/sell/', $current_uri)),
        'permission' => 'view_sell_list_sell' // Base permission for parent (can be adjusted)
    ],
    [
        'title' => 'Giftcard',
        'icon' => 'fa-credit-card',
        'link' => '#',
        'submenu' => [
            ['title' => 'Add Giftcard', 'link' => '/pos/giftcard/add', 'permission' => 'add_giftcard_giftcard'],
            ['title' => 'Giftcard List', 'link' => '/pos/giftcard/list', 'permission' => 'read_giftcard_giftcard'],
            ['title' => 'Giftcard Topup', 'link' => '/pos/giftcard/topup', 'permission' => 'giftcard_topup_giftcard'],
        ],
        'active' => (uri_has('/giftcard/', $current_uri)),
        'permission' => 'read_giftcard_giftcard'
    ],
    [
        'title' => 'Installment',
        'icon' => 'fa-calendar-alt text-teal-400',
        'link' => '#',
        'submenu' => [
            ['title' => 'Installment List', 'link' => '/pos/installment/list', 'permission' => 'read_installment_list_installment'],
            ['title' => 'Payment List', 'link' => '/pos/installment/payments', 'permission' => 'read_installment_list_installment'],
            ['title' => 'Payment Due Today', 'link' => '/pos/installment/due_today', 'permission' => 'read_installment_list_installment'],
            ['title' => 'Payment Due All', 'link' => '/pos/installment/due_all', 'permission' => 'read_installment_list_installment'],
            ['title' => 'Payment Due Exp.', 'link' => '/pos/installment/due_expired', 'permission' => 'read_installment_list_installment'],
            ['title' => 'Overview Report', 'link' => '/pos/installment/overview', 'permission' => 'installment_overview_installment'],
        ],
        'active' => (uri_has('/installment/', $current_uri)),
        'permission' => 'read_installment_list_installment'
    ],
    [
        'title' => 'Products',
        'icon' => 'fa-box',
        'link' => '#',
        'submenu' => [
            ['title' => 'Add Product', 'link' => '/pos/products/add', 'permission' => 'create_product_product'],
            ['title' => 'Product List', 'link' => '/pos/products/list', 'permission' => 'read_product_list_product'],
            ['title' => 'Barcode Print', 'link' => '/pos/products/barcode-print', 'permission' => 'barcode_print_product'],
            ['title' => 'Stock Alert', 'link' => '/pos/products/stock_alert', 'badge' => $alert_count, 'permission' => 'read_stock_alert_product']
        ],
        'active' => (uri_has('/products/', $current_uri)),
        'permission' => 'read_product_list_product'
    ],
    [
        'title' => 'Quotations',
        'icon' => 'fa-file-alt',
        'link' => '#',
        'submenu' => [
            ['title' => 'Add Quotation', 'link' => '/pos/quotations/add', 'permission' => 'create_quotation_quotation'],
            ['title' => 'Quotation List', 'link' => '/pos/quotations/list', 'permission' => 'read_quotation_list_quotation']
        ],
        'active' => (uri_has('/quotations/', $current_uri)),
        'permission' => 'read_quotation_list_quotation'
    ],
    [
        'title' => 'Purchases',
        'icon' => 'fa-shopping-cart',
        'link' => '#',
        'submenu' => [
            ['title' => 'Add Purchase', 'link' => '/pos/purchases/add', 'permission' => 'create_invoice_purchase'],
            ['title' => 'Purchase List', 'link' => '/pos/purchases/list', 'permission' => 'view_invoice_list_purchase'],
            ['title' => 'Due Invoice', 'link' => '/pos/purchases/list?filter=due', 'permission' => 'view_invoice_list_purchase'],
            ['title' => 'Return List', 'link' => '/pos/purchases/return-list', 'permission' => 'view_return_list_purchase'],
            ['title' => 'Purchase Logs', 'link' => '/pos/purchases/purchase-logs', 'permission' => 'read_purchase_log_purchase'],
        ],
        'active' => (uri_has('/purchases/', $current_uri)),
        'permission' => 'view_invoice_list_purchase'
    ],
    [
        'title' => 'Transfer',
        'icon' => 'fa-exchange-alt',
        'link' => '#',
        'submenu' => [
            ['title' => 'Add Transfer', 'link' => '/pos/transfer/stock_transfer', 'permission' => 'add_transfer_transfer'],
            ['title' => 'Transfer List', 'link' => '/pos/transfer/transfer_list', 'permission' => 'read_transfer_transfer'],
            ['title' => 'Receive List', 'link' => '/pos/transfer/receive_list', 'permission' => 'read_receive_list_transfer'],
        ],
        'active' => (uri_has('/transfer/', $current_uri)),
        'permission' => 'read_transfer_transfer'
    ],
    [
        'title' => 'Stores',
        'icon' => 'fa-store',
        'link' => '#',
        'submenu' => [
            ['title' => 'Add Store', 'link' => '/pos/stores/add', 'permission' => 'create_store_store'],
            ['title' => 'Store List', 'link' => '/pos/stores/list', 'permission' => 'read_store_list_store'],
            ['title' => 'Store Settings', 'link' => '/pos/stores/settings', 'permission' => 'view_store_settings_settings']
        ],
        'active' => (uri_has('/stores/', $current_uri)),
        'permission' => 'read_store_list_store'
    ],
    [
        'title' => 'Categories',
        'icon' => 'fa-th-list', 
        'link' => '#',
        'submenu' => [
            ['title' => 'Add Category', 'link' => '/pos/categories/add', 'permission' => 'create_category_category'],
            ['title' => 'Category List', 'link' => '/pos/categories/list', 'permission' => 'read_category_list_category']
        ],
        'active' => (uri_has('/categories/', $current_uri)),
        'permission' => 'read_category_list_category'
    ],
    [
        'title' => 'Currency',
        'icon' => 'fa-dollar-sign',
        'link' => '#',
        'submenu' => [
            ['title' => 'Add Currency', 'link' => '/pos/currency/add', 'permission' => 'add_currency_currency'],
            ['title' => 'Currency List', 'link' => '/pos/currency/list', 'permission' => 'read_currency_currency']
        ],
        'active' => (uri_has('/currency/', $current_uri)),
        'permission' => 'read_currency_currency'
    ],
    [
        'title' => 'Payment Methods',
        'icon' => 'fa-credit-card',
        'link' => '#',
        'submenu' => [
            ['title' => 'Add Payment', 'link' => '/pos/payment-methods/add', 'permission' => 'create_payment_method_payment_method'],
            ['title' => 'Payment List', 'link' => '/pos/payment-methods/list', 'permission' => 'read_payment_method_list_payment_method']
        ],
        'active' => (uri_has('/payment-methods/', $current_uri) || uri_has('/payment_methods/', $current_uri)),
        'permission' => 'read_payment_method_list_payment_method'
    ],
    [
        'title' => 'Units',
        'icon' => 'fa-balance-scale',
        'link' => '#',
        'submenu' => [
            ['title' => 'Add Unit', 'link' => '/pos/units/add', 'permission' => 'create_unit_unit'],
            ['title' => 'Unit List', 'link' => '/pos/units/list', 'permission' => 'read_unit_unit']
        ],
        'active' => (uri_has('/units/', $current_uri)),
        'permission' => 'read_unit_unit'
    ],
    [
        'title' => 'Brands',
        'icon' => 'fa-tags',
        'link' => '#',
        'submenu' => [
            ['title' => 'Add Brand', 'link' => '/pos/brands/add', 'permission' => 'create_brand_brand'],
            ['title' => 'Brand List', 'link' => '/pos/brands/list', 'permission' => 'read_brand_list_brand']
        ],
        'active' => (uri_has('/brands/', $current_uri)),
        'permission' => 'read_brand_list_brand'
    ],
    [
        'title' => 'Tax Rates',
        'icon' => 'fa-percent',
        'link' => '#',
        'submenu' => [
            ['title' => 'Add Taxrate', 'link' => '/pos/taxrates/add', 'permission' => 'create_taxrate_taxrate'],
            ['title' => 'Taxrate List', 'link' => '/pos/taxrates/list', 'permission' => 'read_taxrate_taxrate']
        ],
        'active' => (uri_has('/taxrates/', $current_uri)),
        'permission' => 'read_taxrate_taxrate'
    ],
    [
        'title' => 'Boxes',
        'icon' => 'fa-box', 
        'link' => '#',
        'submenu' => [
            ['title' => 'Add Box', 'link' => '/pos/boxes/add', 'permission' => 'create_box_storebox'],
            ['title' => 'Box List', 'link' => '/pos/boxes/list', 'permission' => 'read_box_storebox']
        ],
        'active' => (uri_has('/boxes/', $current_uri)),
        'permission' => 'read_box_storebox'
    ],
    [
        'title' => 'Suppliers',
        'icon' => 'fa-truck',
        'link' => '#',
        'submenu' => [
            ['title' => 'Add Supplier', 'link' => '/pos/suppliers/add', 'permission' => 'create_supplier_supplier'],
            ['title' => 'Supplier List', 'link' => '/pos/suppliers/list', 'permission' => 'read_supplier_list_supplier']
        ],
        'active' => (uri_has('/suppliers/', $current_uri)),
        'permission' => 'read_supplier_list_supplier'
    ],
    [
        'title' => 'Customers',
        'icon' => 'fa-user-friends', 
        'link' => '#',
        'submenu' => [
            ['title' => 'Add Customer', 'link' => '/pos/customers/add', 'permission' => 'create_customer_customer'],
            ['title' => 'Customer List', 'link' => '/pos/customers/list', 'permission' => 'read_customer_list_customer']
        ],
        'active' => (uri_has('/customers/', $current_uri)),
        'permission' => 'read_customer_list_customer'
    ],
    [
        'title' => 'Users',
        'icon' => 'fa-users',
        'link' => '#',
        'submenu' => [
            ['title' => 'Add User', 'link' => '/pos/users/add', 'permission' => 'create_user_user'],
            ['title' => 'User List', 'link' => '/pos/users/list', 'permission' => 'read_user_list_user'],
            ['title' => 'User Groups', 'link' => '/pos/users/groups', 'permission' => 'read_usergroup_list_usergroup'],
        ],
        'active' => (uri_has('/users/', $current_uri)),
        'permission' => 'read_user_list_user'
    ],
    [
        'title' => 'Accounting',
        'icon' => 'fa-university',
        'link' => '#',
        'submenu' => [
            ['title' => 'Bank List', 'link' => '/pos/accounting/bank/list', 'permission' => 'view_bank_account_accounting'],
            ['title' => 'Bank Transaction', 'link' => '/pos/accounting/bank/transaction-list', 'permission' => 'view_bank_transactions_accounting'],
            ['title' => 'Bank Transfer', 'link' => '/pos/accounting/bank/transfer-list', 'permission' => 'view_bank_transfer_accounting'],
            ['title' => 'Balance Sheet', 'link' => '/pos/accounting/bank/balance-sheet', 'permission' => 'view_bank_account_sheet_accounting'],
            ['title' => 'Add Bank', 'link' => '/pos/accounting/bank/add', 'permission' => 'create_bank_account_accounting'],
            ['title' => 'Income Source List', 'link' => '/pos/accounting/income-source/list', 'permission' => 'view_income_source_accounting'],
            ['title' => 'Add Income Source', 'link' => '/pos/accounting/income-source/add', 'permission' => 'create_income_source_accounting'],
            ['title' => 'Deposit', 'link' => '#', 'onclick' => "openModal('depositModal'); return false;", 'permission' => 'deposit_accounting'],
            ['title' => 'Withdraw', 'link' => '#', 'onclick' => "openModal('withdrawModal'); return false;", 'permission' => 'withdraw_accounting'],
            ['title' => 'Add Transfer', 'link' => '#', 'onclick' => "openModal('transferModal'); return false;", 'permission' => 'transfer_accounting'],
            ['title' => 'Income Monthwise', 'link' => '/pos/accounting/income-monthwise', 'permission' => 'income_monthwise_accounting'],
            ['title' => 'Expense Monthwise', 'link' => '/pos/accounting/expense-monthwise', 'permission' => 'expense_monthwise_expenditure'],
            ['title' => 'Income vs Expense', 'link' => '/pos/accounting/income-vs-expense', 'permission' => 'income_&_expense_accounting'],
            ['title' => 'Profit vs Loss', 'link' => '/pos/accounting/profit-loss', 'permission' => 'profit_&_loss_accounting'],
            ['title' => 'Cashbook', 'link' => '/pos/accounting/cashbook', 'permission' => 'cashbook_accounting'],
        ],
        'active' => (uri_has('/Accounting/', $current_uri)),
        'permission' => 'view_bank_account_accounting'
    ],
    [
        'title' => 'Loan',
        'icon' => 'fa-hand-holding-usd',
        'link' => '#',
        'submenu' => [
            ['title' => 'Take Loan', 'link' => '/pos/loan/add', 'permission' => 'take_loan_loan'],
            ['title' => 'Loan List', 'link' => '/pos/loan/list', 'permission' => 'read_loan_loan'],
            ['title' => 'Loan Summary', 'link' => '/pos/loan/summary', 'permission' => 'read_loan_summary_loan'],
        ],
        'active' => (uri_has('/loan/', $current_uri)),
        'permission' => 'read_loan_loan'
    ],
    [
        'title' => 'Expenditure',
        'icon' => 'fa-wallet',
        'link' => '#',
        'submenu' => [
            ['title' => 'Add Category', 'link' => '/pos/expenditure/category_add', 'permission' => 'create_expense_category_expenditure'],
            ['title' => 'Category List', 'link' => '/pos/expenditure/category_list', 'permission' => 'read_expense_category_expenditure'],
            ['title' => 'Add Expense', 'link' => '/pos/expenditure/add', 'permission' => 'create_expense_expenditure'],
            ['title' => 'Expense List', 'link' => '/pos/expenditure/list', 'permission' => 'read_expense_expenditure'],
            ['title' => 'Monthwise Report', 'link' => '/pos/expenditure/monthwise', 'permission' => 'expense_monthwise_expenditure'],
        ],
        'active' => (uri_has('/expenditure/', $current_uri)),
        'permission' => 'read_expense_expenditure'
    ],
    [
        'title' => 'Reports',
        'icon' => 'fa-chart-pie',
        'link' => '#',
        'submenu' => [
            ['title' => 'Overview Report', 'link' => '/pos/reports/overview', 'permission' => 'overview_report_report'],
            ['title' => 'Collection Report', 'link' => '/pos/reports/collection', 'permission' => 'collection_report_report'],
            ['title' => 'Due Collection Rpt', 'link' => '/pos/reports/due-collection', 'permission' => 'due_collection_report_report'],
            ['title' => 'Due Paid Rpt', 'link' => '/pos/reports/due-paid', 'permission' => 'due_paid_report_report'],
            ['title' => 'Sell Report', 'link' => '/pos/reports/sell', 'permission' => 'sell_report_report'],
            ['title' => 'Purchase Report', 'link' => '/pos/reports/purchase', 'permission' => 'purchase_report_report'],
            ['title' => 'Sell Payment Report', 'link' => '/pos/reports/sell-payment', 'permission' => 'sell_payment_report_report'],
            ['title' => 'Pur. Payment Rpt.', 'link' => '/pos/reports/purchase-payment', 'permission' => 'purchase_payment_report_report'],
            ['title' => 'Sell Tax Report', 'link' => '/pos/reports/sell-tax', 'permission' => 'sell_tax_report_report'],
            ['title' => 'Purchase Tax Report', 'link' => '/pos/reports/purchase-tax', 'permission' => 'purchase_tax_report_report'],
            ['title' => 'Tax Overview Rpt.', 'link' => '/pos/reports/tax-overview', 'permission' => 'tax_overview_report_report'],
            ['title' => 'Stock Report', 'link' => '/pos/reports/stock', 'permission' => 'stock_report_report'],
        ],
        'active' => (uri_has('/reports/', $current_uri)),
        'permission' => 'overview_report_report'
    ],
    [
        'title' => 'System',
        'icon' => 'fa-cog',
        'link' => '#',
        'submenu' => [
            ['title' => 'Add Printer', 'link' => '/pos/printer/add', 'permission' => 'add_printer_printer'],
            ['title' => 'Printer List', 'link' => '/pos/printer/list', 'permission' => 'view_printer_printer'],
            ['title' => 'Receipt Templates', 'link' => '/pos/printer/receipt_templates', 'permission' => 'receipt_template_settings'],
        ],
        'active' => (uri_has('/system/', $current_uri)),
        'permission' => 'view_general_settings_settings' // Default to general settings permission
    ],
    [
        'title' => 'Help & Support',
        'icon' => 'fa-question-circle',
        'link' => '#',
        'submenu' => [
            ['title' => 'Documentation', 'link' => '/pos/documentation', 'permission' => 'documentation_help_support'],
            ['title' => 'User Guide', 'link' => '/pos/guide', 'permission' => 'user_guide_help_support'],
            ['title' => 'Support', 'link' => '/pos/support', 'permission' => 'support_help_support'],
            ['title' => 'Terms of Service', 'link' => '/pos/terms', 'permission' => 'terms_of_service_help_support'],
            ['title' => 'Privacy Policy', 'link' => '/pos/privacy', 'permission' => 'privacy_policy_help_support'],
        ],
        'active' => (uri_has('/guide', $current_uri) || uri_has('/support', $current_uri) || uri_has('/terms', $current_uri) || uri_has('/privacy', $current_uri)),
        'permission' => 'read_help_support_help_support'
    ]
];
?>



<style>
/* --- Sidebar Base Styles --- */
.sidebar {
    width: 256px;
    transition: width 300ms cubic-bezier(0.4, 0, 0.2, 1);
    background-color: rgba(15, 23, 42, 0.95);
    backdrop-filter: blur(8px);
    z-index: 30000; 
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    overflow: visible !important; /* Allow toggle button to stick out */
}

.sidebar.collapsed { width: 80px; }

/* Desktop Hidden Elements */
@media (min-width: 1024px) {
    .sidebar.collapsed .link-text, 
    .sidebar.collapsed .logo-text-container,
    .sidebar.collapsed .fa-chevron-right {
        opacity: 0 !important;
        display: none !important;
        visibility: hidden !important;
    }

    .sidebar.collapsed .menu-group:hover .submenu {
        display: block !important;
        position: absolute;
        left: 70px;
        top: 0;
        width: 200px;
        background: #0f172a;
        border-radius: 0 8px 8px 0;
        padding: 10px;
        z-index: 1100;
        box-shadow: 10px 0 15px rgba(0,0,0,0.3);
    }
}

/* Mobile Responsiveness */
@media (max-width: 1023px) {
    #sidebar-toggle-container { display: none !important; } 
    .sidebar { transform: translateX(-100%); width: 256px !important; }
    .sidebar.sidebar-open { transform: translateX(0) !important; }
    
   
    .sidebar .link-text, .sidebar .logo-text-container {
        opacity: 1 !important;
        display: block !important;
        visibility: visible !important;
        width: auto !important;
    }
}

/* Mobile Overlay */
#sidebar-overlay {
    display: none; 
    position: fixed;
    inset: 0; 
    background: rgba(0, 0, 0, 0.4); 
    backdrop-filter: blur(2px);
    z-index: 900; 
}
#sidebar-overlay.active { display: block !important; }

#sidebar-toggle-container { position: absolute; right: -12px; top: 25px; z-index: 50; }

.active-submenu-bg {
    background-color: rgba(13, 148, 136, 0.3); 
    color: white;
    border-left: 4px solid #0d9488;
}

.rotate-90 { transform: rotate(90deg); }
.rotate-180 { transform: rotate(180deg); }

.custom-scroll::-webkit-scrollbar { width: 4px; }
.custom-scroll::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.1); border-radius: 10px; }
</style>

<div id="sidebar-overlay" onclick="toggleSidebarMobile()"></div>

<aside id="sidebar" class="sidebar min-h-screen fixed left-0 top-0 lg:translate-x-0 transition-transform duration-300 ease-in-out">
    <div class="flex items-center gap-3 px-4 py-4 h-[81px] border-b border-white/5 relative group">
    <div class="w-10 h-10 rounded-xl shrink-0 bg-gradient-to-br from-teal-900 via-teal-800 from-white/10 to-white/5 border border-white/20 shadow-inner flex items-center justify-center overflow-hidden transition-transform duration-300 group-hover:scale-105">
        <img src="<?= $sidebar_logo; ?>" onerror="this.src='/pos/assets/images/logo.png'" alt="Logo" class="w-full h-full object-contain" />
    </div>

    <div class="logo-text-container overflow-hidden flex-1 select-none">
        <h1 class="logo-text text-lg font-extrabold text-white tracking-tight leading-tight whitespace-nowrap">
         <span class="text-teal-400">POS</span>
        </h1>
        <div class="flex items-center gap-1.5">
            <span class="w-1 h-1 rounded-full bg-teal-500 animate-pulse"></span>
            <p class="logo-subtitle text-[9px] text-white/50 uppercase font-bold tracking-[0.1em]">Inventory Hub</p>
        </div>
    </div>
        
        <div id="sidebar-toggle-container">
            <button id="sidebar-toggle" class="w-6 h-6 bg-teal-600 text-white rounded-full flex items-center justify-center shadow-lg hover:bg-teal-500">
                <i class="fas fa-chevron-left text-[10px]" id="toggle-icon"></i>
            </button>
        </div>
    </div>
    
    <nav class="p-4 space-y-2 overflow-y-auto h-[calc(100vh-80px)] custom-scroll">
        <?php foreach($menu_items as $item): ?>
            <?php 
            // 1. Check Main Module Permission
            if(isset($item['permission']) && !check_user_permission($item['permission'])) {
                continue; // Skip if user doesn't have the main module permission
            }

            $has_submenu = isset($item['submenu']);
            $link_classes = $item['active'] ? 'active-submenu-bg text-white shadow-lg' : 'text-white/70 hover:bg-white/10 hover:text-white';
            ?>
            <div class="mb-1 relative menu-group">
                <?php 
                // Pre-calculate if any submenu item is active (needed for chevron rotation)
                $submenu_active = false;
                if($has_submenu) {
                    foreach($item['submenu'] as $sub) {
                        if(uri_has($sub['link'], $current_uri)) {
                            $submenu_active = true;
                            break;
                        }
                    }
                }
                ?>
                <a href="<?= $has_submenu ? '#' : $item['link']; ?>" 
                   class="flex items-center justify-between px-4 py-3 rounded-lg transition-all duration-200 group <?= $link_classes; ?>"
                   <?= $has_submenu ? 'onclick="toggleSubmenu(this); event.preventDefault();"' : ''; ?>>
                    <div class="flex items-center gap-3">
                        <i class="fas <?= $item['icon']; ?> w-5 shrink-0 text-center"></i>
                        <span class="link-text font-medium text-sm whitespace-nowrap"><?= $item['title']; ?></span>
                    </div>
                    <?php if($has_submenu): ?>
                        <i class="link-text fas fa-chevron-right text-[10px] transition-transform duration-200 <?= $submenu_active ? 'rotate-90' : ''; ?>"></i>
                    <?php endif; ?>
                </a>
                
                <?php if($has_submenu): ?>
                    <?php 
                    // Check if any submenu item is actually active
                    $submenu_active = false;
                    foreach($item['submenu'] as $sub) {
                        if(uri_has($sub['link'], $current_uri)) {
                            $submenu_active = true;
                            break;
                        }
                    }
                    ?>
                    <div class="submenu ml-4 mt-1 space-y-1 <?= $submenu_active ? '' : 'hidden'; ?>">
                        <?php foreach($item['submenu'] as $sub): ?>
                            <?php 
                            // 2. Check Submenu Item Permission
                            if(isset($sub['permission']) && !check_user_permission($sub['permission'])) {
                                continue; 
                            }
                            ?>
                            <a href="<?= $sub['link']; ?>" 
                               <?= isset($sub['onclick']) ? 'onclick="' . $sub['onclick'] . '"' : ''; ?>
                               class="flex items-center justify-between px-4 py-2 rounded-lg text-xs transition-all duration-200 <?= (uri_has($sub['link'], $current_uri)) ? 'text-teal-400 font-bold' : 'text-white/50 hover:text-white'; ?>">
                                <div class="flex items-center gap-3">
                                    <i class="fas fa-circle text-[4px] shrink-0"></i>
                                    <span class="link-text"><?= $sub['title']; ?></span>
                                </div>
                                <?php if(isset($sub['badge']) && $sub['badge'] > 0): ?>
                                    <span class="bg-orange-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full min-w-[20px] text-center"><?= $sub['badge']; ?></span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </nav>
</aside>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.getElementById('sidebar-toggle');
        const toggleIcon = document.getElementById('toggle-icon');
        const overlay = document.getElementById('sidebar-overlay');
        const mainContent = document.getElementById('main-content');
        
        // Touch variables for swipe support
        let touchStartX = 0;
        let touchEndX = 0;

        // Retrieve sidebar lock state from local storage
        let isLocked = localStorage.getItem('sidebarLocked') === 'true'; 

        /**
         * INITIALIZATION: Apply layout state on load
         */
        if (isLocked) {
            sidebar.classList.remove('collapsed');
            if(mainContent) mainContent.style.marginLeft = '256px';
            toggleIcon.classList.remove('rotate-180');
        } else {
            sidebar.classList.add('collapsed');
            if(mainContent) mainContent.style.marginLeft = '80px';
            toggleIcon.classList.add('rotate-180');
        }

        /**
         * DESKTOP HOVER LOGIC
         */
        sidebar.addEventListener('mouseenter', () => {
            if (window.innerWidth >= 1024 && !isLocked) {
                sidebar.classList.remove('collapsed');
                if(mainContent) mainContent.style.marginLeft = '256px';
                toggleIcon.classList.remove('rotate-180');
            }
        });

        sidebar.addEventListener('mouseleave', () => {
            if (window.innerWidth >= 1024 && !isLocked) {
                sidebar.classList.add('collapsed');
                if(mainContent) mainContent.style.marginLeft = '80px';
                toggleIcon.classList.add('rotate-180');
            }
        });

        /**
         * TOGGLE LOCK BUTTON
         */
        if(toggleBtn) {
            toggleBtn.addEventListener('click', (e) => {
                e.stopPropagation(); // Prevent event bubbling
                isLocked = !isLocked; 
                localStorage.setItem('sidebarLocked', isLocked);

                if (isLocked) {
                    sidebar.classList.remove('collapsed');
                    if(mainContent) mainContent.style.marginLeft = '256px';
                    toggleIcon.classList.remove('rotate-180');
                } else {
                    sidebar.classList.add('collapsed');
                    if(mainContent) mainContent.style.marginLeft = '80px';
                    toggleIcon.classList.add('rotate-180');
                }
            });
        }

        /**
         * GLOBAL CLICK LISTENER: Closes sidebar if clicking outside
         */
        window.addEventListener('click', (e) => {
            const isMobile = window.innerWidth < 1024;
            // Check if sidebar is open and click is outside sidebar and toggle button
            if (isMobile && sidebar.classList.contains('sidebar-open')) {
                if (!sidebar.contains(e.target) && !e.target.closest('button')) {
                    closeMobileSidebar();
                }
            }
        });

        // Overlay click fallback
        if (overlay) {
            overlay.addEventListener('click', closeMobileSidebar);
        }

        /**
         * MOBILE SWIPE GESTURES
         */
        document.addEventListener('touchstart', e => {
            touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });

        document.addEventListener('touchend', e => {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        }, { passive: true });

        function handleSwipe() {
            const swipeDistance = touchEndX - touchStartX;
            if (window.innerWidth >= 1024) return;

            if (swipeDistance > 100 && touchStartX < 50) {
                if (!sidebar.classList.contains('sidebar-open')) toggleSidebarMobile();
            }
            if (swipeDistance < -100) {
                if (sidebar.classList.contains('sidebar-open')) closeMobileSidebar();
            }
        }

        function closeMobileSidebar() {
            sidebar.classList.remove('sidebar-open');
            if (overlay) overlay.classList.remove('active');
            document.body.style.overflow = ''; // Restore scroll
        }

        /**
         * INITIALIZE SUBMENU STATE FROM LOCALSTORAGE
         */
        initializeSubmenuState();
        
        /**
         * SCROLL TO ACTIVE ITEM AFTER INITIALIZATION
         */
        setTimeout(() => {
            scrollToActiveSubmenuItem();
        }, 300);
    });
    
    // Also try on window load as backup
    window.addEventListener('load', () => {
        setTimeout(() => {
            scrollToActiveSubmenuItem();
        }, 100);
    });

    /**
     * INITIALIZE SUBMENU STATE
     */
    function initializeSubmenuState() {
        const openSubmenu = localStorage.getItem('openSubmenu');
        if (openSubmenu) {
            // Find the menu item with matching title
            const menuLinks = document.querySelectorAll('.menu-group > a');
            menuLinks.forEach(link => {
                const titleElement = link.querySelector('.link-text');
                if (titleElement && titleElement.textContent.trim() === openSubmenu) {
                    const submenu = link.nextElementSibling;
                    const chevron = link.querySelector('.fa-chevron-right');
                    if (submenu && submenu.classList.contains('submenu')) {
                        // Only open if there's an active submenu item (not hidden by PHP)
                        const hasActiveItem = submenu.querySelector('a.text-teal-400, a.font-bold');
                        const isAlreadyVisible = !submenu.classList.contains('hidden');
                        
                        if (hasActiveItem || isAlreadyVisible) {
                            submenu.classList.remove('hidden');
                            if (chevron) chevron.classList.add('rotate-90');
                        } else {
                            // Clear localStorage if the submenu shouldn't be open
                            localStorage.removeItem('openSubmenu');
                        }
                    }
                }
            });
        }
    }
    
    /**
     * SCROLL TO ACTIVE SUBMENU ITEM
     */
    function scrollToActiveSubmenuItem() {
        const sidebarNav = document.querySelector('aside nav');
        if (!sidebarNav) return;
        
        // Find any active submenu item
        const activeSubmenuItem = sidebarNav.querySelector('.submenu a.text-teal-400, .submenu a.font-bold');
        if (!activeSubmenuItem) return;
        
        // Calculate the actual offset by walking up the DOM tree
        let itemOffsetTop = 0;
        let element = activeSubmenuItem;
        
        while (element && element !== sidebarNav) {
            itemOffsetTop += element.offsetTop;
            element = element.offsetParent;
        }
        
        const navHeight = sidebarNav.clientHeight;
        const itemHeight = activeSubmenuItem.clientHeight;
        
        // Calculate scroll position to center the item
        const scrollPosition = itemOffsetTop - (navHeight / 2) + (itemHeight / 2);
        
        // Smooth scroll to position
        sidebarNav.scrollTo({
            top: scrollPosition,
            behavior: 'smooth'
        });
    }
    
    function toggleSidebarMobile() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        if (!sidebar || !overlay) return;

        sidebar.classList.toggle('sidebar-open');
        overlay.classList.toggle('active');
        document.body.style.overflow = sidebar.classList.contains('sidebar-open') ? 'hidden' : '';
    }

    /**
     * SUBMENU HANDLER WITH LOCALSTORAGE PERSISTENCE
     */
    function toggleSubmenu(element) {
        const sidebar = document.getElementById('sidebar');
        if (sidebar.classList.contains('collapsed') && window.innerWidth >= 1024) return;
        
        const submenu = element.nextElementSibling;
        const chevron = element.querySelector('.fa-chevron-right');
        const menuTitle = element.querySelector('.link-text').textContent.trim();
        
        if (submenu) {
            const isHidden = submenu.classList.contains('hidden');
            submenu.classList.toggle('hidden');
            
            // Save state to localStorage
            if (isHidden) {
                // Opening submenu - close all other submenus first
                document.querySelectorAll('.submenu').forEach(sm => {
                    if (sm !== submenu && !sm.classList.contains('hidden')) {
                        sm.classList.add('hidden');
                        const otherChevron = sm.previousElementSibling?.querySelector('.fa-chevron-right');
                        if (otherChevron) otherChevron.classList.remove('rotate-90');
                    }
                });
                localStorage.setItem('openSubmenu', menuTitle);
            } else {
                // Closing submenu
                localStorage.removeItem('openSubmenu');
            }
        }
        
        if (chevron) chevron.classList.toggle('rotate-90');
    }
</script>