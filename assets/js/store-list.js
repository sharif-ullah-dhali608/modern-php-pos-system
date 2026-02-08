// Modal & Badge Functions
const modal = document.getElementById('storeModal');
const modalBackdrop = document.getElementById('modalBackdrop');
const modalPanel = document.getElementById('modalPanel');
let currentStoreId = null;

function openStoreDetailModal(data) {
    currentStoreId = data.id;
    document.getElementById('m_storeName').textContent = data.store_name;
    document.getElementById('m_storeCode').textContent = data.store_code;
    const statusEl = document.getElementById('m_status');
    // Status colors adjusted for Light Mode
    statusEl.textContent = data.status == 1 ? 'ACTIVE' : 'INACTIVE';
    statusEl.className = data.status == 1 ? 'font-bold text-emerald-600' : 'font-bold text-red-600';

    const formatter = new Intl.NumberFormat('en-BD', { style: 'currency', currency: 'BDT', minimumFractionDigits: 0, maximumFractionDigits: 0 });
    document.getElementById('m_target').textContent = formatter.format(data.daily_target);
    document.getElementById('m_openTime').textContent = formatTime(data.open_time);
    document.getElementById('m_closeTime').textContent = formatTime(data.close_time);
    document.getElementById('m_phone').textContent = data.phone || 'N/A';
    document.getElementById('m_email').textContent = data.email || 'N/A';
    document.getElementById('m_address').textContent = (data.address || '') + ' ' + (data.city_zip || '');
    document.getElementById('m_invDisc').textContent = data.max_inv_disc || 0;
    document.getElementById('m_lowStock').textContent = data.low_stock || 0;

    const oversellEl = document.getElementById('m_overselling');
    let oversellText = 'STRICT';
    // Oversell badge colors adjusted for Light Mode
    let oversellClass = 'bg-rose-100 text-rose-700';
    if (data.overselling === 'allow') {
        oversellText = 'ALLOWED';
        oversellClass = 'bg-emerald-100 text-emerald-700';
    } else if (data.overselling === 'warning') {
        oversellText = 'WARNING';
        oversellClass = 'bg-amber-100 text-amber-700';
    }
    oversellEl.textContent = oversellText;
    oversellEl.className = `text-[10px] font-bold px-2 py-0.5 rounded uppercase ${oversellClass}`;

    updateBadge('m_manualPriceBadge', data.allow_manual_price, 'Manual Price');
    updateBadge('m_backdateBadge', data.allow_backdate, 'Backdate');
    document.getElementById('m_editBtn').href = '/pos/stores/edit?id=' + data.id;
    modal.classList.remove('hidden');
    setTimeout(() => { modalBackdrop.classList.remove('opacity-0'); modalPanel.classList.remove('opacity-0', 'scale-95'); modalPanel.classList.add('modal-enter'); }, 10);
}

function updateBadge(id, value, text) {
    const el = document.getElementById(id);
    el.textContent = text;
    // Quick Rules Badge colors adjusted for Light Mode
    if (value == 1) {
        el.className = "px-2 py-1 rounded border border-teal-200 bg-teal-100 text-teal-700 text-[10px] font-bold";
        el.style.opacity = "1";
    }
    else {
        el.className = "px-2 py-1 rounded border border-slate-200 bg-slate-100 text-slate-500 text-[10px] font-bold line-through opacity-70";
    }
}

function closeStoreDetailModal() {
    modalBackdrop.classList.add('opacity-0');
    modalPanel.classList.remove('modal-enter');
    modalPanel.classList.add('modal-exit');
    setTimeout(() => { modal.classList.add('hidden'); modalPanel.classList.remove('modal-exit'); modalPanel.classList.add('opacity-0', 'scale-95'); }, 200);
}

function formatTime(time) {
    if (!time) return '--:--';
    const [h, m] = time.split(':');
    const hour = parseInt(h, 10);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const formattedHour = hour % 12 || 12;
    return `${formattedHour}:${m} ${ampm}`;
}

function confirmDeleteFromModal() { if (currentStoreId) confirmDelete(event, currentStoreId); }

function confirmDelete(e, id) {
    if (e) e.stopPropagation();
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Yes, delete it!',
        // Custom styles for consistency with light mode theme
        background: '#ffffff',
        color: '#1e293b',
        customClass: {
            popup: 'border border-slate-200'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form'); form.method = 'POST'; form.action = 'save_store.php';
            const input = document.createElement('input'); input.type = 'hidden'; input.name = 'delete_id'; input.value = id;
            const btn = document.createElement('input'); btn.type = 'hidden'; btn.name = 'delete_store_btn'; btn.value = true;
            form.appendChild(input); form.appendChild(btn); document.body.appendChild(form); form.submit();
        }
    })
}