<?php
/**
 * Single template for CPT ac_blog (Option C × Warm Terracotta).
 * Hierarchy: breadcrumb → category chip → H1 → date/read-time/views → hero image → content
 *            → 返回列表 → 上一篇/下一篇 → emotional CTA → 相關文章 → footer
 * Floating: WhatsApp (bottom-left) + ↑ top (bottom-right, appears after 300px scroll).
 */
if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

while ( have_posts() ) :
	the_post();
	$cats  = wp_get_post_terms( get_the_ID(), 'ac_blog_cat' );
	$chip  = ! is_wp_error( $cats ) && $cats ? $cats[0]->name : '';
	$rt    = acblog_read_time( get_post_field( 'post_content', get_the_ID() ) );
	$views = (int) get_post_meta( get_the_ID(), 'ac_views', true );
	$img   = get_the_post_thumbnail_url( get_the_ID(), 'full' );
	$cta   = acblog_cta_url();
	$wa    = ACBLOG_WA;
	$blog  = home_url( '/blog/' );
	?>
	<article class="acblog-post">
		<header class="acblog-post-head">
			<nav class="acblog-breadcrumb"><a href="<?php echo esc_url( $blog ); ?>">← 返回文章列表</a></nav>
			<?php if ( $chip ) : ?><span class="acblog-chip"><?php echo esc_html( $chip ); ?></span><?php endif; ?>
			<h1 class="acblog-post-title"><?php the_title(); ?></h1>
			<div class="acblog-post-meta">
				<span><?php echo get_the_date( 'Y-m-d' ); ?></span>
				<span><?php echo $rt; ?> 分鐘閱讀</span>
				<span>👁 <?php echo number_format( $views ); ?></span>
			</div>
		</header>

		<?php if ( $img ) : ?>
			<img class="acblog-hero" src="<?php echo esc_url( $img ); ?>" alt="<?php the_title_attribute(); ?>">
		<?php else : ?>
			<div class="acblog-hero acblog-hero-ph" aria-hidden="true"></div>
		<?php endif; ?>

		<div class="acblog-post-content">
			<?php the_content(); ?>
		</div>

		<div class="acblog-postnav">
			<a class="acblog-backlink" href="<?php echo esc_url( $blog ); ?>">← 返回文章列表</a>
			<?php $pn = acblog_prev_next(); if ( $pn['prev'] || $pn['next'] ) : ?>
				<div class="acblog-pn">
					<?php if ( $pn['prev'] ) : ?>
						<a class="acblog-pn-card" href="<?php echo esc_url( get_permalink( $pn['prev'] ) ); ?>">
							<span class="acblog-pn-label">← 上一篇</span>
							<span class="acblog-pn-title"><?php echo esc_html( $pn['prev']->post_title ); ?></span>
						</a>
					<?php else : ?>
						<span class="acblog-pn-card acblog-pn-empty" aria-hidden="true"></span>
					<?php endif; ?>
					<?php if ( $pn['next'] ) : ?>
						<a class="acblog-pn-card acblog-pn-next" href="<?php echo esc_url( get_permalink( $pn['next'] ) ); ?>">
							<span class="acblog-pn-label">下一篇 →</span>
							<span class="acblog-pn-title"><?php echo esc_html( $pn['next']->post_title ); ?></span>
						</a>
					<?php else : ?>
						<span class="acblog-pn-card acblog-pn-empty" aria-hidden="true"></span>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>

		<?php if ( function_exists( 'ac_escalation_html' ) ) echo ac_escalation_html(); ?>

		<div class="acblog-ecta">
			<h5>照顧路上，你唔係一個人</h5>
			<p>需要專業醫護上門支援？老友宅醫團隊提供上門診症、護士評估、復康治療 — 全天候守護家中長者。</p>
			<div class="acblog-ecta-btns">
				<a class="acblog-btn-main" href="<?php echo esc_url( $cta ); ?>">了解更多 →</a>
				<a class="acblog-btn-wa" href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener">WhatsApp 我哋</a>
			</div>
			<div class="acblog-ecta-app">
				<span class="acblog-ecta-app-label">下載 AngelCare 宅天使 App</span>
				<a class="acblog-btn-app" id="acblog-app-main" href="https://apps.apple.com/hk/app/%E5%AE%85%E5%A4%A9%E4%BD%BF-angelcare/id6473672676?l=en-GB" target="_blank" rel="noopener">
					免費下載 APP
				</a>
				<div class="acblog-ecta-app-links" id="acblog-app-links">
					<span class="acblog-ecta-app-or">或</span>
					<a class="acblog-store-link" href="https://apps.apple.com/hk/app/%E5%AE%85%E5%A4%A9%E4%BD%BF-angelcare/id6473672676?l=en-GB" target="_blank" rel="noopener">App Store</a>
					<span class="acblog-ecta-app-sep">·</span>
					<a class="acblog-store-link" href="https://play.google.com/store/apps/details?id=com.doctornow.easycare" target="_blank" rel="noopener">Google Play</a>
				</div>
			</div>
		</div>

		<?php $related = acblog_related( 3 ); if ( $related ) : ?>
			<section class="acblog-related">
				<h2 class="acblog-related-title">相關文章</h2>
				<div class="acblog-related-grid">
					<?php foreach ( $related as $rp ) { echo acblog_card( $rp, true ); } ?>
				</div>
			</section>
		<?php endif; ?>
	</article>

	<a class="acblog-fab acblog-fab-wa" href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener" aria-label="WhatsApp 我哋">
		<svg viewBox="0 0 24 24" width="26" height="26" fill="#fff" aria-hidden="true"><path d="M19.05 4.91A9.816 9.816 0 0 0 12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01zm-7.01 15.24c-1.48 0-2.93-.4-4.2-1.15l-.3-.18-3.12.82.83-3.04-.2-.32a8.423 8.423 0 0 1-1.29-4.45c0-4.63 3.77-8.4 8.4-8.4 2.24 0 4.34.87 5.92 2.45a8.36 8.36 0 0 1 2.45 5.92c.01 4.63-3.76 8.4-8.39 8.4zm4.6-6.29c-.25-.13-1.49-.74-1.72-.82-.23-.08-.4-.13-.57.12-.17.25-.66.82-.81.99-.15.17-.3.19-.55.06-.25-.13-1.06-.39-2.02-1.25-.75-.67-1.25-1.5-1.4-1.75-.15-.25-.02-.38.11-.51.11-.11.25-.29.37-.44.12-.15.17-.25.25-.42.08-.17.04-.32-.02-.45-.06-.13-.57-1.38-.78-1.89-.21-.51-.42-.42-.57-.43-.15-.01-.32-.01-.49-.01-.17 0-.45.07-.68.32-.24.25-.9.88-.9 2.15 0 1.27.92 2.49 1.05 2.66.13.17 1.82 2.78 4.41 3.9.62.26 1.1.42 1.47.54.62.2 1.19.17 1.63.1.5-.08 1.49-.61 1.7-1.2.21-.59.21-1.1.15-1.2-.06-.11-.23-.17-.48-.3z"/></svg>
	</a>
	<button id="acblog-top" class="acblog-fab acblog-fab-top" aria-label="回到頂部">↑</button>
	<script>
	(function(){var b=document.getElementById('acblog-top');if(!b)return;function t(){if(window.scrollY>300){b.classList.add('show');}else{b.classList.remove('show');}}window.addEventListener('scroll',t,{passive:true});t();b.addEventListener('click',function(e){e.preventDefault();window.scrollTo({top:0,behavior:'smooth'});});})();
	(function(){var m=document.getElementById('acblog-app-main'),s=document.getElementById('acblog-app-links');if(!m||!s)return;var ua=navigator.userAgent||'',iOS=/iPhone|iPad|iPod/i.test(ua)||(navigator.platform==='MacIntel'&&navigator.maxTouchPoints>1),droid=/Android/i.test(ua);if(iOS){m.href='https://apps.apple.com/hk/app/%E5%AE%85%E5%A4%A9%E4%BD%BF-angelcare/id6473672676?l=en-GB';s.style.display='none';}else if(droid){m.href='https://play.google.com/store/apps/details?id=com.doctornow.easycare';s.style.display='none';}})();
	</script>
	<?php
endwhile;

get_footer();
