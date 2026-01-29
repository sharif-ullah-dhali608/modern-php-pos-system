console.log("Reports Panel: File loaded.");

function toggleReportsPanel(e) {
    console.log("Reports Panel: Opening panel.");
    if (e) e.preventDefault();
    const panel = document.getElementById('reportsPanel');
    const overlay = document.getElementById('reportsOverlay');
    panel.classList.add('active');
    overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
    setTimeout(() => document.getElementById('reportSearch').focus(), 400);
}

function closeReportsPanel() {
    const panel = document.getElementById('reportsPanel');
    const overlay = document.getElementById('reportsOverlay');
    panel.classList.remove('active');
    overlay.classList.remove('active');
    document.body.style.overflow = '';
}

function filterReports(input) {
    const term = input.value.toLowerCase();
    const items = document.querySelectorAll('.report-item');
    items.forEach(item => {
        const text = item.textContent.toLowerCase();
        if (text.includes(term)) {
            item.style.display = 'flex';
        } else {
            item.style.display = 'none';
        }
    });
}
