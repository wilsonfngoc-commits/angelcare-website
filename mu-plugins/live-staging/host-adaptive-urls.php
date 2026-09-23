<?php
/**
 * Host-adaptive home/siteurl for local staging.
 * Allows viewing via Cloudflare tunnel / public IP without breaking .local access.
 * Runs after core constant filters (priority 999) so it wins.
 */
if (isset($_SERVER["HTTP_HOST"])) {
    $scheme = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ? "https" : "http";
    add_filter("pre_option_home", function($v) use ($scheme) {
        return $scheme . "://" . $_SERVER["HTTP_HOST"];
    }, 999);
    add_filter("pre_option_siteurl", function($v) use ($scheme) {
        return $scheme . "://" . $_SERVER["HTTP_HOST"];
    }, 999);
    // Allow both http/https variants to be generated consistently
    add_filter("pre_option_home", function($v) use ($scheme) {
        return $scheme . "://" . $_SERVER["HTTP_HOST"];
    }, 999);

    // v2 (2026-08-06): rewrite hardcoded prod-domain links (https://angelcare.dnow.hk)
    // to the current host in the final HTML output. Elementor widget hrefs and the
    // button re-link map in ac-spacing-fix.php embed the literal prod domain; home_url()
    // filters don't touch them. This output buffer rewrites them so staging previews
    // stay on staging (and EN buttons resolve to /en/ pages that only exist on staging
    // until the bilingual package ships). On prod (host == angelcare.dnow.hk) it's a no-op.
    add_action('template_redirect', function() use ($scheme) {
        if (is_admin()) return;
        if (defined('DOING_CRON') && DOING_CRON) return;
        if (defined('REST_REQUEST') && REST_REQUEST) return;
        if (function_exists('wp_doing_ajax') && wp_doing_ajax()) return;
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if ($host === 'angelcare.dnow.hk') return; // prod: nothing to rewrite
        $target = $scheme . '://' . $host;
        ob_start(function($html) use ($target) {
            // Only rewrite the literal prod domain; leave external domains (easycare,
            // doctornowhome, app stores, google play) untouched.
            return str_replace('https://angelcare.dnow.hk', $target, $html);
        });
    }, 1);
}
