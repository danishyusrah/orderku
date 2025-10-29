<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Konfigurasi untuk Midtrans
 */
class Midtrans extends BaseConfig
{
    public string $serverKey = ''; // Default
    public string $clientKey = ''; // Default
    public bool $isProduction = false; // Default

    public function __construct()
    {
        parent::__construct();

        // Ambil konfigurasi dari file .env
        $this->serverKey    = env('MIDTRANS_SERVER_KEY', '');
        $this->clientKey    = env('MIDTRANS_CLIENT_KEY', '');
        $this->isProduction = (bool) env('MIDTRANS_IS_PRODUCTION', false);

        if (empty($this->serverKey) || empty($this->clientKey)) {
            log_message('error', 'Kunci API Midtrans belum diatur di file .env');
        }
    }
}
