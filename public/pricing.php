<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Auth.php';
Auth::boot();
$loggedIn = Auth::check();
$ctaTarget = $loggedIn ? '/billing.php' : '/register.php';

$pageTitle = 'Pricing';
require_once __DIR__ . '/partials/header.php';
?>
<div class="page-head" style="text-align:center;">
    <h1>Simple, credit-based pricing</h1>
    <p class="sub" style="margin:0 auto;">1 credit = 1 personalised email. Start free, upgrade when it's working.</p>
</div>

<div class="price-grid">
    <div class="price-card">
        <h3>Free</h3>
        <div class="amount">$0</div>
        <ul>
            <li>10 credits</li>
            <li>Try before buying</li>
            <li>CSV import &amp; export</li>
            <li>Background processing</li>
        </ul>
        <a class="btn btn-ghost btn-block" href="<?= APP_URL . ($loggedIn ? '/dashboard.php' : '/register.php') ?>">Get started</a>
    </div>

    <div class="price-card">
        <h3>Starter</h3>
        <div class="amount">$19<small>/mo</small></div>
        <ul>
            <li>100 credits / month</li>
            <li>Solo founders &amp; SDRs</li>
            <li>All tone presets</li>
            <li>Email previews</li>
        </ul>
        <a class="btn btn-primary btn-block" href="<?= APP_URL . $ctaTarget ?>">Choose Starter</a>
    </div>

    <div class="price-card featured">
        <h3>Pro</h3>
        <div class="amount">$49<small>/mo</small></div>
        <ul>
            <li>500 credits / month</li>
            <li>High-volume reps</li>
            <li>Priority queue</li>
            <li>Everything in Starter</li>
        </ul>
        <a class="btn btn-primary btn-block" href="<?= APP_URL . $ctaTarget ?>">Choose Pro</a>
    </div>

    <div class="price-card">
        <h3>Agency</h3>
        <div class="amount">$199<small>/mo</small></div>
        <ul>
            <li>Unlimited credits</li>
            <li>Resell to clients</li>
            <li>Run 5–20 accounts</li>
            <li>Everything in Pro</li>
        </ul>
        <a class="btn btn-ghost btn-block" href="<?= APP_URL . $ctaTarget ?>">Choose Agency</a>
    </div>
</div>

<p class="muted" style="text-align:center;margin-top:28px;">
    Just need a top-up? <strong>Pay-as-you-go</strong> — 50 credits for $9, one time. Available on the
    <a href="<?= APP_URL . $ctaTarget ?>">billing page</a>.
</p>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
