<?= $this->extend('auth/layout') ?>

<?= $this->section('title') ?>Sign In — Photos<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="auth-card">
    <div class="text-center mb-4">
        <div class="brand-logo"><i class="bi bi-images"></i></div>
        <div class="brand-title">Welcome back</div>
        <div class="brand-sub">Sign in to your Photos account</div>
    </div>

    <?php if (session()->has('error')): ?>
        <div class="alert alert-auth mb-3">
            <i class="bi bi-exclamation-circle me-1"></i>
            <?= session('error') ?>
        </div>
    <?php endif ?>
    <?php if (session()->has('errors')): ?>
        <div class="alert alert-auth mb-3">
            <ul class="mb-0 ps-3">
                <?php foreach (session('errors') as $error): ?>
                    <li><?= esc($error) ?></li>
                <?php endforeach ?>
            </ul>
        </div>
    <?php endif ?>

    <form action="<?= url_to('login') ?>" method="post">
        <?= csrf_field() ?>

        <div class="mb-3">
            <label class="auth-label" for="email">Email address</label>
            <input type="email" name="email" id="email"
                class="form-control auth-input"
                placeholder="you@example.com"
                value="<?= old('email') ?>"
                required autofocus>
        </div>

        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center">
                <label class="auth-label" for="password">Password</label>
                <a href="<?= url_to('magic-link') ?>" class="auth-link" style="font-size:0.8rem;">Forgot password?</a>
            </div>
            <div class="position-relative">
                <input type="password" name="password" id="password"
                    class="form-control auth-input pe-5"
                    placeholder="••••••••"
                    required>
                <button type="button" class="btn btn-link text-secondary position-absolute end-0 top-50 translate-middle-y me-2 p-0" id="togglePwd">
                    <i class="bi bi-eye" id="eyeIcon"></i>
                </button>
            </div>
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" name="remember" id="remember" value="1">
            <label class="form-check-label" for="remember">Remember me for 30 days</label>
        </div>

        <button type="submit" class="btn btn-auth w-100 mt-1">
            <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
        </button>
    </form>

    <hr class="auth-divider">

    <p class="text-center mb-0 brand-sub">
        Don't have an account?
        <a href="<?= url_to('register') ?>" class="auth-link">Create one</a>
    </p>
</div>

<script>
document.getElementById('togglePwd').addEventListener('click', function () {
    const pwd = document.getElementById('password');
    const icon = document.getElementById('eyeIcon');
    if (pwd.type === 'password') {
        pwd.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        pwd.type = 'password';
        icon.className = 'bi bi-eye';
    }
});
</script>
<?= $this->endSection() ?>
