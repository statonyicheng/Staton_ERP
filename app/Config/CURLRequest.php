<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class CURLRequest extends BaseConfig
{
    // ── CodeIgniter 4.7 新增的設定；缺少時會在執行期噴 Undefined property ──
    public array $shareConnectionOptions = [
        CURL_LOCK_DATA_CONNECT,
        CURL_LOCK_DATA_DNS,
    ];

    /**
     * --------------------------------------------------------------------------
     * CURLRequest Share Options
     * --------------------------------------------------------------------------
     *
     * Whether share options between requests or not.
     *
     * If true, all the options won't be reset between requests.
     * It may cause an error request with unnecessary headers.
     */
    public bool $shareOptions = false;
}
