<?php
/**
 * Maps Integration
 * Support for multiple map providers
 *
 * @package WP_Event_Calendar_Pro
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class WECP_Maps_Integration {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_maps_scripts'));
        add_shortcode('wecp_event_map', array($this, 'event_map_shortcode'));
    }

    /**
     * Get available map providers
     */
    public static function get_providers() {
        return array(
            'google' => array(
                'name' => __('Google Maps', 'wp-event-calendar-pro'),
                'requires_key' => true,
                'url' => 'https://maps.googleapis.com/maps/api/js',
            ),
            'openstreetmap' => array(
                'name' => __('OpenStreetMap (Leaflet)', 'wp-event-calendar-pro'),
                'requires_key' => false,
                'url' => 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
                'css' => 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
            ),
            'mapbox' => array(
                'name' => __('Mapbox', 'wp-event-calendar-pro'),
                'requires_key' => true,
                'url' => 'https://api.mapbox.com/mapbox-gl-js/v3.0.1/mapbox-gl.js',
                'css' => 'https://api.mapbox.com/mapbox-gl-js/v3.0.1/mapbox-gl.css',
            ),
            'here' => array(
                'name' => __('HERE Maps', 'wp-event-calendar-pro'),
                'requires_key' => true,
                'url' => 'https://js.api.here.com/v3/3.1/mapsjs-core.js',
            ),
            'bing' => array(
                'name' => __('Bing Maps', 'wp-event-calendar-pro'),
                'requires_key' => true,
                'url' => 'https://www.bing.com/api/maps/mapcontrol',
            ),
        );
    }

    /**
     * Get active map provider
     */
    public function get_active_provider() {
        return get_option('wecp_map_provider', 'openstreetmap');
    }

    /**
     * Enqueue map scripts
     */
    public function enqueue_maps_scripts() {
        $provider = $this->get_active_provider();
        $providers = self::get_providers();

        if (!isset($providers[$provider])) {
            return;
        }

        $config = $providers[$provider];

        // Enqueue CSS if available
        if (isset($config['css'])) {
            wp_enqueue_style(
                'wecp-map-' . $provider,
                $config['css'],
                array(),
                null
            );
        }

        // Build script URL
        $script_url = $config['url'];

        if ($config['requires_key']) {
            $api_key = get_option('wecp_' . $provider . '_api_key', '');

            if (empty($api_key)) {
                return; // Don't load if no API key
            }

            // Add API key to URL
            switch ($provider) {
                case 'google':
                    $script_url .= '?key=' . $api_key;
                    break;
                case 'mapbox':
                    $script_url .= '?access_token=' . $api_key;
                    break;
                case 'here':
                    $script_url .= '?apikey=' . $api_key;
                    break;
                case 'bing':
                    $script_url .= '?key=' . $api_key;
                    break;
            }
        }

        // Enqueue script
        wp_enqueue_script(
            'wecp-map-' . $provider,
            $script_url,
            array(),
            null,
            true
        );

        // Enqueue our map handler
        wp_enqueue_script(
            'wecp-maps',
            WECP_PLUGIN_URL . 'assets/js/maps.js',
            array('jquery', 'wecp-map-' . $provider),
            WECP_VERSION,
            true
        );

        // Localize script
        wp_localize_script('wecp-maps', 'wecpMaps', array(
            'provider' => $provider,
            'apiKey' => $config['requires_key'] ? get_option('wecp_' . $provider . '_api_key', '') : '',
        ));
    }

    /**
     * Event map shortcode
     * Usage: [wecp_event_map event_id="123"]
     */
    public function event_map_shortcode($atts) {
        $atts = shortcode_atts(array(
            'event_id' => get_the_ID(),
            'width' => '100%',
            'height' => '400px',
            'zoom' => 15,
        ), $atts);

        $event_id = intval($atts['event_id']);

        if (!$event_id) {
            return '';
        }

        // Get venue coordinates
        $lat = get_post_meta($event_id, '_wecp_venue_lat', true);
        $lng = get_post_meta($event_id, '_wecp_venue_lng', true);

        if (empty($lat) || empty($lng)) {
            return '<p>' . __('No location coordinates available', 'wp-event-calendar-pro') . '</p>';
        }

        $venue_name = get_post_meta($event_id, '_wecp_venue_name', true);
        $venue_address = get_post_meta($event_id, '_wecp_venue_address', true);

        ob_start();
        ?>
        <div class="wecp-event-map"
             data-lat="<?php echo esc_attr($lat); ?>"
             data-lng="<?php echo esc_attr($lng); ?>"
             data-zoom="<?php echo esc_attr($atts['zoom']); ?>"
             data-venue="<?php echo esc_attr($venue_name); ?>"
             data-address="<?php echo esc_attr($venue_address); ?>"
             style="width: <?php echo esc_attr($atts['width']); ?>; height: <?php echo esc_attr($atts['height']); ?>;">
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render map in event details
     */
    public function render_event_map($event_id, $args = array()) {
        $defaults = array(
            'width' => '100%',
            'height' => '400px',
            'zoom' => 15,
        );

        $args = wp_parse_args($args, $defaults);

        return $this->event_map_shortcode(array_merge($args, array('event_id' => $event_id)));
    }
}
