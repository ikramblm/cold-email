<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Campaign.php';
Auth::requireLogin();

$user      = Auth::user();
$campaigns = Campaign::listForUser(Auth::id());

$pageTitle = 'Dashboard';
require_once __DIR__ . '/partials/header.php';
?>
<div class="page-head">
    <h1>Hi, <?= htmlspecialchars($user['name']) ?> 👋</h1>
    <p class="sub">You have <strong><?= (int) $user['credits'] ?></strong> credits on the
        <strong><?= htmlspecialchars(ucfirst($user['plan'])) ?></strong> plan.</p>
</div>

<div class="dash-actions">
    <a class="btn btn-primary" href="<?= APP_URL ?>/campaign/new.php">+ New campaign</a>
    <a class="btn btn-ghost" href="<?= APP_URL ?>/billing.php">Buy credits</a>
</div>

<?php if (!$campaigns): ?>
    <div class="card">
        <h3 style="margin-top:0;">No campaigns yet</h3>
        <p class="muted">Upload a CSV of leads to generate your first batch of personalised emails.</p>
        <a class="btn btn-primary" href="<?= APP_URL ?>/campaign/new.php">Create your first campaign</a>
    </div>
<?php else: ?>
    <table class="data">
        <thead>
            <tr>
                <th>Campaign</th>
                <th>Status</th>
                <th>Progress</th>
                <th>Leads</th>
                <th>Created</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($campaigns as $c):
            $p   = Campaign::progress($c['id']);
            $pct = $p['total'] ? round((($p['done'] + $p['failed']) / $p['total']) * 100) : 0;
        ?>
            <tr>
                <td><strong><?= htmlspecialchars($c['name']) ?></strong></td>
                <td><span class="badge-status s-<?= htmlspecialchars($c['status']) ?>"><?= htmlspecialchars($c['status']) ?></span></td>
                <td style="min-width:160px;">
                    <div class="progress"><span style="width:<?= $pct ?>%"></span></div>
                    <small class="muted"><?= $p['done'] + $p['failed'] ?>/<?= $p['total'] ?> · <?= $pct ?>%</small>
                </td>
                <td><?= (int) $c['total_leads'] ?></td>
                <td class="muted"><?= htmlspecialchars(date('M j, Y', strtotime($c['created_at']))) ?></td>
                <td><a class="btn btn-ghost" href="<?= APP_URL ?>/campaign/view.php?id=<?= (int) $c['id'] ?>">View</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <p class="field-hint" style="margin-top:14px;">Campaigns process in the background via a cron job. Refresh to see progress update.</p>
<?php endif; ?>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
