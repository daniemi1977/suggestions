<?php
/**
 * IPV Production System Pro - SupaData API Integration
 *
 * Gestione trascrizioni video con:
 * - Rotazione API key automatica
 * - Gestione errori 402 (quota esaurita) e 429 (rate limit)
 * - Supporto per job asincroni (video lunghi)
 * - Polling automatico per risultati
 *
 * @package IPV_Production_System_Pro
 * @version 4.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_Supadata {

    /**
     * Endpoint base SupaData API
     */
    const API_ENDPOINT = 'https://api.supadata.ai/v1/transcript';

    /**
     * Timeout per richieste HTTP
     */
    const REQUEST_TIMEOUT = 120;

    /**
     * Massimo numero di tentativi di polling per job asincroni
     */
    const MAX_POLL_ATTEMPTS = 30;

    /**
     * Intervallo tra polling (secondi)
     */
    const POLL_INTERVAL = 5;

    /**
     * Ottiene la trascrizione di un video
     *
     * @param string $video_id YouTube Video ID
     * @param string $mode Modalità: 'auto', 'native', 'generate'
     * @param string $lang Lingua preferita (ISO 639-1)
     * @return string|WP_Error Trascrizione o errore
     */
    public static function get_transcript( $video_id, $mode = 'auto', $lang = 'it' ) {
        $api_keys = self::get_api_keys();

        if ( empty( $api_keys ) ) {
            return new WP_Error(
                'ipv_supadata_no_key',
                'SupaData API Key non configurata. Vai in Impostazioni per configurarla.'
            );
        }

        $video_url = 'https://www.youtube.com/watch?v=' . $video_id;
        $last_error = null;

        // Prova ogni API key disponibile (rotazione automatica)
        foreach ( $api_keys as $index => $api_key ) {
            IPV_Prod_Logger::log( 'SupaData: Tentativo con API key #' . ( $index + 1 ), [
                'video_id' => $video_id,
                'mode'     => $mode,
            ] );

            $result = self::make_transcript_request( $video_url, $api_key, $mode, $lang );

            if ( ! is_wp_error( $result ) ) {
                // Successo!
                return $result;
            }

            $error_code = $result->get_error_code();
            $last_error = $result;

            // Se errore 402 o 429, prova la prossima key
            if ( in_array( $error_code, [ 'ipv_supadata_quota', 'ipv_supadata_rate_limit' ], true ) ) {
                IPV_Prod_Logger::log( 'SupaData: Key #' . ( $index + 1 ) . ' esaurita/rate limited, provo la successiva', [
                    'error' => $result->get_error_message(),
                ] );
                continue;
            }

            // Per altri errori, non provare altre key
            break;
        }

        // Tutte le key hanno fallito
        return $last_error ?? new WP_Error( 'ipv_supadata_error', 'Errore SupaData sconosciuto.' );
    }

    /**
     * Esegue la richiesta di trascrizione
     *
     * @param string $video_url URL completo del video
     * @param string $api_key API key da usare
     * @param string $mode Modalità trascrizione
     * @param string $lang Lingua preferita
     * @return string|WP_Error Trascrizione o errore
     */
    protected static function make_transcript_request( $video_url, $api_key, $mode, $lang ) {
        $args = [
            'headers' => [
                'x-api-key' => $api_key,
                'Accept'    => 'application/json',
            ],
            'timeout' => self::REQUEST_TIMEOUT,
        ];

        $query = [
            'url'  => $video_url,
            'text' => 'true', // Vogliamo plain text, non chunks
            'mode' => $mode,
        ];

        if ( ! empty( $lang ) ) {
            $query['lang'] = $lang;
        }

        $request_url = add_query_arg( $query, self::API_ENDPOINT );

        $response = wp_remote_get( $request_url, $args );

        if ( is_wp_error( $response ) ) {
            return new WP_Error(
                'ipv_supadata_connection',
                'Errore di connessione a SupaData: ' . $response->get_error_message()
            );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        // Gestione codici HTTP specifici
        switch ( $code ) {
            case 200:
                // Risposta immediata con trascrizione
                // IMPORTANTE: Il campo corretto è 'content', non 'transcript'
                if ( ! empty( $data['content'] ) ) {
                    IPV_Prod_Logger::log( 'SupaData: Trascrizione ricevuta', [
                        'length' => strlen( $data['content'] ),
                        'lang'   => $data['lang'] ?? 'unknown',
                    ] );
                    return $data['content'];
                }
                return new WP_Error( 'ipv_supadata_empty', 'SupaData ha restituito una risposta vuota.' );

            case 202:
                // Job asincrono - polling richiesto
                if ( ! empty( $data['jobId'] ) ) {
                    IPV_Prod_Logger::log( 'SupaData: Job asincrono avviato', [ 'job_id' => $data['jobId'] ] );
                    return self::poll_job_result( $data['jobId'], $api_key );
                }
                return new WP_Error( 'ipv_supadata_no_job_id', 'Risposta 202 senza jobId.' );

            case 206:
                // Trascrizione nativa non disponibile (solo in mode=native)
                return new WP_Error(
                    'ipv_supadata_no_native',
                    'Trascrizione nativa non disponibile per questo video. Prova con mode=auto.'
                );

            case 400:
                $error_msg = $data['error'] ?? $data['message'] ?? 'Richiesta non valida';
                return new WP_Error( 'ipv_supadata_bad_request', 'Errore SupaData: ' . $error_msg );

            case 402:
                // Crediti esauriti - questa key passa alla successiva
                return new WP_Error(
                    'ipv_supadata_quota',
                    'Crediti SupaData esauriti per questa API key. Aggiungi crediti o usa un\'altra key.'
                );

            case 429:
                // Rate limit - questa key passa alla successiva
                return new WP_Error(
                    'ipv_supadata_rate_limit',
                    'Rate limit SupaData raggiunto. Riprova tra qualche secondo.'
                );

            case 500:
            case 502:
            case 503:
                return new WP_Error(
                    'ipv_supadata_server_error',
                    'Errore server SupaData (HTTP ' . $code . '). Riprova più tardi.'
                );

            default:
                $error_msg = $data['error'] ?? $data['message'] ?? 'Errore sconosciuto';
                return new WP_Error(
                    'ipv_supadata_http_error',
                    'Errore HTTP ' . $code . ' da SupaData: ' . $error_msg
                );
        }
    }

    /**
     * Polling per job asincroni (video lunghi)
     *
     * @param string $job_id ID del job SupaData
     * @param string $api_key API key da usare
     * @return string|WP_Error Trascrizione o errore
     */
    protected static function poll_job_result( $job_id, $api_key ) {
        $poll_url = self::API_ENDPOINT . '/' . $job_id;

        for ( $attempt = 1; $attempt <= self::MAX_POLL_ATTEMPTS; $attempt++ ) {
            // Attendi prima di ogni polling (tranne il primo)
            if ( $attempt > 1 ) {
                sleep( self::POLL_INTERVAL );
            }

            $response = wp_remote_get( $poll_url, [
                'headers' => [
                    'x-api-key' => $api_key,
                    'Accept'    => 'application/json',
                ],
                'timeout' => 30,
            ] );

            if ( is_wp_error( $response ) ) {
                continue; // Riprova
            }

            $code = wp_remote_retrieve_response_code( $response );
            $data = json_decode( wp_remote_retrieve_body( $response ), true );

            if ( $code !== 200 ) {
                continue; // Riprova
            }

            $status = $data['status'] ?? '';

            switch ( $status ) {
                case 'completed':
                    if ( ! empty( $data['content'] ) ) {
                        IPV_Prod_Logger::log( 'SupaData: Job completato', [
                            'job_id'  => $job_id,
                            'attempt' => $attempt,
                        ] );
                        return $data['content'];
                    }
                    return new WP_Error( 'ipv_supadata_empty', 'Job completato ma senza contenuto.' );

                case 'failed':
                    $error_msg = $data['error'] ?? 'Job fallito senza dettagli';
                    return new WP_Error( 'ipv_supadata_job_failed', 'Job SupaData fallito: ' . $error_msg );

                case 'queued':
                case 'active':
                    // Ancora in elaborazione, continua polling
                    IPV_Prod_Logger::log( 'SupaData: Job in corso', [
                        'job_id'  => $job_id,
                        'status'  => $status,
                        'attempt' => $attempt,
                    ] );
                    continue 2; // Continua il loop for
            }
        }

        return new WP_Error(
            'ipv_supadata_timeout',
            'Timeout: il job SupaData non si è completato in tempo (' . ( self::MAX_POLL_ATTEMPTS * self::POLL_INTERVAL ) . ' secondi).'
        );
    }

    /**
     * Ottiene la lista delle API key configurate (supporta rotazione)
     *
     * @return array Lista di API key
     */
    protected static function get_api_keys() {
        // Supporto per multiple key (una per riga) o singola key
        $keys_raw = get_option( 'ipv_supadata_api_key', '' );

        if ( empty( $keys_raw ) ) {
            return [];
        }

        // Se contiene newline, split per riga (rotazione automatica)
        if ( strpos( $keys_raw, "\n" ) !== false ) {
            $keys = array_filter(
                array_map( 'trim', explode( "\n", $keys_raw ) ),
                function( $k ) {
                    return ! empty( $k ) && strpos( $k, '#' ) !== 0; // Ignora linee vuote e commenti
                }
            );
            return array_values( $keys );
        }

        // Singola key
        return [ trim( $keys_raw ) ];
    }

    /**
     * Testa una API key
     *
     * @param string $api_key API key da testare
     * @return bool|WP_Error True se funziona, WP_Error altrimenti
     */
    public static function test_api_key( $api_key ) {
        if ( empty( $api_key ) ) {
            return new WP_Error( 'ipv_supadata_no_key', 'API key vuota.' );
        }

        // Usa un video molto corto per test
        $test_video = 'dQw4w9WgXcQ'; // Video popolare, sempre disponibile
        $test_url = 'https://www.youtube.com/watch?v=' . $test_video;

        $response = wp_remote_get(
            add_query_arg(
                [
                    'url'  => $test_url,
                    'mode' => 'native', // Solo native per test veloce
                    'text' => 'true',
                ],
                self::API_ENDPOINT
            ),
            [
                'headers' => [
                    'x-api-key' => $api_key,
                    'Accept'    => 'application/json',
                ],
                'timeout' => 30,
            ]
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );

        // 200, 202 o 206 sono tutti "funziona"
        if ( in_array( $code, [ 200, 202, 206 ], true ) ) {
            return true;
        }

        if ( $code === 402 ) {
            return new WP_Error( 'ipv_supadata_quota', 'API key valida ma crediti esauriti.' );
        }

        if ( $code === 401 || $code === 403 ) {
            return new WP_Error( 'ipv_supadata_invalid_key', 'API key non valida o non autorizzata.' );
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        $error_msg = $data['error'] ?? $data['message'] ?? 'Errore sconosciuto';

        return new WP_Error( 'ipv_supadata_error', 'Errore: ' . $error_msg );
    }

    /**
     * Ottiene le lingue disponibili per un video
     *
     * @param string $video_id YouTube Video ID
     * @return array|WP_Error Lista lingue o errore
     */
    public static function get_available_languages( $video_id ) {
        $api_keys = self::get_api_keys();

        if ( empty( $api_keys ) ) {
            return new WP_Error( 'ipv_supadata_no_key', 'API key non configurata.' );
        }

        $video_url = 'https://www.youtube.com/watch?v=' . $video_id;

        $response = wp_remote_get(
            add_query_arg(
                [
                    'url'  => $video_url,
                    'mode' => 'native',
                    'text' => 'true',
                ],
                self::API_ENDPOINT
            ),
            [
                'headers' => [
                    'x-api-key' => $api_keys[0],
                    'Accept'    => 'application/json',
                ],
                'timeout' => 30,
            ]
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        return $data['availableLangs'] ?? [];
    }
}
