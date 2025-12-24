
document.addEventListener('DOMContentLoaded', function() {
    const fields = ['store_name', 'store_code', 'business_type', 'phone', 'email', 'address', 'city_zip'];
    
    fields.forEach(id => {
        const el = document.getElementById(id);
        if(el) {
            const clearError = () => {
                const errEl = document.getElementById('err_' + id);
                if(errEl && !errEl.classList.contains('hidden')) {
                    errEl.classList.add('hidden');
                    
                    let parent = el.closest('.relative.w-full.mb-1.group');
                    if(parent) parent.classList.remove('has-error');
                }
            };

            el.addEventListener('input', clearError);
            el.addEventListener('focus', clearError);
            el.addEventListener('blur', clearError); 
            el.addEventListener('change', clearError); 
        }
    });

    // --- Dynamic Store Status Card Toggle Logic (NEW) ---
    const statusToggle = document.getElementById('status-toggle');
    const statusCard = document.getElementById('store-status-card');

    // Define base classes that remain
    const baseClasses = ['glass-card', 'rounded-xl', 'p-8', 'slide-in', 'delay-1'];
    
    // Define theme classes
    const activeThemeClasses = 'bg-gradient-to-br from-teal-900 via-teal-800 to-emerald-900 text-white shadow-xl shadow-indigo-600/30';
    const inactiveThemeClasses = 'bg-slate-300 text-slate-800 shadow-md shadow-slate-500/30';

    function updateStatusCard(isChecked) {
        const subtitle = statusCard.querySelector('p');

        // 1. Reset/Clear existing theme classes
        statusCard.classList.remove(...activeThemeClasses.split(' '));
        statusCard.classList.remove(...inactiveThemeClasses.split(' '));

        // 2. Apply new theme classes
        if (isChecked) {
            statusCard.classList.add(...activeThemeClasses.split(' '));
            // Adjust text colors for contrast against dark background
            statusCard.querySelector('h3').classList.remove('text-slate-800');
            statusCard.querySelector('h3').classList.add('text-white');
            subtitle.classList.remove('text-slate-600/80');
            subtitle.classList.add('text-white/80');
        } else {
            statusCard.classList.add(...inactiveThemeClasses.split(' '));
            // Adjust text colors for contrast against light background
            statusCard.querySelector('h3').classList.remove('text-white');
            statusCard.querySelector('h3').classList.add('text-slate-800');
            subtitle.classList.remove('text-white/80');
            subtitle.classList.add('text-slate-600/80');
        }

        // Ensure base classes are always present (optional, but good practice)
        statusCard.classList.add(...baseClasses);
    }

    // Initialize card state on page load
    updateStatusCard(statusToggle.checked); 

    // Event listener for the toggle
    statusToggle.addEventListener('change', function() {
        updateStatusCard(this.checked);
    });

});

document.getElementById('storeForm').addEventListener('submit', function(e) {
    let isValid = true;
    let firstError = null;

    // Helper to find the correct error wrapper 
    function getErrorParent(id) {
        const el = document.getElementById(id);
        if (id === 'phone') {
            return el.closest('.relative.w-full.mb-1.group');
        }
        return el.closest('.relative.w-full.mb-1.group');
    }

    // Helper to show/hide error
    function toggleError(id, show) {
        const el = document.getElementById('err_' + id);
        const parentDiv = getErrorParent(id);
        
        if(el && parentDiv) {
            if(show) {
                el.classList.remove('hidden');
                parentDiv.classList.add('has-error');
                if(!firstError) firstError = document.getElementById(id);
            } else {
                el.classList.add('hidden');
                parentDiv.classList.remove('has-error');
            }
        }
    }

    // 1. Validate Store Name
    const name = document.getElementById('store_name');
    if(name.value.trim() === '') {
        isValid = false;
        toggleError('store_name', true);
    } else {
        toggleError('store_name', false);
    }

    // 2. Validate Code (Alphanumeric)
    const code = document.getElementById('store_code');
    const codeRegex = /^[A-Z0-9]+$/i;
    if(code.value.trim() === '' || !codeRegex.test(code.value)) {
        isValid = false;
        toggleError('store_code', true);
    } else {
        toggleError('store_code', false);
    }

    // 3. Validate Business Type
    const type = document.getElementById('business_type');
    if(type.value === '') {
        isValid = false;
        toggleError('business_type', true);
    } else {
        toggleError('business_type', false);
    }

    // 4. Validate Phone (Digits only, length 10-15)
    const phone = document.getElementById('phone');
    if(phone.value.trim() === '' || phone.value.length < 10 || phone.value.length > 15) {
        isValid = false;
        toggleError('phone', true); 
    } else {
        toggleError('phone', false);
    }

    // 5. Validate Email (Optional but if exists check format)
    const email = document.getElementById('email');
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if(email.value.trim() !== '' && !emailRegex.test(email.value)) {
        isValid = false;
        toggleError('email', true);
    } else {
        toggleError('email', false);
    }

    // 6. Validate Address
    const address = document.getElementById('address');
    if(address.value.trim() === '') {
        isValid = false;
        toggleError('address', true);
    } else {
        toggleError('address', false);
    }

    // 7. Validate City/Zip
    const city = document.getElementById('city_zip');
    if(city.value.trim() === '') {
        isValid = false;
        toggleError('city_zip', true);
    } else {
        toggleError('city_zip', false);
    }

    if(!isValid) {
        e.preventDefault();
        if(firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            firstError.focus();
        }
        
        Swal.fire({
        icon: 'error',
        title: 'Notice', // Footer title se match kiya
        text: 'Please fill up the required fields correctly.',
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 4000, // Footer timer se match kiya
        timerProgressBar: true,
        background: '#064e3b', // Slate-800 (Jo aapke footer error bg mein hai)
        color: '#fff' // White text
    });
    }
});

const config = {
    hours: Array.from({length: 12}, (_, i) => (i + 1).toString().padStart(2, '0')),
    minutes: Array.from({length: 60}, (_, i) => i.toString().padStart(2, '0')),
    meridiem: ['AM', 'PM']
};

let selections = {
    open: { h: '09', m: '00', p: 'AM' },
    close: { h: '09', m: '00', p: 'PM' }
};

function parseDBTime(timeStr) {
    if(!timeStr) return null;
    let [hour, minute] = timeStr.split(':');
    let h = parseInt(hour);
    let p = h >= 12 ? 'PM' : 'AM';
    h = h % 12 || 12;
    return { h: h.toString().padStart(2, '0'), m: minute, p: p };
}

function initPicker(pickerId, type, initialTime) {
    const picker = document.getElementById(pickerId);
    if(!picker) return;

    const dbTime = parseDBTime(initialTime);
    if(dbTime) selections[type] = dbTime;

    renderColumn(picker.querySelector('.hours'), config.hours, type, 'h');
    renderColumn(picker.querySelector('.minutes'), config.minutes, type, 'm');
    renderColumn(picker.querySelector('.meridiem'), config.meridiem, type, 'p');
    
    setTimeout(() => {
        scrollToValue(picker.querySelector('.hours'), selections[type].h);
        scrollToValue(picker.querySelector('.minutes'), selections[type].m);
        scrollToValue(picker.querySelector('.meridiem'), selections[type].p);
        updateHiddenInput(type);
    }, 100);
}

function scrollToValue(el, value) {
    const items = Array.from(el.querySelectorAll('.time-item'));
    const index = items.findIndex(item => item.innerText === value);
    if (index !== -1) {
        el.scrollTop = (index - 1) * 40;
    }
}

function renderColumn(el, data, pickerType, colType) {
    el.innerHTML = '<div class="time-item"></div>' + 
        data.map(item => `<div class="time-item">${item}</div>`).join('') + 
        '<div class="time-item"></div>';
        
    el.addEventListener('scroll', () => {
        const selectedValue = handleScroll(el, pickerType, colType);
        if(selectedValue) {
            selections[pickerType][colType] = selectedValue;
            updateHiddenInput(pickerType);
        }
    });
}

function handleScroll(el) {
    const items = el.querySelectorAll('.time-item');
    const selectedIndex = Math.round(el.scrollTop / 40);
    let val = "";

    items.forEach((item, i) => {
        if (i === selectedIndex + 1) {
            item.classList.add('selected');
            val = item.innerText;
        } else {
            item.classList.remove('selected');
        }
    });
    return val;
}

function updateHiddenInput(type) {
    let { h, m, p } = selections[type];
    let hours = parseInt(h);
    if (p === 'PM' && hours < 12) hours += 12;
    if (p === 'AM' && hours === 12) hours = 0;
    const finalTime = `${hours.toString().padStart(2, '0')}:${m}`;
    document.getElementById(`${type}_time_val`).value = finalTime;
}
