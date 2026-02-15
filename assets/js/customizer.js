/**
 * Template Customizer JavaScript
 * Handles theme switching, primary colors, layout, and localStorage persistence
 */

(function () {
    'use strict';

    // Constants & Defaults
    const DEFAULTS = {
        theme: 'light',
        primary: 'teal',
        skin: 'default', // default, bordered
        layout_menu: 'expanded', // expanded, collapsed
        layout_navbar: 'sticky', // sticky, static, hidden
        layout_content: 'wide' // wide, compact
    };

    // Configuration Options
    const CONFIG = {
        themes: {
            light: { name: 'Light', icon: 'fa-sun' },
            dark: { name: 'Dark', icon: 'fa-moon' },
            'login-style': { name: 'Gradient', icon: 'fa-palette' }
        },
        primaryColors: {
            teal: { name: 'Teal', color: '#14b8a6' },
            indigo: { name: 'Indigo', color: '#6366f1' },
            purple: { name: 'Purple', color: '#a855f7' },
            rose: { name: 'Rose', color: '#f43f5e' },
            amber: { name: 'Amber', color: '#f59e0b' },
            blue: { name: 'Blue', color: '#3b82f6' }
        },
        skins: {
            default: { name: 'Default', icon_path: '/pos/assets/customizer/skin-default.svg' },
            bordered: { name: 'Bordered', icon_path: '/pos/assets/customizer/skin-border.svg' }
        },
        layout_menu: {
            expanded: { name: 'Expanded', icon_path: '/pos/assets/customizer/layouts-expanded.svg' },
            collapsed: { name: 'Collapsed', icon_path: '/pos/assets/customizer/layouts-collapsed.svg' }
        },
        layout_navbar: {
            sticky: { name: 'Sticky', icon_path: '/pos/assets/customizer/navbar-sticky.svg' },
            static: { name: 'Static', icon_path: '/pos/assets/customizer/navbar-static.svg' },
            hidden: { name: 'Hidden', icon_path: '/pos/assets/customizer/navbar-hidden.svg' }
        },
        layout_content: {
            wide: { name: 'Wide', icon_path: '/pos/assets/customizer/content-wide.svg' },
            compact: { name: 'Compact', icon_path: '/pos/assets/customizer/content-compact.svg' }
        }
    };

    // State Management
    let state = { ...DEFAULTS };

    /**
     * Initialize Application
     */
    function init() {
        loadState();
        applyState();
        // Wait for DOM to be ready for HTML injection
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                createCustomizerHTML();
                setupEventListeners();
                refreshUI(); // Ensure UI matches state
            });
        } else {
            createCustomizerHTML();
            setupEventListeners();
            refreshUI();
        }
    }

    /**
     * Load settings from LocalStorage
     */
    function loadState() {
        const saved = localStorage.getItem('pos_config');
        if (saved) {
            state = { ...DEFAULTS, ...JSON.parse(saved) };
        }
    }

    /**
     * Save settings to LocalStorage
     */
    function saveState() {
        localStorage.setItem('pos_config', JSON.stringify(state));
    }

    /**
     * Apply all current settings to the DOM
     */
    function applyState() {
        // 1. Theme
        document.documentElement.setAttribute('data-theme', state.theme);

        // 2. Primary Color
        document.documentElement.setAttribute('data-primary', state.primary);

        // 3. Skin
        document.body.setAttribute('data-skin', state.skin);

        // 4. Menu (Sidebar)
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('main-content');
        if (sidebar && mainContent) {
            // Only apply if NOT mobile
            if (window.innerWidth >= 1024) {
                if (state.layout_menu === 'collapsed') {
                    sidebar.classList.add('collapsed');
                    mainContent.style.marginLeft = '80px';
                } else {
                    sidebar.classList.remove('collapsed');
                    // Check if sidebar is currently open/closed via other means? 
                    // Usually we just set the margin.
                    mainContent.style.marginLeft = '256px';
                }
            }
            // Update Toggle Button Icon if exists
            const toggleIcon = document.getElementById('toggle-icon');
            if (toggleIcon) {
                state.layout_menu === 'collapsed' ? toggleIcon.classList.add('rotate-180') : toggleIcon.classList.remove('rotate-180');
            }
        }

        // 5. Navbar
        const navbar = document.querySelector('nav');
        if (navbar) {
            // Remove all possible classes
            navbar.classList.remove('sticky', 'top-0', 'hidden', 'relative', 'fixed');
            document.body.classList.remove('navbar-sticky', 'navbar-static', 'navbar-hidden');

            document.body.classList.add(`navbar-${state.layout_navbar}`);

            if (state.layout_navbar === 'sticky') {
                navbar.classList.add('sticky', 'top-0');
            } else if (state.layout_navbar === 'hidden') {
                navbar.classList.add('hidden');
            } else {
                // Static
                navbar.classList.add('relative');
            }
        }

        // 6. Content Width
        const container = document.querySelector('.app-wrapper') || document.body;
        container.setAttribute('data-content', state.layout_content);
    }

    /**
     * Generate and Append Customizer HTML
     */
    function createCustomizerHTML() {
        if (document.getElementById('template-customizer')) return;

        const html = `
            <div id="template-customizer" class="customizer-panel">
                <!-- Toggle Button -->
                <button id="customizer-toggle" class="customizer-toggle">
                    <i class="fas fa-cog fa-spin"></i>
                </button>

                <!-- Panel Content -->
                <div class="customizer-content">
                    <div class="customizer-header">
                        <h3 class="text-lg font-bold text-slate-800">Template Customizer</h3>
                        <p class="text-xs text-slate-500">Customize and preview in real time</p>
                        <div class="header-actions">
                            <button id="reset-customizer" class="btn-icon" title="Reset to Default"><i class="fas fa-redo-alt"></i></button>
                            <button id="close-customizer" class="btn-icon"><i class="fas fa-times"></i></button>
                        </div>
                    </div>

                    <div class="customizer-body custom-scroll">
                        <!-- SECTION: THEME -->
                        <div class="customizer-section">
                            <span class="section-badge px-2 py-1 bg-slate-100 text-slate-600 rounded text-[10px] font-bold uppercase tracking-wider mb-2 inline-block">Theming</span>
                            
                            <!-- Primary Color -->
                            <div class="control-group mb-4">
                                <label class="block text-sm font-bold text-slate-700 mb-2">Primary Color</label>
                                <div class="color-swatches flex gap-2">
                                    ${Object.entries(CONFIG.primaryColors).map(([key, val]) => `
                                        <div class="swatch-item w-8 h-8 rounded-full cursor-pointer transition-transform hover:scale-110 flex items-center justify-center ${state.primary === key ? 'active ring-2 ring-offset-2 ring-slate-400' : ''}" data-val="${key}" title="${val.name}" style="background-color: ${val.color};">
                                            <i class="fas fa-check text-white text-xs ${state.primary === key ? 'block' : 'hidden'}"></i>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>

                            <!-- Theme Mode -->
                            <div class="control-group mb-4">
                                <label class="block text-sm font-bold text-slate-700 mb-2">Theme</label>
                                <div class="grid-options grid grid-cols-3 gap-2">
                                    ${Object.entries(CONFIG.themes).map(([key, val]) => `
                                        <div class="option-item cursor-pointer border border-slate-200 rounded-lg p-2 text-center hover:bg-slate-50 transition-all ${state.theme === key ? 'active border-teal-500 bg-teal-50 text-teal-600 font-bold' : 'text-slate-500'}" data-type="theme" data-val="${key}">
                                            <i class="fas ${val.icon} mb-1"></i>
                                            <span class="block text-xs">${val.name}</span>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>

                            <!-- Skins -->
                            <div class="control-group mb-4">
                                <label class="block text-sm font-bold text-slate-700 mb-2">Skins</label>
                                <div class="grid-options grid grid-cols-2 gap-3">
                                    ${Object.entries(CONFIG.skins).map(([key, val]) => `
                                        <div class="option-card cursor-pointer group text-center" data-type="skin" data-val="${key}">
                                            <div class="img-box bg-slate-50 border rounded-lg overflow-hidden mb-2 transition-all p-3 h-24 flex items-center justify-center ${state.skin === key ? 'active border-teal-500 ring-2 ring-teal-500 ring-offset-1' : 'border-slate-200 group-hover:border-teal-300'}">
                                                <img src="${val.icon_path}" alt="${val.name}" class="w-full h-auto max-h-full object-contain">
                                            </div>
                                            <span class="block text-xs font-medium text-slate-600">${val.name}</span>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        </div>

                        <hr class="divider my-4 border-slate-100">

                        <!-- SECTION: LAYOUT -->
                        <div class="customizer-section">
                            <span class="section-badge px-2 py-1 bg-slate-100 text-slate-600 rounded text-[10px] font-bold uppercase tracking-wider mb-2 inline-block">Layout</span>

                            <!-- Menu (Navigation) -->
                            <div class="control-group mb-4">
                                <label class="block text-sm font-bold text-slate-700 mb-2">Menu (Navigation)</label>
                                <div class="grid-options grid grid-cols-2 gap-3">
                                    ${Object.entries(CONFIG.layout_menu).map(([key, val]) => `
                                        <div class="option-card cursor-pointer group text-center" data-type="layout_menu" data-val="${key}">
                                            <div class="img-box bg-slate-50 border rounded-lg overflow-hidden mb-2 transition-all p-3 h-24 flex items-center justify-center ${state.layout_menu === key ? 'active border-teal-500 ring-2 ring-teal-500 ring-offset-1' : 'border-slate-200 group-hover:border-teal-300'}">
                                                <img src="${val.icon_path}" alt="${val.name}" class="w-full h-auto max-h-full object-contain">
                                            </div>
                                            <span class="block text-xs font-medium text-slate-600">${val.name}</span>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>

                            <!-- Navbar -->
                            <div class="control-group mb-4">
                                <label class="block text-sm font-bold text-slate-700 mb-2">Navbar Type</label>
                                <div class="grid-options grid grid-cols-3 gap-3">
                                    ${Object.entries(CONFIG.layout_navbar).map(([key, val]) => `
                                        <div class="option-card cursor-pointer group text-center" data-type="layout_navbar" data-val="${key}">
                                            <div class="img-box bg-slate-50 border rounded-lg overflow-hidden mb-2 transition-all p-2 h-20 flex items-center justify-center ${state.layout_navbar === key ? 'active border-teal-500 ring-2 ring-teal-500 ring-offset-1' : 'border-slate-200 group-hover:border-teal-300'}">
                                                <img src="${val.icon_path}" alt="${val.name}" class="w-full h-auto max-h-full object-contain">
                                            </div>
                                            <span class="block text-xs font-medium text-slate-600">${val.name}</span>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>

                            <!-- Content -->
                            <div class="control-group mb-4">
                                <label class="block text-sm font-bold text-slate-700 mb-2">Content Width</label>
                                <div class="grid-options grid grid-cols-2 gap-3">
                                    ${Object.entries(CONFIG.layout_content).map(([key, val]) => `
                                        <div class="option-card cursor-pointer group text-center" data-type="layout_content" data-val="${key}">
                                            <div class="img-box bg-slate-50 border rounded-lg overflow-hidden mb-2 transition-all p-3 h-24 flex items-center justify-center ${state.layout_content === key ? 'active border-teal-500 ring-2 ring-teal-500 ring-offset-1' : 'border-slate-200 group-hover:border-teal-300'}">
                                                <img src="${val.icon_path}" alt="${val.name}" class="w-full h-auto max-h-full object-contain">
                                            </div>
                                            <span class="block text-xs font-medium text-slate-600">${val.name}</span>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="customizer-backdrop fixed inset-0 bg-slate-900/20 backdrop-blur-sm z-40 hidden"></div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', html);
    }

    /**
     * Setup Event Listeners
     */
    function setupEventListeners() {
        const panel = document.getElementById('template-customizer');
        const toggleBtn = document.getElementById('customizer-toggle');
        const closeBtn = document.getElementById('close-customizer');
        const backdrop = document.querySelector('.customizer-backdrop');
        const resetBtn = document.getElementById('reset-customizer');

        // Toggle Panel
        function togglePanel() {
            panel.classList.toggle('open');
            if (backdrop) backdrop.classList.toggle('hidden');
        }

        if (toggleBtn) toggleBtn.addEventListener('click', togglePanel);
        if (closeBtn) closeBtn.addEventListener('click', togglePanel);
        if (backdrop) backdrop.addEventListener('click', togglePanel);

        // Reset
        if (resetBtn) {
            resetBtn.addEventListener('click', () => {
                state = { ...DEFAULTS };
                // Also remove LocalStorage specific item
                localStorage.removeItem('pos_config');
                // Re-Save defaults
                saveState();
                applyState();
                refreshUI();
            });
        }

        // Primary Color Swatches
        panel.querySelectorAll('.swatch-item').forEach(el => {
            el.addEventListener('click', () => {
                state.primary = el.dataset.val;
                updateSetting('primary', el);

                // Visual update for swatches
                panel.querySelectorAll('.swatch-item .fa-check').forEach(i => i.classList.add('hidden'));
                el.querySelector('.fa-check').classList.remove('hidden');

                panel.querySelectorAll('.swatch-item').forEach(s => {
                    s.classList.remove('active', 'ring-2', 'ring-offset-2', 'ring-slate-400');
                });
                el.classList.add('active', 'ring-2', 'ring-offset-2', 'ring-slate-400');
            });
        });

        // Theme
        panel.querySelectorAll('.option-item[data-type="theme"]').forEach(el => {
            el.addEventListener('click', () => {
                state.theme = el.dataset.val;
                updateSetting('theme', el);
            });
        });

        // Layouts/Skins (Refactored for new DOM structure)
        panel.querySelectorAll('.option-card').forEach(el => {
            el.addEventListener('click', () => {
                const type = el.dataset.type;
                const val = el.dataset.val;
                state[type] = val;
                updateSettingCard(el);
            });
        });
    }

    /**
     * Helper to update UI and State (Legacy for simple buttons)
     */
    function updateSetting(key, activeEl) {
        saveState();
        applyState();

        const parent = activeEl.parentElement;
        Array.from(parent.children).forEach(child => {
            child.classList.remove('active', 'border-teal-500', 'bg-teal-50', 'text-teal-600', 'font-bold', 'ring-1', 'ring-teal-500');
            if (child.classList.contains('option-item')) child.classList.add('text-slate-500');
        });

        activeEl.classList.add('active');
        if (activeEl.classList.contains('option-item')) {
            activeEl.classList.remove('text-slate-500');
            activeEl.classList.add('border-teal-500', 'bg-teal-50', 'text-teal-600', 'font-bold');
        }
    }

    /**
     * Helper to update UI and State for new Card style
     */
    function updateSettingCard(activeEl) {
        saveState();
        applyState();

        // Remove active style from siblings' img-boxes
        const parent = activeEl.parentElement;
        Array.from(parent.children).forEach(child => {
            const imgBox = child.querySelector('.img-box');
            if (imgBox) {
                imgBox.classList.remove('active', 'border-teal-500', 'ring-2', 'ring-teal-500', 'ring-offset-1');
                imgBox.classList.add('border-slate-200');
            }
        });

        // Add active style to current img-box
        const activeBox = activeEl.querySelector('.img-box');
        if (activeBox) {
            activeBox.classList.remove('border-slate-200');
            activeBox.classList.add('active', 'border-teal-500', 'ring-2', 'ring-teal-500', 'ring-offset-1');
        }
    }

    /**
     * Refresh UI to match current state (used after Reset)
     */
    function refreshUI() {
        const panel = document.getElementById('template-customizer');
        if (!panel) return;

        // 1. Primary
        panel.querySelectorAll('.swatch-item').forEach(el => {
            if (el.dataset.val === state.primary) {
                el.querySelector('.fa-check').classList.remove('hidden');
                el.classList.add('active', 'ring-2', 'ring-offset-2', 'ring-slate-400');
            } else {
                el.querySelector('.fa-check').classList.add('hidden');
                el.classList.remove('active', 'ring-2', 'ring-offset-2', 'ring-slate-400');
            }
        });

        // 2. Theme
        panel.querySelectorAll('.option-item[data-type="theme"]').forEach(el => {
            if (el.dataset.val === state.theme) {
                el.classList.add('active', 'border-teal-500', 'bg-teal-50', 'text-teal-600', 'font-bold');
                el.classList.remove('text-slate-500');
            } else {
                el.classList.remove('active', 'border-teal-500', 'bg-teal-50', 'text-teal-600', 'font-bold');
                el.classList.add('text-slate-500');
            }
        });

        // 3. Skins & Layouts (Cards)
        panel.querySelectorAll('.option-card').forEach(el => {
            const type = el.dataset.type;
            const val = el.dataset.val;
            const imgBox = el.querySelector('.img-box');
            if (state[type] === val) {
                imgBox.classList.remove('border-slate-200');
                imgBox.classList.add('active', 'border-teal-500', 'ring-2', 'ring-teal-500', 'ring-offset-1');
            } else {
                imgBox.classList.remove('active', 'border-teal-500', 'ring-2', 'ring-teal-500', 'ring-offset-1');
                imgBox.classList.add('border-slate-200');
            }
        });
    }

    // Initialize (safe check)
    init();

})();
