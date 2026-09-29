# AngelCare — Collapsible Boxes Inventory (2026-08-07)

Wilson's wrap-alignment fix (toggle title second line aligning under the chevron) — rollout candidates.
Status: **home story boxes FIXED (staging)**, everything else PENDING approval.

## Live collapsible boxes (toggle widgets)

| # | Page | Render source | Widget id(s) | Rows | Chevron rendered? | EN title wrap risk | Status |
|---|------|--------------|--------------|------|-------------------|--------------------|--------|
| 1 | Home (EN+ZH) | template 248 (HOME) | ddc70d5 / 1543f7e / 7b69782 | 2+2+2 | Yes (terracotta) | HIGH — 3/6 titles 39–53ch ("Afraid of doing wrong, dared not act — family worried", "The Primary Caregiver: 'I Can't Find Anyone to Help'") | ✅ **FIXED** (ac-spacing-fix §21, flex layout) |
| 2 | 功能介紹 (EN+ZH) | template 290 (FUNTION) | 4b94b7ad | 9 | Yes (teal) | LOW — max 32ch ("7. Exercise & Rehabilitation"); wraps only on mobile | Pending |
| 3 | 常見問題 (EN+ZH) | template 271 (QA) | 13a2117 | 7 | Yes | **HIGH** — 6/7 titles 38–73ch ("Can my domestic helper use AngelCare without knowing Chinese?") | Pending |
| 4 | Pricing (EN+ZH) | page 1249 (收費計畫) | 0ba326e | 13 | Yes | **HIGH** — 8/13 titles 35–60ch | Pending |
| 5 | 背景故事 (EN+ZH) | template 483 (STORY) | 232aba9 / afd621e | 3+5 | Yes (8 icons) | LOW — max 30ch; wraps only on mobile | Pending |
| 6 | 全新升級 (EN+ZH) | template 1087 | e191226 | 5 | **No** (icons not rendered) | wraps but no chevron column → aligned naturally | Not affected |
| 7 | Landing (EN+ZH) | template 91 | 3b5a0903 / 444f329 | 4+4 | **No** (icons not rendered) | wraps but no chevron column → aligned naturally | Not affected |

## Notes
- **Chevron = float:left** (Elementor widget CSS) → wrapped label lines return to the container's left edge (under the chevron). Fix = flex layout on `.elementor-tab-title` (icon + label flex items) — §21 in ac-spacing-fix.php, scoped to the 3 home toggles.
- The fix targets **widget ids**, so it applies to BOTH languages automatically (ZH/EN share the structure).
- **Not rendered / legacy duplicates (ignore):** page 524 (dup of 483), page 1006 (dup of 91), templates 805 (dup of 638's section), 229 (20250210).
- No **accordion** widgets anywhere on the site.
- Rollout = copy the §21 CSS block per template (248 done; 290/271/1249/483 pending) — or generalize to `.elementor-widget-toggle .elementor-tab-title` once Wilson approves the pattern.
