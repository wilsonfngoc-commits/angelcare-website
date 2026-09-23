<?php
/**
 * Plugin Name: TRP Rank Math Sitemap
 * Description: Adds TranslatePress /en/ translated URLs to the Rank Math XML sitemap (AngelCare).
 *              Hooks rank_math/sitemap/url — the filter TRP itself whitelists for generating
 *              other-language URLs (see TRP class-url-converter.php is_sitemap_path()).
 * Author: CTO
 * Version: 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'rank_math/sitemap/url', 'ac_trp_sitemap_add_translated_urls', 10, 2 );

/**
 * Append the TranslatePress /en/ variant of each default-language sitemap URL.
 *
 * Rank Math renders one <url> entry per object using the default-language
 * permalink (zh, no prefix). TranslatePress stores translations against the
 * same object and serves them at the /en/ prefixed URL, so we emit a second
 * <url> entry for the EN variant. TRP's URL converter is safe to call inside
 * this filter — TRP explicitly bypasses its sitemap-path guard when the
 * current filter is rank_math/sitemap/url.
 *
 * @param string $output Rendered <url> XML for the default-language entry.
 * @param array  $url    Rank Math URL parts (loc, mod, images).
 * @return string
 */
function ac_trp_sitemap_add_translated_urls( $output, $url ) {
	if ( ! class_exists( 'TRP_Translate_Press' ) || empty( $url['loc'] ) || ! is_string( $url['loc'] ) ) {
		return $output;
	}

	// Guard: never re-prefix a URL that already carries a language slug.
	if ( preg_match( '#^[a-z]+://[^/]+/(zh|en)(/|$)#i', $url['loc'] ) ) {
		return $output;
	}

	$language = 'en_US';

	try {
		$trp = TRP_Translate_Press::get_trp_instance();
		$uc  = $trp->get_component( 'url_converter' );
		$en  = $uc->get_url_for_language( $language, $url['loc'] );
	} catch ( \Exception $e ) {
		return $output;
	}

	if ( empty( $en ) || $en === $url['loc'] ) {
		return $output;
	}

	// Reuse Rank Math's own renderer for the translated entry (encoding,
	// lastmod formatting, indentation). Guard against recursion: sitemap_url()
	// re-fires rank_math/sitemap/url, which would call this filter again.
	if ( ! class_exists( '\RankMath\Sitemap\Generator' ) ) {
		return $output;
	}

	static $building = false;
	if ( $building ) {
		return $output;
	}
	$building = true;

	$entry = ( new \RankMath\Sitemap\Generator() )->sitemap_url(
		array(
			'loc' => $en,
			'mod' => isset( $url['mod'] ) ? $url['mod'] : null,
		)
	);

	$building = false;

	return $output . $entry;
}
