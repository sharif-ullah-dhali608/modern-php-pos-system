<?php
session_start();
include('../config/dbcon.php');
include('../includes/store_filter_helper.php'); // Store filtering helper
include('../includes/permission_helper.php');

// Security Check
if(!isset($_SESSION['auth'])){
    header("Location: /pos/signin.php");
    exit(0);
}

// Fetch boxes with active store count
// boxes টেবিল থেকে ডেটা এবং box_stores থেকে রিলেশন কাউন্ট আনা হচ্ছে

// Get store filter (JOIN + WHERE)
$store_filter = getStoreFilterWithJoin('boxes', 'b');
$store_join = $store_filter['join'];
$store_where = $store_filter['where'];

$query = "SELECT b.*, 
          (SELECT COUNT(*) FROM box_stores WHERE box_id = b.id) as store_count
          FROM boxes b
          {$store_join}
          WHERE 1=1 {$store_where}
          ORDER BY b.sort_order ASC, b.id DESC";
$query_run = mysqli_query($conn, $query);

$boxes = [];
while($row = mysqli_fetch_assoc($query_run)) {
    $boxes[] = $row;
}

// Prepare data for reusable list component
// Determine Action URLs based on Permissions
$add_url = check_user_permission('create_box_box') ? '/pos/boxes/add' : '#';
$edit_url = check_user_permission('update_box_box') ? '/pos/boxes/edit' : '#';
$delete_url = check_user_permission('delete_box_box') ? '/pos/boxes/delete' : '#';
$status_url = check_user_permission('update_box_box') ? '/pos/boxes/save' : '#';

// Prepare data for reusable list component
$list_config = [
    'title' => 'Storage Box List',
    'add_url' => $add_url,
    'table_id' => 'boxTable',
    'columns' => [
        ['key' => 'id', 'label' => 'ID', 'sortable' => true],
        ['key' => 'box_name', 'label' => 'Box Name', 'sortable' => true],
        ['key' => 'code_name', 'label' => 'Code', 'sortable' => true],
        ['key' => 'barcode_id', 'label' => 'Barcode', 'sortable' => true],
        ['key' => 'shelf_number', 'label' => 'Shelf No.', 'sortable' => true],
        ['key' => 'storage_type', 'label' => 'Type', 'sortable' => true],
        ['key' => 'max_capacity', 'label' => 'Max Cap.', 'sortable' => true],
        ['key' => 'store_count', 'label' => 'Branches', 'type' => 'badge', 'badge_class' => 'bg-teal-500/20 text-teal-600'],
        ['key' => 'status', 'label' => 'Status', 'type' => 'status'],
        ['key' => 'actions', 'label' => 'Actions', 'type' => 'actions']
    ],
    'data' => $boxes,
    'edit_url' => $edit_url,
    'delete_url' => $delete_url,
    'status_url' => $status_url,
    'primary_key' => 'id',
    'name_field' => 'box_name'
];

$page_title = "Box List - Velocity POS";
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
                include('../includes/reusable_list.php'); 
                renderReusableList($list_config); 
                ?>
            </div>
        </div>
        <?php include('../includes/footer.php'); ?>
    </main>
</div>