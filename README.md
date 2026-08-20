# binnii.com

Binnii 純靜態官網(Home / Features / Pricing / FAQ / About),沒有任何後端。
註冊流程在獨立的 `signup.binnii.com` repo;後台在 `app.binnii.com`。

## 部署

nginx 純靜態:`root` 指到 repo root、`index Home.dc.html`;**不需要 PHP-FPM**。

## 圖片

**首頁(`Home.dc.html`)用一般 `<img>`**,圖檔在 `screenshots/`(`dashboard.webp`
/`enrollment.webp`,1200px 寬 WebP q85;`Dashboard.png`/`Enrollment.png` 是原始
截圖,留著方便重新輸出)。`<img>` 帶 `width`/`height` 讓瀏覽器先保留版位、不會
reflow,外層不要再包 `aspect-ratio`,否則會裁到圖。換圖就換檔案,不需要任何 JS。

`About.dc.html` / `Features.dc.html` 還在用 `<image-slot>`(`image-slot.js`),
圖以 base64 存在同目錄的 `image-slots.state.json`。這套有兩個踩過的坑,填圖前先看:

1. **sidecar 檔名不可以 `.` 開頭** —— 正式站 nginx `location ~ /\.` deny 所有
   dotfile,回 403 圖就空白;而本機 `python3 -m http.server` 不擋 dotfile,測起來
   會是好的。`STATE_FILE` 已改成不帶點的檔名。
2. **Cloudflare 快取 `max-age=14400`(4 小時)** —— 改完部署後前端可能還吃到舊的
   `.js`/`.json`,**incognito 也沒用**(是邊緣快取不是瀏覽器快取)。改完記得到
   Cloudflare 後台 Purge Cache。
