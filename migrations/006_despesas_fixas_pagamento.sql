-- Adiciona forma de pagamento e cartão de crédito às despesas fixas,
-- e permite vencimento até o último dia do mês (deixa de ser limitado a 28)
ALTER TABLE despesas_fixas
    MODIFY COLUMN dia_vencimento TINYINT NOT NULL COMMENT 'Dia do mês (1-31); ver dia_vencimento_ultimo_dia',
    ADD COLUMN dia_vencimento_ultimo_dia TINYINT(1) NOT NULL DEFAULT 0
        COMMENT 'Quando 1, ignora o número em dia_vencimento e usa sempre o último dia do mês'
        AFTER dia_vencimento,
    ADD COLUMN forma_pagamento ENUM('dinheiro','debito','credito','pix','ted','boleto','outro')
        NOT NULL DEFAULT 'boleto'
        AFTER dia_vencimento_ultimo_dia,
    ADD COLUMN cartao_id INT UNSIGNED NULL AFTER forma_pagamento,
    ADD CONSTRAINT fk_df_cartao FOREIGN KEY (cartao_id) REFERENCES cartoes(id) ON DELETE SET NULL,
    ADD INDEX idx_df_cartao (cartao_id);
