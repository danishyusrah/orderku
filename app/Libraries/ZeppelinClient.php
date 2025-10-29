<?php

namespace App\Libraries;

use Config\Orderkuota as OrderkuotaConfig;
use CodeIgniter\HTTP\ClientInterface;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use CodeIgniter\HTTP\ResponseInterface; // Import ResponseInterface
use Throwable; // Import Throwable

/**
 * Class ZeppelinClient
 *
 * Library untuk berinteraksi dengan Zeppelin API (Orderkuota Wrapper).
 */
class ZeppelinClient
{
    protected ClientInterface $httpClient;
    protected OrderkuotaConfig $config;
    protected array $authPayload;

    /**
     * Constructor.
     *
     * @param OrderkuotaConfig|null $config Configuration object.
     * @param ClientInterface|null  $client HTTP Client instance.
     */
    public function __construct(?OrderkuotaConfig $config = null, ?ClientInterface $client = null)
    {
        $this->config     = $config ?? config('Orderkuota');
        $this->httpClient = $client ?? \Config\Services::curlrequest([
            'base_uri' => $this->config->apiUrl,
            'timeout'  => 15, // Set default timeout
            'http_errors' => false, // Handle errors manually based on status code
        ]);

        // Siapkan payload otentikasi sekali saja
        $this->authPayload = [
            'auth_username' => $this->config->authUsername,
            'auth_token'    => $this->config->authToken,
        ];

        // Validasi konfigurasi dasar
        if (empty($this->config->apiUrl) || empty($this->config->authUsername) || empty($this->config->authToken)) {
            log_message('critical', '[ZeppelinClient] Konfigurasi API (URL, Username, Token) tidak lengkap.');
            // Anda bisa melempar exception di sini jika diperlukan
            // throw new \RuntimeException('Konfigurasi ZeppelinClient tidak lengkap.');
        }
    }

    /**
     * Membuat permintaan pembayaran baru ke Zeppelin API.
     *
     * @param string $referenceId ID referensi unik dari sisi Anda.
     * @param int    $amount      Jumlah pembayaran dalam Rupiah.
     *
     * @throws \RuntimeException Jika terjadi kesalahan API atau HTTP.
     * @return array Hasil response dari API yang sudah di-decode.
     */
    public function createPayment(string $referenceId, int $amount): array
    {
        $endpoint = '/api/v1/payments/create';
        $params   = [
            'reference_id' => $referenceId,
            'amount'       => $amount,
            'expiry'       => $this->config->expiryTime,
        ];

        log_message('debug', '[ZeppelinClient] Creating payment. Ref ID: ' . $referenceId . ', Amount: ' . $amount);

        // Kirim request POST dengan auth payload di body dan parameter di query string
        try {
            $response = $this->httpClient->post($endpoint, [
                'query' => $params, // Kirim parameter sebagai query string
                'json'  => $this->authPayload, // Kirim auth payload sebagai JSON body
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ]
            ]);

            return $this->handleResponse($response, 'createPayment', $referenceId);
        } catch (Throwable $e) {
            log_message('error', '[ZeppelinClient Exception - createPayment] Ref ID: ' . $referenceId . ' Error: ' . $e->getMessage());
            throw new \RuntimeException('Gagal membuat pembayaran: ' . $e->getMessage());
        }
    }

    /**
     * Memeriksa status pembayaran berdasarkan reference ID.
     *
     * @param string $referenceId ID referensi transaksi yang ingin dicek.
     *
     * @throws \RuntimeException Jika terjadi kesalahan API atau HTTP.
     * @return array Hasil response dari API yang sudah di-decode.
     */
    public function checkStatus(string $referenceId): array
    {
        $endpoint = '/api/v1/payments/' . $referenceId . '/status';

        log_message('debug', '[ZeppelinClient] Checking status for Ref ID: ' . $referenceId);

        try {
            // Sesuai zeppelin.js, ini juga POST dengan auth payload di body
            $response = $this->httpClient->post($endpoint, [
                'json' => $this->authPayload, // Kirim auth payload sebagai JSON body
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ]
            ]);

            return $this->handleResponse($response, 'checkStatus', $referenceId);
        } catch (Throwable $e) {
            log_message('error', '[ZeppelinClient Exception - checkStatus] Ref ID: ' . $referenceId . ' Error: ' . $e->getMessage());
            throw new \RuntimeException('Gagal memeriksa status pembayaran: ' . $e->getMessage());
        }
    }

    /**
     * Membatalkan transaksi pembayaran yang masih pending.
     *
     * @param string $referenceId ID referensi transaksi yang ingin dibatalkan.
     *
     * @throws \RuntimeException Jika terjadi kesalahan API atau HTTP.
     * @return array Hasil response dari API yang sudah di-decode.
     */
    public function cancelPayment(string $referenceId): array
    {
        $endpoint = '/api/v1/payments/' . $referenceId . '/cancel';

        log_message('debug', '[ZeppelinClient] Cancelling payment for Ref ID: ' . $referenceId);

        try {
            // Sesuai zeppelin.js, ini juga POST dengan auth payload di body
            $response = $this->httpClient->post($endpoint, [
                'json' => $this->authPayload, // Kirim auth payload sebagai JSON body
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ]
            ]);

            return $this->handleResponse($response, 'cancelPayment', $referenceId);
        } catch (Throwable $e) {
            log_message('error', '[ZeppelinClient Exception - cancelPayment] Ref ID: ' . $referenceId . ' Error: ' . $e->getMessage());
            throw new \RuntimeException('Gagal membatalkan pembayaran: ' . $e->getMessage());
        }
    }

    /**
     * Menangani response dari API Zeppelin.
     *
     * @param ResponseInterface $response    Objek response dari HTTP Client.
     * @param string            $methodName  Nama method yang memanggil (untuk logging).
     * @param string|null       $referenceId ID referensi terkait (untuk logging).
     *
     * @throws \RuntimeException Jika response tidak valid atau API mengembalikan error.
     * @return array Data JSON yang sudah di-decode dari response.
     */
    protected function handleResponse(ResponseInterface $response, string $methodName, ?string $referenceId = null): array
    {
        $statusCode = $response->getStatusCode();
        $body = $response->getBody();
        $decoded = json_decode($body, true);

        log_message('debug', "[ZeppelinClient Response - {$methodName}] Ref ID: {$referenceId} | Status: {$statusCode} | Body: {$body}");

        // Handle non-2xx status codes
        if ($statusCode < 200 || $statusCode >= 300) {
            $errorMessage = "Error {$statusCode}";
            if (is_array($decoded) && isset($decoded['message'])) {
                $errorMessage .= ': ' . $decoded['message'];
            } elseif (!empty($body)) {
                 $errorMessage .= ' - Response: ' . substr($body, 0, 150) . (strlen($body) > 150 ? '...' : '');
            } else {
                 $errorMessage .= ' - ' . $response->getReasonPhrase();
            }
            log_message('error', "[ZeppelinClient HTTP Error - {$methodName}] Ref ID: {$referenceId} | {$errorMessage}");
            throw new \RuntimeException($errorMessage, $statusCode);
        }

        // Handle JSON decoding errors
        if (json_last_error() !== JSON_ERROR_NONE) {
            log_message('error', "[ZeppelinClient JSON Error - {$methodName}] Ref ID: {$referenceId} | Error: " . json_last_error_msg() . " | Body: {$body}");
            throw new \RuntimeException('Gagal memproses response dari API (JSON tidak valid).', $statusCode);
        }

        // Periksa struktur response dasar dari API Zeppelin (berdasarkan zeppelin.js)
        if (!isset($decoded['success'])) {
             log_message('error', "[ZeppelinClient Structure Error - {$methodName}] Ref ID: {$referenceId} | Field 'success' tidak ditemukan dalam response: {$body}");
             throw new \RuntimeException("Format response API tidak dikenali (missing 'success' field).", $statusCode);
        }

        // Jika API mengembalikan success = false, lempar exception dengan message dari API
        if ($decoded['success'] === false) {
             $apiMessage = $decoded['message'] ?? 'Operasi gagal tanpa pesan spesifik.';
             log_message('warning', "[ZeppelinClient API Error - {$methodName}] Ref ID: {$referenceId} | API returned success=false. Message: {$apiMessage}");
             throw new \RuntimeException($apiMessage, $statusCode); // Gunakan RuntimeException agar bisa ditangkap controller
        }

        // Jika semua OK, kembalikan data
        return $decoded;
    }

    /**
     * Helper untuk menghasilkan reference ID unik (opsional, bisa diganti dengan logic di controller).
     * Mengadaptasi logika dari utils.js.
     * PENTING: SHA1 tidak seaman hash modern, pertimbangkan algoritma lain jika keamanan tinggi diperlukan.
     * Fungsi ini mungkin tidak menghasilkan ID yang sama persis dengan versi Node.js karena perbedaan implementasi crypto.
     *
     * @param string|int $seed Seed untuk ID (misal: user ID).
     * @return string Reference ID yang dihasilkan.
     */
    public static function generateReferenceID($seed): string
    {
        $hashPart = substr(preg_replace('/\D/', '', sha1((string)$seed)), 0, 6) ?: substr(str_shuffle("0123456789"), 0, 6);
        $timePart = substr((string)floor(microtime(true) * 1000), -6); // Timestamp milliseconds
        $randPart = mt_rand(100, 999);

        // Menggabungkan bagian-bagian
        $combined = $hashPart . $timePart . $randPart;

        // Konversi ke base-17 (contoh, sesuaikan jika perlu) dan pastikan itu integer jika API memerlukannya
        // Base_convert mungkin tidak cocok untuk string panjang, gunakan cara lain jika perlu representasi base-17
        // Untuk saat ini, kita kembalikan sebagai string kombinasi saja, sesuaikan jika API Zeppelin memerlukan format spesifik
        // return base_convert($combined, 10, 17); // Ini bisa menghasilkan nilai non-numerik jika string terlalu panjang
        return $combined; // Kembalikan string kombinasi
    }
}
