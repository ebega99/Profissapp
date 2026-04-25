<?php
/**
 * Profissapp - Helper de Logging
 * Arquivo: api/helpers/Logger.php
 */

class Logger {
    /**
     * Log de erro
     */
    public static function error($message, $data = null) {
        self::log('ERROR', $message, $data, LOG_ERROR_FILE);
    }

    /**
     * Log de API
     */
    public static function api($action, $token = null, $statusCode = null, $data = null) {
        $message = "Action: $action | Token: " . ($token ?: 'N/A') . " | Status: " . ($statusCode ?: 'N/A');
        self::log('API', $message, $data, LOG_API_FILE);
    }

    /**
     * Log de pagamento
     */
    public static function payment($action, $token, $paymentId = null, $data = null) {
        $message = "Action: $action | Token: $token | Payment ID: " . ($paymentId ?: 'N/A');
        self::log('PAYMENT', $message, $data, LOG_PAYMENT_FILE);
    }

    /**
     * Log genérico
     */
    private static function log($level, $message, $data = null, $file = null) {
        if ($file === null) {
            $file = LOG_ERROR_FILE;
        }

        $timestamp = date('Y-m-d H:i:s');
        $ip = self::getClientIP();
        
        $logMessage = "[$timestamp] [$level] [$ip] $message";
        
        if ($data !== null) {
            $logMessage .= "\nData: " . json_encode($data, JSON_PRETTY_PRINT);
        }
        
        $logMessage .= "\n" . str_repeat('-', 80) . "\n";

        // Escrever no arquivo
        if (!file_exists(dirname($file))) {
            mkdir(dirname($file), 0755, true);
        }

        file_put_contents($file, $logMessage, FILE_APPEND);
    }

    /**
     * Obter IP do cliente
     */
    private static function getClientIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        }

        return trim($ip);
    }
}
