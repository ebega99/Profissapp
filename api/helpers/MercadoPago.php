<?php
/**
 * Profissapp - Integração com Mercado Pago
 * Arquivo: api/helpers/MercadoPago.php
 */

class MercadoPago {
    private $accessToken;
    private $publicKey;
    private $apiUrl = 'https://api.mercadopago.com';

    public function __construct($accessToken, $publicKey) {
        $this->accessToken = $accessToken;
        $this->publicKey = $publicKey;
    }

    /**
     * Criar pagamento via Mercado Pago
     */
    public function createPayment($paymentData) {
        try {
            $url = $this->apiUrl . '/v1/payments';

            $payload = [
                'transaction_amount' => (float)$paymentData['amount'],
                'description' => $paymentData['description'] ?? PAYMENT_DESCRIPTION,
                'payment_method_id' => 'pix',
                'payer' => [
                    'email' => $paymentData['email'] ?? 'profissional@example.com'
                ],
                'external_reference' => $paymentData['external_reference'] ?? '',
                'notification_url' => WEBHOOK_NOTIFICATION_URL
            ];

            $response = $this->makeRequest('POST', $url, $payload);

            if ($response && isset($response['id'])) {
                Logger::payment('MP_CREATE', $paymentData['external_reference'], $response['id'], $response);
                return $response;
            }

            throw new Exception('Falha ao criar pagamento no Mercado Pago');
        } catch (Exception $e) {
            Logger::error('Mercado Pago creation failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Obter detalhes do pagamento
     */
    public function getPayment($paymentId) {
        try {
            $url = $this->apiUrl . '/v1/payments/' . $paymentId;
            $response = $this->makeRequest('GET', $url);

            if ($response) {
                return $response;
            }

            throw new Exception('Pagamento não encontrado no Mercado Pago');
        } catch (Exception $e) {
            Logger::error('Mercado Pago fetch failed', ['payment_id' => $paymentId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Reembolsar pagamento
     */
    public function refundPayment($paymentId, $amount = null) {
        try {
            $url = $this->apiUrl . '/v1/payments/' . $paymentId . '/refunds';

            $payload = [];
            if ($amount !== null) {
                $payload['amount'] = (float)$amount;
            }

            $response = $this->makeRequest('POST', $url, $payload);

            if ($response) {
                Logger::payment('MP_REFUND', '', $paymentId, ['amount' => $amount]);
                return $response;
            }

            throw new Exception('Falha ao reembolsar pagamento');
        } catch (Exception $e) {
            Logger::error('Mercado Pago refund failed', ['payment_id' => $paymentId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Validar webhook assinatura
     */
    public function validateWebhookSignature($xSignature, $xRequestId, $requestBody) {
        try {
            // Formato esperado: ts=timestamp,v1=signature
            $parts = explode(',', $xSignature);
            $signature = null;
            $timestamp = null;

            foreach ($parts as $part) {
                if (strpos($part, 'v1=') === 0) {
                    $signature = substr($part, 3);
                } elseif (strpos($part, 'ts=') === 0) {
                    $timestamp = substr($part, 3);
                }
            }

            if (!$signature || !$timestamp) {
                throw new Exception('Formato de assinatura inválido');
            }

            // Verificar timestamp (máximo 10 minutos)
            $currentTime = time();
            if (($currentTime - $timestamp) > 600) {
                throw new Exception('Webhook expirado');
            }

            // Construir string para validação
            $validationString = $xRequestId . '.' . $requestBody;
            $expectedSignature = hash_hmac('sha256', $validationString, $this->accessToken);

            if (!hash_equals($expectedSignature, $signature)) {
                throw new Exception('Assinatura inválida');
            }

            return true;
        } catch (Exception $e) {
            Logger::error('Webhook signature validation failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Fazer requisição HTTP
     */
    private function makeRequest($method, $url, $data = null) {
        try {
            $curl = curl_init();

            $headers = [
                'Authorization: Bearer ' . $this->accessToken,
                'Content-Type: application/json'
            ];

            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_SSL_VERIFYPEER => true
            ]);

            if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
            }

            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $error = curl_error($curl);

            curl_close($curl);

            if ($error) {
                throw new Exception('cURL Error: ' . $error);
            }

            if ($httpCode >= 400) {
                $errorData = json_decode($response, true);
                throw new Exception('HTTP ' . $httpCode . ': ' . ($errorData['message'] ?? 'Erro desconhecido'));
            }

            return json_decode($response, true);
        } catch (Exception $e) {
            Logger::error('Mercado Pago request failed', ['url' => $url, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Gerar código PIX para transferência
     */
    public function generatePixQRCode($amount, $description = '') {
        try {
            // Nota: Isso seria feito através da resposta do pagamento criado
            // O QR Code é retornado na resposta da criação do pagamento
            // Este método é apenas para referência
            return true;
        } catch (Exception $e) {
            Logger::error('PIX QR code generation failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
