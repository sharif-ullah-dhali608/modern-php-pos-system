<?php
session_start();
include('../config/dbcon.php');

if(!isset($_SESSION['auth'])){
    header("Location: /pos/signin.php");
    exit(0);
}

$query = "SELECT * FROM units ORDER BY sort_order ASC, id DESC";
$query_run = mysqli_query($conn, $query);
$items = [];
while($row = mysqli_fetch_assoc($query_run)) { $items[] = $row; }

$list_config = [
    'title' => 'Unit List',
    'add_url' => '/pos/units/add_unit.php',
    'table_id' => 'unitTable',
    'columns' => [
        ['key' => 'id', 'label' => 'ID', 'sortable' => true],
        ['key' => 'unit_name', 'label' => 'Unit Name', 'sortable' => true],
        ['key' => 'code', 'label' => 'Code Name', 'sortable' => true],
        ['key' => 'details', 'label' => 'Details', 'sortable' => false],
        ['key' => 'status', 'label' => 'Status', 'type' => 'status'],
        ['key' => 'actions', 'label' => 'Actions', 'type' => 'actions']
    ],
    'data' => $items,
    'edit_url' => '/pos/units/add_unit.php',
    'delete_url' => '/pos/units/save_unit.php',
    'status_url' => '/pos/units/save_unit.php',
    'primary_key' => 'id',
    'name_field' => 'unit_name'
];

$page_title = "Unit List - POS";
include('../includes/header.php');
?>

<div class="flex">
    <?php include('../includes/sidebar.php'); ?>
    
    <main id="main-content" class="flex-1 ml-64 main-content min-h-screen">
        <?php include('../includes/navbar.php'); ?>
        
        <div class="p-6">
            <?php include('../includes/reusable_list.php'); ?>
            <?php renderReusableList($list_config); ?>
        </div>
        
        <?php include('../includes/footer.php'); ?>
    </main>
</div>

