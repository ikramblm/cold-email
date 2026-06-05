<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/Campaign.php';
require_once __DIR__ . '/../../src/CSVHandler.php';
Auth::requireLogin();

$error = '';
$tones = [
    'Punchy & direct'           => 'Punchy and direct. Short sentences, no fluff, gets to the point fast.',
    'Warm & friendly'           => 'Warm and friendly. Conversational, like emailing a peer you respect.',
    'Formal & professional'     => 'Formal and professional. Polished business tone, still human.',
    'Founder-to-founder'        => 'Founder-to-founder. Casual, candid, no corporate speak.',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $template = trim($_POST['template'] ?? '');

    if ($name === '') {
        $error = 'Give your campaign a name.';
    } elseif (empty($_FILES['csv']['tmp_name']) || $_FILES['csv']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Please upload a valid CSV file.';
    } else {
        // Validate extension + size.
        $ext  = strtolower(pathinfo($_FILES['csv']['name'], PATHINFO_EXTENSION));
        if ($ext !== 'csv') {
            $error = 'File must be a .csv';
        } elseif ($_FILES['csv']['size'] > 5 * 1024 * 1024) {
            $error = 'CSV is too large (max 5 MB).';
        } else {
            if (!is_dir(UPLOAD_DIR)) {
                mkdir(UPLOAD_DIR, 0775, true);
            }
            $dest = UPLOAD_DIR . '/' . uniqid('leads_', true) . '.csv';
            move_uploaded_file($_FILES['csv']['tmp_name'], $dest);

            $csv   = new CSVHandler();
            $leads = $csv->parse($dest);
            @unlink($dest);

            [$ok, $result] = Campaign::create(Auth::id(), $name, $template, $leads);
            if ($ok) {
                header('Location: ' . APP_URL . '/campaign/view.php?id=' . $result);
                exit;
            }
            $error = $result;
        }
    }
}

$pageTitle = 'New campaign';
require_once __DIR__ . '/../partials/header.php';
?>
<div class="page-head">
    <h1>New campaign</h1>
    <p class="sub">Upload your leads and choose a tone. We deduct 1 credit per lead when the campaign is created.</p>
</div>

<div class="card" style="max-width:680px;">
    <div class="alert alert-info">
        Your CSV must have these lowercase headers:
        <code>first_name, last_name, email, company, role, website, linkedin_url</code>.
        <a href="<?= APP_URL ?>/assets/sample_leads.csv" download>Download a sample CSV →</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <p class="field-hint">You currently have <strong><?= (int) Auth::credits() ?></strong> credits.</p>

    <form method="post" enctype="multipart/form-data">
        <label for="name">Campaign name</label>
        <input type="text" id="name" name="name" placeholder="Q2 SaaS founders outreach"
               value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>

        <label for="tone">Tone preset</label>
        <select id="tone" onchange="document.getElementById('template').value = this.value;">
            <?php foreach ($tones as $label => $val): ?>
                <option value="<?= htmlspecialchars($val) ?>"><?= htmlspecialchars($label) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="template">Tone / template instructions</label>
        <textarea id="template" name="template" placeholder="Describe the style you want..."><?= htmlspecialchars($_POST['template'] ?? reset($tones)) ?></textarea>
        <p class="field-hint">This guides Claude's writing style for every email in the campaign.</p>

        <label for="csv">Leads CSV</label>
        <input type="file" id="csv" name="csv" accept=".csv" required>

        <div class="form-row">
            <button class="btn btn-primary" type="submit">Create campaign &amp; queue leads</button>
        </div>
    </form>
</div>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>
