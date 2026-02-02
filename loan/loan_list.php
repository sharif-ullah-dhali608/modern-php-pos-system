<?php
session_start();
include('../config/dbcon.php');
include('../includes/date_filter_helper.php');

if(!isset($_SESSION['auth'])){
    header("Location: /pos/login");
    exit(0);
}

$page_title = "Loan Management - Velocity POS";
include('../includes/header.php');

// Filter parameters
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';
$filter_loan_from = isset($_GET['loan_from']) ? $_GET['loan_from'] : '';
$date_filter = $_GET['date_filter'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// Build Query
$query = "SELECT * FROM loans WHERE 1=1";

// Apply status filter
if($filter_status == 'active') {
    $query .= " AND status = 1";
} elseif($filter_status == 'inactive') {
    $query .= " AND status = 0";
} elseif($filter_status == 'due') {
    $query .= " AND due > 0";
} elseif($filter_status == 'paid') {
    $query .= " AND due <= 0";
}

// Apply loan_from filter
if(!empty($filter_loan_from)) {
    $filter_loan_from_escaped = mysqli_real_escape_string($conn, $filter_loan_from);
    $query .= " AND loan_from = '$filter_loan_from_escaped'";
}

// Apply date filter using helper
applyDateFilter($query, 'created_at', $date_filter, $start_date, $end_date);

$query .= " ORDER BY created_at DESC";

$query_run = mysqli_query($conn, $query);
$items = [];

// Summary totals
$summary_total_loan = 0;
$summary_total_paid = 0;
$summary_total_due = 0;
$summary_total_count = 0;

if($query_run) {
    while($row = mysqli_fetch_assoc($query_run)) {
        // Format values
        $row['amount_formatted'] = number_format((float)$row['amount'], 2);
        $row['interest_formatted'] = number_format((float)$row['interest'], 2) . '%';
        $row['payable_formatted'] = number_format((float)$row['payable'], 2);
        $row['paid_formatted'] = number_format((float)$row['paid'], 2);
        $row['due_formatted'] = number_format((float)$row['due'], 2);
        $row['date_formatted'] = date('d M, Y', strtotime($row['created_at']));
        $row['deadline_formatted'] = (!empty($row['deadline_date']) && $row['deadline_date'] != '0000-00-00') ? date('d M, Y', strtotime($row['deadline_date'])) : '-';
        
        // Status Badge
        $row['status_badge'] = $row['status'] == 1 ? 
            '<span class="px-3 py-1 rounded-full bg-emerald-50 text-emerald-600 text-xs font-black uppercase tracking-wider">✓ Active</span>' :
            '<span class="px-3 py-1 rounded-full bg-amber-50 text-amber-600 text-xs font-black uppercase tracking-wider">⚠ Inactive</span>';

        // Pay Status Badge
        if($row['due'] > 0) {
            $row['pay_status_badge'] = '<span class="px-3 py-1 rounded-full bg-rose-50 text-rose-600 text-xs font-black uppercase tracking-wider">DUE</span>';
        } else {
            $row['pay_status_badge'] = '<span class="px-3 py-1 rounded-full bg-emerald-50 text-emerald-600 text-xs font-black uppercase tracking-wider">PAID</span>';
        }

        // Custom Actions (Pay, Edit, Delete)
        $payBtn = '';
        if($row['due'] > 0) {
            $payBtn = '<button onclick="openPayModal('.$row['loan_id'].', \''.addslashes($row['title']).'\', '.$row['due'].')" class="w-8 h-8 inline-flex items-center justify-center text-emerald-600 bg-emerald-50 rounded-lg hover:bg-emerald-600 hover:text-white transition-all" title="Add Payment">
                            <i class="fas fa-money-bill-wave text-xs"></i>
                        </button>';
        }
        $row['custom_actions'] = '<div class="flex items-center gap-2">
                                '.$payBtn.'
                                <a href="/pos/loan/edit/'.$row['loan_id'].'" class="w-8 h-8 inline-flex items-center justify-center text-teal-600 bg-teal-50 rounded-lg hover:bg-teal-600 hover:text-white transition-all" title="Edit">
                                    <i class="fas fa-edit text-xs"></i>
                                </a>
                                <button type="button" onclick="confirmDelete('.$row['loan_id'].', \''.addslashes($row['title']).'\', \'/pos/loan/save\')" 
                                        class="w-8 h-8 inline-flex items-center justify-center text-red-600 bg-red-50 rounded-lg hover:bg-red-600 hover:text-white transition-all" title="Delete">
                                    <i class="fas fa-trash-alt text-xs"></i>
                                </button>
                           </div>';

        // Summary calculations
        $summary_total_loan += $row['payable'];
        $summary_total_paid += $row['paid'];
        $summary_total_due += $row['due'];
        $summary_total_count++;

        $items[] = $row;
    }
}

// Build filter options - Status
$status_filter_options = [
    ['label' => 'All Loans', 'url' => '?status=all' . ($filter_loan_from ? '&loan_from='.$filter_loan_from : ''), 'active' => $filter_status == 'all'],
    ['label' => 'Active Loans', 'url' => '?status=active' . ($filter_loan_from ? '&loan_from='.$filter_loan_from : ''), 'active' => $filter_status == 'active'],
    ['label' => 'Inactive Loans', 'url' => '?status=inactive' . ($filter_loan_from ? '&loan_from='.$filter_loan_from : ''), 'active' => $filter_status == 'inactive'],
    ['label' => 'Due Loans', 'url' => '?status=due' . ($filter_loan_from ? '&loan_from='.$filter_loan_from : ''), 'active' => $filter_status == 'due'],
    ['label' => 'Paid Loans', 'url' => '?status=paid' . ($filter_loan_from ? '&loan_from='.$filter_loan_from : ''), 'active' => $filter_status == 'paid'],
];

// Build filter options - Loan From
$loan_from_filter_options = [
    ['label' => 'All Sources', 'url' => '?status='.$filter_status, 'active' => empty($filter_loan_from)],
    ['label' => 'Bank', 'url' => '?status='.$filter_status.'&loan_from=Bank', 'active' => $filter_loan_from == 'Bank'],
    ['label' => 'Others', 'url' => '?status='.$filter_status.'&loan_from=Others', 'active' => $filter_loan_from == 'Others'],
];

// Currency symbol - Set to empty as per user request
$currency = '';

// Reusable List Configuration
$list_config = [
    'title' => 'Loan Management',
    'add_url' => '/pos/loan/add',
    'table_id' => 'loanTable',
    'delete_url' => '/pos/loan/save',
    'primary_key' => 'loan_id',
    'name_field' => 'title',
    
    // Filters
    'filters' => [
        ['id' => 'filter_status', 'label' => 'All Loans', 'options' => $status_filter_options],
        ['id' => 'filter_loan_from', 'label' => 'All Sources', 'options' => $loan_from_filter_options]
    ],
    
    // Date filter configuration
    'date_column' => 'created_at',
    
    // Summary Cards
    'summary_cards' => [
        ['label' => 'Total Loan', 'value' => $currency . number_format($summary_total_loan, 2), 'border_color' => 'border-sky-500'],
        ['label' => 'Total Paid', 'value' => $currency . number_format($summary_total_paid, 2), 'border_color' => 'border-emerald-500'],
        ['label' => 'Total Due', 'value' => $currency . number_format($summary_total_due, 2), 'border_color' => 'border-rose-500'],
        ['label' => 'Total Loans', 'value' => number_format($summary_total_count), 'border_color' => 'border-amber-500']
    ],
    
    // Columns
    'columns' => [
        ['key' => 'loan_id', 'label' => 'ID', 'sortable' => true],
        ['key' => 'date_formatted', 'label' => 'Date'],
        ['key' => 'deadline_formatted', 'label' => 'Deadline'],
        ['key' => 'title', 'label' => 'Title'],
        ['key' => 'loan_from', 'label' => 'Source'],
        ['key' => 'amount_formatted', 'label' => 'Amount'],
        ['key' => 'interest_formatted', 'label' => 'Interest'],
        ['key' => 'payable_formatted', 'label' => 'Payable'],
        ['key' => 'paid_formatted', 'label' => 'Paid'],
        ['key' => 'due_formatted', 'label' => 'Due'],
        ['key' => 'pay_status_badge', 'label' => 'Loan Status', 'type' => 'html'],
        ['key' => 'status_badge', 'label' => 'Status', 'type' => 'html'],
        ['key' => 'custom_actions', 'label' => 'Actions', 'type' => 'html']
    ],
    
    'data' => $items,
];

// Deadline Alerts Logic
$today = new DateTime();
$today->setTime(0, 0, 0); // Start of today
$alerts = [];

$alert_query = "SELECT * FROM loans WHERE due > 0 AND status = 1 AND deadline_date IS NOT NULL AND deadline_date != '0000-00-00' AND deadline_date != ''";
$alert_res = mysqli_query($conn, $alert_query);
if($alert_res) {
    while($row = mysqli_fetch_assoc($alert_res)) {
        $deadline = new DateTime($row['deadline_date']);
        $deadline->setTime(0, 0, 0);
        $diff = $today->diff($deadline);
        $days = (int)$diff->format("%r%a"); 
        
        // Only show if deadline is within 5 days range (Upcoming 5 days or Overdue by max 5 days)
        if(abs($days) <= 5) {
            $alerts[] = [
                'id' => $row['loan_id'],
                'title' => $row['title'],
                'loan_from' => $row['loan_from'],
                'payable' => $row['payable'],
                'due' => $row['due'],
                'attachment' => $row['attachment'],
                'days' => $days,
                'is_overdue' => ($days < 0),
                'is_today' => ($days === 0)
            ];
        }
    }
}
?>

<div class="app-wrapper">
    <?php include('../includes/sidebar.php'); ?>
    <main id="main-content" class="flex-1 lg:ml-64 flex flex-col h-screen min-w-0 transition-all duration-300 bg-slate-50">
        <div class="navbar-fixed-top">
            <?php include('../includes/navbar.php'); ?>
        </div>
        <div class="content-scroll-area custom-scroll flex-1 overflow-y-auto">
            <div class="flex flex-col min-h-full">
                <div class="p-4 md:p-6 max-w-7xl mx-auto w-full flex-1">
                    <!-- Modern High-Information Dashboard Alerts (Aligned & Stable) -->
                    <?php if(!empty($alerts)): ?>
                    <div class="mb-10 space-y-3">
                        <?php foreach($alerts as $alert): ?>
                            <?php 
                                $is_urgent = $alert['is_overdue'] || $alert['is_today'];
                                $accent_color = $is_urgent ? 'red' : 'indigo';
                                
                                if($alert['is_overdue']) {
                                    $status_label = 'Overdue';
                                    $days_text = abs($alert['days']) . ' Days Late';
                                } elseif($alert['is_today']) {
                                    $status_label = 'Due Today';
                                    $days_text = 'Final Call';
                                } else {
                                    $status_label = 'Upcoming';
                                    $days_text = 'In ' . $alert['days'] . ' Days';
                                }

                                // Attachment logic
                                $has_file = !empty($alert['attachment']) && file_exists("../" . $alert['attachment']);
                                $is_image = false;
                                if($has_file) {
                                    $ext = strtolower(pathinfo($alert['attachment'], PATHINFO_EXTENSION));
                                    $is_image = in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif']);
                                }
                            ?>
                            <div class="group relative bg-white border border-slate-200 rounded-[1.25rem] p-4 transition-all duration-300 hover:shadow-2xl hover:shadow-slate-200 hover:border-<?= $accent_color; ?>-200">
                                
                                <div class="flex flex-col lg:flex-row items-center gap-6">
                                    
                                    <!-- Visual Preview Pane -->
                                    <div class="flex-shrink-0 flex items-center border-r border-slate-100 pr-6 gap-4">
                                        <div class="relative">
                                            <div class="w-14 h-14 rounded-2xl overflow-hidden bg-slate-50 border border-slate-100 flex items-center justify-center">
                                                <?php if($has_file && $is_image): ?>
                                                    <img src="/pos/<?= $alert['attachment']; ?>" class="w-full h-full object-cover transition-transform group-hover:scale-110" alt="File">
                                                <?php else: ?>
                                                    <i class="fas fa-file-invoice-dollar text-xl text-slate-300"></i>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="hidden sm:block min-w-[90px]">
                                            <span class="inline-block px-2 py-0.5 rounded-md bg-<?= $accent_color; ?>-50 text-<?= $accent_color; ?>-600 text-[9px] font-black uppercase tracking-widest mb-1">
                                                <?= $status_label; ?>
                                            </span>
                                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-tighter italic">
                                                <i class="fas fa-clock mr-1 text-<?= $accent_color; ?>-500"></i> <?= $days_text; ?>
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Information Content Pane -->
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 mb-1.5">
                                            <span class="text-[10px] font-bold text-slate-300 font-mono tracking-tighter">#REF-<?= str_pad($alert['id'], 3, '0', STR_PAD_LEFT); ?></span>
                                            <span class="w-1 h-1 rounded-full bg-slate-200"></span>
                                            <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest flex items-center gap-1.5">
                                                <i class="fas fa-building text-[9px] text-slate-400"></i> <?= htmlspecialchars($alert['loan_from']); ?>
                                            </span>
                                            <span class="w-1 h-1 rounded-full bg-slate-200"></span>
                                            <span class="text-[10px] font-black text-rose-500 uppercase tracking-widest flex items-center gap-1.5 bg-rose-50 px-2 py-0.5 rounded">
                                                <i class="fas fa-hourglass-half text-[8px]"></i> <?= $days_text; ?>
                                            </span>
                                        </div>
                                        <h4 class="text-[15px] font-black text-slate-800 tracking-tight leading-normal truncate">
                                            <?= htmlspecialchars($alert['title']); ?>
                                        </h4>
                                    </div>

                                    <!-- Financial Action Pane (Fixed Alignment) -->
                                    <div class="flex items-center justify-end gap-6 lg:pl-6 lg:border-l border-slate-100 min-w-fit">
                                        <!-- Secondary Stat -->
                                        <div class="text-right whitespace-nowrap px-4 border-r border-slate-50 hidden md:block">
                                            <p class="text-[8px] font-bold text-slate-400 uppercase tracking-[0.2em] mb-1">Total Payable</p>
                                            <p class="text-xs font-bold text-slate-500 tracking-tighter">
                                                <span class="text-[9px] opacity-60 mr-0.5">TK</span><?= number_format($alert['payable'], 2); ?>
                                            </p>
                                        </div>

                                        <!-- Primary Stat -->
                                        <div class="flex items-center gap-4 bg-slate-50/50 p-1.5 pl-4 pr-1.5 rounded-2xl border border-slate-100">
                                            <div class="text-left">
                                                <p class="text-[8px] font-black text-<?= $accent_color; ?>-600 uppercase tracking-widest mb-0.5">Due Now</p>
                                                <p class="text-lg font-black text-slate-900 leading-none tracking-tighter">
                                                    <span class="text-[11px] text-slate-400 font-bold mr-0.5">TK</span><?= number_format($alert['due'], 2); ?>
                                                </p>
                                            </div>
                                            
                                            <button onclick="openPayModal(<?= $alert['id']; ?>, '<?= addslashes($alert['title']); ?>', <?= $alert['due']; ?>)" 
                                                    class="h-10 px-4 bg-red-600 hover:bg-red-700 active:bg-red-800 text-white rounded-2xl text-[13px] font-black uppercase tracking-[0.2em] transition-all shadow-xl shadow-red-100/50 hover:shadow-red-200 active:scale-95 flex items-center justify-center gap-3 group/btn">
                                                <span>Settle</span>
                                                <i class="fas fa-chevron-right text-[10px] transition-transform group-hover/btn:translate-x-1"></i>
                                            </button>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Custom Style to Refine Reset Button Positioning -->
                    <style>
                        /* Target the primary Reset button and move it to the end */
                        .flex.flex-wrap.items-center.gap-2.md\:gap-3 {
                            display: flex !important;
                        }
                        .flex.flex-wrap.items-center.gap-2.md\:gap-3 a.bg-rose-50 {
                            order: 999 !important;
                        }
                        /* Target and hide the redundant Reset button (from reusable_list) */
                        .flex.flex-wrap.items-center.gap-2.md\:gap-3 a[title="Clear All Filters"] {
                            display: none !important;
                        }
                        .animate-pulse-slow {
                            animation: pulse-slow 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
                        }
                        @keyframes pulse-slow {
                            0%, 100% { opacity: 1; }
                            50% { opacity: 0.85; }
                        }
                    </style>
                    <?php include('../includes/reusable_list.php'); renderReusableList($list_config); ?>
                </div>
            </div>
        </div>
        <?php include('../includes/footer.php'); ?>
    </main>
</div>

<!-- Pay Modal -->
<div id="payModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
    <div class="bg-white rounded-3xl shadow-2xl w-11/12 md:w-96 p-8 scale-95 opacity-0 transition-all duration-300 transform">
        <h2 class="text-2xl font-black text-slate-800 mb-6">Add Payment</h2>
        <form id="paymentForm" method="POST" action="/pos/loan/save">
            <input type="hidden" name="save_payment_btn" value="1">
            <input type="hidden" id="pay_loan_id" name="loan_id" value="">
            
            <div class="mb-4">
                <label class="text-xs font-black text-slate-400 uppercase mb-2 block">Loan Title</label>
                <input type="text" id="pay_loan_title_display" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-slate-700 font-bold" readonly>
            </div>

            <div class="mb-4">
                <label class="text-xs font-black text-slate-400 uppercase mb-2 block">Current Due</label>
                <input type="text" id="pay_loan_due_display" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-rose-600 font-bold" readonly>
            </div>

            <div class="mb-4">
                <label class="text-xs font-black text-slate-400 uppercase mb-2 block">Payment Amount <span class="text-red-500">*</span></label>
                <input type="number" id="pay_amount" name="pay_amount" step="0.01" min="0" class="w-full border border-slate-200 rounded-xl px-4 py-3 focus:outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-100" required>
            </div>

            <div class="mb-6">
                <label class="text-xs font-black text-slate-400 uppercase mb-2 block">Note (Optional)</label>
                <textarea name="note" class="w-full border border-slate-200 rounded-xl px-4 py-3 focus:outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-100" rows="3"></textarea>
            </div>

            <div class="flex gap-3">
                <button type="button" onclick="closePayModal()" class="flex-1 px-4 py-3 bg-slate-100 text-slate-700 rounded-xl font-bold hover:bg-slate-200 transition-all">Cancel</button>
                <button type="submit" class="flex-1 px-4 py-3 bg-teal-600 text-white rounded-xl font-bold hover:bg-teal-700 transition-all">Submit Payment</button>
            </div>
        </form>
    </div>
</div>

<script>
function openPayModal(id, title, due) {
    var modal = document.getElementById('payModal');
    var modalContent = modal.querySelector('.bg-white');
    
    modal.classList.remove('hidden');
    document.getElementById('pay_loan_id').value = id;
    document.getElementById('pay_loan_title_display').value = title;
    document.getElementById('pay_loan_due_display').value = due.toFixed(2);
    document.getElementById('pay_amount').max = due;

    setTimeout(function() {
        modalContent.classList.remove('scale-95', 'opacity-0');
        modalContent.classList.add('scale-100', 'opacity-100');
    }, 10);
}

function closePayModal() {
    var modal = document.getElementById('payModal');
    var modalContent = modal.querySelector('.bg-white');
    
    modalContent.classList.remove('scale-100', 'opacity-100');
    modalContent.classList.add('scale-95', 'opacity-0');
    
    setTimeout(function() {
        modal.classList.add('hidden');
        document.getElementById('pay_amount').value = '';
        document.querySelector('#paymentForm textarea[name="note"]').value = '';
    }, 300);
}

document.getElementById('payModal')?.addEventListener('click', function(e) {
    if(e.target === this) closePayModal();
});
</script>
