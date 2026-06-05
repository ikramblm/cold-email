<?php

class Scraper
{
    /**
     * Fetch a public web page and return a trimmed plain-text snippet.
     */
    public function scrapeWebsite($url)
    {
        if (empty($url)) {
            return '';
        }

        // Only allow http/https to avoid file:// or gopher:// tricks.
        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (!in_array($scheme, ['http', 'https'], true)) {
            return '';
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; ColdReachBot/1.0)',
        ]);
        $html = curl_exec($ch);
        curl_close($ch);

        if (!$html) {
            return '';
        }

        // Drop scripts/styles before stripping tags.
        $html = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', ' ', $html);
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);

        return trim(mb_substr($text, 0, 800));
    }

    /**
     * Assemble everything we know about a lead into a context blob
     * that gets fed to Claude.
     */
    public function buildContext($lead)
    {
        $parts = [];

        if (!empty($lead['role'])) {
            $parts[] = "Role: {$lead['role']}";
        }
        if (!empty($lead['company'])) {
            $parts[] = "Company: {$lead['company']}";
        }
        if (!empty($lead['linkedin_url'])) {
            $parts[] = "LinkedIn: {$lead['linkedin_url']}";
        }
        if (!empty($lead['website'])) {
            $txt = $this->scrapeWebsite($lead['website']);
            if ($txt) {
                $parts[] = 'From website: ' . mb_substr($txt, 0, 500);
            }
        }

        return implode("\n", $parts);
    }
}
