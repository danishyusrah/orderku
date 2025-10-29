<?php

namespace App\Libraries;

class TripayClient
{
    private string $apiKey;
    private string $privateKey;
    private string $merchantCode;
    private bool   $isProduction;

    public function __construct(array $config)
    {
        $this->apiKey       = $config['apiKey'] ?? '';
        $this->privateKey   = $config['privateKey'] ?? '';
        $this->merchantCode = $config['merchantCode'] ?? '';
        $this->isProduction = (bool)($config['isProduction'] ?? false);
    }

    private function baseUrl(): string
    {
        return $this->isProduction ? 'https://tripay.co.id' : 'https://tripay.co.id'; // endpoint sama, mode di kredensial
    }

    /** Membuat transaksi Tripay (menghasilkan URL/QR tergantung channel).
     *  $payload minimal: method, merchant_ref, amount, customer_name, customer_email, order_items[], return_url, callback_url
     */
    public function createTransaction(array $payload): array
    {
        // signature pembuatan: HMAC_SHA256(merchant_code + merchant_ref + amount, privateKey)
        $signature = hash_hmac('sha256',
            $this->merchantCode . $payload['merchant_ref'] . (int)$payload['amount'],
            $this->privateKey
        );

        $payload['signature']      = $signature;
        $payload['merchant_code']  = $this->merchantCode;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $this->baseUrl() . '/api/transaction/create',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($payload),
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer '.$this->apiKey],
            CURLOPT_TIMEOUT        => 30
        ]);
        $res = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            throw new \RuntimeException('Tripay create error: '.$err);
        }

        $json = json_decode($res, true);
        if (!is_array($json)) {
            throw new \RuntimeException('Tripay invalid response: '.$res);
        }
        return $json;
    }

    /** Verifikasi callback Tripay.
     *  Signature callback: HMAC_SHA256(raw_body_json, privateKey) pada header X-Callback-Signature
     */
    public function verifyCallback(string $rawBody, string $signatureHeader): bool
    {
        $calc = hash_hmac('sha256', $rawBody, $this->privateKey);
        return hash_equals($calc, $signatureHeader);
    }
}
