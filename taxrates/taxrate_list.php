<?php
session_start();
include('../config/dbcon.php');

if(!isset($_SESSION['auth'])){
    header("Location: /pos/signin.php");
    exit(0);
}

$query = "SELECT * FROM taxrates ORDER BY sort_order ASC, id DESC";
$query_run = mysqli_query($conn, $query);
$items = [];
while($row = mysqli_fetch_assoc($query_run)) { $items[] = $row; }

$list_config = [
    'title' => 'Taxrate List',
    'add_url' => '/pos/taxrates/add',
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
    'edit_url' => '/pos/taxrates/edit',
    'delete_url' => '/pos/taxrates/save_taxrate.php',
    'status_url' => '/pos/taxrates/save_taxrate.php',
    'primary_key' => 'id',
    'name_field' => 'name'
];

$page_title = "Taxrate List - POS";
include('../includes/header.php');
?>

<div class="flex">
    <?php include('../includes/sidebar.php'); ?>
    
    <main id="main-content" class="flex-1 ml-64 main-content min-h-screen">
        <?php include('../includes/navbar.php'); ?>
        
        <div class="p-12">
            <?php include('../includes/reusable_list.php'); ?>
            <?php renderReusableList($list_config); ?>
        </div>
        
        <?php include('../includes/footer.php'); ?>
    </main>
</div>

