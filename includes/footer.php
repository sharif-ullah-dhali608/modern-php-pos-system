<footer class="bg-white border-t border-slate-200 mt-auto shadow-inner">
        <div class="px-6 py-4">
            <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="text-slate-500 text-sm">
                    © 2025, made with <span class="text-red-500"> ❤️ </span> by POS
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
    
    <!-- <button 
        onclick="openSettings()" 
        class="fixed bottom-6 right-6 w-14 h-14 bg-gradient-to-br from-teal-500 to-indigo-600 rounded-full shadow-2xl flex items-center justify-center text-white hover:scale-110 transition-transform z-50"
        title="Settings"
    >
        <i class="fas fa-cog text-xl"></i>
    </button> -->
    <?php if(isset($_SESSION['message'])): ?>
    <script>
        Swal.fire({
            icon: '<?= isset($_SESSION['msg_type']) && $_SESSION['msg_type'] == "success" ? "success" : "error"; ?>',
            title: '<?= isset($_SESSION['msg_type']) && $_SESSION['msg_type'] == "success" ? "Success!" : "Notice"; ?>',
            text: '<?= addslashes($_SESSION['message']); ?>',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            background: '#ffffff', 
            color: '#1e293b', 
            customClass: {
                popup: 'border border-slate-200' 
            }
        });
    </script>
    <?php 
    unset($_SESSION['message']); 
    unset($_SESSION['msg_type']); 
    endif; ?>
    
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    
    <style>
        /* DataTables Pagination Buttons Styling */
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            border: 1px solid #e2e8f0 !important;
            border-radius: 8px !important;
            padding: 5px 12px !important;
            margin-left: 4px !important;
            background: white !important;
            color: #475569 !important;
            font-weight: 600 !important;
            transition: all 0.2s ease !important;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover:not(.disabled) {
            background: #f0fdfa !important; /* Teal-50 */
            color: #0d9488 !important; /* Teal-600 */
            border-color: #99f6e4 !important;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button.current, 
        .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
            background: linear-gradient(135deg, #134e4a 0%, #064e3b 100%) !important; /* Your Theme Gradient */
            color: white !important;
            border-color: transparent !important;
            shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1) !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled {
            color: #cbd5e1 !important;
            cursor: not-allowed !important;
            opacity: 0.6 !important;
        }
    </style>

    <script>
        $(document).ready(function() {
            if ($('.data-table').length) {
                $('.data-table').DataTable({
                    pageLength: 10,
                    "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
                    language: {
                        search: "",
                        searchPlaceholder: "Search records...",
                        lengthMenu: "Show _MENU_ entries",
                        info: "Showing _START_ to _END_ of _TOTAL_ entries",
                        paginate: {
                            first: "First",
                            last: "Last",
                            next: "Next",
                            previous: "Previous"
                        }
                    },
                    dom: '<"flex justify-between items-center mb-4"<"flex items-center gap-4"l><"flex items-center gap-2"f>>rt<"flex justify-between items-center mt-4"<"text-sm text-slate-500"i><"flex gap-2"p>>',
                    order: [[0, 'desc']],
                    responsive: true,
                    autoWidth: false
                });
                
                const dt_styles = 'bg-white border-slate-300 text-slate-700 placeholder-slate-400 focus:ring-teal-500 rounded-lg py-2 px-4 outline-none border transition-all';
                
                $('.dataTables_wrapper .dataTables_filter input').addClass(dt_styles);
                $('.dataTables_wrapper select').addClass(dt_styles);
                $('.dataTables_wrapper .dataTables_length').find('label').addClass('text-slate-600');
                $('.dataTables_wrapper .dataTables_info').addClass('text-slate-600 font-medium');
            }
        });
        
        function openSettings() {
            Swal.fire({
                title: 'Settings',
                html: '<p class="text-gray-600">Settings panel coming soon...</p>',
                icon: 'info',
                confirmButtonColor: '#0d9488' // Teal-600
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
                confirmButtonColor: '#0d9488', // Matching Teal Color
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