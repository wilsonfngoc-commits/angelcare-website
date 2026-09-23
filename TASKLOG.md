
## 2026-08-25 — /download page iOS link: old iTunes US format → HK App Store (PROD DEPLOYED, Wilson GO via CBO)

- **Task:** replace `itunes.apple.com/us/app/宅天使-angelcare/id6473672676` → `https://apps.apple.com/hk/app/宅天使-angelcare/id6473672676?l=en-GB` on angelcare.dnow.hk/download/ (page 1255 prod / 1256 staging).
- **Staging check:** ac-staging.hkdrnow.com/download/ ALREADY had the new HK link (newer page version: button widget e5f6a7b + text-editor f6a7b8c + device-swap JS) — no change needed, verified as reference state.
- **Prod root cause:** page 1255 is an older version — image-badge widget (e5f6a7b, image widget) with `settings.link.url` = old iTunes US URL, stored in `_elementor_data` with escaped-JSON form (`https:\/\/itunes.apple.com\/us\/app\/\u5b85\u5929\u4f7f-angelcare\/id6473672676`).
- **Apply (prod):** raw `$wpdb->update` on meta_id 12422 (bypasses wp_unslash/wpjam hooks — CJK \uXXXX escape preserved). Byte-exact str_replace of escaped form → `https:\/\/apps.apple.com\/hk\/app\/\u5b85\u5929\u4f7f-angelcare\/id6473672676?l=en-GB`. JSON validated, re-read confirmed. Purged: ALL `_elementor_element_cache` rows (17), Redis object cache, WP Super Cache supercache dir.
- **Verify (prod, curl+grep):** `apps.apple.com/hk` ×1 (exact target URL, raw-CJK form) ✅ · `itunes.apple.com` ×0 ✅ · `play.google.com` ×1 (Android badge untouched) ✅.
- **Backups:** `~/projects/angelcare/backups/ac-page1255-elementor-data-backup-20260825.txt` (3087 bytes, pre-change raw meta) + host/container /tmp copies.
- **Note:** prod page is an older structure vs staging (image badge vs button+JS). Task scope = link swap only; staging-structure parity on prod is a separate task if wanted.

## 2026-08-25 — /download smart-redirect parity: staging 1256 version → prod 1255 (PROD DEPLOYED, Wilson GO via CBO)

- **Bug (Wilson, iPhone):** prod /download/ forwarded to Android Play on iOS. Root cause: prod 1255 = old image-badge version (apple.png/google_play.png, no UA-detection JS) + prod ac-blog.php footer swap-JS keyed to `is_page(1256)` (staging ID) → never fired on prod.
- **Fix (2 parts, both verified):**
  1. Prod 1255 `_elementor_data` replaced with staging 1256 smart version (heading 下載 AngelCare 宅天使 App / invite_logo 200px / description / button e5f6a7b id=acblog-app-main → HK App Store / text-editor f6a7b8c acblog-app-links). Normalized: ac-staging URL → angelcare.dnow.hk + attachment id 1253 → 1252 (prod invite_logo). Raw $wpdb update, JSON validated. Old iTunes link + ac-staging refs = 0.
  2. ac-blog.php (staging+prod): `is_page(1256)` → `is_page(array(1255,1256))` in footer-JS + enqueue conditions; CSS `.page-id-1255, .page-id-1256` selectors.
- **Caches purged:** _elementor_element_cache (6 rows) + Redis + WPSC supercache.
- **Verify:** prod curl — smart button 1, swap JS 1, prod logo URL 1, ac-staging 0, old badges 0, itunes 0, apps.apple.com/hk 3, play.google 2. Browser UA test: iOS→App Store href + links hidden ✅ · Android→Play href + links hidden ✅ · Desktop→App Store href + both links shown ✅.
- **Backups:** `~/projects/angelcare/backups/ac-page1255-elementor-data-backup-20260825b.txt` (pre-parity data), `ac-blog.php.pre-parity`; prod host backup dir `mu-plugins/backup-2026-08-25-download-parity/`.
- **Note:** staging ac-blog.php updated to same file (staging==prod parity). Page IDs 1255/1256 both handled in one file.

## 2026-08-25 — Blog batch 6/7/8/9 PROD DEPLOY (Wilson GO via #coding-cto)

- **Posts created on prod:** 1351 老人難溝通點算（日常照顧）· 1352 照顧者的身心準備（日常照顧）· 1353 照顧者的自我防護（行動復健）· 1354 照顧計劃（日常照顧）— dates 09:00/10:00/11:00/12:00, same content/meta/CTAs as staging.
- **Media:** 28 files synced staging→prod (24 jpg + 4 mp4) + .webp siblings generated locally + rsync'd (prod nginx trywebp quirk). Imported as attachments prod IDs 1323–1350 (covers: 06=1323, 07=1328, 08=1333, 09=1342; set as _thumbnail_id). All 28 media URLs verified 200.
- **ID mapping (staging→prod):** posts 1362→1351, 1354→1352, 1355→1353, 1356→1354; attachments 1357→1323 … 1350 (per-file mapping in session).
- **Category cleanup mirror:** deleted junk term "Select 日常照顧" (17); reassigned 1310 生理變化→日常照顧 (was 醫療照護), 1318/1319→日常照顧. Final: 日常照顧 9, 行動復健 1, 居家安全 1, 醫療照護 0.
- **Caches:** _elementor_element_cache (5 rows) + Redis + WPSC supercache purged.
- **Verify (cache-bust ?nb=<ts>):** 4/4 prod URLs 200, correct H1/chip, hero present, FAQ ×5, video present, tables (06 需求→做法→案例 ×5 rows, 09 對照表), 0 ac-staging refs. Browser 390px: 0 overflow, 0 broken images, video/table fit. All media 200.
