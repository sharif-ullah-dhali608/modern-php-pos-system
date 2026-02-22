<?php
session_start();
include('../config/dbcon.php');

// Page Title
$page_title = "Documentation - Velocity POS";

// 1. Include Global Header (Includes CSS, JS, Fonts, Session Check)
include('../includes/header.php');
?>

<div class="app-wrapper flex-col h-auto">
    <!-- 3. Documentation Content Wrapper -->
    <div class="doc-container relative w-full flex-1">
        
        <style>
            /* SCROLL FIX: Force scrolling to work */
            html, body {
                scroll-behavior: smooth !important;
                overflow-y: auto !important;
                height: auto !important;
                background-color: #efefef !important; /* Match content */
            }
            .app-wrapper {
                height: auto !important;
                overflow: visible !important;
            }
            
            /* Reset specific for this section to match Documenter style */
            .doc-wrapper ul, .doc-wrapper ol { margin: 18px 0; line-height: 1.6em; padding-left: 20px; }
            .doc-wrapper ul li { list-style: disc; margin-bottom: 8px; }
            .doc-wrapper ol li { list-style: decimal; margin-bottom: 8px; }
            .doc-wrapper a { color: #008C9E; text-decoration: none; }
            .doc-wrapper a:hover { text-decoration: underline; }
            .doc-wrapper hr { border-top: 1px solid #ddd; border-bottom: 1px solid #aaa; margin: 16px 0; }
            
            /* --- Sidebar Base Styles (Copied from sidebar.php) --- */
            .sidebar {
                width: 256px;
                transition: width 300ms cubic-bezier(0.4, 0, 0.2, 1), transform 300ms ease-in-out;
                background-color: #1e293b !important; /* Force Dark Slate */
                z-index: 5000 !important;
                position: fixed;
                top: 0; /* Full Height */
                left: 0;
                bottom: 0;
                height: 100vh;
                overflow-y: auto;
                border-right: 1px solid rgba(255,255,255,0.1);
            }
            
            .active-submenu-bg {
                background-color: rgba(13, 148, 136, 0.3); 
                color: white !important;
                border-left: 4px solid #0d9488;
            }
            
            .custom-scroll::-webkit-scrollbar { width: 4px; }
            .custom-scroll::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.1); border-radius: 10px; }

            /* Content Layout (Legacy removed) */
            /* #documenter_content styles are now handled by the Design System at the bottom */
        </style>

        <div class="doc-wrapper">
            <!-- New Sidebar Structure -->
            <aside id="sidebar" class="sidebar custom-scroll">
                 <!-- Logo Area (Matches Sidebar.php) -->
                 <div class="flex items-center gap-3 px-4 py-4 h-[81px] border-b border-white/5 relative group">
                    <div class="w-10 h-10 rounded-xl shrink-0 bg-gradient-to-br from-teal-900 via-teal-800 from-white/10 to-white/5 border border-white/20 shadow-inner flex items-center justify-center overflow-hidden transition-transform duration-300 group-hover:scale-105">
                        <img src="/pos/assets/images/logo.png" onerror="this.src='/pos/assets/images/logo.png'" alt="Logo" class="w-full h-full object-contain" />
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
                </div>

                <div class="mt-2 pt-0">
                    <a href="/pos" class="flex items-center gap-3 px-4 py-2 rounded-lg bg-teal-600/20 text-teal-400 hover:bg-teal-600/30 transition-all font-bold">
                        <i class="fas fa-home w-5 text-center"></i>
                        <span class="text-sm">Back to Dashboard</span>
                    </a>
                </div>
                 <div class="p-2 ">
                    <!-- Navigation Links -->
                    <?php
                    $doc_nav = [
                        ['id' => 'overview', 'title' => 'Overview', 'icon' => 'fa-info-circle'],
                        ['id' => 'architecture', 'title' => 'Project Architecture', 'icon' => 'fa-sitemap'],
                        ['id' => 'installation', 'title' => 'Installation', 'icon' => 'fa-download'],
                        ['id' => 'dashboard', 'title' => 'Dashboard', 'icon' => 'fa-tachometer-alt'],
                        ['id' => 'point-of-sale', 'title' => 'Point of Sale', 'icon' => 'fa-cash-register'],
                        ['id' => 'stores', 'title' => 'Store Management', 'icon' => 'fa-store'],
                        ['id' => 'products', 'title' => 'Products & Stock', 'icon' => 'fa-box'],
                        ['id' => 'transfers', 'title' => 'Stock Transfers', 'icon' => 'fa-exchange-alt'],
                        ['id' => 'master-data', 'title' => 'Attributes (Brands/Units)', 'icon' => 'fa-tags'],
                        ['id' => 'purchases', 'title' => 'Purchases & Returns', 'icon' => 'fa-shopping-cart'],
                        ['id' => 'sales', 'title' => 'Sales & Invoices', 'icon' => 'fa-file-invoice-dollar'],
                        ['id' => 'installments', 'title' => 'Installments', 'icon' => 'fa-calendar-alt'],
                        ['id' => 'loan', 'title' => 'Loan Management', 'icon' => 'fa-hand-holding-usd'],
                        ['id' => 'payments-gifting', 'title' => 'Payments & Cards', 'icon' => 'fa-credit-card'],
                        ['id' => 'customers', 'title' => 'Customers', 'icon' => 'fa-users'],
                        ['id' => 'accounting', 'title' => 'Accounting', 'icon' => 'fa-chart-line'],
                        ['id' => 'reports', 'title' => 'Reports', 'icon' => 'fa-file-alt'],
                        ['id' => 'users-roles', 'title' => 'Users & Roles', 'icon' => 'fa-user-shield'],
                        ['id' => 'settings', 'title' => 'Settings', 'icon' => 'fa-cogs'],
                        ['id' => 'tax-rules', 'title' => 'Tax Rules', 'icon' => 'fa-percent'],
                        ['id' => 'references', 'title' => 'References & Citations', 'icon' => 'fa-bookmark'],
                    ];
                    
                    foreach($doc_nav as $item): 
                    ?>
                    <a href="#<?= $item['id']; ?>" class="nav-link flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200 text-white/70 hover:bg-white/10 hover:text-white group">
                        <i class="fas <?= $item['icon']; ?> w-5 shrink-0 text-center text-teal-400/80 group-hover:text-teal-400"></i>
                        <span class="font-medium text-sm"><?= $item['title']; ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </aside>
            
            <!-- Download Button -->
            <button onclick="window.print()" class="download-btn group">
                <i class="fas fa-file-pdf text-teal-400 group-hover:text-white transition-colors"></i>
                <span class="font-bold">Download PDF</span>
            </button>

            <div id="documenter_content">
                <!-- Overview -->
                <section id="overview">
                    <h1>Velocity POS Management System</h1>
                    <h3>Overview</h3><hr class="notop">
                    <div style="background: #f0fdf4; border-left: 4px solid #10b981; padding: 20px; margin-bottom: 25px; border-radius: 0 8px 8px 0;">
                        <p style="margin-bottom: 0; font-weight: 500; color: #065f46;">Velocity POS is a high-performance retail management solution designed for speed, accuracy, and operational efficiency.</p>
                    </div>

                    <p>Welcome to the <strong>Velocity POS Documentation</strong>. This system is a comprehensive suite of tools designed to streamline every aspect of your retail environment—from local inventory tracking to advanced financial reporting and multi-store management.</p>
                    
                    <p>Whether you are a <strong>Store Owner</strong> looking for business insights, a <strong>Cashier</strong> processing rapid transactions, or a <strong>Developer</strong> extending the modular PHP core, this documentation provides the definitive path to mastering Velocity POS.</p>
                    
                    <h4>Core Objectives</h4>
                    <ul class="doc-list">
                        <li><strong>Operational Speed:</strong> Optimized POS interface for high-traffic environments.</li>
                        <li><strong>Data Integrity:</strong> Robust MySQL architecture with real-time stock synchronization.</li>
                        <li><strong>Financial Transparency:</strong> Automated accounting, banking, and detailed tax reporting.</li>
                        <li><strong>Modular Flexibility:</strong> Easy-to-extend codebase with global configuration patterns.</li>
                    </ul>
                    
                    <h4>Target Audience</h4>
                    <p>This guide is categorized into sections tailored for specific users:</p>
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; border: 1px solid #e2e8f0;">
                        <thead>
                            <tr style="background: #f8fafc;">
                                <th style="padding: 12px; border: 1px solid #e2e8f0; text-align: left;">User Role</th>
                                <th style="padding: 12px; border: 1px solid #e2e8f0; text-align: left;">Primary Focus</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="padding: 10px; border: 1px solid #e2e8f0;"><strong>Administrators</strong></td>
                                <td style="padding: 10px; border: 1px solid #e2e8f0;">System Setup, User Roles, Store Settings, and Tax Rules.</td>
                            </tr>
                            <tr>
                                <td style="padding: 10px; border: 1px solid #e2e8f0;"><strong>Managers</strong></td>
                                <td style="padding: 10px; border: 1px solid #e2e8f0;">Inventory, Purchases, Expense Tracking, and Reporting.</td>
                            </tr>
                            <tr>
                                <td style="padding: 10px; border: 1px solid #e2e8f0;"><strong>Cashiers</strong></td>
                                <td style="padding: 10px; border: 1px solid #e2e8f0;">Sales processing, Invoice Printing, and Daily Cashbooks.</td>
                            </tr>
                        </tbody>
                    </table>

                    <h4>System Requirements</h4>
                    <ul>
                        <li class="vimportant">PHP 8.0 - 8.3 (Recommended)</li>
                        <li class="vimportant">MySQL 5.7+ or MariaDB 10.4+</li>
                        <li>Apache Web Server with <code>mod_rewrite</code> enabled.</li>
                        <li>High-speed internet for CDN-based assets (Tailwind, jQuery).</li>
                    </ul>
                </section>

                <!-- Project Architecture -->
                <section id="architecture">
                    <h3>Technical Architecture</h3><hr class="notop">
                    <p>Velocity POS is built using a modular <strong>PHP & MySQL</strong> architecture, leveraging modern frontend tools for a desktop-class experience.</p>
                    
                    <div style="background: #ffffff; padding: 40px; border: 1px solid #e2e8f0; border-radius: 16px; margin: 30px 0; display: flex; flex-direction: column; align-items: center; text-align: center; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                        <svg width="650" height="220" viewBox="0 0 840 220" xmlns="http://www.w3.org/2000/svg" style="max-width: 100%; height: auto; display: block; margin-left: 100px;">
                            <!-- User / Client -->
                            <rect x="20" y="80" width="120" height="60" rx="12" fill="#f8fafc" stroke="#334155" stroke-width="2"/>
                            <text x="80" y="115" font-family="'Inter', sans-serif" font-size="14" font-weight="600" text-anchor="middle" fill="#1e293b">USER (Web)</text>
                            
                            <!-- Arrow 1: User to PHP -->
                            <line x1="140" y1="110" x2="220" y2="110" stroke="#94a3b8" stroke-width="2" />
                            <polygon points="220,110 212,105 212,115" fill="#94a3b8" />
                            <text x="180" y="100" font-family="'Inter', sans-serif" font-size="10" text-anchor="middle" fill="#64748b">Request</text>

                            <!-- PHP Logic Core -->
                            <rect x="240" y="60" width="180" height="100" rx="12" fill="#f0fdf4" stroke="#10b981" stroke-width="2"/>
                            <text x="330" y="105" font-family="'Inter', sans-serif" font-size="14" font-weight="700" text-anchor="middle" fill="#065f46">PHP CORE</text>
                            <text x="330" y="125" font-family="'Inter', sans-serif" font-size="11" text-anchor="middle" fill="#065f46">(Logic &amp; Permissions)</text>
                            
                            <!-- Arrow 2: PHP to DB -->
                            <path d="M420 100 L505 100" stroke="#94a3b8" stroke-width="2" stroke-dasharray="4" />
                            <polygon points="505,100 497,95 497,105" fill="#94a3b8" />
                            
                            <!-- Arrow 3: DB back to PHP -->
                            <path d="M505 120 L420 120" stroke="#94a3b8" stroke-width="2" stroke-dasharray="4" />
                            <polygon points="420,120 428,115 428,125" fill="#94a3b8" />
                            
                            <!-- MySQL Database -->
                            <path d="M520 80 Q 520 70 590 70 Q 660 70 660 80 L 660 140 Q 660 150 590 150 Q 520 150 520 140 Z" fill="#f1f5f9" stroke="#334155" stroke-width="2" />
                            <ellipse cx="590" cy="80" rx="70" ry="10" fill="none" stroke="#334155" stroke-width="1" />
                            <text x="590" y="115" font-family="'Inter', sans-serif" font-size="14" font-weight="600" text-anchor="middle" fill="#1e293b">MySQL DB</text>

                            <!-- Arrow 4: Output -->
                            <line x1="330" y1="160" x2="330" y2="185" stroke="#94a3b8" stroke-width="2" />
                            <polygon points="330,195 325,185 335,185" fill="#94a3b8" />
                            <text x="330" y="210" font-family="'Inter', sans-serif" font-size="11" font-weight="600" text-anchor="middle" fill="#64748b">OUTPUT: RECEIPTS / REPORTS</text>
                            
                            <!-- Assets -->
                            <rect x="270" y="10" width="120" height="30" rx="6" fill="#fef2f2" stroke="#ef4444" stroke-width="1"/>
                            <text x="330" y="29" font-family="'Inter', sans-serif" font-size="10" text-anchor="middle" fill="#b91c1c">CDNs / Assets</text>
                            <line x1="330" y1="40" x2="330" y2="60" stroke="#94a3b8" stroke-width="1"/>
                        </svg>
                        <p style="font-size: 13px; color: #64748b; font-style: italic; margin-top: 15px; font-family: 'Inter', sans-serif;">Fig 1: Modern System Architecture & Data Flow</p>
                    </div>

                    <h4>1. Global Documentation (Includes)</h4>
                    <p>The system uses a <em>Global Include Pattern</em> to maintain consistency across all pages. Key files in <code>includes/</code>:</p>
                    <ul>
                        <li><strong>header.php:</strong> Initializes sessions, checks authentication, sets store-specific currency, and loads all CSS/JS dependencies.</li>
                        <li><strong>footer.php:</strong> Handles page closing, DataTables initialization, and SweetAlert notifications.</li>
                        <li><strong>dbcon.php:</strong> Found in <code>config/</code>, this file maintains the database connection and automatically ensures core tables exist using <code>ensure_core_tables()</code>.</li>
                        <li><strong>permission_helper.php:</strong> A critical security layer that checks user roles before allowing access to specific modules.</li>
                    </ul>

                    <h4>2. Project Directory Structure</h4>
                    <p>How the project is organized:</p>
                    <ul>
                        <li><code>/admin</code>: Core administrative logs (Invoices, Sell Logs).</li>
                        <li><code>/assets</code>: Compiled CSS (Tailwind), custom scripts, and UI images.</li>
                        <li><code>/config</code>: Security, Authentication, and Database configurations.</li>
                        <li><code>/includes</code>: Reusable UI components (Modals, Helpers, Navigation).</li>
                        <li><code>/uploads</code>: Dynamic user content (Product images, Branding, CSVs).</li>
                        <li><code>/[Module Path]</code>: Each module (e.g., <code>/products</code>, <code>/sales</code>) contains its logical scripts.</li>
                    </ul>

                    <h4>3. Root Configuration Files</h4>
                    <p>These files in the root directory control the system's environment and build process:</p>
                    <ul>
                        <li><strong>.htaccess:</strong> Rules for the Apache web server, handling URL routing and protecting sensitive directories.</li>
                        <li><strong>package.json:</strong> Manages modern frontend dependencies and build scripts (Node.js).</li>
                        <li><strong>tailwind.config.js:</strong> Central configuration for the Tailwind CSS design system used in this project.</li>
                        <li><strong>logout.php:</strong> A standalone security script that safely terminates user sessions and redirects to the sign-in page.</li>
                    </ul>

                    <h4>4. External Dependencies (CDNs)</h4>
                    <p>The system integrates several high-performance libraries via CDN:</p>
                    <ul>
                        <li><strong>Tailwind CSS:</strong> For responsive and modern UI styling.</li>
                        <li><strong>jQuery (3.7.1):</strong> Powering the dynamic POS interface.</li>
                        <li><strong>DataTables (1.13.7):</strong> Providing advanced searching, sorting, and export (Excel/PDF/Print) capabilities.</li>
                        <li><strong>SweetAlert2 & Select2:</strong> For beautiful notifications and enhanced searchable dropdowns.</li>
                        <li><strong>Font Awesome (6.5.0):</strong> For ultra-crisp vector icons.</li>
                    </ul>
                </section>

                <!-- Installation -->
                <section id="installation">
                    <h3>Installation</h3><hr class="notop">
                    <p>Setting up Velocity POS is extremely simple. Follow these 3 easy steps:</p>
                    
                    <h4>1. Create a Database</h4>
                    <ul>
                        <li>Go to your database manager (e.g., <strong>phpMyAdmin</strong>).</li>
                        <li>Create a new empty database. (Example name: <code>pos_system</code>).</li>
                    </ul>
                    
                    <h4>2. Configure Connection</h4>
                    <ul>
                        <li>Open the file <code>config/dbcon.php</code> in your code editor.</li>
                        <li>Update the variables at the top to match your database details:</li>
                    </ul>
                    <pre>
$host = "localhost";        // Your Server Host
$username = "root";         // Your Database Username
$password = "";             // Your Database Password
$database = "pos_system";   // The Database Name you created
                    </pre>
                    
                    <h4>3. You are Ready!</h4>
                    <ul>
                        <li>That's it! Open your website in the browser.</li>
                        <li>The system will automatically connect and set everything up for you.</li>
                    </ul>
                </section>
                
                <!-- Dashboard -->
                <section id="dashboard">
                    <h3>Dashboard</h3><hr class="notop">
                    <p>The Dashboard is the central hub of your POS system. It provides a real-time overview of your business performance.</p>
                    
                    <br>
                    <img src="/pos/footer_pages/images/dashboard.png" alt="Velocity POS Dashboard" class="w-full rounded-lg shadow-md border border-gray-200 mb-6" onerror="this.style.display='none'">
                    
                    <h4>Key Metrices</h4>
                    <ul>
                        <li><strong>Today's Sales:</strong> Total revenue generated today.</li>
                        <li><strong>Today's Expenses:</strong> Total operational costs recorded today.</li>
                        <li><strong>Stock Alerts:</strong> Number of products running low on stock.</li>
                        <li><strong>Recent Transactions:</strong> Quick view of the latest 5 invoices.</li>
                    </ul>
                </section>

                <!-- Point of Sale -->
                <section id="point-of-sale">
                    <h3>Point of Sale (POS)</h3><hr class="notop">
                    <p>The POS interface is the heart of your daily operations, designed for speed and efficiency.</p>
                    
                    <br>
                    <img src="/pos/footer_pages/images/pos.png" alt="Velocity POS Interface" class="w-full rounded-lg shadow-md border border-gray-200 mb-6" onerror="this.style.display='none'">

                    <h4>Key Features</h4>
                    <ul>
                        <li><strong>Store Selection:</strong> Switch between different store branches instantly from the top bar.</li>
                        <li><strong>Product Search:</strong> Scan barcodes or type product names to add them to the cart instantly.</li>
                        <li><strong>Category Filter:</strong> Quickly filter products by category using the tabs on the left.</li>
                        <li><strong>Customer Association:</strong> Select a registered customer to track sales history or use "Walking Customer" for quick sales.</li>
                        <li><strong>Cart Management:</strong> Adjust quantities, remove items, or clear the cart with a single click.</li>
                    </ul>

                    <h4>Processing a Sale</h4>
                    <ol>
                        <li><strong>Add Items:</strong> Click on products or scan barcodes.</li>
                        <li><strong>Select Customer:</strong> (Optional) Linked customers allow for credit/due sales.</li>
                        <li><strong>Apply Adjustments:</strong> Add Discounts, Tax, or Shipping charges if applicable.</li>
                        <li><strong>Payment:</strong> Click <strong>PAY NOW</strong>. Select payment method (Cash, Card, Mobile Banking etc.).</li>
                        <li><strong>Receipt:</strong> The system automatically generates and prints a thermal receipt.</li>
                    </ol>
                    
                    <h4>Hold & Suspended Sales</h4>
                    <p>If a customer needs more time, you can click <strong>HOLD</strong> to save the cart and serve the next customer. Retrieve held orders later from the "Hold Order" menu.</p>

                    <h4>Pro Tips & Advanced Features</h4>
                    <ul>
                        <li><strong>Keyboard Shortcuts:</strong> Press the <i class="fas fa-keyboard"></i> icon to see available shortcuts for faster checkout.</li>
                        <li><strong>Product Details:</strong> Click on any product card to view stock availability across stores, image gallery, and related items.</li>
                        <li><strong>Real-time Stock Alerts:</strong> Monitor the <i class="fas fa-exclamation-triangle"></i> icon in the top bar to see which products are running low.</li>
                        <li><strong>Quick Tools:</strong> Use the <i class="fas fa-lock"></i> <strong>Lock Screen</strong> when stepping away, or <i class="fas fa-expand"></i> <strong>Fullscreen</strong> for a focused view.</li>
                        <li><strong>Reports Panel:</strong> Slide out the reports panel <i class="fas fa-file"></i> to check daily stats without leaving the POS screen.</li>
                    </ul>
                </section>

                <!-- Store Management -->
                <section id="stores">
                    <h3>Store Management</h3><hr class="notop">
                    <p>Velocity POS is built with multi-store support, allowing you to manage multiple business branches from a singe dashboard.</p>
                    
                    <h4>1. Adding Branches</h4>
                    <ul>
                        <li>Go to <strong>Stores > Add Store</strong> to register a new branch.</li>
                        <li>Each store can have its own Name, Phone, and unique receipt branding.</li>
                    </ul>

                    <h4>2. Store-Specific Settings</h4>
                    <ul>
                        <li><strong>Product Limits:</strong> Control how many products can be assigned to specific stores.</li>
                        <li><strong>Currency Config:</strong> If your branches are in different countries, you can set specific currency layouts per store.</li>
                        <li><strong>Permission Control:</strong> Assign staff to specific stores so they only see inventory and sales for their own branch.</li>
                    </ul>
                </section>

                <!-- Products -->
                <section id="products">
                    <h3>Products & Stock</h3><hr class="notop">
                    <p>The <strong>Products Module</strong> allows you to manage your inventory, organize items into categories, and track stock levels.</p>
                    
                    <h4>1. Adding a Product</h4>
                    <ul>
                        <li>Go to <strong>Products > Add Product</strong>.</li>
                        <li><strong>Basic Info:</strong> Enter Product Name, Category, Brand, and Unit (e.g., Kg, Pc).</li>
                        <li><strong>Pricing:</strong> Set the <strong>Purchase Price</strong> (Cost) and <strong>Selling Price</strong> (MRP).</li>
                        <li><strong>Stock:</strong> Enter the opening stock and an <strong>Alert Quantity</strong> (to get notified when low).</li>
                        <li><strong>Image:</strong> Upload a clear image for the POS grid.</li>
                    </ul>

                    <h4>2. Categories & Brands</h4>
                    <p>Organize your products for faster searching.</p>
                    <ul>
                        <li><strong>Categories:</strong> Broad groups like 'Electronics', 'Groceries'.</li>
                        <li><strong>Brands:</strong> Manufacturers like 'Samsung', 'Nestle'.</li>
                        <li><strong>Units:</strong> Define measurement units (Kg, Ltr, Box).</li>
                    </ul>

                    <h4>3. Stock Alerts</h4>
                    <p>The system automatically tracks inventory. When a product's stock falls below the <em>Alert Quantity</em>, it appears in the <strong>Stock Alert</strong> report and dashboard widget, prompting you to reorder.</p>
                </section>

                <!-- Stock Transfers -->
                <section id="transfers">
                    <h3>Stock Transfers</h3><hr class="notop">
                    <p>Easily move inventory between your different store branches.</p>
                    
                    <h4>1. send Stock</h4>
                    <ul>
                        <li>Go to <strong>Stock Transfer > Add Transfer</strong>.</li>
                        <li><strong>Select Store:</strong> Choosing the <em>From Store</em> (Source) and <em>To Store</em> (Destination).</li>
                        <li><strong>Add Products:</strong> Search and add items. The system will check if you have enough stock in the source store.</li>
                        <li><strong>Status:</strong> The transfer will be marked as <span class="badge-orange">PENDING</span> until received.</li>
                    </ul>

                    <h4>2. Receiving Stock</h4>
                    <ul>
                        <li>The destination store manager goes to <strong>Stock Transfer > Receive List</strong>.</li>
                        <li>Click <i class="fas fa-check-circle"></i> <strong>Receive</strong> to accept the goods.</li>
                        <li>The stock is automatically deducted from the Sender and added to the Receiver.</li>
                    </ul>
                </section>
                
                <!-- Product Attributes (Master Data) -->
                <section id="master-data">
                    <h3>Product Attributes</h3><hr class="notop">
                    <p>Configure the fundamental building blocks for your inventory.</p>
                    
                    <h4>1. Categories & Brands</h4>
                    <ul>
                        <li><strong>Categories:</strong> Group products logically (e.g., Electronics, Food). Go to <strong>Categories > Add Category</strong>.</li>
                        <li><strong>Brands:</strong> Define manufacturers (e.g., Samsung, Nike). Go to <strong>Brands > Add Brand</strong>.</li>
                    </ul>

                    <h4>2. Units & Boxes</h4>
                    <ul>
                        <li><strong>Units:</strong> Define how you sell items (e.g., Kg, pc, Liter). Go to <strong>Units > Add Unit</strong>.</li>
                        <li><strong>Boxes:</strong> If you sell in cartons/boxes, define them here (e.g., "Box of 12"). This helps in bulk purchasing.</li>
                    </ul>
                </section>
                <!-- Purchases -->
                <section id="purchases">
                    <h3>Purchases & Returns</h3><hr class="notop">
                    <p>Efficiently manage your procurement, supplier orders, and returns.</p>
                    
                    <br>
                    <img src="/pos/footer_pages/images/purchase.png" alt="Purchase Interface" class="w-full rounded-lg shadow-md border border-gray-200 mb-6" onerror="this.style.display='none'">

                    <h4>1. Adding a Purchase</h4>
                    <ul>
                        <li>Go to <strong>Purchases > Add Purchase</strong>.</li>
                        <li><strong>Select Supplier:</strong> Choose an existing supplier or add a new one instantly.</li>
                        <li><strong>Add Products:</strong> Search and add items to the purchase list.</li>
                        <li><strong>Payment:</strong> Enter the amount paid. The system will automatically record any <strong>Due Amount</strong> against the supplier.</li>
                    </ul>

                    <h4>2. Purchase List & Logs</h4>
                    <p>The <strong>Purchase List</strong> serves as your central log for all procurement activities.</p>
                    <ul>
                        <li><strong>View History:</strong> See a chronological list of all purchases.</li>
                        <li><strong>Filter:</strong> Search by Supplier, Date Range, or Purchase Status.</li>
                        <li><strong>Details:</strong> Click the <i class="fas fa-eye"></i> icon to see exactly what was bought, the cost price, and payment status.</li>
                    </ul>

                    <h4>3. Processing Returns</h4>
                    <p>If you receive defective or incorrect products, you can easily return them.</p>
                    <ol>
                        <li>Navigate to the <strong>Purchase List</strong>.</li>
                        <li>Find the specific purchase transaction.</li>
                        <li>Click the <i class="fas fa-undo"></i> <strong>Return</strong> button.</li>
                        <li>Select the items and quantity to return. The system will automatically adjust your stock levels and supplier balance.</li>
                    </ol>

                    <h4>4. Bulk Stock Import (CSV)</h4>
                    <p>You can update stock levels and prices in bulk using a CSV file. This is useful for large inventory updates.</p>
                    <ul>
                        <li>Go to <strong>Purchases > Stock Import</strong>.</li>
                        <li><strong>File Format:</strong> The CSV file must follow this exact column order: <br>
                            <code>product_code, quantity, purchase_price, selling_price, tax</code>
                        </li>
                        <li><strong>Process:</strong> Upload the file and click <strong>Import</strong>. The system will match products by their <em>Product Code</em> and update the stock/prices.</li>
                        <li><strong>Export:</strong> you can also export your current stock to CSV for auditing.</li>
                    </ul>
                </section>
                
                <!-- Sales -->
                <section id="sales">
                    <h3>Sales & Invoices</h3><hr class="notop">
                    <p>Track every transaction, manage customer dues, and handle returns with precision.</p>
                    
                    <h4>1. Invoice Management (Admin View)</h4>
                    <p>The Invoice List (<code>admin/invoice.php</code>) gives you a complete financial overview of every sale.</p>
                    <ul>
                        <li><strong>Status Tracking:</strong> Instantly see if an invoice is <span class="badge-green">PAID</span>, <span class="badge-red">DUE</span>, or <span class="badge-orange">INSTALLMENT</span>.</li>
                        <li><strong>Partial Payments:</strong> The system supports multiple payments per invoice. You can see the <em>Net Amount</em> vs <em>Paid Amount</em>.</li>
                        <li><strong>Actions:</strong>
                            <ul>
                                <li><i class="fas fa-money-bill"></i> <strong>Pay Due:</strong> Click the money icon to collect payment for a due invoice.</li>
                                <li><i class="fas fa-edit"></i> <strong>Edit:</strong> Update customer details or add admin notes.</li>
                                <li><i class="fas fa-undo"></i> <strong>Return:</strong> Return specific items from an invoice to stock.</li>
                            </ul>
                        </li>
                    </ul>

                    <h4>2. Sell Log (Transaction History)</h4>
                    <p>The <strong>Sell Log</strong> (<code>admin/sell_log.php</code>) records every single payment event.</p>
                    <ul>
                        <li><strong>Detailed Breakdown:</strong> Unlike the invoice list, this shows individual payments (e.g., "Customer paid $500 via Cash" on Monday, "Paid $200 via Card" on Tuesday).</li>
                        <li><strong>Payment Types:</strong> Filters for <span class="badge-purple">Full Payment</span>, <span class="badge-blue">Partial Payment</span>, and <span class="badge-teal">Installment</span>.</li>
                        <li><strong>Audit Trail:</strong> See exactly <em>who</em> (Cashier/Admin) processed the payment and <em>when</em>.</li>
                    </ul>

                    <h4>3. Quotations</h4>
                    <p>Create price estimates for customers without affecting inventory.</p>
                    <ul>
                        <li>Go to <strong>Sales > Quotation List</strong>.</li>
                        <li>Create a quote similar to a sale.</li>
                        <li>Convert a <strong>Quotation</strong> to a <strong>Sale</strong> with one click when the customer confirms.</li>
                    </ul>
                </section>
                     <!-- Installments -->
                <section id="installments">
                    <h3>Installment & Credit Sales</h3><hr class="notop">
                    <p>Manage long-term credit sales with structured installment plans.</p>
                    
                    <h4>1. Installment Overview</h4>
                    <p>The <strong>Installment Dashboard</strong> (<code>installment/overview.php</code>) gives you a snapshot of:</p>
                    <ul>
                        <li><strong>Total Active Plans:</strong> Number of customers currently on an installment plan.</li>
                        <li><strong>Upcoming Due:</strong> Payments expected soon.</li>
                        <li><strong>Defaulters:</strong> List of customers who missed their payment dates.</li>
                    </ul>

                    <h4>2. Managing Plans</h4>
                    <ul>
                        <li><strong>Create Plan:</strong> When making a sale, select "Installment" as the type. You can define the <em>Down Payment</em>, <em>Interest Rate (%)</em>, and <em>Number of Months</em>.</li>
                        <li><strong>Collect Payment:</strong> Go to <strong>Installment List</strong>, find the customer, and click <i class="fas fa-money-bill"></i> <strong>Pay</strong> to record a monthly installment.</li>
                    </ul>
                </section>

                <!-- Loan -->
                <section id="loan">
                    <h3>Loan Management</h3><hr class="notop">
                    <p>Track money borrowed from banks or personal lenders.</p>
                    
                    <h4>1. Personal & Bank Loans</h4>
                    <ul>
                        <li><strong>Take Loan:</strong> Go to <strong>Loan > Add Loan</strong>. Record the Lender Name, Amount, and Interest Rate.</li>
                        <li><strong>Loan List:</strong> View all active loans and their repayment status.</li>
                    </ul>

                    <h4>2. Repayment</h4>
                    <ul>
                        <li>When you pay back a loan (full or partial), record it in the system to update your liability.</li>
                        <li><strong>Summary Report:</strong> Check <strong>Loan Summary</strong> to see your total debt and interest paid over time.</li>
                    </ul>
                </section>
                   <!-- Payments & Gifting -->
                <section id="payments-gifting">
                    <h3>Payments & Gift Cards</h3><hr class="notop">
                    <p>Manage how you get paid and offer customer incentives.</p>

                    <h4>1. Payment Methods</h4>
                    <ul>
                        <li>Go to <strong>Payment Methods</strong> to configure accepted types (Cash, Card, Mobile Banking).</li>
                        <li>You can enable/disable methods dynamically at checkout.</li>
                    </ul>

                    <h4>2. Gift Cards</h4>
                    <ul>
                        <li><strong>Create Cards:</strong> Generate gift cards with a specific value and expiry date in <strong>Gift Card > Add Gift Card</strong>.</li>
                        <li><strong>Sell/Issue:</strong> Give the card number to a customer.</li>
                        <li><strong>Redeem:</strong> At checkout, the customer can pay using their Gift Card balance.</li>
                    </ul>
                </section>

              

                <!-- Customers -->
                <section id="customers">
                    <h3>Customers & Suppliers</h3><hr class="notop">
                    <p>Build relationships with your clients and manage your supply chain.</p>
                    
                    <h4>1. Customers & Credit</h4>
                    <ul>
                        <li><strong>Add Customer:</strong> Save details like Name, Phone, and Address for recurring clients.</li>
                        <li><strong>Credit/Due Management:</strong>
                            <ul>
                                <li>The system automatically tracks any unpaid amount from sales.</li>
                                <li>View the <strong>Customer Due Report</strong> to see who owes you money.</li>
                                <li><strong>Receive Payment:</strong> Go to the customer profile to accept due payments.</li>
                            </ul>
                        </li>
                    </ul>

                    <h4>2. Suppliers</h4>
                    <ul>
                        <li><strong>Add Supplier:</strong> Go to <strong>Suppliers > Add Supplier</strong> to register vendors.</li>
                        <li><strong>Purchase History:</strong> Click on a supplier to see a complete log of everything you bought from them.</li>
                        <li><strong>Supplier Due:</strong> Track how much you owe them and record payments via the <strong>Supplier Ledger</strong>.</li>
                    </ul>
                </section>
                
                <!-- Accounting -->
                <section id="accounting">
                    <h3>Accounting & Banking</h3><hr class="notop">
                    <p>Keep track of your business finances with a built-in ledger and banking module.</p>
                    
                    <h4>1. Bank Management</h4>
                    <ul>
                        <li><strong>Register Banks:</strong> Add your business bank accounts in <strong>Accounting > Bank List</strong>.</li>
                        <li><strong>Balance Tracking:</strong> The system maintains a real-time balance for each account.</li>
                        <li><strong>Transactions:</strong> Record Deposits, Withdrawals, and Bank Transfers between accounts or from your Cash drawer.</li>
                        <li><strong>Bank Ledger:</strong> View a detailed statement of all activities for a specific bank account.</li>
                    </ul>

                    <h4>2. Multi-Income & Expenses</h4>
                    <ul>
                        <li><strong>Income Sources:</strong> Record revenue from non-sales activities (e.g., Service fees, asset sales).</li>
                        <li><strong>Expense Tracking:</strong> Categorize your costs (Rent, Salaries, Utilities) to understand where your money is going.</li>
                    </ul>

                    <h4>3. Cashbook</h4>
                    <ul>
                        <li>A daily automated ledger showing the opening balance, daily cash-in (sales/income), cash-out (purchases/expenses), and the final closing balance.</li>
                        <li>Essential for daily cash reconciliation at the end of a shift.</li>
                    </ul>
                </section>
                
                <!-- Reports -->
                <section id="reports">
                    <h3>Advanced Reporting</h3><hr class="notop">
                    <p>Make data-driven decisions with over 15+ specialized reports.</p>
                    
                    <h4>1. Financial Reports</h4>
                    <ul>
                        <li><strong>Profit & Loss:</strong> A comprehensive view of Gross Profit, Expenses, and Net Profit over any date range.</li>
                        <li><strong>Balance Sheet:</strong> Summary of your assets (Stock value, Bank balances) vs Liabilities (Supplier dues).</li>
                        <li><strong>Income vs Expense:</strong> A comparative monthly view of your operational efficiency.</li>
                    </ul>

                    <h4>2. Sales & Inventory Reports</h4>
                    <ul>
                        <li><strong>Sell Report:</strong> Filtered views of sales by store, product, or customer.</li>
                        <li><strong>Stock Report:</strong> Current stock levels, total stock value (at purchase price), and potential revenue (at selling price).</li>
                        <li><strong>Stock Alert:</strong> Instantly see what needs reordering.</li>
                        <li><strong>Tax Reports:</strong> Specialized reports for Purchase Tax and Sell Tax for easy legal filing.</li>
                    </ul>

                    <h4>3. Movement Reports</h4>
                    <ul>
                        <li><strong>Transfer Report:</strong> History of all stock moved between branches.</li>
                        <li><strong>Purchase Report:</strong> Log of all procurement and vendor payments.</li>
                        <li><strong>Collection Report:</strong> Detailed view of cash/check collections from customers.</li>
                    </ul>
                </section>
                
                <!-- Users & Roles -->
                <section id="users-roles">
                    <h3>Users & Roles</h3><hr class="notop">
                    <p>Secure your business by controlling who can access what.</p>
                    
                    <h4>1. User Groups (Roles)</h4>
                    <ul>
                        <li>Go to <strong>User Management > User Groups</strong>.</li>
                        <li><strong>Create Roles:</strong> Define roles like <em>Manager</em>, <em>Cashier</em>, or <em>Accountant</em>.</li>
                        <li><strong>Permissions:</strong> Check/Uncheck boxes to grant specific access (e.g., Allow "Sales" but block "Reports").</li>
                    </ul>

                    <h4>2. Adding Users</h4>
                    <ul>
                        <li>Go to <strong>User Management > Add User</strong>.</li>
                        <li>Enter details: Name, Email, Password, and select a <strong>Role</strong>.</li>
                        <li><strong>Store Access:</strong> You can assign a user to a specific store or all stores.</li>
                    </ul>
                </section>

                <!-- Settings -->
                <section id="settings">
                    <h3>Settings</h3><hr class="notop">
                    <p>Configure the system to match your business needs.</p>
                    
                    <h4>1. General Settings</h4>
                    <ul>
                        <li><strong>Store Info:</strong> Set your Store Name, Address, and Phone (appears on receipts).</li>
                        <li><strong>Currency:</strong> Set your currency symbol (e.g., $, ৳, £).</li>
                    </ul>

                    <h4>2. Printer Setup</h4>
                    <p>Configure your hardware for seamless receipt printing from <strong>Settings > Printers</strong>.</p>
                    <ul>
                        <li><strong>Printers:</strong> Add Network (IP), USB, or Local system printers.</li>
                        <li><strong>Receipt Templates:</strong> Customize logos, headers, footers, and choose between Standard or Compact styles.</li>
                    </ul>

                    <h4>3. Database Backup</h4>
                    <p class="vimportant">Regularly download a database backup from the Settings menu to ensure your data is safe in case of computer failure.</p>
                </section>


                <!-- Tax Rates -->
                <section id="tax-rules">
                    <h3>Tax Rules</h3><hr class="notop">
                    <p>Ensure legal compliance by automating tax calculations.</p>
                    <ul>
                        <li>Go to <strong>Tax Rates > Add Tax Rate</strong>.</li>
                        <li>Define names like "VAT 5%" or "GST 18%".</li>
                        <li>Apply these rates to specific products. The system will automatically calculate the tax amount during sales.</li>
                    </ul>
                </section>

                <!-- References & Citations -->
                <section id="references">
                    <h3>References & Citations</h3><hr class="notop">
                    <p>Velocity POS is built upon high-quality technologies and best practices in modern web development. Below are the key references and instructions for institutional or professional citation.</p>
                    
                    <h4>1. Technical References</h4>
                    <p>The system leverages several industry-standard tools and libraries:</p>
                    <ul>
                        <li><strong>PHP (Hypertext Preprocessor):</strong> Used for core business logic and session management. <a href="https://www.php.net" target="_blank">php.net</a></li>
                        <li><strong>MySQL:</strong> Relational database engine for secure data persistence. <a href="https://www.mysql.com" target="_blank">mysql.com</a></li>
                        <li><strong>Tailwind CSS Framework:</strong> For utility-first responsive UI design. <a href="https://tailwindcss.com" target="_blank">tailwindcss.com</a></li>
                        <li><strong>DataTables jQuery Plugin:</strong> Advanced table interactions and reporting exports. <a href="https://datatables.net" target="_blank">datatables.net</a></li>
                        <li><strong>Font Awesome:</strong> Professional vector iconography. <a href="https://fontawesome.com" target="_blank">fontawesome.com</a></li>
                    </ul>

                    <h4>2. Academic & Professional Citation</h4>
                    <p>If you are using Velocity POS in an academic paper, case study, or corporate report, please use the following citation format:</p>
                    
                    <div style="background: #f8fafc; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px; font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace; font-size: 14px;">
                        Velocity POS Management System. (2026). Professional Retail & Inventory Suite. Developed for Velocity POS Hub. Version 1.2.0.
                    </div>

                    <h4>3. Version History</h4>
                    <table style="width: 100%; border-collapse: collapse; margin-top: 20px; border: 1px solid #e2e8f0;">
                        <thead>
                            <tr style="background: #f8fafc;">
                                <th style="padding: 10px; border: 1px solid #e2e8f0; text-align: left;">Version</th>
                                <th style="padding: 10px; border: 1px solid #e2e8f0; text-align: left;">Date</th>
                                <th style="padding: 10px; border: 1px solid #e2e8f0; text-align: left;">Key Update</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="padding: 10px; border: 1px solid #e2e8f0;">v1.2.0 (Current)</td>
                                <td style="padding: 10px; border: 1px solid #e2e8f0;">Feb 2026</td>
                                <td style="padding: 10px; border: 1px solid #e2e8f0;">Enhanced Documentation & Mobile UI support.</td>
                            </tr>
                            <tr>
                                <td style="padding: 10px; border: 1px solid #e2e8f0;">v1.1.5</td>
                                <td style="padding: 10px; border: 1px solid #e2e8f0;">Jan 2026</td>
                                <td style="padding: 10px; border: 1px solid #e2e8f0;">Multi-currency & Store assignment fixes.</td>
                            </tr>
                        </tbody>
                    </table>

                    <div style="margin-top: 40px; text-align: center; color: #64748b; font-size: 14px; letter-spacing: 0.5px;">
                        &copy; <?= date('Y'); ?> Velocity POS. Developed with <span class="text-red-500">❤️</span> by <strong>Sharif, Takmin & Sadiya</strong>
                    </div>
                </section>
             

            </div>
        </div>
    </div>

    <!-- 4. Footer (Scrollable) -->

</div>


<style>
    /* --- LATEX PROFESSIONAL DESIGN SYSTEM --- */
    @import url('https://fonts.googleapis.com/css2?family=Merriweather:ital,wght@0,300;0,400;0,700;0,900;1,300;1,400&family=Inter:wght@400;500;600&display=swap');

    /* 1. Global Page Setup */
    body {
        background-color: #f1f5f9 !important; /* Neutral background */
        font-family: 'Inter', system-ui, -apple-system, sans-serif !important;
        color: #1e293b !important;
        line-height: 1.7;
    }

    /* 2. Sidebar (Functional & Modern) */
    .sidebar {
        background-color: #334155 !important; /* Soft Slate (Not Black) */
        border-right: 1px solid rgba(255,255,255,0.1);
        box-shadow: none;
        font-family: 'Inter', sans-serif !important;
    }
    .sidebar a { text-decoration: none !important; }

    /* 3. Paper Layout (Centered Single Column) */
    .doc-container {
        display: flex; /* Back to flex */
        justify-content: center; /* Center it */
        padding-left: 256px; /* Offset by sidebar width to center in REMAINING space */
        padding-top: 40px;
        padding-bottom: 80px;
        min-height: 100vh;
    }

    #documenter_content {
        max-width: 900px;
        width: 100%;
        background: #ffffff;
        padding: 50px 70px;
        box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
        border-radius: 16px;
        min-height: calc(100vh - 120px);
        margin: 0 auto; /* Natural centering in container */
    }

    /* 4. Section Styling (Continuous Document Flow) */
    #documenter_content section {
        background: transparent;
        border: none;
        box-shadow: none;
        border-radius: 0;
        padding: 0;
        margin-top: 0 !important; /* Force reset */
        margin-bottom: 40px;
        backdrop-filter: none;
        width: 100%;
        display: block;
        break-inside: auto;
    }

    #documenter_content section:hover {
        transform: none;
        box-shadow: none;
    }

    /* 5. Typography & Formatting */
    
    /* Document Title */
    #documenter_content h1 {
        font-family: 'Inter', sans-serif;
        font-size: 38px;
        font-weight: 800;
        color: #0f172a; /* Deep Navy */
        border-bottom: 2px solid #e2e8f0;
        padding-bottom: 20px;
        margin-bottom: 40px;
        letter-spacing: -0.5px;
        background: none;
        -webkit-text-fill-color: initial;
    }

    /* Section Headings */
    #documenter_content h3 {
        font-size: 22px;
        font-weight: 700;
        color: #1e293b; /* Slate Navy */
        margin-top: 40px;
        margin-bottom: 20px;
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 8px;
        display: block;
    }
    #documenter_content h3::before { content: none; } /* Remove green bar */

    /* Sub-headings */
    #documenter_content h4 {
        font-size: 18px;
        font-weight: 700;
        color: #444;
        margin-top: 24px;
        margin-bottom: 12px;
        font-family: 'Inter', sans-serif;
    }

    /* Paragraphs */
    #documenter_content p {
        margin-bottom: 18px;
        font-size: 16px;
        text-align: justify; /* Academic justification */
        color: #333;
    }

    #documenter_content li {
        margin-bottom: 8px;
        color: #333;
    }

    /* Code Blocks */
    pre {
        background: #f8f9fa !important;
        border: 1px solid #e9ecef;
        border-left: 4px solid #1a1a1a; /* Accent bar */
        border-radius: 4px;
        font-family: 'Menlo', 'Monaco', monospace;
        font-size: 13px;
        padding: 20px;
        margin: 20px 0;
        box-shadow: none;
        color: #333 !important;
    }

    /* Horizontal Rules */
    #documenter_content hr {
        border: none;
        border-top: 1px solid #eee;
        margin: 40px 0;
        background: none;
        height: 0;
    }

    /* Overrides for specific elements to fit plain style */
    .vimportant { color: #d32f2f; }
    
    /* Badges for Documentation */
    .badge-green { background: #dcfce7; color: #166534; padding: 2px 6px; border-radius: 4px; font-size: 0.8em; font-weight: bold; }
    .badge-red { background: #ffe4e6; color: #9f1239; padding: 2px 6px; border-radius: 4px; font-size: 0.8em; font-weight: bold; }
    .badge-orange { background: #ffedd5; color: #9a3412; padding: 2px 6px; border-radius: 4px; font-size: 0.8em; font-weight: bold; }
    .badge-purple { background: #f3e8ff; color: #6b21a8; padding: 2px 6px; border-radius: 4px; font-size: 0.8em; font-weight: bold; }
    .badge-blue { background: #dbeafe; color: #1e40af; padding: 2px 6px; border-radius: 4px; font-size: 0.8em; font-weight: bold; }
    .badge-teal { background: #ccfbf1; color: #115e59; padding: 2px 6px; border-radius: 4px; font-size: 0.8em; font-weight: bold; }

    /* Download Button Styling */
    .download-btn {
        position: fixed;
        top: 25px;
        right: 40px;
        z-index: 6000;
        background: #1e293b;
        color: white;
        padding: 10px 24px;
        border-radius: 12px;
        border: 1px solid rgba(255,255,255,0.1);
        display: flex;
        align-items: center;
        gap: 10px;
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        cursor: pointer;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .download-btn:hover {
        background: #0d9488;
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.3);
        border-color: #14b8a6;
    }

    .download-btn:active {
        transform: translateY(0);
    }

    /* Print Optimization */
    @media print {
        .download-btn, aside, .sidebar { display: none !important; }
        .doc-container { padding-left: 0 !important; padding-top: 0 !important; background: white !important; }
        #documenter_content { 
            box-shadow: none !important; 
            border: none !important; 
            width: 100% !important; 
            max-width: 100% !important; 
            margin: 0 !important; 
            padding: 40px !important; 
        }
        #documenter_content h1 { font-size: 28px !important; border-bottom: 2px solid #333 !important; }
        body { background: white !important; }
        footer, .mt-40 { display: none !important; }
        section { page-break-inside: avoid; break-inside: avoid; margin-bottom: 50px !important; }
    }

    /* Responsive */
    @media (max-width: 1024px) {
        #documenter_content {
            margin-left: 0 !important;
            padding: 40px 20px;
            max-width: 100%;
        }
        .download-btn {
            top: auto;
            bottom: 25px;
            right: 25px;
            padding: 12px 20px;
        }
    }

</style>

<!-- Scripts for smooth scrolling & Scroll Spy -->
<script>
    // Smooth Scroll on Click
    document.querySelectorAll('aside a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if(target){
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Scroll Spy Logic
    document.addEventListener('DOMContentLoaded', () => {
        const sections = document.querySelectorAll('section');
        const navLinks = document.querySelectorAll('aside .nav-link');
        
        function changeActiveLink() {
            let currentSection = '';
            
            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                if (window.scrollY >= (sectionTop - 150)) { // 150px offset for header/padding
                    currentSection = section.getAttribute('id');
                }
            });
            
            // Default to overview if at top
            if(window.scrollY < 100) currentSection = 'overview';

            navLinks.forEach(link => {
                link.classList.remove('active-submenu-bg', 'text-white', 'shadow-lg');
                link.classList.add('text-white/70');
                
                if (link.getAttribute('href') === '#' + currentSection) {
                    link.classList.add('active-submenu-bg', 'text-white', 'shadow-lg');
                    link.classList.remove('text-white/70');
                }
            });
        }
        
        window.addEventListener('scroll', changeActiveLink);
    });
</script>
</body>
</html>
