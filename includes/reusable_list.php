<?php
/**
 * Reusable List Renderer for POS System
 * Handles dynamic tables with Clean URL support
 */
function renderReusableList($config) {
    $title = $config['title'] ?? 'List';
    $add_url = $config['add_url'] ?? '#';
    $table_id = $config['table_id'] ?? 'dataTable';
    $columns = $config['columns'] ?? [];
    $data = $config['data'] ?? [];
    $edit_url = $config['edit_url'] ?? '#';
    $delete_url = $config['delete_url'] ?? '#';
    $status_url = $config['status_url'] ?? '#';
    $view_url = $config['view_url'] ?? null;
    $primary_key = $config['primary_key'] ?? 'id';
    $name_field = $config['name_field'] ?? 'name';
    ?>
    
    <div class="mb-6 slide-in">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-slate-800 mb-2"><?= htmlspecialchars($title); ?></h1>
                <div class="flex items-center gap-2 text-sm text-slate-500">
                    <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                    <span>Showing <?= count($data); ?> entries</span>
                </div>
            </div>
            <a 
                href="<?= $add_url; ?>" 
                class="inline-flex items-center gap-2 px-6 py-3 bg-[#064e3b] hover:bg-[#065f46] text-white font-semibold rounded-lg shadow-lg transition-all transform hover:scale-105"
            >
                <i class="fas fa-plus"></i>
                <span>Add New</span>
            </a>
        </div>
    </div>
    
    <div class="glass-card rounded-xl p-6 shadow-lg border border-slate-200 slide-in">
        <?php if(count($data) > 0): ?>
            <div class="overflow-x-auto custom-scroll">
                <table class="data-table w-full" id="<?= $table_id; ?>">
                    <thead>
                        <tr class="bg-slate-50">
                            <?php foreach($columns as $col): ?>
                                <th class="<?= isset($col['sortable']) && $col['sortable'] ? 'cursor-pointer hover:bg-slate-100' : ''; ?> text-slate-800 p-4 text-left font-bold border-b">
                                    <?= htmlspecialchars($col['label']); ?>
                                    <?php if(isset($col['sortable']) && $col['sortable']): ?>
                                        <i class="fas fa-sort ml-2 text-slate-400"></i>
                                    <?php endif; ?>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($data as $row): ?>
                            <tr class="hover:bg-slate-50 transition-colors border-b last:border-0">
                                <?php foreach($columns as $col): ?>
                                    <td class="p-4">
                                        <?php
                                        $key = $col['key'];
                                        $type = $col['type'] ?? 'text';
                                        
                                        if($key == 'actions'):
                                            // Action buttons with Clean URL support
                                            ?>
                                            <div class="flex items-center gap-10">
                                                <?php if($view_url): ?>
                                                    <a href="<?= $view_url; ?>/<?= $row[$primary_key]; ?>" class="btn-action btn-view text-blue-600 hover:text-blue-800" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <a href="<?= $edit_url; ?>/<?= $row[$primary_key]; ?>" class="btn-action btn-edit text-teal-600 hover:text-teal-800" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                
                                                <button 
                                                    onclick="confirmDelete(<?= $row[$primary_key]; ?>, '<?= addslashes($row[$name_field]); ?>', '<?= $delete_url; ?>')" 
                                                    class="btn-action btn-delete text-red-400 hover:text-red-800"
                                                    title="Delete"
                                                >
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                            <?php
                                        elseif($type == 'status'):
                                            // Render status badge (Status colors are kept vibrant)
                                            $status = isset($row['status']) ? (int)$row['status'] : 0;
                                            $status_class = $status == 1 ? 'bg-emerald-100 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-600 border-slate-200';
                                            $status_text = $status == 1 ? 'Active' : 'Inactive';
                                            ?>
                                            <button 
                                                onclick="toggleStatus(<?= $row[$primary_key]; ?>, <?= $status; ?>, '<?= $status_url; ?>')"
                                                class="px-3 py-1 rounded-full text-xs font-bold border <?= $status_class; ?> cursor-pointer hover:opacity-80 transition-all flex items-center gap-1"
                                            >
                                                <span class="w-1.5 h-1.5 rounded-full <?= $status == 1 ? 'bg-emerald-500 animate-pulse' : 'bg-slate-400'; ?>"></span>
                                                <?= $status_text; ?>
                                            </button>
                                            <?php
                                        elseif($type == 'image'):
                                            $image_url = isset($row[$key]) ? $row[$key] : '';
                                            echo $image_url ? '<img src="'.htmlspecialchars($image_url).'" class="w-10 h-10 rounded-lg object-cover border">' : '<span class="text-slate-400 italic">No Image</span>';
                                        else:
                                            // Default Text Render
                                            $value = isset($row[$key]) ? $row[$key] : '';
                                            echo '<span class="text-slate-700 font-medium">' . htmlspecialchars($value) . '</span>';
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
            <div class="flex flex-col items-center justify-center py-16 text-center">
                <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mb-4 border border-slate-100 text-slate-300">
                    <i class="fas fa-database text-3xl"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-800 mb-1">No results found</h3>
                <p class="text-slate-500 mb-6 text-sm">We couldn't find any data in this section.</p>
                <a href="<?= $add_url; ?>" class="px-5 py-2.5 bg-[#064e3b] text-white rounded-lg text-sm font-bold shadow-md hover:bg-[#065f46] transition-all">
                    Create First Entry
                </a>
            </div>
        <?php endif; ?>
    </div>
    <?php
}