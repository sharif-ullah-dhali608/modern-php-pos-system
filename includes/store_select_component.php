<?php
/**
 * Dynamic Store Selection Component with Select All
 * Required Variables: $all_stores, $selected_stores, $store_label, $search_placeholder
 */
$label = isset($store_label) ? $store_label : "Available in Stores";
$placeholder = isset($search_placeholder) ? $search_placeholder : "Search stores...";
$list_class = isset($store_list_class) ? $store_list_class : "max-h-64";
?>
<div>
    <div class="flex items-center justify-between mb-2">
        <label class="block text-sm font-bold text-slate-700 uppercase tracking-wide">
            <?= $label; ?> <span class="text-red-600">*</span>
        </label>
        
        <label class="flex items-center space-x-2 cursor-pointer group">
            <input type="checkbox" id="selectAllStores" class="form-checkbox h-4 w-4 text-teal-600 border-slate-300 rounded focus:ring-teal-500 transition-all">
            <span class="text-xs font-bold text-teal-600 uppercase group-hover:text-teal-700 transition-colors">Select All</span>
        </label>
    </div>

    <div class="bg-slate-50 border border-slate-200 rounded-xl p-5 <?= $list_class; ?> overflow-y-auto custom-scroll shadow-inner">
        <div class="relative mb-4">
            <input 
                type="text" 
                id="storeSearch" 
                placeholder="<?= $placeholder; ?>" 
                class="w-full bg-white border border-slate-200 rounded-lg px-4 py-2.5 text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-transparent transition-all shadow-sm"
                onkeyup="filterStores()"
            >
            <i class="fas fa-search absolute right-3 top-1/2 transform -translate-y-1/2 text-slate-400"></i>
        </div>

        <div class="space-y-1.5" id="storeList">
            <?php if(!empty($all_stores)): ?>
                <?php foreach($all_stores as $store): ?>
                    <?php $is_checked = in_array($store['id'], $selected_stores) ? 'checked' : ''; ?>
                    <label class="flex items-center gap-3 p-3 rounded-lg hover:bg-teal-50 cursor-pointer text-slate-700 border border-transparent hover:border-teal-100 transition-all group">
                        <input 
                            type="checkbox" 
                            name="stores[]" 
                            value="<?= $store['id']; ?>"
                            <?= $is_checked; ?>
                            class="store-checkbox h-5 w-5 rounded border-slate-300 text-teal-600 focus:ring-teal-500 cursor-pointer accent-teal-600"
                        >
                        <span class="text-sm font-medium group-hover:text-teal-900 transition-colors">
                            <?= htmlspecialchars($store['store_name']); ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <p id="store-error" class="text-xs text-red-500 mt-4 font-bold hidden"><i class="fas fa-exclamation-circle mr-1"></i> At least one store must be selected.</p>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAllBtn = document.getElementById('selectAllStores');
    const storeCheckboxes = document.querySelectorAll('.store-checkbox');
    const storeError = document.getElementById('store-error');

    if(selectAllBtn) {
        function updateSelectAllStatus() {
            const allChecked = Array.from(storeCheckboxes).every(c => c.checked);
            selectAllBtn.checked = allChecked;
        }

        // Run initial check
        updateSelectAllStatus();

        // Select All Logic
        selectAllBtn.addEventListener('change', function() {
            storeCheckboxes.forEach(cb => {
                if (cb.closest('label').style.display !== 'none') {
                    cb.checked = this.checked;
                }
            });
            if(storeError) storeError.classList.add('hidden');
        });

        // Individual Checkbox Logic
        storeCheckboxes.forEach(cb => {
            cb.addEventListener('change', function() {
                updateSelectAllStatus();
                if(storeError && this.checked) storeError.classList.add('hidden');
            });
        });
    }
});

// Search Filter Function
function filterStores() {
    const input = document.getElementById('storeSearch');
    const filter = input.value.toLowerCase();
    const storeList = document.getElementById('storeList');
    if(!storeList) return;
    
    const labels = storeList.querySelectorAll('label'); 
    labels.forEach(label => {
        const span = label.querySelector('span');
        const text = span ? (span.textContent || span.innerText) : "";
        label.style.display = text.toLowerCase().includes(filter) ? 'flex' : 'none';
    });
}
</script>