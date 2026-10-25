/**
 * OfficeSpace — Dashboard Client Interactions
 */

document.addEventListener('DOMContentLoaded', function () {
    // Phase 2 Preview Notice Modal
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

    // Toast helper
    function showToast(message, type = 'info') {
        let container = document.getElementById('toastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toastContainer';
            container.className = 'toast-container';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = 'toast-msg';
        toast.innerHTML = `
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="16" x2="12" y2="12"></line>
                <line x1="12" y1="8" x2="12.01" y2="8"></line>
            </svg>
            <span>${message}</span>
        `;
        container.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(8px)';
            toast.style.transition = 'all 200ms ease';
            setTimeout(() => toast.remove(), 250);
        }, 3200);
    }

    // Attach click events to creation buttons & template chips
    document.querySelectorAll('[data-create-type]').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const type = this.getAttribute('data-create-type');
            const template = this.getAttribute('data-template') || 'Blank';

            if (type === 'document') {
                showModal(
                    'Document Editor (Phase 2 Preview)',
                    `You selected to create "${template}". The full rich-text document editor with real-time formatting, toolbar, and auto-save will be activated in Phase 2.`
                );
            } else if (type === 'spreadsheet') {
                showModal(
                    'Spreadsheet Engine (Phase 2 Preview)',
                    `You selected to create "${template}". The grid calculation engine with rows, columns, formulas, and cells will be activated in Phase 2.`
                );
            }
        });
    });

    // File Tabs Filter Simulation
    const tabButtons = document.querySelectorAll('.tab-filter-btn');
    tabButtons.forEach(tab => {
        tab.addEventListener('click', function () {
            tabButtons.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            const tabName = this.getAttribute('data-tab');
            showToast(`Filter applied: ${this.textContent.trim().split(' ')[0]}`);
        });
    });

    // View Toggle (Grid / List)
    const viewButtons = document.querySelectorAll('.view-toggle-btn');
    viewButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            viewButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            const mode = this.getAttribute('data-view');
            showToast(`Switched to ${mode} view`);
        });
    });

    // Global Search Focus Shortcut (Ctrl+K or Cmd+K)
    const searchInput = document.getElementById('globalSearchInput');
    window.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
            e.preventDefault();
            if (searchInput) {
                searchInput.focus();
                searchInput.select();
            }
        }
        if (e.key === 'Escape' && modal && modal.classList.contains('open')) {
            closeModal();
        }
    });

    // Search filter simulation
    if (searchInput) {
        searchInput.addEventListener('input', function (e) {
            const query = e.target.value.trim();
            // When query is typed, we show empty state or matching
            const emptyDesc = document.querySelector('.empty-state-desc');
            if (emptyDesc && query.length > 0) {
                emptyDesc.textContent = `No files matching "${query}". Try searching with different keywords.`;
            } else if (emptyDesc) {
                emptyDesc.textContent = 'Your workspace is ready. Start by creating a new document or spreadsheet above, or choose from a template.';
            }
        });
    }
});
