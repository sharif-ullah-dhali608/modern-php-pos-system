<?php
// Get current page to highlight active menu item
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
$current_uri = $_SERVER['REQUEST_URI'];

// Helper to check if current uri matches
function uri_has($needle, $uri) {
    return strpos($uri, $needle) !== false;
}

// Menu items configuration with Clean URLs as per .htaccess rules
$menu_items = [
    [
        'title' => 'Dashboards',
        'icon' => 'fa-tachometer-alt', 
        'link' => '/pos', // Clean URL
        'badge' => '5',
        'badge_color' => 'bg-red-500',
        'active' => ($current_page == 'index.php' || $current_uri == '/pos') 
    ],
    [
        'title' => 'Invoice',
        'icon' => 'fa-file-invoice-dollar',
        'link' => '#',
        'submenu' => [
            ['title' => 'List', 'link' => '/pos/invoice/list'],
            ['title' => 'Preview', 'link' => '/pos/invoice/preview'],
            ['title' => 'Edit', 'link' => '/pos/invoice/edit'],
            ['title' => 'Add', 'link' => '/pos/invoice/add']
        ],
        'active' => (uri_has('/invoice/', $current_uri))
    ],
    [
        'title' => 'Stores',
        'icon' => 'fa-store',
        'link' => '#',
        'submenu' => [
            ['title' => 'Add Store', 'link' => '/pos/stores/add'], // Clean URL
            ['title' => 'Store List', 'link' => '/pos/stores/list'] // Clean URL
        ],
        'active' => (uri_has('/stores/', $current_uri))
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
        'title' => 'Users',
        'icon' => 'fa-users',
        'link' => '#',
        'submenu' => [
            ['title' => 'Add User & List', 'link' => '/pos/users/add'],
            ['title' => 'Verify Email', 'link' => '/pos/users/verify'],
            ['title' => 'Reset Password', 'link' => '/pos/users/reset'],
            ['title' => 'Forgot Password', 'link' => '/pos/users/forgot'],
            ['title' => 'Two Steps', 'link' => '/pos/users/two-steps'],
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
        /* Custom styles for the collapsed state and smooth transitions */
        .sidebar {
            width: 256px; /* Expanded width (Tailwind w-64) */
            transition: width 300ms ease-in-out;
            /* Use the transparent dark color that matches the screenshot */
            background-color: rgba(15, 23, 42, 0.9); /* Equivalent to bg-slate-900/90 */
            backdrop-filter: blur(8px); /* Equivalent to backdrop-blur-xl */
        }
        .sidebar.collapsed {
            width: 80px; /* Collapsed width */
        }
        /* Elements that should fade/hide when collapsed */
        .sidebar .link-text, .sidebar .search-input, .sidebar .logo-text, .sidebar .logo-subtitle {
            opacity: 1;
            transition: opacity 300ms ease-in-out, visibility 300ms ease-in-out;
        }
        .sidebar.collapsed .link-text, .sidebar.collapsed .search-input, .sidebar.collapsed .logo-text, .sidebar.collapsed .logo-subtitle {
            opacity: 0;
            visibility: hidden;
            width: 0; 
            overflow: hidden; /* Ensure text doesn't flow outside icon */
        }
        
        /* Positioning the toggle button */
        #sidebar-toggle-container {
            position: absolute; 
            right: 0;
            top: 50%;
            transform: translateY(-50%);
        }
        .active-submenu-bg {
            /* Teal-600 with 30% opacity */
            background-color: rgba(13, 148, 136, 0.3); 
            color: white;
            box-shadow: 0 4px 6px -1px rgba(13, 148, 136, 0.1), 0 2px 4px -2px rgba(13, 148, 136, 0.1);
        }
        .active-submenu-bg:hover {
            background-color: rgba(13, 148, 136, 0.45);
        }
    </style>
<aside class="sidebar w-64 border-r border-white/10 min-h-screen fixed left-0 top-0 z-40 transition-all duration-300 -translate-x-full lg:translate-x-0" id="sidebar">
    
    <div class="p-6 border-b border-white/10 relative h-20"> 
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-teal-500 to-indigo-600 flex items-center justify-center text-white font-bold text-xl shadow-lg shrink-0">
                <i class="fas fa-cash-register"></i>
            </div>
            <div>
                <h1 class="logo-text text-xl font-bold text-white">POS</h1>
                <p class="logo-subtitle text-xs text-white/60">Inventory System</p>
            </div>
        </div>
        
        <div id="sidebar-toggle-container">
            <button id="sidebar-toggle" class="p-1 rounded transition duration-200 text-white/70 hover:text-white hover:bg-white/10">
                <svg id="toggle-icon-open" class="w-5 h-5 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                <svg id="toggle-icon-closed" class="w-5 h-5 transition-transform duration-300 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </button>
        </div>
    </div>
    
    <nav class="p-4 space-y-2 overflow-y-auto h-[calc(100vh-160px)]">
        <?php foreach($menu_items as $item): ?>
            <?php 
            $is_active = $item['active'];
            $has_submenu = isset($item['submenu']);
            // Check if the current item is active, and use the teal color from the screenshot
            $link_classes = $is_active ? 'active-submenu-bg text-white shadow-lg' : 'text-white/70 hover:bg-white/10 hover:text-white';
            ?>
            <div class="mb-1">
                <a 
                    href="<?= $has_submenu ? '#' : $item['link']; ?>" 
                    class="flex items-center justify-between px-4 py-3 rounded-lg transition-all duration-200 group <?= $link_classes; ?>"
                    <?= $has_submenu ? 'onclick="event.preventDefault(); toggleSubmenu(this);"' : ''; ?>
                >
                    <div class="flex items-center gap-3">
                        <i class="fas <?= $item['icon']; ?> w-5 shrink-0"></i>
                        <span class="link-text font-medium whitespace-nowrap"><?= $item['title']; ?></span>
                    </div>
                    <?php if(isset($item['badge'])): ?>
                        <span class="link-text px-2 py-0.5 rounded-full text-xs font-bold <?= $item['badge_color']; ?> text-white shrink-0">
                            <?= $item['badge']; ?>
                        </span>
                    <?php endif; ?>
                    <?php if($has_submenu): ?>
                        <i class="link-text fas fa-chevron-right text-xs transition-transform duration-200 shrink-0 <?= $is_active ? 'rotate-90' : ''; ?>"></i>
                    <?php endif; ?>
                </a>
                
                <?php if($has_submenu): ?>
                    <div class="link-text ml-4 mt-1 space-y-1 submenu <?= $is_active ? '' : 'hidden'; ?>">
                        <?php foreach($item['submenu'] as $subitem): ?>
                            <?php 
                            $sub_active = (basename($_SERVER['PHP_SELF']) == basename($subitem['link'])) || uri_has($subitem['link'], $current_uri);
                            ?>
                            
                            <a 
                                href="<?= $subitem['link']; ?>" 
                                class="flex items-center gap-3 px-4 py-2 rounded-lg text-sm transition-all duration-200 <?= $sub_active ? 'active-submenu-bg text-white' : 'text-white/60 hover:bg-white/5 hover:text-white'; ?>"
                            >
                                <i class="fas fa-circle text-[6px] shrink-0"></i>
                                <span class="link-text"><?= $subitem['title']; ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </nav>
    
</aside>
<script>

        // ... (rest of the logic for closing submenus) ...

    // Handles the toggling of submenu dropdowns
    function toggleSubmenu(element) {
        // Toggle the submenu block
        const submenu = element.nextElementSibling;
        if (submenu && submenu.classList.contains('submenu')) {
            submenu.classList.toggle('hidden');
        }

        // Toggle the chevron icon rotation
        const chevron = element.querySelector('.fa-chevron-right');
        if (chevron) {
            chevron.classList.toggle('rotate-90');
        }
    }

        document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('sidebar');
    const toggleButton = document.getElementById('sidebar-toggle');
    // Ensure this line correctly targets your main content
    const mainContent = document.getElementById('main-content'); 
    const iconOpen = document.getElementById('toggle-icon-open');
    const iconClosed = document.getElementById('toggle-icon-closed');
    
    toggleButton.addEventListener('click', () => {
        const isCollapsed = sidebar.classList.toggle('collapsed');
        
        // **This is the key section to ensure it works:**
        if (mainContent) {
             // 256px (w-64) is the expanded width, 80px is the collapsed width
             mainContent.style.marginLeft = isCollapsed ? '80px' : '256px'; 
        }

        // Toggle the visibility of the chevron icons in the toggle button
        iconOpen.classList.toggle('hidden', isCollapsed);
        iconClosed.classList.toggle('hidden', !isCollapsed);
            if (isCollapsed) {
                document.querySelectorAll('.submenu:not(.hidden)').forEach(submenu => {
                    submenu.classList.add('hidden');
                    // Reset chevron for the parent link
                    const parentLink = submenu.previousElementSibling;
                    if (parentLink) {
                        const chevron = parentLink.querySelector('.fa-chevron-right');
                        if (chevron) {
                             chevron.classList.remove('rotate-90');
                        }
                    }
                });
            }
        });
    });
</script>