# V2 风控复核本地调试

需要 PHP 5.6+、OpenSSL 和 cURL，先在仓库根目录执行 `composer install`；不需要实际商户配置。私钥生成在系统临时目录，不写入示例或源码。

```sh
php examples/riskserver/generate-keys.php
php -S 127.0.0.1:8088 examples/riskserver/router.php
# 在另一个终端运行
php examples/riskserver/simulate.php withdraw
php examples/riskserver/simulate.php exchange
```

模拟器用平台风控私钥签请求，并用商户风控公钥验响应。订单保存 `2.00000000`，模拟请求发送 `2`，仍通过精确金额比较；地址保持大小写。服务只按商户订单中的 `review` 状态审批，未知状态返回等待。

容器使用方式：

```sh
docker run --rm -v "$PWD:/app" -w /app php:5.6-cli php examples/riskserver/generate-keys.php /app/.local-risk-keys
docker run --rm -p 127.0.0.1:8088:8088 -v "$PWD:/app" -w /app -e RISK_KEY_DIR=/app/.local-risk-keys php:5.6-cli php -S 0.0.0.0:8088 examples/riskserver/router.php
# 模拟器也可在同一容器内执行，或从安装了 PHP 的宿主机连接映射端口。
```

`RISK_KEY_DIR` 指定四个 PEM 文件所在目录；`RISK_ORDER_FILE` 可指定其他订单 JSON。正式接入实现 `OrderStoreInterface`，在数据库中读取订单及其审核状态；存储异常会返回已签名的 `2001`，等待平台重试。

PHP 内置服务器会在请求间重建对象，不能用内存 nonce 存储实现跨请求防重放。设置 `REDIS_HOST`、可选 `REDIS_PORT` 后，示例使用 phpredis 的原子 `SET NX EX`，TTL 默认为 600 秒；生产环境还应配置认证、连接方式及商户隔离前缀。未设置 Redis 时仅有时间窗口保护。
