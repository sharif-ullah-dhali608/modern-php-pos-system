<?php
session_start();
include('../config/dbcon.php');

// 1. SECURITY CHECK
if(!isset($_SESSION['auth'])){
    // Corrected login path to standard signin/login
    header("Location: /pos/signin.php"); 
    exit(0);
}

// 2. FETCH DATA (Using the safe query that works)
$query = "SELECT * FROM stores ORDER BY id DESC";
$query_run = mysqli_query($conn, $query);
$page_title = "Store List - Velocity POS";
?>

<?php include('../includes/header.php'); ?>

<div class="flex">
    <?php include('../includes/sidebar.php'); ?>
    
    <main id="main-content" class="flex-1 ml-64 main-content min-h-screen transition-all duration-300">
        <?php include('../includes/navbar.php'); ?>
        
        <div class="p-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
                <div>
                    <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">Store List</h1>
                    <p class="text-slate-500 font-medium text-sm mt-1">Manage your business locations</p>
                </div>
                <a href="add_store.php" class="flex items-center gap-2 px-6 py-3 rounded-lg bg-gradient-to-br from-teal-900 via-teal-800 to-emerald-900 hover:to-emerald-800 text-white font-bold text-sm shadow-xl hover:bg-purple-700 transition-all">
                    <i class="fas fa-plus"></i> <span>Add New Store</span>
                </a>
            </div>

            <?php if(mysqli_num_rows($query_run) > 0): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                    <?php 
                    while($row = mysqli_fetch_assoc($query_run)): 
                        $isActive = $row['status'] == 1;
                        // Light Mode Colors
                        $border_color = $isActive ? 'bg-emerald-600' : 'bg-rose-600';
                        $icon_bg = $isActive ? 'bg-emerald-500/10 text-emerald-600' : 'bg-rose-500/10 text-rose-600';
                        $jsonData = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                    ?>
                    
                    <div onclick="openStoreModal(<?= $jsonData ?>)" class="group relative w-full rounded-2xl shadow-lg border border-slate-200 bg-white hover:shadow-xl overflow-hidden cursor-pointer transition-all duration-300 hover:-translate-y-1">
                        <div class="absolute left-0 top-0 bottom-0 w-1.5 <?= $border_color; ?>"></div>
                        <div class="p-6 pl-8"> 
                            <div class="flex justify-between items-start mb-4">
                                <div class="flex gap-4 items-center">
                                    <div class="w-12 h-12 rounded-2xl <?= $icon_bg; ?> flex items-center justify-center text-lg shadow-sm"><i class="fas fa-store"></i></div>
                                    <div>
                                        <h3 class="font-bold text-slate-800 text-lg leading-tight group-hover:text-purple-600 transition-colors"><?= htmlspecialchars($row['store_name']); ?></h3>
                                        <span class="inline-flex items-center gap-1 mt-1 px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-slate-100 text-slate-500 border border-slate-200">#<?= htmlspecialchars($row['store_code']); ?></span>
                                    </div>
                                </div>
                                <a href="add_store.php?id=<?= $row['id']; ?>" onclick="event.stopPropagation()" class="w-9 h-9 rounded-full bg-slate-100 border border-slate-300 text-slate-500 flex items-center justify-center hover:bg-slate-200 hover:text-purple-600 transition-all shadow-sm z-10"><i class="fas fa-pen text-xs"></i></a>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4 text-xs text-slate-500 mb-5 pt-4 border-t border-slate-100 border-dashed">
                                <div><p class="text-[10px] uppercase font-bold text-slate-400 mb-1">Phone</p><p class="font-semibold text-slate-700 truncate"><?= $row['phone'] ?: 'N/A'; ?></p></div>
                                <div><p class="text-[10px] uppercase font-bold text-slate-400 mb-1">Location</p><p class="font-semibold text-slate-700 truncate"><?= substr($row['city_zip'], 0, 15); ?>..</p></div>
                            </div>
                            
                            <div class="flex items-center justify-between bg-purple-50 rounded-xl p-3 border border-purple-100 group-hover:bg-purple-100 transition-colors">
                                <span class="text-xs font-bold text-purple-600 uppercase">Target</span>
                                <span class="text-lg font-extrabold text-purple-700 font-mono"><span class="text-xs text-purple-500 mr-0.5">৳</span><?= number_format($row['daily_target']); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="flex flex-col items-center justify-center h-[60vh] text-center border-2 border-dashed border-slate-300 rounded-3xl bg-slate-50 shadow-inner">
                    <h2 class="text-xl font-bold text-slate-800">No Stores Found</h2>
                    <a href="add_store.php" class="mt-4 px-6 py-2.5 rounded-lg bg-purple-600 text-white font-bold hover:bg-purple-700 transition">Create First Store</a>
                </div>
            <?php endif; ?>
        </div>
        <?php include('../includes/footer.php'); ?>
     </main>
     
</div>

<div id="storeModal" class="fixed inset-0 z-[100] hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity opacity-0" id="modalBackdrop" onclick="closeStoreModal()"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative w-full max-w-2xl rounded-2xl shadow-2xl transform transition-all opacity-0 scale-95 flex flex-col max-h-[90vh] bg-white border border-slate-300" id="modalPanel">
            <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between rounded-t-2xl z-20">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-lg bg-purple-600/10 text-purple-600 flex items-center justify-center text-lg shadow-sm"><i class="fas fa-store"></i></div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-800 leading-tight" id="m_storeName"></h3>
                        <div class="flex items-center gap-2 text-xs text-slate-500">
                            <span class="font-mono bg-slate-100 px-1.5 rounded" id="m_storeCode"></span>
                            <span id="m_status"></span>
                        </div>
                    </div>
                </div>
                <button onclick="closeStoreModal()" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-red-600 transition"><i class="fas fa-times"></i></button>
            </div>
            
            <div class="p-6 overflow-y-auto custom-scroll bg-white">
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div class="bg-gradient-to-br from-teal-900 via-teal-800 to-emerald-900 hover:to-emerald-800 rounded-xl p-4 text-white shadow-lg shadow-indigo-600/40">
                        <p class="text-[10px] font-bold text-white/50 uppercase">Daily Target</p>
                        <h4 class="text-2xl font-bold font-mono mt-1" id="m_target"></h4>
                    </div>
                    <div class="rounded-xl p-4 border border-slate-200 shadow-sm flex flex-col justify-center bg-slate-50">
                        <p class="text-[10px] font-bold text-slate-400 uppercase">Operating Hours</p>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            <h4 class="text-lg font-bold text-slate-700"><span id="m_openTime"></span> - <span id="m_closeTime"></span></h4>
                        </div>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-slate-700">
                    <div class="space-y-4">
                        <h4 class="text-xs font-bold text-slate-500 uppercase border-b border-slate-200 pb-1">Contact Details</h4>
                        <div class="flex items-start gap-3"><i class="fas fa-phone text-xs mt-1 text-slate-400"></i><p class="text-sm font-semibold text-slate-700" id="m_phone"></p></div>
                        <div class="flex items-start gap-3"><i class="fas fa-envelope text-xs mt-1 text-slate-400"></i><p class="text-sm font-semibold text-slate-700" id="m_email"></p></div>
                        <div class="flex items-start gap-3"><i class="fas fa-map-pin text-xs mt-1 text-slate-400"></i><p class="text-sm font-semibold text-slate-700" id="m_address"></p></div>
                    </div>
                    
                    <div class="space-y-4">
                        <h4 class="text-xs font-bold text-slate-500 uppercase border-b border-slate-200 pb-1">Configuration</h4>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="bg-slate-50 p-3 rounded-lg border border-slate-200 shadow-sm">
                                <p class="text-[10px] text-slate-400 font-bold uppercase">Max Disc</p>
                                <p class="text-sm font-bold text-purple-700 mt-0.5"><span id="m_invDisc"></span>%</p>
                            </div>
                            <div class="bg-slate-50 p-3 rounded-lg border border-slate-200 shadow-sm">
                                <p class="text-[10px] text-slate-400 font-bold uppercase">Low Stock</p>
                                <p class="text-sm font-bold text-rose-600 mt-0.5" id="m_lowStock"></p>
                            </div>
                        </div>
                        <div class="bg-slate-50 p-3 rounded-lg border border-slate-200 shadow-sm flex justify-between">
                            <p class="text-xs font-semibold text-slate-700">Overselling</p>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded uppercase" id="m_overselling"></span>
                        </div>
                        <div class="flex gap-2">
                            <span id="m_manualPriceBadge"></span>
                            <span id="m_backdateBadge"></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex flex-col-reverse sm:flex-row justify-between items-center gap-3 sm:gap-0 rounded-b-2xl">
                <button onclick="confirmDeleteFromModal()" class="text-red-600 hover:text-red-700 text-sm font-semibold flex items-center gap-2 transition"><i class="fas fa-trash"></i> Delete Store</button>
                <div class="flex gap-3">
                    <a href="#" id="m_editBtn" class="px-6 py-2 rounded-lg bg-gradient-to-br from-teal-900 via-teal-800 to-emerald-900 hover:to-emerald-800 text-white font-bold text-sm hover:bg-purple-700 shadow-lg shadow-purple-900/30 transition flex items-center gap-2">
                        <span>Edit Details</span> <i class="fas fa-arrow-right text-xs"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if(isset($_SESSION['message'])): ?>
<script>
    // SweetAlert colors adjusted for Light Mode appearance (assuming footer script handles overall style)
    Swal.fire({ title: '<?= $_SESSION['msg_type'] == "success" ? "Success!" : "Notice"; ?>', text: "<?= $_SESSION['message']; ?>", icon: '<?= $_SESSION['msg_type']; ?>', confirmButtonColor: '#7c3aed', timer: 2000, showConfirmButton: false, toast: true, position: 'top-end' });
</script>
<?php unset($_SESSION['message']); unset($_SESSION['msg_type']); endif; ?>

<script>
    // Modal & Badge Functions
    const modal = document.getElementById('storeModal');
    const modalBackdrop = document.getElementById('modalBackdrop');
    const modalPanel = document.getElementById('modalPanel');
    let currentStoreId = null;

    function openStoreModal(data) {
        currentStoreId = data.id;
        document.getElementById('m_storeName').textContent = data.store_name;
        document.getElementById('m_storeCode').textContent = data.store_code;
        const statusEl = document.getElementById('m_status');
        // Status colors adjusted for Light Mode
        statusEl.textContent = data.status == 1 ? 'ACTIVE' : 'INACTIVE';
        statusEl.className = data.status == 1 ? 'font-bold text-emerald-600' : 'font-bold text-red-600';
        
        const formatter = new Intl.NumberFormat('en-BD', { style: 'currency', currency: 'BDT', minimumFractionDigits: 0, maximumFractionDigits: 0 });
        document.getElementById('m_target').textContent = formatter.format(data.daily_target);
        document.getElementById('m_openTime').textContent = formatTime(data.open_time);
        document.getElementById('m_closeTime').textContent = formatTime(data.close_time);
        document.getElementById('m_phone').textContent = data.phone || 'N/A';
        document.getElementById('m_email').textContent = data.email || 'N/A';
        document.getElementById('m_address').textContent = (data.address || '') + ' ' + (data.city_zip || '');
        document.getElementById('m_invDisc').textContent = data.max_inv_disc || 0;
        document.getElementById('m_lowStock').textContent = data.low_stock || 0;
        
        const oversellEl = document.getElementById('m_overselling');
        let oversellText = 'STRICT';
        // Oversell badge colors adjusted for Light Mode
        let oversellClass = 'bg-rose-100 text-rose-700'; 
        if (data.overselling === 'allow') {
            oversellText = 'ALLOWED';
            oversellClass = 'bg-emerald-100 text-emerald-700';
        } else if (data.overselling === 'warning') {
            oversellText = 'WARNING';
            oversellClass = 'bg-amber-100 text-amber-700';
        }
        oversellEl.textContent = oversellText;
        oversellEl.className = `text-[10px] font-bold px-2 py-0.5 rounded uppercase ${oversellClass}`;

        updateBadge('m_manualPriceBadge', data.allow_manual_price, 'Manual Price');
        updateBadge('m_backdateBadge', data.allow_backdate, 'Backdate');
        document.getElementById('m_editBtn').href = 'add_store.php?id=' + data.id;
        modal.classList.remove('hidden');
        setTimeout(() => { modalBackdrop.classList.remove('opacity-0'); modalPanel.classList.remove('opacity-0', 'scale-95'); modalPanel.classList.add('modal-enter'); }, 10);
    }

    function updateBadge(id, value, text) {
        const el = document.getElementById(id);
        el.textContent = text;
        // Quick Rules Badge colors adjusted for Light Mode
        if(value == 1) { 
            el.className = "px-2 py-1 rounded border border-purple-200 bg-purple-100 text-purple-700 text-[10px] font-bold"; 
            el.style.opacity = "1"; 
        } 
        else { 
            el.className = "px-2 py-1 rounded border border-slate-200 bg-slate-100 text-slate-500 text-[10px] font-bold line-through opacity-70"; 
        }
    }

    function closeStoreModal() {
        modalBackdrop.classList.add('opacity-0');
        modalPanel.classList.remove('modal-enter');
        modalPanel.classList.add('modal-exit');
        setTimeout(() => { modal.classList.add('hidden'); modalPanel.classList.remove('modal-exit'); modalPanel.classList.add('opacity-0', 'scale-95'); }, 200);
    }

    function formatTime(time) {
        if(!time) return '--:--';
        const [h, m] = time.split(':');
        const hour = parseInt(h, 10);
        const ampm = hour >= 12 ? 'PM' : 'AM';
        const formattedHour = hour % 12 || 12;
        return `${formattedHour}:${m} ${ampm}`;
    }

    function confirmDeleteFromModal() { if(currentStoreId) confirmDelete(event, currentStoreId); }

    function confirmDelete(e, id) {
        if(e) e.stopPropagation();
        Swal.fire({ 
            title: 'Are you sure?', 
            text: "You won't be able to revert this!", 
            icon: 'warning', 
            showCancelButton: true, 
            confirmButtonColor: '#ef4444', 
            confirmButtonText: 'Yes, delete it!',
            // Custom styles for consistency with light mode theme
            background: '#ffffff',
            color: '#1e293b',
            customClass: {
                popup: 'border border-slate-200'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form'); form.method = 'POST'; form.action = 'save_store.php';
                const input = document.createElement('input'); input.type = 'hidden'; input.name = 'delete_id'; input.value = id;
                const btn = document.createElement('input'); btn.type = 'hidden'; btn.name = 'delete_store_btn'; btn.value = true;
                form.appendChild(input); form.appendChild(btn); document.body.appendChild(form); form.submit();
            }
        })
    }
</script>