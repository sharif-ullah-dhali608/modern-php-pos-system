<?php
session_start();
include('../config/dbcon.php');

if(!isset($_SESSION['auth'])){
    header("Location: /pos/signin.php");
    exit(0);
}

$mode = "create";
$btn_name = "save_brand_btn";
$btn_text = "Save Brand";
$page_title = "Add Brand";

$d = [
    'id' => '',
    'name' => '',
    'code' => '',
    'thumbnail' => '',
    'details' => '',
    'status' => '1',
    'sort_order' => '0'
];

if(isset($_GET['id'])) {
    $mode = "edit";
    $btn_name = "update_brand_btn";
    $btn_text = "Update Brand";
    $page_title = "Edit Brand";

    $id = mysqli_real_escape_string($conn, $_GET['id']);
    $query = "SELECT * FROM brands WHERE id='$id' LIMIT 1";
    $result = mysqli_query($conn, $query);

    if(mysqli_num_rows($result) > 0){
        $d = mysqli_fetch_array($result);
    } else {
        $_SESSION['message'] = "Brand not found!";
        $_SESSION['msg_type'] = "error";
        header("Location: brand_list.php");
        exit(0);
    }
}

include('../includes/header.php');
?>

<div class="flex">
    <?php include('../includes/sidebar.php'); ?>
    
    <main class="flex-1 ml-64 main-content min-h-screen">
        <?php include('../includes/navbar.php'); ?>
        
        <div class="p-6">
            <div class="mb-6 slide-in">
                <div class="flex items-center gap-4 mb-4">
                    <a href="/pos/brands/brand_list.php" class="w-10 h-10 flex items-center justify-center rounded-lg bg-white/10 hover:bg-white/20 text-white transition-all">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <div>
                        <h1 class="text-3xl font-bold text-white mb-2"><?= $page_title; ?></h1>
                        <div class="flex items-center gap-2 text-sm text-white/60">
                            <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                            <span><?= $mode == 'create' ? 'Create new brand' : 'Update brand'; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="glass-card rounded-xl p-8 slide-in">
                <form action="save_brand.php" method="POST">
                    <?php if($mode == 'edit'): ?>
                        <input type="hidden" name="brand_id" value="<?= $d['id']; ?>">
                    <?php endif; ?>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div class="lg:col-span-2 space-y-6">
                            <div class="flex items-center gap-4">
                                <div class="w-16 h-16 rounded-lg bg-white/10 border border-white/20 flex items-center justify-center overflow-hidden">
                                    <?php if(!empty($d['thumbnail'])): ?>
                                        <img src="<?= htmlspecialchars($d['thumbnail']); ?>" alt="Thumb" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <i class="fas fa-image text-white/60 text-2xl"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-1">
                                    <label class="block text-sm font-semibold text-white mb-2">Thumbnail URL</label>
                                    <input type="text" name="thumbnail" value="<?= htmlspecialchars($d['thumbnail']); ?>" class="w-full bg-white/10 border border-white/20 rounded-lg px-4 py-3 text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent" placeholder="https://...">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-white mb-2">Name <span class="text-red-400">*</span></label>
                                <input type="text" name="name" value="<?= htmlspecialchars($d['name']); ?>" class="w-full bg-white/10 border border-white/20 rounded-lg px-4 py-3 text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent" required>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-white mb-2">Code Name <span class="text-red-400">*</span></label>
                                <input type="text" name="code" value="<?= htmlspecialchars($d['code']); ?>" class="w-full bg-white/10 border border-white/20 rounded-lg px-4 py-3 text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent uppercase" required>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-white mb-2">Details</label>
                                <textarea name="details" rows="3" class="w-full bg-white/10 border border-white/20 rounded-lg px-4 py-3 text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"><?= htmlspecialchars($d['details']); ?></textarea>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <div class="glass-card rounded-lg p-6">
                                <label class="block text-sm font-semibold text-white mb-3">Status <span class="text-red-400">*</span></label>
                                <select name="status" class="w-full bg-white/10 border border-white/20 rounded-lg px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent" required>
                                    <option value="1" <?= $d['status']=='1'?'selected':''; ?>>Active</option>
                                    <option value="0" <?= $d['status']=='0'?'selected':''; ?>>Inactive</option>
                                </select>
                            </div>

                            <div class="glass-card rounded-lg p-6">
                                <label class="block text-sm font-semibold text-white mb-3">Sort Order <span class="text-red-400">*</span></label>
                                <input type="number" name="sort_order" value="<?= htmlspecialchars($d['sort_order']); ?>" class="w-full bg-white/10 border border-white/20 rounded-lg px-4 py-3 text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent" required>
                            </div>

                            <div class="space-y-3">
                                <button type="submit" name="<?= $btn_name; ?>" class="w-full bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-semibold py-3 rounded-lg shadow-lg hover:from-purple-700 hover:to-indigo-700 transition-all"> <?= $btn_text; ?> </button>
                                <a href="brand_list.php" class="block w-full bg-white/10 text-white font-semibold py-3 rounded-lg text-center hover:bg-white/20 transition-all">Cancel</a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <?php include('../includes/footer.php'); ?>
    </main>
</div>

