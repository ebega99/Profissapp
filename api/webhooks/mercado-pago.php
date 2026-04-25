<?php
/**
 * Profissapp - Webhook Handler para Mercado Pago
 * Arquivo: api/webhooks/mercado-pago.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Carregamento de dependências
require_once __DIR__ . '/../config/environment.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../helpers/Auth.php';
require_once __DIR__ . '/../helpers/Logger.php';
require_once __DIR__ . '/../helpers/MercadoPago.php';
require_once __DIR__ . '/../models/Payment.php';
require_once __DIR__ . '/../models/User.php';

try {
    // Obter dados do webhook
    $headers = getallheaders();
    $body = file_get_contents('php://input');
    $data = json_decode($body, true);

    // Log do webhook recebido
    Logger::payment('WEBHOOK_RECEIVED', '', $data['id'] ?? 'unknown', $data);

    // Verificar tipo de evento
    $eventType = $_GET['type'] ?? $data['type'] ?? null;

    if (!$eventType) {
        Logger::error('Invalid webhook event', ['data' => $data]);
        http_response_code(400);
        echo json_encode(['error' => 'Tipo de evento não especificado']);
        exit;
    }

    // Validar assinatura (opcional, depende da configuração do Mercado Pago)
    $xSignature = $headers['X-Signature'] ?? null;
    $xRequestId = $headers['X-Request-ID'] ?? null;

    if ($xSignature && $xRequestId) {
        $mercadoPago = new MercadoPago(MERCADO_PAGO_ACCESS_TOKEN, MERCADO_PAGO_PUBLIC_KEY);

        if (!$mercadoPago->validateWebhookSignature($xSignature, $xRequestId, $body)) {
            Logger::error('Invalid webhook signature', ['x_signature' => $xSignature]);
            http_response_code(403);
            echo json_encode(['error' => 'Assinatura inválida']);
            exit;
        }
    }

    // Processar diferentes tipos de eventos
    $db = new Database();
    $conn = $db->connect();
    $paymentModel = new Payment($conn);
    $userModel = new User($conn);

    switch ($eventType) {
        case 'payment.created':
        case 'payment.updated':
            handlePaymentUpdate($data, $paymentModel, $userModel);
            break;

        case 'plan.updated':
            handlePlanUpdate($data);
            break;

        case 'subscription.updated':
            handleSubscriptionUpdate($data);
            break;

        default:
            Logger::payment('WEBHOOK_UNKNOWN_TYPE', '', $data['id'] ?? 'unknown', ['type' => $eventType]);
            break;
    }

    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Webhook processado']);
    exit;

} catch (Exception $e) {
    Logger::error('Webhook processing failed', ['error' => $e->getMessage()]);
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao processar webhook']);
    exit;
}

/**
 * Processar atualização de pagamento
 */
function handlePaymentUpdate($data, $paymentModel, $userModel) {
    try {
        $mercadoPagoId = $data['id'] ?? null;
        $status = $data['status'] ?? null;
        $externalReference = $data['external_reference'] ?? null;

        if (!$mercadoPagoId || !$externalReference) {
            throw new Exception('Dados incompletos no webhook');
        }

        // Encontrar pagamento no banco
        $payment = $paymentModel->getByMercadoPagoId($mercadoPagoId);

        if (!$payment) {
            // Tentar encontrar por referência externa
            $payment = $paymentModel->getById($externalReference);
        }

        if (!$payment) {
            Logger::error('Payment not found for webhook', ['mercado_pago_id' => $mercadoPagoId, 'external_reference' => $externalReference]);
            return;
        }

        // Atualizar status
        $localStatus = mapMercadoPagoStatus($status);
        $paymentModel->updateStatus($payment['payment_id'], $localStatus, $mercadoPagoId);
        $paymentModel->markWebhookReceived($payment['payment_id']);

        // Se aprovado, ativar Profissa
        if ($localStatus === 'approved') {
            $userModel->activateProfissa($payment['token']);
            Logger::payment('PAYMENT_APPROVED', $payment['token'], $payment['payment_id']);
        }

        // Marcar como processado
        $paymentModel->markWebhookProcessed($payment['payment_id']);

    } catch (Exception $e) {
        Logger::error('Payment update handler failed', ['error' => $e->getMessage()]);
        throw $e;
    }
}

/**
 * Processar atualização de plano
 */
function handlePlanUpdate($data) {
    try {
        // Implementar se necessário
        Logger::payment('WEBHOOK_PLAN_UPDATE', '', $data['id'] ?? 'unknown');
    } catch (Exception $e) {
        Logger::error('Plan update handler failed', ['error' => $e->getMessage()]);
        throw $e;
    }
}

/**
 * Processar atualização de subscrição
 */
function handleSubscriptionUpdate($data) {
    try {
        // Implementar se necessário
        Logger::payment('WEBHOOK_SUBSCRIPTION_UPDATE', '', $data['id'] ?? 'unknown');
    } catch (Exception $e) {
        Logger::error('Subscription update handler failed', ['error' => $e->getMessage()]);
        throw $e;
    }
}

/**
 * Mapear status do Mercado Pago para status local
 */
function mapMercadoPagoStatus($status) {
    $map = [
        'approved' => 'approved',
        'pending' => 'pending',
        'authorized' => 'pending',
        'in_process' => 'pending',
        'in_mediation' => 'pending',
        'rejected' => 'failed',
        'cancelled' => 'cancelled',
        'refunded' => 'refunded',
        'charged_back' => 'failed'
    ];

    return $map[$status] ?? 'pending';
}
