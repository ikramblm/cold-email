<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/Campaign.php';
require_once __DIR__ . '/../../src/CSVHandler.php';
Auth::requireLogin();

$id = (int) ($_GET['id'] ?? 0);

// Ensure the campaign belongs to the logged-in user before exporting.
$campaign = Campaign::find($id, Auth::id());
if (!$campaign) {
    http_response_code(404);
    exit('Campaign not found.');
}

$csv = new CSVHandler();
$csv->export($id); // sends headers + streams + exits
