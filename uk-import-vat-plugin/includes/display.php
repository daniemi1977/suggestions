<?php
/**
 * Funzioni di display frontend
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Ottiene le classi CSS per il box
 */
function uiv_get_box_classes() {
    $classes = array('uiv-box');

    // Aggiungi classe per la posizione
    $plugin = UK_Import_VAT::get_instance();
    $position = $plugin->get_option('box_position', 'after_shipping');
    $classes[] = 'uiv-box-' . $position;

    return implode(' ', $classes);
}

/**
 * Sanitizza l'output HTML
 */
function uiv_sanitize_output($content) {
    return wp_kses_post($content);
}

/**
 * Genera il titolo del box
 */
function uiv_get_box_title() {
    return apply_filters('uiv_box_title', '🇬🇧 Stima Tasse di Importazione UK');
}

/**
 * Genera la nota del box
 */
function uiv_get_box_note() {
    return apply_filters('uiv_box_note', '*Tasse pagate direttamente al corriere alla consegna');
}

/**
 * Hook per permettere personalizzazioni del calcolo
 */
function uiv_apply_calculation_filters($calculation) {
    return apply_filters('uiv_calculation_data', $calculation);
}

/**
 * Verifica se siamo nella pagina checkout
 */
function uiv_is_checkout_page() {
    return is_checkout() && !is_wc_endpoint_url('order-received');
}

/**
 * Verifica se siamo nella pagina carrello
 */
function uiv_is_cart_page() {
    return is_cart();
}
