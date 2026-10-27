# PHP / Go 动态互操作验证

除固定向量外，此工具随机生成消息和 NaCl nonce，验证 PHP → Go、Go → PHP 两个方向的 RSA-SHA256、Ed25519 和 NaCl box。PHP 端支持 PHP 5.6+，先执行 `composer install`；无 Sodium 扩展时由 sodium_compat 执行相同密码学协议。参考 Go 仓库需为方案固定提交 `2aa0cf1`，并具备 Go 工具链及模块依赖。

```sh
# 在 PHP 仓库生成一次 PHP 输出
php tests/Interop/export.php > /tmp/waas-php-interop-input.json

# 在参考 Go 仓库执行（将路径替换为本地 PHP 仓库绝对路径）
cd ../api-sdk-go
GOCACHE=/tmp/go-build-php-sdk-interop go run /absolute/path/api-sdk-php/tests/Interop/main.go \
  /tmp/waas-php-interop-input.json /tmp/waas-go-interop-output.json \
  /absolute/path/api-sdk-php/tests/Fixtures

# 回到 PHP 仓库验证 Go 输出
cd ../api-sdk-php
php tests/Interop/verify.php /tmp/waas-go-interop-output.json
```

若 PHP 运行在容器中，将仓库和结果所在临时目录挂载进去即可；例如 `docker run --rm -v "$PWD:/app" -w /app php:5.6-cli php tests/Interop/export.php`。
