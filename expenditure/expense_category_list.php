<?php
session_start();
include('../config/dbcon.php');
include('../includes/reusable_list.php');

if(!isset($_SESSION['auth'])){
    header("Location: /pos/login");
    exit(0);
}

include('../includes/header.php');

// Fetch categories
$query = "SELECT * FROM expense_category ORDER BY sort_order ASC, category_name ASC";
$result = mysqli_query($conn, $query);
$data = [];
while($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

$config = [
    'title' => 'Expense Categories',
    'table_id' => 'expenseCategoryTable',
    'primary_key' => 'category_id',
    'name_field' => 'category_name',
    'add_url' => '/pos/expenditure/category_add',
    'edit_url' => '/pos/expenditure/category_edit',
    'delete_url' => '/pos/expenditure/save_category', // Handles delete_btn post
    'status_url' => '/pos/expenditure/save_category', // Handles toggle_status_btn post
    'columns' => [
        ['label' => 'ID', 'key' => 'category_id'],
        ['label' => 'Category Name', 'key' => 'category_name'],
        ['label' => 'Details', 'key' => 'category_details'],
        ['label' => 'Sort', 'key' => 'sort_order'],
        ['label' => 'Status', 'key' => 'status', 'type' => 'status'],
        ['label' => 'Actions', 'key' => 'actions', 'type' => 'actions']
    ],
    'data' => $data
];
?>

<div class="app-wrapper">
    <?php include('../includes/sidebar.php'); ?>
    
    <main id="main-content" class="flex-1 lg:ml-64 flex flex-col h-screen min-w-0 transition-all duration-300">
        <div class="navbar-fixed-top"><?php include('../includes/navbar.php'); ?></div> 
        
        <div class="content-scroll-area custom-scroll h-full overflow-y-auto">
            <div class="p-6">
                <?php renderReusableList($config); ?>
            </div>
        </div>
    </main>
</div>

<?php include('../includes/footer.php'); ?>