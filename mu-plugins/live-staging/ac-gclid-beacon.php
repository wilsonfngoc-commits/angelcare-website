<?php
/**
 * Plugin Name: AC gclid beacon client (Option b v1.1)
 * Description: All WA clicks carry short code. gclid present → POST / (idempotent). No gclid
 *              (organic/direct) → POST /assign-organic with ac_<ms>_<rand> client_ref.
 *              Code format [AC-MM-NNN] (ASCII, no emoji). Replaces legacy [AMD-S0x]/😇 preset
 *              prefix with single combined code field. Fail-open. Kill-switch aware.
 * Author: CTO
 * Version: 1.1
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'wp_footer', 'ac_gclid_beacon_client', 99 );
function ac_gclid_beacon_client() {
    if ( is_admin() ) return;
    ?>
<!-- AC gclid beacon client v1.1 (Option b 2026-09-02, Wilson GO 23:04) — all clicks carry code -->
<script>
(function() {
  var BEACON_URL = "https://beacon.hkdrnow.com/";
  var CACHE_PREFIX = "wa_beacon_code_";
  var AC_REF_KEY = "wa_ac_ref";

  function isOff() {
    try {
      if (window.__DNH_WA_OFF__ === 1 || window.__DNH_WA_OFF__ === true) return true;
      if (localStorage.getItem("dnh_wa_off") === "1") return true;
      if (/[?&]dnh_wa_off=1/.test(location.search)) return true;
    } catch (e) {}
    return false;
  }
  function getGclid() {
    var m = location.search.match(/[?&]gclid=([^&#]+)/);
    return m ? decodeURIComponent(m[1]) : null;
  }
  function getBrand() {
    var h = location.hostname;
    if (/angelcare|ac-staging/.test(h)) return "angel-care";  // ac-staging.hkdrnow.com + angelcare.dnow.hk
    if (/dnacpr\.|amdcpr/.test(h)) return "amdcpr";
    return "doctornow";
  }
  function getAcRef() {
    try {
      var r = sessionStorage.getItem(AC_REF_KEY);
      if (r) return r;
      r = "ac_" + Date.now() + "_" + Math.random().toString(36).slice(2, 12);
      sessionStorage.setItem(AC_REF_KEY, r);
      return r;
    } catch (e) { return "ac_" + Date.now() + "_" + Math.random().toString(36).slice(2, 12); }
  }
  function readCache(key) {
    try {
      var raw = localStorage.getItem(CACHE_PREFIX + key);
      if (!raw) return null;
      var d = JSON.parse(raw);
      return d && d.code ? d.code : null;
    } catch (e) { return null; }
  }
  function writeCache(key, code) {
    try { localStorage.setItem(CACHE_PREFIX + key, JSON.stringify({ code: code, ts: Date.now() })); } catch (e) {}
  }
  function post(path, payload) {
    var attempt = function() {
      return new Promise(function(resolve) {
        var ctrl = new AbortController();
        var t = setTimeout(function() { ctrl.abort(); resolve(null); }, 2000);
        fetch(BEACON_URL + path, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(payload),
          keepalive: true,
          signal: ctrl.signal
        }).then(function(r) { return r.json(); }).then(function(d) {
          clearTimeout(t);
          resolve(d && d.code ? d : null);
        }).catch(function() { clearTimeout(t); resolve(null); });
      });
    };
    return attempt().then(function(c) { return c ? c : attempt(); }); // one retry
  }
  function applyCode(code) {
    var links = document.querySelectorAll('a[href*="wa.me"], a[href*="api.whatsapp.com"], a[href*="whatsapp://"], a[data-dnh-href*="wa.me"], a[data-dnh-href*="api.whatsapp.com"]');
    var marker = "[" + code + "]";
    for (var j = 0; j < links.length; j++) {
      var a = links[j];
      try {
        var cur = a.href || a.getAttribute("data-dnh-href") || "";
        if (!cur) continue;
        var u = new URL(cur, location.origin);
        var t = u.searchParams.get("text") || "";
        if (t.indexOf(marker) !== -1) continue;
        // Replace any legacy preset prefix token ([...]) with combined beacon code; keep body text
        var body = t.replace(/^\s*\[[^\]]*\]\s*/, "");
        u.searchParams.set("text", marker + " " + body.trim());
        var out = u.toString();
        if (a.hasAttribute("data-dnh-href")) a.setAttribute("data-dnh-href", out);
        if (a.href) a.href = out;
      } catch (e) { /* fail-open */ }
    }
  }
  function main() {
    try {
      if (isOff()) return;
      var brand = getBrand();
      var gclid = getGclid();
      var isGclid = gclid && /^[A-Za-z0-9_-]{15,}$/.test(gclid);
      var cacheKey = isGclid ? gclid : ("ref:" + getAcRef());
      var cached = readCache(cacheKey);
      if (cached) { applyCode(cached); return; }
      var req = isGclid
        ? post("", { gclid: gclid, ts: Date.now(), url: location.href, brand: brand })
        : post("assign-organic", { client_ref: getAcRef(), ts: Date.now(), url: location.href, brand: brand });
      req.then(function(d) {
        if (!d || !d.code) return; // fail-open
        writeCache(cacheKey, d.code);
        applyCode(d.code);
      });
    } catch (e) { /* fail-open */ }
  }
  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", main);
  else main();
})();
</script>
    <?php
}
