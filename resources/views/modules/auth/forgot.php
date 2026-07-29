<div class="auth-card auth-anim">
    <div class="auth-card-head auth-anim-item" style="--d:.05s">
        <span class="auth-kicker">Password Help</span>
        <h2 class="auth-welcome">Forgot Password</h2>
        <p class="auth-welcome-sub">Enter your account email and we will send a reset link.</p>
    </div>
    <form method="post" action="<?= app_url('forgot-password') ?>" class="auth-form">
        <?= csrf_field() ?>
        <div class="mb-3 auth-anim-item" style="--d:.15s">
            <label class="form-label">Email</label>
            <div class="auth-input-wrap">
                <i class="bi bi-envelope"></i>
                <input class="form-control form-control-lg" type="email" name="email" value="<?= e(old('email')) ?>" required placeholder="you@clinic.com">
            </div>
        </div>
        <div class="auth-anim-item" style="--d:.25s">
            <button class="btn btn-primary btn-lg w-100 auth-submit" type="submit">
                <span>Send Reset Link</span>
                <i class="bi bi-arrow-right"></i>
            </button>
        </div>
        <a class="d-block text-center mt-3 auth-link auth-anim-item" style="--d:.35s" href="<?= app_url('login') ?>">Back to login</a>
    </form>
</div>
