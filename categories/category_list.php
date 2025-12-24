<?php
session_start();
include('../config/dbcon.php');

if(!isset($_SESSION['auth'])){
    header("Location: /pos/login");
    exit(0);
}

$query = "SELECT * FROM categories ORDER BY sort_order ASC, id DESC";
$query_run = mysqli_query($conn, $query);
$items = [];
while($row = mysqli_fetch_assoc($query_run)) { $items[] = $row; }

$list_config = [
    'title' => 'Category List',
    'add_url' => '/pos/categories/add',
    'table_id' => 'categoryTable',
    'columns' => [
        ['key' => 'id', 'label' => 'ID', 'sortable' => true],
        ['key' => 'name', 'label' => 'Category Name', 'sortable' => true],
        ['key' => 'category_code', 'label' => 'Code', 'sortable' => true],
        ['key' => 'slug', 'label' => 'Slug', 'sortable' => true],
        ['key' => 'status', 'label' => 'Status', 'type' => 'status'],
        ['key' => 'actions', 'label' => 'Actions', 'type' => 'actions']
    ],
    'data' => $items,
    'edit_url' => '/pos/categories/edit',
    'delete_url' => '/pos/categories/save_category.php',
    'status_url' => '/pos/categories/save_category.php',
    'primary_key' => 'id',
    'name_field' => 'name'
];

$page_title = "Category List - POS";
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
            
            <?php include('../includes/footer.php'); ?>
        </div>
    </main>
</div>

