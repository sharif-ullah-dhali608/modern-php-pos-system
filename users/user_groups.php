<?php
session_start();
include('../config/dbcon.php');

if(!isset($_SESSION['auth'])){
    header("Location: /pos/login");
    exit(0);
}

$page_title = "User Groups - Velocity POS";
include('../includes/header.php');
?>

<style>
    /* Professional Pagination Styling as per your image */
    .dataTables_paginate .paginate_button {
        padding: 5px 15px !important;
        margin-left: 5px !important;
        border-radius: 8px !important;
        border: 1px solid #e2e8f0 !important;
        background: white !important;
        color: #64748b !important;
        font-weight: 600 !important;
        cursor: pointer;
    }
    .dataTables_paginate .paginate_button.current {
        background: #0d9488 !important; 
        color: white !important;
        border: none !important;
    }
    .dataTables_paginate .paginate_button:hover:not(.current) {
        background: #f8fafc !important;
    }
</style>

<div class="app-wrapper">
    <?php include('../includes/sidebar.php'); ?>
    <main id="main-content" class="flex-1 lg:ml-64 flex flex-col min-h-screen min-w-0 transition-all duration-300 bg-slate-50/50">
        <?php include('../includes/navbar.php'); ?>
        
        <div class="content-scroll-area custom-scroll h-full overflow-y-auto p-4 md:p-6">
            
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-3xl font-black text-slate-800 flex items-center gap-3">User Group Management
                    </h1>
                    <p class="text-sm text-slate-500 font-medium italic mt-1">Create and manage access roles for your system users</p>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 mb-8">
                <h3 class="text-base font-bold text-slate-700 uppercase tracking-widest mb-6 flex items-center gap-2">
                    <span class="w-8 h-8 rounded-full bg-teal-50 flex items-center justify-center">
                        <i class="fas fa-plus text-teal-600 text-xs"></i>
                    </span>
                    ADD NEW USERGROUP
                </h3>
                <form id="addUserGroupForm" class="grid grid-cols-1 md:grid-cols-2 gap-6 items-end">
                    <div>
                        <label class="block text-xs font-black text-slate-500 uppercase mb-2 ml-1">Group Name</label>
                        <input type="text" name="name" id="group_name" required 
                               class="w-full h-12 px-4 rounded-xl border border-slate-200 focus:ring-4 focus:ring-indigo-50 focus:border-indigo-400 outline-none transition-all text-sm font-semibold"
                               placeholder="e.g., Senior Salesman">
                    </div>
                    <div>
                        <label class="block text-xs font-black text-slate-500 uppercase mb-2 ml-1">Slug (Auto-generated)</label>
                        <input type="text" name="slug" id="group_slug" readonly 
                               class="w-full h-12 px-4 rounded-xl border border-slate-200 bg-slate-50 text-sm font-mono text-slate-500 cursor-not-allowed">
                    </div>
                    <div class="md:col-span-2 flex gap-3 mt-2">
                        <button type="submit" class="px-10 py-3 bg-teal-600 text-white font-bold rounded-xl shadow-lg hover:bg-teal-700 transition-all transform hover:-translate-y-0.5 flex items-center gap-2">
                            <i class="fas fa-save"></i> Save Group
                        </button>
                        <button type="reset" class="px-8 py-3 bg-slate-100 text-slate-600 font-bold rounded-xl hover:bg-slate-200 transition-all">
                            Reset
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden p-4 md:p-6 mb-8">
    
            <div class="mb-6 border-b border-slate-100 pb-4">
                <h2 class="text-xl font-black text-slate-800">User Group List</h2>
            </div>

            <div class="flex flex-col md:flex-row items-center justify-between gap-4 mb-6">
                <div class="flex items-center gap-2">
                    <span class="text-sm font-bold text-slate-500">Show</span>
                    <div id="custom_length"></div> <span class="text-sm font-bold text-slate-500">entries</span>
                </div>
                
                <div class="flex-1 w-full md:max-w-2xl relative">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" id="groupSearch" placeholder="Search groups..." 
                        class="w-full h-12 pl-11 pr-4 rounded-xl border border-slate-200 focus:ring-4 focus:ring-indigo-50 outline-none transition-all text-sm font-semibold">
                </div>
            </div>

            <div class="overflow-x-auto rounded-xl border border-slate-100">
                <table id="userGroupTable" class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-slate-200">
                            <th class="p-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">ID</th>
                            <th class="p-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Group Name</th>
                            <th class="p-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Total Users</th>
                            <th class="p-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Edit Permission</th>
                            <th class="p-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Delete</th>
                        </tr>
                    </thead>
                    <tbody id="groupListBody" class="divide-y divide-slate-100"></tbody>
                </table>
            </div>
            
            <div id="pagination_container" class="mt-8 flex flex-col md:flex-row justify-between items-center gap-4"></div>
        </div>

        </div>
            <?php include('../includes/footer.php'); ?>
    </main>
</div>

<div id="permissionModal" class="fixed inset-0 z-[9999] hidden" role="dialog">
    <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm" onclick="closePermissionModal()"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-6xl overflow-hidden border transform transition-all">
                <div class="bg-indigo-600 px-6 py-4 flex justify-between items-center text-white">
                    <h3 class="text-sm font-black uppercase tracking-widest flex items-center gap-3">
                        <i class="fas fa-user-shield"></i> Manage Permissions: <span id="modalGroupName"></span>
                    </h3>
                    <button onclick="closePermissionModal()" class="w-8 h-8 rounded-full bg-indigo-500/50 flex items-center justify-center hover:bg-red-500 transition-all">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>
                <form id="permissionForm" class="p-6 md:p-8 bg-white max-h-[85vh] overflow-y-auto custom-scroll">
                    <input type="hidden" name="group_id" id="perm_group_id">
                    <div id="permissionContainer" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6"></div>
                    <div class="mt-8 border-t pt-6 flex justify-end">
                        <button type="submit" class="px-10 py-3 bg-indigo-600 text-white font-black rounded-xl shadow-lg hover:bg-indigo-700 transition-all">
                            Update Permissions
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    /* JavaScript logic stays the same to ensure reload-free updates and pagination */
    $(document).ready(function() {
        loadGroups();
        $('#group_name').on('input', function() {
            let text = $(this).val();
            let slug = text.toLowerCase().replace(/ /g, '_').replace(/[^\w-]+/g, '');
            $('#group_slug').val(slug);
        });

        $('#addUserGroupForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: 'save_user_group.php',
                type: 'POST',
                data: $(this).serialize() + '&add_group_btn=1',
                success: function(res) {
                    let data = JSON.parse(res);
                    if(data.status == 200) {
                        Swal.fire({ icon: 'success', title: 'Success!', text: data.message, timer: 1500, showConfirmButton: false });
                        $('#addUserGroupForm')[0].reset();
                        loadGroups();
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                }
            });
        });
    });

    function loadGroups() {
        $.ajax({
            url: 'save_user_group.php',
            type: 'POST',
            data: { fetch_groups: 1 },
            success: function(res) {
            
                if ($.fn.DataTable.isDataTable('#userGroupTable')) {
                    $('#userGroupTable').DataTable().clear().destroy();
                }
                
                $('#groupListBody').html(res);

            
                var table = $('#userGroupTable').DataTable({
                    pageLength: 10, 
                    lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                
                    dom: 'lrt<"flex flex-col md:flex-row justify-between items-center gap-4 mt-4"i p>', 
                    language: { 
                        paginate: { next: 'Next', previous: 'Previous' },
                        lengthMenu: "_MENU_" 
                    },
                    initComplete: function() {
                        
                        $('#pagination_container').empty();
                        $('.dataTables_info, .dataTables_paginate').appendTo('#pagination_container');

                        
                        $('#custom_length').empty();
                        $('.dataTables_length select').appendTo('#custom_length');
                        
                        
                        $('.dataTables_length select').addClass('px-2 py-1 border border-slate-200 rounded-lg outline-none focus:ring-2 focus:ring-indigo-500');
                    }
                });

            
                $('#groupSearch').off('keyup').on('keyup', function() {
                    table.search(this.value).draw();
                });
            }
        });
    }

    function deleteGroup(id) {
        Swal.fire({
            title: 'Are you sure?',
            text: "Usergroup will be deleted permanently!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'save_user_group.php',
                    type: 'POST',
                    data: { delete_group_btn: 1, group_id: id },
                    success: function(res) {
                        loadGroups();
                        Swal.fire({ icon: 'success', title: 'Deleted!', text: 'Group has been deleted.', timer: 1500, showConfirmButton: false });
                    }
                });
            }
        });
    }

    // Modal helpers (openPermission, closePermissionModal, etc.) remain as before
    function openPermission(groupId, groupName) {
        $('#perm_group_id').val(groupId);
        $('#modalGroupName').text(groupName);
        $('#permissionModal').removeClass('hidden');
        $('#permissionContainer').html('<div class="col-span-full text-center py-10"><i class="fas fa-spinner fa-spin fa-2x text-indigo-600"></i></div>');
        $.ajax({
            url: 'save_user_group.php',
            type: 'POST',
            data: { fetch_permissions: 1, group_id: groupId },
            success: function(res) {
                $('#permissionContainer').html(res);
                $('.permission-card').each(function() {
                    let card = $(this);
                    let allChecked = card.find('.perm-check').length > 0 && card.find('.perm-check:not(:checked)').length === 0;
                    card.find('.select-all-mod').prop('checked', allChecked);
                });
            }
        });
    }

    function closePermissionModal() { $('#permissionModal').addClass('hidden'); }
    
    $(document).on('change', '.select-all-mod', function() {
        let targetId = $(this).data('target');
        $('#' + targetId).find('.perm-check').prop('checked', $(this).prop('checked'));
    });

    $(document).on('keyup', '.perm-search', function() {
        let value = $(this).val().toLowerCase();
        $(this).closest('.permission-card').find('.perm-item').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });

    $('#permissionForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: 'save_user_group.php',
            type: 'POST',
            data: $(this).serialize() + '&update_permissions_btn=1',
            success: function(res) {
                Swal.fire({ icon: 'success', title: 'Updated', text: 'Permissions Updated Successfully!', timer: 1500, showConfirmButton: false });
                closePermissionModal();
            }
        });
    });
</script>