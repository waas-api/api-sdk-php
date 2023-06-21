<?php

require "vendor/autoload.php";

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
-----END PRIVATE KEY-----",
    "platformPublicKey" => "-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA2RZZISModRzproL3eTxR
tvVK/nToDbeLmAVdmaVtHgNCwjXMeNCZvHySZKO7+t7XySzBd2PhhQjvAkDd8HRK
B5Y0uvP6TFwvhxOMkMydTeBFcUobQWNeAI7ttRms1txSNrmyrZr0WpWfk79VIsO+
yI/jFN/Pj2gxD+vh5UUKU1LD2IQq/VxgtEpf8Vs6w7SvafPwYkpdPGCKUFkRvN1F
rY2+tq0j1MRVJbB2rIunWDnT8cDUh2sWLXf4yNaJVOaaTFB0zbAk/qcoicGQyW7b
VG7dVW8WywKmOAPeQCCQa+SJ4BhfI1k8eEAwFVRuxz/f9Ecfog9hZJ+RgELkpS8q
hQIDAQAB
-----END PUBLIC KEY-----",
];
$client = new Client($config);

try {
    $resp = $client->addressGetBatch("eth"); // Get New Addresses
//    $resp = $client->addressSyncStatus("eth", "0x6828449A5b01F7c3D281A0c06216eA9f4f188B1c", "10"); // Sync Address Status
//    $resp = $client->transfer("eth", "0xb6dEd378Ad9Aa871c6ec5599049B1DFb7C9866a3", 0.23, "20230621164438434765", "10"); // initial a on-chain withdrawal request
    var_dump($resp);
} catch (Exception $e) {
    var_dump($e->getMessage());
}
