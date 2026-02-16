<?php
/**
 * Store Filter Helper
 * Provides centralized store filtering logic based on user permissions
 * Works with pivot table architecture (product_store_map, customer_stores_map, etc.)
 */

/**
 * Map Table Names to Permission Module Keys
 * 
 * @param string $table_name
 * @return string
 */
function getPermissionModule($table_name) {
    $map = [
        'products' => 'product',
        'sells' => 'sell',
        'sales' => 'sell', // Alias just in case
        'quotations' => 'quotation',
        'purchases' => 'purchase',
        'customers' => 'customer',
        'suppliers' => 'supplier',
        'categories' => 'category',
        'brands' => 'brand',
        'boxes' => 'storebox',
        'units' => 'unit',
        'taxrates' => 'taxrate',
        'loans' => 'loan',
        'users' => 'user',
        'user_groups' => 'usergroup',
        'currencies' => 'currency',
        'filemanager' => 'filemanager',
        'payment_methods' => 'payment_method',
        'stores' => 'store',
        'printers' => 'printer',
        'expenses' => 'expenditure',
        'installments' => 'installment',
        'transfers' => 'transfer',
        'gift_cards' => 'giftcard',
        'reports' => 'report'
    ];
    return $map[$table_name] ?? 'settings'; // Default to settings if unknown
}

/**
 * Get Store Filter SQL Clause for Pivot Tables
 * Returns SQL WHERE clause for store filtering using JOIN with pivot tables
 * 
 * @param string $table_name - Main table name (e.g., 'products', 'customers')
 * @param string $table_alias - Table alias (e.g., 'p' for products)
 * @param string $pivot_table - Pivot table name (e.g., 'product_store_map')
 * @param string $join_condition - JOIN condition (e.g., 'p.id = psm.product_id')
 * @return array - ['join' => JOIN clause, 'where' => WHERE clause]
 */
function getStoreFilterWithJoin($table_name, $table_alias = '', $pivot_table = '', $join_condition = '') {
    // 1. Determine Module
    $module = getPermissionModule($table_name);
    
    // 2. Check Permissions (Module Specific OR Global Settings)
    // Slug format: view_all_stores_data_{module}
    $module_perm = 'view_all_stores_data_' . $module;
    
    if(hasPermission($module_perm) || hasPermission('view_all_stores_data_settings')) {
        return ['join' => '', 'where' => '']; // No filter - show all stores
    }
    
    // Filter by current store
    $current_store_id = $_SESSION['store_id'] ?? 0;
    
    // If no store is set, return empty (fallback)
    if(empty($current_store_id)) {
        return ['join' => '', 'where' => ''];
    }
    
    // Auto-detect pivot table if not provided
    if(empty($pivot_table)) {
        $pivot_map = [
            'products' => 'product_store_map',
            'customers' => 'customer_stores_map',
            'suppliers' => 'supplier_stores_map',
            'categories' => 'category_store_map',
            'brands' => 'brand_store',
            'boxes' => 'box_stores',
            'payment_methods' => 'payment_store_map',
            'taxrates' => 'taxrate_store_map',
            'printers' => 'printer_store_map',
            'currencies' => 'store_currency'
        ];
        $pivot_table = $pivot_map[$table_name] ?? '';
    }
    
    // If no pivot table, return empty
    if(empty($pivot_table)) {
        return ['join' => '', 'where' => ''];
    }
    
    // Auto-detect join condition if not provided
    if(empty($join_condition)) {
        $prefix = !empty($table_alias) ? $table_alias : $table_name;
        $pivot_alias = 'psm'; // pivot store map
        
        // Determine the foreign key column name
        // Handle irregular plurals and special cases
        $fk_map = [
            'categories' => 'category_id',
            'boxes' => 'box_id',
            'currencies' => 'currency_id'
        ];
        
        if(isset($fk_map[$table_name])) {
            $fk_column = $fk_map[$table_name];
        } else {
            // Default: remove 's' and add '_id' (products -> product_id)
            $fk_column = substr($table_name, 0, -1) . '_id';
        }
        
        $join_condition = "{$prefix}.id = {$pivot_alias}.{$fk_column}";
    }
    
    $pivot_alias = 'psm';
    // Use LEFT JOIN instead of INNER JOIN to show all records even if no pivot relationship exists
    $join = " LEFT JOIN {$pivot_table} {$pivot_alias} ON {$join_condition}";
    // Filter by store_id OR NULL (NULL means not assigned to any store yet, so show it)
    $where = " AND ({$pivot_alias}.store_id = '{$current_store_id}' OR {$pivot_alias}.store_id IS NULL)";
    
    return ['join' => $join, 'where' => $where];
}

/**
 * Get Store Filter for Direct store_id Column
 * For tables that have direct store_id column (sells, purchases, expenses)
 * 
 * @param string $table_alias - Table alias
 * @return string - WHERE clause
 */
function getStoreFilterDirect($table_alias = '') {
    // We need to guess the table name from specific aliases or context, 
    // but since this function is generic, we might default to 'settings' check
    // OR try to infer if the alias matches known modules.
    
    // INFERENCE:
    // 'p' -> purchases? products? ambiguous.
    // 's' -> sells? suppliers? ambiguous.
    // So we rely on the specific list pages using this function to have checked permissions??
    // NO, the point is centralization.
    
    // Best effort mapping from common aliases used in the project:
    $module = 'settings'; // Default
    if($table_alias == 's' || $table_alias == 'sell' || $table_alias == 'si' || $table_alias == 'sl') $module = 'sell';
    if($table_alias == 'p' || $table_alias == 'purchase' || $table_alias == 'pi' || $table_alias == 'pl' || $table_alias == 'pin') $module = 'purchase';
    if($table_alias == 'e' || $table_alias == 'expenses') $module = 'expenditure';
    if($table_alias == 'r' || $table_alias == 'return') $module = 'sell'; // Returns usually part of sell module
    
    // Check Permissions
    $module_perm = 'view_all_stores_data_' . $module;
    if(hasPermission($module_perm) || hasPermission('view_all_stores_data_settings')) {
        return ''; // No filter - show all stores
    }
    
    // Filter by current store
    $current_store_id = $_SESSION['store_id'] ?? 0;
    
    if(empty($current_store_id)) {
        return '';
    }
    
    $prefix = !empty($table_alias) ? $table_alias . '.' : '';
    return " AND {$prefix}store_id = '{$current_store_id}'";
}

/**
 * Check if user has a specific permission
 * 
 * @param string $permission_slug - Permission slug to check
 * @return bool
 */
function hasPermission($permission_slug) {
    global $conn;
    
    if(!isset($_SESSION['user_id'])) {
        return false;
    }
    
    $user_id = (int)$_SESSION['user_id'];
    
    // Optimization: Check session first if permissions are cached
    // But for now, direct DB to ensure freshness during development
    
    $query = "SELECT ug.permission 
              FROM users u 
              LEFT JOIN user_groups ug ON u.group_id = ug.id 
              WHERE u.id = '$user_id' LIMIT 1";
    
    $result = mysqli_query($conn, $query);
    
    if($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $permissions = unserialize($row['permission'] ?? '') ?: [];
        $access = $permissions['access'] ?? [];
        
        return isset($access[$permission_slug]) && $access[$permission_slug] === 'true';
    }
    
    return false;
}

/**
 * Check if current user can view all stores data
 * Convenience function for the most common permission check
 * 
 * @return bool
 */
function canViewAllStores() {
    return hasPermission('view_all_stores_data_settings');
}
?>
