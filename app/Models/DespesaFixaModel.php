<?php
/**
 * DespesaFixaModel — Templates de despesas recorrentes mensais.
 */

declare(strict_types=1);

class DespesaFixaModel extends BaseModel
{
    protected string $table = 'despesas_fixas';

    /**
     * Lista os templates do usuário, com filtros opcionais de status,
     * descrição (busca parcial) e forma de pagamento.
     *
     * @param int|null $ativo null = todas, 1 = apenas ativas, 0 = apenas inativas
     */
    public function listar(
        int $usuarioId,
        ?int $ativo = null,
        ?string $descricao = null,
        ?string $formaPagamento = null
    ): array {
        $where  = "df.usuario_id = :uid";
        $params = [':uid' => $usuarioId];

        if ($ativo !== null) {
            $where .= " AND df.ativo = :ativo";
            $params[':ativo'] = $ativo;
        }
        if ($descricao) {
            $where .= " AND df.descricao LIKE :desc";
            $params[':desc'] = '%' . $descricao . '%';
        }
        if ($formaPagamento) {
            $where .= " AND df.forma_pagamento = :forma";
            $params[':forma'] = $formaPagamento;
        }

        $stmt = $this->db->prepare("
            SELECT df.*, c.nome AS categoria_nome, c.cor AS categoria_cor,
                   ct.nome AS cartao_nome, ct.cor AS cartao_cor,
                   ct.dia_fechamento AS cartao_dia_fechamento,
                   ct.dia_vencimento AS cartao_dia_vencimento
            FROM despesas_fixas df
            LEFT JOIN categorias c ON c.id = df.categoria_id
            LEFT JOIN cartoes    ct ON ct.id = df.cartao_id
            WHERE {$where}
            ORDER BY df.descricao ASC
        ");
        $stmt->execute($params);
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
                 dia_vencimento, dia_vencimento_ultimo_dia, forma_pagamento, cartao_id,
                 data_inicio, data_fim, observacoes)
            VALUES
                (:uid, :cat, :desc, :valor, :dia, :dia_ultimo, :forma, :cartao, :inicio, :fim, :obs)
        ");
        $stmt->execute([
            ':uid'        => $usuarioId,
            ':cat'        => $dados['categoria_id']  ?: null,
            ':desc'       => trim($dados['descricao']),
            ':valor'      => $dados['valor'],
            ':dia'        => (int) $dados['dia_vencimento'],
            ':dia_ultimo' => (int) $dados['dia_vencimento_ultimo_dia'],
            ':forma'      => $dados['forma_pagamento'],
            ':cartao'     => $dados['forma_pagamento'] === 'credito' ? ($dados['cartao_id'] ?: null) : null,
            ':inicio'     => $dados['data_inicio'],
            ':fim'        => $dados['data_fim']      ?: null,
            ':obs'        => $dados['observacoes']   ?: null,
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
            SET categoria_id              = :cat,
                descricao                 = :desc,
                valor                     = :valor,
                dia_vencimento            = :dia,
                dia_vencimento_ultimo_dia = :dia_ultimo,
                forma_pagamento           = :forma,
                cartao_id                 = :cartao,
                data_inicio               = :inicio,
                data_fim                  = :fim,
                observacoes               = :obs
            WHERE id = :id AND usuario_id = :uid
        ");
        $stmt->execute([
            ':cat'        => $dados['categoria_id']  ?: null,
            ':desc'       => trim($dados['descricao']),
            ':valor'      => $dados['valor'],
            ':dia'        => (int) $dados['dia_vencimento'],
            ':dia_ultimo' => (int) $dados['dia_vencimento_ultimo_dia'],
            ':forma'      => $dados['forma_pagamento'],
            ':cartao'     => $dados['forma_pagamento'] === 'credito' ? ($dados['cartao_id'] ?: null) : null,
            ':inicio'     => $dados['data_inicio'],
            ':fim'        => $dados['data_fim']      ?: null,
            ':obs'        => $dados['observacoes']   ?: null,
            ':id'         => $id,
            ':uid'        => $usuarioId,
        ]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Encerra a vigência do template numa data específica, desativa-o e remove
     * os lançamentos já gerados fora do novo intervalo [data_inicio, data_fim].
     */
    public function finalizar(int $id, int $usuarioId, string $dataFim): bool
    {
        $stmt = $this->db->prepare(
            "SELECT id FROM despesas_fixas WHERE id = :id AND usuario_id = :uid"
        );
        $stmt->execute([':id' => $id, ':uid' => $usuarioId]);
        if (!$stmt->fetch()) return false;

        $this->db->beginTransaction();
        try {
            $this->db->prepare("
                UPDATE despesas_fixas
                SET data_fim = :fim, ativo = 0
                WHERE id = :id AND usuario_id = :uid
            ")->execute([':fim' => $dataFim, ':id' => $id, ':uid' => $usuarioId]);

            $this->db->prepare("
                DELETE d FROM despesas d
                INNER JOIN despesas_fixas df ON df.id = d.despesa_fixa_id
                WHERE df.id = :id
                  AND d.usuario_id = :uid
                  AND (d.data < df.data_inicio OR d.data > df.data_fim)
            ")->execute([':id' => $id, ':uid' => $usuarioId]);

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Exclui permanentemente o template e todos os lançamentos vinculados a ele.
     */
    public function excluirComLancamentos(int $id, int $usuarioId): bool
    {
        // Verifica se pertence ao usuário antes de excluir
        $stmt = $this->db->prepare(
            "SELECT id FROM despesas_fixas WHERE id = :id AND usuario_id = :uid"
        );
        $stmt->execute([':id' => $id, ':uid' => $usuarioId]);
        if (!$stmt->fetch()) return false;

        $this->db->beginTransaction();
        try {
            $this->db->prepare(
                "DELETE FROM despesas WHERE despesa_fixa_id = :id AND usuario_id = :uid"
            )->execute([':id' => $id, ':uid' => $usuarioId]);

            $this->db->prepare(
                "DELETE FROM despesas_fixas WHERE id = :id AND usuario_id = :uid"
            )->execute([':id' => $id, ':uid' => $usuarioId]);

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
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
                (usuario_id, categoria_id, cartao_id, despesa_fixa_id,
                 descricao, valor, data, tipo, forma_pagamento)
            VALUES
                (:uid, :cat, :cartao, :df_id, :desc, :valor, :data, 'fixa', :forma)
        ");

        $count = 0;
        foreach ($templates as $t) {
            $diasNoMes = (int) date('t', strtotime($primeiroDia));
            $dia       = $t['dia_vencimento_ultimo_dia'] ? $diasNoMes : min((int) $t['dia_vencimento'], $diasNoMes);
            $data      = sprintf('%04d-%02d-%02d', $ano, $mes, $dia);
            $forma     = $t['forma_pagamento'] ?: 'boleto';

            $stmtIns->execute([
                ':uid'    => $usuarioId,
                ':cat'    => $t['categoria_id'],
                ':cartao' => $forma === 'credito' ? $t['cartao_id'] : null,
                ':df_id'  => $t['id'],
                ':desc'   => $t['descricao'],
                ':valor'  => $t['valor'],
                ':data'   => $data,
                ':forma'  => $forma,
            ]);
            $count++;
        }

        return $count;
    }
}
