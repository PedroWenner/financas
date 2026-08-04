-- Migration 005: scripts salvos do construtor de consulta (/admin/sql, aba "Construtor de Consulta")

CREATE TABLE IF NOT EXISTS sql_console_consultas (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id  INT UNSIGNED NOT NULL,
    descricao   VARCHAR(150) NOT NULL,
    script      TEXT         NOT NULL,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_sql_console_consultas_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_sql_console_consultas_usuario (usuario_id, descricao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
