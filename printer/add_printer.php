<?php
session_start();
include('../config/dbcon.php');

if(!isset($_SESSION['auth'])){
    header("Location: /pos/login"); 
    exit(0);
}

$mode = "create";
$btn_name = "save_printer_btn";
$btn_text = "Save Printer";
$page_title = "Add New Printer";

$d = [
    'id' => '', 'title' => '', 'type' => 'network', 'profile' => 'thermal',
    'char_per_line' => '200', 'ip_address' => '', 'port' => '9100',
    'path' => '', 'status' => '1', 'sort_order' => '0'
];

$selected_stores = [];

if(isset($_GET['id'])) {
    $mode = "edit";
    $btn_name = "update_printer_btn";
    $btn_text = "Update Printer";
    $page_title = "Edit Printer";
    
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    $query = "SELECT * FROM printers WHERE printer_id='$id' LIMIT 1";
    $result = mysqli_query($conn, $query);

    if(mysqli_num_rows($result) > 0){
        $d = mysqli_fetch_array($result);
        $d['id'] = $d['printer_id'];
        
        // Fetch selected stores
        $store_q = "SELECT store_id FROM printer_store_map WHERE printer_id = '$id'";
        $store_res = mysqli_query($conn, $store_q);
        while($s_row = mysqli_fetch_assoc($store_res)) {
            $selected_stores[] = $s_row['store_id'];
        }
    } else {
        $_SESSION['message'] = "Printer not found!";
        $_SESSION['msg_type'] = "error";
        header("Location: /pos/printer/list");
        exit(0);
    }
}

// Prepare stores for component
$all_stores = [];
$stores_res = mysqli_query($conn, "SELECT id, store_name FROM stores WHERE status = 1 ORDER BY store_name ASC");
while($store = mysqli_fetch_assoc($stores_res)) {
    $all_stores[] = $store;
}

include('../includes/header.php');
?>
<style>
    /* DESIGN SYSTEM Consistency */
    .glass-card { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); }
    [data-theme="dark"] .glass-card { background: rgba(30, 41, 59, 0.95); border-color: #334155; }
    [data-theme="login-style"] .glass-card { background: rgba(15, 23, 42, 0.95); border-color: #1e293b; }

    .premium-bg {
        background: #f8fafc;
        background-attachment: fixed;
    }
    [data-theme="dark"] .premium-bg {
        background: linear-gradient(135deg, #0f172a 0%, #0d9488 50%, #064e3b 100%);
    }
    [data-theme="login-style"] .premium-bg {
        background: linear-gradient(135deg, #1e293b 0%, #0f766e 50%, #134e4a 100%);
    }

    .slide-in { animation: slideIn 0.4s ease-out forwards; opacity: 0; transform: translateY(20px); }
    @keyframes slideIn { to { opacity: 1; transform: translateY(0); } }

    .custom-scroll::-webkit-scrollbar { width: 6px; }
    .custom-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    
    .error-border { border-color: #e11d48 !important; background-color: #fff1f2 !important; }
    .error-text { color: #e11d48; font-size: 0.75rem; font-weight: 700; margin-top: 4px; display: none; align-items: center; gap: 4px; }
    .error-text.active { display: flex; }
    
    .input-standard {
        padding: 0.875rem 1rem;
        width: 100%;
        background: white;
        border: 1px solid #cbd5e1;
        border-radius: 0.75rem;
        transition: all 0.2s;
    }
    [data-theme="dark"] .input-standard, [data-theme="login-style"] .input-standard {
        background: rgba(15, 23, 42, 0.5);
        border-color: #334155;
        color: white;
    }
    .input-standard:focus {
        ring: 2px;
        ring-color: #0d9488;
        border-color: #0d9488;
        outline: none;
    }

    .btn-save {
        background: #064e3b;
        color: white;
        font-weight: 700;
        transition: all 0.3s;
    }
    .btn-save:hover { background: #053d2e; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(6, 78, 59, 0.3); }
    /* Page Title Colors */
    .page-title { color: #1e293b; } /* Slate-800 for light theme */
    [data-theme="dark"] .page-title, [data-theme="login-style"] .page-title { color: #ffffff !important; }
</style>

<div class="app-wrapper flex flex-col h-screen overflow-hidden premium-bg relative">
    <?php include('../includes/sidebar.php'); ?>
    
    <main id="main-content" class="lg:ml-64 flex-1 flex flex-col h-screen overflow-hidden transition-all duration-300 relative z-10">
        <div class="navbar-fixed-top"><?php include('../includes/navbar.php'); ?></div> 

        <div class="flex-1 overflow-y-auto p-4 md:p-8 custom-scroll">
            <div class="max-w-[1400px] mx-auto">
                
                <!-- HEADER (Standard style from add_user) -->
                <div class="mb-8 slide-in">
                    <div class="flex items-center gap-4 mb-4">
                        <a href="/pos/printer/list" class="w-10 h-10 flex items-center justify-center rounded-lg bg-white shadow-sm border border-slate-200 text-slate-700 hover:text-teal-600 transition-all">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <div>
                            <h1 class="text-3xl font-bold page-title"><?= $page_title; ?></h1>
                            <div class="flex items-center gap-2 text-sm text-slate-500">
                                <span class="w-2 h-2 rounded-full bg-teal-500 animate-pulse"></span>
                                <span>Configure and manage hardware printing services</span>
                            </div>
                        </div>
                    </div>
                </div>

                <form action="/pos/printer/save" method="POST" id="printerForm" novalidate>
                    <?php if($mode == 'edit'): ?>
                        <input type="hidden" name="printer_id" value="<?= $d['id']; ?>">
                    <?php endif; ?>

                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                        
                        <!-- LEFT COLUMN: Technical Settings -->
                        <div class="lg:col-span-8 space-y-6">
                            <div class="glass-card rounded-xl p-6 shadow-lg border border-slate-200 slide-in bg-white">
                                <h2 class="text-lg font-bold text-slate-800 mb-8 flex items-center gap-3">
                                    <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-teal-900 to-emerald-900 text-white flex items-center justify-center text-lg shadow-md shadow-emerald-900/40">
                                        <i class="fas fa-sliders-h"></i>
                                    </span>
                                    Technical Profile
                                </h2>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Identity -->
                                    <div class="space-y-2">
                                        <label class="text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Printer Identity *</label>
                                        <input type="text" name="title" id="title" value="<?= htmlspecialchars($d['title']); ?>" 
                                            class="input-standard font-bold" placeholder="Counter 01 - Thermal">
                                        <p class="error-text" id="err_title"><i class="fas fa-exclamation-circle"></i> Identity is required</p>
                                    </div>

                                    <!-- Protocol -->
                                    <div class="space-y-2">
                                        <label class="text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Connection Protocol</label>
                                        <select name="type" id="type" class="input-standard cursor-pointer">
                                            <option value="network" <?= $d['type'] == 'network' ? 'selected' : ''; ?>>Local Network (LAN/Wi-Fi)</option>
                                            <option value="windows" <?= $d['type'] == 'windows' ? 'selected' : ''; ?>>Windows Direct Link</option>
                                            <option value="linux" <?= $d['type'] == 'linux' ? 'selected' : ''; ?>>Linux / CUPS Service</option>
                                        </select>
                                    </div>

                                    <!-- Profile -->
                                    <div class="space-y-2">
                                        <label class="text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Hardware Profile</label>
                                        <select name="profile" id="profile" class="input-standard cursor-pointer font-bold text-teal-600">
                                            <option value="thermal" <?= $d['profile'] == 'thermal' ? 'selected' : ''; ?>>Thermal 80mm (Standard)</option>
                                            <option value="thermal_58" <?= $d['profile'] == 'thermal_58' ? 'selected' : ''; ?>>Thermal 58mm (Narrow)</option>
                                            <option value="dot_matrix" <?= $d['profile'] == 'dot_matrix' ? 'selected' : ''; ?>>Dot Matrix (Impact)</option>
                                            <option value="standard_a4" <?= $d['profile'] == 'standard_a4' ? 'selected' : ''; ?>>Laser / Inkjet (A4)</option>
                                        </select>
                                    </div>

                                    <!-- Density -->
                                    <div class="space-y-2">
                                        <label class="text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Line Density (Chars) *</label>
                                        <input type="number" name="char_per_line" id="char_per_line" value="<?= $d['char_per_line']; ?>" 
                                            class="input-standard font-bold" placeholder="e.g. 32, 42, 48">
                                        <p class="error-text" id="err_char_per_line"><i class="fas fa-exclamation-circle"></i> Density is required</p>
                                    </div>

                                    <!-- IP -->
                                    <div class="space-y-2">
                                        <label class="text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Network Host (IP) *</label>
                                        <input type="text" name="ip_address" id="ip_address" value="<?= htmlspecialchars($d['ip_address']); ?>" 
                                            class="input-standard font-mono" placeholder="192.168.x.x">
                                        <p class="error-text" id="err_ip_address"><i class="fas fa-exclamation-circle"></i> Valid IP is required</p>
                                    </div>

                                    <!-- Port -->
                                    <div class="space-y-2">
                                        <label class="text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Service Port *</label>
                                        <input type="text" name="port" id="port" value="<?= htmlspecialchars($d['port']); ?>" 
                                            class="input-standard" placeholder="9100">
                                        <p class="error-text" id="err_port"><i class="fas fa-exclamation-circle"></i> Port is required</p>
                                    </div>
                                </div>

                                <div class="mt-8 pt-6 border-t border-slate-100 dark:border-slate-800">
                                    <div class="status-card-wrapper w-full">
                                        <?php 
                                        $current_status = $d['status'];
                                        $status_title = "Hardware";
                                        $card_id = "status-card";
                                        $label_id = "status-label";
                                        $input_id = "status_input";
                                        $toggle_id = "status_toggle";
                                        include('../includes/status_card.php'); 
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- RIGHT COLUMN: Assignments -->
                        <div class="lg:col-span-4 space-y-6">
                            <!-- Store Selector -->
                            <div class="glass-card rounded-xl p-6 shadow-lg border border-slate-200 slide-in delay-75 bg-white flex flex-col">
                                <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-4">Deployment Hub</h3>
                                <div class="flex-1 min-h-[350px]">
                                    <?php 
                                    $store_label = "Assign Stores";
                                    include('../includes/store_select_component.php'); 
                                    ?>
                                    <p class="error-msg text-xs text-red-500 mt-2 hidden" id="store-error"><i class="fas fa-exclamation-circle"></i> Select at least one store</p>
                                </div>
                            </div>

                            <!-- Display Priority (Relocated) -->
                            <div class="glass-card rounded-xl p-6 shadow-lg border border-slate-200 slide-in delay-150 bg-white">
                                <label class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 block">Display Priority</label>
                                <div class="relative">
                                    <input type="number" name="sort_order" id="sort_order" value="<?= $d['sort_order']; ?>" 
                                        class="input-standard font-bold" placeholder="0">
                                    <i class="fas fa-sort-numeric-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-300"></i>
                                </div>
                                <p class="error-text" id="err_sort_order"><i class="fas fa-exclamation-circle"></i> Required</p>
                            </div>

                            <!-- Actions -->
                            <div class="slide-in delay-200">
                                <div class="grid grid-cols-2 gap-4">
                                    <button type="submit" name="<?= $btn_name; ?>" class="col-span-1 py-4 rounded-xl btn-save shadow-lg flex items-center justify-center gap-2 group">
                                        <i class="fas fa-save group-hover:scale-110 transition-transform"></i>
                                        <span><?= $btn_text; ?></span>
                                    </button>
                                    <a href="/pos/printer/list" class="col-span-1 py-4 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-bold text-center flex items-center justify-center gap-2 hover:bg-slate-200 transition-all border border-slate-200 dark:border-slate-700">
                                        <i class="fas fa-times"></i>
                                        <span>Cancel</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <?php include('../includes/footer.php'); ?>
    </main>
</div>

<script>
$(document).ready(function() {
    const form = $('#printerForm');
    
    form.on('submit', function(e) {
        let hasError = false;
        const fields = ['title', 'char_per_line', 'ip_address', 'port', 'sort_order'];
        
        fields.forEach(field => {
            const input = $('#' + field);
            const err = $('#err_' + field);
            if(input.val().trim() === '') {
                input.addClass('error-border');
                err.addClass('active');
                hasError = true;
            } else {
                input.removeClass('error-border');
                err.removeClass('active');
            }
        });

        if($('.store-checkbox:checked').length === 0) {
            $('#store-error').removeClass('hidden');
            hasError = true;
        } else {
            $('#store-error').addClass('hidden');
        }

        if(hasError) {
            e.preventDefault();
            Swal.fire({
                title: 'Check Form',
                text: 'Please fill in all required fields.',
                icon: 'warning',
                confirmButtonColor: '#0d9488',
                background: document.documentElement.getAttribute('data-theme') === 'dark' ? '#1e293b' : '#fff',
                color: document.documentElement.getAttribute('data-theme') === 'dark' ? '#fff' : '#000'
            });
        }
    });

    $('input, select').on('input change', function() {
        if($(this).val().trim() !== '') {
            $(this).removeClass('error-border');
            $('#err_' + $(this).attr('id')).removeClass('active');
        }
    });

    $('.store-checkbox').on('change', function() {
        if($('.store-checkbox:checked').length > 0) $('#store-error').addClass('hidden');
    });

    const syncStatusCard = () => {
        const isChecked = $('#status_toggle').is(':checked');
        const card = $('#status-card');
        const label = $('#status-label');
        if (isChecked) {
            card.addClass('active-state');
            label.text('ACTIVE OPERATIONS').addClass('text-emerald-100').removeClass('text-slate-500');
        } else {
            card.removeClass('active-state');
            label.text('INACTIVE STATUS').addClass('text-slate-500').removeClass('text-emerald-100');
        }
    };
    
    setTimeout(syncStatusCard, 150);
    $('#status_toggle').on('change', syncStatusCard);
});
</script>

<?php if(isset($_SESSION['message'])): ?>
<script>
    Swal.fire({ 
        title: '<?= $_SESSION['msg_type'] == "success" ? "Success!" : "Notice"; ?>', 
        text: "<?= $_SESSION['message']; ?>", 
        icon: '<?= $_SESSION['msg_type']; ?>', 
        confirmButtonColor: '#0d9488', 
        timer: 2000, 
        showConfirmButton: false, 
        toast: true, 
        position: 'top-end' 
    });
</script>
<?php unset($_SESSION['message']); unset($_SESSION['msg_type']); endif; ?>

<script>
$(document).ready(function() {
    const form = $('#printerForm');
    
    // Form Submission Validation
    form.on('submit', function(e) {
        let hasError = false;
        
        // Validate required fields
        const fields = ['title', 'char_per_line', 'ip_address', 'port', 'sort_order'];
        fields.forEach(field => {
            const input = $('#' + field);
            const err = $('#err_' + field);
            if(input.val().trim() === '') {
                input.addClass('error-border');
                err.addClass('active');
                hasError = true;
            } else {
                input.removeClass('error-border');
                err.removeClass('active');
            }
        });

        // Validate Stores (from component)
        const checkedStores = $('.store-checkbox:checked').length;
        const storeError = $('#store-error');
        if(checkedStores === 0) {
            storeError.removeClass('hidden');
            hasError = true;
        } else {
            storeError.addClass('hidden');
        }

        if(hasError) {
            e.preventDefault();
            Swal.fire({
                title: 'Validation Error',
                text: 'Please fill in all required fields and select at least one store.',
                icon: 'warning',
                confirmButtonColor: '#0d9488'
            });
        }
    });

    // Real-time validation
    $('input, select').on('input change', function() {
        if($(this).val().trim() !== '') {
            $(this).removeClass('error-border');
            $('#err_' + $(this).attr('id')).removeClass('active');
        }
    });

    // Individual store checkbox logic
    $('.store-checkbox').on('change', function() {
        if($('.store-checkbox:checked').length > 0) {
            $('#store-error').addClass('hidden');
        }
    });

    // --- Status UI Logic (Solid Theme) ---
    const syncStatusCard = () => {
        const isChecked = $('#status_toggle').is(':checked');
        const card = $('#status-card');
        const label = $('#status-label');
        
        if (isChecked) {
            card.addClass('active-state');
            label.text('ACTIVE OPERATIONS').addClass('text-emerald-100').removeClass('text-slate-500');
        } else {
            card.removeClass('active-state');
            label.text('INACTIVE STATUS').addClass('text-slate-500').removeClass('text-emerald-100');
        }
    };

    // Initial sync
    setTimeout(syncStatusCard, 150);

    // Change sync
    $('#status_toggle').on('change', syncStatusCard);
});
</script>

<?php if(isset($_SESSION['message'])): ?>
<script>
    Swal.fire({ 
        title: '<?= $_SESSION['msg_type'] == "success" ? "Success!" : "Notice"; ?>', 
        text: "<?php echo $_SESSION['message']; ?>", 
        icon: '<?= $_SESSION['msg_type']; ?>', 
        confirmButtonColor: '#0d9488', 
        timer: 2000, 
        showConfirmButton: false, 
        toast: true, 
        position: 'top-end' 
    });
</script>
<?php unset($_SESSION['message']); unset($_SESSION['msg_type']); endif; ?>
