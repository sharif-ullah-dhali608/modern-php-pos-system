<?php
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
                class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-br from-teal-900 via-teal-800 to-emerald-900 hover:to-emerald-800 text-white font-semibold rounded-lg shadow-lg hover:from-teal-700 hover:to-indigo-700 transition-all transform hover:scale-105"
            >
                <i class="fas fa-plus"></i>
                <span>Add New</span>
            </a>
        </div>
    </div>
    
    <div class="glass-card rounded-xl p-6 shadow-lg border border-slate-200 slide-in">
        <?php if(count($data) > 0): ?>
            <div class="overflow-x-auto">
                <table class="data-table w-full" id="<?= $table_id; ?>">
                    <thead>
                        <tr>
                            <?php foreach($columns as $col): ?>
                                <th class="<?= isset($col['sortable']) && $col['sortable'] ? 'cursor-pointer hover:bg-slate-100' : ''; ?> text-slate-800">
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
                            <tr class="hover:bg-slate-50 transition-colors">
                                <?php foreach($columns as $col): ?>
                                    <td>
                                        <?php
                                        $key = $col['key'];
                                        $type = $col['type'] ?? 'text';
                                        
                                        if($key == 'actions'):
                                            // Render action buttons
                                            ?>
                                            <div class="flex items-center gap-2">
                                                <?php if($view_url): ?>
                                                    <a 
                                                        href="<?= $view_url; ?>?id=<?= $row[$primary_key]; ?>" 
                                                        class="btn-action btn-view"
                                                        title="View"
                                                    >
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <a 
                                                    href="<?= $edit_url; ?>?id=<?= $row[$primary_key]; ?>" 
                                                    class="btn-action btn-edit"
                                                    title="Edit"
                                                >
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                
                                                <button 
                                                    onclick="confirmDelete(<?= $row[$primary_key]; ?>, '<?= addslashes($row[$name_field]); ?>', '<?= $delete_url; ?>')" 
                                                    class="btn-action btn-delete"
                                                    title="Delete"
                                                >
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                            <?php
                                        elseif($type == 'status'):
                                            // Render status badge (Status colors are kept vibrant)
                                            $status = isset($row['status']) ? (int)$row['status'] : 0;
                                            $status_class = $status == 1 ? 'status-active' : 'status-inactive';
                                            $status_text = $status == 1 ? 'Active' : 'Inactive';
                                            ?>
                                            <button 
                                                onclick="toggleStatus(<?= $row[$primary_key]; ?>, <?= $status; ?>, '<?= $status_url; ?>')"
                                                class="status-badge <?= $status_class; ?> cursor-pointer hover:opacity-80 transition-opacity"
                                            >
                                                <i class="fas fa-<?= $status == 1 ? 'check' : 'times'; ?>-circle mr-1"></i>
                                                <?= $status_text; ?>
                                            </button>
                                            <?php
                                        elseif($type == 'badge'):
                                            // Render custom badge (Colors are kept vibrant)
                                            $badge_value = isset($row[$key]) ? $row[$key] : '';
                                            $badge_class = isset($col['badge_class']) ? $col['badge_class'] : 'bg-teal-500/20 text-teal-600'; // Adjusted text color for visibility
                                            ?>
                                            <span class="status-badge <?= $badge_class; ?>">
                                                <?= htmlspecialchars($badge_value); ?>
                                            </span>
                                            <?php
                                        elseif($type == 'date'):
                                            // Format date
                                            $date_value = isset($row[$key]) ? $row[$key] : '';
                                            echo $date_value ? date('M d, Y', strtotime($date_value)) : 'N/A';
                                        elseif($type == 'currency'):
                                            // Format currency
                                            $amount = isset($row[$key]) ? (float)$row[$key] : 0;
                                            $symbol = isset($col['symbol']) ? $col['symbol'] : '$';
                                            echo $symbol . number_format($amount, 2);
                                        elseif($type == 'image'):
                                            // Render image
                                            $image_url = isset($row[$key]) ? $row[$key] : '';
                                            if($image_url):
                                                ?>
                                                <img src="<?= htmlspecialchars($image_url); ?>" alt="" class="w-10 h-10 rounded-lg object-cover border border-slate-200">
                                                <?php
                                            else:
                                                echo '<span class="text-slate-400">No Image</span>';
                                            endif;
                                        else:
                                            // Default text render
                                            $value = isset($row[$key]) ? $row[$key] : '';
                                            // Ensure general text is dark
                                            echo '<span class="text-slate-700">' . htmlspecialchars($value) . '</span>';
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
                <div class="w-24 h-24 bg-slate-100 rounded-full flex items-center justify-center mb-6 border border-slate-200">
                    <i class="fas fa-inbox text-4xl text-slate-400"></i>
                </div>
                <h3 class="text-xl font-bold text-slate-800 mb-2">No Data Found</h3>
                <p class="text-slate-500 mb-6">Get started by adding your first item.</p>
                <a 
                    href="<?= $add_url; ?>" 
                    class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-br from-teal-900 via-teal-800 to-emerald-900 hover:to-emerald-800 text-white font-semibold rounded-lg shadow-lg hover:from-teal-700 hover:to-indigo-700 transition-all"
                >
                    <i class="fas fa-plus"></i>
                    <span>Add New Item</span>
                </a>
            </div>
        <?php endif; ?>
    </div>
    
    <?php
}