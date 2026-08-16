# PRD — Binnii SaaS Platform Management

> **文件狀態**：Approved
> **建立日期**：2026-08-05
> **最後更新**：2026-08-09
> **負責 PM**：HAO
> **關聯 Module_Spec**：TBD
>
> **直接對齊文件-直接依賴、消費或需要同步的契約**：
> - `02_Tasks/02_Childcare_Management_Software/research/binnii-subscription-plan_CAD.md`
> - `02_Tasks/02_Childcare_Management_Software/PRD/托育中心後台管理系統_PRD.md`
> - `02_Tasks/02_Childcare_Management_Software/PRD/Binnii_Customer_Subscription_PRD.md`
> - `02_Tasks/02_Childcare_Management_Software/PRD/Binnii_Center_Management_PRD.md`
> - `02_Tasks/02_Childcare_Management_Software/Brand/Binnii_Brand_Visual_Brief.md`
> - `04_Webroot/app.binnii.com/resources/views/layouts/app.blade.php`
> - `04_Webroot/app.binnii.com/resources/views/components/sidebar.blade.php`
> - `04_Webroot/app.binnii.com/resources/views/components/`
> - `04_Webroot/app.binnii.com/resources/css/theme.css`
> - `04_Webroot/app.binnii.com/app/Models/Organization.php`
> - `04_Webroot/app.binnii.com/app/Models/User.php`
> - `04_Webroot/app.binnii.com/app/Models/Center.php`
> - `04_Webroot/app.binnii.com/app/Http/Requests/UpdateCenterSettingsRequest.php`
> - `04_Webroot/app.binnii.com/database/migrations/0001_01_01_000000_create_organizations_table.php`
> - `04_Webroot/app.binnii.com/database/migrations/0001_01_01_000000_create_users_table.php`
> - `04_Webroot/app.binnii.com/database/migrations/2026_07_16_100000_create_centers_table.php`
>

---

## 實作紅線（AI Agent MUST NOT 違反）

> 以下規範對負責實作本文件的 AI Agent 強制適用，優先於任何「看起來更好」的主觀判斷。

1. **禁止靜默改動未授權範圍**：MUST 僅修改本文件明確列出或合理推導屬於本任務範圍的程式碼；對**本文件未提及的現有 Live Code**，MUST NOT 進行任何靜默改動。若發現有連帶必要，MUST 先向 RD / PM 揭露並取得授權，才可動手。

2. 執行任何實作前，MUST 確認當前 Session 上下文中已完整讀取以下規範文件（不得以記憶或摘要替代）：

- `AI_Agent_GUIDE.md` — 專案技術治理規範
- `02_Tasks/lessons.md` — 跨任務經驗教訓
- `02_Tasks/known-fixes.md` — 已知修正清單

若任一文件於上下文中缺失，MUST 立即補讀後再繼續，MUST NOT 跳過。
若 RD 方專案內上述文件的路徑或檔名若與此處不符，以實際存在的路徑為準。

---

## 1. 功能背景與目的

《托育中心後台管理系統 PRD》與 live application 定義客戶用來經營 Center 的 tenant application，涵蓋兒童、家庭、教室、員工、出勤、日報、補助與家庭帳務。《Binnii Customer Subscription PRD》定義 customer-facing SaaS 訂閱管理，並以 Center 後台中的獨立 `Subscription` 入口呈現。

Binnii SaaS Platform Management 是整個 Binnii 產品的 control plane 與內部管理介面：

1. **Platform Core**：管理 `Organization → Center` 租戶階層、Organization 唯一的 Go／Plus／Pro 訂閱、跨 Center 去重的 Active Children 計量、Organization invoice、payment、lifecycle、notification 與 audit 契約。
2. **Binnii Admin Console**：讓 Binnii 的 Super Admin、Finance 與 Support 在權限範圍內管理客戶生命週期、訂閱、計費例外、付款失敗、平台事件與稽核紀錄。

本功能要解決的問題：

簡言之，本模組要讓 Binnii 能夠像一個正式的 SaaS 供應商一樣管理旗下的客戶（Organization），同時不干預客戶自己在 Center 現場的托育營運。具體而言：

1. **讓 Binnii 有一個內部的 SaaS 管理後台（Binnii Admin Console）**：後台運作的前提是先以 `Organization → Center` 表達清楚的租戶邊界——一家客戶不管開幾間分校都算同一個租戶，不同客戶之間的資料必須完全隔離。
2. **明確定義訂閱方案，讓帳務工作有清楚依據**：Organization 共用的 Go／Plus／Pro 訂閱、跨 Center 去重後的 Active Children 每日計量、超額費與方案變更，都要轉成可追蹤、可解釋、可稽核的流程，作為 Finance 處理發票、收付款與調整的依據；且這筆帳跟 Center 對家庭收的 Billing & Subsidies 清楚分離，不共用同一套帳目語言。
3. **讓 Support 能處理售前售後事宜，協助客戶但不能越界**：Binnii 內部團隊必須能協助客戶處理帳務與訂閱相關問題，但 MUST NOT 因為客服便利就繞過租戶隔離、或無痕查看客戶托育現場的兒童資料。

三個內部角色各自的權限範圍，見下方「主要服務角色」。

主要服務角色：

- **Customer Subscription consumer**：現有 Center 後台中的獨立 `Subscription` 模組；負責 customer-facing UI 與 customer permissions，本 PRD 不定義其版面。
- **Super Admin**：最高權限內部角色，管理 SaaS 平台政策、租戶生命週期、營運監控與最高風險操作，並涵蓋 Finance 與 Support 的全部業務權限；不等同於任何 Center 的 Director。
- **Finance**：處理 Binnii 發票、付款、credit、refund 與帳務調整。
- **Support**：查看平台層診斷資料與支援紀錄，協助排查但不直接管理兒童照護資料。

| 內部角色 | 權限範圍 |
|---|---|
| Super Admin | 可使用全部 Binnii Admin Console 業務功能，包含平台與角色管理、租戶生命週期、System Health、Finance 及 Support 權限。 |
| Finance | 可處理 invoice、payment、credit、refund、financial adjustment 與財務對帳，並可新增、檢視與編輯 support note；不可管理內部角色或執行租戶生命週期高風險操作。 |
| Support | 可查看授權範圍內的平台診斷資料並管理 support note；不可執行財務異動、內部角色管理或租戶生命週期高風險操作。 |

---

## 2. 使用情境與使用者旅程 (User Stories & Journeys)

### 2.1 User Story

- 身為 Binnii 內部人員，我希望能以 Organization、Center、Owner email 或 phone 找到客戶，並在 Tenant Detail 查看完整 Organization 與 Center Profile，以便不進入客戶營運後台也能確認聯絡與租戶資料。
- 身為 Binnii Support，我希望能以客戶提供的完整 correlation reference 直接定位對應租戶與 audit event，以便不必先猜測租戶或靠時間人工比對即可說明失敗原因與下一步。
- 身為 Binnii Support，我希望查看不含兒童照護內容的平台診斷資料，以便協助客戶解決帳戶、用量與計費問題。
- 身為 Binnii Finance，我希望所有 charge、credit、refund 與人工調整都有原因及稽核軌跡，以便完成對帳並處理爭議。
- 身為 Super Admin，我希望安全地管理租戶生命週期、處理 Finance 與 Support 業務，並監看計量、開票、付款、通知與 lifecycle job，以便維持平台安全與客戶端資料可信度。
- 身為 Super Admin，我希望 customer 首次取得 Center application access 前，Organization 與第一間 Center 已依驗證資料完成關聯，以便 customer 從正確 tenant context 開始使用並避免重複建置。

### 2.2 使用者旅程（Typical Journeys）

#### Journey A：建立 Organization 訂閱並供 Customer Subscription 消費

| 流程 | 對應功能 |
|------|----------|
| 1. Platform 依已驗證 onboarding 資料建立或匯入 Organization 與第一間 Center，完成兩者關聯後才提供 customer 的 Center application access，並套用可識別版本的訂閱政策。 | F-02、F-07、F-08、F-42、§10.1 |
| 2. Customer Subscription 以授權使用者與 Organization scope 取得共用方案、跨 Center 去重總用量、各 Center 用量明細與 estimate。 | F-04、F-06、F-12、F-33、§10.1 |
| 3. Customer Subscription 提交 Organization 方案異動時，Platform 重新驗證最新狀態並回傳生效日、受影響 Center 數量與費用 preview。 | F-15～F-17、F-23、F-36、§10.1 |
| 4. 確認後 Platform 只建立一次異動，記錄 audit event 並發送可追蹤通知。 | F-22、F-28、F-36 |

#### Journey B：Binnii Finance 或 Super Admin 處理計費爭議

| 流程 | 對應功能 |
|------|----------|
| 1. Finance 或 Super Admin 以 Organization、Center、Center ID、Account Owner email、Organization／Center phone、帳單 email 或 invoice number 搜尋客戶。 | F-05、F-24、§8.1 |
| 2. Finance 或 Super Admin 進入 Tenant Detail，比對 Organization 每日去重總用量、各 Center 用量明細、單一發票、付款事件與既有 adjustment。 | F-05、F-10、F-20、F-25、§8.2 |
| 3. 若客戶以 Interac e-Transfer 付款且到帳尚未確認，Finance 或 Super Admin 依金額與匯款參考資訊手動比對後標記已收款，invoice 狀態才更新為已付款。 | F-44、§8.6 |
| 4. 若任一 Center 回溯更正 Enrollment 或合併跨 Center 重複兒童，系統重新計算 Organization 用量；已開立發票的差額進入下一張帳單。 | F-18、F-35 |
| 5. 若仍需人工 financial adjustment，Finance 或 Super Admin 必須輸入原因並確認影響金額；系統保留不可由一般 UI 修改的稽核紀錄。 | F-05、F-28、F-34、§8.3 |

#### Journey C：付款失敗與租戶風險處理

| 流程 | 對應功能 |
|------|----------|
| 1. Organization 收款失敗後，系統將訂閱標示為付款異常並通知 Account Owner，不立即刪除任何 Center 或兒童資料。 | F-21、F-22、F-37 |
| 2. 若持續未解決，系統依生命週期政策自動升級：寬限期過後進入 Read-only，僅能查看與匯出，畫面顯示距第 90 天永久刪除的剩餘天數，並依排程於刪除日前發送通知。 | F-03、F-43 |
| 3. Platform 同時對 Customer Subscription 提供 payment issue、處理期限與允許的重試動作，不輸出完整付款憑證。 | F-03、F-19、F-21、§10.1 |
| 4. 若風險需立即人工處理（不等待自動升級排程），Super Admin 可預覽影響、填寫原因並直接執行 Suspend；Support 與 Finance 無權執行。 | F-05、F-27、§8.4 |
| 5. Center Management System 取得最新 Organization 帳戶狀態，旗下所有 Center 一致進入政策指定的正常、警示、唯讀或停用行為，但 MUST NOT 自行刪除營運資料。 | F-33、F-37、§10 |

#### Journey D：免費試用到轉換

| 流程 | 對應功能 |
|------|----------|
| 1. 建立新 Organization 時，系統依其所屬 Market 套用試用方案權限與試用天數（全局預設或 Super Admin 已個別調整之值），Organization 進入 `Active` 狀態且試用期間不收取基本費或超額費。 | F-45、F-46、F-47、F-48、F-57 |
| 2. 試用期間服務完整可用，不強迫客戶先選定 Go／Plus／Pro；Customer Subscription 可查看試用剩餘天數與方案內容。 | F-47、F-48、§10.1 |
| 3. 若客戶需要更多評估時間，Super Admin 可在 Tenant Detail 為試用中的 Organization 增加天數延長，新增天數僅疊加至到期日，不得縮短既有到期日。 | F-46、§8.2 |
| 4. 試用天數屆滿時，系統檢查付款方式驗證狀態：已完成驗證者自動轉為正式付費訂閱並於下一計費週期開始計費；尚未驗證者直接進入 `Read-only`，比照 `Unsubscribed` 邏輯，不經過 `Payment Issue`／`Grace Period`。 | F-49 |

#### Journey E：Support 依 customer-facing reference 反查事件

| 流程 | 對應功能 |
|------|----------|
| 1. 客戶將 Center setup、Subscription 或 payment 畫面顯示的完整 reference 提供給 Support。 | F-24、F-28 |
| 2. Support 在 Customers 的 `Search Customers` 貼上完整 reference；系統使用 canonical exact match，不做模糊或部分比對。 | F-24、§8.1 |
| 3. 唯一命中時，Admin Console 開啟所屬 Organization 的 Tenant Detail → `Audit`，套用 reference filter 並高亮對應 event。 | F-24、F-25、F-28、§8.2 |
| 4. Support 查看 event time、source module、Organization、Center（如適用）、event type、result 與 customer-safe reason；查無、未授權或資料衝突時顯示安全且明確的結果，不得導向錯誤租戶。 | F-05、F-24、F-28、F-29、F-38 |

---

## 3. 功能需求（Functional Requirements）

| # | 需求描述 | 強制程度 | 版本 | 備註 | 輔助說明圖表 |
|---|---------|---------|------|------|------|
| F-01 | 系統 MUST 將 Platform Core、Binnii Admin Console、Center 後台的 Customer Subscription 與 Center 日常營運功能視為不同責任邊界；Customer Subscription MUST 是 Center 後台的獨立 `Subscription` 入口，MUST NOT 併入 Family Billing & Subsidies。 | MUST | V1 | Customer-facing UI 由《Binnii Customer Subscription PRD》定義。 | §10、Consumer PRD §8 |
| F-02 | 系統 MUST 以 `Organization` 表達客戶租戶階層，並確保任何使用者只能存取其被授權的 Organization 與 Center（旗下的子紀錄）。 | MUST | V1 | 客戶的計費與租戶單位一律是 Organization，不是 Center；一個 Organization 可以只設一個 Center，也可以設多個 Center，不論數量多少都算同一個客戶。不同 Organization 之間的資料必須完全隔離。 | §8.1、§8.5 |
| F-03 | 系統 MUST 在 Organization 訂閱層級管理 `Active`、`Payment Issue`、`Grace Period`、`Read-only`、`Suspended` 與 `Unsubscribed` 六種生命週期狀態，且每次轉換 MUST 提供生效時間、受影響 Center 數量與資料影響。`Active` 為訂閱有效且付款正常時的預設狀態，Organization 底下所有 Center MUST 可完整操作。收款失敗當下立即進入 `Payment Issue`（第 1～7 天），服務仍完整可用並顯示付款異常警示與重試入口；第 8～30 天進入 `Grace Period`，服務仍完整可用，系統 MUST 於第 9、20 天發送催繳通知；第 31～89 天仍未解決者進入 `Read-only`，僅 Account Owner 可登入查看、下載與匯出既有資料，MUST NOT 新增或修改兒童、家庭、員工及營運資料，Account Owner 以外的子帳號 MUST 無法登入，畫面 MUST 顯著顯示唯讀原因與距永久刪除的剩餘天數並於刪除日前第 30、14、7、1 天發送通知，客戶完成付款並重新驗證 Organization 所有權後 MUST 立即恢復 `Active`；第 90 天仍未解決者執行永久刪除，執行前 MUST 完成法規、合約與 legal hold 檢查。`Suspended` 為 Super Admin 得不受付款狀態限制、隨時手動觸發的獨立風險控管狀態，進入後 Account Owner MUST 仍可登入，但畫面 MUST NOT 顯示任何業務功能，僅顯示帳號異常說明與聯絡 Support 的引導文案，MUST 保留 Super Admin 可見帳戶存在並執行 Restore 的能力，MUST NOT 因天數屆滿而自動變化。`Unsubscribed` 為客戶取消訂閱、目前已付款期結束生效時的狀態，MUST 直接銜接 `Read-only` 的資料存取規則與刪除倒數，MUST NOT 先經過 `Payment Issue`／`Grace Period`。 | MUST | V1 | 刪除排程機制（通知節奏、觸發者、撤銷紀錄）為附加於 `Read-only`／`Suspended` 之上的屬性，MUST NOT 列為第七種平行狀態，定義見 F-43；F-41 的 60 天回溯更正門檻與本條共同構成 Organization 資料生命週期政策。 | §8.2、§8.4、§10.1 |
| F-04 | Platform MUST 對 Organization Subscription 分別驗證 View、Manage 與 Payment 類型的 customer permission；既有 Center 的家庭帳務權限 MUST NOT 自動取得 SaaS payment、方案異動或取消權限。 | MUST | V1 | Customer permission UI 與正式 key 由 Consumer PRD 定義。 | §10.1、Consumer PRD §8 |
| F-05 | Binnii Admin Console MUST 提供 Super Admin、Finance 與 Support 三種內部角色。Super Admin MUST 擁有平台管理、營運監控以及 Finance 與 Support 的全部業務權限；Finance 與 Support MUST 僅取得完成各自職務所需的最小權限。 | MUST | V1 | Finance 與 Support 不得執行 Super Admin 專屬的角色管理、停權、恢復、取消覆寫或刪除排程；Support 不得執行 refund 或 billing adjustment。 | §1、§8.1～§8.10 |
| F-06 | Platform MUST 對被授權的 Customer Subscription 提供 Organization subscription status、plan、payment status、跨 Center 去重後的 Active Children 摘要、各 Center 用量明細、next invoice date、estimate 與資料更新時間。 | MUST | V1 | 未完成資料必須帶明確狀態，不得偽造最終金額。 | §10.1、Consumer PRD §8 |
| F-07 | 每個 Organization MUST 只擁有一套 Go、Plus 或 Pro；旗下所有 Center 共用方案與額度。三方案的月繳基本費與內含 Active Children 額度，依 Organization 所屬 Market 的設定（見 F-52）決定，MUST NOT 依 Center 數量重複收取基本費。 | MUST | V1 | 加拿大 Market 現行設定為 CAD $10／10 名、CAD $30／40 名、CAD $70／100 名；價格未含適用稅額與第三方用量費。 | §10.1、Consumer PRD §8 |
| F-08 | Go、Plus 與 Pro MUST 提供相同產品功能；方案差異 MUST 僅為內含 Active Children 額度、超額單價與適用規模，MUST NOT 以方案名稱鎖住安全功能。 | MUST | V1 | 安全與核心產品功能均屬三方案共同權益。 | §10.1、Consumer PRD §8 |
| F-09 | 平台 MUST 依 Active Child 定義取得 Organization 每日唯一人數：兒童在旗下任一 Center 的 Enrollment 為 Active、日期已生效且終止日未生效時納入；Applicant、Waitlist、尚未生效的 Upcoming／Scheduled 及已終止狀態 MUST NOT 計入。同一兒童跨 Classroom 或 Center 時 MUST 以 Organization canonical child ID 去重，只計一名。 | MUST | V1 | 使用來源資料的 effective date，不使用結算日快照取代。 | §10.1 |
| F-10 | 每個 Organization 每日 MUST 產生可稽核的 usage snapshot，至少包含日期、方案、內含額度、跨 Center 去重後的 Active Children 數、構成名單的非顯示型 canonical child ID、各 Center 用量明細、超額人數、使用單價、版本與調整理由。 | MUST | V1 | Center 明細用於解釋用量來源，不得直接相加取代 Organization 去重總數；平台 MUST NOT 複製兒童姓名、健康或家庭資料。 | §8.2、§10.1 |
| F-11 | 當任一應計量 Center 的每日來源資料缺漏、驗證失敗或尚未完成時，Organization 當日 usage snapshot MUST 標示 `Pending` 或 `Error`，MUST NOT 將未知值靜默當成 0 名或排除該 Center 後產生最終金額。 | MUST | V1 | 帳單結算前必須有可追蹤的補齊或人工處理結果。 | §8.2、§8.5、§10.1 |
| F-12 | Platform MUST 對 Customer Subscription 提供本期 Organization 每日去重總用量、各 Center 用量明細、內含額度、預估超額 child-months、預估帳單、資料時間與完成狀態；未完成日期 MUST NOT 呈現為最終金額。 | MUST | V1 | 預估不等於已開立 invoice。 | §10.1、Consumer PRD §8 |
| F-13 | Organization 月繳方案 MUST 在週期開始預收一筆基本月費，並於週期結束後依 Organization 每日去重超額計量、依所屬 Market 設定的超額單價將超額費列入下一張帳單；金額只能在整張 invoice 小計完成後四捨五入至該 Market 幣別的最小單位（如 CAD 為 $0.01）。 | MUST | V1 | 加拿大 Market 現行 Go／Plus／Pro 月繳超額單價分別為 CAD $1／$0.80／$0.60；不得每天先四捨五入。試用期間依 F-48 豁免本條收費。 | §10.1、Consumer PRD §8 |
| F-14 | Organization 年繳方案 MUST 在年期開始預收一筆基本年費，年費與月繳超額單價 MUST 依所屬 Market 設定的年繳折扣率（見 F-52，預設 0.8）換算，Organization 超額費仍按月結算。 | MUST | V1 | 加拿大 Market 現行年繳基本費為 CAD $96／$288／$672，超額單價 CAD $0.80／$0.64／$0.48。試用期間依 F-48 豁免本條收費。 | §10.1、Consumer PRD §8 |
| F-15 | Go → Plus → Pro 升級 MUST 可立即生效，並依 Organization billing timezone 與剩餘週期計算基本費差額；生效日前後的額度與單價 MUST 分段回傳。 | MUST | V1 | 同一天不得混用兩個方案額度。 | §10.1、Consumer PRD §8 |
| F-16 | Pro → Plus → Go 降級 MUST 於下一個月繳週期生效；年繳方案 MUST 於下一個年期生效，preview MUST 提供未來額度與估算費用。 | MUST | V1 | Customer confirmation 由 Consumer PRD 定義。 | §10.1、Consumer PRD §8 |
| F-17 | 取消 Organization 月繳或年繳 MUST 於目前已付款期結束生效並進入 F-03 定義的 `Unsubscribed` 狀態；Platform MUST 提供最後服務日、受影響 Center 數量、資料存取階段與可撤銷取消期限，並對旗下所有 Center 同時生效。 | MUST | V1 | 月繳當期基本費不按未使用天數退款；年繳基本費除適用法律或服務條款另有規定外不退款。 | §10.1、Consumer PRD §8 |
| F-18 | 任一 Center 的回溯 Enrollment 更正、終止日調整或 Organization 內跨 Center 重複兒童合併造成的差額 MUST 重新計算；已開立 invoice MUST NOT 被直接改寫，差額應以後續 debit adjustment 或 credit 呈現。 | MUST | V1 | 保留原 Organization snapshot、重算版本與理由。 | §8.2、§10.1 |
| F-19 | Platform MUST 以支付服務商的安全 reference 管理 Organization billing profile 與 payment method，並只向 Customer Subscription／Admin Console 提供完成工作所需的遮罩摘要；MUST NOT 輸出完整付款憑證。V1 MUST 使用 Stripe Billing 與 Stripe Payments 作為支付服務商，支援 Visa、Mastercard、American Express 與支援 recurring online payment 的 debit card；MUST NOT 支援 PayPal、支票、電匯、Buy Now Pay Later 或 Canadian Pre-Authorized Debit／ACSS Debit。 | MUST | V1 | V1 另支援 Interac e-Transfer 作為人工對帳付款方式，其到帳確認、invoice 狀態與 audit 要求由 F-44 定義，不屬於本條的支付服務商自動化流程。 | §8.2、§10.1 |
| F-20 | 系統 MUST 為每個 Organization 的每個 billing period 提供一張 Binnii invoice，明細分列基本費、Organization 每日超額換算、adjustment／credit、第三方用量費、稅額與各 Center 用量摘要；Center 摘要 MUST NOT 形成獨立 invoice 或基本費。 | MUST | V1 | Invoice 與 Center 對家庭開立的帳單不同。 | §8.2、§10.1 |
| F-21 | Organization 收款失敗時，系統 MUST 標示付款異常、提供可理解的處理期限與允許的重試動作，並依生命週期政策讓旗下所有 Center 同時進入相同服務限制；MUST NOT 因單次失敗立即刪除客戶資料。 | MUST | V1 | 限制階段依 F-03 的正式政策。 | §8.2、§8.4、§10.1 |
| F-22 | 系統 MUST 對方案變更、取消、付款失敗、付款成功、invoice 開立、credit／refund 與停權／恢復發送可追蹤通知。 | MUST | V1 | 收件對象與通知事件必須可稽核。 | §8.2、§8.4 |
| F-23 | Platform 對任何升級、降級或取消 request MUST 提供 Organization、受影響 Center 數量、目前與目標方案、生效日、內含額度、超額單價、費用影響、資料影響與 preview version，並在提交時重驗。 | MUST | V1 | Customer confirmation UI 由 Consumer PRD 定義。 | §10.1、Consumer PRD §8 |
| F-24 | Binnii Admin Console MUST 可依 Organization name、Center name、Center ID、Account Owner email、Organization Contact Phone、Center phone、invoice number、完整 customer-facing correlation reference、方案、生命週期狀態與付款狀態搜尋或篩選租戶。`Organization Contact Phone` MUST 取自目前 Account Owner 的 phone；電話搜尋 MUST 同時涵蓋 Account Owner phone 與旗下 Center phone，並以正規化後的號碼比對，使空白、括號、連字號等顯示格式不影響搜尋。reference 搜尋 MUST 移除輸入頭尾空白、轉成 canonical case 後採完整精確比對，MUST NOT 使用 partial 或 fuzzy match。唯一命中時 MUST 直接開啟所屬 Organization 的 Tenant Detail → `Audit`、套用 reference filter 並高亮對應事件；查無或目前角色無權查看時顯示 `No event found for this reference.`，不得顯示空白結果或洩漏 reference 是否屬於其他 scope；同一 reference 對應多筆不同業務事件或租戶時 MUST 顯示資料完整性錯誤並停止導向。 | MUST | V1 | reference 搜尋結果不得顯示兒童姓名、照護內容、raw payload、stack trace 或內部敏感原因；所有查詢仍受 Organization scope 與內部角色權限限制，且 lookup 本身 MUST 計入 audit event。 | §8.1、§8.2 |
| F-25 | Tenant Detail MUST 彙整 Organization、旗下 Center、帳戶角色、Organization Subscription、每日去重總用量、各 Center 用量明細、invoice、payment、adjustment、support note、通知與 audit event。Overview MUST 直接顯示 `Organization Contact Phone` 卡片及 `Detail` 按鈕；Organization Detail Modal MUST 顯示完整 Organization profile、Account Owner contact 與 Platform-owned context。Centers 清單每列 MUST 提供 `Detail` 按鈕；Center Detail Modal MUST 顯示完整 Center Profile 與 Platform-owned context。兩個 Modal 均為唯讀，MUST NOT 成為修改客戶資料或 Center 營運設定的入口。 | MUST | V1 | `Customers` 在 Admin Console sidebar 展開目前 Organization 的 Tenant Detail 子頁面；各內部角色只看到其權限允許的子項目與動作。欄位契約見 §8.2。 | §8.2 |
| F-26 | Support、Finance 與 Super Admin MUST 可新增 support note，記錄時間、操作者、客戶問題（Customer Issue）、採取動作（Action Taken）與外部 case reference；MUST 可編輯既有 note，但每次編輯 MUST 保留完整版本歷史（原文、修改後內容、操作者、時間），MUST NOT 覆蓋或刪除任何既有版本。 | MUST | V1 | 版本歷史呈現方式見 §8.10；support note 的新增／檢視／編輯屬三個內部角色共用的低風險文件記錄行為，依 NFR-02 例外不受一般角色業務分離限制。 | §8.2、§8.10 |
| F-27 | Suspend、Restore、Cancel Override 與 Deletion Schedule 等高風險操作 MUST 僅限 Super Admin，並在提交前顯示對象、影響範圍、生效時間，要求填寫操作原因及完成明確確認。 | MUST | V1 | Finance 與 Support 不得執行。 | §8.4 |
| F-28 | 系統 MUST 記錄登入、角色變更、方案異動、付款事件、adjustment、匯出、停權、恢復、高風險資料存取與 correlation reference lookup 的 audit event，且一般管理介面 MUST NOT 提供編輯或刪除既有事件的能力。凡 customer UI 顯示的 correlation reference，Platform MUST 保存其 immutable reference、source module、業務事件 identity、Organization、Center（如適用）、event type、result、event time 與 customer-safe reason，並與內部診斷資料分離。Audit Log MUST 可依完整 reference、租戶、事件、操作者與日期篩選；reference 命中時 MUST 顯示上述 customer-safe 欄位，不得顯示 raw payload、stack trace、payment credential 或兒童照護內容。 | MUST | V1 | customer-facing reference 必須全域唯一、opaque、不可變且可索引；相同業務事件安全重送時沿用同一 reference，不得產生第二個 mapping。reference prefix、長度與字元格式由 RD 規劃，但不得改變上述產品契約。 | §8.1、§8.2、§8.4、§10.1 |
| F-29 | V1 的 Binnii 內部角色 MUST NOT impersonate 客戶，亦 MUST NOT 從 SaaS Admin Console 查看兒童姓名、健康、照片、家庭聯絡資訊或照護紀錄。 | MUST NOT | V1 | V1 支援限於平台 metadata 與經去識別化的計費證據。 | §8.2 |
| F-30 | Super Admin MUST 可查看 usage ingestion、invoice generation、payment event、notification 與 lifecycle job 的健康狀態及失敗項目。 | MUST | V1 | 顯示技術識別碼與錯誤摘要，不顯示兒童照護內容。 | §8.5 |
| F-31 | Platform MUST 對具備 Customer Subscription 匯出權限的使用者提供其 Organization Subscription、每日去重總用量、各 Center 用量明細與 Binnii invoice 匯出；每次 MUST 重新驗證 Organization scope 並留下 audit event。 | MUST | V1 | 客戶匯出 UI 由 Consumer PRD 定義；匯出不得包含 canonical child ID。 | §10.1、Consumer PRD §8 |
| F-32 | 所有平台結果、Admin Console、通知與匯出 MUST 將 SaaS 計費標示為 `Binnii Subscription Billing`，將 Center 對家庭的帳務標示為 `Family Billing & Subsidies`，MUST NOT 共用會造成誤解的 `Billing` 單獨名稱。 | MUST | V1 | 這是跨系統命名邊界。 | §8.1～§8.10、§10 |
| F-33 | Center Management System MUST 可取得 Center 所屬 Organization 的帳戶生命週期、共用方案、生效日與服務限制狀態；同一 Organization 旗下所有 Center MUST 取得一致的 Subscription lifecycle 結果，且 MUST NOT 直接讀取 SaaS 平台內部帳務資料。 | MUST | V1 | 供產品顯示付款警示、唯讀或停用狀態。 | §10.1 |
| F-34 | 人工 financial adjustment MUST 限定 Finance 或 Super Admin，要求 adjustment type、金額、幣別、原因、關聯 Organization invoice 與確認；負金額、超過 invoice 餘額或跨幣別情境 MUST 阻擋並提示。 | MUST | V1 | Refund 與 credit 必須分開標示。 | §8.3 |
| F-35 | 計量更正 MUST 由各 Center 的來源資料與 Organization 重算流程驅動；SaaS Admin Console MUST NOT 直接改寫某日 Organization Active Children 數或各 Center 用量明細。 | MUST NOT | V1 | 財務補償使用 F-34，不篡改 usage evidence。 | §8.2、§10.2 |
| F-36 | 相同 Center usage source event、Organization usage snapshot、payment event 或 invoice generation request 被重送時，系統 MUST 識別為同一業務事件，MUST NOT 建立重複用量、重複 invoice 或重複 charge。 | MUST | V1 | 屬計費正確性底線。 | §10.1 |
| F-37 | Organization 的 Suspend、Cancel、payment failure 或一般後台操作 MUST NOT 永久刪除 Organization、旗下 Center 或托育營運資料；永久刪除只能依已核准的 retention policy 與獨立確認流程執行。 | MUST NOT | V1 | 資料可用性依 F-03 逐階段變化。 | §8.4 |
| F-38 | Binnii Admin Console 所有清單與明細頁 MUST 提供 loading、empty、error 與 permission denied 狀態，並以清楚且可行動的文案告知下一步。 | MUST | V1 | 狀態不得只靠顏色表達。 | §8.1～§8.10 |
| F-39 | 短期請假、病假或度假只要 Enrollment 仍為 Active 就 MUST 計費；part-time 兒童 MUST 每人計為 1；同一兒童在 Organization 旗下跨 Classroom 或 Center 有 Active Enrollment 時 MUST 去重，只計一名。 | MUST | V1 | 避免依每週天數折算或重複出現位置造成不可解釋的帳單。 | §8.2、§10.1 |
| F-40 | Organization 在訂閱有效但跨 Center 去重後的 Active Children 為 0 時 MUST 仍收取一筆方案基本月費或基本年費，且 MUST NOT 產生超額費；任何 Center 的 0 人狀態 MUST NOT 產生額外基本費。 | MUST | V1 | 取消生效前皆適用；試用期間依 F-48 豁免本條收費。 | §8.2、§10.1 |
| F-41 | 一般客戶 MUST 可回溯更正最近 60 天的來源資料；超過 60 天的更正 MUST 由具 Billing 權限者審核並記錄原因。 | MUST | V1 | 60 天為現行規劃值，須與 F-03 的正式營運政策一併核准。 | §8.2、§10.1 |
| F-42 | Platform MUST 在 customer 首次取得 Center application access 前，依已驗證 onboarding 資料建立 Organization 與第一間 Center 並完成關聯。Organization 沒有任何 Center record 時 MUST 回傳 customer-safe onboarding exception 與可追蹤 reference，不得授予 customer-facing Add Center eligibility。只有預先建置的第一間 Center setup 可在 Organization 同時沒有有效 Subscription 與 active trial entitlement 時觸發初始 Subscription handoff；customer-created 後續 Center 只在 Organization 具有效服務 entitlement 時建立，並免費繼承相同 Go／Plus／Pro 或 trial entitlement、billing cycle 與 lifecycle，不得建立另一筆 subscription、payment profile、invoice 或 location fee。 | MUST | V1 | Customer-facing Center workflow 由《Binnii Center Management PRD》定義。 | §10.1、Center Management PRD §8.4 |
| F-43 | 系統 MUST 提供獨立於生命週期狀態的刪除排程機制：Organization 進入 `Read-only` 滿 59 天（即第 90 天）、或 Super Admin 對 `Suspended`／`Read-only` 帳戶核准終止時，MUST 記錄 `deletion_scheduled_at`、`deletion_scheduled_reason`（`read_only_timeout` 自動觸發／`super_admin_manual` 人工觸發）與 `deletion_scheduled_by`；MUST 在刪除日前 30、14、7、1 天發送通知；客戶於期限前完成付款恢復，或 Super Admin 撤銷排程時，系統 MUST 清空排程並記錄 `deletion_cancelled_at` 與 `deletion_cancelled_by`，MUST NOT 直接刪除任何資料。 | MUST | V1 | 此機制附加於 `Read-only`／`Suspended` 狀態之上，MUST NOT 作為 F-03 的第七種平行狀態；排程刪除與撤銷排程 MUST 計入 F-28 audit event。 | §8.2、§8.5、§10.1 |
| F-44 | 系統 MUST 支援 Interac e-Transfer 作為 V1 付款方式之一，採人工對帳流程：客戶將款項匯至 Binnii 指定收款 email；到帳確認前，對應 invoice MUST 維持「等待付款」狀態，MUST NOT 顯示為已付款；Finance 或 Super Admin MUST 可在 Admin Console 依 Organization、金額與匯款參考資訊手動比對並將 invoice 標記為已收款，系統 MUST 記錄操作者、比對依據與時間並計入 audit event。 | MUST | V1 | 此流程與 F-19 的 Stripe 自動化付款流程分開處理，不透過 Payment Service 依賴；對應操作畫面見 §8.6。 | §8.2、§8.6、§10.1 |
| F-45 | 系統 MUST 提供平台層級的免費試用開關，Super Admin MUST 可開啟或關閉；開啟時 MUST 可設定全局預設試用天數，預設值為 14 天。關閉免費試用功能 MUST NOT 影響已在試用中的既有 Organization，僅停用未來新建立 Organization 的試用資格。 | MUST | V1 | 全局設定畫面見 §8.7。 | §8.7 |
| F-46 | 免費試用功能開啟時，新建立 Organization MUST 自動套用當時的全局預設試用天數；試用中的 Organization MUST 在 Tenant Detail 顯示剩餘天數與到期日，Super Admin MUST 可為其增加天數以延長試用，新增天數 MUST 疊加至現有到期日，MUST NOT 縮短既有到期日；此調整 MUST NOT 影響其他 Organization 或全局預設值。非試用中的 Organization MUST NOT 顯示延長操作。 | MUST | V1 | 延長入口見 §8.2 Tenant Detail 的 `Subscription` 頁面。 | §8.2、§8.7 |
| F-47 | 免費試用期間 MUST NOT 強制客戶選擇 Go／Plus／Pro 方案；Super Admin MUST 可在全局設定中指定試用期間套用的方案權限（決定試用期間的 Active Children 額度），預設為 Pro。 | MUST | V1 | 依 F-08，三方案功能相同，試用方案權限只影響額度上限，不影響功能範圍。 | §8.7 |
| F-48 | 試用期間 Organization MUST 維持 `Active` 狀態、比照 F-47 指定的方案權限完整可用，MUST NOT 收取基本費或超額費。 | MUST NOT | V1 | 本條試用期免收費規則優先於 F-13、F-14、F-40 的一般收費規則。 | §10.1 |
| F-49 | 試用天數屆滿時，若 Organization 已完成有效付款方式驗證，系統 MUST 自動轉為正式付費訂閱並於下一個計費週期開始計費；若尚未完成付款方式驗證，MUST 直接進入 `Read-only` 狀態，比照 `Unsubscribed` 的轉換邏輯，不經過 `Payment Issue`／`Grace Period`。 | MUST | V1 | 與 F-03 的 `Read-only` 規則共用同一套資料存取與刪除倒數邏輯。 | §8.2、§10.1 |
| F-50 | 系統 MUST 支援將 Organization 標記為 `is_test_account`，供 PM／QA 體驗介面使用；標記為測試帳號的 Organization MUST NOT 計入任何計費流程，MUST NOT 出現在 Customers 正式列表與其統計數據中，但 Super Admin MUST 可透過獨立篩選條件查詢與管理既有測試帳號。 | MUST | V1 | 測試帳號僅供內部使用，非客戶免費試用；兩者為不同機制，不得混用。 | §8.1 |
| F-51 | 系統 MUST 支援多個 Market（國家／市場）設定，每個 Market 使用穩定的內部 ID 識別，不依賴其 UI 顯示名稱；Super Admin MUST 可透過 `Add Market Price` 建立新 Market 設定。 | MUST | V1 | Market ID 為永久識別碼，MUST NOT 因顯示名稱變更而改變。 | §8.8、§8.9 |
| F-52 | 每個 Market MUST 可獨立設定：Go／Plus／Pro 三個方案的 UI 顯示名稱（各 Market 可不同，新建 Market 預設 Go／Plus／Pro）、幣別（新建 Market 預設 USD）、三方案月費（預設 $10／$30／$70）、年繳折扣率（預設 0.8）、三方案 Active Children 額度（預設 10／40／100 名）。方案的功能範圍依 F-08 在所有 Market 一致，僅本條數值可依 Market 調整。 | MUST | V1 | 三個方案的識別依 F-51 的穩定 ID，不依賴顯示名稱；例如新加坡 Market 可將顯示名稱改為 Standard／Pro／Business，底層仍對應同一組方案 ID。 | §8.9 |
| F-53 | Super Admin MUST 可個別開啟或關閉任一 Market；關閉 MUST NOT 影響已綁定該 Market 的既有 Organization 正常運作，僅停用該 Market 供未來新建立 Organization 選用的資格。當該 Market 是目前的 fallback Market 時，系統 MUST 阻擋關閉，直到 Super Admin 先指定另一個符合 F-58 資格的 fallback Market。 | MUST | V1 | 與 F-45 免費試用開關的既有生效邏輯一致；不得讓平台處於沒有可用 fallback Market 的狀態。 | §8.8、§8.9 |
| F-54 | 每個 Market MUST 可設定稅率，新建 Market 稅率預設為 0%；Market 啟用前，系統 MUST 要求 Super Admin 明確確認稅率已正確設定，MUST NOT 允許稅率維持預設 0% 且未經確認即啟用。 | MUST | V1 | 稅率是否符合當地法規仍須 PM／法務依 NFR-10 另行確認，AI 或 RD MUST NOT 自行判斷稅率正確性。 | §8.9 |
| F-55 | 每個 Market MUST 可設定專屬 Invoice 範本，範本 MUST 支援該市場稅務轄區要求的功能性欄位（例如買方稅務登記號碼、當地稅種名稱與稅額拆分方式），不僅限於視覺樣式客製。 | MUST | V1 | 功能性欄位需求依實際上線市場的稅務規定另行盤點，V1 僅定義範本 MUST 可承載此類欄位的能力。 | §8.9 |
| F-56 | 每個 Market MUST 可設定該市場開放的付款方式，選項僅限 Stripe 全球支援的付款方式；V1 MUST NOT 提供跨支付服務商選擇，全平台統一使用 F-19 定義的 Stripe。Interac e-Transfer（F-44）MUST 僅能勾選給明確需要的 Market（如加拿大），MUST NOT 作為其他 Market 的預設可用選項。 | MUST NOT | V1 | 支付服務商登記表非 V1 範圍；未來如需多供應商，須另立需求。 | §8.9 |
| F-57 | 每個 Organization 建立時 MUST 指定唯一的 Market；MUST NOT 於建立後更換 Market，亦 MUST NOT 允許同一 Organization 底下的 Center 使用不同 Market 的計費設定。當新 Organization 的 Market 由 F-58 fallback resolution 決定時，Platform MUST 在第一次 Subscription preview、異動或付款前完成綁定。同一實體客戶如需跨國經營，MUST 個別建立多個獨立 Organization，各自完全隔離、各自一張 Invoice。 | MUST | V1 | 呼應 F-02 的租戶隔離原則；跨國視為多個獨立客戶關係，不建立 Organization 之間的特殊關聯。 | §8.1、§8.2、§10.1 |
| F-58 | Platform MUST 維持且只能維持一個 fallback Market，供尚未綁定 Organization、且依序無法由既有 Organization、已驗證 pre-provisioned first Center handoff 或有效 Location／landing CTA hint 解析 Market 的 customer plan routing 使用。Super Admin 只能將已啟用、稅率已確認，且方案定價、Invoice 範本與付款方式契約完整的 Market 設為 fallback；目前 fallback MUST NOT 可被儲存為不符合上述資格，除非同一交易已指定另一個合格 Market 接替。指定新 fallback 時 MUST 以單一交易原子性取代原值，MUST NOT 先清空或產生兩個 fallback。fallback resolution MUST 回傳 stable Market ID、完整 Market contract、contract version 與 `market_resolution=fallback`；新 Organization MUST 依 F-57 在任何 Subscription preview、異動或付款前綁定該 Market。變更 fallback MUST NOT 遷移或覆寫任何既有 Organization Market，既有 Organization 的 Market 遺失或損壞時 MUST 回傳資料完整性錯誤，不得以 fallback 補位。 | MUST | V1 | 初始平台設定完成前 MUST 指定一個符合資格的 fallback Market，customer-facing plan routing 才可啟用；不使用 IP geolocation。 | §8.8、§8.9、§10.1 |

---

## 4. 非功能需求（Non-Functional Requirements）

| # | 類別 | 需求描述 |
|---|------|---------|
| NFR-01 | 租戶隔離 | 每次讀取、匯出、通知與異動 MUST 先確認 Organization／Center scope；跨租戶資料混入任何 UI、匯出、invoice 或通知均屬阻塞級安全事件。 |
| NFR-02 | 權限 | Customer Subscription 與 Binnii 內部角色 MUST 使用分離的權限模型；Finance 僅可執行財務業務，Support 僅可執行支援業務，Super Admin 可涵蓋兩者並獨占內部角色管理、停權、恢復、取消覆寫與 deletion scheduling。support note 的新增、檢視與編輯（F-26）為三個內部角色共用的低風險文件記錄行為，MUST NOT 受本條業務分離限制排除。 |
| NFR-03 | 身分安全 | Super Admin、Finance、Support 與具 customer Subscription 高風險權限者 MUST 使用個人帳號及 MFA，MUST NOT 共用登入帳號；安全功能 MUST NOT 因 Go／Plus／Pro 方案而被停用。 |
| NFR-04 | 敏感資料最小化 | SaaS control plane MUST 只保存完成租戶、訂閱、計量、計費與稽核所需資料；usage evidence 僅可使用非顯示型唯一識別，MUST NOT 複製兒童檔案內容。 |
| NFR-05 | 傳輸與儲存安全 | 身分、付款、invoice、usage evidence 與 audit data 在傳輸及儲存時 MUST 受到業界標準保護；完整 payment credential MUST 由核准的支付服務管理，Binnii UI 不得顯示或記錄。 |
| NFR-06 | 計費一致性 | usage、invoice 與 payment 的重送、逾時或部分失敗 MUST 可安全重試；任何已向客戶確認成功的財務結果 MUST 可在故障後恢復與對帳。 |
| NFR-07 | 可觀測性 | 計量、invoice、payment、notification、lifecycle、Center Management 與 Customer Subscription 結果 MUST 有可搜尋的成功／失敗狀態、時間、租戶識別、錯誤分類與 customer-facing correlation reference。reference MUST 全域唯一、opaque、不可變、可索引且不含 PII；customer UI 顯示前 MUST 已建立可供 Admin Console 依原碼反查的 durable mapping。log 與反查結果 MUST 排除 payment credential、raw payload、stack trace 與兒童照護內容。 |
| NFR-08 | 效能 | 租戶、invoice、usage 與 audit 清單 MUST 分頁並支援條件查詢；畫面不得一次載入所有租戶或全期 usage。超過一秒的背景請求 MUST 顯示 loading state。 |
| NFR-09 | 可用性 | 上游暫時不可用時，平台 MUST 保留既有已確認狀態、標示資料時間與故障範圍，MUST NOT 以空值覆蓋最後可信資料。 |
| NFR-10 | 合規 | 產品 MUST 支援適用的加拿大隱私、稅務、帳務留存與資料主體請求流程；正式上線前由 PM／法務確認適用法規與保留期限，AI 或 RD MUST NOT 自行提供法律結論。 |
| NFR-11 | 無障礙 | Binnii Admin Console MUST 符合 WCAG 2.1 AA，支援鍵盤操作、可見 focus、語意化標籤及不依賴顏色的狀態傳達；Customer Subscription 依其 PRD 負責 customer-facing 無障礙。 |
| NFR-12 | 在地化 | Subscription 的 billing period、每日 snapshot 邊界、生效日與金額 MUST 使用 Organization billing timezone 及其所屬 Market 幣別（見 F-52、F-57）；各 Center 來源事件保留其 Center timezone，數字與稅額格式 MUST 與 live code 保持一致。 |
| NFR-13 | 品牌與視覺一致性 | UI MUST 遵循 Binnii Brand Visual Brief，並以 `04_Webroot/app.binnii.com` 的 `app → sidebar → main → wrap` shell、sidebar 狀態與共用元件樣式作為視覺實作基準；Binnii 為主品牌，`Binnii by Haody` 僅作次要背書；Coral 與 Gold MUST NOT 作為小字文字色。Admin Console MUST 保留本 PRD 定義的內部導覽與角色權限，不得呈現 Current Center 或 Center 營運選單。 |
| NFR-14 | 響應式 | Binnii Admin Console 以內部桌面作業為優先，清單、filter、modal 與 Tenant Detail MUST 在核准的內部支援寬度保持可操作；不得以隱藏關鍵欄位解決版面問題。 |

---

## 5. 驗收條件（Acceptance Criteria）

- [ ] **AC-F-01**：當客戶使用 Center Management System 的獨立 `Subscription` 時，系統 MUST NOT 顯示 Binnii 內部 Admin Console 導覽或內部操作，且 `Subscription` MUST NOT 併入 Family Billing & Subsidies。
- [ ] **AC-F-02**：當兩個 Organization 各有 Center 時，任一方使用 UI、搜尋、匯出或直接連結均 MUST NOT 取得另一方資料。
- [ ] **AC-F-03**：當 Organization Subscription 狀態改變時，Tenant Detail 與 Customer Subscription contract MUST 提供狀態、生效時間、下一階段、受影響 Center 數量及資料影響，旗下所有 Center MUST 取得一致 lifecycle 結果。
- [ ] **AC-F-04**：當 customer user 沒有 Payment 或 Manage permission 時，Platform MUST 阻擋更新付款、取消或方案異動，即使其具有 Center 家庭帳務權限。
- [ ] **AC-F-05**：當 Support 嘗試執行 refund、adjustment 或租戶生命週期高風險操作時，系統 MUST 阻擋；當 Finance 嘗試管理內部角色或執行租戶生命週期高風險操作時亦 MUST 阻擋；Super Admin MUST 可在通過對應安全確認後執行上述全部業務操作。
- [ ] **AC-F-06**：當 Customer Subscription 請求被授權 Organization summary 時，Platform MUST 提供共用方案、去重總用量、各 Center 用量明細、付款狀態、預估帳單與資料時間；資料缺漏時 MUST 提供明確狀態而非空值成功。
- [ ] **AC-F-07**：當建立或變更 Organization Subscription 時，可選方案只能是 Go、Plus、Pro；一個 Organization MUST 只有一套方案與一筆基本費，旗下 Center MUST 共用額度。
- [ ] **AC-F-08**：當不同 Organization 分別使用 Go、Plus 或 Pro 時，產品功能清單 MUST 相同，安全功能 MUST 在三方案均可用。
- [ ] **AC-F-09**：當 Enrollment 為 Active 且生效日期涵蓋計量日時，兒童 MUST 計入；Applicant、Waitlist、尚未生效的 Upcoming／Scheduled 或已終止狀態 MUST NOT 計入；同一兒童跨 Center MUST 只計一次。
- [ ] **AC-F-10**：當檢視任一 Organization usage snapshot 時，系統 MUST 顯示去重總數、各 Center 明細、計量證據與版本，但 MUST NOT 顯示兒童姓名、健康、家庭或照護內容。
- [ ] **AC-F-11**：當任一應計量 Center 的某日來源資料缺漏或錯誤時，Organization 該日 snapshot MUST 顯示 `Pending` 或 `Error`，預估帳單 MUST NOT 將該日當成 0 名或排除該 Center 後結算。
- [ ] **AC-F-12**：當本期仍有未完成 Organization snapshot 時，預估帳單 MUST 顯示非最終提示、未完成日期數及各 Center 明細狀態。
- [ ] **AC-F-13**：當 Organization 月繳週期結束時，一筆基本費與依每日去重總用量換算的超額費 MUST 分列、使用正確方案單價，並只在 invoice 小計完成後四捨五入至 CAD $0.01。
- [ ] **AC-F-14**：當 Organization 年繳方案超額時，基本年費不得按 Center 重複收取，當月超額費 MUST 使用年繳折扣後單價。
- [ ] **AC-F-15**：當 Organization 升級立即生效時，確認與 invoice preview MUST 依 Organization billing timezone 分段顯示生效日前後方案及按剩餘週期計算的基本費差額。
- [ ] **AC-F-16**：當客戶要求降級時，系統 MUST 顯示並排定下一個合格續訂日，MUST NOT 當日降低額度。
- [ ] **AC-F-17**：當客戶取消 Organization Subscription 時，系統 MUST 顯示最後服務日、受影響 Center 數量、資料存取階段及適用的不退款規則；取消 MUST 對所有 Center 同時生效並進入 `Unsubscribed` 狀態，且 MUST NOT 立即刪除營運資料。
- [ ] **AC-F-18**：當已開立 invoice 後因任一 Center 來源更正或跨 Center 重複兒童合併而重算，原 invoice MUST 保持不變，差額 MUST 出現在後續 debit adjustment 或 credit。
- [ ] **AC-F-19**：當 Customer Subscription 或 Admin Console 取得 payment method 時，Platform MUST 只輸出遮罩後摘要，MUST NOT 輸出完整付款憑證。
- [ ] **AC-F-20**：當客戶、Finance 或 Super Admin 開啟 Binnii invoice 時，每個 billing period MUST 只有一張 Organization invoice；明細 MUST 分列基本費、超額費、adjustment／credit、第三方用量費、稅額與各 Center 用量摘要，且 Center 摘要不得產生額外基本費。
- [ ] **AC-F-21**：當 Organization 單次收款失敗時，系統 MUST 顯示付款異常、重試入口與期限；依生命週期進入服務限制時，旗下所有 Center MUST 套用相同狀態，且 MUST NOT 立即永久刪除資料。
- [ ] **AC-F-22**：當方案、invoice、付款或高風險帳戶狀態變更時，系統 MUST 建立可追蹤通知紀錄並顯示收件對象與結果。
- [ ] **AC-F-23**：當 Customer Subscription 請求方案變更或取消 preview 時，Platform MUST 完整回傳 Organization、受影響 Center 數量、方案、生效日、額度、單價、費用、資料影響與 preview version，提交時 MUST 重驗。
- [ ] **AC-F-24**：當內部使用者輸入 Organization name、Center name、Center ID、Account Owner email、Account Owner phone 或 Center phone 時，搜尋結果 MUST 找到其角色可見的對應 Organization。相同電話以 `+1 (604) 555-0123`、`604-555-0123` 或不含分隔符號的數字輸入時 MUST 可比對。當 Support、Finance 或 Super Admin 貼上完整 customer-facing reference（例如 `CTR-7N4P8` 或 `SUB-5M7T9`）時，系統 MUST 以 canonical exact match 定位唯一事件，開啟對應 Organization 的 Tenant Detail → `Audit`、套用 reference filter 並高亮事件；查無或未授權時 MUST 顯示 `No event found for this reference.`，多筆衝突時 MUST 顯示資料完整性錯誤且不得導向任一租戶。結果 MUST NOT 跨越授權 scope，亦 MUST NOT 顯示兒童姓名、照護資料、raw payload 或 stack trace。
- [ ] **AC-F-25**：當 Super Admin、Finance 與 Support 開啟同一 Tenant Detail 時，Admin Console sidebar 的 `Customers` MUST 展開授權的 Tenant Detail 子項目，目前頁面 MUST 顯示 Indigo active state、Coral 左側 marker 與 `aria-current="page"`。Overview MUST 顯示 Organization Contact Phone；點擊 Overview 的 `Detail` MUST 開啟 Organization Detail Modal 並顯示 §8.2 定義的全部 Organization 欄位。Centers 清單每列 MUST 顯示 `Detail`；點擊後 MUST 開啟該列 Center 的 Detail Modal 並顯示 §8.2 定義的全部 Center Profile 欄位，且不得沿用前一列資料。兩個 Modal 均 MUST 支援 `Close`、關閉圖示、`Esc`、focus trap 與關閉後返回觸發按鈕。Finance 與 Support 的可見項目與動作 MUST 符合最小權限；Super Admin MUST 可執行兩者的全部業務操作及平台管理與營運監控操作。
- [ ] **AC-F-26**：當 Support、Finance 或 Super Admin 點擊某則既有 note 的 `Edit` 時，系統 MUST 開啟可編輯表單並將目前內容存入版本歷史；儲存後 MUST 新增一個標示為目前版本的紀錄，MUST NOT 刪除或覆寫任何舊版本；版本歷史 MUST 可查看每個版本的操作者與時間。
- [ ] **AC-F-27**：當 Finance 或 Support 嘗試高風險操作時，系統 MUST 阻擋；Super Admin 提交前 MUST 看見影響預覽、填寫操作原因並完成明確確認。
- [ ] **AC-F-28**：當登入、角色、方案、付款、匯出、高風險操作或 correlation lookup 發生時，Audit Log MUST 可查到操作者、時間、租戶、事件與結果，且一般 UI MUST NOT 提供修改或刪除。每個 customer-facing reference MUST 全域唯一、不可變且可索引；Audit 依完整 reference 命中後 MUST 顯示 source module、Organization、Center（如適用）、event type、result、event time 與 customer-safe reason。相同業務事件重送 MUST 沿用同一 reference；任何 reference collision MUST 被阻擋並回報資料完整性錯誤。
- [ ] **AC-F-29**：當 Binnii 內部使用者使用 V1 Admin Console 時，介面 MUST NOT 提供 impersonation，亦 MUST NOT 顯示兒童與家庭詳細資料。
- [ ] **AC-F-30**：當計量、invoice、payment、notification 或 lifecycle job 失敗時，Super Admin 的 System Health MUST 顯示類別、時間、租戶、狀態與可執行的下一步。
- [ ] **AC-F-31**：當 Customer Subscription 請求 usage 或 invoice 匯出時，Platform MUST 重新驗證 user、Organization scope 與 export permission，並留下 audit event；檔案可含各 Center 彙總，但不得包含 canonical child ID。
- [ ] **AC-F-32**：當 SaaS 計費與家庭帳務出現在同一產品導覽時，標籤 MUST 分別顯示 `Binnii Subscription Billing` 與 `Family Billing & Subsidies`。
- [ ] **AC-F-33**：當 Organization Subscription 處於付款警示、唯讀或停用狀態時，Center Management System MUST 讓旗下所有 Center 取得相同狀態與生效日，但 MUST NOT 取得內部 invoice 或 payment credential。
- [ ] **AC-F-34**：當 Finance 或 Super Admin 建立 adjustment 時，缺少 type、金額、幣別、原因、Organization invoice 或確認任一條件均 MUST 阻擋提交；成功後 MUST 顯示獨立紀錄。
- [ ] **AC-F-35**：當內部使用者查看 Organization 每日用量與各 Center 明細時，介面 MUST NOT 提供直接改寫 Active Children 數的欄位或按鈕。
- [ ] **AC-F-36**：當同一業務事件重送兩次時，usage、invoice 與 charge 數量 MUST 維持一次，並留下重送處理結果。
- [ ] **AC-F-37**：當帳戶 Suspend、Cancel、付款失敗或 `Read-only` 期滿時，Organization、Center 與營運資料 MUST 仍存在，直到核准的 retention policy 與獨立刪除流程完成。
- [ ] **AC-F-38**：當頁面載入中、無資料、請求失敗或權限不足時，畫面 MUST 顯示對應狀態與下一步，且狀態 MUST 可由鍵盤及螢幕閱讀器理解。
- [ ] **AC-F-39**：當 Active Enrollment 的兒童短期缺席或為 part-time 時，當日 MUST 計為 1；同一兒童在 Organization 旗下跨 Classroom 或 Center 時 MUST 只計一次。
- [ ] **AC-F-40**：當 Organization Subscription 有效且跨 Center 去重後的 Active Children 為 0 時，invoice MUST 只包含一筆方案基本費且超額費 MUST 為 0；任何 Center 不得另產生基本費。
- [ ] **AC-F-41**：當一般客戶回溯更正 60 天內資料時可提交；超過 60 天時 MUST 要求具 Billing 權限者審核並記錄原因。
- [ ] **AC-F-42**：當 customer 首次取得 Center application access 時，Organization 與第一間 Center MUST 已依驗證 onboarding 資料完成關聯；若 Organization 沒有 Center record，Platform MUST 回傳 onboarding exception 與 reference，且 Add Center MUST 不可用。當預先建置的第一間 Center 所屬 Organization 同時沒有有效 Subscription 與 active trial entitlement 時，只有該 Center setup 可取得初始 handoff；當 Organization 具有效服務 entitlement 且新增後續 Center 時，新 Center MUST 免費繼承相同方案或 trial entitlement、billing cycle 與 lifecycle，且 MUST NOT 建立第二筆 subscription、payment profile、invoice 或 location fee。
- [ ] **AC-F-43**：當 Organization 進入 `Read-only` 滿 59 天或 Super Admin 核准終止 `Suspended`／`Read-only` 帳戶時，系統 MUST 記錄刪除排程時間、原因與觸發者，並在刪除日前 30、14、7、1 天發送通知；當客戶於期限前完成付款恢復或 Super Admin 撤銷排程時，系統 MUST 清空排程並記錄撤銷時間與操作者，MUST NOT 直接刪除任何資料。
- [ ] **AC-F-44**：當客戶以 Interac e-Transfer 付款、Finance 或 Super Admin 尚未手動確認到帳時，對應 invoice MUST 顯示「等待付款」而非已付款；當完成比對並標記已收款後，系統 MUST 記錄操作者、依據與時間，並更新 invoice 狀態，MUST NOT 允許非 Finance／Super Admin 角色執行此標記動作。
- [ ] **AC-F-45**：當 Super Admin 關閉免費試用開關時，既有試用中 Organization MUST 不受影響，僅新建立 Organization MUST NOT 再取得試用資格；當 Super Admin 調整全局預設天數時，僅影響此後新建立的試用 Organization。
- [ ] **AC-F-46**：當 Organization 處於試用期間時，Tenant Detail MUST 顯示剩餘天數與到期日，且 Super Admin MUST 可輸入或選擇要增加的天數執行延長，成功後到期日 MUST 疊加延長，MUST NOT 縮短既有到期日；當 Organization 不在試用期間時，MUST NOT 顯示延長操作，且此調整 MUST NOT 影響其他 Organization。
- [ ] **AC-F-47**：當 Organization 進入試用時，MUST NOT 出現要求選擇 Go／Plus／Pro 的強制步驟；試用期間的 Active Children 額度 MUST 依全局設定的試用方案權限（預設 Pro）套用。
- [ ] **AC-F-48**：當 Organization 處於試用期間時，invoice MUST NOT 出現基本費或超額費項目，帳戶狀態 MUST 維持 `Active`。
- [ ] **AC-F-49**：當試用天數屆滿且 Organization 已有效驗證付款方式時，系統 MUST 於下一個計費週期自動開始計費；當試用天數屆滿且尚未驗證付款方式時，系統 MUST 直接將 Organization 轉為 `Read-only`，且 MUST NOT 經過 `Payment Issue`／`Grace Period`。
- [ ] **AC-F-50**：當 Organization 標記為 `is_test_account` 時，該 Organization MUST NOT 出現在 Customers 正式列表、統計數據或任何計費流程中；Super Admin MUST 可透過獨立篩選條件查詢到該帳號。
- [ ] **AC-F-51**：當 Super Admin 點擊 `Add Market Price` 建立新 Market 時，系統 MUST 產生穩定的 Market ID；後續變更該 Market 的顯示名稱或任何設定值，MUST NOT 影響其 ID 或已綁定的 Organization 關聯。
- [ ] **AC-F-52**：當 Super Admin 修改某 Market 的方案顯示名稱、幣別、月費、年繳折扣率或人數額度時，僅該 Market MUST 套用新值，其他 Market MUST NOT 受影響；三方案的功能範圍 MUST 在所有 Market 保持一致。
- [ ] **AC-F-53**：當 Super Admin 關閉某 Market 時，已綁定該 Market 的既有 Organization MUST 不受影響地繼續運作；新建立 Organization MUST NOT 可選擇已關閉的 Market。當該 Market 是目前 fallback 時，系統 MUST 阻擋關閉並要求先指定另一個符合資格的 fallback Market。
- [ ] **AC-F-54**：當某 Market 的稅率維持預設 0% 且未經 Super Admin 明確確認時，系統 MUST 阻擋該 Market 被啟用；完成確認後 MUST 可正常啟用。
- [ ] **AC-F-55**：當某 Market 的 Invoice 範本包含該轄區要求的功能性欄位（如稅務登記號碼）時，開立予該 Market 下 Organization 的 invoice MUST 正確呈現這些欄位。
- [ ] **AC-F-56**：當設定某 Market 的付款方式時，選項 MUST 僅限 Stripe 全球支援的付款方式；Interac e-Transfer MUST NOT 出現在未明確勾選啟用的 Market 選項中。
- [ ] **AC-F-57**：當建立 Organization 時，系統 MUST 要求指定唯一 Market，且之後 MUST NOT 提供更換入口；當使用 fallback resolution 時，系統 MUST 在第一次 Subscription preview、異動或付款前將該 Market 綁定至新 Organization；同一 Organization 底下所有 Center MUST 沿用該 Organization 的 Market 設定，MUST NOT 出現混用不同 Market 計費設定的情形。
- [ ] **AC-F-58**：平台完成初始設定後 MUST 恰有一個 fallback Market。只有已啟用、稅率已確認且方案定價、Invoice 範本與付款方式契約完整的 Market 可被指定；目前 fallback 被關閉或任何必要契約被移除時，系統 MUST 阻擋儲存並要求先指定合格替代值。指定新 fallback MUST 原子性移除舊標記並留下 audit event。當尚未綁定 Organization 的 customer plan routing 無法由既有 Organization、已驗證 handoff 或有效 CTA hint 解析 Market 時，Platform MUST 回傳 fallback Market contract 與 `market_resolution=fallback`，並在任何 Subscription preview、異動或付款前完成新 Organization 綁定。變更 fallback MUST NOT 改變任何既有 Organization Market；既有 Organization Market 遺失或損壞時 MUST 回傳資料完整性錯誤。

---

## 6. 排除範圍（Out of Scope）

### 6.1 V1 不做

- **Center 日常營運功能**：兒童、教師、出勤、日報、照片、健康、事故、Family Billing & Subsidies 等由《托育中心後台管理系統 PRD》定義，本 PRD 不重複建立。
- **家長端 App／PWA**：家長查看日報、照片、訊息、缺席、家庭付款與 Tax Receipt 不屬於 SaaS control plane。
- **Customer-facing Subscription UI**：現有 Center 後台的獨立 `Subscription` 入口、tabs、cards、tables、modal 與文案由《Binnii Customer Subscription PRD》定義，本 PRD 不重繪。
- **公開 Pricing Calculator 與行銷網站 checkout**：登入內的 customer experience 與未登入購買漏斗均不由本 PRD 定義。
- **coupon、promotion code、sales-assisted quote 與永久免費方案**：現行 Active Children 訂閱方案未定義，V1 不納入；免費試用機制由 F-45～F-49 定義，不在此排除範圍內。
- **多支付服務商**：V1 全平台統一使用 F-19 定義的 Stripe，不提供跨供應商選擇；各 Market 僅能從 Stripe 全球支援的付款方式中勾選啟用，見 F-56。
- **依方案限制產品功能**：現行 Go／Plus／Pro 功能相同；V1 不建立功能分級銷售模型。
- **客服 impersonation 與兒童詳細資料存取**：V1 不提供；若未來確有需求，必須另立安全、同意、時限與不可竄改稽核規格。
- **白牌、SSO、公開 API 商品化與專屬單租戶環境**：屬 Enterprise／add-on 範圍，不納入 SaaS 管理 V1。
- **完整商業分析套件**：MRR、ARR、churn prediction、CAC、LTV 與 cohort analytics 可於 V2+ 另行規劃；V1 僅提供營運所需狀態與計費資料。

### 6.2 TBD 給 RD

- 支付服務整合、invoice 文件產生、稅額計算、通知派送與背景作業的實作方式為 `TBD 給 RD`，但 MUST 滿足 §3、§4、§5 的產品行為與安全約束。
- usage snapshot、payment event 與 invoice generation 的安全重送識別方式為 `TBD 給 RD`，但 MUST 達成 F-36「不重複計量／開票／扣款」。
- 稽核紀錄的保存媒介與防竄改實作為 `TBD 給 RD`，但一般 UI MUST NOT 可修改或刪除既有 audit event。

---

## 7. 模組依賴關係（Module Dependencies）

### 7.1 上游依賴（本模組依賴什麼）

| # | 上游模組 / 服務 | 依賴內容 | 邊界責任歸誰 | 上游不可用時的行為 |
|---|--------------|---------|------------|----------------|
| 1 | Center Management System | Organization／Center 對應、Enrollment 生效資料、Organization canonical child ID、各 Center 每日 Active Children 計量證據與 Center 時區 | Center Management System 負責營運資料與跨 Center 兒童身分對應正確性；本模組負責驗證計量輸入、跨 Center 去重、保存 Organization snapshot 與計費 | 保留最後可信 snapshot，Organization 當日標示 `Pending`／`Error`；不得將未知值計為 0、排除缺漏 Center 或直接開立最終 invoice |
| 2 | Identity & Access Management | Customer 與 Binnii 內部使用者身分、MFA、角色及 Organization／Center scope | Identity 服務負責驗證身分；本模組負責每次操作重新檢查產品權限 | 阻擋登入或敏感操作，顯示安全提示；不得以 fallback 帳戶繞過 |
| 3 | Payment Service | payment method token、charge、refund 與 payment event | Payment Service 負責付款憑證與交易結果；本模組負責訂閱意圖、金額、對帳與客戶狀態；Interac e-Transfer 到帳確認不透過本依賴，由 Finance 或 Super Admin 於 Admin Console 人工比對後標記（見 F-44） | 不重複送出 charge；保留 `Pending`／`Payment Issue` 並提供重試或人工處理入口 |
| 4 | Invoice & Tax Service | invoice 文件、帳單地址、適用稅額與 line items | 上游負責合規計算與文件結果；本模組負責提供正確訂閱與用量明細 | 阻擋 invoice finalization，保留 preview 並通知 Finance；不得以 0 稅額靜默開票 |
| 5 | Notification Service | Email／產品內通知之派送與結果 | 本模組負責事件、收件對象與內容；Notification Service 負責派送與 delivery result | 核心狀態照常保存，通知標示失敗並允許重試；不得回滾已完成付款或方案異動 |
| 6 | Binnii Subscription Policy | Organization 共用的 Go／Plus／Pro、月繳／年繳、額度、超額費、升降級、取消、adjustment、免費試用（開關、預設天數、試用方案權限）及各 Market 定價（幣別、方案顯示名稱、月費、年繳折扣、額度、稅率、Invoice 範本、付款方式）規則 | PM 維護商業契約；本模組依核准版本執行並顯示生效版本 | 若缺少可識別的核准版本，阻擋新 Organization Subscription 與方案變更；既有訂閱沿用最後核准版本 |

> ⚠️ 啟用順序規則：Identity & Access Management、Organization／Center 基礎資料及可識別版本的 Binnii Subscription Policy MUST 先可用；否則本模組 MUST 阻擋建立新訂閱。Payment、Invoice／Tax 或 Notification 暫時不可用時，依上表進入可追蹤的 pending／error 狀態，MUST NOT 偽造成功。

### 7.2 下游消費者（誰依賴本模組）

| # | 下游模組 | 消費內容 | 本模組負責 | 下游自己負責 |
|---|---------|---------|----------|-----------|
| 1 | Center Management System | Center 所屬 Organization 的 subscription status、plan、effective date 與共同 service restriction | 提供目前有效且可追蹤版本的 Organization 狀態 | 依狀態讓同一 Organization 的所有 Center 顯示一致警示、唯讀或停用行為；不得自行推算付款狀態 |
| 2 | Binnii Customer Subscription | Organization Subscription、去重總用量、各 Center 用量明細、invoice、payment 與 subscription change | 提供 scope 正確的資料、權限判斷、preview、變更結果與 audit reference | 在 Center live app 顯示 loading／empty／error、確認流程與使用者回饋；UI 依 Consumer PRD §8 |
| 3 | Binnii Admin Console | Tenant、billing、support、audit 與 system health | 提供角色限定的查詢與操作契約 | 僅呈現角色允許的資料及動作，不繞過 API 權限 |
| 4 | Finance Reconciliation | invoice、payment、refund、credit、adjustment 與差異狀態 | 提供可對帳的業務紀錄與 audit reference | 完成外部會計對帳與例外處理，不直接改寫 usage snapshot |
| 5 | Binnii Center Management | Organization／第一間 Center onboarding integrity、後續 Center provisioning、active entitlement eligibility、繼承結果與 customer-safe lifecycle | 零 Center 時提供 onboarding exception；具 active entitlement 時只對後續 Center 提供免費繼承結果；同時沒有有效 Subscription 與 active trial entitlement 時只對預先建置的第一間 Center 提供初始 handoff | 呈現 setup／status、建立後續 Center 並處理 `Setup required`，且不得自行建立第一間 Center、第二筆 Subscription 或 charge |

> 本模組停用時：下游 MUST 停止建立新訂閱、方案異動與最終 invoice；Center Management System MAY 在明確標示資料時間的情況下暫時沿用最後可信 service status，MUST NOT 因 control plane 無回應而自行永久刪除或全面解鎖資料。
> 詳細 Consumer 整合規則見 §10。

---

## 8. UI / UX 草稿或參考

> **本節只定義 Binnii 內部 Admin Console。** Customer-facing UI 的唯一規格來源為《Binnii Customer Subscription PRD》§8，並使用 `04_Webroot/app.binnii.com` 的 Center shell；本文件 MUST NOT 定義 Subscription sidebar、customer plan／usage／invoice／payment 頁面。
>
> **視覺真相來源**：Binnii Admin Console 的資訊架構、資料範圍與角色權限由本 PRD 定義；頁面 shell、色彩、字體、間距、導覽狀態及共用元件視覺 MUST 對齊 `04_Webroot/app.binnii.com` 的 `layouts/app.blade.php`、`components/sidebar.blade.php`、`components/` 與 `resources/css/theme.css`。實作結構固定為 `app → sidebar → main → wrap`：238px Deep Indigo sidebar、反白 Binnii 品牌、Indigo active item、Coral 左側 active marker、Cloud White main、最寬 1000px content wrap、Montserrat、白色 card、light-Indigo table header、Indigo primary action，以及 live code 的 secondary／danger button、modal、empty、error、loading 與 pagination 語言。
>
> **責任邊界**：上述 Live Code 僅是 shell 與共用元件的視覺／互動基準，不是 Admin Console 的資料、路由、導覽或授權來源。Admin Console sidebar MUST 使用下表的內部導覽；MUST NOT 顯示 `Current Center`、Center switcher、Dashboard、Children、Staff、Attendance、Family Billing & Subsidies 或其他 Center 營運項目。Internal design system 的規格 MUST 由 PM 驗證並同步至本節，MUST NOT 由 Module_Spec 自行增刪。

**Admin Console sidebar 導覽與角色可見性**

| 導覽項目 | Support | Finance | Super Admin | Active state |
|---|---:|---:|---:|---|
| `Customers` | 顯示 | 顯示 | 顯示 | Customers 列表與 Tenant Detail 均保持 active |
| `System Health` | 隱藏 | 隱藏 | 顯示 | System Health 頁面 active |
| `Platform Settings` | 隱藏 | 隱藏 | 顯示 | Free Trial／Markets 頁面展開並保持 parent active |
| `Free Trial`（child item） | 隱藏 | 隱藏 | 顯示 | Free Trial 頁面 active |
| `Markets`（child item） | 隱藏 | 隱藏 | 顯示 | Markets 與 Market Configuration active |

Sidebar 底部 MUST 顯示登入者的內部角色（Support／Finance／Super Admin），但不得把角色標籤當成權限判斷來源。無權限項目 MUST 不渲染，不得以 disabled item 洩露入口。

### 8.1 Customers — Binnii Admin Console

| 行為欄位 | 定義 |
|---|---|
| 入口 | Binnii Admin Console → `Customers` |
| 目的 | 讓授權內部角色以 Organization、Center、Owner email、phone 或 customer-facing correlation reference 快速找到客戶、平台狀態及對應支援事件 |
| 卡片 / 操作按鈕 | 每列 `View Customer`；工具列 `Export Results` 僅對具權限角色顯示 |
| 情境 | 搜尋無結果顯示清除篩選建議；權限不足不顯示受限欄位與動作；標記 `is_test_account` 的 Organization MUST NOT 出現在預設列表與統計數據中 |
| 對應功能 | F-01、F-02、F-05、F-24、F-29、F-38、F-50、F-57 |

**版面層級結構（Hierarchy View）**

```text
┌──────── Sidebar 238px ────────┬──────── Main / Cloud White ──────────────────────────────┐
│ Binnii reverse logo           │ Customers                              Role: [User role] │ 固定
├───────────────────────────────┼──────────────────────────────────────────────────────────┤
│▌ Customers                    │ Search [Org / Center / Email / Phone / Reference]          │ active
│  System Health                │ Plan [Select] Payment [Select] Trial [Select] [Clear]     │ 依角色顯示
│  Platform Settings            │ [ ] Show test accounts                  (Super Admin)    │ 依角色顯示
│                               ├──────────────────────────────────────────────────────────┤
│                               │ Customers table                                          │ 固定
│                               │ Organization | Market | Centers | Owner | Plan | Active  │
│                               │ Status | Payment                         [View Customer]   │
│                               ├──────────────────────────────────────────────────────────┤
│ Role: Support / Finance /     │ Pagination / Loading / Empty / Error                    │ 條件顯示
│ Super Admin                   │                                                          │
└───────────────────────────────┴──────────────────────────────────────────────────────────┘
```

**欄位標籤**

| 顯示語意 | PRD 欄位標籤 |
|---|---|
| 跨欄搜尋 | `Search Customers` |
| customer-facing correlation reference 精確搜尋 | `Search Customers` |
| 所屬 Market 篩選 | `Market` |
| 生命週期篩選 | `Account Status` |
| 方案篩選 | `Plan` |
| 付款狀態篩選 | `Payment Status` |
| 試用狀態篩選 | `Trial` |
| 顯示測試帳號開關（Super Admin 專用） | `Show Test Accounts` |
| Organization（唯讀顯示） | `Organization` |
| 所屬 Market（唯讀顯示） | `Market` |
| Center 數量（唯讀顯示） | `Centers` |
| Account Owner email（唯讀顯示） | `Account Owner Email` |
| Organization 共用方案（唯讀顯示） | `Plan` |
| Organization Active Children（唯讀顯示） | `Active Children` |

`Search Customers` MUST 同時查詢 Organization name、Center name、Center ID、Account Owner email、Account Owner phone、Center phone、invoice number 與完整 customer-facing correlation reference。電話輸入與資料端 MUST 使用 Organization Market 或 Center phone country code 進行 country-aware 正規化，移除顯示用空白、括號與連字號並比對相同的國際號碼與 national significant number；含國碼及不含國碼的加拿大／美國 `+1` 常見輸入格式 MUST 可找到相同電話。reference 輸入 MUST 只移除頭尾空白並轉成 canonical case，之後採完整精確比對，不得做 partial／fuzzy search。reference 唯一命中時不顯示一般 Customers table，而是開啟所屬 Tenant Detail → `Audit` 並套用 reference filter；查無或無權查看時顯示 `No event found for this reference.`。空值 MUST NOT 被當成命中，且搜尋回應 MUST 只回傳目前角色有權查看的 Organization。

### 8.2 Tenant Detail

| 行為欄位 | 定義 |
|---|---|
| 入口 | Customers 的 `View Customer`；invoice、payment 或 system event 的 tenant deep link |
| 目的 | 依角色彙整租戶、Center、subscription、usage、billing、support 與 audit context |
| 卡片 / 操作按鈕 | Overview 的 `Detail`；Centers 每列的 `Detail`；`Add Support Note`（Support、Finance、Super Admin）、`View Usage`、`View Invoice`、`Create Adjustment`（Finance、Super Admin）、`Confirm e-Transfer`（Finance、Super Admin）、`Suspend Account`（Super Admin） |
| 情境 | 付款異常、計量錯誤、取消排程或停權時顯示條件式 banner |
| 導覽 | Admin Console sidebar 的 `Customers` MUST 展開目前 Organization 的 8 個 Tenant Detail 子頁面；目前子頁面 MUST 顯示 active state |
| 對應功能 | F-03、F-05、F-10、F-11、F-18、F-20、F-25～F-30、F-35、F-38、F-44、F-46、F-49、F-57 |

**版面層級結構（Hierarchy View）**

```text
┌──────── Single Sidebar 238px ─────────┬──────── Main / Cloud White ──────────────────────┐
│ Binnii reverse logo                   │ Customers › [Organization]       Role: [role]     │ 固定
├───────────────────────────────────────┼──────────────────────────────────────────────────┤
│▌ Customers                            │ [Organization] [Market] [Account Status]         │ parent active
│   [Organization context label]        │ Risk / Payment / Metering banner                 │ 條件顯示
│   ▌Overview                           ├──────────────────────────────────────────────────┤ active example
│    Centers                            │ Selected page white card                         │ 固定
│    Subscription                       │ Page title / page actions                        │
│    Usage                              │ Summary → Filters → Table / Timeline → Pager     │
│    Invoices                           │ Loading / Empty / Error                          │
│    Payments                           │                                                  │
│    Support                            │                                                  │
│    Audit                              │ [Page-specific actions]                          │ 依權限顯示
│  System Health                        │                                                  │ 依角色顯示
│  Platform Settings                    │                                                  │ 依角色顯示
│ Role: Support / Finance / Super Admin │                                                  │
└───────────────────────────────────────┴──────────────────────────────────────────────────┘
```

**Customers 展開選單中的 Tenant Detail 子頁面**

| 順序 | 頁面項目 | 內容 | 選中視覺 |
|---:|---|---|---|
| 1 | `Overview` | Organization、owner、Organization Contact Phone、方案、週期、去重總用量、資料時間及 Organization Detail 入口 | 進入 Tenant Detail 的預設 active item |
| 2 | `Centers` | Center 清單、identifier、timezone、Active Children、Center Status、Usage Data Status 及每列 Center Detail 入口 | Centers 頁面 active |
| 3 | `Subscription` | 方案、billing cycle、費率、生效日、續約、pending change 與試用延長 | Subscription 頁面 active |
| 4 | `Usage` | Organization 去重總用量、各 Center 來源明細與資料完整性 | Usage 頁面 active |
| 5 | `Invoices` | Organization invoice、period、費用明細與狀態 | Invoices 頁面 active |
| 6 | `Payments` | 付款事件、付款方式、狀態與 Interac e-Transfer 對帳入口 | Payments 頁面 active |
| 7 | `Support` | Support note、case reference、版本歷史與新增／編輯動作 | Support 頁面 active |
| 8 | `Audit` | 依權限提供唯讀 reference、source module、event、actor、日期、結果與 customer-safe reason；支援完整 reference filter 與命中事件高亮 | Audit 頁面 active |

Tenant Detail 使用 Admin Console 的單一 238px sidebar。`Customers` 為可返回 Customers 列表的 parent item；進入 Organization 後，其下方 MUST 顯示不可操作的 Organization context label 與授權的 Tenant Detail 子頁面。每個頁面只渲染一個 active child item，使用 Indigo 背景與 Coral 左側 marker，並以 `aria-current="page"` 表達目前位置。Deep link MUST 直接開啟對應頁面並選中其項目；loading、empty、error 與 modal 關閉後 MUST 保留目前頁面的 active state。未授權項目 MUST 不渲染，不得以 disabled item 洩露入口。

**Overview 與 Organization Detail Modal**

- Overview 的 `Organization Contact Phone` 卡片 MUST 直接顯示目前 Account Owner 的 phone，讓內部人員不需開啟 Modal 即可取得主要聯絡電話；沒有可信 phone 時顯示 `—`，MUST NOT 顯示其他使用者或其他 Organization 的電話。
- `Organization Contact Phone` 是目前 Account Owner contact 的顯示語意，資料來源固定為 Account Owner `users.phone`；`organizations` 不建立第二份相同電話資料。
- Overview 的 `Detail` MUST 開啟唯讀 Organization Detail Modal。Customer-provided profile 與 owner contact MUST 顯示 `Organization Name`、`Account Owner Name`、`Account Owner Email`、`Organization Contact Phone`；Platform-owned context MUST 顯示 `Organization ID`、`Market`、`Account Status`、`Onboarding Status`、`Created At`、`Updated At`。
- Organization Subscription、usage、invoice、payment、support 與 audit 詳細資料 MUST 留在各自 Tenant Detail 子頁面，MUST NOT 在 Organization Detail Modal 複製另一套可產生歧義的資料視圖。

**Centers 與 Center Detail Modal**

- Centers 清單每列的 `Detail` MUST 以該列 Center ID 重新取得授權範圍內的最新資料後，開啟唯讀 Center Detail Modal；MUST NOT 信任 client-side Organization／Center payload 或沿用前一次開啟的 Center 資料。
- Center Detail Modal 的 Customer-provided Center Profile MUST 顯示 `Center Name`、`Center Email`、`Phone`（包含 phone country code）、`Time Zone`、`Tax ID`、`Address`、`Address Line 2`、`City`、`State / Province / Territory / Region`、`Country`、`Postal Code`、`Licensed Capacity`、`Desired Full Capacity`。
- Center Profile MUST 使用 live `centers` record 的 `name`、`email`、`phone_country_code`、`phone`、`timezone`、`tax_id`、`address_line1`、`address_line2`、`city`、`state`、`country`、`zip`、`licensed_capacity` 與 `desired_capacity`；`Center ID` 使用 Platform 核發的 stable Center identifier。Modal MUST NOT 從 Center name、row index 或未驗證 client payload 推導 identifier。
- Platform-owned context MUST 顯示 `Center ID`、`Organization`、`Organization ID`、`Center Status`、`Created At`、`Updated At`。無值的 optional profile 欄位顯示 `—`，MUST NOT 以 `0`、`Active` 或其他推測值代替未知資料。
- Detail Modal 的範圍是 Center Profile。`center_settings` 所屬的報表偏好、媒體延遲、家長簽到／接送、姓名顯示與其他營運設定由 Center application 的 Configuration 管理，MUST NOT 顯示於 Binnii Admin Console 的 Center Detail Modal。

**Detail Modal 共同行為**

- Modal MUST 保持目前 Overview 或 Centers sidebar active state，並提供 `Close`、右上角關閉圖示與 `Esc` 關閉；開啟時焦點進入 Modal、Tab 焦點不得離開 Modal，關閉後焦點返回原 `Detail` 按鈕。
- Modal 為唯讀；MUST NOT 顯示 Save、Edit 或任何可修改客戶／Center profile 的控制項。無可信值的 optional 欄位 MUST 顯示 `—`，不得以預設值或其他 tenant 資料補位。欄位查詢、空值處理、錯誤與 permission denied 均 MUST 維持目前 Organization scope，不得洩露其他 tenant 資料。

**Audit reference lookup**

- `Audit` 頁 MUST 提供 `Correlation Reference`、`Event Type`、`Actor` 與 `Date Range` filter。由 Customers reference 搜尋進入時，`Correlation Reference` MUST 預填完整 reference，並高亮唯一命中的 event row。
- 命中結果 MUST 顯示 `Correlation Reference`、`Source Module`、`Organization`、`Center`（如適用）、`Event Type`、`Result`、`Event Time` 與 `Customer-safe Reason`。`Customer-safe Reason` 必須足以讓 Support 向客戶說明未完成的動作與下一步，但不得包含 raw payload、stack trace、payment credential、兒童或家庭資料。
- 清除 reference filter 後 MAY 瀏覽目前角色可見的其他 audit event。查無或無權查看時 MUST 顯示 `No event found for this reference.`；同一 reference 命中多個不同事件或租戶時 MUST 顯示資料完整性錯誤並停止呈現事件內容。
- reference lookup MUST 受目前內部角色與 Organization scope 限制，且查詢行為本身 MUST 留下 audit event；不得以 reference 存在與否洩漏未授權租戶。

**欄位標籤**

| 顯示語意 | PRD 欄位標籤 |
|---|---|
| Organization（唯讀顯示） | `Organization` |
| 所屬 Market（唯讀顯示） | `Market` |
| 帳戶狀態（唯讀顯示） | `Account Status` |
| Organization identifier（唯讀顯示） | `Organization ID` |
| Center identifier（唯讀顯示） | `Center ID` |
| Organization 主要聯絡電話（唯讀顯示） | `Organization Contact Phone` |
| Account Owner 名稱（唯讀顯示） | `Account Owner Name` |
| Account Owner email（唯讀顯示） | `Account Owner Email` |
| Organization onboarding 狀態（唯讀顯示） | `Onboarding Status` |
| Center 名稱（唯讀顯示） | `Center Name` |
| Center email（唯讀顯示） | `Center Email` |
| Center 電話（唯讀顯示） | `Phone` |
| Center 時區（唯讀顯示） | `Time Zone` |
| Center 稅務識別碼（唯讀顯示） | `Tax ID` |
| Center 地址（唯讀顯示） | `Address`、`Address Line 2`、`City`、`State / Province / Territory / Region`、`Country`、`Postal Code` |
| Center 容量（唯讀顯示） | `Licensed Capacity`、`Desired Full Capacity` |
| Center 狀態（唯讀顯示） | `Center Status` |
| Center 用量資料狀態（唯讀顯示） | `Usage Data Status` |
| 詳細資料開啟按鈕 | `Detail` |
| Modal 關閉按鈕 | `Close` |
| Support note 內容 | `Support Note` |
| 外部 case reference | `Case Reference` |
| Audit event 篩選 | `Event Type` |
| Audit operator 篩選 | `Actor` |
| Audit date range | `Date Range` |
| customer-facing correlation reference 篩選 | `Correlation Reference` |
| reference 來源模組（唯讀） | `Source Module` |
| customer-safe 原因摘要（唯讀） | `Customer-safe Reason` |
| 剩餘試用天數與到期日（試用中才顯示，唯讀） | `Trial Days Remaining` |
| 延長試用操作（試用中才顯示，Super Admin 專用） | `Extend Trial` |

### 8.3 Create Financial Adjustment Modal

| 行為欄位 | 定義 |
|---|---|
| 觸發 | Finance 或 Super Admin 在 Tenant Detail 或 Invoice Detail 點擊 `Create Adjustment` |
| Shell context | Modal MUST 疊加於原 Tenant Detail／Invoice Detail 的 `Customers` active shell；背景保留 sidebar 與頁面 context 並套用 modal overlay，不得導向另一套操作介面 |
| 目的 | 建立不改寫 usage snapshot 或原 invoice 的 debit adjustment、credit 或 refund |
| 底部按鈕 | `Cancel`、`Review Adjustment`；Review 後使用 `Confirm Adjustment` |
| 關閉後 | Cancel 不儲存；成功返回原 invoice 並顯示 adjustment reference |
| 對應功能 | F-05、F-18、F-28、F-34、F-35、F-38 |

```text
┌──────────────────────────────────────────────────────────────┐
│ Create Financial Adjustment                              [×] │
├──────────────────────────────────────────────────────────────┤
│ Organization [read-only]                                    │
│ Invoice number [read-only]                                  │
│ Adjustment type [Select]                                    │
│ Amount [____]  Currency [CAD]                               │
│ Reason [______________________________________________]       │
│ Internal reference [________________]                        │
├──────────────────────────────────────────────────────────────┤
│ This does not change Active Children usage evidence.         │
├──────────────────────────────────────────────────────────────┤
│                              [Cancel] [Review Adjustment]     │
└──────────────────────────────────────────────────────────────┘
```

**欄位標籤**

| 顯示語意 | PRD 欄位標籤 |
|---|---|
| Organization（唯讀顯示） | `Organization` |
| Invoice（唯讀顯示） | `Invoice Number` |
| Adjustment 類型 | `Adjustment Type` |
| 金額 | `Amount` |
| 幣別（唯讀顯示） | `Currency` |
| 原因 | `Reason` |
| 內部 reference | `Internal Reference` |

### 8.4 Account Status Action Modal

| 行為欄位 | 定義 |
|---|---|
| 觸發 | Super Admin 點擊 `Suspend Account`、`Restore Account`、`Override Cancellation` 或 `Schedule Deletion` |
| Shell context | Modal MUST 疊加於 Tenant Detail page；關閉後 MUST 恢復觸發頁面、捲動位置與 sidebar active item |
| 目的 | 在高風險生命週期操作前顯示完整影響並取得明確確認 |
| 底部按鈕 | `Go Back`、依動作顯示 `Confirm Suspension`／`Confirm Restore`／`Confirm Override`／`Schedule Deletion` |
| 關閉後 | 未確認返回 Tenant Detail 且不變更；成功顯示 reference 與生效時間 |
| 情境 | 不同動作各自顯示受影響 Center 數量、服務狀態、資料狀態與通知對象 |
| 對應功能 | F-03、F-05、F-21、F-27、F-28、F-37、F-38 |

```text
┌──────────────────────────────────────────────────────────────┐
│ Confirm Account Status Change                            [×] │
├──────────────────────────────────────────────────────────────┤
│ Organization: [name]                                        │
│ Action: [Suspend / Restore / Override / Schedule Deletion]   │
│ Effective time: [date and time]                              │
│ Affected centers: [count]                                    │
│ Service impact: [summary]                                    │
│ Data impact: [summary]                                       │
│ Notifications: [recipients]                                  │
│ Reason [______________________________________________]       │
│ [ ] I understand the impact of this action.                  │
├──────────────────────────────────────────────────────────────┤
│                                [Go Back] [Confirm Action]     │
└──────────────────────────────────────────────────────────────┘
```

**欄位標籤**

| 顯示語意 | PRD 欄位標籤 |
|---|---|
| 動作（唯讀顯示） | `Account Action` |
| 生效時間 | `Effective Time` |
| 受影響 Center（唯讀顯示） | `Affected Centers` |
| 服務影響（唯讀顯示） | `Service Impact` |
| 資料影響（唯讀顯示） | `Data Impact` |
| 通知對象（唯讀顯示） | `Notification Recipients` |
| 操作原因 | `Reason` |
| 影響確認 | `I Understand the Impact` |

### 8.5 System Health

| 行為欄位 | 定義 |
|---|---|
| 入口 | Binnii Admin Console → `System Health`；僅 Super Admin 可見 |
| 目的 | 監看 usage、invoice、payment、notification 與 lifecycle job 的完成度和失敗項目 |
| 卡片 / 操作按鈕 | `View Failures`、`Open Tenant`、`Retry Eligible Job`（僅對可安全重試且具權限者顯示） |
| 情境 | 嚴重失敗顯示 Error banner；資料延遲顯示最後成功時間 |
| 對應功能 | F-05、F-11、F-28、F-30、F-36、F-38 |

**版面層級結構（Hierarchy View）**

```text
┌──────── Sidebar 238px ────────┬──────── Main / Cloud White ──────────────────────────────┐
│ Binnii reverse logo           │ System Health                       Role: Super Admin    │ 固定
├───────────────────────────────┼──────────────────────────────────────────────────────────┤
│  Customers                    │ Critical billing / metering banner                      │ 條件顯示
│▌ System Health                ├────────────────┬────────────────┬────────────────────────┤ active
│  Platform Settings            │ Usage ingestion│ Invoice jobs   │ Payment / Notifications │ 固定
│    Free Trial                 │ Success/Pending│ Success/Failed │ Success/Failed           │
│    Markets                    ├──────────────────────────────────────────────────────────┤
│                               │ Category [Select] Status [Select] Date range [Select]     │ 固定
│                               ├──────────────────────────────────────────────────────────┤
│                               │ Failures table                                           │ 固定
│                               │ Time | Category | Organization | Center | Status | Error │
│ Role: Super Admin             │               [Open Tenant] [Retry Eligible]             │
└───────────────────────────────┴──────────────────────────────────────────────────────────┘
```

**欄位標籤**

| 顯示語意 | PRD 欄位標籤 |
|---|---|
| Job 類別篩選 | `Job Category` |
| Job 狀態篩選 | `Job Status` |
| 日期範圍 | `Date Range` |
| 發生時間（唯讀顯示） | `Occurred At` |
| Organization（唯讀顯示） | `Organization` |
| Center（唯讀顯示） | `Center` |
| 錯誤摘要（唯讀顯示） | `Error Summary` |

### 8.6 Confirm Interac e-Transfer Payment Modal

| 行為欄位 | 定義 |
|---|---|
| 觸發 | Finance 或 Super Admin 在 Tenant Detail 的 `Payments` 頁面點擊 `Confirm e-Transfer` |
| Shell context | Modal MUST 疊加於 `Payments` 頁面，sidebar MUST 維持 `Customers` 展開且 `Payments` active |
| 目的 | 將客戶透過 Interac e-Transfer 匯入的款項與待收款 invoice 手動比對後標記為已收款 |
| 底部按鈕 | `Cancel`、`Confirm Payment` |
| 關閉後 | Cancel 不儲存；成功後 invoice 狀態更新為已付款並顯示操作紀錄 |
| 對應功能 | F-44 |

```text
┌──────────────────────────────────────────────────────────────┐
│ Confirm Interac e-Transfer Payment                       [×] │
├──────────────────────────────────────────────────────────────┤
│ Organization [read-only]                                    │
│ Invoice number [read-only]                                  │
│ Amount due [read-only]                                       │
│ Amount received [____]  Currency [CAD]                      │
│ Reference / sender [________________]                        │
│ Received date [date]                                          │
│ Notes [______________________________________________]        │
├──────────────────────────────────────────────────────────────┤
│ Invoice remains "Awaiting Payment" until confirmed here.       │
├──────────────────────────────────────────────────────────────┤
│                              [Cancel] [Confirm Payment]        │
└──────────────────────────────────────────────────────────────┘
```

**欄位標籤**

| 顯示語意 | PRD 欄位標籤 |
|---|---|
| Organization（唯讀顯示） | `Organization` |
| Invoice（唯讀顯示） | `Invoice Number` |
| 應收金額（唯讀顯示） | `Amount Due` |
| 實收金額 | `Amount Received` |
| 幣別（唯讀顯示） | `Currency` |
| e-Transfer 參考資訊 | `Reference / Sender` |
| 到帳日期 | `Received Date` |
| 備註 | `Notes` |

### 8.7 Platform Settings — Free Trial Configuration

| 行為欄位 | 定義 |
|---|---|
| 入口 | Binnii Admin Console → `Platform Settings`；僅 Super Admin 可見 |
| 目的 | 設定全平台的免費試用政策：開關、預設天數、試用方案權限 |
| 卡片 / 操作按鈕 | `Save Changes` |
| 情境 | 關閉試用不影響既有試用中 Organization；變更僅套用於此後新建立的 Organization |
| 對應功能 | F-45、F-47 |

**版面層級結構（Hierarchy View）**

```text
┌──────── Sidebar 238px ────────┬──────── Main / Cloud White ──────────────────────────────┐
│ Binnii reverse logo           │ Platform Settings › Free Trial       Role: Super Admin  │ 固定
├───────────────────────────────┼──────────────────────────────────────────────────────────┤
│  Customers                    │ Free Trial white card                                    │ 固定
│  System Health                │ Enable Free Trial [Toggle]                               │
│▌ Platform Settings            │ Default Trial Length (Days) [____]                       │ parent active
│   ▌Free Trial                 │ Trial Plan Entitlement [Go / Plus / Pro]                  │ child active
│    Markets                    ├──────────────────────────────────────────────────────────┤
│                               │                                           [Save Changes] │ 固定
│ Role: Super Admin             │                                                          │
└───────────────────────────────┴──────────────────────────────────────────────────────────┘
```

**欄位標籤**

| 顯示語意 | PRD 欄位標籤 |
|---|---|
| 試用功能開關 | `Enable Free Trial` |
| 全局預設試用天數（預設 14） | `Default Trial Length (Days)` |
| 試用期方案權限（預設 Pro） | `Trial Plan Entitlement` |

### 8.8 Markets — Binnii Admin Console

| 行為欄位 | 定義 |
|---|---|
| 入口 | Binnii Admin Console → `Platform Settings` → `Markets`；僅 Super Admin 可見 |
| 目的 | 檢視與管理所有已設定的 Market（國家／市場），快速找到某個 Market 的定價、狀態與 fallback 指派 |
| 卡片 / 操作按鈕 | 每列 `Edit`；工具列 `Add Market Price`；目前 fallback Market MUST 顯示 `Fallback` badge |
| 情境 | 稅率未確認或契約不完整的 Market MUST 顯示警示狀態，MUST NOT 顯示為可正常啟用或可設為 fallback；目前 fallback Market MUST NOT 可直接關閉 |
| 對應功能 | F-51、F-53、F-58 |

**版面層級結構（Hierarchy View）**

```text
┌──────── Sidebar 238px ────────┬──────── Main / Cloud White ──────────────────────────────┐
│ Binnii reverse logo           │ Platform Settings › Markets          Role: Super Admin  │ 固定
├───────────────────────────────┼──────────────────────────────────────────────────────────┤
│  Customers                    │ Markets                         [Add Market Price]        │ 固定
│  System Health                ├──────────────────────────────────────────────────────────┤
│▌ Platform Settings            │ Markets table                                             │ 固定
│    Free Trial                 │ Market | Currency | Plans | Tax | Fallback | Status [Edit]│
│   ▌Markets                    ├──────────────────────────────────────────────────────────┤ child active
│                               │ Pagination / Loading / Empty / Error                      │ 條件顯示
│ Role: Super Admin             │                                                          │
└───────────────────────────────┴──────────────────────────────────────────────────────────┘
```

**欄位標籤**

| 顯示語意 | PRD 欄位標籤 |
|---|---|
| Market 名稱（唯讀顯示） | `Market` |
| 幣別（唯讀顯示） | `Currency` |
| 稅率確認狀態（唯讀顯示） | `Tax Status` |
| fallback 狀態（唯讀顯示） | `Fallback` |
| Market 啟用狀態（唯讀顯示） | `Status` |

### 8.9 Market Configuration（新增／編輯）

| 行為欄位 | 定義 |
|---|---|
| 觸發 | Super Admin 在 Markets 列表點擊 `Add Market Price` 或 `Edit` |
| Shell context | Modal MUST 疊加於 `Platform Settings → Markets` active shell；關閉後回到原 Markets 清單與篩選 context |
| 目的 | 設定單一 Market 的方案顯示名稱、幣別、定價、額度、稅率、Invoice 範本、付款方式與 fallback 指派 |
| 底部按鈕 | `Cancel`、`Save Draft`；稅率已確認且必要契約完整時額外顯示 `Enable Market`；編輯已啟用 Market 時顯示 `Save Changes` |
| 關閉後 | Cancel 不儲存；Save Draft 保留設定但不開放給新 Organization 選用，直到稅率確認、必要契約完整並 Enable；儲存 `Use as Fallback Market` 時原子性取代目前 fallback |
| 對應功能 | F-52、F-54、F-55、F-56、F-58 |

```text
┌──────────────────────────────────────────────────────────────┐
│ Market Configuration — Canada                             [×] │
├──────────────────────────────────────────────────────────────┤
│ Currency [Select: CAD ▾]                                     │
│ Plan Labels   Go → [Go]  Plus → [Plus]  Pro → [Pro]           │
│ Monthly Base Fee   [$10] [$30] [$70]                          │
│ Annual Discount Rate [0.8]                                    │
│ Active Children Entitlement [10] [40] [100]                   │
│ Tax Rate [5%]  [✓] I confirm this tax rate is correct          │
│ Invoice Template [Canada GST/HST ▾]                            │
│ Payment Methods (Stripe)  [✓] Card  [✓] Interac e-Transfer     │
│ [✓] Use as Fallback Market                                    │
├──────────────────────────────────────────────────────────────┤
│                                  [Cancel] [Save Changes]      │
└──────────────────────────────────────────────────────────────┘
```

**欄位標籤**

| 顯示語意 | PRD 欄位標籤 |
|---|---|
| 幣別 | `Currency` |
| 方案顯示名稱（三方案各自） | `Plan Labels` |
| 三方案月費 | `Monthly Base Fee` |
| 年繳折扣率 | `Annual Discount Rate` |
| 三方案人數額度 | `Active Children Entitlement` |
| 稅率 | `Tax Rate` |
| 稅率確認勾選 | `I Confirm This Tax Rate Is Correct` |
| Invoice 範本選擇 | `Invoice Template` |
| 付款方式勾選（僅 Stripe 支援範圍） | `Payment Methods` |
| fallback 指派 | `Use as Fallback Market` |

`Use as Fallback Market` 只在 Market 已啟用、稅率已確認，且方案定價、Invoice 範本與付款方式契約完整時可提交。成功後平台 MUST 恰有一個 fallback；目前 fallback 的勾選 MUST NOT 可直接取消，也不得儲存會讓它失去 fallback 資格的設定，Super Admin 必須先改選另一個符合資格的 Market。指定新值 MUST 在同一交易中移除舊值，並記錄操作者、時間、舊 Market 與新 Market。變更只影響尚未綁定 Organization 且前三順位皆無可信結果的後續 plan routing，不得遷移任何既有 Organization。

### 8.10 Add／Edit Support Note Modal

| 行為欄位 | 定義 |
|---|---|
| 觸發 | Support、Finance 或 Super Admin 在 Tenant Detail 的 `Support` 頁面點擊 `Add Support Note`；或點擊既有 note 旁的 `Edit` |
| Shell context | Modal MUST 疊加於 `Support` 頁面，sidebar MUST 維持 `Customers` 展開且 `Support` active |
| 目的 | 記錄或更正客戶互動的客服備註；編輯既有 note 時保留完整版本歷史 |
| 底部按鈕 | `Cancel`；新增時為 `Save Note`，編輯既有 note 時為 `Save Changes` |
| 關閉後 | Cancel 不儲存；成功後回到 `Support` 頁面並保留 sidebar active state，編輯模式下既有內容移入版本歷史 |
| 對應功能 | F-26 |

```text
┌──────────────────────────────────────────────────────────────┐
│ Add Support Note                                          [×] │
├──────────────────────────────────────────────────────────────┤
│ Customer Issue [______________________________________________]│
│ Action Taken   [______________________________________________]│
│ Case Reference (optional) [________________]                  │
├──────────────────────────────────────────────────────────────┤
│                                    [Cancel] [Save Note]        │
└──────────────────────────────────────────────────────────────┘
```

編輯既有 note 時，同一表單額外顯示版本歷史：

```text
┌──────────────────────────────────────────────────────────────┐
│ Edit Support Note                                          [×] │
├──────────────────────────────────────────────────────────────┤
│ Customer Issue [______________________________________________]│
│ Action Taken   [______________________________________________]│
│ Case Reference (optional) [________________]                  │
├──────────────────────────────────────────────────────────────┤
│ Version History                                         [▾]   │
│  v1 · Alex (Support) · 2026-08-05 14:02                        │
│  v2 · Jamie (Finance) · 2026-08-06 09:15 (current)              │
├──────────────────────────────────────────────────────────────┤
│                                  [Cancel] [Save Changes]        │
└──────────────────────────────────────────────────────────────┘
```

**欄位標籤**

| 顯示語意 | PRD 欄位標籤 |
|---|---|
| 客戶問題 | `Customer Issue` |
| 採取動作 | `Action Taken` |
| 外部 case reference（選填） | `Case Reference` |
| 版本歷史（僅編輯模式顯示） | `Version History` |

---

## 9. 可能的風險與建議開發前須討論的議題

| # | 日期 | 議題 | 結論 | 決策者 |
|---|------|------|------|--------|
| 1 | 2026-08-05 | SaaS 管理責任邊界 | Customer Subscription 屬 Center live app；本 PRD 負責 Platform Core 與 Binnii Admin Console。 | PM |
| 2 | 2026-08-05 | SaaS Billing 與 Center 家庭帳務邊界 | 產品文案與資料契約固定區分 `Binnii Subscription Billing` 與 `Family Billing & Subsidies`。 | PM |
| 3 | 2026-08-06 | V1 訂閱計價契約 | 每個 Organization 只使用一套 Go／Plus／Pro；旗下 Center 共用額度，Active Children 跨 Center 去重，並以一張 Organization invoice 結算。 | PM |
| 4 | 2026-08-05 | 方案功能一致性 | Go／Plus／Pro 的產品與安全功能相同，差異只在額度與超額單價。 | 現行訂閱方案文件 |
| 5 | 2026-08-05 | 客服資料存取邊界 | V1 禁止 customer impersonation；Support 只使用平台 metadata、去識別化計費證據與 support note 排查。 | PM／Security |
| 6 | 2026-08-06 | 內部角色與權限 | Binnii Admin Console 使用 Super Admin、Finance、Support；Super Admin 擁有平台、營運、Finance 與 Support 全部業務權限，高風險操作仍須個人帳號、MFA、原因、確認與 Audit Log。 | PM／Security |
| 7 | 2026-08-07 | Organization 生命週期與資料保留政策 | 採 `Active`／`Payment Issue`（1～7 天）／`Grace Period`（8～30 天）／`Read-only`（31～89 天）／`Suspended`／`Unsubscribed` 六種狀態；第 90 天執行永久刪除，刪除排程為附加於 `Read-only`／`Suspended` 之上的屬性（F-43），不列為第七種平行狀態；F-41 的 60 天回溯更正門檻正式採用。 | PM |
| 8 | 2026-08-07 | 支付服務商與付款方式 | V1 採 Stripe Billing + Stripe Payments，支援 Visa／Mastercard／Amex／debit card；另支援 Interac e-Transfer 作為人工對帳付款方式（F-44）；暫不支援 PayPal、支票、電匯、BNPL 與 ACSS Debit。 | PM |
| 9 | 2026-08-07 | 免費試用與測試帳號 | V1 提供免費試用：Super Admin 可全局開關、設定預設天數（預設 14 天）與試用方案權限（預設 Pro），並可為試用中的個別 Organization 增加天數延長試用；試用期免收費，到期未綁定付款方式則比照 Unsubscribed 邏輯進入 Read-only。`is_test_account` 標記供 PM／QA 使用，不計費、不進正式客戶列表。coupon、促銷碼、業務報價與永久免費方案不在 V1 範圍。 | PM |
| 10 | 2026-08-07 | 多國市場（Market）定價機制 | V1 支援多 Market。Organization 是單一租戶層，Center 為其子紀錄；每個 Organization 建立時綁定唯一 Market，不可跨市場混用，同一實體客戶跨國經營須建立多個獨立 Organization。Market 可獨立設定方案顯示名稱、幣別（新建預設 USD）、月費（預設 $10／$30／$70）、年繳折扣（預設 0.8）、人數額度（預設 10／40／100）、稅率（預設 0%，啟用前須 Super Admin 明確確認）、Invoice 範本（含轄區功能性欄位）與付款方式（僅限 Stripe 全球支援範圍，不建平台級多供應商登記表）。加拿大是第一個已配置 Market，使用現行 CAD 數值。Platform 必須維持唯一且符合啟用、稅率與完整契約條件的 fallback Market；只有尚未綁定 Organization 且 Organization／handoff／有效 CTA hint 皆無可信結果時才使用，並在交易前綁定至新 Organization。客戶端方案能見度路由歸屬《Binnii Customer Subscription PRD》，本 PRD 提供唯一的 Market resolution 與資料 API。 | PM |
| 11 | 2026-08-08 | Organization 與第一間 Center onboarding | Platform 依已驗證 onboarding 資料建立 Organization 與第一間 Center，再提供 customer 的 Center application access；零 Center 為 onboarding exception，customer-facing Add Center 只建立後續 Center。 | PM |
| 12 | 2026-08-09 | Admin Console 視覺基準 | Admin Console 採用 Live Code 的 `app → sidebar → main → wrap` shell、品牌 tokens、sidebar 狀態及共用元件語言；內部導覽、角色可見性與資料範圍仍以本 PRD 為準，不顯示 Current Center 或 Center 營運項目。 | PM |
| 13 | 2026-08-09 | Tenant Detail 內容導覽 | Admin Console sidebar 的 `Customers` 展開目前 Organization context 與 Overview、Centers、Subscription、Usage、Invoices、Payments、Support、Audit 八個獨立子頁面；目前子頁面呈現 Indigo active state、Coral marker 與 `aria-current="page"`。 | PM |
| 14 | 2026-08-09 | 客戶聯絡與租戶詳細資料 | Customers 可搜尋 Organization／Center／Account Owner email／Organization 或 Center phone；Overview 直接顯示 Organization Contact Phone，Organization 與 Center 的完整 profile 以各自唯讀 Detail Modal 呈現。Organization Contact Phone 使用目前 Account Owner phone，不建立重複的 Organization phone 欄位。 | PM |
| 15 | 2026-08-09 | customer-facing correlation reference 反查 | Center Management、Customer Subscription 與其他 customer-facing module 顯示的 reference 必須全域唯一、opaque、不可變、可索引且不含 PII；相同業務事件安全重送沿用同一 reference。Support、Finance 與 Super Admin 可在 Customers 以完整 reference 精確搜尋，唯一命中後進入 Tenant Detail → Audit 並查看 customer-safe reason。prefix、長度與字元格式由 RD 規劃，但可反查屬性不得降級為 TBD。 | PM／Security |

### 9.1 已知技術陷阱（Known Technical Pitfalls）

| # | 陷阱描述 | 影響範圍 | 建議解法 |
|---|---------|---------|----------|
| 1 | 把「同一 Organization 可切換多個 Center」誤當成完整 multi-tenancy，導致跨客戶資料未隔離。 | 所有查詢、匯出、通知、cache 與 audit | Module_Spec 必須從身分到每次資料操作定義 Organization／Center scope 與拒絕路徑。 |
| 2 | 把 Family Billing & Subsidies 與 Binnii SaaS invoice 混在同一選單或資料語意。 | UI、報表、通知、客服與會計對帳 | 使用 F-32 的固定名稱與分離責任；跨系統只交換必要狀態。 |
| 3 | 重送 usage、payment webhook 或 invoice job 時建立重複 charge。 | 計量、開票、付款與退款 | Module_Spec 必須定義可安全重送的業務識別與重試／補償路徑，驗收依 F-36。 |
| 4 | 將缺漏 snapshot 當成 0，造成少收費並掩蓋計量故障。 | Invoice preview、finalization、system health | 未知值必須標 `Pending`／`Error`，阻擋 finalization，保留最後可信資料。 |
| 5 | 允許 Finance 直接改 Active Children 數，讓計量證據與財務結果失去一致性。 | Usage audit、爭議、credit／debit | 計量更正回到來源資料重算；財務補償用獨立 adjustment，不改 snapshot。 |
| 6 | support note、audit event 或 adjustment 被原地改寫，事後無法還原決策過程。 | 客服、財務、資安與合規 | 原紀錄不可由一般 UI 修改；更正使用追加事件並保留 actor、時間、理由。 |
| 7 | 付款失敗直接停權或刪除托育資料，造成營運與兒童安全風險。 | Tenant lifecycle、Center app、客服 | 依 F-03 分階段處理，停權與刪除分離，刪除需獨立 retention policy。 |
| 8 | 把文件內「已同步」當成事實，未實際核對對方文件。 | §11 跨文件同步 | 每次同步必須實際開啟目標文件核對，未執行不得標示已同步。 |
| 9 | 直接加總各 Center 人數，讓同一兒童跨 Center 被重複計費。 | Usage snapshot、invoice、爭議處理 | Organization 彙總必須使用 canonical child ID 去重；Center 明細只解釋用量來源，不得取代去重總數。 |
| 10 | 零 Center Organization 取得 customer-facing Add eligibility，或 customer-created 後續 Center 建立第二筆 Subscription、payment profile、invoice 或 location fee。 | Tenant onboarding、Center provisioning、Customer Subscription、Finance | 零 Center 回傳 onboarding exception；只有預先建置的第一間 Center 可在 Organization 同時沒有有效 Subscription 與 active trial entitlement 時啟動初始 handoff，後續 Center 只繼承 active entitlement。 |
| 11 | 多人共用 Super Admin 帳號，或因最高權限而略過原因、確認與 audit event。 | 全部 Admin Console 高風險操作與責任追蹤 | 每位內部使用者使用個人帳號與 MFA；高風險操作不因 Super Admin 身分而略過安全控制。 |
| 12 | 在 Finance／Super Admin 手動確認到帳前，將 Interac e-Transfer 對應的 invoice 顯示或當成已付款，或比對到錯誤的 Organization／金額。 | Invoice 狀態、對帳、客戶信任 | 到帳確認前 invoice MUST 維持「等待付款」；標記已收款 MUST 記錄操作者、比對依據與時間並計入 audit event（F-44）。 |
| 13 | 標記 `is_test_account` 的 Organization 被計費流程誤判為正式客戶，或反過來讓正式客戶被誤標為測試帳號而漏收費、漏統計。 | 計費正確性、Customers 統計數據 | `is_test_account` MUST 是計費與列表查詢的硬性排除條件，非僅前端隱藏；異動此標記 MUST 限定 Super Admin 並計入 audit event。 |
| 14 | 同一組付款資訊或同一使用者反覆建立新 Organization 濫用免費試用。 | 試用政策、營收 | V1 未定義防濫用機制，`TBD 給 RD`；MUST 於 Module_Spec 階段與 PM 確認是否需要付款方式或身分層級的重複試用偵測。 |
| 15 | Market 稅率仍為預設 0% 卻被啟用，或稅率確認流程被繞過。 | 稅務合規、財務風險 | 依 F-54，MUST 強制 Super Admin 明確確認稅率後才可 Enable，UI 與後端 MUST 雙重檢查，不得僅前端擋。 |
| 16 | 同一實體客戶被誤判為可以共用一個 Organization 橫跨多個 Market，或 Center 被指派到跟 Organization 不同的 Market。 | 稅務、幣別、Invoice 正確性 | 依 F-57，Organization 建立時鎖定唯一 Market 且不可更換；Center MUST 沿用所屬 Organization 的 Market，不得個別指定。 |
| 17 | Admin Console 自建另一套 top navigation／品牌樣式，或直接套用 Center menu 而出現 Current Center 與營運入口。 | 品牌一致性、導覽理解、權限與資料邊界 | 依 §8 與 NFR-13，只共用 Live Code 的 shell、tokens、sidebar state 與共用元件視覺；Admin Console 導覽與角色可見性固定依 §8 表格實作。 |
| 18 | 平台同時存在零個或多個 fallback Market、目前 fallback 被直接關閉，或 fallback 變更時覆寫既有 Organization Market。 | Market routing、稅務、計費與租戶完整性 | 以資料庫與服務層唯一約束、交易式 replacement、eligibility 檢查及 audit event 強制 F-58；fallback 只處理尚未綁定 Organization 的 unresolved routing，既有 Organization Market 永不遷移。 |
| 19 | customer UI 顯示 reference，但平台沒有 durable mapping、允許 partial match、碰撞後任選一筆，或反查結果暴露內部 payload。 | Support 排障、tenant isolation、隱私與歷史事件可追溯性 | 顯示前先依 F-28 建立全域唯一且可索引 mapping；Customers 只做 canonical exact match，collision 視為完整性錯誤，結果只顯示 customer-safe reason 並記錄 lookup audit event。 |

---

## 10. Consumer 整合契約（Consumer Integration Contract）

### 10.1 本模組提供給 Consumer 的 API 介面

| API 功能 | 說明 | 備註 |
|---------|------|------|
| Resolve Tenant Context | 輸入已驗證使用者身分，輸出可存取的 Organization、Center、角色、permission scope 與 onboarding integrity result。 | Consumer 不得自行從前端參數推斷 tenant scope；Organization 沒有 Center record 時回傳 customer-safe exception 與 reference，不授予 Add eligibility。 |
| Authorize Customer Subscription Action | 輸入使用者、Organization 與 View／Manage／Payment／Export action，輸出允許或拒絕及 customer-safe reason；current Center 僅可作為 application context。 | Family Billing & Subsidies 權限不得自動映射為允許。 |
| Get Organization Subscription Status | 輸入 Organization identifier 或可解析所屬 Organization 的授權 Center context，輸出 lifecycle status、current plan stable ID、effective date、billing cycle、受影響 Center 數量、共同 service restriction、customer-safe payment method readiness，以及是否試用中、試用到期日與試用方案權限；同時輸出 Organization Market stable ID／顯示名稱／country code／幣別及其三個方案的 stable plan ID、顯示名稱、月費、年繳費、`Annual Discount Rate`、Active Children 額度、overage rate、稅務提示與可用付款方式。 | 不輸出 payment credential 或內部 Finance note；試用與 payment readiness 欄位供 Customer Subscription 顯示倒數及到期引導；完整 Market plan catalog 供客戶端方案與 annual 折扣 badge 顯示及能見度路由使用（歸屬 Consumer PRD）。Market 停止接受新 Organization 時，已綁定該 Market 的既有 Organization 仍須取得原 Market contract。 |
| Resolve Market Plan Route | 輸入已驗證的 Organization context、pre-provisioned first Center handoff 或 allowlisted Location／landing CTA Market hint，依既有 Organization Market、handoff Market、有效 hint、fallback Market 的固定優先序解析；輸出 stable Market ID、顯示名稱、country code、完整 Market contract、contract version 與 `market_resolution=organization\|handoff\|hint\|fallback`。 | 既有 Organization Market 優先且不可被 hint 或 fallback 覆寫；其 Market 遺失或損壞時回傳資料完整性錯誤。只有尚未綁定 Organization 且前三順位無可信結果時可回傳 fallback；新 Organization 必須在任何 Subscription preview、異動或付款前完成 Market 綁定。fallback 設定不可用時回傳 service configuration error，不得由 Consumer 自選 Canada／CAD 或 IP geolocation。 |
| Record Customer-facing Correlation Result | 輸入 customer-facing reference、source module、業務事件 identity、Organization、Center（如適用）、event type、result、event time 與 customer-safe reason，建立供 Admin Console 精確反查的 immutable mapping。 | reference 必須全域唯一、opaque、不可變、可索引且不含 PII；同一業務事件安全重送時回傳同一 mapping，reference 或 business identity 衝突時拒絕並回傳完整性錯誤。Consumer 在 customer UI 顯示 reference 前，mapping MUST 已 durable 且可由 `Search Customers` 反查；不得把 raw payload、stack trace、payment credential 或兒童照護內容寫入 customer-safe reason。 |
| Submit Daily Usage Source | 輸入 Center、snapshot date、Active Children count、Organization canonical child ID 證據、source version 與 event identity，輸出 accepted／pending／error 與 source version。 | 相同來源事件重送不得重複計量；各 Center count 不得直接相加，實作形式 `TBD 給 RD`。 |
| Get Organization Usage & Invoice Preview | 輸入已授權 Organization 與 billing period，輸出每日去重總用量、各 Center 用量明細、基本費、超額 child-months、adjustment 與預估總額。 | Pending 日期必須與最終金額區分；Center 明細不得形成額外基本費。 |
| Preview Subscription Change | 輸入 Organization、current plan、requested plan／cancellation、billing cycle，輸出 affected Center count、effective date、billing／data impact 與 preview version。 | 提交前必須取得；未知或 stale preview 不得執行。 |
| Request Subscription Change | 輸入 Organization、requested plan／cancellation、preview version 與 customer confirmation，輸出 effective date、request status 與 audit reference。 | 權限、最新狀態與重複提交必須驗證。 |
| Get Customer Billing Summary | 輸入已授權 Organization 與期間，輸出單一 Binnii invoice、各 Center 用量摘要、payment、credit、refund 與 adjustment 摘要。 | MUST NOT 混入 Family Billing & Subsidies。 |
| Request Payment Method Update／Retry | 輸入授權 Organization context 與 payment provider reference，輸出 pending／success／error 與 customer-visible reference。 | MUST NOT 接收或回傳完整 payment credential；重送不得重複 charge。 |
| Export Customer Subscription Data | 輸入授權 Organization context、資料類型與期間，輸出 scope-safe Organization usage、各 Center 彙總／invoice export 及 audit reference。 | MUST 重新驗證 Export permission；不得包含 canonical child ID、兒童姓名或照護資料。 |
| Get Organization Subscription Eligibility | 輸入已授權的 setup Center 與 Organization，輸出 onboarding provenance、Organization Market stable ID／顯示名稱／country code、active entitlement status、可繼承 stable plan ID／Market plan display summary／trial 摘要、初始 Subscription handoff 與允許下一步。 | Customer-created 後續 Center 只在 active entitlement 時取得相同 Organization Market 與繼承結果；Center Country 必須與回傳 country code 一致；Organization 同時沒有有效 Subscription 與 active trial entitlement 時只允許 pre-provisioned first Center setup handoff；零 Center 一律回傳 exception。 |

### 10.2 Consumer 引用規則

- Consumer MUST 先使用 `Resolve Tenant Context` 取得授權 scope，MUST NOT 信任 URL、表單或 client-side state 自帶的 Organization／Center。
- Binnii Customer Subscription MUST 對 View、Manage、Payment 與 Export 分別使用 `Authorize Customer Subscription Action`，並以 Organization 為訂閱 scope；MUST NOT 以 Family Billing & Subsidies 權限、current Center 或只隱藏 CTA 取代授權。
- Center Management System MUST 透過 `Submit Daily Usage Source` 依 Center 提交計量證據與 Organization canonical child ID，MUST NOT 直接加總 Center count、寫入 SaaS invoice 或 financial adjustment。
- Binnii Center Management MUST 先使用 `Resolve Tenant Context` 驗證 Organization 已有預先建置的第一間 Center，再使用 `Get Organization Subscription Eligibility`：預先建置第一間 Center 可在 Organization 同時沒有有效 Subscription 與 active trial entitlement 時進入初始 handoff，customer-created 後續 Center 只可繼承 active entitlement；MUST NOT 對零 Center Organization 開放 Add，或為後續 Center 建立第二筆 Subscription、payment profile、invoice 或 charge。
- Binnii Customer Subscription 與 Binnii Admin Console MUST 透過本節介面取得資料，MUST NOT 以不同 UI 各自維護方案價格或 lifecycle 規則副本。
- Binnii Customer Subscription MUST 使用 `Resolve Market Plan Route` 處理登入／onboarding 前的 Market 路由，並只接受 Platform 回傳的 resolution source 與 Market contract；既有 Organization 仍以 `Get Organization Subscription Status` 的綁定 Market 為準。Consumer MUST NOT 自行挑選、快取替代或覆寫 fallback Market。
- 任何 Consumer 在 customer UI 顯示 correlation reference 前 MUST 使用 `Record Customer-facing Correlation Result` 建立可反查 mapping；相同業務事件重送 MUST 沿用原 reference。reference 格式可由 RD 規劃，但 Consumer MUST NOT 產生無法由 Admin Console 精確反查的暫時碼或只存在於前端的碼。
- 所有 Consumer MUST 顯示資料狀態與最後更新時間；`Pending`／`Error` MUST NOT 被轉譯成 0 或 success。
- Consumer 收到 permission denied、stale version 或 duplicate event 時 MUST 呈現或記錄明確結果，MUST NOT 靜默改用較寬 scope 或重複提交。

### 10.3 本模組停用時的 Consumer 行為

- 停用時：Center Management System MAY 暫時沿用最後可信 service status，並 MUST 顯示狀態資料時間；Binnii Customer Subscription 與 Binnii Admin Console MUST 停止訂閱異動、開票、adjustment 與高風險操作。
- 停用時：Consumer MUST NOT 假設所有 Center 為 Active、MUST NOT 自行解除方案限制、MUST NOT 把未知 usage 當成 0，亦 MUST NOT 永久刪除客戶資料。

---

## 11. 跨文件同步更新責任清單（Cross-Document Sync Checklist）

> **本節閱讀對象**：人類 PM、PM 方 AI、人類 RD、RD 方 AI。
>
> 團隊實際作業分離：人類 PM 負責 PRD；人類 RD 負責實作，且不保證會撰寫 Module_Spec。PM 方與 RD 方作業環境不會自動同步；各方 MUST 負責自己作業環境內的文件同步，確保自己文件都是最新狀態。
>
> 本節列出 PM 方與 RD 方各自需同步的文件與同步狀態。若實作引入新資料結構、新事件類型、新 API 契約或其他會影響既有文件的變更，MUST 在自己作業環境內同步相關文件，MUST NOT 只交付 PRD 或實作而忽略跨文件同步。

| # | 需同步的文件 | 需新增 / 修改的內容 | 更新原則 | PM方是否同步 | RD方是否同步 |
|---|------------|------------------|----------|----------------|----------------|
| 1 | `02_Tasks/02_Childcare_Management_Software/PRD/托育中心後台管理系統_PRD.md` | 補充 Organization／tenant 邊界、Binnii Subscription Billing 與 Family Billing & Subsidies 分工、Center usage source 與 canonical child ID 輸出責任、Organization Subscription status consumer 行為，以及獨立 `Subscription` 入口邊界。 | additive | 待確認 | 待確認 |
| 2 | `02_Tasks/02_Childcare_Management_Software/PRD/Binnii_Customer_Subscription_PRD.md` | 維持 Organization-level Subscription、跨 Center 去重用量、單一 invoice、permissions、customer flow 與本 PRD §10 平台契約一致；提供免費試用倒數／到期引導、Organization Market 顯示、annual 折扣 badge 與 Organization／handoff／有效 CTA hint／fallback 路由；所有 customer-facing Subscription／payment reference 必須在顯示前以 `Record Customer-facing Correlation Result` 建立可反查 mapping。 | additive | 已同步 2026-08-09 | 待確認 |
| 3 | `02_Tasks/02_Childcare_Management_Software/research/binnii-subscription-plan_CAD.md` | 維持 Organization 共用 Go／Plus／Pro、月／年繳、Active Children 跨 Center 去重、單一帳單、後續 Center trial entitlement 繼承、升降級、取消與 adjustment 契約一致。 | 契約對齊 | 已同步 2026-08-08 | 待確認 |
| 4 | `02_Tasks/02_Childcare_Management_Software/PRD/Binnii_Center_Management_PRD.md` | 維持 Organization／第一間 Center 預先建置、Organization Market 與 Center Country 一致、零 Center exception、pre-provisioned first Center handoff、後續 Center 繼承 Market 與 active entitlement eligibility、`Setup required` 與 activation result 契約一致；Center create／resume／activation／switch／denial／failure 的 customer-facing reference 必須符合全域唯一、不可變、可索引、重送沿用及 Admin Console 精確反查契約。 | 驗證對齊 | 已同步 2026-08-09 | 待確認 |
| 5 | `02_Tasks/02_Childcare_Management_Software/Brand/Binnii_Brand_Visual_Brief.md`、`04_Webroot/app.binnii.com/resources/views/layouts/app.blade.php`、`components/sidebar.blade.php`、`components/`、`resources/css/theme.css` | 驗證 SaaS Admin Console 的品牌、色彩、字體、無障礙、`app → sidebar → main → wrap` shell、sidebar 狀態與共用元件視覺；Live Code 只提供視覺／互動基準，不提供 Admin Console 導覽、資料或授權。 | 驗證對齊 | 已驗證無需修改 2026-08-09 | 待確認 |
| 6 | `02_Tasks/02_Childcare_Management_Software/Module_Spec/Binnii_SaaS_Platform_Management_Module_Spec.md`（待建立） | 建立 tenant isolation、Organization Subscription、跨 Center 去重、Center usage source、single invoice、Center inheritance、Super Admin／Finance／Support 權限、lifecycle、payment、notification、audit、customer-facing reference 唯一索引／精確 lookup／collision handling、失效恢復及 §10 Consumer 介面的可實作規格；Visual Reference 只能引用本 PRD §8。 | additive | 待確認 | 待確認 |

> ⚠️ **紅線規則**：PM 方 AI 完成本 PRD 後，MUST 提醒人類 PM 指派 AI 執行 PM 方文件同步；RD 方 AI 完成實作後，MUST 提醒人類 RD 指派 AI 執行 RD 方文件同步。各方 AI 的責任是提醒自己的人類用戶完成自己作業環境內的文件同步，確保自己文件都是最新狀態。若任一同步步驟被跳過，執行者 MUST 明確告知自己的人類用戶哪些文件尚未同步並說明風險，MUST NOT 靜默忽略或提交後才揭露。

### 11.1 文件同步指派用 AI 指令

**PM 方同步指令：**
> 請依據本 PRD 的 §11「跨文件同步更新責任清單」，逐列處理 `PM方是否同步` 尚未完成的項目。
>
> 執行規則：
> 1. 只處理 §11 各列指定且由 PM 方維護的文件；MUST NOT 修改 RD 方文件，也 MUST NOT 代替 RD 更新 `RD方是否同步`。
> 2. 每列 MUST 依「需新增 / 修改的內容」與「更新原則」執行。若要求同步術語、範圍、依賴或契約，必須完成必要內容修改；若為 `驗證對齊`，必須完成比對後才能判定無需修改。
> 3. 逐列完成後，依實際結果將本 PRD 的 `PM方是否同步` 更新為 `已同步 YYYY-MM-DD`、`已驗證無需修改 YYYY-MM-DD`，或保留 `待確認` 並註明阻塞原因。
> 4. 完成後回報：已同步文件、已驗證無需修改文件、未同步文件與原因；MUST NOT 把未執行項目標成已同步。

**RD 方同步指令：**
> 請依據本 PRD 的 §11「跨文件同步更新責任清單」，逐列處理 `RD方是否同步` 尚未完成的項目。
>
> 執行規則：
> 1. 只處理 RD 方維護的實作文件、handoff、Module_Spec（若有）、contracts 或其他 RD 維護文件；MUST NOT 修改 PM 方文件，也 MUST NOT 代替 PM 更新 `PM方是否同步`。
> 2. 每列 MUST 依「需新增 / 修改的內容」與「更新原則」執行。若實作引入或改變資料結構、事件、API、欄位、依賴或失效行為，必須同步到該列指定的 RD 文件，MUST NOT 只加引用後宣告完成。
> 3. 若實作產生 §11 未列出的新同步影響，先揭露並補入同步清單，再執行同步。
> 4. 逐列完成後，依實際結果將本 PRD 的 `RD方是否同步` 更新為 `已同步 YYYY-MM-DD`、`已驗證無需修改 YYYY-MM-DD`，或保留 `待確認` 並註明阻塞原因。
> 5. 完成後回報：已同步文件、已驗證無需修改文件、未同步文件與原因；MUST NOT 把未執行項目標成已同步。

---

## 12. 需求審查聲明

除下列通用審查條件外，本功能若出現以下任一情況亦 MUST 立即提出：

- 任一查詢、cache、匯出、通知或背景作業可能缺少 Organization／Center scope。
- Binnii Subscription Billing 與 Family Billing & Subsidies 的欄位、invoice 或權限邊界混用。
- 同一 Organization 出現多套 Go／Plus／Pro、Center 基本費、Center invoice 或多個 payment profile。
- 各 Center Active Children 被直接相加，或同一兒童跨 Center 未依 canonical child ID 去重。
- Add Center 建立第二筆 Subscription、charge 或 location fee，而不是繼承 Organization 的有效方案或 active trial entitlement。
- Platform 在 Organization 尚無第一間 Center 時提供 customer access 或 Add eligibility，而不是回傳 onboarding exception 與 reference。
- Organization 付款失敗、取消或停用時，旗下 Center 沒有套用相同 lifecycle 結果。
- usage snapshot 缺漏被當成 0，或同一 usage／payment／invoice event 重送可能產生重複 charge。
- Super Admin、Finance 或 Support 能在無額外授權與稽核下查看兒童照護資料。
- Customers phone 搜尋或 Detail Modal 可能跨越目前角色的 Organization scope，或將 Center 營運設定誤當成 Platform profile 詳細資料。
- customer UI 顯示的 correlation reference 無法由 Customers 精確反查、同一 reference 對應多個事件／租戶、查無時導向錯誤租戶，或 Audit 顯示 raw payload／stack trace 而非 customer-safe reason。
- 內部人員共用 Super Admin 帳號，或 Super Admin 的高風險操作可略過原因、確認或 Audit Log。
- `is_test_account` 的 Organization 出現在計費流程、Customers 正式列表或統計數據中。
- 試用期間收取基本費或超額費，或試用到期轉換邏輯繞過 F-49 定義的付款方式驗證判斷。
- Market 稅率未經 Super Admin 明確確認就被啟用，或 Organization／Center 出現跨 Market 混用計費設定的情形。
- fallback Market 不唯一、不符合啟用與完整契約資格、可在沒有替代值時被關閉，或變更 fallback 會遷移／覆寫既有 Organization Market。
- Suspend、Cancel、payment failure 或一般管理操作可能直接刪除客戶營運資料。

**技術執行者（人類 RD 或 AI）在閱讀或實作過程中，
若發現以下任何情況，MUST 立即提出討論，
MUST NOT 擅自跳過、靜默忽略或自行補假設後繼續執行：**

- 技術已過時或有更佳的現代替代方案
- 不符合資訊安全或隱私合規規範（如 GDPR、PIPEDA）
- 在當前技術棧（WordPress / PHP / Plugin）下不可行或代價過高
- 與現有已定義的模組契約、資料結構或 UI 規範衝突
- 需求描述模糊、存在歧義或缺少驗收條件
- 實作範圍超出單一功能點的合理邊界
- §11 跨文件同步清單中有任何項目在技術上無法如期完成

**回報格式**：
> ⚠️ 發現隱憂：[簡述問題]
> 影響範圍：[哪個功能 / 模組 / 文件]
> 建議方向：[替代方案或需要 PM 決策的問題]

---

*Last Updated: 2026-08-09*
