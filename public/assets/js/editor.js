/**
 * OfficeSpace — Google Docs-Inspired Document Editor Client Logic
 * Powered by Tiptap v2 + PaginationPlus with multi-page A4 layout, headers, footers, and autosave.
 */

document.addEventListener('DOMContentLoaded', function () {
    const editorConfig = window.OfficeSpaceDocConfig || {};
    const docId = editorConfig.docId;
    const saveUrl = editorConfig.saveUrl;
    let csrfTokenName = editorConfig.csrfTokenName || document.querySelector('meta[name="csrf-name"]')?.content || 'csrf_test_name';
    let csrfHash = editorConfig.csrfHash || document.querySelector('meta[name="csrf-token"]')?.content || '';

    // UI Elements
    const titleInput = document.getElementById('docTitleInput');
    const saveStatusBadge = document.getElementById('saveStatusBadge');
    const saveStatusText = document.getElementById('saveStatusText');
    const wordCountEl = document.getElementById('wordCountDisplay');
    const charCountEl = document.getElementById('charCountDisplay');
    const pageCountEl = document.getElementById('pageCountDisplay');
    const pageSizeEl = document.getElementById('pageSizeDisplay');
    const zoomSelect = document.getElementById('zoomSelect');
    const zoomWrapper = document.getElementById('editorZoomWrapper');
    const exportDropdownBtn = document.getElementById('exportDropdownBtn');
    const exportMenuPopup = document.getElementById('exportMenuPopup');
    const btnManualSave = document.getElementById('btnManualSave');
    const btnPrint = document.getElementById('btnPrint');
    const btnPageSetup = document.getElementById('btnPageSetup');
    const btnHeaderFooter = document.getElementById('btnHeaderFooter');

    // Modals
    const pageSetupModal = document.getElementById('pageSetupModal');
    const headerFooterModal = document.getElementById('headerFooterModal');
    const btnApplyPageSetup = document.getElementById('btnApplyPageSetup');
    const btnApplyHeaderFooter = document.getElementById('btnApplyHeaderFooter');
    const btnClearHeaderFooter = document.getElementById('btnClearHeaderFooter');

    // State Variables
    let isDirty = false;
    let isSaving = false;
    let saveTimeout = null;
    let pendingSave = false;

    // Document Layout Configuration
    let pageSettings = Object.assign({
        paperSize: 'A4',
        orientation: 'portrait',
        marginTop: 60,
        marginBottom: 60,
        marginLeft: 70,
        marginRight: 70,
        differentFirstPage: false,
    }, editorConfig.pageSettings || {});

    let headerContent = Object.assign({
        left: '',
        right: '',
    }, editorConfig.headerContent || {});

    let footerContent = Object.assign({
        left: '',
        right: '{page}',
    }, editorConfig.footerContent || {});

    // Compute pixel dimensions for paper sizes at 96 DPI
    function getPaperDimensions(size, orientation) {
        let width = 794;
        let height = 1123; // Default A4 portrait (210 x 297 mm)

        if (size === 'Letter') {
            width = 816;
            height = 1056; // Letter (8.5 x 11 in)
        }

        if (orientation === 'landscape') {
            const temp = width;
            width = height;
            height = temp;
        }

        return { width, height };
    }

    const initialDims = getPaperDimensions(pageSettings.paperSize, pageSettings.orientation);

    // Initialize Tiptap
    if (!window.Tiptap) {
        console.error('Tiptap library failed to load.');
        if (saveStatusText) saveStatusText.textContent = 'Editor load failed';
        return;
    }

    const {
        Editor,
        StarterKit,
        Underline,
        TextAlign,
        Link,
        Highlight,
        Color,
        TextStyle,
        CharacterCount,
        PaginationPlus,
        FontFamily,
        FontSize,
    } = window.Tiptap;

    const editor = new Editor({
        element: document.getElementById('editorPaper'),
        extensions: [
            StarterKit.configure({
                heading: {
                    levels: [1, 2, 3],
                },
                history: true,
            }),
            Underline,
            TextAlign.configure({
                types: ['heading', 'paragraph'],
            }),
            Link.configure({
                openOnClick: false,
                HTMLAttributes: {
                    target: '_blank',
                    rel: 'noopener noreferrer',
                },
            }),
            Highlight,
            TextStyle,
            Color,
            FontFamily,
            FontSize,
            CharacterCount,
            PaginationPlus.configure({
                pageWidth: initialDims.width,
                pageHeight: initialDims.height,
                pageGap: 40,
                pageGapBorderSize: 1,
                pageGapBorderColor: '#cbd5e1',
                pageBreakBackground: '#ffffff',
                marginTop: pageSettings.marginTop,
                marginBottom: pageSettings.marginBottom,
                marginLeft: pageSettings.marginLeft,
                marginRight: pageSettings.marginRight,
                contentMarginTop: 15,
                contentMarginBottom: 15,
                headerLeft: headerContent.left || '',
                headerRight: headerContent.right || '',
                footerLeft: footerContent.left || '',
                footerRight: footerContent.right || '{page}',
                customHeader: pageSettings.differentFirstPage ? { 1: { headerLeft: '', headerRight: '' } } : {},
                customFooter: pageSettings.differentFirstPage ? { 1: { footerLeft: '', footerRight: '' } } : {},
                onHeaderClick: () => openHeaderFooterModal('header'),
                onFooterClick: () => openHeaderFooterModal('footer'),
            }),
        ],
        content: editorConfig.initialContent || '<p></p>',
        autofocus: false,
        onUpdate({ editor }) {
            isDirty = true;
            updateMetrics();
            updateToolbarState();
            scheduleAutosave();
        },
        onSelectionUpdate() {
            updateToolbarState();
        },
    });

    // Pagination Layout Refresh Handler (non-looping, debounced)
    let isRefreshingPagination = false;
    function refreshPaginationLayout() {
        if (!editor || !editor.view || isRefreshingPagination) return;
        isRefreshingPagination = true;
        requestAnimationFrame(() => {
            try {
                if (editor && editor.view && !editor.isDestroyed) {
                    if (typeof editor.commands.refreshPagination === 'function') {
                        editor.commands.refreshPagination();
                    } else {
                        const tr = editor.view.state.tr.setMeta('forcePaginationRefresh', true);
                        editor.view.dispatch(tr);
                    }
                    updateMetrics();
                }
            } catch (err) {
                console.warn('Pagination refresh caught error:', err);
            } finally {
                requestAnimationFrame(() => {
                    isRefreshingPagination = false;
                });
            }
        });
    }

    // Update Live Metrics (Words, Characters, Pages)
    function updateMetrics() {
        if (!editor) return;

        // Character and word counts
        const characters = editor.storage.characterCount ? editor.storage.characterCount.characters() : 0;
        const words = editor.storage.characterCount ? editor.storage.characterCount.words() : 0;

        if (wordCountEl) wordCountEl.textContent = `${words} words`;
        if (charCountEl) charCountEl.textContent = `${characters} characters`;

        // Page count detection
        setTimeout(() => {
            const pageBreaks = document.querySelectorAll('.rm-page-break');
            const totalPages = Math.max(1, pageBreaks.length);
            if (pageCountEl) pageCountEl.textContent = `Page 1 of ${totalPages}`;

            // Replace {total} placeholder in DOM footer/header if present
            document.querySelectorAll('.rm-page-footer-right, .rm-page-footer-left, .rm-page-header-right, .rm-page-header-left').forEach(el => {
                if (el.innerHTML.includes('{total}')) {
                    el.innerHTML = el.innerHTML.replaceAll('{total}', totalPages);
                }
            });
        }, 60);

        if (pageSizeEl) {
            pageSizeEl.textContent = `${pageSettings.paperSize} • ${pageSettings.orientation.charAt(0).toUpperCase() + pageSettings.orientation.slice(1)}`;
        }
    }

    updateMetrics();

    // 1. Initial double-rAF after mount ensures DOM and initial styles are painted
    requestAnimationFrame(() => {
        requestAnimationFrame(() => {
            refreshPaginationLayout();
        });
    });

    // 2. Web fonts ready event ensures font metrics (Inter) have stabilized
    if (document.fonts && document.fonts.ready) {
        document.fonts.ready.then(() => {
            refreshPaginationLayout();
        });
    }

    // 3. Full window load event
    window.addEventListener('load', () => {
        refreshPaginationLayout();
    });

    // 4. ResizeObserver for layout changes (debounced)
    let resizeTimer = null;
    let lastObservedWidth = 0;
    const workspaceEl = document.getElementById('editorWorkspace') || document.getElementById('editorPaper');
    if (window.ResizeObserver && workspaceEl) {
        const ro = new ResizeObserver((entries) => {
            for (const entry of entries) {
                const width = Math.round(entry.contentRect.width);
                if (width > 0 && Math.abs(width - lastObservedWidth) > 5) {
                    lastObservedWidth = width;
                    if (resizeTimer) clearTimeout(resizeTimer);
                    resizeTimer = setTimeout(() => {
                        refreshPaginationLayout();
                    }, 150);
                }
            }
        });
        ro.observe(workspaceEl);
    }

    // Toolbar Buttons Mapping
    const buttons = {
        bold: document.getElementById('btnBold'),
        italic: document.getElementById('btnItalic'),
        underline: document.getElementById('btnUnderline'),
        strike: document.getElementById('btnStrike'),
        alignLeft: document.getElementById('btnAlignLeft'),
        alignCenter: document.getElementById('btnAlignCenter'),
        alignRight: document.getElementById('btnAlignRight'),
        alignJustify: document.getElementById('btnAlignJustify'),
        bulletList: document.getElementById('btnBulletList'),
        orderedList: document.getElementById('btnOrderedList'),
        blockquote: document.getElementById('btnBlockquote'),
        codeBlock: document.getElementById('btnCodeBlock'),
        hr: document.getElementById('btnHr'),
        clearFormat: document.getElementById('btnClearFormat'),
        undo: document.getElementById('btnUndo'),
        redo: document.getElementById('btnRedo'),
        link: document.getElementById('btnLink'),
        headingSelect: document.getElementById('headingSelect'),
        fontFamilySelect: document.getElementById('fontFamilySelect'),
        fontSizeSelect: document.getElementById('fontSizeSelect'),
        btnDecreaseFontSize: document.getElementById('btnDecreaseFontSize'),
        btnIncreaseFontSize: document.getElementById('btnIncreaseFontSize'),
    };

    const FONT_SIZES = ['8pt', '9pt', '10pt', '11pt', '12pt', '14pt', '18pt', '24pt', '30pt', '36pt', '48pt', '72pt'];

    // Update active toolbar state based on current cursor selection
    function updateToolbarState() {
        if (!editor) return;

        if (buttons.bold) buttons.bold.classList.toggle('active', editor.isActive('bold'));
        if (buttons.italic) buttons.italic.classList.toggle('active', editor.isActive('italic'));
        if (buttons.underline) buttons.underline.classList.toggle('active', editor.isActive('underline'));
        if (buttons.strike) buttons.strike.classList.toggle('active', editor.isActive('strike'));
        if (buttons.alignLeft) buttons.alignLeft.classList.toggle('active', editor.isActive({ textAlign: 'left' }));
        if (buttons.alignCenter) buttons.alignCenter.classList.toggle('active', editor.isActive({ textAlign: 'center' }));
        if (buttons.alignRight) buttons.alignRight.classList.toggle('active', editor.isActive({ textAlign: 'right' }));
        if (buttons.alignJustify) buttons.alignJustify.classList.toggle('active', editor.isActive({ textAlign: 'justify' }));
        if (buttons.bulletList) buttons.bulletList.classList.toggle('active', editor.isActive('bulletList'));
        if (buttons.orderedList) buttons.orderedList.classList.toggle('active', editor.isActive('orderedList'));
        if (buttons.blockquote) buttons.blockquote.classList.toggle('active', editor.isActive('blockquote'));
        if (buttons.codeBlock) buttons.codeBlock.classList.toggle('active', editor.isActive('codeBlock'));
        if (buttons.link) buttons.link.classList.toggle('active', editor.isActive('link'));

        if (buttons.headingSelect) {
            if (editor.isActive('heading', { level: 1 })) buttons.headingSelect.value = 'h1';
            else if (editor.isActive('heading', { level: 2 })) buttons.headingSelect.value = 'h2';
            else if (editor.isActive('heading', { level: 3 })) buttons.headingSelect.value = 'h3';
            else buttons.headingSelect.value = 'p';
        }

        // Font Family sync with cursor position
        if (buttons.fontFamilySelect) {
            const activeFont = editor.getAttributes('textStyle').fontFamily;
            if (activeFont) {
                const cleanFont = activeFont.replace(/['"]+/g, '');
                const matchedOption = Array.from(buttons.fontFamilySelect.options).find(opt => 
                    opt.value.toLowerCase() === cleanFont.toLowerCase() ||
                    cleanFont.toLowerCase().includes(opt.value.toLowerCase())
                );
                if (matchedOption) {
                    buttons.fontFamilySelect.value = matchedOption.value;
                }
            } else {
                buttons.fontFamilySelect.value = 'Inter';
            }
        }

        // Font Size sync with cursor position
        if (buttons.fontSizeSelect) {
            const activeSize = editor.getAttributes('textStyle').fontSize;
            if (activeSize) {
                const normalized = activeSize.toLowerCase().includes('pt')
                    ? activeSize.toLowerCase()
                    : (parseInt(activeSize, 10) + 'pt');
                const matchedSize = Array.from(buttons.fontSizeSelect.options).find(opt => opt.value.toLowerCase() === normalized);
                if (matchedSize) {
                    buttons.fontSizeSelect.value = matchedSize.value;
                }
            } else {
                buttons.fontSizeSelect.value = '11pt';
            }
        }
    }

    // Attach Toolbar Action Handlers
    if (buttons.bold) buttons.bold.addEventListener('click', () => editor.chain().focus().toggleBold().run());
    if (buttons.italic) buttons.italic.addEventListener('click', () => editor.chain().focus().toggleItalic().run());
    if (buttons.underline) buttons.underline.addEventListener('click', () => editor.chain().focus().toggleUnderline().run());
    if (buttons.strike) buttons.strike.addEventListener('click', () => editor.chain().focus().toggleStrike().run());
    if (buttons.alignLeft) buttons.alignLeft.addEventListener('click', () => editor.chain().focus().setTextAlign('left').run());
    if (buttons.alignCenter) buttons.alignCenter.addEventListener('click', () => editor.chain().focus().setTextAlign('center').run());
    if (buttons.alignRight) buttons.alignRight.addEventListener('click', () => editor.chain().focus().setTextAlign('right').run());
    if (buttons.alignJustify) buttons.alignJustify.addEventListener('click', () => editor.chain().focus().setTextAlign('justify').run());
    if (buttons.bulletList) buttons.bulletList.addEventListener('click', () => editor.chain().focus().toggleBulletList().run());
    if (buttons.orderedList) buttons.orderedList.addEventListener('click', () => editor.chain().focus().toggleOrderedList().run());
    if (buttons.blockquote) buttons.blockquote.addEventListener('click', () => editor.chain().focus().toggleBlockquote().run());
    if (buttons.codeBlock) buttons.codeBlock.addEventListener('click', () => editor.chain().focus().toggleCodeBlock().run());
    if (buttons.hr) buttons.hr.addEventListener('click', () => editor.chain().focus().setHorizontalRule().run());
    if (buttons.undo) buttons.undo.addEventListener('click', () => editor.chain().focus().undo().run());
    if (buttons.redo) buttons.redo.addEventListener('click', () => editor.chain().focus().redo().run());

    if (buttons.clearFormat) {
        buttons.clearFormat.addEventListener('click', () => {
            editor.chain().focus().unsetAllMarks().clearNodes().run();
            if (buttons.fontFamilySelect) buttons.fontFamilySelect.value = 'Inter';
            if (buttons.fontSizeSelect) buttons.fontSizeSelect.value = '11pt';
            refreshPaginationLayout();
        });
    }

    if (buttons.headingSelect) {
        buttons.headingSelect.addEventListener('change', function () {
            const val = this.value;
            if (val === 'p') editor.chain().focus().setParagraph().run();
            else if (val === 'h1') editor.chain().focus().toggleHeading({ level: 1 }).run();
            else if (val === 'h2') editor.chain().focus().toggleHeading({ level: 2 }).run();
            else if (val === 'h3') editor.chain().focus().toggleHeading({ level: 3 }).run();
            refreshPaginationLayout();
        });
    }

    // Font Family & Size Handlers
    function applyFontSize(sizeStr) {
        if (!editor) return;
        if (sizeStr) {
            editor.chain().focus().setFontSize(sizeStr).run();
        } else {
            editor.chain().focus().unsetFontSize().run();
        }
        refreshPaginationLayout();
    }

    if (buttons.fontFamilySelect) {
        buttons.fontFamilySelect.addEventListener('change', function () {
            const val = this.value;
            if (val) {
                editor.chain().focus().setFontFamily(val).run();
            } else {
                editor.chain().focus().unsetFontFamily().run();
            }
            refreshPaginationLayout();
        });
    }

    if (buttons.fontSizeSelect) {
        buttons.fontSizeSelect.addEventListener('change', function () {
            applyFontSize(this.value);
        });
    }

    if (buttons.btnDecreaseFontSize) {
        buttons.btnDecreaseFontSize.addEventListener('click', () => {
            const current = (editor.getAttributes('textStyle').fontSize || buttons.fontSizeSelect?.value || '11pt').toLowerCase();
            let idx = FONT_SIZES.findIndex(s => s.toLowerCase() === current);
            if (idx === -1) {
                const num = parseInt(current, 10);
                idx = FONT_SIZES.findIndex(s => parseInt(s, 10) >= num);
                if (idx > 0) idx = idx - 1;
                else idx = 0;
            } else if (idx > 0) {
                idx = idx - 1;
            }
            const newSize = FONT_SIZES[idx];
            if (buttons.fontSizeSelect) buttons.fontSizeSelect.value = newSize;
            applyFontSize(newSize);
        });
    }

    if (buttons.btnIncreaseFontSize) {
        buttons.btnIncreaseFontSize.addEventListener('click', () => {
            const current = (editor.getAttributes('textStyle').fontSize || buttons.fontSizeSelect?.value || '11pt').toLowerCase();
            let idx = FONT_SIZES.findIndex(s => s.toLowerCase() === current);
            if (idx === -1) {
                const num = parseInt(current, 10);
                idx = FONT_SIZES.findIndex(s => parseInt(s, 10) > num);
                if (idx === -1) idx = FONT_SIZES.length - 1;
            } else if (idx < FONT_SIZES.length - 1) {
                idx = idx + 1;
            }
            const newSize = FONT_SIZES[idx];
            if (buttons.fontSizeSelect) buttons.fontSizeSelect.value = newSize;
            applyFontSize(newSize);
        });
    }

    // Color and Highlight Pickers
    const textColorBtn = document.getElementById('textColorBtn');
    const textColorPalette = document.getElementById('textColorPalette');
    const highlightBtn = document.getElementById('highlightBtn');
    const highlightPalette = document.getElementById('highlightPalette');

    if (textColorBtn && textColorPalette) {
        textColorBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            textColorPalette.classList.toggle('open');
            if (highlightPalette) highlightPalette.classList.remove('open');
        });

        textColorPalette.querySelectorAll('.color-swatch-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const color = this.dataset.color;
                editor.chain().focus().setColor(color).run();
                textColorPalette.classList.remove('open');
            });
        });
    }

    if (highlightBtn && highlightPalette) {
        highlightBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            highlightPalette.classList.toggle('open');
            if (textColorPalette) textColorPalette.classList.remove('open');
        });

        highlightPalette.querySelectorAll('.color-swatch-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const color = this.dataset.color;
                if (color === 'transparent') {
                    editor.chain().focus().unsetHighlight().run();
                } else {
                    editor.chain().focus().setHighlight({ color: color }).run();
                }
                highlightPalette.classList.remove('open');
            });
        });
    }

    // Link Action
    if (buttons.link) {
        buttons.link.addEventListener('click', () => {
            const previousUrl = editor.getAttributes('link').href || '';
            const url = window.prompt('Enter or paste link URL:', previousUrl);
            if (url === null) return;
            if (url === '') {
                editor.chain().focus().extendMarkRange('link').unsetLink().run();
                return;
            }
            editor.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
        });
    }

    // Export Dropdown
    if (exportDropdownBtn && exportMenuPopup) {
        exportDropdownBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            exportMenuPopup.classList.toggle('open');
        });
    }

    // Close open menus/dropdowns on outside click
    document.addEventListener('click', () => {
        if (textColorPalette) textColorPalette.classList.remove('open');
        if (highlightPalette) highlightPalette.classList.remove('open');
        if (exportMenuPopup) exportMenuPopup.classList.remove('open');
    });

    // Zoom Controls
    if (zoomSelect && zoomWrapper) {
        zoomSelect.addEventListener('change', function () {
            const val = this.value;
            if (val === 'fit') {
                const containerWidth = document.getElementById('editorWorkspace').clientWidth - 60;
                const dims = getPaperDimensions(pageSettings.paperSize, pageSettings.orientation);
                const scale = Math.min(1.2, Math.max(0.4, containerWidth / dims.width));
                zoomWrapper.style.transform = `scale(${scale.toFixed(2)})`;
            } else {
                const scale = parseInt(val, 10) / 100;
                zoomWrapper.style.transform = `scale(${scale})`;
            }
            refreshPaginationLayout();
        });
    }

    // Print Button
    if (btnPrint) {
        btnPrint.addEventListener('click', () => {
            window.print();
        });
    }

    // Manual Save Button
    if (btnManualSave) {
        btnManualSave.addEventListener('click', () => {
            executeSave(true);
        });
    }

    // Keyboard Shortcuts: Ctrl+S (Save), Ctrl+P (Print)
    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            executeSave(true);
        }
        if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
            e.preventDefault();
            window.print();
        }
    });

    // Helper functions for verification and scrolling
    window.scrollWorkspace = function (top) {
        const ws = document.getElementById('editorWorkspace');
        if (ws) ws.scrollTop = (top === 'bottom') ? ws.scrollHeight : top;
    };
    window.getEditorPageBreaks = function () {
        return Array.from(document.querySelectorAll('.rm-page-break')).map((el, idx) => {
            const footerEl = el.querySelector('.rm-page-footer-right');
            return {
                pageIndex: idx + 1,
                footerText: footerEl ? footerEl.innerText.trim() : '',
                top: Math.round(el.getBoundingClientRect().top)
            };
        });
    };

    // -------------------------------------------------------------
    // Page Setup Modal & Handling
    // -------------------------------------------------------------
    function openPageSetupModal() {
        if (!pageSetupModal) return;
        const orientRadios = document.querySelectorAll('input[name="pageOrientation"]');
        orientRadios.forEach(r => {
            r.checked = (r.value === pageSettings.orientation);
        });

        const paperSizeSelect = document.getElementById('pageSetupPaperSize');
        if (paperSizeSelect) paperSizeSelect.value = pageSettings.paperSize;

        const topInput = document.getElementById('marginTopInput');
        const bottomInput = document.getElementById('marginBottomInput');
        const leftInput = document.getElementById('marginLeftInput');
        const rightInput = document.getElementById('marginRightInput');

        if (topInput) topInput.value = pageSettings.marginTop;
        if (bottomInput) bottomInput.value = pageSettings.marginBottom;
        if (leftInput) leftInput.value = pageSettings.marginLeft;
        if (rightInput) rightInput.value = pageSettings.marginRight;

        pageSetupModal.classList.add('open');
    }

    if (btnPageSetup) {
        btnPageSetup.addEventListener('click', openPageSetupModal);
    }

    // Preset margins buttons
    document.querySelectorAll('.btn-preset-margin').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.btn-preset-margin').forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            const preset = this.dataset.preset;
            const topInput = document.getElementById('marginTopInput');
            const bottomInput = document.getElementById('marginBottomInput');
            const leftInput = document.getElementById('marginLeftInput');
            const rightInput = document.getElementById('marginRightInput');

            if (preset === 'normal') {
                if (topInput) topInput.value = 60;
                if (bottomInput) bottomInput.value = 60;
                if (leftInput) leftInput.value = 70;
                if (rightInput) rightInput.value = 70;
            } else if (preset === 'narrow') {
                if (topInput) topInput.value = 36;
                if (bottomInput) bottomInput.value = 36;
                if (leftInput) leftInput.value = 40;
                if (rightInput) rightInput.value = 40;
            } else if (preset === 'wide') {
                if (topInput) topInput.value = 80;
                if (bottomInput) bottomInput.value = 80;
                if (leftInput) leftInput.value = 100;
                if (rightInput) rightInput.value = 100;
            }
        });
    });

    if (btnApplyPageSetup) {
        btnApplyPageSetup.addEventListener('click', function () {
            const selectedOrient = document.querySelector('input[name="pageOrientation"]:checked')?.value || 'portrait';
            const selectedSize = document.getElementById('pageSetupPaperSize')?.value || 'A4';
            const top = Math.max(20, Math.min(200, parseInt(document.getElementById('marginTopInput')?.value || 60, 10)));
            const bottom = Math.max(20, Math.min(200, parseInt(document.getElementById('marginBottomInput')?.value || 60, 10)));
            const left = Math.max(20, Math.min(200, parseInt(document.getElementById('marginLeftInput')?.value || 70, 10)));
            const right = Math.max(20, Math.min(200, parseInt(document.getElementById('marginRightInput')?.value || 70, 10)));

            pageSettings.paperSize = selectedSize;
            pageSettings.orientation = selectedOrient;
            pageSettings.marginTop = top;
            pageSettings.marginBottom = bottom;
            pageSettings.marginLeft = left;
            pageSettings.marginRight = right;

            const dims = getPaperDimensions(pageSettings.paperSize, pageSettings.orientation);

            // Update editor pagination extension via commands
            editor.commands.updatePageWidth(dims.width);
            editor.commands.updatePageHeight(dims.height);
            editor.commands.updateMargins({ top, bottom, left, right });
            refreshPaginationLayout();

            pageSetupModal.classList.remove('open');
            isDirty = true;
            updateMetrics();
            scheduleAutosave();
        });
    }

    // -------------------------------------------------------------
    // Header & Footer Modal & Handling
    // -------------------------------------------------------------
    function openHeaderFooterModal(focusSection = 'header') {
        if (!headerFooterModal) return;

        const hLeft = document.getElementById('headerLeftInput');
        const hRight = document.getElementById('headerRightInput');
        const fLeft = document.getElementById('footerLeftInput');
        const fRight = document.getElementById('footerRightInput');
        const diffFirst = document.getElementById('differentFirstPageCheck');

        if (hLeft) hLeft.value = headerContent.left || '';
        if (hRight) hRight.value = headerContent.right || '';
        if (fLeft) fLeft.value = footerContent.left || '';
        if (fRight) fRight.value = footerContent.right || '';
        if (diffFirst) diffFirst.checked = !!pageSettings.differentFirstPage;

        headerFooterModal.classList.add('open');

        if (focusSection === 'footer' && fRight) {
            fRight.focus();
        } else if (hLeft) {
            hLeft.focus();
        }
    }

    if (btnHeaderFooter) {
        btnHeaderFooter.addEventListener('click', () => openHeaderFooterModal('header'));
    }

    // Quick tag button clicks in footer right
    document.querySelectorAll('.btn-quick-tag').forEach(btn => {
        btn.addEventListener('click', function () {
            const tag = this.dataset.tag;
            const fRight = document.getElementById('footerRightInput');
            if (fRight) {
                fRight.value = tag;
                fRight.focus();
            }
        });
    });

    if (btnApplyHeaderFooter) {
        btnApplyHeaderFooter.addEventListener('click', function () {
            const hLeft = document.getElementById('headerLeftInput')?.value.trim() || '';
            const hRight = document.getElementById('headerRightInput')?.value.trim() || '';
            const fLeft = document.getElementById('footerLeftInput')?.value.trim() || '';
            const fRight = document.getElementById('footerRightInput')?.value.trim() || '';
            const diffFirst = !!document.getElementById('differentFirstPageCheck')?.checked;

            headerContent.left = hLeft;
            headerContent.right = hRight;
            footerContent.left = fLeft;
            footerContent.right = fRight;
            pageSettings.differentFirstPage = diffFirst;

            // Apply to editor via commands
            editor.commands.updateHeaderContent(headerContent.left, headerContent.right);
            editor.commands.updateFooterContent(footerContent.left, footerContent.right);

            if (diffFirst) {
                editor.commands.updateHeaderContent('', '', 1);
                editor.commands.updateFooterContent('', '', 1);
            }
            refreshPaginationLayout();

            headerFooterModal.classList.remove('open');
            isDirty = true;
            updateMetrics();
            scheduleAutosave();
        });
    }

    if (btnClearHeaderFooter) {
        btnClearHeaderFooter.addEventListener('click', function () {
            headerContent.left = '';
            headerContent.right = '';
            footerContent.left = '';
            footerContent.right = '';

            editor.commands.updateHeaderContent('', '');
            editor.commands.updateFooterContent('', '');
            refreshPaginationLayout();

            headerFooterModal.classList.remove('open');
            isDirty = true;
            updateMetrics();
            scheduleAutosave();
        });
    }

    // Modal Close Buttons
    document.querySelectorAll('.btn-close-modal').forEach(btn => {
        btn.addEventListener('click', function () {
            const modal = this.closest('.modal-overlay');
            if (modal) modal.classList.remove('open');
        });
    });

    // Close modal on click outside modal-box
    document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.addEventListener('click', function (e) {
            if (e.target === this) {
                this.classList.remove('open');
            }
        });
    });

    // Title Input Auto-Rename & Auto-Save
    if (titleInput) {
        titleInput.addEventListener('input', () => {
            isDirty = true;
            scheduleAutosave();
        });

        titleInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                titleInput.blur();
                editor.commands.focus();
            }
        });
    }

    // -------------------------------------------------------------
    // Autosave & Server Synchronization
    // -------------------------------------------------------------
    function scheduleAutosave() {
        setSaveStatus('saving', 'Saving changes...');
        if (saveTimeout) clearTimeout(saveTimeout);
        saveTimeout = setTimeout(() => {
            executeSave(false);
        }, 1500);
    }

    function executeSave(isManual = false) {
        if (!saveUrl || !editor) return;

        if (isSaving) {
            pendingSave = true;
            return;
        }

        if (saveTimeout) {
            clearTimeout(saveTimeout);
            saveTimeout = null;
        }

        isSaving = true;
        setSaveStatus('saving', isManual ? 'Saving document...' : 'Autosaving...');

        const currentTitle = titleInput ? titleInput.value.trim() || 'Untitled Document' : 'Untitled Document';
        const currentContent = editor.getHTML();

        const payload = {
            title: currentTitle,
            content: currentContent,
            header_content: headerContent,
            footer_content: footerContent,
            page_settings: pageSettings,
        };

        const headers = {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        };

        if (csrfTokenName && csrfHash) {
            headers[csrfTokenName] = csrfHash;
            headers['X-CSRF-TOKEN'] = csrfHash;
        }

        fetch(saveUrl, {
            method: 'POST',
            headers: headers,
            body: JSON.stringify(payload),
        })
            .then(res => {
                if (!res.ok) throw new Error(`HTTP error ${res.status}`);
                return res.json();
            })
            .then(data => {
                isSaving = false;
                if (data.success) {
                    isDirty = false;
                    setSaveStatus('saved', 'Saved to cloud');
                    if (data.title && titleInput) {
                        titleInput.value = data.title;
                    }
                    if (data.csrf_hash) {
                        csrfHash = data.csrf_hash;
                        const meta = document.querySelector('meta[name="csrf-token"]');
                        if (meta) meta.content = data.csrf_hash;
                    }

                    if (pendingSave) {
                        pendingSave = false;
                        executeSave(false);
                    }
                } else {
                    setSaveStatus('error', 'Save error. Retry');
                }
            })
            .catch(err => {
                console.error('Document save failed:', err);
                isSaving = false;
                setSaveStatus('error', 'Offline / Retry');
            });
    }

    function setSaveStatus(state, message) {
        if (!saveStatusBadge || !saveStatusText) return;

        saveStatusBadge.classList.remove('saving', 'saved', 'error');
        saveStatusBadge.classList.add(state);
        saveStatusText.textContent = message;

        if (state === 'error') {
            saveStatusBadge.onclick = () => executeSave(true);
        } else {
            saveStatusBadge.onclick = null;
        }
    }

    // Warn about unsaved changes on window unload
    window.addEventListener('beforeunload', function (e) {
        if (isDirty) {
            e.preventDefault();
            e.returnValue = 'You have unsaved document changes.';
        }
    });
});
