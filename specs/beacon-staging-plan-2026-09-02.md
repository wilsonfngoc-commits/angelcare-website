# Beacon Stage Burn Plan — Angel Care (AC) → DNH rollout template

**Spec owner:** CBO
**Status:** COMMITTED (Wilson approved spec 21:49 + lock + Phase 2 GO 22:03; doc write 21:42; parallel to dnacpr Phase 6 observe)
**Decision context:** Wilson 21:29 #marketing — "Let work on ac" + "The volume from ac to DNH is small at the moment right?" → CTO confirmed volume near-zero (0 codes/30d, 0 WATI AngelCare messages/30d) → AC Option (b) rollout needed to **close the cross-site attribution loop** (AC content builds trust → DNH paid conversion).

---

## 0. TL;DR

| Decision | Value |
|---|---|
| **Burn strategy** | (i) Per-visit sequence burn — same as dnacpr; format `[AC-09-NNN]` (single combined field, no emoji, mirrors dnacpr Option C) |
| **Coverage** | Option (b) — ALL clicks (paid / organic / direct / social / email) carry short code |
| **Roll-out protocol** | staging → e2e test → Wilson approve → deploy → e2e test → observe. 6 phases (mirrors dnacpr spec) |
| **Backend counter impl** | Same as dnacpr — local Node.js `:8791` + `code_sequences` atomic upsert (confirmed by CTO 18:36 ground-truth). Brand enum already supports `angel-care`. |
| **Frontend injection path** | **mu-plugin** `ac-gclid-beacon.php` at `wp_footer` priority 99 (consistent with AC's existing `ac-wa-fab.php` + `ac-outbound-utm.php` patterns — server-side, curl-verifiable) |
| **Cascading risk #1** | Async race — same as dnacpr; `preventDefault()` + `await fetch` + manual `window.location.href` patch |
| **Cascading risk #2** | AC staging missing beacon JS + CORS allowlist — CTO 30 min deploy before Phase 2 |
| **Cascading risk #3** | AC dual-pillar split — `[AC-09-NNN]` single field + `landing_page` column for pillar-level granularity (no `AC-INFO-09-NNN` vs `AC-PAID-09-NNN` sub-code needed) |
| **Cross-site dependency** | AC → DNH outbound already tagged `[R-AC]` per REGISTRY.md 9/2 entry (live on prod via `ac-outbound-utm.php`). AC beacon attribution + R-AC = closes attribution loop |

---

## 1. Background — why this spec

**Wilson 2026-09-02 21:29 (forwarded + relayed):**
> "Let work on ac ... The volume from ac to DNH is small at the moment right?"

**Recon confirmed (CBO 21:30):**
- AC uses **WordPress + Elementor** (NOT Astro like dnacpr) — see `angel-care/WORDPRESS.md` §1-3
- Beacon backend `beacon.hkdrnow.com` = local Node `:8791` (per CTO 18:36 ground-truth), brand enum already includes `angel-care`
- `ac-gclid-beacon.php` mu-plugin **already in production** for paid (gclid) clicks (per CTO 21:30 snapshot)
- **AC staging missing:**
 - Beacon JS absent (`ac-gclid-beacon.php` not on migrate container `:8092`)
 - CORS fail (`ac-staging.hkdrnow.com` NOT in beacon allowlist — only prod `angelcare.dnow.hk` is)
 - AC staging URL: `https://ac-staging.hkdrnow.com` (NOT `https://ac.dnow.hk` — staging zone lives on hkdrnow.com subdomain)
- AC volume (CTO 30-day snapshot 21:30):
 - `ac_beacon_codes_30d` = **0** (vs dnacpr 50) — pre-Option-b, beacon fired 0 codes because no gclid traffic
 - `wati_angelcare_mentions_30d` = **0** (no WATI messages with AngelCare text)
 - `wati_r_tagged_messages` = **0** (R-tags deployed 9/2 — no historical yet)
 - `wati_total_inbound_30d` = 1717 (DNH-dominant)
- AC production WA pages: home / pricing-plan / 全新升級 (paid-services) / 功能介紹 (features) / 常見問題 (faq) — 5 pages with 1 FAB each
- AC outbound (`ac-outbound-utm.php`) live on prod — tags DNH outbound with `utm_source=angelcare`

**Net: AC is low-traffic upstream funnel (trust engine) → DNH paid conversion. Small absolute AC→DNH attribution = BIG insight (high-intent AC visitors who DID cross over). Option (b) rollout = closes the loop.**

---

## 2. Architecture (final state)

### 2.1 Short-code format

**ACTUAL format (mirrors dnacpr Option C):**

| Brand | Format | Example | Channel prefix? |
|---|---|---|---|
| **AC (test bed)** | `[AC-<YY>-<NNN>]` | `[AC-09-152]` | No prefix — sequence burn on NEW gclid (atomic upsert + UNIQUE gclid) |
| **AC organic (no gclid)** | `[AC-<YY>-<NNN>]` | `[AC-09-152]` | Same scheme — organic path via `/assign-organic` (per dnacpr Option A1 hotfix) |
| **dnacpr (deployed Phase 5)** | `[AMD-<YY>-<NNN>]` | `[AMD-09-152]` | Mirrors AC pattern, AMD prefix |
| **DNH (deployed Phase 1-2)** | `[🏥<YY>-<NNN>]` | `[🏥09-152]` | Legacy emoji (still live, alias-resolved to display via backend) |

**Rationale:** Pure sequence (no channel prefix) = simpler counter backend. AC pillar-level granularity derived downstream via `(brand, yyyymm, seq, landing_page)` join. `[AC-09-XXX]` mirrors `[AMD-09-XXX]` style for cross-brand consistency.

**WATI parse regex:** `\[AC-(\d{2})-(\d{1,3})\]` — single combined field, no emoji, no dual prefix (Wilson confirmed 20:22).

### 2.2 Click → counter → WA chain (AC-specific injection points)

```
[user on angelcare.dnow.hk page clicks WA button]
        │
        ▼
[WordPress mu-plugin: ac-gclid-beacon.php @ wp_footer priority 99]
        │  preventDefault() on the click
        │  POST https://beacon.hkdrnow.com/
        │   { gclid, fbclid, utm_*, referrer, url, brand: "angel-care" }
        │  (organic path → POST https://beacon.hkdrnow.com/assign-organic
        │   { client_ref: ac_<ms>_<rand>, brand, ts (ms), url })
        │
        ▼
[Beacon backend — LOCAL Node.js :8791 (systemd, single process)]
        │  code_sequences atomic upsert (brand='angel-care', yyyymm=202609)
        │    INSERT ON CONFLICT DO UPDATE SET last_seq = last_seq + 1
        │    RETURNING last_seq
        │  code = "AC-09-<seq>" → e.g. "AC-09-001"
        │  INSERT INTO gclid_codes (code PK, gclid UNIQUE, click_ts, ...)
        │  return { code: "AC-09-001" }
        │
        ▼
[Beacon client JS — receive code]
        │  patch href text param: "[AC-09-001] <preset text>"
        │  window.location.href = patchedHref  ← fires WA click
        │
        ▼
[WhatsApp app opens with prefilled text]
[user sends]
        │
        ▼
[WATI webhook → WATI DB → brandops.wati_messages]
        │  parse "[AC-09-001]" from message text
        │  join code_sequences + gclid_codes on (brand='angel-care', yyyymm, seq)
        │  → full attribution (channel, campaign, landing_page, organic vs paid)
```

### 2.3 Backend counter — REUSE dnacpr impl (per CTO 18:36 ground-truth)

| Layer | Detail (mirrors dnacpr) |
|---|---|
| **Process** | local Node.js service `beacon.js` at `_shared/_frameworks/_dashboard/beacon/`, systemd user unit `beacon.service`, single process |
| **Port** | `127.0.0.1:8791` (localhost only — public exposure is via Cloudflare proxy at `beacon.hkdrnow.com`) |
| **Counter SQL** | `INSERT INTO brandops.code_sequences (brand, yyyymm, last_seq) VALUES($1,$2,1) ON CONFLICT (brand,yyyymm) DO UPDATE SET last_seq = last_seq + 1 RETURNING last_seq` |
| **Storage** | `brandops.code_sequences (brand PK, yyyymm PK, last_seq)` + `brandops.gclid_codes (code PK, gclid UNIQUE, click_ts, campaign, landing_page, expires_at, client_ref)` (client_ref added per dnacpr Option A1) |
| **Idempotency** | UNIQUE on `gclid_codes.gclid` — same gclid re-click returns same code, NEVER burns 2 numbers |
| **Multi-instance** | Single process → no race risk |
| **Monthly reset** | `(brand, yyyymm)` PK → automatic reset when month rolls over |
| **CBO DB access** | Same as dnacpr — CTO has docker `:5433` access; CBO does NOT have direct access for this spec |

**Backend changes needed for AC:** **ZERO** — brand enum already includes `angel-care` (per dnacpr 18:36 verification).

---

## 3. Six-phase roll-out plan (AC-specific)

### Phase 0.9 — Channel coverage map (clarification per Wilson 21:49)

**Per Wilson question "Does this spec cover meta and google ads and those from organic search": YES, all 3 covered. Here's how each maps to Beacon attribution:**

| Source | Beacon attribution path | Code assigned? | Cross-site tag? |
|---|---|---|---|
| **Google Ads (paid search, PMax)** | URL carries `gclid` → legacy `/` endpoint → `gclid UNIQUE` idempotency + atomic upsert | ✅ YES | (if from AC blog → `[R-AC]` if link to DNH) |
| **Meta Ads (FB + IG CTWA / paid)** | URL carries `fbclid` + `utm_source∈{facebook,fb,instagram,ig,meta}` → atomic upsert | ✅ YES | (same cross-site) |
| **Organic search (Google/Bing/百度)** | No gclid/fbclid → `POST /assign-organic` with `client_ref=ac_<ms>_<rand>` → atomic upsert | ✅ YES | (same cross-site) |
| **Direct (typed URL)** | No referrer → organic path | ✅ YES | (same cross-site) |
| **Social (IG/X/LinkedIn organic)** | Referrer ∈ social domain → organic path | ✅ YES | (same cross-site) |
| **Email (newsletter click)** | Referrer ∈ mail domain OR utm_source=newsletter → organic path | ✅ YES | (same cross-site) |
| **Cross-site AC → DNH `[R-AC]`** | `ac-outbound-utm.php` tags utm_source=angelcare (already live per REGISTRY.md 9/2) | n/a (attribution-side) | ✅ YES (always) |

**Key insight: Option (b) "all-clicks carry code" means ANY click on AC's WA button = Beacon code, regardless of source. Channel attribution derived downstream via `(brand, yyyymm, seq, channel, source, campaign)` join on `code_sequences` + `gclid_codes` columns + UTMs + referrer.**

**Reporting layer (post-Phase 6):**
- CBO weekly report groups by `channel` field derived from gclid/fbclid/utm_source/referrer
- `gclid IS NULL AND fbclid IS NULL AND utm_source IS NULL` → organic
- `gclid IS NOT NULL` → google_ads (sub-tag by utm_medium: cpc/search, pmax/performance-max)
- `fbclid IS NOT NULL OR utm_source∈{facebook,fb,instagram,ig,meta}` → meta
- `referrer ∈ {google.com, bing, yahoo, baidu}` → organic search (sub-tag by engine)
- Cross-site `[R-AC]` + AC beacon seq → full AC → DNH conversion path

### Phase 1 — Deploy Beacon to ac-staging
**Owner:** CTO (per SOUL.md §"Site/Code Task Routing")
**Inputs:**
- Existing `ac-gclid-beacon.php` mu-plugin (already on prod) — COPY to staging `:8092` container
- Backend endpoint `beacon.hkdrnow.com` — **CORS allowlist update** to include `ac-staging.hkdrnow.com`
- AC staging has Elementor template pages → verify mu-plugin path works with Elementor (per `WORDPRESS.md` §2 elementor_library template warning)
**Exit criteria:**
- `curl -sSL https://ac-staging.hkdrnow.com/` → 200 + contains `beacon.hkdrnow.com` reference (1 hit)
- Content marker present (per AC staging smoke check pattern)
- `POST beacon.hkdrnow.com/` from `ac-staging.hkdrnow.com` origin → 2xx (CORS preflight pass)
- No staging-only URLs leak (per WordPress lesson §3.1)
**CTO ETA:** 1 day
**Wilson gate:** none — execution per approved direction

### Phase 2 — Automated e2e click test (staging)
**Owner:** CBO (this turn script)
**Tests:**
- 5 AC staging pages (home / pricing-plan / 全新升級 / 功能介紹 / 常見問題)
- 5 device profiles (curl with UA + viewport spoof)
- Per page × per device: 1 click → assert `[AC-09-NNN]` appears in WA link `?text=` param
- Counter monotonicity check
- Idempotency: same gclid → same code
**Tools:** Reuse `~/projects/amdcpr/scripts/phase2-e2e.sh` template, adapt for AC URL + brand enum
**Exit criteria:**
- 25/25 valid `[AC-09-NNN]` responses
- Counter monotonic within `(angel-care, yyyymm)` bucket
- No 5xx from beacon.hkdrnow.com
**CBO ETA:** 1 day
**Wilson gate:** none — automated test

### Phase 3 — Manual internal test (staging)
**Owner:** Wilson + CBO
**Tests:**
- 30 real human clicks across 10 AC staging pages (3 per page)
- Each click: open WA, send `[AC-09-XXX] <preset>` to WATI test contact
- Verify WATI inbox receives + DB logs `assign_code`
- Cross-check: WATI DB `assign_code` matches beacon_log seq (no async race)
- Verify counter monotonic: gap < 100/day
**Note:** AC volume is small (~10 clicks/day expected), so test window can be shorter than dnacpr (2 days vs 4 days)
**CBO ETA:** 1-2 days
**Wilson gate:** none — observation report

### Phase 4 — Wilson staging review
**Owner:** Wilson (read-only review)
**Inputs:**
- Phase 2 + Phase 3 reports
- Sample WATI inbox conversations (5 messages with `[AC-09-XXX]` + preset)
- Beacon health dashboard screenshot
**Decision:** APPROVE / MODIFY / REJECT
**Wilson gate:** **MANDATORY** — no prod deploy without explicit approve

### Phase 5 — Deploy to angelcare.dnow.hk production ✅ COMPLETE (2026-09-04 12:02)
**Owner:** CTO — **DONE** per AP-20260904-01. See ac-phase5-prod-gate-recommendation for full evidence. Option-b v1.1 mu-plugin live on prod.
**Legacy backup:** prod /tmp/ac-gclid-beacon.php.bak-prod-20260904_1158 (3810B).
**Do not re-execute.**
**Actions:**
- **Pre-Phase-5 cron pause** (per dnacpr incident 20:00 + 20:24 lesson) — comment out `0 */4 * * * deploy.sh` BEFORE deploying
- **Save `dist/` aside** to `dist-ac-<date>/` (per dnacpr Option C protection pattern)
- Deploy via `wp-cli` + Elementor data edit on prod (per `WORDPRESS-LOCAL-DEV.md` §3 prod pattern)
- Post-deploy smoke check: HTTP 200 + content marker + Beacon JS + WATI event
- **AC staging stays on Option C** for ongoing iteration (no rollback needed)
**Exit criteria:**
- Live `angelcare.dnow.hk` pages render Beacon client JS
- Live click test (CBO + 1 user): `[AC-09-XXX]` appears in WATI inbox
- 24h soak: no 5xx errors, counter monotonic, gap < 100
**CTO ETA:** 0.5 day
**Wilson gate:** none — pre-approved in Phase 4

### Phase 6 — Observe window + DNH rollout prep
**Owner:** CBO
**Duration:** 2-4 weeks observation (parallel to dnacpr Phase 6)
**Daily checks:**
- `code_sequences.angel-care.202609` counter monotonic, gap < 100/day
- WATI DB parse success rate = 100% (every `[AC-09-XXX]` decodes)
- No duplicate seq in WATI DB
- Async race incidents: zero
**Weekly:** Counter health report to Wilson via #marketing
**Cross-site observation:**
- AC → DNH `[R-AC]` outbound tags (already live per `ac-outbound-utm.php`) — track conversion lift
- AC beacon attribution + R-AC = full attribution loop measurement
- Goal: measure Stage 1 (AC content) → Stage 2 (DNH paid) conversion lift

---

## 4. Cascading effects + mitigation

| Touch point | Effect | Mitigation | Cost |
|---|---|---|---|
| **Beacon backend restart / crash** | Counter may skip; WATI messages continue; DB seq jumps | Atomic upsert skip-on-fail safe; counter monotonicity health check | $0 |
| **Multi-instance race** | n/a — single Node.js process | n/a | $0 |
| **Async race (click before DB upsert returns)** | WATI sees `[AC-09-XXX]`, DB row stores XXX (UNIQUE gclid guarantees 1:1) | `preventDefault()` + `await fetch` + manual `window.location.href` rewrite | 1 day CTO |
| **Monthly recycle** | Counter resets each month when `(angel-care, yyyymm)` PK rolls over | Bake `YY` into code prefix (`AC-09-152`) — already done in proposed format | 0 |
| **Organic / direct fallback (no gclid)** | No gclid to key idempotency on. Need backend support | **REUSE dnacpr Option A1 hotfix** — `POST /assign-organic` accepts `{client_ref, brand, ts, url}` | 0 (already implemented) |
| **WATI parse failure** | Option (b) means EVERY message has prefix. Old parse `[...\]?\]` works; new edge: agent trims prefix in reply | Re-train agents (Wilson handles); monitoring alert if WATI DB has `[AC-09-XXX]` orphan rows | 1 day CBO monitoring |
| **AC vs DNH vs dnacpr format consistency** | Three brands with three formats (AC-09-152, AMD-09-152, 🏥09-152) | Acceptable — each brand has its own prefix letter; WATI parse regex per brand; alias resolver unifies display if needed | 0 |
| **AC staging CORS fail** | `ac-staging.hkdrnow.com` NOT in beacon allowlist (per CTO 21:30) | CTO add allowlist entry (5 min) | 0.1 day CTO |
| **AC Elementor template pages** | Some AC pages render from `elementor_library` template post, NOT page post (per WORDPRESS.md §2 + §3.1 lesson) | Inject Beacon JS via mu-plugin `wp_footer` (server-side) — not Elementor snippet (which would only fire on pages, not templates) | 0 |
| **Cross-site `[R-AC]` attribution loop** | AC + Beacon + R-AC = full AC→DNH funnel measurement | Already live per REGISTRY.md 9/2 entry; just need AC Beacon attribution to fill the upstream | 0 (infrastructure already in place) |
| **GDPR / PDPA** | Short code is non-PII (no keyword leak, no channel leak) | Existing; update privacy policy: "we assign a random sequence number to your click for analytics" | 0.5 day CBO |
| **DB read access** | CBO cannot self-serve DB reads (no docker access); needs CTO forward for every monitor check | CTO runs reads, forwards via #marketing or session. Same pattern as dnacpr | Recurring 0.1 day/wk |
| **Channel attribution (Meta / Google Ads / organic) gap risk (added 2026-09-02 21:49 per Wilson ask)** | Spec covers all channels implicitly, but explicit channel coverage map (Phase 0.9) ensures CTO backend + frontend both fire Beacon for every source. Reporting layer derives channel from gclid/fbclid/utm_source/referrer via post-Phase 6 reporting cron. | ✅ Addressed via explicit §2.4 channel coverage map + §3 Phase 0.9 (added 21:49) | 0 (already in pattern) |
| **`getBrand()` regex bug (OBSERVED 2026-09-02 23:10)** | Backend `getBrand()` regex pattern `/angelcare\./` did NOT match staging URL `ac-staging.hkdrnow.com` (dot before `ac-` doesn't match), causing first organic test to fall through to **doctornow** brand and burn code `🏠09-199`. Same risk exists for DNH: `/doctornowhome\./` won't match `dnh-staging.hkdrnow.com`. | **DNH FIX:** Use `/(doctornowhome|dnh-staging)/` regex pattern in `beacon.js`. **AC FIX:** Use `/(angelcare|ac-staging)/` (already applied 23:10). **Mitigation:** every staging brand pattern must include the staging subdomain variant. | 0.5 day CTO (regex update + verify) |
| **Cache stale after prod deploy (OBSERVED 2026-09-02 23:10)** | AC staging WPSC + page cache served stale `ac-gclid-beacon.php` instance after Phase 3 v2 deploy → 5-page smoke initially failed until cache flushed. DNH has even more cache layers: **WPSC + Redis object cache + Cloudflare CDN** (per WORDPRESS-LOCAL-DEV.md §3). | **DNH mitigation:** post-deploy cache purge sequence: (1) `wp cache flush` (object cache) + (2) WPSC page cache purge + (3) Cloudflare cache purge for `dnh-staging.hkdrnow.com` zone. Verify with curl `view-source:` AFTER cache flush (not before). AC: same flow but only WPSC. | 0.5 day CTO (cache purge playbook) |

---

## 5. Future hybrid path (per-pillar prefix)

If AC pillar-level granularity needed in WATI inbox (per-pillar `[AC-INFO-09-NNN]` vs `[AC-PAID-09-NNN]`):
- Add column `pillar_code CHAR(8)` to `gclid_codes` (additive)
- Backend emits pillar-aware code: `[AC-{PILLAR}-{YY}-{NNN}]`
- **Decision HELD** until Phase 6 data confirms need (per dnacpr org-leaning pattern)

Current spec: pillar granularity derived via `(brand, yyyymm, seq, landing_page)` join — same approach as dnacpr.

---

## 6. Ownership matrix

| Phase | Owner | Wilson gate |
|---|---|---|
| 0.5 — Backend access ground truth | CTO (DB read, beacon.js status) | — |
| 1 — Deploy staging (CORS + mu-plugin copy) | CTO | — |
| 2 — Automated e2e | CBO + CTO (DB reads forwarded) | — |
| 3 — Manual e2e | Wilson + CBO + CTO (DB reads forwarded) | — |
| 4 — Staging review | Wilson | **MANDATORY** |
| 5 — Deploy prod (pre-Phase-5 cron pause mandatory) | CTO | (auto from Phase 4) + **pre-Phase-5 cron pause verify** |
| 6 — Observe | CBO + CTO (DB reads weekly) | — |
| **Pre-Phase-5 cron pause verify** (mandatory per dnacpr 20:24 incident) | CBO (forward CTO) | **MANDATORY** before prod deploy |
| **Prod leak rollback drill** | CTO (30 sec) + CBO (verify) | Required if cron leaks |

**DB read access (recurring):** Same as dnacpr — CTO has docker `:5433` access; CBO does NOT have direct access for this spec.

**Cron pause protocol (mandatory per dnacpr 20:00 + 20:24 incident):**
- **When:** Wilson approves Option C-equivalent for AC, CBO MUST forward "pause cron `0 */4 * * *` (AC container)" request to CTO BEFORE Phase 5 prod deploy
- **Why:** Cron auto-deploys any source change to prod. AC WordPress cron may differ — verify with CTO if AC has separate cron
- **How:** Same as dnacpr — CTO comments out the cron line, verifies `crontab -l | grep deploy.sh` returns empty
- **Extra protection:** Move `dist/` aside (or `wp-content/plugins/` etc for WordPress — TBD per CTO)
- **Un-pause:** Wilson approves Phase 5 → CBO confirms → CTO un-comments cron line

---

## 7. Rollback plan

**Trigger:** Any of:
- WATI parse failures > 5% over 24h
- Counter regression (non-monotonic within `(angel-care, yyyymm)` window)
- Async race > 1% (WATI DB seq ≠ gclid_codes seq)
- Critical prod incident
- Beacon service crash loop (systemd restart failures)

**Action:**
1. **Immediate (frontend):** CTO removes mu-plugin (`ac-gclid-beacon.php` rename or move aside) — 30 sec
2. **Immediate (backend):** `sudo systemctl stop beacon` — kills Node service on `:8791`. Click falls back to no-prefix. Restoration = `sudo systemctl start beacon`
3. **Short-term:** Backend returns `{ code: null }` → click falls back to no-prefix. Restoration = re-enable mu-plugin
4. **Permanent:** If unfixable, scope down to legacy gclid-only beacon by re-adding `if (!gclid) return;` gate in `ac-gclid-beacon.php`

**Backup:** Pre-Phase-1 mu-plugin state saved to `~/backups/ac-muplugin-preBeaconOptionb-<date>/`. WordPress site backed up per `WORDPRESS-LOCAL-DEV.md` §3 prod pattern (Alibaba RDS + wp-content/backup).

---

## 8. Success metrics (Phase 6 — observe window, parallel to dnacpr)

| Metric | Target |
|---|---|
| Counter monotonicity | 100% (gaps OK, dupes not) |
| Daily counter gap | < 100/day |
| WATI parse success rate | 100% (every `[AC-09-XXX]` decodes) |
| Async race incidents | 0 |
| **AC → DNH `[R-AC]` conversion rate** | **Computed (first time)** — Stage 1 → Stage 2 lift measurement |
| Beacon backend uptime | > 99.5% |
| Customer-visible impact | Zero |

---

## 9. Changelog

| Date | Change |
|---|---|
| 2026-09-02 21:42 | Initial draft (Wilson 21:29 GO "Let work on ac"). Spec written parallel to dnacpr Phase 6 observe. Pattern mirrors dnacpr spec §0-§9 with AC-specific adaptations: WordPress + Elementor stack (vs Astro), mu-plugin injection (vs Layout.astro), dual-pillar structure (per SOUL.md §"Service Pillar Architecture"), cross-site `[R-AC]` attribution (per REGISTRY.md 9/2 entry). Backend reuses dnacpr impl (brand enum already includes `angel-care`). AC staging gaps confirmed (beacon JS absent, CORS fail) — Phase 1 must close before Phase 2. |
| 2026-09-02 21:49 | **Channel coverage clarification (per Wilson ask).** Added §3 Phase 0.9 explicit channel coverage map (Google Ads / Meta Ads / Organic / Direct / Social / Email / Cross-site) confirming all 3 paid sources + all organic paths covered. Added §4 risk row "Channel attribution gap risk" with mitigation. Spec design IS source-agnostic — Option (b) "all-clicks carry code" + cross-site `[R-AC]` = complete funnel attribution. |
| 2026-09-02 21:55 | **AC Phase 1 DEPLOYED + verified.** CTO: CORS allowlist updated (ac-staging.hkdrnow.com), `ac-gclid-beacon.php` copied to migrate container (:8092), 5 pages HTTP 200 + Beacon JS present (incl. Elementor templates 全新升級+功能介紹), backend POST → `😇09-001`, no staging cron risk. angel-care 202609 = 1 (test row, flagged). **Design note:** CTO kept angel-care on legacy 😇 emoji (matching dnacpr sequence — stage legacy first, flip prefix in Phase 5 same deploy). AC prefix staged in code (commented). |
| 2026-09-02 22:03 | **Spec LOCKED — Wilson GO "Ok".** Status DRAFT → COMMITTED. Phase 2 e2e GO triggered (5 pages × 5 devices = 25 click sims, expect codes 😇09-002..026 with legacy emoji, mirroring dnacpr sequence). |
| 2026-09-02 23:10 | **AC Phase 3 v2 deploy DONE.** CTO deployed option-b extension + flipped AC prefix `AC-09-NNN`. **Bug caught:** `getBrand()` regex `/angelcare\./` did NOT match `ac-staging.hkdrnow.com` (fallthrough to doctornow brand). Fixed: `/(angelcare|ac-staging)/`. Wilson 5+5 retest = 10 codes `[AC-09-031..045]` (035 skipped due to scanner interleaving, accepted as one-time). |
| 2026-09-02 23:50 | **Phase 4 Wilson APPROVE.** Wilson staging review ACCEPTED. Phase 5 deploy to AC prod `angelcare.dnow.hk` triggered (cron pause NOT required per CTO 23:55 verify). |
| 2026-09-02 23:55 | **§4 risk rows added (lessons 11+12 per CTO 23:10 verify bug catches):** (1) `getBrand()` regex bug — DNH fix pre-deployed in `beacon.js` to avoid same fallthrough; (2) cache stale after deploy — DNH purge playbook (WPSC + Redis + CF CDN) documented. AC spec now template-ready for DNH replication. |

---

## Appendix A — Brand-specific facts (per `angel-care/WORDPRESS.md`)

| Fact | Value |
|---|---|
| Production URL | `https://angelcare.dnow.hk` |
| Production server | `47.52.68.243` (containers `angelcare_wordpress` + `angelcare_wordpress_php8`) |
| Staging URL | `https://ac-staging.hkdrnow.com` (container `angelcare_wp_migrate` :8092) |
| Rollback backup | `http://localhost:8090` (container `angelcare_wp_local`, FROZEN snapshot 2026-08-05) |
| WP version | 6.6.2 · Elementor 3.25.4 · Elementor Pro 3.25.2 |
| DB prefix | `wp_` (NOT emc_) |
| DB location | Alibaba RDS `angelcare_wordpress_dev` |
| Bilingual | TranslatePress, EN at `/en/` (live since 2026-08-06) |
| PHP-FPM user | `nginx` (NOT www-data) — see §3.2 permission incident |
| wp-admin user | `cbo` |
| WP-CLI | 2.12.0, requires `--allow-root` |

## Appendix B — AC pages with WA CTAs (5 pillars)

| Page | Elementor template | WA CTA location | Preset text mapping |
|---|---|---|---|
| `/` (home) | Template 248 | FAB top-right + inline CTA | `[AC-INFO-09-XXX] 我想了解AngelCare` |
| `/pricing-plan/` | Page post | FAB top-right + inline CTA | `[AC-PAID-09-XXX] 我想了解年費計劃` |
| `/全新升級/` (paid-services) | Template 1087 | FAB top-right + inline CTA | `[AC-PAID-09-XXX] 我想了解升級` |
| `/功能介紹/` (features) | Template 290 | FAB top-right + inline CTA | `[AC-INFO-09-XXX] 我想了解功能` |
| `/常見問題/` (faq) | Page post | FAB top-right + inline CTA | `[AC-INFO-09-XXX] 我有其他問題` |

**Note:** pillar granularity (`INFO` vs `PAID`) deferred per §5 — current spec uses single `[AC-09-XXX]` field; landing_page column provides pillar-level joins in reports.

## Appendix C — Cross-references

| Doc | Relevance |
|---|---|
| `~/projects/amdcpr/specs/beacon-staging-plan-2026-09-02.md` | dnacpr spec — pattern source (mirrored 80%) |
| `~/projects/angel-care/WORDPRESS.md` | AC-specific brand facts (production stack, incidents, lessons) |
| `~/projects/_shared/wa-cta-attribution/REGISTRY.md` | Cross-site `[R-AC]` tag spec + WATI regex patterns |
| `~/projects/_shared/_templates/WORDPRESS-LOCAL-DEV.md` | Generic WP + Elementor lesson (e.g. §3.2 CSS incident, §3 prod deploy pattern) |
| `~/projects/_shared/wa-cta-attribution/deployed/` | Active snippets registry + Red Line #8 cleanup guidance |
| `~/projects/amdcpr/scripts/phase2-e2e.sh` | e2e test script template (ms-convention applied per dnacpr Phase 2 ts-units fix) |
| Skill `brandops-beacon-option-b-deploy` (pending) | Reusable workflow for any BrandOps beacon hotfix |