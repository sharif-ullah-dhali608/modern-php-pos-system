<div class="mb-8 header-controls no-print">
    <div class="flex flex-col justify-between gap-6">
        <div class="space-y-2">
            <nav class="flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-slate-400">
                <a href="javascript:void(0)" onclick="loadReport('/pos/expenditure/monthwise/<?= $year ?>')" class="hover:text-rose-600 transition-colors">Expense</a>
                <?php if($is_month_view): ?>
                    <i class="fas fa-chevron-right text-[8px]"></i>
                    <span class="text-slate-800"><?= $month_names[$month-1] ?></span>
                <?php endif; ?>
            </nav>
            <h1 class="text-4xl font-black text-slate-900 tracking-tight">
                <?= $is_month_view ? $month_names[$month-1] . ', ' . $year : 'Yearly Expense Report ' . $year ?>
            </h1>
            <p class="text-slate-500 font-medium max-w-xl">
                <?= $is_month_view ? "Daily breakdown of all expense categories for this month." : "Comprehensive overview of all expense categories across 12 months." ?>
            </p>
        </div>
        
        <div class="controls-wrapper relative z-50 flex justify-center">
            <div class="flex items-center gap-4 bg-white/50 backdrop-blur-md p-2 rounded-2xl border border-white/50 shadow-sm">
                <div class="flex flex-nowrap items-center bg-slate-100 rounded-xl p-1 gap-2">
                    <button onclick="navigateReport('prev')" class="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-lg hover:bg-white hover:text-rose-600 transition-all text-slate-500"><i class="fas fa-chevron-left"></i></button>
                    
                    <div class="relative group w-[280px] flex-shrink-0 bg-white border-2 border-slate-100 focus-within:border-rose-500 rounded-2xl shadow-sm transition-all" id="store_selector_container">
                        <div class="flex items-center px-4 py-2 text-sm font-bold text-slate-800 outline-none">
                            <div class="pr-3 text-slate-400"><i class="fas fa-store"></i></div>
                            <input type="text" id="store_search_input" 
                                   class="w-full bg-transparent border-none outline-none font-bold text-slate-700 placeholder-slate-400 text-sm"
                                   placeholder="Search Store..."
                                   value="<?= $store_id ? htmlspecialchars($all_stores[array_search($store_id, array_column($all_stores, 'id'))]['store_name']) : 'All Stores' ?>"
                                   autocomplete="off"
                                   onclick="this.select()">
                            <div class="pl-2 text-slate-300"><i class="fas fa-chevron-down text-[10px]"></i></div>
                        </div>
                        <input type="hidden" id="store-select" value="<?= $store_id ?>">
                        
                        <div id="store_dropdown" class="absolute left-0 top-full mt-2 w-full bg-white border-2 border-rose-500 rounded-2xl hidden shadow-2xl z-[50] overflow-hidden custom-scroll">
                            <div id="store_results_container" class="p-1">
                                <?php if($isAdmin || count($all_stores) > 1): ?>
                                <div class="store-option px-4 py-3 hover:bg-rose-50 cursor-pointer transition-colors border-b border-slate-50 flex items-center gap-3 rounded-xl" data-id="" data-name="All Stores">
                                    <div class="w-8 h-8 rounded-lg bg-rose-50 flex items-center justify-center text-rose-600 font-bold text-xs"><i class="fas fa-layer-group"></i></div>
                                    <div class="font-bold text-slate-700 text-sm">All Stores</div>
                                </div>
                                <?php endif; ?>
                                <?php foreach($all_stores as $s): ?>
                                    <div class="store-option px-4 py-3 hover:bg-rose-50 cursor-pointer transition-colors border-b border-slate-50 flex items-center gap-3 rounded-xl" 
                                         data-id="<?= $s['id'] ?>" data-name="<?= htmlspecialchars($s['store_name']) ?>">
                                        <div class="w-8 h-8 rounded-lg bg-rose-50 flex items-center justify-center text-rose-600 font-black text-xs"><?= strtoupper(substr($s['store_name'], 0, 1)) ?></div>
                                        <div class="font-bold text-slate-700 text-sm"><?= htmlspecialchars($s['store_name']) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="selects-container flex-shrink-0 flex flex-nowrap items-center bg-white rounded-lg shadow-sm border border-slate-200 px-2 divide-x divide-slate-100 h-[46px]" id="default-selects">
                        <select id="month-select" class="bg-transparent border-0 px-3 py-2 text-sm font-bold outline-none text-slate-800 h-full">
                            <option value="" <?= !$is_month_view ? 'selected' : '' ?>>Yearly Overview</option>
                            <?php foreach($month_names as $m_idx => $m_name): ?>
                                <option value="<?= str_pad($m_idx+1, 2, '0', STR_PAD_LEFT) ?>" <?= ($is_month_view && (int)$month == ($m_idx+1)) ? 'selected' : '' ?>><?= $m_name ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select id="year-select" class="bg-transparent border-0 px-4 py-2 text-sm font-bold outline-none text-slate-800 h-full">
                            <?php for($i = date('Y'); $i >= 2020; $i--): ?>
                                <option value="<?= $i ?>" <?= $i == $year ? 'selected' : '' ?>><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="relative">
                        <button onclick="toggleCustomCalendar()" class="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-lg hover:bg-white hover:text-rose-600 transition-all text-slate-500" title="Custom Date Range">
                            <i class="far fa-calendar-alt"></i>
                        </button>

                        <!-- Custom Calendar Picker Modal -->
                        <div id="custom-date-picker-modal" class="hidden absolute top-full right-0 mt-2 w-80 bg-white rounded-2xl shadow-2xl border border-slate-200 z-[9999] p-4 overflow-hidden">
                            <!-- Selected Range Display -->
                            <div class="mb-4 p-3 bg-rose-50 rounded-xl text-center border border-rose-100">
                                <div class="text-sm font-bold text-rose-800" id="cal-selected-range">
                                    Select date range
                                </div>
                            </div>
                            
                            <!-- Calendar Header -->
                            <div class="flex items-center justify-between mb-4">
                                <button type="button" onclick="changeCalMonth(-1)" class="w-8 h-8 flex items-center justify-center hover:bg-slate-100 rounded-full transition text-slate-500">
                                    <i class="fas fa-chevron-left text-xs"></i>
                                </button>
                                <div class="relative">
                                    <button type="button" onclick="toggleCalYearMonth()" class="font-bold text-slate-800 uppercase text-sm px-3 py-1 hover:bg-slate-100 rounded-lg transition flex items-center gap-2" id="cal-month-display">
                                        MONTH YEAR
                                    </button>
                                    
                                    <!-- Year/Month Selector -->
                                    <div id="cal-year-month-selector" class="hidden absolute top-full left-1/2 transform -translate-x-1/2 mt-2 bg-white border border-slate-200 rounded-xl shadow-xl z-50 p-3 w-64">
                                        <div class="mb-3">
                                            <label class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1 block">Year</label>
                                            <select id="cal-year-select" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm font-bold focus:outline-none focus:ring-2 focus:ring-rose-500"></select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1 block">Month</label>
                                            <select id="cal-month-select" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm font-bold focus:outline-none focus:ring-2 focus:ring-rose-500">
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
                                        <button type="button" onclick="applyCalYearMonth()" class="w-full px-4 py-2 bg-rose-600 text-white rounded-lg text-sm font-bold hover:bg-rose-700 transition-all shadow-lg shadow-rose-200">
                                            Apply
                                        </button>
                                    </div>
                                </div>
                                <button type="button" onclick="changeCalMonth(1)" class="w-8 h-8 flex items-center justify-center hover:bg-slate-100 rounded-full transition text-slate-500">
                                    <i class="fas fa-chevron-right text-xs"></i>
                                </button>
                            </div>
                            
                            <!-- Weekday Headers -->
                            <div class="grid grid-cols-7 mb-2 text-center">
                                <div class="text-xs font-bold text-slate-400">S</div>
                                <div class="text-xs font-bold text-slate-400">M</div>
                                <div class="text-xs font-bold text-slate-400">T</div>
                                <div class="text-xs font-bold text-slate-400">W</div>
                                <div class="text-xs font-bold text-slate-400">T</div>
                                <div class="text-xs font-bold text-slate-400">F</div>
                                <div class="text-xs font-bold text-slate-400">S</div>
                            </div>

                            <!-- Calendar Grid -->
                            <div id="cal-grid" class="grid grid-cols-7 gap-y-1 place-items-center"></div>
                        </div>
                    </div>

                    <?php if($month || $start_date || $end_date): ?>
                    <button onclick="resetFilters()" class="flex-shrink-0 flex items-center gap-2 px-3 py-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition-colors font-bold text-sm">
                        <i class="fas fa-undo"></i> Reset
                    </button>
                    <?php endif; ?>

                    <button onclick="navigateReport('next')" class="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-lg hover:bg-white hover:text-rose-600 transition-all text-slate-500"><i class="fas fa-chevron-right"></i></button>
                </div>
                <?php if($is_month_view): ?>
                    <a href="javascript:void(0)" onclick="loadReport('/pos/expenditure/monthwise/<?= $year ?>')" class="bg-rose-600 hover:bg-rose-700 text-white px-5 py-3 rounded-xl text-sm font-bold transition-all shadow-lg shadow-rose-200 flex items-center gap-2 group">
                        <i class="fas fa-th group-hover:scale-110 transition-transform"></i> Yearly
                    </a>
                <?php endif; ?>
                <button onclick="openPrintTab()" class="bg-rose-600 hover:bg-rose-700 text-white px-6 py-3 rounded-xl text-sm font-bold shadow-lg shadow-rose-100 transition-all flex items-center gap-2"><i class="fas fa-print"></i> Export</button>
            </div>
        </div>
    </div>
</div>

<!-- Main Report Table -->
<div class="glass-card rounded-3xl shadow-2xl border border-white/40 overflow-hidden bg-white/80 backdrop-blur-xl">
    <div class="overflow-x-auto custom-scroll">
        <table class="w-full text-left border-collapse min-w-[1200px]">
            <thead>
                <tr class="bg-rose-900 text-white border-b border-rose-800">
                    <th class="p-6 text-xs font-black uppercase tracking-widest sticky left-0 bg-rose-900 z-20 border-r border-rose-800 min-w-[200px]"><?= ($is_month_view || $is_custom_range) ? 'Date \ Categories' : 'Categories \ Months' ?></th>
                    <?php if(!$is_month_view && !$is_custom_range): ?>
                        <?php foreach($short_months as $index => $m): ?>
                            <th class="p-4 text-xs font-black uppercase tracking-widest text-center border-r border-slate-800/50 cursor-pointer hover:bg-rose-600 transition-colors" onclick="loadReport('/pos/expenditure/monthwise/<?= $year ?>/<?= str_pad($index+1, 2, '0', STR_PAD_LEFT) ?>')"><?= $m ?></th>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php foreach($categories as $cat): ?>
                            <th class="p-4 text-xs font-black uppercase tracking-widest text-center border-r border-slate-800/50"><?= htmlspecialchars($cat['category_name']) ?></th>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <th class="p-6 text-xs font-black uppercase tracking-widest text-right bg-slate-950">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                <?php if(!$is_month_view && !$is_custom_range): ?>
                    <?php foreach($report_data as $row): ?>
                        <tr class="hover:bg-rose-50/30 transition-colors group">
                            <td class="p-5 text-sm font-bold text-slate-800 sticky left-0 bg-white group-hover:bg-slate-50/50 border-r border-slate-100 z-10"><?= htmlspecialchars($row['name']) ?></td>
                            <?php for($m=1; $m<=12; $m++): $val = $row['cells'][$m]; $cellClass=$val>0?'cell-active font-black':'text-slate-200/50'; ?>
                                <td class="p-5 text-sm text-center border-r border-slate-50 <?= $cellClass ?> cursor-pointer hover:bg-white transition-all shadow-inner" onclick="loadReport('/pos/expenditure/monthwise/<?= $year ?>/<?= str_pad($m, 2, '0', STR_PAD_LEFT) ?>')"><?= $val > 0 ? number_format($val, 0) : '-' ?></td>
                            <?php endfor; ?>
                            <td class="p-5 text-sm font-black text-right text-slate-900 bg-slate-50/50"><?= number_format($row['total'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <?php foreach($report_data as $row): ?>
                        <tr class="hover:bg-rose-50/30 transition-colors group">
                            <td class="p-4 text-sm font-black text-slate-500 text-center sticky left-0 bg-slate-50 group-hover:bg-slate-100 border-r border-slate-200 z-10 w-16"><?= $row['day'] ?></td>
                            <?php foreach($categories as $cat): $val = $row['cells'][$cat['category_id']]; $cellClass=$val>0?'bg-rose-50 text-rose-700 font-black':'text-slate-200/30'; ?>
                                <td class="p-4 text-sm text-center border-r border-slate-50 <?= $cellClass ?>"><?= $val > 0 ? number_format($val, 0) : '-' ?></td>
                            <?php endforeach; ?>
                            <td class="p-4 text-sm font-black text-right text-slate-900 bg-slate-50/50 border-l border-rose-100"><?= number_format($row['total'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr class="bg-rose-50 backdrop-blur-md">
                    <td class="p-6 text-sm font-black uppercase tracking-widest text-rose-900 sticky left-0 bg-rose-100/50 border-r border-rose-200 z-10">Grand Total</td>
                    <?php if(!$is_month_view && !$is_custom_range): ?>
                        <?php for($m=1; $m<=12; $m++): ?>
                            <td class="p-6 text-sm font-black text-center border-r border-white italic text-rose-700"><?= $totals_line[$m] > 0 ? number_format($totals_line[$m], 0) : '-' ?></td>
                        <?php endfor; ?>
                    <?php else: ?>
                        <?php foreach($categories as $cat): ?>
                            <td class="p-6 text-sm font-black text-center border-r border-white italic text-rose-700"><?= $totals_line[$cat['category_id']] > 0 ? number_format($totals_line[$cat['category_id']], 0) : '-' ?></td>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <td class="p-6 text-lg font-black text-right text-rose-900 bg-rose-200/30 border-l-2 border-rose-200"><?= $currency_symbol ?> <?= number_format($grand_total, 2) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- Footer Summary & Analytics -->
 <div class="flex flex-col lg:flex-row gap-8 mt-10 no-print">
    <div class="flex-1 bg-white p-8 rounded-[2.5rem] shadow-xl border border-slate-100 relative overflow-hidden flex flex-col">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h3 class="text-xl font-black text-slate-900">Expense Analytics</h3>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Visual Trends Overview</p>
            </div>
            <div class="flex items-center gap-2 bg-rose-50 px-4 py-2 rounded-xl border border-rose-100">
                <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                <span class="text-[10px] font-black text-rose-700 uppercase">Live Data</span>
            </div>
        </div>
         <div class="flex-1 relative min-h-[256px]"><canvas id="expenditureChart" class="w-full h-full"></canvas></div>
    </div>

    <div class="lg:w-96 space-y-6">
        <div class="bg-rose-900 p-8 rounded-[2.5rem] shadow-2xl shadow-rose-100 text-white border border-rose-800 relative overflow-hidden group">
            <div class="absolute -right-4 -bottom-4 opacity-10 group-hover:scale-110 transition-transform duration-500 text-9xl"><i class="fas fa-wallet"></i></div>
            <p class="text-xs font-bold uppercase tracking-widest opacity-60 mb-2">Total Overview</p>
            <h3 class="text-3xl font-black tracking-tighter"><?= $currency_symbol ?> <?= number_format($grand_total, 2) ?></h3>
            <p class="text-[10px] font-bold mt-4 px-3 py-1 bg-rose-500/20 text-rose-400 rounded-full inline-block uppercase tracking-widest">Expense Confirmed</p>
        </div>

        <div class="bg-white p-6 rounded-[2.5rem] shadow-xl border border-slate-100 flex flex-col">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h4 class="text-lg font-black text-slate-900 tracking-tight">Recent Expenses</h4>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Last 5 Withdrawals</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-lg"><i class="fas fa-history"></i></div>
            </div>

            <div class="space-y-4 flex-1">
                <?php if(empty($recent_expenses)): ?>
                    <div class="flex flex-col items-center justify-center h-full text-center py-10">
                        <i class="fas fa-ghost text-slate-200 text-4xl mb-3"></i>
                        <p class="text-sm font-bold text-slate-400">No recent expenses</p>
                    </div>
                <?php else: ?>
                    <?php foreach($recent_expenses as $re): ?>
                        <div class="group flex items-center gap-4 p-3 rounded-2xl hover:bg-rose-50/50 border border-transparent hover:border-rose-100 transition-all cursor-pointer">
                            <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-500 group-hover:bg-rose-600 group-hover:text-white flex items-center justify-center transition-all">
                                <i class="fas fa-receipt text-xs"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h5 class="text-sm font-black text-slate-800 truncate leading-tight"><?= htmlspecialchars($re['title']) ?></h5>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <span class="text-[9px] font-bold text-rose-600 uppercase bg-rose-50 px-1.5 py-0.5 rounded"><?= htmlspecialchars($re['category_name'] ?? 'N/A') ?></span>
                                    <span class="text-[9px] font-bold text-slate-400 flex items-center gap-1"><i class="far fa-clock"></i> <?= date('h:i A', strtotime($re['created_at'])) ?></span>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-black text-slate-900"><?= $currency_symbol ?> <?= number_format($re['amount'], 0) ?></p>
                                <p class="text-[9px] font-bold text-slate-400"><?= date('d M', strtotime($re['created_at'])) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="mt-6 pt-4 border-t border-slate-50 text-center">
                <a href="/pos/expenditure/expense_list" class="inline-flex items-center justify-center gap-2 w-full py-3 bg-slate-50 hover:bg-rose-50 text-slate-600 hover:text-rose-700 font-bold rounded-xl transition-all group">
                    View All Expenses <i class="fas fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                </a>
            </div>
        </div>
    </div>
</div>
