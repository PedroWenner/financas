<?php
/**
 * DespesaModel — Gerenciamento de despesas variáveis e parceladas.
 */

declare(strict_types=1);

class DespesaModel extends BaseModel
{
    protected string $table = 'despesas';

    /**
     * Lista despesas do usuário com filtros.
     */
    public function listar(
        int $usuarioId,
        int $ano,
        int $mes,
        ?int $categoriaId   = null,
        ?string $tipo       = null,
        ?int $cartaoId      = null,
        ?string $pagamento  = null,
        int $pagina         = 1,
        int $porPagina      = 25
    ): array {
        $anoMes = sprintf('%04d-%02d', $ano, $mes);
        $params = [':uid' => $usuarioId, ':ano_mes' => $anoMes];
        $where  = "d.usuario_id = :uid AND DATE_FORMAT(
            CASE WHEN d.cartao_id IS NOT NULL AND DAY(d.data) >= IFNULL(c.dia_fechamento, 32)
                 THEN DATE_ADD(d.data, INTERVAL 1 MONTH)
                 ELSE d.data
            END, '%Y-%m') = :ano_mes";

        if ($categoriaId) {
            $where .= " AND d.categoria_id = :cat";
            $params[':cat'] = $categoriaId;
        }
        if ($tipo) {
            $where .= " AND d.tipo = :tipo";
            $params[':tipo'] = $tipo;
        }
        if ($cartaoId) {
            $where .= " AND d.cartao_id = :cartao";
            $params[':cartao'] = $cartaoId;
        }
        if ($pagamento) {
            $where .= " AND d.forma_pagamento = :pag";
            $params[':pag'] = $pagamento;
        }

        $offset = ($pagina - 1) * $porPagina;

        $stmt = $this->db->prepare("
            SELECT
                d.*,
                cat.nome AS categoria_nome,
                cat.cor  AS categoria_cor,
                c.nome   AS cartao_nome,
                c.cor    AS cartao_cor
            FROM despesas d
            LEFT JOIN categorias cat ON cat.id = d.categoria_id
            LEFT JOIN cartoes    c   ON c.id   = d.cartao_id
            WHERE {$where}
            ORDER BY d.data DESC, d.id DESC
            LIMIT {$porPagina} OFFSET {$offset}
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Conta total de despesas para paginação.
     */
    public function contar(int $usuarioId, int $ano, int $mes, ?string $tipo = null): int
    {
        $anoMes = sprintf('%04d-%02d', $ano, $mes);
        $where  = "d.usuario_id = :uid AND DATE_FORMAT(
            CASE WHEN d.cartao_id IS NOT NULL AND DAY(d.data) >= IFNULL(c.dia_fechamento, 32)
                 THEN DATE_ADD(d.data, INTERVAL 1 MONTH)
                 ELSE d.data
            END, '%Y-%m') = :ano_mes";
        $params = [':uid' => $usuarioId, ':ano_mes' => $anoMes];

        if ($tipo) {
            $where .= " AND d.tipo = :tipo";
            $params[':tipo'] = $tipo;
        }

        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM despesas d
            LEFT JOIN cartoes c ON c.id = d.cartao_id
            WHERE {$where}
        ");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Total de despesas do mês.
     */
    public function totalMes(int $usuarioId, int $ano, int $mes): float
    {
        $anoMes = sprintf('%04d-%02d', $ano, $mes);
        $stmt   = $this->db->prepare("
            SELECT COALESCE(SUM(d.valor), 0)
            FROM despesas d
            LEFT JOIN cartoes c ON c.id = d.cartao_id
            WHERE d.usuario_id = :uid
              AND DATE_FORMAT(
                  CASE WHEN d.cartao_id IS NOT NULL AND DAY(d.data) >= IFNULL(c.dia_fechamento, 32)
                       THEN DATE_ADD(d.data, INTERVAL 1 MONTH)
                       ELSE d.data
                  END, '%Y-%m') = :ano_mes
        ");
        $stmt->execute([':uid' => $usuarioId, ':ano_mes' => $anoMes]);
        return (float) $stmt->fetchColumn();
    }

    /**
     * Total de despesas do mês por tipo.
     */
    public function totalPorTipo(int $usuarioId, int $ano, int $mes): array
    {
        $anoMes = sprintf('%04d-%02d', $ano, $mes);
        $stmt   = $this->db->prepare("
            SELECT d.tipo, COALESCE(SUM(d.valor), 0) AS total
            FROM despesas d
            LEFT JOIN cartoes c ON c.id = d.cartao_id
            WHERE d.usuario_id = :uid
              AND DATE_FORMAT(
                  CASE WHEN d.cartao_id IS NOT NULL AND DAY(d.data) >= IFNULL(c.dia_fechamento, 32)
                       THEN DATE_ADD(d.data, INTERVAL 1 MONTH)
                       ELSE d.data
                  END, '%Y-%m') = :ano_mes
            GROUP BY d.tipo
        ");
        $stmt->execute([':uid' => $usuarioId, ':ano_mes' => $anoMes]);
        $rows = $stmt->fetchAll();

        $result = ['fixa' => 0.0, 'variavel' => 0.0, 'parcelada' => 0.0];
        foreach ($rows as $r) {
            $result[$r['tipo']] = (float) $r['total'];
        }
        return $result;
    }

    /**
     * Cria nova despesa variável.
     */
    public function criar(int $usuarioId, array $dados): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO despesas
                (usuario_id, categoria_id, cartao_id, descricao, valor,
                 data, tipo, forma_pagamento, pago, data_pagamento, observacoes)
            VALUES
                (:uid, :cat, :cartao, :desc, :valor,
                 :data, :tipo, :pagamento, :pago, :data_pag, :obs)
        ");
        $stmt->execute([
            ':uid'      => $usuarioId,
            ':cat'      => $dados['categoria_id']    ?: null,
            ':cartao'   => $dados['cartao_id']        ?: null,
            ':desc'     => trim($dados['descricao']),
            ':valor'    => $dados['valor'],
            ':data'     => $dados['data'],
            ':tipo'     => $dados['tipo']             ?? 'variavel',
            ':pagamento'=> $dados['forma_pagamento']  ?? 'outro',
            ':pago'     => (int) ($dados['pago']      ?? 0),
            ':data_pag' => $dados['data_pagamento']   ?: null,
            ':obs'      => $dados['observacoes']      ?: null,
        ]);
        return $this->lastId();
    }

    /**
     * Atualiza despesa (valida dono).
     */
    public function atualizar(int $id, int $usuarioId, array $dados): bool
    {
        $stmt = $this->db->prepare("
            UPDATE despesas
            SET categoria_id    = :cat,
                cartao_id       = :cartao,
                descricao       = :desc,
                valor           = :valor,
                data            = :data,
                tipo            = :tipo,
                forma_pagamento = :pagamento,
                pago            = :pago,
                data_pagamento  = :data_pag,
                observacoes     = :obs
            WHERE id = :id AND usuario_id = :uid
        ");
        $stmt->execute([
            ':cat'      => $dados['categoria_id']    ?: null,
            ':cartao'   => $dados['cartao_id']        ?: null,
            ':desc'     => trim($dados['descricao']),
            ':valor'    => $dados['valor'],
            ':data'     => $dados['data'],
            ':tipo'     => $dados['tipo']             ?? 'variavel',
            ':pagamento'=> $dados['forma_pagamento']  ?? 'outro',
            ':pago'     => (int) ($dados['pago']      ?? 0),
            ':data_pag' => $dados['data_pagamento']   ?: null,
            ':obs'      => $dados['observacoes']      ?: null,
            ':id'       => $id,
            ':uid'      => $usuarioId,
        ]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Marca despesa como paga/não paga.
     */
    public function marcarPago(int $id, int $usuarioId, bool $pago): bool
    {
        $stmt = $this->db->prepare("
            UPDATE despesas
            SET pago = :pago, data_pagamento = :data_pag
            WHERE id = :id AND usuario_id = :uid
        ");
        $stmt->execute([
            ':pago'     => (int) $pago,
            ':data_pag' => $pago ? date('Y-m-d') : null,
            ':id'       => $id,
            ':uid'      => $usuarioId,
        ]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Exclui despesa (valida dono).
     */
    public function excluir(int $id, int $usuarioId): bool
    {
        return $this->deleteByIdAndUser($id, $usuarioId);
    }

    /**
     * Busca despesa por ID com joins.
     */
    public function buscarComJoins(int $id, int $usuarioId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT d.*,
                   cat.nome AS categoria_nome,
                   c.nome   AS cartao_nome
            FROM despesas d
            LEFT JOIN categorias cat ON cat.id = d.categoria_id
            LEFT JOIN cartoes    c   ON c.id   = d.cartao_id
            WHERE d.id = :id AND d.usuario_id = :uid
        ");
        $stmt->execute([':id' => $id, ':uid' => $usuarioId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Despesas por categoria no mês (para gráfico).
     */
    public function porCategoria(int $usuarioId, int $ano, int $mes): array
    {
        $anoMes = sprintf('%04d-%02d', $ano, $mes);
        $stmt   = $this->db->prepare("
            SELECT
                COALESCE(cat.nome, 'Sem categoria') AS nome,
                COALESCE(cat.cor,  '#6c757d')       AS cor,
                SUM(d.valor) AS total
            FROM despesas d
            LEFT JOIN categorias cat ON cat.id = d.categoria_id
            LEFT JOIN cartoes    c   ON c.id   = d.cartao_id
            WHERE d.usuario_id = :uid
              AND DATE_FORMAT(
                  CASE WHEN d.cartao_id IS NOT NULL AND DAY(d.data) >= IFNULL(c.dia_fechamento, 32)
                       THEN DATE_ADD(d.data, INTERVAL 1 MONTH)
                       ELSE d.data
                  END, '%Y-%m') = :ano_mes
            GROUP BY d.categoria_id, cat.nome, cat.cor
            ORDER BY total DESC
        ");
        $stmt->execute([':uid' => $usuarioId, ':ano_mes' => $anoMes]);
        return $stmt->fetchAll();
    }
}
