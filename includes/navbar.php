<nav class="bg-white/95 backdrop-blur-lg border-b border-slate-200 sticky top-0 z-50 shadow-sm">
    <div class="px-4 md:px-6 py-4">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-3 flex-1">
                <button 
                    class="lg:hidden w-10 h-10 rounded-lg bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-700 transition-all"
                    onclick="toggleSidebarMobile()" 
                    type="button">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="flex-1 max-w-md">
                    <div class="relative">
                        <input 
                            type="text" 
                            placeholder="Search [CTRL + K]" 
                            class="w-full bg-slate-100 border border-slate-300 rounded-lg px-4 py-2 pl-10 text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-transparent transition-all"
                        >
                        <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-400"></i>
                    </div>
                </div>
            </div>
            
            <div class="flex items-center gap-4">
                <button class="w-10 h-10 rounded-lg bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-700 transition-all">
                    <i class="fas fa-globe"></i>
                </button>
                
                <button class="w-10 h-10 rounded-lg bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-700 transition-all">
                    <i class="fas fa-moon"></i>
                </button>
                
                <button class="w-10 h-10 rounded-lg bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-700 transition-all">
                    <i class="fas fa-th"></i>
                </button>
                
                <button class="w-10 h-10 rounded-lg bg-slate-100 hover:bg-slate-200 flex items-center justify-center text-slate-700 transition-all relative">
                    <i class="fas fa-bell"></i>
                    <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                </button>
                
                <div class="relative group">
                    <button class="flex items-center gap-3 px-3 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 transition-all">
                        <div class="relative">
                            <span class="absolute inset-0 rounded-full border border-teal-400/60 animate-pulse"></span>
                            <img 
                                src="https://ui-avatars.com/api/?name=<?= urlencode(isset($_SESSION['auth_user']['name']) ? $_SESSION['auth_user']['name'] : 'User'); ?>&background=667eea&color=fff&size=128" 
                                alt="Profile" 
                                class="w-10 h-10 rounded-full border-2 border-slate-200 relative"
                            >
                            <span class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 rounded-full border-2 border-white"></span>
                        </div>
                        <div class="text-left hidden md:block">
                            <p class="text-sm font-semibold text-slate-800"><?= isset($_SESSION['auth_user']['name']) ? htmlspecialchars($_SESSION['auth_user']['name']) : 'User'; ?></p>
                            <p class="text-xs text-slate-500"><?= isset($_SESSION['auth_user']['role_as']) ? ucfirst(htmlspecialchars($_SESSION['auth_user']['role_as'])) : 'Admin'; ?></p>
                        </div>
                        <i class="fas fa-chevron-down text-slate-500 text-xs"></i>
                    </button>
                    
                    <div class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-2xl border border-slate-200 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-50">
                        <div class="p-2">
                            <a href="#" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-slate-100 transition-colors text-slate-700">
                                <i class="fas fa-user w-5"></i>
                                <span class="text-sm font-medium">My Profile</span>
                            </a>
                            <a href="#" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-slate-100 transition-colors text-slate-700">
                                <i class="fas fa-cog w-5"></i>
                                <span class="text-sm font-medium">Settings</span>
                            </a>
                            <a href="#" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-slate-100 transition-colors text-slate-700 relative">
                                <i class="fas fa-file-invoice w-5"></i>
                                <span class="text-sm font-medium">Billing Plan</span>
                                <span class="ml-auto bg-red-500 text-white text-xs px-2 py-0.5 rounded-full">4</span>
                            </a>
                            <a href="#" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-slate-100 transition-colors text-slate-700">
                                <i class="fas fa-dollar-sign w-5"></i>
                                <span class="text-sm font-medium">Pricing</span>
                            </a>
                            <a href="#" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-slate-100 transition-colors text-slate-700">
                                <i class="fas fa-question-circle w-5"></i>
                                <span class="text-sm font-medium">FAQ</span>
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