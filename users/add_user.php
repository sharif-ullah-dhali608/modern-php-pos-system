<?php
session_start();
include('../config/dbcon.php');

if(!isset($_SESSION['auth'])){
    header("Location: /pos/login");
    exit(0);
}

// URL ID Parsing
if(!isset($_GET['id'])){
    $uri_segments = explode('/', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
    $last_segment = end($uri_segments);
    if(is_numeric($last_segment)) $_GET['id'] = $last_segment;
}

$mode = "create";
$btn_name = "save_user_btn";
$btn_text = "Save User";
$page_title = "Add New User";

$d = ['id'=>'','username'=>'','email'=>'','mobile'=>'','dob'=>'','group_id'=>'','status'=>'1','user_image'=>''];
$selected_stores = [];

if(isset($_GET['id'])) {
    $mode = "edit";
    $btn_name = "update_user_btn";
    $btn_text = "Update User";
    $page_title = "Edit User";
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    
    $res = mysqli_query($conn, "SELECT * FROM users WHERE id='$id' LIMIT 1");
    if($row = mysqli_fetch_assoc($res)){
        $d = $row;
        $map_res = mysqli_query($conn, "SELECT store_id FROM user_store_map WHERE user_id='$id'");
        while($m = mysqli_fetch_assoc($map_res)) $selected_stores[] = $m['store_id'];
    }
}

include('../includes/header.php');
?>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<div class="app-wrapper">
    <?php include('../includes/sidebar.php'); ?>
    <main id="main-content" class="flex-1 lg:ml-64 flex flex-col h-screen min-w-0 transition-all duration-300 bg-slate-50">
        <?php include('../includes/navbar.php'); ?>
        
        <div class="content-scroll-area custom-scroll h-full overflow-y-auto p-4 md:p-6">
            <div class="max-w-7xl mx-auto">
                <form action="/pos/users/save" method="POST" enctype="multipart/form-data" id="userForm" class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-stretch">
                    <?php if($mode == 'edit'): ?>
                        <input type="hidden" name="user_id" value="<?= $d['id'] ?>">
                        <input type="hidden" name="old_image" value="<?= $d['user_image'] ?>">
                    <?php endif; ?>

                    <div class="lg:col-span-2 flex">
                        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 relative overflow-hidden w-full">
                            <div class="absolute top-0 left-0 w-1 h-full bg-teal-500"></div>
                            <h3 class="text-lg font-bold text-slate-800 mb-6 flex items-center gap-2"><i class="fas fa-user-circle text-teal-600"></i> Account Information</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div class="form-group md:col-span-2">
                                    <label class="block text-xs font-black text-slate-500 uppercase mb-2">Username/Full Name *</label>
                                    <input type="text" name="username" value="<?= $d['username'] ?>" class="user-input w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 outline-none transition-all" placeholder="Enter Full Name">
                                    <span class="error-msg text-[10px] text-red-500 hidden mt-1">Username is required</span>
                                </div>
                                <div class="form-group">
                                    <label class="block text-xs font-black text-slate-500 uppercase mb-2">Email Address *</label>
                                    <input type="email" name="email" value="<?= $d['email'] ?>" class="user-input w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 outline-none" placeholder="example@mail.com">
                                    <span class="error-msg text-[10px] text-red-500 hidden mt-1">Valid email is required</span>
                                </div>
                                <div class="form-group">
                                    <label class="block text-xs font-black text-slate-500 uppercase mb-2">Mobile Number *</label>
                                    <input type="text" name="mobile" value="<?= $d['mobile'] ?>" class="user-input w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 outline-none" placeholder="017xxxxxxxx">
                                    <span class="error-msg text-[10px] text-red-500 hidden mt-1">Mobile number is required</span>
                                </div>
                                <div class="form-group">
                                    <label class="block text-xs font-black text-slate-500 uppercase mb-2">Password <?= $mode=='create'?'*':'(Leave blank to keep old)' ?></label>
                                    <input type="password" name="password" id="password" class="user-input w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 outline-none" placeholder="Min 6 characters">
                                    <span class="error-msg text-[10px] text-red-500 hidden mt-1">Min 6 characters required</span>
                                </div>
                                <div class="form-group">
                                    <label class="block text-xs font-black text-slate-500 uppercase mb-2">User Group *</label>
                                    <select name="group_id" id="group_id" class="select2 w-full">
                                        <option value="">-- Select Group --</option>
                                        <?php 
                                        $groups = mysqli_query($conn, "SELECT id, name FROM user_groups");
                                        while($g = mysqli_fetch_assoc($groups)): ?>
                                            <option value="<?= $g['id'] ?>" <?= $d['group_id']==$g['id']?'selected':'' ?>><?= $g['name'] ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                    <span class="error-msg text-[10px] text-red-500 hidden mt-1">Please select a group</span>
                                </div>
                                <div class="form-group">
                                    <label class="block text-xs font-black text-slate-500 uppercase mb-2">Date of Birth *</label>
                                    <input type="date" name="dob" value="<?= $d['dob'] ?>" class="user-input w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 outline-none">
                                    <span class="error-msg text-[10px] text-red-500 hidden mt-1">Date of Birth is required</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col gap-6">
                        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 flex-1">
                            <label class="block text-xs font-black text-slate-500 uppercase mb-4 text-center">User Photo</label>
                            <div class="flex flex-col items-center">
                                <div class="w-40 h-40 rounded-2xl border-2 border-dashed border-slate-300 flex items-center justify-center overflow-hidden mb-4 bg-slate-50 relative">
                                    <img id="imgPreview" src="<?= !empty($d['user_image'])?$d['user_image']:'/pos/assets/img/placeholder.png' ?>" class="w-full h-full object-cover">
                                    <input type="file" name="user_image" id="user_image" class="absolute inset-0 opacity-0 cursor-pointer" accept="image/*">
                                </div>
                                <p class="text-[10px] text-slate-400 uppercase font-bold text-center">Click Image to Upload</p>
                            </div>
                        </div>

                        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 flex-1">
                            <label class="block text-xs font-black text-slate-500 uppercase mb-4">Assign Stores *</label>
                            <div class="space-y-2 max-h-40 overflow-y-auto custom-scroll pr-2" id="storeList">
                                <?php 
                                $stores = mysqli_query($conn, "SELECT id, store_name FROM stores WHERE status='1'");
                                while($s = mysqli_fetch_assoc($stores)): ?>
                                    <label class="flex items-center gap-3 p-2 bg-slate-50 rounded-lg cursor-pointer hover:bg-teal-50 transition-all">
                                        <input type="checkbox" name="store_ids[]" value="<?= $s['id'] ?>" <?= in_array($s['id'], $selected_stores)?'checked':'' ?> class="store-check w-4 h-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                                        <span class="text-xs font-bold text-slate-600"><?= $s['store_name'] ?></span>
                                    </label>
                                <?php endwhile; ?>
                            </div>
                            <span id="storeError" class="text-[10px] text-red-500 hidden mt-1">Select at least one store</span>
                        </div>

                        <div class="bg-white rounded-2xl shadow-lg border border-slate-200 p-4">
                            <div class="flex gap-3">
                                <button type="submit" name="<?= $btn_name ?>" class="flex-1 bg-teal-600 hover:bg-teal-700 text-white font-black py-4 rounded-xl shadow-md transition-all uppercase tracking-wider text-xs"> 
                                    <i class="fas fa-save mr-1"></i> <?= $btn_text ?> 
                                </button>
                                <a href="/pos/users/list" class="flex-1 bg-slate-100 text-slate-500 font-bold py-4 rounded-xl text-center hover:bg-slate-200 transition-all uppercase text-[10px] flex items-center justify-center">Cancel</a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    $('.select2').select2({ width: '100%', placeholder: 'Search and Select Group...' });

    // Image Preview
    $('#user_image').change(function(e) {
        const reader = new FileReader();
        reader.onload = function(e) { $('#imgPreview').attr('src', e.target.result); }
        reader.readAsDataURL(this.files[0]);
    });

    // Handle Session Messages via SWAL
    <?php if(isset($_SESSION['message'])): ?>
        Swal.fire({
            icon: '<?php echo $_SESSION['msg_type']; ?>',
            title: '<?php echo ($_SESSION['msg_type'] == "error") ? "Oops..." : "Success!"; ?>',
            text: '<?php echo $_SESSION['message']; ?>',
            confirmButtonColor: '#0d9488'
        });
        <?php unset($_SESSION['message']); unset($_SESSION['msg_type']); ?>
    <?php endif; ?>

    // Validation logic stays here...
});
</script>