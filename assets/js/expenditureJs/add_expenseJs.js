$(document).ready(function () {
    $('.select2').select2({ width: '100%' });

    window.previewAttachment = function (input) {
        if (input.files && input.files[0]) {
            const fileName = input.files[0].name;
            $('#file-name-display').text(fileName).show();
            $('#upload-placeholder-icon').addClass('text-teal-600');
        }
    };

    window.processExpenseForm = function () {
        let isValid = true;

        const showError = (id, show) => {
            const el = document.getElementById(id);
            const errEl = document.getElementById('error-' + id);
            if (el) {
                if (show) {
                    el.classList.add('error-border');
                    if (errEl) errEl.style.display = 'block';
                    isValid = false;
                } else {
                    el.classList.remove('error-border');
                    if (errEl) errEl.style.display = 'none';
                }
            }
        };

        showError('title', $('#title').val().trim() === "");
        showError('amount', $('#amount').val() === "" || parseFloat($('#amount').val()) <= 0);

        const catId = $('#category_id').val();
        if (catId === "") {
            $('#category_id').next('.select2').find('.select2-selection').addClass('error-border');
            $('#error-category_id').show();
            isValid = false;
        } else {
            $('#category_id').next('.select2').find('.select2-selection').removeClass('error-border');
            $('#error-category_id').hide();
        }

        const storeId = $('#store_id').val();
        if (storeId === "") {
            $('#store_id').next('.select2').find('.select2-selection').addClass('error-border');
            $('#error-store_id').show();
            isValid = false;
        } else {
            $('#store_id').next('.select2').find('.select2-selection').removeClass('error-border');
            $('#error-store_id').hide();
        }

        if (isValid) {
            $('#expenseForm').submit();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Check fields!',
                background: '#1e293b',
                color: '#fff'
            });
        }
    };
});
