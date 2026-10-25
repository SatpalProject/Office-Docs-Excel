<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="description" content="OfficeSpace - Modern cloud workspace for collaborative documents and spreadsheets">
    <title><?= esc($pageTitle ?? 'OfficeSpace — Modern Cloud Workspace') ?></title>
    
    <!-- Modern Typography: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Core Application CSS -->
    <link rel="stylesheet" href="<?= base_url('assets/css/dashboard.css') ?>">
</head>
<body>
    <?php
        $session = session();
        $isLoggedIn = (bool) ($session->get('isLoggedIn') ?? ($user['isLoggedIn'] ?? false));
        $currentUserName = $session->get('name') ?? ($user['name'] ?? 'Guest');
        $currentUserEmail = $session->get('email') ?? ($user['email'] ?? '');
        $currentInitial = strtoupper(mb_substr($currentUserName, 0, 1)) ?: 'O';
    ?>
    <div class="app-shell">
        <!-- Top Sticky Header Navigation -->
        <header class="top-navbar">
            <div class="navbar-container">
                <!-- Branding -->
                <a href="<?= site_url('/') ?>" class="brand-section">
                    <div class="brand-logo-badge" title="OfficeSpace Suite">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="18" height="18" rx="3" ry="3"></rect>
                            <line x1="3" y1="9" x2="21" y2="9"></line>
                            <line x1="9" y1="21" x2="9" y2="9"></line>
                        </svg>
                    </div>
                    <div class="brand-meta">
                        <div class="brand-title">
                            <span>OfficeSpace</span>
                            <span class="brand-tag">Suite</span>
                        </div>
                        <span class="brand-subtitle">Docs & Spreadsheets</span>
                    </div>
                </a>

                <!-- Global Quick Search -->
                <div class="nav-search-bar">
                    <div class="search-input-wrapper">
                        <svg class="search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                        <input type="text" id="globalSearchInput" class="search-input" placeholder="Search workspace, documents, sheets..." autocomplete="off">
                        <span class="search-shortcut-badge">Ctrl K</span>
                    </div>
                </div>

                <!-- Right Utility & Account Actions -->
                <div class="nav-actions">
                    <?php if (!empty($dbStatus['connected'])): ?>
                        <div class="status-badge" title="Connected to MySQL database '<?= esc($dbStatus['name']) ?>'">
                            <span class="status-dot"></span>
                            <span>DB: <?= esc($dbStatus['name']) ?></span>
                        </div>
                    <?php else: ?>
                        <div class="status-badge" style="background:#fff1f2; color:#be123c; border-color:#fecdd3;" title="Database check failed">
                            <span class="status-dot" style="background:#e11d48; box-shadow:0 0 0 2px rgba(225,29,72,0.3);"></span>
                            <span>DB: Connected</span>
                        </div>
                    <?php endif; ?>

                    <?php if ($isLoggedIn): ?>
                        <div class="user-profile-menu">
                            <div class="user-profile-btn" title="<?= esc($currentUserEmail) ?>">
                                <div class="avatar-badge"><?= esc($currentInitial) ?></div>
                                <span class="user-name-text"><?= esc($currentUserName) ?></span>
                            </div>
                            <form action="<?= site_url('logout') ?>" method="post" style="display:inline;">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn-logout" title="Sign out of OfficeSpace">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                        <polyline points="16 17 21 12 16 7"></polyline>
                                        <line x1="21" y1="12" x2="9" y2="12"></line>
                                    </svg>
                                    <span>Logout</span>
                                </button>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="auth-nav-buttons">
                            <a href="<?= site_url('login') ?>" class="btn-nav-login">Sign In</a>
                            <a href="<?= site_url('register') ?>" class="btn-nav-register">Create Account</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <!-- Sub Navigation Bar (for authenticated view) -->
        <?php if ($isLoggedIn): ?>
            <nav class="sub-nav">
                <div class="sub-nav-container">
                    <a href="<?= site_url('dashboard') ?>" class="sub-nav-link <?= ($activeNav ?? '') === 'dashboard' ? 'active' : '' ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="7" height="7"></rect>
                            <rect x="14" y="3" width="7" height="7"></rect>
                            <rect x="14" y="14" width="7" height="7"></rect>
                            <rect x="3" y="14" width="7" height="7"></rect>
                        </svg>
                        <span>Dashboard</span>
                    </a>
                    <a href="#documents" class="sub-nav-link" data-create-type="document" data-template="Document Section">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <polyline points="10 9 9 9 8 9"></polyline>
                        </svg>
                        <span>Documents</span>
                    </a>
                    <a href="#spreadsheets" class="sub-nav-link" data-create-type="spreadsheet" data-template="Spreadsheet Section">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="3" y1="9" x2="21" y2="9"></line>
                            <line x1="3" y1="15" x2="21" y2="15"></line>
                            <line x1="9" y1="3" x2="9" y2="21"></line>
                            <line x1="15" y1="3" x2="15" y2="21"></line>
                        </svg>
                        <span>Spreadsheets</span>
                    </a>
                    <a href="#templates" class="sub-nav-link" data-create-type="document" data-template="Template Gallery">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                            <polyline points="2 17 12 22 22 17"></polyline>
                            <polyline points="2 12 12 17 22 12"></polyline>
                        </svg>
                        <span>Templates</span>
                    </a>
                </div>
            </nav>
        <?php endif; ?>

        <!-- Main Page Content Slot -->
        <main class="workspace-container">
            <!-- Flash Message Alerts -->
            <?php if ($session->getFlashdata('success')): ?>
                <div class="alert alert-success" role="alert">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                    <span><?= esc($session->getFlashdata('success')) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($session->getFlashdata('error')): ?>
                <div class="alert alert-danger" role="alert">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <span><?= esc($session->getFlashdata('error')) ?></span>
                </div>
            <?php endif; ?>

            <?= $this->renderSection('content') ?>
        </main>

        <!-- Modal for Phase 2 Preview -->
        <div id="phaseModal" class="modal-overlay" role="dialog" aria-modal="true">
            <div class="modal-box">
                <div class="modal-header">
                    <h4 id="modalTitle" class="modal-title">OfficeSpace Preview</h4>
                    <button type="button" id="modalCloseBtn" class="modal-close-btn" aria-label="Close modal">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>
                <div id="modalDesc" class="modal-body">
                    Feature incoming in the next phase.
                </div>
                <div class="modal-footer">
                    <button type="button" id="modalDismissBtn" class="btn-modal-dismiss">Got It</button>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="app-footer">
            <div class="footer-container">
                <div>
                    <strong>OfficeSpace</strong> &copy; <?= date('Y') ?> &mdash; Cloud Documents & Spreadsheets Workspace
                </div>
                <div class="footer-tech-stack">
                    <span class="tech-tag">CodeIgniter 4.7.5</span>
                    <span class="tech-tag">PHP <?= PHP_VERSION ?></span>
                    <span class="tech-tag">MySQL Ready</span>
                    <span class="tech-tag">Phase 2: Auth & DB Active</span>
                </div>
            </div>
        </footer>
    </div>

    <!-- Core Application JS -->
    <script src="<?= base_url('assets/js/dashboard.js') ?>"></script>
</body>
</html>
