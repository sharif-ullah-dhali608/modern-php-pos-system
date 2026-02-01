<?php
/**
 * Reusable List Renderer (Cleaned & Integrated with Footer Swal)
 */
function renderReusableList($config) {
    // -----------------------------------------
    // Configuration Setup
    // -----------------------------------------
    $title       = $config['title'] ?? 'List';
    $table_id    = $config['table_id'] ?? 'dataTable_' . uniqid();
    $columns     = $config['columns'] ?? [];
    $data        = $config['data'] ?? [];
    
    // URL Configurations
    $add_url     = $config['add_url'] ?? '#';
    $edit_url    = $config['edit_url'] ?? '#';
    $delete_url  = $config['delete_url'] ?? '#';
    $status_url  = $config['status_url'] ?? '#';
    $view_url    = $config['view_url'] ?? null;
    
    // Key Fields
    $primary_key = $config['primary_key'] ?? 'id';
    $name_field  = $config['name_field'] ?? 'name';
    
    // New Features
    $extra_buttons = $config['extra_buttons'] ?? [];
    $filters = $config['filters'] ?? [];
    $summary_cards = $config['summary_cards'] ?? [];
    
    // Action Buttons Control: array of allowed actions ['view', 'edit', 'delete']
    // Default: all buttons if URLs are provided
    $action_buttons = $config['action_buttons'] ?? ['view', 'edit', 'delete'];
    ?>

    <style>
        .slide-in { animation: slideIn 0.3s ease-out forwards; }
        @keyframes slideIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        
        /* Hide extra filter options initially, show on search */
        .hidden-initially { display: none !important; }
        
        /* Ultra-Premium 480px Advanced Mobile Design */
        @media (max-width: 1023px) {
            #sidebar { display: block !important; }
        }
        @media (max-width: 480px) {
            .data-table, .data-table thead, .data-table tbody, .data-table th, .data-table td, .data-table tr { 
                display: block; 
                width: 100%;
            }
            .data-table thead { display: none; }
            
            .data-table tbody {
                display: flex;
                flex-direction: column;
                gap: 16px;
                padding: 10px 0;
            }

            .data-table tbody tr {
                background: #ffffff;
                border: 1px solid #f1f5f9;
                border-radius: 20px;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
                overflow: hidden;
                display: grid;
                grid-template-columns: 1fr 1fr;
                padding-bottom: 0;
            }
            
            /* Primary Info Block (Spans full width) */
            .data-table td.mobile-primary {
                grid-column: span 2;
                background: #f8fafc;
                border-bottom: 1px solid #f1f5f9;
                padding: 16px !important;
                display: flex;
                align-items: center;
                gap: 12px;
                text-align: left;
            }
            .data-table td.mobile-primary::before { display: none; }
            .data-table td.mobile-primary > span { font-weight: 800; font-size: 1rem; color: #1e293b; }

            /* Grid Detail Cells */
            .data-table td {
                padding: 12px 16px !important;
                border-bottom: 1px solid #f8fafc;
                display: flex;
                flex-direction: column;
                align-items: flex-start;
                justify-content: center;
                text-align: left;
                min-height: 60px;
            }

            .data-table td::before {
                content: attr(data-label);
                font-weight: 700;
                text-transform: uppercase;
                font-size: 0.6rem;
                color: #94a3b8;
                letter-spacing: 0.05em;
                margin-bottom: 4px;
            }

            /* Action area styling */
            .data-table td.mobile-actions {
                grid-column: span 2;
                border-bottom: none;
                background: #fdfdfd;
                padding: 16px !important;
                flex-direction: row;
                justify-content: space-around;
                align-items: center;
                border-top: 1px solid #f1f5f9;
            }
            .data-table td.mobile-actions::before { display: none; }

            /* Image styling in mobile primary */
            .data-table td.mobile-primary img {
                width: 50px;
                height: 50px;
                border-radius: 12px;
                box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            }
        }

        /* DataTables Controls Premium Styling */
        .dataTables_wrapper .dataTables_length, 
        .dataTables_wrapper .dataTables_filter {
            margin-bottom: 20px;
        }

        /* Hide native search icons in WebKit browsers (Chrome, Safari) to prevent double icon */
        input[type="search"]::-webkit-search-decoration,
        input[type="search"]::-webkit-search-cancel-button,
        input[type="search"]::-webkit-search-results-button,
        input[type="search"]::-webkit-search-results-decoration {
            display: none;
        }

        .dataTables_wrapper .dataTables_length select,
        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #e2e8f0 !important;
            border-radius: 12px !important;
            padding: 10px 14px 10px 40px !important; /* Restored padding for icon */
            outline: none !important;
            transition: all 0.2s !important;
            background: #ffffff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%230d9488' stroke-width='3'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z'%3E%3C/path%3E%3C/svg%3E") no-repeat !important; /* Restored Icon */
            background-size: 18px !important;
            background-position: 14px center !important;
            font-size: 14px !important;
            color: #475569 !important;
        }

        .dataTables_wrapper .dataTables_length select:focus,
        .dataTables_wrapper .dataTables_filter input:focus {
            border-color: #0d9488 !important;
            box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.1) !important;
        }

        .dataTables_wrapper .dataTables_filter label,
        .dataTables_wrapper .dataTables_length label {
            font-weight: 700 !important;
            color: #64748b !important;
            text-transform: uppercase !important;
            font-size: 11px !important;
            letter-spacing: 0.025em !important;
            display: flex !important;
            align-items: center !important;
            gap: 8px !important;
        }

        @media (max-width: 480px) {
            .dataTables_wrapper .dataTables_length, 
            .dataTables_wrapper .dataTables_filter {
                width: 100% !important;
                text-align: center !important;
                margin-bottom: 20px !important;
            }
            
            /* Entries Dropdown: Single Line */
            .dataTables_wrapper .dataTables_length label {
                width: 100% !important;
                flex-direction: row !important;
                justify-content: center !important;
                align-items: center !important;
                gap: 12px !important;
            }
            .dataTables_wrapper .dataTables_length select {
                width: 100px !important;
                margin: 0 !important;
            }

            /* Search Field: Stays Full Width */
            .dataTables_wrapper .dataTables_filter label {
                width: 100% !important;
                flex-direction: column !important;
                align-items: center !important;
                gap: 6px !important;
            }
            .dataTables_wrapper .dataTables_filter input {
                width: 100% !important;
                margin-left: 0 !important;
                /* Keep search icon for mobile as requested */
                padding: 10px 14px 10px 40px !important;
                background: #ffffff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%230d9488' stroke-width='3'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z'%3E%3C/path%3E%3C/svg%3E") no-repeat !important;
                background-size: 18px !important;
                background-position: 14px center !important;
            }
       }

        /* Large Screen Table Scroll Fallback */
        @media (min-width: 481px) and (max-width: 1024px) {
            .data-table-container {
                width: 100%;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            .data-table {
                min-width: 900px !important;
            }
            .w-full.slide-in button, 
            .w-full.slide-in a {
                padding-left: 12px !important;
                padding-right: 12px !important;
                font-size: 13px !important;
            }
            .flex-wrap.items-center.gap-2.md\:gap-3 {
                gap: 8px !important;
            }
        }
    </style>

    <div class="w-full slide-in">
        
        <div class="w-full animate-fade-in">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-3xl font-black text-slate-800 mb-1"><?= htmlspecialchars($title); ?></h1>
                <div class="flex items-center gap-2 text-xs font-bold text-slate-400 uppercase tracking-widest">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Total <?= count($data); ?> entries</span>
                </div>
            </div>
            
            <div class="flex flex-wrap items-center gap-2 md:gap-3 w-full lg:w-auto lg:justify-end">
                <div class="relative w-full md:w-auto">
                    <button type="button" onclick="toggleExportDropdown()" class="w-full md:w-auto inline-flex items-center justify-center gap-2 px-5 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-lg border border-slate-300 transition-all">
                        <i class="fas fa-upload rotate-180"></i>
                        <span>Export</span>
                        <i class="fas fa-chevron-down text-xs"></i>
                    </button>
                    <div id="exportDropdown" class="hidden absolute right-0 mt-2 w-40 bg-white rounded-lg shadow-xl border border-slate-200 z-50 overflow-hidden">
                        <button onclick="triggerDtAction('print')" class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition-colors">
                            <i class="fas fa-print w-4 text-slate-500"></i> Print
                        </button>
                        <button onclick="triggerDtAction('csv')" class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition-colors">
                            <i class="fas fa-file-csv w-4 text-slate-500"></i> Csv
                        </button>
                        <button onclick="triggerDtAction('excel')" class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition-colors">
                            <i class="fas fa-file-excel w-4 text-slate-500"></i> Excel
                        </button>
                        <button onclick="triggerDtAction('pdf')" class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition-colors">
                            <i class="fas fa-file-pdf w-4 text-slate-500"></i> Pdf
                        </button>
                        <button onclick="triggerDtAction('copy')" class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition-colors">
                            <i class="fas fa-copy w-4 text-slate-500"></i> Copy
                        </button>
                    </div>
                </div>

                <?php if($add_url !== '#'): ?>
                <a href="<?= $add_url; ?>" class="w-full md:w-auto inline-flex items-center justify-center gap-2 px-5 py-3 bg-teal-600 hover:bg-teal-700 text-white font-bold rounded-lg shadow-lg transition-all transform hover:-translate-y-0.5">
                    <i class="fas fa-plus"></i>
                    <span>Add New</span>
                </a>
                <?php endif; ?>
                
                <?php // Extra Buttons (Pay All, etc.)
                foreach($extra_buttons as $btn): ?>
                    <button type="button" onclick="<?= $btn['onclick'] ?? ''; ?>" class="<?= $btn['class'] ?? 'inline-flex items-center gap-2 px-5 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg shadow transition-all'; ?> w-full md:w-auto inline-flex items-center justify-center gap-2">
                        <?php if(isset($btn['icon'])): ?><i class="<?= $btn['icon']; ?>"></i><?php endif; ?>
                        <span><?= $btn['label']; ?></span>
                    </button>
                <?php endforeach; ?>
                
                <?php // Filter Dropdowns
                // Determine if any filter is active for Global Reset
                $is_any_filter_active = false;
                if(isset($config['date_column'])) {
                    if(isset($_GET['date_filter']) || isset($_GET['start_date']) || isset($_GET['end_date'])) $is_any_filter_active = true;
                }
                foreach($filters as $f) {
                    if(isset($f['name']) && isset($_GET[$f['name']]) && $_GET[$f['name']] !== '') $is_any_filter_active = true;
                }

                if($is_any_filter_active): ?>
                    <a href="<?= strtok($_SERVER['REQUEST_URI'], '?'); ?>" class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-rose-50 hover:bg-rose-100 text-rose-600 font-bold rounded-lg border border-rose-200 transition-all shadow-sm">
                        <i class="fas fa-undo text-xs"></i>
                        <span>Reset</span>
                    </a>
                <?php endif; ?>

                <?php foreach($filters as $filter): 
                    // Determine active filter label
                    $filter_button_label = $filter['label'];
                    $is_filter_active = false;
                    
                    foreach($filter['options'] as $opt) {
                        if(isset($opt['active']) && $opt['active']) {
                            $filter_button_label = $opt['label'];
                            $is_filter_active = true;
                            break;
                        }
                    }
                ?>
                    <div class="relative w-full md:w-auto">
                        <button type="button" onclick="toggleFilterDropdown('<?= $filter['id']; ?>')" class="inline-flex items-center justify-center gap-2 px-5 py-3 <?= $is_filter_active ? 'bg-teal-600 text-white' : 'bg-slate-100 text-slate-700'; ?> hover:bg-teal-700 hover:text-white font-bold rounded-lg shadow transition-all w-full md:w-auto">
                            <i class="fas fa-filter"></i>
                            <span><?= $filter_button_label; ?></span>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>
                        <div id="<?= $filter['id']; ?>" class="hidden absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-xl border border-slate-200 z-50 overflow-hidden">
                            <?php if(isset($filter['searchable']) && $filter['searchable']): ?>
                            <div class="p-2 border-b border-slate-100">
                                <input type="text" placeholder="Search..." class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-teal-500" onkeyup="filterDropdownOptions(this, '<?= $filter['id']; ?>')">
                            </div>
                            <?php endif; ?>
                            <div class="max-h-60 overflow-y-auto filter-options">
                                <?php 
                                $option_count = 0;
                                foreach($filter['options'] as $opt): 
                                    $option_count++;
                                    // Show first 6 options (All Customers + 5 customers), hide rest initially
                                    $hidden_class = ($option_count > 6) ? 'hidden-initially' : '';
                                ?>
                                    <a href="<?= $opt['url']; ?>" class="flex items-center gap-2 w-full px-4 py-2.5 text-sm text-slate-700 hover:bg-teal-50 hover:text-teal-700 transition-colors <?= (isset($opt['active']) && $opt['active']) ? 'bg-teal-50 text-teal-700 font-semibold' : ''; ?> <?= $hidden_class; ?>" data-text="<?= strtolower($opt['label']); ?>">
                                        <?= $opt['label']; ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php // Date Filter - Always show if date_column is configured
                if(isset($config['date_column'])): 
                    $date_filter = $_GET['date_filter'] ?? '';
                    $start_date = $_GET['start_date'] ?? '';
                    $end_date = $_GET['end_date'] ?? '';
                    
                    // Determine active filter label
                    $filter_label = 'Date';
                    if($date_filter) {
                        $filter_label = ucwords(str_replace('_', ' ', $date_filter));
                    } elseif($start_date && $end_date) {
                        $filter_label = date('d M Y', strtotime($start_date)) . ' - ' . date('d M Y', strtotime($end_date));
                    }
                ?>
                    <div class="relative w-full md:w-auto">
                        <button type="button" onclick="toggleFilterDropdown('filter_date')" class="inline-flex items-center justify-center gap-2 px-5 py-3 <?= ($date_filter || ($start_date && $end_date)) ? 'bg-teal-600 text-white' : 'bg-slate-100 text-slate-700'; ?> hover:bg-teal-700 hover:text-white font-bold rounded-lg shadow transition-all w-full md:min-w-[180px]">
                            <i class="fas fa-calendar-alt"></i>
                            <span><?= $filter_label; ?></span>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>
                        <div id="filter_date" class="hidden absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-xl border border-slate-200 z-50 overflow-hidden">
                            <!-- Selected Date Range Display -->
                            <?php if($start_date && $end_date): ?>
                            <div class="p-4 border-b border-slate-100 bg-slate-50">
                                <div class="text-center text-sm font-semibold text-slate-700">
                                    <?= date('d M Y', strtotime($start_date)); ?> - <?= date('d M Y', strtotime($end_date)); ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Preset Filters -->
                            <div id="preset-filters-list" class="max-h-60 overflow-y-auto">
                                <?php
                                $base_url = strtok($_SERVER['REQUEST_URI'], '?');
                                $preset_filters = [
                                    ['value' => 'today', 'label' => 'Today'],
                                    ['value' => 'tomorrow', 'label' => 'Tomorrow'],
                                    ['value' => 'yesterday', 'label' => 'Yesterday'],
                                    ['value' => '3_days', 'label' => '3 Day(s)'],
                                    ['value' => '1_week', 'label' => '1 Week(s)'],
                                    ['value' => '1_month', 'label' => '1 Month(s)'],
                                    ['value' => '3_months', 'label' => '3 Month(s)'],
                                    ['value' => '6_months', 'label' => '6 Month(s)'],
                                ];
                                foreach($preset_filters as $pf):
                                    $is_active = ($date_filter === $pf['value']);
                                ?>
                                    <a href="<?= $base_url; ?>?date_filter=<?= $pf['value']; ?>" class="block w-full px-4 py-3 text-sm text-slate-700 hover:bg-slate-50 transition-colors <?= $is_active ? 'bg-slate-50 font-semibold' : ''; ?>">
                                        <?= $pf['label']; ?>
                                    </a>
                                <?php endforeach; ?>
                                
                                <!-- Custom Option -->
                                <button type="button" onclick="event.stopPropagation(); showCustomCalendar()" class="block w-full px-4 py-3 text-sm text-teal-600 hover:bg-slate-50 transition-colors text-left font-semibold">
                                    Custom
                                </button>
                                
                                <!-- Clear Filter -->
                                <?php if($date_filter || ($start_date && $end_date)): ?>
                                <a href="<?= $base_url; ?>" class="block w-full px-4 py-3 text-sm text-red-600 hover:bg-red-50 transition-colors border-t border-slate-100">
                                    <i class="fas fa-times-circle mr-2"></i>Clear Filter
                                </a>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Custom Calendar Picker -->
                            <div id="custom-calendar-picker" class="hidden border-t border-slate-200 bg-white p-4">
                                <!-- Selected Range Display -->
                                <div class="mb-3 p-3 bg-slate-50 rounded-lg text-center">
                                    <div class="text-sm font-semibold text-slate-700" id="calendar-selected-range">
                                        Select date range
                                    </div>
                                </div>
                                
                                <!-- Calendar Header -->
                                <div class="flex items-center justify-between mb-3">
                                    <button type="button" onclick="changeCalendarMonth(-1)" class="p-2 hover:bg-slate-100 rounded transition">
                                        <i class="fas fa-chevron-left text-slate-600"></i>
                                    </button>
                                    <div class="relative">
                                        <button type="button" onclick="toggleYearMonthSelector()" class="font-bold text-slate-800 uppercase text-sm px-3 py-1 hover:bg-slate-100 rounded transition" id="calendar-month-display"></button>
                                        
                                        <!-- Year/Month Selector Dropdown -->
                                        <div id="year-month-selector" class="hidden absolute top-full left-1/2 transform -translate-x-1/2 mt-2 bg-white border border-slate-200 rounded-lg shadow-xl z-50 p-3 w-64">
                                            <div class="mb-3">
                                                <label class="text-xs font-semibold text-slate-500 mb-1 block">Year</label>
                                                <select id="year-select" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-teal-500">
                                                    <!-- Years will be populated by JavaScript -->
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="text-xs font-semibold text-slate-500 mb-1 block">Month</label>
                                                <select id="month-select" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-teal-500">
                                                    <option value="0">January</option>
                                                    <option value="1">February</option>
                                                    <option value="2">March</option>
                                                    <option value="3">April</option>
                                                    <option value="4">May</option>
                                                    <option value="5">June</option>
                                                    <option value="6">July</option>
                                                    <option value="7">August</option>
                                                    <option value="8">September</option>
                                                    <option value="9">October</option>
                                                    <option value="10">November</option>
                                                    <option value="11">December</option>
                                                </select>
                                            </div>
                                            <button type="button" onclick="applyYearMonthSelection()" class="w-full px-4 py-2 bg-teal-600 text-white rounded-lg text-sm font-semibold hover:bg-teal-700 transition-all">
                                                Apply
                                            </button>
                                        </div>
                                    </div>
                                    <button type="button" onclick="changeCalendarMonth(1)" class="p-2 hover:bg-slate-100 rounded transition">
                                        <i class="fas fa-chevron-right text-slate-600"></i>
                                    </button>
                                </div>
                                
                                <!-- Calendar Grid -->
                                <div class="grid grid-cols-7 gap-y-1 mb-3">
                                    <div class="text-center text-xs font-semibold text-slate-500 py-2">S</div>
                                    <div class="text-center text-xs font-semibold text-slate-500 py-2">M</div>
                                    <div class="text-center text-xs font-semibold text-slate-500 py-2">T</div>
                                    <div class="text-center text-xs font-semibold text-slate-500 py-2">W</div>
                                    <div class="text-center text-xs font-semibold text-slate-500 py-2">T</div>
                                    <div class="text-center text-xs font-semibold text-slate-500 py-2">F</div>
                                    <div class="text-center text-xs font-semibold text-slate-500 py-2">S</div>
                                </div>
                                <div id="calendar-grid" class="grid grid-cols-7 gap-y-1"></div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>


                <?php // Reset Filter Button
                if(!empty($_GET)): ?>
                    <a href="<?= strtok($_SERVER['REQUEST_URI'], '?'); ?>" class="w-full md:w-auto inline-flex items-center justify-center gap-2 px-5 py-3 bg-red-100 hover:bg-red-200 text-red-700 font-bold rounded-lg border border-red-200 transition-all" title="Clear All Filters">
                        <i class="fas fa-undo"></i>
                        <span>Reset</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <?php // Summary Cards
        if(!empty($summary_cards)): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-<?= count($summary_cards); ?> gap-6 mb-6">
            <?php foreach($summary_cards as $card): ?>
            <div class="bg-white rounded-xl shadow-sm border-l-4 <?= $card['border_color'] ?? 'border-teal-500'; ?> p-5 text-center md:text-left">
                <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2"><?= $card['label']; ?></div>
                <div class="text-2xl font-black text-slate-800"><?= $card['value']; ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden p-6">
            
            <?php if(count($data) > 0): ?>
                <div class="overflow-x-auto">
                    <table id="<?= $table_id; ?>" class="data-table w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200">
                                <?php foreach($columns as $col): ?>
                                    <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider whitespace-nowrap">
                                        <?= htmlspecialchars($col['label']); ?>
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach($data as $row): ?>
                                <tr class="hover:bg-slate-50 transition-colors group">
                                    <?php foreach($columns as $col): 
                                        $key  = $col['key'];
                                        $type = $col['type'] ?? 'text';
                                        
                                        // Mobile helper classes
                                        $mobile_class = '';
                                        if($type === 'actions' || $key === 'actions') $mobile_class = 'mobile-actions';
                                        // Show invoice_id, customer name, and images prominently on mobile
                                        elseif($type === 'image' || $key === $name_field || $key === 'id' || $key === 'customer_name' || $key === 'customer_display') $mobile_class = 'mobile-primary';

                                        // Desktop helper: wrap product name
                                        $desktop_class = ($key === $name_field || $key === 'product_name') ? '' : 'whitespace-nowrap';
                                    ?>
                                        <td class="p-4 text-sm text-slate-700 align-middle <?= $desktop_class; ?> <?= $mobile_class; ?>" data-label="<?= htmlspecialchars($col['label']); ?>">
                                            <?php
                                            $val  = $row[$key] ?? '';
                                            $id   = $row[$primary_key] ?? 0;

                                            // --- Action Buttons ---
                                            if ($key === 'actions' || $type === 'actions'): ?>
                                                <div class="flex flex-wrap items-center gap-2 md:gap-3">
                                                    <?php if($view_url && in_array('view', $action_buttons)): 
                                                        $view_type = $config['view_type'] ?? 'link';
                                                        if($view_type === 'modal'): ?>
                                                            <button type="button" onclick="openViewModal(<?= $id; ?>, '<?= $view_url; ?>')" class="p-2 text-blue-500 hover:bg-blue-50 rounded transition" title="View">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                        <?php else: ?>
                                                            <a href="<?= $view_url; ?>/<?= $id; ?>" class="p-2 text-blue-500 hover:bg-blue-50 rounded transition" title="View">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                    <?php endif; ?>

                                                    <?php if($edit_url !== '#' && in_array('edit', $action_buttons)): ?>
                                                    <a href="<?= $edit_url; ?>/<?= $id; ?>" class="p-2 text-teal-600 hover:bg-teal-50 rounded transition" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <?php endif; ?>
                                                    
                                                    <?php if($delete_url !== '#' && in_array('delete', $action_buttons)): ?>
                                                    <button type="button" onclick="confirmDelete(<?= $id; ?>, '<?= addslashes($row[$name_field] ?? 'Item'); ?>', '<?= $delete_url; ?>')" 
                                                            class="p-2 text-red-500 hover:bg-red-50 rounded transition" title="Delete">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                    
                                                    <?php 
                                                    // Custom actions
                                                    if(in_array('custom', $action_buttons) && isset($config['custom_actions'])):
                                                        $customActionFunc = $config['custom_actions'];
                                                        echo $customActionFunc($row);
                                                    endif;
                                                    ?>
                                                </div>

                                            <?php 
                                            // --- Status Toggle ---
                                            elseif ($type === 'status'): 
                                                $status = (int)$val;
                                                $active_label = $col['active_label'] ?? 'Active';
                                                $inactive_label = $col['inactive_label'] ?? 'Inactive';
                                                
                                                if($status === 1) {
                                                    $badgeClass = 'bg-emerald-100 text-emerald-700 border border-emerald-200';
                                                    $dotClass = 'bg-emerald-500';
                                                    $text = $active_label;
                                                } else {
                                                    $badgeClass = 'bg-amber-100 text-amber-700 border border-amber-200';
                                                    $dotClass = 'bg-amber-500';
                                                    $text = $inactive_label;
                                                }
                                            ?>
                                                <button type="button" onclick="toggleStatus(<?= $id; ?>, <?= $status; ?>, '<?= $status_url; ?>')"
                                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium <?= $badgeClass; ?> hover:opacity-80 transition">
                                                    <span class="w-1.5 h-1.5 rounded-full <?= $dotClass; ?>"></span>
                                                    <?= $text; ?>
                                                </button>

                                            <?php 
                                            // --- Badges ---
                                            elseif ($type === 'badge'): 
                                                $badgeClass = $col['badge_class'] ?? 'bg-slate-100 text-slate-700';
                                            ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold uppercase <?= $badgeClass; ?>">
                                                    <?= htmlspecialchars($val); ?>
                                                </span>

                                            <?php 
                                            // --- Images ---
                                            elseif ($type === 'image'): 
                                                $path = $col['path'] ?? '';
                                                $name = $row[$config['name_field'] ?? 'name'] ?? 'C';
                                            ?>
                                                <?php if(!empty($val)): ?>
                                                    <img src="<?= htmlspecialchars($path . $val); ?>" 
                                                         class="w-10 h-10 rounded-lg object-cover border border-slate-200 shadow-sm" 
                                                         alt="Img"
                                                         onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($name); ?>&background=random&size=40'; this.onerror=null;">
                                                <?php else: ?>
                                                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($name); ?>&background=random&size=40" class="w-10 h-10 rounded-lg border border-slate-200" alt="Avtr">
                                                <?php endif; ?>

                                            <?php 
                                            // --- HTML / Default Text ---
                                            elseif ($type === 'html'): 
                                                echo $val; // RAW Output
                                            else:
                                                echo htmlspecialchars($val);
                                            endif;
                                            ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <?php if (!empty($config['footer'])): ?>
                            <tfoot class="bg-gray-50 border-t-2 border-slate-200 font-bold text-slate-700">
                                <tr>
                                    <?php foreach ($columns as $col): 
                                        $key = $col['key'];
                                        $align = isset($col['item_align']) && $col['item_align'] === 'right' ? 'text-right' : (isset($col['item_align']) && $col['item_align'] === 'center' ? 'text-center' : 'text-left');
                                        $val = isset($config['footer'][$key]) ? $config['footer'][$key] : '';
                                    ?>
                                        <td class="p-4 text-sm <?= $align; ?>">
                                            <?= $val; // Render HTML directly as footer is usually trusted/formatted ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            </tfoot>
                        <?php endif; ?>
                    </table>
                </div>

            <?php else: ?>
                <div class="flex flex-col items-center justify-center py-12 text-center">
                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4 border border-slate-100 text-slate-300">
                        <i class="fas fa-folder-open text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800 mb-1">No records found</h3>
                    <p class="text-slate-500 mb-6 text-sm">Get started by creating a new entry.</p>
                    <?php if($add_url !== '#'): ?>
                    <a href="<?= $add_url; ?>" class="px-5 py-2.5 bg-teal-600 text-white rounded-lg text-sm font-bold shadow hover:bg-teal-700 transition-all">
                        Create First Entry
                    </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php
    
}
?>

<script>
// Toggle Filter Dropdown
function toggleFilterDropdown(id) {
    const dropdown = document.getElementById(id);
    const allDropdowns = document.querySelectorAll('[id^="filter_"]');
    
    // Close other dropdowns
    allDropdowns.forEach(dd => {
        if(dd.id !== id) dd.classList.add('hidden');
    });
    
    dropdown.classList.toggle('hidden');
}

// Filter dropdown options by search
function filterDropdownOptions(input, dropdownId) {
    const filter = input.value.toLowerCase();
    const dropdown = document.getElementById(dropdownId);
    const options = dropdown.querySelectorAll('.filter-options a');
    
    options.forEach(opt => {
        const text = opt.getAttribute('data-text') || opt.textContent.toLowerCase();
        
        // If searching, show all matching items (remove hidden-initially)
        if(filter.length > 0) {
            opt.classList.remove('hidden-initially');
            if(text.includes(filter)) {
                opt.style.display = '';
            } else {
                opt.style.display = 'none';
            }
        } else {
            // If search is cleared, restore hidden-initially class and show all
            opt.style.display = '';
            // Re-apply hidden-initially to items beyond first 6
            const allOptions = Array.from(options);
            const index = allOptions.indexOf(opt);
            if(index > 5) {
                opt.classList.add('hidden-initially');
            }
        }
    });
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(e) {
    if(!e.target.closest('[onclick*="toggleFilterDropdown"]') && !e.target.closest('[id^="filter_"]')) {
        document.querySelectorAll('[id^="filter_"]').forEach(dd => dd.classList.add('hidden'));
    }
});

// Calendar Date Picker Variables
let calendarCurrentDate = new Date();
let calendarStartDate = null;
let calendarEndDate = null;

// Show Custom Calendar (hide preset filters)
function showCustomCalendar() {
    const presetList = document.getElementById('preset-filters-list');
    const calendar = document.getElementById('custom-calendar-picker');
    
    // Hide preset filters
    presetList.classList.add('hidden');
    
    // Show calendar
    calendar.classList.remove('hidden');
    renderCustomCalendar();
    populateYearSelector();
}

// Toggle Year/Month Selector
function toggleYearMonthSelector() {
    const selector = document.getElementById('year-month-selector');
    selector.classList.toggle('hidden');
}

// Populate Year Selector
function populateYearSelector() {
    const yearSelect = document.getElementById('year-select');
    const currentYear = new Date().getFullYear();
    
    // Clear existing options
    yearSelect.innerHTML = '';
    
    // Add years from 10 years ago to 10 years in future
    for(let year = currentYear - 10; year <= currentYear + 10; year++) {
        const option = document.createElement('option');
        option.value = year;
        option.textContent = year;
        if(year === calendarCurrentDate.getFullYear()) {
            option.selected = true;
        }
        yearSelect.appendChild(option);
    }
    
    // Set current month
    document.getElementById('month-select').value = calendarCurrentDate.getMonth();
}

// Apply Year/Month Selection
function applyYearMonthSelection() {
    const year = parseInt(document.getElementById('year-select').value);
    const month = parseInt(document.getElementById('month-select').value);
    
    calendarCurrentDate = new Date(year, month, 1);
    renderCustomCalendar();
    
    // Hide selector
    document.getElementById('year-month-selector').classList.add('hidden');
}

// Change Calendar Month
function changeCalendarMonth(direction) {
    calendarCurrentDate.setMonth(calendarCurrentDate.getMonth() + direction);
    renderCustomCalendar();
}

// Render Custom Calendar
function renderCustomCalendar() {
    const year = calendarCurrentDate.getFullYear();
    const month = calendarCurrentDate.getMonth();
    
    // Update month/year display
    const monthNames = ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 
                        'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'];
    document.getElementById('calendar-month-display').textContent = `${monthNames[month]} ${year}`;
    
    // Get first day of month and number of days
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    
    // Render calendar days
    const grid = document.getElementById('calendar-grid');
    grid.innerHTML = '';
    
    // Remove default grid gap for horizontal connection
    grid.parentElement.classList.remove('gap-1', 'grid-cols-7');
    grid.parentElement.classList.add('grid-cols-7', 'gap-y-1'); // Keep vertical gap, remove horizontal
    grid.style.rowGap = '4px';

    // Empty cells before first day
    for(let i = 0; i < firstDay; i++) {
        const emptyCell = document.createElement('div');
        grid.appendChild(emptyCell);
    }
    
    // Render days
    for(let day = 1; day <= daysInMonth; day++) {
        const dayCellWrapper = document.createElement('div');
        dayCellWrapper.className = 'flex items-center justify-center w-full relative'; // wrapper for positioning

        const dayBtn = document.createElement('button');
        dayBtn.type = 'button';
        dayBtn.textContent = day;
        
        const currentDate = new Date(year, month, day);
        const dateStr = formatCalendarDate(currentDate);
        
        // Base styling for the button itself (the number)
        let btnClasses = 'w-9 h-9 flex items-center justify-center text-sm z-10 relative transition-colors duration-200 ';
        let wrapperClasses = 'flex items-center justify-center w-full relative h-9 ';

        // Check if this date is in selected range
        if(calendarStartDate && calendarEndDate) {
            const start = new Date(calendarStartDate);
            const end = new Date(calendarEndDate);
            
            if(currentDate > start && currentDate < end) {
                // Middle of range
                wrapperClasses += 'bg-teal-100'; // Light teal background fills the cell
                btnClasses += 'text-teal-900 font-medium'; // Darker text
            } else if (dateStr === formatCalendarDate(start)) {
                // Start of range
                wrapperClasses += 'bg-teal-100 rounded-l-full'; // Rounded left, background extends right
                btnClasses += 'bg-teal-600 text-white font-bold rounded-full';
                // Adjust wrapper background to start from center for pure circle look if needed, 
                // but for connected look:
                // If it is the start, we want the background to go towards the right only? 
                // Actually 'bg-teal-100' on wrapper makes the "track".
                // We need a pseudo element or gradient if we want it perfect, but let's try this:
                dayCellWrapper.style.background = 'linear-gradient(to right, transparent 50%, #ccfbf1 50%)'; 
                wrapperClasses = 'flex items-center justify-center w-full relative h-9 '; // Override default class to avoid conflict
            } else if (dateStr === formatCalendarDate(end)) {
                // End of range
                // wrapperClasses += 'bg-teal-100 rounded-r-full';
                btnClasses += 'bg-teal-600 text-white font-bold rounded-full';
                dayCellWrapper.style.background = 'linear-gradient(to left, transparent 50%, #ccfbf1 50%)';
                wrapperClasses = 'flex items-center justify-center w-full relative h-9 ';
            } else {
                // Outside range
                btnClasses += 'rounded-full hover:bg-slate-100 text-slate-700';
            }
        } else if(calendarStartDate && dateStr === formatCalendarDate(new Date(calendarStartDate))) {
            // Only start date selected
            btnClasses += 'bg-teal-600 text-white font-bold rounded-full';
        } else {
            // No selection or unrelated date
            btnClasses += 'rounded-full hover:bg-slate-100 text-slate-700';
        }
        
        // Special Case: Start and End are same day (single day range) -> Should be just a circle
        if(calendarStartDate && calendarEndDate && calendarStartDate === calendarEndDate && dateStr === formatCalendarDate(new Date(calendarStartDate))) {
             dayCellWrapper.style.background = 'transparent';
        }

        dayBtn.className = btnClasses;
        dayCellWrapper.className = wrapperClasses;
        
        dayBtn.onclick = (e) => {
            e.stopPropagation();
            selectCalendarDateRange(currentDate);
        };
        
        dayCellWrapper.appendChild(dayBtn);
        grid.appendChild(dayCellWrapper);
    }
    
    // Update selected range display
    updateCalendarRangeDisplay();
}

// Select Calendar Date Range
function selectCalendarDateRange(date) {
    if(!calendarStartDate || (calendarStartDate && calendarEndDate)) {
        // Start new selection (1st click)
        calendarStartDate = formatCalendarDate(date);
        calendarEndDate = null;
        renderCustomCalendar();
    } else {
        // Complete range selection (2nd click)
        const start = new Date(calendarStartDate);
        
        // If clicking before start date, swap or verify logic
        if(date < start) {
            calendarEndDate = calendarStartDate;
            calendarStartDate = formatCalendarDate(date);
        } else {
            calendarEndDate = formatCalendarDate(date);
        }
        
        // Re-render to show highlighting before redirecting? 
        // User wants to see the selection. 
        // If we redirect immediately, they might not see it. 
        // But the redirect is how the filter applies.
        renderCustomCalendar();

        // Small timeout to let user see the selection effect?
        setTimeout(() => {
            const baseUrl = window.location.pathname;
            window.location.href = `${baseUrl}?start_date=${calendarStartDate}&end_date=${calendarEndDate}`;
        }, 300); // 300ms delay
    }
}

// Update Calendar Range Display
function updateCalendarRangeDisplay() {
    const display = document.getElementById('calendar-selected-range');
    
    if(calendarStartDate && calendarEndDate) {
        const start = new Date(calendarStartDate);
        const end = new Date(calendarEndDate);
        display.textContent = `${formatCalendarDisplayDate(start)} - ${formatCalendarDisplayDate(end)}`;
    } else if(calendarStartDate) {
        display.textContent = 'Select end date...';
    } else {
        display.textContent = 'Select date range';
    }
}

// Format date for calendar (YYYY-MM-DD)
function formatCalendarDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

// Format date for display (14 Jan 2026)
function formatCalendarDisplayDate(date) {
    const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 
                        'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const day = String(date.getDate()).padStart(2, '0');
    return `${day} ${monthNames[date.getMonth()]} ${date.getFullYear()}`;
}

// Prevent closing dropdown when calendar is active and dates not selected
document.addEventListener('click', function(e) {
    const filterDropdown = document.getElementById('filter_date');
    const calendar = document.getElementById('custom-calendar-picker');
    const yearMonthSelector = document.getElementById('year-month-selector');
    
    // Don't close if clicking inside calendar or year/month selector
    if(calendar && !calendar.classList.contains('hidden')) {
        if(e.target.closest('#custom-calendar-picker') || e.target.closest('#year-month-selector')) {
            return;
        }
    }
    
    // Close year/month selector if clicking outside
    if(yearMonthSelector && !yearMonthSelector.classList.contains('hidden')) {
        if(!e.target.closest('#year-month-selector') && !e.target.closest('#calendar-month-display')) {
            yearMonthSelector.classList.add('hidden');
        }
    }
});

</script>


