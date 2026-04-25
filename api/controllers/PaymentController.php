<?php
/**
 * Profissapp - Controller: Payment
 * Arquivo: api/controllers/PaymentController.php
 */

class PaymentController {
    private $paymentModel;
    private $userModel;
    private $mercadoPago;

    public function __construct($conn, $mercadoPago) {
        $this->paymentModel = new Payment($conn);
        $this->userModel = new User($conn);
        $this->mercadoPago = $mercadoPago;
        $this->conn = $conn;
    }

    /**
     * Criar pagamento (POST /api/create-payment)
     */
    public function create() {
        try {
            $token = Auth::getToken();

            if (!$token) {
                return Response::error('Token não fornecido', 400);
            }

            $user = $this->userModel->getByToken($token);

            if (!$user) {
                return Response::error('Token não encontrado', 404);
            }

            // Criar pagamento local
            $payment = $this->paymentModel->create($token, PAYMENT_AMOUNT, PAYMENT_CURRENCY);

            // Criar pagamento no Mercado Pago
            $mpPaymentData = [
                'amount' => PAYMENT_AMOUNT,
                'description' => PAYMENT_DESCRIPTION,
                'external_reference' => $payment['payment_id'],
                'email' => $token . '@profissapp.com' // Email genérico baseado no token
            ];

            $mpResponse = $this->mercadoPago->createPayment($mpPaymentData);

            // Atualizar com dados do Mercado Pago
            $this->paymentModel->updateStatus($payment['payment_id'], 'pending', $mpResponse['id'] ?? null);

            if (isset($mpResponse['point_of_interaction']['transaction_data']['qr_code'])) {
                $this->paymentModel->updateQRCode($payment['payment_id'], $mpResponse['point_of_interaction']['transaction_data']);
            }

            Logger::payment('CREATE_PAYMENT', $token, $payment['payment_id'], ['amount' => PAYMENT_AMOUNT]);

            $response = [
                'payment_id' => $payment['payment_id'],
                'amount' => PAYMENT_AMOUNT,
                'currency' => PAYMENT_CURRENCY,
                'status' => 'pending',
                'mercado_pago_id' => $mpResponse['id'] ?? null,
                'qr_code' => $mpResponse['point_of_interaction']['transaction_data']['qr_code'] ?? null,
                'copy_paste' => $mpResponse['point_of_interaction']['transaction_data']['qr_code_base64'] ?? null
            ];

            return Response::success($response, 'Pagamento criado com sucesso', 201);
        } catch (Exception $e) {
            Logger::error('Payment creation failed', ['error' => $e->getMessage()]);
            return Response::error('Falha ao criar pagamento: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Obter status do pagamento (GET /api/payment/:payment_id)
     */
    public function getStatus($paymentId) {
        try {
            $token = Auth::getToken();

            if (!$token) {
                return Response::error('Token não fornecido', 400);
            }

            $payment = $this->paymentModel->getById($paymentId);

            if (!$payment) {
                return Response::error('Pagamento não encontrado', 404);
            }

            // Verificar se o pagamento pertence ao token
            if ($payment['token'] !== $token) {
                return Response::forbidden('Acesso negado a este pagamento');
            }

            Logger::api('GET_PAYMENT_STATUS', $token, 200);

            return Response::success([
                'payment_id' => $payment['payment_id'],
                'amount' => $payment['amount'],
                'currency' => $payment['currency'],
                'status' => $payment['status'],
                'created_at' => $payment['created_at'],
                'paid_at' => $payment['paid_at']
            ]);
        } catch (Exception $e) {
            Logger::error('Payment status fetch failed', ['payment_id' => $paymentId, 'error' => $e->getMessage()]);
            return Response::error('Erro ao obter pagamento', 500);
        }
    }

    /**
     * Listar pagamentos do usuário (GET /api/payments)
     */
    public function list() {
        try {
            $token = Auth::getToken();

            if (!$token) {
                return Response::error('Token não fornecido', 400);
            }

            $payments = $this->paymentModel->getByToken($token);

            Logger::api('LIST_PAYMENTS', $token, 200);

            return Response::success([
                'total' => count($payments),
                'payments' => $payments
            ]);
        } catch (Exception $e) {
            Logger::error('Payments list fetch failed', ['error' => $e->getMessage()]);
            return Response::error('Erro ao listar pagamentos', 500);
        }
    }

    /**
     * Confirmar pagamento manualmente (POST /api/confirm-payment)
     * Usado para webhook e validações
     */
    public function confirm() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);

            if (!isset($input['payment_id']) || !isset($input['mercado_pago_id'])) {
                return Response::error('Dados incompletos', 400);
            }

            $payment = $this->paymentModel->getById($input['payment_id']);

            if (!$payment) {
                return Response::error('Pagamento não encontrado', 404);
            }

            // Obter status do Mercado Pago
            $mpPayment = $this->mercadoPago->getPayment($input['mercado_pago_id']);

            if ($mpPayment && $mpPayment['status'] === 'approved') {
                // Atualizar pagamento
                $this->paymentModel->updateStatus($input['payment_id'], 'approved', $input['mercado_pago_id']);

                // Ativar Profissa do usuário
                if ($this->userModel->activateProfissa($payment['token'])) {
                    Logger::payment('CONFIRM_PAYMENT', $payment['token'], $input['payment_id']);

                    return Response::success([
                        'payment_id' => $input['payment_id'],
                        'status' => 'approved',
                        'message' => 'Pagamento confirmado e Profissa ativado'
                    ]);
                }
            }

            return Response::error('Não foi possível confirmar o pagamento', 400);
        } catch (Exception $e) {
            Logger::error('Payment confirmation failed', ['error' => $e->getMessage()]);
            return Response::error('Erro ao confirmar pagamento', 500);
        }
    }

    /**
     * Reembolsar pagamento (POST /api/refund-payment)
     */
    public function refund() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);

            if (!isset($input['payment_id'])) {
                return Response::error('Payment ID não fornecido', 400);
            }

            $token = Auth::getToken();
            $payment = $this->paymentModel->getById($input['payment_id']);

            if (!$payment) {
                return Response::error('Pagamento não encontrado', 404);
            }

            if ($payment['token'] !== $token) {
                return Response::forbidden('Acesso negado');
            }

            if ($payment['status'] !== 'approved') {
                return Response::error('Apenas pagamentos aprovados podem ser reembolsados', 400);
            }

            $amount = $input['amount'] ?? null;
            $mpRefund = $this->mercadoPago->refundPayment($payment['mercado_pago_id'], $amount);

            if ($mpRefund) {
                $newStatus = $amount ? 'pending' : 'refunded';
                $this->paymentModel->updateStatus($input['payment_id'], $newStatus, $payment['mercado_pago_id']);

                Logger::payment('REFUND_PAYMENT', $token, $input['payment_id'], ['amount' => $amount]);

                return Response::success([
                    'payment_id' => $input['payment_id'],
                    'refund_status' => 'processed',
                    'amount' => $amount ?? $payment['amount']
                ]);
            }

            return Response::error('Falha ao reembolsar pagamento', 500);
        } catch (Exception $e) {
            Logger::error('Payment refund failed', ['error' => $e->getMessage()]);
            return Response::error('Erro ao reembolsar pagamento', 500);
        }
    }

    /**
     * Obter estatísticas
     */
    public function statistics() {
        try {
            $stats = $this->paymentModel->getStatistics();

            Logger::api('GET_STATISTICS', null, 200);

            return Response::success($stats);
        } catch (Exception $e) {
            Logger::error('Statistics fetch failed', ['error' => $e->getMessage()]);
            return Response::error('Erro ao obter estatísticas', 500);
        }
    }
}
