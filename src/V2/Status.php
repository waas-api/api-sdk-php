<?php

namespace WaasApi\ApiSdkPhp\V2;

final class Status
{
    const OK = 200;
    const SIGNATURE_MALFORMED = 601;
    const TIMESTAMP_OUT_OF_WINDOW = 602;
    const NONCE_REUSED = 603;
    const ALGORITHM_UNSUPPORTED = 604;
    const SIGNATURE_INVALID = 605;
    const ANTI_REPLAY_DOWN = 606;
    const TR_NOT_ENABLED = 610;
    const VASP_INVALID = 611;
    const COIN_NOT_MAPPED = 612;
    const INVALID_PARAMETER = 613;
    const PROVIDER_DOWN = 614;
    const RESOURCE_NOT_FOUND = 615;
    const SIGNATURE_CALLBACK = 616;
    const PROVIDER_ERROR = 617;
    const SEARCH_PROCESSING = 618;
    const VASP_KEY_CACHE_MISSING = 619;
}
