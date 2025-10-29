<?php

namespace App\Libraries;

use Config\Orderkuota as OrderkuotaConfig;
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
    protected $httpClient; // HTTP Client instance without base_uri
    protected OrderkuotaConfig $config;
    protected array $authPayload;

    /**
     * Constructor.
     *
     * @param OrderkuotaConfig|null $config Configuration object.
     * @param \CodeIgniter\HTTP\ClientInterface|null  $client HTTP Client instance.
     */
    public function __construct(?OrderkuotaConfig $config = null, ?\CodeIgniter\HTTP\ClientInterface $client = null)
    {
        $this->config = $config ?? config('Orderkuota'); // Ensure config is loaded

        log_message('debug', '[ZeppelinClient Constructor] Using API URL: "' . ($this->config->apiUrl ?? 'NULL') . '"');

        // Initialize HTTP client without base_uri
        $this->httpClient = $client ?? \Config\Services::curlrequest([
            // 'base_uri' is removed here
            'timeout'     => 15, // Set default timeout
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
        $fullUrl = rtrim($this->config->apiUrl, '/') . $endpoint; // Create full URL
        $params   = [
            'reference_id' => $referenceId,
            'amount'       => $amount,
            'expiry'       => $this->config->expiryTime,
        ];

        log_message('debug', '[ZeppelinClient] Creating payment. URL: ' . $fullUrl . ' Ref ID: ' . $referenceId . ', Amount: ' . $amount . ', Expiry: ' . $this->config->expiryTime . ' mins');

        try {
            // Use full URL in the post request
            $response = $this->httpClient->post($fullUrl, [
                'query' => $params,
                'json'  => $this->authPayload,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ]
            ]);

            return $this->handleResponse($response, 'createPayment', $referenceId);
        } catch (Throwable $e) {
            log_message('error', '[ZeppelinClient Exception - createPayment] Ref ID: ' . $referenceId . ' Error Type: '. get_class($e) . ' Code: ' . $e->getCode() . ' Message: ' . $e->getMessage());
            $errorMessage = 'Gagal membuat pembayaran via Zeppelin API.';
            if (ENVIRONMENT !== 'production') {
                $errorMessage .= ' Detail: ' . $e->getCode() . ' : ' . $e->getMessage();
            }
            throw new \RuntimeException($errorMessage, $e->getCode(), $e);
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
        $fullUrl = rtrim($this->config->apiUrl, '/') . $endpoint; // Create full URL

        log_message('debug', '[ZeppelinClient] Checking status. URL: ' . $fullUrl . ' Ref ID: ' . $referenceId);

        try {
            // Use full URL in the post request
            $response = $this->httpClient->post($fullUrl, [
                'json' => $this->authPayload,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ]
            ]);

            return $this->handleResponse($response, 'checkStatus', $referenceId);
        } catch (Throwable $e) {
            log_message('error', '[ZeppelinClient Exception - checkStatus] Ref ID: ' . $referenceId . ' Error Type: '. get_class($e) . ' Code: ' . $e->getCode() . ' Message: ' . $e->getMessage());
            $errorMessage = 'Gagal memeriksa status pembayaran via Zeppelin API.';
             if (ENVIRONMENT !== 'production') {
                $errorMessage .= ' Detail: ' . $e->getCode() . ' : ' . $e->getMessage();
            }
            throw new \RuntimeException($errorMessage, $e->getCode(), $e);
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
        $fullUrl = rtrim($this->config->apiUrl, '/') . $endpoint; // Create full URL

        log_message('debug', '[ZeppelinClient] Cancelling payment. URL: ' . $fullUrl . ' Ref ID: ' . $referenceId);

        try {
            // Use full URL in the post request
            $response = $this->httpClient->post($fullUrl, [
                'json' => $this->authPayload,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ]
            ]);

            return $this->handleResponse($response, 'cancelPayment', $referenceId);
        } catch (Throwable $e) {
            log_message('error', '[ZeppelinClient Exception - cancelPayment] Ref ID: ' . $referenceId . ' Error Type: '. get_class($e) . ' Code: ' . $e->getCode() . ' Message: ' . $e->getMessage());
            $errorMessage = 'Gagal membatalkan pembayaran via Zeppelin API.';
            if (ENVIRONMENT !== 'production') {
                $errorMessage .= ' Detail: ' . $e->getCode() . ' : ' . $e->getMessage();
            }
            throw new \RuntimeException($errorMessage, $e->getCode(), $e);
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
            $exceptionMessage = (is_array($decoded) && !empty($decoded['message'])) ? $decoded['message'] : 'Terjadi kesalahan saat menghubungi API pembayaran.';
            if(ENVIRONMENT !== 'production') $exceptionMessage .= " (HTTP {$statusCode})";
            throw new \RuntimeException($exceptionMessage, $statusCode);
        }

        // Handle JSON decoding errors
        if (json_last_error() !== JSON_ERROR_NONE) {
            log_message('error', "[ZeppelinClient JSON Error - {$methodName}] Ref ID: {$referenceId} | Error: " . json_last_error_msg() . " | Body: {$body}");
            throw new \RuntimeException('Gagal memproses response dari API (JSON tidak valid).', $statusCode);
        }

        // Periksa struktur response dasar
        if (!isset($decoded['success'])) {
             log_message('error', "[ZeppelinClient Structure Error - {$methodName}] Ref ID: {$referenceId} | Field 'success' tidak ditemukan dalam response: {$body}");
             throw new \RuntimeException("Format response API tidak dikenali (missing 'success' field).", $statusCode);
        }

        return $decoded;
    }

    /**
     * Helper untuk menghasilkan reference ID unik (opsional).
     *
     * @param string|int $seed Seed untuk ID.
     * @return string Reference ID yang dihasilkan.
     */
    public static function generateReferenceID($seed): string
    {
        $hashPart = substr(preg_replace('/\D/', '', sha1((string)$seed)), 0, 6) ?: substr(str_shuffle("0123456789"), 0, 6);
        $timePart = substr((string)floor(microtime(true) * 1000), -6);
        $randPart = mt_rand(100, 999);
        $combined = $hashPart . $timePart . $randPart;
        return (string)$combined;
    }
}

