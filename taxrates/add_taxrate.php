<?php
session_start();
include('../config/dbcon.php');

if(!isset($_SESSION['auth'])){
    header("Location: /pos/signin.php");
    exit(0);
}

$mode = "create";
$btn_name = "save_taxrate_btn";
$btn_text = "Save Taxrate";
$page_title = "Add Taxrate";

$d = [
    'id' => '',
    'name' => '',
    'code' => '',
    'taxrate' => '0',
    'status' => '1',
    'sort_order' => '0'
];

if(isset($_GET['id'])) {
    $mode = "edit";
    $btn_name = "update_taxrate_btn";
    $btn_text = "Update Taxrate";
    $page_title = "Edit Taxrate";

    $id = mysqli_real_escape_string($conn, $_GET['id']);
    $query = "SELECT * FROM taxrates WHERE id='$id' LIMIT 1";
    $result = mysqli_query($conn, $query);

    if(mysqli_num_rows($result) > 0){
        $d = mysqli_fetch_array($result);
    } else {
        $_SESSION['message'] = "Taxrate not found!";
        $_SESSION['msg_type'] = "error";
        header("Location: taxrate_list.php");
        exit(0);
    }
}

include('../includes/header.php');
?>

<div class="flex">
    <?php include('../includes/sidebar.php'); ?>
    
    <main id="main-content" class="flex-1 ml-64 main-content min-h-screen">
        <?php include('../includes/navbar.php'); ?>
        
        <div class="p-12">
            <div class="mb-6 slide-in">
                <div class="flex items-center gap-4 mb-4">
                    <a href="/pos/taxrates/taxrate_list.php" class="w-10 h-10 flex items-center justify-center rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition-all">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <div>
                        <h1 class="text-3xl font-bold text-slate-800 mb-2"><?= $page_title; ?></h1>
                        <div class="flex items-center gap-2 text-sm text-slate-500">
                            <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                            <span><?= $mode == 'create' ? 'Create new taxrate' : 'Update taxrate'; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="glass-card rounded-xl p-8 slide-in">
                <form action="save_taxrate.php" method="POST">
                    <?php if($mode == 'edit'): ?>
                        <input type="hidden" name="taxrate_id" value="<?= $d['id']; ?>">
                    <?php endif; ?>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div class="lg:col-span-2 space-y-6">
                            
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">Taxrate Name <span class="text-red-600">*</span></label>
                                <input type="text" name="name" value="<?= htmlspecialchars($d['name']); ?>" 
                                    class="w-full bg-white border border-slate-300 rounded-lg px-4 py-3 text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-purple-600 transition-all" required>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">Code Name <span class="text-red-600">*</span></label>
                                <input type="text" name="code" value="<?= htmlspecialchars($d['code']); ?>" 
                                    class="w-full bg-white border border-slate-300 rounded-lg px-4 py-3 text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-purple-600 transition-all uppercase" required>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">Taxrate (%) <span class="text-red-600">*</span></label>
                                <input type="number" step="0.01" name="taxrate" value="<?= htmlspecialchars($d['taxrate']); ?>" 
                                    class="w-full bg-white border border-slate-300 rounded-lg px-4 py-3 text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-purple-600 transition-all" required>
                            </div>
                        </div>

                        <div class="space-y-6">
                            
                            <div class="glass-card rounded-xl p-6 border border-slate-200 shadow-sm">
                                <label class="block text-sm font-semibold text-slate-700 mb-3">Status <span class="text-red-600">*</span></label>
                                <select name="status" 
                                    class="w-full bg-white border border-slate-300 rounded-lg px-4 py-3 text-slate-800 focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-purple-600 transition-all" required>
                                    <option value="1" <?= $d['status']=='1'?'selected':''; ?>>Active</option>
                                    <option value="0" <?= $d['status']=='0'?'selected':''; ?>>Inactive</option>
                                </select>
                            </div>

                            <div class="glass-card rounded-xl p-6 border border-slate-200 shadow-sm">
                                <label class="block text-sm font-semibold text-slate-700 mb-3">Sort Order <span class="text-red-600">*</span></label>
                                <input type="number" name="sort_order" value="<?= htmlspecialchars($d['sort_order']); ?>" 
                                    class="w-full bg-white border border-slate-300 rounded-lg px-4 py-3 text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-purple-600 transition-all" required>
                            </div>

                            <div class="space-y-3 mt-8">
                                <button type="submit" name="<?= $btn_name; ?>" 
                                    class="w-full bg-gradient-to-br from-teal-900 via-teal-800 to-emerald-900 hover:to-emerald-800 text-white font-semibold py-3 rounded-lg shadow-lg transition-all transform hover:scale-[1.01]"> <?= $btn_text; ?> </button>
                                
                                <a href="taxrate_list.php" 
                                    class="block w-full bg-slate-100 text-slate-700 font-semibold py-3 rounded-lg text-center hover:bg-slate-200 transition-all">Cancel</a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <?php include('../includes/footer.php'); ?>
    </main>
</div>