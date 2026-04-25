# 🚀 GUIA DE DEPLOY - Profissapp Backend + Mercado Pago

## ⚠️ ANTES DE COMEÇAR

- [ ] Ter acesso SSH/FTP à hospedagem
- [ ] Ter credenciais MySQL (usuário: ebega99, senha: Gorila93@)
- [ ] Ter credenciais Mercado Pago (Public Key + Access Token)
- [ ] Ter domínio com HTTPS configurado
- [ ] Ter PHP 7.4+ com extensões: curl, mysqli, json, openssl

---

## 🎬 PASSO 1: Preparar Banco de Dados (5 minutos)

### 1.1 Conectar ao MySQL via SSH
```bash
# SSH na hospedagem
ssh seu_usuario@seu_dominio.com

# Conectar ao MySQL
mysql -u ebega99 -p
# Digite a senha: Gorila93@
```

### 1.2 Criar banco de dados
```sql
CREATE DATABASE profissapp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

### 1.3 Executar script SQL
```bash
# Voltar para home
cd ~

# Upload do arquivo database.sql (via FTP ou SCP)
# Se estiver no FTP: colocar na raiz

# Executar script
mysql -u ebega99 -p profissapp < database.sql
```

### 1.4 Verificar criação das tabelas
```bash
# Conectar novamente
mysql -u ebega99 -p profissapp

# Verificar tabelas
SHOW TABLES;
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

## 📁 PASSO 2: Upload de Arquivos (10 minutos)

### 2.1 Via FTP (Filezilla, WinSCP, etc)

**Conectar ao servidor:**
- Host: seu_dominio.com
- Usuário: seu_usuario_ftp
- Senha: sua_senha_ftp
- Porta: 21 (ou 22 para SFTP)

**Estrutura de upload:**
```
public_html/
├── api/                    # ← Copiar pasta inteira
├── src/                    # ← Copiar pasta inteira
├── public/                 # ← Copiar se existir
├── index.html
├── vite.config.js
├── package.json
└── ... outros arquivos
```

### 2.2 Via SSH/SCP (terminal)
```bash
# Do seu computador
scp -r api/ seu_usuario@seu_dominio.com:/home/seu_usuario/public_html/

# Ou se usar SSH git:
git clone seu_repositorio /home/seu_usuario/public_html/
cd /home/seu_usuario/public_html/
```

---

## ⚙️ PASSO 3: Configurar Ambiente (5 minutos)

### 3.1 Criar arquivo .env
```bash
# SSH novamente
ssh seu_usuario@seu_dominio.com

# Ir para pasta api
cd /home/seu_usuario/public_html/api

# Copiar exemplo
cp .env.example .env

# Editar arquivo
nano .env
```

### 3.2 Preencher credenciais
```env
# Arquivo: api/.env

APP_ENV=production
APP_DEBUG=false

# Obter em: https://www.mercadopago.com.br/developers/panel
MERCADO_PAGO_PUBLIC_KEY=APP_USR-SEU_PUBLIC_KEY_AQUI
MERCADO_PAGO_ACCESS_TOKEN=APP_USR-SEU_ACCESS_TOKEN_AQUI

# Gerar uma chave aleatória (mínimo 32 caracteres)
SECRET_KEY=sua_chave_aleatoria_super_secreta_aqui_12345678

APP_URL=https://seu_dominio.com
FRONTEND_URL=https://seu_dominio.com
```

### 3.3 Localizar credenciais Mercado Pago

1. Ir para: https://www.mercadopago.com.br/developers/panel/applications
2. Fazer login com sua conta
3. Selecionar sua aplicação
4. Em **"Credenciais"**, encontrar:
   - **Public Key** → Copiar para `MERCADO_PAGO_PUBLIC_KEY`
   - **Access Token** → Copiar para `MERCADO_PAGO_ACCESS_TOKEN`
5. Salvar arquivo `.env`

**Comando para salvar (nano):**
```
Ctrl + O → Enter → Ctrl + X
```

---

## 🔐 PASSO 4: Configurar Permissões (2 minutos)

### 4.1 Dar permissão de escrita para logs
```bash
# SSH
ssh seu_usuario@seu_dominio.com
cd /home/seu_usuario/public_html

# Criar diretório de logs
mkdir -p logs

# Dar permissão
chmod 777 logs

# Verificar se .env ficou correto
cat api/.env
```

### 4.2 Verificar permissões dos arquivos
```bash
# Pastas devem estar 755
find . -type d -exec chmod 755 {} \;

# Arquivos devem estar 644
find . -type f -exec chmod 644 {} \;

# PHP e .htaccess podem ser 755
chmod 755 api/index.php
chmod 755 api/.htaccess
chmod 755 api/webhooks/mercado-pago.php
```

---

## ✅ PASSO 5: Testar a API (5 minutos)

### 5.1 Health Check
```bash
curl https://seu_dominio.com/api/health

# Resposta esperada (JSON bonito):
{
  "success": true,
  "message": "API is running",
  "data": {
    "status": "online"
  },
  "timestamp": "2024-04-24T10:30:00+00:00"
}
```

**Se retornar erro 404:**
- Verificar se arquivos foram uploadados
- Verificar se `.htaccess` foi uploadado
- Verificar se mod_rewrite está ativado (contato com suporte)

### 5.2 Gerar Token
```bash
curl -X POST https://seu_dominio.com/api/generate-token

# Resposta esperada:
{
  "success": true,
  "message": "Token gerado com sucesso",
  "data": {
    "token": "PF1682591234ABC123XYZ",
    "status": "demo",
    "created_at": "2024-04-24T10:30:00+00:00"
  }
}
```

**Se retornar erro na conexão do banco:**
- Verificar credenciais em `api/config/database.php`
- Verificar se banco foi criado
- Verificar se script SQL foi executado

### 5.3 Validar Token
```bash
# Copiar o token da resposta anterior
TOKEN="PF1682591234ABC123XYZ"

curl -X POST https://seu_dominio.com/api/validate-token \
  -H "Authorization: Bearer $TOKEN"

# Resposta esperada:
{
  "success": true,
  "data": {
    "token": "PF1682591234ABC123XYZ",
    "status": "demo",
    "restorations_used": 0,
    "restorations_available": 3,
    "days_remaining": 0
  }
}
```

---

## 💳 PASSO 6: Configurar Mercado Pago (5 minutos)

### 6.1 Configurar Webhook
1. Ir para: https://www.mercadopago.com.br/developers/panel/webhooks
2. Em **"Webhoooks de aplicação"**, clicar em **"Novo Webhook"**
3. Preencher:
   - **URL**: `https://seu_dominio.com/api/webhooks/mercado-pago.php`
   - **Eventos**: Marcar:
     - `payment.created`
     - `payment.updated`
     - `payment.refunded`
4. Clicar em **"Criar"**

### 6.2 Testar Webhook (opcional)
```bash
# O Mercado Pago vai enviar um evento de teste

# Ver logs do webhook
ssh seu_usuario@seu_dominio.com
tail -f logs/payments.log
```

---

## 🔗 PASSO 7: Integrar Frontend (10 minutos)

### 7.1 Configurar variáveis do Vite
```bash
# Criar arquivo .env na raiz do projeto
echo "VITE_API_URL=https://seu_dominio.com/api" > .env
```

### 7.2 Criar serviço de API
Já foi criado em: `src/services/api.js`

### 7.3 Usar no componente React
```javascript
import { useAuth } from './hooks/useAuth';
import { usePayment } from './hooks/usePayment';
import PaymentModal from './components/PaymentModal';

export default function App() {
  const { token, generateToken } = useAuth();
  const [showPayment, setShowPayment] = useState(false);

  return (
    <>
      {!token && (
        <button onClick={generateToken}>Gerar Token</button>
      )}
      {token && (
        <button onClick={() => setShowPayment(true)}>
          Ativar Profissapp
        </button>
      )}
      <PaymentModal isOpen={showPayment} onClose={() => setShowPayment(false)} />
    </>
  );
}
```

### 7.4 Build e Deploy do Frontend
```bash
# No seu computador
npm run build

# Upload da pasta dist/ via FTP
# Copiar arquivos de dist/ para public_html/
```

---

## 📊 PASSO 8: Monitorar Logs (contínuo)

### 8.1 Acessar logs
```bash
ssh seu_usuario@seu_dominio.com

# Ver erros
tail -f logs/errors.log

# Ver pagamentos
tail -f logs/payments.log

# Ver toda atividade de API
tail -f logs/api.log
```

### 8.2 Configurar alertas (opcional)
```bash
# Monitorar erros em tempo real
watch -n 5 'tail -10 logs/errors.log'
```

---

## 🧪 PASSO 9: Testes Completos (15 minutos)

### 9.1 Teste de Fluxo Completo
```bash
# 1. Gerar token
TOKEN=$(curl -s -X POST https://seu_dominio.com/api/generate-token | jq -r '.data.token')
echo "Token: $TOKEN"

# 2. Validar token
curl -s -H "Authorization: Bearer $TOKEN" https://seu_dominio.com/api/validate-token | jq .

# 3. Criar pagamento
PAYMENT=$(curl -s -X POST https://seu_dominio.com/api/create-payment \
  -H "Authorization: Bearer $TOKEN" | jq -r '.data.payment_id')
echo "Payment: $PAYMENT"

# 4. Verificar status
curl -s -H "Authorization: Bearer $TOKEN" \
  https://seu_dominio.com/api/payment/$PAYMENT | jq .
```

### 9.2 Teste via Frontend
1. Abrir https://seu_dominio.com
2. Clicar em "Gerar Token"
3. Copiar e salvar token (para testes)
4. Clicar em "Ativar Profissapp"
5. Escaneador QR Code com seu banco (modo teste)
6. Confirmar pagamento (será com credenciais de teste)
7. Verificar se status muda para "profissa"

### 9.3 Teste com Mercado Pago (Sandbox)
1. Ir para: https://www.mercadopago.com.br/developers/panel
2. Alternar para "Modo Teste"
3. Gerar novo token e testar pagamento
4. Usar dados de teste do Mercado Pago

---

## 🎯 Checklist Final

- [ ] Banco de dados criado
- [ ] Script SQL executado
- [ ] Arquivos uploadados
- [ ] `.env` criado com credenciais
- [ ] Permissões configuradas (logs com 777)
- [ ] Health check retorna 200
- [ ] Token gerado com sucesso
- [ ] Token validado
- [ ] Webhook Mercado Pago configurado
- [ ] Frontend integrado com API
- [ ] Teste de pagamento realizado
- [ ] Logs criados e funcionando
- [ ] HTTPS ativado
- [ ] Certificado SSL válido
- [ ] CORS configurado
- [ ] Backup do banco agendado

---

## ⚠️ Troubleshooting

### Erro: "Class not found"
**Causa:** Arquivo PHP não encontrado
**Solução:**
- Verificar se todos os arquivos foram uploadados
- Verificar caminho correto em `require_once`
- Verificar permissões dos arquivos (644)

### Erro: "Connection denied"
**Causa:** Erro na conexão com MySQL
**Solução:**
- Verificar credenciais: usuário `ebega99`, senha `Gorila93@`
- Verificar se banco `profissapp` foi criado
- Verificar se script SQL foi executado

### Erro: "API not found" (404)
**Causa:** `.htaccess` não foi processado
**Solução:**
- Verificar se `.htaccess` foi uploadado (e não é arquivo oculto)
- Pedir suporte para ativar `mod_rewrite`
- Testar diretamente em `api/index.php?url=/health`

### Erro: "Mercado Pago API error"
**Causa:** Credenciais incorretas ou inativas
**Solução:**
- Verificar credenciais em `.env`
- Verificar se credenciais são da aplicação correta
- Verificar se está em "Modo Teste" ou "Modo Produção"
- Regenerar credenciais se necessário

### Pagamento não confirma
**Causa:** Webhook não chegou
**Solução:**
- Verificar logs de webhook: `tail -f logs/payments.log`
- Verificar URL do webhook no Mercado Pago
- Verificar se assinatura do webhook está correta
- Testar manualmente: `POST /api/confirm-payment`

---

## 📈 Próximos Passos

1. ✅ Setup completo
2. ⏳ Monitorar por 24h
3. ⏳ Analisar logs
4. ⏳ Ajustar conforme necessário
5. ⏳ Divulgar para clientes

---

## 📞 Suporte

**Se tiver problemas:**

1. **Verificar logs primeiro:**
   ```bash
   tail -50 logs/errors.log | head -20
   tail -50 logs/api.log | head -20
   tail -50 logs/payments.log | head -20
   ```

2. **Testar endpoint direto:**
   ```bash
   curl -v https://seu_dominio.com/api/health
   ```

3. **Verificar PHP:**
   ```bash
   php -v
   php -m | grep -E "curl|mysqli|json|openssl"
   ```

4. **Verificar MySQL:**
   ```bash
   mysql -u ebega99 -p -e "SELECT VERSION();"
   mysql -u ebega99 -p profissapp -e "SHOW TABLES;"
   ```

---

## 🎊 Parabéns!

Se tudo passou no checklist, seu **Profissapp** está:

✅ **Rodando em produção**
✅ **Com banco de dados**
✅ **Integrando Mercado Pago**
✅ **Aceitar pagamentos via PIX**
✅ **Pronto para gerar receita**

**Agora é só divulgar e começar a lucrar! 🚀💰**
