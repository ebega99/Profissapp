# 🔧 Profissapp - Backend PHP (Guia de Implementação)

## 📋 Estrutura do Banco de Dados

### Tabela: `users`
```sql
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `token` VARCHAR(255) UNIQUE NOT NULL,
  `status` ENUM('demo', 'profissa') DEFAULT 'demo',
  `status_activated_at` DATETIME,
  `status_expires_at` DATETIME,
  `restorations_used` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_token` (`token`),
  INDEX `idx_status` (`status`)
);
```

### Tabela: `payments`
```sql
CREATE TABLE `payments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `token` VARCHAR(255) NOT NULL,
  `payment_id` VARCHAR(255),
  `amount` DECIMAL(10, 2) DEFAULT 5.00,
  `status` ENUM('pending', 'approved', 'failed', 'cancelled') DEFAULT 'pending',
  `mercado_pago_id` VARCHAR(255),
  `mercado_pago_qr` LONGTEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`token`) REFERENCES `users`(`token`),
  INDEX `idx_token` (`token`),
  INDEX `idx_status` (`status`)
);
```

### Tabela: `restorations`
```sql
CREATE TABLE `restorations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `token` VARCHAR(255) NOT NULL,
  `device_identifier` VARCHAR(255),
  `restored_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `month` INT,
  `year` INT,
  FOREIGN KEY (`token`) REFERENCES `users`(`token`),
  INDEX `idx_token` (`token`),
  INDEX `idx_month_year` (`month`, `year`)
);
```

## 🔌 Endpoints da API

### 1. Gerar Token (Primeira Vez)
```
POST /api/generate-token
Content-Type: application/json

Response:
{
  "success": true,
  "token": "PF1682591234ABC123XYZ",
  "status": "demo"
}
```

**PHP:**
```php
<?php
header('Content-Type: application/json');

try {
    // Conectar BD
    $conn = new mysqli("localhost", "ebega99", "Gorila93@", "profissapp");
    
    if ($conn->connect_error) {
        throw new Exception("Erro na conexão: " . $conn->connect_error);
    }
    
    // Gerar token único
    $token = 'PF' . time() . substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 12);
    
    // Inserir na tabela users
    $stmt = $conn->prepare("INSERT INTO users (token, status) VALUES (?, 'demo')");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    
    echo json_encode([
        "success" => true,
        "token" => $token,
        "status" => "demo"
    ]);
    
    $conn->close();
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>
```

### 2. Verificar Status do Token
```
POST /api/verify-token
Content-Type: application/json

Request:
{
  "token": "PF1682591234ABC123XYZ"
}

Response:
{
  "success": true,
  "status": "profissa",
  "days_remaining": 25,
  "restorations_used": 1,
  "restorations_allowed": 3,
  "payment_expires_at": "2025-05-24T14:30:00Z"
}
```

**PHP:**
```php
<?php
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);
$token = $data['token'] ?? null;

try {
    $conn = new mysqli("localhost", "ebega99", "Gorila93@", "profissapp");
    
    $stmt = $conn->prepare("SELECT status, status_expires_at, restorations_used FROM users WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!$user) {
        throw new Exception("Token não encontrado");
    }
    
    $status = $user['status'];
    $daysRemaining = 0;
    
    if ($status === 'profissa' && $user['status_expires_at']) {
        $expiresAt = new DateTime($user['status_expires_at']);
        $now = new DateTime();
        $daysRemaining = $expiresAt->diff($now)->days;
        
        // Se expirou, volta para demo
        if ($daysRemaining < 0) {
            $stmt = $conn->prepare("UPDATE users SET status = 'demo' WHERE token = ?");
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $status = 'demo';
            $daysRemaining = 0;
        }
    }
    
    echo json_encode([
        "success" => true,
        "status" => $status,
        "days_remaining" => $daysRemaining,
        "restorations_used" => (int)$user['restorations_used'],
        "restorations_allowed" => 3,
        "payment_expires_at" => $user['status_expires_at']
    ]);
    
    $conn->close();
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>
```

### 3. Criar Pagamento (Mercado Pago)
```
POST /api/create-payment
Content-Type: application/json

Request:
{
  "token": "PF1682591234ABC123XYZ"
}

Response:
{
  "success": true,
  "payment_id": "12345678",
  "qr_code": "data:image/png;base64,iVBORw0K...",
  "qr_code_url": "https://api.mercadopago.com/..."
}
```

**PHP:**
```php
<?php
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);
$token = $data['token'] ?? null;

try {
    $conn = new mysqli("localhost", "ebega99", "Gorila93@", "profissapp");
    
    // Verificar se token existe
    $stmt = $conn->prepare("SELECT id FROM users WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Token não encontrado");
    }
    
    // Criar pagamento no Mercado Pago
    // OBS: Você precisa de credenciais do Mercado Pago
    $mercadoPagoToken = "YOUR_MERCADO_PAGO_TOKEN";
    
    $paymentData = [
        "description" => "Profissapp - Remover Demonstração (30 dias)",
        "external_reference" => $token,
        "notification_url" => "https://seu-dominio.com/api/webhook-payment",
        "items" => [
            [
                "title" => "Profissapp - 30 dias de uso profissional",
                "quantity" => 1,
                "unit_price" => 5.00
            ]
        ]
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.mercadopago.com/instore/qr/seller/collectors/[USER_ID]/pos/[EXTERNAL_POS_ID]/qrs");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . $mercadoPagoToken,
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($paymentData));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200 && $httpCode !== 201) {
        throw new Exception("Erro ao criar QR Code no Mercado Pago");
    }
    
    $mpResponse = json_decode($response, true);
    
    // Salvar pagamento no banco
    $stmt = $conn->prepare("INSERT INTO payments (token, payment_id, mercado_pago_id, status) VALUES (?, ?, ?, 'pending')");
    $paymentId = $mpResponse['id'];
    $stmt->bind_param("sss", $token, $paymentId, $paymentId);
    $stmt->execute();
    
    echo json_encode([
        "success" => true,
        "payment_id" => $paymentId,
        "qr_code" => $mpResponse['qr_data'] ?? null
    ]);
    
    $conn->close();
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>
```

### 4. Webhook - Confirmar Pagamento
```
POST /api/webhook-payment
Content-Type: application/json

// Mercado Pago vai chamar este endpoint após confirmar pagamento
```

**PHP:**
```php
<?php
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

try {
    $conn = new mysqli("localhost", "ebega99", "Gorila93@", "profissapp");
    
    $paymentId = $data['data']['id'] ?? null;
    $status = $data['action'] ?? null; // 'payment.created', 'payment.updated'
    
    if ($status === 'payment.updated') {
        // Buscar detalhes do pagamento no Mercado Pago
        $mercadoPagoToken = "YOUR_MERCADO_PAGO_TOKEN";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.mercadopago.com/v1/payments/{$paymentId}");
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer " . $mercadoPagoToken]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $mpPayment = json_decode($response, true);
        
        if ($mpPayment['status'] === 'approved') {
            // Buscar token pela referência externa
            $externalRef = $mpPayment['external_reference'];
            
            // Atualizar status do usuário
            $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
            
            $stmt = $conn->prepare("UPDATE users SET status = 'profissa', status_activated_at = NOW(), status_expires_at = ? WHERE token = ?");
            $stmt->bind_param("ss", $expiresAt, $externalRef);
            $stmt->execute();
            
            // Atualizar pagamento
            $stmt = $conn->prepare("UPDATE payments SET status = 'approved' WHERE payment_id = ?");
            $stmt->bind_param("s", $paymentId);
            $stmt->execute();
        }
    }
    
    echo json_encode(["success" => true]);
    $conn->close();
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>
```

### 5. Restaurar Token (Outro Dispositivo)
```
POST /api/restore-token
Content-Type: application/json

Request:
{
  "token": "PF1682591234ABC123XYZ",
  "device_id": "device-fingerprint-aqui"
}

Response:
{
  "success": true,
  "status": "profissa",
  "days_remaining": 25,
  "restorations_left": 2
}
```

**PHP:**
```php
<?php
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);
$token = $data['token'] ?? null;
$deviceId = $data['device_id'] ?? null;

try {
    $conn = new mysqli("localhost", "ebega99", "Gorila93@", "profissapp");
    
    // Verificar token
    $stmt = $conn->prepare("SELECT status, restorations_used, status_expires_at FROM users WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!$user) {
        throw new Exception("Token não encontrado");
    }
    
    // Verificar se ainda há restaurações disponíveis neste mês
    $currentMonth = date('m');
    $currentYear = date('Y');
    
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count FROM restorations 
        WHERE token = ? AND month = ? AND year = ?
    ");
    $stmt->bind_param("sii", $token, $currentMonth, $currentYear);
    $stmt->execute();
    $result = $stmt->get_result();
    $restoration = $result->fetch_assoc();
    
    if ($restoration['count'] >= 3) {
        throw new Exception("Limite de restaurações excedido");
    }
    
    // Registrar nova restauração
    $stmt = $conn->prepare("INSERT INTO restorations (token, device_identifier, month, year) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssii", $token, $deviceId, $currentMonth, $currentYear);
    $stmt->execute();
    
    // Calcular dias restantes
    $daysRemaining = 0;
    if ($user['status'] === 'profissa' && $user['status_expires_at']) {
        $expiresAt = new DateTime($user['status_expires_at']);
        $now = new DateTime();
        $daysRemaining = $expiresAt->diff($now)->days;
    }
    
    $restorationLeft = 3 - ($restoration['count'] + 1);
    
    echo json_encode([
        "success" => true,
        "status" => $user['status'],
        "days_remaining" => $daysRemaining,
        "restorations_left" => $restorationLeft
    ]);
    
    $conn->close();
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>
```

## ⚙️ Variáveis de Configuração

Crie um arquivo `config.php`:

```php
<?php
// config.php

// Banco de Dados
define('DB_HOST', 'localhost');
define('DB_USER', 'ebega99');
define('DB_PASS', 'Gorila93@');
define('DB_NAME', 'profissapp');
define('DB_PORT', 3306);

// Mercado Pago
define('MERCADO_PAGO_TOKEN', 'APP_USR_xxxxxxxxxxxx'); // Você vai obter isso
define('MERCADO_PAGO_USER_ID', 'seu_user_id_aqui');

// App
define('APP_URL', 'https://seu-dominio.com');
define('WEBHOOK_URL', APP_URL . '/api/webhook-payment');

// CORS
define('ALLOWED_ORIGINS', [
    'http://localhost:5174',
    'http://localhost:5173',
    'https://seu-dominio.com'
]);

// Headers de CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
?>
```

## 📁 Estrutura de Pastas Recomendada

```
api/
├── config.php              # Configurações
├── generate-token.php      # POST /api/generate-token
├── verify-token.php        # POST /api/verify-token
├── create-payment.php      # POST /api/create-payment
├── webhook-payment.php     # POST /api/webhook-payment
├── restore-token.php       # POST /api/restore-token
├── database.php            # Conexão reutilizável
└── functions.php           # Funções auxiliares

database/
└── schema.sql              # Criar todas as tabelas

index.php                   # Roteador (opcional)
```

## 🔌 Integração Frontend → Backend

No `App.jsx`, você vai chamar os endpoints assim:

```javascript
// Exemplo: Verificar status do token
const verifyToken = async () => {
    try {
        const response = await axios.post('http://seu-dominio.com/api/verify-token', {
            token: localStorage.getItem('profisstoken')
        });
        
        if (response.data.success) {
            setStatus(response.data.status);
            setDaysRemaining(response.data.days_remaining);
            setRestorationsUsed(response.data.restorations_used);
        }
    } catch (error) {
        console.error('Erro:', error);
    }
};

// Chamar ao carregar a página
useEffect(() => {
    verifyToken();
}, []);
```

## 🚀 Deploy

1. Upload dos arquivos PHP para seu servidor
2. Configurar `.htaccess` para pretty URLs (opcional)
3. Configurar CORS corretamente
4. Testar endpoints com Postman
5. Integrar Mercado Pago
6. Configurar webhooks no Mercado Pago

## 📝 Checklist

- [ ] Criar banco de dados e tabelas
- [ ] Criar endpoints PHP
- [ ] Integrar Mercado Pago
- [ ] Configurar CORS
- [ ] Testar endpoints
- [ ] Conectar Frontend
- [ ] Testar fluxo completo
- [ ] Deploy em produção

---

**Necessita de ajuda? Implemente seguindo este guia passo a passo!**
