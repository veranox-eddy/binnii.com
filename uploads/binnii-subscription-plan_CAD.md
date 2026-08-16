# Binnii Active Children 訂閱方案規劃- 加拿大市場

> **品牌**：Binnii by Haody
> **規劃日期**：2026-07-23
> **競品依據**：[Childcare Management Software 競品功能矩陣與定價報告](feature-matrix-pricing-report.md)
> **容量依據**：[BC Childcare Facilities](bc_childcare_facilities.csv)
> **產品依據**：[托育中心後台管理系統 PRD](../../supporting-assets/EddyPRD/托育中心後台管理系統_PRD.md)
> **價格幣別**：Canadian Dollar（CAD），未含適用的 GST／HST／PST

## 1. 執行摘要

Binnii 採公開定價。每個 Organization 只訂閱一套 Go、Plus 或 Pro，旗下所有 Center 共用方案額度；計費數量為 Organization 在每個計費日彙總並去重後的 Active Children。三方案的產品功能相同（將來有可能會變化），只以內含 Active Children 額度、超額單價及適用規模區分，不依 Center 數量重複收取基本費，也不另對 Classroom、管理員、教師或家長帳號收取 seat fee。

| 方案 | 月繳 | 年繳（八折） | 適合客群 |
|---|---:|---:|---|
| Go | CAD $10／Organization／月，含 10 名 Active Children；第 11 名起每名 CAD $1／月 | 基本年費 CAD $96，含 10 名；超額每名 CAD $0.80／月 | Home daycare、家庭式托育及小型 Organization |
| Plus | CAD $30／Organization／月，含 40 名 Active Children；第 41 名起每名 CAD $0.80／月 | 基本年費 CAD $288，含 40 名；超額每名 CAD $0.64／月 | 成長中的托育業者及中型 Organization |
| Pro | CAD $70／Organization／月，含 100 名 Active Children；第 101 名起每名 CAD $0.60／月 | 基本年費 CAD $672，含 100 名；超額每名 CAD $0.48／月 | 大型或多 Center Organization |

Pro 採 CAD $70 基本月費與 CAD $0.60 超額單價，使三方案的超額單價形成 CAD $1.00 → $0.80 → $0.60 的清楚規模折扣。Organization 的 Active Children 達 100 名時，Plus 為 CAD $78，Pro 為 CAD $70，因此成長中的單一或多 Center Organization 都有合理升級誘因。

本文件是 Binnii 的目標商業與產品規劃，不代表所有權益已經完成。正式定價頁只能將已可交付且可驗收的功能標為 Available；尚未完成者應標示 Coming soon，或等功能完成後再公開販售。

## 2. 計價方式

### 2.1 月繳公式

設 Organization 整月每日去重後的 Active Children 數量皆為 `N`：

```text
Go monthly total = CAD $10 + max(0, N - 10) × CAD $1
Plus monthly total = CAD $30 + max(0, N - 40) × CAD $0.80
Pro monthly total = CAD $70 + max(0, N - 100) × CAD $0.60
```

`N` 是 Organization 整月人數不變時的 Active Children；若月中有人數或 Center 配置變化，超額人數依第 6 章的每日比例計算。

### 2.2 年繳公式

年繳為月繳價格的 80%，即打八折：

```text
Go annual base = CAD $10 × 12 × 80% = CAD $96
Plus annual base = CAD $30 × 12 × 80% = CAD $288
Pro annual base = CAD $70 × 12 × 80% = CAD $672

Go annual overage rate = CAD $1 × 80% = CAD $0.80 per Active Child-month
Plus annual overage rate = CAD $0.80 × 80% = CAD $0.64 per Active Child-month
Pro annual overage rate = CAD $0.60 × 80% = CAD $0.48 per Active Child-month
```

年繳客戶先支付 12 個月基本年費；因 Active Children 會變動，超額費用仍按月結算並於次月收取，但使用各方案八折後的超額單價。

### 2.3 商業結構提醒

三方案功能相同時，客戶應選擇當下人數成本最低的方案。月繳損益平衡點如下：

```text
At 30 Active Children:
Go = CAD $30
Plus = CAD $30

At 90 Active Children:
Plus = CAD $70
Pro = CAD $70
```

因此，1–30 名通常以 Go 最便宜，31–90 名以 Plus 最便宜，91 名以上以 Pro 最便宜。公開計價器應主動標示最低成本方案；續訂前若其他方案更便宜，也應提醒客戶切換，避免形成「忠誠客戶反而多付費」的負面體驗。

## 3. Active Child 定義

### 3.1 計費條件

某名兒童在某一天於 Organization 旗下任一 Center 同時符合下列條件時，計為該 Organization 的一名 Active Child：

- Enrollment 狀態為 `Active`。
- Enroll date 已到達或早於該日。
- Grad date、withdrawal date 或 service end date 尚未到達，或欄位為空。
- 兒童帳戶未被合併、刪除或標記為重複資料。

### 3.2 不計費狀態

- Applicant、Waitlist、Registration 尚未入學者。
- Upcoming／Scheduled 且 Enroll date 尚未到達者。
- Graduated、Withdrawn、Cancelled 或 Archived，且終止日已生效者。
- 示範、測試或 sandbox 資料；正式帳戶必須能明確標記，且不得用於真實照護紀錄。

### 3.3 特殊情況

- **請假、病假或度假**：只要 Enrollment 仍為 Active，仍計費；短期缺席不代表已終止服務。
- **Part-time 兒童**：每名計為 1 名，不按每週天數折算，避免規則過度複雜。
- **跨 Classroom 與 Center 去重**：同一名兒童即使同日在 Organization 旗下多個 Classroom 或 Center 有 Active Enrollment，仍只計 1 名。
- **唯一兒童識別**：跨 Center 的同一名兒童 MUST 對應至 Organization 內的 canonical child ID；重複資料合併後重新計算，多收金額於下一張 Organization 帳單列為 credit。
- **Center 沒有 Active Children**：Center 本身不產生基本費；只要 Organization 訂閱未取消，仍收取該方案基本月費或基本年費。

## 4. 方案與訂閱權益

### 4.1 三方案共同包含的功能

Go、Plus 與 Pro 的功能權益相同。方案升級只增加內含 Active Children 額度並降低超額單價，不解鎖額外產品功能。

- Center、Classroom、Child、Parent／Guardian、Emergency Contact 與 Designated Pickup 檔案。
- 不限制 Center 或 Classroom 數量，也不另收使用者 seat fee；價格只依 Organization 去重後的 Active Children 計算。
- Enrollment、班級名冊、兒童排程、缺席及轉班管理。
- 兒童與員工 Check-in／Check-out Kiosk。
- 15 類日常托育 Entry、Daily Report 建立、預覽、編輯及寄送。
- 健康、睡眠、過敏、醫療註記及事故紀錄。
- 照片、影片、Journal、Portfolio、兒童標記及照片授權檢查。
- Email／SMS 訊息、公告、附件、範本及排程寄送；SMS 電信費另計。
- Contacts、Menus、Calendars 與事件管理。
- 員工檔案、證照、打卡、Time Log 及師生比。
- 標準營運報表與 CSV／PDF／Excel 匯出。
- Director／Teacher 角色權限、基本系統設定及 Help Center。
- 所有方案共用的 MFA 與安全稽核能力；安全功能不作為高價方案限定權益。
- Registration、Applicant、Waitlist、Registration Package、公開申請連結、自訂欄位、Permissions、E-consents 與文件收集。
- 招生名額預測、候補優先排序及入學轉換。
- 家長 Self-service Portal／PWA：日報、照片、訊息、缺席、文件、行事曆、帳務、付款與 Tax Receipt。
- Billing 與 Subsidies：發票、收款、Ledger、credit／refund、補助、copay、付款提醒及 Tax Receipt；支付服務商交易費另計。
- 完整員工排班、重複班次、跨教室派工、Time Off 申請／核准、工時核准及師生比風險警示。
- Weekly Planner、Activity Library、Portfolio、Development Evidence、learning framework 與學習評量。
- 進階報表、自動寄送、財務摘要、招生漏斗、課綱進度及跨中心彙總。
- 未來多語 i18n、家長語言偏好、訊息翻譯輔助及 Live Translation。
- 多中心 Head Office Dashboard、跨中心比較、中央設定、範本及權限政策。
- 招生 CRM、聯絡歷程、參觀安排、失單原因及自動跟進。
- QuickBooks／Sage 匯出與雙向整合。
- 公開 API、Webhook、sandbox、SSO、自訂角色及進階 Audit Log。
- AI 教師草稿、活動建議、營運摘要及排班建議；所有正式紀錄均須由人員確認。
- 優先 Email 支援。

尚未完成或尚未納入 PRD 的功能，必須依第 5 章標示 Coming soon；不能因列於共同權益就提前宣稱 Available。

### 4.2 Go

**CAD $10／Organization／月，含 10 名 Active Children；第 11 名起每名 CAD $1／月。**

適合 Home daycare、家庭式托育與小型 Organization。BC 設施資料中 48.6% 的 `Max Capacity at One Time` 不超過 8 名，10 名額度能為典型 8 人規模保留 25% 的 Active Children 餘裕。

### 4.3 Plus

**CAD $30／Organization／月，含 40 名 Active Children；第 41 名起每名 CAD $0.80／月。**

適合成長中的托育業者與中型 Organization。BC 設施第 75 百分位容量約為 35 名，40 名額度可涵蓋多數中型單一 Center，亦可供多個小型 Center 共用。

### 4.4 Pro

**CAD $70／Organization／月，含 100 名 Active Children；第 101 名起每名 CAD $0.60／月。**

適合大型、多班級或多 Center Organization。BC 設施第 95 百分位容量為 84 名，100 名額度可涵蓋大型單一 Center，也能讓多 Center 業者彙總使用並取得較低的平均每名兒童成本。

## 5. 功能與 PRD 規劃狀態

### 5.1 判讀方式

| 標示 | 意義 | PM 處理方式 |
|---|---|---|
| ✅ PRD 已規劃 | PRD 已有對應模組、流程或資料規格；不代表已完成開發 | 納入既有開發與驗收範圍，並依 PRD 第 24 章補完未實作流程 |
| 🟡 PRD 部分規劃 | PRD 有相關入口或部分資料，但不足以交付本表所述完整權益 | PM 必須補產品流程、資料、權限與驗收條件 |
| ➕ PRD 未規劃 | PRD 沒有足以支持此權益的規格 | PM 必須建立後續 Epic／PRD，完成前不得宣稱 Available |
| — 商業規則 | 計價或包裝規則，不是既有產品 PRD 功能 | 納入 Billing、Entitlement 與定價頁規格 |

### 5.2 三方案共同核心功能

| 訂閱功能 | PRD 狀態 | PRD 依據／缺口 |
|---|---|---|
| Center、Classroom、Child 與家庭聯絡人檔案 | ✅ PRD 已規劃 | 第 6、7、14、21、22 章 |
| 不限 Classroom 與不另收使用者 seat fee | — 商業規則 | PRD 第 1.2 節支援動態新增教室；實際 entitlement 及防濫用規則需另建 Billing 規格 |
| Enrollment、名冊、排程、缺席與轉班 | ✅ PRD 已規劃 | 第 5、6、7、10 章 |
| 兒童及員工 Kiosk 簽到退 | ✅ PRD 已規劃 | 第 20 章；Sign-in Codes、簽名及身分驗證列於第 24 章待實作 |
| 15 類托育 Entry 與 Daily Reports | ✅ PRD 已規劃 | 第 8、9 章；寄送、重寄及 Entries Log 列於第 24 章待實作 |
| 健康、睡眠、過敏、醫療與事故紀錄 | ✅ PRD 已規劃 | 第 6、11 章；事故建立表單列於第 24 章待補設計與實作 |
| 照片、影片、Journal、Portfolio 與授權檢查 | ✅ PRD 已規劃 | 第 6.3、8.3、12、23 章 |
| Email／SMS、附件、範本與排程訊息 | ✅ PRD 已規劃 | 第 13、23 章；SMS、附件及範本實際邏輯列於第 24 章待實作 |
| Contacts、Menus、Calendars 與事件 | ✅ PRD 已規劃 | 第 14、18 章；事件新增／編輯列於第 24 章待實作 |
| 員工檔案、證照、打卡、Time Log 與師生比 | ✅ PRD 已規劃 | 第 4.2、7.2、17、20.2 章 |
| 標準報表與 CSV／PDF／Excel 匯出 | ✅ PRD 已規劃 | 第 19 章；檔案產生與寄送列於第 24 章待實作 |
| Director／Teacher 權限與中心設定 | ✅ PRD 已規劃 | 第 21、23 章；細部設定頁與登入管理列於第 24 章待實作 |
| Help Center | ✅ PRD 已規劃 | 第 21.3 節 |
| Multi-factor authentication（MFA） | ➕ PRD 未規劃 | **後續需追加**：登入挑戰、復原碼、裝置記憶、管理員強制政策與稽核事件 |
| 全系統安全 Audit Log | 🟡 PRD 部分規劃 | 第 23 章只有日報、缺席及簽到退等局部稽核；**後續需追加**完整管理操作、資料異動、查詢與匯出規格 |

### 5.3 三方案共同目標擴充功能

| 訂閱功能 | PRD 狀態 | PRD 依據／缺口 |
|---|---|---|
| Registration、Applicant、Waitlist、表單、E-consents 與文件 | ✅ PRD 已規劃 | 第 5.3–5.5 節；家長端公開申請頁列於第 24 章待補設計與實作 |
| 招生名額預測與入學轉換 | ✅ PRD 已規劃 | 第 5.3、5.6 節 |
| 家長 Self-service Portal／PWA | ➕ PRD 未規劃 | PRD 第 1.2 節明示家長端 App 詳細規格不在範圍；**後續需追加**家長登入、權限、日報、照片、訊息、缺席、文件、付款與帳務流程 |
| Billing、Subsidies、發票、付款、Ledger 與 Tax Receipt | ✅ PRD 已規劃 | 第 6.1、16、24 章；完整 Billing 仍待設計與實作，但已屬 PRD 規劃範圍 |
| 完整員工排班、Time Off 核准與工時核准 | 🟡 PRD 部分規劃 | 第 17 章有班表欄位、工時、Time Off 入口及師生比；**後續需追加**排班器、重複班次、跨班派工、申請／核准與衝突處理流程 |
| Weekly Planner、Activity Library、Portfolio 與 Development Evidence | ✅ PRD 已規劃 | 第 6.3、8.4、15 章 |
| 可配置 learning framework 與學習評量 | 🟡 PRD 部分規劃 | PRD 有 Development Skill、Evidence 與課程活動，但缺 framework schema、里程碑、評量量表及家庭分享流程；**後續需追加** |
| 進階報表與自動寄送 | ✅ PRD 已規劃 | 第 19、21.1、24 章；產生與排程寄送邏輯尚待實作 |
| 財務摘要、招生漏斗及課綱進度報表 | ➕ PRD 未規劃 | **後續需追加**指標定義、資料來源、篩選、匯出與權限 |
| 多語介面與家長語言偏好 | ➕ PRD 未規劃 | **後續需追加**核准語系、i18n 架構、翻譯內容、fallback 與語言偏好 |
| 訊息翻譯輔助與 Live Translation | ➕ PRD 未規劃 | **後續需追加**翻譯供應商、成本、同意、原文對照、人工確認及錯誤處理 |
| 多中心切換 | ✅ PRD 已規劃 | 第 1.2、21.1、21.2、23 章 |
| Head Office Dashboard、跨中心比較與中央政策 | ➕ PRD 未規劃 | 現有 PRD 只有中心切換，沒有總部彙總與中央治理；**後續需追加**Organization 層級、資料範圍、政策繼承與跨中心報表 |
| 招生 CRM 與自動跟進 | ➕ PRD 未規劃 | 現有 Registration／Waitlist 不含 lead source、聯絡歷程、參觀、失單原因或 automation；**後續需追加** |
| QuickBooks／Sage 匯出與雙向整合 | ➕ PRD 未規劃 | **後續需追加**會計 mapping、同步方向、失敗重試、對帳及權限 |
| 公開 API、Webhook 與 sandbox | ➕ PRD 未規劃 | 第 24 章的 backend API 是產品內部實作需求，不等於客戶公開 API；**後續需追加**認證、rate limit、事件、版本與開發者文件 |
| SSO 與自訂角色 | 🟡 PRD 部分規劃 | PRD 已有 Director／Teacher／多中心管理員角色，但沒有 SAML／OIDC、role builder 或細粒度權限；**後續需追加** |
| 進階 Audit Log | 🟡 PRD 部分規劃 | 現有稽核範圍有限；**後續需追加**全域事件模型、保留期、篩選、匯出及不可竄改要求 |
| AI 草稿、活動、營運摘要及排班建議 | ➕ PRD 未規劃 | **後續需追加**用途限制、人工核准、資料隱私、模型供應商、成本與品質驗收 |
| 優先 Email 支援 | — 商業規則 | 建立 SLA、服務時間、優先級及升級流程，不屬產品功能 PRD |

### 5.4 PM 必須追加的功能清單

下列項目不是目前 PRD 的完整規劃，若要作為訂閱權益販售，PM 必須建立後續 Epic／PRD：

1. 全方案安全基線：MFA 與全系統 Audit Log。
2. 家長 Self-service Portal／PWA。
3. 完整員工排班、Time Off 與工時核准工作流。
4. 可配置 learning framework、里程碑及評量。
5. 財務、招生漏斗與課綱進度報表。
6. 多語 i18n、語言偏好、翻譯輔助及 Live Translation。
7. Head Office Dashboard、跨中心彙總與中央政策管理。
8. 招生 CRM 與自動跟進。
9. QuickBooks／Sage 匯出及雙向整合。
10. 客戶公開 API、Webhook、sandbox 與開發者文件。
11. SSO、自訂角色及細粒度權限。
12. AI 草稿、活動、營運摘要與排班建議。

> 第 5.4 節是「PRD 規劃缺口」，不是「尚未完成開發清單」。PRD 已規劃但仍待實作的項目，應另外依 PRD 第 24 章追蹤。

## 6. 結算規則

### 6.1 計費週期與收款時間

- 每個 Organization 只有一個 billing cycle、一套 Go／Plus／Pro 方案、一個 payment profile 及一張彙總帳單；旗下 Center 共用方案與額度。
- Add Center 不建立新訂閱、不重選方案、不收取額外 location fee；新 Center 直接繼承 Organization 的有效付費訂閱或 active trial entitlement。
- 月繳基本月費於週期開始時預收；當期超額費用於週期結束後計算，列入下一張帳單。
- 年繳基本年費於年期開始時預收；超額費用仍每月結算，Go／Plus／Pro 分別使用八折後的 CAD $0.80／$0.64／$0.48 單價。
- 稅額依 Organization billing profile 的帳單地址及發票日期另計。
- 帳單必須分列：方案基本費、每日超額換算、調整／credit、第三方用量費及稅額，並提供各 Center 的 Active Children 用量明細；Center 明細不形成獨立 invoice 或基本費。

### 6.2 以每日 Active Children 計算

不採「當月最高人數」或「結算日快照」，而是每日取得 Organization 內跨 Center 去重後的 Active Children 唯一人數，再按當期天數換算。這能公平處理月中入學、退園及跨 Center 服務，也降低客戶在結算日前暫時改狀態規避費用的誘因。

對 billing cycle 中每一天 `d`：

```text
Go daily overage(d) = max(0, Active Children(d) - 10)
Plus daily overage(d) = max(0, Active Children(d) - 40)
Pro daily overage(d) = max(0, Active Children(d) - 100)

Overage child-months = sum(daily overage) / days in billing cycle

Go monthly overage charge = overage child-months × CAD $1
Plus monthly overage charge = overage child-months × CAD $0.80
Pro monthly overage charge = overage child-months × CAD $0.60

Go annual-plan overage charge = overage child-months × CAD $0.80
Plus annual-plan overage charge = overage child-months × CAD $0.64
Pro annual-plan overage charge = overage child-months × CAD $0.48
```

只在整張發票小計完成後四捨五入至 CAD $0.01；不可每天先四捨五入，以免累積誤差。

### 6.3 每日快照與可稽核性

Billing 系統每天依 Organization billing profile 的時區建立不可直接編輯的 snapshot，至少保存：

- Snapshot date、Organization、plan、included allowance 及 billing timezone。
- Organization 去重後的 Active Children 數、構成名單的 canonical child IDs，以及各 Center 的用量明細。
- 每名兒童的 Enrollment status、effective start date 與 effective end date。
- 當日超額人數、日比例及使用單價。
- 建立時間、重算版本、調整原因與操作人。

管理員必須能在 Billing 頁查看「本期預估帳單」、每日人數明細及狀態異動紀錄，並能匯出 CSV。這些 metering、invoice preview 與 adjustment 能力不在現有產品 PRD 中，PM 必須另外建立訂閱計費 PRD。

### 6.4 月中入學、退園與資料回溯

- 月中入學：自 Enroll date 生效日起納入每日計數。
- 月中退園／畢業：effective end date 生效日起不再計數。
- 預先輸入未來日期：只在日期到達後影響計費。
- 回溯修改：系統重新計算受影響日期；已開立帳單不直接竄改，差額於下一張帳單列為 debit adjustment 或 credit。
- 一般客戶可回溯更正最近 60 天；超過 60 天需由具 Billing 權限的管理員審核並留下原因。此期限上線前應由財務與客服確認。
- 重複兒童合併：重新計算同一 Organization 內跨 Classroom／Center 的重複計數，下一張 Organization 帳單自動抵扣。

### 6.5 方案升降級

- Go → Plus → Pro：升級可立即生效；生效日前依原方案額度與單價計算，生效日起使用新方案額度與單價，基本費差額按剩餘天數補收。
- Pro → Plus → Go：降級於下一個月繳週期生效；年繳方案於下一個年期生效，避免週期內反覆切換額度。
- 升降級當日以 Organization billing timezone 的 00:00 為界，不在同一天混用兩個額度。
- 三方案功能相同，降級不會失去功能或歷史資料；確認畫面必須顯示 Organization 名稱、新額度、超額單價及依目前彙總 Active Children 推估的下期帳單。

### 6.6 取消、退款與付款失敗

- 月繳取消於當期結束生效；當期基本費不按未使用天數退款，超額費計至終止日。
- 年繳取消於目前年期結束生效；除法律要求或 Binnii 服務條款另有規定外，已付基本年費不退款。
- 付款失敗應先進入 grace period 並通知 Organization 帳戶管理員，不得立即刪除兒童資料；若最終進入停用狀態，旗下所有 Center 同時停用，不提供個別 Center 例外。
- Organization 取消訂閱時，取消於適用期末對旗下所有 Center 同時生效。
- Grace period、唯讀期、資料匯出期與最終刪除政策必須在服務條款及產品內清楚揭露。

### 6.7 第三方費用

- Payment Processing 的卡片、銀行扣款、退款及 chargeback 費用不含於訂閱價格，必須在用戶啟用付款前公開。
- SMS carrier fee 不含於訂閱價格；正式售價需在選定供應商並確認加拿大 carrier cost 後公開。
- 不得以未公開的 platform fee、每位教師費或每位家長費改變上述訂閱公式。

## 7. 價格試算

### 7.1 Active Children 整月不變

以下未含稅與第三方費用：

| Active Children | Go 月繳 | Plus 月繳 | Pro 月繳 | 最低月費方案 |
|---:|---:|---:|---:|---|
| 5 | $10 | $30 | $70 | Go |
| 10 | $10 | $30 | $70 | Go |
| 30 | $30 | $30 | $70 | Go／Plus 同價 |
| 40 | $40 | $30 | $70 | Plus |
| 60 | $60 | $46 | $70 | Plus |
| 90 | $90 | $70 | $70 | Plus／Pro 同價 |
| 100 | $100 | $78 | $70 | Pro |
| 150 | $150 | $118 | $100 | Pro |
| 200 | $200 | $158 | $130 | Pro |

年繳假設 Active Children 整年不變，基本費與超額費均打八折：

| Active Children | Go 年繳 | Plus 年繳 | Pro 年繳 | 最低年費方案 |
|---:|---:|---:|---:|---|
| 10 | $96 | $288 | $672 | Go |
| 30 | $288 | $288 | $672 | Go／Plus 同價 |
| 40 | $384 | $288 | $672 | Plus |
| 60 | $576 | $441.60 | $672 | Plus |
| 90 | $864 | $672 | $672 | Plus／Pro 同價 |
| 100 | $960 | $748.80 | $672 | Pro |
| 150 | $1,440 | $1,132.80 | $960 | Pro |
| 200 | $1,920 | $1,516.80 | $1,248 | Pro |

### 7.2 月中新增兒童範例

採用 Go 方案的 Organization 在 30 天週期中：旗下所有 Center 去重後的 Active Children，前 15 天為 10 名，後 15 天為 12 名。

```text
Daily overage total = 15 days × 0 children + 15 days × 2 children = 30 child-days
Overage child-months = 30 / 30 = 1
Monthly invoice = CAD $10 base + CAD $1 overage = CAD $11 before tax
```

若為年繳客戶，基本年費已預收，該月只收 `1 × CAD $0.80 = CAD $0.80` 的超額費，再加適用稅額。

## 8. 公開定價頁必須揭露

- Go、Plus 與 Pro 三種方案的基本費、內含人數及各自超額單價。
- 月繳／年繳切換，以及年繳八折後的基本費與 CAD $0.80／$0.64／$0.48 超額單價。
- 每個 Organization 只使用一套方案、Add Center 不另收 location fee，以及後續 Center 共用有效方案或 trial entitlement。
- Active Child 的定義、請假仍計費、跨 Classroom／Center 的同一名兒童每日只計一次等規則。
- 帳單由預收基本費與次月結算的變動超額費組成，因此每月總額可能不同。
- 即時計價器：輸入預估 Active Children 後顯示三方案價格、最低成本方案與年繳節省金額。
- 三方案功能相同，差異只在內含額度、超額單價及適用規模。
- 稅額、Payment Processing 與 SMS 等第三方費用不包含在訂閱價格內。
- Coming soon 功能不得顯示為已可使用。

建議公開文案：

```text
One plan for your entire organization.
Pay for active children—not locations, classrooms, parents, or staff accounts.
```

## 9. 方案名稱

由於 Binnii 三方案功能相同，公開名稱必須搭配「內含幾名 Active Children」，避免客戶誤以為低價方案缺少核心功能。對外使用：

- **Go** — Includes 10 active children
- **Plus** — Includes 40 active children
- **Pro** — Includes 100 active children



## 10. 上線前必要工作

1. 建立 subscription entitlement 與每日 Active Children metering 規格。
2. 建立 invoice preview、snapshot、adjustment、credit 與計費稽核紀錄。
3. 逐項確認第 5 章功能的 Available／Coming soon 狀態。
4. 對 PRD 未規劃與部分規劃項目建立 Epic、優先級及驗收條件。
5. 驗證每個 Active Child 及每個 Center 的儲存、Email、SMS、支援與基礎設施成本。
6. 以不同 Center 數量搭配 10、30、40、60、90、100、150 與 200 名 Organization Active Children 的情境驗證 Gross Margin。
7. 用 Home daycare、中型中心、大型中心及多中心客戶訪談驗證 CAD $10／$30／$70 的付費意願。
8. 由財務、法務與客服確認稅務、退款、回溯更正、grace period 及資料保留條款。

在成本與客戶訪談完成前，CAD $10／$30／$70 與每名 CAD $1／$0.80／$0.60 應視為可測試的公開定價假設。
