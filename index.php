<?php
session_start();

if(!isset($_SESSION['auth'])){
    header("Location: /pos/login");
    exit(0);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-slate-50 min-h-screen">

    <nav class="bg-white/80 backdrop-blur-md border-b border-slate-200 px-6 py-4 flex justify-between items-center sticky top-0 z-50">
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-lg bg-teal-600 flex items-center justify-center text-white font-bold text-lg">V</div>
            <h1 class="text-xl font-bold text-slate-800 tracking-tight">Velocity POS</h1>
        </div>
        
        <a href="logout.php" class="flex items-center gap-2 px-4 py-2 rounded-lg bg-red-50 text-red-600 text-sm font-semibold hover:bg-red-500 hover:text-white transition-all duration-300 border border-red-100 group">
            <span>Logout</span>
            <i class="fas fa-sign-out-alt group-hover:translate-x-1 transition-transform"></i>
        </a>
    </nav>

    <div class="max-w-7xl mx-auto px-6 py-10">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Dashboard Overview</h2>
                <p class="text-slate-500 text-sm mt-1">Welcome back, Admin</p>
            </div>
            <div class="text-right hidden sm:block">
                <p class="text-2xl font-bold text-slate-800" id="clock">00:00</p>
                <p class="text-xs text-slate-400 font-bold uppercase tracking-widest" id="date">Date</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">
            
            <a href="stores/add_store.php" class="group block p-6 bg-white border border-slate-200 rounded-2xl hover:shadow-[0_8px_30px_rgb(0,0,0,0.04)] hover:border-teal-500/50 transition-all duration-300 cursor-pointer relative overflow-hidden">
                <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
                    <i class="fas fa-plus-circle text-8xl text-teal-600 transform rotate-12 group-hover:scale-110 transition-transform duration-500"></i>
                </div>
                
                <div class="relative z-10">
                    <div class="w-14 h-14 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center text-2xl mb-4 group-hover:bg-teal-600 group-hover:text-white transition-colors duration-300 shadow-sm">
                        <i class="fas fa-plus"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800 mb-1 group-hover:text-teal-700 transition-colors">Add New Store</h3>
                    <p class="text-sm text-slate-500">Create new branch configuration</p>
                </div>
            </a>

            <a href="stores/store_list.php" class="group block p-6 bg-white border border-slate-200 rounded-2xl hover:shadow-[0_8px_30px_rgb(0,0,0,0.04)] hover:border-indigo-500/50 transition-all duration-300 cursor-pointer relative overflow-hidden">
                <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
                    <i class="fas fa-list-ul text-8xl text-indigo-600 transform rotate-12 group-hover:scale-110 transition-transform duration-500"></i>
                </div>
                
                <div class="relative z-10">
                    <div class="w-14 h-14 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-2xl mb-4 group-hover:bg-indigo-600 group-hover:text-white transition-colors duration-300 shadow-sm">
                        <i class="fas fa-store"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800 mb-1 group-hover:text-indigo-700 transition-colors">All Stores</h3>
                    <p class="text-sm text-slate-500">View and manage store list</p>
                </div>
            </a>

        </div>
    </div>

    <script>
        // Simple Clock Script
        function updateClock() {
            const now = new Date();
            document.getElementById('clock').innerText = now.toLocaleTimeString('en-US', {hour12: false, hour: '2-digit', minute:'2-digit'});
            document.getElementById('date').innerText = now.toLocaleDateString('en-US', { weekday: 'long', month: 'short', day: 'numeric' });
        }
        setInterval(updateClock, 1000);
        updateClock();
    </script>
</body>
</html>