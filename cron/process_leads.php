<?php
/**
 * Background lead processor.
 *
 * Schedule (Linux, every minute):
 *   * * * * * php /var/www/html/cold-email-app/cron/process_leads.php >> /var/log/coldemail.log 2>&1
 *
 * Processes up to BATCH pending leads per run, sleeping briefly between
 * each to respect Claude API rate limits.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/DB.php';
require_once __DIR__ . '/../src/LeadProcessor.php';

const BATCH = 5;

$db        = DB::connect();
$processor = new LeadProcessor();

$stmt = $db->prepare(
    "SELECT l.id AS lead_id, c.template
       FROM leads l
       JOIN campaigns c ON l.campaign_id = c.id
      WHERE l.status = 'pending'
      ORDER BY l.id ASC
      LIMIT " . BATCH
);
$stmt->execute();
$pending = $stmt->fetchAll();

$ts = date('Y-m-d H:i:s');
if (!$pending) {
    echo "[$ts] No pending leads.\n";
    exit;
}

echo "[$ts] Processing " . count($pending) . " lead(s)...\n";

foreach ($pending as $item) {
    $ok = $processor->processLead($item['lead_id'], $item['template'] ?? '');
    echo "  lead #{$item['lead_id']}: " . ($ok ? 'done' : 'failed') . "\n";
    sleep(1); // be gentle on the API
}

echo "[$ts] Batch complete.\n";
