<?php
/**
 * Plugin Name: AngelCare Blog (ac-blog)
 * Description: CPT ac_blog (文章) + taxonomy ac_blog_cat + [ac_blog_grid] / [ac_blog_recent] / [ac_highlight] + custom single template + per-post CTA.
 * Version: 1.0.0
 * Author: CTO
 *
 * Design: Option C × Warm Terracotta (Wilson spec 2026-08-05).
 * Palette scoped to .acblog-* only; global kit untouched.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'ACBLOG_VER', '1.0.0' );
define( 'ACBLOG_DIR', __DIR__ );
define( 'ACBLOG_WA', 'https://wa.me/85263324599?text=' . rawurlencode( '我想了解AngelCare服務' ) );
define( 'ACBLOG_DEFAULT_CTA', 'https://www.doctornowhome.com/?utm_source=angelcare&utm_medium=content&utm_campaign=blog' );

/* ============================================================
 * 1. CPT + taxonomy (+ seed 5 categories)
 * ============================================================ */
add_action( 'init', 'acblog_register', 5 );
function acblog_register() {
	register_post_type( 'ac_blog', array(
		'labels' => array(
			'name'          => '文章',
			'singular_name' => '文章',
			'add_new_item'  => '新增文章',
			'edit_item'     => '編輯文章',
			'view_item'     => '查看文章',
			'search_items'  => '搜尋文章',
			'not_found'     => '沒有文章',
		),
		'public'       => true,
		'show_in_rest' => true,
		'menu_icon'    => 'dashicons-welcome-write-blog',
		'menu_position'=> 25,
		'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail', 'author', 'revisions' ),
		'has_archive'  => false,
		'rewrite'      => array( 'slug' => 'blog', 'with_front' => false ),
	) );

	register_taxonomy( 'ac_blog_cat', 'ac_blog', array(
		'labels' => array(
			'name'          => '文章分類',
			'singular_name' => '文章分類',
			'add_new_item'  => '新增分類',
			'edit_item'     => '編輯分類',
		),
		'public'       => true,
		'hierarchical' => false,
		'show_in_rest' => true,
		'rewrite'      => array( 'slug' => 'blog-category', 'with_front' => false ),
	) );

	// Seed the 5 categories once.
	if ( ! get_option( 'acblog_terms_seeded' ) ) {
		foreach ( array( '日常照顧', '行動復健', '認知障礙', '醫療照護', '居家安全' ) as $t ) {
			if ( ! term_exists( $t, 'ac_blog_cat' ) ) {
				wp_insert_term( $t, 'ac_blog_cat' );
			}
		}
		update_option( 'acblog_terms_seeded', 1 );
	}
}

/* ============================================================
 * 2. Views counter (simple v1, front-end only, skips admins)
 * ============================================================ */
add_action( 'template_redirect', 'acblog_count_view' );
function acblog_count_view() {
	if ( is_singular( 'ac_blog' ) && ! current_user_can( 'manage_options' ) ) {
		$id = get_queried_object_id();
		update_post_meta( $id, 'ac_views', (int) get_post_meta( $id, 'ac_views', true ) + 1 );
	}
}

/* ============================================================
 * 3. Helpers: read time / snippet / CTA url
 * ============================================================ */
function acblog_read_time( $content = '' ) {
	if ( ! $content ) {
		$content = get_post_field( 'post_content', get_the_ID() );
	}
	$chars = mb_strlen( wp_strip_all_tags( $content ) );
	return max( 1, (int) ceil( $chars / 300 ) ); // CJK ~300 chars/min
}

function acblog_snippet( $content, $len = 90 ) {
	$text = trim( wp_strip_all_tags( $content ) );
	$text = preg_replace( '/\s+/u', ' ', $text );
	if ( mb_strlen( $text ) <= $len ) {
		return $text;
	}
	return mb_substr( $text, 0, $len ) . '…';
}

function acblog_cta_url( $post_id = 0 ) {
	$post_id = $post_id ?: get_the_ID();
	$url     = get_post_meta( $post_id, 'ac_cta_url', true );
	return $url ? $url : ACBLOG_DEFAULT_CTA;
}

/* Category-aware prev/next (same ac_blog_cat term; falls back to any post) */
function acblog_prev_next() {
	$id   = get_the_ID();
	$cats = wp_get_post_terms( $id, 'ac_blog_cat', array( 'fields' => 'ids' ) );
	$now  = get_the_date( 'Y-m-d H:i:s' );
	$base = array(
		'post_type'      => 'ac_blog',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'no_found_rows'  => true,
		'orderby'        => 'date',
	);
	$prev_args = $base + array( 'date_query' => array( array( 'before' => $now, 'inclusive' => false ) ), 'order' => 'DESC' );
	$next_args = $base + array( 'date_query' => array( array( 'after' => $now, 'inclusive' => false ) ), 'order' => 'ASC' );

	$prev = $next = null;
	if ( $cats && ! is_wp_error( $cats ) ) {
		$tq = array( array( 'taxonomy' => 'ac_blog_cat', 'field' => 'term_id', 'terms' => $cats ) );
		$p  = get_posts( $prev_args + array( 'tax_query' => $tq ) );
		if ( $p ) {
			$prev = $p[0];
		}
		$n = get_posts( $next_args + array( 'tax_query' => $tq ) );
		if ( $n ) {
			$next = $n[0];
		}
	}
	if ( ! $prev ) {
		$p = get_posts( $prev_args );
		if ( $p ) {
			$prev = $p[0];
		}
	}
	if ( ! $next ) {
		$n = get_posts( $next_args );
		if ( $n ) {
			$next = $n[0];
		}
	}
	return array( 'prev' => $prev, 'next' => $next );
}

/* Same-category related posts (fills with latest others if short) */
function acblog_related( $count = 3 ) {
	$id      = get_the_ID();
	$cats    = wp_get_post_terms( $id, 'ac_blog_cat', array( 'fields' => 'ids' ) );
	$related = array();
	if ( $cats && ! is_wp_error( $cats ) ) {
		$q = new WP_Query( array(
			'post_type'      => 'ac_blog',
			'post_status'    => 'publish',
			'posts_per_page' => $count,
			'post__not_in'   => array( $id ),
			'no_found_rows'  => true,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'tax_query'      => array( array( 'taxonomy' => 'ac_blog_cat', 'field' => 'term_id', 'terms' => $cats ) ),
		) );
		$related = $q->posts;
		wp_reset_postdata();
	}
	if ( count( $related ) < $count ) {
		$exclude = array_merge( array( $id ), wp_list_pluck( $related, 'ID' ) );
		$q2      = new WP_Query( array(
			'post_type'      => 'ac_blog',
			'post_status'    => 'publish',
			'posts_per_page' => $count - count( $related ),
			'post__not_in'   => $exclude,
			'no_found_rows'  => true,
			'orderby'        => 'date',
			'order'          => 'DESC',
		) );
		$related = array_merge( $related, $q2->posts );
		wp_reset_postdata();
	}
	return $related;
}

/* ============================================================
 * 4. Per-post CTA meta box
 * ============================================================ */
add_action( 'add_meta_boxes', function () {
	add_meta_box( 'acblog_cta', '文章底部 CTA（老友宅醫連結）', 'acblog_cta_box', 'ac_blog', 'normal', 'default' );
} );
function acblog_cta_box( $post ) {
	wp_nonce_field( 'acblog_cta_save', 'acblog_cta_nonce' );
	$url = get_post_meta( $post->ID, 'ac_cta_url', true );
	echo '<p><label for="acblog_cta_url"><strong>CTA 連結（內容相關）</strong></label></p>';
	echo '<p><input type="url" id="acblog_cta_url" name="acblog_cta_url" value="' . esc_attr( $url ) . '" style="width:100%;max-width:560px;" placeholder="https://www.doctornowhome.com/..."></p>';
	echo '<p style="color:#666;font-size:12px;">留空＝預設連到老友宅醫首頁（附 UTM: utm_source=angelcare&amp;utm_medium=content&amp;utm_campaign=blog）。可依文章內容填寫 doctornowhome.com 指定頁面／文章連結。WhatsApp 按鈕自動顯示，毋須填寫。</p>';
}
add_action( 'save_post_ac_blog', function ( $post_id ) {
	if ( ! isset( $_POST['acblog_cta_nonce'] ) || ! wp_verify_nonce( $_POST['acblog_cta_nonce'], 'acblog_cta_save' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( isset( $_POST['acblog_cta_url'] ) ) {
		$url = esc_url_raw( trim( $_POST['acblog_cta_url'] ) );
		if ( $url ) {
			update_post_meta( $post_id, 'ac_cta_url', $url );
		} else {
			delete_post_meta( $post_id, 'ac_cta_url' );
		}
	}
} );

/* ============================================================
 * 5. Shortcodes
 * ============================================================ */

// Sticky highlight box inside post content
add_shortcode( 'ac_highlight', function ( $atts, $content = '' ) {
	$content = trim( (string) $content );
	if ( ! $content ) {
		return '';
	}
	return '<div class="acblog-sticky"><div class="acblog-sticky-label">💡 照顧重點</div><div class="acblog-sticky-body">' . wp_kses_post( $content ) . '</div></div>';
} );

// Blog listing: tabs + card grid + pager + CTA strip
add_shortcode( 'ac_blog_grid', 'acblog_grid' );
function acblog_grid( $atts ) {
	$atts   = shortcode_atts( array( 'per_page' => 9 ), $atts, 'ac_blog_grid' );
	$cat    = isset( $_GET['ac_cat'] ) ? sanitize_title( $_GET['ac_cat'] ) : '';
	$paged  = isset( $_GET['ac_paged'] ) ? max( 1, (int) $_GET['ac_paged'] ) : 1;
	$page   = get_page_by_path( 'blog' );
	$base   = $page ? get_permalink( $page ) : home_url( '/blog/' );
	$filter = $cat ? add_query_arg( 'ac_cat', $cat, $base ) : $base;

	$args = array(
		'post_type'      => 'ac_blog',
		'post_status'    => 'publish',
		'posts_per_page' => (int) $atts['per_page'],
		'paged'          => $paged,
	);
	if ( $cat ) {
		$term = get_term_by( 'slug', $cat, 'ac_blog_cat' );
		if ( $term ) {
			$args['tax_query'] = array( array( 'taxonomy' => 'ac_blog_cat', 'field' => 'slug', 'terms' => $cat ) );
		}
	}
	$q = new WP_Query( $args );
	$terms = get_terms( array( 'taxonomy' => 'ac_blog_cat', 'hide_empty' => false ) );

	ob_start();
	?>
	<div class="acblog-wrap">
		<div class="acblog-tabs">
			<a class="acblog-tab<?php echo $cat ? '' : ' on'; ?>" href="<?php echo esc_url( $base ); ?>">全部</a>
			<?php foreach ( $terms as $t ) : ?>
				<a class="acblog-tab<?php echo $cat === $t->slug ? ' on' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'ac_cat', $t->slug, $base ) ); ?>"><?php echo esc_html( $t->name ); ?></a>
			<?php endforeach; ?>
		</div>

		<?php if ( $q->have_posts() ) : ?>
			<div class="acblog-grid">
				<?php while ( $q->have_posts() ) : $q->the_post(); echo acblog_card( get_post() ); endwhile; ?>
			</div>
			<?php if ( $q->max_num_pages > 1 ) : ?>
				<div class="acblog-pager">
					<?php if ( $paged > 1 ) : ?><a href="<?php echo esc_url( add_query_arg( 'ac_paged', $paged - 1, $filter ) ); ?>">← 上一頁</a><?php endif; ?>
					<span><?php echo $paged; ?> / <?php echo $q->max_num_pages; ?></span>
					<?php if ( $paged < $q->max_num_pages ) : ?><a href="<?php echo esc_url( add_query_arg( 'ac_paged', $paged + 1, $filter ) ); ?>">下一頁 →</a><?php endif; ?>
				</div>
			<?php endif; ?>
		<?php else : ?>
			<div class="acblog-empty">文章整理中，敬請期待 ✍️</div>
		<?php endif; wp_reset_postdata(); ?>

		<div class="acblog-hero-strip">
			<div>
				<h5>需要專業醫護上門支援？</h5>
				<p>老友宅醫提供上門診症、護士評估、復康治療 — 全天候守護家中長者。</p>
			</div>
			<a class="acblog-hero-btn" href="<?php echo esc_url( ACBLOG_DEFAULT_CTA ); ?>">了解更多 →</a>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

// Homepage: latest 3 posts + link to /blog/
add_shortcode( 'ac_blog_recent', 'acblog_recent' );
function acblog_recent( $atts ) {
	$q = new WP_Query( array(
		'post_type'      => 'ac_blog',
		'post_status'    => 'publish',
		'posts_per_page' => 3,
		'no_found_rows'  => true,
	) );
	$page = get_page_by_path( 'blog' );
	$base = $page ? get_permalink( $page ) : home_url( '/blog/' );
	ob_start();
	?>
	<section class="acblog-recent">
		<div class="acblog-recent-head">
			<h2>最新文章</h2>
			<a class="acblog-all" href="<?php echo esc_url( $base ); ?>">查看全部文章 →</a>
		</div>
		<?php if ( $q->have_posts() ) : ?>
			<div class="acblog-recent-grid">
				<?php while ( $q->have_posts() ) : $q->the_post(); echo acblog_card( get_post(), true ); endwhile; ?>
			</div>
		<?php else : ?>
			<div class="acblog-empty">文章整理中，敬請期待 ✍️</div>
		<?php endif; wp_reset_postdata(); ?>
	</section>
	<?php
	return ob_get_clean();
}

// Card markup shared by grid + recent
function acblog_card( $post, $mini = false ) {
	$img   = get_the_post_thumbnail_url( $post->ID, 'medium_large' );
	$cats  = wp_get_post_terms( $post->ID, 'ac_blog_cat' );
	$chip  = ! is_wp_error( $cats ) && $cats ? $cats[0]->name : '';
	$views = number_format( (int) get_post_meta( $post->ID, 'ac_views', true ) );
	$rt    = acblog_read_time( $post->post_content );
	$snip  = $post->post_excerpt ? $post->post_excerpt : acblog_snippet( $post->post_content );
	$url   = get_permalink( $post->ID );
	ob_start();
	?>
	<article class="acblog-card<?php echo $mini ? ' acblog-card-mini' : ''; ?>">
		<a class="acblog-card-img" href="<?php echo esc_url( $url ); ?>">
			<?php if ( $img ) : ?>
				<img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $post->post_title ); ?>">
			<?php else : ?>
				<span class="acblog-ph"></span>
			<?php endif; ?>
			<?php if ( $chip ) : ?><span class="acblog-chip"><?php echo esc_html( $chip ); ?></span><?php endif; ?>
		</a>
		<div class="acblog-card-body">
			<div class="acblog-meta"><span><?php echo get_the_date( 'Y-m-d', $post->ID ); ?></span><span><?php echo $rt; ?> 分鐘閱讀</span></div>
			<h3 class="acblog-title"><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $post->post_title ); ?></a></h3>
			<?php if ( $snip ) : ?><p class="acblog-snippet"><?php echo esc_html( $snip ); ?></p><?php endif; ?>
		</div>
		<div class="acblog-card-foot"><span>👁 <?php echo esc_html( $views ); ?></span><a class="acblog-readmore" href="<?php echo esc_url( $url ); ?>">閱讀全文 →</a></div>
	</article>
	<?php
	return ob_get_clean();
}

/* ============================================================
 * 6. Single post template (template_include)
 * ============================================================ */
add_filter( 'template_include', function ( $template ) {
	if ( is_singular( 'ac_blog' ) ) {
		$f = ACBLOG_DIR . '/templates/single-ac_blog.php';
		if ( file_exists( $f ) ) {
			return $f;
		}
	}
	return $template;
} );

/* ============================================================
 * 7. Homepage "最近文章" section (after Elementor Theme Builder single)
 * ============================================================ */
add_action( 'elementor/theme/after_do_single', 'acblog_home_recent_after' );
function acblog_home_recent_after() {
	static $done = false;
	if ( $done ) {
		return;
	}
	if ( is_front_page() || is_page( 258 ) ) {
		$done = true;
		echo do_shortcode( '[ac_blog_recent]' ); // WPCS: ok — section markup
	}
}

/* ============================================================
 * 8. Scoped CSS (enqueue only where blog renders)
 * ============================================================ */
add_action( 'wp_enqueue_scripts', 'acblog_maybe_enqueue' );

add_action( 'wp_head', function () {
	if ( is_singular( 'ac_blog' ) ) {
		echo '<meta name="apple-itunes-app" content="app-id=6473672676">' . "\n";
	}
}, 5 );

add_action( 'wp_footer', function () {
	// Smart app-download UA redirect for /download/ page (page 1256). Blog posts handle their own inline script.
	if ( is_page( array( 1255, 1256 ) ) ) {
		$appstore = 'https://apps.apple.com/hk/app/%E5%AE%85%E5%A4%A9%E4%BD%BF-angelcare/id6473672676?l=en-GB';
		$play     = 'https://play.google.com/store/apps/details?id=com.doctornow.easycare';
		echo '<script>' . "\n"
			. "(function(){var m=document.getElementById('acblog-app-main'),s=document.getElementById('acblog-app-links');if(!m||!s)return;var ua=navigator.userAgent||'',iOS=/iPhone|iPad|iPod/i.test(ua)||(navigator.platform==='MacIntel'&&navigator.maxTouchPoints>1),droid=/Android/i.test(ua);if(iOS){m.href='$appstore';s.style.display='none';}else if(droid){m.href='$play';s.style.display='none';}})();" . "\n"
			. '</script>' . "\n";
	}
}, 99 );
function acblog_maybe_enqueue() {
	$on = is_singular( 'ac_blog' ) || is_page( 'blog' ) || is_front_page() || is_page( 258 ) || is_page( array( 1255, 1256 ) );
	if ( ! $on ) {
		$p = get_queried_object();
		if ( $p instanceof WP_Post && has_shortcode( $p->post_content, 'ac_blog_grid' ) ) {
			$on = true;
		}
	}
	if ( $on ) {
		wp_register_style( 'acblog', false, array(), ACBLOG_VER );
		wp_enqueue_style( 'acblog' );
		wp_add_inline_style( 'acblog', acblog_css() );
	}
}

function acblog_css() {
	return <<<'CSS'
.acblog-wrap{max-width:1120px;margin:0 auto;padding:44px 24px 64px;background:#FFFDFA;color:#675451;font-family:"Noto Sans CJK HK","Noto Sans TC","PingFang HK","Microsoft JhengHei",sans-serif}
.acblog-tabs{display:flex;flex-wrap:wrap;gap:9px;margin-bottom:30px}
.acblog-tab{display:inline-block;padding:8px 20px;border-radius:999px;border:1px solid #E0D5C9;background:#fff;color:#675451;font-size:14px;font-weight:600;text-decoration:none;line-height:1.4}
.acblog-tab:hover{border-color:#C4775A;color:#C4775A}
.acblog-tab.on{background:#C4775A;border-color:#C4775A;color:#fff}
.acblog-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:24px}
.acblog-card{display:flex;flex-direction:column;background:#fff;border:1px solid #EEE6DC;border-radius:12px;overflow:hidden;transition:box-shadow .2s ease}
.acblog-card:hover{box-shadow:0 6px 18px rgba(101,84,81,.12)}
.acblog-card-img{position:relative;display:block;aspect-ratio:16/9;overflow:hidden;background:linear-gradient(135deg,#F3D9C4,#E8A585)}
.acblog-card-img img{width:100%;height:100%;object-fit:cover}
.acblog-ph{position:absolute;inset:0;background:linear-gradient(135deg,#F3D9C4,#E8A585)}
.acblog-chip{position:absolute;top:10px;left:10px;background:rgba(255,255,255,.95);color:#C4775A;font-size:11px;font-weight:700;padding:3px 10px;border-radius:999px}
.acblog-card-body{padding:14px 16px 10px;display:flex;flex-direction:column;gap:6px;flex:1}
.acblog-meta{display:flex;gap:12px;color:#9A8F84;font-size:12px}
.acblog-title{margin:0;font-size:17px;font-weight:800;color:#3D3733;line-height:1.45}
.acblog-title a{color:inherit;text-decoration:none}
.acblog-title a:hover{color:#C4775A}
.acblog-snippet{margin:0;color:#7A7068;font-size:13px;line-height:1.65}
.acblog-card-foot{display:flex;justify-content:space-between;align-items:center;padding:10px 16px;border-top:1px solid #F5EFE7;font-size:12px;color:#9A8F84}
.acblog-card-foot .acblog-readmore{color:#E56B6B;font-weight:700;text-decoration:none}
.acblog-hero-strip{margin-top:36px;display:flex;align-items:center;justify-content:space-between;gap:18px;background:linear-gradient(120deg,#C4775A,#E8A585);border-radius:14px;padding:24px 30px;color:#fff}
.acblog-hero-strip h5{margin:0 0 4px;font-size:17px;font-weight:800}
.acblog-hero-strip p{margin:0;font-size:13px;opacity:.94;line-height:1.6}
.acblog-hero-strip .acblog-hero-btn{flex:none;display:inline-block;background:#fff;color:#C4775A;font-weight:800;font-size:13px;padding:10px 24px;border-radius:8px;text-decoration:none}
.acblog-pager{display:flex;justify-content:center;align-items:center;gap:8px;margin-top:30px}
.acblog-pager a,.acblog-pager span{display:inline-block;padding:7px 14px;border-radius:999px;border:1px solid #E0D5C9;color:#675451;text-decoration:none;font-size:13px}
.acblog-empty{padding:48px 0;text-align:center;color:#9A8F84;font-size:15px}
.acblog-recent{max-width:1120px;margin:0 auto;padding:48px 24px 60px;background:#FDF1E8;color:#675451;font-family:"Noto Sans CJK HK","Noto Sans TC","PingFang HK","Microsoft JhengHei",sans-serif}
.acblog-recent-head{display:flex;align-items:baseline;justify-content:space-between;margin-bottom:22px}
.acblog-recent-head h2{margin:0;font-size:22px;font-weight:800;color:#3D3733}
.acblog-recent-head .acblog-all{color:#4DB6B8;font-weight:700;font-size:14px;text-decoration:none}
.acblog-recent-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px}
.acblog-post{max-width:720px;margin:0 auto;padding:40px 24px 64px;color:#675451;font-family:"Noto Sans CJK HK","Noto Sans TC","PingFang HK","Microsoft JhengHei",sans-serif}
.acblog-post-head{margin-bottom:20px}
.acblog-post .acblog-chip{position:static;display:inline-block;background:#FDF1E8;margin-bottom:12px}
.acblog-post-title{margin:0 0 10px;font-size:30px;font-weight:800;color:#3D3733;line-height:1.4}
.acblog-post-meta{display:flex;gap:16px;color:#9A8F84;font-size:13px}
.acblog-hero{display:block;width:100%;aspect-ratio:16/9;object-fit:cover;border-radius:12px;margin:6px 0 26px}
.acblog-hero-ph{background:linear-gradient(135deg,#F3D9C4,#D9A878)}
.acblog-post-content{font-size:16px;line-height:1.95;color:#57504A}
.acblog-post-content p{margin:0 0 18px}
.acblog-sticky{background:#FDF1E8;border-left:4px solid #C4775A;border-radius:8px;padding:14px 18px;margin:22px 0}
.acblog-sticky-label{font-weight:800;color:#C4775A;margin-bottom:4px;font-size:14px}
.acblog-sticky-body{color:#6B6158;font-size:15px;line-height:1.7}
.acblog-ecta{margin-top:34px;background:linear-gradient(120deg,#C4775A,#E56B6B);border-radius:12px;padding:28px 26px;color:#fff;text-align:center}
.acblog-ecta h5{margin:0 0 6px;font-size:18px;font-weight:800}
.acblog-ecta p{margin:0 auto 16px;font-size:13px;opacity:.95;line-height:1.7;max-width:460px}
.acblog-ecta-btns{display:flex;justify-content:center;gap:12px;flex-wrap:wrap}
.acblog-btn-main{display:inline-block;background:#fff;color:#E56B6B;font-weight:800;font-size:14px;padding:11px 28px;border-radius:8px;text-decoration:none}
.acblog-btn-wa{display:inline-block;background:#25D366;color:#fff;border:2px solid #25D366;font-weight:700;font-size:14px;padding:9px 24px;border-radius:8px;text-decoration:none;box-shadow:0 2px 8px rgba(37,211,102,.35);transition:opacity .2s}
.acblog-btn-wa:hover{opacity:.9;color:#fff}
.acblog-ecta-app{margin-top:18px;padding-top:16px;border-top:1px solid rgba(255,255,255,.35)}
.acblog-ecta-app-label{display:block;color:#fff;font-size:13px;font-weight:600;margin-bottom:12px;letter-spacing:.3px}
.acblog-btn-app{display:inline-block;background:#32373C;color:#fff;font-weight:800;font-size:16px;padding:13px 36px;border-radius:9px;text-decoration:none;box-shadow:0 2px 12px rgba(0,0,0,.28);transition:opacity .2s,transform .15s}
.acblog-btn-app:hover{opacity:.92;color:#fff;transform:translateY(-1px)}
.acblog-btn-app svg{display:none}
.acblog-ecta-app-links{display:flex;justify-content:center;align-items:center;gap:8px;margin-top:12px;color:rgba(255,255,255,.85);font-size:13px}
.acblog-ecta-app-or{opacity:.8}
.acblog-ecta-app-sep{opacity:.5}
.acblog-store-link{color:#fff;text-decoration:underline;text-underline-offset:3px;font-weight:600;transition:opacity .2s}
.acblog-store-link:hover{opacity:.8;color:#fff}
/* /download/ page (1256): terracotta CTA style on Elementor button */
.page-id-1255 #acblog-app-main.elementor-button,.page-id-1256 #acblog-app-main.elementor-button{background:#32373C;color:#fff;font-weight:800;font-size:16px;padding:14px 40px;border-radius:9px;box-shadow:0 2px 12px rgba(0,0,0,.28);border:none}
.page-id-1255 #acblog-app-main.elementor-button:hover,.page-id-1256 #acblog-app-main.elementor-button:hover{opacity:.92;color:#fff}
.page-id-1255 .acblog-store-link,.page-id-1256 .acblog-store-link{color:#C4775A;text-decoration:underline;text-underline-offset:3px;font-weight:600}
.acblog-faq{background:#FDF1E8;border:1px solid #F2E1CE;border-radius:14px;padding:22px 26px;margin:28px 0}
.acblog-faq h2{margin:0 0 14px;padding-left:12px;border-left:4px solid #C4775A;font-size:20px;font-weight:800;color:#3D3733}
.acblog-faq h3{margin:16px 0 6px;font-size:16px;font-weight:800;color:#3D3733}
.acblog-qmark{display:inline-block;background:#C4775A;color:#fff;font-size:12px;font-weight:800;border-radius:6px;padding:2px 9px;margin-right:8px;vertical-align:2px}
.acblog-faq p{margin:0 0 12px;color:#6B6158;font-size:15px;line-height:1.8}
.acblog-faq p:last-child{margin-bottom:0}
/* CTA color variants — Wilson pick pending (default terracotta; .is-teal = 1-class swap) */
.acblog-hero-strip.is-teal{background:linear-gradient(120deg,#4DB6B8,#2E9A9C)}
.acblog-hero-strip.is-teal .acblog-hero-btn{color:#2E8B8D}
.acblog-ecta.is-teal{background:linear-gradient(120deg,#4DB6B8,#2E9A9C)}
.acblog-ecta.is-teal .acblog-btn-main{color:#2E8B8D}
/* Post nav: breadcrumb + prev/next + related + floating buttons */
.acblog-breadcrumb{margin-bottom:14px;font-size:13px}
.acblog-breadcrumb a{color:#4DB6B8;text-decoration:none;font-weight:600}
.acblog-breadcrumb a:hover{color:#2E8B8D}
.acblog-postnav{margin:26px 0 6px}
.acblog-backlink{display:inline-block;color:#4DB6B8;font-weight:700;font-size:14px;text-decoration:none;margin-bottom:16px}
.acblog-backlink:hover{color:#2E8B8D}
.acblog-pn{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.acblog-pn-card{display:flex;flex-direction:column;gap:4px;background:#FDF1E8;border:1px solid #F2E1CE;border-radius:10px;padding:12px 14px;text-decoration:none;transition:box-shadow .2s ease}
.acblog-pn-card:hover{box-shadow:0 4px 12px rgba(101,84,81,.12)}
.acblog-pn-next{text-align:right}
.acblog-pn-label{color:#C4775A;font-size:12px;font-weight:800}
.acblog-pn-title{color:#3D3733;font-size:14px;font-weight:700;line-height:1.45}
.acblog-pn-empty{visibility:hidden}
.acblog-related{margin-top:40px}
.acblog-related-title{margin:0 0 18px;font-size:22px;font-weight:800;color:#3D3733;padding-left:12px;border-left:4px solid #C4775A}
.acblog-related-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px}
.acblog-fab{position:fixed;z-index:999;width:52px;height:52px;border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 14px rgba(0,0,0,.18);border:none;cursor:pointer;text-decoration:none}
.acblog-fab-wa{bottom:24px;left:24px;background:#25D366;color:#fff}
.acblog-fab-wa:hover{filter:brightness(.95)}
.acblog-fab-top{bottom:24px;right:24px;background:#32373C;color:#fff;font-size:22px;font-weight:800;line-height:1;opacity:0;visibility:hidden;transform:translateY(8px);transition:opacity .25s ease,visibility .25s ease,transform .25s ease}
.acblog-fab-top.show{opacity:1;visibility:visible;transform:translateY(0)}
@media(max-width:767px){
.acblog-related-grid{grid-template-columns:1fr}
.acblog-pn{grid-template-columns:1fr}
.acblog-pn-empty{display:none}
.acblog-fab-wa{bottom:20px;left:20px}
.acblog-fab-top{bottom:20px;right:20px}
}
@media(max-width:767px){
.acblog-grid{grid-template-columns:1fr}
.acblog-recent-grid{grid-template-columns:1fr}
.acblog-hero-strip{flex-direction:column;align-items:flex-start}
.acblog-post-title{font-size:24px}
}
CSS;
}

/* ── Trust-ladder escalation block (AC → DNH) — 2026-09-22 Wilson-approved ─────────────
 * ac_escalation_html( $variant ) renders the 「情況轉差？」 cross-brand CTA.
 *   $variant 'card'   → full block (blog singles, key pages)
 *   $variant 'footer' → compact one-line strip (site footer, all pages)
 * UTM registry: utm_source=angelcare | utm_medium=content (in-content) / footer_nav (footer)
 *               utm_campaign=trust-ladder-<pillar>; pillars: doctor nurse palliative home-death home
 * Fail-open: returns '' on any error. Bilingual via trp_get_current_language().
 */
function ac_escalation_html( $variant = 'card' ) {
	// EN detection: TranslatePress serves EN under /en/ path. (trp_get_current_language()
	// is NOT defined on this build — verified 2026-09-22 — so REQUEST_URI is primary.)
	$en   = false;
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
	$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
	if ( preg_match( '#^/en(?:/|$)#', $path ) ) {
		$en = true;
	}
	if ( function_exists( 'trp_get_current_language' ) ) {
		$cur = trp_get_current_language();
		if ( is_string( $cur ) && '' !== $cur ) {
			$en = 0 === strpos( $cur, 'en' );
		}
	}
	$base = 'https://www.doctornowhome.com';
	$url  = function ( $path, $campaign, $medium = 'content' ) use ( $base ) {
		return esc_url( $base . $path . '?utm_source=angelcare&utm_medium=' . $medium . '&utm_campaign=' . $campaign );
	};
	$pillars = array(
		array( '/service/' . rawurlencode( '西醫上門' ) . '/', 'ladder-doctor', '西醫上門', 'House-call Doctor' ),
		array( '/service/' . rawurlencode( '護士上門' ) . '/', 'ladder-nurse', '護士上門評估', 'Nurse Home Visit' ),
		array( '/service/' . rawurlencode( '紓緩治療' ) . '/', 'ladder-palliative', '紓緩治療', 'Palliative Care' ),
		array( '/service/' . rawurlencode( '在家離世臨終服務【安辭在家、善終服務】' ) . '/', 'ladder-home-death', '在家離世安排', 'Passing at Home' ),
	);
	if ( 'footer' === $variant ) {
		$t = $en ? 'Condition worsening? DoctorNow 24/7 home-visit doctors & nurses' : '情況轉差？老友宅醫 24/7 上門醫護';
		$b = $en ? 'Explore DoctorNow →' : '了解老友宅醫 →';
		$u = $url( '/', 'trust-ladder-home', 'footer_nav' );
		return '<div class="acblog-ladder-strip" style="background:linear-gradient(120deg,#C4775A,#E56B6B);border-radius:10px;padding:14px 18px;margin:0 0 14px;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:10px">'
			. '<span style="color:#fff;font-size:15px;font-weight:800">' . esc_html( $t ) . '</span>'
			. '<a href="' . $u . '" style="background:#fff;color:#C4775A;font-size:14px;font-weight:800;padding:9px 18px;border-radius:8px;text-decoration:none;white-space:nowrap">' . esc_html( $b ) . '</a>'
			. '</div>';
	}
	$t = $en ? 'Condition worsening? DoctorNow is the medical side of the same family' : '情況轉差？老友宅醫上門醫護';
	$s = $en
		? 'Sudden illness change, hospital discharge, caregiver burnout — home-visit doctor, nurse assessment, palliative care and passing-at-home support, 24/7.'
		: '病情突變、出院返家、照顧頂唔順 — 上門睇醫生、護士評估、紓緩治療、在家離世安排，全天候跟進。';
	$h = '<div class="acblog-ladder" style="margin-top:34px;background:linear-gradient(120deg,#C4775A,#E56B6B);border-radius:12px;padding:26px 24px;color:#fff;text-align:center">'
		. '<h4 style="margin:0 0 6px;font-size:18px;font-weight:800;color:#fff">' . esc_html( $t ) . '</h4>'
		. '<p style="margin:0 auto 16px;font-size:13px;opacity:.95;line-height:1.7;max-width:520px">' . esc_html( $s ) . '</p>'
		. '<div style="display:flex;flex-wrap:wrap;justify-content:center;gap:10px">';
	foreach ( $pillars as $p ) {
		$label = $en ? $p[3] : $p[2];
		$h .= '<a href="' . $url( $p[0], $p[1] ) . '" style="display:inline-block;background:#fff;color:#3D3733;font-size:13.5px;font-weight:700;padding:10px 16px;border-radius:8px;text-decoration:none">' . esc_html( $label ) . '</a>';
	}
	$h .= '</div>'
		. '<p style="margin:14px 0 0;font-size:11.5px;opacity:.8">' . ( $en ? 'A DoctorNow Home service · same family of brands' : 'DoctorNow Home 老友宅醫 · Angel Care 同系列品牌' ) . '</p>'
		. '</div>';
	return $h;
}
add_shortcode( 'ac_escalation', function ( $atts ) {
	$atts = shortcode_atts( array( 'layout' => 'card' ), $atts, 'ac_escalation' );
	try {
		return ac_escalation_html( $atts['layout'] );
	} catch ( \Throwable $e ) {
		return '';
	}
} );
