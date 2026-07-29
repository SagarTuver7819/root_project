<div class="auth-card auth-anim">
    <div class="auth-card-head auth-anim-item" style="--d:.08s">
        <img src="<?= e(logo_url('logo_login')) ?>" alt="Roots Dentistry" class="auth-card-logo">
        <h2 class="auth-welcome">Welcome back</h2>
        <p class="auth-welcome-sub">Sign in to manage appointments, clinical care and billing.</p>
    </div>

    <form method="post" action="<?= app_url('login') ?>" class="ajax-form auth-form" data-redirect="<?= app_url('dashboard') ?>">
        <?= csrf_field() ?>
        <div class="mb-3 auth-anim-item" style="--d:.18s">
            <label class="form-label">Username / Email</label>
            <div class="auth-input-wrap">
                <i class="bi bi-person"></i>
                <input class="form-control form-control-lg" name="login" value="<?= e(old('login')) ?>" required autofocus placeholder="Enter Username">
            </div>
        </div>
        <div class="mb-3 auth-anim-item" style="--d:.28s">
            <label class="form-label">Password</label>
            <div class="auth-input-wrap password-wrap">
                <i class="bi bi-lock"></i>
                <input id="loginPassword" class="form-control form-control-lg" type="password" name="password" required placeholder="Enter password">
                <button class="toggle-password" type="button" data-target="#loginPassword" aria-label="Show password"><i class="bi bi-eye"></i></button>
            </div>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-4 auth-anim-item" style="--d:.38s">
            <label class="form-check mb-0">
                <input class="form-check-input" type="checkbox" name="remember" value="1">
                <span class="form-check-label">Remember Me</span>
            </label>
            <a class="auth-link" href="<?= app_url('forgot-password') ?>">Forgot Password?</a>
        </div>
        <div class="auth-anim-item" style="--d:.48s">
            <button type="submit" class="btn btn-primary btn-lg w-100 auth-submit">
                <span>Sign In</span>
                <i class="bi bi-arrow-right"></i>
            </button>
        </div>
    </form>
</div>
