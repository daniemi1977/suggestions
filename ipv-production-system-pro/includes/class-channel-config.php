<?php
/**
 * Channel Configuration Manager
 *
 * Manages channel-specific settings for multi-site/multi-channel support
 */

if (!defined('ABSPATH')) {
    exit;
}

class IPV_Channel_Config {

    /**
     * Get all channel configuration
     */
    public static function get_config() {
        return [
            // Channel Identity
            'channel_name' => get_option('ipv_pro_channel_name', 'Il Punto di Vista'),
            'channel_owner' => get_option('ipv_pro_channel_owner', 'Adrian Fiorelli'),
            'channel_description' => get_option('ipv_pro_channel_description', 'Canale dedicato a spiritualità, esoterismo, misteri e geopolitica'),
            'channel_youtube_handle' => get_option('ipv_pro_channel_youtube_handle', '@ilpuntodivista'),

            // Channel Themes
            'channel_themes' => get_option('ipv_pro_channel_themes', [
                'Esoterismo – Tradizioni occulte e conoscenze nascoste',
                'Spiritualità – Ricerca interiore e crescita personale',
                'Misteri – Enigmi storici e archeologia alternativa',
                'Geopolitica – Analisi critica degli eventi mondiali',
                'Divulgazione alternativa – Informazione fuori dagli schemi'
            ]),

            // Sponsor Configuration
            'sponsor_enabled' => get_option('ipv_pro_sponsor_enabled', true),
            'sponsor_name' => get_option('ipv_pro_sponsor_name', 'Biovital – Progetto Italia'),
            'sponsor_description' => get_option('ipv_pro_sponsor_description', 'Biovital è un\'azienda italiana leader nella produzione di integratori naturali e biologici. Con oltre 30 anni di esperienza, Biovital si impegna nella ricerca e sviluppo di prodotti per il benessere e la salute, utilizzando esclusivamente ingredienti naturali di alta qualità.'),
            'sponsor_link' => get_option('ipv_pro_sponsor_link', 'https://biovital-italia.com/?bio=17'),
            'sponsor_cta' => get_option('ipv_pro_sponsor_cta', 'Sostieni il progetto'),

            // Donations
            'donations_enabled' => get_option('ipv_pro_donations_enabled', true),
            'donations_platform' => get_option('ipv_pro_donations_platform', 'PayPal'),
            'donations_link' => get_option('ipv_pro_donations_link', 'https://paypal.me/adrianfiorelli'),
            'donations_message' => get_option('ipv_pro_donations_message', 'Per sostenere il canale e i progetti de {{channel_name}} basta fare una donazione a questo link'),
            'donations_thanks' => get_option('ipv_pro_donations_thanks', 'Grazie in anticipo a tutti i pirati 🖤🏴‍☠️'),

            // Social Media
            'social_telegram' => get_option('ipv_pro_social_telegram', 'https://t.me/il_punto_divista'),
            'social_facebook' => get_option('ipv_pro_social_facebook', 'https://facebook.com/groups/4102938329737588'),
            'social_instagram' => get_option('ipv_pro_social_instagram', 'https://instagram.com/_ilpuntodivista._'),
            'social_twitter' => get_option('ipv_pro_social_twitter', ''),
            'social_tiktok' => get_option('ipv_pro_social_tiktok', ''),
            'social_linkedin' => get_option('ipv_pro_social_linkedin', ''),

            // Contact Information
            'contact_email' => get_option('ipv_pro_contact_email', 'ilpuntodivistaredazione@gmail.com'),
            'contact_website' => get_option('ipv_pro_contact_website', 'https://ilpuntodivistachannel.com'),
            'contact_phone' => get_option('ipv_pro_contact_phone', ''),

            // Additional Links
            'link_patreon' => get_option('ipv_pro_link_patreon', ''),
            'link_shop' => get_option('ipv_pro_link_shop', ''),
            'link_newsletter' => get_option('ipv_pro_link_newsletter', ''),
            'link_courses' => get_option('ipv_pro_link_courses', ''),

            // Content Style
            'content_tone' => get_option('ipv_pro_content_tone', 'Approfondito, rigoroso, senza sensazionalismo. Tono professionale ma accessibile.'),
            'content_language' => get_option('ipv_pro_content_language', 'it'),

            // Production Credits
            'production_credits' => get_option('ipv_pro_production_credits', 'Il Punto di Vista'),

            // Branding
            'branding_tagline' => get_option('ipv_pro_branding_tagline', ''),
            'branding_hashtags' => get_option('ipv_pro_branding_hashtags', ['#IlPuntoDiVista', '#AdrianFiorelli']),
        ];
    }

    /**
     * Replace variables in text with actual values
     */
    public static function parse_template($text, $additional_vars = []) {
        $config = self::get_config();

        $variables = array_merge($config, $additional_vars);

        foreach ($variables as $key => $value) {
            if (is_string($value)) {
                $text = str_replace('{{' . $key . '}}', $value, $text);
            } elseif (is_array($value)) {
                // For arrays, join with newline or bullet points
                $text = str_replace('{{' . $key . '}}', implode("\n", $value), $text);
            }
        }

        return $text;
    }

    /**
     * Get channel preset configurations
     */
    public static function get_presets() {
        return [
            'spirituality' => [
                'name' => 'Spiritualità & Esoterismo',
                'themes' => [
                    'Spiritualità – Ricerca interiore',
                    'Esoterismo – Tradizioni occulte',
                    'Misteri – Enigmi storici',
                    'Meditazione – Pratiche spirituali',
                    'Crescita personale'
                ],
                'tone' => 'Approfondito, rigoroso, senza sensazionalismo. Tono professionale ma accessibile.'
            ],
            'gaming' => [
                'name' => 'Gaming & Esports',
                'themes' => [
                    'Gaming – Recensioni e gameplay',
                    'Esports – Tornei e competizioni',
                    'Guide e tutorial',
                    'News del settore',
                    'Hardware e setup'
                ],
                'tone' => 'Coinvolgente, energico, tecnico quando serve. Linguaggio gaming-friendly.'
            ],
            'tech' => [
                'name' => 'Tecnologia & Innovazione',
                'themes' => [
                    'Tecnologia – Novità e recensioni',
                    'Innovazione – Tendenze future',
                    'Software e app',
                    'Hardware e gadget',
                    'Tutorial tecnici'
                ],
                'tone' => 'Tecnico ma comprensibile, oggettivo, basato su dati e test.'
            ],
            'education' => [
                'name' => 'Educazione & Tutorial',
                'themes' => [
                    'Tutorial pratici',
                    'Corsi e formazione',
                    'Approfondimenti tematici',
                    'Risorse didattiche',
                    'Tips & tricks'
                ],
                'tone' => 'Chiaro, didattico, passo-passo. Linguaggio semplice e accessibile.'
            ],
            'food' => [
                'name' => 'Food & Cucina',
                'themes' => [
                    'Ricette tradizionali',
                    'Cucina moderna',
                    'Tecniche culinarie',
                    'Ingredienti e prodotti',
                    'Recensioni ristoranti'
                ],
                'tone' => 'Appetitoso, descrittivo, appassionato. Dettagli pratici e consigli.'
            ],
            'lifestyle' => [
                'name' => 'Lifestyle & Vlog',
                'themes' => [
                    'Lifestyle quotidiano',
                    'Travel e viaggi',
                    'Moda e style',
                    'Wellness e benessere',
                    'Esperienze personali'
                ],
                'tone' => 'Personale, autentico, conversazionale. Storytelling coinvolgente.'
            ]
        ];
    }

    /**
     * Apply preset to current configuration
     */
    public static function apply_preset($preset_id) {
        $presets = self::get_presets();

        if (!isset($presets[$preset_id])) {
            return false;
        }

        $preset = $presets[$preset_id];

        update_option('ipv_pro_channel_themes', $preset['themes']);
        update_option('ipv_pro_content_tone', $preset['tone']);

        return true;
    }

    /**
     * Validate configuration
     */
    public static function validate_config() {
        $errors = [];
        $config = self::get_config();

        // Check required fields
        if (empty($config['channel_name'])) {
            $errors[] = 'Il nome del canale è obbligatorio';
        }

        if (empty($config['channel_owner'])) {
            $errors[] = 'Il proprietario del canale è obbligatorio';
        }

        // Validate URLs
        $url_fields = ['sponsor_link', 'donations_link', 'contact_website'];
        foreach ($url_fields as $field) {
            if (!empty($config[$field]) && !filter_var($config[$field], FILTER_VALIDATE_URL)) {
                $errors[] = "URL non valido: {$field}";
            }
        }

        // Validate email
        if (!empty($config['contact_email']) && !filter_var($config['contact_email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email di contatto non valida';
        }

        return empty($errors) ? true : $errors;
    }

    /**
     * Export configuration as JSON
     */
    public static function export_config() {
        return json_encode(self::get_config(), JSON_PRETTY_PRINT);
    }

    /**
     * Import configuration from JSON
     */
    public static function import_config($json) {
        $config = json_decode($json, true);

        if (!$config) {
            return new WP_Error('invalid_json', 'JSON non valido');
        }

        foreach ($config as $key => $value) {
            update_option('ipv_pro_' . $key, $value);
        }

        return true;
    }
}
