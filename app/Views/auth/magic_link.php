<?= $this->extend('auth/layout') ?>

<?= $this->section('title') ?>Forgot Password — Photos<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="auth-card">
    <div class="text-center mb-4">
        <div class="brand-logo"><i class="bi bi-envelope-open"></i></div>
        <div class="brand-title">Magic link login</div>
        <div class="brand-sub">Enter your email and we'll send you a sign-in link</div>
    </div>

    <?php if (session()->has('error')): ?>
        <div class="alert alert-auth mb-3">
            <i class="bi bi-exclamation-circle me-1"></i>
            <?= session('error') ?>
        </div>
    <?php endif ?>

    <?php if (session()->has('message')): ?>
        <div class="alert alert-success-auth mb-3">
            <i class="bi bi-check-circle me-1"></i>
            <?= session('message') ?>
        </div>
    <?php endif ?>

    <form action="<?= url_to('magic-link') ?>" method="post">
        <?= csrf_field() ?>

        <div class="mb-4">
            <label class="auth-label" for="email">Email address</label>
            <input type="email" name="email" id="email"
                class="form-control auth-input"
                placeholder="you@example.com"
                required autofocus>
        </div>

        <button type="submit" class="btn btn-auth w-100">
            <i class="bi bi-send me-2"></i>Send Magic Link
        </button>
    </form>

    <hr class="auth-divider">

    <p class="text-center mb-0 brand-sub">
        <a href="<?= url_to('login') ?>" class="auth-link">
            <i class="bi bi-arrow-left me-1"></i>Back to Sign In
        </a>
    </p>
</div>
<?= $this->endSection() ?>
