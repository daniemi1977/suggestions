<?php
/**
 * SupaData API Integration
 *
 * Handles video transcription with native captions fallback
 */

if (!defined('ABSPATH')) {
    exit;
}

class IPV_SupaData_API {

    /**
     * API endpoints
     */
    private $api_endpoint = 'https://api.supadata.ai/v1/transcribe';

    /**
     * Get API key from settings
     */
    private function get_api_key() {
        return get_option('ipv_pro_supadata_api_key', '');
    }

    /**
     * Get transcript mode from settings
     */
    private function get_transcript_mode() {
        return get_option('ipv_pro_transcript_mode', 'auto');
    }

    /**
     * Get transcript timeout from settings
     */
    private function get_timeout() {
        return (int)get_option('ipv_pro_transcript_timeout', 300);
    }

    /**
     * Generate transcript for YouTube video
     *
     * @param string $video_url YouTube video URL
     * @return string|WP_Error Transcript text or error
     */
    public function generate_transcript($video_url) {
        $api_key = $this->get_api_key();

        if (empty($api_key)) {
            return new WP_Error('no_api_key', 'SupaData API key non configurata');
        }

        $mode = $this->get_transcript_mode();

        // Step 1: Try native captions first
        $result = $this->call_api('native', $video_url);

        if (!is_wp_error($result) && $result['status'] === 200) {
            return $result['text'];
        }

        // Step 2: Fallback to generated transcription (if mode is 'auto' or 'generate')
        if ($mode === 'auto' || $mode === 'generate') {
            $result = $this->call_api('generate', $video_url);

            if (!is_wp_error($result)) {
                if ($result['status'] === 200) {
                    return $result['text'];
                }

                // Job queued - poll for result
                if ($result['status'] === 202 && isset($result['job_id'])) {
                    return $this->poll_job($result['job_id']);
                }
            }
        }

        // No transcript available
        if ($mode === 'native_only') {
            return new WP_Error('no_native_captions', 'Sottotitoli nativi non disponibili per questo video');
        }

        return new WP_Error('transcription_failed', 'Impossibile generare trascrizione per questo video');
    }

    /**
     * Call SupaData API
     *
     * @param string $method 'native' or 'generate'
     * @param string $video_url YouTube video URL
     * @return array|WP_Error API response or error
     */
    private function call_api($method, $video_url) {
        $api_key = $this->get_api_key();

        $body = [
            'url' => $video_url,
            'method' => $method,
            'language' => 'it'
        ];

        $response = wp_remote_post($this->api_endpoint, [
            'timeout' => 60,
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ],
            'body' => json_encode($body)
        ]);

        if (is_wp_error($response)) {
            return new WP_Error('api_error', 'Errore connessione API: ' . $response->get_error_message());
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);

        // Handle different response codes
        switch ($response_code) {
            case 200:
                // Success - transcript ready
                return [
                    'status' => 200,
                    'text' => isset($data['transcript']) ? $data['transcript'] : $data['text']
                ];

            case 202:
                // Accepted - job queued
                return [
                    'status' => 202,
                    'job_id' => $data['job_id'],
                    'message' => 'Job in coda'
                ];

            case 400:
                return new WP_Error('bad_request', 'Richiesta non valida: ' . ($data['message'] ?? 'Errore sconosciuto'));

            case 401:
                return new WP_Error('unauthorized', 'API key non valida');

            case 404:
                return new WP_Error('not_found', 'Video non trovato o sottotitoli non disponibili');

            case 429:
                return new WP_Error('rate_limit', 'Limite richieste API superato');

            default:
                return new WP_Error('api_error', 'Errore API: ' . $response_code);
        }
    }

    /**
     * Poll SupaData job until complete
     *
     * @param string $job_id Job ID from SupaData
     * @return string|WP_Error Transcript text or error
     */
    private function poll_job($job_id) {
        $api_key = $this->get_api_key();
        $timeout = $this->get_timeout();
        $start_time = time();
        $poll_interval = 5; // seconds

        $poll_url = "https://api.supadata.ai/v1/jobs/{$job_id}";

        while ((time() - $start_time) < $timeout) {
            $response = wp_remote_get($poll_url, [
                'timeout' => 30,
                'headers' => [
                    'Authorization' => 'Bearer ' . $api_key,
                    'Accept' => 'application/json'
                ]
            ]);

            if (is_wp_error($response)) {
                return new WP_Error('polling_error', 'Errore durante polling: ' . $response->get_error_message());
            }

            $response_code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            if ($response_code === 200) {
                if (isset($data['status'])) {
                    switch ($data['status']) {
                        case 'completed':
                            return $data['transcript'] ?? $data['text'];

                        case 'failed':
                            return new WP_Error('job_failed', 'Job fallito: ' . ($data['error'] ?? 'Errore sconosciuto'));

                        case 'processing':
                        case 'queued':
                            // Continue polling
                            sleep($poll_interval);
                            continue 2;
                    }
                }
            }

            // Unexpected response
            sleep($poll_interval);
        }

        return new WP_Error('timeout', 'Timeout durante generazione trascrizione (>300s)');
    }

    /**
     * Test API connection
     *
     * @return array Result with status and message
     */
    public function test_connection() {
        $api_key = $this->get_api_key();

        if (empty($api_key)) {
            return [
                'success' => false,
                'message' => 'API key non configurata'
            ];
        }

        // Test with a short public video
        $test_url = 'https://www.youtube.com/watch?v=jNQXAC9IVRw'; // "Me at the zoo" - first YouTube video

        $result = $this->call_api('native', $test_url);

        if (is_wp_error($result)) {
            // Check if it's just "no captions" - that's actually OK for connection test
            if ($result->get_error_code() === 'not_found') {
                return [
                    'success' => true,
                    'message' => 'Connessione riuscita! API key valida (video test senza sottotitoli).'
                ];
            }

            return [
                'success' => false,
                'message' => $result->get_error_message()
            ];
        }

        return [
            'success' => true,
            'message' => 'Connessione riuscita! API key valida.'
        ];
    }

    /**
     * Format transcript with timestamps
     *
     * @param string $transcript Raw transcript
     * @return string Formatted transcript
     */
    public function format_transcript($transcript) {
        // Add basic formatting if needed
        $transcript = trim($transcript);

        // Split into paragraphs every ~500 characters at sentence boundaries
        $sentences = preg_split('/(?<=[.!?])\s+/', $transcript);
        $paragraphs = [];
        $current = '';

        foreach ($sentences as $sentence) {
            if (strlen($current) + strlen($sentence) > 500 && !empty($current)) {
                $paragraphs[] = trim($current);
                $current = $sentence;
            } else {
                $current .= ' ' . $sentence;
            }
        }

        if (!empty($current)) {
            $paragraphs[] = trim($current);
        }

        return implode("\n\n", $paragraphs);
    }

    /**
     * Get transcript statistics
     *
     * @param string $transcript Transcript text
     * @return array Statistics
     */
    public function get_stats($transcript) {
        $word_count = str_word_count($transcript);
        $char_count = strlen($transcript);
        $estimated_duration = round($word_count / 150, 1); // 150 words per minute

        return [
            'word_count' => $word_count,
            'char_count' => $char_count,
            'estimated_duration' => $estimated_duration . ' minuti'
        ];
    }
}
