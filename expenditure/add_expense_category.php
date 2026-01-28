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
    
    <main id="main-content" class="flex-1 lg:ml-64 flex flex-col h-screen min-w-0 transition-all duration-300">
        <div class="navbar-fixed-top"><?php include('../includes/navbar.php'); ?></div> 
        
        <div class="content-scroll-area custom-scroll h-full overflow-y-auto">
            <div class="p-6">
                <div class="mb-8 slide-in">
                    <div class="flex items-center gap-4 mb-4">
                        <a href="/pos/expenditure/category_list" class="w-10 h-10 flex items-center justify-center rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition-all">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <div>
                            <h1 class="text-3xl font-bold text-slate-800 mb-2"><?= $page_title; ?></h1>
                            <div class="flex items-center gap-2 text-sm text-slate-500">
                                <span class="w-2 h-2 rounded-full bg-teal-500 animate-pulse"></span>
                                <span><?= $mode == 'create' ? 'Define a new category for tracking expenses' : 'Update category details'; ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <form action="/pos/expenditure/save_category" method="POST" id="categoryForm" novalidate autocomplete="off">
                    <?php if($mode == 'edit'): ?>
                        <input type="hidden" name="category_id" value="<?= $d['category_id'] ?>">
                        <input type="hidden" name="update_category_btn" value="true">
                    <?php else: ?>
                        <input type="hidden" name="save_category_btn" value="true">
                    <?php endif; ?>

                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                        <div class="lg:col-span-8 space-y-6">
                            <div class="glass-card rounded-xl p-8 shadow-lg border border-slate-200 slide-in delay-1 bg-white">
                                <h2 class="text-lg font-bold text-slate-800 mb-8 flex items-center gap-3">
                                    <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-teal-600 to-emerald-600 text-white flex items-center justify-center text-lg shadow-md"><i class="fas fa-tags"></i></span>
                                    Category Information
                                </h2>
                                
                                <div class="space-y-6">
                                    <div class="relative w-full group">
                                        <label class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 block">Category Name *</label>
                                        <input type="text" name="category_name" id="category_name" value="<?= htmlspecialchars($d['category_name']); ?>" 
                                            class="peer block py-4 px-5 w-full text-sm text-slate-800 bg-slate-50 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-teal-600 focus:bg-white transition-all font-medium" placeholder="E.g. Rent, Electricity, Salaries">
                                        <div class="error-msg-text" id="error-name"><i class="fas fa-exclamation-circle"></i> Category name is required</div>
                                    </div>

                                    <div class="relative w-full group">
                                        <label class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-2 block">Description / Details</label>
                                        <textarea name="category_details" rows="4" 
                                            class="peer block py-4 px-5 w-full text-sm text-slate-800 bg-slate-50 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-teal-600 focus:bg-white transition-all resize-none" 
                                            placeholder="Write some details about this category..."><?= htmlspecialchars($d['category_details']); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="lg:col-span-4 space-y-6">
                            <div class="glass-card rounded-xl p-6 shadow-lg border border-slate-200 slide-in delay-2 bg-white">
                                <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-4">Settings</h3>
                                
                                <div class="space-y-4">
                                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="text-sm font-bold text-slate-700">Display Status</p>
                                                <p class="text-xs text-slate-500">Toggle visibility in lists</p>
                                            </div>
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input type="checkbox" name="status" value="1" class="sr-only peer" <?= $d['status'] == '1' ? 'checked' : ''; ?>>
                                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-teal-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-teal-600"></div>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="relative w-full group">
                                        <label class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-1 block">Sort Order</label>
                                        <input type="number" name="sort_order" value="<?= htmlspecialchars($d['sort_order']); ?>" 
                                            class="peer block py-3 px-4 w-full text-sm text-slate-800 bg-slate-50 rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-teal-600 font-bold" min="0">
                                    </div>
                                </div>
                            </div>

                            <div class="slide-in delay-3 pt-4">
                                <button type="button" onclick="processCategoryForm()" 
                                    class="w-full py-4 rounded-xl bg-teal-900 hover:bg-teal-950 text-white font-bold text-lg shadow-xl transition-all duration-300 flex items-center justify-center gap-3 transform hover:-translate-y-1">
                                    <i class="fas fa-save"></i>
                                    <span><?= $btn_text; ?></span>
                                </button>
                                
                                <button type="reset" class="w-full mt-4 py-3 text-slate-500 font-bold hover:text-rose-600 transition-colors">
                                    Reset Form
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/pos/assets/js/expenditureJs/add_expense_categoryJs.js"></script>
<?php include('../includes/footer.php'); ?>
