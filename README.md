# 🔨 Profissapp - Gerador de Orçamentos

Aplicação web moderna para profissionais autônomos gerarem orçamentos profissionais com um clique. Desenvolvido com React + Vite e integrável com PHP/MySQL e Mercado Pago.

## ✨ Funcionalidades Implementadas (Frontend)

### 🎯 Status e Autenticação
- ✅ Sistema de token automático (sem login necessário)
- ✅ Indicador de Status: **Demo** (verde) vs **Profissa** (ativo)
- ✅ Contador de dias restantes (quando ativo)
- ✅ Sistema de restaurações (até 3x por mês)
- ✅ Modal com opção de copiar token

### 🎨 Seletor de Logos
- ✅ 4 ícones padrão: Martelo, Raio, Pincel, Chave de Fenda
- ✅ Seleção visual intuitiva
- ✅ Exibição dinâmica no orçamento

### 📝 Formulário de Orçamento
- ✅ Campo de nome da empresa (fantasia)
- ✅ Número sequencial automático
- ✅ Lista dinâmica de itens com:
  - Descrição do serviço
  - Quantidade
  - Valor unitário
  - Botão para remover
  - Botão para adicionar novos itens

### 👁️ Preview em Tempo Real
- ✅ Atualização ao vivo conforme digitação
- ✅ Tarja "DEMONSTRAÇÃO" discreta no modo demo
- ✅ Logo profissional
- ✅ Tabela formatada com cálculo automático
- ✅ Total geral destacado
- ✅ Data e validade do orçamento

### 💾 Exportação de Orçamentos
- ✅ Botão PNG - Exportar como imagem PNG
- ✅ Botão JPG - Exportar como imagem JPG
- ✅ Botão PDF - Exportar como PDF
- ✅ Nomes automáticos (orcamento-NUMERO.ext)

### 💰 Sistema de Pagamento
- ✅ Modal de pagamento modal (não sai da página)
- ✅ QR Code gerado automaticamente com token
- ✅ Indicador de aguardando confirmação
- ✅ Estrutura pronta para Mercado Pago

### 📱 Responsividade
- ✅ Desktop: Layout em 2 colunas (Form | Preview)
- ✅ Tablet: Adaptação automática
- ✅ Mobile: Layout vertical (Form em cima, Preview embaixo)
- ✅ Totalmente otimizado para celulares
- ✅ Suporte a telas pequenas (380px+)

## 🚀 Como Usar

### Instalação
```bash
cd "d:\Users\Edu_Durr\Desktop\Fibra99\profissapp\VSCode Profissapp"
npm install
npm run dev
```

Acesse: `http://localhost:5174`

### Uso do App
1. **Primeira vez**: Token gerado automaticamente - copie e guarde
2. **Escolha um logo**: Clique em um dos 4 ícones
3. **Preencha dados**: Nome da empresa e itens do orçamento
4. **Veja preview**: Atualiza em tempo real embaixo
5. **Exporte**: PNG, JPG ou PDF
6. **Pague** (opcional): Clique "Um cafezinho" para remover demo

## 🛠️ Stack Técnico

### Frontend (Completo ✅)
- **React 18** - UI framework
- **Vite** - Build tool & dev server
- **Lucide React** - Ícones vetoriais
- **html2canvas** - Captura de tela (PNG/JPG)
- **jsPDF** - Geração de PDF
- **qrcode.react** - Geração de QR Code

### Backend (Próximo ⏳)
- **PHP** - Server-side
- **MySQL** - Database (Plesk)
- **Mercado Pago API** - Pagamentos

## 📁 Estrutura de Arquivos

```
src/
├── App.jsx          # Componente principal
├── App.css          # Estilos responsivos
├── main.jsx         # Entrada React
└── index.css        # Estilos globais

index.html           # HTML principal
vite.config.js       # Configuração Vite
package.json         # Dependências
```

## 🔧 Backend - O Que Precisa Ser Feito

### 1. Banco de Dados (MySQL)
```sql
-- Será criado um arquivo SQL com estrutura completa
-- Tabelas: users, tokens, payments, budgets
```

### 2. API PHP (Endpoints Necessários)
```
POST   /api/generate-token      → Gera novo token
POST   /api/verify-token        → Valida token
GET    /api/user-status         → Retorna status do usuário
POST   /api/process-payment     → Processa pagamento
GET    /api/payment-status      → Verifica status de pagamento
POST   /api/restore-token       → Restaura em outro dispositivo
```

### 3. Integração Mercado Pago
- Configurar credenciais
- Webhook para confirmar pagamento
- Atualizar status automaticamente
- Sistema de 30 dias com renovação

## 💡 Como Funciona o Sistema

### Fluxo Demo → Profissa
1. Usuário entra pela primeira vez
2. Token gerado automaticamente
3. Armazenado em `localStorage`
4. Status aparece como **Demo** (verde)
5. Tarja de demonstração aparece nos orçamentos
6. Usuário clica "Um cafezinho"
7. Modal com QR Code aparece
8. Escaneando o QR Code → Mercado Pago (webhook retorna)
9. Status muda para **Profissa** (30 dias)
10. Tarja desaparece automaticamente

### Sistema de Restauração
- Cada token gerado permite até **3 restaurações** por mês
- Restauração = usar o mesmo token em outro dispositivo
- Contador aparece: "Restaurações 2/3"
- Após 30 dias, volta para Demo automaticamente

## 📝 Configuração Banco de Dados

Seus dados de conexão (já disponível em Plesk):
```
Server: localhost
User: ebega99
Password: Gorila93@
Database: profissapp
Port: 3306
```

## 🎯 Status do Projeto

- ✅ **Frontend**: 100% Completo e Funcional
- ⏳ **Backend**: Pronto para Implementação
- ⏳ **Mercado Pago**: Aguardando Credenciais

## 📞 Próximas Etapas

1. Criar Backend PHP com endpoints
2. Integrar Mercado Pago com API
3. Conectar Frontend ao Backend
4. Testar fluxo completo de pagamento
5. Deploy em produção

---

**Desenvolvido com ❤️ para profissionais autônomos**
