<?php
session_start();
include('../config/dbcon.php');

if(!isset($_SESSION['auth_user'])){
    header("Location: /pos/signin.php");
    exit(0);
}

$query = "SELECT g.*, c.name as customer_name, u.name as created_by_name FROM giftcards g 
          LEFT JOIN customers c ON g.customer_id = c.id 
          LEFT JOIN users u ON g.created_by = u.id 
          ORDER BY g.id DESC";
$query_run = mysqli_query($conn, $query);
$items = [];

if($query_run) {
    while($row = mysqli_fetch_assoc($query_run)) {
        // Format values
        $row['value_formatted'] = 'USD ' . number_format((float)$row['value'], 2);
        $row['balance_formatted'] = 'USD ' . number_format((float)$row['balance'], 2);
        $row['expiry_formatted'] = !empty($row['expiry_date']) ? date('d M, Y', strtotime($row['expiry_date'])) : 'N/A';
        
        // Status Badge (Clickable to toggle)
        $row['status_badge'] = $row['status'] == 1 ? 
            '<button onclick="toggleStatus('.$row['id'].', 1, \'/pos/giftcard/save\')" class="px-3 py-1 rounded-full bg-emerald-50 text-emerald-600 text-xs font-black uppercase tracking-wider hover:bg-emerald-100 cursor-pointer transition-all">✓ Active</button>' :
            '<button onclick="toggleStatus('.$row['id'].', 0, \'/pos/giftcard/save\')" class="px-3 py-1 rounded-full bg-amber-50 text-amber-600 text-xs font-black uppercase tracking-wider hover:bg-amber-100 cursor-pointer transition-all">⚠ Inactive</button>';

        // Custom Actions
        $row['custom_actions'] = '<div class="flex items-center gap-2">
                                <button onclick="viewGiftcard('.$row['id'].')" class="w-8 h-8 inline-flex items-center justify-center text-indigo-500 bg-indigo-50 rounded-lg hover:bg-indigo-600 hover:text-white transition-all" title="View Details">
                                    <i class="fas fa-eye text-xs"></i>
                                </button>
                                <a href="/pos/giftcard/edit?id='.$row['id'].'" class="w-8 h-8 inline-flex items-center justify-center text-teal-600 bg-teal-50 rounded-lg hover:bg-teal-600 hover:text-white transition-all" title="Edit">
                                    <i class="fas fa-edit text-xs"></i>
                                </a>
                                <button onclick="openTopupModal('.$row['id'].')" class="w-8 h-8 inline-flex items-center justify-center text-amber-600 bg-amber-50 rounded-lg hover:bg-amber-600 hover:text-white transition-all" title="Add Topup">
                                    <i class="fas fa-plus-circle text-xs"></i>
                                </button>
                                <button type="button" onclick="confirmDelete('.$row['id'].', \''.addslashes($row['card_no']).'\', \'/pos/giftcard/save_giftcard.php\')" 
                                        class="w-8 h-8 inline-flex items-center justify-center text-red-600 bg-red-50 rounded-lg hover:bg-red-600 hover:text-white transition-all" title="Delete">
                                    <i class="fas fa-trash-alt text-xs"></i>
                                </button>
                           </div>';

        $items[] = $row;
    }
}

$list_config = [
    'title' => 'Giftcard Management',
    'add_url' => '/pos/giftcard/add',
    'table_id' => 'giftcardTable',
    'columns' => [
        ['key' => 'id', 'label' => 'ID', 'sortable' => true],
        ['key' => 'card_no', 'label' => 'Card No.'],
        ['key' => 'value_formatted', 'label' => 'Value'],
        ['key' => 'balance_formatted', 'label' => 'Balance'],
        ['key' => 'created_by_name', 'label' => 'Created By'],
        ['key' => 'customer_name', 'label' => 'Customer'],
        ['key' => 'expiry_formatted', 'label' => 'Expiry'],
        ['key' => 'status_badge', 'label' => 'Status', 'type' => 'html'],
        ['key' => 'custom_actions', 'label' => 'Actions', 'type' => 'html']
    ],
    'data' => $items,
    'delete_url' => '/pos/giftcard/save',
    'status_url' => '/pos/giftcard/save',
    'primary_key' => 'id',
    'permissions' => [
        'add' => 'giftcard_add',
        'view' => 'giftcard_view',
        'edit' => 'giftcard_edit',
        'delete' => 'giftcard_delete'
    ]
];

$page_title = "Giftcard Management - Velocity POS";
include('../includes/header.php');
?>
<div class="app-wrapper">
    <?php include('../includes/sidebar.php'); ?>
    <main id="main-content" class="flex-1 lg:ml-64 flex flex-col h-screen min-w-0 transition-all duration-300">
        <?php include('../includes/navbar.php'); ?>
        <div class="content-scroll-area custom-scroll h-full overflow-y-auto p-6">
            <?php include('../includes/reusable_list.php'); renderReusableList($list_config); ?>
            <?php include('../includes/footer.php'); ?>
        </div>
    </main>
</div>

<!-- View Modal -->
<div id="viewGiftcardModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
    <div class="modal-content bg-white rounded-3xl shadow-2xl w-11/12 md:w-2/3 max-h-[90vh] overflow-y-auto scale-95 opacity-0 transition-all duration-300 transform">
        <div class="sticky top-0 bg-white border-b border-slate-100 px-8 py-6 flex items-center justify-between rounded-t-3xl">
            <div>
                <h2 class="text-2xl font-black text-slate-800">GIFTCARD DETAILS</h2>
                <p class="text-slate-400 text-sm mt-1">View giftcard information and manage balance</p>
            </div>
            <button onclick="closeViewGiftcardModal()" class="w-10 h-10 flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-xl transition-all">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>
        <div id="giftcard_view_body" class="p-8">
            <div class="flex items-center justify-center py-12">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-teal-600"></div>
            </div>
        </div>
    </div>
</div>

<!-- Topup Modal -->
<div id="topupModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
    <div class="bg-white rounded-3xl shadow-2xl w-11/12 md:w-96 p-8 scale-95 opacity-0 transition-all duration-300 transform">
        <h2 class="text-2xl font-black text-slate-800 mb-6">Add Topup</h2>
        <form id="topupForm" method="POST" action="/pos/giftcard/save">
            <input type="hidden" name="topup_giftcard_btn" value="1">
            <input type="hidden" id="topup_giftcard_id" name="giftcard_id" value="">
            
            <div class="mb-4">
                <label class="text-xs font-black text-slate-400 uppercase mb-2 block">Current Balance</label>
                <input type="text" id="current_balance_display" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-slate-700 font-bold" readonly>
            </div>

            <div class="mb-4">
                <label class="text-xs font-black text-slate-400 uppercase mb-2 block">Maximum Can Add</label>
                <input type="text" id="max_can_add_display" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-slate-700 font-bold" readonly>
            </div>

            <div class="mb-4">
                <label class="text-xs font-black text-slate-400 uppercase mb-2 block">Amount to Add</label>
                <input type="number" id="topup_amount" name="topup_amount" step="0.01" min="0" class="w-full border border-slate-200 rounded-xl px-4 py-3 focus:outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-100" required>
            </div>

            <div class="mb-6">
                <label class="text-xs font-black text-slate-400 uppercase mb-2 block">Note (Optional)</label>
                <textarea name="topup_note" class="w-full border border-slate-200 rounded-xl px-4 py-3 focus:outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-100" rows="3"></textarea>
            </div>

            <div class="flex gap-3">
                <button type="button" onclick="closeTopupModal()" class="flex-1 px-4 py-3 bg-slate-100 text-slate-700 rounded-xl font-bold hover:bg-slate-200 transition-all">Cancel</button>
                <button type="submit" class="flex-1 px-4 py-3 bg-teal-600 text-white rounded-xl font-bold hover:bg-teal-700 transition-all">Add Topup</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
<script>
// Store current giftcard ID for modal operations
var currentGiftcardId = null;

function viewGiftcard(id) {
    currentGiftcardId = id;
    const modal = document.getElementById('viewGiftcardModal');
    const content = modal.querySelector('.modal-content');
    const body = document.getElementById('giftcard_view_body');

    // Show modal and reset body
    modal.classList.remove('hidden');
    body.innerHTML = '<div class="flex items-center justify-center py-12"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-teal-600"></div></div>';
    
    // Animate in
    setTimeout(() => {
        content.classList.remove('scale-95', 'opacity-0');
        content.classList.add('scale-100', 'opacity-100');
    }, 10);

    // Fetch Giftcard Details
    $.ajax({
        type: "POST",
        url: "/pos/giftcard/fetch_giftcard_modal.php",
        data: { giftcard_id: id },
        success: function(response) {
            body.innerHTML = response;
            
            // Generate barcode after content is loaded
            setTimeout(function() {
                generateModalBarcode(id);
                attachModalButtonHandlers(id);
            }, 100);
        },
        error: function() {
            body.innerHTML = '<p class="text-center py-8 text-red-500">Failed to load giftcard data.</p>';
        }
    });
}

// Generate barcode in the modal
function generateModalBarcode(id) {
    var svgElement = document.getElementById('modal-barcode-svg-' + id);
    if (!svgElement) {
        console.log('SVG element not found: modal-barcode-svg-' + id);
        return;
    }
    
    // Get card number from the hidden data div
    var dataDiv = document.getElementById('modal-card-data-' + id);
    var cardNo = null;
    
    if (dataDiv) {
        cardNo = dataDiv.getAttribute('data-card-no');
    }
    
    // Fallback: try to get from the visible text
    if (!cardNo) {
        var cardNoElement = document.querySelector('#giftcard_view_body .text-lg.font-bold');
        if (cardNoElement) {
            cardNo = cardNoElement.textContent.trim();
        }
    }
    
    console.log('Generating barcode for:', cardNo);
    
    if (typeof JsBarcode !== 'undefined' && cardNo) {
        try {
            JsBarcode('#modal-barcode-svg-' + id, cardNo, {
                format: "CODE128",
                width: 2,
                height: 40,
                displayValue: false,
                margin: 2
            });
            console.log('Barcode generated successfully');
        } catch(e) {
            console.error('Barcode error:', e);
        }
    } else {
        console.log('JsBarcode not loaded or cardNo empty');
    }
}

// Attach handlers to buttons in the modal
function attachModalButtonHandlers(id) {
    // Print button
    var printBtn = document.getElementById('print-btn-' + id);
    if (printBtn) {
        printBtn.onclick = function() {
            window.print();
        };
    }
    
    // Topup button
    var topupBtn = document.getElementById('topup-btn-' + id);
    if (topupBtn) {
        topupBtn.onclick = function() {
            closeViewGiftcardModal();
            setTimeout(function() {
                openTopupModal(id);
            }, 350);
        };
    }
}
function closeViewGiftcardModal() {
    const modal = document.getElementById('viewGiftcardModal');
    const content = modal.querySelector('.modal-content');
    
    content.classList.add('scale-95', 'opacity-0');
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 300);
}


function openTopupModal(id) {
    var modal = document.getElementById('topupModal');
    var modalContent = modal.querySelector('.bg-white');
    
    // Show modal
    modal.classList.remove('hidden');
    document.getElementById('topup_giftcard_id').value = id;

    // Animate in
    setTimeout(function() {
        modalContent.classList.remove('scale-95', 'opacity-0');
        modalContent.classList.add('scale-100', 'opacity-100');
    }, 10);

    $.ajax({
        type: "POST",
        url: "/pos/giftcard/save",
        // 'action' name-e ekta parameter pathate hobe jeta PHP file-e check hobe
        data: { action: 'get_balance', id: id }, 
        dataType: 'json',
        success: function(response) {
            document.getElementById('current_balance_display').value = 'USD ' + response.balance;
            document.getElementById('max_can_add_display').value = 'USD ' + response.remaining; 
            document.getElementById('topup_amount').removeAttribute('max');
        },
        error: function(xhr) {
            console.log(xhr.responseText); // Error check korar jonno
        }
    });
}
function closeTopupModal() {
    const modal = document.getElementById('topupModal');
    const modalContent = modal.querySelector('.bg-white');
    
    // Animate out
    modalContent.classList.remove('scale-100', 'opacity-100');
    modalContent.classList.add('scale-95', 'opacity-0');
    
    setTimeout(() => {
        modal.classList.add('hidden');
        // Reset form fields
        document.getElementById('topup_amount').value = '';
        document.getElementById('current_balance_display').value = '';
        document.getElementById('max_can_add_display').value = '';
        document.querySelector('#topupForm textarea[name="topup_note"]').value = '';
    }, 300);
}

// Close modal when clicking outside
document.getElementById('viewGiftcardModal')?.addEventListener('click', function(e) {
    if(e.target === this) closeViewGiftcardModal();
});

document.getElementById('topupModal')?.addEventListener('click', function(e) {
    if(e.target === this) closeTopupModal();
});
</script>
