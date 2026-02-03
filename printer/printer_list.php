<?php
session_start();
include('../config/dbcon.php');
include('../includes/reusable_list.php');

if(!isset($_SESSION['auth'])){
    header("Location: /pos/login"); 
    exit(0);
}

// Fetch printers data
$query = "SELECT * FROM printers ORDER BY sort_order ASC, printer_id DESC";
$query_run = mysqli_query($conn, $query);

$printers_data = [];
if($query_run && mysqli_num_rows($query_run) > 0) {
    $sl = 1;
    while($row = mysqli_fetch_assoc($query_run)) {
        $row['sl'] = $sl++;
        $row['type_display'] = strtoupper($row['type']);
        $row['ip_port'] = ($row['ip_address'] ?: 'Local') . ($row['port'] ? ':'.$row['port'] : '');
        $printers_data[] = $row;
    }
}

$config = [
    'title' => 'Printer List',
    'add_url' => '/pos/printer/add',
    'edit_url' => '/pos/printer/edit',
    'delete_url' => '/pos/printer/save',
    'status_url' => '/pos/printer/save',
    'primary_key' => 'printer_id',
    'name_field' => 'title',
    'columns' => [
        ['label' => 'SL', 'key' => 'sl'],
        ['label' => 'Title', 'key' => 'title'],
        ['label' => 'Type', 'key' => 'type_display', 'type' => 'badge', 'badge_class' => 'bg-slate-100 text-slate-600'],
        ['label' => 'Connection', 'key' => 'ip_port'],
        ['label' => 'Status', 'key' => 'status', 'type' => 'status'],
        ['label' => 'Actions', 'key' => 'actions', 'type' => 'actions']
    ],
    'data' => $printers_data,
    'action_buttons' => ['edit', 'delete']
];

$page_title = "Printer List - Velocity POS";
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
                    renderReusableList($config); 
                ?>
            </div>
        </div>
        <?php include('../includes/footer.php'); ?>
    </main>
</div>
