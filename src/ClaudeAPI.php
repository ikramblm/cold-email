<?php

class ClaudeAPI
{
    private $apiKey;
    private $model;

    public function __construct()
    {
        $this->apiKey = PERPLEXITY_API_KEY;
        $this->model  = defined('PERPLEXITY_MODEL') ? PERPLEXITY_MODEL : 'sonar';
    }

    /**
     * Generate a personalised subject + email body for a lead.
     * Returns an array ['subject' => ..., 'email' => ...] or null on failure.
     */
    public function generateEmail($leadData, $template)
    {
        $prompt = $this->buildPrompt($leadData, $template);

        $response = $this->call([
            'model'    => $this->model,
            'messages' => [['role' => 'user', 'content' => $prompt]],
        ]);

        $text = $response['choices'][0]['message']['content'] ?? null;
        if ($text === null) {
            return null;
        }

        return $this->parseOutput($text);
    }

    private function buildPrompt($lead, $template)
    {
        return "You are an expert cold email copywriter.\n\n"
             . "Lead Name: {$lead['first_name']} {$lead['last_name']}\n"
             . "Role: {$lead['role']}\n"
             . "Company: {$lead['company']}\n"
             . "Context: {$lead['raw_context']}\n\n"
             . "Template style: {$template}\n\n"
             . "Rules: Subject max 8 words. Opening line hyper-personalised.\n"
             . "Body 3-4 sentences. CTA = one simple question.\n"
             . "Total under 120 words. Tone: conversational, not salesy.\n\n"
             . 'Respond ONLY in JSON: {"subject":"...","email":"..."}';
    }

    /**
     * Models sometimes wrap JSON in ```json fences or add stray text.
     * Extract the first {...} block and decode defensively.
     */
    private function parseOutput($text)
    {
        $clean = trim($text);
        $clean = preg_replace('/^```(?:json)?|```$/m', '', $clean);

        // Grab the outermost JSON object if there is surrounding prose.
        if (preg_match('/\{.*\}/s', $clean, $m)) {
            $clean = $m[0];
        }

        $parsed = json_decode($clean, true);
        if (is_array($parsed) && isset($parsed['email'])) {
            return [
                'subject' => $parsed['subject'] ?? 'Following up',
                'email'   => $parsed['email'],
            ];
        }

        // Fallback: treat the whole text as the email body.
        return [
            'subject' => 'Following up',
            'email'   => trim($text),
        ];
    }

    private function call($payload)
    {
        $ch = curl_init('https://api.perplexity.ai/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
        ]);

        $result   = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err      = curl_error($ch);
        curl_close($ch);

        if ($result === false) {
            error_log('[PerplexityAPI] cURL error: ' . $err);
            return [];
        }
        if ($httpCode >= 400) {
            error_log('[PerplexityAPI] HTTP ' . $httpCode . ': ' . $result);
            return [];
        }

        return json_decode($result, true) ?: [];
    }
}
