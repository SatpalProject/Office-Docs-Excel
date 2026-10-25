<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-logo-badge">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="18" height="18" rx="3" ry="3"></rect>
                    <line x1="3" y1="9" x2="21" y2="9"></line>
                    <line x1="9" y1="21" x2="9" y2="9"></line>
                </svg>
            </div>
            <h1 class="auth-title">Create your Account</h1>
            <p class="auth-subtitle">Get started with your free collaborative cloud workspace</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger" role="alert">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <div class="alert-content">
                    <?php if (is_array($errors)): ?>
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?= esc($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <span><?= esc($errors) ?></span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <form action="<?= site_url('register') ?>" method="post" class="auth-form" autocomplete="on">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="name" class="form-label">Full Name</label>
                <div class="input-wrapper">
                    <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    <input type="text" id="name" name="name" class="form-control" placeholder="Jane Doe" value="<?= esc(old('name')) ?>" required autofocus autocomplete="name">
                </div>
            </div>

            <div class="form-group">
                <label for="email" class="form-label">Email Address</label>
                <div class="input-wrapper">
                    <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                        <polyline points="22,6 12,13 2,6"></polyline>
                    </svg>
                    <input type="email" id="email" name="email" class="form-control" placeholder="jane@example.com" value="<?= esc(old('email')) ?>" required autocomplete="email">
                </div>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <div class="input-wrapper">
                    <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Minimum 8 characters" required minlength="8" autocomplete="new-password">
                </div>
                <small class="form-hint">Must be at least 8 characters long</small>
            </div>

            <div class="form-group">
                <label for="password_confirm" class="form-label">Confirm Password</label>
                <div class="input-wrapper">
                    <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                    <input type="password" id="password_confirm" name="password_confirm" class="form-control" placeholder="Re-enter password" required minlength="8" autocomplete="new-password">
                </div>
            </div>

            <button type="submit" class="btn-auth-submit">
                <span>Create Account & Enter Workspace</span>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                    <polyline points="12 5 19 12 12 19"></polyline>
                </svg>
            </button>
        </form>

        <div class="auth-footer-link">
            <span>Already have an account?</span>
            <a href="<?= site_url('login') ?>">Sign in here</a>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
