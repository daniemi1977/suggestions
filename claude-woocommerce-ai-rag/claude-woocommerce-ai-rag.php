<?php
/**
 * Plugin Name: Claude WooCommerce AI - RAG Sales Agent
 * Description: Advanced AI Sales Assistant with RAG (Retrieval-Augmented Generation) using Claude and OpenAI APIs
 * Version: 3.0.0
 * Author: AI Shop Assistant
 * Requires at least: 5.7
 * Tested up to: 6.6
 * Requires PHP: 7.4
 * Text Domain: claude-wc-ultimate
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) exit;

define('CWAU_VERSION', '3.0.0');
define('CWAU_FILE', __FILE__);
define('CWAU_PATH', plugin_dir_path(__FILE__));
define('CWAU_URL', plugin_dir_url(__FILE__));
define('CWAU_BASENAME', plugin_basename(__FILE__));

// Autoload classes
spl_autoload_register(function($class) {
    if (strpos($class, 'CWAU_') === 0) {
        $file = CWAU_PATH . 'includes/class-' . strtolower(str_replace('_', '-', substr($class, 5))) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

// Load required classes
require_once CWAU_PATH . 'includes/class-database.php';
require_once CWAU_PATH . 'includes/class-rag.php';
require_once CWAU_PATH . 'includes/class-chat.php';
require_once CWAU_PATH . 'includes/class-admin.php';
require_once CWAU_PATH . 'includes/class-analytics.php';
require_once CWAU_PATH . 'includes/class-operators.php';
require_once CWAU_PATH . 'includes/class-ecommerce.php';
require_once CWAU_PATH . 'includes/class-testers.php';

class CWAU_Plugin {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->add_hooks();
        register_activation_hook(CWAU_FILE, array($this, 'activate'));
        register_deactivation_hook(CWAU_FILE, array($this, 'deactivate'));
    }

    private function add_hooks() {
        add_action('init', array($this, 'load_textdomain'));
        add_action('admin_init', array('CWAU_Admin', 'register_settings'));
        add_action('admin_menu', array('CWAU_Admin', 'register_menu'));
        add_action('admin_enqueue_scripts', array('CWAU_Admin', 'enqueue_assets'));

        // AJAX Actions
        add_action('wp_ajax_cwau_test_openai', array('CWAU_Testers', 'test_openai'));
        add_action('wp_ajax_cwau_test_anthropic', array('CWAU_Testers', 'test_anthropic'));
        add_action('wp_ajax_cwau_rag_index_batch', array('CWAU_RAG', 'ajax_index_batch'));
        add_action('wp_ajax_cwau_rag_clear', array('CWAU_RAG', 'ajax_clear'));
        add_action('wp_ajax_cwau_rag_reindex', array('CWAU_RAG', 'ajax_reindex'));
        add_action('wp_ajax_cwau_chat', array('CWAU_Chat', 'ajax_chat'));
        add_action('wp_ajax_nopriv_cwau_chat', array('CWAU_Chat', 'ajax_chat'));

        // Analytics & Conversations
        add_action('wp_ajax_cwau_get_analytics', array('CWAU_Analytics', 'get_analytics'));
        add_action('wp_ajax_cwau_get_conversations', array('CWAU_Analytics', 'get_conversations'));
        add_action('wp_ajax_cwau_export_conversations', array('CWAU_Analytics', 'export_conversations'));
        add_action('wp_ajax_cwau_get_conversation_messages', array('CWAU_Analytics', 'get_conversation_messages'));

        // Operators
        add_action('wp_ajax_cwau_escalate_to_operator', array('CWAU_Operators', 'escalate'));
        add_action('wp_ajax_cwau_get_escalations', array('CWAU_Operators', 'get_escalations'));

        // E-commerce
        add_action('wp_ajax_cwau_search_products', array('CWAU_Ecommerce', 'search_products'));
        add_action('wp_ajax_nopriv_cwau_search_products', array('CWAU_Ecommerce', 'search_products'));

        // Frontend
        add_shortcode('cwau_chat', array('CWAU_Chat', 'shortcode'));
        add_action('wp_footer', array('CWAU_Chat', 'maybe_widget'));
        add_action('wp_enqueue_scripts', array('CWAU_Chat', 'enqueue_frontend_assets'));
    }

    public function load_textdomain() {
        load_plugin_textdomain('claude-wc-ultimate', false, dirname(CWAU_BASENAME) . '/languages');
    }

    public function activate() {
        // Create all necessary tables
        CWAU_Database::create_tables();

        // Default options
        $defaults = array(
            'cwau_openai_api_key' => '',
            'cwau_anthropic_api_key' => '',
            'cwau_preferred_ai' => 'openai',
            'cwau_chat_enabled' => 1,
            'cwau_chat_position' => 'bottom-right',
            'cwau_chat_title' => 'Shop Assistant',
            'cwau_chat_placeholder' => 'Come posso aiutarti?',
            'cwau_chat_bg' => '#2563eb',
            'cwau_chat_color' => '#ffffff',
            'cwau_save_conversations' => 1,
            'cwau_operator_email' => get_option('admin_email'),
            'cwau_rag_enabled' => 1,
            'cwau_rag_top_k' => 5,
            'cwau_prompt_templates' => array(
                'default' => 'Sei un assistente esperto di e-commerce. Aiuta i clienti con prodotti, ordini e domande generali. Sii professionale ma amichevole.',
                'product_inquiry' => 'Concentrati sui dettagli tecnici e benefici del prodotto. Suggerisci prodotti correlati se appropriato.',
                'order_support' => 'Aiuta con problemi di ordini, spedizioni e resi. Sii empatico e orientato alla soluzione.',
                'complaint' => 'Ascolta con empatia, scusati se necessario e proponi soluzioni concrete. Escalate se richiesto.'
            ),
            'cwau_quick_replies' => array(
                'Informazioni prodotto',
                'Stato ordine',
                'Assistenza tecnica',
                'Parla con operatore'
            )
        );

        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }
}

// Initialize plugin
function cwau_init() {
    return CWAU_Plugin::get_instance();
}
add_action('plugins_loaded', 'cwau_init');

// Uninstall hook
register_uninstall_hook(__FILE__, 'cwau_uninstall');
function cwau_uninstall() {
    global $wpdb;

    // Drop tables
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}cwau_conversations");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}cwau_messages");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}cwau_escalations");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}cwau_embeddings");

    // Delete all options
    $options = array(
        'cwau_openai_api_key', 'cwau_anthropic_api_key', 'cwau_preferred_ai',
        'cwau_chat_enabled', 'cwau_chat_position', 'cwau_chat_title',
        'cwau_chat_placeholder', 'cwau_chat_bg', 'cwau_chat_color',
        'cwau_save_conversations', 'cwau_operator_email', 'cwau_rag_enabled',
        'cwau_rag_top_k', 'cwau_prompt_templates', 'cwau_quick_replies',
        'cwau_escalation_keywords'
    );

    foreach ($options as $option) {
        delete_option($option);
    }

    // Delete post meta for indexed products
    $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_cwau_%'");
}
