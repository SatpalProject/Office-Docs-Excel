/**
 * OfficeSpace — Dashboard Client Interactions
 */

document.addEventListener('DOMContentLoaded', function () {
    // Phase Modal (for Spreadsheet Preview)
    const modal = document.getElementById('phaseModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalDesc = document.getElementById('modalDesc');
    const modalCloseBtn = document.getElementById('modalCloseBtn');
    const modalDismissBtn = document.getElementById('modalDismissBtn');

    function showModal(title, message) {
        if (!modal) return;
        if (modalTitle) modalTitle.textContent = title;
        if (modalDesc) modalDesc.textContent = message;
        modal.classList.add('open');
    }

    function closeModal() {
        if (!modal) return;
        modal.classList.remove('open');
    }

    if (modalCloseBtn) modalCloseBtn.addEventListener('click', closeModal);
    if (modalDismissBtn) modalDismissBtn.addEventListener('click', closeModal);
    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) closeModal();
        });
    }

    // Spreadsheet creation triggers Phase 4 preview
    document.querySelectorAll('[data-create-type="spreadsheet"]').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const template = this.getAttribute('data-template') || 'Blank';
            showModal(
                'Spreadsheet Engine (Phase 4 Preview)',
                `You selected "${template}". The grid calculation engine with formulas, cells, and rows is scheduled for Phase 4.`
            );
        });
    });

    // Rename Modal Handling
    const renameModal = document.getElementById('renameDocModal');
    const renameForm = document.getElementById('renameDocForm');
    const renameTitleInput = document.getElementById('renameDocTitle');
    const renameModalTitle = document.getElementById('renameModalTitle');

    document.querySelectorAll('.btn-open-rename').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const id = this.getAttribute('data-id');
            const title = this.getAttribute('data-title');
            const type = this.getAttribute('data-type') || 'document';
            const controller = type === 'spreadsheet' ? 'spreadsheets' : 'documents';

            if (renameForm) {
                renameForm.action = window.location.origin + window.location.pathname.replace(/\/dashboard.*|\/index.php.*|\/$/, '') + `/index.php/${controller}/rename/` + id;
            }
            if (renameTitleInput) {
                renameTitleInput.value = title;
            }
            if (renameModalTitle) {
                renameModalTitle.textContent = type === 'spreadsheet' ? 'Rename Spreadsheet' : 'Rename Document';
            }
            if (renameModal) {
                renameModal.classList.add('open');
                setTimeout(() => renameTitleInput && renameTitleInput.focus(), 100);
            }
        });
    });

    // Delete Modal Handling
    const deleteModal = document.getElementById('deleteDocModal');
    const deleteForm = document.getElementById('deleteDocForm');
    const deleteTitleDisplay = document.getElementById('deleteDocTitle');
    const deleteModalTitle = document.getElementById('deleteModalTitle');

    document.querySelectorAll('.btn-open-delete').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const id = this.getAttribute('data-id');
            const title = this.getAttribute('data-title');
            const type = this.getAttribute('data-type') || 'document';
            const controller = type === 'spreadsheet' ? 'spreadsheets' : 'documents';

            if (deleteForm) {
                deleteForm.action = window.location.origin + window.location.pathname.replace(/\/dashboard.*|\/index.php.*|\/$/, '') + `/index.php/${controller}/delete/` + id;
            }
            if (deleteTitleDisplay) {
                deleteTitleDisplay.textContent = `"${title}"`;
            }
            if (deleteModalTitle) {
                deleteModalTitle.textContent = type === 'spreadsheet' ? 'Delete Spreadsheet' : 'Delete Document';
            }
            if (deleteModal) {
                deleteModal.classList.add('open');
            }
        });
    });

    // Close any modal via .btn-close-modal
    document.querySelectorAll('.btn-close-modal').forEach(btn => {
        btn.addEventListener('click', function () {
            if (renameModal) renameModal.classList.remove('open');
            if (deleteModal) deleteModal.classList.remove('open');
        });
    });

    [renameModal, deleteModal].forEach(m => {
        if (m) {
            m.addEventListener('click', function (e) {
                if (e.target === m) m.classList.remove('open');
            });
        }
    });

    // View Toggle (Grid / List)
    const viewButtons = document.querySelectorAll('.view-toggle-btn');
    const filesContainer = document.getElementById('filesContainer');

    viewButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            viewButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            const mode = this.getAttribute('data-view');

            if (filesContainer) {
                if (mode === 'list') {
                    filesContainer.classList.remove('view-grid');
                    filesContainer.classList.add('view-list');
                } else {
                    filesContainer.classList.remove('view-list');
                    filesContainer.classList.add('view-grid');
                }
            }
        });
    });

    // Filtering by Tab & Search
    const tabButtons = document.querySelectorAll('.tab-filter-btn');
    const searchInput = document.getElementById('dashboardSearchInput');
    const globalSearchInput = document.getElementById('globalSearchInput');
    const fileCards = document.querySelectorAll('.file-item-card');

    let currentTab = 'all';
    let searchQuery = '';

    function applyFileFilters() {
        let visibleCount = 0;
        fileCards.forEach(card => {
            const cardType = card.getAttribute('data-type');
            const cardTitle = card.getAttribute('data-title') || '';

            const matchesTab = (currentTab === 'all' || cardType === currentTab);
            const matchesSearch = (searchQuery === '' || cardTitle.includes(searchQuery));

            if (matchesTab && matchesSearch) {
                card.style.display = '';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });
    }

    tabButtons.forEach(tab => {
        tab.addEventListener('click', function () {
            tabButtons.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            currentTab = this.getAttribute('data-tab');
            applyFileFilters();
        });
    });

    function handleSearchInput(e) {
        searchQuery = e.target.value.trim().toLowerCase();
        applyFileFilters();
    }

    if (searchInput) searchInput.addEventListener('input', handleSearchInput);
    if (globalSearchInput) globalSearchInput.addEventListener('input', handleSearchInput);

    // Global Search Focus Shortcut (Ctrl+K or Cmd+K)
    window.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
            e.preventDefault();
            if (globalSearchInput) {
                globalSearchInput.focus();
                globalSearchInput.select();
            }
        }
        if (e.key === 'Escape') {
            closeModal();
            if (renameModal) renameModal.classList.remove('open');
            if (deleteModal) deleteModal.classList.remove('open');
        }
    });
});
