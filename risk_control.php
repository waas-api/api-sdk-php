<?php

require "vendor/autoload.php";

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
-----END PRIVATE KEY-----",
    "riskPlatformPublicKey" => "-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAwtOCAf3sMUAJhBT/hOf9
4JVW5ainBh1vSljqu1q+2GIzmjravp9IeSgvRgT4FQ9vcf4BXNbV3VwHOzCrKQbc
SudF2s8+ly2Yg+AiTWo9LgRvZvuXLbvESLrvmT6E9epAUW8g/1FSPnmOWMn1wtJ0
cYmi+kA3hRQ65xL/yJAz714F5N/WLy9hpnIIECAZx8NaBzilLD2QMcMZt1WRAIW6
z7Nw5VuCdoGu6EesGSRqTXQYfFZtEM/8otkd6WiMDuswjRQUX3Js1szb4CtPmT1j
huJtUtZQ6Mf/oY2gHB9vNONUSUu24pQi9E5CKttcFnE2PPZsOTYGyZbgPQiXVmel
/QIDAQAB
-----END PUBLIC KEY-----",
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
