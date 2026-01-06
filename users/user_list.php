<?php
session_start();
include('../config/dbcon.php');

if(!isset($_SESSION['auth'])){
    header("Location: /pos/login");
    exit(0);
}

$query = "SELECT u.*, g.name as group_name FROM users u LEFT JOIN user_groups g ON u.group_id = g.id ORDER BY u.id DESC";
$query_run = mysqli_query($conn, $query);
$items = [];

if($query_run) {
    while($row = mysqli_fetch_assoc($query_run)) {
        $row['user_info'] = '<div class="flex items-center gap-3">
                                <img src="'.(!empty($row['user_image']) ? $row['user_image'] : '/pos/assets/img/user-placeholder.png').'" class="w-10 h-10 rounded-lg object-cover">
                                <div><p class="font-bold text-slate-700">'.$row['username'].'</p><p class="text-[11px] text-slate-400">'.$row['email'].'</p></div>
                             </div>';
        $row['group_badge'] = '<span class="px-3 py-1 rounded-full bg-indigo-50 text-indigo-600 text-xs font-black uppercase">'.$row['group_name'].'</span>';
        $items[] = $row;
    }
}

$list_config = [
    'title' => 'User Management',
    'add_url' => '/pos/users/add',
    'table_id' => 'userTable',
    'columns' => [
        ['key' => 'id', 'label' => 'ID', 'sortable' => true],
        ['key' => 'user_info', 'label' => 'User Info'],
        ['key' => 'group_badge', 'label' => 'Group/Role'],
        ['key' => 'mobile', 'label' => 'Contact'],
        ['key' => 'status', 'label' => 'Status', 'type' => 'status'],
        ['key' => 'actions', 'label' => 'Actions', 'type' => 'actions']
    ],
    'data' => $items,
    'edit_url' => '/pos/users/edit',
    'delete_url' => '/pos/users/save',
    'status_url' => '/pos/users/save',
    'primary_key' => 'id'
];

$page_title = "User List - Velocity POS";
include('../includes/header.php');
?>
<div class="app-wrapper">
    <?php include('../includes/sidebar.php'); ?>
    <main id="main-content" class="flex-1 lg:ml-64 flex flex-col h-screen min-w-0 transition-all duration-300">
        <?php include('../includes/navbar.php'); ?>
        <div class="content-scroll-area custom-scroll h-full overflow-y-auto p-6">
            <?php include('../includes/reusable_list.php'); renderReusableList($list_config); ?>
            <?php include('../includes/footer.php'); ?>
        </div>
    </main>
</div>