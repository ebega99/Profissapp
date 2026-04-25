# 🚀 Profissapp - Setup Backend

## ⚙️ Pré-requisitos

- ✅ PHP 7.4+ (com extensões: curl, mysqli, json, openssl)
- ✅ MySQL 5.7+ ou MariaDB
- ✅ Apache com `mod_rewrite` ativado
- ✅ Conta Mercado Pago (para credenciais de API)

---

## 📋 Passo 1: Configurar Banco de Dados

### 1.1 Criar banco de dados
```bash
# SSH na sua hospedagem
mysql -u ebega99 -p

# Criar banco
CREATE DATABASE profissapp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Sair
EXIT;
```

### 1.2 Executar script SQL
```bash
# Executar o script database.sql
mysql -u ebega99 -p profissapp < database.sql
```

### 1.3 Verificar tabelas criadas
```bash
mysql -u ebega99 -p profissapp -e "SHOW TABLES;"
```

**Resultado esperado:**
```
Tables_in_profissapp
users
payments
restorations
budgets
webhook_logs
access_logs
```

---

## 🔧 Passo 2: Configurar Arquivo de Ambiente

### 2.1 Criar arquivo .env
```bash
# Copiar arquivo de exemplo
cp api/.env.example api/.env

# Editar o arquivo
nano api/.env
```

### 2.2 Preencher as credenciais
```env
APP_ENV=production
APP_DEBUG=false

# Obter em: https://www.mercadopago.com.br/developers/panel
MERCADO_PAGO_PUBLIC_KEY=APP_USR-XXXXXXXXXXXX-YYYYYYYYYYYYYY
MERCADO_PAGO_ACCESS_TOKEN=APP_USR-ZZZZZZZZZZZZZZZZZZ

SECRET_KEY=sua_chave_super_secreta_aqui_pelo_menos_32_caracteres
```

### 2.3 Localizar credenciais Mercado Pago

1. Ir para: https://www.mercadopago.com.br/developers/panel
2. Fazer login com sua conta
3. Em **"Credenciais da aplicação"**, copiar:
   - **Public Key** (para o PUBLIC_KEY)
   - **Access Token** (para o ACCESS_TOKEN)
4. Salvar no `.env`

---

## 📁 Passo 3: Estrutura de Pastas

A estrutura já foi criada automaticamente:

```
api/
├── .env                    # Credenciais (NÃO versionar!)
├── .env.example           # Exemplo de .env
├── .htaccess              # Rewriting de URLs
├── index.php              # Entry point
├── API.md                 # Documentação
├── config/
│   ├── database.php       # Conexão MySQL
│   └── environment.php    # Variáveis de ambiente
├── controllers/
│   ├── TokenController.php
│   └── PaymentController.php
├── models/
│   ├── User.php
│   ├── Payment.php
│   └── Restoration.php
├── helpers/
│   ├── Response.php       # Respostas JSON
│   ├── Auth.php           # Autenticação/Tokens
│   ├── Logger.php         # Logs
│   └── MercadoPago.php    # Integração MP
└── webhooks/
    └── mercado-pago.php   # Handler de webhooks
```

---

## ✅ Passo 4: Testar API

### 4.1 Health Check
```bash
curl https://profissapp.fibra99.com/api/health

# Resposta esperada:
# {"success":true,"message":"API is running","data":{"status":"online"},"timestamp":"2024-04-24T..."}
```

### 4.2 Gerar Token
```bash
curl -X POST https://profissapp.fibra99.com/api/generate-token

# Resposta esperada:
# {"success":true,"message":"Token gerado com sucesso","data":{"token":"PF1682591234ABC123XYZ","status":"demo"}}
```

### 4.3 Validar Token
```bash
# Substituir SEU_TOKEN_AQUI pelo token gerado acima
curl -H "Authorization: Bearer SEU_TOKEN_AQUI" \
     https://profissapp.fibra99.com/api/validate-token

# Resposta esperada:
# {"success":true,"data":{"token":"...","status":"demo","restorations_used":0,...}}
```

---

## 🔗 Passo 5: Integrar Frontend com API

### 5.1 Configurar URL da API no Frontend

Editar `src/main.jsx` ou criar arquivo de configuração:

```javascript
// src/config/api.js
export const API_BASE_URL = 'https://profissapp.fibra99.com/api';

// Em desenvolvimento:
// export const API_BASE_URL = 'http://localhost:8000/api';
```

### 5.2 Criar serviço de API

```javascript
// src/services/api.js
export const api = {
  async generateToken() {
    const response = await fetch(`${API_BASE_URL}/generate-token`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' }
    });
    return response.json();
  },

  async validateToken(token) {
    const response = await fetch(`${API_BASE_URL}/validate-token`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
      }
    });
    return response.json();
  },

  async createPayment(token) {
    const response = await fetch(`${API_BASE_URL}/create-payment`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
      }
    });
    return response.json();
  },

  async getPaymentStatus(paymentId, token) {
    const response = await fetch(`${API_BASE_URL}/payment/${paymentId}`, {
      headers: {
        'Authorization': `Bearer ${token}`
      }
    });
    return response.json();
  }
};
```

---

## 🔔 Passo 6: Configurar Webhook Mercado Pago

### 6.1 No Dashboard do Mercado Pago

1. Ir para **"Configurações"** → **"Webhooks"**
2. Clicar em **"Adicionar webhook"**
3. Preencher:
   - **URL**: `https://profissapp.fibra99.com/api/webhooks/mercado-pago.php`
   - **Eventos**: Marcar `payment.created` e `payment.updated`
4. Clicar em **"Salvar"**

### 6.2 Testar Webhook

```bash
# Enviar evento de teste
curl -X POST https://profissapp.fibra99.com/api/webhooks/mercado-pago.php \
  -H "Content-Type: application/json" \
  -d '{
    "type": "payment.updated",
    "id": "1234567890",
    "status": "approved"
  }'
```

---

## 📊 Passo 7: Monitorar Logs

Os logs são salvos em `/logs/`:

```bash
# Logs de erro
tail -f logs/errors.log

# Logs de API
tail -f logs/api.log

# Logs de pagamento
tail -f logs/payments.log
```

---

## 🐛 Troubleshooting

### Erro: "Access denied"
```
Solução: Verificar credenciais MySQL no api/config/database.php
- Usuário: ebega99
- Senha: Gorila93@
- Host: localhost
```

### Erro: "Mercado Pago API error"
```
Solução: Verificar credenciais em api/.env
- Ir para https://www.mercadopago.com.br/developers/panel
- Copiar Public Key e Access Token corretos
```

### Erro: "mod_rewrite not enabled"
```
Solução: Ativar mod_rewrite no Apache
Contato com suporte da hospedagem para ativar
```

### Erro: "Class not found"
```
Solução: Verificar se todos os arquivos PHP foram criados
Caminho esperado: /home/ebega99/profissapp/api/
```

---

## 📋 Checklist Final

- [ ] Banco de dados criado e script SQL executado
- [ ] Arquivo `.env` criado com credenciais
- [ ] URL de saúde retorna status online
- [ ] Consegue gerar um token
- [ ] Consegue validar um token
- [ ] Frontend conecta à API
- [ ] Webhook do Mercado Pago configurado
- [ ] Testes de pagamento realizados

---

## 🚀 Deploy para Produção

1. Verificar todos os itens do checklist
2. Definir `APP_ENV=production` no `.env`
3. Definir `APP_DEBUG=false` no `.env`
4. Acessar via HTTPS (não HTTP)
5. Fazer backup regular do banco de dados
6. Monitorar logs periodicamente

---

## 📞 Próximos Passos

1. ✅ Backend completo
2. ⏳ Frontend totalmente integrado
3. ⏳ Testes em produção
4. ⏳ Documentação para usuários finais
5. ⏳ Sistema de suporte e reembolsos

---

**Sucesso! Seu backend está pronto para produção! 🎉**
