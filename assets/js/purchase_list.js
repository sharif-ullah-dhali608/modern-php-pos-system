$(document).ready(function () {
    if ($('#purchaseTable').length) {
        $('#purchaseTable').DataTable({
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
            initComplete: function () {
                // Cleanup default DataTables text and setup unique search
                const searchWrapper = $('.dataTables_filter');
                const searchInput = searchWrapper.find('input');

                searchWrapper.contents().filter(function () {
                    return this.nodeType === 3;
                }).remove();

                searchInput.unwrap().addClass('unique-search-field');
                searchInput.before('<i class="fas fa-search search-icon-inside"></i>');

                // Apply horizontal scroll container class
                $('#purchaseTable').wrap('<div class="data-table-container"></div>');
            }
        });
    }

});
// Trigger DataTable Action from Custom Dropdown Menu
function triggerDtAction(action) {
    const table = $('#purchaseTable').DataTable();

    if (action === 'print') table.button('.buttons-print').trigger();
    else if (action === 'csv') table.button('.buttons-csv').trigger();
    else if (action === 'excel') table.button('.buttons-excel').trigger();
    else if (action === 'pdf') table.button('.buttons-pdf').trigger();
    else if (action === 'copy') table.button('.buttons-copy').trigger();

    $('#exportDropdown').addClass('hidden');
}

// Export dropdown toggle
function toggleExportDropdown() {
    $('#exportDropdown').toggleClass('hidden');
    $('#filterDropdown').addClass('hidden');
}

// Filter dropdown toggle
function toggleFilterDropdown() {
    $('#filterDropdown').toggleClass('hidden');
    $('#exportDropdown').addClass('hidden');
}

// Close dropdowns when clicking outside
$(document).on('click', function (e) {
    if (!$(e.target).closest('.relative').length) {
        $('#filterDropdown').addClass('hidden');
        $('#exportDropdown').addClass('hidden');
    }
});

// View Purchase
function viewPurchase(invoiceId) {
    $('#viewModalTitle').text('Purchase > ' + invoiceId);
    $('#viewModalContent').html('<div class="text-center py-8"><i class="fas fa-spinner fa-spin fa-2x text-teal-600"></i><p class="mt-4 text-slate-600">Loading...</p></div>');
    $('#viewModal').removeClass('hidden');

    $.ajax({
        url: '/pos/purchases/save_purchase.php',
        type: 'POST',
        data: { view_purchase_btn: true, invoice_id: invoiceId },
        dataType: 'json',
        success: function (response) {
            if (response.status == 200) {
                $('#viewModalContent').html(response.html);
            } else {
                $('#viewModalContent').html('<div class="text-center py-8 text-red-600">' + response.message + '</div>');
            }
        },
        error: function () {
            $('#viewModalContent').html('<div class="text-center py-8 text-red-600">Error loading purchase details</div>');
        }
    });
}

function closeViewModal() {
    $('#viewModal').addClass('hidden');
}

function printPurchase() {
    var content = document.getElementById('viewModalContent').innerHTML;
    var mywindow = window.open('', 'PRINT', 'height=800,width=1000');
    mywindow.document.write('<html><head><title>Purchase_' + $('#viewModalTitle').text().replace('Purchase > ', '') + '</title>');
    mywindow.document.write('<link rel="stylesheet" href="/pos/assets/css/output.css">');
    mywindow.document.write('<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"/>');
    mywindow.document.write('</head><body class="p-4">');
    mywindow.document.write(content);
    mywindow.document.write('</body></html>');
    mywindow.document.close();
    mywindow.focus();
    setTimeout(function () { mywindow.print(); mywindow.close(); }, 1500);
}

// Payment Modal
function openPaymentModal(invoiceId) {
    $('#paymentModalContent').html('<div class="text-center py-8"><i class="fas fa-spinner fa-spin fa-2x text-green-600"></i><p class="mt-4 text-slate-600">Loading...</p></div>');
    $('#paymentModal').removeClass('hidden');

    $.ajax({
        url: '/pos/purchases/save_purchase.php',
        type: 'POST',
        data: { payment_modal_btn: true, invoice_id: invoiceId },
        dataType: 'json',
        success: function (response) {
            if (response.status == 200) {
                $('#paymentModalContent').html(response.html);
            } else {
                $('#paymentModalContent').html('<div class="text-center py-8 text-red-600">' + response.message + '</div>');
            }
        },
        error: function () {
            $('#paymentModalContent').html('<div class="text-center py-8 text-red-600">Error loading payment form</div>');
        }
    });
}

function closePaymentModal() {
    $('#paymentModal').addClass('hidden');
}

// Return Modal
function openReturnModal(invoiceId) {
    $('#returnModalTitle').text('Return > ' + invoiceId);
    $('#returnModalContent').html('<div class="text-center py-8"><i class="fas fa-spinner fa-spin fa-2x text-orange-600"></i><p class="mt-4 text-slate-600">Loading...</p></div>');
    $('#returnModal').removeClass('hidden');

    $.ajax({
        url: '/pos/purchases/save_purchase.php',
        type: 'POST',
        data: { return_modal_btn: true, invoice_id: invoiceId },
        dataType: 'json',
        success: function (response) {
            if (response.status == 200) {
                $('#returnModalContent').html(response.html);
            } else {
                $('#returnModalContent').html('<div class="text-center py-8 text-red-600">' + response.message + '</div>');
            }
        },
        error: function () {
            $('#returnModalContent').html('<div class="text-center py-8 text-red-600">Error loading return form</div>');
        }
    });
}

function closeReturnModal() {
    $('#returnModal').addClass('hidden');
}
// Pay All Selected
function payAllSelected() {
    var selected = [];
    $('.row-checkbox:checked').each(function () {
        var due = parseFloat($(this).data('due'));
        if (due > 0) {
            selected.push($(this).data('invoice'));
        }
    });

    if (selected.length === 0) {
        Swal.fire({
            icon: 'info',
            title: 'No Selection',
            text: 'Please select invoices with a due amount first.',
            confirmButtonColor: '#0d9488'
        });
        return;
    }

    Swal.fire({
        title: 'Pay All Selected?',
        text: `You are about to process payments for ${selected.length} invoice(s).`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#059669',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Yes, Pay All'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '/pos/purchases/save_purchase.php',
                type: 'POST',
                data: { pay_all_btn: true, invoice_ids: selected },
                dataType: 'json',
                success: function (response) {
                    if (response.status == 200) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Paid!',
                            text: 'Bulk payments processed successfully.',
                            timer: 2000,
                            showConfirmButton: false
                        });
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                }
            });
        }
    });
}

// Handle payment submission
$(document).on('submit', '#paymentForm', function (e) {
    e.preventDefault();
    var formData = $(this).serialize();
    var submitBtn = $(this).find('button[type="submit"]');

    submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');

    $.ajax({
        url: '/pos/purchases/save_purchase.php',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function (response) {
            if (response.status == 200) {
                closePaymentModal();
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: 'Payment processed successfully!',
                    timer: 1500,
                    showConfirmButton: false
                });
                setTimeout(() => location.reload(), 1500);
            } else {
                submitBtn.prop('disabled', false).text('Save Payment');
                Swal.fire('Error', response.message, 'error');
            }
        },
        error: function () {
            submitBtn.prop('disabled', false).text('Save Payment');
            Swal.fire('Error', 'Something went wrong. Please try again.', 'error');
        }
    });
});

// Handle return submission
$(document).on('submit', '#returnForm', function (e) {
    e.preventDefault();
    var formData = $(this).serialize();
    var submitBtn = $(this).find('button[type="submit"]');

    submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');

    $.ajax({
        url: '/pos/purchases/save_purchase.php',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function (response) {
            if (response.status == 200) {
                closeReturnModal();
                Swal.fire({
                    icon: 'success',
                    title: 'Returned',
                    text: 'Return processed successfully!',
                    timer: 1500,
                    showConfirmButton: false
                });
                setTimeout(() => location.reload(), 1500);
            } else {
                submitBtn.prop('disabled', false).text('Submit Return');
                Swal.fire('Error', response.message, 'error');
            }
        },
        error: function () {
            submitBtn.prop('disabled', false).text('Submit Return');
            Swal.fire('Error', 'Failed to process return.', 'error');
        }
    });
});

function confirmDelete(id, name, url) {
    Swal.fire({
        title: 'Are you sure?',
        text: "You want to delete " + name + "! This action cannot be undone.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: url,
                type: 'POST',
                data: { delete_btn: true, delete_id: id },
                dataType: 'json',
                success: function (response) {
                    if (response.status == 200) {
                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true,
                            background: '#1e3a3a',
                            color: '#fff'
                        });
                        Toast.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: 'Purchase deleted!'
                        });
                        setTimeout(() => location.reload(), 1500);
                    }
                }
            });
        }
    })
}
