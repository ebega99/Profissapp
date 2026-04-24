# 🧪 Profissapp - Guia de Testes do Frontend

## ✅ O Que Está Funcionando

Todas as funcionalidades do frontend estão 100% operacionais:

### 1. Sistema de Token ✅
- [x] Token gerado automaticamente na primeira vez
- [x] Token salvo em `localStorage`
- [x] Modal aparece na primeira vez para copiar token
- [x] Botão copy com feedback visual

**Como testar:**
1. Abra http://localhost:5174
2. Veja o modal com seu token
3. Clique no botão de copiar
4. Atualize a página - token persiste
5. Abra DevTools (F12) → Application → LocalStorage → profisstoken

### 2. Seletor de Logos ✅
- [x] 4 logos diferentes disponíveis
- [x] Seleção visual (com efeito ativo)
- [x] Atualização dinâmica no preview

**Como testar:**
1. Na seção "Dados da Empresa"
2. Clique em cada logo (Martelo, Raio, Pincel, Chave)
3. Veja o logo atualizar no preview abaixo
4. Clique novamente para deselecionar e reselecionar

### 3. Formulário de Dados ✅
- [x] Campo nome da empresa (máximo 50 caracteres)
- [x] Campo número de orçamento (pode incrementar)
- [x] Validação de entrada

**Como testar:**
1. Digite um nome de empresa com até 50 caracteres
2. Mude o número do orçamento
3. Veja atualizações em tempo real no preview

### 4. Lista de Itens Dinâmica ✅
- [x] Adicionar novos itens
- [x] Remover itens
- [x] Editar descrição, quantidade e valor
- [x] Cálculo automático total

**Como testar:**
1. Clique "+ Adicionar Item" várias vezes
2. Preencha:
   - Descrição: "Manutenção Elétrica"
   - Qtd: 2
   - Valor: 150.00
3. Veja total atualizar automaticamente
4. Clique X para remover um item
5. Total recalcula sozinho

### 5. Preview em Tempo Real ✅
- [x] Atualiza ao digitar
- [x] Layout profissional
- [x] Tarja "DEMONSTRAÇÃO" visível
- [x] Tabela com formatação
- [x] Total destacado
- [x] Data automática

**Como testar:**
1. Preencha todos os dados
2. Note que tudo atualiza no preview conforme digita
3. O layout está profissional com:
   - Logo grande
   - Nome da empresa
   - Número do orçamento
   - Tabela com descrição, qtd, valor unit, total
   - Tarja discreta "DEMONSTRAÇÃO"
   - Data e validade

### 6. Exportação em PNG ✅
- [x] Botão PNG captura o preview
- [x] Download automático
- [x] Nome: `orcamento-NUMERO.png`

**Como testar:**
1. Preencha um orçamento completo
2. Clique botão "PNG"
3. Verifique se o arquivo baixou
4. Abra a imagem (contém tudo do preview)

### 7. Exportação em JPG ✅
- [x] Botão JPG captura o preview
- [x] Download automático
- [x] Nome: `orcamento-NUMERO.jpg`

**Como testar:**
1. Preencha um orçamento completo
2. Clique botão "JPG"
3. Verifique se o arquivo baixou
4. Qualidade igual ao PNG

### 8. Exportação em PDF ✅
- [x] Botão PDF captura e converte
- [x] Download automático
- [x] Nome: `orcamento-NUMERO.pdf`

**Como testar:**
1. Preencha um orçamento completo
2. Clique botão "PDF"
3. Verifique se o arquivo baixou
4. Abra o PDF em qualquer leitor
5. Verifique se o conteúdo está correto

### 9. Status Indicator ✅
- [x] Bolinha verde com animação
- [x] Mostra "Demo" no status
- [x] Pode atualizar para "Profissa" (via backend)
- [x] Mostra dias restantes (quando ativo)
- [x] Mostra restaurações usadas

**Como testar:**
1. Veja o header
2. Status aparece como "Demo" (verde)
3. Quando integrar com backend, mudará para "Profissa"
4. Dias restantes aparecerão automaticamente

### 10. Modal de Pagamento ✅
- [x] Botão "Um cafezinho" aparece em modo Demo
- [x] Modal abre sem sair da página
- [x] QR Code gerado automaticamente
- [x] Token exibido
- [x] Status de aguardando pagamento
- [x] Botão X para fechar
- [x] Clique fora fecha modal

**Como testar:**
1. App está em modo Demo (padrão)
2. Veja botão "Um cafezinho" abaixo do preview
3. Clique nele
4. Modal aparece com:
   - QR Code (gerado com seu token)
   - Token exibido
   - Status aguardando pagamento
   - Spinner animado
5. Clique X ou fora do modal para fechar

### 11. Responsividade ✅
- [x] Desktop: 2 colunas
- [x] Tablet: Adaptado
- [x] Mobile: 1 coluna vertical
- [x] Muito responsivo (até 380px)

**Como testar:**
1. **Desktop (1400px+):**
   - F12 → Resize → Desktop
   - Form à esquerda, Preview à direita
   
2. **Tablet (768px-1024px):**
   - F12 → Resize → iPad
   - Layout adapta
   
3. **Mobile (até 640px):**
   - F12 → Resize → iPhone
   - Preview em cima, Form embaixo
   - Totalmente vertical
   - Tudo legível
   
4. **Muito Pequeno (380px):**
   - Inputs encolhem
   - Tabela se adapta
   - Tudo funciona

## 🔴 O Que NÃO Está Funcionando (Previsto)

Essas funcionalidades dependem do backend:

### 1. Pagamento Real ❌
- Mercado Pago não está integrado
- Webhook não está configurado
- Status não muda automaticamente para "Profissa"

**Será implementado quando:** Credenciais do Mercado Pago forem fornecidas

### 2. Persistência de Dados no Backend ❌
- Orçamentos não são salvos
- Status não é verificado no banco
- Restaurações não são contadas

**Será implementado quando:** Backend PHP for criado

### 3. Sincronização Entre Dispositivos ❌
- Token funciona localmente
- Restauração não é registrada
- Dias restantes não são sincronizados

**Será implementado quando:** Backend estiver pronto

## 🎯 Casos de Uso para Testar

### Caso 1: Criar um Orçamento Simples
```
1. Escolha: Martelo
2. Empresa: "Eletricista João"
3. Número: 1001
4. Item 1: "Fiação nova sala" | 1 | 200
5. Item 2: "Tomadas e interruptores" | 1 | 150
6. Exporte em PNG
7. Veja resultado
```

### Caso 2: Múltiplos Itens
```
1. Escolha: Pincel
2. Empresa: "Pinturas Premium"
3. Número: 1005
4. Item 1: "Pintura sala (tinta + mão de obra)" | 2 | 500
5. Item 2: "Pintura cozinha (tinta + mão de obra)" | 1 | 300
6. Item 3: "Acabamento especial" | 1 | 200
7. Total deve ser: 1500
8. Exporte em PDF
```

### Caso 3: Orçamento Detalhado
```
1. Escolha: Chave de Fenda
2. Empresa: "Hidráulica Geral"
3. Número: 2050
4. Múltiplos itens com valores variados
5. Teste edição de itens
6. Teste exclusão de itens
7. Teste todas as exportações
8. Copie o token para outro dispositivo depois
```

### Caso 4: Responsividade
```
1. Crie um orçamento no desktop
2. Redimensione para tablet (F12)
3. Redimensione para mobile
4. Verifique se tudo funciona em cada tamanho
5. Tente editar em cada resolução
6. Exporte em cada resolução
```

## 📊 Checklist Final do Frontend

```
[ ] Token gerado e persistido
[ ] Modal de token aparece na primeira vez
[ ] Copiar token funciona
[ ] Logos estão selecionáveis
[ ] Preview atualiza em tempo real
[ ] Adicionar itens funciona
[ ] Remover itens funciona
[ ] Cálculos estão corretos
[ ] PNG exporta corretamente
[ ] JPG exporta corretamente
[ ] PDF exporta corretamente
[ ] Modal de pagamento abre
[ ] QR Code aparece
[ ] Responsividade funciona em todas resoluções
[ ] Header está profissional
[ ] Status aparece como "Demo"
[ ] Tarja de demonstração é discreta
[ ] Botão "Um cafezinho" aparece
[ ] Todos os ícones carregam
[ ] Sem erros no console (F12)
[ ] Performance está boa
```

## 🚀 Próximo Passo

Quando estiver satisfeito com o frontend:

1. **Crie o Backend PHP**
   - Use o arquivo `BACKEND.md`
   - Use o arquivo `database.sql`
   - Configure Mercado Pago

2. **Integre Frontend + Backend**
   - Atualize URLs de API em `App.jsx`
   - Conecte endpoints
   - Teste fluxo completo

3. **Teste Pagamento Real**
   - Configure webhook
   - Teste com QR Code real
   - Valide mudança de status

## 📞 Suporte

Se encontrar algum bug:
1. Abra DevTools (F12)
2. Verifique Console (aba vermelhinha)
3. Anote qualquer erro
4. Verifique qual funcionalidade não funciona
5. Reporte com print do erro

---

**Tudo pronto para começar a usar! 🎉**
