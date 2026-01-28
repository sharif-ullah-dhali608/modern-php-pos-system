<?php
session_start();
include('../config/dbcon.php');
include('../includes/reusable_list.php');

if(!isset($_SESSION['auth'])){
    header("Location: /pos/login");
    exit(0);
}

include('../includes/header.php');

// Simple Filters logic
$where = "WHERE 1=1";
if(isset($_GET['store_id']) && !empty($_GET['store_id'])) {
    $sid = (int)$_GET['store_id'];
    $where .= " AND e.store_id='$sid'";
}
if(isset($_GET['category_id']) && !empty($_GET['category_id'])) {
    $cid = (int)$_GET['category_id'];
    $where .= " AND e.category_id='$cid'";
}

// Fetch Expenses with joins
$query = "SELECT e.*, c.category_name, s.store_name 
          FROM expenses e 
          JOIN expense_category c ON e.category_id = c.category_id 
          JOIN stores s ON e.store_id = s.id 
          $where 
          ORDER BY e.created_at DESC";
$result = mysqli_query($conn, $query);

$data = [];
$total_amount = 0;
$today_amount = 0;
$month_amount = 0;
$category_totals = [];

$current_date = date('Y-m-d');
$current_month = date('Y-m');

while($row = mysqli_fetch_assoc($result)) {
    $amt = $row['amount'];
    $date = date('Y-m-d', strtotime($row['created_at']));
    $month = date('Y-m', strtotime($row['created_at']));
    $cat_name = $row['category_name'];

    $total_amount += $amt;
    $data[] = $row;

    // Calculate Today's Expense
    if($date == $current_date) {
        $today_amount += $amt;
    }

    // Calculate This Month's Expense
    if($month == $current_month) {
        $month_amount += $amt;
    }

    // Track Category Totals
    if(!isset($category_totals[$cat_name])) {
        $category_totals[$cat_name] = 0;
    }
    $category_totals[$cat_name] += $amt;
}

// Find Top Category
$top_category = 'N/A';
$top_category_amount = 0;
if(!empty($category_totals)) {
    arsort($category_totals);
    $top_category = array_key_first($category_totals);
    $top_category_amount = $category_totals[$top_category];
}

// Prepare Store Filters
$stores_res = mysqli_query($conn, "SELECT id, store_name FROM stores WHERE status='1'");
$store_opts = [['label' => 'All Stores', 'url' => '/pos/expenditure/expense_list']];
while($st = mysqli_fetch_assoc($stores_res)) {
    $store_opts[] = [
        'label' => $st['store_name'], 
        'url' => '/pos/expenditure/expense_list?store_id='.$st['id'],
        'active' => (isset($_GET['store_id']) && $_GET['store_id'] == $st['id'])
    ];
}

// Resolve Dynamic Currency Symbol
$currency_symbol = ''; // Default for "All Stores" (No symbol)
if(isset($_GET['store_id']) && !empty($_GET['store_id'])) {
    $sid = (int)$_GET['store_id'];
    $curr_q = mysqli_query($conn, "SELECT c.symbol_left, c.symbol_right FROM stores s JOIN currencies c ON s.currency_id = c.id WHERE s.id='$sid'");
    if($curr_row = mysqli_fetch_assoc($curr_q)) { 
        $currency_symbol = $curr_row['symbol_left'] ?: $curr_row['symbol_right']; 
        $currency_symbol .= ' '; // Add space after symbol
    }
}

$config = [
    'title' => 'Expenditures',
    'table_id' => 'expenseTable',
    'primary_key' => 'id',
    'name_field' => 'title',
    'add_url' => '/pos/expenditure/expense_add',
    'edit_url' => '/pos/expenditure/expense_edit',
    'delete_url' => '/pos/expenditure/save_expense',
    'summary_cards' => [
        ['label' => 'Total Expenditure', 'value' => $currency_symbol . number_format($total_amount, 2), 'border_color' => 'border-rose-500'],
        ['label' => 'Today\'s Expense', 'value' => $currency_symbol . number_format($today_amount, 2), 'border_color' => 'border-emerald-500'],
        ['label' => 'This Month', 'value' => $currency_symbol . number_format($month_amount, 2), 'border_color' => 'border-blue-500'],
        ['label' => 'Top Category', 'value' => $top_category, 'border_color' => 'border-violet-500']
    ],
    'filters' => [
        ['id' => 'filter_store', 'label' => 'Store', 'options' => $store_opts]
    ],
    'columns' => [
        ['label' => 'Date', 'key' => 'created_at'],
        ['label' => 'Ref No', 'key' => 'reference_no'],
        ['label' => 'Store', 'key' => 'store_name'],
        ['label' => 'Category', 'key' => 'category_name'],
        ['label' => 'Title', 'key' => 'title'],
        ['label' => 'Amount', 'key' => 'amount'],
        ['label' => 'Actions', 'key' => 'actions', 'type' => 'actions']
    ],
    'data' => $data
];
?>

<div class="app-wrapper">
    <?php include('../includes/sidebar.php'); ?>
    
    <main id="main-content" class="flex-1 lg:ml-64 flex flex-col h-screen min-w-0 transition-all duration-300">
        <div class="navbar-fixed-top"><?php include('../includes/navbar.php'); ?></div> 
        
        <div class="content-scroll-area custom-scroll h-full overflow-y-auto">
            <div class="p-6">
                <?php renderReusableList($config); ?>
            </div>
        </div>
    </main>
</div>

<!-- Select2 Resources -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="/pos/assets/css/expenditureCss/select2_custom.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- Custom Store Selector for Injection -->
<div id="custom-store-selector-wrapper" class="hidden">
    <div class="relative w-full md:w-[200px]">
        <select id="store-select-list" class="w-full">
            <option value="">All Stores</option>
            <?php 
            // Reuse the stores fetched earlier
            mysqli_data_seek($stores_res, 0); // Reset pointer
            while($st = mysqli_fetch_assoc($stores_res)): 
                $selected = (isset($_GET['store_id']) && $_GET['store_id'] == $st['id']) ? 'selected' : '';
            ?>
                <option value="<?= $st['id'] ?>" <?= $selected ?>><?= htmlspecialchars($st['store_name']) ?></option>
            <?php endwhile; ?>
        </select>
    </div>
</div>

<script>
$(document).ready(function() {
    // 1. Inject the selector by replacing the existing Store filter placeholder
    // This ensures perfect positioning inside the reusable list's filter bar
    
    // Find the button that toggles 'filter_store'
    var $placeholderBtn = $('button[onclick*="filter_store"]');
    
    if ($placeholderBtn.length > 0) {
        var $container = $placeholderBtn.parent(); // The relative wrapper div
        var $wrapper = $('#custom-store-selector-wrapper').children();
        
        // Clear the container (remove the default button and dropdown)
        $container.empty();
        
        // Append our Select2 wrapper
        $container.append($wrapper);
        
        // Styling fixes to match the height
        $wrapper.addClass('w-full md:w-auto');
        $wrapper.find('.select2-container').css('width', '100%');
        
        // On mobile, ensure it takes full width if needed
        if($(window).width() < 768) {
            $wrapper.removeClass('md:w-[200px]').addClass('w-full');
        }
    } else {
        // Fallback if filter not found (should not happen if config is correct)
        console.error("Store filter placeholder not found");
        // Try appending to the main container as last resort
        var $mainContainer = $('.flex.flex-wrap.items-center.gap-2.md\\:gap-3').last();
        if ($mainContainer.length > 0) {
            var $wrapper = $('#custom-store-selector-wrapper').children();
            $mainContainer.append($wrapper);
        }
    }

    // 2. Initialize Select2 with ROBUST matcher logic (Same as expense_monthwise)
    function initSelect2() {
        // Build data array with original index for filtering
        var storesData = [];
        $('#store-select-list option').each(function(index) {
            storesData.push({
                id: $(this).val(),
                text: $(this).text(),
                originalIndex: index // Keep track of position (0=All, 1-5=Visible, 6+=Hidden)
            });
        });

        // Destroy if already initialized
        if ($('#store-select-list').hasClass("select2-hidden-accessible")) {
             $('#store-select-list').select2('destroy');
        }

        $('#store-select-list').select2({
            placeholder: 'All Stores',
            allowClear: true,
            width: '100%',
            minimumResultsForSearch: 0,
            theme: 'default',
            dropdownCssClass: 'select2-custom-dropdown',
            data: storesData,
            // Custom matcher logic: Show only first 6 if no search, else show all matches
            matcher: function(params, data) {
                // If there IS a search term, use default fuzzy search
                if ($.trim(params.term) !== '') {
                    if (data.text.toUpperCase().indexOf(params.term.toUpperCase()) > -1) {
                        return data;
                    }
                    return null;
                }

                // If NO search term, only return items with index <= 5
                // (Index 0 is "All Stores", 1-5 represent the first 5 stores)
                if (data.originalIndex <= 5) {
                    return data;
                }

                // Hide everything else
                return null;
            }
        });

        // Handle Change -> Redirect
        $('#store-select-list').on('select2:select', function(e) {
             var val = e.params.data.id;
             var url = '/pos/expenditure/expense_list';
             if (val) {
                 url += '?store_id=' + val;
             }
             window.location.href = url;
        });
        
        $('#store-select-list').on('select2:unselect', function(e) {
             window.location.href = '/pos/expenditure/expense_list';
        });
    }

    initSelect2();
});
</script>

<?php include('../includes/footer.php'); ?>
