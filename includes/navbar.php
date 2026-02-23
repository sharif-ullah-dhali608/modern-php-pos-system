<nav id="main-navbar" class="relative bg-[#fcfdfd]/95 backdrop-blur-lg border-b border-slate-200 top-0 z-[10000] shadow-sm transition-all duration-300 cubic-bezier(0.4, 0, 0.2, 1)">

    <style>
    @keyframes gradient-x {
        0%, 100% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
    }
    .animate-gradient-x {
        background-size: 200% 200%;
        animation: gradient-x 6s ease infinite;
    }
    </style>

    <div class="px-4 md:px-6 py-4 h-[80px]">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-3 flex-1">
                <button 
                    class="lg:hidden w-10 h-10 rounded-lg bg-white border border-slate-200 hover:bg-slate-50 flex items-center justify-center text-slate-700 transition-all shadow-sm"
                    onclick="toggleSidebarMobile()" 
                    type="button"
                    >
                    <i class="fas fa-bars"></i>
                </button>

            <?php
            // --- Store Selection Modal Logic ---
            $current_store_id = isset($_SESSION['store_id']) ? $_SESSION['store_id'] : 0;
            $curr_store_name = 'select_store';
            
            // Get current store name quickly
            if($current_store_id) {
                $c_q = mysqli_query($conn, "SELECT store_name FROM stores WHERE id='$current_store_id'");
                if($c = mysqli_fetch_assoc($c_q)) $curr_store_name = $c['store_name'];
            }
            
            // Check if we MUST open the modal (First Login)
            $must_select = isset($_SESSION['must_select_store']) && $_SESSION['must_select_store'] === true;

            // Check if user has access to multiple stores (or is admin)
            $can_switch_store = false;
            $u_id = $_SESSION['auth_user']['user_id'] ?? 0;
            $u_role = $_SESSION['auth_user']['role_as'] ?? '';

            if($u_role === 'admin') {
                $can_switch_store = true;
            } else {
                $store_count_q = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM user_store_map WHERE user_id='$u_id'");
                $store_cnt = 0;
                if($r = mysqli_fetch_assoc($store_count_q)) $store_cnt = $r['cnt'];
                
                // If staff has > 1 store, they can switch
                if($store_cnt > 1) $can_switch_store = true;
            }

            // --- Comprehensive Notification System: Data Fetching ---
            $check_store_id = $_SESSION['store_id'] ?? 0;
            $notifications = [
                'low_stock' => ['count' => 0, 'items' => [], 'icon' => 'fa-exclamation-triangle', 'color' => 'rose', 'title' => 'low_stock', 'link' => '/pos/products/stock_alert'],
                'cust_due' => ['count' => 0, 'items' => [], 'icon' => 'fa-user-clock', 'color' => 'orange', 'title' => 'customer_dues', 'link' => '/pos/reports/due-collection'],
                'sup_due' => ['count' => 0, 'items' => [], 'icon' => 'fa-file-invoice-dollar', 'color' => 'amber', 'title' => 'supplier_payments', 'link' => '/pos/purchases/list'],
                'installments' => ['count' => 0, 'items' => [], 'icon' => 'fa-calendar-alt', 'color' => 'blue', 'title' => 'upcoming_installments', 'link' => '/pos/installment/list'],
                'logs' => ['count' => 0, 'items' => [], 'icon' => 'fa-history', 'color' => 'slate', 'title' => 'security_logs', 'link' => '/pos/reports/overview']
            ];

            // Helper: Time Ago
            if(!function_exists('notifTimeAgo')) {
                function notifTimeAgo($timestamp) {
                    if(!is_numeric($timestamp)) $timestamp = strtotime($timestamp);
                    $diff = time() - $timestamp;
                    if ($diff < 1) return 'now';
                    if ($diff < 60) return $diff . 's';
                    if ($diff < 3600) return floor($diff / 60) . 'm';
                    if ($diff < 86400) return floor($diff / 3600) . 'h';
                    return date('d M', $timestamp);
                }
            }

            if ($check_store_id) {
                // 1. Low Stock Alerts
                $low_stock_q = mysqli_query($conn, "SELECT p.product_name, psm.stock, p.alert_quantity FROM products p JOIN product_store_map psm ON p.id = psm.product_id WHERE psm.store_id = '$check_store_id' AND p.status = 1 AND psm.stock <= p.alert_quantity ORDER BY psm.stock ASC LIMIT 3");
                while ($r = mysqli_fetch_assoc($low_stock_q)) $notifications['low_stock']['items'][] = ['title' => $r['product_name'], 'sub' => "Stock: " . $r['stock'] . " (Limit: " . $r['alert_quantity'] . ")", 'time' => ''];
                $notifications['low_stock']['count'] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM products p JOIN product_store_map psm ON p.id = psm.product_id WHERE psm.store_id = '$check_store_id' AND p.status = 1 AND psm.stock <= p.alert_quantity"))['total'] ?? 0;

                // 2. Customer Dues
                $cust_due_q = mysqli_query($conn, "SELECT name, current_due FROM customers WHERE current_due > 0 ORDER BY current_due DESC LIMIT 3");
                while ($r = mysqli_fetch_assoc($cust_due_q)) $notifications['cust_due']['items'][] = ['title' => $r['name'], 'sub' => "Due: " . number_format($r['current_due'], 2), 'time' => ''];
                $notifications['cust_due']['count'] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM customers WHERE current_due > 0"))['total'] ?? 0;

                // 3. Supplier Dues
                $sup_due_q = mysqli_query($conn, "SELECT s.name, pi.invoice_id, pi.total_sell FROM purchase_info pi LEFT JOIN suppliers s ON pi.sup_id = s.id WHERE pi.payment_status = 'due' AND pi.store_id = '$check_store_id' ORDER BY pi.info_id DESC LIMIT 3");
                while ($r = mysqli_fetch_assoc($sup_due_q)) $notifications['sup_due']['items'][] = ['title' => ($r['name'] ?? 'Inv: #'.$r['invoice_id']), 'sub' => "Amt: " . number_format($r['total_sell'], 2), 'time' => ''];
                $notifications['sup_due']['count'] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM purchase_info WHERE payment_status = 'due' AND store_id = '$check_store_id'"))['total'] ?? 0;

                // 4. Upcoming Installments (Due within 7 days)
                $inst_q = mysqli_query($conn, "SELECT invoice_id, payable FROM installment_payments WHERE payment_status = 'due' AND store_id = '$check_store_id' AND payment_date <= DATE_ADD(NOW(), INTERVAL 7 DAY) ORDER BY payment_date ASC LIMIT 3");
                while ($r = mysqli_fetch_assoc($inst_q)) $notifications['installments']['items'][] = ['title' => "Inv: #".$r['invoice_id'], 'sub' => "Payable: " . number_format($r['payable'], 2), 'time' => ''];
                $notifications['installments']['count'] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM installment_payments WHERE payment_status = 'due' AND store_id = '$check_store_id' AND payment_date <= DATE_ADD(NOW(), INTERVAL 7 DAY)"))['total'] ?? 0;

                // 5. Activity Logs (Security/Recent)
                $logs_q = mysqli_query($conn, "SELECT pmethod_id, type, amount, created_at FROM sell_logs WHERE store_id = '$check_store_id' ORDER BY created_at DESC LIMIT 3");
                while ($r = mysqli_fetch_assoc($logs_q)) $notifications['logs']['items'][] = ['title' => ucfirst(str_replace('_', ' ', $r['type'])), 'sub' => "Amt: " . number_format($r['amount'], 2), 'time' => notifTimeAgo($r['created_at'])];
                $notifications['logs']['count'] = 3; // Always show latest 3 logs for awareness
            }

            $total_notif_count = array_sum(array_column($notifications, 'count'));
            
            // Interaction classes
            $btn_interaction = $can_switch_store ? 'onclick="openStoreModal(false)" class="group flex flex-col justify-center items-start text-left focus:outline-none cursor-pointer"' : 'class="flex flex-col justify-center items-start text-left cursor-default"';
            $hover_card = $can_switch_store ? 'group-hover:bg-white group-hover:border-teal-100 group-hover:shadow-md' : '';
            $hover_icon = $can_switch_store ? 'group-hover:scale-110' : '';
            $hover_text = $can_switch_store ? 'group-hover:text-teal-600' : '';
            ?>
            
            <!-- Store Trigger Button (Left Navbar) -->
            <div class="flex-1 px-4">
                <button <?= $btn_interaction; ?>>
                    <div class="store-trigger flex items-center gap-3 px-4 py-2 rounded-2xl transition-all border border-transparent <?= $hover_card; ?>">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-teal-400 to-emerald-500 flex items-center justify-center text-white shadow-lg shadow-teal-500/30 transition-transform duration-300 <?= $hover_icon; ?>">
                            <i class="fas fa-store-alt text-lg"></i>
                        </div>
                        <div>
                            <div class="text-[10px] uppercase font-black text-teal-600/80 tracking-widest transition-colors <?= $hover_text; ?>">Current Workspace</div>
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-black text-slate-800 truncate max-w-[150px] md:max-w-[200px]" id="nav_store_name"><?= htmlspecialchars($curr_store_name); ?></span>
                                <?php if($can_switch_store): ?>
                                <i class="fas fa-chevron-right text-[10px] text-slate-300 group-hover:text-teal-500 transition-all group-hover:translate-x-1"></i>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </button>
            </div>
            
            <!-- STORE SELECTION MODAL -->
            <div id="storeSelectionModal" class="fixed inset-0 z-[50000] hidden" style="z-index: 50000 !important;">
                <!-- Backdrop with Light Blur -->
                <div class="absolute inset-0 bg-slate-900/30 backdrop-blur-sm transition-opacity duration-300"></div>
                
                <!-- Modal Content -->
                <div class="flex h-full w-full items-center justify-center p-4">
                    <div class="relative w-full max-w-lg transform overflow-hidden rounded-[2.5rem] bg-white shadow-2xl transition-all duration-300 scale-95 opacity-0 modal-content border-2 border-white/50 ring-1 ring-slate-200">
                        
                        <!-- Premium Animated Header (Compact) -->
                        <div class="bg-gradient-to-r from-teal-500 via-emerald-500 to-teal-600 animate-gradient-x p-6 text-center relative overflow-hidden">
                            <!-- Decorative Circles -->
                            <div class="absolute top-[-20%] left-[-10%] w-24 h-24 rounded-full bg-white/10 blur-xl"></div>
                            <div class="absolute bottom-[-20%] right-[-10%] w-32 h-32 rounded-full bg-white/10 blur-xl"></div>
                            
                            <div class="w-14 h-14 bg-white/20 rounded-[1rem] flex items-center justify-center mx-auto mb-3 backdrop-blur-md border border-white/20 shadow-lg shadow-teal-900/10">
                                <i class="fas fa-store text-2xl text-white drop-shadow-md"></i>
                            </div>
                            <h2 class="text-xl font-black text-white tracking-tight drop-shadow-sm">Select Store Access</h2>
                            <p class="text-teal-50 text-[10px] font-bold uppercase tracking-[0.2em] mt-2 opacity-90">Choose your workspace</p>
                        </div>
                        
                        <!-- Search & List -->
                        <div class="p-6">
                            <!-- Search Input -->
                            <div class="relative mb-4 group">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <i class="fas fa-search text-slate-400 group-focus-within:text-teal-500 transition-colors text-sm"></i>
                                </div>
                                <input type="text" id="modal_store_search" 
                                    class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-700 font-bold focus:border-teal-500 focus:bg-white outline-none transition-all placeholder:text-slate-400 text-sm shadow-inner"
                                    placeholder="Search store name..." autocomplete="off">
                            </div>

                            <!-- Stores Grid/List -->
                            <div id="modal_store_list" class="space-y-2 max-h-[350px] overflow-y-auto custom-scroll pr-1">
                                <!-- Loading State -->
                                <div class="py-8 text-center">
                                    <i class="fas fa-circle-notch fa-spin text-teal-500 text-lg"></i>
                                    <p class="text-slate-400 text-[10px] font-bold mt-2 uppercase tracking-widest">Loading...</p>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="bg-slate-50 p-4 border-t border-slate-100 flex justify-center items-center">
                             <!-- Logout is ONLY visible if it's a forced selection -->
                             <a href="/pos/logout" id="modal_logout_btn" class="text-rose-500 text-[10px] font-bold uppercase tracking-widest hover:text-rose-700 transition-colors flex items-center gap-2 px-3 py-1.5 rounded-lg hover:bg-rose-50">
                                 <i class="fas fa-sign-out-alt"></i> Logout
                             </a>
                             
                             <button onclick="closeStoreModal()" id="modal_close_btn" class="text-slate-400 hover:text-slate-600 text-xs font-bold px-4 py-2 rounded-lg hover:bg-slate-200 transition-all hidden">
                                 Cancel
                             </button>
                        </div>
                    </div>
                </div>
            </div>

            <script>
            let isForcedSelection = <?= $must_select ? 'true' : 'false'; ?>;

            document.addEventListener('DOMContentLoaded', () => {
                // Move Modal to Body to fix Z-Index & Blur issues over Sidebar
                const modalEl = document.getElementById('storeSelectionModal');
                if(modalEl && modalEl.parentElement !== document.body) {
                    document.body.appendChild(modalEl);
                }

                if(isForcedSelection) {
                    openStoreModal(true);
                }
                
                // Live Search with Debounce
                let timeout = null;
                const searchInput = document.getElementById('modal_store_search');
                searchInput.addEventListener('input', function() {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => {
                        fetchStores(this.value);
                    }, 300);
                });
            });

            function openStoreModal(forced = false) {
                const modal = document.getElementById('storeSelectionModal');
                const content = modal.querySelector('.modal-content');
                const logoutBtn = document.getElementById('modal_logout_btn');
                const closeBtn = document.getElementById('modal_close_btn');
                
                modal.classList.remove('hidden');
                
                // Animation
                setTimeout(() => {
                    content.classList.remove('scale-95', 'opacity-0');
                    content.classList.add('scale-100', 'opacity-100');
                }, 10);

                // Setup Buttons based on context
                if (forced || isForcedSelection) {
                    closeBtn.classList.add('hidden'); // Cannot close
                    logoutBtn.classList.remove('hidden'); // Can only logout
                } else {
                    closeBtn.classList.remove('hidden');
                    logoutBtn.classList.add('hidden'); // Hide strictly unless we want it
                }

                document.getElementById('modal_store_search').value = '';
                document.getElementById('modal_store_search').focus();
                
                fetchStores('');
            }

            function closeStoreModal() {
                if(isForcedSelection) return; // Cannot close if forced

                const modal = document.getElementById('storeSelectionModal');
                const content = modal.querySelector('.modal-content');
                
                content.classList.remove('scale-100', 'opacity-100');
                content.classList.add('scale-95', 'opacity-0');
                
                setTimeout(() => {
                    modal.classList.add('hidden');
                }, 300);
            }

            function fetchStores(query) {
                const container = document.getElementById('modal_store_list');
                
                fetch(`/pos/api/stores/search?search=${encodeURIComponent(query)}`)
                    .then(r => r.json())
                    .then(data => {
                        if(data.success) {
                            renderStores(data.stores);
                        } else {
                            container.innerHTML = `<div class="text-center py-8 text-rose-500 font-bold">Error loading stores</div>`;
                        }
                    })
                    .catch(e => {
                        container.innerHTML = `<div class="text-center py-8 text-rose-500 font-bold">Network Error</div>`;
                    });
            }

            function renderStores(stores) {
                const container = document.getElementById('modal_store_list');
                if(stores.length === 0) {
                    container.innerHTML = `
                        <div class="text-center py-8 opacity-50">
                            <i class="fas fa-store-slash text-4xl text-slate-300 mb-3"></i>
                            <p class="text-xs font-bold uppercase tracking-widest text-slate-400">No stores found</p>
                        </div>`;
                    return;
                }

                let html = '';
                stores.forEach(s => {
                    const activeClass = s.is_active ? 'border-teal-500 bg-teal-50 ring-2 ring-teal-500/20' : 'border-slate-100 hover:border-teal-300 hover:bg-slate-50';
                    const badge = s.is_active ? `<i class="fas fa-check-circle text-teal-600 text-xl"></i>` : '';
                    const location = s.city_zip || s.address || 'Unknown Location';
                    
                    html += `
                        <div onclick="selectStore(${s.id})" class="group cursor-pointer relative p-4 rounded-2xl border-2 transition-all duration-200 ${activeClass} flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-white flex items-center justify-center text-lg font-black text-slate-700 shadow-sm border border-slate-100 group-hover:scale-110 transition-transform">
                                ${s.initial}
                            </div>
                            <div class="flex-1">
                                <h4 class="font-bold text-slate-800 text-sm group-hover:text-teal-700 transition-colors">${s.store_name}</h4>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="text-[10px] uppercase font-bold text-slate-400 bg-white px-2 py-0.5 rounded border border-slate-100">ID: ${s.id}</span>
                                    <span class="text-[10px] font-bold text-slate-400 flex items-center gap-1 truncate max-w-[150px]">
                                        <i class="fas fa-map-marker-alt text-slate-300"></i> ${location}
                                    </span>
                                    ${s.status == 1 ? '<span class="w-2 h-2 rounded-full bg-green-500 ml-auto"></span>' : '<span class="w-2 h-2 rounded-full bg-rose-500 ml-auto"></span>'}
                                </div>
                            </div>
                            ${badge}
                        </div>
                    `;
                });
                container.innerHTML = html;
            }

            function selectStore(id) {
                // Post to switch_store.php
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/pos/api/config/switch-store';
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'store_id';
                input.value = id;
                
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
            </script>
            </div>

            <div class="flex items-center gap-4">
                <!-- Theme Switcher -->
                <div class="relative group">
                    <button id="theme-toggle-btn" class="w-10 h-10 rounded-lg bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-700 transition-all focus:outline-none focus:ring-0" type="button">
                        <i class="fas fa-desktop" id="theme-icon"></i>
                    </button>
                    <!-- Theme Dropdown -->
                    <div class="absolute right-0 mt-2 w-48 rounded-xl border border-slate-200 bg-white shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 dropdown-premium p-1 z-[1002]">
                        <div class="p-2 border-b border-slate-100 mb-1">
                            <h3 class="text-xs font-black uppercase tracking-widest text-slate-400">Theme</h3>
                        </div>
                        <button onclick="setTheme('light')" class="w-full flex items-center justify-between px-3 py-2 rounded-lg hover:bg-slate-50 transition-colors text-slate-600 group/theme" data-theme-val="light">
                            <div class="flex items-center gap-3">
                                <div class="w-6 h-6 rounded-lg bg-amber-100 text-amber-500 flex items-center justify-center"><i class="fas fa-sun text-xs"></i></div>
                                <span class="text-sm font-medium group-hover/theme:text-slate-900">Light</span>
                            </div>
                            <i class="fas fa-check text-teal-500 text-xs opacity-0 theme-check"></i>
                        </button>
                        <button onclick="setTheme('dark')" class="w-full flex items-center justify-between px-3 py-2 rounded-lg hover:bg-slate-50 transition-colors text-slate-600 group/theme" data-theme-val="dark">
                            <div class="flex items-center gap-3">
                                <div class="w-6 h-6 rounded-lg bg-slate-800 text-slate-300 flex items-center justify-center"><i class="fas fa-moon text-xs"></i></div>
                                <span class="text-sm font-medium group-hover/theme:text-slate-900">Dark</span>
                            </div>
                            <i class="fas fa-check text-teal-500 text-xs opacity-0 theme-check"></i>
                        </button>
                        <button onclick="setTheme('system')" class="w-full flex items-center justify-between px-3 py-2 rounded-lg hover:bg-slate-50 transition-colors text-slate-600 group/theme" data-theme-val="system">
                            <div class="flex items-center gap-3">
                                <div class="w-6 h-6 rounded-lg bg-blue-100 text-blue-500 flex items-center justify-center"><i class="fas fa-desktop text-xs"></i></div>
                                <span class="text-sm font-medium group-hover/theme:text-slate-900">System</span>
                            </div>
                            <i class="fas fa-check text-teal-500 text-xs opacity-0 theme-check"></i>
                        </button>
                        <button onclick="setTheme('login-style')" class="w-full flex items-center justify-between px-3 py-2 rounded-lg hover:bg-slate-50 transition-colors text-slate-600 group/theme" data-theme-val="login-style">
                            <div class="flex items-center gap-3">
                                <div class="w-6 h-6 rounded-lg bg-purple-100 text-purple-500 flex items-center justify-center"><i class="fas fa-magic text-xs"></i></div>
                                <span class="text-sm font-medium group-hover/theme:text-slate-900"><?= 'gradient_theme' ?></span>
                            </div>
                            <i class="fas fa-check text-teal-500 text-xs opacity-0 theme-check"></i>
                        </button>
                    </div>
                </div>
                
                <div class="relative group">
                    <button class="w-10 h-10 rounded-lg bg-slate-100 hover:bg-slate-200 flex items-center justify-center transition-all focus:outline-none focus:ring-0" type="button">
                        <i class="fas fa-bell text-xl notification-bell-icon"></i>
                    </button>
                    
                    <?php if($total_notif_count > 0): ?>
                        <span class="absolute block bg-rose-500 text-white font-black rounded-full flex items-center justify-center border-2 border-white shadow-lg shadow-rose-500/40 z-[1001] transition-transform group-hover:scale-110 pointer-events-none" 
                               style="width: 21px; height: 21px; top: -5px; right: -5px; font-size: 10px; min-width: 21px;">
                            <?= $total_notif_count ?>
                        </span>
                    <?php endif; ?>

                    <!-- Notification Dropdown -->
                    <div class="absolute right-0 mt-2 w-80 rounded-xl border border-slate-200 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 dropdown-premium px-1 py-1">
                        <div class="p-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50 rounded-t-xl">
                            <h3 class="text-sm font-bold text-slate-800"><?= 'notification_center' ?></h3>
                            <?php if($total_notif_count > 0): ?>
                                <span class="text-[10px] font-black bg-teal-100 text-teal-600 px-2 py-0.5 rounded-full uppercase tracking-wider">
                                    <?= $total_notif_count ?> <?= 'total' ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="max-h-[400px] overflow-y-auto py-1 custom-scroll">
                            <?php if($total_notif_count > 0): ?>
                                <?php foreach($notifications as $key => $cat): ?>
                                    <?php if($cat['count'] > 0): ?>
                                        <div class="px-3 py-2 text-[10px] font-black text-slate-400 uppercase tracking-[0.15em] bg-slate-50/30 flex items-center justify-between">
                                            <span><?= $cat['title'] ?></span>
                                            <span class="text-<?= $cat['color'] ?>-500"><?= $cat['count'] ?></span>
                                        </div>
                                        <?php foreach($cat['items'] as $item): ?>
                                            <a href="<?= $cat['link'] ?>" class="flex items-start gap-3 p-3 rounded-lg hover:bg-slate-50 transition-colors group/item border-b border-slate-50 last:border-0">
                                                <div class="w-8 h-8 rounded-lg bg-<?= $cat['color'] ?>-50 flex items-center justify-center text-<?= $cat['color'] ?>-500 shrink-0 shadow-sm transition-transform group-hover/item:scale-110">
                                                    <i class="fas <?= $cat['icon'] ?> text-xs"></i>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center justify-between gap-2">
                                                        <p class="text-[13px] font-bold text-slate-700 truncate"><?= htmlspecialchars($item['title']) ?></p>
                                                        <?php if(!empty($item['time'])): ?>
                                                            <span class="text-[9px] font-black text-slate-400 uppercase shrink-0"><?= $item['time'] ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <p class="text-[11px] text-slate-500 mt-0.5 font-medium"><?= htmlspecialchars($item['sub']) ?></p>
                                                </div>
                                            </a>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="py-12 text-center opacity-50">
                                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-slate-100 shadow-inner">
                                        <i class="fas fa-bell-slash text-2xl text-slate-300"></i>
                                    </div>
                                    <p class="text-[11px] font-black uppercase tracking-widest text-slate-400"><?= 'all_clear' ?></p>
                                    <p class="text-[10px] text-slate-400 font-medium"><?= 'no_new_alerts' ?></p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="p-2 border-t border-slate-100 bg-slate-50/50 rounded-b-xl">
                            <a href="/pos/reports/overview" class="block w-full text-center py-2.5 text-xs font-bold text-teal-600 hover:text-white hover:bg-teal-500 rounded-lg transition-all shadow-sm">
                                View Overview Dashboard
                            </a>
                        </div>
                    </div>
                </div>

                

                
                <div class="relative group">
                    <?php
                       
                        $userName = isset($_SESSION['auth_user']['name']) ? $_SESSION['auth_user']['name'] : 'User';

                        $userRoleRaw = isset($_SESSION['auth_user']['role_as']) ? strtolower(trim($_SESSION['auth_user']['role_as'])) : '';

                        if ($userRoleRaw == 'admin') {
                            $displayRole = 'Admin';
                        } elseif ($userRoleRaw == 'salesman') {
                            $displayRole = 'Salesman';
                        } elseif ($userRoleRaw == 'cashier') {
                            $displayRole = 'Cashier';
                        } else {
                            $displayRole = !empty($userRoleRaw) ? ucfirst($userRoleRaw) : ($userName ?: 'User');
                        }

                        $userImgFromDB = isset($_SESSION['auth_user']['image']) ? $_SESSION['auth_user']['image'] : '';

                        if (!empty($userImgFromDB)) {
                            $userImage = $userImgFromDB; 
                        } else {
                            $userImage = "https://ui-avatars.com/api/?name=" . urlencode($userName) . "&background=0d9488&color=fff&size=128";
                        }
                        ?>

                        <button class="flex items-center gap-3 px-3 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 focus:outline-none focus:ring-0 transition-all">
                            <div class="relative">
                                <span class="absolute inset-0 rounded-full border border-teal-400/60 animate-pulse"></span>
                                <img 
                                    src="<?= $userImage; ?>" 
                                    alt="Profile" 
                                    class="w-10 h-10 rounded-full border-2 border-slate-200 relative object-cover"
                                    onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($userName); ?>&background=0d9488&color=fff';"
                                >
                                <span class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 rounded-full border-2 border-white"></span>
                            </div>
                            <div class="text-left hidden md:block">
                                <p class="text-sm font-semibold text-slate-800"><?= htmlspecialchars($userName); ?></p>
                                    <p class="text-xs text-slate-500 font-medium"><?= $displayRole; ?></p>
                            </div>
                            <i class="fas fa-chevron-down text-slate-500 text-xs"></i>
                        </button>
                    
                    <div class="absolute right-0 mt-2 w-56 rounded-xl border border-slate-200 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 dropdown-premium">
                        <div class="p-2">
                                <a href="javascript:void(0)" onclick="openMyProfile()" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-slate-100 transition-colors text-slate-700">
                                    <i class="fas fa-user w-5 text-teal-600"></i>
                                    <span class="text-sm font-medium">My Profile</span>
                                </a>
                            <a href="/pos/system/settings" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-slate-100 transition-colors text-slate-700">
                                <i class="fas fa-cog w-5 text-slate-500"></i>
                                <span class="text-sm font-medium">Settings</span>
                            </a>
                            <hr class="my-2 border-slate-200">
                            <a href="/pos/logout" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-red-50 transition-colors text-red-600">
                                <i class="fas fa-sign-out-alt w-5"></i>
                                <span class="text-sm font-medium">Log Out</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>

<script>
function toggleSidebarMobile() {
    const sidebar = document.getElementById('sidebar');
    if (!sidebar) return;
    sidebar.classList.toggle('sidebar-open');
}
</script>

<!-- Shared View User Modal (global) -->
<div id="viewUserModal" class="fixed inset-0 z-[50000] hidden overflow-y-auto overflow-x-hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true" style="z-index: 50000 !important;">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity duration-300"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative w-full max-w-2xl transform overflow-hidden rounded-3xl bg-white shadow-2xl transition-all duration-300 scale-95 opacity-0 modal-content border border-slate-100">
            <div class="flex items-center justify-between bg-indigo-600 px-8 py-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center text-white shadow-inner">
                        <i class="fas fa-user-shield text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white uppercase tracking-wider">User Account Details</h3>
                        <p class="text-indigo-100 text-[10px] font-black uppercase opacity-80">Full Profile & Access Overview</p>
                    </div>
                </div>
                <button onclick="closeViewModal()" class="w-10 h-10 rounded-xl bg-white/10 text-white hover:bg-white/20 flex items-center justify-center transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="p-8" id="user_view_body">
                <div class="flex items-center justify-center py-12">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                </div>
            </div>

            <div class="bg-slate-50 px-8 py-4 border-t border-slate-100 flex justify-end">
                <button onclick="closeViewModal()" class="px-6 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold rounded-xl text-xs uppercase tracking-widest transition-all">
                    Close Sheet
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function viewUser(id) {
    const modal = document.getElementById('viewUserModal');
    const content = modal.querySelector('.modal-content');
    const body = document.getElementById('user_view_body');

    // Show modal and reset body
    modal.classList.remove('hidden');
    body.innerHTML = '<div class="flex items-center justify-center py-12"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div></div>';
    // Animate in
    setTimeout(() => {
        content.classList.remove('scale-95', 'opacity-0');
        content.classList.add('scale-100', 'opacity-100');
    }, 10);

    // Fetch User Details
    $.ajax({
        type: "POST",
        url: "/pos/api/users/fetch-modal",
        data: { user_id: id },
        success: function(response) {
            $('#user_view_body').html(response);
        }
    });
}
function closeViewModal() {
    const modal = document.getElementById('viewUserModal');
    const content = modal.querySelector('.modal-content');
    
    content.classList.remove('scale-100', 'opacity-100');
    content.classList.add('scale-95', 'opacity-0');
    
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 300);
}

// --- Theme Switcher Logic ---
function setTheme(theme) {
    let state = {
        theme: 'light',
        primary: 'teal',
        skin: 'default',
        layout_menu: 'expanded',
        layout_navbar: 'sticky',
        layout_content: 'wide'
    };
    
    // Read existing config
    const saved = localStorage.getItem('pos_config');
    if (saved) {
        try {
            state = { ...state, ...JSON.parse(saved) };
        } catch (e) {
            console.error('Error parsing pos_config', e);
        }
    }
    
    // Update theme
    state.theme = theme;
    
    // Save back to config
    localStorage.setItem('pos_config', JSON.stringify(state));
    localStorage.setItem('theme', theme); // Keep legacy key for now just in case
    
    applyTheme(theme);
}

function applyTheme(theme) {
    const root = document.documentElement;
    const icon = document.getElementById('theme-icon');
    
    root.classList.remove('dark');
    root.removeAttribute('data-theme');

    if (theme === 'dark') {
        root.classList.add('dark');
        root.setAttribute('data-theme', 'dark');
        if(icon) icon.className = 'fas fa-moon';
    } else if (theme === 'light') {
        root.classList.remove('dark');
        root.setAttribute('data-theme', 'light');
        if(icon) icon.className = 'fas fa-sun';
    } else if (theme === 'login-style') {
        root.classList.remove('dark');
        root.setAttribute('data-theme', 'login-style');
        if(icon) icon.className = 'fas fa-magic';
    } else {
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            root.classList.add('dark');
            root.setAttribute('data-theme', 'dark');
        } else {
            root.classList.remove('dark');
            root.setAttribute('data-theme', 'light');
        }
        if(icon) icon.className = 'fas fa-desktop';
    }
    
    document.querySelectorAll('.theme-check').forEach(el => el.classList.add('opacity-0'));
    const activeBtn = document.querySelector(`button[data-theme-val="${theme}"]`);
    if(activeBtn) {
        const check = activeBtn.querySelector('.theme-check');
        if(check) check.classList.remove('opacity-0');
        activeBtn.classList.add('bg-slate-50', 'font-bold');
    }
    
    document.querySelectorAll('button[data-theme-val]').forEach(btn => {
        if(btn !== activeBtn) btn.classList.remove('bg-slate-50', 'font-bold');
    });
}

(function() {
    let savedTheme = 'system';
    
    // Try to get from pos_config first (Customizer standard)
    const config = localStorage.getItem('pos_config');
    if(config) {
        try {
            const state = JSON.parse(config);
            if(state.theme) savedTheme = state.theme;
        } catch(e) {}
    } else {
        // Fallback to legacy key
        savedTheme = localStorage.getItem('theme') || 'system';
    }
    
    applyTheme(savedTheme);
    
    if(window.matchMedia) {
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
            // Check current config again
            let current = 'system';
            const c = localStorage.getItem('pos_config');
            if(c) {
                try {
                    const s = JSON.parse(c);
                    if(s.theme) current = s.theme;
                } catch(e) {}
            } else {
                current = localStorage.getItem('theme') || 'system';
            }
            
            if(current === 'system') {
                applyTheme('system');
            }
        });
    }
})();
</script>

<script>
// Close on escape key
document.addEventListener('keydown', (e) => {
    if(e.key === 'Escape') closeViewModal();
});

function openMyProfile() {
    const userId = <?= isset($_SESSION['auth_user']['user_id']) ? intval($_SESSION['auth_user']['user_id']) : 0; ?>;
    if (!userId) return;
    viewUser(userId);
}
</script>