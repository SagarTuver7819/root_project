<div class="auth-card auth-anim">
    <div class="auth-card-head auth-anim-item" style="--d:.08s">
        <span class="auth-kicker">Reset Access</span>
        <h2 class="auth-welcome">Reset Password</h2>
        <p class="auth-welcome-sub">Create a new password for your staff account.</p>
    </div>
    <form method="post" action="<?= app_url('reset-password') ?>" class="auth-form">
        <?= csrf_field() ?>
        <input type="hidden" name="token" value="<?= e($token ?? '') ?>">
        <input type="hidden" name="email" value="<?= e($email ?? '') ?>">
        <div class="mb-3 auth-anim-item" style="--d:.18s">
            <label class="form-label">Email</label>
            <div class="auth-input-wrap">
                <i class="bi bi-envelope"></i>
                <input class="form-control form-control-lg" value="<?= e($email ?? '') ?>" disabled>
            </div>
        </div>
        <div class="mb-3 auth-anim-item" style="--d:.28s">
            <label class="form-label">New Password</label>
            <div class="auth-input-wrap">
                <i class="bi bi-lock"></i>
                <input class="form-control form-control-lg" type="password" name="password" required placeholder="New password">
            </div>
        </div>
        <div class="mb-3 auth-anim-item" style="--d:.38s">
            <label class="form-label">Confirm Password</label>
            <div class="auth-input-wrap">
                <i class="bi bi-shield-lock"></i>
                <input class="form-control form-control-lg" type="password" name="password_confirmation" required placeholder="Confirm password">
            </div>
        </div>
        <div class="auth-anim-item" style="--d:.48s">
            <button class="btn btn-primary btn-lg w-100 auth-submit" type="submit">
                <span>Reset Password</span>
                <i class="bi bi-arrow-right"></i>
            </button>
        </div>
    </form>
</div>
