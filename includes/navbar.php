<nav class="bg-white/10 backdrop-blur-lg border-b border-white/10 sticky top-0 z-50">
    <div class="px-4 md:px-6 py-4">
        <div class="flex items-center justify-between gap-3">
            <!-- Left: Mobile toggle + Search -->
            <div class="flex items-center gap-3 flex-1">
                <button 
                    class="lg:hidden w-10 h-10 rounded-lg bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition-all"
                    onclick="toggleSidebarMobile()"
                    aria-label="Toggle sidebar"
                >
                    <i class="fas fa-bars"></i>
                </button>
                <div class="flex-1 max-w-md">
                    <div class="relative">
                        <input 
                            type="text" 
                            placeholder="Search [CTRL + K]" 
                            class="w-full bg-white/10 border border-white/20 rounded-lg px-4 py-2 pl-10 text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all"
                        >
                        <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-white/50"></i>
                    </div>
                </div>
            </div>
            
            <!-- Right: Icons & Profile -->
            <div class="flex items-center gap-4">
                <!-- Globe Icon -->
                <button class="w-10 h-10 rounded-lg bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition-all">
                    <i class="fas fa-globe"></i>
                </button>
                
                <!-- Theme Toggle -->
                <button class="w-10 h-10 rounded-lg bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition-all">
                    <i class="fas fa-moon"></i>
                </button>
                
                <!-- Apps Grid -->
                <button class="w-10 h-10 rounded-lg bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition-all">
                    <i class="fas fa-th"></i>
                </button>
                
                <!-- Notifications -->
                <button class="w-10 h-10 rounded-lg bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition-all relative">
                    <i class="fas fa-bell"></i>
                    <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                </button>
                
                <!-- User Profile -->
                <div class="relative group">
                    <button class="flex items-center gap-3 px-3 py-2 rounded-lg bg-white/10 hover:bg-white/20 transition-all">
                        <div class="relative">
                            <span class="absolute inset-0 rounded-full border border-purple-400/60 animate-pulse"></span>
                            <img 
                                src="https://ui-avatars.com/api/?name=<?= urlencode(isset($_SESSION['auth_user']['name']) ? $_SESSION['auth_user']['name'] : 'User'); ?>&background=667eea&color=fff&size=128" 
                                alt="Profile" 
                                class="w-10 h-10 rounded-full border-2 border-white/50 relative"
                            >
                            <span class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 rounded-full border-2 border-white"></span>
                        </div>
                        <div class="text-left hidden md:block">
                            <p class="text-sm font-semibold text-white"><?= isset($_SESSION['auth_user']['name']) ? htmlspecialchars($_SESSION['auth_user']['name']) : 'User'; ?></p>
                            <p class="text-xs text-white/60"><?= isset($_SESSION['auth_user']['role_as']) ? ucfirst(htmlspecialchars($_SESSION['auth_user']['role_as'])) : 'Admin'; ?></p>
                        </div>
                        <i class="fas fa-chevron-down text-white/60 text-xs"></i>
                    </button>
                    
                    <!-- Dropdown Menu -->
                    <div class="absolute right-0 mt-2 w-56 bg-white/95 backdrop-blur-lg rounded-xl shadow-2xl border border-white/20 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300">
                        <div class="p-2">
                            <a href="#" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-100 transition-colors text-gray-700">
                                <i class="fas fa-user w-5"></i>
                                <span class="text-sm font-medium">My Profile</span>
                            </a>
                            <a href="#" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-100 transition-colors text-gray-700">
                                <i class="fas fa-cog w-5"></i>
                                <span class="text-sm font-medium">Settings</span>
                            </a>
                            <a href="#" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-100 transition-colors text-gray-700 relative">
                                <i class="fas fa-file-invoice w-5"></i>
                                <span class="text-sm font-medium">Billing Plan</span>
                                <span class="ml-auto bg-red-500 text-white text-xs px-2 py-0.5 rounded-full">4</span>
                            </a>
                            <a href="#" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-100 transition-colors text-gray-700">
                                <i class="fas fa-dollar-sign w-5"></i>
                                <span class="text-sm font-medium">Pricing</span>
                            </a>
                            <a href="#" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-100 transition-colors text-gray-700">
                                <i class="fas fa-question-circle w-5"></i>
                                <span class="text-sm font-medium">FAQ</span>
                            </a>
                            <hr class="my-2 border-gray-200">
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

