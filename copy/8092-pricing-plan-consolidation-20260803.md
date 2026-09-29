# /pricing-plan/ Consolidation Proposal (CBO, 2026-08-03) — FOR WILSON APPROVAL
# No changes made yet. Design elements stay; copy consolidated.

## Current page structure (after Batch 2 additions)

1. **H1 (animated headline):** AngelCare 年費計劃 ← KEEP (Wilson confirmed)
2. **3 paid-plan boxes (icon-box design):**
   - 1. 註冊護士上門評估服務 (單次) — HK$1,500–2,000/次 (適用醫療券)
   - 2. 維生指數自訂功能 (即將推出) — HK$99/年
   - 3. 醫療群組諮詢服務 (年費計劃) — HK$5,800/年 ← KEEP design (Wilson confirmed)
3. **個人化健康指標設定 section** (original): 4 icon-box sub-features — 護士上門評估 / 個性化 Target / 數據共享 / 專家支援 ← KEEP (original, not duplicated)
4. **了解專業醫療方案** CTA button ← KEEP
5. **[ADDED — DUPLICATES #2]** 透明收費，無隱藏費用 + 護士上門評估 / 維生指數監測 / 醫療群組諮詢 H2 sections — repeats the same 3 services + same prices as the boxes above ← **REMOVE**
6. **[ADDED]** 收費常見問題 (3 Q&A, plain text) ← **REWRITE as toggle, expand**
7. **[ADDED]** 下一步 CTA ← KEEP as closing CTA

## Plan

- **Remove:** sections #5 entirely (the duplication Wilson flagged) — the 3 plan boxes already carry service + price.
- **Keep:** #1 H1, #2 boxes, #3 個人化健康指標設定, #4 CTA, #7 下一步.
- **Rewrite FAQ (#6):** same `elementor-toggle` accordion design as 常見問題 page (widget: toggle, left icon, title + content), service-focused Q&As (7 items), placed before the 下一步 closing CTA.

## New FAQ copy (toggle items, matching 常見問題 design)

**Q1. 收費係點樣計？有冇隱藏費用？**
A：所有服務收費都會喺預約前清晰確認，冇任何隱藏收費。每個計劃嘅內容同費用都列明喺上面，你可以按需要單獨或組合選用。

**Q2. 護士上門評估服務點樣進行？**
A：註冊護士會親身到訪，進行家居環境安全評估、藥物篩查同全面身體狀況評估（ICP），並即場協助配置宅天使 App 基本功能，完成後制訂個人化照顧計劃。

**Q3. 維生指數自訂功能同一般 App 有咩分別？**
A：一般 App 用預設數值，升級版嘅血壓、血糖、血氧等指標範圍會由專業醫護根據長者實際狀況度身訂造，仲可以授權主診醫生遠端查閱同設定正常範圍，唔使靠家人自行判斷。

**Q4. 醫療群組諮詢服務包啲咩？**
A：開通一對一 WhatsApp 諮詢群組，與註冊醫生直接對話，提供全年無限次醫療意見。醫生會針對病情、報告或維生指數異常提供建議。此計劃已包含價值 HK$99/年嘅維生指數自訂功能。

**Q5. 可唔可以單次使用服務？**
A：可以。護士上門評估服務支援單次預約；維生指數自訂同醫療群組諮詢為年費計劃，按年訂閱。

**Q6. 服務可以點樣組合？**
A：可以。好多家庭會選擇「護士上門評估 + 醫療群組諮詢」組合，先全面評估，再持續跟進，最完整咁掌握長者健康狀況。

**Q7. 點樣預約服務？**
A：可以透過網站聯絡表格或直接聯絡我哋，同事會了解長者情況、確認服務範圍同收費後，再安排護士或醫生跟進。

## After change
- 1× H1 (AngelCare 年費計劃) · heading order clean · FAQ = toggle widget matching 常見問題 design
- Thin-content flag: page carries real content (boxes + 個人化健康指標設定 + 7-Q&A toggle + CTA) — no filler added
- Backup refreshed after apply; render-grep verify per standing rule
