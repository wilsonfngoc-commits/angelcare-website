# pricing-plan (收費計畫/年費計劃) — Full Rewrite Proposal (CBO, 2026-08-03) — FOR WILSON APPROVAL
# Scope: Option A — move service walkthrough from 教學指南 → pricing-plan (rewritten),
# move 10 service Q&As from 常見問題 → pricing-plan, combined single FAQ block.
# No changes made yet.

## Page structure after rewrite (top to bottom)

1. **H1: AngelCare 年費計劃** (animated headline) — KEEP
2. **3 paid-plan boxes** (icon-box design) — KEEP unchanged
   - 1. 註冊護士上門評估服務 (單次) — HK$1,500–2,000/次 (適用醫療券)
   - 2. 維生指數自訂功能 (即將推出) — HK$99/年
   - 3. 醫療群組諮詢服務 (年費計劃) — HK$5,800/年
3. **個人化健康指標設定** section (4 icon-box sub-features: 護士上門評估 / 個性化 Target / 數據共享 / 專家支援) — KEEP
4. **了解專業醫療方案** CTA — KEEP
5. **🆕 服務流程** (moved from 教學指南, rewritten — NEW H2 + 4 steps)
6. **🆕 收費常見問題** — single combined FAQ toggle (7 pricing Q&As + 10 service Q&As, deduped → ~13 items)
7. **下一步** closing CTA — KEEP

## NEW copy — 服務流程 (H2, placed after 了解專業醫療方案 CTA)

**H2 服務流程**

**第一步：聯絡查詢**
透過網站或 WhatsApp 聯絡我哋，同事會了解長者嘅基本情況，講解各項服務內容同收費，並同你確認預約時間。

**第二步：上門評估**
註冊護士親身上門，進行家居環境安全評估、藥物篩查同全面身體狀況評估（ICP），即場協助配置宅天使 App 基本功能，完成後提供個人化照顧建議。

**第三步：度身訂造方案**
按評估結果制定個人化照顧計劃，幫你揀選最合適嘅計劃組合——護士上門評估、維生指數自訂、醫療群組諮詢可獨立或組合使用。

**第四步：持續跟進**
定期監測維生指數，主診醫生可遠端查閱健康數據；有任何變化，隨時聯絡我哋調校照顧方案。

## Combined FAQ block (single elementor-toggle, same theme as current pricing FAQ)

**服務一般問題**
Q1. Angel Care 提供邊啲服務？
Q2. 服務範圍覆蓋香港邊啲地區？
Q3. 服務對象係邊啲人？
Q4. 有冇政府資助？

**上門評估**
Q5. 護士上門評估服務點樣進行？
Q6. 評估要準備啲乜？
Q7. 評估結果會唔會提供報告？

**收費與計劃**
Q8. 收費係點樣計？有冇隱藏費用？
Q9. 維生指數自訂功能同一般 App 有咩分別？
Q10. 醫療群組諮詢服務包啲咩？
Q11. 可唔可以單次使用服務？
Q12. 服務可以點樣組合？

**預約**
Q13. 點樣預約服務？

(Dedupe note: pricing FAQ had 7 items, service FAQ had 10 — merged 護士上門評估點樣進行/單次使用/預約/收費 into single items; net 13 unique.)

## Other pages after this change

- **教學指南 page:** remove the service walkthrough section (第一步了解需要 → 第四步持續跟進 + 常見情況速查) → back to pure app tutorial (步驟1–6) + add CTA at end: 「想了解 Angel Care 上門服務？去收費計畫頁面睇睇。」
- **常見問題 page:** remove the 10 service Q&As from the toggle → back to 7 app Q&As (matches original intent). FAQPage schema updated to 7 items.

## Verification after apply
- pricing: 1× H1, one FAQ toggle (~13 items), 服務流程 H2 present, heading order clean
- guide: app steps only + CTA, no service sections
- faq: 7 app items only
- Backup refresh; render-grep verify; deploy to prod after Wilson approval
