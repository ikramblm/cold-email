<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Stripe.php';

$payload   = file_get_contents('php://input');
$sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

Stripe::handleWebhook($payload, $sigHeader);
