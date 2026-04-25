<?php
/**
 * Profissapp - Configuração do Banco de Dados
 * Arquivo: api/config/database.php
 */

class Database {
    private $host = 'localhost';
    private $db_name = 'profissapp';
    private $db_user = 'ebega99';
    private $db_pass = 'Gorila93@';
    private $conn;

    /**
     * Conectar ao banco de dados
     */
    public function connect() {
        $this->conn = new mysqli(
            $this->host,
            $this->db_user,
            $this->db_pass,
            $this->db_name
        );

        // Configurar charset
        if (!$this->conn->set_charset('utf8mb4')) {
            throw new Exception('Erro ao definir charset: ' . $this->conn->error);
        }

        if ($this->conn->connect_error) {
            throw new Exception('Erro na conexão: ' . $this->conn->connect_error);
        }

        return $this->conn;
    }

    /**
     * Obter conexão existente
     */
    public function getConnection() {
        return $this->conn;
    }

    /**
     * Fechar conexão
     */
    public function closeConnection() {
        if ($this->conn) {
            $this->conn->close();
        }
    }

    /**
     * Executar query preparada
     */
    public static function executeQuery($conn, $query, $params = [], $types = '') {
        try {
            $stmt = $conn->prepare($query);
            
            if (!$stmt) {
                throw new Exception('Erro ao preparar query: ' . $conn->error);
            }

            if (!empty($params) && !empty($types)) {
                $stmt->bind_param($types, ...$params);
            }

            if (!$stmt->execute()) {
                throw new Exception('Erro ao executar query: ' . $stmt->error);
            }

            return $stmt;
        } catch (Exception $e) {
            error_log('Database Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obter resultado como array
     */
    public static function fetchArray($stmt) {
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Obter uma linha
     */
    public static function fetchOne($stmt) {
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    /**
     * Obter última ID inserida
     */
    public static function getLastId($conn) {
        return $conn->insert_id;
    }

    /**
     * Obter linhas afetadas
     */
    public static function getAffectedRows($conn) {
        return $conn->affected_rows;
    }
}

// Criar instância global de conexão
$db = new Database();
try {
    $db->connect();
} catch (Exception $e) {
    error_log('Database Connection Error: ' . $e->getMessage());
}
