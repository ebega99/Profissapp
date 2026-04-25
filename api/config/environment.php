<?php
/**
 * Profissapp - Variáveis de Ambiente
 * Arquivo: api/config/environment.php
 */

// ============================================
// AMBIENTE
// ============================================
define('APP_ENV', getenv('APP_ENV') ?: 'production');
define('APP_DEBUG', APP_ENV === 'development');

// ============================================
// APLICAÇÃO
// ============================================
define('APP_NAME', 'Profissapp');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'https://profissapp.fibra99.com');
define('FRONTEND_URL', 'http://localhost:5174');

// ============================================
// BANCO DE DADOS (já definido em database.php)
// ============================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'profissapp');
define('DB_USER', 'ebega99');
define('DB_PASS', 'Gorila93@');

// ============================================
// MERCADO PAGO
// ============================================
// IMPORTANTE: Substituir com suas credenciais reais
define('MERCADO_PAGO_PUBLIC_KEY', getenv('MERCADO_PAGO_PUBLIC_KEY') ?: 'SEU_PUBLIC_KEY_AQUI');
define('MERCADO_PAGO_ACCESS_TOKEN', getenv('MERCADO_PAGO_ACCESS_TOKEN') ?: 'SEU_ACCESS_TOKEN_AQUI');

// URLs de Webhook
define('WEBHOOK_URL', APP_URL . '/api/webhooks/mercado-pago.php');
define('WEBHOOK_NOTIFICATION_URL', APP_URL . '/api/webhooks/notification.php');

// ============================================
// CONFIGURAÇÕES DE PAGAMENTO
// ============================================
define('PAYMENT_AMOUNT', 5.00); // R$ 5,00
define('PAYMENT_CURRENCY', 'BRL');
define('PAYMENT_DESCRIPTION', 'Assinatura Profissapp - 30 dias');
define('PAYMENT_INSTALLMENTS', 1);

// ============================================
// TOKENS E SEGURANÇA
// ============================================
define('TOKEN_PREFIX', 'PF');
define('TOKEN_LENGTH', 12);
define('SECRET_KEY', getenv('SECRET_KEY') ?: 'sua_chave_secreta_super_segura_aqui_123456');

// ============================================
// LIMITES
// ============================================
define('MAX_RESTORATIONS_PER_MONTH', 3);
define('PROFISSA_DURATION_DAYS', 30);
define('DEMO_EXPORTS_PER_MONTH', 3);

// ============================================
// CORS
// ============================================
define('ALLOWED_ORIGINS', [
    'http://localhost:5174',
    'http://localhost:5173',
    'https://profissapp.fibra99.com'
]);

// ============================================
// LOGS
// ============================================
define('LOG_PATH', __DIR__ . '/../../logs/');
define('LOG_ERROR_FILE', LOG_PATH . 'errors.log');
define('LOG_API_FILE', LOG_PATH . 'api.log');
define('LOG_PAYMENT_FILE', LOG_PATH . 'payments.log');

// Criar diretório de logs se não existir
if (!is_dir(LOG_PATH)) {
    mkdir(LOG_PATH, 0755, true);
}
