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
    .custom-scroll::-webkit-scrollbar { width: 4px; }
    .custom-scroll::-webkit-scrollbar-thumb { background: rgba(0, 0, 0, 0.1); border-radius: 10px; }
    
    .error-border { border-color: #ef4444 !important; background-color: #fef2f2 !important; }
    .error-text { color: #ef4444; font-size: 0.75rem; font-weight: 700; margin-top: 0.25rem; margin-left: 0.25rem; display: none; }
    .error-text.active { display: flex; align-items: center; gap: 0.25rem; }
    
    input:focus, select:focus {
        border-color: #0d9488 !important;
        box-shadow: 0 0 0 2px rgba(13, 148, 136, 0.2) !important;
    }
</style>

<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #0f172a 0%, #0d9488 50%, #064e3b 100%);
        --glass-bg: rgba(255, 255, 255, 0.85);
        --glass-border: rgba(255, 255, 255, 0.4);
    }

    .premium-bg {
        background: var(--primary-gradient);
        background-attachment: fixed;
    }

    .glass-panel {
        backdrop-filter: blur(12px);
        background: var(--glass-bg);
        border: 1px solid var(--glass-border);
        box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.08);
    }

    .floating-shape {
        position: absolute;
        z-index: 0;
        filter: blur(60px);
        opacity: 0.4;
        border-radius: 50%;
        animation: float 20s infinite alternate;
    }

    @keyframes float {
        0% { transform: translate(0, 0) scale(1); }
        100% { transform: translate(50px, 100px) scale(1.2); }
    }

    .custom-scroll::-webkit-scrollbar { width: 4px; }
    .custom-scroll::-webkit-scrollbar-thumb { background: rgba(13, 148, 136, 0.3); border-radius: 10px; }
    
    .error-border { border-color: #f43f5e !important; background-color: #fff1f2 !important; box-shadow: 0 0 0 4px rgba(244, 63, 94, 0.1) !important; }
    .error-text { color: #f43f5e; font-size: 0.7rem; font-weight: 800; margin-top: 0.4rem; padding-left: 0.5rem; display: none; text-transform: uppercase; letter-spacing: 0.05em; }
    .error-text.active { display: flex; align-items: center; gap: 0.25rem; }
    
    .input-focus-effect:focus {
        border-color: #0d9488 !important;
        background-color: #ffffff !important;
        box-shadow: 0 10px 15px -3px rgba(13, 148, 136, 0.1), 0 4px 6px -2px rgba(13, 148, 136, 0.05) !important;
        transform: translateY(-2px);
    }

    .btn-gradient {
        background: linear-gradient(to right, #0d9488, #059669);
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .status-card-wrapper #status-card {
        margin: 0 !important;
        border-radius: 1.5rem !important;
        background: #f8fafc !important;
        border: 1px solid #e2e8f0 !important;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
        transform: translateY(-8px);
    }

    .status-card-wrapper #status-card.active-state {
        background: #064e3b !important;
        border-color: #065f46 !important;
        box-shadow: 0 10px 25px -5px rgba(6, 78, 59, 0.3) !important;
    }

    .status-card-wrapper #status-card h3 {
        transition: color 0.4s ease !important;
    }

    .status-card-wrapper #status-card.active-state h3 {
        color: white !important;
    }

    .status-card-wrapper #status-card.active-state p {
        color: rgba(255, 255, 255, 0.7) !important;
    }

    .store-list-container {
        max-height: 400px !important;
        overflow-y: auto !important;
    }

    .group\/store div.max-h-64 {
        max-height: 400px !important;
    }
</style>

<div class="app-wrapper flex flex-col h-screen overflow-hidden premium-bg relative">
    <!-- Abstract Decorative Shapes -->
    <div class="floating-shape bg-emerald-400 w-96 h-96 top-[-10%] right-[-10%]"></div>
    <div class="floating-shape bg-teal-600 w-80 h-80 bottom-[-5%] left-[-5%] animation-delay-2000"></div>

    <?php include('../includes/sidebar.php'); ?>
    <?php include('../includes/navbar.php'); ?>
    
    <main id="main-content" class="lg:ml-64 flex-1 flex flex-col h-screen overflow-hidden transition-all duration-300 relative z-10">
        <div class="flex-1 overflow-y-auto p-6 md:p-10 custom-scroll">
            <div class="max-w-5xl mx-auto mb-12">
                
                <div class="flex items-center justify-between mb-10 slide-in">
                    <div class="flex items-center gap-6">
                        <a href="/pos/printer/list" class="w-12 h-12 flex items-center justify-center rounded-2xl glass-panel group hover:bg-white/90 transition-all duration-300">
                            <i class="fas fa-chevron-left text-slate-800 group-hover:text-teal-600 group-hover:-translate-x-1 transition-all"></i>
                        </a>
                        <div>
                            <span class="inline-block px-3 py-1 bg-teal-500/10 text-teal-300 text-[10px] font-bold uppercase tracking-widest rounded-full mb-2 backdrop-blur-sm border border-teal-500/20">System Hardware</span>
                            <h1 class="text-4xl font-black text-white tracking-tight"><?= $page_title; ?></h1>
                        </div>
                    </div>
                </div>

                <form action="/pos/printer/save" method="POST" id="printerForm" class="space-y-8" novalidate>
                    <?php if($mode == 'edit'): ?>
                        <input type="hidden" name="printer_id" value="<?= $d['id']; ?>">
                    <?php endif; ?>

                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-stretch">
                        <!-- Left Column: Settings -->
                        <div class="lg:col-span-12 xl:col-span-7 space-y-8 h-full">
                            <div class="glass-panel rounded-[2.5rem] p-8 md:p-12 relative overflow-hidden group h-full flex flex-col justify-between">
                                <div class="absolute top-0 right-0 p-8 opacity-[0.03] select-none pointer-events-none group-hover:scale-110 transition-transform duration-700">
                                    <i class="fas fa-print text-[15rem]"></i>
                                </div>

                                <div>
                                    <div class="flex items-center gap-4 mb-10">
                                        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-teal-600 to-emerald-800 flex items-center justify-center shadow-lg shadow-teal-900/40">
                                            <i class="fas fa-sliders-h text-white text-xl"></i>
                                        </div>
                                        <div>
                                            <h2 class="text-2xl font-black text-slate-800 tracking-tight">Technical Profile</h2>
                                            <p class="text-slate-500 font-bold text-xs uppercase tracking-widest opacity-70">Hardware Connectivity Settings</p>
                                        </div>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                                        <!-- Title -->
                                        <div class="space-y-3">
                                            <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Printer Identity</label>
                                            <div class="relative">
                                                <input type="text" name="title" id="title" value="<?= htmlspecialchars($d['title']); ?>" 
                                                    class="w-full px-6 py-4 bg-slate-100/50 border border-slate-200 rounded-2xl transition-all input-focus-effect font-bold text-slate-800 placeholder:font-medium" 
                                                    placeholder="Counter 01 - Thermal">
                                                <i class="fas fa-tag absolute right-5 top-1/2 -translate-y-1/2 text-slate-300"></i>
                                            </div>
                                            <p class="error-text" id="err_title"><i class="fas fa-exclamation-triangle"></i> Required Field</p>
                                        </div>

                                        <!-- Type -->
                                        <div class="space-y-3">
                                            <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Connection Protocol</label>
                                            <div class="relative">
                                                <select name="type" id="type" class="w-full pl-6 pr-14 py-4 bg-slate-100/50 border border-slate-200 rounded-2xl transition-all input-focus-effect font-bold text-slate-800 appearance-none cursor-pointer">
                                                    <option value="network" <?= $d['type'] == 'network' ? 'selected' : ''; ?>>Local Network (LAN/Wi-Fi)</option>
                                                    <option value="windows" <?= $d['type'] == 'windows' ? 'selected' : ''; ?>>Windows Direct Link</option>
                                                    <option value="linux" <?= $d['type'] == 'linux' ? 'selected' : ''; ?>>Linux / CUPS Service</option>
                                                </select>
                                                <i class="fas fa-network-wired absolute right-5 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none"></i>
                                            </div>
                                        </div>

                                        <!-- Profile -->
                                        <div class="space-y-3">
                                            <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Hardware Profile</label>
                                            <div class="relative">
                                                <select name="profile" id="profile" class="w-full pl-6 pr-14 py-4 bg-slate-100/50 border border-slate-200 rounded-2xl transition-all input-focus-effect font-bold text-teal-700 appearance-none cursor-pointer shadow-sm">
                                                    <option value="thermal" <?= $d['profile'] == 'thermal' ? 'selected' : ''; ?>>Thermal 80mm (Standard)</option>
                                                    <option value="thermal_58" <?= $d['profile'] == 'thermal_58' ? 'selected' : ''; ?>>Thermal 58mm (Narrow)</option>
                                                    <option value="dot_matrix" <?= $d['profile'] == 'dot_matrix' ? 'selected' : ''; ?>>Dot Matrix (Impact)</option>
                                                    <option value="standard_a4" <?= $d['profile'] == 'standard_a4' ? 'selected' : ''; ?>>Laser / Inkjet (A4)</option>
                                                </select>
                                                <i class="fas fa-microchip absolute right-5 top-1/2 -translate-y-1/2 text-slate-300 pointer-events-none"></i>
                                            </div>
                                        </div>

                                        <!-- Char Per Line -->
                                        <div class="space-y-3">
                                            <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Line Density (Chars)</label>
                                            <div class="relative">
                                                <input type="number" name="char_per_line" id="char_per_line" value="<?= $d['char_per_line']; ?>" 
                                                    class="w-full px-6 py-4 bg-slate-100/50 border border-slate-200 rounded-2xl transition-all input-focus-effect font-bold text-teal-700" placeholder="e.g. 32, 42, 48">
                                                <i class="fas fa-text-width absolute right-5 top-1/2 -translate-y-1/2 text-slate-300"></i>
                                            </div>
                                            <p class="error-text" id="err_char_per_line"><i class="fas fa-exclamation-triangle"></i> Density Missing</p>
                                        </div>

                                        <!-- IP Address -->
                                        <div class="space-y-3">
                                            <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Network Host (IP)</label>
                                            <div class="relative">
                                                <input type="text" name="ip_address" id="ip_address" value="<?= htmlspecialchars($d['ip_address']); ?>" 
                                                    class="w-full px-6 py-4 bg-slate-100/50 border border-slate-200 rounded-2xl transition-all input-focus-effect font-mono font-bold text-slate-800"
                                                    placeholder="192.168.x.x">
                                                <i class="fas fa-ethernet absolute right-5 top-1/2 -translate-y-1/2 text-slate-300"></i>
                                            </div>
                                            <p class="error-text" id="err_ip_address"><i class="fas fa-exclamation-triangle"></i> Valid IP Needed</p>
                                        </div>

                                        <!-- Port -->
                                        <div class="space-y-3">
                                            <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Service Port</label>
                                            <div class="relative">
                                                <input type="text" name="port" id="port" value="<?= htmlspecialchars($d['port']); ?>" 
                                                    class="w-full px-6 py-4 bg-slate-100/50 border border-slate-200 rounded-2xl transition-all input-focus-effect font-bold text-slate-800" placeholder="9100">
                                                <i class="fas fa-door-open absolute right-5 top-1/2 -translate-y-1/2 text-slate-300"></i>
                                            </div>
                                            <p class="error-text" id="err_port"><i class="fas fa-exclamation-triangle"></i> Port Undefined</p>
                                        </div>

                                        <!-- Order -->
                                        <div class="space-y-3">
                                            <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest ml-1">Display Priority</label>
                                            <div class="relative">
                                                <input type="number" name="sort_order" id="sort_order" value="<?= $d['sort_order']; ?>" 
                                                    class="w-full px-6 py-4 bg-slate-100/50 border border-slate-200 rounded-2xl transition-all input-focus-effect font-bold text-slate-800" placeholder="0">
                                                <i class="fas fa-sort-numeric-down absolute right-5 top-1/2 -translate-y-1/2 text-slate-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-[-42px] space-y-2 relative z-20">
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
                                    <div class="px-4 flex items-center gap-2">
                                        <i class="fas fa-info-circle text-blue-500 text-[10px]"></i>
                                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.1em]">Hardware status directly affects real-time printing queues and store sync.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Store Mapping -->
                        <div class="lg:col-span-12 xl:col-span-5 space-y-8 h-full flex flex-col">
                            <div class="glass-panel rounded-[2.5rem] p-6 md:p-10 flex-1 flex flex-col relative overflow-hidden">
                                <div class="absolute -right-10 -bottom-10 opacity-[0.05] pointer-events-none select-none">
                                    <i class="fas fa-map-marked-alt text-[12rem]"></i>
                                </div>

                                <div class="flex items-center justify-between mb-10 px-2 relative z-10">
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-orange-400 to-rose-600 flex items-center justify-center shadow-lg shadow-orange-900/20">
                                            <i class="fas fa-store text-white"></i>
                                        </div>
                                        <div>
                                            <h3 class="text-2xl font-black text-slate-800 tracking-tight">Deployment Hub</h3>
                                            <p class="text-slate-500 font-bold text-[10px] uppercase tracking-widest opacity-70">Target Store Distribution</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="flex-1 min-h-[400px] flex flex-col relative z-10 group/store">
                                    <style>
                                        #storeList label {
                                            background: white;
                                            border: 1px solid #f1f5f9;
                                            margin-bottom: 0.75rem;
                                            padding: 1.25rem !important;
                                            border-radius: 1.25rem !important;
                                            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
                                            transition: all 0.3s ease;
                                        }
                                        #storeList label:hover {
                                            transform: translateX(5px) scale(1.01);
                                            border-color: #0d9488;
                                            box-shadow: 0 10px 15px -3px rgba(13, 148, 136, 0.1);
                                        }
                                        #storeList input:checked + span {
                                            color: #0d9488;
                                            font-weight: 800;
                                        }
                                        #storeList label:has(input:checked) {
                                            background: #f0fdfa;
                                            border-color: #5eead4;
                                        }
                                        #storeSearch {
                                            padding: 1rem 1.5rem !important;
                                            border-radius: 1.25rem !important;
                                            background: white !important;
                                            border: 2px solid #f1f5f9 !important;
                                        }
                                        #storeSearch:focus {
                                            border-color: #0d9488 !important;
                                        }
                                    </style>
                                    <?php 
                                    $store_label = "Active Access Control";
                                    include('../includes/store_select_component.php'); 
                                    ?>
                                </div>
                            </div>

                            <div class="pt-2">
                                <button type="submit" name="<?= $btn_name; ?>" class="w-full py-6 btn-gradient text-white font-black text-lg rounded-[2.5rem] shadow-2xl flex items-center justify-center gap-4 group overflow-hidden relative">
                                    <div class="absolute inset-0 bg-white/20 translate-y-full group-hover:translate-y-0 transition-transform duration-500"></div>
                                    <i class="fas fa-bolt group-hover:animate-pulse relative z-10"></i> 
                                    <span class="uppercase tracking-[0.25em] text-sm relative z-10 font-black"><?= $btn_text; ?></span>
                                </button>
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
