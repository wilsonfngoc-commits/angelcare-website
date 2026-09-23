<?php
/** Trust-ladder staging apply — 2026-09-22 (staging 8092). Idempotent. */
global $wpdb;

function ld_json( $post_id ) {
	global $wpdb;
	$raw = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM wp_postmeta WHERE post_id = %d AND meta_key = '_elementor_data'", $post_id ) );
	if ( ! is_string( $raw ) || '' === $raw ) return null;
	$d = json_decode( $raw, true );
	return is_array( $d ) ? $d : null;
}
function ld_save( $post_id, $data ) {
	global $wpdb;
	$new = json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	$wpdb->update( 'wp_postmeta', array( 'meta_value' => $new ), array( 'post_id' => $post_id, 'meta_key' => '_elementor_data' ) );
	clean_post_cache( $post_id );
	return false !== strpos( (string) $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM wp_postmeta WHERE post_id = %d AND meta_key = '_elementor_data'", $post_id ) ), 'ac_escalation' );
}
function ld_container( $widget_id, $shortcode, $title ) {
	return array(
		'id'       => $widget_id . 'c',
		'elType'   => 'container',
		'isInner'  => false,
		'settings' => array(
			'content_width' => 'full',
			'padding'       => array( 'unit' => 'px', 'top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '0', 'isLinked' => false ),
			'_title'        => $title,
		),
		'elements' => array(
			array(
				'id'         => $widget_id,
				'elType'     => 'widget',
				'isInner'    => false,
				'widgetType' => 'text-editor',
				'settings'   => array( 'editor' => $shortcode ),
				'elements'   => array(),
			),
		),
	);
}

$out = array();

// ── A. footer template 152: prepend strip ──
$f152 = ld_json( 152 );
if ( null === $f152 ) { $out[] = 'FAIL: 152 decode'; }
elseif ( false !== strpos( json_encode( $f152 ), 'ac_escalation' ) ) { $out[] = 'SKIP: 152 already has ladder'; }
else {
	array_unshift( $f152, ld_container( 'ladftr1', '[ac_escalation layout=footer]', 'Trust Ladder CTA' ) );
	$out[] = ( ld_save( 152, $f152 ) ? 'OK' : 'FAIL' ) . ': footer 152 prepended';
}

// ── C1. assessment page 704: append card ──
$f704 = ld_json( 704 );
if ( null === $f704 ) { $out[] = 'FAIL: 704 decode'; }
elseif ( false !== strpos( json_encode( $f704 ), 'ac_escalation' ) ) { $out[] = 'SKIP: 704 already has ladder'; }
else {
	$c = ld_container( 'lad704a', '[ac_escalation]', 'Trust Ladder' );
	$c['settings']['padding'] = array( 'unit' => 'px', 'top' => '10', 'right' => '0', 'bottom' => '0', 'left' => '0', 'isLinked' => false );
	$f704[] = $c;
	$out[] = ( ld_save( 704, $f704 ) ? 'OK' : 'FAIL' ) . ': page 704 appended';
}

// ── C2. carer post 840: append shortcode to classic content ──
$pc = $wpdb->get_var( $wpdb->prepare( 'SELECT post_content FROM wp_posts WHERE ID = %d', 840 ) );
if ( ! is_string( $pc ) ) { $out[] = 'FAIL: 840 content'; }
elseif ( false !== strpos( $pc, 'ac_escalation' ) ) { $out[] = 'SKIP: 840 already'; }
else {
	$new = rtrim( $pc ) . "\n\n[ac_escalation]\n";
	$wpdb->update( 'wp_posts', array( 'post_content' => $new ), array( 'ID' => 840 ) );
	clean_post_cache( 840 );
	$chk = $wpdb->get_var( $wpdb->prepare( 'SELECT post_content FROM wp_posts WHERE ID = %d', 840 ) );
	$out[] = ( false !== strpos( (string) $chk, 'ac_escalation' ) ? 'OK' : 'FAIL' ) . ': post 840 appended';
}

// ── CSS regen (per-post only, never flush_css) ──
if ( class_exists( '\Elementor\Core\Files\CSS\Post' ) ) {
	foreach ( array( 152, 704 ) as $pid ) {
		try { \Elementor\Core\Files\CSS\Post::create( $pid )->update(); $out[] = "CSS regenerated: $pid"; }
		catch ( \Throwable $e ) { $out[] = "CSS FAIL $pid: " . $e->getMessage(); }
	}
}

// ── cache purge: element cache + WPSC + object cache ──
$wpdb->query( "DELETE FROM wp_postmeta WHERE meta_key = '_elementor_element_cache'" );
if ( function_exists( 'wp_cache_clear_cache' ) ) { wp_cache_clear_cache(); $out[] = 'WPSC cleared'; }
wp_cache_flush();
$out[] = 'object cache flushed';

echo implode( "\n", $out ) . "\nDONE\n";
