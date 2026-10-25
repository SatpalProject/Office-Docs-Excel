<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<!-- Welcome Section -->
<section class="welcome-hero">
    <div class="welcome-content">
        <div class="welcome-header-group">
            <span class="welcome-pill">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                </svg>
                Cloud Workspace
            </span>
            <h1 class="welcome-title">Welcome, <?= esc($user['name'] ?? 'Member') ?></h1>
            <p class="welcome-subtitle">
                Create, organize, and collaborate on documents and spreadsheets in your private cloud workspace.
            </p>
        </div>

        <!-- Metrics summary -->
        <div class="workspace-metrics">
            <div class="metric-card">
                <span class="metric-label">Documents</span>
                <div class="metric-value">
                    <span><?= (int)($stats['totalDocuments'] ?? 0) ?></span>
                    <span class="metric-sub">docs</span>
                </div>
            </div>
            <div class="metric-card">
                <span class="metric-label">Spreadsheets</span>
                <div class="metric-value">
                    <span><?= (int)($stats['totalSpreadsheets'] ?? 0) ?></span>
                    <span class="metric-sub">sheets</span>
                </div>
            </div>
            <div class="metric-card">
                <span class="metric-label">Total Files</span>
                <div class="metric-value">
                    <span><?= (int)($stats['totalFiles'] ?? 0) ?></span>
                    <span class="metric-sub">items</span>
                </div>
            </div>
            <div class="metric-card">
                <span class="metric-label">Database Storage</span>
                <div class="metric-value">
                    <span><?= !empty($dbStatus['connected']) ? 'Ready' : 'Pending' ?></span>
                    <span class="metric-sub"><?= esc($dbStatus['name'] ?? 'MySQL') ?></span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Start New Creation Section -->
<section>
    <div class="section-title-wrap">
        <h2 class="section-title">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            <span>Create New Content</span>
        </h2>
        <span class="section-hint">Choose a workspace application or quick-start template</span>
    </div>

    <div class="creator-cards-grid">
        <!-- New Document Card (Active Phase 3 Editor) -->
        <div class="creator-card doc-card">
            <div>
                <div class="creator-card-header">
                    <div class="creator-icon-bubble doc-bubble">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <polyline points="10 9 9 9 8 9"></polyline>
                        </svg>
                    </div>
                    <div class="creator-info">
                        <h3>New Document</h3>
                        <p>Write rich text documents with Tiptap formatting, cloud autosave, and export features.</p>
                    </div>
                </div>

                <div class="templates-row">
                    <div class="templates-label">Quick Templates</div>
                    <div class="templates-list">
                        <a href="<?= site_url('documents/create?template=blank_doc') ?>" class="template-chip">
                            <span class="template-chip-tag doc-chip-tag">New</span>
                            <span>Blank Document</span>
                        </a>
                        <a href="<?= site_url('documents/create?template=meeting_notes') ?>" class="template-chip">
                            <span class="template-chip-tag doc-chip-tag">Template</span>
                            <span>Meeting Notes</span>
                        </a>
                        <a href="<?= site_url('documents/create?template=project_brief') ?>" class="template-chip">
                            <span class="template-chip-tag doc-chip-tag">Template</span>
                            <span>Project Brief</span>
                        </a>
                    </div>
                </div>
            </div>

            <a href="<?= site_url('documents/create?template=blank_doc') ?>" class="btn-primary-action btn-doc-create">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span>+ Blank Document</span>
            </a>
        </div>

        <!-- New Spreadsheet Card (Active Phase 4 Editor) -->
        <div class="creator-card sheet-card">
            <div>
                <div class="creator-card-header">
                    <div class="creator-icon-bubble sheet-bubble">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="3" y1="9" x2="21" y2="9"></line>
                            <line x1="3" y1="15" x2="21" y2="15"></line>
                            <line x1="9" y1="3" x2="9" y2="21"></line>
                            <line x1="15" y1="3" x2="15" y2="21"></line>
                        </svg>
                    </div>
                    <div class="creator-info">
                        <h3>New Spreadsheet</h3>
                        <p>Perform calculations, organize numerical data, build tables, and track finances.</p>
                    </div>
                </div>

                <div class="templates-row">
                    <div class="templates-label">Quick Templates</div>
                    <div class="templates-list">
                        <a href="<?= site_url('spreadsheets/create?template=blank_sheet') ?>" class="template-chip">
                            <span class="template-chip-tag sheet-chip-tag">New</span>
                            <span>Blank Sheet</span>
                        </a>
                        <a href="<?= site_url('spreadsheets/create?template=budget_planner') ?>" class="template-chip">
                            <span class="template-chip-tag sheet-chip-tag">Template</span>
                            <span>Budget Planner</span>
                        </a>
                        <a href="<?= site_url('spreadsheets/create?template=project_tracker') ?>" class="template-chip">
                            <span class="template-chip-tag sheet-chip-tag">Template</span>
                            <span>Task Tracker</span>
                        </a>
                    </div>
                </div>
            </div>

            <a href="<?= site_url('spreadsheets/create?template=blank_sheet') ?>" class="btn-primary-action btn-sheet-create">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span>+ Blank Spreadsheet</span>
            </a>
        </div>
    </div>
</section>

<!-- My Files Section -->
<section class="files-section">
    <div class="files-header-bar">
        <!-- Filter Tabs -->
        <div class="files-tabs-group" role="tablist">
            <button type="button" class="tab-filter-btn active" data-tab="all" role="tab">
                <span>All Files</span>
                <span class="tab-count"><?= (int)($stats['totalFiles'] ?? 0) ?></span>
            </button>
            <button type="button" class="tab-filter-btn" data-tab="document" role="tab">
                <span>Documents</span>
                <span class="tab-count"><?= (int)($stats['totalDocuments'] ?? 0) ?></span>
            </button>
            <button type="button" class="tab-filter-btn" data-tab="spreadsheet" role="tab">
                <span>Spreadsheets</span>
                <span class="tab-count"><?= (int)($stats['totalSpreadsheets'] ?? 0) ?></span>
            </button>
        </div>

        <!-- Files Controls Toolbar -->
        <div class="files-toolbar">
            <div class="files-filter-input-wrap">
                <svg class="files-filter-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="text" id="dashboardSearchInput" class="files-filter-input" placeholder="Filter files...">
            </div>

            <div class="view-toggle-group" role="group" aria-label="View mode">
                <button type="button" class="view-toggle-btn active" data-view="grid" title="Grid View">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7"></rect>
                        <rect x="14" y="3" width="7" height="7"></rect>
                        <rect x="14" y="14" width="7" height="7"></rect>
                        <rect x="3" y="14" width="7" height="7"></rect>
                    </svg>
                </button>
                <button type="button" class="view-toggle-btn" data-view="list" title="List View">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="8" y1="6" x2="21" y2="6"></line>
                        <line x1="8" y1="12" x2="21" y2="12"></line>
                        <line x1="8" y1="18" x2="21" y2="18"></line>
                        <line x1="3" y1="6" x2="3.01" y2="6"></line>
                        <line x1="3" y1="12" x2="3.01" y2="12"></line>
                        <line x1="3" y1="18" x2="3.01" y2="18"></line>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Files Container / Empty State -->
    <?php if (!empty($files)): ?>
        <div id="filesContainer" class="files-grid view-grid">
            <?php foreach ($files as $file): ?>
                <div class="file-item-card" data-type="<?= esc($file['type']) ?>" data-title="<?= esc(strtolower($file['title'])) ?>">
                    <div class="file-item-preview">
                        <a href="<?= $file['type'] === 'document' ? site_url("documents/edit/{$file['id']}") : site_url("spreadsheets/edit/{$file['id']}") ?>" class="file-preview-link">
                            <div class="file-preview-icon <?= $file['type'] === 'document' ? 'doc-preview-icon' : 'sheet-preview-icon' ?>">
                                <?php if ($file['type'] === 'document'): ?>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                        <polyline points="14 2 14 8 20 8"></polyline>
                                        <line x1="16" y1="13" x2="8" y2="13"></line>
                                        <line x1="16" y1="17" x2="8" y2="17"></line>
                                    </svg>
                                <?php else: ?>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                        <line x1="3" y1="9" x2="21" y2="9"></line>
                                        <line x1="3" y1="15" x2="21" y2="15"></line>
                                        <line x1="9" y1="3" x2="9" y2="21"></line>
                                        <line x1="15" y1="3" x2="15" y2="21"></line>
                                    </svg>
                                <?php endif; ?>
                            </div>
                        </a>
                    </div>

                    <div class="file-item-meta">
                        <div class="file-title-wrap">
                            <a href="<?= $file['type'] === 'document' ? site_url("documents/edit/{$file['id']}") : site_url("spreadsheets/edit/{$file['id']}") ?>" class="file-title-link" title="<?= esc($file['title']) ?>">
                                <?= esc($file['title']) ?>
                            </a>
                            <span class="file-date-sub">Updated <?= date('M j, Y g:i A', strtotime($file['updated_at'])) ?></span>
                        </div>

                        <div class="file-item-actions">
                            <?php if ($file['type'] === 'document'): ?>
                                <a href="<?= site_url("documents/export/html/{$file['id']}") ?>" class="file-action-icon-btn" title="Download HTML">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                        <polyline points="7 10 12 15 17 10"></polyline>
                                        <line x1="12" y1="15" x2="12" y2="3"></line>
                                    </svg>
                                </a>
                            <?php else: ?>
                                <a href="<?= site_url("spreadsheets/export/xlsx/{$file['id']}") ?>" class="file-action-icon-btn" title="Download Excel (.xlsx)">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                                        <line x1="3" y1="9" x2="21" y2="9"></line>
                                        <line x1="9" y1="21" x2="9" y2="9"></line>
                                    </svg>
                                </a>
                                <a href="<?= site_url("spreadsheets/export/csv/{$file['id']}") ?>" class="file-action-icon-btn" title="Download CSV (.csv)">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                        <polyline points="14 2 14 8 20 8"></polyline>
                                        <line x1="16" y1="13" x2="8" y2="13"></line>
                                        <line x1="16" y1="17" x2="8" y2="17"></line>
                                    </svg>
                                </a>
                            <?php endif; ?>

                            <button type="button" class="file-action-icon-btn btn-open-rename" data-id="<?= (int)$file['id'] ?>" data-type="<?= esc($file['type']) ?>" data-title="<?= esc($file['title']) ?>" title="Rename <?= esc($file['type']) ?>">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 20h9"></path>
                                    <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                                </svg>
                            </button>
                            <button type="button" class="file-action-icon-btn btn-open-delete" data-id="<?= (int)$file['id'] ?>" data-type="<?= esc($file['type']) ?>" data-title="<?= esc($file['title']) ?>" title="Delete <?= esc($file['type']) ?>">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state-card">
            <div class="empty-state-visual">
                <div class="empty-graphic-circle">
                    <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                    </svg>
                </div>
                <div class="floating-doc-badge">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    </svg>
                </div>
                <div class="floating-sheet-badge">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                    </svg>
                </div>
            </div>

            <h3 class="empty-state-title">No files created yet</h3>
            <p class="empty-state-desc">
                Your workspace is ready. Start by creating a new document or spreadsheet above, or choose from one of the templates.
            </p>

            <div class="empty-state-actions">
                <a href="<?= site_url('documents/create?template=blank_doc') ?>" class="btn-empty-cta btn-empty-doc">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span>Create Document</span>
                </a>
                <a href="<?= site_url('spreadsheets/create?template=blank_sheet') ?>" class="btn-empty-cta btn-empty-sheet">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span>Create Spreadsheet</span>
                </a>
            </div>
        </div>
    <?php endif; ?>
</section>

<!-- Rename File Modal -->
<div id="renameDocModal" class="modal-overlay" role="dialog" aria-modal="true">
    <div class="modal-box">
        <form id="renameDocForm" method="post" action="">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h4 class="modal-title" id="renameModalTitle">Rename Item</h4>
                <button type="button" class="modal-close-btn btn-close-modal" aria-label="Close modal">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="renameDocTitle" class="form-label">New Title</label>
                    <input type="text" id="renameDocTitle" name="title" class="form-control" required maxlength="255">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modal-cancel btn-close-modal">Cancel</button>
                <button type="submit" class="btn-modal-dismiss" style="background:var(--brand-primary);">Save Name</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete File Confirmation Modal -->
<div id="deleteDocModal" class="modal-overlay" role="dialog" aria-modal="true">
    <div class="modal-box">
        <form id="deleteDocForm" method="post" action="">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h4 class="modal-title" id="deleteModalTitle" style="color:#dc2626;">Delete Item</h4>
                <button type="button" class="modal-close-btn btn-close-modal" aria-label="Close modal">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="deleteDocTitle">this item</strong>?</p>
                <p style="font-size:0.85rem; color:#64748b; margin-top:0.5rem;">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modal-cancel btn-close-modal">Cancel</button>
                <button type="submit" class="btn-modal-dismiss" style="background:#dc2626;">Yes, Delete</button>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>
