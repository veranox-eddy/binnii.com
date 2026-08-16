# binnii.com

Binnii 純靜態官網(Home / Features / Pricing / FAQ / About),沒有任何後端。
註冊流程在獨立的 `signup.binnii.com` repo;後台在 `app.binnii.com`。

## 部署

nginx 純靜態:`root` 指到 repo root、`index Home.dc.html`;**不需要 PHP-FPM**。
