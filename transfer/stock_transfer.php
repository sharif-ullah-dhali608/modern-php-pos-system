<?php
session_start();
include('../config/dbcon.php');

if (!isset($_SESSION['auth'])) {
    header("Location: /pos/login");
    exit(0);
}

$page_title = "Stock Transfer";
include('../includes/header.php');

$user_id = $_SESSION['auth_user']['user_id'];
$user_role = $_SESSION['auth_user']['role_as'];

// Fetch allowed stores for the user
$allowed_stores_q = mysqli_query($conn, "SELECT s.id, s.store_name FROM stores s 
                                       JOIN user_store_map usm ON s.id = usm.store_id 
                                       WHERE usm.user_id = '$user_id' AND s.status = '1'");

// Admins see all stores if no explicit mapping
if (mysqli_num_rows($allowed_stores_q) == 0 && $user_role == 'admin') {
    $allowed_stores_q = mysqli_query($conn, "SELECT id, store_name FROM stores WHERE status = '1'");
}

$allowed_stores = [];
while($row = mysqli_fetch_assoc($allowed_stores_q)) {
    $allowed_stores[] = $row;
}

// Fetch all active stores for "To Store" selection
$all_stores_q = mysqli_query($conn, "SELECT id, store_name FROM stores WHERE status = '1' ORDER BY store_name ASC");
$all_stores = [];
while($row = mysqli_fetch_assoc($all_stores_q)) {
    $all_stores[] = $row;
}

$default_from_store = !empty($allowed_stores) ? $allowed_stores[0]['id'] : null;
?>

<style>
    .glass-card {
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.5);
        box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.07);
    }
    
    .input-premium {
        background: #f8fafc;
        border: 2px solid #f1f5f9;
        border-radius: 1rem;
        padding: 0.75rem 1.25rem;
        font-weight: 700;
        color: #1e293b;
        transition: all 0.3s;
        width: 100%;
        outline: none;
    }
    
    .input-premium:focus {
        border-color: #0d9488;
        background: #ffffff;
        box-shadow: 0 0 0 4px rgba(13, 148, 136, 0.1);
    }
    
    .label-premium {
        display: block;
        font-size: 0.75rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #64748b;
        margin-bottom: 0.5rem;
        margin-left: 0.25rem;
    }

    .btn-teal { background: linear-gradient(to right, #0d9488, #059669); }
    .btn-rose { background: linear-gradient(to right, #f43f5e, #e11d48); }
    
    .btn-premium { 
        border: none; 
        outline: none; 
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        text-transform: uppercase;
        letter-spacing: 0.15em;
        font-weight: 900;
        display: flex;
        align-items: center;
        gap: 1rem;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        position: relative;
        overflow: hidden;
    }

    .btn-premium:hover {
        transform: translateY(-3px);
        box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.2);
    }

    .btn-premium:active {
        transform: translateY(1px) scale(0.98);
    }

    .btn-premium .btn-overlay {
        position: absolute;
        inset: 0;
        background: rgba(255, 255, 255, 0.2);
        transform: translateY(100%);
        transition: transform 0.5s ease;
    }

    .btn-premium:hover .btn-overlay {
        transform: translateY(0);
    }

    .search-container {
        position: relative;
    }
    
    .search-results {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        z-index: 50;
        background: white;
        border: 2px solid #0d9488;
        border-radius: 1.5rem;
        margin-top: 0.5rem;
        max-height: 300px;
        overflow-y: auto;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
    }

    .custom-scroll::-webkit-scrollbar { width: 5px; }
    .custom-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    
    .list-container {
        height: 450px;
        display: flex;
        flex-direction: column;
    }
    
    .list-scroll {
        flex: 1;
        overflow-y: auto;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
    }

    /* Animation */
    .slide-up { animation: slideUp 0.5s ease-out; }
    @keyframes slideUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Hide Number Arrows */
    input[type=number]::-webkit-inner-spin-button, 
    input[type=number]::-webkit-outer-spin-button { 
        -webkit-appearance: none; 
        margin: 0; 
    }
    input[type=number] { -moz-appearance: textfield; }

    /* Custom Dropdown Styling */
    .premium-dropdown-container {
        position: relative;
    }
    .premium-dropdown-trigger {
        background: #ffffff;
        border: 2px solid #f1f5f9;
        border-radius: 1.5rem;
        padding: 0.75rem 1.25rem;
        font-weight: 700;
        color: #1e293b;
        transition: all 0.3s;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: space-between;
        cursor: pointer;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }
    .premium-dropdown-trigger:hover {
        border-color: #0d9488;
        box-shadow: 0 4px 12px rgba(13, 148, 136, 0.05);
    }
    .premium-dropdown-trigger.active {
        border-color: #0d9488;
        box-shadow: 0 0 0 4px rgba(13, 148, 136, 0.1);
    }
    .premium-dropdown-menu {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        margin-top: 0.5rem;
        background: white;
        border: 2px solid #0d9488;
        border-radius: 1.5rem;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        z-index: 1000;
        max-height: 250px;
        overflow-y: auto;
        overflow-x: hidden;
    }
    .premium-dropdown-item {
        padding: 1rem 1.25rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        cursor: pointer;
        transition: all 0.2s;
        border-bottom: 1px solid #f1f5f9;
        font-weight: 700;
        color: #475569;
    }
    .premium-dropdown-item:last-child { border-bottom: none; }
    .premium-dropdown-item:hover {
        background: #f0fdfa;
        color: #0d9488;
    }
    .premium-dropdown-item.selected {
        background: #f0fdfa;
        color: #0d9488;
    }
    .store-initial {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: #f0fdfa;
        color: #0d9488;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 900;
        font-size: 0.75rem;
    }

    .dropdown-search-input {
        width: 100%;
        border: none;
        outline: none;
        font-weight: 700;
        padding: 0;
        background: transparent;
        color: #1e293b;
        font-size: 0.875rem;
    }
</style>

<div class="app-wrapper">
    <?php include('../includes/sidebar.php'); ?>
    
    <main id="main-content" class="flex-1 lg:ml-64 main-content flex flex-col h-screen min-w-0 transition-all duration-300 bg-[#f1f5f9]">        
        <div class="navbar-fixed-top">
            <?php include('../includes/navbar.php'); ?>
        </div>
        
        <div class="content-scroll-area custom-scroll h-full overflow-y-auto">
            <div class="p-4 md:p-8 max-w-[1600px] mx-auto">
                
                <div class="mb-8 slide-up">
                    <div class="flex items-center gap-4 mb-2">
                        <div class="w-12 h-12 bg-teal-600 rounded-2xl flex items-center justify-center shadow-lg shadow-teal-200">
                            <i class="fas fa-exchange-alt text-white text-xl"></i>
                        </div>
                        <div>
                            <h1 class="text-3xl font-black text-slate-900 tracking-tight">Stock Transfer</h1>
                            <p class="text-slate-500 font-medium">Move products between your store branches securely.</p>
                        </div>
                    </div>
                </div>

                <form id="transfer_form" enctype="multipart/form-data">
                    <!-- Top Controls Card -->
                    <div class="glass-card rounded-[2.5rem] p-8 mb-8 slide-up relative z-[60]" style="animation-delay: 0.1s">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                            <!-- Left Column: Attachment Only -->
                            <div class="flex flex-col gap-0">
                                <!-- Attachment -->
                                <div class="flex flex-col items-center justify-center border-2 border-dashed border-slate-200 rounded-[2rem] p-4 hover:border-teal-500 transition-colors group cursor-pointer relative bg-slate-50/30 min-h-[520px]" onclick="document.getElementById('attachment').click()">
                                    <input type="file" id="attachment" name="attachment" class="hidden" onchange="previewFile(this)">
                                    <div id="preview_container" class="flex flex-col items-center py-12">
                                        <div class="w-16 h-22 bg-white rounded-2xl flex items-center justify-center shadow-sm text-slate-300 group-hover:text-teal-500 transition-colors mb-4 border border-slate-100">
                                            <i class="fas fa-cloud-upload-alt text-2xl"></i>
                                        </div>
                                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] text-center">Reference Attachment</span>
                                        <p class="text-[9px] text-slate-300 mt-2 font-bold italic">IMG, PDF or DOC</p>
                                    </div>
                                    <img id="file_preview" class="hidden absolute inset-0 w-full h-full object-contain p-4 rounded-3xl bg-white">
                                </div>
                                
                            </div>

                            <!-- Right Column: Info, Stores, Status & Note -->
                            <div class="flex flex-col gap-4">
                                  <!-- Ref No -->
                                <div class="mb-1" style="margin-top: 1rem;">
                                    <label class="label-premium">Ref No</label>
                                    <input type="text" name="ref_no" class="input-premium" placeholder="TR-<?= date('YmdHis'); ?>">
                                </div>
                              

                                <!-- Status -->
                                <div class="premium-dropdown-container" id="status_dropdown_container">
                                    <label class="label-premium">Status <span class="text-rose-500">*</span></label>
                                    <div class="premium-dropdown-trigger" id="status_trigger">
                                        <span id="status_display">Sent</span>
                                        <i class="fas fa-chevron-down text-[10px] text-slate-300"></i>
                                    </div>
                                    <input type="hidden" name="status" id="status_input" value="Sent">
                                    <div class="premium-dropdown-menu hidden" id="status_menu">
                                        <div class="premium-dropdown-item selected" data-value="Sent">Sent</div>
                                        <div class="premium-dropdown-item" data-value="Received">Received</div>
                                    </div>
                                </div>

                                <!-- From Store -->
                                <div class="premium-dropdown-container" id="from_store_container">
                                    <label class="label-premium">From (Source) <span class="text-rose-500">*</span></label>
                                    <div class="premium-dropdown-trigger" id="from_store_trigger">
                                        <div class="flex items-center gap-2 flex-1">
                                            <i class="fas fa-store-alt text-slate-400 text-xs"></i>
                                            <input type="text" class="dropdown-search-input" id="from_store_search" placeholder="Search Source Store..." value="<?= htmlspecialchars($allowed_stores[0]['store_name'] ?? ''); ?>" autocomplete="off">
                                        </div>
                                        <i class="fas fa-chevron-down text-[10px] text-slate-300"></i>
                                    </div>
                                    <input type="hidden" name="from_store_id" id="from_store_id" value="<?= htmlspecialchars($allowed_stores[0]['id'] ?? ''); ?>" required>
                                    <div class="premium-dropdown-menu hidden" id="from_store_menu">
                                        <div class="p-1 custom-scroll scroll-teal" id="from_store_results">
                                            <?php foreach($allowed_stores as $s): ?>
                                                <div class="premium-dropdown-item" data-id="<?= $s['id']; ?>" data-name="<?= htmlspecialchars($s['store_name']); ?>">
                                                    <div class="store-initial"><?= strtoupper(substr($s['store_name'], 0, 1)); ?></div>
                                                    <span><?= htmlspecialchars($s['store_name']); ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- To Store -->
                                <div class="premium-dropdown-container" id="to_store_container">
                                    <label class="label-premium">To (Destination) <span class="text-rose-500">*</span></label>
                                    <div class="premium-dropdown-trigger" id="to_store_trigger">
                                        <div class="flex items-center gap-2 flex-1">
                                            <i class="fas fa-map-marker-alt text-slate-400 text-xs"></i>
                                            <input type="text" class="dropdown-search-input" id="to_store_search" placeholder="Select Target..." autocomplete="off">
                                        </div>
                                        <i class="fas fa-chevron-down text-[10px] text-slate-300"></i>
                                    </div>
                                    <input type="hidden" name="to_store_id" id="to_store_id" value="" required>
                                    <div class="premium-dropdown-menu hidden" id="to_store_menu">
                                        <div class="p-1 custom-scroll scroll-teal" id="to_store_results">
                                            <?php foreach($all_stores as $s): ?>
                                                <div class="premium-dropdown-item" data-id="<?= $s['id']; ?>" data-name="<?= htmlspecialchars($s['store_name']); ?>">
                                                    <div class="store-initial"><?= strtoupper(substr($s['store_name'], 0, 1)); ?></div>
                                                    <span><?= htmlspecialchars($s['store_name']); ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Note (Moved here) -->
                                <div class="mt-2 flex-1" >
                                    <label class="label-premium">Note</label>
                                    <textarea name="note" class="input-premium h-20 min-h-[100px]" rows="2" placeholder="Optional comments..."></textarea>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Main Transfer Section -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                        
                        <!-- Left: Stock List -->
                        <div class="glass-card rounded-[2.5rem] p-8 slide-up relative z-10" style="animation-delay: 0.2s">
                            <div class="flex items-center justify-between mb-6">
                                <h3 class="text-xl font-black text-slate-800 tracking-tight">Stock List</h3>
                                <div class="px-4 py-1.5 bg-slate-100 rounded-full text-[10px] font-black text-slate-500 uppercase tracking-widest">
                                    Source Inventory
                                </div>
                            </div>

                            <div class="search-container mb-6">
                                <div class="relative">
                                    <div class="absolute left-6 top-1/2 -translate-y-1/2 text-slate-400">
                                        <i class="fas fa-search"></i>
                                    </div>
                                    <input 
                                        type="text" 
                                        id="product_search" 
                                        placeholder="Search product by name or barcode..." 
                                        class="w-full bg-slate-50 border-2 border-transparent focus:border-teal-500 rounded-2xl pl-14 pr-8 py-4 outline-none font-bold text-slate-700 placeholder:text-slate-300 transition-all shadow-sm"
                                        autocomplete="off"
                                    >
                                </div>
                                <div id="search_results" class="search-results hidden custom-scroll"></div>
                            </div>

                            <div class="list-container">
                                <div id="stock_list_results" class="list-scroll custom-scroll">
                                    <!-- AJAX results will be injected here -->
                                    <div class="p-12 text-center" id="stock_placeholder">
                                        <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4">
                                            <i class="fas fa-search text-slate-300 text-2xl"></i>
                                        </div>
                                        <p class="text-slate-400 font-bold">Search or select a store to see products</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Transfer List -->
                        <div class="glass-card rounded-[2.5rem] p-8 slide-up relative z-10" style="animation-delay: 0.3s">
                            <div class="flex items-center justify-between mb-6">
                                <h3 class="text-xl font-black text-slate-800 tracking-tight">Transfer List</h3>
                                <div id="item_count" class="px-4 py-1.5 bg-teal-500 rounded-full text-[10px] font-black text-white uppercase tracking-widest">
                                    0 Items Selected
                                </div>
                            </div>

                            <div class="overflow-hidden rounded-2xl border border-slate-100 mb-4">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="bg-slate-50 text-[10px] uppercase font-black tracking-widest text-slate-400 border-b border-slate-100">
                                            <th class="px-6 py-4">Item Name</th>
                                            <th class="px-6 py-4 text-center">Transfer Qty</th>
                                            <th class="px-6 py-4 text-right">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="transfer_items_list">
                                        <tr id="empty_row">
                                            <td colspan="3" class="px-6 py-20 text-center text-slate-300 font-bold italic">
                                                No items added yet...
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-wrap items-center justify-center gap-8 slide-up" style="animation-delay: 0.4s">
                        <button type="submit" id="submit_btn" class="btn-premium btn-teal text-white py-5 px-12 rounded-[2.5rem] shadow-xl">
                            <div class="btn-overlay"></div>
                            <i class="fas fa-paper-plane relative z-10 group-hover:animate-pulse"></i>
                            <span class="text-xs relative z-10">Transfer Now →</span>
                        </button>
                        
                        <button type="button" onclick="resetForm()" class="btn-premium btn-rose text-white py-5 px-12 rounded-[2.5rem] shadow-xl">
                            <div class="btn-overlay"></div>
                            <i class="fas fa-redo-alt relative z-10"></i>
                            <span class="text-xs relative z-10">Reset</span>
                        </button>
                    </div>

                </form>

            </div>
        </div>
        <?php include('../includes/footer.php'); ?>
    </main>
</div>

<script>
    let addedItems = new Set();

    // Custom Dropdowns JS
    function closeAllDropdowns() {
        document.querySelectorAll('.premium-dropdown-menu').forEach(m => m.classList.add('hidden'));
        document.querySelectorAll('.premium-dropdown-trigger').forEach(t => t.classList.remove('active'));
    }

    const statusTrigger = document.getElementById('status_trigger');
    const statusMenu = document.getElementById('status_menu');
    const statusInput = document.getElementById('status_input');
    const statusDisplay = document.getElementById('status_display');

    statusTrigger.addEventListener('click', (e) => {
        e.stopPropagation();
        const isHidden = statusMenu.classList.contains('hidden');
        closeAllDropdowns();
        if (isHidden) {
            statusMenu.classList.remove('hidden');
            statusTrigger.classList.add('active');
        }
    });

    statusMenu.querySelectorAll('.premium-dropdown-item').forEach(item => {
        item.addEventListener('click', function() {
            statusMenu.querySelectorAll('.premium-dropdown-item').forEach(i => i.classList.remove('selected'));
            this.classList.add('selected');
            statusInput.value = this.dataset.value;
            statusDisplay.innerText = this.dataset.value;
            statusMenu.classList.add('hidden');
            statusTrigger.classList.remove('active');
        });
    });

    // From Store Selector
    const fromStoreSearch = document.getElementById('from_store_search');
    const fromStoreMenu = document.getElementById('from_store_menu');
    const fromStoreInput = document.getElementById('from_store_id');
    const allowedStores = <?= json_encode($allowed_stores); ?>;

    fromStoreSearch.addEventListener('focus', (e) => {
        const isHidden = fromStoreMenu.classList.contains('hidden');
        closeAllDropdowns();
        if (isHidden) {
            fromStoreMenu.classList.remove('hidden');
            document.getElementById('from_store_trigger').classList.add('active');
        }
        fromStoreSearch.select();
    });

    fromStoreSearch.addEventListener('keydown', function(e) {
        if ((e.key === 'Backspace' || e.key === 'Delete') && this.value !== '') {
            this.value = '';
            this.dispatchEvent(new Event('input'));
        }
    });

    fromStoreSearch.addEventListener('input', function() {
        const val = this.value.toLowerCase();
        const otherStoreId = toStoreInput.value;
        const results = allowedStores.filter(s => s.store_name.toLowerCase().includes(val) && s.id != otherStoreId);
        renderStoreResults('from_store_results', results, (id, name) => {
            fromStoreInput.value = id;
            fromStoreSearch.value = name;
            fromStoreMenu.classList.add('hidden');
            document.getElementById('from_store_trigger').classList.remove('active');
            clearLists();
            fetchStock();
        });
    });

    // To Store Selector
    const toStoreSearch = document.getElementById('to_store_search');
    const toStoreMenu = document.getElementById('to_store_menu');
    const toStoreInput = document.getElementById('to_store_id');
    const allStores = <?= json_encode($all_stores); ?>;

    toStoreSearch.addEventListener('focus', (e) => {
        const isHidden = toStoreMenu.classList.contains('hidden');
        closeAllDropdowns();
        if (isHidden) {
            toStoreMenu.classList.remove('hidden');
            document.getElementById('to_store_trigger').classList.add('active');
            // Re-render to ensure current source is excluded
            const val = toStoreSearch.value.toLowerCase();
            const fromId = fromStoreInput.value;
            const results = allStores.filter(s => s.store_name.toLowerCase().includes(val) && s.id != fromId);
            renderStoreResults('to_store_results', results, (id, name) => {
                toStoreInput.value = id;
                toStoreSearch.value = name;
                toStoreMenu.classList.add('hidden');
                document.getElementById('to_store_trigger').classList.remove('active');
            });
        }
        toStoreSearch.select();
    });

    toStoreSearch.addEventListener('keydown', function(e) {
        if ((e.key === 'Backspace' || e.key === 'Delete') && this.value !== '') {
            this.value = '';
            this.dispatchEvent(new Event('input'));
        }
    });

    toStoreSearch.addEventListener('input', function() {
        const val = this.value.toLowerCase();
        const fromId = fromStoreInput.value;
        const results = allStores.filter(s => s.store_name.toLowerCase().includes(val) && s.id != fromId);
        renderStoreResults('to_store_results', results, (id, name) => {
            toStoreInput.value = id;
            toStoreSearch.value = name;
            toStoreMenu.classList.add('hidden');
            document.getElementById('to_store_trigger').classList.remove('active');
        });
    });

    function renderStoreResults(containerId, data, onSelect) {
        const container = document.getElementById(containerId);
        container.innerHTML = '';
        data.forEach(s => {
            const item = document.createElement('div');
            item.className = 'premium-dropdown-item';
            item.innerHTML = `<div class="store-initial">${s.store_name.charAt(0).toUpperCase()}</div><span>${s.store_name}</span>`;
            item.addEventListener('click', () => onSelect(s.id, s.store_name));
            container.appendChild(item);
        });
    }

    // Initial click attachment for pre-rendered items
    document.querySelectorAll('#from_store_results .premium-dropdown-item').forEach(item => {
        item.addEventListener('click', function() {
            fromStoreInput.value = this.dataset.id;
            fromStoreSearch.value = this.dataset.name;
            fromStoreMenu.classList.add('hidden');
            document.getElementById('from_store_trigger').classList.remove('active');
            clearLists();
            fetchStock();
        });
    });

    document.querySelectorAll('#to_store_results .premium-dropdown-item').forEach(item => {
        item.addEventListener('click', function() {
            toStoreInput.value = this.dataset.id;
            toStoreSearch.value = this.dataset.name;
            toStoreMenu.classList.add('hidden');
            document.getElementById('to_store_trigger').classList.remove('active');
        });
    });

    // Close dropdowns on outside click
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.premium-dropdown-container')) {
            document.querySelectorAll('.premium-dropdown-menu').forEach(m => m.classList.add('hidden'));
            document.querySelectorAll('.premium-dropdown-trigger').forEach(t => t.classList.remove('active'));
        }
    });

    // Product Search Logic
    const searchInput = document.getElementById('product_search');
    const stockListResults = document.getElementById('stock_list_results');
    let searchTimeout;

    function fetchStock(query = '') {
        const storeId = document.getElementById('from_store_id').value;
        if (!storeId) return;

        fetch(`search_stock?q=${query}&store_id=${storeId}`)
            .then(res => res.text())
            .then(html => {
                stockListResults.innerHTML = html;
                attachOptionClick();
                syncStockList(); // Sync after fetching new results
            });
    }

    // Initial load
    document.addEventListener('DOMContentLoaded', () => {
        fetchStock();
    });

    // On Store Change (Already handled in dropdown select)

    searchInput.addEventListener('input', function() {
        const query = this.value.trim();
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            fetchStock(query);
        }, 300);
    });

    function attachOptionClick() {
        document.querySelectorAll('.product-option').forEach(option => {
            option.removeEventListener('click', optionClickHandler); // Clear prev listeners
            option.addEventListener('click', optionClickHandler);
        });
    }

    function optionClickHandler() {
        const id = this.dataset.id;
        const name = this.dataset.name;
        const code = this.dataset.code;
        const stock = parseFloat(this.dataset.stock);

        // Store original stock if not already stored
        if (!this.originalStock) this.originalStock = stock;
        
        addItemToTransfer(id, name, code, stock);
        // We don't hide the list anymore, it's a permanent display
    }

    function addItemToTransfer(id, name, code, stock) {
        if (addedItems.has(id)) {
            Swal.fire({
                icon: 'warning',
                title: 'Already Added',
                text: 'This item is already in your transfer list.',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
            return;
        }

        const emptyRow = document.getElementById('empty_row');
        if (emptyRow) emptyRow.remove();

        const row = document.createElement('tr');
        row.className = 'border-b border-slate-50 hover:bg-slate-50/50 transition-colors group';
        row.id = `item_row_${id}`;
        row.innerHTML = `
            <td class="px-6 py-4">
                <div class="font-bold text-slate-700">${name}</div>
                <div class="text-[10px] text-slate-400 font-black uppercase tracking-widest">${code}</div>
                <input type="hidden" name="product_id[]" value="${id}">
                <input type="hidden" name="product_name[]" value="${name}">
            </td>
            <td class="px-6 py-4">
                <div class="flex items-center justify-center gap-3">
                    <button type="button" onclick="updateQty('${id}', -1)" class="w-8 h-8 rounded-lg border border-slate-200 flex items-center justify-center text-slate-400 hover:bg-white hover:text-teal-600 hover:border-teal-200 transition-all">-</button>
                    <input type="number" name="quantity[]" id="qty_${id}" value="1" min="1" max="${stock}" 
                           class="w-20 bg-white border border-slate-200 rounded-lg px-2 py-1.5 text-center font-bold text-slate-700 outline-none focus:border-teal-500"
                           onchange="validateQty('${id}', ${stock})" onfocus="this.select()">
                    <button type="button" onclick="updateQty('${id}', 1, ${stock})" class="w-8 h-8 rounded-lg border border-slate-200 flex items-center justify-center text-slate-400 hover:bg-white hover:text-teal-600 hover:border-teal-200 transition-all">+</button>
                </div>
                <div class="text-[9px] text-center mt-1 text-slate-400 font-bold uppercase">Max: ${stock}</div>
            </td>
            <td class="px-6 py-4 text-right">
                <button type="button" onclick="removeItem('${id}')" class="w-10 h-10 rounded-xl bg-rose-50 text-rose-500 flex items-center justify-center mx-auto hover:bg-rose-500 hover:text-white transition-all shadow-sm">
                    <i class="fas fa-trash-alt text-xs"></i>
                </button>
            </td>
        `;

        document.getElementById('transfer_items_list').appendChild(row);
        addedItems.add(id);
        updateItemCount();
        syncStockList(); // Sync after adding

        // Auto selection of the 1
        const input = document.getElementById(`qty_${id}`);
        input.focus();
        input.select();
    }

    function updateQty(id, delta, max) {
        const input = document.getElementById(`qty_${id}`);
        let val = parseFloat(input.value) + delta;
        if (val < 1) val = 1;
        if (max !== undefined && val > max) val = max;
        input.value = val;
        syncStockList(); // Sync after qty update
    }

    function validateQty(id, max) {
        const input = document.getElementById(`qty_${id}`);
        let val = parseFloat(input.value);
        if (isNaN(val) || val < 1) val = 1;
        if (val > max) {
            val = max;
            Swal.fire({
                icon: 'error',
                title: 'Insufficient Stock',
                text: `Maximum available stock is ${max}`,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
        }
        input.value = val;
        syncStockList(); // Sync after validation
    }

    function removeItem(id) {
        document.getElementById(`item_row_${id}`).remove();
        addedItems.delete(id);
        updateItemCount();
        syncStockList(); // Sync after removal
        
        if (addedItems.size === 0) {
            document.getElementById('transfer_items_list').innerHTML = `
                <tr id="empty_row">
                    <td colspan="3" class="px-6 py-20 text-center text-slate-300 font-bold italic">
                        No items added yet...
                    </td>
                </tr>
            `;
        }
    }

    function updateItemCount() {
        const count = addedItems.size;
        document.getElementById('item_count').innerText = `${count} ITEM${count !== 1 ? 'S' : ''} SELECTED`;
    }

    function clearLists() {
        addedItems.clear();
        document.getElementById('transfer_items_list').innerHTML = `
            <tr id="empty_row">
                <td colspan="3" class="px-6 py-20 text-center text-slate-300 font-bold italic">
                    No items added yet...
                </td>
            </tr>
        `;
        updateItemCount();
        syncStockList(); // Sync after clearing
    }

    function syncStockList() {
        // Look through all items in the transfer list
        const transferRows = document.querySelectorAll('#transfer_items_list tr[id^="item_row_"]');
        
        // Reset all visible stock counters to their original value first (or what's in their data-stock attribute)
        document.querySelectorAll('.product-option').forEach(option => {
            const id = option.dataset.id;
            const originalStock = parseFloat(option.dataset.stock);
            const stockDisplay = document.getElementById(`stock_qty_val_${id}`);
            if (stockDisplay) {
                stockDisplay.innerText = originalStock;
            }
        });

        // Now subtract transfer quantities from the stock list display
        transferRows.forEach(row => {
            const id = row.id.replace('item_row_', '');
            const transferQty = parseFloat(document.getElementById(`qty_${id}`).value) || 0;
            const stockDisplay = document.getElementById(`stock_qty_val_${id}`);
            
            if (stockDisplay) {
                const option = document.querySelector(`.product-option[data-id="${id}"]`);
                if (option) {
                    const originalStock = parseFloat(option.dataset.stock);
                    const newStock = originalStock - transferQty;
                    stockDisplay.innerText = Math.max(0, newStock);
                }
            }
        });
    }

    function previewFile(input) {
        const container = document.getElementById('preview_container');
        const preview = document.getElementById('file_preview');
        const file = input.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.classList.remove('hidden');
                container.classList.add('opacity-0');
            }
            reader.readAsDataURL(file);
        }
    }

    function resetForm() {
        document.getElementById('transfer_form').reset();
        document.getElementById('file_preview').classList.add('hidden');
        document.getElementById('preview_container').classList.remove('opacity-0');
        clearLists();
    }

    // Handle Form Submission
    document.getElementById('transfer_form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const fromStore = document.getElementById('from_store_id').value;
        const toStore = document.getElementById('to_store_id').value;
        
        if (!toStore) {
            Swal.fire('Error', 'Please select a destination store.', 'error');
            return;
        }
        
        if (fromStore === toStore) {
            Swal.fire('Error', 'Source and target stores cannot be the same.', 'error');
            return;
        }

        if (addedItems.size === 0) {
            Swal.fire('Error', 'Please add at least one item to transfer.', 'error');
            return;
        }

        const formData = new FormData(this);
        const submitBtn = document.getElementById('submit_btn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<div class="btn-overlay"></div><i class="fas fa-spinner fa-spin relative z-10"></i> <span class="text-xs relative z-10">Processing...</span>';

        fetch('save', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 200) {
                Swal.fire({
                    icon: 'success',
                    title: 'Transfer Successful!',
                    text: data.message,
                    confirmButtonColor: '#0d9488'
                }).then(() => {
                    window.location.href = 'transfer_list';
                });
            } else {
                Swal.fire('Error', data.message || 'Something went wrong', 'error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<div class="btn-overlay"></div><i class="fas fa-paper-plane relative z-10"></i> <span class="text-xs relative z-10">Transfer Now →</span>';
            }
        })
        .catch(err => {
            console.error(err);
            Swal.fire('Error', 'Connection failed', 'error');
            const submitBtn = document.getElementById('submit_btn');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<div class="btn-overlay"></div><i class="fas fa-paper-plane relative z-10"></i> <span class="text-xs relative z-10">Transfer Now →</span>';
        });
    });

    // Close results when clicking outside (Removed as results are now inline)
</script>

