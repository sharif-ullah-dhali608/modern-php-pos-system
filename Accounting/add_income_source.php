<?php
session_start();
include('../config/dbcon.php');

// Security Check
if(!isset($_SESSION['auth'])){
    header("Location: /pos/login");
    exit(0);
}

// Initialize Variables
$mode = "add";
$source_id = "";
$source_name = "";
$slug = "";
$parent_id = 0;
$source_details = "";
$status = 1; 
$sort_order = 0;

$page_title = "Add New Income Source";
$btn_text = "Save";
$btn_name = "save_income_source";

// Check for Edit Mode
if(isset($_GET['id'])) {
    $mode = "edit";
    $source_id = mysqli_real_escape_string($conn, $_GET['id']);
    $page_title = "Edit Income Source";
    $btn_text = "Update";
    
    // Fetch Details
    $query = "SELECT * FROM income_sources WHERE source_id='$source_id' LIMIT 1";
    $query_run = mysqli_query($conn, $query);
    
    if(mysqli_num_rows($query_run) > 0) {
        $row = mysqli_fetch_assoc($query_run);
        $source_name = $row['source_name'];
        $slug = $row['slug'];
        $parent_id = $row['parent_id'];
        $source_details = $row['source_details'];
        $status = $row['status'];
        $sort_order = $row['sort_order'];
    } else {
        $_SESSION['status'] = "Income Source not found";
        $_SESSION['status_code'] = "error";
        header("Location: /pos/accounting/income-source/list");
        exit(0);
    }
}

// Handle Form Submission
if(isset($_POST['save_income_source'])) {
    $source_name = mysqli_real_escape_string($conn, $_POST['source_name']);
    $slug = mysqli_real_escape_string($conn, $_POST['slug']);
    if(empty($slug)) {
        // Auto-generate slug from name if empty
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $source_name)));
    }
    $parent_id = mysqli_real_escape_string($conn, $_POST['parent_id']);
    $source_details = mysqli_real_escape_string($conn, $_POST['source_details']);
    $status = isset($_POST['status']) ? mysqli_real_escape_string($conn, $_POST['status']) : 0;
    $sort_order = mysqli_real_escape_string($conn, $_POST['sort_order']);
    
    if($mode == "add") {
        $query = "INSERT INTO income_sources (source_name, slug, parent_id, source_details, status, sort_order) 
                  VALUES ('$source_name', '$slug', '$parent_id', '$source_details', '$status', '$sort_order')";
        $query_run = mysqli_query($conn, $query);
        
        if($query_run) {
            $_SESSION['status'] = "Income Source Added Successfully";
            $_SESSION['status_code'] = "success";
            header("Location: /pos/accounting/income-source/list");
            exit(0);
        } else {
            $_SESSION['status'] = "Something Went Wrong: " . mysqli_error($conn);
            $_SESSION['status_code'] = "error";
        }
    } else {
        $update_query = "UPDATE income_sources SET 
                        source_name='$source_name', 
                        slug='$slug', 
                        parent_id='$parent_id', 
                        source_details='$source_details', 
                        status='$status', 
                        sort_order='$sort_order' 
                        WHERE source_id='$source_id'";
        $update_run = mysqli_query($conn, $update_query);
        
        if($update_run) {
            $_SESSION['status'] = "Income Source Updated Successfully";
            $_SESSION['status_code'] = "success";
            header("Location: /pos/accounting/income-source/list");
            exit(0);
        } else {
            $_SESSION['status'] = "Update Failed: " . mysqli_error($conn);
            $_SESSION['status_code'] = "error";
        }
    }
}

// Fetch Parent Sources for Dropdown
$parents = [];
$p_q = "SELECT source_id, source_name FROM income_sources WHERE parent_id=0 AND status=1";
if($mode == 'edit') {
    $p_q .= " AND source_id != '$source_id'"; // Prevent self-parenting
}
$p_run = mysqli_query($conn, $p_q);
if($p_run) {
    while($p = mysqli_fetch_assoc($p_run)) {
        $parents[] = $p;
    }
}

// Find selected parent name for edit mode
$selected_parent_name = "";
if($parent_id > 0 && !empty($parents)) {
    foreach($parents as $p) {
        if($p['source_id'] == $parent_id) {
            $selected_parent_name = $p['source_name'];
            break;
        }
    }
}

include('../includes/header.php');
?>

<div class="app-wrapper">
    <?php include('../includes/sidebar.php'); ?>
    
    <main id="main-content" class="flex-1 lg:ml-64 flex flex-col h-screen min-w-0 transition-all duration-300 bg-slate-50">
        <div class="navbar-fixed-top">
            <?php include('../includes/navbar.php'); ?>
        </div>
        
        <div class="content-scroll-area custom-scroll h-full overflow-y-auto">
            <div class="p-4 md:p-6 max-w-[1600px] mx-auto">
                <!-- Header -->
                <div class="mb-6 slide-in flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <a href="/pos/accounting/income-source/list" class="w-10 h-10 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-600 hover:text-teal-600 hover:border-teal-200 shadow-sm transition-all">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <div>
                            <h1 class="text-2xl md:text-3xl font-bold text-slate-800"><?= $page_title; ?></h1>
                            <p class="text-slate-500 text-sm mt-1">Manage income categories</p>
                        </div>
                    </div>
                </div>

                <form action="" method="POST" class="slide-in delay-100" id="incomeForm" novalidate>
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        
                        <!-- Column 1: Main Info (Spans 2 cols usually, but user layout might differ. Trying 2 cols here) -->
                        <div class="lg:col-span-2 space-y-6">
                            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 relative h-fit">
                                <div class="absolute top-0 left-0 w-1 h-full bg-teal-500"></div>
                                <h3 class="text-lg font-bold text-slate-800 mb-5 flex items-center gap-2">
                                    <i class="fas fa-info-circle text-teal-600"></i> Basic Information
                                </h3>
                                
                                <div class="space-y-4">
                                    <!-- Source Name -->
                                    <div>
                                        <label class="block text-sm font-bold text-slate-700 uppercase tracking-wide mb-2">
                                            Source Name <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" name="source_name" id="source_name" value="<?= htmlspecialchars($source_name); ?>" required class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-teal-500 transition-all">
                                        <span class="error-msg text-xs text-red-500 mt-1 hidden">Source Name is required.</span>
                                    </div>

                                    <!-- Slug -->
                                    <div>
                                        <label class="block text-sm font-bold text-slate-700 uppercase tracking-wide mb-2">Source Slug</label>
                                        <input type="text" name="slug" value="<?= htmlspecialchars($slug); ?>" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-teal-500 transition-all">
                                        <p class="text-xs text-slate-400 mt-1">Leave empty to auto-generate from name.</p>
                                    </div>

                                    <!-- Parent (Custom Searchable Dropdown) -->
                                    <div class="relative group">
                                        <label class="block text-sm font-bold text-slate-700 uppercase tracking-wide mb-2">Parent</label>
                                        <input type="hidden" name="parent_id" id="parent_id" value="<?= $parent_id; ?>">
                                        <div class="relative">
                                            <input type="text" id="parent_search" 
                                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-teal-500 transition-all font-bold text-slate-700 placeholder:font-normal"
                                                   placeholder="--- Please Select ---"
                                                   autocomplete="off"
                                                   value="<?= htmlspecialchars($selected_parent_name); ?>">
                                            
                                            <div class="absolute right-4 top-3 text-slate-400 pointer-events-none transition-transform duration-200" id="parent_chevron">
                                                <i class="fas fa-chevron-down text-xs"></i>
                                            </div>
                                        </div>

                                        <!-- Results Dropdown -->
                                        <div id="parent_dropdown" class="absolute left-0 right-0 mt-2 bg-white border border-slate-200 rounded-xl shadow-xl z-50 hidden max-h-60 overflow-y-auto custom-scroll">
                                            <div id="parent_results" class="p-1">
                                                <!-- Populated by JS -->
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Details -->
                                    <div>
                                        <label class="block text-sm font-bold text-slate-700 uppercase tracking-wide mb-2">Source Details</label>
                                        <textarea name="source_details" rows="3" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-teal-500 transition-all"><?= htmlspecialchars($source_details); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Column 2: Status & Settings -->
                        <div class="space-y-6">
                            <!-- Status Card -->
                            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-1">
                                <?php
                                    $current_status = $status;
                                    $status_title = 'Status';
                                    $card_id = 'inc-status-card';
                                    $label_id = 'inc-status-label';
                                    $input_id = 'inc-status-input';
                                    $toggle_id = 'inc-status-toggle';
                                    include('../includes/status_card.php');
                                ?>
                            </div>

                            <!-- Configuration -->
                            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 relative">
                                <div class="absolute top-0 left-0 w-1 h-full bg-orange-500"></div>
                                <h3 class="text-lg font-bold text-slate-800 mb-5 flex items-center gap-2">
                                    <i class="fas fa-cog text-orange-600"></i> Configuration
                                </h3>
                                
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 uppercase tracking-wide mb-2">
                                        Order
                                    </label>
                                    <input type="number" name="sort_order" value="<?= htmlspecialchars($sort_order); ?>" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-teal-500 transition-all">
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="pt-4">
                                <button type="submit" name="save_income_source" class="w-full py-4 bg-teal-600 text-white font-bold rounded-xl hover:bg-teal-700 transition-all shadow-lg shadow-teal-500/30 flex items-center justify-center text-lg mb-3">
                                    <i class="fas fa-save mr-2"></i> <?= $btn_text; ?>
                                </button>
                                <button type="reset" class="w-full py-3 bg-red-500 border border-red-500 text-white font-bold rounded-xl hover:bg-red-600 transition-all flex items-center justify-center mb-3">
                                    <i class="fas fa-undo mr-2"></i> Reset
                                </button>
                            </div>
                        </div>

                    </div>
                </form>
            </div>
            
            <?php include('../includes/footer.php'); ?>
        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Status Toggle
    if(typeof initStatusToggle === 'function') {
        initStatusToggle('inc-status-card', 'inc-status-toggle', 'inc-status-input', 'inc-status-label');
    }

    // Form Validation (Simple version tailored for this form)
    const form = document.getElementById('incomeForm');
    const requiredIds = ['source_name'];

    form.addEventListener('submit', function(e) {
        let isValid = true;
        let firstError = null;

        // Reset errors
        document.querySelectorAll('.error-msg').forEach(el => el.classList.add('hidden'));

        requiredIds.forEach(id => {
            const input = document.getElementById(id);
            if(!input.value.trim()) {
                const errorSpan = input.parentNode.querySelector('.error-msg');
                if(errorSpan) {
                    errorSpan.classList.remove('hidden');
                    isValid = false;
                    if(!firstError) firstError = input;
                }
            }
        });

        if(!isValid) {
            e.preventDefault();
            if(firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstError.focus();
            }
        }
    });

    // Clear error on input
    requiredIds.forEach(id => {
        const input = document.getElementById(id);
        input.addEventListener('input', function() {
            if(this.value.trim()) {
                const errorSpan = this.parentNode.querySelector('.error-msg');
                if(errorSpan) errorSpan.classList.add('hidden');
            }
        });
    });

    // Auto-Slug Generation
    const nameInput = document.getElementById('source_name');
    const slugInput = document.querySelector('input[name="slug"]');

    if(nameInput && slugInput) {
        nameInput.addEventListener('keyup', function() {
            const val = this.value;
            const slug = val.toLowerCase()
                .trim()
                .replace(/[^\w\s-]/g, '') // Remove non-word chars
                .replace(/[\s_-]+/g, '-') // Replace spaces with -
                .replace(/^-+|-+$/g, ''); // Trim -
            
            slugInput.value = slug;
        });
    }

    // Custom Parent Search Logic
    const parentsData = <?= json_encode($parents); ?>;
    const parentSearch = document.getElementById('parent_search');
    const parentDropdown = document.getElementById('parent_dropdown');
    const parentResults = document.getElementById('parent_results');
    const parentIdInput = document.getElementById('parent_id');
    const parentChevron = document.getElementById('parent_chevron');

    function renderParents(items) {
        if(items.length > 0) {
            let html = '';
            items.forEach(p => {
                html += `
                    <div class="parent-option px-4 py-3 hover:bg-teal-50 cursor-pointer transition-colors border-b border-slate-50 last:border-b-0 rounded-lg font-medium text-slate-700 text-sm flex items-center gap-2" 
                         data-id="${p.source_id}" 
                         data-name="${p.source_name}">
                         <i class="fas fa-level-up-alt text-teal-400 rotate-90 text-[10px]"></i>
                         ${p.source_name}
                    </div>
                `;
            });
            parentResults.innerHTML = html;
        } else {
            parentResults.innerHTML = '<div class="p-4 text-center text-slate-400 text-xs font-bold uppercase tracking-wide">No sources found</div>';
        }
    }

    if(parentSearch) {
        // Initial render (limited to 5 or all if small)
        // User requested: "search kora jabe first eii 5 ta data dekhabe"
        
        parentSearch.addEventListener('focus', function() {
            parentDropdown.classList.remove('hidden');
            parentChevron.classList.add('rotate-180');
            
            // Show top 5 initially
            renderParents(parentsData.slice(0, 5));
        });

        parentSearch.addEventListener('blur', function() {
            // Delay hide to allow click
            setTimeout(() => {
                parentDropdown.classList.add('hidden');
                parentChevron.classList.remove('rotate-180');
            }, 200);
        });

        parentSearch.addEventListener('input', function() {
            const val = this.value.toLowerCase();
            const filtered = parentsData.filter(p => p.source_name.toLowerCase().includes(val));
            renderParents(filtered.slice(0, 5)); 
        });

        // Delegate click for options (using mousedown to fire before blur)
        parentResults.addEventListener('mousedown', function(e) {
            const option = e.target.closest('.parent-option');
            if(option) {
                const id = option.dataset.id;
                const name = option.dataset.name;
                
                parentIdInput.value = id;
                parentSearch.value = name;
                
                // Clear validation error if any
                const errorSpan = parentSearch.parentNode.parentNode.querySelector('.error-msg');
                if(errorSpan) errorSpan.classList.add('hidden');
            }
        });
    }

    // Custom Parent Search Logic (End)
});
</script>
