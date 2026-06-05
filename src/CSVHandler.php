<?php

require_once __DIR__ . '/DB.php';

class CSVHandler
{
    /**
     * Parse an uploaded CSV into an array of normalised lead rows.
     * Expected headers (lowercase):
     *   first_name, last_name, email, company, role, website, linkedin_url
     */
    public function parse($filePath)
    {
        $leads = [];
        if (($handle = fopen($filePath, 'r')) === false) {
            return [];
        }

        $headerRow = fgetcsv($handle);
        if ($headerRow === false) {
            fclose($handle);
            return [];
        }
        $headers = array_map(function ($h) {
            return strtolower(trim($h));
        }, $headerRow);

        while (($row = fgetcsv($handle)) !== false) {
            // Skip completely empty lines.
            if (count(array_filter($row, 'strlen')) === 0) {
                continue;
            }
            // Pad/trim row to header count so array_combine never fails.
            $row = array_pad(array_slice($row, 0, count($headers)), count($headers), '');
            $lead = array_combine($headers, $row);

            $leads[] = [
                'first_name'   => trim($lead['first_name']   ?? ''),
                'last_name'    => trim($lead['last_name']    ?? ''),
                'email'        => trim($lead['email']        ?? ''),
                'company'      => trim($lead['company']      ?? ''),
                'role'         => trim($lead['role']         ?? ''),
                'linkedin_url' => trim($lead['linkedin_url'] ?? ''),
                'website'      => trim($lead['website']      ?? ''),
            ];
        }

        fclose($handle);
        return $leads;
    }

    /**
     * Stream finished leads for a campaign as a CSV download.
     */
    public function export($campaignId)
    {
        $db   = DB::connect();
        $stmt = $db->prepare(
            "SELECT * FROM leads WHERE campaign_id = ? AND status = 'done'"
        );
        $stmt->execute([$campaignId]);
        $leads = $stmt->fetchAll();

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="results.csv"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['First', 'Last', 'Email', 'Company', 'Role', 'Subject', 'Body']);
        foreach ($leads as $l) {
            fputcsv($out, [
                $l['first_name'],
                $l['last_name'],
                $l['email'],
                $l['company'],
                $l['role'],
                $l['generated_subject'],
                $l['generated_email'],
            ]);
        }
        fclose($out);
        exit;
    }
}
