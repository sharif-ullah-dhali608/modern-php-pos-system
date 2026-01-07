<?php
/**
 * Reusable List Renderer (Cleaned & Integrated with Footer Swal)
 */
function renderReusableList($config) {
    // -----------------------------------------
    // Configuration Setup
    // -----------------------------------------
    $title       = $config['title'] ?? 'List';
    $table_id    = $config['table_id'] ?? 'dataTable_' . uniqid();
    $columns     = $config['columns'] ?? [];
    $data        = $config['data'] ?? [];
    
    // URL Configurations
    $add_url     = $config['add_url'] ?? '#';
    $edit_url    = $config['edit_url'] ?? '#';
    $delete_url  = $config['delete_url'] ?? '#';
    $status_url  = $config['status_url'] ?? '#';
    $view_url    = $config['view_url'] ?? null;
    
    // Key Fields
    $primary_key = $config['primary_key'] ?? 'id';
    $name_field  = $config['name_field'] ?? 'name';
    ?>

    <style>
        .slide-in { animation: slideIn 0.3s ease-out forwards; }
        @keyframes slideIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>

    <div class="w-full slide-in">
        
        <div class="w-full animate-fade-in">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-3xl font-black text-slate-800 mb-1"><?= htmlspecialchars($title); ?></h1>
                <div class="flex items-center gap-2 text-xs font-bold text-slate-400 uppercase tracking-widest">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Total <?= count($data); ?> entries</span>
                </div>
            </div>
            
            <div class="flex items-center gap-3">
                <div class="relative">
                    <button type="button" onclick="toggleExportDropdown()" class="inline-flex items-center gap-2 px-5 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg border border-slate-300 transition-all">
                        <i class="fas fa-upload rotate-180"></i>
                        <span>Export</span>
                        <i class="fas fa-chevron-down text-xs"></i>
                    </button>
                    <div id="exportDropdown" class="hidden absolute right-0 mt-2 w-40 bg-white rounded-lg shadow-xl border border-slate-200 z-50 overflow-hidden">
                        <button onclick="triggerDtAction('print')" class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition-colors">
                            <i class="fas fa-print w-4 text-slate-500"></i> Print
                        </button>
                        <button onclick="triggerDtAction('csv')" class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition-colors">
                            <i class="fas fa-file-csv w-4 text-slate-500"></i> Csv
                        </button>
                        <button onclick="triggerDtAction('excel')" class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition-colors">
                            <i class="fas fa-file-excel w-4 text-slate-500"></i> Excel
                        </button>
                        <button onclick="triggerDtAction('pdf')" class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition-colors">
                            <i class="fas fa-file-pdf w-4 text-slate-500"></i> Pdf
                        </button>
                        <button onclick="triggerDtAction('copy')" class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition-colors">
                            <i class="fas fa-copy w-4 text-slate-500"></i> Copy
                        </button>
                    </div>
                </div>

                <?php if($add_url !== '#'): ?>
                <a href="<?= $add_url; ?>" class="inline-flex items-center gap-2 px-6 py-3 bg-teal-600 hover:bg-teal-700 text-white font-black rounded-xl shadow-lg transition-all transform hover:-translate-y-0.5">
                    <i class="fas fa-plus"></i>
                    <span>Add New</span>
                </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden p-6">
            
            <?php if(count($data) > 0): ?>
                <div class="overflow-x-auto">
                    <table id="<?= $table_id; ?>" class="data-table w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200">
                                <?php foreach($columns as $col): ?>
                                    <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider whitespace-nowrap">
                                        <?= htmlspecialchars($col['label']); ?>
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach($data as $row): ?>
                                <tr class="hover:bg-slate-50 transition-colors group">
                                    <?php foreach($columns as $col): ?>
                                        <td class="p-4 text-sm text-slate-700 align-middle">
                                            <?php
                                            $key  = $col['key'];
                                            $type = $col['type'] ?? 'text';
                                            $val  = $row[$key] ?? '';
                                            $id   = $row[$primary_key] ?? 0;

                                            // --- Action Buttons ---
                                            if ($key === 'actions' || $type === 'actions'): ?>
                                                <div class="flex items-center gap-3">
                                                    <?php if($view_url): ?>
                                                        <a href="<?= $view_url; ?>/<?= $id; ?>" class="p-2 text-blue-500 hover:bg-blue-50 rounded transition" title="View">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    <?php endif; ?>

                                                    <a href="<?= $edit_url; ?>/<?= $id; ?>" class="p-2 text-teal-600 hover:bg-teal-50 rounded transition" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    
                                                    <button type="button" onclick="confirmDelete(<?= $id; ?>, '<?= addslashes($row[$name_field] ?? 'Item'); ?>', '<?= $delete_url; ?>')" 
                                                            class="p-2 text-red-500 hover:bg-red-50 rounded transition" title="Delete">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </div>

                                            <?php 
                                            // --- Status Toggle ---
                                            elseif ($type === 'status'): 
                                                $status = (int)$val;
                                                $active_label = $col['active_label'] ?? 'Active';
                                                $inactive_label = $col['inactive_label'] ?? 'Inactive';
                                                
                                                if($status === 1) {
                                                    $badgeClass = 'bg-emerald-100 text-emerald-700 border border-emerald-200';
                                                    $dotClass = 'bg-emerald-500';
                                                    $text = $active_label;
                                                } else {
                                                    $badgeClass = 'bg-amber-100 text-amber-700 border border-amber-200';
                                                    $dotClass = 'bg-amber-500';
                                                    $text = $inactive_label;
                                                }
                                            ?>
                                                <button type="button" onclick="toggleStatus(<?= $id; ?>, <?= $status; ?>, '<?= $status_url; ?>')"
                                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium <?= $badgeClass; ?> hover:opacity-80 transition">
                                                    <span class="w-1.5 h-1.5 rounded-full <?= $dotClass; ?>"></span>
                                                    <?= $text; ?>
                                                </button>

                                            <?php 
                                            // --- Badges ---
                                            elseif ($type === 'badge'): 
                                                $badgeClass = $col['badge_class'] ?? 'bg-slate-100 text-slate-700';
                                            ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold uppercase <?= $badgeClass; ?>">
                                                    <?= htmlspecialchars($val); ?>
                                                </span>

                                            <?php 
                                            // --- Images ---
                                            elseif ($type === 'image'): 
                                                $path = $col['path'] ?? '';
                                                $name = $row[$config['name_field'] ?? 'name'] ?? 'C';
                                            ?>
                                                <?php if(!empty($val)): ?>
                                                    <img src="<?= htmlspecialchars($path . $val); ?>" 
                                                         class="w-10 h-10 rounded-lg object-cover border border-slate-200 shadow-sm" 
                                                         alt="Img"
                                                         onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($name); ?>&background=random&size=40'; this.onerror=null;">
                                                <?php else: ?>
                                                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($name); ?>&background=random&size=40" class="w-10 h-10 rounded-lg border border-slate-200" alt="Avtr">
                                                <?php endif; ?>

                                            <?php 
                                            // --- HTML / Default Text ---
                                            elseif ($type === 'html'): 
                                                echo $val; // RAW Output
                                            else:
                                                echo htmlspecialchars($val);
                                            endif;
                                            ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php else: ?>
                <div class="flex flex-col items-center justify-center py-12 text-center">
                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4 border border-slate-100 text-slate-300">
                        <i class="fas fa-folder-open text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800 mb-1">No records found</h3>
                    <p class="text-slate-500 mb-6 text-sm">Get started by creating a new entry.</p>
                    <?php if($add_url !== '#'): ?>
                    <a href="<?= $add_url; ?>" class="px-5 py-2.5 bg-teal-600 text-white rounded-lg text-sm font-bold shadow hover:bg-teal-700 transition-all">
                        Create First Entry
                    </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php
    
}
?>

