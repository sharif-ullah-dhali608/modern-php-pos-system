<nav id="main-navbar" class="bg-[#fcfdfd]/95 backdrop-blur-lg border-b border-slate-200 top-0 z-50 shadow-sm transition-colors duration-300">

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
            $curr_store_name = 'Select Store';
            
            // Get current store name quickly
            if($current_store_id) {
                $c_q = mysqli_query($conn, "SELECT store_name FROM stores WHERE id='$current_store_id'");
                if($c = mysqli_fetch_assoc($c_q)) $curr_store_name = $c['store_name'];
            }
            
            // Check if we MUST open the modal (First Login)
            $must_select = isset($_SESSION['must_select_store']) && $_SESSION['must_select_store'] === true;
            ?>
            
            <!-- Store Trigger Button (Left Navbar) -->
            <div class="flex-1 px-4">
                <button onclick="openStoreModal(false)" class="group flex flex-col justify-center items-start text-left focus:outline-none">
                    <div class="store-trigger flex items-center gap-3 group-hover:bg-white px-4 py-2 rounded-2xl transition-all border border-transparent group-hover:border-teal-100 group-hover:shadow-md">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-teal-400 to-emerald-500 flex items-center justify-center text-white shadow-lg shadow-teal-500/30 group-hover:scale-110 transition-transform duration-300">
                            <i class="fas fa-store-alt text-lg"></i>
                        </div>
                        <div>
                            <div class="text-[10px] uppercase font-black text-teal-600/80 tracking-widest group-hover:text-teal-600 transition-colors">Current Workspace</div>
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-black text-slate-800 truncate max-w-[150px] md:max-w-[200px]" id="nav_store_name"><?= htmlspecialchars($curr_store_name); ?></span>
                                <i class="fas fa-chevron-right text-[10px] text-slate-300 group-hover:text-teal-500 transition-all group-hover:translate-x-1"></i>
                            </div>
                        </div>
                    </div>
                </button>
            </div>
            
            <!-- STORE SELECTION MODAL -->
            <div id="storeSelectionModal" class="fixed inset-0 z-[10000] hidden">
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
                             <a href="/pos/logout.php" id="modal_logout_btn" class="text-rose-500 text-[10px] font-bold uppercase tracking-widest hover:text-rose-700 transition-colors flex items-center gap-2 px-3 py-1.5 rounded-lg hover:bg-rose-50">
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
                
                fetch(`/pos/users/fetch_stores_modal.php?search=${encodeURIComponent(query)}`)
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
                form.action = '/pos/config/switch_store.php';
                
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
                <button class="w-10 h-10 rounded-lg bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-700 transition-all">
                    <i class="fas fa-globe"></i>
                </button>
                
                <button class="w-10 h-10 rounded-lg bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-700 transition-all">
                    <i class="fas fa-moon"></i>
                </button>
                
                <button class="w-10 h-10 rounded-lg bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-700 transition-all relative">
                    <i class="fas fa-bell"></i>
                    <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                </button>
                

                
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

                        <button class="flex items-center gap-3 px-3 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 transition-all">
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
                    
                    <div class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-2xl border border-slate-200 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-50">
                        <div class="p-2">
                                <a href="javascript:void(0)" onclick="openMyProfile()" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-slate-100 transition-colors text-slate-700">
                                    <i class="fas fa-user w-5 text-teal-600"></i>
                                    <span class="text-sm font-medium">My Profile</span>
                                </a>
                            <a href="#" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-slate-100 transition-colors text-slate-700">
                                <i class="fas fa-cog w-5 text-slate-500"></i>
                                <span class="text-sm font-medium">Settings</span>
                            </a>
                            <hr class="my-2 border-slate-200">
                            <a href="/pos/logout.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-red-50 transition-colors text-red-600">
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
<div id="viewUserModal" class="fixed inset-0 z-[9999] hidden overflow-y-auto overflow-x-hidden">
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
        url: "/pos/users/fetch_user_modal.php",
        data: { user_id: id },
        success: function(response) {
            body.innerHTML = response;
        },
        error: function() {
            body.innerHTML = '<p class="text-center py-8 text-red-500">Failed to load user data.</p>';
        }
    });
}

function closeViewModal() {
    const modal = document.getElementById('viewUserModal');
    const content = modal.querySelector('.modal-content');
    content.classList.remove('scale-100', 'opacity-100');
    content.classList.add('scale-95', 'opacity-0');
    setTimeout(() => {
        if(modal) modal.classList.add('hidden');
    }, 200);
}

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