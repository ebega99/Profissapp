<?php
/**
 * Profissapp - Model: User
 * Arquivo: api/models/User.php
 */

class User {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    /**
     * Criar novo usuário (token)
     */
    public function create($token, $status = 'demo') {
        try {
            $query = "INSERT INTO users (token, status) VALUES (?, ?)";
            $stmt = Database::executeQuery($this->conn, $query, [$token, $status], 'ss');
            
            return [
                'success' => true,
                'token' => $token,
                'status' => $status,
                'created_at' => date('c')
            ];
        } catch (Exception $e) {
            Logger::error('User creation failed', ['token' => $token, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Obter usuário por token
     */
    public function getByToken($token) {
        try {
            $query = "SELECT * FROM users WHERE token = ?";
            $stmt = Database::executeQuery($this->conn, $query, [$token], 's');
            $result = Database::fetchOne($stmt);
            $stmt->close();
            
            return $result;
        } catch (Exception $e) {
            Logger::error('User fetch failed', ['token' => $token, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Obter todos os usuários
     */
    public function getAll() {
        try {
            $query = "SELECT * FROM users ORDER BY created_at DESC";
            $stmt = $this->conn->query($query);
            $result = $stmt->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
            return $result;
        } catch (Exception $e) {
            Logger::error('Users fetch failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Ativar Profissa
     */
    public function activateProfissa($token, $daysValid = PROFISSA_DURATION_DAYS) {
        try {
            $expiresAt = date('Y-m-d H:i:s', strtotime("+{$daysValid} days"));
            
            $query = "UPDATE users SET status = ?, status_activated_at = NOW(), status_expires_at = ?, restorations_used = 0 WHERE token = ?";
            $stmt = Database::executeQuery($this->conn, $query, ['profissa', $expiresAt, $token], 'sss');
            
            if (Database::getAffectedRows($this->conn) > 0) {
                Logger::api('ACTIVATE_PROFISSA', $token, 200);
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            Logger::error('Profissa activation failed', ['token' => $token, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Incrementar contador de restaurações
     */
    public function incrementRestorations($token) {
        try {
            $query = "UPDATE users SET restorations_used = restorations_used + 1 WHERE token = ?";
            $stmt = Database::executeQuery($this->conn, $query, [$token], 's');
            
            if (Database::getAffectedRows($this->conn) > 0) {
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            Logger::error('Restorations increment failed', ['token' => $token, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Resetar contador de restaurações (mensal)
     */
    public function resetMonthlyRestorations($token) {
        try {
            $query = "UPDATE users SET restorations_used = 0 WHERE token = ?";
            $stmt = Database::executeQuery($this->conn, $query, [$token], 's');
            
            return Database::getAffectedRows($this->conn) > 0;
        } catch (Exception $e) {
            Logger::error('Restorations reset failed', ['token' => $token, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Obter status atual
     */
    public function getStatus($token) {
        try {
            $user = $this->getByToken($token);
            
            if (!$user) {
                return null;
            }

            // Se Profissa expirou, reverter para demo
            if ($user['status'] === 'profissa' && $user['status_expires_at'] < date('Y-m-d H:i:s')) {
                $this->resetToDemoStatus($token);
                return 'demo';
            }

            return $user['status'];
        } catch (Exception $e) {
            Logger::error('Status check failed', ['token' => $token, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Reverter para status demo
     */
    public function resetToDemoStatus($token) {
        try {
            $query = "UPDATE users SET status = ?, status_expires_at = NULL, restorations_used = 0 WHERE token = ?";
            $stmt = Database::executeQuery($this->conn, $query, ['demo', $token], 'ss');
            
            return Database::getAffectedRows($this->conn) > 0;
        } catch (Exception $e) {
            Logger::error('Reset to demo failed', ['token' => $token, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Obter dias restantes
     */
    public function getDaysRemaining($token) {
        try {
            $user = $this->getByToken($token);
            
            if (!$user || $user['status'] !== 'profissa' || !$user['status_expires_at']) {
                return 0;
            }

            $expiresAt = new DateTime($user['status_expires_at']);
            $now = new DateTime();
            $interval = $now->diff($expiresAt);
            
            if ($interval->invert === 1) {
                return 0; // Já expirou
            }

            return $interval->days;
        } catch (Exception $e) {
            Logger::error('Days remaining calculation failed', ['token' => $token, 'error' => $e->getMessage()]);
            return 0;
        }
    }

    /**
     * Obter restorations disponíveis
     */
    public function getAvailableRestorations($token) {
        try {
            $user = $this->getByToken($token);
            
            if (!$user) {
                return 0;
            }

            $used = (int)$user['restorations_used'];
            $available = max(0, MAX_RESTORATIONS_PER_MONTH - $used);
            
            return $available;
        } catch (Exception $e) {
            Logger::error('Available restorations check failed', ['token' => $token, 'error' => $e->getMessage()]);
            return 0;
        }
    }

    /**
     * Deletar usuário
     */
    public function delete($token) {
        try {
            $query = "DELETE FROM users WHERE token = ?";
            $stmt = Database::executeQuery($this->conn, $query, [$token], 's');
            
            return Database::getAffectedRows($this->conn) > 0;
        } catch (Exception $e) {
            Logger::error('User deletion failed', ['token' => $token, 'error' => $e->getMessage()]);
            return false;
        }
    }
}
