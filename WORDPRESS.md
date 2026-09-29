# WORDPRESS.md — Angel Care (angelcare.dnow.hk)

> **Purpose:** Brand-specific WordPress + Elementor reference for Angel Care. General workflow rules live in the template; this file holds only what is specific to this brand.
> **Template:** `_shared/_templates/WORDPRESS-LOCAL-DEV.md` · **Server access:** `SERVER.md` (this directory)
> **Update rule:** general lessons → template; brand facts → here. Append dated changelog entries.
> **📝 Marketing Action Log (2026-08-09):** every marketing action for this brand (ads, conversion-tracking, strategic page moves) → append to `MARKETING-LOG.json` in this folder after execution+verify. Guide: `_shared/_frameworks/_dashboard/MARKETING-ACTION-LOG-GUIDE.md`.

---

## 1. Environments

| Environment | URL | Details |
|---|---|---|
| **Production** | https://angelcare.dnow.hk | WordPress Docker on `47.52.68.243` (container `angelcare_wordpress` — **no `_php8` container exists** as of 2026-09-04; old docs referencing `angelcare_wordpress_php8` are stale), WP-CLI 2.12.0 (`--allow-root`), wp-admin user `cbo`. **DB prefix `wp_`** (NOT emc_ — verified at deploy 2026-08-06; Alibaba RDS `angelcare_wordpress_dev`). **PHP-FPM user = `nginx`** (not www-data) — see §3.2 permission incident. Bilingual live since 2026-08-06 (TranslatePress, EN at `/en/`). |
| **WIP staging** | https://ac-staging.hkdrnow.com | Container `angelcare_wp_migrate` (port 8092), DB `angelcare_db_migrate` + Redis. **This is the active dev/staging site** (blog, bilingual, all fixes) — source of truth for prod. siteurl = `https://ac-staging.hkdrnow.com`. |
| **Rollback backup** | http://localhost:8090 | Container `angelcare_wp_local` (port 8090), DB `angelcare_db_local`. **FROZEN snapshot** of 8092 (taken 2026-08-05, pre-bilingual) — rollback insurance only, do NOT edit. |
| DNH local (other brand) | http://localhost:8091 | Container `dnh_wp_local` — DoctorNow Home, NOT AngelCare. |

**Versions (verified 2026-08-01):** WP 6.6.2 · Elementor 3.25.4 · Elementor Pro 3.25.2 · active kit ID **8** · theme `hello-elementor` · plugin `post-duplicator` already installed.
**Staging URLs applied:** `ac-staging.hkdrnow.com` (clone URLs pre-replaced; re-check after any import).

---

## 2. Brand facts

- **Key pages are `elementor_library` templates, not plain posts** — and **several pages render from a template post, NOT the page's own `_elementor_data`**: 全新升級=template 1087, 功能介紹=template 290 ("FUNTION"), 廢用症候群=template 275, Footer=template 152, home=template 248 (header template 469). Always find the render source (curl → `data-elementor-id`) before editing; editing the page post does nothing. (Template §5 lesson 30.)
- **paid-services page move pending** — 付費服務 (護士上門評估 HK$1,500-2,000/次, 維生指數 HK$99/年, 醫生諮詢 HK$5,800/年) moving to a dedicated page. Coordinate with Service Pillar Architecture (`_brands/angel-care/_services/`).
- **Content model:** informational home-care content (居家安老資訊) + paid services. Same population as DoctorNow Home but earlier stage of the journey (trust ladder: AC content → DNH services).

---

## 3. Known incidents

### 3.1 Elementor CSS files missing on production (2026-07-31 → 2026-08-01)
**Symptom:** 功能介紹 / 教學指南 / 常見問題 pages rendered broken on prod (giant QR, missing sections) while local was fine.
**Root cause:** incomplete Elementor CSS regeneration — after a UTM/data edit + cache flush, `post-290.css`, `post-271.css`, `post-720.css` were missing on prod. A later `wp elementor flush_css` on prod wiped ALL css files and frontend loads did NOT regenerate them (template posts + cached pages never fire regeneration).
**Resolution:** restored from backup, then force-regenerated per-post.
**Lesson → template §5.1.** Never `flush_css` casually on prod; per-post regeneration via:
```
docker exec angelcare_wordpress_php8 wp eval '
foreach ([POST_ID1, POST_ID2] as $pid) {
    $css = \Elementor\Core\Files\CSS\Post::create( $pid );
    $css->update();
}'
--allow-root
```
(Use `\Elementor\Core\Files\CSS\Post::create($id)->update()` — `$doc->update_css()` fails on ThemeBuilder Single_Page docs; `regenerate-css` is not a valid subcommand.)
**Backup location:** `/data/angelcare_wordpress/backups/elementor-css-pre-regen-20260801/` (prod server).

### 3.2 uploads/elementor ownership — silent CSS failure since 2026-06-18 (found 2026-08-06)
**Symptom:** after the bilingual deploy's `flush_css`, prod rendered UNSTYLED. Elementor log: `file_put_contents(.../uploads/elementor/css/post-248.css): Failed to open stream: Permission denied` (hundreds of entries since 2026-06-18). Prod had been serving cached old CSS via Cloudflare for ~2 months; any cache purge exposed it.
**Root cause:** `uploads/elementor/` owned by `1008:xfs` but PHP-FPM runs as **`nginx`** user on prod (first chown to www-data did NOT fix it).
**Fix:** `chown -R nginx:nginx uploads/elementor/` + `flush_css` + re-render (post-248.css = 102,418 bytes, parity with staging). Verify CSS by content/size (template §5 lesson 19), and check DNH prod separately (DNH verified clean — its Elementor CSS timestamp is fresh).
**Lesson → template §5 lesson 24.**

---

## 4. Changelog

| Date | Change |
|---|---|
| 2026-08-01 | Created. Captured CSS incident + brand env facts. |

## 5. Changelog — 2026-08-03: OpenSEO audit remediation (staging 8092)

**Audit clean for deployment** (verified by post-fix re-crawl + rendered-HTML sweeps):
- Critical broken link `_wp_link_placeholder` → `https://www.doctornowhome.com/` (template 91 + 9 revisions + post_content; site convention for 了解更多/老友宅醫 CTAs)
- H1s normalized (1 per page, no skips) across home/landing/pricing/sitemap/terms/guide/elder-care/story
- Orphans noindexed via Rank Math: `/category/uncategorized/` + `/sitemap/` (meta key `rank_math_robots` = `['noindex']` — values-only array, key=>value format is silently ignored)
- Meta descriptions + thin-content expansion applied (9 pages expanded, 3 noindexed); backups: `~/backups/angelcare-8092/20260803_fixes_applied/db.sql.gz` + baseline `20260803_0724_baseline/`
- **⚠️ DEPLOY REQUIREMENT (staging→production):** add page-caching layer first — Cloudflare APO or WP cache plugin + Redis object cache. Audit showed 2–3s crawl-time TTFB; manual TTFB 0.2–0.3s (cold-origin artifact), opcache ON, MySQL <5ms.

**Lesson (general → template):** `update_post_meta()` on a **revision** post silently redirects to the **parent** post (WP core behavior — `UPDATE ... WHERE post_id = parent`). Direct `_elementor_data` edits on revisions must use `$wpdb->update(wp_postmeta)` directly, or you'll corrupt the parent. Detection: after update, re-read via `$wpdb->get_var` and compare — `get_post_meta` can mask it via cache. (Template: `_shared/_templates/WORDPRESS-LOCAL-DEV.md` §lessons.)

## 6. Changelog — 2026-08-03: Caching layer on staging 8092 (Wilson-approved, pre-prod testbed)

**Stack (staging testbed for prod):** Redis object cache + WP Super Cache page cache.
- **Redis:** `redis:7-alpine` container added to `/home/oc/staging/angelcare-migrate/docker-compose.yml` (service `redis`, internal only); `redis-cache` plugin v2.8.0 + phpredis via compose `build:` (`./docker/Dockerfile` — also bakes in wp-cli, which was previously ad-hoc in the container FS and lost on recreate). `WP_REDIS_HOST=redis`, `WP_REDIS_PORT=6379` in wp-config. Hit ratio 88–93%.
- **WP Super Cache:** v**1.12.1** (latest requires WP ≥6.8; site pinned at 6.6.2). `WP_CACHE=true` + `WPCACHEHOME` in wp-config. Verified: static HTML served at advanced-cache stage, TTFB 1.2–2.3ms vs 190–440ms baseline (~150×).
- Backups: `~/backups/angelcare-8092/20260803_pre-cache/` + `20260803_post-cache/`.

**⚠️ WPSC config gotchas (all cost real debugging time):**
1. `$wp_cache_pages[...]` semantics are INVERTED: `= 1` means **don't cache** that page type (all 0 = cache everything). The admin checkboxes are exclusion boxes.
2. `$wp_cache_slash_check` must be `1` or non-root pages with trailing slashes (`/pricing-plan/`) never serve from supercache (only `/` matches `$wp_cache_home_path`). It was absent from the generated config.
3. Cache dir perms: wp-cli (`--allow-root`) creates `wp-content/cache/` as root → www-data can't write → silent generation failure. `chown -R www-data:www-data wp-content/cache/`.
4. **CLI `update_post_meta` edits bypass WP actions → no WPSC purge.** After any wp eval `_elementor_data` edit, purge manually: `wp eval 'wp_cache_clear_cache();'` (in addition to deleting `_elementor_element_cache`). wp-admin/`wp_update_post` edits DO purge automatically (edit_post hook — verified).
5. Debug: `wp_cache_debug()` only logs when the CONFIG FILE flags (`$wp_super_cache_debug`, `$wp_cache_debug_to_file`) are on — `wp option` values are ignored by phase1.

**Prod deploy (Part B, queued — do NOT do now):** re-enable element-cache TTL after deploy+QA; clear Rank Math 404 log; close comments on 478; placeholder sweep. This compose build (redis+phpredis+wp-cli) is the template for prod.

## 7. Changelog — 2026-08-05: Blog section (CPT ac_blog) built on staging 8092

**Feature:** 文章/Blog section, Option C × Warm Terracotta (Wilson spec 2026-08-05), Elementor-free build.
- mu-plugin `wp-content/mu-plugins/ac-blog.php` (v1.0.0): CPT `ac_blog` (rewrite slug `/blog/`, has_archive=false) + taxonomy `ac_blog_cat` (5 seeded terms: 日常照顧/行動復健/認知障礙/醫療照護/居家安全); `[ac_blog_grid]` listing shortcode (pill tabs 全部+5 cats via `?ac_cat=`, 2-col grid→1-col mobile, pager, terracotta CTA strip → doctornowhome.com + UTM); `[ac_blog_recent]` homepage 3-card section; `[ac_highlight]` sticky box; per-post CTA meta box `ac_cta_url` (default = DNH homepage + `?utm_source=angelcare&utm_medium=content&utm_campaign=blog`); views counter (postmeta `ac_views`, front-end only); read-time (CJK 300 chars/min).
- Single template `mu-plugins/templates/single-ac_blog.php` via `template_include` (hierarchy: chip → H1 → meta → hero → content → sticky → emotional CTA + WhatsApp `https://wa.me/85263324599?text=我想了解老友宅醫服務`).
- Page 1257 文章 (`/blog/`); menu item 1258 「文章」 at menu_order 8 (after 常見問題 7, before 收費計畫 9), menu MAIN.
- Homepage 最近文章 section via `elementor/theme/after_do_single` hook — **the_content never fires on page 258** (Elementor template 248 has no page-content widget); hook verified via HTML offsets (section at ~180.8K before footer 181.8K).
- Palette scoped to `.acblog-*` only; global kit (#8CCBCB teal) untouched.

**Gotchas (all cost real time — log them):**
1. `wp_enqueue_style($handle, false)` does **NOT** register the handle on WP 6.6 — `wp_add_inline_style` then silently no-ops and no CSS prints. Must `wp_register_style($handle, false, ...)` first. Symptom: DOM correct, page renders unstyled/white.
2. `wp post create/update` with a future `post_date` forces status back to `future` (WP compares GMT); CLI also merges stale `post_date_gmt` into updates so it never recomputes. Fix via direct `$wpdb->update` of post_status+post_date+post_date_gmt.
3. WP Super Cache serves stale pages after new CPT/page/menu edits — always `wp eval 'wp_cache_clear_cache();'` after changes.
4. `wp menu item list` position display lags raw `menu_order` behind Redis object cache — verify via raw `$wpdb` query + flush.

**Verified:** curl greps (tabs/cards/nav/UTM/WA/empty-state), browser DOM dump, headless chrome screenshots (desktop/mobile/single — `~/projects/angelcare/blog-screens/20260805/`), full-page PDF text for homepage section (print strips colors — use pdftotext for content proof on giant pages beyond 30K px capture limit).
**Backup:** `~/backups/angelcare-8092/20260805_pre-blog/db.sql.gz` (7.0M).
**Prod deploy PENDING Wilson approval** (backup → copy mu-plugin+template → terms auto-seed → page+menu → rewrite flush → purge caches → verify live).

## 7.1 Changelog — 2026-08-05: First blog post loaded (staging 8092)

- Post 1266 《長者護理常識》恰到好處的照顧之道 (slug 恰到好處的照顧之道, percent-encoded), cat 日常照顧, publish 2026-08-05 07:30 HKT (GMT-past).
- Media imported (IDs 1261-1265): fig-eating/toileting/hygiene/dressing.jpg + video-promo-h264.mp4 (HEVC→H.264 transcode via host ffmpeg, crf 26, 6.8MB, poster=fig-eating).
- Per-post CTA meta: DNH 護士上門評估 `.../service/%e8%ad%b7%e5%a3%ab%e4%b8%8a%e9%96%80/` + `?utm_source=angelcare&utm_medium=content&utm_campaign=blog-care-amount`; WhatsApp wa.me/85263324599 (template-level).
- Rank Math: description + focus keyword set.
- Content: 3 H2 sections + [ac_highlight] (輔助自立) + 4 Do/Don't panels inline (relative /wp-content/uploads/... srcs) + FAQ Q1-Q5 (plain h3/p, no accordion) + <video controls> embedded.
- Verified: curl greps (all sections/images/FAQ/video/CTA/WA), full-page PDF text proof, screenshots → #angelcare. Screenshots: ~/projects/angelcare/blog-screens/20260805/.
- **Prod deploy must include the 5 uploads files** (they exist only in the migrate container's uploads dir) — copy `/wp-content/uploads/2026/08/{fig-*.jpg,video-promo-h264.mp4,first-post-content.html}` + trash the html helper file.

## 7.2 Changelog — 2026-08-05: FAQ boxed redesign (staging 8092, per Wilson)

- Post content FAQ wrapped in `<div class="acblog-faq">` (convention for all future posts): rounded #FDF1E8 box, 1px #F2E1CE border, h2 with terracotta left accent bar, Q = `<span class="acblog-qmark">Q1</span>` terracotta chip + dark question, A = warm grey #6B6158 paragraphs.
- CTA color variants pre-staged in CSS: `.acblog-hero-strip.is-teal` / `.acblog-ecta.is-teal` (gradient #4DB6B8→#2E9A9C, button text #2E8B8D) — Wilson pick pending; default remains terracotta.
- **Prod deploy still HELD** (Wilson 2026-08-05 08:23 directive: no prod until explicit approval).

## 7.3 Changelog — 2026-08-05: Wilson decisions confirmed (staging 8092)

- CTA strip color = **Option A Warm Terracotta** (#C4775A→#E8A585) confirmed — default stays, teal variants kept as unused `.is-teal` classes (easy future swap).
- FAQ apricot box confirmed per Wilson screenshot review (#FDF1E8, terracotta Q chips, warm-grey A).
- Video promo restored (was temporarily removed during a layout-diagnostics false alarm — the page was rendering correctly; the "missing CTA/footer" was a histogram-scan artifact: gradient pixels spread across shades + light-grey footer blending with white. Lesson: use vision-model crop checks for colored regions, not top-color histograms).
- **Prod deploy HELD** (Wilson directive 2026-08-05).

## 7.4 Changelog — 2026-08-05: Post navigation + floating buttons + related posts (staging 8092, CBO spec/Wilson approved)

- Single template now: breadcrumb 返回文章列表 (top) → chip/H1/meta/hero/content → 返回列表 + 上一篇/下一篇 (category-aware via `acblog_prev_next()`: same ac_blog_cat term first, fallback any post; apricot side-by-side cards, hidden if no neighbor) → emotional CTA → 相關文章 (`acblog_related()`, 2-3 same-cat cards, fills with latest others) → footer.
- Floating: WhatsApp FAB bottom-left (#25D366, wa.me/85263324599) + ↑ top arrow bottom-right (#C4775A, JS: shows after 300px scroll, smooth scroll). Scoped to single ac_blog pages.
- Verified: curl greps + vision-model crop (返回列表/上一篇/CTA/相關文章 all present) + temp 2nd post for prev/next/related both directions, then removed (listing back to 1 post). Screenshots: `~/projects/angelcare/blog-screens/20260805/` + posted #angelcare.
- **Gotcha logged:** `wp post delete <id>` on this env prints "Please use --force" WITHOUT trashing (leaves post publish — explains the earlier 1259 "resurrection"). Use `wp post delete <id> --force` for temp artifacts.
- **Prod deploy still HELD** (Wilson directive).

## 8. Changelog — 2026-08-05/06: Bilingual (ZH+EN) rollout + PROD DEPLOYED

**Bilingual architecture (Wilson-approved, phase-by-phase):** TranslatePress Free 3.3, default zh_Hant (URLs unchanged) + en_US under `/en/`, hreflang auto via TRP url_converter, EN titles via mu-plugin `trp-rankmath-meta.php`. 8092 = WIP stage; 8090 = frozen rollback backup (snapshot taken pre-bilingual 2026-08-05).
- **TRP lessons → template §5 lesson 25** (href stripping on Elementor buttons → JS relink map + MutationObserver; no img[alt] accessor; `<link>` skip; byte-exact dict keys; excerpt fallback).
- **Hardcoded prod-domain in link map bug → template §5 lesson 26** (Learn More opened Chinese page on EN; fixed by host-adaptive-urls.php v2 output-buffer rewrite `angelcare.dnow.hk`→current host; no-op on prod).
- **Design decisions:** hamburger/menu spec (mobile all pages hamburger; desktop homepage hamburger top-left + menu bar below hero BOTH; desktop all pages menu bar) — v1.9/v1.10 in `ac-spacing-fix.php`; EN/中 toggle injected at 5 nav surfaces; old TRP switchers removed (floater filter + CSS hide).
- **九大實用功能 REVERTED to 8090 original design** (2026-08-06, Wilson): the teal-v2 redesign was 100% mu-plugin CSS/JS injection — Elementor data was byte-identical on 8090/8092, so revert = removing the override blocks (v1.10), NOT editing Elementor data. Redesign remains in `bak-v19` if ever needed.

**PROD DEPLOY 2026-08-06 (angelcare.dnow.hk):** backup (DB 57MB + mu-plugins) → push 5 mu-plugins (ac-spacing-fix v1.10, host-adaptive-urls v2, trp-rankmath-meta, ac-blog, eeat-schema) + TRP plugin (was installed but INACTIVE on prod — activated) → import 6 TRP tables + trp_settings → flush caches → rendered-HTML verify (EN pages 200, toggle 1, floating switcher 0, blog 200). DB prefix on prod = `wp_` (NOT emc_ as assumed — no mapping needed). TRP was installed but never activated on prod before.
- ⚠️ **Prod uploads permission bug discovered post-deploy** → §3.2 above (template lesson 24). Staging/prod home DOM now MD5-identical.
- **Prod blog post ID differs from staging:** prod post = **1256** (staging 1266), and its `post_excerpt` is EMPTY on prod → /en/blog/ card showed Chinese auto-snippet. Fix queued: `UPDATE wp_posts SET post_excerpt = '<staging 143-char excerpt>' WHERE ID = 1256` (TRP dict entry already on prod).

## 8.1 Changelog — 2026-08-06: Post-deploy fix batch (staging 8092 → prod batch, Wilson GO 2026-08-07)

All staging-verified, prod apply batched:
1. **功能介紹 restyle** — 一站式居家安老支援 section rebuilt from raw text blob → H2 + intro + 5 icon-box cards + 點樣開始 4 steps + closing (template 290, teal/pastel design language).
2. **全新升級 × 4** (template 1087): carousel arrows outside frame + 2s autoplay (pause_on_interaction); button clip fix (curve divider overlap → 50/70px padding, gap 0→103px mobile); 今次升級有咩唔同 vs FAQ redesign + spacing (teal cards + 40px margin); 常見問題 heading moved from teal card into FAQ white section (wrapper b436220 → first child of 18143ad).
3. **Pricing × 2** (post 1249): box bottom-gap 214-262px → uniform 25px (min-height 68vh→0, flex-start, strip empty `<p>`); zoom-out centering (container boxed 1140px + widgets 860px max-width + auto margins; centerOffset 0px at all zooms).
4. **廢用症候群 × 5** (template 275): all 5 complication sections unified boxed column (圖上文字下, teal border #8CCBCB, 20px radius, shadow) — Wilson confirmed 尿道炎 is part of the group so ALL changed.
5. **Footer 單行** (template 152, site-wide): flex row, © left / Terms center / Download right; mobile wraps centered. `$` artifact = headless-browser font glitch, NOT real content (0 `$` in rendered HTML).
6. **Blog EN excerpt** — prod-only one-line DB fix (post 1256).

**File state:** `ac-spacing-fix.php` v1.10 (+§13 carousel, §14 zoom, §15 footer — backups bak-v17..v19); host-adaptive-urls.php v2 (backup bak-v1); trp-rankmath-meta v1.2.

## 9. Changelog — 2026-08-07: EN 九大實用功能 titles styling fix (staging 8092, per Wilson via CBO)

**Task:** EN version of the 九大實用功能 (Nine Essential Features) section must match ZH's teal text-box block color + font size.

**Root cause (TRP artifact, NOT Elementor data):** the section is a Reviews carousel (widget `015d86b` inside container `55714d2` teal #40B4B4) on home template 248. Elementor renders each slide title as `<cite class="elementor-testimonial__cite"><span class="elementor-testimonial__name">…</span></cite>`. On EN (non-default lang), TRP replaces the cite innerHTML with the translated string and marks the cite `translation-block` — **dropping the `__name` span** (verified: nameSpanCount=0, computed title rgb(51,51,51) 14px/21px vs ZH rgb(64,180,180) 30px/45px). ZH is untouched (default lang). Elementor data + post-248.css are byte-identical on both languages.

**Fix (v1.11, §16 `ac-en-reviews-title` in ac-spacing-fix.php):** scoped CSS restores ZH values on the cite itself:
`html[lang="en-US"] .elementor-248 .elementor-element-015d86b .elementor-testimonial__cite.translation-block { color: var(--e-global-color-accent); font-family: "Roboto", Sans-serif; font-size: 30px; font-weight: 600; }`
Backup: `ac-spacing-fix.php.bak-v20`. WPSC flushed (rm supercache tree — `wp super-cache flush` NOT registered on 1.12.1; `wp eval 'wp_cache_clear_cache()'` throws).

**Verified (playwright, live EN page):** computed title = rgb(64,180,180) 30px 600 Roboto line-height 45px — identical to ZH. Body text unchanged 24px. Screenshot confirms. Prod untouched.

**Related observation (NOT fixed, pre-existing site-wide):** EN section-heading button "Nine Essential Features" (315acd2a) lacks the Apple icon ZH has — `ac_btn_relink_js` rebuilds stripped buttons without icons (all mapped buttons affected). Button color/font-size already match ZH. Wilson to decide if icon parity is wanted.

**§16 size adjustment (2026-08-07, Wilson preview):** 30px → 24px (~20% smaller) per Wilson — title now `font-size: 24px` (color #40B4B4 / weight 600 / Roboto / line-height 36px unchanged intent). Verified computed rgb(64,180,180) 24px 600 on live EN page. Prod untouched.

## 10. Changelog — 2026-08-07: EN toggle expand chevrons restored (staging 8092, Wilson via CBO)

**Task:** EN homepage Caregiver's Story 3 story boxes (toggle widgets ddc70d5/1543f7e/7b69782) missing the expand chevrons ZH has.

**Root cause (same TRP artifact family):** TRP replaces each `.elementor-tab-title` innerHTML with the translated string, dropping BOTH the `.elementor-toggle-icon` span (chevron-down closed + chevron-up opened SVGs, fill #DB9C7B terracotta on home) AND the `.elementor-toggle-title` label span (EN label was bare text → 16px/700 instead of ZH 20px/400 Inter).

**Sweep (Red Line #15):** same bug on 4 more EN pages — 功能介紹 (9 rows), 常見問題 (7), pricing-plan (13), all icons missing. 全新升級 FAQ (e191226) has NO icons in ZH → excluded (EN already matches).

**Fix (v1.11 → §17 `ac_en_toggle_icons_js`, wp_footer 100):** JS restores the full ZH markup per tab-title on EN — icon span (exact chevron-down/up SVGs copied from ZH DOM) + `.elementor-toggle-title` label span — for whitelisted widget ids (ddc70d5, 1543f7e, 7b69782, 4b94b7ad, 13a2117, 0ba326e). MutationObserver + `data-ac-toggle-fixed` guard (same pattern as relink JS). Existing per-page CSS then colors/sizes icons per ZH automatically (home=terracotta, pricing=teal). Backup: `bak-v21`.

**Verified (playwright live EN):** home 3/3 widgets — icon present, fill rgb(219,156,123) #DB9C7B, label 20px Inter 400; click → active + chevron-up swap + content opens; pricing EN icon restored (teal per page CSS). ZH untouched. Prod untouched.

## 11. Changelog — 2026-08-07: 全新升級 EN feature-box Chinese leak — prod-only (staging clean)

**Report:** Wilson saw 更方便/更安心 boxes untranslated on prod EN 全新升級. 

**Diagnosis:** staging EN 全新升級 = **0 visible CJK** (all 6 icon-boxes incl. b100011 More convenient / b100012 More reassuring / b100013 More considerate + English descs). Prod EN = 3 boxes + 3 descs in ZH. Prod ZH node bytes IDENTICAL to staging (titles + \n-bullet descs, 88/81/85 chars). Root cause: prod `wp_trp_dictionary_zh_hant_en_us` missing the 6 current rows (staging ids 1573-1578, status=2 — added during 全新升級 restyle after prod's 2026-08-06 dict import). Prod likely carries stale merged keys (ids 443-454 "更方便：服務流程更簡單" style) — the classic merged-mega-key trap; current page renders separate title+desc nodes.

**Artifact:** `/tmp/prod-trp-dict-fix-20260807.sql` — 6 rows, DELETE+INSERT (original NOT unique in TRP dict schema — ON DUPLICATE KEY won't fire), byte-exact keys incl. literal \n in descs (generated via HEX() roundtrip — mysql batch output escapes \n which corrupts naive dumps). Prod apply pending Wilson approval: run SQL on prod RDS (prefix wp_) + flush WPSC + verify rendered /en/ page.

## 11.1 Changelog — 2026-08-07: 功能介紹 EN 9-item task module chevrons — already covered by §17

Wilson follow-up (same family as story-boxes): ZH 功能介紹 nine-dimensional task module (toggle 4b94b7ad, 9 rows) has expand chevrons; EN missing. **Verified: already fixed** by §17 whitelist (4b94b7ad included) — live EN render: 9/9 chevrons, teal fill rgb(64,180,180) = ZH, labels 20px Inter, click-expand works. 常見問題 (13a2117) also confirmed 7/7. No new changes; prod untouched.

## 11.2 Changelog — 2026-08-07: 功能介紹 "How to get started?" EN translations added (staging 8092)

Wilson report: EN 功能介紹 step boxes — teal titles EN but grey descs ZH. Diagnosis: staging AND prod both have the gap — 7 strings untranslated (box a100031 desc + boxes a100032/33/34 titles+descs; box a100031 title was already EN via dict id 576 聯絡我哋→Contact Us). Dict had the 7 keys as status=0 rows (ids 859/861/863 + 1569-1572) — TRP auto-registered but never translated. Stale merged keys (328-331 "聯絡我哋：講低…" style) and colon-prefixed rows (858-864) exist but don't match current nodes — left alone.

Fix (staging): UPDATE the 7 status=0 rows → status=2 with EN (titles per Wilson screenshot wording: Arranging Assessment / Formulating a Plan / Ongoing Follow-up; descs in site style). WPSC flushed. Verified: rendered EN page 0 visible CJK; computed titles teal 22px 600 Roboto, descs grey 15px 300 Inter. Artifact: /tmp/prod-trp-dict-fix-howto-20260807.sql (same 7 UPDATEs, prod RDS wp_ prefix) — prod apply pending Wilson approval.

## 11.3 Changelog — 2026-08-07: 教學指南 EN text fixes (staging 8092)

Wilson report (4 issues): step 2 desc run-together "familyAfter registration,waitthe"; step 3 "EmployerGo"; pricing "SeePricingpage"; steps 4-6 titles missing the line breaks steps 1-3 have.

**Root causes:**
1-3. ZH source has inline `<span>/<a>` adjacent to text nodes with NO whitespace (CJK needs none). TRP translates each node; dict-value spaces are TRIMMED on insert (verified: row 1018 trailing space stripped in render) → dict-space fixes impossible. Fix: §18 output-buffer rewrite (ac-spacing-fix.php, EN guide page only) inserting the 5 missing spaces. ⚠️ TRP starts its buffer at `init` priority 0 — an outer (template_redirect) buffer sees PRE-translation HTML and never matches; §18 must hook `init` priority -1 (buffer sits outside TRP's, sees translated output). Gated by URL (is_page unavailable at init -1).
4. Steps 4-6 h3 got merged dict keys (1443-1445, "步驟4：回答問卷（僑主專用）" style) → TRP block-translated the whole h3, dropping the `</BR>` breaks; steps 1-3 had only per-segment keys → breaks preserved. Fix: DELETE merged keys + fill 7 per-segment keys (1025/1026/1029/1030/1032/1033/1034) → status 2.

**Verified (playwright DOM):** titles render 2-5 lines with `<br>` breaks matching steps 1-3 (step 4/5 = 3 lines); descs + pricing have proper spaces; ZH page untouched (buffer EN-only). Note: step images (STEP-*.png) have baked-in Chinese — images, not translatable text, out of scope. Prod artifact: /tmp/prod-trp-dict-fix-guide-20260807.sql + ac-spacing-fix.php §18 (mu-plugin file update). Prod untouched.

## 11.4 Changelog — 2026-08-07: 廢用症候群 EN "Learn More" run-together fix (staging 8092)

Wilson report: "Learn Morebedsoresandjoint stiffnessknowledge" → "Learn more Bedsore and Joint stiffness knowledge". Same structural cause as §18 (ZH "了解更多<a>褥瘡</a>及<a>關節僵硬</a>知識" — inline links, no inter-node whitespace; TRP trims dict spaces). Fix: §19 `ac_en_disuse_spaces_ob` (init -1 buffer, EN disuse page only) — 6 targeted str_replaces (case + spaces). Same-pattern sweep found a SECOND occurrence on the same page ("Learn more<a>Urinary tract infection</a>and<a>nappy rash</a>knowledge") — fixed too. No dict changes. Verified: live body text = "Learn more Bedsore and Joint stiffness knowledge" + "Learn more Urinary tract infection and nappy rash knowledge"; ZH untouched. Prod: ships in ac-spacing-fix.php (§17/§18 batch), pending approval.

## 11.5 Changelog — 2026-08-07: 廢用症候群 EN "nappy rash" → "Napkin rash" (staging 8092, Wilson)

Wilson preference: "Napkin rash" (assumed HK/UK site convention). **Checked: site convention is actually "nappy rash"** — all EN occurrences use "nappy" (disuse page: dict 626 "bedsores and nappy rash"; dict 1252 尿疹→"nappy rash" is the link text source; stale merged keys 640/1500 also "nappy"). No page anywhere uses "Napkin". Applied Wilson's wording to the requested phrase only: §19 buffer now outputs "Napkin rash" in the Learn-more line (`">nappy rash</a>knowledge` → `">Napkin rash</a> knowledge`, combining the space fix). Dict untouched → "Skin problems such as bedsores and nappy rash" (626) still "nappy". Verified live: "Learn more Urinary tract infection and Napkin rash knowledge". If Wilson wants site-wide "Napkin", that's a separate dict change (1252 + 626 + stale 640/1500). Prod: ships in ac-spacing-fix.php batch.

## 11.6 Changelog — 2026-08-07: 從居家安老到院舍安老 EN comparison table (staging 8092)

Wilson report: "Home Care vs Care Home" comparison shows Chinese on EN. **CBO diagnosis "baked-in image" was WRONG** — it's a **Graphina data_table_lite widget** (852dc57): a real HTML table rendered client-side from a `data-chart_data` JSON attribute (ZH content; TRP can't translate data attributes). Fix: §20 `ac_en_elder_table_ob` (init -1 buffer, EN page only) regex-swaps the widget's data-chart_data attribute to full EN copy (9 rows × 2 cols + headers; CBO copy adjusted: kept `&lt;/BR&gt;` cell breaks, apostrophe→`&#039;` since the attribute is single-quoted). ZH untouched. 

**Gotchas:** (1) rendered markup uses TABS between attributes — regex needs `[^>]*` not literal spaces; (2) Graphina DataTable lazy-inits on visibility — page load shows "Loading.........." until scrolled into view (normal, both languages); (3) attribute is single-quoted in HTML but Elementor JS re-serializes DOM copy double-quoted with &quot; — irrelevant to fix, verified via both.

**Verified (playwright, scrolled into view):** table `data_table_lite_852dc57` = 10 rows, full EN text, 0 CJK; screenshot clean. ZH attr byte-unchanged. Prod: ships in ac-spacing-fix.php batch, no SQL.

## 11.7 Changelog — 2026-08-07: 從居家安老到院舍安老 comparison — PROPER bilingual table (staging 8092)

Wilson: "convert to table or infographic, applicable to English and Chinese" — replace the Graphina data_table_lite hack with a real TRP-translatable component.

**Done (proper fix, no output-buffer swap):**
1. Template **638** (長者照護選擇, renders page 636) — widget 852dc57 (`iq_data_table_lite`, Graphina) replaced in `_elementor_data` (in-place PHP array edit via `$wpdb->update` on the PARENT, script /tmp/ac-replace-table-widget.php) with an **HTML widget** (widgetType `html`) containing a clean responsive comparison table: teal headers (#40B4B4 / #8CCBCB), zebra stripes (#FFFFFF/#F7FCFC), inline styles, `min-width:640px` + `overflow-x:auto` wrapper for mobile. ZH source text in the cells.
2. **29 TRP dict entries added** (status=2, byte-exact) for all table strings (headers + 9 rows × 3 cells) — EN translates natively via TRP on /en/. Dict was clean (none of the 28 keys pre-existed; no conflicts, incl. single-char 有/無/高/低/重/輕).
3. **§20 removed** from ac-spacing-fix.php (dead — no more data-chart_data attr on the page); backup bak-v25.
4. Caches: Redis flush + `_elementor_element_cache` purge (638/636) + Elementor post CSS regen (638/636) + WPSC flush.

**Verified (playwright, both languages):** ZH table = full Chinese (居家安老/院舍安老 + 9 rows); EN table = full English (Home Care/Care Home + 9 rows, 0 CJK in widget; 0 visible CJK page-wide). No JS dependency (plain HTML — no lazy-load). Screenshots clean.

**Prod apply (pending Wilson GO):** sync template 638 `_elementor_data` + add the 29 dict rows + ship ac-spacing-fix.php (without §20) + flush caches (Redis/element CSS/WPSC). SQL artifact: none separate (dict rows logged in this section) — generate from staging if needed.

## 11.8 Changelog — 2026-08-07: 從居家安老到院舍安老 EN — 5 CTA links + Step 2 label styling (staging 8092)

Wilson: (1) 5 CTA buttons point to wrong pages (landed on ZH homepage); (2) Step 2 "Complete here:" label doesn't match Step 1's style.

**Render source:** the page renders TEMPLATE 638 (data-elementor-id=638; page 636's own _elementor_data is NOT rendered — editing it is a no-op, lesson re-learned: always verify render source before editing).

**Issue 1:** buttons' data links = `https://angelcare.dnow.hk/?cff-form=N` (correct for ZH). On EN, TRP strips the anchors and `ac_btn_relink_js` rebuilds them from the relink map — the map had the same non-/en/ hrefs, so EN clicks landed on the ZH homepage (form popup on ZH). Fix: relink map entries for da78fb7/19b514e/8fbbca4/3bcb022/9dd9402 → `https://angelcare.dnow.hk/en/?cff-form=N` (host-adaptive rewrites domain; /en/ suffix survives). ZH untouched (relink is EN-only).

**Issue 2:** 638 heading 27e2ffd (Step 2 label) had typography custom + 13px + weight 700 vs ccfe3ae (Step 1) custom/Roboto/600 no size. Fixed in 638 _elementor_data: copy typography (Roboto 600) + remove the 13px size (default h4 24px applies). ⚠️ PHP gotcha: a find-function `return $node` returns by VALUE — modifications lost (rows affected 0). Must modify the target node IN-PLACE via a by-ref recursive function.

**Verified (playwright live):** EN 5 buttons → /en/?cff-form=N (all 5 targets 200 + html lang=en-US); ZH buttons unchanged; ALL Complete-here labels (EN+ZH, incl. swiper clones) = Roboto 600 / 24px / #8CC86E. Prod: map change (ac-spacing-fix.php) + 638 data change — ships in batch.

## 11.9 Changelog — 2026-08-07: Toggle title wrap-alignment fix + collapsible inventory (staging 8092)

Wilson: wrapped 2nd line of EN home story-box toggle title ("Afraid Of Doing Wrong, Dared Not Act — Family Worried") starts under the chevron instead of aligning with "Afraid". Root cause: chevron icon = `float:left` (Elementor widget CSS) → wrapped label lines return to the container's left edge. Fix: §21 `ac-toggle-wrap-align` CSS — flex layout on `.elementor-tab-title` (icon + label flex items, gap 7px, icon float:none) scoped to home toggles ddc70d5/1543f7e/7b69782 (both languages — CSS targets widget ids). Verified at 768/480/390px: all wrapped lines share the first line's left edge (e.g. 294/294, 87/87/87).

**Inventory of all collapsible boxes** (see ~/projects/angel-care/inventory-collapsible-20260807.md): 7 live toggle groups — home (FIXED), 功能介紹 (290/4b94b7ad, 9 rows, low risk), 常見問題 (271/13a2117, 7 rows, HIGH), pricing (1249/0ba326e, 13 rows, HIGH), 背景故事 (483/232aba9+afd621e, 8 rows, low), 全新升級 (1087/e191226, no chevrons rendered → not affected), landing (91/3b5a0903+444f329, no chevrons → not affected). No accordions. Legacy dups not rendered: 524/1006/805/229. Rollout (same §21 CSS per template) PENDING Wilson approval of the first fix. Prod untouched.

## 11.10 Changelog — 2026-08-07: Wrap-alignment fix ROLLED OUT site-wide (Wilson approval)

Wilson approved applying the §21 flex-layout wrap fix to ALL chevron collapsible boxes: §21 extended to 功能介紹 (290/4b94b7ad), 常見問題 (271/13a2117), Pricing (1249/0ba326e), 背景故事 (483/232aba9+afd621e). Home (248) already done. 全新升級 + Landing excluded (no chevrons). **Gap found during rollout:** 背景故事's widgets were missing from the §17 icon-restore whitelist → EN toggles had NO chevrons (ZH had them); added 232aba9+afd621e to §17. Verified at 390px mobile: 常見問題 6/7 rows wrap, all lines aligned ([48,48]); Pricing 8/13 wrap, aligned ([57,57]); 功能介紹 + 背景故事 short titles (aligned, chevrons now present on EN 背景故事). Prod untouched. Also closed: STEP-001..008 EN image conversions — SKIPPED per Wilson (colleagues will produce later).

## 11.11 Changelog — 2026-08-07: FAQ toggle titles — numeric prefixes removed (staging 8092)

Wilson: strip "1. ".."7. " from 常見問題 toggle titles (template 271 / 13a2117), both languages if ZH numbered. ZH used "N、" (full-width comma), EN "N. ". Removed from BOTH: template 271 _elementor_data (7 tab titles, in-place edit) + TRP dict (deleted old 7 entries keyed by prefixed ZH titles 335-347; inserted 7 new entries keyed by unprefixed titles). Verified rendered (playwright, both langs): EN "Who is AngelCare for?"…, ZH "AngelCare 宅天使適合什麼人使用？"…, 0 numbered prefixes remaining. Element cache + CSS regen + Redis + WPSC flushed. Prod untouched.

## 12. Changelog — 2026-08-08: Assessment section Phase A built (staging 8092, clean-room)

**Wilson directive (2026-08-08): CLEAN-ROOM rebuild** — new pages from scratch under ONE new master page (sub-menu children); old page 636 + old forms untouched until Phase C. Full spec: `~/projects/angel-care/ASSESSMENT-SITE-IA.md` v2 + `ASSESSMENT-ADAPTIVE-FLOW.md` v1.3 + `ASSESSMENT-TIER1-QUESTIONS.md` v2 (TC/EN).

**Built (all new, all verified ZH+EN 200, curl+grep rendered HTML):**
- **1272 長者照護選擇** (master, parent=0) — Elementor data copied from 636 (4 care-type framework, stats, 3-step flow, comparison tables), H1 rewritten to 長者照護選擇, animated headline → 居家安老與院舍選擇, prepended brand mission banner (集團願景, teal #F0F7F7, mobile 18px), 5 × `?cff-form` buttons relinked → `/評估工具/` (0 cff-form remaining on page).
- **1273 居家安老** (child of 1272) — static SEO page from CARE-OPTIONS-RESEARCH.md §1-2 (LHNO/送膳/平安鐘/器材/a家/CCSV, fees, how to apply).
- **1274 安老院考慮** (child of 1272) — static SEO page from §4 (care place levels, fees, waiting times, RCSV, red flags) + 私營院舍 relay to elderlyinfo.swd.gov.hk register (per build note: private homes OUT of scope, relay only).
- **1275 評估工具** (child of 1272) — interactive wizard, single URL. **CPCF NOT used — plugin is a broken leftover on staging AND prod (dir `calculated-fields-form/` exists but no main plugin file; `?cff-form=6..10` renders nothing anywhere).** Built custom vanilla-JS wizard instead (assets: `uploads/angelcare-assessment/ac-assessment-wizard.js/.css` + static JSON):
  - 4-phase adaptive flow: Phase 0 intro → Phase 1 safety gate (S1-S3) → Phase 2 scored core (15Q, adaptive order, AD8 ×2 + NPI-Q ×3 inline branches, Q7=2 skips Q8) → Phase 3 filters (G1 18-district, G2 budget 5 brackets, G3 carer, G4 logistics, G5 acceptance modifier, G6 priority/out-of-scope)
  - sessionStorage resume (progress auto-saved, resume banner + restart)
  - Scoring: 5 dimensions independent (Option B, no cross-totals), worst-of ability/support axes, lean 1-2 with reasons, red-flag overrides, out-of-scope official-resources box
  - TC/EN in-wizard toggle (byte-exact strings from spec files); SWD disclaimer in wizard footer
  - Phase 3 G1 lazy-loads `uploads/angelcare-assessment/swd-directory-5districts.json` (240 entries, 5 districts) — only fetches when user reaches district question; verified 觀塘 services render with phone+address
- **Menu:** master + 3 children added to MAIN menu (1276 + 1277-1279), master positioned at 8, dropdown sub-menu verified in rendered header.
- **TRP:** 98 new dict rows (ids 1621-1718, status=2, byte-exact TC→EN); page titles via trp-rankmath-meta.php **v1.2→v1.3** (title+desc maps for 1272-1275).
- **Caches:** WP Super Cache cleared (repeatedly — stale cache masked EN titles/dict during build), Redis flushed, Apache graceful restarted (container restarted itself once when apachectl stop hit pid 1).

**Verification:** 14/15 Playwright E2E checks pass (all functional; the 1 "failure" = Elementor JS bundle failing through my local test proxy only — direct curl 200). Screenshots: `~/projects/angelcare/assessment-phaseA-screens/` (master/wizard, desktop/mobile, ZH/EN).

**Staging URLs (Wilson preview):**
- https://ac-staging.hkdrnow.com/長者照護選擇/
- https://ac-staging.hkdrnow.com/居家安老/
- https://ac-staging.hkdrnow.com/安老院考慮/
- https://ac-staging.hkdrnow.com/評估工具/

**Backups:** `~/backups/angelcare-8092/20260808_pre-assessment-phaseA/db.sql.gz` (7.4M, pre-build) + `20260808_phaseA-assessment-built/db.sql.gz` (7.5M, post-build).

**Pending:** Wilson preview → approve → Phase B (居家社區服務 + 上門護理服務 sub-pages + wizard JSON wiring polish) → Phase C (delete 636 + 5 old forms, redirect `?cff-form=6..10` → /評估工具/, prod sync). Prod untouched this phase.

## 13.1 Changelog — 2026-08-11: Meta Pixel PROD PUSH (Wilson GO 2026-08-11 ~07:39) + domain discovery

**Applied to prod (angelcare.dnow.hk):**
- Copied `ac-meta-pixel.php` to prod container `angelcare_wordpress_php8` (host 47.52.68.243), ownership 1008:xfs (matches other mu-plugins), `php -l` clean
- Pre-deploy backup: `/data/angelcare_wordpress/backups/muplugins-pre-pixel-20260811_0740/`
- Caches flushed: WPSC supercache tree rm + `wp cache flush` (Redis; note: `wp redis flush` subcommand NOT registered in this plugin version — use `wp cache flush`) + `_elementor_element_cache` rows purged (18 deleted)
- **Verified live (curl+grep rendered HTML):** angelcare.dnow.hk home /en/ pricing-plan — all 1× `fbq('init','1003655972074979')` + PageView + noscript + events JS. No CF on angelcare.dnow.hk (nginx direct) — no purge needed.

**⚠️ Domain discovery — angelcare.hk is NOT the AngelCare 宅天使 site:**
- angelcare.hk → www.angelcare.hk = **Wix-hosted site** (DNS www208.wixdns.net, IP 23.236.62.147, server `Pepyaka`) for **安耆苑 Angel's Health Care** — a dementia care home (腦退化症長者支援中心), different business/company. Zero references to dnow/doctornow/宅天使. NOT our WordPress (that is angelcare.dnow.hk only).
- **Pixel NOT installed on angelcare.hk** — installing 1003655972074979 there would place the ad-account pixel on an unrelated third-party site (wrong-company data pollution). Flagged to Wilson via CBO.

## 13. Changelog — 2026-08-11: Meta Pixel installed on staging 8092 (Task 1 of CBO conversion-tracking fix)

**Task (CBO via Meta ads audit act_367479981):** 0 conversions because angelcare.hk has NO Meta pixel installed (pixel 1003655972074979 "AngelCare website" registered in ad account, never on site). DNH fires pixel 4144712838891867 via GTM but nothing promoted to conversion.

**Done (staging 8092 only, prod HELD for Wilson approval):**
- New mu-plugin `wp-content/mu-plugins/ac-meta-pixel.php` v1.0: base code + PageView + noscript in `wp_head` (priority 0), plus `wp_footer` JS with delegated standard events:
  - **Lead**: click on any `a[href*="wa.me"], a[href*="api.whatsapp.com"], a[href*="tel:"]` (pricing CTAs, landing page, blog FAB via ACBLOG_WA, wizard WA CTA)
  - **CompleteRegistration**: click on `.acw-emailrow button.acw-primary` (assessment wizard 評估工具 email-send button; wizard email backend NOT wired server-side yet — pixel fires on click, Phase B pending)
  - Double-click guard via `data-acp-lead` / `data-acp-reg` attributes; TRP-safe (DOM delegation works on EN too)
- **Verified:** curl+grep rendered HTML on 10 pages (home, /en/, pricing, landing, download, blog, 全新升級, 功能介紹, 常見問題, 評估工具): exactly 1× `fbq('init','1003655972074979')` + PageView + noscript + events JS each; no double-install with GTM-59KDJ567 (that container fires zero fbq). Playwright live: init+PageView on load, Lead on WA click, CompleteRegistration on wizard email click. WPSC supercache purged; CF edge DYNAMIC.
- **DNH Task 2 (verification only, no change):** GTM PN7F2KX contains pixel 4144712838891867 with 3 tags — PageView (all pages, blocked on `?q` URLs), Lead (link click with URL containing 63324599 + 1 header CSS match), Contact (wa.me click on landingpage paths only). **Purchase NOT configured.** All pixel tags `vtp_consent: true` (Consent Mode v2). Pixel config request fires on load (init ✅). 0-conversion root cause = no Purchase tag + nothing promoted in Events Manager (CBO-side).
- **Prod deploy PENDING Wilson approval:** copy mu-plugin to prod containers (`angelcare_wordpress`/`angelcare_wordpress_php8` on 47.52.68.243), flush WPSC + element cache, verify rendered HTML on angelcare.dnow.hk + angelcare.hk, CF purge.

## 2026-08-09: CF auto-purge wired into staging deploy flow

- `/home/oc/bin/cf-purge-url <url> [<url2> …]` — Cloudflare v4 purge_cache by URL. Zone `hkdrnow.com` (id `d0e8053358458606f6d5fcaf480ea44d`) covers `ac-staging.hkdrnow.com` (and dnh-staging). Token: `~/.cloudflared/token.txt` (BrandOps Tunnel token) — reference the path, never commit the value.
- **Staging deploy step:** after local cache purge, run `cf-purge-url https://ac-staging.hkdrnow.com/<affected-page>` (or with the tunnel hostname used) so CF doesn't serve stale HTML. Verified live 2026-08-09.

## 12. Changelog — 2026-08-18: 4 new blog posts (staging 8092, Wilson via CBO)

**Task:** Add 4 blog posts from Wilson's GDrive folders (02 環境改造 / 03 照護預防 / 04 生理變化 / 05 心理變化). STAGING ONLY, prod untouched.

**Posts created (all publish, CPT ac_blog):**
| Post | ID | Slug | Cat | Media IDs |
|---|---|---|---|---|
| 家居環境改造 | 1296 | 家居環境改造 (percent-encoded) | 居家安全 16 | 1280-1284 |
| 照顧預防 | 1303 | 照顧預防 | 日常照顧 12 | 1285-1288 |
| 生理變化及應對方法 | 1305 | 生理變化及應對方法 | 醫療照護 15 | 1289-1295 |
| 心理變化及應對方法 | 1307 | 心理變化及應對方法 | 日常照顧 12 | 1299-1302 |

**Pattern followed (per post 1266):** H2 sections, [ac_highlight] sticky boxes, inline images (relative /wp-content/uploads/2026/08/...), .acblog-faq boxed FAQ, video embed (max-width 340px, poster=cover), per-post ac_cta_url (DNH 護士上門評估 + utm_campaign=blog-{home-env,prevention,physio,psych}), Rank Math description + focus keyword, post_excerpt.

**Media:** 4 videos HEVC 1080×1920 → H.264 transcode (host ffmpeg, crf 26, ~5.4-7.4MB each) in `~/projects/angelcare/blog-content/h264/`; images imported with clean names (05 images kept CJK filenames — imported as-is from Drive).

**Bilingual (TRP):** ~190 dict entries added (DELETE+INSERT pattern, §11) covering all article text nodes + split nodes (inline <strong>/<a> split text nodes need per-node keys — full-sentence keys DON'T match). EN SEO titles/descriptions added to trp-rankmath-meta.php blog_map (v1.4, backup .bak-20260818). Verified: 0 CJK in visible EN article text on all 4 posts; EN titles correct; listing EN cards translated.

**Gotchas hit (worth logging):**
1. TRP dict lookup `WHERE status != 0 AND original IN` returns ALL matching rows → duplicates ambiguous. DELETE+INSERT single row per original required (with explicit COLLATE utf8mb4_unicode_520_ci — emoji keys fail collation otherwise).
2. `wp post meta update` on a REVISION id silently redirects to parent (verified again — created 1304 as revision of 1303, polluted 1303 meta; fixed by re-updating 1303).
3. Drive downloads via rclone's shared OAuth client hit quota (403) constantly — workaround: paced direct Drive API calls with backoff.

**⚠️ OPEN ISSUE (Wilson decision pending):** ALL provided images are Chinese infographics with baked-in text (防滑/扶手/消除高差 etc.). EN pages show Chinese in images. Options: accept / provide EN versions / omit on EN / commission re-creations. ZH pages unaffected.

**Screenshots:** `~/projects/angelcare/blog-screens/20260818/` (shot-02-en.png, shot-05-zh.png).
**Source content:** `~/projects/angelcare/blog-content/` (4 folders: docx/html/video notes).
**Prod deploy: PENDING Wilson approval** (after image decision): media files + 4 posts + TRP dict rows + trp-rankmath-meta.php v1.4.

## 7. Changelog — 2026-08-24: TRP EN URLs added to Rank Math sitemap (prod + staging)

**Change:** New mu-plugin `wp-content/mu-plugins/trp-rankmath-sitemap.php` (v1.0) — adds TranslatePress `/en/` variants of every sitemap URL to the Rank Math XML sitemap.
- **Root cause:** Rank Math (1.0.238) has no TranslatePress integration code; "built-in integration" per Rank Math KB = TRP paid SEO Pack (not installed; site runs TRP Free + mu-plugin shims). TRP Free whitelists the `rank_math/sitemap/url` filter for generating other-language URLs (`is_sitemap_path()` bypass list in class-url-converter.php) — hook there, append second `<url>` entry via TRP URL converter, rendered with `new \RankMath\Sitemap\Generator()->sitemap_url()` (recursion-guarded via static flag).
- **Deployed:** staging 8092 + prod 47.52.68.243 (`angelcare_wordpress` container; host mu-plugins dir owned systemd-network:systemd-journal — NOT writable by SSH user; upload via SFTP→host /tmp→`docker cp`→container copy as root).
- **Cache invalidation (both envs):** `rm uploads/rank-math/rank_math_*.xml` + delete `_transient_sitemap_%`/`_transient_timeout_sitemap_%` + reset option `rank_math_sitemap_cache_files` + `rm -rf cache/supercache/*` + `wp cache flush`. NOTE: `\RankMath\Sitemap\Cache::invalidate_storage()` throws WP_Filesystem/FTP error on prod (no FTP creds) — do the file/transient deletion directly instead.
- **Verified prod:** page-sitemap 15 zh + 15 en, post-sitemap 3+3, ac_blog-sitemap 7+7, all XML well-formed, `/en/` pages HTTP 200 + lang="en-US". sitemap_index.xml unchanged.
- **Lesson:** Rank Math file cache (`uploads/rank-math/rank_math_*.xml`) serves stale sitemaps — always clear after sitemap-affecting changes or the change "silently doesn't appear" (hit on staging ac_blog: stuck on Aug 19 file).

## 8. Changelog — 2026-08-25: 廢用症候群 On-Page.ai refresh (staging → PROD DEPLOYED, Wilson GO 14:25)

**Source:** On-Page.ai scan (HK SERP, 廢用症候群, scored 34/100 — thin page) → refresh brief → CTO staging iterations → prod deploy.

**Content changes (final, prod-live):**
- Title → 廢用症候群是什麼？原因、症狀與預防方法全解析 | 宅天使
- Page expanded from thin 113w → ~3,000+ chars; +8 content sections (成因/主要症狀/風險族群/功能退化連鎖影響/評估診斷/康復方案/運動與物理治療/飲食營養) + FAQ extended 5→8 Qs (moved to BOTTOM per Wilson — was mid-page)
- 5 disease images corrected: 肌肉萎縮-2 / 關節攣縮-2 / 痰積聚與肺炎-2 / 便秘-2 / 尿道炎與皮膚感染2 (order verified) — was showing wrong env-* images
- Vicious-cycle diagram added (肌肉萎縮→活動更少→關節僵硬→無法站立→更依賴照顧→進一步失能): circular ring desktop / vertical chain mobile, CSS-only in text-editor widget
- List items: shortened copy + 88% inset width (Wilson), li margin-bottom 14px, left-aligned block (mobile indent fix), line-height 1.45em
- Font change Inter 17px → REVERTED to Roboto 20px/300 (Wilson: "no need to change font type and size")
- FAQ answers: hanging-indent pattern (padding-left 1.1em, list-style-position:inside) — was staircase on mobile

**Incidents hit (see template §5 lessons 31–36):**
- **Elementor renders image widgets by ATTACHMENT ID, not URL field** — staging attachments 1280/1281/1282 pointed to env-* files while widget URLs showed correct names → wrong images rendered. Fixed: synced 5 images from prod, repointed attachments, regenerated thumbnails. Also 肌肉萎縮-2/關節攣縮-2 files were MISSING from staging uploads (404) — known staging-vs-prod media sync gap.
- **Content lives in elementor_library post 275 (body) vs page 263 (title/meta)** — both updated (per §3.2/lesson 30 pattern; 275 is the real render source).
- **FAQ hanging-indent broken** (A: floated mid-screen, wraps jumped to left margin) — fixed with 1.1em container + list-style-position:inside.
- **Appended sections must match original design** (boxed not full_width) — see template lesson 31.
- CTO regex delimiter bug (`#` clashing with `color:#54595F`) briefly emptied list editors — caught, restored from staged version, re-applied. Lesson: delimiter choice in search/replace must avoid `#`.

**Deploy:** staging → prod 2026-08-25 (byte-parity verified, full DB export backup ac-backup-20260825.sql, caches purged WPSC+Redis+CF).

---

## Changelog — 2026-08-29: Two-way merge (Wilson GO, scope-adjusted)

**Deploy staging → prod (3 pages, content-only):** staging pages 1272 長者照護選擇 / 1273 居家安老 / 1274 安老院考慮 (built 08-08) deployed to prod. Prod ID mapping: **1272→1392, 1273→1390, 1274→1391** (1272's ID was free; 1273 = attachment + 1274 = revision on prod → auto IDs; WP 7.x rejects explicit-ID insert for non-existent posts → auto-ID + `$wpdb` byte-exact slug). Method: raw `_elementor_data` + `_elementor_page_settings` extracted from staging via wp-cli (mysql `-e` escapes JSON without `--raw` — trap), base64 payload, `wp eval-file` on prod, `update_post_meta(..., wp_slash($json))` (lesson 13), regenerable metas (`_elementor_css/_elementor_page_assets/_elementor_element_cache/_eael_post_view_count`) deleted. URLs already prod (angelcare.dnow.hk baked in staging DB) — zero URL rewrite needed. All referenced 2025/04 images already on prod uploads — no media sync. Verified: 3 URLs 200 + exact staging markers (titles, H1s, data-elementor-id 1392/1390/1391 + templates 129/152), all imgs 200. WP Super Cache flushed (`wp cache flush`; `wp super-cache` CLI not registered).

**Pull prod → staging (1 page):** 收費計畫 1249 (prod 13/8 version, _elementor_data 46,347B + post_content 23,124B, slug pricing-plan) pulled to staging 1249 (replacing 3/8 version). Same raw-write method reversed; host-adaptive mu-plugin v2 rewrites angelcare.dnow.hk → ac-staging on output (0 old-domain refs in rendered HTML). Verified: staging /pricing-plan/ 200, markers byte-match prod (年費計劃 ×5, 消耗品管理gif ×6, 註冊護士上門評估 ×2).

**PARKED (untouched):** 1275 評估工具 (both envs; prod 1275 = revision of template 275 — collision confirmed but not triggered), draft page 2 家居環境改造. ⚠️ **Notes for Wilson:** ① the 3 deployed pages link to /評估工具/ which 404s on prod until 1275 is deployed (parked); ② prod nav menu has NO items for these pages (staging menu items 1276-1279→1272-1275 NOT deployed — menu changes out of scope); ③ WP core 7.1 + 60 prod-only media files NOT synced (not approved — flagged only); ④ EN (TRP) translations for the 3 new pages not in dictionary yet → /en/ shows Chinese until translated.

**Backups:** `~/staging/angelcare-migrate/db-dumps/ac-prod-before-3pg-deploy-20260829.sql.gz` (prod, via DNH host mysql-client 8.0) + `ac-staging-before-1249-pull-20260829.sql.gz` (staging).

---

## Changelog — 2026-08-29: Option A REPLACE — 3 new pages replace old combined page 636 (Wilson GO)

**Prod (angelcare.dnow.hk) + staging 8092 mirrored:**
1. **636 從居家安老到院舍安老，全面了解長者照護選擇 RETIRED** — unpublished (draft, kept for rollback) on both envs. **Recommendation implemented: 301 redirect (not dead 404)** → new hub 長者照護選擇, via new mu-plugin `ac-636-redirect.php` (env-agnostic: looks up hub by slug; matches `?page_id=636`/`?p=636`/old-slug path). Verified: prod + staging `?page_id=636` and old slug → 301 → hub. (Note: 636 was an empty shell rendering elementor_library template 638, condition-locked to page 636 — template now orphaned, harmless.)
2. **Nav menu (MAIN, term 8):** prod — trashed item 812 (→636), added hub 長者照護選擇→1392 (item 1393, ord 11) + children 居家安老→1390 (1394) + 安老院考慮→1391 (1395); verified hub+dropdown render in header nav, 636 entry gone. Staging — force-deleted item 812 (first delete attempt refused: `wp post delete` needs `--force` for nav_menu_item); existing items 1276-1279 untouched (incl. parked 1279→1275).
3. **TranslatePress dictionary (`wp_trp_dictionary_zh_hant_en_us`):** 297 key strings inserted/updated on prod (252 upd + 45 ins) and staging (292 upd + 5 ins) — all page content incl. long paragraphs, headings, CTAs, pricing table. Verified /en/ renders English on both (body ~98% EN; residual CJK = sitewide header/footer labels + title tags + img alts, pre-existing). **Granularity lesson: TRP originals are per-line/per-paragraph (matches wp-cli `--format=json` extraction), NOT whole-widget blocks — calibrated against 1249's existing entries.**
4. **EN SEO titles:** prod `trp-rankmath-meta.php` map was keyed to STAGING IDs 1272/1273/1274 → remapped to 1392/1390/1391 (title + description maps; backup `trp-rankmath-meta.php.bak-20260829`). Staging v1.3 already correct. Verified /en/ `<title>` = "Elderly Care Options: Home vs Care Home - AngelCare" / "Ageing at Home: Care Options & Services - AngelCare" / "Considering a Care Home: Waiting Time & Fees - AngelCare".
5. **Homepage CTA fix:** button in home template 248 still linked old 636 slug → href updated to new hub (raw `$wpdb` JSON write, 1 link; element cache + WPSC purged).

**Backups:** `ac-prod-before-636-retire-20260829.sql.gz` + `ac-staging-before-636-retire-20260829.sql.gz` (both in `~/staging/angelcare-migrate/db-dumps/`).
**Open flags:** ① parked 1275 評估工具 — its menu item (staging 1279) still points at it; prod has no 1275 menu item; links from the 3 new pages to /評估工具/ 404 until 1275 deploys (301 plugin only covers 636). ② `/en/` variants of old 636 not 301-covered (TRP-rewritten URL path) — minor. ③ 636's Rank Math title/desc map entries left in mu-plugin (dead, harmless).

## 2026-09-03 — llms.txt v1 deployed (AI-agent discovery, approved by Wilson via CBO)

- **File:** static `llms.txt` at `/data/angelcare_wordpress/llms.txt` (outer nginx 1.10.3 serves directly; bypasses WP/Redis/WPSC — no purge needed; byte-identical verified immediately).
- **Content:** approved v1 (10 core pages + facts + sitemap link; 2026-09-03). Source-of-truth: `~/projects/angel-care/ac-mu-plugins/llms.txt`.
- **Verified:** `curl -I https://angelcare.dnow.hk/llms.txt` → 200, text/plain; charset=utf-8, 1997B; source↔live cmp IDENTICAL; homepage 200 (no regression). Was 404 pre-deploy.

## 12. Changelog — 2026-09-04: 廢用症候群 layout incident (element-cache poisoning) — FIXED

**Report:** Wilson 22:04 HKT — https://angelcare.dnow.hk/廢用症候群/ 排版全部亂晒.

**Root cause (verified):** Elementor 3.29.2 "Element Cache" experiment (`e_element_cache`) was ACTIVE on prod (staging: OFF). With it on, frontend prints widgets as `[elementor-element k=… data=<base64>]` shortcode tokens (element-base.php:454-458) that a later pass decodes via registered shortcode (modules/element-cache/module.php). When that pass doesn't run, raw tokens leak into HTML **and the poisoned output gets stored back into `_elementor_element_cache` postmeta**.
- Trigger sequence: 12:02 beacon hotfix deploy flushed Elementor CSS + WPSC → lazy per-page regen 20:07–21:59 HKT (visits by Wilson 21:46–22:05, per WPSC dir mtimes) → 275's 21:59:33 render stored poisoned cache (19 tokens) + WPSC snapshotted garbage → Wilson saw 亂晒. Renders clean again from 22:07. Rank Math Pro auto-update 20:07 same minute (coincidence, not causal).
- Amplifier/ambient: prod (and staging) missing `wp_trp_original_strings` table → TRP throws DB errors at shutdown buffer pass on every render (log 2026-09-04 22:07:29) — prime suspect for breaking the buffer chain Elementor's decode depends on. Both envs affected equally.

**Content divergence discovered (NOT from us):** prod template 275 `_elementor_data` modified 2026-08-31 15:31 HKT via non-Editor write (no revisions, no `_elementor_edit_last`, nothing in MARKETING-LOG; server shared w/ other WP devs; prod users admin/cbo/ChongJoey-editor). 連鎖影響 section: ring diagram (.ac-cycle, Aug-25 On-Page design) → 6 icon-boxes (prod-only variant). Only non-blog post changed on prod since Aug 25.

**Fix applied (Wilson GO AP-20260904-02 + D=restore, 22:21 HKT):**
1. Restored prod 275 `_elementor_data` from staging (byte-parity, md5 a67fd05ace26145d49199187e49d92ed == staging; raw `$wpdb->update`, NOT update_post_meta — slashing lesson §5/template).
2. Elementor experiment `e_element_cache` → inactive + `elementor_element_cache_ttl` → disable (prod parity w/ staging which had it OFF).
3. Deleted all 16 `_elementor_element_cache` postmeta rows (backup: container /tmp/ac-fix-20260904/ + local rollback-20260904/).
4. Purged WP Super Cache tree + Redis db3 (wp_cache_flush + FLUSHDB) → re-ran post-275 CSS regen AFTER flush (first regen used stale Redis-cached data → identical old file; second regen = 40,645B == staging size ✓).
**Verified:** ZH/EN 0 tokens, ring present, iconbox=0 on 275; home/功能介紹 clean (their icon-boxes are legit); page height 9832→7192px (= staging); pixel-level band parity prod vs staging; CSS ver bumped 1788531842.

**Known follow-ups:** ① EN translation gap: 8 Aug-25-era section H2s untranslated on BOTH envs (TRP dict rows exist w/ translated='' status=0, e.g. id 2446 身體功能退化嘅連鎖影響) — CBO content task. ② TRP missing original_strings tables (ambient, both envs). ③ Prevention probe script ready: ~/scripts/ac-element-cache-check.sh (wiring into weekly health needs Wilson OK — cron rule). ④ No prod DB backup Aug 19→Sep 4 — recommend prod snapshot cadence.
**Backups/rollback:** container /tmp/ac-fix-20260904/ (pre-change 275 data md5 1d7063be…, 16 metas JSON, options) + workspace tmp-angelcare-investigation/rollback-20260904/.

## 13. Changelog — 2026-09-04: AC Beacon Phase 5 prod deploy (option-b all-click)

**Applies:** `ac-gclid-beacon.php` mu-plugin (legacy gclid-only → option-b v1.1, all-click + assign-organic)
**Status:** ✅ DEPLOYED + VERIFIED (12:02 HKT) per AP-20260904-01 (thread closed 12:05)

### What changed
- `ac-gclid-beacon.php` replaced with option-b v1.1 (sha256 e6e51bb5e5dc… = canonical). File grows 3810→4909B.
- No backend change (beacon.js prefix `'AC'` live since 9/2 23:07, already serving ASCII codes AC-09-048..055).
- Caches flushed: WPSC tree + Elementor CSS + Redis (host-level nginx front runs WPSC; files owned by systemd-network; delete via `docker exec angelcare_wordpress sh -c rm -rf`).

### Verification
- Clean prod URL `curl https://angelcare.dnow.hk/` → serves `assign-organic` (was absent pre-deploy).
- `POST /` → `{"code":"AC-09-056"}` ✅
- `POST /assign-organic` → `{"code":"AC-09-057"}` ✅
- Phase 6 health: NORMAL, last_seq 57, gap 0, organic 23/57 tracked.

### Rollback
- **Prod backup:** `/tmp/ac-gclid-beacon.php.bak-prod-20260904_1158` (3810B, md5 f4b72f0ba… — NOTE: /tmp is ephemeral on prod; persist to `/data/angelcare_wordpress/backups/` for durability).
- **Local durable copy:** `~/projects/angel-care/backups/beacon-phase5-20260904/` (legacy 3810B + v1.1 canonical 4909B).
- Restore: copy file in place (`cat backup | sshpass -e ssh angelcareuser@47.52.68.243 'cat > /data/…/ac-gclid-beacon.php'`) then `docker exec angelcare_wordpress php -l` + cache flush.

### Incident note (written-test corruption, 11:57 HKT)
- **During deploy:** an overwrite-permission test was run directly against the **live prod file** (`/data/…/ac-gclid-beacon.php`), accidentally writing test content into it. The file was corrupted (md5 d41d8cd9… = empty). `scp` restore failed ("Connection closed"). Restored via `cat backup | sshpass -e ssh … 'cat > file'`. Downtime: ~1 min (empty file → no JS beacon on prod pages).
- **Lesson:** NEVER test writes against live prod files. Test on a `/tmp` copy. Verify write permissions with `touch /tmp/test` not against the real path. Backup first. Use `cat | ssh cat >` as primary upload method (scp dropped mid-transfer once).

### Deploy safety rules (learned 2026-09-04)
- Prod is single container `angelcare_wordpress` (nginx+php-fpm, bind-mount /data → /var/www/html, host port 8096). No `_php8` container.
- mu-plugins/ dir owned by systemd-network: CANNOT create NEW files as angelcareuser, but CAN overwrite in-place files owned by angelcareuser.
- ssh access via `sshpass -e ssh -o StrictHostKeyChecking=no angelcareuser@47.52.68.243` (sshpass 1.09 is available locally).
- No `php` in host PATH — use `docker exec angelcare_wordpress php -l <path>` for syntax check.
- WP Super Cache + page cache files owned by systemd-network → delete via `docker exec angelcare_wordpress sh -c "rm -rf /var/www/html/wp-content/cache/*"`.
- scp is unreliable (dropped mid-transfer 2026-09-04) — prefer `cat local | sshpass -e ssh … 'cat > remote_file'` then `sshpass -e ssh … 'cat temp > destination'`.
