# AngelCare 評估工具 — 網站頁面架構 (IA v2, 2026-08-08)

**建置原則 (Wilson 確認 12:44):**
- 🆕 **全新頁面, 由零開始** — 唔改/唔 overwrite 現有頁面
- 📁 **一個 master page + 子頁 (sub-menu items)** — 所有新頁喺 master 之下
- 📋 **內容重用 = 抄過去新頁** — 現有內容 copy 到新頁 (唔係 edit 舊頁)
- 🗑️ **新頁 ready + 驗證 + Wilson 批准後, 先刪舊頁**

---

## 現有 master page (參照來源, 唔改動)

**URL:** `ac-staging.hkdrnow.com/從家居安老到院舍安老，全面了解長者照護選擇/` (WP page ID **636**)
**內容結構 (已抓取確認):**
- H1: 居家安老計算機：幾分先夠免入院？
- 一、老年人照顧可分為四個類型 (4 care types)
- 二、居家安老的評估流程 (3-step)
- 三、何時考慮院舍照顧？ + 私營院舍的收費明細
- 四、最終決策
- 內嵌 CTA: `?cff-form=6..10` (5 個評估表單)

→ 呢啲內容**全部抄去新 master + 子頁**, 舊頁 636 原封不動留到新頁 ready

---

## 新架構 — Master + 子頁 (sub-menu)

```
/長者照護選擇/ (新 master page — 全新, 內容由 636 抄入 + 升級)
│   ├─ 品牌使命 banner (集團願景)
│   ├─ 4 care-type 框架 (抄自 636)
│   ├─ 評估工具入口 CTA (取代 ?cff-form)
│   └─ 子頁導覽 (sub-menu)
│
├── /居家安老/           (子頁 1 — 靜態內容, SEO: 居家安老)
├── /居家社區服務/        (子頁 2 — 靜態內容, SEO: 日間護理/社區服務)
├── /上門護理服務/        (子頁 3 — 靜態內容, SEO: 上門護理)
├── /安老院考慮/          (子頁 4 — 靜態內容, SEO: 安老院輪候/院舍收費)
└── /評估工具/           (子頁 5 — 互動 wizard, 4-phase adaptive flow)

PDF 個人化報告 = 生成輸出 (email), 唔係頁面
5-區服務目錄 = 靜態 JSON lazy-load, 唔係頁面
```

**子頁定位 (WordPress parent-child → 自動 sub-menu):**
- master = parent page; 5 個子頁 = child pages → 導覽 menu 自然出現下拉
- 每個子頁由零建 (Elementor 新 page), 內容由 636 對應 section 抄入

---

## 內容搬移對照 (636 → 新頁)

| 636 section | 搬去邊 |
|---|---|
| 一、四類型 framework | master (保留) + 每類型各開一子頁詳述 |
| 二、評估流程 3-step | master + /評估工具/ 引言 |
| 三、何時考慮院舍 + 私營院舍收費明細 | /安老院考慮/ (內容 copy, 加官方名冊轉介) |
| 四、最終決策 | master (改為 CTA → /評估工具/) |
| ?cff-form=6..10 CTA | 全部改指 /評估工具/ (新 wizard) |
| 統計數字/內鏈/比較表 | 按主題分散到 master + 各子頁 |

---

## 刪除時序 (舊頁清理)

1. Phase A: 建 master + /評估工具/ + 2 個高搜尋子頁 (staging 8092)
2. Wilson 喺 staging preview + 批准
3. Phase B: 其餘 2 子頁 + 5-區 JSON 接入
4. Phase C: 全數驗證 (rendered HTML curl+grep) → Wilson GO
5. **先刪舊頁 636 + 5 個舊 form** → redirect 舊 CTA 到新 master/評估工具
6. Prod 同步 (同一套新頁)

---

## 建置備註 (CTO handoff, staging-first)
- 所有新頁由零建喺 8092, 每個附 staging verify URL
- 舊頁 636 **唔准改** — 只作內容參照來源
- Elementor 新頁面: element-cache TTL 已 disable on 8092, 每個改動 curl+grep 驗證
- TRP 雙語: 新 UI 字串 byte-exact dict (TC + EN); 子頁 slug 中文 (同 636 模式)
- 互動 wizard = CPCF conditional logic, 單一 URL, sessionStorage
- 5-區 JSON = 靜態檔 lazy-load
- Master 子頁關係 = WP parent-child (自動 sub-menu), 唔使手動 menu 設定
