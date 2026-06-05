<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Stripe.php';
require_once __DIR__ . '/../src/DB.php';
Auth::requireLogin();

$user   = Auth::user();
$notice = '';
$error  = '';

// Returning from Stripe Checkout.
$status = $_GET['status'] ?? '';
if ($status === 'success') {
    $notice = 'Payment received. Credits are added once Stripe confirms the webhook — refresh in a moment if your balance hasn\'t updated.';
} elseif ($status === 'cancel') {
    $error = 'Checkout cancelled. No charge was made.';
}

// Start a checkout session.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['package'])) {
    [$ok, $result] = Stripe::checkout(Auth::id(), $_POST['package']);
    if ($ok) {
        header('Location: ' . $result);
        exit;
    }
    $error = $result;
}

$packages = Stripe::packages();

$db   = DB::connect();
$stmt = $db->prepare('SELECT * FROM payments WHERE user_id = ? ORDER BY created_at DESC LIMIT 10');
$stmt->execute([Auth::id()]);
$payments = $stmt->fetchAll();

$pageTitle = 'Billing';
require_once __DIR__ . '/partials/header.php';
?>
<div class="page-head">
    <h1>Billing</h1>
    <p class="sub">Current balance: <strong><?= (int) $user['credits'] ?></strong> credits ·
        Plan: <strong><?= htmlspecialchars(ucfirst($user['plan'])) ?></strong></p>
</div>

<?php if ($notice): ?><div class="alert alert-success"><?= htmlspecialchars($notice) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="price-grid">
    <?php foreach ($packages as $key => $pkg): ?>
        <div class="price-card<?= $key === 'pro' ? ' featured' : '' ?>">
            <h3><?= htmlspecialchars(ucfirst($key === 'payg' ? 'Pay-as-you-go' : $key)) ?></h3>
            <div class="amount">$<?= number_format($pkg['amount'] / 100, $pkg['amount'] % 100 ? 2 : 0) ?><small><?= $pkg['mode'] === 'subscription' ? '/mo' : ' one-time' ?></small></div>
            <ul>
                <li><?= $pkg['credits'] >= 100000 ? 'Unlimited' : number_format($pkg['credits']) ?> credits</li>
                <li><?= htmlspecialchars($pkg['label']) ?></li>
            </ul>
            <form method="post">
                <input type="hidden" name="package" value="<?= htmlspecialchars($key) ?>">
                <button class="btn btn-primary btn-block" type="submit">Buy</button>
            </form>
        </div>
    <?php endforeach; ?>
</div>

<div class="alert alert-info" style="margin-top:26px;">
    Checkout uses Stripe. Install the SDK with <code>composer require stripe/stripe-php</code> and set your
    keys in <code>config/config.php</code>. Point a Stripe webhook at
    <code><?= APP_URL ?>/webhook.php</code> for the <code>checkout.session.completed</code> event.
</div>

<?php if ($payments): ?>
    <h2 style="font-size:1.3rem;margin-top:36px;">Recent payments</h2>
    <table class="data">
        <thead><tr><th>Date</th><th>Amount</th><th>Credits</th><th>Stripe ID</th></tr></thead>
        <tbody>
        <?php foreach ($payments as $pay): ?>
            <tr>
                <td class="muted"><?= htmlspecialchars(date('M j, Y', strtotime($pay['created_at']))) ?></td>
                <td>$<?= number_format($pay['amount'] / 100, 2) ?></td>
                <td><?= (int) $pay['credits_added'] ?></td>
                <td class="muted"><?= htmlspecialchars(substr($pay['stripe_payment_id'], 0, 22)) ?>…</td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
