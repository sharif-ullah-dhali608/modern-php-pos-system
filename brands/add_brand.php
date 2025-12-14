<?php
session_start();
include('../config/dbcon.php');

// 1. SECURITY CHECK
if(!isset($_SESSION['auth'])){
    header("Location: /pos/signin.php");
    exit(0);
}

// Default data structure and mode setup
$mode = "create";
$btn_name = "save_brand_btn";
$btn_text = "Save Brand";
$page_title = "Add Brand";

$d = [
    'id' => '',
    'name' => '',
    'code' => '',
    'thumbnail' => '', // This will hold the path to the file in the database
    'details' => '',
    'status' => '1',
    'sort_order' => '0'
];

// 2. FETCH DATA FOR EDIT MODE
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
    
    <main id="main-content" class="flex-1 ml-64 main-content min-h-screen">
        <?php include('../includes/navbar.php'); ?>
        
        <div class="p-12">
            <div class="mb-6 slide-in">
                <div class="flex items-center gap-4 mb-4">
                    <a href="/pos/brands/brand_list.php" class="w-10 h-10 flex items-center justify-center rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition-all">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <div>
                        <h1 class="text-3xl font-bold text-slate-800 mb-2"><?= $page_title; ?></h1>
                        <div class="flex items-center gap-2 text-sm text-slate-500">
                            <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                            <span><?= $mode == 'create' ? 'Create new brand' : 'Update brand'; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="glass-card rounded-xl p-8 slide-in">
                <form action="save_brand.php" method="POST" enctype="multipart/form-data">
                    <?php if($mode == 'edit'): ?>
                        <input type="hidden" name="brand_id" value="<?= $d['id']; ?>">
                        <input type="hidden" name="old_thumbnail" value="<?= htmlspecialchars($d['thumbnail']); ?>">
                    <?php endif; ?>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div class="lg:col-span-2 space-y-6">
                            
                            <div class="flex items-center gap-4">
                                <label for="thumbnail-upload" class="w-16 h-16 rounded-lg bg-slate-100 border border-slate-300 flex items-center justify-center overflow-hidden shrink-0 cursor-pointer" id="thumbnail-preview-container">
                                    <?php if(!empty($d['thumbnail'])): ?>
                                        <img src="<?= htmlspecialchars($d['thumbnail']); ?>" alt="Thumb" class="w-full h-full  object-cover" id="thumbnail-preview">
                                    <?php else: ?>
                                        <i class="fas fa-image text-slate-400 text-2xl" id="default-icon"></i>
                                    <?php endif; ?>
                                </label>
                                
                                <div class="flex-1">
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Thumbnail (Image Upload) <span class="text-slate-500">(Optional)</span></label>
                                    <input 
                                        type="file" 
                                        name="thumbnail" 
                                        id="thumbnail-upload" 
                                        class="w-full text-slate-700 border border-slate-300 rounded-lg bg-white file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-gradient-to-br file:from-teal-900 file:via-teal-800 file:to-emerald-900 file:text-white hover:file:from-teal-800 hover:file:to-emerald-800 focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-purple-600 transition-all"
                                        onchange="readURL(this);" 
                                        accept="image/*"
                                    >
                                    <p class="text-xs text-slate-400 mt-1">Max size: 2MB. Format: JPG, PNG.</p>
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">Name <span class="text-red-600">*</span></label>
                                <input type="text" name="name" value="<?= htmlspecialchars($d['name']); ?>" 
                                    class="w-full bg-white border border-slate-300 rounded-lg px-4 py-3 text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-purple-600 transition-all" required>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">Code Name <span class="text-red-600">*</span></label>
                                <input type="text" name="code" value="<?= htmlspecialchars($d['code']); ?>" 
                                    class="w-full bg-white border border-slate-300 rounded-lg px-4 py-3 text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-purple-600 transition-all uppercase" required>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">Details</label>
                                <textarea name="details" rows="3" 
                                    class="w-full bg-white border border-slate-300 rounded-lg px-4 py-3 text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-purple-600 transition-all"><?= htmlspecialchars($d['details']); ?></textarea>
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
                                
                                <a href="brand_list.php" 
                                    class="block w-full bg-slate-100 text-slate-700 font-semibold py-3 rounded-lg text-center hover:bg-slate-200 transition-all">Cancel</a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <script>
            function readURL(input) {
                const previewContainer = document.getElementById('thumbnail-preview-container');
                let thumbnailImg = document.getElementById('thumbnail-preview');
                
                if (input.files && input.files[0]) {
                    var reader = new FileReader();

                    reader.onload = function (e) {
                        if (!thumbnailImg) {
                            // Create img element if it doesn't exist
                            thumbnailImg = document.createElement('img');
                            thumbnailImg.id = 'thumbnail-preview';
                            thumbnailImg.className = 'w-full h-full object-cover';
                            
                            // Remove existing content (like the default icon)
                            previewContainer.innerHTML = '';
                            previewContainer.appendChild(thumbnailImg);
                        }
                        thumbnailImg.src = e.target.result;
                    };

                    reader.readAsDataURL(input.files[0]); // read the file as a data URL
                } else {
                    // Reset to default icon if file is cleared
                    const existingPath = document.querySelector('input[name="old_thumbnail"]');
                    
                    if (existingPath && existingPath.value) {
                         // If in edit mode and there's an existing image, show it
                         previewContainer.innerHTML = `<img src="${existingPath.value}" alt="Thumb" class="w-full h-full object-cover" id="thumbnail-preview">`;
                    } else {
                         // Show default icon
                         previewContainer.innerHTML = '<i class="fas fa-image text-slate-400 text-2xl" id="default-icon"></i>';
                    }
                }
            }
        </script>
        
        <?php include('../includes/footer.php'); ?>
    </main>
</div>