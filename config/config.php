<?php
/**
 * Application configuration.
 *
 * For production, prefer real environment variables over hard-coded values.
 * Any getenv() below falls back to the local-dev default after the ?: operator.
 */

// ---- Database ----
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'coldemail');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

// ---- Perplexity ----
define('PERPLEXITY_API_KEY', getenv('PERPLEXITY_API_KEY') ?: 'your-perplexity-key-here');
define('PERPLEXITY_MODEL',   getenv('PERPLEXITY_MODEL')   ?: 'sonar');

// ---- Stripe ----
define('STRIPE_SECRET_KEY',      getenv('STRIPE_SECRET_KEY')      ?: 'sk_test_...');
define('STRIPE_PUBLISHABLE_KEY', getenv('STRIPE_PUBLISHABLE_KEY') ?: 'pk_test_...');
define('STRIPE_WEBHOOK_SECRET',  getenv('STRIPE_WEBHOOK_SECRET')  ?: 'whsec_...');

// ---- App ----
define('APP_URL', getenv('APP_URL') ?: 'http://localhost/cold-email-app/public');
define('APP_NAME', 'ColdReach');
define('CREDITS_PER_LEAD', 1);

// Where uploaded CSVs are stored temporarily (outside the web root).
define('UPLOAD_DIR', __DIR__ . '/../storage/uploads');

// ---- Error reporting (turn off display in production) ----
error_reporting(E_ALL);
ini_set('display_errors', getenv('APP_ENV') === 'production' ? '0' : '1');
date_default_timezone_set('UTC');
