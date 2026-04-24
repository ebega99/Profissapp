# 📋 Profissapp - Plano de Implementação (Roadmap)

## 🎯 Objetivo Final
Criar um sistema completo de geração de orçamentos para profissionais autônomos com:
- ✅ Frontend 100% completo (React + Vite)
- ⏳ Backend PHP com banco MySQL
- ⏳ Integração Mercado Pago para pagamentos
- ⏳ Sistema automático de renovação 30 dias

---

## 📊 Status Atual

| Componente | Status | % Completo |
|-----------|--------|-----------|
| **Frontend React** | ✅ Completo | 100% |
| **Banco de Dados** | 📝 Script pronto | 0% (aguarda setup) |
| **API PHP** | 📝 Documentação pronta | 0% (aguarda implementação) |
| **Mercado Pago** | ⏳ Pronto para integrar | 0% (aguarda credenciais) |
| **Testes** | ⏳ Guia pronto | 0% |
| **Deploy** | ⏳ Pendente | 0% |

---

## 🚀 Fase 1: Setup do Banco de Dados (1-2 horas)

### Tarefas
- [ ] Acessar painel Plesk da hospedagem
- [ ] Criar banco `profissapp` (se não existir)
- [ ] Executar script `database.sql`
- [ ] Verificar criação de tabelas
- [ ] Fazer backup inicial

### Comandos
```bash
# Acessar MySQL
mysql -u ebega99 -p

# Criar banco (se necessário)
CREATE DATABASE profissapp CHARACTER SET utf8mb4;

# Executar script
mysql -u ebega99 -p profissapp < database.sql

# Verificar tabelas
USE profissapp;
SHOW TABLES;
```

### Verificação
```sql
-- Verifique se as tabelas existem
SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = 'profissapp';
```

**Resultado esperado:** 7 tabelas criadas
- users
- payments
- restorations
- budgets
- webhook_logs
- access_logs
- (+ 3 views)

---

## 🔧 Fase 2: Setup do Backend PHP (3-4 horas)

### 2.1: Estrutura de Pastas
```bash
# Na raiz da sua hospedagem, criar:
mkdir -p api/controllers
mkdir -p api/models
mkdir -p api/helpers
mkdir -p api/config

# Estrutura final:
profissapp/
├── api/
│   ├── config/
│   │   ├── config.php         # Configurações globais
│   │   └── database.php       # Conexão MySQL
│   ├── models/
│   │   ├── User.php
│   │   ├── Payment.php
│   │   └── Restoration.php
│   ├── controllers/
│   │   ├── AuthController.php
│   │   ├── PaymentController.php
│   │   └── WebhookController.php
│   ├── helpers/
│   │   ├── functions.php
│   │   └── logger.php
│   ├── .htaccess              # Pretty URLs
│   └── index.php              # Roteador principal
├── public/                     # Frontend React (build)
└── .env                       # Variáveis de ambiente
```

### 2.2: Arquivo `api/config/config.php`
```php
<?php
// config.php

// Ambiente
define('ENVIRONMENT', getenv('ENVIRONMENT') ?: 'development');
define('DEBUG', ENVIRONMENT === 'development');

// Database
define('DB_HOST', 'localhost');
define('DB_USER', 'ebega99');
define('DB_PASS', 'Gorila93@');
define('DB_NAME', 'profissapp');
define('DB_PORT', 3306);

// App URLs
define('APP_URL', getenv('APP_URL') ?: 'http://localhost:5174');
define('API_URL', getenv('API_URL') ?: 'http://localhost/api');
define('WEBHOOK_URL', API_URL . '/webhook/payment');

// Mercado Pago (você preencherá depois)
define('MERCADO_PAGO_TOKEN', getenv('MERCADO_PAGO_TOKEN') ?: '');
define('MERCADO_PAGO_USER_ID', getenv('MERCADO_PAGO_USER_ID') ?: '');
define('MERCADO_PAGO_EXTERNAL_POS_ID', getenv('MERCADO_PAGO_EXTERNAL_POS_ID') ?: '');

// CORS
define('ALLOWED_ORIGINS', [
    'http://localhost:5174',
    'http://localhost:5173',
    'http://localhost:3000',
    getenv('APP_URL') ?: '',
]);

// Segurança
define('JWT_SECRET', getenv('JWT_SECRET') ?: 'sua_chave_secreta_aqui');
define('TOKEN_EXPIRY', 30 * 24 * 60 * 60); // 30 dias em segundos

// Logging
define('LOG_DIR', __DIR__ . '/../../logs');

// Headers
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

// CORS Headers
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, ALLOWED_ORIGINS)) {
    header("Access-Control-Allow-Origin: $origin");
}

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 3600');

// Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Timezone
date_default_timezone_set('America/Sao_Paulo');
?>
```

### 2.3: Arquivo `api/config/database.php`
```php
<?php
// database.php - Conexão reutilizável

class Database {
    private static $instance;
    private $conn;

    private function __construct() {
        $this->connect();
    }

    private function connect() {
        $this->conn = new mysqli(
            DB_HOST,
            DB_USER,
            DB_PASS,
            DB_NAME,
            DB_PORT
        );

        if ($this->conn->connect_error) {
            die(json_encode([
                'success' => false,
                'error' => 'Database connection failed: ' . $this->conn->connect_error
            ]));
        }

        $this->conn->set_charset('utf8mb4');
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->conn;
    }

    public function query($sql) {
        return $this->conn->query($sql);
    }

    public function prepare($sql) {
        return $this->conn->prepare($sql);
    }

    public function close() {
        $this->conn->close();
    }
}
?>
```

### 2.4: Arquivo `api/helpers/functions.php`
```php
<?php
// functions.php - Funções auxiliares

function generateToken() {
    return 'PF' . time() . substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 12);
}

function getClientIp() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

function generateDeviceFingerprint() {
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $ip = getClientIp();
    return hash('sha256', $ua . '|' . $ip);
}

function response($success, $data = [], $message = '') {
    return json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('c')
    ]);
}

function errorResponse($message, $code = 400) {
    http_response_code($code);
    return response(false, [], $message);
}

function successResponse($data = [], $message = '') {
    return response(true, $data, $message);
}

function getInputJSON() {
    return json_decode(file_get_contents('php://input'), true);
}

function validateToken($token) {
    if (!$token || !preg_match('/^PF\d{10}[A-Z0-9]{12}$/', $token)) {
        return false;
    }
    return true;
}

function logAccess($token, $action, $endpoint, $method, $status = 200) {
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("
        INSERT INTO access_logs (token, action, endpoint, method, ip_address, user_agent, response_status)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    if ($stmt) {
        $ip = getClientIp();
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $stmt->bind_param('ssssssi', $token, $action, $endpoint, $method, $ip, $ua, $status);
        $stmt->execute();
        $stmt->close();
    }
}
?>
```

---

## 💰 Fase 3: Implementar Endpoints da API (4-6 horas)

### 3.1: POST `/api/generate-token`
Implementar no arquivo `api/controllers/AuthController.php`

**O que faz:**
- Gera novo token
- Salva na tabela users
- Retorna token

### 3.2: POST `/api/verify-token`
**O que faz:**
- Recebe token
- Verifica se existe
- Retorna status (demo/profissa)
- Calcula dias restantes

### 3.3: POST `/api/create-payment`
**O que faz:**
- Recebe token
- Cria QR Code no Mercado Pago
- Salva no banco
- Retorna QR Code

### 3.4: POST `/api/webhook/payment`
**O que faz:**
- Recebe notificação do Mercado Pago
- Verifica pagamento
- Atualiza status para 'profissa'
- Registra na tabela payments

### 3.5: POST `/api/restore-token`
**O que faz:**
- Recebe token + device_id
- Verifica restaurações do mês
- Se < 3, restaura
- Retorna novo status

---

## 🔐 Fase 4: Integração Mercado Pago (2-3 horas)

### 4.1: Obter Credenciais
1. Acesse https://www.mercadopago.com.br
2. Crie conta se não tiver
3. Vá em "Credenciais de Sandbox" ou "Produção"
4. Copie:
   - Access Token
   - User ID
   - External POS ID

### 4.2: Configurar no Backend
Adicione ao `.env`:
```
MERCADO_PAGO_TOKEN=APP_USR_xxxxxxxxxxxxxxxxx
MERCADO_PAGO_USER_ID=123456789
MERCADO_PAGO_EXTERNAL_POS_ID=SUA_POS_ID
```

### 4.3: Implementar Pagamento
No `PaymentController.php`:
- Criar pedido no Mercado Pago
- Gerar QR Code
- Registrar no banco

### 4.4: Configurar Webhook
No painel do Mercado Pago:
- URL: `https://seu-dominio.com/api/webhook/payment`
- Eventos: `payment.updated`

---

## ✅ Fase 5: Testes (2 horas)

### 5.1: Testes Unitários
```bash
# Testar cada endpoint com Postman ou cURL
curl -X POST http://localhost/api/generate-token \
  -H "Content-Type: application/json"
```

### 5.2: Testes de Integração
1. Gerar token
2. Verificar token
3. Criar pagamento
4. Simular webhook
5. Verificar status mudou para profissa

### 5.3: Testes em Produção
1. Deploy no servidor
2. Testar com Mercado Pago Real
3. Validar webhook

---

## 🚀 Fase 6: Deploy (1-2 horas)

### 6.1: Preparação
- [ ] Gerar build React: `npm run build`
- [ ] Upload de arquivos para servidor
- [ ] Configurar .env em produção
- [ ] Testar HTTPS
- [ ] Configurar domínio

### 6.2: Checklist
- [ ] Frontend carrega
- [ ] API responde
- [ ] Banco funciona
- [ ] Mercado Pago integrado
- [ ] Webhooks funcionam
- [ ] Logs registram
- [ ] Performance OK

---

## 📅 Timeline Estimada

| Fase | Duração | Data Prevista |
|-----|---------|---------------|
| Fase 1: BD | 1-2h | Hoje |
| Fase 2: Backend Setup | 3-4h | Hoje/Amanhã |
| Fase 3: Endpoints | 4-6h | Amanhã |
| Fase 4: Mercado Pago | 2-3h | Dia seguinte |
| Fase 5: Testes | 2h | Mesmo dia |
| Fase 6: Deploy | 1-2h | Último dia |
| **TOTAL** | **13-19 horas** | **~3 dias** |

---

## 📚 Arquivos de Referência

- `README.md` - Visão geral
- `BACKEND.md` - Documentação detalhada
- `database.sql` - Script do banco
- `TESTING.md` - Guia de testes
- `ROADMAP.md` - Este arquivo

---

## 🎯 Próximas Ações Imediatas

1. ✅ **Hoje**: 
   - Executar `database.sql`
   - Verificar tabelas criadas
   - Começar Fase 2

2. ✅ **Amanhã**:
   - Implementar endpoints
   - Testar localmente

3. ✅ **Próximos dias**:
   - Integrar Mercado Pago
   - Deploy
   - Testes finais

---

## 📞 Suporte Técnico

Se tiver dúvidas em alguma fase:
1. Consulte o arquivo correspondente (BACKEND.md, TESTING.md, etc)
2. Verifique os logs do servidor
3. Use Postman para testar endpoints
4. Valide credenciais do Mercado Pago

---

**Você está pronto para começar! 🚀**

Comece pela **Fase 1** agora mesmo!
