<?php
/**
 * Plugin Name: UK Import VAT 3.2
 * Plugin URI: https://github.com/daniemi1977/suggestions
 * Description: Mostra ai clienti UK una stima precisa delle tasse di importazione durante il checkout e nel carrello WooCommerce, senza alterare i prezzi.
 * Version: 3.2.0
 * Author: Daniele Michielon
 * Author URI: https://github.com/daniemi1977
 * Text Domain: uk-import-vat
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * WC requires at least: 4.0
 * WC tested up to: 8.5
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Previene l'accesso diretto al file
if (!defined('ABSPATH')) {
    exit;
}

// Verifica che WooCommerce sia attivo
function uiv_check_woocommerce() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'uiv_woocommerce_missing_notice');
        return false;
    }
    return true;
}

function uiv_woocommerce_missing_notice() {
    ?>
    <div class="notice notice-error">
        <p><strong>UK Import VAT 3.2:</strong> Questo plugin richiede WooCommerce per funzionare. Per favore, installa e attiva WooCommerce.</p>
    </div>
    <?php
}

// Costanti del plugin
define('UIV_VERSION', '3.2.0');
define('UIV_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('UIV_PLUGIN_URL', plugin_dir_url(__FILE__));
define('UIV_LOG_FILE', WP_CONTENT_DIR . '/uk_import_vat_debug.log');

/**
 * Classe principale del plugin
 */
class UK_Import_VAT {

    private static $instance = null;
    private $options = array();

    /**
     * Singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Carica le opzioni
        $this->load_options();

        // Hook per l'inizializzazione
        add_action('plugins_loaded', array($this, 'init'));
    }

    /**
     * Inizializzazione del plugin
     */
    public function init() {
        // Verifica WooCommerce
        if (!uiv_check_woocommerce()) {
            return;
        }

        // Log inizializzazione
        $this->debug_log('Plugin inizializzato. Versione: ' . UIV_VERSION);

        // Carica i file necessari
        $this->includes();

        // Hook admin
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_menu'));
            add_action('admin_init', array($this, 'register_settings'));
        }

        // Hook frontend
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));

        // Hook per mostrare il box delle tasse
        $this->setup_display_hooks();
    }

    /**
     * Carica file necessari
     */
    private function includes() {
        require_once UIV_PLUGIN_DIR . 'includes/calculations.php';
        require_once UIV_PLUGIN_DIR . 'includes/display.php';
    }

    /**
     * Carica le opzioni dal database
     */
    private function load_options() {
        $defaults = array(
            'minimum_order' => 220,
            'exchange_rate' => 0.86,
            'vat_rate' => 20,
            'duty_rate' => 6.5,
            'box_position' => 'after_shipping',
            'debug_mode' => false,
            'custom_css' => ''
        );

        $saved_options = get_option('uiv_opt', array());
        $this->options = wp_parse_args($saved_options, $defaults);

        $this->debug_log('Opzioni caricate: ' . json_encode($this->options));
    }

    /**
     * Ottiene un'opzione
     */
    public function get_option($key, $default = null) {
        return isset($this->options[$key]) ? $this->options[$key] : $default;
    }

    /**
     * Aggiorna le opzioni
     */
    public function update_options($new_options) {
        $this->options = wp_parse_args($new_options, $this->options);
        update_option('uiv_opt', $this->options);
        $this->debug_log('Opzioni aggiornate: ' . json_encode($new_options));
    }

    /**
     * Setup display hooks basati sulla posizione configurata
     */
    private function setup_display_hooks() {
        $position = $this->get_option('box_position', 'after_shipping');

        if ($position === 'before_total') {
            // Prima del totale ordine
            add_action('woocommerce_review_order_before_order_total', array($this, 'display_uk_tax_box'));
            add_action('woocommerce_cart_totals_before_order_total', array($this, 'display_uk_tax_box'));
        } else {
            // Dopo le spese di spedizione (default)
            add_action('woocommerce_review_order_after_shipping', array($this, 'display_uk_tax_box'));
            add_action('woocommerce_cart_totals_after_shipping', array($this, 'display_uk_tax_box'));
        }
    }

    /**
     * Mostra il box delle tasse UK
     */
    public function display_uk_tax_box() {
        if (!$this->should_display_box()) {
            $this->debug_log('Box non visualizzato: condizioni non soddisfatte');
            return;
        }

        $calculation = $this->calculate_uk_taxes();

        if (!$calculation) {
            $this->debug_log('Box non visualizzato: calcolo fallito');
            return;
        }

        $this->debug_log('Box visualizzato con dati: ' . json_encode($calculation));

        // Includi il template
        include UIV_PLUGIN_DIR . 'templates/tax-box.php';
    }

    /**
     * Verifica se il box deve essere mostrato
     */
    private function should_display_box() {
        // Verifica che WC sia attivo
        if (!function_exists('WC')) {
            return false;
        }

        // Verifica che ci sia un carrello
        $cart = WC()->cart;
        if (!$cart || $cart->is_empty()) {
            return false;
        }

        // Verifica che il cliente sia del Regno Unito
        $customer = WC()->customer;
        if (!$customer) {
            return false;
        }

        $billing_country = $customer->get_billing_country();
        $shipping_country = $customer->get_shipping_country();

        $is_uk = ($billing_country === 'GB' || $shipping_country === 'GB');

        if (!$is_uk) {
            $this->debug_log('Cliente non UK: billing=' . $billing_country . ', shipping=' . $shipping_country);
            return false;
        }

        // Verifica ordine minimo (sempre senza IVA italiana)
        if (wc_prices_include_tax()) {
            $cart_total = floatval($cart->get_subtotal()) - floatval($cart->get_subtotal_tax()) +
                         floatval($cart->get_shipping_total()) - floatval($cart->get_shipping_tax());
        } else {
            $cart_total = floatval($cart->get_subtotal()) + floatval($cart->get_shipping_total());
        }

        $minimum = floatval($this->get_option('minimum_order', 220));

        if ($cart_total < $minimum) {
            $this->debug_log('Totale ordine sotto il minimo (ex-tax): ' . $cart_total . ' < ' . $minimum);
            return false;
        }

        return true;
    }

    /**
     * Calcola le tasse UK
     */
    private function calculate_uk_taxes() {
        if (!function_exists('WC') || !WC()->cart) {
            return false;
        }

        $cart = WC()->cart;

        // Subtotal senza IVA + spedizione
        // IMPORTANTE: Usa sempre i prezzi SENZA IVA italiana
        if (wc_prices_include_tax()) {
            // Se i prezzi includono IVA, sottrai le tasse italiane
            $subtotal = floatval($cart->get_subtotal()) - floatval($cart->get_subtotal_tax());
            $shipping = floatval($cart->get_shipping_total()) - floatval($cart->get_shipping_tax());
        } else {
            // Se i prezzi non includono IVA, usa direttamente i totali
            $subtotal = floatval($cart->get_subtotal());
            $shipping = floatval($cart->get_shipping_total());
        }

        $total_eur = $subtotal + $shipping;

        // Tassi
        $exchange_rate = floatval($this->get_option('exchange_rate', 0.86));
        $vat_rate = floatval($this->get_option('vat_rate', 20));
        $duty_rate = floatval($this->get_option('duty_rate', 6.5));

        // Conversione in GBP
        $total_gbp = $total_eur * $exchange_rate;

        // Calcolo IVA UK (in EUR)
        $vat_eur = ($total_gbp * ($vat_rate / 100)) / $exchange_rate;

        // Calcolo dazio (in EUR)
        $duty_eur = ($total_gbp * ($duty_rate / 100)) / $exchange_rate;

        // Totale tasse
        $total_tax = $vat_eur + $duty_eur;

        $result = array(
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'total_eur' => $total_eur,
            'total_gbp' => $total_gbp,
            'vat_eur' => $vat_eur,
            'duty_eur' => $duty_eur,
            'total_tax' => $total_tax,
            'vat_rate' => $vat_rate,
            'duty_rate' => $duty_rate
        );

        $tax_mode = wc_prices_include_tax() ? 'PREZZI INC. IVA (sottratta)' : 'PREZZI EX IVA';
        $this->debug_log('Calcolo [' . $tax_mode . ']: subtotal=' . number_format($subtotal, 2) . ', shipping=' . number_format($shipping, 2) . ', total_eur=' . number_format($total_eur, 2) . ', total_gbp=' . number_format($total_gbp, 2) . ', vat_eur=' . number_format($vat_eur, 2) . ', duty_eur=' . number_format($duty_eur, 2));

        return $result;
    }

    /**
     * Enqueue styles
     */
    public function enqueue_styles() {
        if (!is_checkout() && !is_cart()) {
            return;
        }

        // CSS inline per il box
        $css = $this->get_default_css();

        // Aggiungi CSS personalizzato
        $custom_css = $this->get_option('custom_css', '');
        if (!empty($custom_css)) {
            $css .= "\n" . $custom_css;
        }

        wp_register_style('uk-import-vat-styles', false);
        wp_enqueue_style('uk-import-vat-styles');
        wp_add_inline_style('uk-import-vat-styles', $css);
    }

    /**
     * CSS di default
     */
    private function get_default_css() {
        return "
.uiv-box {
    background: #e8f5e9;
    border: 2px solid #4caf50;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
}

.uiv-box-title {
    font-size: 1.2em;
    font-weight: 600;
    color: #2e7d32;
    margin: 0 0 15px 0;
    display: flex;
    align-items: center;
}

.uiv-box-title::before {
    content: '🇬🇧';
    margin-right: 8px;
    font-size: 1.3em;
}

.uiv-box-total {
    font-size: 1.1em;
    margin-bottom: 15px;
    color: #333;
}

.uiv-box-breakdown {
    list-style: none;
    padding: 0;
    margin: 15px 0;
}

.uiv-box-breakdown li {
    padding: 5px 0;
    color: #555;
    font-size: 0.95em;
}

.uiv-box-breakdown li::before {
    content: '• ';
    color: #4caf50;
    font-weight: bold;
    margin-right: 5px;
}

.uiv-box-final {
    font-size: 1.15em;
    font-weight: 600;
    color: #1b5e20;
    margin: 15px 0 10px 0;
    padding-top: 15px;
    border-top: 1px solid #81c784;
}

.uiv-box-note {
    font-size: 0.85em;
    color: #666;
    font-style: italic;
    margin-top: 10px;
}

@media (max-width: 768px) {
    .uiv-box {
        padding: 15px;
        font-size: 0.95em;
    }

    .uiv-box-title {
        font-size: 1.1em;
    }
}
";
    }

    /**
     * Aggiunge menu admin
     */
    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            'UK Import VAT',
            'UK VAT',
            'manage_woocommerce',
            'uk-import-vat',
            array($this, 'admin_page')
        );
    }

    /**
     * Registra le impostazioni
     */
    public function register_settings() {
        register_setting('uiv_settings', 'uiv_opt');
    }

    /**
     * Pagina admin
     */
    public function admin_page() {
        include UIV_PLUGIN_DIR . 'admin/settings-page.php';
    }

    /**
     * Debug logging
     */
    public function debug_log($message) {
        if (!$this->get_option('debug_mode', false)) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $log_message = '[' . $timestamp . '] ' . $message . PHP_EOL;

        @file_put_contents(UIV_LOG_FILE, $log_message, FILE_APPEND);
    }
}

// Inizializza il plugin
function uk_import_vat_init() {
    return UK_Import_VAT::get_instance();
}

add_action('plugins_loaded', 'uk_import_vat_init');

// Hook di attivazione
register_activation_hook(__FILE__, 'uiv_activate');
function uiv_activate() {
    // Crea opzioni di default se non esistono
    if (!get_option('uiv_opt')) {
        add_option('uiv_opt', array(
            'minimum_order' => 220,
            'exchange_rate' => 0.86,
            'vat_rate' => 20,
            'duty_rate' => 6.5,
            'box_position' => 'after_shipping',
            'debug_mode' => false,
            'custom_css' => ''
        ));
    }
}

// Hook di disattivazione
register_deactivation_hook(__FILE__, 'uiv_deactivate');
function uiv_deactivate() {
    // Opzionale: pulizia se necessaria
}
