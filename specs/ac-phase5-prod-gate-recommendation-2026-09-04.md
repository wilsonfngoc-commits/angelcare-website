# AC Phase 5 — Prod Gate Recommendation

**Author:** CBO (Wilson directive 2026-09-04 11:40)
**Date:** 2026-09-04
**Status:** ✅ **DEPLOYED & CLOSED (2026-09-04 12:02)** — Option 1 executed, Wilson GO 11:52 via CBO relay. Evidence: prod clean-URL serves option-b v1.1 (assign-organic present); smoke codes AC-09-056/057; phase6-health NORMAL seq 57; thread closed 12:05. **Do not re-request / re-execute.**
**Related:** `~/projects/angel-care/specs/ac-phase4-staging-health-report-2026-09-04.md` (Phase 4 PASS)
**Related:** `~/projects/_shared/_frameworks/beacon-decision-log-2026-09-02.md` Decision 4, 7

---

## TL;DR

**CBO recommendation: GO Option 1 (full hotfix)** — replace prod mu-plugin `ac-gclid-beacon.php` legacy (gclid-only) with option-b v1.1 (all-click + `assign-organic`). ~15-20 min CTO execution window.

**✅ Corrected 2026-09-04 (CTO verify): beacon.js prefix flip is ALREADY LIVE — NOT part of this scope.** `BRAND_CODE_PREFIX['angel-care'] = 'AC'` at beacon.js:47 since 9/2 23:07 restart; DB codes AC-09-048..055 issued since 9/2 23:44, all ASCII. Prod + staging share ONE backend (beacon.hkdrnow.com). The 😇 survives only as unused BRAND_EMOJI fallback. **Real remaining prod gap = organic untracked (frontend-only).**

**Why now:**
1. Staging 5/5 click test PASS (counter monotonic, WATI delivery confirmed, ASCII format clean)
2. AC organic traffic is the highest-volume untracked source in our stack (vs dnacpr near-zero ramp, DNH already tracked)
3. Risk surface = same as dnacpr Phase 1 (low per CTO 9/2 19:51 assessment)
4. Phase 6 daily cron (installed 9/4 06:35) auto-monitors AC counter health post-deploy

---

## What's being proposed

### Change scope (Option 1 = full hotfix)

| Component | Current state | Proposed state | Effort |
|---|---|---|---|
| `ac-gclid-beacon.php` (AC mu-plugin, prod) | Legacy gclid-only (`if (!gclid) return;`) — has beacon client but NO `assign-organic` | Swap → option-b v1.1 (all-click, same as staging ac-staging.hkdrnow.com) | ~10 min CTO |
| `BRAND_CODE_PREFIX['angel-care']` in beacon.js | **Already `'AC'` live (9/2 23:07)** — NOT pending | No change | 0 |
| Backend (beacon.hkdrnow.com) | Shared prod+staging, already has `POST /assign-organic` | No change / no redeploy | 0 |
| `ac-outbound-utm.php` | Live, untag-attached for AC | No change (DNH outbound tags already live) | 0 |
| Phase 6 dashboard | AC counter monitored | AC counter + click burn rate + format audit added | ~5 min CBO (extend `phase6-health.js`) |

### Rollback plan (per spec §7)

| Failure mode | Mitigation | Time to revert |
|---|---|---|
| Backend / DB issue | `sudo systemctl --user stop beacon` | 30 sec |
| Frontend AC beacon issue | Revert `ac-gclid-beacon.php` to legacy version (backup file restore) | 30 sec |
| Layout/UI issue | Restore previous mu-plugin from backup | 2 min |

**Combined max-rollback time: 2 min (backend untouched — no format-flip revert needed).** All failures preserve preset text + WA delivery (fail-open).

---

## Why Option 1 over Option 2/3

### Option 1 (recommended) — Full hotfix (mu-plugin swap only)

**Pros:**
- Closes attribution loop for AC organic traffic (currently 100% untracked)
- ASCII prefix already live prod-side (9/2 23:44+) — no format transition in this deploy
- Same code as staging option-b v1.1 (already 5/5 click-test PASS + 37h soak)
- Phase 6 daily cron auto-detects anomalies within 24h
- Backend untouched → rollback = 30 sec backup restore

**Cons:**
- AC prod traffic near-zero per CTO 21:30 data (lower blast radius than expected, but still a deploy)
- Frontend mu-plugin change requires Elementor/page cache flush (CTO standard procedure)

### Option 2 — Prefix flip only → **NO-OP, REMOVED (2026-09-04 CTO verify)**
Prefix already `'AC'` live since 9/2 23:07; DB already emitting AC-09-NNN. No remaining scope — not a meaningful choice.

### Option 3 — Hold (sequential after dnacpr prod ramp)

**Pros:** Wait for dnacpr to prove prod pattern stable
**Cons:** Delays attribution coverage, AC organic traffic continues untracked

---

## Wilson gate conditions (per spec §3 Phase 5)

This is a P1 approval per `~/.openclaw/SHARED/APPROVAL_PROTOCOL.md` (frontend code + format change → Wilson explicit GO).

### Gate criteria checklist

| # | Criterion | Status | Evidence |
|---|---|---|---|
| 1 | Staging 24-48h soak | ✅ ~37h (9/2 23:04 → 9/4 11:36) | Phase 4 report §"Staging click test data" |
| 2 | Click test pass (5+ URLs) | ✅ 5/5 | Phase 4 report |
| 3 | Counter monotonic gap < 100 | ✅ 0 | Phase 6 dashboard 11:37 |
| 4 | Idempotency (UNIQUE gclid) | ✅ 5 unique | gclid_codes query |
| 5 | WATI parse rate | ✅ 5/5 | Wilson screenshot |
| 6 | Format clean (no `[<?>]`) | ✅ ASCII | Backend code confirmed |
| 7 | Rollback plan documented | ✅ | Phase 4 report §"Rollback plan" |
| 8 | Phase 6 monitoring in place | ✅ | `phase6-health.js` cron 06:35 HKT daily |

**All 8 criteria met.** Recommend Wilson GO Option 1.

---

## Execution plan (if Wilson GO Option 1)

### T+0 to T+5: CTO prep
1. Snapshot current `ac-gclid-beacon.php` from prod container → backup
2. Diff staging vs prod `ac-gclid-beacon.php` (CTO already deployed option-b v1.1 to staging, prod still legacy gclid-only)
3. Verify AC staging beacon still working (counter + WATI parse)

### T+5 to T+15: CTO deploy
1. Swap prod `ac-gclid-beacon.php` → option-b v1.1 (staging version, incl. `assign-organic` frontend call). **No beacon.js edit, no backend change needed — prefix already `'AC'` live, backend shared prod/staging already has the endpoint.**
2. Cache flush (WordPress + Elementor per CTO standard; AC prod has Redis object cache + WP Super Cache — purge BOTH, verify with plain curl)

### T+15 to T+20: CTO smoke verify
1. `curl https://beacon.hkdrnow.com/ -X POST -H 'Content-Type: application/json' -d '{"gclid":"test_prod_phase5_ac_<ts>","ts":<ms>,"url":"https://angelcare.dnow.hk/","brand":"angel-care"}'`
2. Expect: `{"code":"AC-09-XXX"}` (where XXX = current counter + 1)
3. `curl https://angelcare.dnow.hk/` → grep `assign-organic` JS presence (the real prod gap signal)
4. Verify WordPress + Elementor cache flushed

### T+20 to T+30: CBO verify
1. Run `node phase6-health.js --brand ac` → expect counter incremented by 1
2. (Optional) Send test WA from prod page with `?gclid=` → verify WATI parse
3. Write entry to `~/projects/angel-care/MARKETING-LOG.json`

### T+30 to T+24h: Observe window
- Phase 6 daily cron 06:35 HKT tomorrow auto-reports AC counter health
- CBO watches for any anomaly (gap > 100, dup rate spike, etc.)
- Wilson pinged only on 🚨 CRITICAL

---

## Comparison to dnacpr Phase 5 deploy (9/2)

| Aspect | dnacpr Phase 5 | AC Phase 5 (proposed) |
|---|---|---|
| Backend change | New `POST /assign-organic` endpoint | None (already exists from dnacpr A1) |
| Frontend change | Layout.astro (Astro) | `ac-gclid-beacon.php` mu-plugin (WordPress) |
| Cron safety | 20:00 incident → cron paused | Cron already paused (per spec §7) |
| Wilson GO date | 2026-09-02 20:44 | 2026-09-04 (pending) |
| Pre-deploy staging soak | 9 hours (Phase 1 19:00 → A1 19:51 → GO 20:44) | 37 hours (more conservative) |
| Real traffic expected | Day 1, $0 spend, 6 impressions 0 clicks (low) | ~50+ organic daily visitors per CTO 21:30 estimate (medium) |

**Risk profile: AC Phase 5 = similar to dnacpr Phase 5, lower blast radius (smaller AC traffic).**

---

## Open question for Wilson

Do you want **Option 1 (full hotfix — mu-plugin swap to option-b v1.1)** or **Option 3 (hold)**?

Option 2 (prefix flip) is no longer a choice — already live since 9/2 23:07. **Option 1 = the right answer per CBO analysis, but the choice is yours.**

---

## Changelog

| Date | Author | Change |
|---|---|---|
| 2026-09-04 11:42 | CBO | Initial Phase 5 recommendation — Option 1 full hotfix, await Wilson GO |
| 2026-09-04 (CTO verify relay) | CBO | **Corrected:** beacon.js prefix already live `'AC'` (9/2 23:07) — removed from scope; Option 2 removed as no-op. Actual scope = prod mu-plugin `ac-gclid-beacon.php` legacy → option-b v1.1 swap + cache flush + smoke verify. Approval reframed Option 1 vs Option 3. |
| 2026-09-04 12:02 | CTO | **✅ DEPLOYED & CLOSED.** Mu-plugin legaxy → option-b v1.1 (sha256 e6e51bb5…, 4909B). Cache flush. Smokes: clean-URL assign-organic ✅, POST / → AC-09-056, /assign-organic → AC-09-057. Phase 6 NORMAL seq 57, gap 0, organic 23/57 tracked. Rollback: prod /tmp/ac-gclid-beacon.php.bak-prod-20260904_1158 (3810B, md5 f4b72f0b…). Thread AP-20260904-01 closed 12:05. |
