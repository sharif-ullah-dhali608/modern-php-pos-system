<?php
include('../config/dbcon.php');

if(isset($_POST['giftcard_id'])) {
    $id = (int)$_POST['giftcard_id'];
    
    // Fetch card details
    $query = "SELECT g.*, c.name as customer_name FROM giftcards g 
              LEFT JOIN customers c ON g.customer_id = c.id 
              WHERE g.id=$id LIMIT 1";
    $result = mysqli_query($conn, $query);
    
    if(mysqli_num_rows($result) > 0) {
        $card = mysqli_fetch_assoc($result);
        $balance = (float)$card['balance'];
        $value = (float)$card['value'];
        $used = $value - $balance;
        $card_no = htmlspecialchars($card['card_no']);
?>

<!-- Print-specific CSS to preserve colors and design -->
<style>
@media print {
    /* Hide non-printable elements */
    .no-print,
    .print-hide {
        display: none !important;
    }
    
    /* Force print colors and backgrounds */
    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        color-adjust: exact !important;
    }
    
    /* Force gradient backgrounds */
    .giftcard-green {
        background: linear-gradient(to bottom right, #059669, #0f766e) !important;
    }
    
    .giftcard-purple {
        background: linear-gradient(to bottom right, #7c3aed, #3730a3, #6b21a8) !important;
    }
    
    /* Ensure text colors are preserved */
    .text-white {
        color: white !important;
    }
    
    .text-emerald-600 {
        color: #059669 !important;
    }
    
    .text-emerald-500 {
        color: #10b981 !important;
    }
    
    .text-rose-500 {
        color: #f43f5e !important;
    }
    
    .text-amber-500 {
        color: #f59e0b !important;
    }
    
    .text-slate-400 {
        color: #94a3b8 !important;
    }
    
    /* Background colors */
    .bg-white {
        background-color: white !important;
    }
    
    .bg-slate-50 {
        background-color: #f8fafc !important;
    }
    
    /* Modal specific print styling */
    #viewGiftcardModal {
        position: absolute !important;
        background: white !important;
    }
    
    #viewGiftcardModal .modal-content {
        box-shadow: none !important;
        max-height: none !important;
        overflow: visible !important;
    }
    
    #viewGiftcardModal .sticky {
        position: relative !important;
    }
    
    #viewGiftcardModal button[onclick="closeViewGiftcardModal()"] {
        display: none !important;
    }
    
    @page {
        margin: 0.5cm;
        size: auto;
    }
    
    body {
        margin: 0 !important;
        padding: 0 !important;
    }
}
</style>

<div class="space-y-8" id="giftcard-printable-area">
    <div class="flex flex-col md:flex-row gap-6 justify-center items-center">
        
        <div class="giftcard-green bg-gradient-to-br from-emerald-600 to-teal-700 rounded-2xl shadow-lg p-8 text-white relative overflow-hidden w-full max-w-[400px] aspect-[1.58/1]">
            <div class="absolute top-0 right-0 opacity-10">
                <i class="fas fa-credit-card text-8xl"></i>
            </div>
            
            <div class="relative z-10 h-full flex flex-col justify-between">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-[10px] font-semibold opacity-80 uppercase tracking-widest">Giftcard Number</p>
                        <p class="text-lg font-bold mt-1 tracking-wider"><?= $card['card_no']; ?></p>
                    </div>
                </div>

                <div class="text-center">
                    <p class="text-4xl font-black">USD <?= number_format($balance, 2); ?></p>
                    <p class="text-[10px] opacity-80 mt-1 uppercase tracking-[0.2em]">Card Balance</p>
                </div>

                <div>
                    <div class="bg-white rounded-lg p-2 mb-4 flex justify-center items-center h-12 overflow-hidden" id="barcode-container-<?= $id ?>">
                        <svg id="modal-barcode-svg-<?= $id ?>" style="width: 100%; height: 100%; max-width: 100%;"></svg>
                    </div>
                    
                    <div class="flex justify-between items-end text-[10px] uppercase font-bold">
                        <div>
                            <p class="opacity-70">Card Holder</p>
                            <p class="text-xs"><?= htmlspecialchars($card['customer_name'] ?? 'Guest'); ?></p>
                        </div>
                        <div class="text-right">
                            <p class="opacity-70">Expiry</p>
                            <p class="text-xs"><?= date('Y-m-d', strtotime($card['expiry_date'])); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="giftcard-purple bg-gradient-to-br from-purple-700 via-indigo-800 to-purple-900 rounded-2xl shadow-lg w-full max-w-[400px] aspect-[1.58/1] flex items-center justify-center p-8">
            <div class="bg-white rounded-2xl p-6 shadow-2xl flex items-center justify-center min-w-[150px] min-h-[100px]">
                <?php if(!empty($card['image'])): ?>
                    <img src="<?= $card['image']; ?>" class="max-h-16 w-auto object-contain">
                <?php else: ?>
                    <div class="text-center">
                        <p class="text-2xl font-black text-slate-800 tracking-tighter">Modern</p>
                        <p class="bg-orange-600 text-white text-[10px] px-2 py-0.5 rounded font-black italic">POS</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-4 gap-4 px-2">
        <div class="bg-slate-50 p-3 rounded-xl border border-slate-100 text-center">
            <p class="text-[9px] font-black text-slate-400 uppercase">Value</p>
            <p class="text-xs font-bold">USD <?= number_format($value, 2); ?></p>
        </div>
        <div class="bg-slate-50 p-3 rounded-xl border border-slate-100 text-center">
            <p class="text-[9px] font-black text-slate-400 uppercase">Remaining</p>
            <p class="text-xs font-bold text-emerald-600">USD <?= number_format($balance, 2); ?></p>
        </div>
        <div class="bg-slate-50 p-3 rounded-xl border border-slate-100 text-center">
            <p class="text-[9px] font-black text-slate-400 uppercase">Used</p>
            <p class="text-xs font-bold text-rose-500">USD <?= number_format($used, 2); ?></p>
        </div>
        <div class="bg-slate-50 p-3 rounded-xl border border-slate-100 text-center">
            <p class="text-[9px] font-black text-slate-400 uppercase">Status</p>
            <span class="text-[9px] font-black <?= $card['status']==1?'text-emerald-500':'text-amber-500' ?> uppercase italic">
                <?= $card['status']==1?'Active':'Inactive' ?>
            </span>
        </div>
    </div>

    <div class="flex gap-4 pt-4 border-t border-slate-100 no-print">
        <button id="topup-btn-<?= $id ?>" type="button"
                class="flex-1 bg-amber-500 hover:bg-amber-600 text-white py-4 rounded-2xl font-black uppercase text-xs tracking-widest transition-all shadow-lg">
            <i class="fas fa-plus-circle mr-2"></i> Add Topup
        </button>
        
        <button id="print-btn-<?= $id ?>" type="button"
                class="px-10 bg-slate-900 hover:bg-black text-white py-4 rounded-2xl font-black uppercase text-xs tracking-widest transition-all flex items-center gap-2">
            <i class="fas fa-print"></i> Print
        </button>
    </div>
</div>

<!-- Data attributes for parent page to use -->
<div id="modal-card-data-<?= $id ?>" 
     data-card-id="<?= $id ?>" 
     data-card-no="<?= $card_no ?>" 
     style="display:none;"></div>

<?php 
    } else {
        echo "<p class='text-center py-10'>Giftcard not found.</p>";
    }
} 
?>