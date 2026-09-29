# AngelCare 評估工具 — 整合討論 v4 (2026-08-08)

**品牌使命 / 集團願景 (2026-08-08 Wilson 確認):** 「讓老人家喺佢熟悉嘅環境有尊嚴自主地生活直到死為止」= 在宅醫療嘅集團願景 — 老友宅醫 (醫療側) + 宅天使 AngelCare (照顧側) 共同服務。本工具屬照顧側: 支持居家安老, 同時誠實承認某啲情況院舍係最好選擇。

**v4 更新 (08-08 凌晨決策):** Tier 1→Tier 2 觸發規則 (00:18)、lean 格式+數目規則 (00:28/00:33)、原網頁內容保留率 ~85-90% (00:42)。PDF v2 已於 00:33 產生 (同 link)。

## 1. 已確認的發現 (過去 2 小時)

### 資產
- 5 個評估表單 = CP Calculated Fields forms 6-10 (ADL/IADL/心理/家庭資源/家居安全)，存在於 8092 + 8090 DB，只有 ADL (form 6) 有獨立頁面 (704 基本生活能力評估)
- 原始 CTA = `?cff-form=6..10`，prod/staging 均仍 HTTP 200；「gone」指新版網站 CTA 未接上 → 需 redirect 任務
- Form 9 判定規則 note 錯貼 form 8 內容 (需修正)

### 研究 (deep-research subagent, 16 sources)
- 驗證對應：ADL≈Barthel (80-100/60-79/40-59/<40)，IADL≈Lawton (0-8)，心理≈GDS-15，家庭資源≈Zarit Burden，家居安全≈CDC STEADI/Hendrich II
- HK 事實：CCSV 2026-27 $4,526-$10,824/月 6 級共同付款 (5-40%)；SCNAMES = 法定門檻；私營上門護理 ~$100-400/hr；資助院舍輪候 ~20-40+ 月
- 四象限模型：概念上有效 (CGA/ICF 思維)，但定位必須是「SCNAMES 前的家庭決策支援工具」，非替代品

### 決策
- ✅ Option B：每維度用自己的驗證分級帶 → 各自觸發建議；不做跨表單加總/加權合成
- ✅ 主目標 = SEO/GEO + WhatsApp/Email 報告引流 (WATI 整合中)
- ✅ 心理健康工具 = 11 題 CES-D 節錄版，cutoff 非驗證 (1-9女/11男 不符任何工具) → 需決定 GDS-15 重建 vs 重新標籤
- ✅ BPSD 缺口：失智+行為症狀是家屬放棄居家 #1 原因 → AD8 (認知觸發) + NPI-Q (12域, 照顧者填, ~5min, 含照顧者困擾分)
- ✅ 心理評估角色 = 修正因子 + 紅旗 override，非第三軸；GDS 在失智下不可靠
- ✅ 沒有現成的「外行決策樹」；HK 實務 = 維度+試行 (人病家錢風險)；台灣 SDM 決策輔助 = 最接近的格式 (A/B 方案對照)
- ✅ 可用性：4/5 工具家屬可觀察填寫；心理需長者自答；單獨 1-5 min，全部 12-18 min 太長

### 08-08 凌晨新增決策
- ✅ **Tier 1 → Tier 2 觸發規則**: 任何 🟡 → 選配 module; 任何 🔴 → module + 專業評估推薦; Q5=2 → 直接觸發 NPI-Q + 家居安全警告; Q6=2 → 家居安全警告 + NPI-Q/AD8 選配; Q10=2 → 強烈推薦安全 module; 情緒🔴 → 心理健康建議 + GDS-15 選配; 全部🟢 → 無 Tier 2 選配 (詳見 TIER1 doc 觸發表)
- ✅ **Lean 格式**: 永遠附「為什麼」— 2-3 行引用旗標卡 + 實際意義; 非黑箱計算器
- ✅ **Lean 數目**: 全部良好 → 1 個; 兩軸皆需介入 → 院舍 primary + 居家密集護理 alternative; 預設 2 個, 不多於 2 (避免選擇麻痺); 仿 HK/TW SDM 的 A/B 並列
- ✅ **原網頁內容保留 ~85-90%**: 4 care-type 框架、所有 stats、3-step 評估敘事、doctornowhome 內鏈、CTA、比較表全部保留 (SEO/GEO backbone); 5 個評估工具轉成 Tier 2 modules (題目/計分/判定規則全數沿用)

## 2. 三個選項

### 選項 A — 內容先行 (SEO/GEO 最快)
把 5 個評估工具 (或 7 個含 AD8/NPI-Q) 做成**靜態、可索引、LLM 可引用**的內容頁：完整題目 + 驗證分級帶 + 建議文字 + WhatsApp CTA。互動計分後置。
- 優點：最快見 SEO/GEO 效應；零 UX 風險；內容即 GEO 燃料；不需先解 GDS-15 之爭 (內容可同時呈現)
- 缺點：無個人化、無分數→報告轉換；轉換率弱；之後仍要做互動版
- 連鎖：redirect 任務合併；TRP 雙語；staging-first；無 WATI 依賴

### 選項 B — 兩層互動流程 (建議)
**Tier 1 快速版 (~3 min, 5 題)**：每維度一題 (自理/認知行為/家庭支援/家居安全/情緒) → 即時傾向 + 四象限 lean + WhatsApp CTA。
**Tier 2 完整版 (可選 modules, 每項標明時間)**：ADL 3 / IADL 2 / GDS-15 4 / 家居安全 2 / 家庭資源 1 / 醫療複雜度 1 / AD8 2 → NPI-Q 4 (條件觸發)。完成任一 module 即出該維度建議；全完成出 A/B 方案報告 (仿 SDM)。sessionStorage 存檔續做。
- 優點：尊重注意力 (Tier 1 已夠 80% 用戶)；短版利 SEO 分享；深度版 opt-in；符合 HK 實務；每步 WhatsApp CTA
- 缺點：雙流程建置+維護；Tier 2 使用率可能低；需先定 GDS-15
- 連鎖：WATI webhook 接收分數 payload；報告模板 (CBO 起草)；醫療複雜度 module 新寫；臨床審閱分級帶

### 選項 C — 完整精靈 + WATI 自動報告
單一入口 5-7 步精靈，進度條、分數卡、結果儀表板 (雷達圖)、報告自動生成並經 WATI 送出。
- 優點：最完整品牌體驗；全自動；最強轉換
- 缺點：最大建置量；最長時程；若 Tier 1 已覆蓋多數用戶則過度投資
- 連鎖：全部 B 的連鎖 + 報告自動化邏輯 + 模板/QA 一致性；臨床審閱壓力最大

## 3. 共通連鎖效應與緩解
1. **Redirect 任務合併**：舊 `?cff-form=N` + 長者照護選擇頁 CTA → 新流程 (與 CTO 的 redirect 任務合併)
2. **Form 9 錯貼內容**：一併修正
3. **GDS-15 決策**：重建 vs 重新標籤 (獨立於選項，但影響心理健康 module 內容)
4. **AD8/NPI-Q**：需 HK 驗證譯本 (TC wording) + TRP dict
5. **TRP 雙語**：所有新 UI 字串 byte-exact dict 條目
6. **Elementor**：新頁面 staging-first + element-cache 驗證 (curl+grep)
7. **私隱**：sessionStorage 本機；分數傳送 = opt-in (WhatsApp/email 即同意)；報告含免責 (服務聲明連結)
8. **臨床審閱**：分級帶/紅旗規則需指定審閱人
9. **WATI**：webhook + 模板 + 分數 payload 解析 (CTO)；自動 vs 半自動待定
10. **計分**：Option B 已免除跨表單加權 — 每維度用自己的驗證帶

## 4. 待 Wilson 決策 (gating)
- [ ] ~~選項 A / B / C~~ → ✅ B 已選 (18:23)
- [ ] ~~Tier 1 題數~~ → ✅ 15 題 (18:19-18:23)
- [ ] ~~lean 格式/數目~~ → ✅ 附 why + 最多 2 個 (00:28)
- [ ] GDS-15 重建 vs 重新標籤
- [ ] WATI 全自動 vs 半自動 (通知員工)
- [ ] 臨床審閱人 (分級帶 + 紅旗規則 + 建議文案)
- [ ] 新頁面 URL 結構 (每表單一頁 / assessment/ 前綴)
- [ ] 背景題 C1/C2 去留
