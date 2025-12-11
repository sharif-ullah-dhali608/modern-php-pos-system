<?php
session_start();
include('../config/dbcon.php');

// 1. SECURITY CHECK
if(!isset($_SESSION['auth'])){
    header("Location: /pos/login");
    exit(0);
}

// 2. INITIALIZE VARIABLES (DEFAULT VALUES)
$mode = "create";
$btn_name = "save_store_btn";
$btn_text = "Create Store";
$page_title = "Create New Store";

// Initialize empty array to avoid undefined index errors
$d = [
    'id' => '', 'store_name' => '', 'store_code' => '', 'business_type' => '',
    'email' => '', 'phone' => '', 'address' => '', 'city_zip' => '',
    'vat_number' => '', 'timezone' => 'Asia/Dhaka', 
    'max_line_disc' => '0', 'max_inv_disc' => '0', 'approval_disc' => '0',
    'overselling' => 'deny', 'low_stock' => '5', 
    'status' => '1', 'daily_target' => '', 'open_time' => '09:00', 'close_time' => '21:00',
    'allow_manual_price' => '0', 'allow_backdate' => '0'
];

// 3. CHECK IF EDIT MODE
if(isset($_GET['id'])) {
    $mode = "edit";
    $btn_name = "update_store_btn";
    $btn_text = "Update Store";
    $page_title = "Edit Store Config";
    
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    $query = "SELECT * FROM stores WHERE id='$id' LIMIT 1";
    $result = mysqli_query($conn, $query);

    if(mysqli_num_rows($result) > 0){
        $d = mysqli_fetch_array($result);
    } else {
        $_SESSION['message'] = "Store not found!";
        $_SESSION['msg_type'] = "error";
        header("Location: store_list.php");
        exit(0);
    }
}

// 4. HELPER STYLES
$input_wrapper = "relative z-0 w-full mb-5 group";
$input_field = "block py-3.5 px-4 w-full text-sm text-slate-800 bg-white/60 rounded-xl border border-slate-200 appearance-none focus:outline-none focus:ring-0 focus:border-teal-700 focus:bg-white peer transition-all placeholder-transparent shadow-sm font-medium";
$input_label = "peer-focus:font-bold absolute text-sm text-slate-600 duration-300 transform -translate-y-6 scale-75 top-4 left-4 origin-[0] peer-focus:left-4 peer-focus:text-teal-700 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6 pointer-events-none bg-white/80 px-2 backdrop-blur-sm rounded-md z-10 font-semibold";
$icon_style = "absolute right-4 top-4 text-slate-400 peer-focus:text-teal-700 transition-colors text-lg pointer-events-none z-20";
$card_style = "bg-white/90 backdrop-blur-2xl rounded-3xl p-6 shadow-[0_8px_30px_rgb(0,0,0,0.12)] border border-white/60 hover:shadow-2xl transition-all duration-500 relative overflow-hidden h-full";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= $page_title; ?> - Velocity POS</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .fixed-bg { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -50; background: linear-gradient(-45deg, #ccfbf1, #5eead4, #0f766e, #115e59, #99f6e4); background-size: 400% 400%; animation: gradientBG 15s ease infinite; will-change: background-position; }
        @keyframes gradientBG { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }
        .shape-container { position: fixed; inset: 0; z-index: -40; pointer-events: none; overflow: hidden; }
        .float-slow { animation: float 8s ease-in-out infinite; }
        .float-medium { animation: float 6s ease-in-out infinite; }
        .float-fast { animation: float 4s ease-in-out infinite; }
        @keyframes float { 0% { transform: translateY(0px) rotate(0deg); } 50% { transform: translateY(-30px) rotate(5deg); } 100% { transform: translateY(0px) rotate(0deg); } }
        .shape-shadow { box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2), 0 10px 10px -5px rgba(0, 0, 0, 0.1); }
        .icon-shadow { filter: drop-shadow(0 10px 10px rgba(0, 0, 0, 0.25)); }
        .slide-in { animation: slideIn 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; transform: translateY(30px); }
        .delay-1 { animation-delay: 0.1s; } .delay-2 { animation-delay: 0.2s; } .delay-3 { animation-delay: 0.3s; }
        @keyframes slideIn { to { opacity: 1; transform: translateY(0); } }
        ::-webkit-scrollbar { width: 6px; } ::-webkit-scrollbar-track { background: transparent; } ::-webkit-scrollbar-thumb { background: #0f766e; border-radius: 10px; } ::-webkit-scrollbar-thumb:hover { background: #115e59; }
        select { -webkit-appearance: none; -moz-appearance: none; appearance: none; }
    </style>
</head>

<body class="min-h-screen text-slate-800 selection:bg-teal-200 selection:text-teal-900">

    <div class="fixed-bg"></div>
    <div class="shape-container">
        <div class="absolute top-10 left-[5%] w-32 h-32 bg-teal-900/20 rounded-3xl shape-shadow float-slow backdrop-blur-sm border border-white/10"></div>
        <div class="absolute bottom-20 right-[5%] w-48 h-48 bg-emerald-900/15 rounded-full shape-shadow float-medium delay-1000 backdrop-blur-sm"></div>
        <div class="absolute top-[40%] left-[10%] w-16 h-16 bg-teal-800/20 rounded-xl shape-shadow float-fast delay-2000 rotate-12"></div>
        <div class="absolute top-[15%] right-[20%] text-teal-900/20 float-slow delay-2000"><i class="fas fa-cash-register text-9xl icon-shadow"></i></div>
        <div class="absolute bottom-[15%] left-[20%] text-emerald-900/20 float-medium delay-1000"><i class="fas fa-chart-pie text-8xl icon-shadow"></i></div>
    </div>

    <?php if(isset($_SESSION['message'])): ?>
    <script>
        Swal.fire({
            icon: '<?= $_SESSION['msg_type']; ?>',
            title: '<?= $_SESSION['msg_type'] == "success" ? "Success!" : "Notice"; ?>',
            text: '<?= $_SESSION['message']; ?>',
            confirmButtonColor: '#0f766e',
            timer: 3000
        });
    </script>
    <?php unset($_SESSION['message']); unset($_SESSION['msg_type']); endif; ?>

    <nav class="sticky top-0 z-50 backdrop-blur-md bg-white/70 border-b border-white/40 px-6 py-4 shadow-sm transition-all">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="/pos/index.php" class="w-10 h-10 flex items-center justify-center rounded-full bg-white text-slate-500 hover:text-teal-700 hover:shadow-md transition-all duration-300 group">
                    <i class="fas fa-arrow-left transform group-hover:-translate-x-1 transition-transform"></i>
                </a>
                <div>
                    <h1 class="text-xl font-bold text-slate-800 tracking-tight"><?= $page_title; ?></h1>
                    <div class="flex items-center gap-2 text-[10px] font-bold text-slate-500 uppercase tracking-widest">
                        <span class="w-2 h-2 rounded-full bg-teal-600 animate-pulse"></span>
                        <?= $mode == 'create' ? 'System Configuration' : 'Update Configuration'; ?>
                    </div>
                </div>
            </div>
            <div class="hidden md:flex gap-3">
                <a href="store_list.php" class="w-10 h-10 rounded-full bg-white/80 text-slate-400 hover:text-teal-600 hover:bg-white transition flex items-center justify-center shadow-sm" title="View List">
                    <i class="fas fa-list"></i>
                </a>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 relative z-10">
        
        <form action="save_store.php" method="POST" id="storeForm">
            
            <?php if($mode == 'edit'): ?>
                <input type="hidden" name="store_id" value="<?= $d['id']; ?>">
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

                <div class="lg:col-span-8 space-y-6">
                    <div class="<?= $card_style; ?> slide-in delay-1">
                        <h2 class="text-lg font-bold text-slate-800 mb-8 flex items-center gap-3">
                            <span class="w-10 h-10 rounded-xl bg-teal-100 text-teal-700 flex items-center justify-center text-lg shadow-sm shadow-teal-100"><i class="fas fa-store"></i></span>
                            Store Identity
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6">
                            <div class="<?= $input_wrapper; ?> md:col-span-2">
                                <input type="text" name="store_name" id="store_name" value="<?= htmlspecialchars($d['store_name']); ?>" class="<?= $input_field; ?>" placeholder=" " required>
                                <label for="store_name" class="<?= $input_label; ?>">Store Name *</label>
                                <i class="<?= $icon_style; ?> fas fa-pen-nib"></i>
                            </div>
                            <div class="<?= $input_wrapper; ?>">
                                <input type="text" name="store_code" id="store_code" value="<?= htmlspecialchars($d['store_code']); ?>" class="<?= $input_field; ?> uppercase tracking-wider font-mono" placeholder=" " required>
                                <label for="store_code" class="<?= $input_label; ?>">Branch Code *</label>
                                <i class="<?= $icon_style; ?> fas fa-barcode"></i>
                            </div>
                            <div class="<?= $input_wrapper; ?>">
                                <select name="business_type" id="business_type" class="<?= $input_field; ?> cursor-pointer bg-white/0">
                                    <option value="" disabled <?= $mode == 'create' ? 'selected' : ''; ?>></option>
                                    <option value="Retail" <?= $d['business_type'] == 'Retail' ? 'selected' : ''; ?>>Retail Store</option>
                                    <option value="Grocery" <?= $d['business_type'] == 'Grocery' ? 'selected' : ''; ?>>Grocery / Super Shop</option>
                                    <option value="Restaurant" <?= $d['business_type'] == 'Restaurant' ? 'selected' : ''; ?>>Restaurant / Cafe</option>
                                    <option value="Fashion" <?= $d['business_type'] == 'Fashion' ? 'selected' : ''; ?>>Fashion & Apparel</option>
                                </select>
                                <label for="business_type" class="<?= $input_label; ?>">Business Type</label>
                                <i class="fas fa-chevron-down absolute right-4 top-5 text-slate-400 pointer-events-none text-xs"></i>
                            </div>
                            <div class="<?= $input_wrapper; ?>">
                                <input type="email" name="email" id="email" value="<?= htmlspecialchars($d['email']); ?>" class="<?= $input_field; ?>" placeholder=" ">
                                <label for="email" class="<?= $input_label; ?>">Official Email</label>
                                <i class="<?= $icon_style; ?> fas fa-envelope"></i>
                            </div>
                            <div class="<?= $input_wrapper; ?>">
                                <input type="text" name="phone" id="phone" value="<?= htmlspecialchars($d['phone']); ?>" class="<?= $input_field; ?>" placeholder=" ">
                                <label for="phone" class="<?= $input_label; ?>">Phone Number</label>
                                <i class="<?= $icon_style; ?> fas fa-phone"></i>
                            </div>
                        </div>
                    </div>

                    <div class="<?= $card_style; ?> slide-in delay-2">
                         <h2 class="text-lg font-bold text-slate-800 mb-8 flex items-center gap-3">
                            <span class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center text-lg shadow-sm shadow-indigo-100"><i class="fas fa-map-marked-alt"></i></span>
                            Location & Tax
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6">
                            <div class="<?= $input_wrapper; ?> md:col-span-2">
                                <textarea name="address" id="address" rows="2" class="<?= $input_field; ?> resize-none" placeholder=" "><?= htmlspecialchars($d['address']); ?></textarea>
                                <label for="address" class="<?= $input_label; ?>">Full Address</label>
                                <i class="<?= $icon_style; ?> fas fa-map-pin"></i>
                            </div>
                            <div class="<?= $input_wrapper; ?>">
                                <input type="text" name="city_zip" id="city_zip" value="<?= htmlspecialchars($d['city_zip']); ?>" class="<?= $input_field; ?>" placeholder=" ">
                                <label for="city_zip" class="<?= $input_label; ?>">City & Zip Code</label>
                                <i class="<?= $icon_style; ?> fas fa-city"></i>
                            </div>
                            <div class="<?= $input_wrapper; ?>">
                                <input type="text" name="vat_number" id="vat_number" value="<?= htmlspecialchars($d['vat_number']); ?>" class="<?= $input_field; ?>" placeholder=" ">
                                <label for="vat_number" class="<?= $input_label; ?>">VAT / BIN Number</label>
                                <i class="<?= $icon_style; ?> fas fa-file-invoice-dollar"></i>
                            </div>
                            <div class="<?= $input_wrapper; ?>">
                                <select name="timezone" id="timezone" class="<?= $input_field; ?> cursor-pointer bg-white/0">
                                    <option value="Asia/Dhaka" <?= $d['timezone'] == 'Asia/Dhaka' ? 'selected' : ''; ?>>Asia/Dhaka (GMT+6)</option>
                                    <option value="UTC" <?= $d['timezone'] == 'UTC' ? 'selected' : ''; ?>>UTC</option>
                                </select>
                                <label for="timezone" class="<?= $input_label; ?>">Timezone</label>
                                <i class="fas fa-globe absolute right-4 top-5 text-slate-400 pointer-events-none text-xs"></i>
                            </div>
                        </div>
                    </div>

                    <div class="<?= $card_style; ?> slide-in delay-3">
                        <h2 class="text-lg font-bold text-slate-800 mb-8 flex items-center gap-3">
                            <span class="w-10 h-10 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center text-lg shadow-sm shadow-rose-100"><i class="fas fa-shield-alt"></i></span>
                            Operational Controls
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="<?= $input_wrapper; ?>">
                                <input type="number" name="max_line_disc" id="max_line_disc" value="<?= $d['max_line_disc']; ?>" class="<?= $input_field; ?> text-center font-bold text-slate-600" placeholder=" ">
                                <label for="max_line_disc" class="<?= $input_label; ?>">Max Line Disc %</label>
                            </div>
                             <div class="<?= $input_wrapper; ?>">
                                <input type="number" name="max_inv_disc" id="max_inv_disc" value="<?= $d['max_inv_disc']; ?>" class="<?= $input_field; ?> text-center font-bold text-slate-600" placeholder=" ">
                                <label for="max_inv_disc" class="<?= $input_label; ?>">Max Inv Disc %</label>
                            </div>
                             <div class="<?= $input_wrapper; ?>">
                                <input type="number" name="approval_disc" id="approval_disc" value="<?= $d['approval_disc']; ?>" class="<?= $input_field; ?> text-center font-bold text-slate-600" placeholder=" ">
                                <label for="approval_disc" class="<?= $input_label; ?>">Manager Appr %</label>
                            </div>
                            <div class="<?= $input_wrapper; ?> md:col-span-2">
                                <select name="overselling" id="overselling" class="<?= $input_field; ?> cursor-pointer bg-white/0">
                                    <option value="deny" <?= $d['overselling'] == 'deny' ? 'selected' : ''; ?>>🚫 Do Not Allow (Strict)</option>
                                    <option value="warning" <?= $d['overselling'] == 'warning' ? 'selected' : ''; ?>>⚠️ Allow with Warning</option>
                                    <option value="allow" <?= $d['overselling'] == 'allow' ? 'selected' : ''; ?>>✅ Allow Unlimited</option>
                                </select>
                                <label for="overselling" class="<?= $input_label; ?>">Overselling Policy</label>
                                <i class="fas fa-chevron-down absolute right-4 top-5 text-slate-400 pointer-events-none text-xs"></i>
                            </div>
                             <div class="<?= $input_wrapper; ?>">
                                <input type="number" name="low_stock" id="low_stock" value="<?= $d['low_stock']; ?>" class="<?= $input_field; ?> text-center font-bold text-slate-600" placeholder=" ">
                                <label for="low_stock" class="<?= $input_label; ?>">Low Stock Alert</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-4 space-y-6 sticky top-28">
                    <div class="<?= $card_style; ?> slide-in delay-1 !bg-gradient-to-br from-teal-700 via-teal-800 to-emerald-800 text-white !border-none shadow-teal-900/20 shadow-xl p-8">
                            <div class="flex items-center justify-between">
                            <div>
                                <h3 class="font-bold text-xl">Store Status</h3>
                                <p class="text-teal-100 text-sm mt-1 opacity-90 font-medium">Branch operations</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="status" class="sr-only peer" <?= $d['status'] == 1 ? 'checked' : ''; ?>>
                                <div class="w-16 h-9 bg-black/20 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[4px] after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-7 after:w-7 after:transition-all peer-checked:bg-white/30 backdrop-blur-md shadow-inner"></div>
                            </label>
                        </div>
                    </div>

                    <div class="<?= $card_style; ?> slide-in delay-2">
                            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-6 flex items-center gap-2">
                            <i class="fas fa-clock"></i> Daily Operations
                        </h3>
                            <div class="space-y-5">
                            <div class="<?= $input_wrapper; ?>">
                                <input type="number" name="daily_target" id="daily_target" value="<?= $d['daily_target']; ?>" class="pl-8 <?= $input_field; ?> font-mono font-bold text-teal-600 text-right text-lg" placeholder=" ">
                                <label for="daily_target" class="<?= $input_label; ?>">Daily Sales Target</label>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="<?= $input_wrapper; ?>">
                                    <input type="time" name="open_time" id="open_time" value="<?= $d['open_time']; ?>" class="<?= $input_field; ?> text-center px-2 font-bold">
                                    <label for="open_time" class="<?= $input_label; ?>">Open</label>
                                </div>
                                <div class="<?= $input_wrapper; ?>">
                                    <input type="time" name="close_time" id="close_time" value="<?= $d['close_time']; ?>" class="<?= $input_field; ?> text-center px-2 font-bold">
                                    <label for="close_time" class="<?= $input_label; ?>">Close</label>
                                </div>
                            </div>
                            </div>
                    </div>

                    <div class="<?= $card_style; ?> slide-in delay-3">
                        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-6 flex items-center gap-2">
                            <i class="fas fa-toggle-on"></i> Quick Rules
                        </h3>
                        <div class="space-y-4">
                            <label class="flex items-center justify-between p-4 rounded-2xl bg-slate-50 hover:bg-teal-50/50 border border-slate-100 hover:border-teal-100 transition cursor-pointer group">
                                <div class="flex items-center gap-3">
                                    <span class="w-8 h-8 rounded-lg bg-white flex items-center justify-center text-slate-500 group-hover:text-teal-500 transition shadow-sm"><i class="fas fa-hand-holding-usd"></i></span>
                                    <span class="text-sm font-bold text-slate-700 group-hover:text-teal-700 transition">Manual Price</span>
                                </div>
                                <div class="relative inline-flex items-center">
                                    <input type="checkbox" name="allow_manual_price" class="sr-only peer" <?= $d['allow_manual_price'] == 1 ? 'checked' : ''; ?>>
                                    <div class="w-11 h-6 bg-slate-200 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-teal-500 shadow-inner"></div>
                                </div>
                            </label>
                            <label class="flex items-center justify-between p-4 rounded-2xl bg-slate-50 hover:bg-rose-50/50 border border-slate-100 hover:border-rose-100 transition cursor-pointer group">
                                <div class="flex items-center gap-3">
                                    <span class="w-8 h-8 rounded-lg bg-white flex items-center justify-center text-slate-500 group-hover:text-rose-500 transition shadow-sm"><i class="fas fa-calendar-times"></i></span>
                                    <span class="text-sm font-bold text-slate-700 group-hover:text-rose-700 transition">Backdated Sales</span>
                                </div>
                                <div class="relative inline-flex items-center">
                                    <input type="checkbox" name="allow_backdate" class="sr-only peer" <?= $d['allow_backdate'] == 1 ? 'checked' : ''; ?>>
                                    <div class="w-11 h-6 bg-slate-200 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-rose-500 shadow-inner"></div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="slide-in delay-3 pt-4">
                        <button type="submit" name="<?= $btn_name; ?>" class="w-full py-4 rounded-2xl bg-gradient-to-br from-teal-700 via-teal-800 to-emerald-800 text-white font-bold text-lg shadow-2xl shadow-slate-900/20 hover:bg-teal-950 hover:scale-[1.02] active:scale-[0.98] transition-all duration-300 flex items-center justify-center gap-3 group">
                            <span><?= $btn_text; ?></span>
                            <i class="fas fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                        </button>
                    </div>
                </div> 
            </div>
        </form>
    </div>
</body>
</html>