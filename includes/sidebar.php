<?php
// Get current page to highlight active menu item
$current_page = basename($_SERVER['PHP_SELF']);
$current_uri = $_SERVER['REQUEST_URI'];

// Helper to check if current uri matches
function uri_has($needle, $uri) {
    return strpos($uri, $needle) !== false;
}

// Menu items configuration
$menu_items = [
    [
        'title' => 'Dashboards',
        'icon' => 'fa-tachometer-alt', 
        'link' => '/pos',
        'active' => ($current_page == 'index.php' || $current_uri == '/pos') 
    ],
    [
        'title' => 'POS',
        'icon' => 'fa-cash-register',
        'link' => '/pos/pos/',
        'active' => (uri_has('/pos/pos', $current_uri))
    ],
    [
        'title' => 'Sell',
        'icon' => 'fa-cash-register',
        'link' => '#',
        'submenu' => [ 
            ['title' => 'Sell List', 'link' => '/pos/sell/list'],
            ['title' => 'Return List', 'link' => '/pos/sell/return'],
            ['title' => 'Sell Log', 'link' => '/pos/sell/log'],
        ],
        'active' => (uri_has('/sell/', $current_uri))
    ],
    [
        'title' => 'Giftcard',
        'icon' => 'fa-credit-card',
        'link' => '#',
        'submenu' => [
            ['title' => 'Add Giftcard', 'link' => '/pos/giftcard/add'],
            ['title' => 'Giftcard List', 'link' => '/pos/giftcard/list'],
            ['title' => 'Giftcard Topup', 'link' => '/pos/giftcard/topup'],
        ],
        'active' => (uri_has('/giftcard/', $current_uri))
    ],
    [
        'title' => 'Products',
        'icon' => 'fa-box',
        'link' => '#',
        'submenu' => [
            ['title' => 'Add Product', 'link' => '/pos/products/add'],
            ['title' => 'Product List', 'link' => '/pos/products/list']
        ],
        'active' => (uri_has('/products/', $current_uri))
    ],
    [
        'title' => 'Quotations',
        'icon' => 'fa-file-alt',
        'link' => '#',
        'submenu' => [
            ['title' => 'Add Quotation', 'link' => '/pos/quotations/add'],
            ['title' => 'Quotation List', 'link' => '/pos/quotations/list']
        ],
        'active' => (uri_has('/quotations/', $current_uri))
    ],
    [
        'title' => 'Purchases',
        'icon' => 'fa-shopping-cart',
        'link' => '#',
        // Around lines 44-49 - Change submenu:
        'submenu' => [
            ['title' => 'Add Purchase', 'link' => '/pos/purchases/add'],
            ['title' => 'Purchase List', 'link' => '/pos/purchases/list'],
            ['title' => 'Due Invoice', 'link' => '/pos/purchases/list?filter=due'],
            ['title' => 'Return List', 'link' => '/pos/purchases/return-list'],
            ['title' => 'Purchase Logs', 'link' => '/pos/purchases/purchase-logs'],
        ],
        'active' => (uri_has('/purchases/', $current_uri))
    ],
    // [
    //     'title' => 'Invoice',
    //     'icon' => 'fa-file-invoice-dollar',
    //     'link' => '#',
    //     'submenu' => [
    //         ['title' => 'List', 'link' => '/pos/invoice/list'],
    //         ['title' => 'Preview', 'link' => '/pos/invoice/preview'],
    //         ['title' => 'Edit', 'link' => '/pos/invoice/edit'],
    //         ['title' => 'Add', 'link' => '/pos/invoice/add']
    //     ],
    //     'active' => (uri_has('/invoice/', $current_uri))
    // ],
    [
        'title' => 'Stores',
        'icon' => 'fa-store',
        'link' => '#',
        'submenu' => [
            ['title' => 'Add Store', 'link' => '/pos/stores/add'],
            ['title' => 'Store List', 'link' => '/pos/stores/list']
        ],
        'active' => (uri_has('/stores/', $current_uri))
    ],
    [
        'title' => 'Categories',
        'icon' => 'fa-th-list', 
        'link' => '#',
        'submenu' => [
            ['title' => 'Add Category', 'link' => '/pos/categories/add'],
            ['title' => 'Category List', 'link' => '/pos/categories/list']
        ],
        'active' => (uri_has('/categories/', $current_uri))
    ],
    [
        'title' => 'Currency',
        'icon' => 'fa-dollar-sign',
        'link' => '#',
        'submenu' => [
            ['title' => 'Add Currency', 'link' => '/pos/currency/add'],
            ['title' => 'Currency List', 'link' => '/pos/currency/list']
        ],
        'active' => (uri_has('/currency/', $current_uri))
    ],
    [
        'title' => 'Payment Methods',
        'icon' => 'fa-credit-card',
        'link' => '#',
        'submenu' => [
            ['title' => 'Add Payment', 'link' => '/pos/payment-methods/add'],
            ['title' => 'Payment List', 'link' => '/pos/payment-methods/list']
        ],
        'active' => (uri_has('/payment-methods/', $current_uri) || uri_has('/payment_methods/', $current_uri))
    ],
    [
        'title' => 'Units',
        'icon' => 'fa-balance-scale',
        'link' => '#',
        'submenu' => [
            ['title' => 'Add Unit', 'link' => '/pos/units/add'],
            ['title' => 'Unit List', 'link' => '/pos/units/list']
        ],
        'active' => (uri_has('/units/', $current_uri))
    ],
    [
        'title' => 'Brands',
        'icon' => 'fa-tags',
        'link' => '#',
        'submenu' => [
            ['title' => 'Add Brand', 'link' => '/pos/brands/add'],
            ['title' => 'Brand List', 'link' => '/pos/brands/list']
        ],
        'active' => (uri_has('/brands/', $current_uri))
    ],
    [
        'title' => 'Tax Rates',
        'icon' => 'fa-percent',
        'link' => '#',
        'submenu' => [
            ['title' => 'Add Taxrate', 'link' => '/pos/taxrates/add'],
            ['title' => 'Taxrate List', 'link' => '/pos/taxrates/list']
        ],
        'active' => (uri_has('/taxrates/', $current_uri))
    ],
    [
        'title' => 'Boxes',
        'icon' => 'fa-box', 
        'link' => '#',
        'submenu' => [
            ['title' => 'Add Box', 'link' => '/pos/boxes/add'],
            ['title' => 'Box List', 'link' => '/pos/boxes/list']
        ],
        'active' => (uri_has('/boxes/', $current_uri))
    ],
    [
        'title' => 'Suppliers',
        'icon' => 'fa-truck',
        'link' => '#',
        'submenu' => [
            ['title' => 'Add Supplier', 'link' => '/pos/suppliers/add'],
            ['title' => 'Supplier List', 'link' => '/pos/suppliers/list']
        ],
        'active' => (uri_has('/suppliers/', $current_uri))
    ],
    [
        'title' => 'Customers',
        'icon' => 'fa-user-friends', 
        'link' => '#',
        'submenu' => [
            ['title' => 'Add Customer', 'link' => '/pos/customers/add'],
            ['title' => 'Customer List', 'link' => '/pos/customers/list']
        ],
        'active' => (uri_has('/customers/', $current_uri))
    ],
    [
        'title' => 'Users',
        'icon' => 'fa-users',
        'link' => '#',
        'submenu' => [
            ['title' => 'Add User', 'link' => '/pos/users/add'],
            ['title' => 'User List', 'link' => '/pos/users/list'],
            ['title' => 'User Groups', 'link' => '/pos/users/groups'],
            // ['title' => 'Verify Email', 'link' => '/pos/users/verify'],
            // ['title' => 'Reset Password', 'link' => '/pos/users/reset'],
            // ['title' => 'Forgot Password', 'link' => '/pos/users/forgot'],
            // ['title' => 'Two Steps', 'link' => '/pos/users/two-steps'],
        ],
        'active' => (uri_has('/users/', $current_uri))
    ],
    [
        'title' => 'System',
        'icon' => 'fa-cog',
        'link' => '#',
        'submenu' => [
            ['title' => 'Settings', 'link' => '/pos/system/settings'],
            ['title' => 'Backup', 'link' => '/pos/system/backup']
        ],
        'active' => (uri_has('/system/', $current_uri))
    ]
];
?>



<style>
/* --- Sidebar Base Styles --- */
.sidebar {
    width: 256px;
    transition: width 300ms cubic-bezier(0.4, 0, 0.2, 1), transform 300ms ease-in-out;
    background-color: rgba(15, 23, 42, 0.95);
    backdrop-filter: blur(8px);
    z-index: 1000; 
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
}

.sidebar.collapsed { width: 80px; }

/* Desktop Hidden Elements */
@media (min-width: 1024px) {
    .sidebar.collapsed .link-text, 
    .sidebar.collapsed .logo-text-container,
    .sidebar.collapsed .fa-chevron-right {
        opacity: 0 !important;
        display: none !important;
        visibility: hidden !important;
    }

    .sidebar.collapsed .menu-group:hover .submenu {
        display: block !important;
        position: absolute;
        left: 70px;
        top: 0;
        width: 200px;
        background: #0f172a;
        border-radius: 0 8px 8px 0;
        padding: 10px;
        z-index: 1100;
        box-shadow: 10px 0 15px rgba(0,0,0,0.3);
    }
}

/* Mobile Responsiveness */
@media (max-width: 1023px) {
    #sidebar-toggle-container { display: none !important; } 
    .sidebar { transform: translateX(-100%); width: 256px !important; }
    .sidebar.sidebar-open { transform: translateX(0) !important; }
    
   
    .sidebar .link-text, .sidebar .logo-text-container {
        opacity: 1 !important;
        display: block !important;
        visibility: visible !important;
        width: auto !important;
    }
}

/* Mobile Overlay */
#sidebar-overlay {
    display: none; 
    position: fixed;
    inset: 0; 
    background: rgba(0, 0, 0, 0.4); 
    backdrop-filter: blur(2px);
    z-index: 900; 
}
#sidebar-overlay.active { display: block !important; }

#sidebar-toggle-container { position: absolute; right: -12px; top: 25px; }

.active-submenu-bg {
    background-color: rgba(13, 148, 136, 0.3); 
    color: white;
    border-left: 4px solid #0d9488;
}

.rotate-90 { transform: rotate(90deg); }
.rotate-180 { transform: rotate(180deg); }

.custom-scroll::-webkit-scrollbar { width: 4px; }
.custom-scroll::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.1); border-radius: 10px; }
</style>

<div id="sidebar-overlay" onclick="toggleSidebarMobile()"></div>

<aside id="sidebar" class="sidebar min-h-screen fixed left-0 top-0 lg:translate-x-0 transition-all duration-300">
    <div class="flex items-center gap-3 px-2 py-4 border-b border-white/5 relative group">
    <div class="w-14 h-14 rounded-2xl shrink-0 bg-gradient-to-br from-teal-900 via-teal-800 from-white/10 to-white/5 border border-white/20 shadow-inner flex items-center justify-center overflow-hidden transition-transform duration-300 group-hover:scale-105">
        <img src="/pos/assets/images/logo.png" alt="Logo" class="w-full h-full object-contain" />
    </div>

    <div class="logo-text-container overflow-hidden flex-1 select-none">
        <h1 class="logo-text text-lg font-extrabold text-white tracking-tight leading-tight whitespace-nowrap">
         <span class="text-teal-400">POS</span>
        </h1>
        <div class="flex items-center gap-1.5">
            <span class="w-1 h-1 rounded-full bg-teal-500 animate-pulse"></span>
            <p class="logo-subtitle text-[9px] text-white/50 uppercase font-bold tracking-[0.1em]">Inventory Hub</p>
        </div>
    </div>
        
        <div id="sidebar-toggle-container">
            <button id="sidebar-toggle" class="w-6 h-6 bg-teal-600 text-white rounded-full flex items-center justify-center shadow-lg hover:bg-teal-500">
                <i class="fas fa-chevron-left text-[10px]" id="toggle-icon"></i>
            </button>
        </div>
    </div>
    
    <nav class="p-4 space-y-2 overflow-y-auto h-[calc(100vh-80px)] custom-scroll">
        <?php foreach($menu_items as $item): ?>
            <?php 
            $has_submenu = isset($item['submenu']);
            $link_classes = $item['active'] ? 'active-submenu-bg text-white shadow-lg' : 'text-white/70 hover:bg-white/10 hover:text-white';
            ?>
            <div class="mb-1 relative menu-group">
                <a href="<?= $has_submenu ? '#' : $item['link']; ?>" 
                   class="flex items-center justify-between px-4 py-3 rounded-lg transition-all duration-200 group <?= $link_classes; ?>"
                   <?= $has_submenu ? 'onclick="toggleSubmenu(this); event.preventDefault();"' : ''; ?>>
                    <div class="flex items-center gap-3">
                        <i class="fas <?= $item['icon']; ?> w-5 shrink-0 text-center"></i>
                        <span class="link-text font-medium text-sm whitespace-nowrap"><?= $item['title']; ?></span>
                    </div>
                    <?php if($has_submenu): ?>
                        <i class="link-text fas fa-chevron-right text-[10px] transition-transform duration-200 <?= $item['active'] ? 'rotate-90' : ''; ?>"></i>
                    <?php endif; ?>
                </a>
                
                <?php if($has_submenu): ?>
                    <div class="submenu ml-4 mt-1 space-y-1 <?= $item['active'] ? '' : 'hidden'; ?>">
                        <?php foreach($item['submenu'] as $sub): ?>
                            <a href="<?= $sub['link']; ?>" class="flex items-center gap-3 px-4 py-2 rounded-lg text-xs transition-all duration-200 <?= (uri_has($sub['link'], $current_uri)) ? 'text-teal-400 font-bold' : 'text-white/50 hover:text-white'; ?>">
                                <i class="fas fa-circle text-[4px] shrink-0"></i>
                                <span class="link-text"><?= $sub['title']; ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </nav>
</aside>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.getElementById('sidebar-toggle');
        const toggleIcon = document.getElementById('toggle-icon');
        const overlay = document.getElementById('sidebar-overlay');
        const mainContent = document.getElementById('main-content');
        
        // Touch variables for swipe support
        let touchStartX = 0;
        let touchEndX = 0;

        // Retrieve sidebar lock state from local storage
        let isLocked = localStorage.getItem('sidebarLocked') === 'true'; 

        /**
         * INITIALIZATION: Apply layout state on load
         */
        if (isLocked) {
            sidebar.classList.remove('collapsed');
            if(mainContent) mainContent.style.marginLeft = '256px';
            toggleIcon.classList.remove('rotate-180');
        } else {
            sidebar.classList.add('collapsed');
            if(mainContent) mainContent.style.marginLeft = '80px';
            toggleIcon.classList.add('rotate-180');
        }

        /**
         * DESKTOP HOVER LOGIC
         */
        sidebar.addEventListener('mouseenter', () => {
            if (window.innerWidth >= 1024 && !isLocked) {
                sidebar.classList.remove('collapsed');
                if(mainContent) mainContent.style.marginLeft = '256px';
                toggleIcon.classList.remove('rotate-180');
            }
        });

        sidebar.addEventListener('mouseleave', () => {
            if (window.innerWidth >= 1024 && !isLocked) {
                sidebar.classList.add('collapsed');
                if(mainContent) mainContent.style.marginLeft = '80px';
                toggleIcon.classList.add('rotate-180');
            }
        });

        /**
         * TOGGLE LOCK BUTTON
         */
        if(toggleBtn) {
            toggleBtn.addEventListener('click', (e) => {
                e.stopPropagation(); // Prevent event bubbling
                isLocked = !isLocked; 
                localStorage.setItem('sidebarLocked', isLocked);

                if (isLocked) {
                    sidebar.classList.remove('collapsed');
                    if(mainContent) mainContent.style.marginLeft = '256px';
                    toggleIcon.classList.remove('rotate-180');
                } else {
                    sidebar.classList.add('collapsed');
                    if(mainContent) mainContent.style.marginLeft = '80px';
                    toggleIcon.classList.add('rotate-180');
                }
            });
        }

        /**
         * GLOBAL CLICK LISTENER: Closes sidebar if clicking outside
         */
        window.addEventListener('click', (e) => {
            const isMobile = window.innerWidth < 1024;
            // Check if sidebar is open and click is outside sidebar and toggle button
            if (isMobile && sidebar.classList.contains('sidebar-open')) {
                if (!sidebar.contains(e.target) && !e.target.closest('button')) {
                    closeMobileSidebar();
                }
            }
        });

        // Overlay click fallback
        if (overlay) {
            overlay.addEventListener('click', closeMobileSidebar);
        }

        /**
         * MOBILE SWIPE GESTURES
         */
        document.addEventListener('touchstart', e => {
            touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });

        document.addEventListener('touchend', e => {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        }, { passive: true });

        function handleSwipe() {
            const swipeDistance = touchEndX - touchStartX;
            if (window.innerWidth >= 1024) return;

            if (swipeDistance > 100 && touchStartX < 50) {
                if (!sidebar.classList.contains('sidebar-open')) toggleSidebarMobile();
            }
            if (swipeDistance < -100) {
                if (sidebar.classList.contains('sidebar-open')) closeMobileSidebar();
            }
        }

        function closeMobileSidebar() {
            sidebar.classList.remove('sidebar-open');
            if (overlay) overlay.classList.remove('active');
            document.body.style.overflow = ''; // Restore scroll
        }
    });

    /**
     * TOGGLE MOBILE SIDEBAR
     */
    function toggleSidebarMobile() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        if (!sidebar || !overlay) return;

        sidebar.classList.toggle('sidebar-open');
        overlay.classList.toggle('active');
        document.body.style.overflow = sidebar.classList.contains('sidebar-open') ? 'hidden' : '';
    }

    /**
     * SUBMENU HANDLER
     */
    function toggleSubmenu(element) {
        const sidebar = document.getElementById('sidebar');
        if (sidebar.classList.contains('collapsed') && window.innerWidth >= 1024) return;
        
        const submenu = element.nextElementSibling;
        if (submenu) submenu.classList.toggle('hidden');
        const chevron = element.querySelector('.fa-chevron-right');
        if (chevron) chevron.classList.toggle('rotate-90');
    }
</script>
