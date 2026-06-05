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
    [$ok, $result] = Auth::register(
        $_POST['name'] ?? '',
        $_POST['email'] ?? '',
        $_POST['password'] ?? ''
    );
    if ($ok) {
        header('Location: ' . APP_URL . '/dashboard.php');
        exit;
    }
    $error = $result;
}

$pageTitle = 'Create your account';
require_once __DIR__ . '/partials/header.php';
?>
<div class="card panel-narrow">
    <h1 style="margin-top:0;">Start free</h1>
    <p class="muted">10 credits on the house. No card required.</p>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post">
        <label for="name">Name</label>
        <input type="text" id="name" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>

        <label for="email">Work email</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
        <p class="field-hint">At least 8 characters.</p>

        <div class="form-row">
            <button class="btn btn-primary btn-block" type="submit">Create account</button>
        </div>
    </form>
    <p class="muted" style="margin-top:18px;text-align:center;">
        Already have an account? <a href="<?= APP_URL ?>/login.php">Log in</a>
    </p>
</div>
<?php require_once __DIR__ . '/partials/footer.php'; ?>
