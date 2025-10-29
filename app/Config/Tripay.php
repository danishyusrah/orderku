<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Tripay extends BaseConfig
{
    public string $apiKey = '';
    public string $privateKey = '';
    public string $merchantCode = '';
    public bool   $isProduction = false;

    public function __construct()
    {
        parent::__construct();

        $this->apiKey       = env('TRIPAY_API_KEY', '');
        $this->privateKey   = env('TRIPAY_PRIVATE_KEY', '');
        $this->merchantCode = env('TRIPAY_MERCHANT_CODE', '');
        $this->isProduction = (bool) env('TRIPAY_IS_PRODUCTION', false);

        if (empty($this->apiKey) || empty($this->privateKey) || empty($this->merchantCode)) {
            log_message('notice', '[Tripay Config] Kunci Tripay default sistem belum lengkap di .env');
        }
    }
}
