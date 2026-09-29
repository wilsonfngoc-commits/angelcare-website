#!/usr/bin/env bash
# Phase 2 — Automated e2e click test for AC Beacon Option (b)
# Owner: CBO
# Spec: ~/projects/angel-care/specs/beacon-staging-plan-2026-09-02.md (COMMITTED 22:03)
# Triggered: AC Phase 1 done 21:55; Phase 2 baseline confirmed 22:37
# Cloned from: ~/projects/amdcpr/scripts/phase2-e2e.sh
#
# Usage: bash phase2-ac-e2e.sh [<preview_url>] [<batch_ts>]
# Example: bash phase2-ac-e2e.sh https://ac-staging.hkdrnow.com

set -uo pipefail

PREVIEW="${1:-https://ac-staging.hkdrnow.com}"
BATCH_TS="${2:-$(date +%s)}"
# NOTE: beacon backend expects `ts` in MILLISECONDS (epoch ms), NOT seconds.
# BATCH_TS from caller is unix seconds; appending 000 converts to ms.
# CRITICAL: sending raw seconds causes backend to treat as 1970-01 epoch
#   → codes land in 197001 bucket (broken month attribution).
# dnacpr Phase 2 v1 lesson: 27 rows stuck in 197001 until ms-convention applied.
LOG_DIR="$HOME/projects/angel-care/logs"
SCRIPT_DIR="$HOME/projects/angel-care/scripts"
mkdir -p "$LOG_DIR"

LOG="$LOG_DIR/phase2-${BATCH_TS}.log"
SUMMARY="$LOG_DIR/phase2-${BATCH_TS}.summary.json"

BEACON="https://beacon.hkdrnow.com/"

# AC staging pages (5 representative, matches AC Phase 1 smoke check)
PAGES=(
  "/"
  "/pricing-plan/"
  "/全新升級/"
  "/功能介紹/"
  "/常見問題/"
)

# UA profiles (5)
declare -A UA=(
  [desktop_chrome]="Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36"
  [mobile_safari]="Mozilla/5.0 (iPhone; CPU iPhone OS 17_5_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1"
  [mobile_chrome]="Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Mobile Safari/537.36"
  [tablet_safari]="Mozilla/5.0 (iPad; CPU OS 17_5_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1"
  [inapp_fb]="Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Mobile Safari/537.36 [FBAN/FBIOS;FBAV/455.0.0.16.100;FBBV/579689041;]"
)

declare -A STATUS=()

echo "=== AC Phase 2 e2e run ===" | tee "$LOG"
echo "preview: $PREVIEW" | tee -a "$LOG"
echo "batch_ts: $BATCH_TS" | tee -a "$LOG"
echo "beacon: $BEACON" | tee -a "$LOG"
echo "brand: angel-care" | tee -a "$LOG"
echo "expected code format: 😇\d{2}-\d{3} (legacy emoji — Phase 5 flips to AC- prefix)" | tee -a "$LOG"
echo "" | tee -a "$LOG"

PASS=***
FAIL=0
TOTAL=0

# Track codes returned (for monotonicity check)
LAST_SEQ=-1
declare -a ALL_CODES=()

n=0
for page in "${PAGES[@]}"; do
  for profile_key in desktop_chrome mobile_safari mobile_chrome tablet_safari inapp_fb; do
    n=$((n+1))
    UA_VAL="${UA[$profile_key]}"
    TEST_GCLID="test_phase2_ac_${BATCH_TS}_${n}"
    PAGE_URL="${PREVIEW}${page}"

    echo "--- [$n] page=$page profile=$profile_key gclid=$TEST_GCLID ---" | tee -a "$LOG"
    TOTAL=$((TOTAL+1))

    # 1. Curl page (smoke)
    PAGE_HTTP=$(curl -sSL --max-time 15 -A "$UA_VAL" -o /tmp/p2ac_page.html -w "%{http_code}" "$PAGE_URL" 2>&1)
    echo "  page HTTP: $PAGE_HTTP" | tee -a "$LOG"

    # 2. Grep beacon marker on page
    BEACON_HITS=$(grep -c "beacon\.hkdrnow\.com\|BEACON_URL" /tmp/p2ac_page.html 2>/dev/null || echo 0)
    echo "  beacon marker hits: $BEACON_HITS" | tee -a "$LOG"

    if [ "$PAGE_HTTP" != "200" ]; then
      echo "  FAIL: page not 200" | tee -a "$LOG"
      STATUS[$n]="PAGE_HTTP_$PAGE_HTTP"
      FAIL=$((FAIL+1))
      continue
    fi
    if [ "$BEACON_HITS" -lt 1 ]; then
      echo "  FAIL: no beacon marker on page" | tee -a "$LOG"
      STATUS[$n]="NO_BEACON_MARKER"
      FAIL=$((FAIL+1))
      continue
    fi

    # 3. POST to beacon (ts must be MILLISECONDS — append 000)
    BEACON_RESP=$(curl -sS --max-time 15 -X POST "$BEACON" \
      -H "Content-Type: application/json" \
      -d "{\"gclid\":\"${TEST_GCLID}\",\"brand\":\"angel-care\",\"url\":\"${PAGE_URL}\",\"ts\":${BATCH_TS}000}" 2>&1)
    echo "  beacon response: $BEACON_RESP" | tee -a "$LOG"

    # 4. Parse code (regex: 😇\d{2}-\d{3} — legacy emoji per AC Phase 1 deploy)
    CODE=$(echo "$BEACON_RESP" | grep -oE '😇[0-9]{1,2}-[0-9]{1,3}' | head -1)
    REUSED=$(echo "$BEACON_RESP" | grep -oE '"reused":(true|false)' | head -1)

    if [ -z "$CODE" ]; then
      echo "  FAIL: code parse failed" | tee -a "$LOG"
      STATUS[$n]="NO_CODE"
      FAIL=$((FAIL+1))
      continue
    fi

    # 5. Monotonic check: extract seq number
    SEQ=$(echo "$CODE" | grep -oE '[0-9]+$' || echo "0")
    if [ "$SEQ" -gt "$LAST_SEQ" ]; then
      echo "  monotonic: $SEQ > $LAST_SEQ ✓" | tee -a "$LOG"
      LAST_SEQ=$SEQ
    else
      echo "  WARN: non-monotonic (got $SEQ, last was $LAST_SEQ)" | tee -a "$LOG"
    fi
    ALL_CODES+=("$CODE")

    # 6. Idempotency spot check (every 5th click)
    if [ $((n % 5)) -eq 0 ]; then
      IDEM_RESP=$(curl -sS --max-time 15 -X POST "$BEACON" \
        -H "Content-Type: application/json" \
        -d "{\"gclid\":\"${TEST_GCLID}\",\"brand\":\"angel-care\",\"url\":\"${PAGE_URL}\",\"ts\":${BATCH_TS}000}" 2>&1)
      IDEM_CODE=$(echo "$IDEM_RESP" | grep -oE '😇[0-9]{1,2}-[0-9]{1,3}' | head -1)
      IDEM_REUSED=$(echo "$IDEM_RESP" | grep -oE '"reused":(true|false)' | head -1)
      echo "  idempotency check: code=$IDEM_CODE reused=$IDEM_REUSED" | tee -a "$LOG"
      if [ "$IDEM_CODE" = "$CODE" ] && [ "$IDEM_REUSED" = '"reused":true' ]; then
        echo "  idempotency: PASS" | tee -a "$LOG"
      else
        echo "  idempotency: FAIL (expected same $CODE + reused:true)" | tee -a "$LOG"
        FAIL=$((FAIL+1))
        STATUS[$n]="IDEMPOTENCY_FAIL"
        continue
      fi
    fi

    echo "  PASS: code=$CODE" | tee -a "$LOG"
    STATUS[$n]="PASS"
    PASS=***
  done
done

echo "" | tee -a "$LOG"
echo "=== Summary ===" | tee -a "$LOG"
echo "Total: $TOTAL  Pass: $PASS  Fail: $FAIL" | tee -a "$LOG"
echo "Last seq observed: $LAST_SEQ" | tee -a "$LOG"

# JSON summary
cat > "$SUMMARY" <<EOF
{
  "batch_ts": ${BATCH_TS},
  "preview": "${PREVIEW}",
  "brand": "angel-care",
  "total": ${TOTAL},
  "pass": ${PASS},
  "fail": ${FAIL},
  "last_seq": ${LAST_SEQ},
  "all_codes": [$(printf '"%s",' "${ALL_CODES[@]}" | sed 's/,$//')]
}
EOF

echo "Summary written to $SUMMARY" | tee -a "$LOG"
echo "Log written to $LOG" | tee -a "$LOG"