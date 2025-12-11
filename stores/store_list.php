<?php
session_start();
include('../config/dbcon.php');

// 1. SECURITY CHECK
if(!isset($_SESSION['auth'])){
    header("Location: /pos/login");
    exit(0);
}

// 2. FETCH DATA FROM DATABASE
$query = "SELECT * FROM stores ORDER BY id DESC";
$query_run = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Store List - Velocity POS</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }

        /* === 1. EXACT BACKGROUND (FIXED) === */
        .fixed-bg {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -50;
            background: linear-gradient(-45deg, #ccfbf1, #5eead4, #0f766e, #115e59, #99f6e4);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
            will-change: background-position;
        }
        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* === 2. FLOATING SHAPES === */
        .shape-container { position: fixed; inset: 0; z-index: -40; pointer-events: none; overflow: hidden; }
        .float-slow { animation: float 8s ease-in-out infinite; }
        .float-medium { animation: float 6s ease-in-out infinite; }
        .float-fast { animation: float 4s ease-in-out infinite; }
        @keyframes float {
            0% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-30px) rotate(5deg); }
            100% { transform: translateY(0px) rotate(0deg); }
        }
        .shape-shadow { box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2), 0 10px 10px -5px rgba(0, 0, 0, 0.1); }
        .icon-shadow { filter: drop-shadow(0 10px 10px rgba(0, 0, 0, 0.25)); }

        /* === 3. UI ANIMATIONS === */
        .slide-in { animation: slideIn 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; transform: translateY(30px); }
        @keyframes slideIn { to { opacity: 1; transform: translateY(0); } }

        /* === 4. GLASS CARD STYLE === */
        .glass-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.6);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: all 0.3s ease;
        }
        .glass-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            border-color: rgba(13, 148, 136, 0.5); /* Teal border on hover */
        }

        /* Scrollbar styling */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #0f766e; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #115e59; }
    </style>
</head>

<body class="min-h-screen text-slate-800 selection:bg-teal-200 selection:text-teal-900">

    <div class="fixed-bg"></div>
    <div class="shape-container">
        <div class="absolute top-10 left-[5%] w-32 h-32 bg-teal-900/20 rounded-3xl shape-shadow float-slow backdrop-blur-sm border border-white/10"></div>
        <div class="absolute bottom-20 right-[5%] w-48 h-48 bg-emerald-900/15 rounded-full shape-shadow float-medium delay-1000 backdrop-blur-sm"></div>
        <div class="absolute top-[40%] left-[10%] w-16 h-16 bg-teal-800/20 rounded-xl shape-shadow float-fast delay-2000 rotate-12"></div>
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
                    <h1 class="text-xl font-bold text-slate-800 tracking-tight">Store List</h1>
                    <div class="flex items-center gap-2 text-[10px] font-bold text-slate-500 uppercase tracking-widest">
                        <span class="w-2 h-2 rounded-full bg-teal-600 animate-pulse"></span>
                        Branch Overview
                    </div>
                </div>
            </div>
            
            <a href="add_store.php" class="flex items-center gap-2 px-5 py-2.5 rounded-full bg-teal-700 text-white font-bold text-sm hover:bg-teal-800 hover:shadow-lg transition-all transform active:scale-95 group">
                <i class="fas fa-plus bg-white/20 rounded-full p-1 w-5 h-5 flex items-center justify-center text-[10px]"></i>
                <span>Add Store</span>
            </a>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 relative z-10">
        
        <?php if(mysqli_num_rows($query_run) > 0): ?>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php 
                $delay = 0;
                while($row = mysqli_fetch_assoc($query_run)): 
                    $delay += 0.1; // Staggered animation delay
                    $status_color = $row['status'] == 1 ? 'bg-emerald-100 text-emerald-700 border-emerald-200' : 'bg-red-100 text-red-700 border-red-200';
                    $status_text = $row['status'] == 1 ? 'Active' : 'Inactive';
                    $status_icon = $row['status'] == 1 ? 'fa-check-circle' : 'fa-times-circle';
                ?>
                
                <div class="glass-card rounded-3xl p-6 relative group slide-in" style="animation-delay: <?= $delay; ?>s;">
                    
                    <div class="absolute top-4 right-4 flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                        <a href="add_store.php?id=<?= $row['id']; ?>" class="w-8 h-8 rounded-full bg-white text-blue-500 hover:bg-blue-500 hover:text-white flex items-center justify-center shadow-sm border border-slate-200 transition-colors" title="Edit">
                            <i class="fas fa-pencil-alt text-xs"></i>
                        </a>
                        <button onclick="confirmDelete(<?= $row['id']; ?>)" class="w-8 h-8 rounded-full bg-white text-red-500 hover:bg-red-500 hover:text-white flex items-center justify-center shadow-sm border border-slate-200 transition-colors" title="Delete">
                            <i class="fas fa-trash text-xs"></i>
                        </button>
                    </div>

                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-teal-50 to-teal-100 text-teal-600 flex items-center justify-center text-xl shadow-inner border border-white">
                                <i class="fas fa-store"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-800 text-lg leading-tight group-hover:text-teal-700 transition-colors">
                                    <?= htmlspecialchars($row['store_name']); ?>
                                </h3>
                                <span class="text-xs font-mono text-slate-400 uppercase tracking-wide bg-slate-100 px-1.5 py-0.5 rounded">
                                    <?= htmlspecialchars($row['store_code']); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3 mb-6">
                        <div class="flex items-center gap-3 text-sm text-slate-600">
                            <span class="w-6 h-6 rounded-full bg-slate-50 flex items-center justify-center text-slate-400 text-xs"><i class="fas fa-phone"></i></span>
                            <span class="font-medium"><?= $row['phone'] ? htmlspecialchars($row['phone']) : 'N/A'; ?></span>
                        </div>
                        <div class="flex items-center gap-3 text-sm text-slate-600">
                            <span class="w-6 h-6 rounded-full bg-slate-50 flex items-center justify-center text-slate-400 text-xs"><i class="fas fa-map-marker-alt"></i></span>
                            <span class="truncate max-w-[200px]"><?= $row['city_zip'] ? htmlspecialchars($row['city_zip']) : 'Location N/A'; ?></span>
                        </div>
                        <div class="flex items-center gap-3 text-sm text-slate-600">
                            <span class="w-6 h-6 rounded-full bg-slate-50 flex items-center justify-center text-slate-400 text-xs"><i class="fas fa-tag"></i></span>
                            <span><?= htmlspecialchars($row['business_type']); ?></span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-4 border-t border-slate-100">
                        <span class="px-3 py-1 rounded-full text-xs font-bold border flex items-center gap-1.5 <?= $status_color; ?>">
                            <i class="fas <?= $status_icon; ?>"></i> <?= $status_text; ?>
                        </span>
                        
                        <div class="text-right">
                            <p class="text-[10px] text-slate-400 uppercase font-bold tracking-wider">Target</p>
                            <p class="text-sm font-bold text-slate-700 font-mono">৳ <?= number_format($row['daily_target'], 2); ?></p>
                        </div>
                    </div>

                </div>
                <?php endwhile; ?>
            </div>

        <?php else: ?>
            
            <div class="flex flex-col items-center justify-center h-[60vh] text-center slide-in">
                <div class="w-24 h-24 bg-white/50 rounded-full flex items-center justify-center mb-6 shadow-sm backdrop-blur-sm">
                    <i class="fas fa-store-slash text-4xl text-slate-300"></i>
                </div>
                <h2 class="text-2xl font-bold text-slate-700">No Stores Found</h2>
                <p class="text-slate-500 mt-2 mb-8 max-w-xs mx-auto">It looks like you haven't added any branches yet. Start by creating your first store.</p>
                <a href="add_store.php" class="px-8 py-3 rounded-xl bg-teal-600 text-white font-bold shadow-lg hover:bg-teal-700 hover:shadow-teal-500/30 transition-all">
                    Create First Store
                </a>
            </div>

        <?php endif; ?>

    </div>

    <script>
        function confirmDelete(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#cbd5e1',
                confirmButtonText: 'Yes, delete it!',
                background: '#fff',
                color: '#334155'
            }).then((result) => {
                if (result.isConfirmed) {
                    var form = document.createElement("form");
                    form.method = "POST";
                    form.action = "save_store.php";

                    var inputId = document.createElement("input");
                    inputId.type = "hidden";
                    inputId.name = "delete_id";
                    inputId.value = id;
                    form.appendChild(inputId);

                    var inputBtn = document.createElement("input");
                    inputBtn.type = "hidden";
                    inputBtn.name = "delete_store_btn";
                    inputBtn.value = true;
                    form.appendChild(inputBtn);

                    document.body.appendChild(form);
                    form.submit();
                }
            })
        }
    </script>

</body>
</html>