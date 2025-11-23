<?php
/**
 * IPV Production System Pro - YouTube Data API v3 Integration
 *
 * Gestione completa dei dati YouTube:
 * - Snippet (title, description, tags, categoryId, thumbnails)
 * - ContentDetails (duration, definition, caption)
 * - Statistics (viewCount, likeCount, commentCount)
 * - Bulk import da canale
 *
 * @package IPV_Production_System_Pro
 * @version 4.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_YouTube_API {

    /**
     * Endpoint base YouTube Data API v3
     */
    const API_ENDPOINT = 'https://www.googleapis.com/youtube/v3';

    /**
     * Cache duration in seconds (1 hour)
     */
    const CACHE_DURATION = 3600;

    /**
     * Ottiene i dati completi di un video da YouTube
     *
     * @param string $video_id YouTube Video ID
     * @return array|WP_Error Dati video o errore
     */
    public static function get_video_data( $video_id ) {
        $api_key = get_option( 'ipv_youtube_api_key', '' );

        if ( empty( $api_key ) ) {
            return new WP_Error(
                'ipv_youtube_no_key',
                'YouTube Data API Key non configurata. Vai in Impostazioni per configurarla.'
            );
        }

        // Check cache
        $cache_key = 'ipv_yt_video_' . $video_id;
        $cached = get_transient( $cache_key );
        if ( false !== $cached ) {
            return $cached;
        }

        $url = add_query_arg(
            [
                'part' => 'snippet,contentDetails,statistics,status',
                'id'   => $video_id,
                'key'  => $api_key,
            ],
            self::API_ENDPOINT . '/videos'
        );

        $response = wp_remote_get( $url, [
            'timeout' => 30,
            'headers' => [
                'Accept' => 'application/json',
            ],
        ] );

        if ( is_wp_error( $response ) ) {
            IPV_Prod_Logger::log( 'Errore YouTube API', [ 'error' => $response->get_error_message() ] );
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( $code === 403 ) {
            return new WP_Error( 'ipv_youtube_quota', 'Quota YouTube API esaurita. Riprova domani.' );
        }

        if ( $code === 400 ) {
            return new WP_Error( 'ipv_youtube_bad_request', 'Richiesta non valida: ' . ( $data['error']['message'] ?? 'Errore sconosciuto' ) );
        }

        if ( $code < 200 || $code >= 300 ) {
            return new WP_Error( 'ipv_youtube_http_error', 'Errore HTTP YouTube: ' . $code );
        }

        if ( empty( $data['items'][0] ) ) {
            return new WP_Error( 'ipv_youtube_not_found', 'Video non trovato o privato.' );
        }

        $item = $data['items'][0];
        $result = self::parse_video_item( $item );

        // Cache result
        set_transient( $cache_key, $result, self::CACHE_DURATION );

        return $result;
    }

    /**
     * Ottiene la lista degli ultimi video di un canale
     *
     * @param string $channel_id YouTube Channel ID
     * @param int    $max_results Numero massimo di video (max 50)
     * @param string $page_token Token per paginazione
     * @return array|WP_Error Lista video o errore
     */
    public static function get_channel_videos( $channel_id, $max_results = 50, $page_token = '' ) {
        $api_key = get_option( 'ipv_youtube_api_key', '' );

        if ( empty( $api_key ) ) {
            return new WP_Error(
                'ipv_youtube_no_key',
                'YouTube Data API Key non configurata.'
            );
        }

        // Prima otteniamo l'uploads playlist ID
        $uploads_playlist = self::get_uploads_playlist_id( $channel_id );
        if ( is_wp_error( $uploads_playlist ) ) {
            return $uploads_playlist;
        }

        // Poi otteniamo i video dalla playlist
        return self::get_playlist_videos( $uploads_playlist, $max_results, $page_token );
    }

    /**
     * Ottiene l'ID della playlist "uploads" di un canale
     *
     * @param string $channel_id YouTube Channel ID
     * @return string|WP_Error Playlist ID o errore
     */
    public static function get_uploads_playlist_id( $channel_id ) {
        $api_key = get_option( 'ipv_youtube_api_key', '' );

        $cache_key = 'ipv_yt_uploads_' . $channel_id;
        $cached = get_transient( $cache_key );
        if ( false !== $cached ) {
            return $cached;
        }

        $url = add_query_arg(
            [
                'part' => 'contentDetails',
                'id'   => $channel_id,
                'key'  => $api_key,
            ],
            self::API_ENDPOINT . '/channels'
        );

        $response = wp_remote_get( $url, [ 'timeout' => 30 ] );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( empty( $data['items'][0]['contentDetails']['relatedPlaylists']['uploads'] ) ) {
            return new WP_Error( 'ipv_youtube_no_channel', 'Canale non trovato o senza video.' );
        }

        $playlist_id = $data['items'][0]['contentDetails']['relatedPlaylists']['uploads'];
        set_transient( $cache_key, $playlist_id, DAY_IN_SECONDS );

        return $playlist_id;
    }

    /**
     * Ottiene i video da una playlist
     *
     * @param string $playlist_id YouTube Playlist ID
     * @param int    $max_results Numero massimo di video
     * @param string $page_token Token per paginazione
     * @return array|WP_Error Lista video o errore
     */
    public static function get_playlist_videos( $playlist_id, $max_results = 50, $page_token = '' ) {
        $api_key = get_option( 'ipv_youtube_api_key', '' );

        $args = [
            'part'       => 'snippet,contentDetails',
            'playlistId' => $playlist_id,
            'maxResults' => min( $max_results, 50 ),
            'key'        => $api_key,
        ];

        if ( ! empty( $page_token ) ) {
            $args['pageToken'] = $page_token;
        }

        $url = add_query_arg( $args, self::API_ENDPOINT . '/playlistItems' );

        $response = wp_remote_get( $url, [ 'timeout' => 30 ] );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code === 403 ) {
            return new WP_Error( 'ipv_youtube_quota', 'Quota YouTube API esaurita.' );
        }

        if ( $code < 200 || $code >= 300 ) {
            return new WP_Error( 'ipv_youtube_http_error', 'Errore HTTP: ' . $code );
        }

        $videos = [];
        $video_ids = [];

        foreach ( $data['items'] as $item ) {
            $video_ids[] = $item['contentDetails']['videoId'];
        }

        // Ottieni dati completi per tutti i video in una sola chiamata
        if ( ! empty( $video_ids ) ) {
            $videos = self::get_multiple_videos( $video_ids );
        }

        return [
            'videos'         => $videos,
            'next_page_token' => $data['nextPageToken'] ?? '',
            'prev_page_token' => $data['prevPageToken'] ?? '',
            'total_results'   => $data['pageInfo']['totalResults'] ?? 0,
        ];
    }

    /**
     * Ottiene i dati di più video in una sola chiamata
     *
     * @param array $video_ids Array di Video ID
     * @return array Lista dati video
     */
    public static function get_multiple_videos( $video_ids ) {
        $api_key = get_option( 'ipv_youtube_api_key', '' );

        if ( empty( $api_key ) || empty( $video_ids ) ) {
            return [];
        }

        // YouTube API permette max 50 video per chiamata
        $chunks = array_chunk( $video_ids, 50 );
        $all_videos = [];

        foreach ( $chunks as $chunk ) {
            $url = add_query_arg(
                [
                    'part' => 'snippet,contentDetails,statistics,status',
                    'id'   => implode( ',', $chunk ),
                    'key'  => $api_key,
                ],
                self::API_ENDPOINT . '/videos'
            );

            $response = wp_remote_get( $url, [ 'timeout' => 30 ] );

            if ( is_wp_error( $response ) ) {
                continue;
            }

            $data = json_decode( wp_remote_retrieve_body( $response ), true );

            if ( ! empty( $data['items'] ) ) {
                foreach ( $data['items'] as $item ) {
                    $all_videos[] = self::parse_video_item( $item );
                }
            }
        }

        return $all_videos;
    }

    /**
     * Parsifica un item video dalla risposta API
     *
     * @param array $item Item dalla risposta API
     * @return array Dati video strutturati
     */
    protected static function parse_video_item( $item ) {
        $snippet = $item['snippet'] ?? [];
        $content_details = $item['contentDetails'] ?? [];
        $statistics = $item['statistics'] ?? [];
        $status = $item['status'] ?? [];

        // Parse ISO 8601 duration
        $duration_seconds = self::parse_duration( $content_details['duration'] ?? 'PT0S' );

        // Get best thumbnail
        $thumbnails = $snippet['thumbnails'] ?? [];
        $thumbnail_url = '';
        $thumbnail_resolutions = [];

        foreach ( [ 'maxres', 'standard', 'high', 'medium', 'default' ] as $res ) {
            if ( ! empty( $thumbnails[ $res ]['url'] ) ) {
                if ( empty( $thumbnail_url ) ) {
                    $thumbnail_url = $thumbnails[ $res ]['url'];
                }
                $thumbnail_resolutions[ $res ] = $thumbnails[ $res ]['url'];
            }
        }

        return [
            'video_id'            => $item['id'],
            'title'               => $snippet['title'] ?? '',
            'description'         => $snippet['description'] ?? '',
            'published_at'        => $snippet['publishedAt'] ?? '',
            'channel_id'          => $snippet['channelId'] ?? '',
            'channel_title'       => $snippet['channelTitle'] ?? '',
            'tags'                => $snippet['tags'] ?? [],
            'category_id'         => $snippet['categoryId'] ?? '',
            'default_language'    => $snippet['defaultLanguage'] ?? '',
            'default_audio_language' => $snippet['defaultAudioLanguage'] ?? '',
            'thumbnail_url'       => $thumbnail_url,
            'thumbnail_resolutions' => $thumbnail_resolutions,
            'duration'            => $content_details['duration'] ?? '',
            'duration_seconds'    => $duration_seconds,
            'duration_formatted'  => self::format_duration( $duration_seconds ),
            'definition'          => $content_details['definition'] ?? '',
            'caption'             => $content_details['caption'] ?? 'false',
            'licensed_content'    => $content_details['licensedContent'] ?? false,
            'view_count'          => intval( $statistics['viewCount'] ?? 0 ),
            'like_count'          => intval( $statistics['likeCount'] ?? 0 ),
            'comment_count'       => intval( $statistics['commentCount'] ?? 0 ),
            'privacy_status'      => $status['privacyStatus'] ?? '',
            'embeddable'          => $status['embeddable'] ?? true,
            'made_for_kids'       => $status['madeForKids'] ?? false,
        ];
    }

    /**
     * Parsifica durata ISO 8601 in secondi
     *
     * @param string $duration Durata ISO 8601 (es. PT1H30M45S)
     * @return int Durata in secondi
     */
    public static function parse_duration( $duration ) {
        $pattern = '/PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?/';
        preg_match( $pattern, $duration, $matches );

        $hours = intval( $matches[1] ?? 0 );
        $minutes = intval( $matches[2] ?? 0 );
        $seconds = intval( $matches[3] ?? 0 );

        return ( $hours * 3600 ) + ( $minutes * 60 ) + $seconds;
    }

    /**
     * Formatta durata in formato leggibile
     *
     * @param int $seconds Durata in secondi
     * @return string Durata formattata (es. "1:30:45" o "30:45")
     */
    public static function format_duration( $seconds ) {
        $hours = floor( $seconds / 3600 );
        $minutes = floor( ( $seconds % 3600 ) / 60 );
        $secs = $seconds % 60;

        if ( $hours > 0 ) {
            return sprintf( '%d:%02d:%02d', $hours, $minutes, $secs );
        }

        return sprintf( '%d:%02d', $minutes, $secs );
    }

    /**
     * Cerca video per query
     *
     * @param string $query Query di ricerca
     * @param string $channel_id Opzionale: limita al canale
     * @param int    $max_results Numero massimo risultati
     * @return array|WP_Error Risultati ricerca o errore
     */
    public static function search_videos( $query, $channel_id = '', $max_results = 25 ) {
        $api_key = get_option( 'ipv_youtube_api_key', '' );

        if ( empty( $api_key ) ) {
            return new WP_Error( 'ipv_youtube_no_key', 'YouTube Data API Key non configurata.' );
        }

        $args = [
            'part'       => 'snippet',
            'type'       => 'video',
            'q'          => $query,
            'maxResults' => min( $max_results, 50 ),
            'order'      => 'date',
            'key'        => $api_key,
        ];

        if ( ! empty( $channel_id ) ) {
            $args['channelId'] = $channel_id;
        }

        $url = add_query_arg( $args, self::API_ENDPOINT . '/search' );

        $response = wp_remote_get( $url, [ 'timeout' => 30 ] );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( empty( $data['items'] ) ) {
            return [];
        }

        // Estrai video IDs e ottieni dati completi
        $video_ids = array_map( function( $item ) {
            return $item['id']['videoId'] ?? '';
        }, $data['items'] );

        $video_ids = array_filter( $video_ids );

        return self::get_multiple_videos( $video_ids );
    }

    /**
     * Verifica se un video esiste ed è accessibile
     *
     * @param string $video_id YouTube Video ID
     * @return bool True se il video esiste e è accessibile
     */
    public static function video_exists( $video_id ) {
        $data = self::get_video_data( $video_id );
        return ! is_wp_error( $data ) && ! empty( $data['video_id'] );
    }

    /**
     * Estrae l'ID del canale da vari formati URL
     *
     * @param string $url URL del canale YouTube
     * @return string|WP_Error Channel ID o errore
     */
    public static function extract_channel_id( $url ) {
        // Format: /channel/UC...
        if ( preg_match( '/youtube\.com\/channel\/([^\/\?]+)/', $url, $matches ) ) {
            return $matches[1];
        }

        // Format: /c/ChannelName or /@username - richiede API lookup
        if ( preg_match( '/youtube\.com\/(@[\w-]+|c\/[\w-]+)/', $url, $matches ) ) {
            return self::lookup_channel_id( $matches[1] );
        }

        // Format: /user/username
        if ( preg_match( '/youtube\.com\/user\/([\w-]+)/', $url, $matches ) ) {
            return self::lookup_channel_id_by_username( $matches[1] );
        }

        // Potrebbe essere già un Channel ID
        if ( preg_match( '/^UC[\w-]{22}$/', $url ) ) {
            return $url;
        }

        return new WP_Error( 'ipv_youtube_invalid_url', 'URL canale YouTube non valido.' );
    }

    /**
     * Cerca l'ID del canale per handle (@username)
     *
     * @param string $handle Handle del canale (@username o c/name)
     * @return string|WP_Error Channel ID o errore
     */
    protected static function lookup_channel_id( $handle ) {
        $api_key = get_option( 'ipv_youtube_api_key', '' );

        if ( empty( $api_key ) ) {
            return new WP_Error( 'ipv_youtube_no_key', 'YouTube API Key necessaria per risolvere handle.' );
        }

        // Per handle @username
        $handle = ltrim( $handle, '@' );
        $handle = str_replace( 'c/', '', $handle );

        $url = add_query_arg(
            [
                'part'       => 'id',
                'forHandle'  => $handle,
                'key'        => $api_key,
            ],
            self::API_ENDPOINT . '/channels'
        );

        $response = wp_remote_get( $url, [ 'timeout' => 30 ] );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( ! empty( $data['items'][0]['id'] ) ) {
            return $data['items'][0]['id'];
        }

        return new WP_Error( 'ipv_youtube_channel_not_found', 'Canale non trovato.' );
    }

    /**
     * Cerca l'ID del canale per username legacy
     *
     * @param string $username Username del canale
     * @return string|WP_Error Channel ID o errore
     */
    protected static function lookup_channel_id_by_username( $username ) {
        $api_key = get_option( 'ipv_youtube_api_key', '' );

        $url = add_query_arg(
            [
                'part'        => 'id',
                'forUsername' => $username,
                'key'         => $api_key,
            ],
            self::API_ENDPOINT . '/channels'
        );

        $response = wp_remote_get( $url, [ 'timeout' => 30 ] );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( ! empty( $data['items'][0]['id'] ) ) {
            return $data['items'][0]['id'];
        }

        return new WP_Error( 'ipv_youtube_user_not_found', 'Utente non trovato.' );
    }

    /**
     * Ottiene informazioni sul canale
     *
     * @param string $channel_id YouTube Channel ID
     * @return array|WP_Error Dati canale o errore
     */
    public static function get_channel_info( $channel_id ) {
        $api_key = get_option( 'ipv_youtube_api_key', '' );

        if ( empty( $api_key ) ) {
            return new WP_Error( 'ipv_youtube_no_key', 'YouTube API Key non configurata.' );
        }

        $url = add_query_arg(
            [
                'part' => 'snippet,statistics,brandingSettings',
                'id'   => $channel_id,
                'key'  => $api_key,
            ],
            self::API_ENDPOINT . '/channels'
        );

        $response = wp_remote_get( $url, [ 'timeout' => 30 ] );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( empty( $data['items'][0] ) ) {
            return new WP_Error( 'ipv_youtube_channel_not_found', 'Canale non trovato.' );
        }

        $item = $data['items'][0];
        $snippet = $item['snippet'] ?? [];
        $statistics = $item['statistics'] ?? [];

        return [
            'channel_id'        => $item['id'],
            'title'             => $snippet['title'] ?? '',
            'description'       => $snippet['description'] ?? '',
            'custom_url'        => $snippet['customUrl'] ?? '',
            'thumbnail_url'     => $snippet['thumbnails']['high']['url'] ?? '',
            'subscriber_count'  => intval( $statistics['subscriberCount'] ?? 0 ),
            'video_count'       => intval( $statistics['videoCount'] ?? 0 ),
            'view_count'        => intval( $statistics['viewCount'] ?? 0 ),
        ];
    }

    /**
     * Salva i dati YouTube nei meta del post
     *
     * @param int   $post_id WordPress Post ID
     * @param array $video_data Dati video da YouTube API
     * @return bool True se salvato con successo
     */
    public static function save_video_meta( $post_id, $video_data ) {
        if ( empty( $post_id ) || empty( $video_data ) ) {
            return false;
        }

        // Meta principali
        update_post_meta( $post_id, '_ipv_video_id', $video_data['video_id'] );
        update_post_meta( $post_id, '_ipv_youtube_url', 'https://www.youtube.com/watch?v=' . $video_data['video_id'] );

        // Snippet
        update_post_meta( $post_id, '_ipv_yt_title', $video_data['title'] );
        update_post_meta( $post_id, '_ipv_yt_description', $video_data['description'] );
        update_post_meta( $post_id, '_ipv_yt_published_at', $video_data['published_at'] );
        update_post_meta( $post_id, '_ipv_yt_channel_id', $video_data['channel_id'] );
        update_post_meta( $post_id, '_ipv_yt_channel_title', $video_data['channel_title'] );
        update_post_meta( $post_id, '_ipv_yt_tags', $video_data['tags'] );
        update_post_meta( $post_id, '_ipv_yt_category_id', $video_data['category_id'] );
        update_post_meta( $post_id, '_ipv_yt_thumbnail_url', $video_data['thumbnail_url'] );
        update_post_meta( $post_id, '_ipv_yt_thumbnails', $video_data['thumbnail_resolutions'] );

        // Content Details
        update_post_meta( $post_id, '_ipv_yt_duration', $video_data['duration'] );
        update_post_meta( $post_id, '_ipv_yt_duration_seconds', $video_data['duration_seconds'] );
        update_post_meta( $post_id, '_ipv_yt_duration_formatted', $video_data['duration_formatted'] );
        update_post_meta( $post_id, '_ipv_yt_definition', $video_data['definition'] );
        update_post_meta( $post_id, '_ipv_yt_caption', $video_data['caption'] );

        // Statistics
        update_post_meta( $post_id, '_ipv_yt_view_count', $video_data['view_count'] );
        update_post_meta( $post_id, '_ipv_yt_like_count', $video_data['like_count'] );
        update_post_meta( $post_id, '_ipv_yt_comment_count', $video_data['comment_count'] );

        // Status
        update_post_meta( $post_id, '_ipv_yt_privacy_status', $video_data['privacy_status'] );
        update_post_meta( $post_id, '_ipv_yt_embeddable', $video_data['embeddable'] );

        // Timestamp ultimo aggiornamento dati YouTube
        update_post_meta( $post_id, '_ipv_yt_data_updated', current_time( 'mysql' ) );

        IPV_Prod_Logger::log( 'Dati YouTube salvati', [ 'post_id' => $post_id, 'video_id' => $video_data['video_id'] ] );

        return true;
    }
}
