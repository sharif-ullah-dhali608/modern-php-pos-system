<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('../config/dbcon.php');
include('../includes/date_filter_helper.php');

// Security Check
if(!isset($_SESSION['auth'])){
    header("Location: /pos/login");
    exit(0);
}

include('../includes/header.php');

date_default_timezone_set('Asia/Dhaka');

// --- DATE FILTER LOGIC ---
$date_filter = $_GET['date_filter'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// Statistics - using installment_payments for financial data
$stats_query = "SELECT 
    SUM(payable) as total_receivable, 
    SUM(paid) as total_received, 
    SUM(due) as total_due
FROM installment_payments WHERE 1=1";

applyDateFilter($stats_query, 'payment_date', $date_filter, $start_date, $end_date);
$stat_q = mysqli_query($conn, $stats_query);
$stats = [
    'total_receivable' => 0,
    'total_received' => 0,
    'total_due' => 0,
    'total_count' => 0
];

if($stat_q) {
    $data = mysqli_fetch_assoc($stat_q);
    $stats['total_receivable'] = $data['total_receivable'] ?? 0;
    $stats['total_received'] = $data['total_received'] ?? 0;
    $stats['total_due'] = $data['total_due'] ?? 0;
}

// Get Total Orders Count (Installment Plans)
// Note: Date filter might apply to when order was Created?
// For simplicity, total_count usually implies active orders, but here we can count all or filtered.
// Let's count filtered installment_orders if date filter is active, else all.
$count_query = "SELECT COUNT(*) as count FROM installment_orders WHERE 1=1";
// We don't have created_at in installment_orders used in previous loops, let's assume it exists or use invoice_id/date logic.
// installment_orders structure from list query: created_at exists.
applyDateFilter($count_query, 'created_at', $date_filter, $start_date, $end_date);
$cnt_q = mysqli_query($conn, $count_query);
if($cnt_q) {
    $stats['total_count'] = mysqli_fetch_assoc($cnt_q)['count'] ?? 0;
}


// Recent Payments
$payments_query = "SELECT ip.*, si.invoice_id, c.name as customer_name, u.name as created_by_name
              FROM installment_payments ip 
              LEFT JOIN selling_info si ON ip.invoice_id = si.invoice_id
              LEFT JOIN customers c ON si.customer_id = c.id
              LEFT JOIN users u ON ip.created_by = u.id 
              WHERE ip.paid > 0 ";

applyDateFilter($payments_query, 'ip.payment_date', $date_filter, $start_date, $end_date);

$payments_query .= " ORDER BY ip.payment_date DESC LIMIT 50";
$payments_res = mysqli_query($conn, $payments_query);
$payments = [];
while($row = mysqli_fetch_assoc($payments_res)) {
    $payments[] = $row;
}

// Determine active filter label for UI
$filter_label = 'Date';
$is_filtering = false;

if($date_filter) {
    $filter_label = ucwords(str_replace('_', ' ', $date_filter));
    $is_filtering = true;
} elseif($start_date && $end_date) {
    if($start_date === $end_date) {
        if($start_date !== date('Y-m-d')) {
            $is_filtering = true;
        }
        $filter_label = date('d M Y', strtotime($start_date));
    } else {
        $is_filtering = true;
        $filter_label = date('d M Y', strtotime($start_date)) . ' - ' . date('d M Y', strtotime($end_date));
    }
}

$currency = ''; // As per user preference
?>

<div class="app-wrapper">
    <?php include('../includes/sidebar.php'); ?>
    
    <main id="main-content" class="flex-1 lg:ml-64 main-content flex flex-col h-screen min-w-0 transition-all duration-300 bg-slate-50">
        <div class="navbar-fixed-top"><?php include('../includes/navbar.php'); ?></div>
        
        <div class="content-scroll-area custom-scroll flex-1 overflow-y-auto">
            <div class="flex flex-col min-h-full">
                <div class="p-4 md:p-6 max-w-7xl mx-auto w-full flex-1">
                <!-- Header -->
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8 mt-16 lg:mt-0">
                    <div>
                        <div class="flex items-center gap-3 mb-1">
                            <h1 class="text-3xl font-black text-slate-900 tracking-tight">Installment Summary</h1>
                            <style>
                                .flex.flex-wrap.items-center.gap-3 { display: flex !important; }
                                .flex.flex-wrap.items-center.gap-3 a.bg-rose-50 { order: 99 !important; }
                            </style>
                            <?php if($start_date && $end_date): ?>
                            <span class="bg-indigo-100 text-indigo-800 border border-indigo-200 px-3 py-1 rounded-full text-xs font-black uppercase tracking-wide">
                                <?php 
                                    if($start_date === $end_date) {
                                        echo date('d M Y', strtotime($start_date));
                                    } else {
                                        echo date('d M', strtotime($start_date)) . ' - ' . date('d M Y', strtotime($end_date));
                                    }
                                ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        <p class="text-slate-500 font-medium text-sm">Overview of all installments and recent payments</p>
                    </div>
                    
                    <!-- Actions and Filters -->
                    <div class="flex flex-col md:flex-row md:items-center gap-4 w-full md:w-auto">
                        <?php if($is_filtering): ?>
                        <a href="<?= strtok($_SERVER['REQUEST_URI'], '?'); ?>" class="inline-flex items-center justify-center gap-2 px-5 py-3 bg-red-100 hover:bg-red-200 text-red-700 font-bold rounded-lg border border-red-200 transition-all shadow-sm w-full md:w-auto">
                            <i class="fas fa-undo text-xs"></i>
                            <span>Reset</span>
                        </a>
                        <?php endif; ?>

                        <!-- Advanced Date Filter Dropdown -->
                        <div class="relative w-full md:w-auto">
                            <button type="button" onclick="toggleFilterDropdown('filter_date')" class="inline-flex items-center justify-center gap-2 px-5 py-3 <?= $is_filtering ? 'bg-teal-600 text-white' : 'bg-slate-100 text-slate-700'; ?> hover:bg-teal-600 hover:text-white font-bold rounded-lg shadow-sm transition-all w-full md:min-w-[180px]">
                                <i class="fas fa-calendar-alt"></i>
                                <span><?= $filter_label; ?></span>
                                <i class="fas fa-chevron-down text-xs"></i>
                            </button>
                            
                            <div id="filter_date" class="hidden absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-2xl border border-slate-200 z-[110] overflow-hidden text-left">
                                <div id="preset-filters-list" class="max-h-60 overflow-y-auto">
                                    <?php
                                    $base_url = strtok($_SERVER['REQUEST_URI'], '?');
                                    $presets = [
                                        ['value' => 'today', 'label' => 'Today'],
                                        ['value' => 'tomorrow', 'label' => 'Tomorrow'],
                                        ['value' => 'yesterday', 'label' => 'Yesterday'],
                                        ['value' => '3_days', 'label' => '3 Day(s)'],
                                        ['value' => '1_week', 'label' => '1 Week(s)'],
                                        ['value' => '1_month', 'label' => '1 Month(s)'],
                                        ['value' => '3_months', 'label' => '3 Month(s)'],
                                        ['value' => '6_months', 'label' => '6 Month(s)'],
                                    ];
                                    foreach($presets as $p):
                                        $act = ($date_filter === $p['value']);
                                    ?>
                                        <a href="<?= $base_url; ?>?date_filter=<?= $p['value']; ?>" class="block w-full px-4 py-3 text-sm text-slate-700 hover:bg-slate-50 transition-colors <?= $act ? 'bg-slate-50 font-semibold' : 'font-medium'; ?>">
                                            <?= $p['label']; ?>
                                        </a>
                                    <?php endforeach; ?>
                                    
                                    <button type="button" onclick="event.stopPropagation(); showCustomCalendar()" class="block w-full px-4 py-3 text-sm text-teal-600 hover:bg-slate-50 transition-colors text-left font-semibold border-t border-slate-50">
                                        Custom Range
                                    </button>

                                    <?php if($is_filtering): ?>
                                    <a href="<?= $base_url; ?>" class="block w-full px-4 py-3 text-sm text-red-600 hover:bg-red-50 transition-colors font-semibold border-t border-slate-100">
                                        <i class="fas fa-times-circle mr-2"></i>Clear Filter
                                    </a>
                                    <?php endif; ?>
                                </div>
                                <div id="custom-calendar-picker" class="hidden border-t border-slate-200 bg-white p-4">
                                     <!-- Selected Range Display -->
                                     <div class="mb-3 p-3 bg-slate-50 rounded-lg text-center">
                                         <div class="text-sm font-semibold text-slate-700" id="calendar-selected-range">
                                             Select date range
                                         </div>
                                     </div>
                                     <!-- Calendar Header -->
                                     <div class="flex items-center justify-between mb-3">
                                         <button type="button" onclick="changeCalendarMonth(-1)" class="p-2 hover:bg-slate-100 rounded transition"><i class="fas fa-chevron-left text-slate-600"></i></button>
                                         <div class="relative">
                                             <button type="button" onclick="toggleYearMonthSelector()" class="font-bold text-slate-800 uppercase text-sm px-3 py-1 hover:bg-slate-100 rounded transition" id="calendar-month-display"></button>
                                             <div id="year-month-selector" class="hidden absolute top-full left-1/2 transform -translate-x-1/2 mt-2 bg-white border border-slate-200 rounded-lg shadow-xl z-50 p-3 w-64">
                                                 <div class="mb-3"><label class="text-xs font-semibold text-slate-500 mb-1 block">Year</label><select id="year-select" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-teal-500"></select></div>
                                                 <div class="mb-3"><label class="text-xs font-semibold text-slate-500 mb-1 block">Month</label><select id="month-select" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-teal-500"><option value="0">January</option><option value="1">February</option><option value="2">March</option><option value="3">April</option><option value="4">May</option><option value="5">June</option><option value="6">July</option><option value="7">August</option><option value="8">September</option><option value="9">October</option><option value="10">November</option><option value="11">December</option></select></div>
                                                 <button type="button" onclick="applyYearMonthSelection()" class="w-full px-4 py-2 bg-teal-600 text-white rounded-lg text-sm font-semibold hover:bg-teal-700 transition-all">Apply</button>
                                             </div>
                                         </div>
                                         <button type="button" onclick="changeCalendarMonth(1)" class="p-2 hover:bg-slate-100 rounded transition"><i class="fas fa-chevron-right text-slate-600"></i></button>
                                     </div>
                                     <div class="grid grid-cols-7 gap-y-1 mb-3">
                                         <div class="text-center text-xs font-semibold text-slate-500 py-2">S</div><div class="text-center text-xs font-semibold text-slate-500 py-2">M</div><div class="text-center text-xs font-semibold text-slate-500 py-2">T</div><div class="text-center text-xs font-semibold text-slate-500 py-2">W</div><div class="text-center text-xs font-semibold text-slate-500 py-2">T</div><div class="text-center text-xs font-semibold text-slate-500 py-2">F</div><div class="text-center text-xs font-semibold text-slate-500 py-2">S</div>
                                     </div>
                                     <div id="calendar-grid" class="grid grid-cols-7 gap-y-1"></div>
                                </div>
                            </div>
                        </div>

                        <a href="/pos/installment/list" class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-teal-600 hover:bg-teal-700 text-white font-bold rounded-xl transition-all shadow-md w-full md:w-auto">
                            <i class="fas fa-list"></i>
                            <span>View List</span>
                        </a>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
                    <!-- Total Receivable Card (Blue) -->
                    <div class="bg-gradient-to-br from-sky-500 to-blue-600 rounded-[2rem] p-8 shadow-xl shadow-sky-200/50 relative overflow-hidden group">
                        <div class="relative z-10 text-white">
                            <div class="flex items-center gap-4 mb-4">
                                <div class="w-12 h-12 bg-white/20 backdrop-blur-md rounded-2xl flex items-center justify-center">
                                    <i class="fas fa-hand-holding-usd text-xl"></i>
                                </div>
                                <span class="text-sm font-black uppercase tracking-widest opacity-80">Total Receivable</span>
                            </div>
                            <div class="text-2xl xl:text-3xl font-black mb-1 whitespace-nowrap tracking-tight"><?= $currency ?><?= number_format($stats['total_receivable'], 2) ?></div>
                            <div class="text-xs font-bold opacity-70">Payable amount</div>
                        </div>
                        <div class="absolute -right-4 -bottom-4 text-white/5 text-8xl font-black italic group-hover:scale-110 transition-transform duration-500">LOAN</div>
                    </div>

                    <!-- Total Received Card (Green) -->
                    <div class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-[2rem] p-8 shadow-xl shadow-emerald-200/50 relative overflow-hidden group">
                        <div class="relative z-10 text-white">
                            <div class="flex items-center gap-4 mb-4">
                                <div class="w-12 h-12 bg-white/20 backdrop-blur-md rounded-2xl flex items-center justify-center">
                                    <i class="fas fa-check-circle text-xl"></i>
                                </div>
                                <span class="text-sm font-black uppercase tracking-widest opacity-80">Total Received</span>
                            </div>
                            <div class="text-2xl xl:text-3xl font-black mb-1 whitespace-nowrap tracking-tight"><?= $currency ?><?= number_format($stats['total_received'], 2) ?></div>
                            <div class="text-xs font-bold opacity-70">Amount collected</div>
                        </div>
                        <div class="absolute -right-4 -bottom-4 text-white/5 text-8xl font-black italic group-hover:scale-110 transition-transform duration-500">PAID</div>
                    </div>

                    <!-- Total Due Card (Red) -->
                    <div class="bg-gradient-to-br from-rose-500 to-pink-600 rounded-[2rem] p-8 shadow-xl shadow-rose-200/50 relative overflow-hidden group">
                        <div class="relative z-10 text-white">
                            <div class="flex items-center gap-4 mb-4">
                                <div class="w-12 h-12 bg-white/20 backdrop-blur-md rounded-2xl flex items-center justify-center">
                                    <i class="fas fa-exclamation-triangle text-xl"></i>
                                </div>
                                <span class="text-sm font-black uppercase tracking-widest opacity-80">Total Due</span>
                            </div>
                            <div class="text-2xl xl:text-3xl font-black mb-1 whitespace-nowrap tracking-tight"><?= $currency ?><?= number_format($stats['total_due'], 2) ?></div>
                            <div class="text-xs font-bold opacity-70">Remaining balance</div>
                        </div>
                        <div class="absolute -right-4 -bottom-4 text-white/5 text-8xl font-black italic group-hover:scale-110 transition-transform duration-500">DUE</div>
                    </div>

                    <!-- Total Orders Card (White) -->
                    <div class="bg-white rounded-[2rem] p-8 shadow-xl border border-slate-100 relative overflow-hidden group">
                        <div class="relative z-10">
                            <div class="flex items-center gap-4 mb-4">
                                <div class="bg-amber-100 text-amber-600 w-12 h-12 rounded-2xl flex items-center justify-center shadow-inner">
                                    <i class="fas fa-file-invoice-dollar text-xl"></i>
                                </div>
                                <span class="text-sm font-black uppercase tracking-widest text-slate-400">Total Orders</span>
                            </div>
                            <div class="text-2xl lg:text-3xl font-black mb-1 text-slate-800 break-words leading-tight"><?= number_format($stats['total_count']) ?></div>
                            <div class="text-xs font-bold text-slate-400 italic">Active installment plans</div>
                        </div>
                        <div class="absolute -right-4 -bottom-4 text-slate-50 text-8xl font-black italic group-hover:scale-110 transition-transform duration-500">COUNT</div>
                    </div>
                </div>

                <!-- Recent Payments Table -->
                <div class="bg-white rounded-xl shadow-xl border border-slate-100 overflow-hidden">
                    <div class="p-6 border-b border-slate-100 bg-teal-50/30 flex items-center justify-between">
                        <h3 class="font-black text-slate-900 text-lg flex items-center gap-2">
                            <span class="w-2 h-8 bg-teal-500 rounded-full"></span>
                            Recent Payments
                        </h3>
                        <span class="bg-teal-100 text-teal-700 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider"><?= count($payments) ?> Entries</span>
                    </div>
                    <div class="flex-1 overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-widest font-bold border-b border-slate-100">
                                    <th class="p-4">ID</th>
                                    <th class="p-4">Date</th>
                                    <th class="p-4">Customer</th>
                                    <th class="p-4">Invoice</th>
                                    <th class="p-4">Note</th>
                                    <th class="p-4">Created By</th>
                                    <th class="p-4 text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <?php if(empty($payments)): ?>
                                    <tr><td colspan="7" class="p-8 text-center text-slate-400 italic font-medium">No payment records found</td></tr>
                                <?php else: ?>
                                    <?php $i=1; foreach($payments as $p): ?>
                                        <tr class="hover:bg-slate-50 transition-colors">
                                            <td class="p-4 text-sm font-bold text-slate-600"><?= $i++ ?></td>
                                            <td class="p-4 text-sm font-bold text-slate-600 whitespace-nowrap">
                                                <?= date('d M Y', strtotime($p['payment_date'])) ?>
                                                <div class="text-[10px] text-slate-400 font-medium"><?= isset($p['created_at']) ? date('h:i A', strtotime($p['created_at'])) : '' ?></div>
                                            </td>
                                            <td class="p-4 text-sm font-bold text-slate-800"><?= htmlspecialchars($p['customer_name'] ?? 'N/A') ?></td>
                                            <td class="p-4 text-sm">
                                                 <a href="/pos/installment/list?search=<?= $p['invoice_id'] ?>" class="font-bold text-indigo-600 hover:text-indigo-800 bg-indigo-50 px-2 py-1 rounded text-xs transition-colors"><?= $p['invoice_id'] ?></a>
                                            </td>
                                            <td class="p-4 text-sm text-slate-500 italic"><?= htmlspecialchars($p['note'] ?? '-') ?></td>
                                            <td class="p-4 text-sm font-medium text-slate-600"><?= $p['created_by_name'] ?: 'System' ?></td>
                                            <td class="p-4 text-sm font-black text-teal-600 text-right"><?= $currency ?><?= number_format($p['paid'], 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                </div>
                <?php include('../includes/footer.php'); ?>
            </div>
        </div>
    </main>
</div>

<script>
let calendarCurrentDate = new Date();
let calendarStartDate = '<?= $start_date ?>';
let calendarEndDate = '<?= $end_date ?>';

function toggleFilterDropdown(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.toggle('hidden');
    event.stopPropagation();
}

function showCustomCalendar() {
    document.getElementById('preset-filters-list').classList.add('hidden');
    document.getElementById('custom-calendar-picker').classList.remove('hidden');
    renderCustomCalendar();
    populateYearSelector();
}

function populateYearSelector() {
    const yearSelect = document.getElementById('year-select');
    if (!yearSelect) return;
    const currentYear = new Date().getFullYear();
    yearSelect.innerHTML = '';
    for (let i = currentYear - 5; i <= currentYear + 1; i++) {
        const option = document.createElement('option');
        option.value = i; option.textContent = i;
        if (i === calendarCurrentDate.getFullYear()) option.selected = true;
        yearSelect.appendChild(option);
    }
    document.getElementById('month-select').value = calendarCurrentDate.getMonth();
}

function toggleYearMonthSelector() { document.getElementById('year-month-selector').classList.toggle('hidden'); }

function applyYearMonthSelection() {
    const year = parseInt(document.getElementById('year-select').value);
    const month = parseInt(document.getElementById('month-select').value);
    calendarCurrentDate = new Date(year, month, 1);
    renderCustomCalendar();
    document.getElementById('year-month-selector').classList.add('hidden');
}

function changeCalendarMonth(direction) {
    calendarCurrentDate.setMonth(calendarCurrentDate.getMonth() + direction);
    renderCustomCalendar();
}

function renderCustomCalendar() {
    const year = calendarCurrentDate.getFullYear();
    const month = calendarCurrentDate.getMonth();
    const monthNames = ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'];
    document.getElementById('calendar-month-display').textContent = `${monthNames[month]} ${year}`;
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const grid = document.getElementById('calendar-grid');
    grid.innerHTML = '';
    for (let i = 0; i < firstDay; i++) grid.appendChild(document.createElement('div'));
    for (let day = 1; day <= daysInMonth; day++) {
        const wrapper = document.createElement('div');
        const btn = document.createElement('button');
        btn.textContent = day;
        const currentDate = new Date(year, month, day);
        const dateStr = formatCalendarDate(currentDate);
        let btnCls = 'w-9 h-9 flex items-center justify-center text-sm z-10 relative transition-colors duration-200 ';
        let wrapCls = 'flex items-center justify-center w-full relative h-9 ';
        if (calendarStartDate && calendarEndDate) {
            const start = new Date(calendarStartDate); const end = new Date(calendarEndDate);
            if (currentDate > start && currentDate < end) { wrapCls += 'bg-teal-100'; btnCls += 'text-teal-900 font-medium'; }
            else if (dateStr === formatCalendarDate(start)) { wrapper.style.background = 'linear-gradient(to right, transparent 50%, #ccfbf1 50%)'; btnCls += 'bg-teal-600 text-white font-bold rounded-full'; }
            else if (dateStr === formatCalendarDate(end)) { wrapper.style.background = 'linear-gradient(to left, transparent 50%, #ccfbf1 50%)'; btnCls += 'bg-teal-600 text-white font-bold rounded-full'; }
            else { btnCls += 'rounded-full hover:bg-slate-100 text-slate-700'; }
        } else if (calendarStartDate && dateStr === formatCalendarDate(new Date(calendarStartDate))) { btnCls += 'bg-teal-600 text-white font-bold rounded-full'; }
        else { btnCls += 'rounded-full hover:bg-slate-100 text-slate-700'; }
        
        if (calendarStartDate && calendarEndDate && calendarStartDate === calendarEndDate && dateStr === formatCalendarDate(new Date(calendarStartDate))) wrapper.style.background = 'transparent';

        btn.className = btnCls; wrapper.className = wrapCls;
        btn.onclick = (e) => { e.stopPropagation(); selectCalendarDateRange(currentDate); };
        wrapper.appendChild(btn); grid.appendChild(wrapper);
    }
    updateCalendarRangeDisplay();
}

function selectCalendarDateRange(date) {
    if (!calendarStartDate || (calendarStartDate && calendarEndDate)) { calendarStartDate = formatCalendarDate(date); calendarEndDate = null; renderCustomCalendar(); }
    else {
        const start = new Date(calendarStartDate);
        if (date < start) { calendarEndDate = calendarStartDate; calendarStartDate = formatCalendarDate(date); }
        else { calendarEndDate = formatCalendarDate(date); }
        renderCustomCalendar();
        setTimeout(() => { window.location.href = `${window.location.pathname}?start_date=${calendarStartDate}&end_date=${calendarEndDate}`; }, 300);
    }
}

function updateCalendarRangeDisplay() {
    const disp = document.getElementById('calendar-selected-range');
    if (calendarStartDate && calendarEndDate) disp.textContent = `${formatCalendarDisplayDate(new Date(calendarStartDate))} - ${formatCalendarDisplayDate(new Date(calendarEndDate))}`;
    else if (calendarStartDate) disp.textContent = 'Select end date...';
    else disp.textContent = 'Select date range';
}

function formatCalendarDate(date) { return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`; }
function formatCalendarDisplayDate(date) { const m = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']; return `${String(date.getDate()).padStart(2, '0')} ${m[date.getMonth()]} ${date.getFullYear()}`; }

document.addEventListener('click', function(e) {
    const filters = document.getElementById('filter_date');
    const ym = document.getElementById('year-month-selector');
    if (filters && !filters.contains(e.target) && !e.target.closest('[onclick*="toggleFilterDropdown"]')) {
        filters.classList.add('hidden');
        document.getElementById('preset-filters-list')?.classList.remove('hidden');
        document.getElementById('custom-calendar-picker')?.classList.add('hidden');
    }
    if (ym && !ym.classList.contains('hidden') && !e.target.closest('#year-month-selector') && !e.target.closest('#calendar-month-display')) ym.classList.add('hidden');
});
</script>
