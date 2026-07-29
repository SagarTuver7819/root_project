<div class="card content-card shadow-sm border-0">
    <div class="card-body p-4">
        <h2 class="h5 mb-1">Welcome back</h2>
        <p class="text-muted small mb-4">Sign in to continue to the hospital management system.</p>
        <form method="post" action="<?= app_url('login') ?>" class="ajax-form" data-redirect="<?= app_url('dashboard') ?>">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Username / Email</label>
                <input class="form-control form-control-lg" name="login" value="<?= e(old('login')) ?>" required autofocus placeholder="admin">
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <div class="password-wrap">
                    <input id="loginPassword" class="form-control form-control-lg" type="password" name="password" required placeholder="••••••••">
                    <button class="toggle-password" type="button" data-target="#loginPassword"><i class="bi bi-eye"></i></button>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <label class="form-check mb-0">
                    <input class="form-check-input" type="checkbox" name="remember" value="1">
                    <span class="form-check-label">Remember Me</span>
                </label>
                <a href="<?= app_url('forgot-password') ?>">Forgot Password?</a>
            </div>
            <button type="submit" class="btn btn-primary btn-lg w-100">Login</button>
        </form>
    </div>
</div>
