<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Auth.php';
Auth::boot();

if (Auth::check()) {
    header('Location: ' . APP_URL . '/dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    [$ok, $result] = Auth::login($_POST['email'] ?? '', $_POST['password'] ?? '');
    if ($ok) {
        header('Location: ' . APP_URL . '/dashboard.php');
        exit;
    }
    $error = $result;
}

$pageTitle = 'Log in';
require_once __DIR__ . '/partials/header.php';
?>
<div class="card panel-narrow">
    <h1 style="margin-top:0;">Welcome back</h1>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>

        <div class="form-row">
            <button class="btn btn-primary btn-block" type="submit">Log in</button>
        </div>
    </form>
    <p class="muted" style="margin-top:18px;text-align:center;">
        New here? <a href="<?= APP_URL ?>/register.php">Create an account</a>
    </p>
</div>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
