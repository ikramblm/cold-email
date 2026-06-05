<?php

require_once __DIR__ . '/DB.php';
require_once __DIR__ . '/Auth.php';

class Campaign
{
    /**
     * Create a campaign and queue its leads.
     * Credits are deducted UP FRONT (one per lead) — per the blueprint,
     * this prevents abuse and keeps revenue predictable.
     *
     * Returns [true, campaignId] or [false, errorMessage].
     */
    public static function create($userId, $name, $template, array $leads)
    {
        $db = DB::connect();

        $leads = self::filterValidLeads($leads);
        $count = count($leads);

        if ($count === 0) {
            return [false, 'No valid leads found in your CSV. Each lead needs at least an email.'];
        }

        $cost = $count * CREDITS_PER_LEAD;

        // Check + deduct credits atomically before doing any work.
        $stmt = $db->prepare(
            'UPDATE users SET credits = credits - ? WHERE id = ? AND credits >= ?'
        );
        $stmt->execute([$cost, $userId, $cost]);
        if ($stmt->rowCount() === 0) {
            $have = (int) Auth::credits();
            return [false, "Not enough credits. This campaign needs {$cost}, you have {$have}. Top up on the billing page."];
        }

        $db->beginTransaction();
        try {
            $stmt = $db->prepare(
                "INSERT INTO campaigns (user_id, name, template, status, total_leads)
                 VALUES (?, ?, ?, 'pending', ?)"
            );
            $stmt->execute([$userId, $name, $template, $count]);
            $campaignId = (int) $db->lastInsertId();

            $ins = $db->prepare(
                "INSERT INTO leads
                    (campaign_id, first_name, last_name, email, company, role, linkedin_url, website, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')"
            );
            foreach ($leads as $l) {
                $ins->execute([
                    $campaignId,
                    $l['first_name'],
                    $l['last_name'],
                    $l['email'],
                    $l['company'],
                    $l['role'],
                    $l['linkedin_url'],
                    $l['website'],
                ]);
            }

            $db->commit();
            return [true, $campaignId];
        } catch (Exception $e) {
            $db->rollBack();
            // Refund the credits we deducted since the insert failed.
            Auth::addCredits($userId, $cost);
            error_log('[Campaign] create failed: ' . $e->getMessage());
            return [false, 'Something went wrong creating the campaign. Your credits were not charged.'];
        }
    }

    /** All campaigns for a user, newest first. */
    public static function listForUser($userId)
    {
        $db   = DB::connect();
        $stmt = $db->prepare(
            'SELECT * FROM campaigns WHERE user_id = ? ORDER BY created_at DESC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /** One campaign, scoped to its owner so users can't read others' data. */
    public static function find($campaignId, $userId)
    {
        $db   = DB::connect();
        $stmt = $db->prepare('SELECT * FROM campaigns WHERE id = ? AND user_id = ?');
        $stmt->execute([$campaignId, $userId]);
        return $stmt->fetch() ?: null;
    }

    /** Leads belonging to a campaign. */
    public static function leads($campaignId)
    {
        $db   = DB::connect();
        $stmt = $db->prepare('SELECT * FROM leads WHERE campaign_id = ? ORDER BY id ASC');
        $stmt->execute([$campaignId]);
        return $stmt->fetchAll();
    }

    /** Live progress numbers for the dashboard / view page. */
    public static function progress($campaignId)
    {
        $db   = DB::connect();
        $stmt = $db->prepare(
            "SELECT
                COUNT(*) AS total,
                SUM(status = 'done')    AS done,
                SUM(status = 'failed')  AS failed,
                SUM(status = 'pending') AS pending
             FROM leads WHERE campaign_id = ?"
        );
        $stmt->execute([$campaignId]);
        $r = $stmt->fetch();
        return [
            'total'   => (int) $r['total'],
            'done'    => (int) $r['done'],
            'failed'  => (int) $r['failed'],
            'pending' => (int) $r['pending'],
        ];
    }

    private static function filterValidLeads(array $leads)
    {
        $valid = [];
        foreach ($leads as $l) {
            $email = trim($l['email'] ?? '');
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $valid[] = $l;
            }
        }
        return $valid;
    }
}
