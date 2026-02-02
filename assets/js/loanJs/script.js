/**
 * Loan Module Scripts
 */

$(document).ready(function () {
    // 1. Initial Logic
    calculatePayable(); // Initial calculation for edit mode

    // 1.1 Auto-select input content on focus
    $(document).on('focus', 'input:not([type="date"]):not([type="time"]):not(.datepicker), textarea', function () {
        $(this).select();
    });

    // 1.2 Custom Calendar Logic
    let calendarCurrentDate = new Date();
    let calendarStartDate = null;
    let calendarEndDate = null;

    // Check for existing values in edit mode
    const existingRange = $('#transaction_range').val();
    if (existingRange) {
        const parts = existingRange.split(' ---- To ---- ');
        if (parts[0]) calendarStartDate = parts[0];
        if (parts[1]) calendarEndDate = parts[1];
        if (calendarStartDate) calendarCurrentDate = new Date(calendarStartDate);
    }

    // Toggle Dropdown
    window.toggleCustomCalendar = function (e) {
        if (e) e.stopPropagation();
        const dropdown = document.getElementById('custom-calendar-dropdown');
        const input = document.getElementById('transaction_range');

        dropdown.classList.toggle('hidden');
        if (!dropdown.classList.contains('hidden')) {
            renderCustomCalendar();
            populateYearSelector();
            input.classList.add('ring-2', 'ring-teal-500', 'border-teal-500', 'bg-white');
            input.classList.remove('bg-slate-50', 'border-slate-200');
        } else {
            input.classList.remove('ring-2', 'ring-teal-500', 'border-teal-500', 'bg-white');
            input.classList.add('bg-slate-50', 'border-slate-200');
        }
    };

    // Show/Hide Year/Month Selector
    window.toggleYearMonthSelector = function () {
        const selector = document.getElementById('year-month-selector');
        selector.classList.toggle('hidden');
    };

    // Change Month
    window.changeCalendarMonth = function (direction) {
        calendarCurrentDate.setMonth(calendarCurrentDate.getMonth() + direction);
        renderCustomCalendar();
    };

    // Apply Year/Month view
    window.applyYearMonthSelection = function () {
        const year = parseInt(document.getElementById('year-select').value);
        const month = parseInt(document.getElementById('month-select').value);
        calendarCurrentDate = new Date(year, month, 1);
        renderCustomCalendar();
        document.getElementById('year-month-selector').classList.add('hidden');
    };

    // Render Calendar
    window.renderCustomCalendar = function () {
        const year = calendarCurrentDate.getFullYear();
        const month = calendarCurrentDate.getMonth();
        const monthNames = ['JANUARY', 'FEBRUARY', 'MARCH', 'APRIL', 'MAY', 'JUNE', 'JULY', 'AUGUST', 'SEPTEMBER', 'OCTOBER', 'NOVEMBER', 'DECEMBER'];

        document.getElementById('calendar-month-display').textContent = `${monthNames[month]} ${year}`;

        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();

        const grid = document.getElementById('calendar-grid');
        grid.innerHTML = '';

        // Empty cells
        for (let i = 0; i < firstDay; i++) {
            grid.appendChild(document.createElement('div'));
        }

        // Render days
        for (let day = 1; day <= daysInMonth; day++) {
            const date = new Date(year, month, day);
            const dateStr = formatCalendarDate(date);

            const wrapper = document.createElement('div');
            wrapper.className = 'calendar-day-wrapper';

            const btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = day;
            btn.className = 'calendar-day-btn';

            // Highlight Logic
            if (calendarStartDate && calendarEndDate) {
                const start = new Date(calendarStartDate);
                const end = new Date(calendarEndDate);
                if (date > start && date < end) {
                    wrapper.classList.add('bg-teal-50');
                    btn.classList.add('text-teal-600', 'font-bold');
                } else if (dateStr === calendarStartDate) {
                    wrapper.style.background = 'linear-gradient(to right, transparent 50%, #f0fdfa 50%)';
                    btn.classList.add('bg-teal-600', 'text-white', 'font-bold', 'rounded-xl', 'shadow-lg', 'shadow-teal-500/30');
                } else if (dateStr === calendarEndDate) {
                    wrapper.style.background = 'linear-gradient(to left, transparent 50%, #f0fdfa 50%)';
                    btn.classList.add('bg-teal-600', 'text-white', 'font-bold', 'rounded-xl', 'shadow-lg', 'shadow-teal-500/30');
                } else {
                    btn.classList.add('text-slate-600', 'hover:bg-slate-50', 'rounded-xl');
                }
            } else if (calendarStartDate && dateStr === calendarStartDate) {
                btn.classList.add('bg-teal-600', 'text-white', 'font-bold', 'rounded-xl', 'shadow-lg', 'shadow-teal-500/30');
            } else {
                btn.classList.add('text-slate-600', 'hover:bg-slate-50', 'rounded-xl');
            }

            btn.onclick = (e) => {
                e.stopPropagation();
                selectCalendarDate(date);
            };

            wrapper.appendChild(btn);
            grid.appendChild(wrapper);
        }
        updateRangeDisplay();
    };

    function selectCalendarDate(date) {
        const dateStr = formatCalendarDate(date);
        if (!calendarStartDate || (calendarStartDate && calendarEndDate)) {
            calendarStartDate = dateStr;
            calendarEndDate = null;
        } else {
            const start = new Date(calendarStartDate);
            if (date < start) {
                calendarEndDate = calendarStartDate;
                calendarStartDate = dateStr;
            } else {
                calendarEndDate = dateStr;
            }
            // If range complete, update input and close
            $('#transaction_range').val(`${calendarStartDate} ---- To ---- ${calendarEndDate}`).trigger('change');
            setTimeout(() => {
                document.getElementById('custom-calendar-dropdown').classList.add('hidden');
            }, 300);
        }
        renderCustomCalendar();
    }

    function updateRangeDisplay() {
        const display = document.getElementById('calendar-selected-range');
        if (calendarStartDate && calendarEndDate) {
            display.textContent = `${calendarStartDate} ---- To ---- ${calendarEndDate}`;
            display.classList.add('text-teal-600');
        } else if (calendarStartDate) {
            display.textContent = 'Select deadline date';
            display.classList.remove('text-teal-600');
        } else {
            display.textContent = 'Select start & deadline';
            display.classList.remove('text-teal-600');
        }
    }

    function formatCalendarDate(date) {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    }

    function populateYearSelector() {
        const select = document.getElementById('year-select');
        if (!select) return;
        select.innerHTML = '';
        const curr = new Date().getFullYear();
        for (let i = curr - 5; i <= curr + 5; i++) {
            const opt = document.createElement('option');
            opt.value = i;
            opt.textContent = i;
            if (i === calendarCurrentDate.getFullYear()) opt.selected = true;
            select.appendChild(opt);
        }
        document.getElementById('month-select').value = calendarCurrentDate.getMonth();
    }

    // Close on outside click
    $(document).on('click', function (e) {
        if (!$(e.target).closest('#transaction-range-container').length) {
            $('#custom-calendar-dropdown').addClass('hidden');
            $('#year-month-selector').addClass('hidden');
        }
    });

    // 2. Swiper/Toast Session Messages (from DOM attributes)
    const sessionData = document.getElementById('session-toast-data');
    if (sessionData) {
        const icon = sessionData.getAttribute('data-icon') || 'error';
        const title = sessionData.getAttribute('data-title') || '';
        const bgColor = sessionData.getAttribute('data-bg') || '#1e293b';

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
            icon: icon,
            title: title,
            background: bgColor,
            color: '#fff',
            iconColor: '#fff',
            customClass: {
                popup: 'rounded-2xl shadow-2xl px-5 py-2'
            }
        });
    }

    // 3. Select2 Initialization
    $('#loan_from_id').select2({
        placeholder: "Search Loan Source...",
        width: '100%',
        dropdownCssClass: "custom-select2-dropdown"
    });

    $('#loan_from_id').on('change', function () {
        // Trigger validation clearing
        const field = this;
        const parent = field.closest('.form-group') || field.parentElement;
        const errorMsg = parent.querySelector('.error-msg');

        // Remove error styling from Select2 container
        $(field).next('.select2-container').removeClass('select2-error');
        if (errorMsg) errorMsg.classList.add('hidden');
    });

    // 4. Input Watchers for Payable Calculation
    $('#amount').on('input change', calculatePayable);
    $('#interest').on('input change', function () {
        let val = parseFloat($(this).val());
        if (val > 100) $(this).val(100);
        if (val < 0) $(this).val(0);
        calculatePayable();
    });

    // 5. Real-time Validation Feedback
    const fieldsToWatch = ['transaction_range', 'loan_from_id', 'ref_no', 'title', 'amount', 'interest'];
    fieldsToWatch.forEach(function (fieldName) {
        const field = document.getElementById(fieldName) || document.querySelector(`[name="${fieldName}"]`);
        if (field) {
            const eventType = (field.tagName === 'SELECT' || field.type === 'date') ? 'change' : 'input';
            field.addEventListener(eventType, function () {
                const parent = field.closest('.form-group') || field.parentElement;
                const errorMsg = parent.querySelector('.error-msg');

                field.classList.remove('border-red-500', 'focus:ring-red-500', 'bg-red-50');
                field.classList.add('border-slate-300', 'focus:ring-teal-500', 'bg-slate-50');
                if (errorMsg) errorMsg.classList.add('hidden');
            });
        }
    });

    // Special watch for attachment
    document.getElementById('attachment')?.addEventListener('change', function () {
        const previewBox = document.getElementById('attachment-preview-container');
        const parent = this.closest('.form-group');
        const errorMsg = parent.querySelector('.error-msg');

        const oldAttachment = document.querySelector('input[name="old_attachment"]')?.value;
        if (this.files.length > 0 || (oldAttachment && oldAttachment !== "")) {
            previewBox.classList.remove('border-red-500', 'bg-red-50');
            previewBox.classList.add('border-slate-200', 'bg-slate-50/30');
            if (errorMsg) errorMsg.classList.add('hidden');
        }
    });

    // 6. Phone Validation for Modal
    let iti;
    const phoneInput = document.querySelector("#source_phone");
    const fullPhoneInput = document.querySelector("#source_full_phone");
    const phoneErrorMsg = document.querySelector("#source-error-msg");
    const phoneValidMsg = document.querySelector("#source-valid-msg");

    const initPhone = () => {
        if (typeof window.intlTelInput === 'undefined') { setTimeout(initPhone, 50); return; }
        iti = window.intlTelInput(phoneInput, {
            initialCountry: "bd",
            separateDialCode: true,
            utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/utils.js"
        });

        const reset = () => {
            phoneInput.classList.remove("border-rose-500", "border-green-500", "bg-rose-50", "bg-green-50/30");
            phoneInput.classList.add("border-slate-200", "bg-slate-50");
            phoneErrorMsg.innerHTML = "";
            phoneErrorMsg.classList.add("hidden");
            phoneValidMsg.classList.add("hidden");
        };

        const validatePhone = () => {
            reset();
            const val = phoneInput.value.trim();
            if (val.length > 0) {
                if (iti.isValidNumber()) {
                    phoneValidMsg.classList.remove("hidden");
                    phoneInput.classList.remove("border-slate-200", "bg-slate-50");
                    phoneInput.classList.add("border-green-500", "bg-green-50/30");
                    fullPhoneInput.value = iti.getNumber();
                    return true;
                } else {
                    phoneInput.classList.remove("border-slate-200", "bg-slate-50");
                    phoneInput.classList.add("border-rose-500", "bg-rose-50");
                    const errorCode = iti.getValidationError();
                    const errorMap = ["Invalid number", "Invalid country code", "Too short", "Too long", "Invalid number"];
                    phoneErrorMsg.innerHTML = errorMap[errorCode] || "Invalid number";
                    phoneErrorMsg.classList.remove("hidden");
                    return false;
                }
            } else {
                // Keep it clean if empty
                reset();
            }
            return false;
        };

        phoneInput.addEventListener('blur', validatePhone);
        phoneInput.addEventListener('input', function () {
            this.value = this.value.replace(/[^0-9]/g, '');
            reset();
        });
    };

    if (phoneInput) initPhone();

    // 6. AJAX for Adding Loan Source
    const sourceAddForm = document.getElementById('sourceAddForm');
    if (sourceAddForm) {
        // Real-time clearing for modal fields
        const modalFields = ['modal_source_name', 'modal_source_type', 'modal_source_address'];
        modalFields.forEach(id => {
            const field = document.getElementById(id);
            if (field) {
                field.addEventListener('input', function () {
                    const parent = field.closest('.form-group');
                    const errorMsg = parent.querySelector('.error-msg');
                    field.classList.remove('border-rose-500', 'bg-rose-50');
                    field.classList.add('border-slate-200', 'bg-slate-50');
                    if (errorMsg) errorMsg.classList.add('hidden');
                });
                if (field.tagName === 'SELECT') {
                    field.addEventListener('change', function () {
                        const parent = field.closest('.form-group');
                        const errorMsg = parent.querySelector('.error-msg');
                        field.classList.remove('border-rose-500', 'bg-rose-50');
                        field.classList.add('border-slate-200', 'bg-slate-50');
                        if (errorMsg) errorMsg.classList.add('hidden');
                    });
                }
            }
        });

        sourceAddForm.addEventListener('submit', function (e) {
            e.preventDefault();
            let isModalValid = true;

            // Manual field check
            modalFields.forEach(id => {
                const field = document.getElementById(id);
                if (field && field.value.trim() === "") {
                    const parent = field.closest('.form-group');
                    const errorMsg = parent.querySelector('.error-msg');
                    field.classList.remove('border-slate-200', 'bg-slate-50');
                    field.classList.add('border-rose-500', 'bg-rose-50');
                    if (errorMsg) errorMsg.classList.remove('hidden');
                    isModalValid = false;
                }
            });

            // Validate Phone before submit
            if (!iti.isValidNumber()) {
                phoneInput.classList.remove('border-slate-200', 'bg-slate-50');
                phoneInput.classList.add("border-rose-500", "bg-rose-50");
                phoneInput.focus();

                // Ensure error message is visible
                const phoneParent = phoneInput.closest('.form-group');
                const phoneError = phoneParent.querySelector('#source-error-msg');
                if (phoneError && phoneInput.value.trim() === "") {
                    phoneError.innerHTML = "Phone number is required";
                    phoneError.classList.remove('hidden');
                }
                isModalValid = false;
            }

            if (!isModalValid) return;

            fullPhoneInput.value = iti.getNumber();
            const formData = new FormData(this);
            formData.append('add_loan_source_ajax', '1');

            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Saving...';

            fetch('save_loan.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 200) {
                        const newOption = new Option(data.name, data.id, true, true);
                        $('#loan_from_id').append(newOption).trigger('change');

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
                            icon: 'success',
                            title: 'Loan source added successfully',
                            background: '#0d9488',
                            color: '#fff',
                            iconColor: '#fff',
                            customClass: {
                                popup: 'rounded-2xl shadow-2xl px-5 py-2'
                            }
                        });
                        closeSourceModal();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Something went wrong',
                            background: '#1e293b',
                            color: '#fff'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'System error occurred'
                    });
                })
                .finally(() => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                });
        });
    }
});

// --- Global Functions ---

// Attachment Preview Logic
function previewFile() {
    const input = document.getElementById('attachment');
    const container = document.getElementById('preview-inner');
    const removeBtn = document.getElementById('remove-file-btn');

    if (input.files && input.files[0]) {
        const file = input.files[0];
        const reader = new FileReader();

        reader.onload = function (e) {
            let html = '<div class="flex flex-col items-center justify-center">';
            if (file.type.startsWith('image/')) {
                html += `
                    <div class="relative group">
                        <img src="${e.target.result}" class="h-20 w-32 object-cover rounded-xl shadow-lg border-2 border-white transform transition-transform duration-500">
                        <div class="absolute inset-0 bg-teal-500/10 rounded-xl opacity-0"></div>
                    </div>
                `;
            } else {
                html += `
                    <div class="w-16 h-16 bg-rose-50 rounded-2xl flex items-center justify-center shadow-inner">
                        <i class="fas fa-file-pdf text-rose-500 text-3xl"></i>
                    </div>
                `;
            }
            html += `
                <div class="mt-2 text-center w-full">
                    <p class="text-slate-700 font-bold text-[11px] truncate px-4">${file.name}</p>
                    <p class="text-teal-600 font-extrabold text-[8px] uppercase tracking-tighter bg-teal-50 px-2 py-0.5 rounded-full inline-block">New File Ready</p>
                </div>
            </div>`;

            container.innerHTML = html;
            removeBtn.classList.remove('hidden');
        }

        reader.readAsDataURL(file);
    }
}

function removeFile() {
    const input = document.getElementById('attachment');
    const container = document.getElementById('preview-inner');
    const removeBtn = document.getElementById('remove-file-btn');
    const oldAttachmentInput = document.querySelector('input[name="old_attachment"]');

    input.value = '';
    if (oldAttachmentInput) oldAttachmentInput.value = '';
    removeBtn.classList.add('hidden');

    container.innerHTML = `
        <div class="flex flex-col items-center justify-center">
            <div class="w-10 h-10 bg-white rounded-xl shadow-sm border border-slate-100 flex items-center justify-center mb-1 group-hover:bg-teal-500 group-hover:text-white transition-all duration-500">
                <i class="fas fa-cloud-upload-alt text-lg"></i>
            </div>
            <h5 class="text-slate-800 font-bold text-xs mb-0">Click to upload evidence</h5>
            <p class="text-slate-400 text-[8px] font-semibold uppercase tracking-widest mt-0.5">
                JPG • PNG • PDF <span class="text-slate-200">(MAX 5MB)</span>
            </p>
        </div>
    `;

    input.dispatchEvent(new Event('change'));
}

// Generate Reference Number
function generateRef() {
    const randomNum = Math.floor(100000 + Math.random() * 900000); // 6 digits
    const ref = "LN-" + randomNum;
    const input = document.getElementById('ref_no');
    if (input) {
        input.value = ref;
        input.dispatchEvent(new Event('input'));
    }
}

// Calculate payable amount
function calculatePayable() {
    const amountVal = document.getElementById('amount')?.value;
    const interestVal = document.getElementById('interest')?.value;
    const amount = parseFloat(amountVal) || 0;
    const interest = parseFloat(interestVal) || 0;
    const payable = amount + (amount * interest / 100);
    const payableInput = document.getElementById('payable');
    if (payableInput) {
        payableInput.value = payable.toFixed(2);
    }
}

// Reset Form Functionality
function resetForm() {
    const form = document.getElementById('loanForm');
    if (!form) return;

    form.reset();
    removeFile();

    const errorMessages = form.querySelectorAll('.error-msg');
    const inputFields = form.querySelectorAll('input, select, textarea');

    errorMessages.forEach(msg => msg.classList.add('hidden'));

    inputFields.forEach(field => {
        field.classList.remove('border-red-500', 'focus:ring-red-500', 'bg-red-50');
        field.classList.add('border-slate-300', 'focus:ring-teal-500', 'bg-slate-50');
    });

    calculatePayable();
}

// Modal Logic for Loan Source
function openSourceModal() {
    document.getElementById('loanSourceModal').classList.remove('hidden');
    setTimeout(() => {
        document.querySelector('#loanSourceModal .bg-white').classList.remove('scale-95', 'opacity-0');
        document.querySelector('#loanSourceModal .bg-white').classList.add('scale-100', 'opacity-100');
    }, 10);
}

function closeSourceModal() {
    const modalContent = document.querySelector('#loanSourceModal .bg-white');
    modalContent.classList.remove('scale-100', 'opacity-100');
    modalContent.classList.add('scale-95', 'opacity-0');
    setTimeout(() => {
        document.getElementById('loanSourceModal').classList.add('hidden');
        document.getElementById('sourceAddForm').reset();
    }, 300);
}

// Form Submission & Validation
document.getElementById('loanForm')?.addEventListener('submit', function (e) {
    let isValid = true;
    let firstErrorField = null;

    const requiredFields = [
        { name: 'transaction_range', label: 'Period' },
        { name: 'loan_from_id', label: 'Loan Source' },
        { name: 'ref_no', label: 'Reference No' },
        { name: 'title', label: 'Title' },
        { name: 'amount', label: 'Amount' }
    ];

    requiredFields.forEach(function (fieldObj) {
        const field = document.getElementById(fieldObj.name) || document.querySelector(`[name="${fieldObj.name}"]`);
        if (field) {
            const parent = field.closest('.form-group') || field.parentElement;
            const errorMsg = parent.querySelector('.error-msg');
            const val = field.value.trim();

            if (val === "") {
                isValid = false;
                if (field.id === 'loan_from_id') {
                    $(field).next('.select2-container').addClass('select2-error');
                } else {
                    field.classList.remove('border-slate-300', 'focus:ring-teal-500', 'bg-slate-50');
                    field.classList.add('border-red-500', 'focus:ring-red-500', 'bg-red-50');
                }
                if (errorMsg) errorMsg.classList.remove('hidden');
                if (!firstErrorField) firstErrorField = field;
            }
        }
    });

    // Attachment Validation
    const attachment = document.getElementById('attachment');
    if (attachment) {
        const attachmentParent = attachment.closest('.form-group');
        const attachmentErrorMsg = attachmentParent.querySelector('.error-msg');
        const previewBox = document.getElementById('attachment-preview-container');
        const oldAttachment = document.querySelector('input[name="old_attachment"]')?.value;
        const hasFile = (attachment.files && attachment.files.length > 0) || (oldAttachment && oldAttachment !== "");

        if (!hasFile) {
            isValid = false;
            previewBox.classList.remove('border-slate-200', 'bg-slate-50/30');
            previewBox.classList.add('border-red-500', 'bg-red-50');
            if (attachmentErrorMsg) attachmentErrorMsg.classList.remove('hidden');
            if (!firstErrorField) firstErrorField = attachment;
        }
    }

    if (!isValid) {
        e.preventDefault();
        if (firstErrorField) {
            firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
            firstErrorField.focus();
        }
    }
});

// Event Listeners for Attachment
document.getElementById('attachment')?.addEventListener('change', previewFile);
