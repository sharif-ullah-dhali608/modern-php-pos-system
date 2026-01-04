<?php
session_start();
include('../config/dbcon.php');

if(!isset($_SESSION['auth'])){
    header("Location: /pos/login");
    exit(0);
}

$query = "SELECT pl.*, 
                 pi.invoice_id as main_inv,
                 s.name as supplier_name,
                 pm.name as pmethod_name,
                 u.name as creator_name
          FROM purchase_logs pl
          LEFT JOIN purchase_info pi ON pl.ref_invoice_id = pi.invoice_id
          LEFT JOIN suppliers s ON pl.sup_id = s.id
          LEFT JOIN payment_methods pm ON pl.pmethod_id = pm.id
          LEFT JOIN users u ON pl.created_by = u.id
          ORDER BY pl.created_at DESC";

$query_run = mysqli_query($conn, $query);
$items = [];
$total_amount = 0;

if($query_run) {
    while($row = mysqli_fetch_assoc($query_run)) {
        $row['formatted_datetime'] = date('Y-m-d H:i:s', strtotime($row['created_at']));
        
        if($row['type'] == 'due') {
            $status_badge = '<span class="px-3 py-1 rounded-full text-xs font-bold bg-red-500 text-white">Due</span>';
        } elseif(strpos(strtolower($row['description']), 'paid') !== false && $row['type'] == 'purchase' && !empty($row['pmethod_id'])) {
            // Check if it's a payment against a due invoice
            $status_badge = '<span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-500 text-white">Due paid</span>';
        } else {
            $status_badge = '<span class="px-3 py-1 rounded-full text-xs font-bold bg-orange-400 text-white">Purchase</span>';
        }

        $row['status_badge'] = $status_badge;
        $row['supplier_display'] = !empty($row['supplier_name']) ? $row['supplier_name'] : 'No Supplier';
        $row['pmethod_display'] = !empty($row['pmethod_name']) ? $row['pmethod_name'] : 'dffger3'; // Default as per image
        $row['creator_display'] = !empty($row['creator_name']) ? $row['creator_name'] : 'Your Name';
        
        $items[] = $row;
        $total_amount += floatval($row['amount']);
    }
}

$page_title = "Purchase Log List - Velocity POS";
include('../includes/header.php');
?>

<style>
    .dt-buttons { display: none !important; }
    .dataTables_filter { width: 100%; margin-bottom: 20px; }
    .dataTables_filter input {
        width: 100% !important; height: 45px !important;
        border-radius: 8px !important; border: 1px solid #d1d5db !important;
        padding: 0 15px !important; font-size: 14px !important;
    }
</style>

<div class="app-wrapper">
    <?php include('../includes/sidebar.php'); ?>
    <main class="flex-1 lg:ml-64 flex flex-col h-screen bg-slate-50/50">
        <?php include('../includes/navbar.php'); ?>
        
        <div class="p-6 overflow-y-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-slate-800">Purchase Log List</h1>
                <div class="relative inline-block text-left">
                    <button onclick="toggleExport()" class="bg-white border px-4 py-2 rounded shadow-sm flex items-center gap-2">
                        <i class="fas fa-file-export"></i> Export <i class="fas fa-chevron-down text-xs"></i>
                    </button>
                    <div id="exportMenu" class="hidden absolute right-0 mt-2 w-40 bg-white border rounded shadow-lg z-50">
                        <a href="javascript:void(0)" onclick="doExport('print')" class="block px-4 py-2 hover:bg-slate-100">Print</a>
                        <a href="javascript:void(0)" onclick="doExport('excel')" class="block px-4 py-2 hover:bg-slate-100">Excel</a>
                        <a href="javascript:void(0)" onclick="doExport('pdf')" class="block px-4 py-2 hover:bg-slate-100">PDF</a>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border overflow-hidden p-4">
                <table id="logTable" class="table w-full text-left">
                    <thead class="bg-slate-100">
                        <tr>
                            <th class="p-3 text-xs font-bold text-slate-600 uppercase">Serial No</th>
                            <th class="p-3 text-xs font-bold text-slate-600 uppercase">Created At</th>
                            <th class="p-3 text-xs font-bold text-slate-600 uppercase">Type</th>
                            <th class="p-3 text-xs font-bold text-slate-600 uppercase">Supplier</th>
                            <th class="p-3 text-xs font-bold text-slate-600 uppercase">Pmethod</th>
                            <th class="p-3 text-xs font-bold text-slate-600 uppercase">Created By</th>
                            <th class="p-3 text-xs font-bold text-slate-600 uppercase">Amount</th>
                            <th class="p-3 text-xs font-bold text-slate-600 uppercase text-center">View</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($items as $index => $row): ?>
                        <tr class="border-b">
                            <td class="p-3 text-sm"><?= $index + 1; ?></td>
                            <td class="p-3 text-sm"><?= $row['formatted_datetime']; ?></td>
                            <td class="p-3"><?= $row['status_badge']; ?></td>
                            <td class="p-3 text-sm"><?= $row['supplier_display']; ?></td>
                            <td class="p-3 text-sm text-slate-500 font-mono"><?= $row['pmethod_display']; ?></td>
                            <td class="p-3 text-sm"><?= $row['creator_display']; ?></td>
                            <td class="p-3 text-sm font-bold"><?= number_format($row['amount'], 2); ?></td>
                            <td class="p-3 text-center">
                                <button onclick="viewLog('<?= $row['id']; ?>')" class="p-2 text-blue-500 hover:bg-blue-50 rounded transition">
                                        <i class="fas fa-eye text-xs"></i>
                                    </button>                            
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="bg-slate-50">
                        <tr class="font-bold">
                            <td colspan="6" class="p-3 text-right">Total:</td>
                            <td class="p-3 text-indigo-700"><?= number_format($total_amount, 2); ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </main>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>

<script>
$(document).ready(function() {
    var table = $('#logTable').DataTable({
        pageLength: 25,
        dom: 'Bfrtip',
        buttons: ['print', 'excel', 'pdf'],
        language: { search: "", searchPlaceholder: "Search..." }
    });
    window.doExport = function(type) {
        if(type === 'print') table.button('.buttons-print').trigger();
        if(type === 'excel') table.button('.buttons-excel').trigger();
        if(type === 'pdf') table.button('.buttons-pdf').trigger();
        $('#exportMenu').addClass('hidden');
    };
});
function toggleExport() { $('#exportMenu').toggleClass('hidden'); }
</script>
<script>
function viewLog(logId) {

    $('#viewModalTitle').text('Log Information > ' + logId);
    $('#viewModalContent').html('<div class="text-center py-8"><i class="fas fa-spinner fa-spin fa-2x text-teal-600"></i><p class="mt-4 text-slate-600">Fetching History...</p></div>');
    $('#viewModal').removeClass('hidden'); 
    
    $.ajax({
        url: '/pos/purchases/save_purchase.php',
        type: 'POST',
        data: { view_log_btn: true, log_id: logId },
        dataType: 'json',
        success: function(response) {
            if(response.status == 200) {
                $('#viewModalContent').html(response.html);
            } else {
                $('#viewModalContent').html('<div class="p-4 text-red-500">Error: ' + response.message + '</div>');
            }
        }
    });
}
</script>
