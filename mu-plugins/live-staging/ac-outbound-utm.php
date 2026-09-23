<?php
/**
 * Plugin Name: AC Outbound UTM (ac-outbound-utm)
 * Description: Appends utm_source=angelcare to any outbound doctornowhome.com link in post
 *              content that lacks a utm_source param. Cross-site attribution (Step B 2026-09-02):
 *              AC → DNH journey tracking. Server-side (no JS flash, curl-verifiable).
 *              Preserves existing query params + fragments. Fail-open (regex miss = untouched).
 * Version: 1.0.0
 * Author: CTO
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_filter( 'the_content', 'ac_outbound_utm_the_content', 20 );
function ac_outbound_utm_the_content( $content ) {
	if ( ! is_string( $content ) || $content === '' ) {
		return $content;
	}
	// Only rewrite links to doctornowhome.com (any subdomain form www / bare)
	$content = preg_replace_callback(
		'#(<a\b[^>]*\bhref=["\'])(https?://(?:www\.)?doctornowhome\.com[^"\']*)(["\'])#i',
		'ac_outbound_utm_rewrite_href',
		$content
	);
	return $content;
}

function ac_outbound_utm_rewrite_href( $m ) {
	$prefix = $m[1];
	$url    = $m[2];
	$suffix = $m[3];

	// Split fragment first
	$frag = '';
	if ( strpos( $url, '#' ) !== false ) {
		$pos  = strpos( $url, '#' );
		$frag = substr( $url, $pos );
		$url  = substr( $url, 0, $pos );
	}

	// Parse query params
	$q = array();
	if ( strpos( $url, '?' ) !== false ) {
		parse_str( parse_url( $url, PHP_URL_QUERY ), $q );
		$url = strtok( $url, '?' );
	}

	// Skip if utm_source already present (don't override existing attribution)
	if ( isset( $q['utm_source'] ) && $q['utm_source'] !== '' ) {
		return $m[0];
	}

	$q['utm_source'] = 'angelcare';
	if ( ! isset( $q['utm_medium'] ) ) {
		$q['utm_medium'] = 'content';
	}
	if ( ! isset( $q['utm_campaign'] ) ) {
		$q['utm_campaign'] = 'blog';
	}

	$out = $prefix . esc_url( $url . '?' . http_build_query( $q ) . $frag ) . $suffix;
	return $out;
}
