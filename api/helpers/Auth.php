<?php
/**
 * Profissapp - Helper de Autenticação
 * Arquivo: api/helpers/Auth.php
 */

class Auth {
    /**
     * Obter token do header Authorization
     */
    public static function getToken() {
        $headers = getallheaders();
        
        if (isset($headers['Authorization'])) {
            $matches = [];
            if (preg_match('/Bearer\s+(.*)$/i', $headers['Authorization'], $matches)) {
                return trim($matches[1]);
            }
        }

        // Tentar obter do GET/POST
        if (isset($_GET['token'])) {
            return trim($_GET['token']);
        }
        if (isset($_POST['token'])) {
            return trim($_POST['token']);
        }

        return null;
    }

    /**
     * Gerar novo token
     */
    public static function generateToken() {
        $prefix = TOKEN_PREFIX;
        $timestamp = time();
        $random = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, TOKEN_LENGTH);
        
        return $prefix . $timestamp . $random;
    }

    /**
     * Validar formato do token
     */
    public static function isValidTokenFormat($token) {
        // Deve começar com PF, seguido de timestamp e aleatório
        return preg_match('/^PF\d{10}[A-Z0-9]{' . TOKEN_LENGTH . '}$/', $token) === 1;
    }

    /**
     * Gerar hash para requisição Mercado Pago (HMAC-SHA256)
     */
    public static function generateMercadoPagoSignature($requestBody, $secret) {
        return hash('sha256', $requestBody . $secret);
    }

    /**
     * Validar assinatura do webhook Mercado Pago
     */
    public static function validateMercadoPagoWebhook($signature, $requestBody, $secret) {
        $expectedSignature = self::generateMercadoPagoSignature($requestBody, $secret);
        
        // Comparar de forma segura
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Gerar PIN de 4 dígitos
     */
    public static function generatePIN() {
        return str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Encriptar dados sensíveis
     */
    public static function encrypt($data, $key = null) {
        if ($key === null) {
            $key = SECRET_KEY;
        }
        
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('AES-256-CBC'));
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
        
        return base64_encode($iv . $encrypted);
    }

    /**
     * Descriptografar dados
     */
    public static function decrypt($data, $key = null) {
        if ($key === null) {
            $key = SECRET_KEY;
        }
        
        $data = base64_decode($data);
        $iv = substr($data, 0, openssl_cipher_iv_length('AES-256-CBC'));
        $encrypted = substr($data, openssl_cipher_iv_length('AES-256-CBC'));
        
        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
    }
}
