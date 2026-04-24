# 🔧 Profissapp - Troubleshooting & Dicas

## 🐛 Problemas Comuns e Soluções

### Frontend React

#### 1. "Port 5173 is in use"
```
Problema: Porta já está em uso
Solução: 
  - Abra outra porta: npm run dev -- --port 3000
  - Ou: Feche outro servidor rodando na porta
  - Ou: Mate processo: lsof -ti:5173 | xargs kill -9 (Mac/Linux)
```

#### 2. "Módulo não encontrado"
```
Problema: Alguma dependência faltando
Solução:
  - Delete node_modules: rm -rf node_modules
  - Reinstale: npm install
  - Limpe cache: npm cache clean --force
```

#### 3. "Erro ao exportar PNG/JPG"
```
Problema: html2canvas pode ter problema com CORS
Solução:
  - Atualizar biblioteca: npm update html2canvas
  - Certificar que as imagens/ícones carregam
  - Testar com imagens locais
```

#### 4. "PDF não gera corretamente"
```
Problema: jsPDF pode ter problema com tamanho
Solução:
  - No App.jsx, ajustar canvas size
  - Testar com preview menor
  - Usar outro formato (PNG/JPG)
```

#### 5. "localStorage não funciona"
```
Problema: Navegador pode ter desabilitado
Solução:
  - Checar private browsing (desabilita localStorage)
  - Permitir cookies/storage
  - Testar em navegador diferente
```

---

### Backend PHP

#### 1. "Connection refused" - MySQL
```
Problema: Banco de dados não conecta
Solução:
  - Verificar credenciais: ebega99 / Gorila93@
  - Verificar host: localhost (não 127.0.0.1)
  - Verificar porta: 3306
  - Verificar se MySQL está rodando
  - Testar: mysql -u ebega99 -p
```

#### 2. "CORS error" - Request bloqueado
```
Problema: Frontend não consegue chamar API
Solução:
  - Certificar que config.php tem headers CORS
  - Adicionar origem correta em ALLOWED_ORIGINS
  - Testar com curl: curl -H "Origin: ..." http://api
  - Verificar .htaccess se tem rewrite rules
```

#### 3. "Erro 404" - Endpoint não encontrado
```
Problema: Arquivo PHP não existe ou rota incorreta
Solução:
  - Verificar caminho do arquivo
  - Verificar nome do arquivo
  - Se usar router, verificar rotas
  - Testar URL direto no navegador
```

#### 4. "Fatal error: Allowed memory exceeded"
```
Problema: Limite de memória do PHP
Solução:
  - Aumentar em php.ini: memory_limit = 256M
  - Ou adicionar no .htaccess:
    php_value memory_limit 256M
  - Ou no início do arquivo:
    ini_set('memory_limit', '256M');
```

#### 5. "Error: Call to undefined function mysqli_connect"
```
Problema: MySQLi não está instalado
Solução:
  - Verificar php.ini: extension=mysqli
  - Reiniciar servidor web
  - Contatar hospedagem se não conseguir ativar
```

---

### Mercado Pago

#### 1. "Invalid access token"
```
Problema: Token do Mercado Pago inválido/expirado
Solução:
  - Regenerar token no painel MP
  - Certificar que é Production ou Sandbox
  - Copiar token exato (sem espaços)
  - Testar com curl
```

#### 2. "Webhook não chegando"
```
Problema: Mercado Pago não consegue chamar seu endpoint
Solução:
  - Verificar URL webhook está correta
  - Certificar que servidor pode receber HTTP POST
  - Verificar firewall/WAF bloqueando
  - Usar ngrok para testar localmente:
    ngrok http 8000
```

#### 3. "QR Code não gera"
```
Problema: Erro ao criar QR no Mercado Pago
Solução:
  - Verificar credenciais
  - Verificar External POS ID
  - Testar API do MP com Postman
  - Ver logs de erro
```

---

## 💡 Dicas Úteis

### Desenvolvimento Local

#### 1. Usar Postman para Testar API
```
1. Baixe Postman
2. Crie requests para cada endpoint
3. Use Environments para dev/prod
4. Salve no GitHub para colaboração
```

#### 2. Ativar Debug Completo
```php
// No topo do arquivo PHP:
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Registrar tudo em arquivo:
ini_set('log_errors', 1);
ini_set('error_log', '/var/log/php-errors.log');
```

#### 3. Usar Console do Browser (F12)
```javascript
// No console do navegador:
localStorage.setItem('test', 'value');
localStorage.getItem('test');
localStorage.clear(); // Limpar tudo
```

#### 4. Ver Requisições HTTP (F12 → Network)
```
1. Abra DevTools (F12)
2. Vá em Network
3. Reload página
4. Veja todas as requisições
5. Clique em cada uma para detalhes
```

### Banco de Dados

#### 1. Fazer Backup Regular
```bash
# Backup
mysqldump -u ebega99 -p profissapp > backup_$(date +%Y%m%d_%H%M%S).sql

# Restaurar
mysql -u ebega99 -p profissapp < backup.sql
```

#### 2. Monitorar Queries Lentas
```sql
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 1; -- segundos

-- Ver logs de query lenta
SHOW VARIABLES LIKE 'slow_query_log%';
```

#### 3. Otimizar Índices
```sql
-- Ver índices de uma tabela
SHOW INDEX FROM users;

-- Analisar performance
EXPLAIN SELECT * FROM users WHERE token = 'xxx';

-- Otimizar tabela
OPTIMIZE TABLE users;
```

### Performance

#### 1. Minificar JavaScript/CSS
```bash
# Com Vite (já faz automaticamente em build)
npm run build

# Resultado: /dist com arquivos minificados
```

#### 2. Comprimir Respostas PHP
```php
// No topo do arquivo:
ob_start('ob_gzhandler');
// ... resto do código
ob_end_flush();
```

#### 3. Cache do Navegador
```php
// Headers para cache:
header('Cache-Control: public, max-age=3600');
header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + 3600));
```

---

## 🔐 Segurança

### Validação de Inputs
```php
// Sempre validar entrada
$token = $_POST['token'] ?? null;

if (!$token || !preg_match('/^PF\d{10}[A-Z0-9]{12}$/', $token)) {
    die(json_encode(['error' => 'Token inválido']));
}
```

### Proteção contra Injection
```php
// NÃO fazer isto:
$sql = "SELECT * FROM users WHERE token = '" . $_POST['token'] . "'";

// Fazer isto:
$stmt = $conn->prepare("SELECT * FROM users WHERE token = ?");
$stmt->bind_param("s", $_POST['token']);
$stmt->execute();
```

### Rate Limiting (Prevenir Abuso)
```php
// Simples rate limit
$ip = $_SERVER['REMOTE_ADDR'];
$key = 'rate_limit_' . $ip;

$redis->incr($key);
if ($redis->ttl($key) < 0) {
    $redis->expire($key, 60); // 1 minuto
}

if ($redis->get($key) > 10) { // 10 requests por minuto
    http_response_code(429);
    die('Too many requests');
}
```

---

## 📊 Monitoramento

### Logs Estruturados
```php
// Classe de logging
class Logger {
    public static function info($message, $data = []) {
        self::write('INFO', $message, $data);
    }
    
    private static function write($level, $message, $data) {
        $log = [
            'timestamp' => date('c'),
            'level' => $level,
            'message' => $message,
            'data' => $data,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null
        ];
        
        file_put_contents(
            LOG_DIR . '/' . date('Y-m-d') . '.log',
            json_encode($log) . PHP_EOL,
            FILE_APPEND
        );
    }
}
```

### Monitorar Erros
```php
// Registrar todos os erros
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    Logger::error("$errstr in $errfile:$errline", ['errno' => $errno]);
});
```

---

## 🚀 Deploy

### Checklist Final

```
[ ] Frontend compilado (npm run build)
[ ] Variáveis de ambiente (.env) configuradas
[ ] Banco de dados criado e populado
[ ] Endpoints testados localmente
[ ] CORS configurado corretamente
[ ] HTTPS habilitado
[ ] Credenciais Mercado Pago ativas
[ ] Webhook configurado em MP
[ ] Logs de erro ativados
[ ] Backup do banco pronto
[ ] DocumentaçãoDocs da API criada
[ ] Monitoramento ativo
```

### Rollback Strategy
```
1. Sempre tenha backup antes de deploy
2. Versione o código (git tags)
3. Mantenha versão anterior rodando
4. Use blue-green deployment se possível
5. Monitore após deploy
```

---

## 📞 Recursos Úteis

### Documentações
- React: https://react.dev
- Vite: https://vitejs.dev
- PHP: https://www.php.net/manual
- MySQL: https://dev.mysql.com/doc
- Mercado Pago: https://developers.mercadopago.com

### Ferramentas
- **Postman**: Testar APIs
- **ngrok**: Expor localhost
- **PHPStorm**: IDE para PHP
- **TablePlus**: Gerenciar BD
- **VS Code**: Editor (já tem tudo)

### Comunidades
- Stack Overflow
- Dev.to
- GitHub Discussions
- Fóruns do Mercado Pago

---

## ✅ Checklist de Deploy

- [ ] Testar em staging
- [ ] Backup do banco
- [ ] HTTPS configurado
- [ ] CORS correto
- [ ] Rate limiting ativo
- [ ] Logs funcionando
- [ ] Monitoramento ativo
- [ ] Alertas configurados
- [ ] Documentação atualizada
- [ ] Suporte pronto

---

**Pronto para resolver qualquer problema! 🎯**
