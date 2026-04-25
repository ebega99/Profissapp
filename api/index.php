<?php
/**
 * Profissapp - API Entry Point
 * Arquivo: api/index.php
 */

// Headers CORS
header('Access-Control-Allow-Origin: ' . (in_array($_SERVER['HTTP_ORIGIN'] ?? '', ALLOWED_ORIGINS) ? $_SERVER['HTTP_ORIGIN'] : ALLOWED_ORIGINS[0]));
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Carregamento de dependências
require_once __DIR__ . '/config/environment.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/Response.php';
require_once __DIR__ . '/helpers/Auth.php';
require_once __DIR__ . '/helpers/Logger.php';
require_once __DIR__ . '/helpers/MercadoPago.php';
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/models/Payment.php';
require_once __DIR__ . '/models/Restoration.php';
require_once __DIR__ . '/controllers/TokenController.php';
require_once __DIR__ . '/controllers/PaymentController.php';

try {
    // Conectar ao banco de dados
    $db = new Database();
    $conn = $db->connect();

    // Inicializar Mercado Pago
    $mercadoPago = new MercadoPago(MERCADO_PAGO_ACCESS_TOKEN, MERCADO_PAGO_PUBLIC_KEY);

    // Parser da URL
    $request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $request_uri = str_replace('/api', '', $request_uri);
    $request_method = $_SERVER['REQUEST_METHOD'];

    // Log de requisição
    Logger::api(
        $request_method . ' ' . $request_uri,
        Auth::getToken(),
        null
    );

    // Router de endpoints
    switch (true) {
        // ============================================
        // TOKENS
        // ============================================
        case $request_method === 'POST' && $request_uri === '/generate-token':
            $controller = new TokenController($conn);
            $controller->generate();
            break;

        case $request_method === 'POST' && $request_uri === '/validate-token':
            $controller = new TokenController($conn);
            $controller->validate();
            break;

        case $request_method === 'POST' && $request_uri === '/restore-token':
            $controller = new TokenController($conn);
            $controller->restore();
            break;

        case $request_method === 'POST' && $request_uri === '/delete-token':
            $controller = new TokenController($conn);
            $controller->delete();
            break;

        // ============================================
        // PAYMENTS
        // ============================================
        case $request_method === 'POST' && $request_uri === '/create-payment':
            $controller = new PaymentController($conn, $mercadoPago);
            $controller->create();
            break;

        case $request_method === 'GET' && preg_match('/^\/payment\/(.+)$/', $request_uri, $matches):
            $controller = new PaymentController($conn, $mercadoPago);
            $controller->getStatus($matches[1]);
            break;

        case $request_method === 'GET' && $request_uri === '/payments':
            $controller = new PaymentController($conn, $mercadoPago);
            $controller->list();
            break;

        case $request_method === 'POST' && $request_uri === '/confirm-payment':
            $controller = new PaymentController($conn, $mercadoPago);
            $controller->confirm();
            break;

        case $request_method === 'POST' && $request_uri === '/refund-payment':
            $controller = new PaymentController($conn, $mercadoPago);
            $controller->refund();
            break;

        case $request_method === 'GET' && $request_uri === '/statistics':
            $controller = new PaymentController($conn, $mercadoPago);
            $controller->statistics();
            break;

        // ============================================
        // HEALTH CHECK
        // ============================================
        case $request_method === 'GET' && $request_uri === '/health':
            Response::success(['status' => 'online'], 'API is running');
            break;

        case $request_method === 'GET' && $request_uri === '/':
            Response::success(
                [
                    'name' => APP_NAME,
                    'version' => APP_VERSION,
                    'environment' => APP_ENV,
                    'timestamp' => date('c')
                ],
                'Profissapp API'
            );
            break;

        // ============================================
        // 404 - Não encontrado
        // ============================================
        default:
            Response::notFound('Endpoint não encontrado: ' . $request_method . ' ' . $request_uri);
            break;
    }

} catch (Exception $e) {
    Logger::error('API Error', ['error' => $e->getMessage()]);
    Response::internalError('Erro no servidor: ' . (APP_DEBUG ? $e->getMessage() : 'Erro interno'));
}

$db->closeConnection();
