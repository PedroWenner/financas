-- Migration 003: tabela de tokens "Lembrar-me"
-- Execute este script no banco de dados antes de usar a funcionalidade.

CREATE TABLE IF NOT EXISTS remember_tokens (
    id         INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    usuario_id INT UNSIGNED     NOT NULL,
    token_hash VARCHAR(64)      NOT NULL COMMENT 'SHA-256 hex do token raw (nunca armazenar o token em si)',
    expires_at DATETIME         NOT NULL,
    created_at DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE  KEY uq_token_hash  (token_hash),
    INDEX        idx_usuario   (usuario_id),
    INDEX        idx_expires   (expires_at),
    CONSTRAINT fk_rt_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
