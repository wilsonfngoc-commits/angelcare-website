<?php
/**
 * Plugin Name: AC Spacing Fix v1.5 (Staging)
 * Description:
 *  1. David Wong quote block fixes on home hero (mobile justify kill, spacing rhythm)
 *  2. TRP: add img[alt] node accessor so TranslatePress translates image alt attributes
 *  3. TRP: translate RSS <link> feed titles (blogname + the_title dict lookup)
 *  4. Button href re-link (option a, Wilson approved 2026-08-05): TRP Free strips the
 *     <a> wrapper from Elementor buttons, leaving a dead translation-block div.
 *     This mu-plugin re-attaches the original href + classes + target via JS,
 *     keyed on the Elementor widget data-id (which survives translation).
 * Author: CTO
 * Version: 1.10
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ========== 0. TRP: translate img[alt] attributes ==========
add_filter( 'trp_node_accessors', 'ac_trp_add_alt_accessor', 10, 1 );
function ac_trp_add_alt_accessor( $accessors ) {
    if ( ! isset( $accessors['image_alt'] ) ) {
        $accessors['image_alt'] = array(
            'selector'  => 'img[alt]',
            'accessor'  => 'alt',
            'attribute' => true,
        );
    }
    return $accessors;
}

// ========== 1. TRP: translate RSS <link> feed titles ==========
add_action( 'wp_head', 'ac_trp_feed_title_filters', 1 );
function ac_trp_feed_title_filters() {
    global $TRP_LANGUAGE;
    if ( empty( $TRP_LANGUAGE ) || $TRP_LANGUAGE !== 'en_US' ) return;
    add_filter( 'option_blogname', function( $name ) { return 'AngelCare'; }, 99 );
    add_filter( 'the_title', function( $title ) {
        global $wpdb;
        if ( empty( $title ) ) return $title;
        $t = trim( wp_strip_all_tags( $title ) );
        if ( $t === '' ) return $title;
        if ( ! preg_match( '/[\x{4e00}-\x{9fff}\x{3400}-\x{4dbf}]/u', $t ) ) return $title;
        $row = $wpdb->get_var( $wpdb->prepare(
            "SELECT translated FROM {$wpdb->prefix}trp_dictionary_zh_hant_en_us WHERE original = %s AND status = 2 LIMIT 1",
            $t
        ) );
        return $row ? $row : $title;
    }, 99 );
}

// ========== 2. Button href re-link (option a) ==========
// Map: Elementor widget data-id → original {href, class, target}
function ac_btn_relink_map() {
    return array(
        '03e12a2' => array( 'href' => 'https://apps.apple.com/hk/app/%E5%AE%85%E5%A4%A9%E4%BD%BF-angelcare/id6473672676?l=en-GB', 'class' => 'elementor-button elementor-button-link elementor-size-sm elementor-animation-float', 'target' => '_blank' ),
        '10e0819' => array( 'href' => 'https://angelcare.dnow.hk/en/angelcare-landingpage/', 'class' => 'elementor-button elementor-button-link elementor-size-sm elementor-animation-float', 'target' => '_blank' ),
        '19b514e' => array( 'href' => 'https://angelcare.dnow.hk/en/?cff-form=6', 'class' => 'elementor-button elementor-button-link elementor-size-sm', 'target' => '' ),
        '204d851d' => array( 'href' => 'https://apps.apple.com/hk/app/%E5%AE%85%E5%A4%A9%E4%BD%BF-angelcare/id6473672676?l=en-GB', 'class' => 'elementor-button elementor-button-link elementor-size-sm elementor-animation-float', 'target' => '_blank' ),
        '28edfa20' => array( 'href' => 'https://apps.apple.com/hk/app/%E5%AE%85%E5%A4%A9%E4%BD%BF-angelcare/id6473672676?l=en-GB', 'class' => 'elementor-button elementor-button-link elementor-size-sm', 'target' => '' ),
        '2c860e0' => array( 'href' => '#', 'class' => 'elementor-button elementor-button-link elementor-size-sm', 'target' => '' ),
        '315acd2a' => array( 'href' => 'https://easycare.dnow.hk/?page_id=261', 'class' => 'elementor-button elementor-button-link elementor-size-sm', 'target' => '' ),
        '31e0b516' => array( 'href' => 'https://play.google.com/store/apps/details?id=com.doctornow.easycare', 'class' => 'elementor-button elementor-button-link elementor-size-sm elementor-animation-float', 'target' => '_blank' ),
        '353e11e6' => array( 'href' => 'https://apps.apple.com/hk/app/%E5%AE%85%E5%A4%A9%E4%BD%BF-angelcare/id6473672676?l=en-GB', 'class' => 'elementor-button elementor-button-link elementor-size-sm elementor-animation-float', 'target' => '_blank' ),
        '3a99b8c' => array( 'href' => 'https://angelcare.dnow.hk/en/%e8%83%8c%e6%99%af%e6%95%85%e4%ba%8b/', 'class' => 'elementor-button elementor-button-link elementor-size-sm', 'target' => '' ),
        '3bcb022' => array( 'href' => 'https://angelcare.dnow.hk/en/?cff-form=9', 'class' => 'elementor-button elementor-button-link elementor-size-sm', 'target' => '' ),
        '3ce76101' => array( 'href' => 'https://play.google.com/store/apps/details?id=com.doctornow.easycare', 'class' => 'elementor-button elementor-button-link elementor-size-sm elementor-animation-float', 'target' => '_blank' ),
        '5326fbd' => array( 'href' => 'https://play.google.com/store/apps/details?id=com.doctornow.easycare', 'class' => 'elementor-button elementor-button-link elementor-size-sm elementor-animation-float', 'target' => '_blank' ),
        '58e4875e' => array( 'href' => 'https://apps.apple.com/hk/app/%E5%AE%85%E5%A4%A9%E4%BD%BF-angelcare/id6473672676?l=en-GB', 'class' => 'elementor-button elementor-button-link elementor-size-sm elementor-animation-float', 'target' => '_blank' ),
        '671fe5ce' => array( 'href' => 'https://play.google.com/store/apps/details?id=com.doctornow.easycare', 'class' => 'elementor-button elementor-button-link elementor-size-sm elementor-animation-float', 'target' => '_blank' ),
        '77434c9' => array( 'href' => '#', 'class' => 'elementor-button elementor-button-link elementor-size-sm', 'target' => '' ),
        '7a361cd' => array( 'href' => 'https://angelcare.dnow.hk/en/download', 'class' => 'elementor-button elementor-button-link elementor-size-sm elementor-animation-float', 'target' => '_blank' ),
        '8fbbca4' => array( 'href' => 'https://angelcare.dnow.hk/en/?cff-form=8', 'class' => 'elementor-button elementor-button-link elementor-size-sm', 'target' => '' ),
        '9dd9402' => array( 'href' => 'https://angelcare.dnow.hk/en/?cff-form=10', 'class' => 'elementor-button elementor-button-link elementor-size-sm', 'target' => '' ),
        'd80fcbc' => array( 'href' => 'https://angelcare.dnow.hk/en/%e5%be%9e%e5%b1%85%e5%ae%b6%e5%ae%89%e8%80%81%e5%88%b0%e9%99%a2%e8%88%8d%e5%ae%89%e8%80%81%ef%bc%8c%e5%85%a8%e9%9d%a2%e4%ba%86%e8%a7%a3%e9%95%b7%e8%80%85%e7%85%a7%e8%ad%b7%e9%81%b8%e6%93%87/', 'class' => 'elementor-button elementor-button-link elementor-size-sm elementor-animation-float', 'target' => '_blank' ),
        'da78fb7' => array( 'href' => 'https://angelcare.dnow.hk/en/?cff-form=7', 'class' => 'elementor-button elementor-button-link elementor-size-sm', 'target' => '' ),
        'e4f9c22' => array( 'href' => '#', 'class' => 'elementor-button elementor-button-link elementor-size-sm', 'target' => '' ),    );
}

add_action( 'wp_footer', 'ac_btn_relink_js', 99 );
function ac_btn_relink_js() {
    global $TRP_LANGUAGE;
    if ( empty( $TRP_LANGUAGE ) || $TRP_LANGUAGE !== 'en_US' ) return;
    $map = ac_btn_relink_map();
    if ( empty( $map ) ) return;
    ?>
<script id="ac-btn-relink">
(function () {
    var MAP = <?php echo json_encode( $map, JSON_UNESCAPED_SLASHES ); ?>;
    var MARK = 'data-ac-relinked';
    function relink() {
        document.querySelectorAll('.elementor-widget-button .elementor-button-wrapper.translation-block').forEach(function (wrapper) {
            // Never process twice (TRP dynamic translator may re-create wrappers)
            if (wrapper.getAttribute(MARK)) return;
            var widget = wrapper.closest('.elementor-widget-button');
            if (!widget) return;
            var id = widget.getAttribute('data-id');
            if (!id || !MAP[id]) return;
            if (wrapper.querySelector('a[href]')) return;
            // Skip empty wrappers (avoids dead <a></a> in race conditions)
            var text = (wrapper.textContent || '').replace(/\s+/g, ' ').trim();
            if (!text) { wrapper.setAttribute(MARK, '1'); return; }
            var info = MAP[id];
            var a = document.createElement('a');
            a.href = info.href;
            if (info.class) a.className = info.class;
            if (info.target) a.target = info.target;
            if (info.target === '_blank') a.rel = 'noopener noreferrer';
            // Rebuild full Elementor button structure (icon + content-wrapper + text)
            var wrap = document.createElement('span');
            wrap.className = 'elementor-button-content-wrapper';
            var txt = document.createElement('span');
            txt.className = 'elementor-button-text';
            txt.textContent = text;
            wrap.appendChild(txt);
            a.appendChild(wrap);
            wrapper.textContent = '';
            wrapper.classList.remove('translation-block');
            wrapper.appendChild(a);
            wrapper.setAttribute(MARK, '1');
        });
    }


    function boot() {
        relink();
        // TRP dynamic translator + Elementor re-render can re-wrap text at any time.
        // Watch the DOM and re-link anything new, debounced.
        var timer = null;
        var observer = new MutationObserver(function () {
            if (timer) clearTimeout(timer);
            timer = setTimeout(relink, 150);
        });
        observer.observe(document.documentElement, { childList: true, subtree: true, attributes: true, attributeFilter: ['class'] });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
</script>
    <?php
}


// ========== 3. CSS (spacing + fallback button visual) ==========
add_action( 'wp_head', 'ac_spacing_fix_css', 99 );

function ac_spacing_fix_css() {
    echo '<style id="ac-spacing-fix">' . "\n";

    // ========== 1. Kill mobile justify on David Wong quote (widget 78193198) ==========
    echo '@media (max-width: 767px) {' . "\n";
    echo '  .elementor-element-78193198,'
       . '.elementor-element-78193198 .elementor-widget-container,'
       . '.elementor-element-78193198 .wp-block-group,'
       . '.elementor-element-78193198 .wp-block-group p,'
       . '.elementor-element-78193198 p { text-align: left !important; }' . "\n";
    echo '}' . "\n";

    echo '.elementor-element-78193198 .wp-block-group p,'
       . '.elementor-element-78193198 .wp-block-group,'
       . '.elementor-element-78193198 .elementor-widget-container { text-align: left !important; }' . "\n";
    echo '.elementor-element-78193198 .wp-block-group { justify-content: flex-start !important; align-items: flex-start !important; }' . "\n";

    // ========== 2. Normalize vertical spacing in David Wong block ==========
    echo '.elementor-element-891a295 .elementor-icon-box-title { margin-bottom: 4px !important; }' . "\n";
    echo '.elementor-element-891a295 { margin-bottom: 24px !important; }' . "\n";
    echo '.elementor-element-78193198 .wp-block-group { margin: 0 !important; padding: 0 !important; }' . "\n";
    echo '.elementor-element-78193198 { margin-bottom: 16px !important; }' . "\n";
    echo '.elementor-element-3a99b8c .elementor-widget-container { margin: 0 !important; }' . "\n";

    // ========== 3. Fallback button look (only for buttons JS hasn't re-linked) ==========
    echo '.elementor-widget-button .elementor-button-wrapper.translation-block {'
       . 'display: inline-block; padding: 15px 40px; border-radius: 30px;'
       . 'background-color: #1E8A8A; color: #FFFFFF; font-family: Roboto, sans-serif;'
       . 'font-size: 20px; font-weight: 600; line-height: 1.5; text-align: center;'
       . 'cursor: pointer; transition: all 0.3s; }' . "\n";
    echo '.elementor-widget-button .elementor-button-wrapper.translation-block:hover {'
       . 'background-color: #359a9a; }' . "\n";


    // ========== 10. Nav menu fixes (Wilson 2026-08-05) ==========
    // 10a. (v1.9) 7561b1b RE-ENABLED per Wilson spec 2026-08-06: desktop homepage needs the
    //      top-left hamburger AND the menu bar below the hero - both. Do not hide either.
    //      (Was display:none in v1.7/v1.8; kept commented for history.)

    // 10b. Force explicit horizontal nav item spacing (desktop nav 36261f8 + mobile nav c5741a7)
    //      so items can never concatenate regardless of Elementor CSS variable state.
    echo '.elementor-element-36261f8 .elementor-nav-menu--main .elementor-nav-menu > li,'
       . '.elementor-element-c5741a7 .elementor-nav-menu--main .elementor-nav-menu > li {'
       . 'margin-left: 0 !important; margin-right: 0 !important; }' . "\n";
    echo '.elementor-element-36261f8 .elementor-nav-menu--main .elementor-nav-menu > li > a,'
       . '.elementor-element-c5741a7 .elementor-nav-menu--main .elementor-nav-menu > li > a {'
       . 'margin-right: 0 !important; margin-left: 0 !important; }' . "\n";
    echo '.elementor-element-36261f8 .elementor-nav-menu--main .elementor-nav-menu,'
       . '.elementor-element-c5741a7 .elementor-nav-menu--main .elementor-nav-menu {'
       . 'display: flex !important; flex-wrap: wrap !important; align-items: center; gap: 30px !important; }' . "\n";
    // Ensure the toggle is hidden on desktop for the dropdown-mobile navs
    echo '@media (min-width: 768px) {' . "\n";
    echo '  .elementor-element-36261f8 .elementor-menu-toggle,'
       . '.elementor-element-c5741a7 .elementor-menu-toggle { display: none !important; }' . "\n";
    echo '  .elementor-element-36261f8 .elementor-nav-menu--main,'
       . '.elementor-element-c5741a7 .elementor-nav-menu--main { display: block !important; }' . "\n";
    echo '}' . "\n";


    // ========== 11. Language toggle (EN/中) + desktop nav visibility (v1.8) ==========
    // 11a. (v1.9) REMOVED the v1.8 pinned-nav rule per Wilson spec: desktop homepage keeps the
    //      menu bar BELOW the hero section in normal flow (hamburger top-left is widget 7561b1b).
    // 11b. Hide the old TRP shortcode language switcher (mobile header widget on main page)
    echo '.elementor-element-trp-ls-widget { display: none !important; }' . "\n";
    // 11c. New EN/中 toggle styles (desktop nav + mobile drawer)
    echo '.ac-ls-item { display: flex; align-items: center; gap: 4px; margin: 0 6px; }' . "\n";
    echo '.ac-ls-item .ac-ls-link { display: inline-flex; align-items: center; justify-content: center;'
       . 'min-width: 30px; padding: 4px 11px; border-radius: 14px; font-size: 13px; font-weight: 700;'
       . 'color: #143852; border: 1.5px solid #1E8A8A; background: #fff; text-decoration: none; line-height: 1.4;'
       . 'transition: all .2s; }' . "\n";
    echo '.ac-ls-item .ac-ls-link:hover { background: #e6f6f6; }' . "\n";
    echo '.ac-ls-item .ac-ls-link.ac-ls-active { background: #1E8A8A; color: #fff; border-color: #1E8A8A; }' . "\n";
    echo '.ac-ls-item .ac-ls-sep { color: #c9c9c9; font-size: 12px; }' . "\n";
    // Mobile drawer variant
    echo '.elementor-nav-menu--dropdown .ac-ls-item { padding: 10px 14px; border-top: 1px solid #ececec; margin-top: 6px; }' . "\n";

    echo '</style>' . "\n";
}


// ========== 12. EN/中 toggle — replaces TRP floating + header switchers (v1.8) ==========

// 12a. Suppress the TRP floating bottom language switcher entirely (all pages)
add_filter( 'trp_floater_ls_html_v2', function () { return ''; }, 999 );

// 12b. Build toggle URLs + inject EN/中 into nav menus
add_action( 'wp_footer', 'ac_lang_toggle_inject', 120 );
function ac_lang_toggle_inject() {
    if ( ! class_exists( 'TRP_Translate_Press' ) ) return;
    global $TRP_LANGUAGE;
    $current_lang = ! empty( $TRP_LANGUAGE ) ? $TRP_LANGUAGE : 'zh_Hant';
    try {
        $trp    = TRP_Translate_Press::get_trp_instance();
        $conv   = $trp->get_component( 'url_converter' );
        $cur    = $conv->cur_page_url();
        $en_url = str_replace( '#TRPLINKPROCESSED', '', $conv->get_url_for_language( 'en_US', $cur ) );
        $zh_url = str_replace( '#TRPLINKPROCESSED', '', $conv->get_url_for_language( 'zh_Hant', $cur ) );
    } catch ( Throwable $e ) {
        return;
    }
    $data = array(
        'en'  => esc_url( $en_url ),
        'zh'  => esc_url( $zh_url ),
        'cur' => $current_lang,
    );
    ?>
<script id="ac-lang-toggle">
(function () {
    var D = <?php echo json_encode( $data ); ?>;
    var MARK = 'data-ac-ls-ready';
    function buildItem() {
        var zhActive = ( D.cur !== 'en_US' ) ? ' ac-ls-active' : '';
        var enActive = ( D.cur === 'en_US' ) ? ' ac-ls-active' : '';
        var li = document.createElement('li');
        li.className = 'menu-item ac-ls-item';
        li.setAttribute( MARK, '1' );
        li.setAttribute( 'data-no-translation', '' );
        li.innerHTML =
            '<a class="ac-ls-link ac-ls-zh' + zhActive + '" href="' + D.zh + '" data-no-translation>中</a>' +
            '<span class="ac-ls-sep" data-no-translation>/</span>' +
            '<a class="ac-ls-link ac-ls-en' + enActive + '" href="' + D.en + '" data-no-translation>EN</a>';
        return li;
    }
    // Target navs: main desktop nav (36261f8), main mobile drawer (c5741a7),
    // inner desktop nav + drawer (7ccfb07a)
    // v1.9: menu-2-7561b1b = desktop homepage hamburger drawer (re-enabled widget)
    var TARGETS = [ 'menu-1-36261f8', 'menu-2-c5741a7', 'menu-1-7ccfb07a', 'menu-2-7ccfb07a', 'menu-2-7561b1b' ];
    function inject() {
        TARGETS.forEach( function ( id ) {
            var ul = document.getElementById( id );
            if ( ! ul ) return;
            if ( ul.querySelector( '.ac-ls-item' ) ) return;
            ul.appendChild( buildItem() );
        } );
    }
    function boot() {
        inject();
        var timer = null;
        var obs = new MutationObserver( function () {
            if ( timer ) clearTimeout( timer );
            timer = setTimeout( inject, 150 );
        } );
        obs.observe( document.documentElement, { childList: true, subtree: true } );
    }
    if ( document.readyState === 'loading' ) {
        document.addEventListener( 'DOMContentLoaded', boot );
    } else {
        boot();
    }
})();
</script>
    <?php
}


// ========== 13. Carousel arrows outside frame (全新升級 template 1087) ==========
add_action( 'wp_head', 'ac_carousel_arrows_css', 120 );
function ac_carousel_arrows_css() {
    // Only inject on the 全新升級 page (or anywhere the widget renders — harmless)
    ?>
<style id="ac-carousel-arrows">
    /* Scope to the image-carousel widget on the 全新升級 template */
    .elementor-element-4cd441d .elementor-image-carousel-wrapper.swiper {
        position: relative;
        padding: 0 0 0 0;
    }

    /* Arrows: outside the frame, vertically centered, teal circle + white chevron */
    .elementor-element-4cd441d .elementor-swiper-button {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        width: 46px;
        height: 46px;
        background: #1E8A8A;               /* accent teal */
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 8px rgba(0,0,0,0.18);
        transition: background 0.2s ease, transform 0.2s ease;
        z-index: 5;
    }
    .elementor-element-4cd441d .elementor-swiper-button svg {
        width: 20px;
        height: 20px;
        fill: #ffffff;                     /* white chevron */
    }
    .elementor-element-4cd441d .elementor-swiper-button:hover {
        background: #2E8B8B;               /* darker teal hover */
        transform: translateY(-50%) scale(1.06);
    }

    /* Place prev/next OUTSIDE the image frame, flanking it */
    .elementor-element-4cd441d .elementor-swiper-button-prev {
        left: -58px;
    }
    .elementor-element-4cd441d .elementor-swiper-button-next {
        right: -58px;
    }

    /* Mobile: arrows at the image frame edges (inside frame, clearly visible) */
    @media (max-width: 767px) {
        .elementor-element-4cd441d .elementor-swiper-button-prev { left: 8px; }
        .elementor-element-4cd441d .elementor-swiper-button-next { right: 8px; }
        .elementor-element-4cd441d .elementor-swiper-button {
            width: 38px;
            height: 38px;
        }
        .elementor-element-4cd441d .elementor-swiper-button svg {
            width: 16px;
            height: 16px;
        }
    }
</style>
    <?php
}

// ========== 14. Pricing page: center 服務流程 + FAQ content (zoom-out fix) ==========
add_action( 'wp_head', 'ac_pricing_center_css', 121 );
function ac_pricing_center_css() {
    ?>
<style id="ac-pricing-center">
    /* Container 23f2d028 (服務流程 + 收費常見問題): constrain + center content so it
       doesn't hug the left edge at small zoom levels. */
    .elementor-element-23f2d028 > .e-con-inner {
        max-width: 900px !important;
        margin: 0 auto !important;
        width: 100% !important;
    }
    /* Center the content widgets inside 23f2d028 by capping their width */
    .elementor-element-23f2d028 .elementor-element.elementor-widget-text-editor,
    .elementor-element-23f2d028 .elementor-element.elementor-widget-heading,
    .elementor-element-23f2d028 .elementor-element.elementor-widget-toggle {
        max-width: 860px !important;
        margin-left: auto !important;
        margin-right: auto !important;
        width: 100% !important;
    }
</style>
    <?php
}

// ========== 15. Footer: single-line layout polish (template 152, site-wide) ==========
add_action( 'wp_head', 'ac_footer_css', 122 );
function ac_footer_css() {
    ?>
<style id="ac-footer-line">
    /* Footer (container 52157e27): ensure all 3 items align on one line (desktop),
       and wrap gracefully + center on mobile */
    .elementor-element-52157e27.e-con-boxed > .e-con-inner {
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
    }
    .elementor-element-52157e27 .elementor-widget {
        align-self: center;
    }
    /* Mobile: center the wrapped items */
    @media (max-width: 767px) {
        .elementor-element-52157e27.e-con-boxed > .e-con-inner {
            justify-content: center;
            gap: 6px 16px;
        }
        .elementor-element-52157e27 .elementor-widget {
            max-width: 100%;
        }
        .elementor-element-52157e27 .elementor-widget-text-editor,
        .elementor-element-52157e27 .elementor-widget-heading {
            margin: 0 !important;
        }
    }
</style>
    <?php
}

// ========== 16. EN: Reviews widget card titles — TRP strips the .elementor-testimonial__name span ==========
// (2026-08-07, Wilson via CBO: EN 九大實用功能 section must match ZH teal text-box block color + font size)
// ZH title rule: .elementor-248 .elementor-element-015d86b .elementor-testimonial__name{color:var(--e-global-color-accent);font-size:30px;font-weight:600}
// TRP replaces the cite innerHTML with the translated string, dropping the __name span (verified: nameSpanCount=0 on EN,
// computed title = rgb(51,51,51) 14px vs ZH rgb(64,180,180) 30px). Restore styling on the cite itself.
add_action( 'wp_head', 'ac_en_reviews_title_css', 123 );
function ac_en_reviews_title_css() {
    ?>
<style id="ac-en-reviews-title">
    html[lang="en-US"] .elementor-248 .elementor-element-015d86b .elementor-testimonial__cite.translation-block {
        color: var( --e-global-color-accent );
        font-family: "Roboto", Sans-serif;
        font-size: 24px;
        font-weight: 600;
    }
</style>
    <?php
}

// ========== 17. EN: Restore toggle expand chevrons — TRP strips .elementor-toggle-icon markup ==========
// (2026-08-07, Wilson via CBO: EN Caregiver's Story toggles missing the ZH expand arrows)
// TRP replaces each .elementor-tab-title innerHTML with the translated string, dropping the icon span
// AND the .elementor-toggle-title label span (verified: EN tab-title = bare text, no icon; ZH has
// chevron-down/up SVGs fill #DB9C7B + 20px Inter label). Restore both on EN.
// Widgets whose ZH counterpart has chevrons (1:1 verified per page): home 248 (ddc70d5/1543f7e/7b69782),
// 功能介紹 (4b94b7ad), 常見問題 (13a2117), pricing-plan (0ba326e). 全新升級 e191226 has NO icons in ZH — excluded.
add_action( 'wp_footer', 'ac_en_toggle_icons_js', 100 );
function ac_en_toggle_icons_js() {
    global $TRP_LANGUAGE;
    if ( empty( $TRP_LANGUAGE ) || $TRP_LANGUAGE !== 'en_US' ) return;
    $widgets = array( 'ddc70d5', '1543f7e', '7b69782', '4b94b7ad', '13a2117', '0ba326e', '232aba9', 'afd621e' ); // + 背景故事 (483) chevrons (2026-08-07 wrap-fix rollout)
    $chev_down = '<svg class="e-font-icon-svg e-fas-chevron-down" viewBox="0 0 448 512" xmlns="http://www.w3.org/2000/svg"><path d="M207.029 381.476L12.686 187.132c-9.373-9.373-9.373-24.569 0-33.941l22.667-22.667c9.357-9.357 24.522-9.375 33.901-.04L224 284.505l154.745-154.021c9.379-9.335 24.544-9.317 33.901.04l22.667 22.667c9.373 9.373 9.373 24.569 0 33.941L240.971 381.476c-9.373 9.372-24.569 9.372-33.942 0z"></path></svg>';
    $chev_up = '<svg class="elementor-toggle-icon-opened e-font-icon-svg e-fas-chevron-up" viewBox="0 0 448 512" xmlns="http://www.w3.org/2000/svg"><path d="M240.971 130.524l194.343 194.343c9.373 9.373 9.373 24.569 0 33.941l-22.667 22.667c-9.357 9.357-24.522 9.375-33.901.04L224 227.495 69.255 381.516c-9.379 9.335-24.544 9.317-33.901-.04l-22.667-22.667c-9.373-9.373-9.373-24.569 0-33.941L207.03 130.525c9.372-9.373 24.568-9.373 33.941-.001z"></path></svg>';
    ?>
<script id="ac-toggle-icons">
(function () {
    var WIDGETS = <?php echo json_encode( $widgets ); ?>;
    var MARK = 'data-ac-toggle-fixed';
    var ICON = '<span class="elementor-toggle-icon elementor-toggle-icon-left" aria-hidden="true">' +
        '<span class="elementor-toggle-icon-closed">' + <?php echo json_encode( $chev_down ); ?> + '</span>' +
        '<span class="elementor-toggle-icon-opened">' + <?php echo json_encode( $chev_up ); ?> + '</span></span>';

    function fix() {
        document.querySelectorAll('.elementor-widget-toggle .elementor-tab-title.translation-block').forEach(function (title) {
            if (title.getAttribute(MARK)) return;
            var widget = title.closest('.elementor-widget-toggle');
            if (!widget) return;
            var id = widget.getAttribute('data-id');
            if (!id || WIDGETS.indexOf(id) === -1) return;
            var text = (title.textContent || '').replace(/\s+/g, ' ').trim();
            if (!text) { title.setAttribute(MARK, '1'); return; }
            var label = document.createElement('span');
            label.className = 'elementor-toggle-title';
            label.textContent = text;
            title.textContent = '';
            title.classList.remove('translation-block');
            title.insertAdjacentHTML('afterbegin', ICON);
            title.appendChild(label);
            title.setAttribute(MARK, '1');
        });
    }

    function boot() {
        fix();
        var timer = null;
        var observer = new MutationObserver(function () {
            if (timer) clearTimeout(timer);
            timer = setTimeout(fix, 150);
        });
        observer.observe(document.documentElement, { childList: true, subtree: true, attributes: true, attributeFilter: ['class'] });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
</script>
    <?php
}

// ========== 18. EN 教學指南: restore spaces lost between inline segments ==========
// (2026-08-07, Wilson: "familyAfter registration,waitthe" / "EmployerGo" / "SeePricingpage" run-together)
// ZH source has inline elements (red <span>, <a>) adjacent to text nodes with NO whitespace
// (fine for CJK). TRP translates each node separately and trims value edges (verified: dict
// row 1018 trailing space was stripped on render), so dict-space fixes do NOT work.
// Fix: output-buffer rewrite on the EN guide page only, inserting the missing spaces.
// TRP starts its own output buffer at `init` priority 0 — our buffer must start
// EARLIER (init -1) so it sits OUTSIDE TRP's and sees the TRANSLATED html.
// (A template_redirect buffer sees pre-translation content and never matches.)
add_action( 'init', 'ac_en_guide_spaces_ob', -1 );
function ac_en_guide_spaces_ob() {
    if ( is_admin() ) return;
    $uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';
    // EN guide page only (query not parsed this early, so gate by URL)
    if ( strpos( $uri, '/en/' ) === false ) return;
    if ( strpos( $uri, '%e6%95%99%e5%ad%b8%e6%8c%87%e5%8d%97' ) === false && strpos( $uri, '教學指南' ) === false ) return;
    ob_start( 'ac_en_guide_spaces_rewrite' );
}
function ac_en_guide_spaces_rewrite( $html ) {
    // Step 2 desc: Helpers/other family | After registration, | wait | the employer...
    $html = str_replace( '</span>After registration,<span', '</span> After registration, <span', $html );
    $html = str_replace( 'wait</span>the employer', 'wait</span> the employer', $html );
    // Step 3 desc: Employer | Go to the Setup List...
    $html = str_replace( '</span>Go to the Setup List', '</span> Go to the Setup List', $html );
    // Pricing link: See | Pricing | page.
    $html = str_replace( 'See<a', 'See <a', $html );
    $html = str_replace( '</a>page.', '</a> page.', $html );
    return $html;
}

// ========== 19. EN 廢用症候群: restore spaces + case in "Learn More <link> and <link> knowledge" ==========
// (2026-08-07, Wilson: "Learn Morebedsoresandjoint stiffnessknowledge" run-together)
// Same structural cause as §18: ZH source "了解更多<a>褥瘡</a>及<a>關節僵硬</a>知識" has inline links
// with no inter-node whitespace; TRP translates each node and trims dict-value spaces.
// Two occurrences on the page (bedsore/joint-stiffness + UTI/nappy-rash) — both fixed.
add_action( 'init', 'ac_en_disuse_spaces_ob', -1 );
function ac_en_disuse_spaces_ob() {
    if ( is_admin() ) return;
    $uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';
    if ( strpos( $uri, '/en/' ) === false ) return;
    if ( strpos( $uri, '%e5%bb%a2%e7%94%a8%e7%97%87%e5%80%99%e7%be%a4' ) === false && strpos( $uri, '廢用症候群' ) === false ) return;
    ob_start( 'ac_en_disuse_spaces_rewrite' );
}
function ac_en_disuse_spaces_rewrite( $html ) {
    // Occ 1: bedsore + joint stiffness links (case per Wilson: "Learn more Bedsore and Joint stiffness knowledge")
    $html = str_replace(
        'Learn More<a href="https://www.doctornowhome.com/%e9%95%b7%e6%9c%9f%e8%87%a5%e5%ba%8a%e5%bf%85%e6%9c%89%e5%a3%93%e7%98%a1',
        'Learn more <a href="https://www.doctornowhome.com/%e9%95%b7%e6%9c%9f%e8%87%a5%e5%ba%8a%e5%bf%85%e6%9c%89%e5%a3%93%e7%98%a1',
        $html );
    $html = str_replace( '">bedsores</a>and<a', '">Bedsore</a> and <a', $html );
    $html = str_replace( '">joint stiffness</a>knowledge', '">Joint stiffness</a> knowledge', $html );
    // Occ 2: UTI + nappy rash links (same run-together, spaces + case-consistency only)
    $html = str_replace(
        'Learn More<a href="https://www.doctornowhome.com/%e7%82%ba%e4%bb%80%e9%ba%bc%e9%95%b7%e8%80%85%e5%ae%b9%e6%98%93%e6%b3%8c%e5%b0%bf%e9%81%93%e6%84%9f%e6%9f%93%ef%bc%9f',
        'Learn more <a href="https://www.doctornowhome.com/%e7%82%ba%e4%bb%80%e9%ba%bc%e9%95%b7%e8%80%85%e5%ae%b9%e6%98%93%e6%b3%8c%e5%b0%bf%e9%81%93%e6%84%9f%e6%9f%93%ef%bc%9f',
        $html );
    $html = str_replace( '">Urinary tract infection</a>and<a', '">Urinary tract infection</a> and <a', $html );
    $html = str_replace( '">nappy rash</a>knowledge', '">nappy rash</a> knowledge', $html );
    return $html;
}

// ========== 21. Toggle title wrap alignment (home story boxes) ==========
// (2026-08-07, Wilson: wrapped 2nd line of "Afraid Of Doing Wrong, Dared Not Act — Family Worried"
// starts under the chevron instead of aligning with "Afraid". The chevron icon is float:left, so
// wrapped label lines return to the container left edge. Fix: flex layout on the tab-title so the
// label is a flex item and every wrapped line starts after the chevron column. Scoped to the 3 home
// story-box toggles (ddc70d5 / 1543f7e / 7b69782) — rollout to other pages pending Wilson approval.)
add_action( 'wp_head', 'ac_toggle_wrap_align_css', 124 );
function ac_toggle_wrap_align_css() {
    ?>
<style id="ac-toggle-wrap-align">
    /* Wilson-approved rollout 2026-08-07: ALL chevron collapsible boxes.
       Excluded by decision: 全新升級 (e191226) + Landing (3b5a0903/444f329) — no chevrons rendered. */
    .elementor-248 .elementor-element-1543f7e .elementor-tab-title,
    .elementor-248 .elementor-element-7b69782 .elementor-tab-title,
    .elementor-248 .elementor-element-ddc70d5 .elementor-tab-title,
    .elementor-290 .elementor-element-4b94b7ad .elementor-tab-title,
    .elementor-271 .elementor-element-13a2117 .elementor-tab-title,
    .elementor-1249 .elementor-element-0ba326e .elementor-tab-title,
    .elementor-483 .elementor-element-232aba9 .elementor-tab-title,
    .elementor-483 .elementor-element-afd621e .elementor-tab-title {
        display: flex;
        align-items: flex-start;
        gap: 7px;
    }
    .elementor-248 .elementor-element-1543f7e .elementor-toggle-icon,
    .elementor-248 .elementor-element-7b69782 .elementor-toggle-icon,
    .elementor-248 .elementor-element-ddc70d5 .elementor-toggle-icon,
    .elementor-290 .elementor-element-4b94b7ad .elementor-toggle-icon,
    .elementor-271 .elementor-element-13a2117 .elementor-toggle-icon,
    .elementor-1249 .elementor-element-0ba326e .elementor-toggle-icon,
    .elementor-483 .elementor-element-232aba9 .elementor-toggle-icon,
    .elementor-483 .elementor-element-afd621e .elementor-toggle-icon {
        float: none;
        margin: 3px 0 0 0;
    }
</style>
    <?php
}
