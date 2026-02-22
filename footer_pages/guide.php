<?php
session_start();
include('../config/dbcon.php');

$page_title = "User Guide - Velocity POS";
include('../includes/header.php');
?>

<div class="app-wrapper">
    <?php include('../includes/sidebar.php'); ?>

    <main id="main-content" class="flex-1 lg:ml-64 flex flex-col h-screen min-w-0 transition-all duration-300">
        <div class="navbar-fixed-top">
            <?php include('../includes/navbar.php'); ?>
        </div>

        <div class="content-scroll-area custom-scroll">
            <div class="p-6">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-8 max-w-5xl mx-auto">
                    <h1 class="text-3xl font-bold text-slate-800 mb-2">User Guide</h1>
                    <p class="text-slate-500 mb-8">Comprehensive documentation for using Velocity POS effectively.</p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Section 1: Getting Started -->
                        <div class="prose max-w-none">
                            <h3 class="text-xl font-bold text-indigo-600 flex items-center gap-2">
                                <i class="fas fa-rocket"></i> Getting Started
                            </h3>
                            <ul class="list-disc pl-5 mt-3 space-y-2 text-slate-600">
                                <li><strong>Login:</strong> Access the system using your secure credentials.</li>
                                <li><strong>Dashboard:</strong> View key metrics, sales charts, and quick actions immediately upon login.</li>
                                <li><strong>Navigation:</strong> Use the sidebar to access different modules like POS, Products, and Reports.</li>
                            </ul>
                        </div>

                        <!-- Section 2: POS Operation -->
                        <div class="prose max-w-none">
                            <h3 class="text-xl font-bold text-emerald-600 flex items-center gap-2">
                                <i class="fas fa-cash-register"></i> POS Operation
                            </h3>
                            <ul class="list-disc pl-5 mt-3 space-y-2 text-slate-600">
                                <li><strong>New Sale:</strong> Navigate to the POS screen to start a transaction.</li>
                                <li><strong>Adding Products:</strong> Scan barcodes or search by name to add items to the cart.</li>
                                <li><strong>Customers:</strong> Select an existing customer or add a walk-in customer.</li>
                                <li><strong>Checkout:</strong> Choose payment method (Cash, Card, etc.) and complete the sale.</li>
                            </ul>
                        </div>

                        <!-- Section 3: Product Management -->
                        <div class="prose max-w-none">
                            <h3 class="text-xl font-bold text-orange-600 flex items-center gap-2">
                                <i class="fas fa-box-open"></i> Product Management
                            </h3>
                            <ul class="list-disc pl-5 mt-3 space-y-2 text-slate-600">
                                <li><strong>Add Product:</strong> Go to Products > Add Product. Fill in details like name, price, stock, and category.</li>
                                <li><strong>Inventory:</strong> Monitor stock levels and receive low-stock alerts.</li>
                                <li><strong>Barcodes:</strong> Generate and print barcodes for your inventory items.</li>
                            </ul>
                        </div>

                        <!-- Section 4: Reports -->
                        <div class="prose max-w-none">
                            <h3 class="text-xl font-bold text-blue-600 flex items-center gap-2">
                                <i class="fas fa-chart-line"></i> Reports & Analytics
                            </h3>
                            <ul class="list-disc pl-5 mt-3 space-y-2 text-slate-600">
                                <li><strong>Sales Report:</strong> View detailed sales history by date range.</li>
                                <li><strong>Profit & Loss:</strong> Analyze your business profitability.</li>
                                <li><strong>Expense Tracking:</strong> Keep track of operational expenses.</li>
                            </ul>
                        </div>
                    </div>

                    <div class="mt-10 p-6 bg-slate-50 rounded-xl border border-slate-200">
                        <h4 class="font-bold text-slate-700 mb-2">Need more help?</h4>
                        <p class="text-slate-600 text-sm">For technical issues or specific inquiries, please visit the <a href="/pos/support" class="text-indigo-600 hover:underline">Support Page</a>.</p>
                    </div>
                </div>
            </div>
            <?php include('../includes/footer.php'); ?>
        </div>
    </main>
</div>
