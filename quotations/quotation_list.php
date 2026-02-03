<?php
session_start();
include('../config/dbcon.php');
include('../includes/date_filter_helper.php');

// Security Check
if(!isset($_SESSION['auth'])){
    header("Location: /pos/login");
    exit(0);
}

// ---------------------------------------------------------
// 1. DATA FETCHING (List View)
// ---------------------------------------------------------

// Filter Inputs
$date_filter = $_GET['date_filter'] ?? '';
$start_date  = $_GET['start_date'] ?? '';
$end_date    = $_GET['end_date'] ?? '';
$customer_id = $_GET['customer_id'] ?? '';
$status      = $_GET['status'] ?? '';

// Build Query
$where_clause = "WHERE 1=1";
applyDateFilter($where_clause, 'q.date', $date_filter, $start_date, $end_date);

if(!empty($customer_id)) { $customer_id = mysqli_real_escape_string($conn, $customer_id); $where_clause .= " AND q.customer_id = '$customer_id'"; }
if(!empty($status)) {
    $status = mysqli_real_escape_string($conn, $status);
    if($status === 'sent') {
        $where_clause .= " AND (q.status = 'sent' OR q.status = 'Sent' OR q.status = '1')";
    } elseif($status === 'pending') {
        $where_clause .= " AND (q.status = 'pending' OR q.status = 'Pending' OR q.status = '0')";
    } else {
        $where_clause .= " AND q.status = '$status'";
    }
}

$query = "SELECT q.*, s.name as supplier_name 
          FROM quotations q
          LEFT JOIN suppliers s ON q.supplier_id = s.id
          $where_clause
          ORDER BY q.id DESC";

$query_run = mysqli_query($conn, $query);
$items = [];

// URL Definitions
$base_url = '/pos/quotations';
$process_url = '/pos/quotations/save_quotation.php'; // For Delete & Status

if($query_run) {
    while($row = mysqli_fetch_assoc($query_run)) {
        
        // --- 1. Related Party Logic ---
        $row['related_party'] = 'Unknown'; 
        if(!empty($row['supplier_id']) && $row['supplier_id'] != 0) {
             $row['related_party'] = $row['supplier_name'] . ' <span class="text-xs text-slate-400">(Supplier)</span>';
        } elseif($row['customer_id'] == 1) {
            $row['related_party'] = 'Walk-in Customer';
        } elseif($row['customer_id'] == 2) {
            $row['related_party'] = 'Regular Customer';
        } elseif(!empty($row['customer_id'])) {
            $c_id = mysqli_real_escape_string($conn, $row['customer_id']); 
            $c_query = mysqli_query($conn, "SELECT name FROM customers WHERE id='$c_id' LIMIT 1");
            if(mysqli_num_rows($c_query) > 0){
                $c_data = mysqli_fetch_assoc($c_query);
                $row['related_party'] = $c_data['name'];
            }
        }

        // --- 2. Formatting ---
        $row['formatted_date'] = date('d M, Y', strtotime($row['date']));
        $row['grand_total_display'] = 'TK ' . number_format($row['grand_total'], 2);
        
        // --- 3. Status Conversion (Fix for Toggle) ---
        // Reusable list expects 1 or 0. We map 'sent' to 1, others to 0.
        // Adjust this logic if your DB stores 1/0 directly.
        $db_status = strtolower($row['status']);
        $row['status_val'] = ($db_status == 'sent' || $db_status == '1') ? 1 : 0;

        // --- 4. Custom Actions (Fix for Delete Modal) ---
        $edit_url = $base_url . "/add_quotation.php?id=" . $row['id'];
        $ref_no_safe = addslashes($row['ref_no']);

        $actions = '<div class="flex items-center gap-2">';
        
        // View Button (Triggers your custom Modal)
        $actions .= '<button type="button" class="view-btn p-2 text-blue-500 hover:bg-blue-50 rounded transition" data-id="'.$row['id'].'" title="View"><i class="fas fa-eye"></i></button>';
        
        // Edit Button
        $actions .= '<a href="'.$edit_url.'" class="p-2 text-teal-600 hover:bg-teal-50 rounded transition" title="Edit"><i class="fas fa-edit"></i></a>';
        
        // Delete Button (FIX: Calls confirmDelete JS instead of Form Submit)
        $actions .= '<button type="button" onclick="confirmDelete('.$row['id'].', \''.$ref_no_safe.'\', \''.$process_url.'\')" class="p-2 text-red-500 hover:bg-red-50 rounded transition" title="Delete"><i class="fas fa-trash-alt"></i></button>';
        
        $actions .= '</div>';
        
        $row['custom_actions'] = $actions;
        $items[] = $row;
    }
}

// ---------------------------------------------------------
// 2. LIST CONFIGURATION
// ---------------------------------------------------------

// --- Fetch Filter Masters ---
$filters = [];

// Customer Filter
$cust_opts = [['label' => 'All Customers', 'url' => '?customer_id=', 'active' => empty($customer_id)]];
$cust_q = mysqli_query($conn, "SELECT id, name FROM customers ORDER BY name ASC");
if($cust_q){
    while($c = mysqli_fetch_assoc($cust_q)) {
        $cust_opts[] = [
            'label' => $c['name'],
            'url' => "?customer_id={$c['id']}",
            'active' => ($customer_id == $c['id'])
        ];
    }
}
$filters[] = [ 'id' => 'filter_cust', 'label' => 'Customer', 'searchable' => true, 'options' => $cust_opts ];

// Status Filter
$filters[] = [
    'id' => 'filter_status',
    'label' => 'Status',
    'options' => [
        ['label' => 'All Status', 'url' => '?status=', 'active' => ($status === '')],
        ['label' => 'Sent', 'url' => '?status=sent', 'active' => ($status === 'sent')],
        ['label' => 'Pending', 'url' => '?status=pending', 'active' => ($status === 'pending')],
    ]
];

$list_config = [
    'title' => 'Quotation List',
    'add_url' => '#',
    'table_id' => 'quotationTable',
    'status_url' => '/pos/quotations/save_quotation.php', // Required for Status Toggle
    'primary_key' => 'id',
    'name_field' => 'ref_no',
    'filters' => $filters,
    'date_column' => 'q.date',
    'columns' => [
        ['key' => 'formatted_date', 'label' => 'Date', 'sortable' => true],
        ['key' => 'ref_no', 'label' => 'Reference No', 'sortable' => true],
        ['key' => 'related_party', 'label' => 'Bill To', 'sortable' => true, 'type' => 'html'],
        ['key' => 'grand_total_display', 'label' => 'Grand Total', 'sortable' => true, 'type' => 'badge', 'badge_class' => 'bg-teal-500/10 text-teal-600 font-bold'],
        [
            'key' => 'status_val', // Using the converted 1/0 value
            'label' => 'Status', 
            'type' => 'status', 
            'active_label' => 'Sent',     
            'inactive_label' => 'Pending' 
        ],
        ['key' => 'custom_actions', 'label' => 'Actions', 'type' => 'html']
    ],
    'data' => $items
];

$page_title = "Quotation List - Velocity POS";
include('../includes/header.php');
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<div class="app-wrapper">
    <?php include('../includes/sidebar.php'); ?>
    
    <main id="main-content" class="flex-1 lg:ml-64 flex flex-col h-screen min-w-0 transition-all duration-300">
        <div class="navbar-fixed-top">
            <?php include('../includes/navbar.php'); ?>
        </div>
        
        <div class="content-scroll-area custom-scroll h-full overflow-y-auto bg-slate-50/50">
            <div class="p-6 max-w-7xl mx-auto w-full">
                <?php 
                if(file_exists('../includes/reusable_list.php')){
                    include('../includes/reusable_list.php'); 
                    renderReusableList($list_config); 
                } else {
                    echo "<div class='bg-red-100 text-red-700 p-4 rounded'>Error: reusable_list.php not found.</div>";
                }
                ?>
            </div>
        </div>
        <?php include('../includes/footer.php'); ?>
    </main>
</div>

<div id="viewModal" class="fixed inset-0 z-[9999] hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/70 backdrop-blur-sm transition-opacity" onclick="closeModal()"></div>

    <div class="fixed inset-0 z-10 overflow-y-auto">
        <input type="hidden" id="view_id_hidden">
        <div class="flex min-h-full items-center justify-center p-2 sm:p-4 text-center">
            
            <div class="relative transform overflow-hidden rounded-xl bg-white text-left shadow-2xl transition-all w-full max-w-4xl my-4">
                
                <div class="bg-teal-600 px-4 py-3 flex flex-wrap justify-between items-center gap-2 print:hidden">
                    <h3 class="text-sm sm:text-base font-semibold text-white truncate" id="modal-title">
                        <i class="fas fa-file-invoice mr-2"></i> Quotation Details
                    </h3>
                    <div class="flex flex-wrap gap-1 sm:gap-2">
                        <button onclick="downloadCSV()" class="p-1.5 sm:p-2 bg-teal-700 text-white rounded hover:bg-teal-800 transition" title="CSV"><i class="fas fa-file-csv"></i></button>
                        <button onclick="downloadImage()" class="p-1.5 sm:p-2 bg-teal-700 text-white rounded hover:bg-teal-800 transition" title="Image"><i class="fas fa-image"></i></button>
                        <button onclick="downloadPDF()" class="p-1.5 sm:p-2 bg-teal-700 text-white rounded hover:bg-teal-800 transition" title="PDF"><i class="fas fa-file-pdf"></i></button>
                        <button onclick="printModal()" class="p-1.5 sm:p-2 bg-teal-700 text-white rounded hover:bg-teal-800 transition" title="Print"><i class="fas fa-print"></i></button>
                        <button onclick="closeModal()" class="p-1.5 sm:p-2 bg-red-500 text-white rounded hover:bg-red-600 transition" title="Close"><i class="fas fa-times"></i></button>
                    </div>
                </div>

                <div class="bg-white p-4 sm:p-8 md:p-12" id="printableArea">
                    
                    <div class="flex flex-col-2 sm:flex-row justify-between items-center border-b-2 border-slate-800 pb-6 mb-8 gap-4">
                        <div>
                            <img class="w-24 bg-teal-600 rounded-sm" src="../assets/images/logo.png" alt="">
                        </div>
                        <div class="text-right sm:text-right w-full sm:w-auto">
                            <h2 class="text-2xl sm:text-3xl font-bold text-slate-200 uppercase tracking-widest mb-1">Quotation</h2>
                            <p class="font-mono text-slate-600 text-sm sm:text-base"># <span id="view_ref" class="text-slate-900 font-bold"></span></p>
                            <p class="text-xs sm:text-sm text-slate-500">Date: <span id="view_date"></span></p>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 md:grid-cols-3 gap-6 sm:gap-6 mb-4">
                        <div class="text-sm">
                            <p class="text-slate-400 font-bold uppercase text-[10px] mb-2">From:</p>
                            <h4 class="font-bold text-slate-800 text-base sm:text-lg">Velocity POS Inc.</h4>
                            <div class="text-slate-600 space-y-0.5">
                                <p class="mt-1 text-xs sm:text-sm">123 Tech Street, Silicon Valley</p>
                                <p class="mt-1 text-xs sm:text-sm">Dhaka, Bangladesh</p>
                                <p class="mt-1 text-xs sm:text-sm">info@velocitypos.com</p>
                                <p class="text-xs sm:text-sm">+880 123 456 7890</p>
                            </div>
                        </div>
                        
                        <div class="text-sm">
                            <p class="text-slate-400 font-bold uppercase text-[10px] mb-2">Bill To:</p>
                            <h4 class="font-bold text-slate-800 text-base sm:text-lg" id="view_customer_name">...</h4>
                            <div id="view_customer_details" class="text-slate-600 mt-1 space-y-1 text-xs sm:text-sm"></div>
                        </div>

                        <div class="flex flex-col items-start md:items-end">
                            <div class="bg-slate-50 p-3 rounded-lg border border-slate-200 w-full max-w-[140px] text-center">
                                <p class="text-[10px] text-slate-400 uppercase font-bold mb-2">Scan to Verify</p>
                                <div id="qrcode" class="flex justify-center mb-1"></div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-t-lg border border-slate-200 overflow-x-auto mb-8 custom-scroll">
                        <table class="w-full text-left border-collapse min-w-[700px]" id="exportTable">
                            <thead>
                                <tr class="bg-teal-600 text-white text-[10px] sm:text-xs uppercase tracking-wider">
                                    <th class="p-3 sm:p-4 font-semibold w-12 text-center">SL</th>
                                    <th class="p-3 sm:p-4 font-semibold w-20 text-center">Product</th>
                                    <th class="p-3 sm:p-4 font-semibold">Description</th>
                                    <th class="p-3 sm:p-4 font-semibold text-right">Unit Price</th>
                                    <th class="p-3 sm:p-4 font-semibold text-center">Qty</th>
                                    <th class="p-3 sm:p-4 font-semibold text-right">Tax</th>
                                    <th class="p-3 sm:p-4 font-semibold text-right bg-teal-700">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody id="view_items_body" class="text-xs sm:text-sm text-slate-700 bg-slate-50 divide-y divide-slate-200">
                                </tbody>
                        </table>
                    </div>

                    <div class="flex flex-col md:flex-row justify-between gap-8">
                        <div class="md:w-3/5 order-2 md:order-1">
                            <h5 class="font-bold text-slate-800 mb-2 border-b border-slate-200 pb-1 text-sm sm:text-base">Terms & Conditions:</h5>
                            <div id="view_terms" class="text-[11px] sm:text-xs text-slate-600 space-y-1 whitespace-pre-line leading-relaxed font-mono bg-slate-50 p-3 rounded border border-slate-100">
                            </div>
                            
                            <div class="mt-8 pt-8 border-t border-slate-200 w-48 text-center mx-auto md:mx-0">
                                <p class="text-[10px] font-bold text-slate-400 uppercase">Authorized Signature</p>
                            </div>
                        </div>

                        <div class="md:w-2/5 space-y-3 order-1 md:order-2">
                            <div class="flex justify-between text-xs sm:text-sm text-slate-600 px-1">
                                <span class="font-medium">Subtotal</span>
                                <span class="font-bold font-mono" id="view_subtotal">0.00</span>
                            </div>
                            <div class="flex justify-between text-xs sm:text-sm text-slate-600 px-1">
                                <span>Order Tax (+)</span>
                                <span class="font-mono" id="view_tax">0.00</span>
                            </div>
                            <div class="flex justify-between text-xs sm:text-sm text-slate-600 px-1">
                                <span>Shipping (+)</span>
                                <span class="font-mono" id="view_shipping">0.00</span>
                            </div>
                            <div class="flex justify-between text-xs sm:text-sm text-slate-600 px-1">
                                <span>Others (+)</span>
                                <span class="font-mono" id="view_others">0.00</span>
                            </div>
                            <div class="flex justify-between text-xs sm:text-sm text-red-500 px-1">
                                <span>Discount (-)</span>
                                <span class="font-mono">- <span id="view_discount">0.00</span></span>
                            </div>
                            
                            <div class="mt-4 bg-teal-600 text-white p-3 sm:p-4 rounded-lg shadow-lg flex justify-between items-center">
                                <span class="text-xs sm:text-sm font-bold uppercase tracking-wider">Grand Total</span>
                                <span class="text-lg sm:text-xl font-bold" id="view_grand_total">0.00</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    // Custom Toggle Status Override
    function customToggleStatus(id, currentStatus, updateUrl) {
        const newStatus = currentStatus == 1 ? 0 : 1;
        const statusLabel = newStatus == 1 ? 'Sent' : 'Pending';
        
        Swal.fire({
            title: `Change Status?`,
            text: `Do you want to mark this quotation as ${statusLabel}?`, 
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#0d9488',
            confirmButtonText: `Yes, Mark as ${statusLabel}`,
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = updateUrl;
                const inputId = document.createElement('input');
                inputId.type = 'hidden'; inputId.name = 'item_id'; inputId.value = id;
                form.appendChild(inputId);
                const inputStatus = document.createElement('input');
                inputStatus.type = 'hidden'; inputStatus.name = 'status'; inputStatus.value = newStatus;
                form.appendChild(inputStatus);
                const inputBtn = document.createElement('input');
                inputBtn.type = 'hidden'; inputBtn.name = 'toggle_status_btn'; inputBtn.value = '1';
                form.appendChild(inputBtn);
                document.body.appendChild(form);
                form.submit();
            }
        });
    }

    // AJAX View Details
    $(document).on('click', '.view-btn', function() {
        var id = $(this).data('id');
        $('#view_id_hidden').val(id);
        $('#view_ref').text('Loading...');
        
        $.ajax({
            url: '/pos/quotations/save_quotation.php',
            type: 'POST',
            data: { view_quotation_btn: true, quotation_id: id },
            dataType: 'json',
            success: function(response) {
                if(response.status == 200) {
                    var q = response.data;
                    var items = response.items;

                    $('#view_ref').text(q.ref_no);
                    try {
                        var dateObj = new Date(q.date);
                        $('#view_date').text(dateObj.toLocaleDateString("en-GB", { day: 'numeric', month: 'short', year: 'numeric' }));
                    } catch (e) { $('#view_date').text(q.date); }
                    
                    // Logic to show Bill To Name & Address
                    var billToName = 'N/A';
                    var billToHtml = '';

                    if(q.supplier_id && q.supplier_id != 0) {
                        billToName = q.supplier_name;
                        if(q.s_address) billToHtml += '<p class="block">' + q.s_address + '</p>';
                        if(q.s_city || q.s_country) billToHtml += '<p class="block">' + (q.s_city || '') + ' ' + (q.s_country || '') + '</p>';
                        if(q.s_mobile) billToHtml += '<p class="block">' + q.s_mobile + '</p>';
                        if(q.s_email) billToHtml += '<p class="block">' + q.s_email + '</p>';
                    } else if(q.customer_id == 2) {
                        billToName = 'Regular Customer';
                        billToHtml += '<p class="block">Retail Customer</p>';
                    } else if(q.customer_id == 1) {
                         billToName = 'Walk-in Customer';
                         billToHtml += '<p class="block">Retail Customer</p>';
                    } else {
                         billToName = q.customer_name || 'Customer';
                         if(q.c_mobile) billToHtml += '<p class="block">' + q.c_mobile + '</p>';
                    }

                    $('#view_customer_name').text(billToName);
                    $('#view_customer_details').html(billToHtml);
                    $('#view_terms').text((q.terms && q.terms.trim() !== "") ? q.terms : "N/A");

                    $('#qrcode').empty();
                    new QRCode(document.getElementById("qrcode"), {
                        text: "Ref: " + q.ref_no + " | Amt: " + q.grand_total,
                        width: 100, height: 100,
                        colorDark : "#036661", colorLight : "#ffffff"
                    });

                    var rows = '';
                    if(items && items.length > 0) {
                        $.each(items, function(key, item) {
                            // Generate placeholder initials
                            const initials = (item.product_name || 'NA').split(' ').map(n=>n[0]).join('').substring(0,2).toUpperCase();
                            var imgDisplay = '';
                            
                            // Check for valid thumbnail
                            if(item.thumbnail && item.thumbnail !== '/pos/assets/img/no-image.png') {
                                imgDisplay = '<img src="' + item.thumbnail + '" class="w-10 h-10 object-cover rounded shadow-sm border border-slate-200 mx-auto bg-white" onerror="this.onerror=null; this.replaceWith(Object.assign(document.createElement(\'div\'), {className: \'w-10 h-10 flex items-center justify-center bg-teal-100 text-teal-800 font-bold rounded shadow-sm border border-teal-200 mx-auto text-xs\', innerText: \'' + initials + '\'}))">';
                            } else {
                                imgDisplay = '<div class="w-10 h-10 flex items-center justify-center bg-teal-100 text-teal-800 font-bold rounded shadow-sm border border-teal-200 mx-auto text-xs">' + initials + '</div>';
                            }

                            rows += '<tr class="hover:bg-teal-50 transition-colors border-b border-slate-100">';
                            rows += '<td class="p-3 text-center text-slate-400 text-[10px]">' + (key + 1) + '</td>';
                            rows += '<td class="p-3 text-center">' + imgDisplay + '</td>';
                            rows += '<td class="p-3 font-medium text-slate-800">' + item.product_name + '<br><span class="text-[10px] text-slate-500 font-mono bg-slate-100 px-1 rounded">' + (item.product_code || 'N/A') + '</span></td>';
                            rows += '<td class="p-3 text-right font-mono text-slate-600">' + parseFloat(item.price || 0).toFixed(2) + '</td>';
                            rows += '<td class="p-3 text-center font-bold">' + parseFloat(item.qty || 0) + '</td>';
                            var taxInfo = item.tax_method === 'exclusive' ? '<span class="text-[10px] text-red-400">(Excl)</span>' : '<span class="text-[10px] text-green-500">(Incl)</span>';
                            rows += '<td class="p-3 text-right">' + taxInfo + '</td>';
                            rows += '<td class="p-3 text-right font-bold text-slate-800 bg-slate-100/30">' + parseFloat(item.subtotal || 0).toFixed(2) + '</td>';
                            rows += '</tr>';
                        });
                    }
                    $('#view_items_body').html(rows);

                    const parseVal = (val) => parseFloat(val || 0);
                    $('#view_subtotal').text(parseVal(q.subtotal).toFixed(2));
                    
                    var taxRate = parseVal(q.order_tax_rate);
                    var discount = parseVal(q.discount);
                    // Simple tax recalc for display mostly
                    var taxable = parseVal(q.subtotal) - discount;
                    var calculatedTax = (taxable * taxRate) / 100;
                    
                    $('#view_tax').text(calculatedTax.toFixed(2) + ' ('+taxRate+'%)');
                    $('#view_shipping').text(parseVal(q.shipping_cost).toFixed(2));
                    $('#view_others').text(parseVal(q.others_charge).toFixed(2));
                    $('#view_discount').text(discount.toFixed(2));
                    $('#view_grand_total').text(parseVal(q.grand_total).toFixed(2));

                    $('#viewModal').removeClass('hidden');
                } else {
                    alert('Error: ' + response.message);
                }
            }
        });
    });

    function closeModal() { $('#viewModal').addClass('hidden'); }

    // Ultimate Print Logic (Hidden Iframe + Full CSS)
    function printModal() {
        const modalContent = document.getElementById('printableArea').innerHTML;
        const refNo = $('#view_ref').text();
        
        // Remove old frame if exists to ensure fresh state
        $('#print_frame_hidden').remove();
        
        // Create hidden iframe
        const iframe = document.createElement('iframe');
        iframe.id = 'print_frame_hidden';
        iframe.style.position = 'fixed';
        iframe.style.bottom = '0';
        iframe.style.right = '0';
        iframe.style.width = '0px';
        iframe.style.height = '0px';
        iframe.style.border = 'none';
        document.body.appendChild(iframe);
        
        const doc = iframe.contentWindow.document;
        
        // Write Content with FULL CSS (Cached)
        doc.open();
        doc.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Quotation_${refNo}</title>
                <!-- Reuse main cached CSS -->
                <link rel="stylesheet" href="/pos/assets/css/output.css">
                <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"/>
                <style>
                    body { background: white; padding: 0; margin: 0; }
                    /* Wrapper for proper sizing */
                    #wrapper { padding: 40px; max-width: 900px; margin: 0 auto; }
                    
                    @media print {
                        body { padding: 0; }
                        #wrapper { padding: 0; width: 100%; max-width: 100%; margin: 0; }
                        .no-print { display: none !important; }
                        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
                        
                        /* Ensure Tailwind Grid/Flex work */
                        .grid { display: grid !important; }
                        .flex { display: flex !important; }
                    }
                </style>
            </head>
            <body>
                <div id="wrapper">
                    ${modalContent}
                </div>
            </body>
            </html>
        `);
        doc.close();
        
        // Wait for CSS to load then print
        iframe.onload = function() {
            setTimeout(function() {
                iframe.contentWindow.focus();
                iframe.contentWindow.print();
            }, 500); // Short delay for rendering
        };
    }

    // Image Download Logic
    function downloadImage() {
        const element = document.getElementById('printableArea');
        html2canvas(element, { scale: 2, useCORS: true }).then(canvas => {
            var link = document.createElement('a');
            link.download = 'Quotation_' + $('#view_ref').text() + '.png';
            link.href = canvas.toDataURL();
            link.click();
        });
    }

    // PDF Download Logic
    function downloadPDF() {
        const { jsPDF } = window.jspdf;
        const element = document.getElementById('printableArea');
        html2canvas(element, { scale: 2, useCORS: true }).then(canvas => {
            const imgData = canvas.toDataURL('image/png');
            const pdf = new jsPDF('p', 'mm', 'a4');
            const pdfWidth = pdf.internal.pageSize.getWidth();
            const pdfHeight = (canvas.height * pdfWidth) / canvas.width;
            pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
            pdf.save('Quotation_' + $('#view_ref').text() + '.pdf');
        });
    }

    // CSV Download Logic
    function downloadCSV() {
        var data = [];
        data.push(["QUOTATION DETAILS"], ["Reference No", $('#view_ref').text()], ["Date", $('#view_date').text()], ["Bill To", $('#view_customer_name').text()]);
        var headerRow = [];
        $("#exportTable thead th").each(function(i) { if(i !== 1) headerRow.push($(this).text().trim()); });
        data.push(headerRow);
        $("#exportTable tbody tr").each(function() {
            var row = [];
            $(this).find("td").each(function(i) { if(i !== 1) row.push($(this).text().trim()); });
            data.push(row);
        });
        var csvContent = data.map(e => e.join(",")).join("\n");
        var blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
        var link = document.createElement("a");
        link.href = URL.createObjectURL(blob);
        link.download = 'Quotation_' + $('#view_ref').text() + '.csv';
        link.click();
    }
</script>