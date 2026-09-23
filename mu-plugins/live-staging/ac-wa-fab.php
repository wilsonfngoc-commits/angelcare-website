<?php
/**
 * Plugin Name: AC WA FAB + per-page preset mapping
 * Description: Server-side floating WhatsApp button for AngelCare main pages; reads ac_wa_page_mapping option
 *              (slug -> text/utm) for per-page preset text. No client-side dependency. Skips blog posts (own FAB).
 * Version: 0.1.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'wp_footer', function () {
	if ( is_admin() || wp_doing_ajax() ) { return; }
	// Blog posts have their own template FAB — skip to avoid duplicates.
	if ( is_singular( 'ac_blog' ) ) { return; }

	$number = '85263324599';

	// Resolve current path (decode, normalize trailing slash).
	$path = isset( $_SERVER['REQUEST_URI'] ) ? rawurldecode( $_SERVER['REQUEST_URI'] ) : '/';
	$path = preg_replace( '/\?.*$/', '', $path );
	$path = '/' . ltrim( $path, '/' );
	if ( $path !== '/' && substr( $path, -1 ) !== '/' ) { $path .= '/'; }

	$isEn = ( strpos( $path, '/en/' ) === 0 );
	$defaultText = $isEn ? 'I want to learn about AngelCare' : '我想了解AngelCare';
	$defaultUtm  = $isEn ? 'en-home' : 'home';

	$mapping = get_option( 'ac_wa_page_mapping', array() );
	$row     = null;
	if ( is_array( $mapping ) ) {
		foreach ( $mapping as $r ) {
			if ( isset( $r['slug'] ) && $r['slug'] === $path ) { $row = $r; break; }
		}
	}

	$text = $row && ! empty( $row['text'] ) ? $row['text'] : $defaultText;
	$utm  = $row && ! empty( $row['utm_campaign'] ) ? $row['utm_campaign'] : $defaultUtm;

	$href = 'https://wa.me/' . $number . '?text=' . rawurlencode( $text )
		. '&utm_source=angelcare&utm_medium=whatsapp_cta&utm_campaign=' . rawurlencode( $utm );

	echo "\n<!-- AC WA FAB (ac-wa-fab.php) -->\n";
	echo '<a class="ac-wa-fab" href="' . esc_url( $href ) . '" target="_blank" rel="noopener" aria-label="WhatsApp 我哋">';
	echo '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M19.05 4.91A9.816 9.816 0 0 0 12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01zm-7.01 15.24c-1.48 0-2.93-.4-4.2-1.15l-.3-.18-3.12.82.83-3.04-.2-.32a8.423 8.423 0 0 1-1.29-4.45c0-4.63 3.77-8.4 8.4-8.4 2.24 0 4.34.87 5.92 2.45a8.36 8.36 0 0 1 2.45 5.92c.01 4.63-3.76 8.4-8.39 8.4zm4.6-6.29c-.25-.13-1.49-.74-1.72-.82-.23-.08-.4-.13-.57.12-.17.25-.66.82-.81.99-.15.17-.3.19-.55.06-.25-.13-1.06-.39-2.02-1.25-.75-.67-1.25-1.5-1.4-1.75-.15-.25-.02-.38.11-.51.11-.11.25-.29.37-.44.12-.15.17-.25.25-.42.08-.17.04-.32-.02-.45-.06-.13-.57-1.38-.78-1.89-.21-.51-.42-.42-.57-.43-.15-.01-.32-.01-.49-.01-.17 0-.45.07-.68.32-.24.25-.9.88-.9 2.15 0 1.27.92 2.49 1.05 2.66.13.17 1.82 2.78 4.41 3.9.62.26 1.1.42 1.47.54.62.2 1.19.17 1.63.1.5-.08 1.49-.61 1.7-1.2.21-.59.21-1.1.15-1.2-.06-.11-.23-.17-.48-.3z"/></svg>';
	echo '</a>';
	echo '<style>.ac-wa-fab{position:fixed;bottom:24px;right:24px;z-index:99999;width:56px;height:56px;border-radius:50%;background:#25D366;color:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 14px rgba(0,0,0,.18);transition:transform .15s ease}.ac-wa-fab:hover{transform:scale(1.06)}</style>' . "\n";
}, 5 );
