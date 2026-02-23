<?php
session_start();
include('../config/dbcon.php');
include('../includes/store_filter_helper.php'); // Store filtering helper
include('../includes/permission_helper.php');

if(!isset($_SESSION['auth'])){
    header("Location: /pos/login");
    exit(0);
}

// Get store filter (JOIN + WHERE)
$store_filter = getStoreFilterWithJoin('taxrates', 't');
$store_join = $store_filter['join'];
$store_where = $store_filter['where'];

$query = "SELECT t.* FROM taxrates t {$store_join} WHERE 1=1 {$store_where} ORDER BY t.sort_order ASC, t.id DESC";
$query_run = mysqli_query($conn, $query);
$items = [];
while($row = mysqli_fetch_assoc($query_run)) { $items[] = $row; }

// Determine Action URLs based on Permissions
$add_url = check_user_permission('create_taxrate_taxrate') ? '/pos/taxrates/add' : '#';
$edit_url = check_user_permission('update_taxrate_taxrate') ? '/pos/taxrates/edit' : '#';
$delete_url = check_user_permission('delete_taxrate_taxrate') ? '/pos/taxrates/delete' : '#';
$status_url = check_user_permission('update_taxrate_taxrate') ? '/pos/taxrates/save' : '#';

$list_config = [
    'title' => 'Taxrate List',
    'add_url' => $add_url,
    'table_id' => 'taxrateTable',
    'columns' => [
        ['key' => 'id', 'label' => 'ID', 'sortable' => true],
        ['key' => 'name', 'label' => 'Taxrate Name', 'sortable' => true],
        ['key' => 'code', 'label' => 'Code Name', 'sortable' => true],
        ['key' => 'taxrate', 'label' => 'Taxrate %', 'sortable' => true],
        ['key' => 'status', 'label' => 'Status', 'type' => 'status'],
        ['key' => 'actions', 'label' => 'Actions', 'type' => 'actions']
    ],
    'data' => $items,
    'edit_url' => $edit_url,
    'delete_url' => $delete_url,
    'status_url' => $status_url,
    'primary_key' => 'id',
    'name_field' => 'name'
];

$page_title = "Taxrate List - POS";
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

