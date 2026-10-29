<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="description" content="OfficeSpace Document Editor">
    <title><?= esc($doc['title'] ?? 'Untitled Document') ?> — OfficeSpace Docs</title>
    
    <!-- Modern Typography: Inter & Roboto -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Roboto:ital,wght@0,400;0,500;0,700;1,400;1,700&display=swap" rel="stylesheet">
    
    <!-- CSRF Tokens for Autosave -->
    <meta name="csrf-token" content="<?= csrf_hash() ?>">
    <meta name="csrf-name" content="<?= csrf_token() ?>">

    <!-- Editor Stylesheet -->
    <link rel="stylesheet" href="<?= base_url('assets/css/editor.css') ?>">
</head>
<body class="editor-body">
    <!-- Top Header Bar -->
    <header class="editor-header">
        <div class="editor-header-inner">
            <div class="editor-brand-group">
                <a href="<?= site_url('dashboard') ?>" class="editor-docs-badge" title="Back to OfficeSpace Dashboard">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                </a>
                
                <div class="editor-meta-group">
                    <div class="editor-title-row">
                        <input type="text" id="docTitleInput" class="editor-title-input" value="<?= esc($doc['title'] ?? 'Untitled Document') ?>" placeholder="Untitled Document" maxlength="255" autocomplete="off" spellcheck="false" title="Click to rename document">
                        
                        <div id="saveStatusBadge" class="save-status-badge saved">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            <span id="saveStatusText">Saved to cloud</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="editor-actions-right">
                <!-- Manual Save Action -->
                <button type="button" id="btnManualSave" class="btn-header-action" title="Save changes (Ctrl+S)">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17 21 17 13 7 13 7 21"></polyline>
                        <polyline points="7 3 7 8 15 8"></polyline>
                    </svg>
                    <span>Save</span>
                </button>

                <!-- Page Setup Button -->
                <button type="button" id="btnPageSetup" class="btn-header-action" title="Page Setup (A4/Letter, Margins, Orientation)">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="4" y="2" width="16" height="20" rx="2"></rect>
                        <line x1="8" y1="6" x2="16" y2="6"></line>
                        <line x1="8" y1="10" x2="16" y2="10"></line>
                        <line x1="8" y1="14" x2="12" y2="14"></line>
                    </svg>
                    <span>Page Setup</span>
                </button>

                <!-- Header & Footer Button -->
                <button type="button" id="btnHeaderFooter" class="btn-header-action" title="Header, Footer & Page Numbers">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 4h16v3H4z"></path>
                        <path d="M4 17h16v3H4z"></path>
                        <line x1="4" y1="11" x2="20" y2="11" stroke-dasharray="2 2"></line>
                    </svg>
                    <span>Header & Footer</span>
                </button>

                <!-- Print Action -->
                <button type="button" id="btnPrint" class="btn-header-action" title="Print document (Ctrl+P)">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 9 6 2 18 2 18 9"></polyline>
                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                        <rect x="6" y="14" width="12" height="8"></rect>
                    </svg>
                    <span>Print</span>
                </button>

                <!-- Export Dropdown -->
                <div class="btn-export-dropdown">
                    <button type="button" id="exportDropdownBtn" class="btn-header-action" title="Export document options">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
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
                        <a href="<?= site_url("documents/export/html/{$doc['id']}") ?>" class="export-menu-item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="16 18 22 12 16 6"></polyline>
                                <polyline points="8 6 2 12 8 18"></polyline>
                            </svg>
                            <span>Download as HTML (.html)</span>
                        </a>
                        <a href="<?= site_url("documents/export/text/{$doc['id']}") ?>" class="export-menu-item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                            </svg>
                            <span>Download as Plain Text (.txt)</span>
                        </a>
                    </div>
                </div>

                <a href="<?= site_url('dashboard') ?>" class="btn-header-action" title="Return to Dashboard">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7"></rect>
                        <rect x="14" y="3" width="7" height="7"></rect>
                        <rect x="14" y="14" width="7" height="7"></rect>
                        <rect x="3" y="14" width="7" height="7"></rect>
                    </svg>
                    <span>Dashboard</span>
                </a>

                <div class="user-editor-avatar" title="<?= esc($user['email'] ?? '') ?>">
                    <?= esc($user['avatarLetter'] ?? 'U') ?>
                </div>
            </div>
        </div>

        <!-- Google Docs Style Formatting Toolbar -->
        <div class="editor-toolbar" role="toolbar" aria-label="Formatting Toolbar">
            <!-- Undo & Redo -->
            <div class="toolbar-group">
                <button type="button" id="btnUndo" class="toolbar-btn" title="Undo (Ctrl+Z)" aria-label="Undo">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="1 4 1 10 7 10"></polyline>
                        <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                    </svg>
                </button>
                <button type="button" id="btnRedo" class="toolbar-btn" title="Redo (Ctrl+Y)" aria-label="Redo">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="23 4 23 10 17 10"></polyline>
                        <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
                    </svg>
                </button>
                <button type="button" id="btnClearFormat" class="toolbar-btn" title="Clear Formatting" aria-label="Clear formatting">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>

            <div class="toolbar-separator"></div>

            <!-- Styles & Headings -->
            <div class="toolbar-group">
                <select id="headingSelect" class="toolbar-select" aria-label="Paragraph Style">
                    <option value="p">Normal text</option>
                    <option value="h1">Heading 1</option>
                    <option value="h2">Heading 2</option>
                    <option value="h3">Heading 3</option>
                </select>
            </div>

            <div class="toolbar-separator"></div>

            <!-- Font Family -->
            <div class="toolbar-group">
                <select id="fontFamilySelect" class="toolbar-select font-family-select" aria-label="Font Family" title="Font">
                    <option value="Inter" style="font-family: 'Inter', sans-serif;" selected>Inter</option>
                    <option value="Arial" style="font-family: Arial, sans-serif;">Arial</option>
                    <option value="Roboto" style="font-family: 'Roboto', sans-serif;">Roboto</option>
                    <option value="Georgia" style="font-family: Georgia, serif;">Georgia</option>
                    <option value="Times New Roman" style="font-family: 'Times New Roman', serif;">Times New Roman</option>
                    <option value="Courier New" style="font-family: 'Courier New', monospace;">Courier New</option>
                    <option value="Verdana" style="font-family: Verdana, sans-serif;">Verdana</option>
                    <option value="Trebuchet MS" style="font-family: 'Trebuchet MS', sans-serif;">Trebuchet MS</option>
                </select>
            </div>

            <div class="toolbar-separator"></div>

            <!-- Font Size -->
            <div class="toolbar-group font-size-group">
                <button type="button" id="btnDecreaseFontSize" class="toolbar-btn btn-font-step" title="Decrease font size" aria-label="Decrease font size">
                    <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                </button>
                <select id="fontSizeSelect" class="toolbar-select font-size-select" aria-label="Font Size" title="Font size">
                    <option value="8pt">8</option>
                    <option value="9pt">9</option>
                    <option value="10pt">10</option>
                    <option value="11pt" selected>11</option>
                    <option value="12pt">12</option>
                    <option value="14pt">14</option>
                    <option value="18pt">18</option>
                    <option value="24pt">24</option>
                    <option value="30pt">30</option>
                    <option value="36pt">36</option>
                    <option value="48pt">48</option>
                    <option value="72pt">72</option>
                </select>
                <button type="button" id="btnIncreaseFontSize" class="toolbar-btn btn-font-step" title="Increase font size" aria-label="Increase font size">
                    <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                </button>
            </div>

            <div class="toolbar-separator"></div>

            <!-- Text Formatting -->
            <div class="toolbar-group">
                <button type="button" id="btnBold" class="toolbar-btn" title="Bold (Ctrl+B)" aria-label="Bold">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 4h8a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z"></path>
                        <path d="M6 12h9a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z"></path>
                    </svg>
                </button>
                <button type="button" id="btnItalic" class="toolbar-btn" title="Italic (Ctrl+I)" aria-label="Italic">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="19" y1="4" x2="10" y2="4"></line>
                        <line x1="14" y1="20" x2="5" y2="20"></line>
                        <line x1="15" y1="4" x2="9" y2="20"></line>
                    </svg>
                </button>
                <button type="button" id="btnUnderline" class="toolbar-btn" title="Underline (Ctrl+U)" aria-label="Underline">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 3v7a6 6 0 0 0 6 6 6 6 0 0 0 6-6V3"></path>
                        <line x1="4" y1="21" x2="20" y2="21"></line>
                    </svg>
                </button>
                <button type="button" id="btnStrike" class="toolbar-btn" title="Strikethrough" aria-label="Strikethrough">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17.5 19H9a7 7 0 1 1 0-14h9"></path>
                        <line x1="4" y1="12" x2="20" y2="12"></line>
                    </svg>
                </button>
            </div>

            <div class="toolbar-separator"></div>

            <!-- Colors -->
            <div class="toolbar-group">
                <!-- Text Color -->
                <div class="color-picker-wrap">
                    <button type="button" id="textColorBtn" class="toolbar-btn" title="Text Color" aria-label="Text color">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="4 20 12 4 20 20"></polyline>
                            <line x1="7" y1="15" x2="17" y2="15"></line>
                        </svg>
                    </button>
                    <div id="textColorPalette" class="color-dropdown-palette">
                        <button type="button" class="color-swatch-btn" style="background:#0f172a;" data-color="#0f172a" title="Dark Slate"></button>
                        <button type="button" class="color-swatch-btn" style="background:#475569;" data-color="#475569" title="Slate Gray"></button>
                        <button type="button" class="color-swatch-btn" style="background:#2563eb;" data-color="#2563eb" title="Royal Blue"></button>
                        <button type="button" class="color-swatch-btn" style="background:#059669;" data-color="#059669" title="Emerald Green"></button>
                        <button type="button" class="color-swatch-btn" style="background:#dc2626;" data-color="#dc2626" title="Crimson Red"></button>
                        <button type="button" class="color-swatch-btn" style="background:#d97706;" data-color="#d97706" title="Amber Orange"></button>
                        <button type="button" class="color-swatch-btn" style="background:#7c3aed;" data-color="#7c3aed" title="Purple"></button>
                        <button type="button" class="color-swatch-btn" style="background:#db2777;" data-color="#db2777" title="Pink"></button>
                    </div>
                </div>

                <!-- Highlight Color -->
                <div class="color-picker-wrap">
                    <button type="button" id="highlightBtn" class="toolbar-btn" title="Highlight Color" aria-label="Highlight color">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="m9 11-6 6v3h3l6-6"></path>
                            <path d="m22 12-4.6 4.6a2 2 0 0 1-2.8 0l-5.2-5.2a2 2 0 0 1 0-2.8L14 4"></path>
                        </svg>
                    </button>
                    <div id="highlightPalette" class="color-dropdown-palette">
                        <button type="button" class="color-swatch-btn" style="background:#fef08a;" data-color="#fef08a" title="Yellow Highlight"></button>
                        <button type="button" class="color-swatch-btn" style="background:#bbf7d0;" data-color="#bbf7d0" title="Green Highlight"></button>
                        <button type="button" class="color-swatch-btn" style="background:#bae6fd;" data-color="#bae6fd" title="Blue Highlight"></button>
                        <button type="button" class="color-swatch-btn" style="background:#fbcfe8;" data-color="#fbcfe8" title="Pink Highlight"></button>
                        <button type="button" class="color-swatch-btn" style="background:#fed7aa;" data-color="#fed7aa" title="Orange Highlight"></button>
                        <button type="button" class="color-swatch-btn" style="background:#ffffff; border:1px dashed #cbd5e1;" data-color="transparent" title="No Highlight"></button>
                    </div>
                </div>

                <!-- Link -->
                <button type="button" id="btnLink" class="toolbar-btn" title="Insert Link" aria-label="Insert link">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path>
                        <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>
                    </svg>
                </button>
            </div>

            <div class="toolbar-separator"></div>

            <!-- Alignment -->
            <div class="toolbar-group">
                <button type="button" id="btnAlignLeft" class="toolbar-btn" title="Align Left" aria-label="Align left">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="21" y1="6" x2="3" y2="6"></line>
                        <line x1="15" y1="12" x2="3" y2="12"></line>
                        <line x1="17" y1="18" x2="3" y2="18"></line>
                    </svg>
                </button>
                <button type="button" id="btnAlignCenter" class="toolbar-btn" title="Align Center" aria-label="Align center">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="21" y1="6" x2="3" y2="6"></line>
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <line x1="21" y1="18" x2="3" y2="18"></line>
                    </svg>
                </button>
                <button type="button" id="btnAlignRight" class="toolbar-btn" title="Align Right" aria-label="Align right">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="21" y1="6" x2="3" y2="6"></line>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                        <line x1="21" y1="18" x2="7" y2="18"></line>
                    </svg>
                </button>
                <button type="button" id="btnAlignJustify" class="toolbar-btn" title="Justify" aria-label="Justify">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="21" y1="6" x2="3" y2="6"></line>
                        <line x1="21" y1="12" x2="3" y2="12"></line>
                        <line x1="21" y1="18" x2="3" y2="18"></line>
                    </svg>
                </button>
            </div>

            <div class="toolbar-separator"></div>

            <!-- Lists & Blocks -->
            <div class="toolbar-group">
                <button type="button" id="btnBulletList" class="toolbar-btn" title="Bulleted List" aria-label="Bulleted list">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="8" y1="6" x2="21" y2="6"></line>
                        <line x1="8" y1="12" x2="21" y2="12"></line>
                        <line x1="8" y1="18" x2="21" y2="18"></line>
                        <line x1="3" y1="6" x2="3.01" y2="6"></line>
                        <line x1="3" y1="12" x2="3.01" y2="12"></line>
                        <line x1="3" y1="18" x2="3.01" y2="18"></line>
                    </svg>
                </button>
                <button type="button" id="btnOrderedList" class="toolbar-btn" title="Numbered List" aria-label="Numbered list">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="10" y1="6" x2="21" y2="6"></line>
                        <line x1="10" y1="12" x2="21" y2="12"></line>
                        <line x1="10" y1="18" x2="21" y2="18"></line>
                        <path d="M4 6h1v4"></path>
                        <path d="M4 10h2"></path>
                        <path d="M6 18H4c0-1 2-2 2-3s-1-1.5-2-1"></path>
                    </svg>
                </button>
                <button type="button" id="btnBlockquote" class="toolbar-btn" title="Quote" aria-label="Blockquote">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 21c3 0 7-1 7-8V5c0-1.25-.756-2.017-2-2H4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2 1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V20c0 1 0 1 1 1z"></path>
                        <path d="M15 21c3 0 7-1 7-8V5c0-1.25-.757-2.017-2-2h-4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2 1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V20c0 1 0 1 1 1z"></path>
                    </svg>
                </button>
                <button type="button" id="btnCodeBlock" class="toolbar-btn" title="Code Block" aria-label="Code block">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="16 18 22 12 16 6"></polyline>
                        <polyline points="8 6 2 12 8 18"></polyline>
                    </svg>
                </button>
                <button type="button" id="btnHr" class="toolbar-btn" title="Horizontal Divider" aria-label="Horizontal rule">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                </button>
            </div>

            <div class="toolbar-separator"></div>

            <!-- Zoom & Page Layout Quick Controls -->
            <div class="toolbar-group">
                <div class="zoom-picker-wrap">
                    <span class="zoom-icon-label" title="Zoom level">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            <line x1="11" y1="8" x2="11" y2="14"></line>
                            <line x1="8" y1="11" x2="14" y2="11"></line>
                        </svg>
                    </span>
                    <select id="zoomSelect" class="toolbar-select zoom-select" aria-label="Canvas Zoom">
                        <option value="fit">Fit</option>
                        <option value="50">50%</option>
                        <option value="75">75%</option>
                        <option value="90">90%</option>
                        <option value="100" selected>100%</option>
                        <option value="125">125%</option>
                        <option value="150">150%</option>
                    </select>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Multi-Page A4 Document Canvas -->
    <main class="editor-workspace" id="editorWorkspace">
        <div id="editorZoomWrapper" class="editor-zoom-wrapper">
            <div id="editorPaper" class="editor-paper" role="textbox" aria-multiline="true"></div>
        </div>
    </main>

    <!-- Bottom Status Bar -->
    <footer class="editor-bottom-bar">
        <div class="editor-bottom-metrics">
            <span id="pageCountDisplay" class="metric-pill">Page 1 of 1</span>
            <span id="wordCountDisplay" class="metric-pill">0 words</span>
            <span id="charCountDisplay" class="metric-pill">0 characters</span>
        </div>
        <div class="editor-bottom-info">
            <span id="pageSizeDisplay">A4 &bull; Portrait</span>
            <span class="info-dot">&bull;</span>
            <span>OfficeSpace Cloud Synced</span>
        </div>
    </footer>

    <!-- Page Setup Modal -->
    <div id="pageSetupModal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="pageSetupTitle">
        <div class="modal-box modal-box-large">
            <div class="modal-header">
                <div class="modal-title-wrap">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="4" y="2" width="16" height="20" rx="2"></rect>
                        <line x1="8" y1="6" x2="16" y2="6"></line>
                        <line x1="8" y1="10" x2="16" y2="10"></line>
                    </svg>
                    <h3 class="modal-title" id="pageSetupTitle">Page Setup</h3>
                </div>
                <button type="button" class="modal-close-btn btn-close-modal" aria-label="Close modal">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <!-- Orientation & Paper Size -->
                <div class="modal-grid-2col">
                    <div class="form-group">
                        <label class="form-label" for="pageSetupOrientation">Orientation</label>
                        <div class="radio-card-group">
                            <label class="radio-card">
                                <input type="radio" name="pageOrientation" value="portrait" id="orientPortrait" checked>
                                <div class="radio-card-content">
                                    <div class="orient-icon portrait-icon"></div>
                                    <span>Portrait</span>
                                </div>
                            </label>
                            <label class="radio-card">
                                <input type="radio" name="pageOrientation" value="landscape" id="orientLandscape">
                                <div class="radio-card-content">
                                    <div class="orient-icon landscape-icon"></div>
                                    <span>Landscape</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="pageSetupPaperSize">Paper Size</label>
                        <select id="pageSetupPaperSize" class="form-control">
                            <option value="A4" selected>A4 (210 × 297 mm)</option>
                            <option value="Letter">Letter (8.5 × 11 in)</option>
                        </select>
                        <span class="form-help-text">Standard ISO 216 dimensions for crisp printing & PDF export.</span>
                    </div>
                </div>

                <!-- Margins Section -->
                <div class="form-section-divider">
                    <span>Margins</span>
                </div>

                <div class="margin-preset-buttons">
                    <button type="button" class="btn-preset-margin active" data-preset="normal">Normal (25 mm / 1 in)</button>
                    <button type="button" class="btn-preset-margin" data-preset="narrow">Narrow (12.7 mm / 0.5 in)</button>
                    <button type="button" class="btn-preset-margin" data-preset="wide">Wide (35 mm / 1.4 in)</button>
                </div>

                <div class="modal-grid-4col">
                    <div class="form-group">
                        <label class="form-label" for="marginTopInput">Top (px)</label>
                        <input type="number" id="marginTopInput" class="form-control" min="20" max="200" value="60">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="marginBottomInput">Bottom (px)</label>
                        <input type="number" id="marginBottomInput" class="form-control" min="20" max="200" value="60">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="marginLeftInput">Left (px)</label>
                        <input type="number" id="marginLeftInput" class="form-control" min="20" max="200" value="70">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="marginRightInput">Right (px)</label>
                        <input type="number" id="marginRightInput" class="form-control" min="20" max="200" value="70">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modal-cancel btn-close-modal">Cancel</button>
                <button type="button" id="btnApplyPageSetup" class="btn-modal-action">Apply Page Setup</button>
            </div>
        </div>
    </div>

    <!-- Header & Footer Modal -->
    <div id="headerFooterModal" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="hfModalTitle">
        <div class="modal-box modal-box-large">
            <div class="modal-header">
                <div class="modal-title-wrap">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 4h16v3H4z"></path>
                        <path d="M4 17h16v3H4z"></path>
                        <line x1="4" y1="11" x2="20" y2="11" stroke-dasharray="2 2"></line>
                    </svg>
                    <h3 class="modal-title" id="hfModalTitle">Header, Footer & Page Numbers</h3>
                </div>
                <button type="button" class="modal-close-btn btn-close-modal" aria-label="Close modal">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <!-- Header Controls -->
                <div class="form-section-divider">
                    <span>Header Content (Appears at top of pages)</span>
                </div>
                <div class="modal-grid-2col">
                    <div class="form-group">
                        <label class="form-label" for="headerLeftInput">Header Left</label>
                        <input type="text" id="headerLeftInput" class="form-control" placeholder="e.g. Document Title or Company Name" maxlength="200">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="headerRightInput">Header Right</label>
                        <input type="text" id="headerRightInput" class="form-control" placeholder="e.g. Confidential, Date or {page}" maxlength="200">
                    </div>
                </div>

                <!-- Footer Controls -->
                <div class="form-section-divider">
                    <span>Footer Content & Page Numbers (Appears at bottom of pages)</span>
                </div>
                <div class="modal-grid-2col">
                    <div class="form-group">
                        <label class="form-label" for="footerLeftInput">Footer Left</label>
                        <input type="text" id="footerLeftInput" class="form-control" placeholder="e.g. OfficeSpace Cloud Workspace" maxlength="200">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="footerRightInput">Footer Right / Page Number</label>
                        <input type="text" id="footerRightInput" class="form-control" placeholder="e.g. {page} or Page {page} of {total}" maxlength="200">
                        <div class="quick-tags-row">
                            <span class="quick-tag-label">Insert variable:</span>
                            <button type="button" class="btn-quick-tag" data-tag="{page}">Number (1, 2, 3)</button>
                            <button type="button" class="btn-quick-tag" data-tag="Page {page} of {total}">Page 1 of 5</button>
                            <button type="button" class="btn-quick-tag" data-tag="">Clear</button>
                        </div>
                    </div>
                </div>

                <!-- First Page Customization -->
                <div class="form-section-divider">
                    <span>Options</span>
                </div>
                <div class="checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="differentFirstPageCheck">
                        <span>Different first page (suppress header & footer on cover page)</span>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" id="btnClearHeaderFooter" class="btn-modal-cancel" style="margin-right:auto; color:#ef4444;">Remove All</button>
                <button type="button" class="btn-modal-cancel btn-close-modal">Cancel</button>
                <button type="button" id="btnApplyHeaderFooter" class="btn-modal-action">Apply Changes</button>
            </div>
        </div>
    </div>

    <!-- Configuration payload for Editor JS -->
    <script>
        window.OfficeSpaceDocConfig = {
            docId: <?= (int) $doc['id'] ?>,
            saveUrl: '<?= site_url("documents/save/{$doc['id']}") ?>',
            csrfTokenName: '<?= csrf_token() ?>',
            csrfHash: '<?= csrf_hash() ?>',
            initialContent: <?= json_encode($doc['content'] ?? '<p></p>') ?>,
            headerContent: <?= json_encode($headerContent ?? ['left' => '', 'right' => '']) ?>,
            footerContent: <?= json_encode($footerContent ?? ['left' => '', 'right' => '{page}']) ?>,
            pageSettings: <?= json_encode($pageSettings ?? [
                'paperSize'          => 'A4',
                'orientation'        => 'portrait',
                'marginTop'          => 60,
                'marginBottom'       => 60,
                'marginLeft'         => 70,
                'marginRight'        => 70,
                'differentFirstPage' => false,
            ]) ?>
        };
    </script>

    <!-- Local Standalone Tiptap Bundle & Client Script -->
    <script src="<?= base_url('assets/vendor/tiptap.bundle.min.js') ?>"></script>
    <script src="<?= base_url('assets/js/editor.js') ?>"></script>
</body>
</html>
