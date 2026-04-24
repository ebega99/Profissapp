-- ============================================
-- Profissapp - Banco de Dados Completo
-- ============================================
-- Crie o banco de dados antes de executar este script
-- CREATE DATABASE profissapp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Use o banco de dados
USE profissapp;

-- ============================================
-- Tabela: Users (Usuários/Tokens)
-- ============================================
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `token` VARCHAR(255) UNIQUE NOT NULL COMMENT 'Token único gerado (PF + timestamp + aleatório)',
  `status` ENUM('demo', 'profissa') DEFAULT 'demo' COMMENT 'Status atual do usuário',
  `status_activated_at` DATETIME NULL COMMENT 'Data de ativação do Profissa',
  `status_expires_at` DATETIME NULL COMMENT 'Data de expiração do Profissa (30 dias)',
  `restorations_used` INT DEFAULT 0 COMMENT 'Número de restaurações usadas neste mês',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Data de criação',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Última atualização',
  
  INDEX `idx_token` (`token`),
  INDEX `idx_status` (`status`),
  INDEX `idx_expires_at` (`status_expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabela: Payments (Pagamentos)
-- ============================================
CREATE TABLE IF NOT EXISTS `payments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `token` VARCHAR(255) NOT NULL COMMENT 'Token do usuário',
  `payment_id` VARCHAR(255) UNIQUE COMMENT 'ID do pagamento no sistema',
  `amount` DECIMAL(10, 2) DEFAULT 5.00 COMMENT 'Valor do pagamento',
  `currency` VARCHAR(3) DEFAULT 'BRL' COMMENT 'Moeda do pagamento',
  `status` ENUM('pending', 'approved', 'failed', 'cancelled', 'refunded') DEFAULT 'pending' COMMENT 'Status do pagamento',
  `mercado_pago_id` VARCHAR(255) COMMENT 'ID do pagamento no Mercado Pago',
  `mercado_pago_qr` LONGTEXT COMMENT 'Dados do QR Code (JSON)',
  `payment_method` VARCHAR(50) COMMENT 'Método de pagamento usado',
  `external_reference` VARCHAR(255) COMMENT 'Referência externa (nosso token)',
  `paid_at` DATETIME NULL COMMENT 'Data do pagamento aprovado',
  `webhook_received` BOOLEAN DEFAULT FALSE COMMENT 'Webhook recebido do Mercado Pago',
  `webhook_processed` BOOLEAN DEFAULT FALSE COMMENT 'Webhook já foi processado',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`token`) REFERENCES `users`(`token`) ON DELETE CASCADE,
  INDEX `idx_token` (`token`),
  INDEX `idx_status` (`status`),
  INDEX `idx_payment_id` (`payment_id`),
  INDEX `idx_mercado_pago_id` (`mercado_pago_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabela: Restorations (Restaurações de Token)
-- ============================================
CREATE TABLE IF NOT EXISTS `restorations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `token` VARCHAR(255) NOT NULL COMMENT 'Token do usuário',
  `device_identifier` VARCHAR(255) COMMENT 'Identificador do dispositivo (fingerprint/UUID)',
  `device_name` VARCHAR(255) COMMENT 'Nome/descrição do dispositivo',
  `ip_address` VARCHAR(45) COMMENT 'IP da restauração',
  `user_agent` TEXT COMMENT 'User agent do navegador',
  `restored_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `month` INT COMMENT 'Mês da restauração',
  `year` INT COMMENT 'Ano da restauração',
  
  FOREIGN KEY (`token`) REFERENCES `users`(`token`) ON DELETE CASCADE,
  INDEX `idx_token` (`token`),
  INDEX `idx_month_year` (`month`, `year`),
  UNIQUE KEY `unique_restoration` (`token`, `device_identifier`, `month`, `year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabela: Budgets (Orçamentos Salvos)
-- ============================================
CREATE TABLE IF NOT EXISTS `budgets` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `token` VARCHAR(255) NOT NULL COMMENT 'Token do usuário',
  `budget_number` VARCHAR(50) NOT NULL COMMENT 'Número do orçamento',
  `company_name` VARCHAR(255) COMMENT 'Nome da empresa',
  `logo_type` ENUM('hammer', 'zap', 'palette', 'wrench') COMMENT 'Tipo de logo escolhido',
  `items` JSON COMMENT 'Itens do orçamento (JSON)',
  `total_value` DECIMAL(12, 2) COMMENT 'Valor total do orçamento',
  `status` ENUM('draft', 'sent', 'approved', 'rejected') DEFAULT 'draft' COMMENT 'Status do orçamento',
  `exported_format` VARCHAR(10) COMMENT 'Formato exportado (png, jpg, pdf)',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`token`) REFERENCES `users`(`token`) ON DELETE CASCADE,
  INDEX `idx_token` (`token`),
  INDEX `idx_budget_number` (`budget_number`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabela: Webhooks Log
-- ============================================
CREATE TABLE IF NOT EXISTS `webhook_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `event_type` VARCHAR(100) COMMENT 'Tipo de evento (payment.updated, etc)',
  `payment_id` VARCHAR(255) COMMENT 'ID do pagamento',
  `payload` LONGTEXT COMMENT 'Payload completo do webhook',
  `status` ENUM('received', 'processing', 'processed', 'failed') DEFAULT 'received',
  `error_message` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  INDEX `idx_event_type` (`event_type`),
  INDEX `idx_payment_id` (`payment_id`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Tabela: Logs de Acesso
-- ============================================
CREATE TABLE IF NOT EXISTS `access_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `token` VARCHAR(255) COMMENT 'Token do usuário',
  `action` VARCHAR(100) COMMENT 'Ação realizada',
  `endpoint` VARCHAR(255) COMMENT 'Endpoint chamado',
  `method` VARCHAR(10) COMMENT 'Método HTTP',
  `ip_address` VARCHAR(45),
  `user_agent` TEXT,
  `response_status` INT,
  `response_time` INT COMMENT 'Tempo de resposta em ms',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  INDEX `idx_token` (`token`),
  INDEX `idx_action` (`action`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Views Úteis
-- ============================================

-- View: Usuários com Profissa Ativo
CREATE OR REPLACE VIEW `active_profissa_users` AS
SELECT 
    u.id,
    u.token,
    u.status,
    u.status_activated_at,
    u.status_expires_at,
    DATEDIFF(u.status_expires_at, NOW()) as days_remaining,
    u.restorations_used,
    (3 - u.restorations_used) as restorations_available
FROM `users` u
WHERE u.status = 'profissa' AND u.status_expires_at > NOW();

-- View: Estatísticas de Pagamentos
CREATE OR REPLACE VIEW `payment_statistics` AS
SELECT 
    DATE(p.created_at) as payment_date,
    COUNT(*) as total_payments,
    SUM(CASE WHEN p.status = 'approved' THEN 1 ELSE 0 END) as approved_count,
    SUM(CASE WHEN p.status = 'pending' THEN 1 ELSE 0 END) as pending_count,
    SUM(CASE WHEN p.status = 'failed' THEN 1 ELSE 0 END) as failed_count,
    SUM(CASE WHEN p.status = 'approved' THEN p.amount ELSE 0 END) as total_revenue
FROM `payments` p
GROUP BY DATE(p.created_at);

-- View: Tokens que vão expirar em breve (próximos 7 dias)
CREATE OR REPLACE VIEW `expiring_soon` AS
SELECT 
    u.token,
    u.status_expires_at,
    DATEDIFF(u.status_expires_at, NOW()) as days_until_expiry
FROM `users` u
WHERE u.status = 'profissa' 
  AND u.status_expires_at IS NOT NULL
  AND u.status_expires_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY);

-- ============================================
-- Índices Adicionais para Otimização
-- ============================================

ALTER TABLE `users` ADD FULLTEXT INDEX `ft_token` (`token`);
ALTER TABLE `payments` ADD INDEX `idx_created_updated` (`created_at`, `updated_at`);
ALTER TABLE `restorations` ADD INDEX `idx_restored_at` (`restored_at`);
ALTER TABLE `budgets` ADD INDEX `idx_created_updated` (`created_at`, `updated_at`);

-- ============================================
-- Stored Procedures Úteis
-- ============================================

-- Procedure: Processar Expiração de Status
DELIMITER //
CREATE PROCEDURE `process_expired_status`()
BEGIN
    UPDATE `users`
    SET status = 'demo',
        status_expires_at = NULL,
        restorations_used = 0
    WHERE status = 'profissa' 
      AND status_expires_at <= NOW();
END //
DELIMITER ;

-- Procedure: Resetar Restaurações Mensais
DELIMITER //
CREATE PROCEDURE `reset_monthly_restorations`()
BEGIN
    UPDATE `users`
    SET restorations_used = 0
    WHERE MONTH(updated_at) != MONTH(NOW()) OR YEAR(updated_at) != YEAR(NOW());
END //
DELIMITER ;

-- ============================================
-- Dados Iniciais (Opcional)
-- ============================================

-- Você pode adicionar usuários de teste aqui quando necessário
-- INSERT INTO `users` (token, status, created_at) VALUES 
-- ('DEMO_TOKEN_12345', 'demo', NOW());

-- ============================================
-- Triggers (Opcional)
-- ============================================

-- Trigger: Atualizar timestamp de atualização
DELIMITER //
CREATE TRIGGER `users_update_timestamp`
BEFORE UPDATE ON `users`
FOR EACH ROW
BEGIN
    SET NEW.updated_at = NOW();
END //
DELIMITER ;

DELIMITER //
CREATE TRIGGER `payments_update_timestamp`
BEFORE UPDATE ON `payments`
FOR EACH ROW
BEGIN
    SET NEW.updated_at = NOW();
END //
DELIMITER ;

-- ============================================
-- Comentários e Documentação
-- ============================================

-- Para restaurar de um backup:
-- mysql -u ebega99 -p profissapp < backup.sql

-- Para fazer backup:
-- mysqldump -u ebega99 -p profissapp > backup.sql

-- Para acessar o banco:
-- mysql -u ebega99 -p profissapp

-- Host: localhost
-- Porta: 3306
-- Usuário: ebega99
-- Senha: Gorila93@
-- Database: profissapp

-- ============================================
-- FIM DO SCRIPT
-- ============================================
