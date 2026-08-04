-- Migration 004: log de auditoria do console SQL (/admin/sql)
-- Nunca grava senha — só host/banco/preview do SQL executado e o resultado.

CREATE TABLE IF NOT EXISTS sql_console_log (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id  INT UNSIGNED NOT NULL,
    host        VARCHAR(255) NOT NULL,
    banco       VARCHAR(150) NOT NULL,
    sql_preview TEXT         NOT NULL,
    status      ENUM('sucesso','erro') NOT NULL,
    mensagem    VARCHAR(500) NULL,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_sql_console_log_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_sql_console_log_usuario (usuario_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
