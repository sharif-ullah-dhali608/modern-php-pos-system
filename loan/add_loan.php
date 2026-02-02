<?php
session_start();
include('../config/dbcon.php');

// Security Check
if(!isset($_SESSION['auth'])){
    header("Location: /pos/login");
    exit(0);
}

// URL ID Parsing
if(!isset($_GET['id'])){
    $uri_segments = explode('/', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
    $last_segment = end($uri_segments);
    if(is_numeric($last_segment)){
        $_GET['id'] = $last_segment;
    }
}

// Default Mode Settings
$mode = "create";
$btn_name = "save_loan_btn";
$btn_text = "Save Loan";
$page_title = "Take Loan";

// Initialize default values
$d = [
    'loan_id' => '',
    'ref_no' => '',
    'loan_from_id' => '',
    'loan_from' => '',
    'title' => '',
    'amount' => '',
    'interest' => '0',
    'payable' => '',
    'details' => '',
    'attachment' => '',
    'status' => '1',
    'created_at' => date('Y-m-d'),
    'deadline_date' => '',
    'deadline_time' => ''
];

// Fetch Loan Sources for dropdown
$loan_sources_query = "SELECT id, name FROM loan_sources WHERE status='1' ORDER BY name ASC";
$loan_sources_result = mysqli_query($conn, $loan_sources_query);

// Edit Mode Logic
if(isset($_GET['id'])) {
    $mode = "edit";
    $btn_name = "update_loan_btn";
    $btn_text = "Update Loan";
    $page_title = "Edit Loan";

    $id = mysqli_real_escape_string($conn, $_GET['id']);
    $query = "SELECT * FROM loans WHERE loan_id='$id' LIMIT 1";
    $result = mysqli_query($conn, $query);

    if(mysqli_num_rows($result) > 0){
        $d = mysqli_fetch_array($result);
        $d['created_at'] = date('Y-m-d', strtotime($d['created_at']));
    } else {
        $_SESSION['message'] = "Loan not found";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/loan/list");
        exit(0);
    }
}

include('../includes/header.php');
?>

<!-- Link Loan Module Assets -->
<link rel="stylesheet" href="/pos/assets/css/loanCss/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/css/intlTelInput.css">
<style>
    /* Custom Calendar Styling (borrowed from reusable_list.php) */
    .calendar-dropdown {
        position: absolute;
        top: 100%;
        left: 0;
        z-index: 1000;
        width: 320px;
        background: white;
        border: 1px solid #f1f5f9;
        border-radius: 1.5rem;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        padding: 1.25rem;
        margin-top: 0.75rem;
        animation: slideDown 0.3s ease-out;
    }
    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .calendar-day-btn {
        width: 2.25rem;
        height: 2.25rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8125rem;
        font-weight: 600;
        position: relative;
        z-index: 10;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .calendar-day-wrapper {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        position: relative;
        height: 2.5rem;
    }
    /* Input Focus Styling to match image */
    #transaction_range:focus {
        border-color: #0d9488 !important;
        box-shadow: 0 0 0 4px rgba(13, 148, 136, 0.1) !important;
    }
</style>

<div class="app-wrapper">
    <?php include('../includes/sidebar.php'); ?>
    
    <main id="main-content" class="flex-1 lg:ml-64 main-content flex flex-col h-screen min-w-0 transition-all duration-300 bg-slate-50">        
        <div class="navbar-fixed-top">
            <?php include('../includes/navbar.php'); ?>
        </div>
        
        <div class="content-scroll-area custom-scroll flex-1 overflow-y-auto">
            <div class="flex flex-col min-h-full">
                <div class="p-4 md:p-6 max-w-7xl mx-auto w-full flex-1">
                
                <?php if(isset($_SESSION['message'])): 
                    $msgType = isset($_SESSION['msg_type']) ? $_SESSION['msg_type'] : "error";
                    $swalIcon = ($msgType == "success") ? "success" : "error";
                    $bgColor = ($msgType == "success") ? "#059669" : "#1e293b";
                ?>
                <div id="session-toast-data" 
                     data-title="<?= htmlspecialchars($_SESSION['message']); ?>" 
                     data-icon="<?= $swalIcon; ?>" 
                     data-bg="<?= $bgColor; ?>"></div>
                <?php unset($_SESSION['message']); unset($_SESSION['msg_type']); ?>
                <?php endif; ?>

                <div class="mb-6 slide-in flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <a href="/pos/loan/list" class="w-10 h-10 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-600 hover:text-teal-600 hover:border-teal-200 shadow-sm transition-all">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <div>
                            <h1 class="text-2xl md:text-3xl font-bold text-slate-800"><?= $page_title; ?></h1>
                            <p class="text-slate-500 text-sm mt-1">Fill in the details to <?= $mode == 'create' ? 'create a new' : 'update'; ?> loan.</p>
                        </div>
                    </div>
                </div>

                <form action="/pos/loan/save" method="POST" enctype="multipart/form-data" id="loanForm" novalidate>
                    <?php if($mode == 'edit'): ?>
                        <input type="hidden" name="loan_id" value="<?= $d['loan_id']; ?>">
                        <input type="hidden" name="old_attachment" value="<?= htmlspecialchars($d['attachment']); ?>">
                    <?php endif; ?>

                    <div class="grid grid-cols-1 gap-6">
                        
                        <div class="space-y-6 slide-in delay-100">
                            <!-- Basic Information Section -->
                            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 relative overflow-hidden">
                                <div class="absolute top-0 left-0 w-1 h-full bg-teal-500"></div>
                                <h3 class="text-lg font-bold text-slate-800 mb-5 flex items-center gap-2">
                                    <i class="fas fa-hand-holding-usd text-teal-600"></i> Loan Information
                                </h3>
                                
                                <div class="grid grid-cols-1 gap-5">
                                    <!-- Unified 3-Column Modern Grid -->
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                                        <!-- Row 1: Transaction Date Range -->
                                        <div class="form-group col-span-1 md:col-span-3">
                                            <label class="block text-sm font-bold text-slate-700 mb-2">Transaction Date <span class="text-rose-500">*</span></label>
                                            <div class="relative" id="transaction-range-container">
                                                <input type="text" name="transaction_range" id="transaction_range" value="<?= date('Y-m-d', strtotime($d['created_at'])) . (isset($d['deadline_date']) && !empty($d['deadline_date']) ? ' ---- To ---- ' . date('Y-m-d', strtotime($d['deadline_date'])) : ''); ?>" class="w-full h-[54px] bg-slate-50 border border-slate-200 rounded-2xl px-5 py-3 focus:ring-2 focus:ring-teal-500 focus:bg-white outline-none transition-all font-semibold text-slate-700 cursor-pointer" placeholder="Select Start & Deadline Date" readonly onclick="toggleCustomCalendar(event)">
                                                <div class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                                                    <i class="fas fa-calendar-alt"></i>
                                                </div>

                                                <!-- Custom Calendar Picker Dropdown -->
                                                <div id="custom-calendar-dropdown" class="calendar-dropdown hidden">
                                                    <!-- Selected Range Display -->
                                                    <div class="mb-4 p-3 bg-slate-50 rounded-xl text-center">
                                                        <div class="text-sm font-bold text-slate-700" id="calendar-selected-range">
                                                            Select date range
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Calendar Header -->
                                                    <div class="flex items-center justify-between mb-4">
                                                        <button type="button" onclick="event.stopPropagation(); changeCalendarMonth(-1)" class="w-8 h-8 flex items-center justify-center hover:bg-slate-100 rounded-lg transition-colors">
                                                            <i class="fas fa-chevron-left text-slate-600 text-xs"></i>
                                                        </button>
                                                        <div class="relative">
                                                            <button type="button" onclick="event.stopPropagation(); toggleYearMonthSelector()" class="font-black text-slate-800 uppercase text-xs tracking-widest px-3 py-1.5 hover:bg-slate-50 rounded-lg transition-all" id="calendar-month-display"></button>
                                                            
                                                            <!-- Year/Month Selector Dropdown -->
                                                            <div id="year-month-selector" class="hidden absolute top-full left-1/2 transform -translate-x-1/2 mt-2 bg-white border border-slate-100 rounded-xl shadow-2xl z-[1100] p-4 w-64">
                                                                <div class="mb-3 text-center">
                                                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Jump to Date</span>
                                                                </div>
                                                                <div class="grid grid-cols-2 gap-3 mb-4">
                                                                    <div>
                                                                        <label class="text-[9px] font-black text-slate-500 uppercase mb-1 block">Year</label>
                                                                        <select id="year-select" class="w-full px-3 py-2 bg-slate-50 border border-slate-100 rounded-lg text-xs font-bold focus:ring-2 focus:ring-teal-500 outline-none">
                                                                        </select>
                                                                    </div>
                                                                    <div>
                                                                        <label class="text-[9px] font-black text-slate-500 uppercase mb-1 block">Month</label>
                                                                        <select id="month-select" class="w-full px-3 py-2 bg-slate-50 border border-slate-100 rounded-lg text-xs font-bold focus:ring-2 focus:ring-teal-500 outline-none">
                                                                            <option value="0">January</option>
                                                                            <option value="1">February</option>
                                                                            <option value="2">March</option>
                                                                            <option value="3">April</option>
                                                                            <option value="4">May</option>
                                                                            <option value="5">June</option>
                                                                            <option value="6">July</option>
                                                                            <option value="7">August</option>
                                                                            <option value="8">September</option>
                                                                            <option value="9">October</option>
                                                                            <option value="10">November</option>
                                                                            <option value="11">December</option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                                <button type="button" onclick="event.stopPropagation(); applyYearMonthSelection()" class="w-full py-2.5 bg-teal-600 text-white rounded-lg text-[10px] font-black uppercase tracking-widest hover:bg-teal-700 shadow-lg shadow-teal-500/20 transition-all">
                                                                    Apply View
                                                                </button>
                                                            </div>
                                                        </div>
                                                        <button type="button" onclick="event.stopPropagation(); changeCalendarMonth(1)" class="w-8 h-8 flex items-center justify-center hover:bg-slate-100 rounded-lg transition-colors">
                                                            <i class="fas fa-chevron-right text-slate-600 text-xs"></i>
                                                        </button>
                                                    </div>
                                                    
                                                    <!-- Calendar Grid -->
                                                    <div class="grid grid-cols-7 mb-2 text-center">
                                                        <div class="text-[10px] font-black text-slate-400 py-2">S</div>
                                                        <div class="text-[10px] font-black text-slate-400 py-2">M</div>
                                                        <div class="text-[10px] font-black text-slate-400 py-2">T</div>
                                                        <div class="text-[10px] font-black text-slate-400 py-2">W</div>
                                                        <div class="text-[10px] font-black text-slate-400 py-2">T</div>
                                                        <div class="text-[10px] font-black text-slate-400 py-2">F</div>
                                                        <div class="text-[10px] font-black text-slate-400 py-2">S</div>
                                                    </div>
                                                    <div id="calendar-grid" class="grid grid-cols-7 gap-y-1"></div>
                                                </div>
                                            </div>
                                            <span class="error-msg text-xs text-rose-500 mt-1 hidden">Transaction period is required.</span>
                                        </div>

                                        <!-- Row 2: Identifiers -->
                                        <div class="form-group">
                                            <label class="block text-sm font-bold text-slate-700 mb-2">Loan Source <span class="text-rose-500">*</span></label>
                                            <div class="flex items-stretch">
                                                <div class="flex-1">
                                                    <select name="loan_from_id" id="loan_from_id" class="w-full h-[54px] bg-slate-50 border border-slate-200 fused-group-input px-4 py-3 text-slate-700 font-semibold outline-none appearance-none cursor-pointer focus:border-teal-500 transition-all">
                                                        <option value="">Search Loan Source...</option>
                                                        <?php if($loan_sources_result): ?>
                                                            <?php mysqli_data_seek($loan_sources_result, 0); ?>
                                                            <?php while($source = mysqli_fetch_assoc($loan_sources_result)): ?>
                                                                <option value="<?= $source['id']; ?>" <?= ($d['loan_from_id'] == $source['id']) ? 'selected' : '' ?>><?= htmlspecialchars($source['name']); ?></option>
                                                            <?php endwhile; ?>
                                                        <?php endif; ?>
                                                    </select>
                                                </div>
                                                <button type="button" onclick="openSourceModal()" class="w-[54px] h-[54px] px-5 flex items-center justify-center fused-group-btn bg-teal-800 text-white hover:bg-teal-700 transition-all duration-200 shadow-sm" title="Add New Source">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </div>
                                            <span class="error-msg text-xs text-rose-500 mt-1 hidden">Please select a loan source</span>
                                        </div>

                                        <div class="form-group">
                                            <label class="block text-sm font-bold text-slate-700 mb-2">Reference No <span class="text-rose-500">*</span></label>
                                            <div class="flex items-stretch">
                                                <input type="text" name="ref_no" id="ref_no" value="<?= htmlspecialchars($d['ref_no']); ?>" class="w-full h-[54px] bg-slate-50 border border-slate-200 fused-group-input px-4 py-3 focus:ring-2 focus:ring-teal-500 focus:bg-white outline-none transition-all font-semibold text-slate-700" placeholder="e.g. LN-001">
                                                <button type="button" onclick="generateRef()" class="h-[54px] bg-teal-800 hover:bg-teal-700 text-white px-5 fused-group-btn font-medium transition-colors" title="Generate Reference No">
                                                    <i class="fas fa-random"></i>
                                                </button>
                                            </div>
                                            <span id="ref-error" class="error-msg text-xs text-rose-500 mt-1 hidden"></span>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if(!document.getElementById('ref_no').value) {
            generateRef();
        }
    });

    async function generateRef() {
        try {
            const response = await fetch('/pos/loan/save_loan.php?action=generate_ref');
            const data = await response.json();
            if(data.status === 200) {
                document.getElementById('ref_no').value = data.ref_no;
            }
        } catch (error) {
            console.error('Error generating ref:', error);
        }
    }
</script>

                                            <span class="error-msg text-xs text-rose-500 mt-1 hidden">Reference No is required.</span>
                                        </div>

                                        <div class="form-group">
                                            <label class="block text-sm font-bold text-slate-700 mb-2">Loan Title <span class="text-rose-500">*</span></label>
                                            <div class="relative">
                                                <input type="text" name="title" value="<?= htmlspecialchars($d['title']); ?>" class="w-full h-[54px] bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 focus:ring-2 focus:ring-teal-500 focus:bg-white outline-none transition-all font-semibold text-slate-700" placeholder="e.g. Business Loan" required>
                                            </div>
                                            <span class="error-msg text-xs text-rose-500 mt-1 hidden">Title is required.</span>
                                        </div>

                                        <!-- Row 3: Financials -->
                                        <div class="form-group">
                                            <label class="block text-sm font-bold text-slate-700 mb-2">Principal Amount <span class="text-rose-500">*</span></label>
                                            <div class="relative group">
                                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 group-focus-within:text-teal-500 transition-colors">
                                                    <span class="font-bold text-xs">TK</span>
                                                </div>
                                                <input type="number" step="0.01" name="amount" id="amount" value="<?= $d['amount']; ?>" class="w-full h-[54px] bg-slate-50 border border-slate-200 rounded-2xl pl-10 pr-4 py-3 focus:ring-2 focus:ring-teal-500 focus:bg-white outline-none font-extrabold text-teal-700 transition-all text-lg" placeholder="0.00" min="0" required>
                                            </div>
                                            <span class="error-msg text-xs text-rose-500 mt-1 hidden">Amount is required.</span>
                                        </div>

                                        <div class="form-group">
                                            <label class="block text-sm font-bold text-slate-700 mb-2">Interest Rate (%) <span class="text-rose-500">*</span></label>
                                            <div class="relative group">
                                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 group-focus-within:text-teal-500 transition-colors">
                                                    <i class="fas fa-percent text-xs"></i>
                                                </div>
                                                <input type="number" step="0.01" name="interest" id="interest" value="<?= $d['interest']; ?>" class="w-full h-[54px] bg-slate-50 border border-slate-200 rounded-2xl pl-10 pr-4 py-3 focus:ring-2 focus:ring-teal-500 focus:bg-white outline-none font-extrabold text-teal-700 transition-all text-lg" placeholder="0.00" min="0" max="100">
                                            </div>
                                            <span class="error-msg text-xs text-rose-500 mt-1 hidden">Interest is required.</span>
                                        </div>

                                        <div class="form-group">
                                            <label class="block text-sm font-bold text-slate-700 mb-2">Total Payable Amount</label>
                                            <div class="relative flex items-center bg-teal-50/50 border border-teal-100 rounded-2xl p-1.5 overflow-hidden h-[54px]">
                                                <div class="w-9 h-9 rounded-xl bg-teal-500 text-white flex items-center justify-center shadow-lg shadow-teal-500/20 flex-shrink-0">
                                                    <i class="fas fa-wallet text-xs"></i>
                                                </div>
                                                <div class="ml-3 flex-1 overflow-hidden">
                                                    <input type="number" step="0.01" name="payable" id="payable" value="<?= $d['payable']; ?>" 
                                                        class="bg-transparent border-none px-5 font-extrabold text-lg text-teal-900 outline-none w-full cursor-default" 
                                                        placeholder="0.00" readonly>
                                                </div>
                                                <div class="pr-2 flex-shrink-0">
                                                    <span class="bg-teal-100 text-teal-700 text-[8px] font-black px-1.5 py-0.5 rounded uppercase">Verified</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- End Unified Grid -->

                                    <!-- Row 4: Attachment & Status side-by-side -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8 items-stretch">
                                        <!-- Attachment Card -->
                                        <div class="form-group flex flex-col h-full">
                                            <label class="block text-sm font-bold text-slate-700 mb-2">Evidence Attachment <span class="text-rose-500">*</span></label>
                                            <div class="relative flex-1">
                                                <input type="file" name="attachment" id="attachment" class="hidden" accept="image/*,application/pdf">
                                                <div id="attachment-preview-container" onclick="document.getElementById('attachment').click()" 
                                                    class="border-2 border-dashed border-slate-200 rounded-2xl p-6 bg-slate-50/50 hover:bg-white hover:border-teal-500 hover:shadow-xl hover:shadow-teal-500/5 transition-all duration-500 cursor-pointer text-center group relative overflow-hidden flex flex-col items-center justify-center h-[110px]">
                                                    
                                                    <!-- Ambient Glow Decor -->
                                                    <div class="absolute -bottom-10 -right-10 w-32 h-32 bg-teal-50 rounded-full blur-3xl opacity-0 group-hover:opacity-100 transition-opacity"></div>
                                                    
                                                    <!-- Remove Button -->
                                                    <?php 
                                                        $baseDir = dirname(__DIR__);
                                                        $hasInitialFile = !empty($d['attachment']) && file_exists($baseDir . '/' . $d['attachment']);
                                                    ?>
                                                    <button type="button" id="remove-file-btn" onclick="event.stopPropagation(); removeFile();" 
                                                        class="absolute top-2 right-2 bg-white/90 backdrop-blur-sm text-rose-500 w-8 h-8 rounded-lg flex items-center justify-center hover:bg-rose-500 hover:text-white transition-all shadow-lg z-20 <?= $hasInitialFile ? '' : 'hidden' ?>">
                                                        <i class="fas fa-trash-alt text-xs"></i>
                                                    </button>

                                                    <div id="preview-inner" class="relative z-10 w-full mb-0">
                                                        <?php if($hasInitialFile): ?>
                                                            <?php 
                                                                $ext = strtolower(pathinfo($d['attachment'], PATHINFO_EXTENSION));
                                                                $isImg = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                                            ?>
                                                            <div class="flex flex-col items-center justify-center">
                                                                <?php if($isImg): ?>
                                                                    <img src="/pos/<?= $d['attachment'] ?>" class="h-16 w-32 rounded-lg object-cover border-2 border-white shadow-md">
                                                                <?php else: ?>
                                                                    <div class="w-12 h-12 bg-rose-50 rounded-lg flex items-center justify-center shadow-inner">
                                                                        <i class="fas fa-file-pdf text-rose-500 text-2xl"></i>
                                                                    </div>
                                                                <?php endif; ?>
                                                                <div class="mt-2 text-center">
                                                                    <p class="text-slate-700 font-bold text-[10px] truncate max-w-[180px]"><?= basename($d['attachment']) ?></p>
                                                                    <p class="text-teal-600 font-extrabold text-[8px] uppercase tracking-tighter mt-0.5 bg-teal-50 px-2 py-0.5 rounded-full inline-block">File Ready</p>
                                                                </div>
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="flex flex-col items-center justify-center">
                                                                <div class="w-10 h-10 bg-white rounded-xl shadow-sm border border-slate-100 flex items-center justify-center mb-1 group-hover:bg-teal-500 group-hover:text-white transition-all duration-500">
                                                                    <i class="fas fa-cloud-upload-alt text-lg"></i>
                                                                </div>
                                                                <h5 class="text-slate-800 font-bold text-xs mb-0">Click to upload evidence</h5>
                                                                <p class="text-slate-400 text-[8px] font-semibold uppercase tracking-widest mt-0.5">
                                                                    JPG • PNG • PDF <span class="text-slate-200">(MAX 5MB)</span>
                                                                </p>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <span class="error-msg text-xs text-rose-500 mt-1 hidden flex items-center gap-1">
                                                    <i class="fas fa-exclamation-triangle mt-0.5"></i> Evidence is required.
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Status Card component -->
                                        <div class="form-group flex flex-col h-full">
                                            <label class="block text-sm font-bold text-slate-700 mb-2">Record Status</label>
                                            <div class="flex-1">
                                                <?php 
                                                    $current_status = $d['status'];  
                                                    $status_title = "Loan Record";      
                                                    $card_id = "loan-status-card-new";
                                                    $label_id = "loan-status-label-new";
                                                    $input_id = "loan_status_input";
                                                    $toggle_id = "loan_status_toggle";
                                                    
                                                    echo '<div class="h-[110px]">';
                                                    include('../includes/status_card.php'); 
                                                    echo '</div>';
                                                ?>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Row 5: Details (Full Width) -->
                                    <div class="mb-8">
                                        <div class="form-group">
                                            <label class="block text-sm font-bold text-slate-700 mb-2">Details</label>
                                            <textarea name="details" rows="3" class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 focus:ring-2 focus:ring-teal-500 focus:bg-white outline-none transition-all font-semibold" placeholder="Additional notes..."><?= htmlspecialchars($d['details']); ?></textarea>
                                        </div>
                                    </div>
                                </div> <!-- End main grid -->
                            </div> <!-- End Loan Information column -->
                        </div> <!-- End outer grid -->
                    </div> <!-- End glass-card -->

                    <!-- Form Actions -->
                    <div class="mt-8 flex items-center justify-center gap-3">
                        <button type="submit" name="<?= $btn_name; ?>" class="bg-teal-600 hover:bg-teal-700 text-white px-6 py-3 rounded-lg font-semibold transition-all shadow-md hover:shadow-lg flex items-center gap-2">
                            <i class="fas fa-save"></i> <?= $btn_text; ?>
                        </button>
                        <button type="button" onclick="resetForm()" class="bg-rose-600 hover:bg-rose-700 text-white px-6 py-3 rounded-lg font-semibold transition-all shadow-md hover:shadow-lg flex items-center gap-2">
                            <i class="fas fa-undo"></i> Reset
                        </button>
                    </div>
                </form>
                </div>
                <?php include('../includes/footer.php'); ?>
            </div>
        </div>
    </main>
</div>

<!-- Scripts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/intlTelInput.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/utils.js"></script>
<script src="/pos/assets/js/loanJs/script.js"></script>

<!-- Clean Professional Add Loan Source Modal -->
<div id="loanSourceModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/60 backdrop-blur-md p-4 transition-all duration-300">
    <div class="bg-white rounded-2xl shadow-2xl w-full overflow-hidden scale-95 opacity-0 transition-all duration-300 transform border border-slate-200" style="width: 100%; max-width: 480px !important;">
        <!-- Modal Header -->
        <div class="px-8 pt-8 pb-4 flex items-center justify-between border-b border-slate-100">
            <div>
                <h2 class="text-xl font-bold text-slate-800">New Loan Source</h2>
                <p class="text-slate-500 text-xs font-medium">Quickly add a new source to your records</p>
            </div>
            <button onclick="closeSourceModal()" class="w-8 h-8 flex items-center justify-center rounded-full text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-all">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="sourceAddForm" class="p-8 space-y-5">
            <div class="grid grid-cols-1 gap-5">
                <!-- Source Name -->
                <div class="form-group">
                    <label class="text-sm font-bold text-slate-700 mb-2 block">Source Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" id="modal_source_name" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-3 text-slate-700 font-semibold focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 outline-none transition-all placeholder:text-slate-300" placeholder="e.g. Dutch Bangla Bank">
                    <span class="error-msg text-[10px] text-rose-500 font-bold mt-1 hidden">Source name is required</span>
                </div>

                <!-- Source Type -->
                <div class="form-group">
                    <label class="text-sm font-bold text-slate-700 mb-2 block">Source Type <span class="text-rose-500">*</span></label>
                    <select name="type" id="modal_source_type" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-3 text-slate-700 font-semibold focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 outline-none transition-all cursor-pointer">
                        <option value="">Select Type</option>
                        <option value="Bank">Bank</option>
                        <option value="Person">Person</option>
                        <option value="Others">Others</option>
                    </select>
                    <span class="error-msg text-[10px] text-rose-500 font-bold mt-1 hidden">Please select a source type</span>
                </div>

                <!-- Phone Number -->
                <div class="form-group col-span-1">
                    <label class="text-sm font-bold text-slate-700 mb-2 block">Phone Number <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <input type="hidden" name="phone" id="source_full_phone">
                        <input type="tel" id="source_phone" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-3 text-slate-700 font-semibold focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 outline-none transition-all placeholder:text-slate-300" placeholder="017...">
                        <div class="flex justify-between items-center mt-1">
                            <span id="source-valid-msg" class="hidden text-[10px] text-green-600 font-bold"><i class="fas fa-check-circle"></i> Valid Number</span>
                            <span id="source-error-msg" class="hidden text-[10px] text-rose-600 font-bold"></span>
                        </div>
                    </div>
                </div>

                <!-- Address -->
                <div class="form-group">
                    <label class="text-sm font-bold text-slate-700 mb-2 block">Address <span class="text-rose-500">*</span></label>
                    <textarea name="address" id="modal_source_address" rows="1" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-3 text-slate-700 font-medium focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 outline-none transition-all placeholder:text-slate-300 resize-none" placeholder="Source location or address..."></textarea>
                    <span class="error-msg text-[10px] text-rose-500 font-bold mt-1 hidden">Address is required</span>
                </div>

                <!-- Extra Details -->
                <div class="form-group">
                    <label class="text-sm font-bold text-slate-700 mb-2 block">Details</label>
                    <textarea name="details" rows="2" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-3 text-slate-700 font-medium focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 outline-none transition-all placeholder:text-slate-300 resize-none" placeholder="Account number, address, etc."></textarea>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="closeSourceModal()" class="flex-1 px-4 py-3 bg-slate-100 text-slate-600 rounded-lg font-bold hover:bg-slate-200 transition-all">Cancel</button>
                <button type="submit" class="flex-[2] px-4 py-3 bg-teal-600 text-white rounded-lg font-bold hover:bg-teal-700 transition-all shadow-lg shadow-teal-500/20">Save Source</button>
            </div>
        </form>
    </div>
</div>
