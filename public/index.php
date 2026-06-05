<?php
$pageTitle = 'AI Cold Email Personalizer';
require_once __DIR__ . '/partials/header.php';
?>
<section class="hero">
    <div class="container">
        <span class="badge">Send 100 personalised emails in the time it took to send 10</span>
        <h1>Cold emails that actually get replies.</h1>
        <p class="lead">
            Upload your leads, pick a tone, and let AI research each prospect and write a
            hyper-personalised email — automatically. Built for SDRs, founders, and agencies
            who live in the inbox.
        </p>
        <div class="cta-row">
            <a class="btn btn-primary btn-lg" href="<?= APP_URL ?>/register.php">Start free — 10 credits</a>
            <a class="btn btn-ghost btn-lg" href="<?= APP_URL ?>/pricing.php" style="color:#fff;border-color:rgba(255,255,255,.3)">See pricing</a>
        </div>
    </div>
</section>

<div class="container">
    <div class="stat-strip">
        <div class="stat-card"><div class="num">3–4×</div><div class="lbl">Higher reply rates</div></div>
        <div class="stat-card"><div class="num">~5 sec</div><div class="lbl">Per personalised email</div></div>
        <div class="stat-card"><div class="num">CSV in</div><div class="lbl">CSV out</div></div>
        <div class="stat-card"><div class="num">$0</div><div class="lbl">To start</div></div>
    </div>
</div>

<section class="block" id="features">
    <div class="container">
        <h2>Why reps switch to <?= APP_NAME ?></h2>
        <p class="sub">Real personalisation takes 15–20 minutes per prospect. We get it down to seconds without sounding like a robot.</p>
        <div class="feature-grid">
            <div class="feature">
                <div class="ico">🔎</div>
                <h3>Auto-research</h3>
                <p>We pull context from each lead's company website so every email references something real.</p>
            </div>
            <div class="feature">
                <div class="ico">✍️</div>
                <h3>On-brand copy</h3>
                <p>Set your tone once. Get a tight subject line and a 3–4 sentence email with a single clear CTA.</p>
            </div>
            <div class="feature">
                <div class="ico">⚡</div>
                <h3>Bulk processing</h3>
                <p>Drop in a CSV of hundreds of leads. We process them in the background and show live progress.</p>
            </div>
            <div class="feature">
                <div class="ico">📤</div>
                <h3>Export anywhere</h3>
                <p>Download finished emails as CSV and load them straight into Instantly, Smartlead, or your CRM.</p>
            </div>
            <div class="feature">
                <div class="ico">💳</div>
                <h3>Credit-based</h3>
                <p>Pay only for what you generate. Free tier to try, paid plans when you scale.</p>
            </div>
            <div class="feature">
                <div class="ico">🏢</div>
                <h3>Agency-ready</h3>
                <p>Run outreach for multiple clients and resell. The unlimited plan is built for volume.</p>
            </div>
        </div>
    </div>
</section>

<section class="block" style="background:var(--surface);border-top:1px solid var(--line);border-bottom:1px solid var(--line);">
    <div class="container">
        <h2>How it works</h2>
        <p class="sub">Four steps, no learning curve.</p>
        <div class="steps">
            <div class="step"><h4>Upload</h4><p>Drop a CSV with names, companies, roles and websites.</p></div>
            <div class="step"><h4>Set the tone</h4><p>Tell us the style — punchy, warm, formal, founder-to-founder.</p></div>
            <div class="step"><h4>We generate</h4><p>Each lead is researched and written up in the background.</p></div>
            <div class="step"><h4>Export & send</h4><p>Preview, download the CSV, and fire it through your sender.</p></div>
        </div>
        <div style="margin-top:36px;">
            <a class="btn btn-primary btn-lg" href="<?= APP_URL ?>/register.php">Create your free account</a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
