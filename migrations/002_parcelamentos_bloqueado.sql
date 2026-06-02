-- Adiciona coluna para bloquear exibição de parcelas a partir de um mês/ano
ALTER TABLE parcelamentos
    ADD COLUMN bloqueado_a_partir DATE NULL DEFAULT NULL
        COMMENT 'Quando preenchido, parcelas com data_vencimento >= este valor não aparecem nas despesas'
    AFTER observacoes;
