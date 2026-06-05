<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/Campaign.php';
Auth::requireLogin();

$id       = (int) ($_GET['id'] ?? 0);
$campaign = Campaign::find($id, Auth::id());
if (!$campaign) {
    http_response_code(404);
    $pageTitle = 'Not found';
    require_once __DIR__ . '/../partials/header.php';
    echo '<div class="card" style="margin-top:40px;"><h2>Campaign not found</h2><p class="muted">It may have been deleted or belong to another account.</p><a class="btn btn-ghost" href="' . APP_URL . '/dashboard.php">Back to dashboard</a></div>';
    require_once __DIR__ . '/../partials/footer.php';
    exit;
}

$leads = Campaign::leads($id);
$p     = Campaign::progress($id);
$pct   = $p['total'] ? round((($p['done'] + $p['failed']) / $p['total']) * 100) : 0;
$stillRunning = $p['pending'] > 0;

$pageTitle = $campaign['name'];
require_once __DIR__ . '/../partials/header.php';
?>
<?php if ($stillRunning): ?>
    <meta http-equiv="refresh" content="10">
<?php endif; ?>

<div class="page-head">
    <h1><?= htmlspecialchars($campaign['name']) ?></h1>
    <p class="sub">
        <span class="badge-status s-<?= htmlspecialchars($campaign['status']) ?>"><?= htmlspecialchars($campaign['status']) ?></span>
        · <?= $p['done'] ?> done · <?= $p['failed'] ?> failed · <?= $p['pending'] ?> pending
    </p>
</div>

<div class="card" style="margin-bottom:22px;">
    <div class="progress"><span style="width:<?= $pct ?>%"></span></div>
    <p class="field-hint" style="margin-top:10px;">
        <?= $p['done'] + $p['failed'] ?> of <?= $p['total'] ?> leads processed (<?= $pct ?>%).
        <?php if ($stillRunning): ?>
            This page refreshes automatically every 10 seconds.
        <?php endif; ?>
    </p>
    <div class="dash-actions" style="margin-bottom:0;">
        <?php if ($p['done'] > 0): ?>
            <a class="btn btn-primary" href="<?= APP_URL ?>/campaign/download.php?id=<?= $id ?>">⬇ Download results CSV</a>
        <?php endif; ?>
        <a class="btn btn-ghost" href="<?= APP_URL ?>/dashboard.php">Back to dashboard</a>
    </div>
</div>

<h2 style="font-size:1.3rem;">Generated emails</h2>
<?php foreach ($leads as $l): ?>
    <div class="card" style="margin-bottom:14px;">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
            <div>
                <strong><?= htmlspecialchars(trim($l['first_name'] . ' ' . $l['last_name'])) ?: $l['email'] ?></strong>
                <span class="muted"> · <?= htmlspecialchars($l['role']) ?><?= $l['company'] ? ' @ ' . htmlspecialchars($l['company']) : '' ?></span>
            </div>
            <span class="badge-status s-<?= htmlspecialchars($l['status']) ?>"><?= htmlspecialchars($l['status']) ?></span>
        </div>
        <?php if ($l['status'] === 'done'): ?>
            <div class="email-preview" style="margin-top:12px;">
                <div class="subj">Subject: <?= htmlspecialchars($l['generated_subject']) ?></div>
                <?= nl2br(htmlspecialchars($l['generated_email'])) ?>
            </div>
        <?php elseif ($l['status'] === 'failed'): ?>
            <p class="muted" style="margin:10px 0 0;">Generation failed for this lead. It won't be charged twice — retry by creating a new campaign with this row.</p>
        <?php else: ?>
            <p class="muted" style="margin:10px 0 0;">⏳ Waiting in the queue…</p>
        <?php endif; ?>
    </div>
<?php endforeach; ?>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
