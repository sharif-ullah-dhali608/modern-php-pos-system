<?php
session_start();
include('../config/dbcon.php');

if(!isset($_SESSION['auth'])){
    header("Location: /pos/login");
    exit(0);
}


$query = "SELECT pl.*, 
                 s.name as supplier_name,
                 pm.name as pmethod_name,
                 u.name as creator_name
          FROM purchase_logs pl
          LEFT JOIN suppliers s ON pl.sup_id = s.id
          LEFT JOIN payment_methods pm ON pl.pmethod_id = pm.id
          LEFT JOIN users u ON pl.created_by = u.id
          ORDER BY pl.created_at DESC";

$query_run = mysqli_query($conn, $query);
$items = [];
$total_accumulated = 0;

if($query_run) {
    while($row = mysqli_fetch_assoc($query_run)) {
        $row['formatted_datetime'] = date('d M Y, h:i A', strtotime($row['created_at']));
        
        
        $type = strtolower($row['type']);
        $desc = strtolower($row['description']);
        
        if($type == 'return') {
            $row['source_text'] = 'Returned';
            $row['source_class'] = 'bg-orange-100 text-orange-700 border-orange-200';
        } elseif(strpos($desc, 'edit') !== false || strpos($desc, 'updated') !== false) {
            $row['source_text'] = 'Edited';
            $row['source_class'] = 'bg-blue-100 text-blue-700 border-blue-200';
        } elseif($type == 'purchase' && (strpos($desc, 'paid') !== false || !empty($row['pmethod_id']))) {
            $row['source_text'] = 'Payment';
            $row['source_class'] = 'bg-emerald-100 text-emerald-700 border-emerald-200';
        } else {
            $row['source_text'] = 'Added';
            $row['source_class'] = 'bg-slate-100 text-slate-700 border-slate-200';
        }

        $row['supplier_display'] = !empty($row['supplier_name']) ? $row['supplier_name'] : 'N/A';
        $row['pmethod_display'] = !empty($row['pmethod_name']) ? $row['pmethod_name'] : 'N/A';
        
        $items[] = $row;
        $total_accumulated += floatval($row['amount']);
    }
}

$page_title = "Purchase Activity Logs - Velocity POS";
include('../includes/header.php');
?>

<style>
    /* DataTable Custom Styling */
    .dataTables_length select {
        padding: 8px 30px 8px 10px !important;
        border-radius: 8px !important;
        border: 1px solid #e2e8f0 !important;
        margin: 0 8px !important;
        outline: none !important;
    }
    .dataTables_paginate .paginate_button {
        padding: 5px 12px !important;
        margin: 0 2px !important;
        border-radius: 8px !important;
        border: 1px solid #e2e8f0 !important;
        cursor: pointer !important;
    }
    .dataTables_paginate .paginate_button.current {
        background: #4f46e5 !important;
        color: white !important;
        border-color: #4f46e5 !important;
    }
    .dataTables_info {
        font-size: 0.875rem;
        color: #64748b;
        font-weight: 600;
    }
</style>

<div class="app-wrapper">
    <?php include('../includes/sidebar.php'); ?>
    
    <main id="main-content" class="flex-1 lg:ml-64 flex flex-col min-h-screen min-w-0 transition-all duration-300 bg-slate-50/50">
        <div class="navbar-fixed-top"><?php include('../includes/navbar.php'); ?></div>
        
        <div class="content-scroll-area custom-scroll h-full overflow-y-auto p-4 md:p-6">
            
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-black text-slate-800">Purchase Activity Logs</h1>
                    <p class="text-xs text-slate-500 font-medium italic">Detailed history of all transactions</p>
                </div>
                <div class="relative inline-block">
                    <button onclick="$('#exportMenu').toggleClass('hidden')" class="px-5 py-2.5 bg-indigo-600 text-white font-bold rounded-xl shadow-lg hover:bg-indigo-700 transition-all text-sm">
                        <i class="fas fa-file-export mr-2"></i> Export Data
                    </button>
                    <div id="exportMenu" class="hidden absolute right-0 mt-2 w-44 bg-white border border-slate-100 rounded-xl shadow-2xl z-50 overflow-hidden">
                        <button onclick="doExport('print')" class="flex items-center gap-3 w-full px-4 py-3 text-xs font-bold text-slate-600 hover:bg-slate-50 border-b"><i class="fas fa-print text-indigo-500"></i> Print List</button>
                        <button onclick="doExport('excel')" class="flex items-center gap-3 w-full px-4 py-3 text-xs font-bold text-slate-600 hover:bg-slate-50"><i class="fas fa-file-excel text-green-500"></i> Excel Export</button>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden p-4 md:p-6">
                
                <div class="flex flex-col md:flex-row items-center justify-between gap-4 mb-6">
                    <div id="custom_length" class="text-sm font-bold text-slate-700 flex items-center">
                        </div>
                    <div class="flex-1 w-full md:max-w-2xl relative">
                        <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                        <input type="text" id="logSearch" placeholder="Search invoices, suppliers, users or amounts..." 
                               class="w-full h-12 pl-11 pr-4 rounded-xl border border-slate-200 focus:ring-4 focus:ring-indigo-50 focus:border-indigo-400 outline-none transition-all text-sm font-semibold">
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table id="logTable" class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/50 border-b border-slate-200">
                                <th class="p-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">S/N</th>
                                <th class="p-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Date & Time</th>
                                <th class="p-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Source</th>
                                <th class="p-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Reference Invoice</th>
                                <th class="p-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Supplier</th>
                                <th class="p-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Method</th>
                                <th class="p-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Amount</th>
                                <th class="p-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach($items as $index => $row): ?>
                            <tr class="hover:bg-indigo-50/30 transition-colors group">
                                <td class="p-4 text-xs text-slate-400 font-mono"><?= $index + 1; ?></td>
                                <td class="p-4 text-xs font-bold text-slate-700"><?= $row['formatted_datetime']; ?></td>
                                <td class="p-4">
                                    <span class="inline-flex px-2 py-1 rounded text-[10px] font-black uppercase border <?= $row['source_class']; ?>">
                                        <?= $row['source_text']; ?>
                                    </span>
                                </td>
                                <td class="p-4 text-xs font-black text-indigo-600 font-mono italic">#<?= $row['ref_invoice_id']; ?></td>
                                <td class="p-4 text-xs font-bold text-slate-600"><?= $row['supplier_display']; ?></td>
                                <td class="p-4 text-[10px] text-slate-500 uppercase font-black"><?= $row['pmethod_display']; ?></td>
                                <td class="p-4 text-sm font-black text-slate-900 "><?= number_format($row['amount'], 2); ?></td>
                                <td class="p-4 text-center">
                                    <button onclick="viewLogDetail('<?= $row['ref_invoice_id']; ?>')" class="w-8 h-8 inline-flex items-center justify-center text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-600 hover:text-white transition-all shadow-sm">
                                        <i class="fas fa-eye text-xs"></i>
                                    </button>                            
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div id="pagination_container" class="mt-6 border-t border-slate-100 pt-4 flex flex-col md:flex-row justify-between items-center gap-4">
                    </div>
            </div>
        </div>
        <?php include('../includes/footer.php'); ?>
    </main>
</div>

<div id="viewModal" class="fixed inset-0 z-[9999] hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity" onclick="closeViewModal()"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-5xl overflow-hidden border border-slate-200 transform transition-all">
                <div class="bg-indigo-600 px-6 py-4 flex justify-between items-center text-white">
                    <h3 class="text-sm font-black uppercase tracking-widest flex items-center gap-3">
                        <i class="fas fa-file-invoice"></i> <span id="viewModalTitle">Activity Detail</span>
                    </h3>
                    <button onclick="closeViewModal()" class="w-8 h-8 rounded-full bg-indigo-500/50 flex items-center justify-center hover:bg-red-500 transition-all">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>
                <div id="viewModalContent" class="p-8 max-h-[85vh] overflow-y-auto custom-scroll bg-white">
                    </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>

<script>
$(document).ready(function() {
    var table = $('#logTable').DataTable({
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        // dom logic: l = length, t = table, i = info, p = pagination
        dom: 'ltip', 
        order: [[1, 'desc']],
        language: {
            search: "",
            lengthMenu: "Show _MENU_ entries",
            info: "Displaying _START_ to _END_ of _TOTAL_ activities"
        },
        // Move components to custom locations
        initComplete: function() {
            $('.dataTables_length').appendTo('#custom_length');
            $('.dataTables_info, .dataTables_paginate').appendTo('#pagination_container');
        }
    });

    // Custom Search Box Connection
    $('#logSearch').on('keyup', function() {
        table.search(this.value).draw();
    });

    window.doExport = function(type) {
        // Buttons are hidden but can be triggered via JS
        const btnIdx = type === 'print' ? 0 : 1;
        new $.fn.dataTable.Buttons(table, {
            buttons: [
                { extend: 'print', title: 'Activity Logs' },
                { extend: 'excel', title: 'Activity Logs' }
            ]
        }).container().appendTo('body').hide();
        
        if(type === 'print') $('.buttons-print').click();
        if(type === 'excel') $('.buttons-excel').click();
        
        $('#exportMenu').addClass('hidden');
    };
});

function viewLogDetail(invoiceId) {
    $('#viewModalTitle').text('Purchase Detail > ' + invoiceId);
    $('#viewModalContent').html('<div class="text-center py-20"><i class="fas fa-circle-notch fa-spin fa-3x text-indigo-600 mb-4"></i><p class="text-slate-500 font-bold tracking-tight">Fetching Secure Data...</p></div>');
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
                $('#viewModalContent').html('<div class="p-10 text-center text-red-500 font-bold bg-red-50 rounded-2xl"><i class="fas fa-exclamation-triangle text-2xl mb-2"></i><br>Error: ' + response.message + '</div>');
            }
        },
        error: function() {
            $('#viewModalContent').html('<div class="p-10 text-center text-red-500 font-bold bg-red-50 rounded-2xl">Could not connect to server.</div>');
        }
    });
}

function closeViewModal() { $('#viewModal').addClass('hidden'); }
</script>