console.log("POS Settings: File loaded.");

function togglePosSetting(key, value) {
    console.log("POS Settings: Toggling", key, "to", value);
    // Optimistic UI update
    if (key === 'show_images') {
        if (value) {
            $('.product-card img').show();
        } else {
            $('.product-card img').hide();
        }
    }

    // Get current store ID dynamically
    const currentStoreId = document.getElementById('store_select').value;

    // Save to DB
    $.ajax({
        url: '/pos/stores/save_store_settings.php',
        method: 'POST',
        data: {
            store_id: currentStoreId,
            settings: { [key]: value ? 1 : 0 }
        }
    });
}

function openSettings(e) {
    e.preventDefault();
    const currentStoreId = document.getElementById('store_select').value;
    const url = `/pos/stores/settings.php?store_id=${currentStoreId}`;
    window.location.href = url;
}
