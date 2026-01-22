<footer class="bg-white border-t border-slate-200 mt-auto shadow-inner">
    <div class="px-6 py-4">
        <div class="flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="text-slate-500 text-sm">
                &copy; <?php echo date("Y"); ?>, Developed <span class="text-red-500"> ❤️ </span> by STS
            </div>
            <div class="flex items-center gap-6 text-sm">
                <a href="#" class="text-slate-600 hover:text-slate-800 transition-colors">License</a>
                <a href="#" class="text-slate-600 hover:text-slate-800 transition-colors">More Projects</a>
                <a href="#" class="text-slate-600 hover:text-slate-800 transition-colors">Documentation</a>
                <a href="#" class="text-slate-600 hover:text-slate-800 transition-colors">Support</a>
            </div>
        </div>
    </div>
</footer>

<?php if(isset($_SESSION['message'])): 
    $msgType = isset($_SESSION['msg_type']) ? $_SESSION['msg_type'] : "error";
    
    if ($msgType == "success") {
        $swalIcon = "success";
        $swalTitle = "Success!";
        $bgColor = "#064e3b"; 
    } else {
        $swalIcon = "error";
        $swalTitle = "Notice";
        $bgColor = "#1e293b"; 
    }
    
    // Security Fix: XSS Protection using json_encode for the message
    $safeMessage = json_encode($_SESSION['message']);
?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> 
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const msgType = '<?= $msgType; ?>';
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });

        Toast.fire({
            icon: '<?= $swalIcon; ?>',
            title: <?= $safeMessage; ?>,
            background: msgType === 'success' ? '#059669' : '#1e293b', // Green for success, Slate for error/notice
            color: '#fff',
            iconColor: '#fff',
            customClass: {
                popup: 'rounded-2xl shadow-2xl px-5 py-2'
            }
        });
    });
</script>
<?php 
    unset($_SESSION['message']); 
    unset($_SESSION['msg_type']); 
endif; ?>
  
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
<style>
    /* --- INVISIBLE HORIZONTAL SCROLLBAR --- */
    .dataTables_wrapper .overflow-x-auto, 
    .data-table-container {
        overflow-x: auto;
        scrollbar-width: none; /* Firefox */
        -ms-overflow-style: none; /* IE/Edge */
    }
    .dataTables_wrapper .overflow-x-auto::-webkit-scrollbar,
    .data-table-container::-webkit-scrollbar {
        display: none; /* Chrome, Safari, Opera */
    }

    /* --- UNIQUE SEARCH FIELD DESIGN --- */
    .dataTables_filter {
        position: relative;
    }
    .unique-search-field {
        width: 500px !important;
        height: 48px !important;
        padding-left: 48px !important;
        padding-right: 20px !important;
        background: #f8fafc !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 14px !important;
        font-size: 14px !important;
        font-weight: 600 !important;
        color: #1e293b !important;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02) !important;
        transition: all 0.3s ease !important;
        outline: none !important;
    }
    .unique-search-field:focus {
        border-color: #0d9488 !important;
        box-shadow: 0 4px 12px rgba(13, 148, 136, 0.1) !important;
        background: #ffffff !important;
        width: 650px !important;
    }
    .search-icon-inside {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 16px;
        transition: all 0.3s ease;
        z-index: 10;
        pointer-events: none;
    }
    .unique-search-field:focus + .search-icon-inside {
        color: #0d9488;
    }

    /* --- UNIQUE SHOW ENTRIES DESIGN --- */
    .dataTables_length {
        display: flex;
        align-items: center;
        background: #f8fafc;
        padding: 6px 14px;
        border-radius: 14px;
        border: 1px solid #e2e8f0;
    }
    .dataTables_length select {
        appearance: none;
        background: transparent !important;
        border: none !important;
        color: #0d9488 !important;
        font-weight: 800 !important;
        font-size: 14px !important;
        padding: 0 8px !important;
        cursor: pointer;
        outline: none !important;
    }

    /* --- PAGINATION & TABLE DESIGN --- */
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        border: 1px solid #e2e8f0 !important;
        border-radius: 10px !important;
        padding: 6px 14px !important;
        margin-left: 5px !important;
        background: white !important;
        color: #475569 !important;
        font-weight: 700 !important;
        transition: all 0.3s ease !important; /* স্মুথ ট্রানজিশন */
        cursor: pointer !important;
    }

     .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background: #0d9488 !important;
        color: white !important;
        border-color: #0d9488 !important;
    }

     .dataTables_wrapper .dataTables_paginate .paginate_button:hover:not(.disabled) {
        background: #0d9488 !important;
        color: white !important;
        border-color: #0d9488 !important;
        box-shadow: 0 4px 12px rgba(13, 148, 136, 0.2) !important; /* হালকা শ্যাডো ইফেক্ট */
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.disabled {
        cursor: default !important;
        opacity: 0.5;
    }
</style>

<script>
    $(document).ready(function() {
        if ($('.data-table').length) {
            $('.data-table').DataTable({
                pageLength: 10,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
                buttons: [
                    { extend: 'print', className: 'dt-print', exportOptions: { columns: ':visible' } },
                    { extend: 'csv', className: 'dt-csv', exportOptions: { columns: ':visible' } },
                    { extend: 'excel', className: 'dt-excel', exportOptions: { columns: ':visible' } },
                    { extend: 'pdf', className: 'dt-pdf', exportOptions: { columns: ':visible' } },
                    { extend: 'copy', className: 'dt-copy', exportOptions: { columns: ':visible' } }
                ],
                language: {
                    search: "",
                    searchPlaceholder: "Search records...",
                    lengthMenu: "<span class='text-xs font-black text-slate-400 uppercase tracking-widest mr-1'>Show</span> _MENU_ <span class='text-xs font-black text-slate-400 uppercase tracking-widest ml-1'>Entries</span>",
                    paginate: {
                        next: '<i class="fas fa-chevron-right text-xs"></i>',
                        previous: '<i class="fas fa-chevron-left text-xs"></i>'
                    }
                },
                // Updated layout for perfect alignment
                dom: '<"flex flex-col md:flex-row justify-between items-center gap-4 mb-6"<"flex items-center"l><"relative w-full md:max-w-md"f>>rt<"flex flex-col md:flex-row justify-between items-center mt-6 gap-4"ip>',
                order: [[1, 'desc']],
                responsive: true,
                autoWidth: false,
                initComplete: function() {
                    // Cleanup default DataTables text and setup unique search
                    const searchWrapper = $('.dataTables_filter');
                    const searchInput = searchWrapper.find('input');
                    
                    searchWrapper.contents().filter(function() {
                        return this.nodeType === 3; 
                    }).remove();
                    
                    searchInput.unwrap().addClass('unique-search-field');
                    // Removed duplicate icon - using CSS background-image instead
                    // searchInput.before('<i class="fas fa-search search-icon-inside"></i>');

                    // Apply horizontal scroll container class
                    $('.data-table').wrap('<div class="data-table-container"></div>');
                }
            });
        }
        // $('#selectAll').on('click', function() {
        // $('.row-checkbox').prop('checked', $(this).prop('checked'));
    });
    function toggleExportDropdown() {
        const dropdown = document.getElementById('exportDropdown');
        dropdown.classList.toggle('hidden');
        
        const closeDropdown = (e) => {
            if (!e.target.closest('#exportDropdown') && !e.target.closest('button[onclick="toggleExportDropdown()"]')) {
                dropdown.classList.add('hidden');
                document.removeEventListener('click', closeDropdown);
            }
        };
        
        if (!dropdown.classList.contains('hidden')) {
            setTimeout(() => {
                document.addEventListener('click', closeDropdown);
            }, 0);
        }
    }
    // Trigger DataTable Action from Custom Dropdown Menu
    function triggerDtAction(action) {
        const table = $('.data-table').DataTable(); 
        
        if(action === 'print') table.button('.buttons-print').trigger();
        else if(action === 'csv') table.button('.buttons-csv').trigger();
        else if(action === 'excel') table.button('.buttons-excel').trigger();
        else if(action === 'pdf') table.button('.buttons-pdf').trigger();
        else if(action === 'copy') table.button('.buttons-copy').trigger();
        
        $('#exportDropdown').addClass('hidden'); 
    }
    function openSettings() {
        Swal.fire({
            title: 'Settings',
            html: '<p class="text-gray-600">Settings panel coming soon...</p>',
            icon: 'info',
            confirmButtonColor: '#0d9488'
        });
    }
    
    function confirmDelete(id, name, deleteUrl) {
        Swal.fire({
            title: 'Are you sure?',
            text: `You want to delete "${name}"? This action cannot be undone!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444', 
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel',
            background: '#ffffff',
            color: '#1e293b',
            customClass: {
                popup: 'border border-slate-200'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = deleteUrl;
                const inputId = document.createElement('input');
                inputId.type = 'hidden'; inputId.name = 'delete_id'; inputId.value = id;
                form.appendChild(inputId);
                const inputBtn = document.createElement('input');
                inputBtn.type = 'hidden'; inputBtn.name = 'delete_btn'; inputBtn.value = '1';
                form.appendChild(inputBtn);
                document.body.appendChild(form);
                form.submit();
            }
        });
    }
    
    function toggleStatus(id, currentStatus, updateUrl) {
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
            color: '#1e293b',
            customClass: {
                popup: 'border border-slate-200'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = updateUrl;
                const inputId = document.createElement('input');
                inputId.type = 'hidden'; inputId.name = 'item_id'; inputId.value = id;
                form.appendChild(inputId);
                const inputStatus = document.createElement('input');
                inputStatus.type = 'hidden'; inputStatus.name = 'status'; inputStatus.value = newStatus;
                form.appendChild(inputStatus);
                const inputBtn = document.createElement('input');
                inputBtn.type = 'hidden'; inputBtn.name = 'toggle_status_btn'; inputBtn.value = '1';
                form.appendChild(inputBtn);
                document.body.appendChild(form);
                form.submit();
            }
        });
    }
</script>
</body>
</html>
