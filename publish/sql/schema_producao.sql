-- ============================================================
-- AlcatechFinancas — Schema de Producao
-- Compativel com phpMyAdmin e CLI MySQL 8.0+
--
-- INSTRUCOES:
--   phpMyAdmin: Selecione o banco > aba "SQL" > cole e execute
--   CLI:        mysql -h host -u user -p banco < schema_producao.sql
--
-- IMPORTANTE: Execute DEPOIS de criar o banco de dados
--             pelo painel da Hostinger (hPanel > MySQL).
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

-- ────────────────────────────────────────
-- TABELA: usuarios
-- ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `usuarios` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nome`         VARCHAR(100) NOT NULL,
    `email`        VARCHAR(150) NOT NULL,
    `senha_hash`   VARCHAR(255) NOT NULL,
    `ativo`        TINYINT(1)   NOT NULL DEFAULT 1,
    `ultimo_login` DATETIME         NULL,
    `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_email` (`email`),
    INDEX `idx_email_ativo` (`email`, `ativo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────
-- TABELA: categorias
-- ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `categorias` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `usuario_id` INT UNSIGNED NOT NULL,
    `nome`       VARCHAR(80)  NOT NULL,
    `tipo`       ENUM('receita','despesa','ambos') NOT NULL DEFAULT 'ambos',
    `icone`      VARCHAR(50)      NULL,
    `cor`        VARCHAR(7)       NULL DEFAULT '#6c757d',
    `ativo`      TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    CONSTRAINT `fk_cat_usuario` FOREIGN KEY (`usuario_id`)
        REFERENCES `usuarios`(`id`) ON DELETE CASCADE,
    INDEX `idx_cat_usuario_tipo` (`usuario_id`, `tipo`, `ativo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────
-- TABELA: cartoes
-- ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `cartoes` (
    `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `usuario_id`     INT UNSIGNED NOT NULL,
    `nome`           VARCHAR(80)  NOT NULL,
    `bandeira`       ENUM('visa','mastercard','elo','amex','hipercard','outro') NOT NULL DEFAULT 'outro',
    `limite`         DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `dia_fechamento` TINYINT      NOT NULL,
    `dia_vencimento` TINYINT      NOT NULL,
    `cor`            VARCHAR(7)       NULL DEFAULT '#2563eb',
    `ativo`          TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    CONSTRAINT `fk_cartao_usuario` FOREIGN KEY (`usuario_id`)
        REFERENCES `usuarios`(`id`) ON DELETE CASCADE,
    INDEX `idx_cartao_usuario_ativo` (`usuario_id`, `ativo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────
-- TABELA: receitas
-- ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `receitas` (
    `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `usuario_id`   INT UNSIGNED  NOT NULL,
    `categoria_id` INT UNSIGNED      NULL,
    `descricao`    VARCHAR(200)  NOT NULL,
    `valor`        DECIMAL(12,2) NOT NULL,
    `data`         DATE          NOT NULL,
    `tipo_receita` ENUM('salario','salario_conjuge','renda_extra','investimento','outro') NOT NULL DEFAULT 'outro',
    `observacoes`  TEXT              NULL,
    `created_at`   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    CONSTRAINT `fk_rec_usuario`   FOREIGN KEY (`usuario_id`)   REFERENCES `usuarios`(`id`)   ON DELETE CASCADE,
    CONSTRAINT `fk_rec_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias`(`id`) ON DELETE SET NULL,
    INDEX `idx_rec_usuario_data` (`usuario_id`, `data`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────
-- TABELA: despesas_fixas
-- ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `despesas_fixas` (
    `id`             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `usuario_id`     INT UNSIGNED  NOT NULL,
    `categoria_id`   INT UNSIGNED      NULL,
    `descricao`      VARCHAR(200)  NOT NULL,
    `valor`          DECIMAL(12,2) NOT NULL,
    `dia_vencimento` TINYINT       NOT NULL,
    `data_inicio`    DATE          NOT NULL,
    `data_fim`       DATE              NULL,
    `ativo`          TINYINT(1)    NOT NULL DEFAULT 1,
    `observacoes`    TEXT              NULL,
    `created_at`     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    CONSTRAINT `fk_df_usuario`   FOREIGN KEY (`usuario_id`)   REFERENCES `usuarios`(`id`)   ON DELETE CASCADE,
    CONSTRAINT `fk_df_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias`(`id`) ON DELETE SET NULL,
    INDEX `idx_df_usuario_ativo` (`usuario_id`, `ativo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────
-- TABELA: parcelamentos
-- ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `parcelamentos` (
    `id`                    INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `usuario_id`            INT UNSIGNED  NOT NULL,
    `cartao_id`             INT UNSIGNED      NULL,
    `categoria_id`          INT UNSIGNED      NULL,
    `descricao`             VARCHAR(200)  NOT NULL,
    `valor_total`           DECIMAL(12,2) NOT NULL,
    `numero_parcelas`       TINYINT       NOT NULL,
    `valor_parcela`         DECIMAL(12,2) NOT NULL,
    `data_compra`           DATE          NOT NULL,
    `data_primeira_parcela` DATE          NOT NULL,
    `observacoes`           TEXT              NULL,
    `created_at`            DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`            DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    CONSTRAINT `fk_parc_usuario`   FOREIGN KEY (`usuario_id`)   REFERENCES `usuarios`(`id`)    ON DELETE CASCADE,
    CONSTRAINT `fk_parc_cartao`    FOREIGN KEY (`cartao_id`)    REFERENCES `cartoes`(`id`)     ON DELETE SET NULL,
    CONSTRAINT `fk_parc_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias`(`id`)  ON DELETE SET NULL,
    INDEX `idx_parc_usuario` (`usuario_id`),
    INDEX `idx_parc_cartao`  (`cartao_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────
-- TABELA: parcelas
-- ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `parcelas` (
    `id`               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `parcelamento_id`  INT UNSIGNED  NOT NULL,
    `numero_parcela`   TINYINT       NOT NULL,
    `valor`            DECIMAL(12,2) NOT NULL,
    `data_vencimento`  DATE          NOT NULL,
    `pago`             TINYINT(1)    NOT NULL DEFAULT 0,
    `data_pagamento`   DATE              NULL,
    `created_at`       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    CONSTRAINT `fk_parcela_parcelamento` FOREIGN KEY (`parcelamento_id`)
        REFERENCES `parcelamentos`(`id`) ON DELETE CASCADE,
    INDEX `idx_parcela_venc`        (`parcelamento_id`, `data_vencimento`),
    INDEX `idx_parcela_pago`        (`parcelamento_id`, `pago`),
    INDEX `idx_parcela_global_venc` (`data_vencimento`, `pago`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────
-- TABELA: despesas
-- ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `despesas` (
    `id`              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `usuario_id`      INT UNSIGNED  NOT NULL,
    `categoria_id`    INT UNSIGNED      NULL,
    `cartao_id`       INT UNSIGNED      NULL,
    `parcela_id`      INT UNSIGNED      NULL,
    `despesa_fixa_id` INT UNSIGNED      NULL,
    `descricao`       VARCHAR(200)  NOT NULL,
    `valor`           DECIMAL(12,2) NOT NULL,
    `data`            DATE          NOT NULL,
    `tipo`            ENUM('fixa','variavel','parcelada') NOT NULL DEFAULT 'variavel',
    `forma_pagamento` ENUM('dinheiro','debito','credito','pix','ted','boleto','outro') NOT NULL DEFAULT 'outro',
    `pago`            TINYINT(1)    NOT NULL DEFAULT 0,
    `data_pagamento`  DATE              NULL,
    `observacoes`     TEXT              NULL,
    `created_at`      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    CONSTRAINT `fk_desp_usuario`   FOREIGN KEY (`usuario_id`)      REFERENCES `usuarios`(`id`)      ON DELETE CASCADE,
    CONSTRAINT `fk_desp_categoria` FOREIGN KEY (`categoria_id`)    REFERENCES `categorias`(`id`)    ON DELETE SET NULL,
    CONSTRAINT `fk_desp_cartao`    FOREIGN KEY (`cartao_id`)       REFERENCES `cartoes`(`id`)       ON DELETE SET NULL,
    CONSTRAINT `fk_desp_parcela`   FOREIGN KEY (`parcela_id`)      REFERENCES `parcelas`(`id`)      ON DELETE SET NULL,
    CONSTRAINT `fk_desp_fixa`      FOREIGN KEY (`despesa_fixa_id`) REFERENCES `despesas_fixas`(`id`) ON DELETE SET NULL,

    INDEX `idx_desp_usuario_data` (`usuario_id`, `data`),
    INDEX `idx_desp_cartao_data`  (`cartao_id`,  `data`),
    INDEX `idx_desp_tipo`         (`usuario_id`, `tipo`),
    INDEX `idx_desp_pago`         (`usuario_id`, `pago`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ────────────────────────────────────────
-- VIEWS
-- ────────────────────────────────────────
CREATE OR REPLACE VIEW `vw_parcelas_futuras` AS
SELECT
    p.id,
    par.descricao,
    par.usuario_id,
    par.cartao_id,
    c.nome        AS cartao_nome,
    c.cor         AS cartao_cor,
    p.numero_parcela,
    par.numero_parcelas,
    p.valor,
    p.data_vencimento,
    p.pago
FROM `parcelas` p
JOIN `parcelamentos` par ON par.id = p.parcelamento_id
LEFT JOIN `cartoes` c ON c.id = par.cartao_id
WHERE p.pago = 0
ORDER BY p.data_vencimento;

CREATE OR REPLACE VIEW `vw_gastos_cartao` AS
SELECT
    c.id         AS cartao_id,
    c.usuario_id,
    c.nome,
    c.bandeira,
    c.limite,
    c.cor,
    YEAR(d.data)  AS ano,
    MONTH(d.data) AS mes,
    COALESCE(SUM(d.valor), 0)            AS total_gasto,
    c.limite - COALESCE(SUM(d.valor), 0) AS limite_disponivel
FROM `cartoes` c
LEFT JOIN `despesas` d ON d.cartao_id = c.id
WHERE c.ativo = 1
GROUP BY c.id, c.usuario_id, c.nome, c.bandeira, c.limite, c.cor, YEAR(d.data), MONTH(d.data);

-- ────────────────────────────────────────
-- FIM DO SCHEMA
-- As 12 categorias padrao sao inseridas
-- automaticamente pelo PHP no cadastro.
-- ────────────────────────────────────────
