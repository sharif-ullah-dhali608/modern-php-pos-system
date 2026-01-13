<?php
/**
 * Dynamic Status Toggle Card
 * Required Variables: $current_status, $status_title, $card_id, $label_id, $input_id, $toggle_id
 */
$is_active = ($current_status == '1');
?>
<div id="<?= $card_id; ?>" class="rounded-2xl p-7 border transition-all duration-500 shadow-md <?= $is_active ? 'bg-[#064e3b] border-[#064e3b]' : 'bg-[#f1f5f9] border-[#e2e8f0]'; ?>">
    <div class="flex items-center justify-between">
        <div class="space-y-1">
            <h3 class="text-lg font-extrabold <?= $is_active ? 'text-white' : 'text-slate-800'; ?>">
                <?= $status_title; ?> Status
            </h3>
            <p class="text-xs font-medium uppercase tracking-widest <?= $is_active ? 'text-teal-100' : 'text-slate-500'; ?>" id="<?= $label_id; ?>">
                <?= $is_active ? 'Active Operations' : 'Operations Disabled'; ?>
            </p>
        </div>
        <label class="relative inline-flex items-center cursor-pointer">
            <input type="hidden" name="status" id="<?= $input_id; ?>" value="<?= $current_status; ?>">
            <input type="checkbox" id="<?= $toggle_id; ?>" class="sr-only peer" <?= $is_active ? 'checked' : ''; ?>>
            <div class="w-12 h-7 bg-white/20 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[4px] after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-white/30 border border-white/10 shadow-inner"></div>
        </label>
    </div>
</div>

<script>
function initStatusToggle(cardId, toggleId, inputId, labelId) {
    const statusToggle = document.getElementById(toggleId);
    const statusInput = document.getElementById(inputId);
    const statusLabel = document.getElementById(labelId);
    const statusCard = document.getElementById(cardId);
    const statusTextHeader = statusCard ? statusCard.querySelector('h3') : null;

    if(statusToggle && statusCard) {
        statusToggle.addEventListener('change', function() {
            if(this.checked) {
                statusInput.value = "1";
                statusLabel.innerText = "Active Operations";
                statusLabel.className = "text-xs font-medium uppercase tracking-widest text-teal-100";
                statusTextHeader.className = "text-lg font-extrabold text-white";
                statusCard.className = "rounded-2xl p-7 border transition-all duration-500 shadow-md bg-[#064e3b] border-[#064e3b]";
            } else {
                statusInput.value = "0";
                statusLabel.innerText = "Operations Disabled";
                statusLabel.className = "text-xs font-medium uppercase tracking-widest text-slate-500";
                statusTextHeader.className = "text-lg font-extrabold text-slate-800";
                statusCard.className = "rounded-2xl p-7 border transition-all duration-500 shadow-md bg-[#f1f5f9] border-[#e2e8f0]";
            }
        });
    }
}

// Call the function for payment method page
document.addEventListener('DOMContentLoaded', function() {
    initStatusToggle('status-card', 'status_toggle', 'status_input', 'status-label');
});
</script>