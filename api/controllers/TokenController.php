<?php
/**
 * Profissapp - Controller: Token
 * Arquivo: api/controllers/TokenController.php
 */

class TokenController {
    private $userModel;
    private $restorationModel;

    public function __construct($conn) {
        $this->userModel = new User($conn);
        require_once __DIR__ . '/../models/Restoration.php';
        $this->restorationModel = new Restoration($conn);
    }

    /**
     * Gerar novo token (POST /api/generate-token)
     */
    public function generate() {
        try {
            $token = Auth::generateToken();

            // Criar usuário no banco
            $user = $this->userModel->create($token, 'demo');

            Logger::api('GENERATE_TOKEN', $token, 200);

            return Response::success($user, 'Token gerado com sucesso', 201);
        } catch (Exception $e) {
            Logger::error('Token generation failed', ['error' => $e->getMessage()]);
            return Response::error('Falha ao gerar token', 500);
        }
    }

    /**
     * Validar/Obter informações do token (POST /api/validate-token)
     */
    public function validate() {
        try {
            $token = Auth::getToken();

            if (!$token) {
                return Response::error('Token não fornecido', 400);
            }

            if (!Auth::isValidTokenFormat($token)) {
                return Response::error('Formato de token inválido', 400);
            }

            $user = $this->userModel->getByToken($token);

            if (!$user) {
                return Response::error('Token não encontrado', 404);
            }

            $daysRemaining = $this->userModel->getDaysRemaining($token);
            $availableRestorations = $this->userModel->getAvailableRestorations($token);

            $userData = [
                'token' => $user['token'],
                'status' => $user['status'],
                'restorations_used' => (int)$user['restorations_used'],
                'restorations_available' => $availableRestorations,
                'days_remaining' => $daysRemaining,
                'activated_at' => $user['status_activated_at'],
                'expires_at' => $user['status_expires_at'],
                'created_at' => $user['created_at']
            ];

            Logger::api('VALIDATE_TOKEN', $token, 200);

            return Response::success($userData, 'Token válido');
        } catch (Exception $e) {
            Logger::error('Token validation failed', ['error' => $e->getMessage()]);
            return Response::error('Erro ao validar token', 500);
        }
    }

    /**
     * Restaurar token (POST /api/restore-token)
     * Permite que um usuário recupere seu token anterior
     */
    public function restore() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);

            if (!isset($input['device_identifier'])) {
                return Response::error('Device identifier não fornecido', 400);
            }

            $deviceIdentifier = $input['device_identifier'];
            $deviceName = $input['device_name'] ?? 'Unknown Device';
            $ipAddress = $this->getClientIP();

            // Verificar se já tem uma restauração este mês
            $month = date('n');
            $year = date('Y');

            $restoration = $this->restorationModel->getByDeviceAndMonth($deviceIdentifier, $month, $year);

            if ($restoration) {
                $user = $this->userModel->getByToken($restoration['token']);
                
                Logger::api('RESTORE_TOKEN', $restoration['token'], 200);

                return Response::success([
                    'token' => $restoration['token'],
                    'status' => $user['status'],
                    'message' => 'Token restaurado com sucesso'
                ]);
            }

            // Criar novo token para este dispositivo
            $newToken = Auth::generateToken();
            $user = $this->userModel->create($newToken, 'demo');

            // Registrar restauração
            $this->restorationModel->create($newToken, $deviceIdentifier, $deviceName, $ipAddress, $_SERVER['HTTP_USER_AGENT'] ?? '');

            Logger::api('RESTORE_TOKEN_NEW', $newToken, 201);

            return Response::success([
                'token' => $newToken,
                'status' => 'demo',
                'message' => 'Token restaurado com sucesso'
            ], 'Token restaurado', 201);
        } catch (Exception $e) {
            Logger::error('Token restoration failed', ['error' => $e->getMessage()]);
            return Response::error('Falha ao restaurar token', 500);
        }
    }

    /**
     * Deletar token (POST /api/delete-token)
     */
    public function delete() {
        try {
            $token = Auth::getToken();

            if (!$token) {
                return Response::error('Token não fornecido', 400);
            }

            $user = $this->userModel->getByToken($token);

            if (!$user) {
                return Response::error('Token não encontrado', 404);
            }

            if ($this->userModel->delete($token)) {
                Logger::api('DELETE_TOKEN', $token, 200);
                return Response::success(null, 'Token deletado com sucesso');
            }

            return Response::error('Falha ao deletar token', 500);
        } catch (Exception $e) {
            Logger::error('Token deletion failed', ['error' => $e->getMessage()]);
            return Response::error('Erro ao deletar token', 500);
        }
    }

    /**
     * Obter IP do cliente
     */
    private function getClientIP() {
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
