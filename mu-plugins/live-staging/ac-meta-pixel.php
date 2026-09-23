<?php
/**
 * Plugin Name: AC Meta Pixel (unified BM pixel)
 * Description: Meta Pixel 4144712838891867 ("doctornowhome 的像素" — canonical BM pixel, one-pixel
 *              design 2026-09-05) — base code + PageView on all pages, Lead on WhatsApp/tel contact
 *              clicks (wa.me / api.whatsapp.com / tel:), CompleteRegistration on assessment-wizard
 *              email submit (.acw-emailrow button). All events carry eventID with ac_ prefix for
 *              cross-brand dedup (dnh_/ac_/dnacpr_). Old pixel 1003655972074979 retained in Meta
 *              for historical audit (not deleted). Standard events only.
 * Author: CTO
 * Version: 2.0 (2026-09-05 unified pixel)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'AC_META_PIXEL_ID', '4144712838891867' );
define( 'AC_META_EVENT_PREFIX', 'ac_' );

/* ========== 1. Base code + PageView (wp_head) ========== */
add_action( 'wp_head', 'ac_meta_pixel_head', 0 );
function ac_meta_pixel_head() {
    if ( is_admin() ) return;
    $pid = AC_META_PIXEL_ID;
    ?>
<!-- Meta Pixel Code -->
<script>
!function(f,b,e,v,n,t,s)
{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};
if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];
s.parentNode.insertBefore(t,s)}(window, document,'script',
'https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '<?php echo esc_js( $pid ); ?>');
fbq('track', 'PageView');
</script>
<noscript><img height="1" width="1" style="display:none"
src="https://www.facebook.com/tr?id=<?php echo esc_attr( $pid ); ?>&ev=PageView&noscript=1"
/></noscript>
<!-- End Meta Pixel Code -->
    <?php
}

/* ========== 2. Standard events (wp_footer, delegated — works with TRP EN too) ========== */
add_action( 'wp_footer', 'ac_meta_pixel_events', 99 );
function ac_meta_pixel_events() {
    if ( is_admin() ) return;
    ?>
<script>
(function () {
  'use strict';
  if ( typeof window.fbq !== 'function' ) return;

  function makeEventId() {
    return '<?php echo AC_META_EVENT_PREFIX; ?>' + Date.now() + '_' + Math.random().toString(36).slice(2, 10);
  }

  function fire( ev, guardKey, el ) {
    if ( guardKey && el ) {
      if ( el.getAttribute( guardKey ) === '1' ) return;
      el.setAttribute( guardKey, '1' );
    }
    try {
      window.fbq( 'track', ev, { eventID: makeEventId() } );
    } catch ( e ) {}
  }

  /* Lead: any WhatsApp / tel contact link (pricing CTAs, FAB, landing, blog, wizard CTA) */
  document.addEventListener( 'click', function ( e ) {
    var a = e.target && e.target.closest ? e.target.closest( 'a[href*="wa.me"], a[href*="api.whatsapp.com"], a[href*="tel:"]' ) : null;
    if ( ! a ) return;
    fire( 'Lead', 'data-acp-lead', a );
  }, true );

  /* CompleteRegistration: assessment wizard email submit (發送 / Send) */
  document.addEventListener( 'click', function ( e ) {
    var btn = e.target && e.target.closest ? e.target.closest( '.acw-emailrow button.acw-primary' ) : null;
    if ( ! btn ) return;
    fire( 'CompleteRegistration', 'data-acp-reg', btn );
  }, true );
})();
</script>
    <?php
}
