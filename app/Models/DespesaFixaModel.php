<?php
/**
 * DespesaFixaModel — Templates de despesas recorrentes mensais.
 */

declare(strict_types=1);

class DespesaFixaModel extends BaseModel
{
    protected string $table = 'despesas_fixas';

    /**
     * Lista todos os templates ativos do usuário.
     */
    public function listar(int $usuarioId, bool $apenasAtivos = true): array
    {
        $where = "df.usuario_id = :uid";
        if ($apenasAtivos) $where .= " AND df.ativo = 1";

        $stmt = $this->db->prepare("
            SELECT df.*, c.nome AS categoria_nome, c.cor AS categoria_cor
            FROM despesas_fixas df
            LEFT JOIN categorias c ON c.id = df.categoria_id
            WHERE {$where}
            ORDER BY df.descricao ASC
        ");
        $stmt->execute([':uid' => $usuarioId]);
        return $stmt->fetchAll();
    }

    /**
     * Soma dos templates ativos (valor mensal fixo estimado).
     */
    public function totalMensalEstimado(int $usuarioId): float
    {
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(valor), 0) FROM despesas_fixas
            WHERE usuario_id = :uid AND ativo = 1
        ");
        $stmt->execute([':uid' => $usuarioId]);
        return (float) $stmt->fetchColumn();
    }

    /**
     * Cria novo template de despesa fixa.
     */
    public function criar(int $usuarioId, array $dados): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO despesas_fixas
                (usuario_id, categoria_id, descricao, valor,
                 dia_vencimento, data_inicio, data_fim, observacoes)
            VALUES
                (:uid, :cat, :desc, :valor, :dia, :inicio, :fim, :obs)
        ");
        $stmt->execute([
            ':uid'    => $usuarioId,
            ':cat'    => $dados['categoria_id']  ?: null,
            ':desc'   => trim($dados['descricao']),
            ':valor'  => $dados['valor'],
            ':dia'    => (int) $dados['dia_vencimento'],
            ':inicio' => $dados['data_inicio'],
            ':fim'    => $dados['data_fim']      ?: null,
            ':obs'    => $dados['observacoes']   ?: null,
        ]);
        return $this->lastId();
    }

    /**
     * Atualiza template (valida dono).
     */
    public function atualizar(int $id, int $usuarioId, array $dados): bool
    {
        $stmt = $this->db->prepare("
            UPDATE despesas_fixas
            SET categoria_id   = :cat,
                descricao      = :desc,
                valor          = :valor,
                dia_vencimento = :dia,
                data_inicio    = :inicio,
                data_fim       = :fim,
                observacoes    = :obs
            WHERE id = :id AND usuario_id = :uid
        ");
        $stmt->execute([
            ':cat'    => $dados['categoria_id']  ?: null,
            ':desc'   => trim($dados['descricao']),
            ':valor'  => $dados['valor'],
            ':dia'    => (int) $dados['dia_vencimento'],
            ':inicio' => $dados['data_inicio'],
            ':fim'    => $dados['data_fim']      ?: null,
            ':obs'    => $dados['observacoes']   ?: null,
            ':id'     => $id,
            ':uid'    => $usuarioId,
        ]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Desativa template (não gera mais lançamentos futuros).
     */
    public function desativar(int $id, int $usuarioId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE despesas_fixas SET ativo = 0 WHERE id = :id AND usuario_id = :uid"
        );
        $stmt->execute([':id' => $id, ':uid' => $usuarioId]);
        return $stmt->rowCount() > 0;
    }

    public function ativar(int $id, int $usuarioId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE despesas_fixas SET ativo = 1 WHERE id = :id AND usuario_id = :uid"
        );
        $stmt->execute([':id' => $id, ':uid' => $usuarioId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Gera os lançamentos na tabela `despesas` para um mês específico,
     * caso ainda não tenham sido gerados.
     */
    public function gerarLancamentos(int $usuarioId, int $ano, int $mes): int
    {
        $primeiroDia = sprintf('%04d-%02d-01', $ano, $mes);
        $ultimoDia   = date('Y-m-t', strtotime($primeiroDia));

        // Busca apenas templates que ainda não foram gerados neste mês
        $stmtTpl = $this->db->prepare("
            SELECT * FROM despesas_fixas
            WHERE usuario_id = :uid
              AND ativo = 1
              AND data_inicio <= :ultimo
              AND (data_fim IS NULL OR data_fim >= :primeiro)
              AND NOT EXISTS (
                  SELECT 1 FROM despesas d2
                  WHERE d2.usuario_id = :uid2
                    AND d2.despesa_fixa_id = despesas_fixas.id
                    AND YEAR(d2.data) = :ano AND MONTH(d2.data) = :mes
              )
        ");
        $stmtTpl->execute([
            ':uid'      => $usuarioId,
            ':uid2'     => $usuarioId,
            ':primeiro' => $primeiroDia,
            ':ultimo'   => $ultimoDia,
            ':ano'      => $ano,
            ':mes'      => $mes,
        ]);
        $templates = $stmtTpl->fetchAll();

        if (empty($templates)) return 0;

        $stmtIns = $this->db->prepare("
            INSERT INTO despesas
                (usuario_id, categoria_id, despesa_fixa_id,
                 descricao, valor, data, tipo, forma_pagamento)
            VALUES
                (:uid, :cat, :df_id, :desc, :valor, :data, 'fixa', 'boleto')
        ");

        $count = 0;
        foreach ($templates as $t) {
            $diasNoMes = (int) date('t', strtotime($primeiroDia));
            $dia       = min((int) $t['dia_vencimento'], $diasNoMes);
            $data      = sprintf('%04d-%02d-%02d', $ano, $mes, $dia);

            $stmtIns->execute([
                ':uid'   => $usuarioId,
                ':cat'   => $t['categoria_id'],
                ':df_id' => $t['id'],
                ':desc'  => $t['descricao'],
                ':valor' => $t['valor'],
                ':data'  => $data,
            ]);
            $count++;
        }

        return $count;
    }
}
