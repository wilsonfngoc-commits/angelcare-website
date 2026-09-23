<?php
/** Trust-ladder staging apply #2 — 2026-09-22: real render source for 評估 is template 708, not page 704. */
global $wpdb;

function ld_json( $post_id ) {
	global $wpdb;
	$raw = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM wp_postmeta WHERE post_id = %d AND meta_key = '_elementor_data'", $post_id ) );
	if ( ! is_string( $raw ) || '' === $raw ) return null;
	$d = json_decode( $raw, true );
	return is_array( $d ) ? $d : null;
}

$out = array();

// ── restore 704 (its own data is dead — render source is 708) ──
$orig = @file_get_contents( '/tmp/el704_data.json' );
if ( false !== $orig ) {
	$check = json_decode( trim( $orig ), true );
	if ( is_array( $check ) ) {
		$wpdb->update( 'wp_postmeta', array( 'meta_value' => trim( $orig ) ), array( 'post_id' => 704, 'meta_key' => '_elementor_data' ) );
		clean_post_cache( 704 );
		$out[] = 'OK: 704 restored to original (dead-data cleanup)';
	} else { $out[] = 'SKIP: 704 backup not valid JSON'; }
} else { $out[] = 'SKIP: 704 backup missing'; }

// ── append ladder card to 708 (single template for page 704) ──
$f = ld_json( 708 );
if ( null === $f ) { $out[] = 'FAIL: 708 decode'; }
elseif ( false !== strpos( json_encode( $f ), 'ac_escalation' ) ) { $out[] = 'SKIP: 708 already has ladder'; }
else {
	$c = array(
		'id'       => 'lad708cc',
		'elType'   => 'container',
		'isInner'  => false,
		'settings' => array(
			'content_width' => 'full',
			'padding'       => array( 'unit' => 'px', 'top' => '0', 'right' => '0', 'bottom' => '10', 'left' => '0', 'isLinked' => false ),
			'_title'        => 'Trust Ladder',
		),
		'elements' => array(
			array(
				'id'         => 'lad708cw',
				'elType'     => 'widget',
				'isInner'    => false,
				'widgetType' => 'text-editor',
				'settings'   => array( 'editor' => '[ac_escalation]' ),
				'elements'   => array(),
			),
		),
	);
	$f[] = $c;
	$new = json_encode( $f, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	$wpdb->update( 'wp_postmeta', array( 'meta_value' => $new ), array( 'post_id' => 708, 'meta_key' => '_elementor_data' ) );
	clean_post_cache( 708 );
	$chk = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM wp_postmeta WHERE post_id = %d AND meta_key = '_elementor_data'", 708 ) );
	$out[] = ( false !== strpos( (string) $chk, 'lad708cc' ) ? 'OK' : 'FAIL' ) . ': 708 appended';
}

// ── CSS regen 708 ──
if ( class_exists( '\Elementor\Core\Files\CSS\Post' ) ) {
	try { \Elementor\Core\Files\CSS\Post::create( 708 )->update(); $out[] = 'CSS regenerated: 708'; }
	catch ( \Throwable $e ) { $out[] = 'CSS FAIL 708: ' . $e->getMessage(); }
}

$wpdb->query( "DELETE FROM wp_postmeta WHERE meta_key = '_elementor_element_cache'" );
wp_cache_flush();
$out[] = 'caches flushed';
echo implode( "\n", $out ) . "\nDONE\n";
