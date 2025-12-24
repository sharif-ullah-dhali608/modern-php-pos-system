<?php
session_start();
include('config/dbcon.php');

if(!isset($_SESSION['auth'])){
    header("Location: /pos/login");
    exit(0);
}

$page_title = "Dashboard - Velocity POS";
include('includes/header.php');
?>

<div class="app-wrapper">
    <?php include('includes/sidebar.php'); ?>

    <main id="main-content" class="lg:ml-64 flex flex-col h-screen">
        <div class="navbar-fixed-top">
            <?php include('includes/navbar.php'); ?>
        </div>
        
        <div class="content-scroll-area custom-scroll">
            <div class="p-6">
                <div class="mb-8 slide-in">
                    <h1 class="text-3xl font-bold text-slate-800 mb-2">Dashboard Overview</h1> 
                    <div class="flex items-center gap-2 text-sm text-slate-500">
                        <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                        <span>Welcome back, <?= isset($_SESSION['auth_user']['name']) ? htmlspecialchars($_SESSION['auth_user']['name']) : 'Admin'; ?></span>
                    </div>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-xl p-6 shadow-lg border border-slate-100 slide-in">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white shadow-lg">
                                <i class="fas fa-store text-xl"></i>
                            </div>
                            <span class="text-sm text-slate-500">Total</span> </div>
                        <?php
                        $stores_query = "SELECT COUNT(*) as total FROM stores";
                        $stores_result = mysqli_query($conn, $stores_query);
                        $stores_data = mysqli_fetch_assoc($stores_result);
                        ?>
                        <h3 class="text-3xl font-bold text-slate-800 mb-1"><?= $stores_data['total']; ?></h3> <p class="text-sm text-slate-600">Active Stores</p> 
                        <a href="/pos/stores/list" class="text-xs text-blue-600 hover:text-blue-700 mt-2 inline-block">View all →</a> </div>

                    <div class="bg-white rounded-xl p-6 shadow-lg border border-slate-100 slide-in" style="animation-delay: 0.1s;">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center text-white shadow-lg">
                                <i class="fas fa-dollar-sign text-xl"></i>
                            </div>
                            <span class="text-sm text-slate-500">Total</span>
                        </div>
                        <?php
                        $currency_query = "SELECT COUNT(*) as total FROM currencies";
                        $currency_result = mysqli_query($conn, $currency_query);
                        $currency_data = mysqli_fetch_assoc($currency_result);
                        ?>
                        <h3 class="text-3xl font-bold text-slate-800 mb-1"><?= $currency_data['total']; ?></h3>
                        <p class="text-sm text-slate-600">Currencies</p>
                        <a href="/pos/currency/list" class="text-xs text-purple-600 hover:text-purple-700 mt-2 inline-block">View all →</a>
                    </div>

                    <div class="bg-white rounded-xl p-6 shadow-lg border border-slate-100 slide-in" style="animation-delay: 0.2s;">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center text-white shadow-lg">
                                <i class="fas fa-users text-xl"></i>
                            </div>
                            <span class="text-sm text-slate-500">Total</span>
                        </div>
                        <?php
                        $users_query = "SELECT COUNT(*) as total FROM users";
                        $users_result = mysqli_query($conn, $users_query);
                        $users_data = mysqli_fetch_assoc($users_result);
                        ?>
                        <h3 class="text-3xl font-bold text-slate-800 mb-1"><?= $users_data['total'] ?? 0; ?></h3>
                        <p class="text-sm text-slate-600">Users</p>
                        <a href="" class="text-xs text-green-600 hover:text-green-700 mt-2 inline-block">View all →</a>
                    </div>

                    <div class="bg-white rounded-xl p-6 shadow-lg border border-slate-100 slide-in" style="animation-delay: 0.3s;">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-orange-500 to-orange-600 flex items-center justify-center text-white shadow-lg">
                                <i class="fas fa-chart-line text-xl"></i>
                            </div>
                            <span class="text-sm text-slate-500">Today</span>
                        </div>
                        <h3 class="text-3xl font-bold text-slate-800 mb-1">৳ 0</h3>
                        <p class="text-sm text-slate-600">Total Sales</p>
                        <a href="#" class="text-xs text-orange-600 hover:text-orange-700 mt-2 inline-block">View report →</a>
                    </div>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <a href="/pos/stores/add" class="bg-white rounded-xl p-6 shadow-lg border border-slate-100 hover:scale-105 transition-transform slide-in group cursor-pointer">
                        <div class="flex items-center gap-4">
                            <div class="w-16 h-16 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white shadow-lg group-hover:shadow-xl transition-shadow">
                                <i class="fas fa-plus text-2xl"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-slate-800 mb-1">Add New Store</h3>
                                <p class="text-sm text-slate-600">Create new branch</p>
                            </div>
                        </div>
                    </a>

                    <a href="/pos/currency/add" class="bg-white rounded-xl p-6 shadow-lg border border-slate-100 hover:scale-105 transition-transform slide-in group cursor-pointer" style="animation-delay: 0.1s;">
                        <div class="flex items-center gap-4">
                            <div class="w-16 h-16 rounded-xl bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center text-white shadow-lg group-hover:shadow-xl transition-shadow">
                                <i class="fas fa-dollar-sign text-2xl"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-slate-800 mb-1">Add Currency</h3>
                                <p class="text-sm text-slate-600">Manage currencies</p>
                            </div>
                        </div>
                    </a>

                    <a href="/pos/stores/list" class="bg-white rounded-xl p-6 shadow-lg border border-slate-100 hover:scale-105 transition-transform slide-in group cursor-pointer" style="animation-delay: 0.2s;">
                        <div class="flex items-center gap-4">
                            <div class="w-16 h-16 rounded-xl bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center text-white shadow-lg group-hover:shadow-xl transition-shadow">
                                <i class="fas fa-list text-2xl"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-slate-800 mb-1">View All Stores</h3>
                                <p class="text-sm text-slate-600">Browse stores</p>
                            </div>
                        </div>
                    </a>
                </div>
            </div> 
            <?php include('includes/footer.php'); ?>
        </div> 
    </main>
</div>