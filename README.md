# binnii.com

Binnii 純靜態官網(Home / Features / Pricing / FAQ / About),沒有任何後端。
註冊流程在獨立的 `signup.binnii.com` repo;後台在 `app.binnii.com`。

## 部署

nginx 純靜態:`root` 指到 repo root、`index Home.dc.html`;**不需要 PHP-FPM**。

## 圖片(image-slot)

頁面上的截圖不是 `<img src>`,是 `<image-slot id="...">`(`image-slot.js`)。
圖以 base64 data URL 存在**同目錄的 `image-slots.state.json`**,整個目錄共用一份,
換圖請合併寫入、不要整份覆蓋。慣例:最長邊 ≤1200、WebP q=0.85。

**檔名不可以用 `.` 開頭。** 上線的 nginx 會 deny 所有 dotfile
(`location ~ /\.`),`.image-slots.state.json` 會回 403,圖就整格空白 —— 而且
本機 `python3 -m http.server` 不擋 dotfile,測起來是好的,只有正式站壞。
(2026-08-20 踩過;`image-slot.js` 的 `STATE_FILE` 已改成不帶點的檔名。)
