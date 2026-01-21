<?php
session_start();
$base_path = '../'; 
include($base_path . 'config/dbcon.php');

// Security Check
if(!isset($_SESSION['auth'])){
    header("Location: /pos/login");
    exit(0);
}

// Stats
$total_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM installment_orders"))['count'] ?? 0;
$total_receivable = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(payable) as total FROM installment_payments"))['total'] ?? 0;
$total_received = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(paid) as total FROM installment_payments"))['total'] ?? 0;
$total_due = $total_receivable - $total_received;

$due_today = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(due) as total FROM installment_payments WHERE DATE(payment_date) = CURDATE() AND payment_status = 'due'"))['total'] ?? 0;
$overdue = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(due) as total FROM installment_payments WHERE DATE(payment_date) < CURDATE() AND payment_status = 'due'"))['total'] ?? 0;

$page_title = "Installment Overview - Velocity POS";
include($base_path . 'includes/header.php');
?>

<div class="app-wrapper">
    <?php include($base_path . 'includes/sidebar.php'); ?>
    
    <main id="main-content" class="flex-1 lg:ml-64 flex flex-col h-screen min-w-0 transition-all duration-300">
        <div class="navbar-fixed-top">
            <?php include($base_path . 'includes/navbar.php'); ?>
        </div>
        
        <div class="content-scroll-area custom-scroll h-full overflow-y-auto">
            <div class="p-6">
                <h1 class="text-3xl font-black text-slate-800 mb-6">Installment Overview</h1>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-blue-500">
                        <div class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Total Orders</div>
                        <div class="text-2xl font-black text-slate-800"><?= $total_orders; ?></div>
                    </div>
                    <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-teal-500">
                        <div class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Total Received</div>
                        <div class="text-2xl font-black text-teal-600">৳<?= number_format($total_received, 2); ?></div>
                    </div>
                    <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-orange-500">
                        <div class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Due Today</div>
                        <div class="text-2xl font-black text-orange-600">৳<?= number_format($due_today, 2); ?></div>
                    </div>
                    <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-red-500">
                        <div class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Total Overdue</div>
                        <div class="text-2xl font-black text-red-600">৳<?= number_format($overdue, 2); ?></div>
                    </div>
                </div>

                <div class="mt-8 bg-white p-6 rounded-xl shadow-sm border border-slate-100">
                    <h2 class="text-xl font-bold text-slate-800 mb-4 text-center md:text-left">Financial Summary</h2>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <tbody>
                                <tr class="border-b border-slate-50">
                                    <td class="py-4 font-semibold text-slate-600">Total Installment Value (Principal + Interest)</td>
                                    <td class="py-4 text-right font-bold text-slate-800">৳<?= number_format($total_receivable, 2); ?></td>
                                </tr>
                                <tr class="border-b border-slate-50">
                                    <td class="py-4 font-semibold text-slate-600">Amount Already Collected</td>
                                    <td class="py-4 text-right font-bold text-teal-600">৳<?= number_format($total_received, 2); ?></td>
                                </tr>
                                <tr>
                                    <td class="py-4 font-semibold text-slate-600">Remaining Balance</td>
                                    <td class="py-4 text-right font-bold text-red-600">৳<?= number_format($total_due, 2); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php include($base_path . 'includes/footer.php'); ?>
        </div>
    </main>
</div>
