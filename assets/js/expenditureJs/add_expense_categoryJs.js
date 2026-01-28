$(document).ready(function() {
    window.processCategoryForm = function() {
        let isValid = true;
        const name = $('#category_name').val().trim();
        
        if(name === "") {
            $('#category_name').addClass('error-border');
            $('#error-name').show();
            isValid = false;
        } else {
            $('#category_name').removeClass('error-border');
            $('#error-name').hide();
        }

        if(isValid) {
            $('#categoryForm').submit();
        } else {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
            Toast.fire({
                icon: 'error',
                title: 'Please fill name!',
                background: '#1e293b',
                color: '#fff'
            });
        }
    };
});
