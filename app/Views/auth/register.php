<?= $this->extend('auth/layout') ?>

<?= $this->section('title') ?>Create Account — Photos<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="auth-card">
    <div class="text-center mb-4">
        <div class="brand-logo"><i class="bi bi-images"></i></div>
        <div class="brand-title">Create account</div>
        <div class="brand-sub">Join Photos and start your photo library</div>
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

    <form action="<?= url_to('register') ?>" method="post">
        <?= csrf_field() ?>

        <div class="mb-3">
            <label class="auth-label" for="username">Username</label>
            <input type="text" name="username" id="username"
                class="form-control auth-input"
                placeholder="johndoe"
                value="<?= old('username') ?>"
                required autofocus>
        </div>

        <div class="mb-3">
            <label class="auth-label" for="email">Email address</label>
            <input type="email" name="email" id="email"
                class="form-control auth-input"
                placeholder="you@example.com"
                value="<?= old('email') ?>"
                required>
        </div>

        <div class="mb-3">
            <label class="auth-label" for="password">Password</label>
            <div class="position-relative">
                <input type="password" name="password" id="password"
                    class="form-control auth-input pe-5"
                    placeholder="Min. 8 characters"
                    required>
                <button type="button" class="btn btn-link text-secondary position-absolute end-0 top-50 translate-middle-y me-2 p-0" id="togglePwd">
                    <i class="bi bi-eye" id="eyeIcon"></i>
                </button>
            </div>
        </div>

        <div class="mb-3">
            <label class="auth-label" for="password_confirm">Confirm Password</label>
            <input type="password" name="password_confirm" id="password_confirm"
                class="form-control auth-input"
                placeholder="••••••••"
                required>
        </div>

        <button type="submit" class="btn btn-auth w-100 mt-1">
            <i class="bi bi-person-plus me-2"></i>Create Account
        </button>
    </form>

    <hr class="auth-divider">

    <p class="text-center mb-0 brand-sub">
        Already have an account?
        <a href="<?= url_to('login') ?>" class="auth-link">Sign in</a>
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
