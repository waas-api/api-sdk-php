
## Install

```shell
composer require waas-api/api-sdk-php
```

## Generate Rsa Key Pair

```shell
mkdir rsa_pem && cd rsa_pem
openssl
OpenSSL> genrsa -out app_private_key.pem 2048 # generate private key
OpenSSL> pkcs8 -topk8 -inform PEM -in app_private_key.pem -outform PEM -nocrypt -out app_private_key_pkcs8.pem # convert private key to PKCS8 format
OpenSSL> rsa -in app_private_key.pem -pubout -out app_public_key.pem # generate public key
OpenSSL> exit
```

## Client Request Methods

```php

use WaasApi\ApiSdkPhp\Client;

$config = [
    "baseURL" => "http://api.***.com/shopapi",
    "appId" => "2zn6rtrm62ak82y5",
    "privateKey" => "-----BEGIN PRIVATE KEY-----
MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQCvfPvCnUv8Uy80
JNmVvrpsH1BEgJIACdfde+DIobD98NBSXFStHpQ7d7V5q6/3ZH+jK0r8lJv+5CF9
PLWJAemoRDxoRp9VI0SkTSSuRSi9zWIgGAFXOM1vYAz6KyPr3+nwUAwwH784QYmH
XNzmjM91HtVsT9BhnV/+n2/Zpaq7vciPNgANcRCwrMMLWyjblaigOeWgUPG8f0As
PaE5tYTMcYuiKulNnweAnHdQ/LI8eLcJiqwUCtx8QjQtgmuhiDiAz5IobFCOTRDe
z/b4fkzUgfNyVzyXMLM7wZSM6SuWuGNu/1gYSHVo2LuiGqaW/6n5CanxDq2tCde9
IlwRSEzjAgMBAAECggEBAIShBjmo3jPevq8/Jn9JPeXXqLeNIEsNJWuMY2/e3ECg
TyHgDFMm6Eslhy9ktcJY3yp33t2Uh22WB0V6q4zXuDtnDuyUa3lSBV/TueRH+qmw
Xvf4jJLaKFGDm2s7TZPIkCsCpKN+auvehE2cvos0YtqWqiCJFemgoltzLnqk/auT
W9B+qqUXdYi9voixwWDUDQE1qOpVNKsS4Le2nWfrBGE60GOouSJFPy3UlfoSPt2l
N/PMNm6O7n0kifi/63XEQn1FcrcIIRvDCDbiJ78iYCdwQjdGieDmrXF+cglfqb9j
PtTkGBgBuEFJFil/iIW5XPhiFRecuMQ7c5w6dvs912kCgYEAxzxL5RZCtsNAWBWn
HFy2yu89eCe9150H+ulh/8/rUzqBDy9GZunkMj/87W+1RSP/Bj5+2U77gIJixPDv
sowlNo0zYDCGn7QjBOdGEEKe9xv6MURUNdLxhuou2BlMOO78uyOZ0nSPFlzl5IrO
V+cDBFem26S71Lo1xl5xpZO3GxcCgYEA4XyfIsQbCI8bjjYzBG9WSi0pF82wQzn0
6tnebmR1fGHu/T6wvNxRnwDwOtsPZ8MmAoMMZAKqmXm1uBheVYzyC0MwhoWvWUSH
5WkX7DFUzXXAXD6EG8I5F9xk26Of6KYVlnu07Ou3gl+KtWEQb4LZ5U1fPggsSePy
fDQ7F7iyDBUCgYASjnMjKyebjiP4EEw4QApmbFH1Vv4/jsinfgU6pkrsvY43s9eC
zlYKuBr+omDnx6qBEmEOLGUQWxSH80jgAF2W7x/R3bMsPMLmOgQ0j3/u1BRBcnPF
b5g/UKB8UB7hTqELbQ1upJyPIKfo2WTOmx2U0fUgj/9OmOlZSIKdQVzUkQKBgA5E
p4E4rYk+HP1Duri2Hty0vNvvvQ6T9UaDIMsVekWzoL7Pn7ljq5qHIaxnvjLPq3EP
P9J4RLlPdUs/54A8WfiSeNiaTv00E/FVVBkzCe2yXMi5f/yNrrUu0gb/9JdxIv6R
t2IijEsopotRqAuWWRlkavlu5PaMAQUQ/QhEPKf9AoGAMKVtWOetz+bvFwNMtsFF
gtMbnhLtwbX9uZ/XLpHoMgN+omhJVcRgUIm4ckZS4RnNjJ0rJUxaCYGH9U+p+exj
D12MbAJ8tXvnEBY6IG/WCmIsZee8tJsmnHZgUgYlGFzMkup+i1YoO4RYubmCP1Ey
P5lS63MtTlC7TP9N8sLttws=
-----END PRIVATE KEY-----", // app_private_key_pkcs8.pem content
    "platformPublicKey" => "-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA2RZZISModRzproL3eTxR
tvVK/nToDbeLmAVdmaVtHgNCwjXMeNCZvHySZKO7+t7XySzBd2PhhQjvAkDd8HRK
B5Y0uvP6TFwvhxOMkMydTeBFcUobQWNeAI7ttRms1txSNrmyrZr0WpWfk79VIsO+
yI/jFN/Pj2gxD+vh5UUKU1LD2IQq/VxgtEpf8Vs6w7SvafPwYkpdPGCKUFkRvN1F
rY2+tq0j1MRVJbB2rIunWDnT8cDUh2sWLXf4yNaJVOaaTFB0zbAk/qcoicGQyW7b
VG7dVW8WywKmOAPeQCCQa+SJ4BhfI1k8eEAwFVRuxz/f9Ecfog9hZJ+RgELkpS8q
hQIDAQAB
-----END PUBLIC KEY-----", // platform public key
];
$client = new Client($config);

try {
    $resp = $client->addressGetBatch("eth"); // Get New Addresses
//    $resp = $client->addressSyncStatus("eth", "0x6828449A5b01F7c3D281A0c06216eA9f4f188B1c", "10"); // Sync Address Status
//    $resp = $client->transfer("eth", "0xb6dEd378Ad9Aa871c6ec5599049B1DFb7C9866a3", 0.23, "20230621164438434765", "10"); // initial a on-chain withdrawal request
//    ...
    var_dump($resp);
} catch (Exception $e) {
    var_dump($e->getMessage());
}
```

## Deposit And Withdraw Callback

```php

use WaasApi\ApiSdkPhp\Serve;

$config = [
    "privateKey" => "-----BEGIN PRIVATE KEY-----
MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQCvfPvCnUv8Uy80
JNmVvrpsH1BEgJIACdfde+DIobD98NBSXFStHpQ7d7V5q6/3ZH+jK0r8lJv+5CF9
PLWJAemoRDxoRp9VI0SkTSSuRSi9zWIgGAFXOM1vYAz6KyPr3+nwUAwwH784QYmH
XNzmjM91HtVsT9BhnV/+n2/Zpaq7vciPNgANcRCwrMMLWyjblaigOeWgUPG8f0As
PaE5tYTMcYuiKulNnweAnHdQ/LI8eLcJiqwUCtx8QjQtgmuhiDiAz5IobFCOTRDe
z/b4fkzUgfNyVzyXMLM7wZSM6SuWuGNu/1gYSHVo2LuiGqaW/6n5CanxDq2tCde9
IlwRSEzjAgMBAAECggEBAIShBjmo3jPevq8/Jn9JPeXXqLeNIEsNJWuMY2/e3ECg
TyHgDFMm6Eslhy9ktcJY3yp33t2Uh22WB0V6q4zXuDtnDuyUa3lSBV/TueRH+qmw
Xvf4jJLaKFGDm2s7TZPIkCsCpKN+auvehE2cvos0YtqWqiCJFemgoltzLnqk/auT
W9B+qqUXdYi9voixwWDUDQE1qOpVNKsS4Le2nWfrBGE60GOouSJFPy3UlfoSPt2l
N/PMNm6O7n0kifi/63XEQn1FcrcIIRvDCDbiJ78iYCdwQjdGieDmrXF+cglfqb9j
PtTkGBgBuEFJFil/iIW5XPhiFRecuMQ7c5w6dvs912kCgYEAxzxL5RZCtsNAWBWn
HFy2yu89eCe9150H+ulh/8/rUzqBDy9GZunkMj/87W+1RSP/Bj5+2U77gIJixPDv
sowlNo0zYDCGn7QjBOdGEEKe9xv6MURUNdLxhuou2BlMOO78uyOZ0nSPFlzl5IrO
V+cDBFem26S71Lo1xl5xpZO3GxcCgYEA4XyfIsQbCI8bjjYzBG9WSi0pF82wQzn0
6tnebmR1fGHu/T6wvNxRnwDwOtsPZ8MmAoMMZAKqmXm1uBheVYzyC0MwhoWvWUSH
5WkX7DFUzXXAXD6EG8I5F9xk26Of6KYVlnu07Ou3gl+KtWEQb4LZ5U1fPggsSePy
fDQ7F7iyDBUCgYASjnMjKyebjiP4EEw4QApmbFH1Vv4/jsinfgU6pkrsvY43s9eC
zlYKuBr+omDnx6qBEmEOLGUQWxSH80jgAF2W7x/R3bMsPMLmOgQ0j3/u1BRBcnPF
b5g/UKB8UB7hTqELbQ1upJyPIKfo2WTOmx2U0fUgj/9OmOlZSIKdQVzUkQKBgA5E
p4E4rYk+HP1Duri2Hty0vNvvvQ6T9UaDIMsVekWzoL7Pn7ljq5qHIaxnvjLPq3EP
P9J4RLlPdUs/54A8WfiSeNiaTv00E/FVVBkzCe2yXMi5f/yNrrUu0gb/9JdxIv6R
t2IijEsopotRqAuWWRlkavlu5PaMAQUQ/QhEPKf9AoGAMKVtWOetz+bvFwNMtsFF
gtMbnhLtwbX9uZ/XLpHoMgN+omhJVcRgUIm4ckZS4RnNjJ0rJUxaCYGH9U+p+exj
D12MbAJ8tXvnEBY6IG/WCmIsZee8tJsmnHZgUgYlGFzMkup+i1YoO4RYubmCP1Ey
P5lS63MtTlC7TP9N8sLttws=
-----END PRIVATE KEY-----", // app_private_key_pkcs8.pem content
    "platformPublicKey" => "-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA2RZZISModRzproL3eTxR
tvVK/nToDbeLmAVdmaVtHgNCwjXMeNCZvHySZKO7+t7XySzBd2PhhQjvAkDd8HRK
B5Y0uvP6TFwvhxOMkMydTeBFcUobQWNeAI7ttRms1txSNrmyrZr0WpWfk79VIsO+
yI/jFN/Pj2gxD+vh5UUKU1LD2IQq/VxgtEpf8Vs6w7SvafPwYkpdPGCKUFkRvN1F
rY2+tq0j1MRVJbB2rIunWDnT8cDUh2sWLXf4yNaJVOaaTFB0zbAk/qcoicGQyW7b
VG7dVW8WywKmOAPeQCCQa+SJ4BhfI1k8eEAwFVRuxz/f9Ecfog9hZJ+RgELkpS8q
hQIDAQAB
-----END PUBLIC KEY-----", // platform public key
];

$serve = new Serve($config);

try {
    $postJson = file_get_contents('php://input');
    $request = json_decode($postJson, true);

    // get request data
    $data = $serve->getDepositCallbackData($request); // Get Deposit Callback data
//    $data = $client->getWithdrawCallbackData($request); // Get Withdraw Callback data

    // todo : processing business logic
    var_dump($data);

    // response
    $responseData = $serve->callbackResponseData();
    echo json_encode($responseData);
    exit();
} catch (Exception $e) {
    var_dump($e->getMessage());
}
```

## Withdraw Order Risk Control Callback

```php

use WaasApi\ApiSdkPhp\RiskControl;

$config = [
    "riskPrivateKey" => "-----BEGIN PRIVATE KEY-----
MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQCu2tBRWS97gPRj
VZQl27gTCf1hOfwHDjHskEaJE79+BJ2llQ1wBaOk4kwWVkep2L/AAmHDfQXHOqWB
ASwugippvs74po5+S0fN9DON7HXpL4tcDYNngvRZIdE1kFdv9Yey0EA5PvP6yLkC
zXRAdzUPUvTmUk7bsN/SgldHM4Y846A4fL5PDMT+aViCjOL5C9YvZa5AVZTVbwJZ
HSNuAPmgp21l6/xvVGTI5QVd73bxAIGvqb/IUoFn61/60/FUcvCK4JxPwOeIragE
wKFoeUDPwMqY2RTvdZbsWMczzfiBkMqplsOzhxNKbuWKOJU1oNRdD9gxHFbWejcO
Jy7b+fwfAgMBAAECggEBAIsaZomg4uZk44c26MHpdxomY136zCYsw8jHymV4wWmW
cHfvCR6qTdJ5eUB36VEUrw8ole/4QSWK4WmsriJdyTIQ6wTjNA8DAp+0+2KX0AHv
ofVchsJNauiRv6bWKslV0luN0tn33A8RqiWxnx899oc3Xr7wke2yW41Txe1N7yf2
OVfYNHwwvR1rLR1CcHw2IgHUnjTwxz+PcNiY6qSgBQ8HdCxuJ0iV8rYwuY9juo/S
2qE8OFSVHaSYzrdG3vwy2e9uNVOh150S7Ru5A8dVoVsDAEiq7UAyGei93H7ARWuA
1saDjIHVFVBZBYoAhkKxeV5sEVnLi871BpVfimZtsdkCgYEA3eVm2yBGLNs9D8ra
YMGC3bjgBfm04lW3a+pkm2QA4+O4FUQKSdhiYisQai0BB4agHnh025150rP94Ptt
Ikh1EDob9n2BcstsjfFmyvftOQj8gajkSqg5WTdbXOan3lWfzPgLyuxrQeTluPkz
My5rs50E+Aa2ssOQOVeC+ecqk90CgYEAybqMnjA6YjusU/02XKaylTqt3IMbIrat
iNnSbSI9CnvWtEWOnpPB7bzo15XWmMLYaytti+7gbfWKKtUgl6zCpQEoyo9K2Kk6
ZMo+RQiviYENAe70GSTzc93abKZ67I/9N0NgmiIIIKiuLCxue7KCR9mhyjkN50+V
pFtTskcoXisCgYBLJezOm0CI4nrshUOt7vtWAc2E2IuE94iV+Jy374dJpo5qTU+6
sJipLHJzsugdV1+U0jCpS8y9Kg36CsxsBuP2aeecc+bKLNCHxdCNPqfNYBi8BKnE
CvLZHyFG9iq15oOnE/5Sa/zrJQ0Ttvm7BKae/rd3FDb9lbObZ+LIpZxK1QKBgGXA
nCc6imh8Uws2YNJRHykYpRPiabCT1mp9/J8hswpztrPNlge03g/txsDfipQZTBKa
oDICNuAgByZdxIfdE30pXYr2xjkM+zBVtx0ZKXnBR29fOW7pbYdXM1K0krJ6Wq4i
ZTq5ycG3c8tCSiRIwWA+jVY0eyfew8tYaZo2PE1rAoGAB+nAq8KHWRZ3lYj1/NUZ
JJ1uvSqSXDfSfcsY4lpe5bmuMevk3wXSQ40EkBaVXPBVm9n08POQ7V8HRCVKK0Iz
Zesn2TBd3yuOf8VPw8s91dKMv2dku0lRBeX+QXGWlnwvFFTEfZl5nvKZsh/+3ibU
LDt83niVyWaOR0grrb53POw=
-----END PRIVATE KEY-----", // a new risk control app_private_key_pkcs8.pem content
    "riskPlatformPublicKey" => "-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAwtOCAf3sMUAJhBT/hOf9
4JVW5ainBh1vSljqu1q+2GIzmjravp9IeSgvRgT4FQ9vcf4BXNbV3VwHOzCrKQbc
SudF2s8+ly2Yg+AiTWo9LgRvZvuXLbvESLrvmT6E9epAUW8g/1FSPnmOWMn1wtJ0
cYmi+kA3hRQ65xL/yJAz714F5N/WLy9hpnIIECAZx8NaBzilLD2QMcMZt1WRAIW6
z7Nw5VuCdoGu6EesGSRqTXQYfFZtEM/8otkd6WiMDuswjRQUX3Js1szb4CtPmT1j
huJtUtZQ6Mf/oY2gHB9vNONUSUu24pQi9E5CKttcFnE2PPZsOTYGyZbgPQiXVmel
/QIDAQAB
-----END PUBLIC KEY-----", // risk platform public key  
];

$riskControl = new RiskControl($config);

try {
    $postJson = file_get_contents('php://input');
    $request = json_decode($postJson, true);

    // get request data
    $data = $riskControl->getWithdrawOrderCheckData($request);

    // todo : processing business logic
    var_dump($data);
    $statusCode = 200;
    $orderId = $data["order_id"];

    // response
    $responseData = $riskControl->callbackResponseData($statusCode, $orderId);
    echo json_encode($responseData);
    exit();
} catch (Exception $e) {
    var_dump($e->getMessage());
}
```
