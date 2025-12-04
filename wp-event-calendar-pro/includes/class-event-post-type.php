<?php
/**
 * Event Custom Post Type
 *
 * @package WP_Event_Calendar_Pro
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class WECP_Event_Post_Type {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('init', array($this, 'register_taxonomies'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post_wecp_event', array($this, 'save_event_meta'));
        add_filter('manage_wecp_event_posts_columns', array($this, 'add_admin_columns'));
        add_action('manage_wecp_event_posts_custom_column', array($this, 'display_admin_columns'), 10, 2);
    }

    /**
     * Register Event Custom Post Type
     */
    public function register_post_type() {
        $labels = array(
            'name' => __('Events Calendar', 'wp-event-calendar-pro'),
            'singular_name' => __('Event', 'wp-event-calendar-pro'),
            'menu_name' => __('Events Calendar', 'wp-event-calendar-pro'),
            'add_new' => __('Add New Event', 'wp-event-calendar-pro'),
            'add_new_item' => __('Add New Event', 'wp-event-calendar-pro'),
            'edit_item' => __('Edit Event', 'wp-event-calendar-pro'),
            'new_item' => __('New Event', 'wp-event-calendar-pro'),
            'view_item' => __('View Event', 'wp-event-calendar-pro'),
            'search_items' => __('Search Events', 'wp-event-calendar-pro'),
            'not_found' => __('No events found', 'wp-event-calendar-pro'),
            'not_found_in_trash' => __('No events found in trash', 'wp-event-calendar-pro'),
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'has_archive' => true,
            'menu_icon' => 'dashicons-calendar-alt',
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'),
            'show_in_rest' => true,
            'rewrite' => array('slug' => 'events'),
            'capability_type' => 'post',
            'menu_position' => 5,
        );

        register_post_type('wecp_event', $args);
    }

    /**
     * Register Event Taxonomies
     */
    public function register_taxonomies() {
        // Event Categories
        $category_labels = array(
            'name' => __('Event Categories', 'wp-event-calendar-pro'),
            'singular_name' => __('Event Category', 'wp-event-calendar-pro'),
            'search_items' => __('Search Categories', 'wp-event-calendar-pro'),
            'all_items' => __('All Categories', 'wp-event-calendar-pro'),
            'edit_item' => __('Edit Category', 'wp-event-calendar-pro'),
            'update_item' => __('Update Category', 'wp-event-calendar-pro'),
            'add_new_item' => __('Add New Category', 'wp-event-calendar-pro'),
            'new_item_name' => __('New Category Name', 'wp-event-calendar-pro'),
        );

        register_taxonomy('wecp_event_category', 'wecp_event', array(
            'labels' => $category_labels,
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'event-category'),
            'show_in_rest' => true,
        ));

        // Event Tags
        $tag_labels = array(
            'name' => __('Event Tags', 'wp-event-calendar-pro'),
            'singular_name' => __('Event Tag', 'wp-event-calendar-pro'),
            'search_items' => __('Search Tags', 'wp-event-calendar-pro'),
            'all_items' => __('All Tags', 'wp-event-calendar-pro'),
            'edit_item' => __('Edit Tag', 'wp-event-calendar-pro'),
            'update_item' => __('Update Tag', 'wp-event-calendar-pro'),
            'add_new_item' => __('Add New Tag', 'wp-event-calendar-pro'),
            'new_item_name' => __('New Tag Name', 'wp-event-calendar-pro'),
        );

        register_taxonomy('wecp_event_tag', 'wecp_event', array(
            'labels' => $tag_labels,
            'hierarchical' => false,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'event-tag'),
            'show_in_rest' => true,
        ));
    }

    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'wecp_event_details',
            __('Event Details', 'wp-event-calendar-pro'),
            array($this, 'render_event_details_meta_box'),
            'wecp_event',
            'normal',
            'high'
        );

        // Add recurring events meta box
        WECP_Recurring_Events::get_instance()->add_recurrence_meta_box();

        add_meta_box(
            'wecp_event_location',
            __('Event Location', 'wp-event-calendar-pro'),
            array($this, 'render_event_location_meta_box'),
            'wecp_event',
            'normal',
            'default'
        );

        add_meta_box(
            'wecp_event_tickets',
            __('Event Tickets & Booking', 'wp-event-calendar-pro'),
            array($this, 'render_event_tickets_meta_box'),
            'wecp_event',
            'normal',
            'default'
        );
    }

    /**
     * Render event details meta box
     */
    public function render_event_details_meta_box($post) {
        wp_nonce_field('wecp_event_meta', 'wecp_event_meta_nonce');

        $start_date = get_post_meta($post->ID, '_wecp_start_date', true);
        $end_date = get_post_meta($post->ID, '_wecp_end_date', true);
        $start_time = get_post_meta($post->ID, '_wecp_start_time', true);
        $end_time = get_post_meta($post->ID, '_wecp_end_time', true);
        $all_day = get_post_meta($post->ID, '_wecp_all_day', true);
        $event_color = get_post_meta($post->ID, '_wecp_event_color', true) ?: '#3498db';
        ?>
        <div class="wecp-meta-box">
            <p>
                <label for="wecp_start_date"><?php _e('Start Date:', 'wp-event-calendar-pro'); ?></label>
                <input type="date" id="wecp_start_date" name="wecp_start_date" value="<?php echo esc_attr($start_date); ?>" required>
            </p>
            <p>
                <label for="wecp_start_time"><?php _e('Start Time:', 'wp-event-calendar-pro'); ?></label>
                <input type="time" id="wecp_start_time" name="wecp_start_time" value="<?php echo esc_attr($start_time); ?>" <?php echo $all_day ? 'disabled' : ''; ?>>
            </p>
            <p>
                <label for="wecp_end_date"><?php _e('End Date:', 'wp-event-calendar-pro'); ?></label>
                <input type="date" id="wecp_end_date" name="wecp_end_date" value="<?php echo esc_attr($end_date); ?>">
            </p>
            <p>
                <label for="wecp_end_time"><?php _e('End Time:', 'wp-event-calendar-pro'); ?></label>
                <input type="time" id="wecp_end_time" name="wecp_end_time" value="<?php echo esc_attr($end_time); ?>" <?php echo $all_day ? 'disabled' : ''; ?>>
            </p>
            <p>
                <label>
                    <input type="checkbox" id="wecp_all_day" name="wecp_all_day" value="1" <?php checked($all_day, '1'); ?>>
                    <?php _e('All Day Event', 'wp-event-calendar-pro'); ?>
                </label>
            </p>
            <p>
                <label for="wecp_event_color"><?php _e('Event Color:', 'wp-event-calendar-pro'); ?></label>
                <input type="text" id="wecp_event_color" name="wecp_event_color" value="<?php echo esc_attr($event_color); ?>" class="wecp-color-picker">
            </p>
        </div>
        <?php
    }

    /**
     * Render event location meta box
     */
    public function render_event_location_meta_box($post) {
        $venue_name = get_post_meta($post->ID, '_wecp_venue_name', true);
        $venue_address = get_post_meta($post->ID, '_wecp_venue_address', true);
        $venue_city = get_post_meta($post->ID, '_wecp_venue_city', true);
        $venue_state = get_post_meta($post->ID, '_wecp_venue_state', true);
        $venue_zip = get_post_meta($post->ID, '_wecp_venue_zip', true);
        $venue_country = get_post_meta($post->ID, '_wecp_venue_country', true);
        $venue_lat = get_post_meta($post->ID, '_wecp_venue_lat', true);
        $venue_lng = get_post_meta($post->ID, '_wecp_venue_lng', true);
        ?>
        <div class="wecp-meta-box">
            <p>
                <label for="wecp_venue_name"><?php _e('Venue Name:', 'wp-event-calendar-pro'); ?></label>
                <input type="text" id="wecp_venue_name" name="wecp_venue_name" value="<?php echo esc_attr($venue_name); ?>" style="width: 100%;">
            </p>
            <p>
                <label for="wecp_venue_address"><?php _e('Address:', 'wp-event-calendar-pro'); ?></label>
                <input type="text" id="wecp_venue_address" name="wecp_venue_address" value="<?php echo esc_attr($venue_address); ?>" style="width: 100%;">
            </p>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <p>
                    <label for="wecp_venue_city"><?php _e('City:', 'wp-event-calendar-pro'); ?></label>
                    <input type="text" id="wecp_venue_city" name="wecp_venue_city" value="<?php echo esc_attr($venue_city); ?>" style="width: 100%;">
                </p>
                <p>
                    <label for="wecp_venue_state"><?php _e('State/Province:', 'wp-event-calendar-pro'); ?></label>
                    <input type="text" id="wecp_venue_state" name="wecp_venue_state" value="<?php echo esc_attr($venue_state); ?>" style="width: 100%;">
                </p>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <p>
                    <label for="wecp_venue_zip"><?php _e('ZIP Code:', 'wp-event-calendar-pro'); ?></label>
                    <input type="text" id="wecp_venue_zip" name="wecp_venue_zip" value="<?php echo esc_attr($venue_zip); ?>" style="width: 100%;">
                </p>
                <p>
                    <label for="wecp_venue_country"><?php _e('Country:', 'wp-event-calendar-pro'); ?></label>
                    <input type="text" id="wecp_venue_country" name="wecp_venue_country" value="<?php echo esc_attr($venue_country); ?>" style="width: 100%;">
                </p>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <p>
                    <label for="wecp_venue_lat"><?php _e('Latitude:', 'wp-event-calendar-pro'); ?></label>
                    <input type="text" id="wecp_venue_lat" name="wecp_venue_lat" value="<?php echo esc_attr($venue_lat); ?>" style="width: 100%;">
                </p>
                <p>
                    <label for="wecp_venue_lng"><?php _e('Longitude:', 'wp-event-calendar-pro'); ?></label>
                    <input type="text" id="wecp_venue_lng" name="wecp_venue_lng" value="<?php echo esc_attr($venue_lng); ?>" style="width: 100%;">
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Render event tickets meta box
     */
    public function render_event_tickets_meta_box($post) {
        $enable_booking = get_post_meta($post->ID, '_wecp_enable_booking', true);
        $max_attendees = get_post_meta($post->ID, '_wecp_max_attendees', true);
        $ticket_price = get_post_meta($post->ID, '_wecp_ticket_price', true);
        $wc_product_id = get_post_meta($post->ID, '_wecp_wc_product_id', true);
        ?>
        <div class="wecp-meta-box">
            <p>
                <label>
                    <input type="checkbox" id="wecp_enable_booking" name="wecp_enable_booking" value="1" <?php checked($enable_booking, '1'); ?>>
                    <?php _e('Enable Booking for this Event', 'wp-event-calendar-pro'); ?>
                </label>
            </p>
            <p>
                <label for="wecp_max_attendees"><?php _e('Max Attendees:', 'wp-event-calendar-pro'); ?></label>
                <input type="number" id="wecp_max_attendees" name="wecp_max_attendees" value="<?php echo esc_attr($max_attendees); ?>" min="0" step="1">
            </p>
            <p>
                <label for="wecp_ticket_price"><?php _e('Ticket Price:', 'wp-event-calendar-pro'); ?></label>
                <input type="number" id="wecp_ticket_price" name="wecp_ticket_price" value="<?php echo esc_attr($ticket_price); ?>" min="0" step="0.01">
            </p>
            <?php if ($wc_product_id): ?>
            <p>
                <strong><?php _e('WooCommerce Product ID:', 'wp-event-calendar-pro'); ?></strong> <?php echo esc_html($wc_product_id); ?>
                <br>
                <a href="<?php echo admin_url('post.php?post=' . $wc_product_id . '&action=edit'); ?>" target="_blank">
                    <?php _e('Edit Product', 'wp-event-calendar-pro'); ?>
                </a>
            </p>
            <?php endif; ?>
            <p class="description">
                <?php _e('When booking is enabled, a WooCommerce product will be automatically created/updated for this event.', 'wp-event-calendar-pro'); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Save event meta
     */
    public function save_event_meta($post_id) {
        // Check nonce
        if (!isset($_POST['wecp_event_meta_nonce']) || !wp_verify_nonce($_POST['wecp_event_meta_nonce'], 'wecp_event_meta')) {
            return;
        }

        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save event details
        $fields = array(
            'wecp_start_date' => '_wecp_start_date',
            'wecp_end_date' => '_wecp_end_date',
            'wecp_start_time' => '_wecp_start_time',
            'wecp_end_time' => '_wecp_end_time',
            'wecp_all_day' => '_wecp_all_day',
            'wecp_event_color' => '_wecp_event_color',
            'wecp_venue_name' => '_wecp_venue_name',
            'wecp_venue_address' => '_wecp_venue_address',
            'wecp_venue_city' => '_wecp_venue_city',
            'wecp_venue_state' => '_wecp_venue_state',
            'wecp_venue_zip' => '_wecp_venue_zip',
            'wecp_venue_country' => '_wecp_venue_country',
            'wecp_venue_lat' => '_wecp_venue_lat',
            'wecp_venue_lng' => '_wecp_venue_lng',
            'wecp_enable_booking' => '_wecp_enable_booking',
            'wecp_max_attendees' => '_wecp_max_attendees',
            'wecp_ticket_price' => '_wecp_ticket_price',
        );

        foreach ($fields as $field => $meta_key) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $meta_key, sanitize_text_field($_POST[$field]));
            } else {
                delete_post_meta($post_id, $meta_key);
            }
        }

        // Sync with WooCommerce if booking is enabled
        if (isset($_POST['wecp_enable_booking']) && $_POST['wecp_enable_booking'] === '1' && class_exists('WooCommerce')) {
            do_action('wecp_sync_event_product', $post_id);
        }
    }

    /**
     * Add custom admin columns
     */
    public function add_admin_columns($columns) {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = $columns['title'];
        $new_columns['event_date'] = __('Event Date', 'wp-event-calendar-pro');
        $new_columns['event_location'] = __('Location', 'wp-event-calendar-pro');
        $new_columns['event_bookings'] = __('Bookings', 'wp-event-calendar-pro');
        $new_columns['taxonomy-wecp_event_category'] = $columns['taxonomy-wecp_event_category'];
        $new_columns['date'] = $columns['date'];

        return $new_columns;
    }

    /**
     * Display custom admin columns
     */
    public function display_admin_columns($column, $post_id) {
        switch ($column) {
            case 'event_date':
                $start_date = get_post_meta($post_id, '_wecp_start_date', true);
                $start_time = get_post_meta($post_id, '_wecp_start_time', true);
                if ($start_date) {
                    echo esc_html(date_i18n(get_option('date_format'), strtotime($start_date)));
                    if ($start_time) {
                        echo '<br>' . esc_html($start_time);
                    }
                }
                break;

            case 'event_location':
                $venue_name = get_post_meta($post_id, '_wecp_venue_name', true);
                $venue_city = get_post_meta($post_id, '_wecp_venue_city', true);
                if ($venue_name) {
                    echo esc_html($venue_name);
                    if ($venue_city) {
                        echo '<br><small>' . esc_html($venue_city) . '</small>';
                    }
                }
                break;

            case 'event_bookings':
                $enable_booking = get_post_meta($post_id, '_wecp_enable_booking', true);
                if ($enable_booking === '1') {
                    $max_attendees = get_post_meta($post_id, '_wecp_max_attendees', true);
                    $current_bookings = $this->get_event_bookings_count($post_id);
                    echo esc_html($current_bookings) . ' / ' . esc_html($max_attendees ?: '∞');
                } else {
                    echo '—';
                }
                break;
        }
    }

    /**
     * Get event bookings count
     */
    private function get_event_bookings_count($event_id) {
        $wc_product_id = get_post_meta($event_id, '_wecp_wc_product_id', true);

        if (!$wc_product_id || !class_exists('WooCommerce')) {
            return 0;
        }

        global $wpdb;

        $count = $wpdb->get_var($wpdb->prepare("
            SELECT SUM(oim.meta_value)
            FROM {$wpdb->prefix}woocommerce_order_items AS oi
            LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS oim ON oi.order_item_id = oim.order_item_id
            LEFT JOIN {$wpdb->prefix}posts AS p ON oi.order_id = p.ID
            WHERE oim.meta_key = '_qty'
            AND oim.order_item_id IN (
                SELECT order_item_id FROM {$wpdb->prefix}woocommerce_order_itemmeta
                WHERE meta_key = '_product_id' AND meta_value = %d
            )
            AND p.post_status IN ('wc-completed', 'wc-processing', 'wc-on-hold')
        ", $wc_product_id));

        return intval($count);
    }
}
