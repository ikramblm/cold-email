<?php

require_once __DIR__ . '/ClaudeAPI.php';
require_once __DIR__ . '/Scraper.php';
require_once __DIR__ . '/DB.php';

class LeadProcessor
{
    private $claude;
    private $scraper;
    private $db;

    public function __construct()
    {
        $this->claude  = new ClaudeAPI();
        $this->scraper = new Scraper();
        $this->db      = DB::connect();
    }

    /**
     * Process a single lead end-to-end and return true on success.
     */
    public function processLead($leadId, $template)
    {
        $stmt = $this->db->prepare('SELECT * FROM leads WHERE id = ?');
        $stmt->execute([$leadId]);
        $lead = $stmt->fetch();
        if (!$lead) {
            return false;
        }

        // 1. Build research context (scrapes website if present).
        $context = $this->scraper->buildContext($lead);
        $this->db->prepare('UPDATE leads SET raw_context = ? WHERE id = ?')
                 ->execute([$context, $leadId]);
        $lead['raw_context'] = $context;

        // 2. Generate the email via Claude.
        $output = $this->claude->generateEmail($lead, $template);
        if (!$output) {
            $this->db->prepare("UPDATE leads SET status = 'failed' WHERE id = ?")
                     ->execute([$leadId]);
            $this->bumpCampaignProgress($lead['campaign_id']);
            return false;
        }

        // 3. Store the result.
        $this->db->prepare(
            "UPDATE leads
                SET generated_subject = ?, generated_email = ?, status = 'done'
              WHERE id = ?"
        )->execute([$output['subject'], $output['email'], $leadId]);

        // 4. Advance the campaign counter / status.
        $this->bumpCampaignProgress($lead['campaign_id']);

        return true;
    }

    /**
     * Recompute processed_leads and flip the campaign to 'done'
     * once nothing is left pending.
     */
    private function bumpCampaignProgress($campaignId)
    {
        $this->db->prepare(
            "UPDATE campaigns c
                SET processed_leads = (
                        SELECT COUNT(*) FROM leads
                         WHERE campaign_id = c.id AND status <> 'pending'
                    )
              WHERE c.id = ?"
        )->execute([$campaignId]);

        // Any still pending?
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS pending FROM leads
              WHERE campaign_id = ? AND status = 'pending'"
        );
        $stmt->execute([$campaignId]);
        $pending = (int) $stmt->fetchColumn();

        if ($pending === 0) {
            $this->db->prepare("UPDATE campaigns SET status = 'done' WHERE id = ?")
                     ->execute([$campaignId]);
        } else {
            $this->db->prepare(
                "UPDATE campaigns SET status = 'processing'
                  WHERE id = ? AND status = 'pending'"
            )->execute([$campaignId]);
        }
    }
}
