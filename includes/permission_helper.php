<?php
if(!function_exists('check_user_permission')) {
    /**
     * Check if the authenticated user has a specific permission.
     * 
     * @param string $slug The permission slug to check (e.g., 'view_stock_alert_widget_dashboard')
     * @return boolean True if allowed, False otherwise
     */
    function check_user_permission($slug) {
        // If not logged in, deny
        if(!isset($_SESSION['auth_user'])) return false;
        
        // Admin Bypass: Admins have full access
        // We check if the role is 'admin'
        if(isset($_SESSION['auth_user']['role_as']) && $_SESSION['auth_user']['role_as'] === 'admin') {
            return true;
        }

        // Check Permissions array in Session
        // The array keys are the slugs, values are "true" string or boolean
        $perms = $_SESSION['auth_user']['permissions'] ?? [];
        
        // Check if slug exists and is truthy
        if(isset($perms[$slug])) {
            return true; // The existence of the key implies permission granted (value is usually "true")
        }
        
        return false;
    }
}
?>
