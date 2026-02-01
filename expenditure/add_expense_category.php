<?php
session_start();
include('../config/dbcon.php');

if(!isset($_SESSION['auth'])){
    header("Location: /pos/login");
    exit(0);
}

$mode = "create";
$btn_text = "Save Category";
$page_title = "New Expense Category";

$d = [
    'category_id' => '',
    'category_name' => '',
    'category_details' => '',
    'status' => '1',
    'sort_order' => '0'
];

if(isset($_GET['id'])) {
    $mode = "edit";
    $btn_text = "Update Category";
    $page_title = "Edit Expense Category";
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    
    $res = mysqli_query($conn, "SELECT * FROM expense_category WHERE category_id='$id' LIMIT 1");
    if($row = mysqli_fetch_assoc($res)){
        $d = array_merge($d, $row);
    }
}

include('../includes/header.php');
?>

<link rel="stylesheet" href="/pos/assets/css/expenditureCss/add_expense_categoryCss.css">

<div class="app-wrapper">
    <?php include('../includes/sidebar.php'); ?>
    
    <main id="main-content" class="flex-1 lg:ml-64 flex flex-col h-screen min-w-0 transition-all duration-300 bg-slate-50">
        <div class="navbar-fixed-top"><?php include('../includes/navbar.php'); ?></div> 
        
        <div class="content-scroll-area custom-scroll h-full overflow-y-auto">
            <div class="p-4 md:p-6 max-w-[1600px] mx-auto">
                <!-- Header -->
                <div class="mb-6 slide-in flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <a href="/pos/expenditure/category_list" class="w-10 h-10 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-600 hover:text-teal-600 hover:border-teal-200 shadow-sm transition-all">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <div>
                            <h1 class="text-2xl md:text-3xl font-bold text-slate-800"><?= $page_title; ?></h1>
                            <div class="flex items-center gap-2 text-sm text-slate-500 mt-1">
                                <?php if($mode == 'create'): ?>
                                    <span class="w-2 h-2 rounded-full bg-teal-500 animate-pulse"></span>
                                    <span>Define a new category for tracking expenses</span>
                                <?php else: ?>
                                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                                    <span>Update category details</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <form action="/pos/expenditure/save_category" method="POST" id="categoryForm" novalidate autocomplete="off" class="slide-in delay-100">
                    <?php if($mode == 'edit'): ?>
                        <input type="hidden" name="category_id" value="<?= $d['category_id'] ?>">
                        <input type="hidden" name="update_category_btn" value="true">
                    <?php else: ?>
                        <input type="hidden" name="save_category_btn" value="true">
                    <?php endif; ?>

                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                        <!-- Left Column: Category Info -->
                        <div class="lg:col-span-8 space-y-6">
                            <div class="bg-white rounded-[2.5rem] shadow-xl border border-slate-100 p-8 relative overflow-hidden">
                                <div class="absolute top-0 right-0 w-32 h-32 bg-teal-50 rounded-bl-[4rem] -mr-8 -mt-8 opacity-50 pointer-events-none"></div>
                                
                                <h2 class="text-lg font-black text-slate-800 mb-8 flex items-center gap-3 relative z-10">
                                    <span class="w-10 h-10 rounded-xl bg-teal-100 text-teal-600 flex items-center justify-center text-lg"><i class="fas fa-tags"></i></span>
                                    Category Information
                                </h2>
                                
                                <div class="space-y-6 relative z-10">
                                    <!-- Category Name -->
                                    <div class="relative w-full group">
                                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Category Name *</label>
                                        <div class="relative">
                                            <input type="text" name="category_name" id="category_name" value="<?= htmlspecialchars($d['category_name']); ?>" 
                                                class="peer block w-full py-4 px-5 text-sm font-bold text-slate-700 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500 focus:bg-white transition-all placeholder-slate-400" 
                                                placeholder="E.g. Rent, Electricity, Salaries" required>
                                            <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-slate-400 peer-focus:text-teal-500 transition-colors">
                                                <i class="fas fa-heading"></i>
                                            </div>
                                        </div>
                                        <div class="error-msg-text text-rose-500 text-xs font-bold mt-1 hidden" id="error-name"><i class="fas fa-exclamation-circle"></i> Category name is required</div>
                                    </div>

                                    <!-- Description -->
                                    <div class="relative w-full group">
                                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Description / Details</label>
                                        <div class="relative">
                                            <textarea name="category_details" rows="5" 
                                                class="peer block w-full py-4 px-5 text-sm font-bold text-slate-700 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500 focus:bg-white transition-all placeholder-slate-400 resize-none" 
                                                placeholder="Write some details about this category..."><?= htmlspecialchars($d['category_details']); ?></textarea>
                                            <div class="absolute top-4 right-4 text-slate-400 peer-focus:text-teal-500 transition-colors">
                                                <i class="fas fa-align-left"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Settings & Actions -->
                        <div class="lg:col-span-4 space-y-6">
                            <div class="bg-white rounded-[2.5rem] shadow-xl border border-slate-100 p-6 relative overflow-hidden">
                                <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-6 border-b border-slate-100 pb-2">Configuration</h3>
                                
                                <div class="space-y-6">
                                    <!-- Status Toggle -->
                                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100 hover:border-teal-200 transition-all group cursor-pointer" onclick="document.getElementById('status_chk').click()">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="text-sm font-black text-slate-700 group-hover:text-teal-700 transition-colors">Active Status</p>
                                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Visible in Lists</p>
                                            </div>
                                            <label class="relative inline-flex items-center cursor-pointer pointer-events-none"> <!-- Pointer events none to use parent click -->
                                                <input type="checkbox" name="status" id="status_chk" value="1" class="sr-only peer" <?= $d['status'] == '1' ? 'checked' : ''; ?>>
                                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-teal-500"></div>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Sort Order -->
                                    <div class="relative w-full group">
                                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Sort Order</label>
                                        <div class="relative">
                                            <input type="number" name="sort_order" value="<?= htmlspecialchars($d['sort_order']); ?>" 
                                                class="peer block w-full py-3 px-4 text-sm font-bold text-slate-700 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500 focus:bg-white transition-all pl-12" min="0">
                                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 peer-focus:text-teal-500 transition-colors">
                                                <i class="fas fa-sort-numeric-down"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="pt-2">
                                <button type="button" onclick="processCategoryForm()" 
                                    class="w-full py-4 rounded-xl bg-teal-800 hover:bg-teal-900 text-white font-black text-sm uppercase tracking-widest shadow-lg shadow-teal-200 transition-all duration-300 flex items-center justify-center gap-3 transform hover:-translate-y-1 hover:shadow-xl">
                                    <i class="fas fa-save text-lg"></i>
                                    <span><?= $btn_text; ?></span>
                                </button>
                                
                                <button type="reset" class="w-full mt-4 py-3 bg-slate-100 hover:bg-rose-50 text-slate-500 hover:text-rose-600 font-bold text-xs uppercase tracking-widest rounded-xl transition-all flex items-center justify-center gap-2 group">
                                    <i class="fas fa-undo group-hover:rotate-[-45deg] transition-transform"></i> Reset
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <?php include('../includes/footer.php'); ?>
    </main>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/pos/assets/js/expenditureJs/add_expense_categoryJs.js"></script>
