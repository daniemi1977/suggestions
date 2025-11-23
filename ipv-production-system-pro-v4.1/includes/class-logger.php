<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IPV_Prod_Logger {

    public static function log( $message, $context = [] ) {
        if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
            return;
        }
        $prefix = '[IPV Production] ';
        if ( ! empty( $context ) && function_exists( 'wp_json_encode' ) ) {
            $message .= ' ' . wp_json_encode( $context );
        }
        error_log( $prefix . $message );
    }
}
