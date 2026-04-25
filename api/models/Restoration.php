<?php
/**
 * Profissapp - Model: Restoration
 * Arquivo: api/models/Restoration.php
 */

class Restoration {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    /**
     * Criar nova restauração
     */
    public function create($token, $deviceIdentifier, $deviceName, $ipAddress, $userAgent) {
        try {
            $month = date('n');
            $year = date('Y');

            $query = "
                INSERT INTO restorations (token, device_identifier, device_name, ip_address, user_agent, month, year)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ";

            $stmt = Database::executeQuery(
                $this->conn,
                $query,
                [$token, $deviceIdentifier, $deviceName, $ipAddress, $userAgent, $month, $year],
                'sssssii'
            );

            Logger::api('CREATE_RESTORATION', $token, 201);

            return [
                'id' => Database::getLastId($this->conn),
                'token' => $token,
                'device_identifier' => $deviceIdentifier
            ];
        } catch (Exception $e) {
            Logger::error('Restoration creation failed', ['token' => $token, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Obter restauração por dispositivo e mês
     */
    public function getByDeviceAndMonth($deviceIdentifier, $month, $year) {
        try {
            $query = "
                SELECT * FROM restorations
                WHERE device_identifier = ? AND month = ? AND year = ?
                LIMIT 1
            ";

            $stmt = Database::executeQuery($this->conn, $query, [$deviceIdentifier, $month, $year], 'sii');
            $result = Database::fetchOne($stmt);
            $stmt->close();

            return $result;
        } catch (Exception $e) {
            Logger::error('Restoration fetch failed', ['device_identifier' => $deviceIdentifier, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Obter todas as restaurações de um token
     */
    public function getByToken($token) {
        try {
            $query = "SELECT * FROM restorations WHERE token = ? ORDER BY restored_at DESC";

            $stmt = Database::executeQuery($this->conn, $query, [$token], 's');
            $result = Database::fetchArray($stmt);
            $stmt->close();

            return $result;
        } catch (Exception $e) {
            Logger::error('Restorations fetch failed', ['token' => $token, 'error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Deletar restauração
     */
    public function delete($id) {
        try {
            $query = "DELETE FROM restorations WHERE id = ?";
            $stmt = Database::executeQuery($this->conn, $query, [$id], 'i');

            return Database::getAffectedRows($this->conn) > 0;
        } catch (Exception $e) {
            Logger::error('Restoration deletion failed', ['id' => $id, 'error' => $e->getMessage()]);
            return false;
        }
    }
}
