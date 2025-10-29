<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Konfigurasi untuk API Zeppelin (Orderkuota Wrapper)
 */
class Orderkuota extends BaseConfig
{
    /**
     * Base URL API Zeppelin
     * Ambil dari file config.js di folder orderkuota atau .env jika ada
     */
    public string $apiUrl = '';

    /**
     * Username akun Orderkuota Anda
     * Ambil dari file config.js di folder orderkuota atau .env jika ada
     */
    public string $authUsername = '';

    /**
     * Auth Token yang didapat dari Zeppelin API
     * Ambil dari file config.js di folder orderkuota atau .env jika ada
     */
    public string $authToken = '';

    /**
     * Waktu kedaluwarsa transaksi dalam menit
     * Ambil dari file config.js di folder orderkuota atau .env jika ada
     */
    public int $expiryTime = 5; // Default 5 menit

    public function __construct()
    {
        parent::__construct();

        // Ambil konfigurasi dari file .env jika ada, jika tidak, gunakan nilai default di atas
        $this->apiUrl       = env('ORDERKUOTA_API_URL', 'https://zeppelin-api.vercel.app'); // Default dari config.js
        $this->authUsername = env('ORDERKUOTA_AUTH_USERNAME', '');
        $this->authToken    = env('ORDERKUOTA_AUTH_TOKEN', '');
        $this->expiryTime   = (int) env('ORDERKUOTA_EXPIRY_TIME', 5);

        // Beri peringatan jika kredensial penting belum diatur di .env
        if (empty($this->authUsername) || empty($this->authToken)) {
            log_message('warning', '[Orderkuota Config] Kredensial API (Username/Token) belum diatur di file .env');
        }
    }
}
