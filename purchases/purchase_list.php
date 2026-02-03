<?php
session_start();
include('../config/dbcon.php');

// Security Check
if(!isset($_SESSION['auth'])){
    header("Location: /pos/login");
    exit(0);
}

// Get filter parameter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Filter Labels Map
$filter_labels = [
    'all' => 'Filter', // Default label
    'today' => 'Today Invoice',
    'due' => 'Due Invoice',
    'all_due' => 'All Due Invoice',
    'paid' => 'Paid Invoice',
    'inactive' => 'Inactive Invoice'
];
$active_filter_label = $filter_labels[$filter] ?? 'Filter';

/**

 * Added Subqueries to calculate total bought vs total returned.
 * Used HAVING to hide invoices where everything has been returned.
 */
$query = "SELECT pi.*, 
                 s.name as supplier_name,
                 u.name as creator_name,
                 (SELECT SUM(item_quantity) FROM purchase_item WHERE invoice_id = pi.invoice_id) as total_qty_bought,
                 (SELECT SUM(return_quantity) FROM purchase_item WHERE invoice_id = pi.invoice_id) as total_qty_returned
          FROM purchase_info pi
          LEFT JOIN suppliers s ON pi.sup_id = s.id
          LEFT JOIN users u ON pi.created_by = u.id
          WHERE 1=1";

// Apply existing filters
if($filter == 'today') {
    $query .= " AND DATE(pi.created_at) = CURDATE()";
} elseif($filter == 'due' || $filter == 'all_due') {
    $query .= " AND pi.payment_status = 'due'";
} elseif($filter == 'paid') {
    $query .= " AND pi.payment_status = 'paid'";
} elseif($filter == 'inactive') {
    $query .= " AND pi.is_visible = 0";
} else {
    $query .= " AND pi.is_visible = 1";
}

/**

 * If total items bought minus total items returned is 0, 
 * it means the invoice is fully returned and should be hidden from the list.
 */
$query .= " HAVING (total_qty_bought - total_qty_returned) > 0";

$query .= " ORDER BY pi.created_at DESC";

$query_run = mysqli_query($conn, $query);
$items = [];

// Reset totals for summary cards
$total_amount = 0;
$total_paid = 0;
$total_due = 0;
$total_stock_qty = 0;

if($query_run) {
    while($row = mysqli_fetch_assoc($query_run)) {
        $invoice_id = mysqli_real_escape_string($conn, $row['invoice_id']);
        
        // Calculate paid amount from logs
        $paid_query = "SELECT COALESCE(SUM(amount), 0) as total_paid 
                       FROM purchase_logs 
                       WHERE ref_invoice_id = '$invoice_id' AND type = 'purchase'";
        $paid_result = mysqli_query($conn, $paid_query);
        $paid_data = mysqli_fetch_assoc($paid_result);
        $paid_amount = floatval($paid_data['total_paid']);
        
        // Use total_sell from info table as the source of truth for grand total
        $amount = floatval($row['total_sell']);        
        $due_amount = $amount - $paid_amount;  

        // Ensure Due doesn't show negative if overpaid
        // $due_display_val = ($due_amount < 0) ? 0 : $due_amount;
        
        // Format data for table display
        $row['formatted_datetime'] = date('Y-m-d H:i:s', strtotime($row['created_at']));
        $row['invoice_id_display'] = $row['invoice_id'];
        $row['supplier_display'] = !empty($row['supplier_name']) ? $row['supplier_name'] : 'No Supplier';
        $row['creator_display'] = !empty($row['creator_name']) ? $row['creator_name'] : 'Admin';
        $row['amount_display'] = number_format($amount, 2);
        $row['paid_display'] = number_format($paid_amount, 2);
        $row['due_display'] = number_format($due_amount, 2);
        $row['stock_display'] = number_format($row['total_qty_bought'], 0);
        
        // Status badge logic
        // Status badge
        if($row['payment_status'] == 'paid') {
            $status_class = 'bg-green-100 text-green-700';
        } elseif($row['payment_status'] == 'receivable') {
            $status_class = 'bg-blue-100 text-blue-700';
        } else {
            $status_class = 'bg-red-100 text-red-700';
        }
        $status_text = ucfirst($row['payment_status']);
        $row['status_badge'] = '<span class="px-2 py-1 rounded-full text-xs font-bold ' . $status_class . '">' . $status_text . '</span>';
        
        // Raw values for footer totals
        $row['amount_raw'] = $amount;
        $row['paid_raw'] = $paid_amount;
        $row['due_raw'] = $due_amount;
        
        // Action Buttons
        $row['actions_html'] = '';
        if($due_amount > 0) {
            $row['actions_html'] .= '<button type="button" onclick="openPaymentModal(\'' . $row['invoice_id'] . '\')" class="p-2 text-green-500 hover:bg-green-50 rounded transition" title="Pay"><i class="fas fa-dollar-sign"></i></button>';
        }
        $row['actions_html'] .= '<button type="button" onclick="openReturnModal(\'' . $row['invoice_id'] . '\')" class="p-2 text-orange-500 hover:bg-orange-50 rounded transition" title="Return"><i class="fas fa-arrow-left"></i></button>';
        $row['actions_html'] .= '<button type="button" onclick="viewPurchase(\'' . $row['invoice_id'] . '\')" class="p-2 text-blue-500 hover:bg-blue-50 rounded transition" title="View"><i class="fas fa-eye"></i></button>';
        $row['actions_html'] .= '<a href="/pos/purchases/add?invoice_id=' . $row['invoice_id'] . '" class="p-2 text-teal-600 hover:bg-teal-50 rounded transition" title="Edit"><i class="fas fa-edit"></i></a>';
        $row['actions_html'] .= '<button type="button" onclick="confirmDelete(\'' . $row['invoice_id'] . '\', \'' . addslashes($row['invoice_id']) . '\', \'/pos/purchases/save_purchase.php\')" class="p-2 text-red-500 hover:bg-red-50 rounded transition" title="Delete"><i class="fas fa-trash-alt"></i></button>';
        
        $items[] = $row;
        
        // Add to aggregate totals for summary cards
        $total_amount += $amount;
        $total_paid += $paid_amount;
        $total_due += $due_amount;

        $current_stock = floatval($row['total_qty_bought']) - floatval($row['total_qty_returned']);
        $row['stock_display'] = number_format($current_stock, 0);
        $total_stock_qty += $current_stock;
    }
}

$page_title = "Purchase List - Velocity POS";
include('../includes/header.php');
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">

<!-- <style>
    /* 1. Table Container & Responsiveness */
  
    .bg-white.rounded-xl.shadow-sm.border,
    .table-responsive-container {
        max-width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        background: white;
        border-radius: 15px;
    }
    
    
    #purchaseTable {
        width: 100% !important;
        white-space: nowrap; 
    }

   
    .dataTables_wrapper .dataTables_filter {
        width: 100%;
        float: none;
        text-align: center;
        margin-bottom: 25px;
        display: flex;
        justify-content: center;
    }

    .dataTables_filter label {
        width: 100%;
        display: flex !important;
        align-items: center;
        justify-content: center;
    }

   
    .dataTables_filter input {
        width: 100% !important;
        max-width: 800px; 
        height: 50px !important;
        margin-left: 0 !important;
        border-radius: 12px !important;
        border: 1px solid #e2e8f0 !important;
        padding: 0 20px !important;
        font-size: 16px !important;
        background-color: #fff !important;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        transition: all 0.3s ease;
        outline: none;
    }

    .dataTables_filter input:focus {
        border-color: #0d9488 !important;
        box-shadow: 0 0 0 4px rgba(13, 148, 136, 0.1) !important;
    }

   
    .dataTables_paginate .paginate_button {
        padding: 6px 12px !important;
        margin: 0 2px !important;
        border-radius: 8px !important;
        border: 1px solid #e2e8f0 !important;
        cursor: pointer !important;
    }

    .dataTables_paginate .paginate_button.current {
        background: #4f46e5 !important;
        color: white !important;
        border: none !important;
    }

   
    .dataTables_length select {
        border-radius: 8px !important;
        border: 1px solid #e2e8f0 !important;
        padding: 5px 10px !important;
        outline: none;
    }

   
    .summary-grid {
        display: grid;
        grid-template-cols: repeat(1, minmax(0, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    .search-container {
        width: 100%;
        display: flex;
        justify-content: right;
        margin-bottom: 25px;
        position: sticky; 
        left: 0;
    }

    @media (min-width: 640px) { 
        .summary-grid { grid-template-cols: repeat(2, minmax(0, 1fr)); } 
    }

    @media (min-width: 1024px) { 
        .summary-grid { grid-template-cols: repeat(4, minmax(0, 1fr)); } 
    }

    /* 5. Utility Styles */
   
    .dt-buttons { display: none !important; }
</style> -->

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="app-wrapper">
    <?php include('../includes/sidebar.php'); ?>
    
    <main id="main-content" class="flex-1 lg:ml-64 flex flex-col h-screen min-w-0 transition-all duration-300">
        <div class="navbar-fixed-top">
            <?php include('../includes/navbar.php'); ?>
        </div>
        
        <div class="content-scroll-area custom-scroll h-full overflow-y-auto bg-slate-50/50">
            <div class="p-6 max-w-full mx-auto w-full">
                
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                    <div>
                        <h1 class="text-3xl font-bold text-slate-800 mb-2">Purchase List</h1>
                        <div class="flex items-center gap-2 text-sm text-slate-500">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span>Total <?= count($items); ?> entries</span>
                        </div>
                    </div>
                    
                    <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                        <div class="relative">
                            <button type="button" onclick="toggleExportDropdown()" class="inline-flex items-center gap-2 px-4 sm:px-5 py-2 sm:py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg border border-slate-300 transition-all text-sm sm:text-base">
                                <i class="fas fa-upload rotate-180"></i>
                                <span>Export</span>
                                <i class="fas fa-chevron-down text-xs"></i>
                            </button>
                            <!-- Export Dropdown -->
                            <div id="exportDropdown" class="hidden absolute right-0 mt-2 w-40 bg-white rounded-lg shadow-xl border border-slate-200 z-50 overflow-hidden">
                                <button onclick="triggerDtAction('print')" class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition-colors">
                                    <i class="fas fa-print w-4 text-slate-500"></i> Print
                                </button>
                                <button onclick="triggerDtAction('csv')" class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition-colors">
                                    <i class="fas fa-file-csv w-4 text-slate-500"></i> Csv
                                </button>
                                <button onclick="triggerDtAction('excel')" class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition-colors">
                                    <i class="fas fa-file-excel w-4 text-slate-500"></i> Excel
                                </button>
                                <button onclick="triggerDtAction('pdf')" class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition-colors">
                                    <i class="fas fa-file-pdf w-4 text-slate-500"></i> Pdf
                                </button>
                                <button onclick="triggerDtAction('copy')" class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition-colors">
                                    <i class="fas fa-copy w-4 text-slate-500"></i> Copy
                                </button>
                            </div>
                        </div>

                        <a href="/pos/purchases/add" class="inline-flex items-center gap-2 px-4 sm:px-6 py-2 sm:py-3 bg-teal-600 hover:bg-teal-700 text-white font-semibold rounded-lg shadow-lg transition-all transform hover:-translate-y-0.5 group text-sm sm:text-base">
                            <i class="fas fa-plus transition-transform group-hover:rotate-90"></i>
                            <span>Add New</span>
                        </a>
                        
                        <button type="button" onclick="payAllSelected()" class="inline-flex items-center gap-2 px-4 sm:px-6 py-2 sm:py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-lg transition-all text-sm sm:text-base">
                            <i class="fas fa-dollar-sign"></i>
                            <span>Pay All</span>
                        </button>
                        
                        <div class="relative">
                            <button type="button" onclick="toggleFilterDropdown()" class="inline-flex items-center gap-2 px-4 sm:px-6 py-2 sm:py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow-lg transition-all text-sm sm:text-base min-w-[140px] justify-between">
                                <span class="flex items-center gap-2">
                                    <i class="fas fa-filter"></i>
                                    <span><?= $active_filter_label; ?></span>
                                </span>
                                <i class="fas fa-chevron-down text-xs"></i>
                            </button>
                            <div id="filterDropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-slate-200 z-50">
                                <a href="?filter=all" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50 border-b border-slate-100 font-semibold">
                                    <i class="fas fa-times-circle mr-2"></i>Reset Filter
                                </a>
                                <a href="?filter=today" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-100 <?= $filter == 'today' ? 'bg-teal-50 text-teal-700' : '' ?>">Today Invoice</a>
                                <a href="?filter=all" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-100 <?= $filter == 'all' ? 'bg-teal-50 text-teal-700' : '' ?>">All Invoice</a>
                                <a href="?filter=due" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-100 <?= $filter == 'due' ? 'bg-teal-50 text-teal-700' : '' ?>">Due Invoice</a>
                                <a href="?filter=all_due" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-100 <?= $filter == 'all_due' ? 'bg-teal-50 text-teal-700' : '' ?>">All Due Invoice</a>
                                <a href="?filter=paid" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-100 <?= $filter == 'paid' ? 'bg-teal-50 text-teal-700' : '' ?>">Paid Invoice</a>
                                <a href="?filter=inactive" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-100 <?= $filter == 'inactive' ? 'bg-teal-50 text-teal-700' : '' ?>">Inactive Invoice</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-8">
                    <div class="bg-white p-4 sm:p-6 rounded-xl border-l-4 border-indigo-500 shadow-sm transition-transform hover:scale-[1.02]">
                        <p class="text-[10px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Grand Total</p>
                        <h2 class="text-xl sm:text-2xl font-black text-slate-800"><?= number_format($total_amount, 2); ?></h2>
                    </div>
                    <div class="bg-white p-4 sm:p-6 rounded-xl border-l-4 border-emerald-500 shadow-sm transition-transform hover:scale-[1.02]">
                        <p class="text-[10px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Total Paid</p>
                        <h2 class="text-xl sm:text-2xl font-black text-slate-800"><?= number_format($total_paid, 2); ?></h2>
                    </div>
                    <div class="bg-white p-4 sm:p-6 rounded-xl border-l-4 border-rose-500 shadow-sm transition-transform hover:scale-[1.02]">
                        <p class="text-[10px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Total Due</p>
                        <h2 class="text-xl sm:text-2xl font-black text-slate-800"><?= number_format($total_due, 2); ?></h2>
                    </div>
                    <div class="bg-white p-4 sm:p-6 rounded-xl border-l-4 border-orange-500 shadow-sm transition-transform hover:scale-[1.02]">
                        <p class="text-[10px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Total Stock</p>
                        <h2 class="text-xl sm:text-2xl font-black text-slate-800"><?= number_format($total_stock_qty, 0); ?></h2>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-x-auto p-4">
                    <table id="purchaseTable" class="table table-striped table-hover w-full text-left">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200">
                                <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">
                                    <input type="checkbox" id="selectAll" class="form-check-input">
                                </th>
                                <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Datetime</th>
                                <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Invoice Id</th>
                                <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Supplier</th>
                                <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Stock</th>
                                <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Creator</th>
                                <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right whitespace-nowrap min-w-[100px]" style="text-align: right !important; padding-right: 25px !important;">Amount</th>
                                <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right whitespace-nowrap min-w-[100px]" style="text-align: right !important; padding-right: 25px !important;">Invoice Paid</th>
                                <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right whitespace-nowrap min-w-[100px]" style="text-align: right !important; padding-right: 25px !important;">Due</th>
                                <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
                                <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">Pay</th>
                                <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">Return</th>
                                <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">View</th>
                                <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">Edit</th>
                                <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">Delete</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($items as $row): ?>
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="p-4">
                                        <input type="checkbox" class="form-check-input row-checkbox" data-invoice="<?= htmlspecialchars($row['invoice_id']); ?>" data-due="<?= $row['due_raw']; ?>">
                                    </td>
                                    <td class="p-4 text-sm text-slate-700"><?= htmlspecialchars($row['formatted_datetime']); ?></td>
                                    <td class="p-4 text-sm font-mono text-slate-700"><?= htmlspecialchars($row['invoice_id_display']); ?></td>
                                    <td class="p-4 text-sm text-blue-600 hover:underline cursor-pointer" onclick="window.location.href='/pos/suppliers/list'">
                                        <?= htmlspecialchars($row['supplier_display']); ?>
                                    </td>
                                    <td class="p-4 text-sm font-bold text-slate-600"><?= $row['stock_display']; ?></td>
                                    <td class="p-4 text-sm text-blue-600 hover:underline cursor-pointer" onclick="window.location.href='/pos/users/add'">
                                        <?= htmlspecialchars($row['creator_display']); ?>
                                    </td>
                                    <td class="p-4 text-sm text-slate-700 font-bold text-right whitespace-nowrap" style="text-align: right !important; padding-right: 25px !important;"><?= $row['amount_display']; ?></td>
                                    <td class="p-4 text-sm text-slate-700 font-bold text-right whitespace-nowrap" style="text-align: right !important; padding-right: 25px !important;"><?= $row['paid_display']; ?></td>
                                    <td class="p-4 text-sm text-slate-700 font-bold text-right whitespace-nowrap" style="text-align: right !important; padding-right: 25px !important;"><?= $row['due_display']; ?></td>
                                    <td class="p-4"><?= $row['status_badge']; ?></td>
                                    <td class="p-4 text-center">
                                        <?php if($row['due_raw'] > 0): ?>
                                            <button type="button" onclick="openPaymentModal('<?= $row['invoice_id']; ?>')" class="p-2 text-green-500 hover:bg-green-50 rounded transition" title="Pay">
                                                <i class="fas fa-dollar-sign"></i>
                                            </button>
                                        <?php else: ?>
                                            <span class="p-2 text-gray-400">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-4 text-center">
                                        <button type="button" onclick="openReturnModal('<?= $row['invoice_id']; ?>')" class="p-2 text-orange-500 hover:bg-orange-50 rounded transition" title="Return">
                                            <i class="fas fa-arrow-left"></i>
                                        </button>
                                    </td>
                                    <td class="p-4 text-center">
                                        <button type="button" onclick="viewPurchase('<?= $row['invoice_id']; ?>')" class="p-2 text-blue-500 hover:bg-blue-50 rounded transition" title="View">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                    <td class="p-4 text-center">
                                        <a href="/pos/purchases/add?invoice_id=<?= $row['invoice_id']; ?>" class="p-2 text-teal-600 hover:bg-teal-50 rounded transition" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </td>
                                    <td class="p-4 text-center">
                                        <button type="button" onclick="confirmDelete('<?= $row['invoice_id']; ?>', '<?= addslashes($row['invoice_id']); ?>', '/pos/purchases/save_purchase.php')" class="p-2 text-red-500 hover:bg-red-50 rounded transition" title="Delete">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="bg-slate-100 font-bold  w-full">
                                <td colspan="6" class="p-4 text-right text-slate-700 border-t border-slate-200">Total:</td>
                                <td class="p-4 text-slate-700 border-t border-slate-200 text-right whitespace-nowrap font-bold" style="text-align: right !important; padding-right: 25px !important;"><?= number_format($total_amount, 2); ?></td>
                                <td class="p-4 text-slate-700 border-t border-slate-200 text-right whitespace-nowrap font-bold" style="text-align: right !important; padding-right: 25px !important;"><?= number_format($total_paid, 2); ?></td>
                                <td class="p-4 text-slate-700 border-t border-slate-200 text-right whitespace-nowrap font-bold" style="text-align: right !important; padding-right: 25px !important;"><?= number_format($total_due, 2); ?></td>
                                <td colspan="6" class="p-4 border-t border-slate-200"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        <?php include('../includes/footer.php'); ?>
    </main>
</div>

<div id="paymentModal" class="fixed inset-0 z-[9999] hidden">
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" onclick="closePaymentModal()"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden">
                <div class="bg-green-600 px-4 py-3 flex justify-between items-center">
                    <h3 class="text-white font-semibold flex items-center gap-2">
                        <i class="fas fa-sync-alt"></i>
                        <span>Purchase Payment</span>
                    </h3>
                    <button onclick="closePaymentModal()" class="text-white hover:text-gray-200">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div id="paymentModalContent" class="p-6 overflow-y-auto max-h-[calc(90vh-80px)]">
                    </div>
            </div>
        </div>
    </div>
</div>

<div id="viewModal" class="fixed inset-0 z-[9999] hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/70 backdrop-blur-sm transition-opacity" onclick="closeViewModal()"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-2 sm:p-4 text-center">
            <div class="relative transform overflow-hidden rounded-xl bg-white text-left shadow-2xl transition-all w-full max-w-4xl my-4">
                <div class="bg-teal-600 px-4 py-3 flex flex-wrap justify-between items-center gap-2 print:hidden">
                    <h3 class="text-sm sm:text-base font-semibold text-white truncate" id="modal-title">
                        <i class="fas fa-eye mr-2"></i> <span id="viewModalTitle">Purchase Details</span>
                    </h3>
                    <div class="flex flex-wrap gap-1 sm:gap-2">
                        <button onclick="printPurchase()" class="p-1.5 sm:p-2 bg-teal-700 text-white rounded hover:bg-teal-800 transition" title="Print">
                            <i class="fas fa-print"></i>
                        </button>
                        <button onclick="closeViewModal()" class="p-1.5 sm:p-2 bg-red-500 text-white rounded hover:bg-red-600 transition" title="Close">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <div id="viewModalContent" class="overflow-y-auto max-h-[calc(90vh-80px)]">
                    </div>
            </div>
        </div>
    </div>
</div>

<div id="returnModal" class="fixed inset-0 z-[9999] hidden">
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" onclick="closeReturnModal()"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden">
                <div class="bg-orange-600 px-4 py-3 flex justify-between items-center">
                    <h3 class="text-white font-semibold flex items-center gap-2">
                        <i class="fas fa-sync-alt"></i>
                        <span id="returnModalTitle">Return Purchase</span>
                    </h3>
                    <button onclick="closeReturnModal()" class="text-white hover:text-gray-200">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div id="returnModalContent" class="p-6 overflow-y-auto max-h-[calc(90vh-80px)]">
                    </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<link rel="stylesheet" href="/pos/assets/css/purchase_list.css">

<script src="/pos/assets/js/purchase_list.js"></script>