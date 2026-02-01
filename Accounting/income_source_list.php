<?php
session_start();
include('../config/dbcon.php');

// Security Check
if(!isset($_SESSION['auth'])){
    header("Location: /pos/login");
    exit(0);
}

// Fetch Income Sources
$query = "SELECT * FROM income_sources ORDER BY sort_order ASC, source_id DESC";
$query_run = mysqli_query($conn, $query);

$items = [];

if($query_run) {
    while($row = mysqli_fetch_assoc($query_run)) {
        // Format Created At
        $row['created_at_formatted'] = date('Y-m-d H:i:s', strtotime($row['created_at']));
        //$row['total'] = 0.00; // Placeholder for now as per screenshot '0'
        
        $items[] = $row;
    }
}

$list_config = [
    'title' => 'Income Source List',
    'add_url' => '/pos/accounting/income-source/add',
    'table_id' => 'incomeSourceTable',
    'columns' => [
        ['key' => 'source_id', 'label' => 'Id', 'sortable' => true],
        ['key' => 'source_name', 'label' => 'Source Name', 'sortable' => true],
        // ['key' => 'total', 'label' => 'Total', 'sortable' => true], // Optional, not in DB yet
        ['key' => 'sort_order', 'label' => 'Order', 'sortable' => true],
        ['key' => 'status', 'label' => 'Status', 'type' => 'status'],
        ['key' => 'created_at_formatted', 'label' => 'Created At', 'sortable' => true],
        ['key' => 'actions', 'label' => 'Actions', 'type' => 'actions']
    ],
    'data' => $items,
    'edit_url' => '/pos/accounting/income-source/edit',
    'delete_url' => '/pos/Accounting/income_source_delete.php',
    'delete_url' => '/pos/Accounting/income_source_delete.php',
    'status_url' => '/pos/Accounting/income_source_status.php', 
    'primary_key' => 'source_id',
    'name_field' => 'source_name'
];

$page_title = "Income Source List - Velocity POS";
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
                
                <script>
                // Override toggleStatus for AJAX functionality on this page
                function customToggleStatus(id, currentStatus, updateUrl) {
                    const newStatus = currentStatus == 1 ? 0 : 1;
                    const statusText = newStatus == 1 ? 'activate' : 'deactivate';
                    
                    Swal.fire({
                        title: `Are you sure?`,
                        text: `You want to ${statusText} this item?`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#0d9488',
                        cancelButtonColor: '#6b7280',
                        confirmButtonText: `Yes, ${statusText} it!`,
                        cancelButtonText: 'Cancel',
                        background: '#ffffff',
                        customClass: {
                            popup: 'border border-slate-200 rounded-2xl'
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                             const formData = new FormData();
                             formData.append('item_id', id);
                             formData.append('status', newStatus);
                             formData.append('toggle_status_btn', '1');

                             fetch(updateUrl, {
                                 method: 'POST',
                                 body: formData
                             })
                             .then(response => response.json())
                             .then(data => {
                                 if(data.status === 'success') {
                                     const Toast = Swal.mixin({
                                        toast: true,
                                        position: 'top-end',
                                        showConfirmButton: false,
                                        timer: 1500,
                                        timerProgressBar: true
                                     });
                                     
                                     Toast.fire({
                                        icon: 'success',
                                        title: data.message
                                     }).then(() => {
                                         // Refresh page to show new status without leaving the page
                                         location.reload(); 
                                     });
                                 } else {
                                     Swal.fire('Error', data.message, 'error');
                                 }
                             })
                             .catch(error => {
                                 console.error('Error:', error);
                                 Swal.fire('Error', 'Something went wrong!', 'error');
                             });
                        }
                    });
                }
                </script>
            </div>
            
            <?php include('../includes/footer.php'); ?>
        </div>
    </main>
</div>
