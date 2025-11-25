<?php
/**
 * Funzioni di calcolo tasse UK
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Formatta un valore in euro
 */
function uiv_format_eur($amount) {
    return '€' . number_format($amount, 2, ',', '.');
}

/**
 * Formatta un valore in sterline
 */
function uiv_format_gbp($amount) {
    return '£' . number_format($amount, 2, '.', ',');
}

/**
 * Verifica se un paese è il Regno Unito
 */
function uiv_is_uk_country($country_code) {
    return in_array($country_code, array('GB', 'UK'));
}

/**
 * Ottiene il tasso di cambio corrente (placeholder per integrazioni future)
 */
function uiv_get_live_exchange_rate() {
    // Placeholder per future integrazioni con API di cambio valuta
    // Per ora ritorna false e usa il valore configurato
    return false;
}

/**
 * Calcola la percentuale di un valore
 */
function uiv_calculate_percentage($amount, $percentage) {
    return ($amount * $percentage) / 100;
}

/**
 * Arrotonda un valore al centesimo
 */
function uiv_round_currency($amount) {
    return round($amount, 2);
}

/**
 * Valida un tasso di cambio
 */
function uiv_validate_exchange_rate($rate) {
    $rate = floatval($rate);
    return ($rate > 0 && $rate < 10); // Tasso ragionevole tra 0 e 10
}

/**
 * Valida una percentuale
 */
function uiv_validate_percentage($percentage) {
    $percentage = floatval($percentage);
    return ($percentage >= 0 && $percentage <= 100);
}

/**
 * Ottiene il simbolo della valuta
 */
function uiv_get_currency_symbol($currency = 'EUR') {
    $symbols = array(
        'EUR' => '€',
        'GBP' => '£',
        'USD' => '$'
    );

    return isset($symbols[$currency]) ? $symbols[$currency] : $currency;
}

/**
 * Debug helper per stampare array
 */
function uiv_debug_array($array, $label = '') {
    $plugin = UK_Import_VAT::get_instance();

    if (!$plugin->get_option('debug_mode', false)) {
        return;
    }

    $message = $label ? $label . ': ' : '';
    $message .= print_r($array, true);

    $plugin->debug_log($message);
}
