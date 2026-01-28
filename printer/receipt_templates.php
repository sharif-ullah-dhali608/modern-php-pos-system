<?php
session_start();
include('../config/dbcon.php');

if(!isset($_SESSION['auth'])){
    header("Location: /pos/login");
    exit(0);
}

$page_title = "Receipt Templates";
include('../includes/header.php');

$templates = [
    [
        'id' => 'classic',
        'name' => 'Classic Heritage',
        'desc' => 'Traditional POS design with dashed borders and centered labels.',
        'image' => '/pos/assets/images/templates/classic.png', // Placeholder or generated later
        'accent' => 'bg-slate-500'
    ],
    [
        'id' => 'modern',
        'name' => 'Modern Edge',
        'desc' => 'Clean typography, rounded corners, and bold total section.',
        'image' => '/pos/assets/images/templates/modern.png',
        'accent' => 'bg-teal-600'
    ],
    [
        'id' => 'minimal',
        'name' => 'Eco Minimal',
        'desc' => 'Optimized for paper saving. Essential info only in compact layout.',
        'image' => '/pos/assets/images/templates/minimal.png',
        'accent' => 'bg-amber-600'
    ]
];
?>

<div class="app-wrapper">
    <?php include('../includes/sidebar.php'); ?>

    <main id="main-content" class="lg:ml-64 flex flex-col h-screen">
        <div class="navbar-fixed-top">
            <?php include('../includes/navbar.php'); ?>
        </div>
        
        <div class="content-scroll-area custom-scroll">
            <div class="p-6">
                <div class="flex items-center justify-between mb-10">
                    <div>
            <h1 class="text-3xl font-black text-slate-800 tracking-tight">Receipt Design Studio</h1>
            <p class="text-slate-500 font-medium">Choose a visual identity for your customer invoices.</p>
        </div>
        <a href="/pos/stores/settings" class="bg-teal-600 text-white px-6 py-3 rounded-2xl font-bold flex items-center gap-2 shadow-lg shadow-teal-900/20 hover:bg-teal-700 transition-all">
            <i class="fas fa-cog"></i> Apply in Settings
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <?php foreach($templates as $tpl): ?>
        <div class="group h-full">
            <div class="bg-white rounded-[2rem] overflow-hidden border border-slate-100 shadow-xl shadow-slate-200/50 hover:shadow-2xl hover:shadow-teal-900/10 transition-all duration-500 flex flex-col h-full border-b-4 border-b-transparent hover:border-b-teal-500">
                <!-- Preview Canvas (Mocking with CSS) -->
                <div class="aspect-[3/4] p-8 bg-slate-50 overflow-hidden relative border-b border-slate-50">
                    <div class="w-full h-full bg-white shadow-inner rounded-lg p-6 overflow-y-auto custom-scroll scale-90 origin-top group-hover:scale-95 transition-transform duration-700">
                        <?php 
                        // Simplified preview include
                        include("../includes/receipt_templates/{$tpl['id']}.php"); 
                        ?>
                    </div>
                    
                    <!-- Overlay Badge -->
                    <div class="absolute top-6 left-6">
                        <span class="px-4 py-1.5 <?= $tpl['accent']; ?> text-white text-[10px] font-black uppercase tracking-widest rounded-full shadow-lg">
                            <?= $tpl['id']; ?>
                        </span>
                    </div>
                </div>

                <div class="p-8 flex-1 flex flex-col justify-between">
                    <div>
                        <h3 class="text-xl font-black text-slate-800 mb-2"><?= $tpl['name']; ?></h3>
                        <p class="text-slate-500 text-sm font-medium line-clamp-2"><?= $tpl['desc']; ?></p>
                    </div>
                    
                    <div class="mt-6 flex items-center justify-between">
                        <div class="flex -space-x-2">
                             <div class="w-8 h-8 rounded-full bg-slate-100 border-2 border-white flex items-center justify-center text-[10px] font-bold text-slate-400">80mm</div>
                             <div class="w-8 h-8 rounded-full bg-slate-100 border-2 border-white flex items-center justify-center text-[10px] font-bold text-slate-400">58mm</div>
                        </div>
                        <button onclick="window.location.href='/pos/stores/settings'" class="text-teal-600 font-black text-xs uppercase tracking-widest hover:text-teal-800 transition-colors">
                            Select Design <i class="fas fa-arrow-right ml-1"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
            </div> <!-- Close p-6 -->
            <?php include('../includes/footer.php'); ?>
        </div> <!-- Close content-scroll-area -->
    </main>
</div>

<style>
.custom-scroll::-webkit-scrollbar { width: 3px; }
.custom-scroll::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.05); border-radius: 10px; }
.receipt-template { max-width: 100% !important; }
</style>
