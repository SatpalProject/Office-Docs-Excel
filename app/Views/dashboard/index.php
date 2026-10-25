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
        <!-- New Document Card -->
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
                        <p>Write documents, memos, proposals, and notes with rich formatting and auto-saving.</p>
                    </div>
                </div>

                <div class="templates-row">
                    <div class="templates-label">Quick Templates</div>
                    <div class="templates-list">
                        <?php foreach ($templates['documents'] as $tpl): ?>
                            <button type="button" class="template-chip" data-create-type="document" data-template="<?= esc($tpl['title']) ?>">
                                <span class="template-chip-tag doc-chip-tag"><?= esc($tpl['badge']) ?></span>
                                <span><?= esc($tpl['title']) ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <button type="button" class="btn-primary-action btn-doc-create" data-create-type="document" data-template="Blank Document">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span>+ Blank Document</span>
            </button>
        </div>

        <!-- New Spreadsheet Card -->
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
                        <?php foreach ($templates['spreadsheets'] as $tpl): ?>
                            <button type="button" class="template-chip" data-create-type="spreadsheet" data-template="<?= esc($tpl['title']) ?>">
                                <span class="template-chip-tag sheet-chip-tag"><?= esc($tpl['badge']) ?></span>
                                <span><?= esc($tpl['title']) ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <button type="button" class="btn-primary-action btn-sheet-create" data-create-type="spreadsheet" data-template="Blank Spreadsheet">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span>+ Blank Spreadsheet</span>
            </button>
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
            <button type="button" class="tab-filter-btn" data-tab="documents" role="tab">
                <span>Documents</span>
                <span class="tab-count"><?= (int)($stats['totalDocuments'] ?? 0) ?></span>
            </button>
            <button type="button" class="tab-filter-btn" data-tab="spreadsheets" role="tab">
                <span>Spreadsheets</span>
                <span class="tab-count"><?= (int)($stats['totalSpreadsheets'] ?? 0) ?></span>
            </button>
            <button type="button" class="tab-filter-btn" data-tab="recent" role="tab">
                <span>Recent</span>
            </button>
            <button type="button" class="tab-filter-btn" data-tab="starred" role="tab">
                <span>Starred</span>
            </button>
        </div>

        <!-- Files Controls Toolbar -->
        <div class="files-toolbar">
            <div class="files-filter-input-wrap">
                <svg class="files-filter-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="text" class="files-filter-input" placeholder="Filter files...">
            </div>

            <select class="sort-select" aria-label="Sort files">
                <option value="modified">Last modified</option>
                <option value="title">Title (A-Z)</option>
                <option value="created">Created date</option>
            </select>

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
        <div class="files-grid">
            <!-- Files listing will be rendered here in Phase 2 -->
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
                <button type="button" class="btn-empty-cta btn-empty-doc" data-create-type="document" data-template="Blank Document">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span>Create Document</span>
                </button>
                <button type="button" class="btn-empty-cta btn-empty-sheet" data-create-type="spreadsheet" data-template="Blank Spreadsheet">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span>Create Spreadsheet</span>
                </button>
            </div>
        </div>
    <?php endif; ?>
</section>

<?= $this->endSection() ?>
