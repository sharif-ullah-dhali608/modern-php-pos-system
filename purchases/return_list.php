<?php
session_start();
include('../config/dbcon.php');

// Security Check
if(!isset($_SESSION['auth'])){
    header("Location: /pos/login");
    exit(0);
}

// Get purchases with returns
$query = "SELECT pi.*, 
                 s.name as supplier_name,
                 u.name as creator_name,
                 SUM(pi_item.return_quantity) as total_returns
          FROM purchase_info pi
          LEFT JOIN suppliers s ON pi.sup_id = s.id
          LEFT JOIN users u ON pi.created_by = u.id
          LEFT JOIN purchase_item pi_item ON pi.invoice_id = pi_item.invoice_id
          WHERE pi_item.return_quantity > 0
          GROUP BY pi.invoice_id
          ORDER BY pi.created_at DESC";

$query_run = mysqli_query($conn, $query);
$items = [];

// Calculate totals for summary cards
$grand_total_amount = 0;
$grand_total_returns = 0;

if($query_run) {
    while($row = mysqli_fetch_assoc($query_run)) {
        $invoice_id = mysqli_real_escape_string($conn, $row['invoice_id']);
        
        // Calculate totals
        $total_query = "SELECT COALESCE(SUM(item_total), 0) as total_amount FROM purchase_item WHERE invoice_id = '$invoice_id'";
        $total_result = mysqli_query($conn, $total_query);
        $total_data = mysqli_fetch_assoc($total_result);
        $amount = floatval($total_data['total_amount'] ?? 0);
        
        $row['formatted_datetime'] = date('Y-m-d H:i:s', strtotime($row['created_at']));
        $row['supplier_display'] = !empty($row['supplier_name']) ? $row['supplier_name'] : 'No Supplier';
        $row['creator_display'] = !empty($row['creator_name']) ? $row['creator_name'] : 'Your Name';
        $row['amount_display'] = number_format($amount, 2);
        $row['returns_display'] = number_format($row['total_returns'] ?? 0, 2);
        
        $grand_total_amount += $amount;
        $grand_total_returns += floatval($row['total_returns'] ?? 0);
        
        $items[] = $row;
    }
}

$page_title = "Return List - Velocity POS";
include('../includes/header.php');
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">

<style>
    /* Hide original DataTable buttons */
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
                        <h1 class="text-3xl font-bold text-slate-800 mb-2">Return List</h1>
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
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div class="bg-white p-6 rounded-xl border-l-4 border-indigo-500 shadow-sm transition-transform hover:scale-[1.02]">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Total Return Invoices Amount</p>
                        <h2 class="text-2xl font-black text-slate-800"><?= number_format($grand_total_amount, 2); ?></h2>
                    </div>
                    <div class="bg-white p-6 rounded-xl border-l-4 border-orange-500 shadow-sm transition-transform hover:scale-[1.02]">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Total Return Quantity</p>
                        <h2 class="text-2xl font-black text-slate-800"><?= number_format($grand_total_returns, 2); ?></h2>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden p-4">
                    <table id="returnTable" class="table table-striped table-hover w-full text-left">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200">
                                <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Datetime</th>
                                <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Invoice Id</th>
                                <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Supplier</th>
                                <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Creator</th>
                                <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Total Amount</th>
                                <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Return Quantity</th>
                                <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-center">View</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($items as $row): ?>
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="p-4 text-sm text-slate-700"><?= htmlspecialchars($row['formatted_datetime']); ?></td>
                                    <td class="p-4 text-sm font-mono text-slate-700"><?= htmlspecialchars($row['invoice_id']); ?></td>
                                    <td class="p-4 text-sm text-slate-700"><?= htmlspecialchars($row['supplier_display']); ?></td>
                                    <td class="p-4 text-sm text-slate-700"><?= htmlspecialchars($row['creator_display']); ?></td>
                                    <td class="p-4 text-sm text-slate-700 font-bold"><?= $row['amount_display']; ?></td>
                                    <td class="p-4 text-sm text-slate-700 font-bold text-orange-600"><?= $row['returns_display']; ?></td>
                                    <td class="p-4 text-center">
                                        <button type="button" onclick="viewReturn('<?= $row['invoice_id']; ?>')" class="p-2 text-blue-500 hover:bg-blue-50 rounded transition" title="View">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php include('../includes/footer.php'); ?>
        </div>
    </main>
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
    var table = $('#returnTable').DataTable({
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
        order: [[0, 'desc']],
        language: {
            search: "",
            searchPlaceholder: "Search return invoices, suppliers, or creator..."
        }
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
}

// Close dropdowns when clicking outside
$(document).on('click', function(e) {
    if (!$(e.target).closest('.relative').length) {
        $('#exportDropdown').addClass('hidden');
    }
});

function viewReturn(invoiceId) {
    window.location.href = '/pos/purchases/list?filter=all';
}
</script>