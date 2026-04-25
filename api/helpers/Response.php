<?php
/**
 * Profissapp - Helper de Response JSON
 * Arquivo: api/helpers/Response.php
 */

class Response {
    /**
     * Sucesso
     */
    public static function success($data = null, $message = 'Success', $statusCode = 200) {
        http_response_code($statusCode);
        return self::json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('c')
        ]);
    }

    /**
     * Erro
     */
    public static function error($message = 'Error', $statusCode = 400, $errors = null) {
        http_response_code($statusCode);
        return self::json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'timestamp' => date('c')
        ]);
    }

    /**
     * Não encontrado
     */
    public static function notFound($message = 'Not found') {
        return self::error($message, 404);
    }

    /**
     * Não autorizado
     */
    public static function unauthorized($message = 'Unauthorized') {
        return self::error($message, 401);
    }

    /**
     * Proibido
     */
    public static function forbidden($message = 'Forbidden') {
        return self::error($message, 403);
    }

    /**
     * Validação
     */
    public static function validation($errors, $message = 'Validation error') {
        return self::error($message, 422, $errors);
    }

    /**
     * Erro interno do servidor
     */
    public static function internalError($message = 'Internal server error') {
        return self::error($message, 500);
    }

    /**
     * Retornar JSON
     */
    private static function json($data) {
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }
}
