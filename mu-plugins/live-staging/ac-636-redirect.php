<?php
/**
 * Plugin Name: AC 636 Redirect
 * Description: 301 redirect retired page 636 (從居家安老到院舍安老，全面了解長者照護選擇) to the new hub page 長者照護選擇. Env-agnostic (hub looked up by slug).
 * Author: CTO
 * Version: 1.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'template_redirect', 'ac_636_redirect', 1 );

function ac_636_redirect() {
    if ( is_admin() ) return;

    $hit = false;
    if ( isset( $_GET['page_id'] ) && $_GET['page_id'] === '636' ) $hit = true;
    if ( ! $hit && isset( $_GET['p'] ) && $_GET['p'] === '636' ) $hit = true;
    if ( ! $hit ) {
        $uri = isset( $_SERVER['REQUEST_URI'] ) ? rawurldecode( $_SERVER['REQUEST_URI'] ) : '';
        // old slug (raw or %-encoded), with or without /en/ prefix
        if ( strpos( $uri, '從居家安老到院舍安老' ) !== false ) $hit = true;
    }
    if ( ! $hit ) return;

    global $wpdb;
    $hub = $wpdb->get_var( $wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type = 'page' AND post_status = 'publish' LIMIT 1",
        '%e9%95%b7%e8%80%85%e7%85%a7%e8%ad%b7%e9%81%b8%e6%93%87'
    ) );
    if ( $hub ) {
        wp_redirect( get_permalink( $hub ), 301 );
        exit;
    }
    wp_redirect( home_url( '/' ), 301 );
    exit;
}
