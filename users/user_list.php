<?php
session_start();
include('../config/dbcon.php');
include('../includes/permission_helper.php');

if(!isset($_SESSION['auth'])){
    header("Location: /pos/login");
    exit(0);
}

$query = "SELECT u.*, g.name as group_name FROM users u LEFT JOIN user_groups g ON u.group_id = g.id ORDER BY u.id DESC";
$query_run = mysqli_query($conn, $query);
$items = [];

if($query_run) {
    while($row = mysqli_fetch_assoc($query_run)) {
        // User Info with Image
        $img_src = !empty($row['user_image']) ? $row['user_image'] : 'https://ui-avatars.com/api/?name='.urlencode($row['name']).'&background=random&size=40';
        $row['user_info'] = '<div class="flex items-center gap-3">
                                <img src="'.$img_src.'" class="w-10 h-10 rounded-lg object-cover shadow-sm" onerror="this.src=\'https://ui-avatars.com/api/?name='.urlencode($row['name']).'&background=random&size=40\'">
                                <div><p class="font-bold text-slate-700">'.$row['name'].'</p><p class="text-[11px] text-slate-400">'.$row['email'].'</p></div>
                             </div>';
        
        // Group Badge
        $row['group_badge'] = '<span class="px-3 py-1 rounded-full bg-indigo-50 text-indigo-600 text-[10px] font-black uppercase tracking-wider">'.$row['group_name'].'</span>';
        
        // Joined Date Column
        $row['joined_date'] = '<div class="flex flex-col"><span class="text-xs font-bold text-slate-600">'.date('d M, Y', strtotime($row['created_at'])).'</span><span class="text-[10px] text-slate-400">'.date('h:i A', strtotime($row['created_at'])).'</span></div>';

        // Check permissions for buttons
        $can_view = check_user_permission('view_user_user');
        $can_edit = check_user_permission('update_user_user');
        $can_delete = check_user_permission('delete_user_user');

        // Custom Actions (Eye icon added)
        $actions_html = '<div class="flex items-center gap-2">';
        
        if($can_view) {
             $actions_html .= '<button onclick="viewUser('.$row['id'].')" class="w-8 h-8 inline-flex items-center justify-center text-indigo-500 bg-indigo-50 rounded-lg hover:bg-indigo-600 hover:text-white transition-all" title="Quick View">
                                    <i class="fas fa-eye text-xs"></i>
                               </button>';
        }
        
        if($can_edit) {
            $actions_html .= '<a href="/pos/users/edit/'.$row['id'].'" class="w-8 h-8 inline-flex items-center justify-center text-teal-600 bg-teal-50 rounded-lg hover:bg-teal-600 hover:text-white transition-all" title="Edit">
                                    <i class="fas fa-edit text-xs"></i>
                              </a>';
        }

        if($can_delete) {
            $actions_html .= '<button type="button" onclick="confirmDelete('.$row['id'].', \''.addslashes($row['name']).'\', \'/pos/users/save\')" 
                                        class="w-8 h-8 inline-flex items-center justify-center text-red-600 bg-red-50 rounded-lg hover:bg-red-600 hover:text-white transition-all" title="Delete">
                                    <i class="fas fa-trash-alt text-xs"></i>
                              </button>';
        }

        $actions_html .= '</div>';
        $row['custom_actions'] = $actions_html;

        $items[] = $row;
    }
}

// Determine Action URLs based on Permissions
$add_url = check_user_permission('create_user_user') ? '/pos/users/add' : '#';
$delete_url = check_user_permission('delete_user_user') ? '/pos/users/save' : '#';
$status_url = check_user_permission('update_user_user') ? '/pos/users/save' : '#'; // Assuming status toggle needs update permission

$list_config = [
    'title' => 'User Management',
    'add_url' => $add_url,
    'table_id' => 'userTable',
    'columns' => [
        ['key' => 'id', 'label' => 'ID', 'sortable' => true],
        ['key' => 'user_info', 'label' => 'User Information', 'type' => 'html'],
        ['key' => 'group_badge', 'label' => 'Role/Group', 'type' => 'html'],
        ['key' => 'phone', 'label' => 'Contact'],
        ['key' => 'joined_date', 'label' => 'Joined', 'type' => 'html'],
        ['key' => 'status', 'label' => 'Status', 'type' => 'status'],
        ['key' => 'custom_actions', 'label' => 'Actions', 'type' => 'html']
    ],
    'data' => $items,
    'delete_url' => $delete_url,
    'status_url' => $status_url,
    'primary_key' => 'id'
];

$page_title = "User List - Velocity POS";
include('../includes/header.php');
?>
<div class="app-wrapper">
    <?php include('../includes/sidebar.php'); ?>
    

    <main id="main-content" class="flex-1 lg:ml-64 flex flex-col h-screen min-w-0 transition-all duration-300">
        <div class="navbar-fixed-top">
            <?php include('../includes/navbar.php'); ?>
        </div>
        
        <div class="content-scroll-area custom-scroll h-full overflow-y-auto">
            <div class="p-6">
                <?php 
                // This assumes reusable_list.php handles the table generation, 
                // search, status toggles, and delete modals dynamically.
                include('../includes/reusable_list.php'); 
                renderReusableList($list_config); 
                ?>
            </div>
            
        </div>
        <?php include('../includes/footer.php'); ?>
    </main>
</div>
