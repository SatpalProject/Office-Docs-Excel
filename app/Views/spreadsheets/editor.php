<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="description" content="OfficeSpace Spreadsheet Editor">
    <title><?= esc($sheet['title'] ?? 'Untitled Spreadsheet') ?> — OfficeSpace Sheets</title>
    
    <!-- Modern Typography: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- CSRF Tokens for Autosave -->
    <meta name="csrf-token" content="<?= csrf_hash() ?>">
    <meta name="csrf-name" content="<?= csrf_token() ?>">

    <!-- Spreadsheet Stylesheet -->
    <link rel="stylesheet" href="<?= base_url('assets/css/spreadsheet.css') ?>">
</head>
<body class="sheet-body">
    <!-- Top Header Bar -->
    <header class="sheet-header">
        <div class="sheet-header-inner">
            <div class="sheet-brand-group">
                <a href="<?= site_url('dashboard') ?>" class="sheet-logo-badge" title="Back to OfficeSpace Dashboard">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="3" y1="9" x2="21" y2="9"></line>
                        <line x1="3" y1="15" x2="21" y2="15"></line>
                        <line x1="9" y1="3" x2="9" y2="21"></line>
                        <line x1="15" y1="3" x2="15" y2="21"></line>
                    </svg>
                </a>
                
                <div class="sheet-meta-group">
                    <input type="text" id="sheetTitleInput" class="sheet-title-input" value="<?= esc($sheet['title'] ?? 'Untitled Spreadsheet') ?>" placeholder="Untitled Spreadsheet" maxlength="255" autocomplete="off" spellcheck="false" title="Click to rename spreadsheet">
                    
                    <div id="saveStatusBadge" class="save-status-badge saved">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        <span id="saveStatusText">Saved to cloud</span>
                    </div>
                </div>
            </div>

            <div class="sheet-actions-right">
                <!-- Export Dropdown -->
                <div class="btn-export-dropdown">
                    <button type="button" id="exportDropdownBtn" class="btn-header-action" title="Export spreadsheet options">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                        <span>Export</span>
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </button>
                    
                    <div id="exportMenuPopup" class="export-menu-popup">
                        <a href="<?= site_url("spreadsheets/export/xlsx/{$sheet['id']}") ?>" class="export-menu-item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                                <line x1="3" y1="9" x2="21" y2="9"></line>
                                <line x1="9" y1="21" x2="9" y2="9"></line>
                            </svg>
                            <span>Microsoft Excel (.xlsx)</span>
                        </a>
                        <a href="<?= site_url("spreadsheets/export/csv/{$sheet['id']}") ?>" class="export-menu-item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                            </svg>
                            <span>Comma-Separated Values (.csv)</span>
                        </a>
                    </div>
                </div>

                <a href="<?= site_url('dashboard') ?>" class="btn-header-action" title="Close and return to dashboard">
                    Dashboard
                </a>
            </div>
        </div>
    </header>

    <!-- Toolbar -->
    <div class="sheet-toolbar" role="toolbar" aria-label="Spreadsheet Formatting Tools">
        <!-- Undo / Redo -->
        <div class="toolbar-group">
            <button type="button" id="btnUndo" class="toolbar-btn" title="Undo (Ctrl+Z)" disabled>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="1 4 1 10 7 10"></polyline>
                    <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                </svg>
            </button>
            <button type="button" id="btnRedo" class="toolbar-btn" title="Redo (Ctrl+Y)" disabled>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="23 4 23 10 17 10"></polyline>
                    <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
                </svg>
            </button>
        </div>

        <div class="toolbar-separator"></div>

        <!-- Text Styling -->
        <div class="toolbar-group">
            <button type="button" id="btnBold" class="toolbar-btn" title="Bold (Ctrl+B)">
                <strong>B</strong>
            </button>
            <button type="button" id="btnItalic" class="toolbar-btn" title="Italic (Ctrl+I)">
                <em>I</em>
            </button>
        </div>

        <div class="toolbar-separator"></div>

        <!-- Text Color & Fill Color -->
        <div class="toolbar-group">
            <label class="color-picker-label" title="Text Color">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 20h16"></path>
                    <path d="m6 16 6-12 6 12"></path>
                    <path d="M8 12h8"></path>
                </svg>
                <input type="color" id="inputTextColor" value="#1e293b">
            </label>
            <label class="color-picker-label" title="Cell Fill Color">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="m19 11-8-8-8.6 8.6a2 2 0 0 0 0 2.8l5.2 5.2c.8.8 2 .8 2.8 0L19 11Z"></path>
                    <path d="m5 2 5 5"></path>
                    <path d="M2 22h20"></path>
                </svg>
                <input type="color" id="inputBgColor" value="#f8fafc">
            </label>
            <button type="button" id="btnClearFormat" class="toolbar-btn" title="Clear Formatting">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="m7 21-4.3-4.3c-1-1-1-2.5 0-3.4l9.6-9.6c1-1 2.5-1 3.4 0l5.6 5.6c1 1 1 2.5 0 3.4L13 21"></path>
                    <path d="M22 21H7"></path>
                    <path d="m5 11 9 9"></path>
                </svg>
            </button>
        </div>

        <div class="toolbar-separator"></div>

        <!-- Text Alignment -->
        <div class="toolbar-group">
            <button type="button" id="btnAlignLeft" class="toolbar-btn active" title="Align Left">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="17" y1="10" x2="3" y2="10"></line>
                    <line x1="21" y1="6" x2="3" y2="6"></line>
                    <line x1="21" y1="14" x2="3" y2="14"></line>
                    <line x1="17" y1="18" x2="3" y2="18"></line>
                </svg>
            </button>
            <button type="button" id="btnAlignCenter" class="toolbar-btn" title="Align Center">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="10" x2="6" y2="10"></line>
                    <line x1="21" y1="6" x2="3" y2="6"></line>
                    <line x1="21" y1="14" x2="3" y2="14"></line>
                    <line x1="18" y1="18" x2="6" y2="18"></line>
                </svg>
            </button>
            <button type="button" id="btnAlignRight" class="toolbar-btn" title="Align Right">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="21" y1="10" x2="7" y2="10"></line>
                    <line x1="21" y1="6" x2="3" y2="6"></line>
                    <line x1="21" y1="14" x2="3" y2="14"></line>
                    <line x1="21" y1="18" x2="7" y2="18"></line>
                </svg>
            </button>
        </div>

        <div class="toolbar-separator"></div>

        <!-- Row & Column Operations -->
        <div class="toolbar-group">
            <button type="button" id="btnAddRow" class="toolbar-btn" title="Add Row Below">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                    <line x1="12" y1="8" x2="12" y2="16"></line>
                    <line x1="8" y1="12" x2="16" y2="12"></line>
                </svg>
            </button>
            <button type="button" id="btnDeleteRow" class="toolbar-btn" title="Delete Selected Row">
                <svg viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                    <line x1="8" y1="12" x2="16" y2="12"></line>
                </svg>
            </button>
            <button type="button" id="btnAddCol" class="toolbar-btn" title="Add Column Right">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 3v18"></path>
                    <path d="M3 12h18"></path>
                </svg>
            </button>
            <button type="button" id="btnDeleteCol" class="toolbar-btn" title="Delete Selected Column">
                <svg viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2">
                    <path d="M18 6 6 18"></path>
                    <path d="m6 6 12 12"></path>
                </svg>
            </button>
        </div>

        <div class="toolbar-separator"></div>

        <!-- Quick Function Helper -->
        <div class="toolbar-group">
            <select id="formulaHelperSelect" class="formula-select" title="Insert Function">
                <option value="">fx Functions...</option>
                <option value="SUM">&Sigma; SUM</option>
                <option value="AVERAGE">x&#772; AVERAGE</option>
                <option value="MIN">&darr; MIN</option>
                <option value="MAX">&uarr; MAX</option>
                <option value="COUNT"># COUNT</option>
            </select>
        </div>
    </div>

    <!-- Formula Bar -->
    <div class="sheet-formula-bar">
        <div id="formulaNameBox" class="formula-name-box">A1</div>
        <div class="formula-fx-symbol">fx</div>
        <input type="text" id="formulaBarInput" class="formula-input" placeholder="Enter value or formula like =SUM(A1:A5)" autocomplete="off" spellcheck="false">
    </div>

    <!-- Main Spreadsheet Grid Canvas -->
    <main id="sheetWorkspace" class="sheet-workspace" tabindex="0">
        <table id="sheetGridTable" class="sheet-grid-table"></table>
        <!-- Floating Inline Cell Editor Input -->
        <input type="text" id="inlineCellEditor" class="inline-cell-editor" autocomplete="off" spellcheck="false">
    </main>

    <!-- Bottom Status Bar -->
    <footer class="sheet-bottom-bar">
        <div class="sheet-tabs-group">
            <div class="sheet-tab-pill">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                    <line x1="3" y1="9" x2="21" y2="9"></line>
                </svg>
                <span>Sheet1</span>
            </div>
        </div>

        <div class="sheet-stats-group">
            <span class="sheet-stat-item">Sum: <span id="statSum" class="sheet-stat-val">0</span></span>
            <span class="sheet-stat-item">Avg: <span id="statAvg" class="sheet-stat-val">0</span></span>
            <span class="sheet-stat-item">Count: <span id="statCount" class="sheet-stat-val">0</span></span>
        </div>
    </footer>

    <!-- Configuration payload for Spreadsheet JS -->
    <script>
        window.OfficeSpaceSheetConfig = {
            sheetId: <?= (int) $sheet['id'] ?>,
            saveUrl: '<?= site_url("spreadsheets/save/{$sheet['id']}") ?>',
            csrfTokenName: '<?= csrf_token() ?>',
            csrfHash: '<?= csrf_hash() ?>',
            initialData: <?= json_encode($gridData) ?>
        };
    </script>

    <!-- Client Spreadsheet Engine Script -->
    <script src="<?= base_url('assets/js/spreadsheet.js') ?>"></script>
</body>
</html>
