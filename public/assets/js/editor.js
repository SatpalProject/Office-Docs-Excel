/**
 * OfficeSpace — Document Editor Client Logic
 * Powered by bundled Tiptap with real-time autosave and formatting toolbar
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
    const exportDropdownBtn = document.getElementById('exportDropdownBtn');
    const exportMenuPopup = document.getElementById('exportMenuPopup');

    let isDirty = false;
    let isSaving = false;
    let saveTimeout = null;
    let pendingSave = false;
    let lastSavedTitle = titleInput ? titleInput.value.trim() : '';
    let lastSavedContent = editorConfig.initialContent || '';

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
        CharacterCount
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
            CharacterCount,
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

    // Update Word & Character Counts
    function updateMetrics() {
        if (!editor) return;
        const characters = editor.storage.characterCount.characters();
        const words = editor.storage.characterCount.words();

        if (wordCountEl) wordCountEl.textContent = `${words} words`;
        if (charCountEl) charCountEl.textContent = `${characters} characters`;
    }
    updateMetrics();

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
        undo: document.getElementById('btnUndo'),
        redo: document.getElementById('btnRedo'),
        clearFormat: document.getElementById('btnClearFormat'),
        link: document.getElementById('btnLink'),
        hr: document.getElementById('btnHr'),
    };

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
    if (buttons.undo) buttons.undo.addEventListener('click', () => editor.chain().focus().undo().run());
    if (buttons.redo) buttons.redo.addEventListener('click', () => editor.chain().focus().redo().run());
    if (buttons.hr) buttons.hr.addEventListener('click', () => editor.chain().focus().setHorizontalRule().run());

    if (buttons.clearFormat) {
        buttons.clearFormat.addEventListener('click', () => {
            editor.chain().focus().clearNodes().unsetAllMarks().run();
        });
    }

    if (buttons.link) {
        buttons.link.addEventListener('click', () => {
            const previousUrl = editor.getAttributes('link').href;
            const url = window.prompt('Enter link URL:', previousUrl || 'https://');
            if (url === null) return;
            if (url.trim() === '') {
                editor.chain().focus().extendMarkRange('link').unsetLink().run();
                return;
            }
            editor.chain().focus().extendMarkRange('link').setLink({ href: url.trim() }).run();
        });
    }

    // Heading format selector
    const headingSelect = document.getElementById('headingSelect');
    if (headingSelect) {
        headingSelect.addEventListener('change', function () {
            const val = this.value;
            if (val === 'p') {
                editor.chain().focus().setParagraph().run();
            } else if (val === 'h1') {
                editor.chain().focus().setHeading({ level: 1 }).run();
            } else if (val === 'h2') {
                editor.chain().focus().setHeading({ level: 2 }).run();
            } else if (val === 'h3') {
                editor.chain().focus().setHeading({ level: 3 }).run();
            }
        });
    }

    // Text & Highlight Color Dropdown Popovers
    const textColorBtn = document.getElementById('textColorBtn');
    const textColorPalette = document.getElementById('textColorPalette');
    const highlightBtn = document.getElementById('highlightBtn');
    const highlightPalette = document.getElementById('highlightPalette');

    if (textColorBtn && textColorPalette) {
        textColorBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            if (highlightPalette) highlightPalette.classList.remove('open');
            textColorPalette.classList.toggle('open');
        });

        textColorPalette.querySelectorAll('.color-swatch-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const color = this.getAttribute('data-color');
                editor.chain().focus().setColor(color).run();
                textColorPalette.classList.remove('open');
            });
        });
    }

    if (highlightBtn && highlightPalette) {
        highlightBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            if (textColorPalette) textColorPalette.classList.remove('open');
            highlightPalette.classList.toggle('open');
        });

        highlightPalette.querySelectorAll('.color-swatch-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const color = this.getAttribute('data-color');
                if (color === 'transparent') {
                    editor.chain().focus().unsetHighlight().run();
                } else {
                    editor.chain().focus().setHighlight({ color: color }).run();
                }
                highlightPalette.classList.remove('open');
            });
        });
    }

    // Close palettes on click outside
    document.addEventListener('click', () => {
        if (textColorPalette) textColorPalette.classList.remove('open');
        if (highlightPalette) highlightPalette.classList.remove('open');
        if (exportMenuPopup) exportMenuPopup.classList.remove('open');
    });

    // Update active toolbar button states based on selection
    function updateToolbarState() {
        if (!editor) return;

        const syncActive = (btn, condition) => {
            if (!btn) return;
            if (condition) btn.classList.add('is-active');
            else btn.classList.remove('is-active');
        };

        syncActive(buttons.bold, editor.isActive('bold'));
        syncActive(buttons.italic, editor.isActive('italic'));
        syncActive(buttons.underline, editor.isActive('underline'));
        syncActive(buttons.strike, editor.isActive('strike'));
        syncActive(buttons.alignLeft, editor.isActive({ textAlign: 'left' }));
        syncActive(buttons.alignCenter, editor.isActive({ textAlign: 'center' }));
        syncActive(buttons.alignRight, editor.isActive({ textAlign: 'right' }));
        syncActive(buttons.alignJustify, editor.isActive({ textAlign: 'justify' }));
        syncActive(buttons.bulletList, editor.isActive('bulletList'));
        syncActive(buttons.orderedList, editor.isActive('orderedList'));
        syncActive(buttons.blockquote, editor.isActive('blockquote'));
        syncActive(buttons.codeBlock, editor.isActive('codeBlock'));
        syncActive(buttons.link, editor.isActive('link'));

        if (headingSelect) {
            if (editor.isActive('heading', { level: 1 })) headingSelect.value = 'h1';
            else if (editor.isActive('heading', { level: 2 })) headingSelect.value = 'h2';
            else if (editor.isActive('heading', { level: 3 })) headingSelect.value = 'h3';
            else headingSelect.value = 'p';
        }
    }

    // Editable Title Input Change
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

    // Export Dropdown Trigger
    if (exportDropdownBtn && exportMenuPopup) {
        exportDropdownBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            exportMenuPopup.classList.toggle('open');
        });
    }

    // Set Save Status Pill
    function setSaveStatus(state, message) {
        if (!saveStatusBadge || !saveStatusText) return;

        saveStatusBadge.className = 'save-status-badge ' + state;
        if (state === 'saving') {
            saveStatusBadge.innerHTML = `
                <svg class="save-spin-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <circle cx="12" cy="12" r="10" stroke-opacity="0.25"></circle>
                    <path d="M12 2a10 10 0 0 1 10 10"></path>
                </svg>
                <span id="saveStatusText">${message || 'Saving...'}</span>
            `;
        } else if (state === 'saved') {
            saveStatusBadge.innerHTML = `
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                <span id="saveStatusText">${message || 'Saved to cloud'}</span>
            `;
        } else if (state === 'error') {
            saveStatusBadge.innerHTML = `
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <span id="saveStatusText">${message || 'Save failed — Click to retry'}</span>
            `;
            saveStatusBadge.onclick = triggerSaveNow;
        }
    }

    // Schedule Debounced Autosave (1000ms)
    function scheduleAutosave() {
        if (saveTimeout) clearTimeout(saveTimeout);
        setSaveStatus('saving', 'Saving changes...');
        saveTimeout = setTimeout(triggerSaveNow, 1000);
    }

    // Execute Save Request
    async function triggerSaveNow() {
        if (isSaving) {
            pendingSave = true;
            return;
        }

        const currentTitle = titleInput ? titleInput.value.trim() : 'Untitled Document';
        const currentContent = editor.getHTML();

        isSaving = true;
        setSaveStatus('saving', 'Saving to cloud...');

        try {
            const formData = new FormData();
            formData.append('title', currentTitle || 'Untitled Document');
            formData.append('content', currentContent);
            formData.append(csrfTokenName, csrfHash);

            const response = await fetch(saveUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfHash,
                },
            });

            if (!response.ok) {
                throw new Error(`HTTP error ${response.status}`);
            }

            const data = await response.json();

            if (data.success) {
                isDirty = false;
                lastSavedTitle = currentTitle;
                lastSavedContent = currentContent;

                // Update CSRF token for next request
                if (data.csrf_hash) {
                    csrfHash = data.csrf_hash;
                    const metaToken = document.querySelector('meta[name="csrf-token"]');
                    if (metaToken) metaToken.content = data.csrf_hash;
                }
                if (data.csrf_token) {
                    csrfTokenName = data.csrf_token;
                    const metaName = document.querySelector('meta[name="csrf-name"]');
                    if (metaName) metaName.content = data.csrf_token;
                }

                setSaveStatus('saved', `Saved at ${data.updated_at || 'just now'}`);

                // Update document page title in browser tab
                document.title = `${currentTitle || 'Untitled Document'} — OfficeSpace Docs`;
            } else {
                throw new Error(data.message || 'Server reported save error');
            }
        } catch (err) {
            console.error('Autosave error:', err);
            setSaveStatus('error', 'Save failed — Retry');
        } finally {
            isSaving = false;
            if (pendingSave) {
                pendingSave = false;
                scheduleAutosave();
            }
        }
    }

    // Warn about unsaved changes
    window.addEventListener('beforeunload', function (e) {
        if (isDirty) {
            e.preventDefault();
            e.returnValue = 'You have unsaved changes that may not be saved.';
            return e.returnValue;
        }
    });
});
