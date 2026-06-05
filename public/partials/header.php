<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/Auth.php';
Auth::boot();
$loggedIn = Auth::check();
$pageTitle = $pageTitle ?? APP_NAME;
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> · <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/style.css">
</head>
<body>
<header class="site-header">
    <div class="container nav">
        <a class="brand" href="<?= APP_URL ?>/index.php">
            <span class="brand-mark">✶</span> <?= APP_NAME ?>
        </a>
        <nav class="nav-links">
            <a href="<?= APP_URL ?>/index.php#features">Features</a>
            <a href="<?= APP_URL ?>/pricing.php">Pricing</a>
            <?php if ($loggedIn): ?>
                <a href="<?= APP_URL ?>/dashboard.php">Dashboard</a>
                <a href="<?= APP_URL ?>/billing.php">Billing</a>
                <span class="credit-pill"><?= (int) Auth::credits() ?> credits</span>
                <a class="btn btn-ghost" href="<?= APP_URL ?>/logout.php">Log out</a>
            <?php else: ?>
                <a href="<?= APP_URL ?>/login.php">Log in</a>
                <a class="btn btn-primary" href="<?= APP_URL ?>/register.php">Start free</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main class="container">
