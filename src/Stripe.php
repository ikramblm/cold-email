<?php

require_once __DIR__ . '/DB.php';
require_once __DIR__ . '/Auth.php';

/**
 * Thin Stripe wrapper.
 *
 * Uses the official Stripe PHP SDK if it is installed in /vendor
 * (composer require stripe/stripe-php). If the SDK is missing, the
 * methods degrade gracefully and return a helpful message so the rest
 * of the app still runs in local dev without Stripe configured.
 */
class Stripe
{
    /**
     * The purchasable packages. Keyed by a short slug used in URLs.
     * `amount` is in cents (Stripe expects the smallest currency unit).
     */
    public static function packages()
    {
        return [
            'starter' => ['label' => 'Starter — 100 credits / mo', 'amount' => 1900, 'credits' => 100,  'mode' => 'subscription'],
            'pro'     => ['label' => 'Pro — 500 credits / mo',     'amount' => 4900, 'credits' => 500,  'mode' => 'subscription'],
            'agency'  => ['label' => 'Agency — Unlimited',         'amount' => 19900,'credits' => 100000,'mode' => 'subscription'],
            'payg'    => ['label' => 'Pay-as-you-go — 50 credits', 'amount' => 900,  'credits' => 50,    'mode' => 'payment'],
        ];
    }

    private static function sdkAvailable()
    {
        $autoload = __DIR__ . '/../vendor/autoload.php';
        if (file_exists($autoload)) {
            require_once $autoload;
        }
        return class_exists('\Stripe\Stripe');
    }

    /**
     * Create a Checkout Session and return its URL, or [false, message].
     */
    public static function checkout($userId, $packageKey)
    {
        $packages = self::packages();
        if (!isset($packages[$packageKey])) {
            return [false, 'Unknown plan.'];
        }
        $pkg = $packages[$packageKey];

        if (!self::sdkAvailable()) {
            return [false, 'Stripe SDK not installed. Run: composer require stripe/stripe-php'];
        }

        try {
            \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

            $session = \Stripe\Checkout\Session::create([
                'mode'                 => $pkg['mode'],
                'success_url'          => APP_URL . '/billing.php?status=success',
                'cancel_url'           => APP_URL . '/billing.php?status=cancel',
                'client_reference_id'  => (string) $userId,
                'metadata'             => [
                    'user_id'     => $userId,
                    'package'     => $packageKey,
                    'credits'     => $pkg['credits'],
                ],
                'line_items' => [[
                    'quantity'   => 1,
                    'price_data' => [
                        'currency'     => 'usd',
                        'unit_amount'  => $pkg['amount'],
                        'recurring'    => $pkg['mode'] === 'subscription' ? ['interval' => 'month'] : null,
                        'product_data' => ['name' => $pkg['label']],
                    ],
                ]],
            ]);

            return [true, $session->url];
        } catch (Exception $e) {
            error_log('[Stripe] checkout error: ' . $e->getMessage());
            return [false, 'Could not start checkout: ' . $e->getMessage()];
        }
    }

    /**
     * Handle an incoming webhook. Verifies the signature, then credits
     * the user on a successful checkout. Echoes a status and exits.
     */
    public static function handleWebhook($rawPayload, $sigHeader)
    {
        if (!self::sdkAvailable()) {
            http_response_code(500);
            echo 'Stripe SDK not installed';
            return;
        }

        try {
            $event = \Stripe\Webhook::constructEvent(
                $rawPayload,
                $sigHeader,
                STRIPE_WEBHOOK_SECRET
            );
        } catch (Exception $e) {
            http_response_code(400);
            echo 'Invalid signature';
            return;
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            $userId  = (int) ($session->metadata->user_id ?? $session->client_reference_id ?? 0);
            $credits = (int) ($session->metadata->credits ?? 0);
            $package = $session->metadata->package ?? '';
            $amount  = (int) ($session->amount_total ?? 0);

            if ($userId && $credits) {
                self::applyPurchase($userId, $credits, $amount, $package, $session->id);
            }
        }

        http_response_code(200);
        echo 'ok';
    }

    private static function applyPurchase($userId, $credits, $amountCents, $package, $stripeId)
    {
        $db = DB::connect();

        // Idempotency: ignore if we've already recorded this Stripe id.
        $stmt = $db->prepare('SELECT id FROM payments WHERE stripe_payment_id = ?');
        $stmt->execute([$stripeId]);
        if ($stmt->fetch()) {
            return;
        }

        $db->prepare(
            'INSERT INTO payments (user_id, amount, credits_added, stripe_payment_id)
             VALUES (?, ?, ?, ?)'
        )->execute([$userId, $amountCents, $credits, $stripeId]);

        Auth::addCredits($userId, $credits);

        // Reflect the plan on the user record for subscription packages.
        if (in_array($package, ['starter', 'pro', 'agency'], true)) {
            $db->prepare('UPDATE users SET plan = ? WHERE id = ?')
               ->execute([$package, $userId]);
        }
    }
}
