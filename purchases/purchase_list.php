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

// Build query based on filter
$query = "SELECT pi.*, 
                 s.name as supplier_name,
                 u.name as creator_name
          FROM purchase_info pi
          LEFT JOIN suppliers s ON pi.sup_id = s.id
          LEFT JOIN users u ON pi.created_by = u.id
          WHERE 1=1";

// Apply filters
if($filter == 'today') {
    $query .= " AND DATE(pi.created_at) = CURDATE()";
} elseif($filter == 'due') {
    $query .= " AND pi.payment_status = 'due'";
} elseif($filter == 'all_due') {
    $query .= " AND pi.payment_status = 'due'";
} elseif($filter == 'paid') {
    $query .= " AND pi.payment_status = 'paid'";
} elseif($filter == 'inactive') {
    $query .= " AND pi.is_visible = 0";
} else {
    // All invoice
    $query .= " AND pi.is_visible = 1";
}

$query .= " ORDER BY pi.created_at DESC";

$query_run = mysqli_query($conn, $query);
$items = [];

// Calculate totals for summary
$total_amount = 0;
$total_paid = 0;
$total_due = 0;

if($query_run) {
    while($row = mysqli_fetch_assoc($query_run)) {
        // Get payment details
        $invoice_id = mysqli_real_escape_string($conn, $row['invoice_id']);
        
        // Calculate paid amount from purchase_logs
        $paid_query = "SELECT COALESCE(SUM(amount), 0) as total_paid 
                       FROM purchase_logs 
                       WHERE ref_invoice_id = '$invoice_id' AND type = 'purchase'";
        $paid_result = mysqli_query($conn, $paid_query);
        $paid_data = mysqli_fetch_assoc($paid_result);
        $paid_amount = floatval($paid_data['total_paid']);
        
        // Calculate total from purchase_items
        $total_query = "SELECT COALESCE(SUM(item_total), 0) as total_amount 
                        FROM purchase_item 
                        WHERE invoice_id = '$invoice_id'";
        $total_result = mysqli_query($conn, $total_query);
        $total_data = mysqli_fetch_assoc($total_result);
        $amount = floatval($total_data['total_amount']);
        
        $due_amount = $amount - $paid_amount;
        
        // Format data
        $row['formatted_datetime'] = date('Y-m-d H:i:s', strtotime($row['created_at']));
        $row['invoice_id_display'] = $row['invoice_id'];
        $row['supplier_display'] = !empty($row['supplier_name']) ? $row['supplier_name'] : 'No Supplier';
        $row['creator_display'] = !empty($row['creator_name']) ? $row['creator_name'] : 'Your Name';
        $row['amount_display'] = number_format($amount, 2);
        $row['paid_display'] = number_format($paid_amount, 2);
        $row['due_display'] = number_format($due_amount, 2);
        
        // Status badge
        $status_class = $row['payment_status'] == 'paid' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700';
        $status_text = ucfirst($row['payment_status']);
        $row['status_badge'] = '<span class="px-2 py-1 rounded-full text-xs font-bold ' . $status_class . '">' . $status_text . '</span>';
        
        // Store raw values for calculations
        $row['amount_raw'] = $amount;
        $row['paid_raw'] = $paid_amount;
        $row['due_raw'] = $due_amount;
        
        // Actions
        $row['actions_html'] = '';
        
        // Pay button (only show if due)
        if($due_amount > 0) {
            $row['actions_html'] .= '<button type="button" onclick="openPaymentModal(\'' . $row['invoice_id'] . '\')" class="p-2 text-green-500 hover:bg-green-50 rounded transition" title="Pay"><i class="fas fa-dollar-sign"></i></button>';
        } else {
            $row['actions_html'] .= '<span class="p-2 text-gray-400">-</span>';
        }
        
        // Return button
        $row['actions_html'] .= '<button type="button" onclick="openReturnModal(\'' . $row['invoice_id'] . '\')" class="p-2 text-orange-500 hover:bg-orange-50 rounded transition" title="Return"><i class="fas fa-arrow-left"></i></button>';
        
        // View button
        $row['actions_html'] .= '<button type="button" onclick="viewPurchase(\'' . $row['invoice_id'] . '\')" class="p-2 text-blue-500 hover:bg-blue-50 rounded transition" title="View"><i class="fas fa-eye"></i></button>';
        
        // Edit button
        $row['actions_html'] .= '<a href="/pos/purchases/add?invoice_id=' . $row['invoice_id'] . '" class="p-2 text-teal-600 hover:bg-teal-50 rounded transition" title="Edit"><i class="fas fa-edit"></i></a>';
        
        // Delete button
        $row['actions_html'] .= '<button type="button" onclick="confirmDelete(\'' . $row['invoice_id'] . '\', \'' . addslashes($row['invoice_id']) . '\', \'/pos/purchases/save_purchase.php\')" class="p-2 text-red-500 hover:bg-red-50 rounded transition" title="Delete"><i class="fas fa-trash-alt"></i></button>';
        
        $items[] = $row;
        
        // Add to totals
        $total_amount += $amount;
        $total_paid += $paid_amount;
        $total_due += $due_amount;
    }
}

$page_title = "Purchase List - Velocity POS";
include('../includes/header.php');
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">

<style>
    /* Custom style to hide original DataTable buttons but keep functionality */
    .dt-buttons { display: none !important; }
    
    /* Boro Search Field Styling */
    .dataTables_filter {
        width: 100%;
        margin-bottom: 20px;
    }
    .dataTables_filter label {
        width: 100%;
        display: flex !important;
        align-items: center;
    }
    .dataTables_filter input {
        width: 100% !important;
        height: 50px !important;
        margin-left: 0 !important;
        border-radius: 10px !important;
        border: 1px solid #e2e8f0 !important;
        padding: 0 20px !important;
        font-size: 16px !important;
        background-color: #fff !important;
        transition: all 0.3s ease;
    }
    .dataTables_filter input:focus {
        border-color: #0d9488 !important;
        box-shadow: 0 0 0 4px rgba(13, 148, 136, 0.1) !important;
        outline: none;
    }
</style>

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
                    
                    <div class="flex items-center gap-3">
                        <div class="relative">
                            <button type="button" onclick="toggleExportDropdown()" class="inline-flex items-center gap-2 px-5 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg border border-slate-300 transition-all">
                                <i class="fas fa-upload rotate-180"></i>
                                <span>Export</span>
                                <i class="fas fa-chevron-down text-xs"></i>
                            </button>
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

                        <a href="/pos/purchases/add" class="inline-flex items-center gap-2 px-6 py-3 bg-teal-600 hover:bg-teal-700 text-white font-semibold rounded-lg shadow-lg transition-all transform hover:-translate-y-0.5 group">
                            <i class="fas fa-plus transition-transform group-hover:rotate-90"></i>
                            <span>Add New</span>
                        </a>
                        
                        <button type="button" onclick="payAllSelected()" class="inline-flex items-center gap-2 px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-lg transition-all">
                            <i class="fas fa-dollar-sign"></i>
                            <span>Pay All</span>
                        </button>
                        
                        <div class="relative">
                            <button type="button" onclick="toggleFilterDropdown()" class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow-lg transition-all">
                                <i class="fas fa-filter"></i>
                                <span>Filter</span>
                                <i class="fas fa-chevron-down text-xs"></i>
                            </button>
                            <div id="filterDropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-slate-200 z-50">
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

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white p-6 rounded-xl border-l-4 border-indigo-500 shadow-sm transition-transform hover:scale-[1.02]">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Grand Total</p>
                        <h2 class="text-2xl font-black text-slate-800"><?= number_format($total_amount, 2); ?></h2>
                    </div>
                    <div class="bg-white p-6 rounded-xl border-l-4 border-emerald-500 shadow-sm transition-transform hover:scale-[1.02]">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Total Paid</p>
                        <h2 class="text-2xl font-black text-slate-800"><?= number_format($total_paid, 2); ?></h2>
                    </div>
                    <div class="bg-white p-6 rounded-xl border-l-4 border-rose-500 shadow-sm transition-transform hover:scale-[1.02]">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Total Due</p>
                        <h2 class="text-2xl font-black text-slate-800"><?= number_format($total_due, 2); ?></h2>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden p-4">
                    <table id="purchaseTable" class="table table-striped table-hover w-full text-left">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200">
                                <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">
                                    <input type="checkbox" id="selectAll" class="form-check-input">
                                </th>
                                <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Datetime</th>
                                <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Invoice Id</th>
                                <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Supplier</th>
                                <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Creator</th>
                                <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Amount</th>
                                <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Invoice Paid</th>
                                <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Due</th>
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
                                    <td class="p-4 text-sm text-blue-600 hover:underline cursor-pointer" onclick="window.location.href='/pos/users/add'">
                                        <?= htmlspecialchars($row['creator_display']); ?>
                                    </td>
                                    <td class="p-4 text-sm text-slate-700 font-bold"><?= $row['amount_display']; ?></td>
                                    <td class="p-4 text-sm text-slate-700 font-bold"><?= $row['paid_display']; ?></td>
                                    <td class="p-4 text-sm text-slate-700 font-bold"><?= $row['due_display']; ?></td>
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
                            <tr class="bg-slate-100 font-bold">
                                <td colspan="5" class="p-4 text-right text-slate-700 border-t border-slate-200">Total:</td>
                                <td class="p-4 text-slate-700 border-t border-slate-200"><?= number_format($total_amount, 2); ?></td>
                                <td class="p-4 text-slate-700 border-t border-slate-200"><?= number_format($total_paid, 2); ?></td>
                                <td class="p-4 text-slate-700 border-t border-slate-200"><?= number_format($total_due, 2); ?></td>
                                <td colspan="6" class="p-4 border-t border-slate-200"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <?php include('../includes/footer.php'); ?>
        </div>
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

<script>
$(document).ready(function() {
    // Initialize DataTable
    var table = $('#purchaseTable').DataTable({
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        dom: 'Bfrtip',
        buttons: [
            { extend: 'print', className: 'dt-print' },
            { extend: 'csv', className: 'dt-csv' },
            { extend: 'excel', className: 'dt-excel' },
            { extend: 'pdf', className: 'dt-pdf' },
            { extend: 'copy', className: 'dt-copy' }
        ],
        order: [[1, 'desc']],
        language: {
            search: "",
            searchPlaceholder: "Search for invoices, suppliers, or dates..."
        }
    });
    
    // Select all checkbox
    $('#selectAll').on('click', function() {
        $('.row-checkbox').prop('checked', $(this).prop('checked'));
    });
});

// Trigger DataTable Button from Custom Menu
function triggerDtAction(action) {
    if(action == 'print') $('.buttons-print').click();
    if(action == 'csv') $('.buttons-csv').click();
    if(action == 'excel') $('.buttons-excel').click();
    if(action == 'pdf') $('.buttons-pdf').click();
    if(action == 'copy') $('.buttons-copy').click();
    $('#exportDropdown').addClass('hidden');
}

// Export dropdown toggle
function toggleExportDropdown() {
    $('#exportDropdown').toggleClass('hidden');
    $('#filterDropdown').addClass('hidden');
}

// Filter dropdown toggle
function toggleFilterDropdown() {
    $('#filterDropdown').toggleClass('hidden');
    $('#exportDropdown').addClass('hidden');
}

// Close dropdowns when clicking outside
$(document).on('click', function(e) {
    if (!$(e.target).closest('.relative').length) {
        $('#filterDropdown').addClass('hidden');
        $('#exportDropdown').addClass('hidden');
    }
});

// View Purchase
function viewPurchase(invoiceId) {
    $('#viewModalTitle').text('Purchase > ' + invoiceId);
    $('#viewModalContent').html('<div class="text-center py-8"><i class="fas fa-spinner fa-spin fa-2x text-teal-600"></i><p class="mt-4 text-slate-600">Loading...</p></div>');
    $('#viewModal').removeClass('hidden');
    
    $.ajax({
        url: '/pos/purchases/save_purchase.php',
        type: 'POST',
        data: { view_purchase_btn: true, invoice_id: invoiceId },
        dataType: 'json',
        success: function(response) {
            if(response.status == 200) {
                $('#viewModalContent').html(response.html);
            } else {
                $('#viewModalContent').html('<div class="text-center py-8 text-red-600">' + response.message + '</div>');
            }
        },
        error: function() {
            $('#viewModalContent').html('<div class="text-center py-8 text-red-600">Error loading purchase details</div>');
        }
    });
}

function closeViewModal() {
    $('#viewModal').addClass('hidden');
}

function printPurchase() {
    var content = document.getElementById('viewModalContent').innerHTML;
    var mywindow = window.open('', 'PRINT', 'height=800,width=1000');
    mywindow.document.write('<html><head><title>Purchase_' + $('#viewModalTitle').text().replace('Purchase > ', '') + '</title>');
    mywindow.document.write('<script src="https://cdn.tailwindcss.com"><\/script>');
    mywindow.document.write('<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"/>');
    mywindow.document.write('</head><body class="p-4">');
    mywindow.document.write(content);
    mywindow.document.write('</body></html>');
    mywindow.document.close(); 
    mywindow.focus(); 
    setTimeout(function(){ mywindow.print(); mywindow.close(); }, 1500);
}

// Payment Modal
function openPaymentModal(invoiceId) {
    $('#paymentModalContent').html('<div class="text-center py-8"><i class="fas fa-spinner fa-spin fa-2x text-green-600"></i><p class="mt-4 text-slate-600">Loading...</p></div>');
    $('#paymentModal').removeClass('hidden');
    
    $.ajax({
        url: '/pos/purchases/save_purchase.php',
        type: 'POST',
        data: { payment_modal_btn: true, invoice_id: invoiceId },
        dataType: 'json',
        success: function(response) {
            if(response.status == 200) {
                $('#paymentModalContent').html(response.html);
            } else {
                $('#paymentModalContent').html('<div class="text-center py-8 text-red-600">' + response.message + '</div>');
            }
        },
        error: function() {
            $('#paymentModalContent').html('<div class="text-center py-8 text-red-600">Error loading payment form</div>');
        }
    });
}

function closePaymentModal() {
    $('#paymentModal').addClass('hidden');
}

// Return Modal
function openReturnModal(invoiceId) {
    $('#returnModalTitle').text('Return > ' + invoiceId);
    $('#returnModalContent').html('<div class="text-center py-8"><i class="fas fa-spinner fa-spin fa-2x text-orange-600"></i><p class="mt-4 text-slate-600">Loading...</p></div>');
    $('#returnModal').removeClass('hidden');
    
    $.ajax({
        url: '/pos/purchases/save_purchase.php',
        type: 'POST',
        data: { return_modal_btn: true, invoice_id: invoiceId },
        dataType: 'json',
        success: function(response) {
            if(response.status == 200) {
                $('#returnModalContent').html(response.html);
            } else {
                $('#returnModalContent').html('<div class="text-center py-8 text-red-600">' + response.message + '</div>');
            }
        },
        error: function() {
            $('#returnModalContent').html('<div class="text-center py-8 text-red-600">Error loading return form</div>');
        }
    });
}

function closeReturnModal() {
    $('#returnModal').addClass('hidden');
}

// Pay All Selected
function payAllSelected() {
    var selected = [];
    $('.row-checkbox:checked').each(function() {
        var due = parseFloat($(this).data('due'));
        if(due > 0) {
            selected.push($(this).data('invoice'));
        }
    });
    
    if(selected.length === 0) {
        alert('Please select invoices with due amount');
        return;
    }
    
    if(confirm('Pay all selected ' + selected.length + ' invoice(s)?')) {
        // Implement bulk payment logic
        $.ajax({
            url: '/pos/purchases/save_purchase.php',
            type: 'POST',
            data: { pay_all_btn: true, invoice_ids: selected },
            dataType: 'json',
            success: function(response) {
                if(response.status == 200) {
                    alert('Payment processed successfully');
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            }
        });
    }
}

// Handle payment submission
$(document).on('submit', '#paymentForm', function(e) {
    e.preventDefault();
    var formData = $(this).serialize();
    
    $.ajax({
        url: '/pos/purchases/save_purchase.php',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if(response.status == 200) {
                alert('Payment successful!');
                closePaymentModal();
                location.reload();
            } else {
                alert('Error: ' + response.message);
            }
        }
    });
});

// Handle return submission
$(document).on('submit', '#returnForm', function(e) {
    e.preventDefault();
    var formData = $(this).serialize();
    
    $.ajax({
        url: '/pos/purchases/save_purchase.php',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if(response.status == 200) {
                alert('Return processed successfully!');
                closeReturnModal();
                location.reload();
            } else {
                alert('Error: ' + response.message);
            }
        }
    });
});
</script>
<script>

function confirmDelete(id, name, url) {
    Swal.fire({
        title: 'Are you sure?',
        text: "You want to delete " + name + "! This action cannot be undone.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: url,
                type: 'POST',
                data: { delete_btn: true, delete_id: id },
                dataType: 'json',
                success: function(response) {
                    if(response.status == 200) {
                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true,
                            background: '#1e3a3a', 
                            color: '#fff'
                        });
                        Toast.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: 'Purchase deleted!'
                        });
                        setTimeout(() => location.reload(), 1500);
                    }
                }
            });
        }
    })
}
</script>