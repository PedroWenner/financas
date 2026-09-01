<?php
declare(strict_types=1);

class RelatorioModel extends BaseModel
{
    protected string $table = 'despesas';

    // Effective date expression (credit card billing cycle adjustment)
    private const DATA_EF = "CASE
        WHEN d.tipo = 'parcelada' AND d.pago = 1 AND d.data_pagamento IS NOT NULL
            THEN d.data_pagamento
        WHEN d.tipo != 'parcelada' AND d.cartao_id IS NOT NULL
            THEN DATE_ADD(d.data, INTERVAL (
                CASE WHEN DAY(d.data) >= IFNULL(c.dia_fechamento, 32) THEN 1 ELSE 0 END +
                CASE WHEN IFNULL(c.dia_fechamento, 32) >= IFNULL(c.dia_vencimento, 32) THEN 1 ELSE 0 END
            ) MONTH)
        ELSE d.data
    END";

    /**
     * Builds WHERE clause and params for expense queries.
     * $tipos: subset of ['fixa','variavel','parcelada'] — empty means all
     * $modoCartao: 'todos' | 'apenas_cartao' | 'sem_cartao'
     */
    private function buildWhere(int $uid, array $tipos, string $modoCartao): array
    {
        $where  = "d.usuario_id = :uid";
        $params = [':uid' => $uid];

        $validos = array_intersect($tipos, ['fixa', 'variavel', 'parcelada']);
        if (!empty($validos) && count($validos) < 3) {
            // Values are whitelisted above — safe to inline
            $in     = implode(',', array_map(fn($t) => "'$t'", $validos));
            $where .= " AND d.tipo IN ($in)";
        }

        if ($modoCartao === 'sem_cartao') {
            $where .= " AND d.cartao_id IS NULL";
        } elseif ($modoCartao === 'apenas_cartao') {
            $where .= " AND d.cartao_id IS NOT NULL";
        }

        return [$where, $params];
    }

    /** Monthly expense totals for a period. */
    public function despesasPorMes(
        int $uid,
        int $anoIni, int $mesIni,
        int $anoFim, int $mesFim,
        array $tipos = [],
        string $modoCartao = 'todos'
    ): array {
        [$where, $params] = $this->buildWhere($uid, $tipos, $modoCartao);
        $params[':ini'] = $anoIni * 100 + $mesIni;
        $params[':fim'] = $anoFim * 100 + $mesFim;

        $stmt = $this->db->prepare("
            SELECT YEAR(sub.data_ef) AS ano, MONTH(sub.data_ef) AS mes, SUM(sub.valor) AS total
            FROM (
                SELECT d.valor, " . self::DATA_EF . " AS data_ef
                FROM despesas d
                LEFT JOIN cartoes c ON c.id = d.cartao_id
                WHERE {$where}
            ) sub
            WHERE (YEAR(sub.data_ef) * 100 + MONTH(sub.data_ef)) BETWEEN :ini AND :fim
            GROUP BY YEAR(sub.data_ef), MONTH(sub.data_ef)
            ORDER BY ano, mes
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Monthly income totals for a period. */
    public function receitasPorMes(
        int $uid,
        int $anoIni, int $mesIni,
        int $anoFim, int $mesFim
    ): array {
        $stmt = $this->db->prepare("
            SELECT YEAR(r.data) AS ano, MONTH(r.data) AS mes, SUM(r.valor) AS total
            FROM receitas r
            WHERE r.usuario_id = :uid
              AND (YEAR(r.data) * 100 + MONTH(r.data)) BETWEEN :ini AND :fim
            GROUP BY YEAR(r.data), MONTH(r.data)
            ORDER BY ano, mes
        ");
        $stmt->execute([
            ':uid' => $uid,
            ':ini' => $anoIni * 100 + $mesIni,
            ':fim' => $anoFim * 100 + $mesFim,
        ]);
        return $stmt->fetchAll();
    }

    /** Expense totals grouped by category for a period. */
    public function despesasPorCategoria(
        int $uid,
        int $anoIni, int $mesIni,
        int $anoFim, int $mesFim,
        array $tipos = [],
        string $modoCartao = 'todos'
    ): array {
        [$where, $params] = $this->buildWhere($uid, $tipos, $modoCartao);
        $params[':ini'] = $anoIni * 100 + $mesIni;
        $params[':fim'] = $anoFim * 100 + $mesFim;

        $stmt = $this->db->prepare("
            SELECT
                COALESCE(cat.nome, 'Sem categoria') AS nome,
                COALESCE(cat.cor,  '#6c757d')       AS cor,
                SUM(sub.valor) AS total
            FROM (
                SELECT d.valor, d.categoria_id, " . self::DATA_EF . " AS data_ef
                FROM despesas d
                LEFT JOIN cartoes c ON c.id = d.cartao_id
                WHERE {$where}
            ) sub
            LEFT JOIN categorias cat ON cat.id = sub.categoria_id
            WHERE (YEAR(sub.data_ef) * 100 + MONTH(sub.data_ef)) BETWEEN :ini AND :fim
            GROUP BY sub.categoria_id, cat.nome, cat.cor
            ORDER BY total DESC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Expense totals grouped by credit card for a period. */
    public function despesasPorCartao(
        int $uid,
        int $anoIni, int $mesIni,
        int $anoFim, int $mesFim,
        array $tipos = [],
        string $modoCartao = 'todos'
    ): array {
        [$where, $params] = $this->buildWhere($uid, $tipos, $modoCartao);
        $params[':ini'] = $anoIni * 100 + $mesIni;
        $params[':fim'] = $anoFim * 100 + $mesFim;

        $stmt = $this->db->prepare("
            SELECT
                COALESCE(cart.nome, 'Sem cartão') AS nome,
                COALESCE(cart.cor,  '#6c757d')    AS cor,
                SUM(sub.valor) AS total
            FROM (
                SELECT d.valor, d.cartao_id, " . self::DATA_EF . " AS data_ef
                FROM despesas d
                LEFT JOIN cartoes c ON c.id = d.cartao_id
                WHERE {$where}
            ) sub
            LEFT JOIN cartoes cart ON cart.id = sub.cartao_id
            WHERE (YEAR(sub.data_ef) * 100 + MONTH(sub.data_ef)) BETWEEN :ini AND :fim
            GROUP BY sub.cartao_id, cart.nome, cart.cor
            ORDER BY total DESC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Individual income entries for a period. */
    public function listarReceitas(
        int $uid,
        int $anoIni, int $mesIni,
        int $anoFim, int $mesFim
    ): array {
        $stmt = $this->db->prepare("
            SELECT
                r.data,
                r.descricao,
                r.valor,
                COALESCE(cat.nome, 'Sem categoria') AS categoria,
                COALESCE(cat.cor, '#6c757d')        AS cor_categoria
            FROM receitas r
            LEFT JOIN categorias cat ON cat.id = r.categoria_id
            WHERE r.usuario_id = :uid
              AND (YEAR(r.data) * 100 + MONTH(r.data)) BETWEEN :ini AND :fim
            ORDER BY r.data DESC, r.valor DESC
        ");
        $stmt->execute([
            ':uid' => $uid,
            ':ini' => $anoIni * 100 + $mesIni,
            ':fim' => $anoFim * 100 + $mesFim,
        ]);
        return $stmt->fetchAll();
    }

    /** Individual expense entries for a period (all tipos; apply cartao filter only). */
    public function listarDespesas(
        int $uid,
        int $anoIni, int $mesIni,
        int $anoFim, int $mesFim,
        string $modoCartao = 'todos'
    ): array {
        [$where, $params] = $this->buildWhere($uid, [], $modoCartao);
        $params[':ini'] = $anoIni * 100 + $mesIni;
        $params[':fim'] = $anoFim * 100 + $mesFim;

        $stmt = $this->db->prepare("
            SELECT
                sub.data_ef  AS data,
                sub.descricao,
                sub.valor,
                sub.tipo,
                COALESCE(cat.nome, 'Sem categoria') AS categoria,
                COALESCE(cat.cor, '#6c757d')        AS cor_categoria
            FROM (
                SELECT d.valor, d.tipo, d.descricao, d.categoria_id,
                       " . self::DATA_EF . " AS data_ef
                FROM despesas d
                LEFT JOIN cartoes c ON c.id = d.cartao_id
                WHERE {$where}
            ) sub
            LEFT JOIN categorias cat ON cat.id = sub.categoria_id
            WHERE (YEAR(sub.data_ef) * 100 + MONTH(sub.data_ef)) BETWEEN :ini AND :fim
            ORDER BY sub.data_ef DESC, sub.valor DESC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Expense totals broken down by type (fixa/variavel/parcelada) for a period. */
    public function despesasPorTipo(
        int $uid,
        int $anoIni, int $mesIni,
        int $anoFim, int $mesFim,
        string $modoCartao = 'todos'
    ): array {
        // Always show all types for the breakdown chart
        [$where, $params] = $this->buildWhere($uid, ['fixa', 'variavel', 'parcelada'], $modoCartao);
        $params[':ini'] = $anoIni * 100 + $mesIni;
        $params[':fim'] = $anoFim * 100 + $mesFim;

        $stmt = $this->db->prepare("
            SELECT sub.tipo, SUM(sub.valor) AS total
            FROM (
                SELECT d.valor, d.tipo, " . self::DATA_EF . " AS data_ef
                FROM despesas d
                LEFT JOIN cartoes c ON c.id = d.cartao_id
                WHERE {$where}
            ) sub
            WHERE (YEAR(sub.data_ef) * 100 + MONTH(sub.data_ef)) BETWEEN :ini AND :fim
            GROUP BY sub.tipo
        ");
        $stmt->execute($params);
        $rows   = $stmt->fetchAll();
        $result = ['fixa' => 0.0, 'variavel' => 0.0, 'parcelada' => 0.0];
        foreach ($rows as $r) {
            $result[$r['tipo']] = (float) $r['total'];
        }
        return $result;
    }
}
