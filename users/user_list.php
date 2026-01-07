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
        // User Info with Image
        $img_src = !empty($row['user_image']) ? $row['user_image'] : 'https://ui-avatars.com/api/?name='.urlencode($row['name']).'&background=random&size=40';
        $row['user_info'] = '<div class="flex items-center gap-3">
                                <img src="'.$img_src.'" class="w-10 h-10 rounded-lg object-cover shadow-sm" onerror="this.src=\'https://ui-avatars.com/api/?name='.urlencode($row['name']).'&background=random&size=40\'">
                                <div><p class="font-bold text-slate-700">'.$row['name'].'</p><p class="text-[11px] text-slate-400">'.$row['email'].'</p></div>
                             </div>';
        
        // Group Badge
        $row['group_badge'] = '<span class="px-3 py-1 rounded-full bg-indigo-50 text-indigo-600 text-[10px] font-black uppercase tracking-wider">'.$row['group_name'].'</span>';
        
        // Joined Date Column
        $row['joined_date'] = '<div class="flex flex-col"><span class="text-xs font-bold text-slate-600">'.date('d M, Y', strtotime($row['created_at'])).'</span><span class="text-[10px] text-slate-400">'.date('h:i A', strtotime($row['created_at'])).'</span></div>';

        // Custom Actions (Eye icon added)
        $row['custom_actions'] = '<div class="flex items-center gap-2">
                                <button onclick="viewUser('.$row['id'].')" class="w-8 h-8 inline-flex items-center justify-center text-indigo-500 bg-indigo-50 rounded-lg hover:bg-indigo-600 hover:text-white transition-all" title="Quick View">
                                    <i class="fas fa-eye text-xs"></i>
                                </button>
                                <a href="/pos/users/edit/'.$row['id'].'" class="w-8 h-8 inline-flex items-center justify-center text-teal-600 bg-teal-50 rounded-lg hover:bg-teal-600 hover:text-white transition-all" title="Edit">
                                    <i class="fas fa-edit text-xs"></i>
                                </a>
                                <button type="button" onclick="confirmDelete('.$row['id'].', \''.addslashes($row['name']).'\', \'/pos/users/save\')" 
                                        class="w-8 h-8 inline-flex items-center justify-center text-red-600 bg-red-50 rounded-lg hover:bg-red-600 hover:text-white transition-all" title="Delete">
                                    <i class="fas fa-trash-alt text-xs"></i>
                                </button>
                           </div>';

        $items[] = $row;
    }
}

$list_config = [
    'title' => 'User Management',
    'add_url' => '/pos/users/add',
    'table_id' => 'userTable',
    'columns' => [
        ['key' => 'id', 'label' => 'ID', 'sortable' => true],
        ['key' => 'user_info', 'label' => 'User Information', 'type' => 'html'],
        ['key' => 'group_badge', 'label' => 'Role/Group', 'type' => 'html'],
        ['key' => 'phone', 'label' => 'Contact'],
        ['key' => 'joined_date', 'label' => 'Joined', 'type' => 'html'],
        ['key' => 'status', 'label' => 'Status', 'type' => 'status'],
        ['key' => 'custom_actions', 'label' => 'Actions', 'type' => 'html']
    ],
    'data' => $items,
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

<!-- View User Modal -->
<div id="viewUserModal" class="fixed inset-0 z-[9999] hidden overflow-y-auto overflow-x-hidden">
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity duration-300"></div>
    
    <!-- Modal Container -->
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative w-full max-w-2xl transform overflow-hidden rounded-3xl bg-white shadow-2xl transition-all duration-300 scale-95 opacity-0 modal-content border border-slate-100">
            
            <!-- Header -->
            <div class="flex items-center justify-between bg-indigo-600 px-8 py-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center text-white shadow-inner">
                        <i class="fas fa-user-shield text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white uppercase tracking-wider">User Account Details</h3>
                        <p class="text-indigo-100 text-[10px] font-black uppercase opacity-80">Full Profile & Access Overview</p>
                    </div>
                </div>
                <button onclick="closeViewModal()" class="w-10 h-10 rounded-xl bg-white/10 text-white hover:bg-white/20 flex items-center justify-center transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Content Area (Loaded via AJAX) -->
            <div class="p-8" id="user_view_body">
                <div class="flex items-center justify-center py-12">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                </div>
            </div>

            <!-- Footer -->
            <div class="bg-slate-50 px-8 py-4 border-t border-slate-100 flex justify-end">
                <button onclick="closeViewModal()" class="px-6 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold rounded-xl text-xs uppercase tracking-widest transition-all">
                    Close Sheet
                </button>
            </div>

        </div>
    </div>
</div>

<script>
function viewUser(id) {
    const modal = document.getElementById('viewUserModal');
    const content = modal.querySelector('.modal-content');
    const body = document.getElementById('user_view_body');

    // Show modal and reset body
    modal.classList.remove('hidden');
    body.innerHTML = '<div class="flex items-center justify-center py-12"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div></div>';
    
    // Animate in
    setTimeout(() => {
        content.classList.remove('scale-95', 'opacity-0');
        content.classList.add('scale-100', 'opacity-100');
    }, 10);

    // Fetch User Details
    $.ajax({
        type: "POST",
        url: "/pos/users/fetch_user_modal.php",
        data: { user_id: id },
        success: function(response) {
            body.innerHTML = response;
        },
        error: function() {
            body.innerHTML = '<p class="text-center py-8 text-red-500">Failed to load user data.</p>';
        }
    });
}

function closeViewModal() {
    const modal = document.getElementById('viewUserModal');
    const content = modal.querySelector('.modal-content');
    
    content.classList.remove('scale-100', 'opacity-100');
    content.classList.add('scale-95', 'opacity-0');
    
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 200);
}

// Close on escape key
document.addEventListener('keydown', (e) => {
    if(e.key === 'Escape') closeViewModal();
});
</script>
