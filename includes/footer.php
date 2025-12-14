<footer class="bg-white border-t border-slate-200 mt-auto shadow-inner">
        <div class="px-6 py-4">
            <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="text-slate-500 text-sm">
                    © 2025, made with <span class="text-red-500">❤️</span> by POS
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
    
    <button 
        onclick="openSettings()" 
        class="fixed bottom-6 right-6 w-14 h-14 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-full shadow-2xl flex items-center justify-center text-white hover:scale-110 transition-transform z-50"
        title="Settings"
    >
        <i class="fas fa-cog text-xl"></i>
    </button>
    
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
            /* Changed SweetAlert background/color to light mode */
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
    endif; 
    ?>
    
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    
    <script>
        $(document).ready(function() {
            if ($('.data-table').length) {
                $('.data-table').DataTable({
                    pageLength: 25,
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
                    // Dom structure remains the same
                    dom: '<"flex justify-between items-center mb-4"<"flex items-center gap-4"l><"flex items-center gap-2"f>>rt<"flex justify-between items-center mt-4"<"text-sm text-slate-500"i><"flex gap-2"p>>',
                    order: [[0, 'desc']],
                    responsive: true,
                    autoWidth: false
                });
                
                // --- DataTables Light Mode Styling Fixes ---
                // Apply light mode text/background styles to DataTables elements
                const dt_styles = 'bg-white border-slate-300 text-slate-700 placeholder-slate-400 focus:ring-purple-500';
                
                $('.dataTables_wrapper .dataTables_filter input').addClass(dt_styles);
                $('.dataTables_wrapper select').addClass(dt_styles);
                $('.dataTables_wrapper .dataTables_paginate .paginate_button').addClass('text-slate-600 hover:bg-slate-100');
                $('.dataTables_wrapper .dataTables_paginate .current').addClass('bg-purple-600 text-white');
                $('.dataTables_wrapper .dataTables_length').find('label').addClass('text-slate-600');
                $('.dataTables_wrapper .dataTables_info').addClass('text-slate-600');
            }
        });
        
        function openSettings() {
            Swal.fire({
                title: 'Settings',
                html: '<p class="text-gray-600">Settings panel coming soon...</p>',
                icon: 'info',
                confirmButtonColor: '#667eea'
            });
        }
        
        // Confirm Delete Function
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
                /* Changed SweetAlert background/color to light mode */
                background: '#ffffff',
                color: '#1e293b',
                customClass: {
                    popup: 'border border-slate-200'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Create form and submit
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = deleteUrl;
                    
                    const inputId = document.createElement('input');
                    inputId.type = 'hidden';
                    inputId.name = 'delete_id';
                    inputId.value = id;
                    form.appendChild(inputId);
                    
                    const inputBtn = document.createElement('input');
                    inputBtn.type = 'hidden';
                    inputBtn.name = 'delete_btn';
                    inputBtn.value = '1';
                    form.appendChild(inputBtn);
                    
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
        
        // Toggle Status Function
        function toggleStatus(id, currentStatus, updateUrl) {
            const newStatus = currentStatus == 1 ? 0 : 1;
            const statusText = newStatus == 1 ? 'activate' : 'deactivate';
            
            Swal.fire({
                title: `Are you sure?`,
                text: `You want to ${statusText} this item?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#6b7280',
                confirmButtonText: `Yes, ${statusText} it!`,
                cancelButtonText: 'Cancel',
                /* Changed SweetAlert background/color to light mode */
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
                    inputId.type = 'hidden';
                    inputId.name = 'item_id';
                    inputId.value = id;
                    form.appendChild(inputId);
                    
                    const inputStatus = document.createElement('input');
                    inputStatus.type = 'hidden';
                    inputStatus.name = 'status';
                    inputStatus.value = newStatus;
                    form.appendChild(inputStatus);
                    
                    const inputBtn = document.createElement('input');
                    inputBtn.type = 'hidden';
                    inputBtn.name = 'toggle_status_btn';
                    inputBtn.value = '1';
                    form.appendChild(inputBtn);
                    
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
    </script>
</body>
</html>