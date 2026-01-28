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

<div class="app-wrapper flex flex-col h-screen overflow-hidden">
    <?php include('../includes/sidebar.php'); ?>
    <?php include('../includes/navbar.php'); ?>
    
    <main id="main-content" class="lg:ml-64 flex-1 flex flex-col h-screen overflow-hidden transition-all duration-300">
        <div class="flex-1 overflow-y-auto p-4 md:p-8 custom-scroll">
            <div class="max-w-7xl mx-auto">
                <?php renderReusableList($config); ?>
            </div>
        </div>
        <?php include('../includes/footer.php'); ?>
    </main>
</div>
