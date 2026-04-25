<?php
/**
 * Profissapp - Model: Payment
 * Arquivo: api/models/Payment.php
 */

class Payment {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    /**
     * Criar novo pagamento
     */
    public function create($token, $amount = PAYMENT_AMOUNT, $currency = PAYMENT_CURRENCY) {
        try {
            $paymentId = 'PAY_' . time() . '_' . substr(uniqid(), -6);
            
            $query = "INSERT INTO payments (token, payment_id, amount, currency, status) VALUES (?, ?, ?, ?, 'pending')";
            $stmt = Database::executeQuery($this->conn, $query, [$token, $paymentId, $amount, $currency], 'ssds');
            
            Logger::payment('CREATE', $token, $paymentId, ['amount' => $amount, 'currency' => $currency]);
            
            return [
                'id' => Database::getLastId($this->conn),
                'payment_id' => $paymentId,
                'token' => $token,
                'amount' => $amount,
                'currency' => $currency,
                'status' => 'pending'
            ];
        } catch (Exception $e) {
            Logger::error('Payment creation failed', ['token' => $token, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Obter pagamento por ID
     */
    public function getById($paymentId) {
        try {
            $query = "SELECT * FROM payments WHERE payment_id = ?";
            $stmt = Database::executeQuery($this->conn, $query, [$paymentId], 's');
            $result = Database::fetchOne($stmt);
            $stmt->close();
            
            return $result;
        } catch (Exception $e) {
            Logger::error('Payment fetch failed', ['payment_id' => $paymentId, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Obter pagamentos por token
     */
    public function getByToken($token) {
        try {
            $query = "SELECT * FROM payments WHERE token = ? ORDER BY created_at DESC";
            $stmt = Database::executeQuery($this->conn, $query, [$token], 's');
            $result = Database::fetchArray($stmt);
            $stmt->close();
            
            return $result;
        } catch (Exception $e) {
            Logger::error('Payments fetch failed', ['token' => $token, 'error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Obter pagamento por Mercado Pago ID
     */
    public function getByMercadoPagoId($mercadoPagoId) {
        try {
            $query = "SELECT * FROM payments WHERE mercado_pago_id = ?";
            $stmt = Database::executeQuery($this->conn, $query, [$mercadoPagoId], 's');
            $result = Database::fetchOne($stmt);
            $stmt->close();
            
            return $result;
        } catch (Exception $e) {
            Logger::error('Payment fetch by MP ID failed', ['mercado_pago_id' => $mercadoPagoId, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Atualizar status de pagamento
     */
    public function updateStatus($paymentId, $status, $mercadoPagoId = null) {
        try {
            $query = "UPDATE payments SET status = ?, mercado_pago_id = ?, updated_at = NOW()";
            
            if ($status === 'approved') {
                $query .= ", paid_at = NOW()";
            }
            
            $query .= " WHERE payment_id = ?";
            
            $params = [$status, $mercadoPagoId, $paymentId];
            $types = 'sss';
            
            $stmt = Database::executeQuery($this->conn, $query, $params, $types);
            
            if (Database::getAffectedRows($this->conn) > 0) {
                Logger::payment('UPDATE_STATUS', '', $paymentId, ['status' => $status]);
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            Logger::error('Payment status update failed', ['payment_id' => $paymentId, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Salvar QR Code do Mercado Pago
     */
    public function updateQRCode($paymentId, $qrCode) {
        try {
            $qrCodeJson = is_array($qrCode) ? json_encode($qrCode) : $qrCode;
            
            $query = "UPDATE payments SET mercado_pago_qr = ?, updated_at = NOW() WHERE payment_id = ?";
            $stmt = Database::executeQuery($this->conn, $query, [$qrCodeJson, $paymentId], 'ss');
            
            return Database::getAffectedRows($this->conn) > 0;
        } catch (Exception $e) {
            Logger::error('QR Code update failed', ['payment_id' => $paymentId, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Registrar webhook recebido
     */
    public function markWebhookReceived($paymentId) {
        try {
            $query = "UPDATE payments SET webhook_received = TRUE WHERE payment_id = ?";
            $stmt = Database::executeQuery($this->conn, $query, [$paymentId], 's');
            
            return Database::getAffectedRows($this->conn) > 0;
        } catch (Exception $e) {
            Logger::error('Webhook received mark failed', ['payment_id' => $paymentId, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Registrar webhook processado
     */
    public function markWebhookProcessed($paymentId) {
        try {
            $query = "UPDATE payments SET webhook_processed = TRUE WHERE payment_id = ?";
            $stmt = Database::executeQuery($this->conn, $query, [$paymentId], 's');
            
            return Database::getAffectedRows($this->conn) > 0;
        } catch (Exception $e) {
            Logger::error('Webhook processed mark failed', ['payment_id' => $paymentId, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Obter pagamentos pendentes
     */
    public function getPending() {
        try {
            $query = "SELECT * FROM payments WHERE status = 'pending' ORDER BY created_at DESC";
            $stmt = $this->conn->query($query);
            $result = $stmt->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
            return $result;
        } catch (Exception $e) {
            Logger::error('Pending payments fetch failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Obter estatísticas de pagamentos
     */
    public function getStatistics() {
        try {
            $query = "
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                    SUM(CASE WHEN status = 'approved' THEN amount ELSE 0 END) as total_revenue
                FROM payments
            ";
            
            $stmt = $this->conn->query($query);
            $result = $stmt->fetch_assoc();
            $stmt->close();
            
            return $result;
        } catch (Exception $e) {
            Logger::error('Statistics calculation failed', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
