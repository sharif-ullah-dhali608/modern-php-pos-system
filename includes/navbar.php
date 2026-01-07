<nav class="bg-white/95 backdrop-blur-lg border-b border-slate-200 top-0 z-50 shadow-sm">

    <div class="px-4 md:px-6 py-4">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-3 flex-1">
                <button 
                    class="lg:hidden w-10 h-10 rounded-lg bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-700 transition-all"
                    onclick="toggleSidebarMobile()" 
                    type="button"
                    >
                    <i class="fas fa-bars"></i>
                </button>
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