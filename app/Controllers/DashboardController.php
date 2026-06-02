<?php

/**
 * DashboardController — Painel principal com resumo financeiro.
 */

declare(strict_types=1);

class DashboardController
{
    private PDO $db;

    public function __construct()
    {
        Auth::requireLogin();
        $this->db = Database::getInstance();
    }

    /** GET /dashboard */
    public function index(): void
    {
        $uid = Auth::id();
        $ano = Request::getInt('ano',  (int) date('Y'));
        $mes = Request::getInt('mes',  (int) date('n'));

        // Garante range válido
        $mes = max(1, min(12, $mes));
        $ano = max(2000, min(2100, $ano));

        // Gera lançamentos recorrentes do mês se necessário
        $this->gerarRecorrenciasMes($uid, $ano, $mes);

        $totalReceitas  = $this->totalReceitas($uid, $ano, $mes);
        $totalDespesas  = $this->totalDespesas($uid, $ano, $mes);
        $saldo          = $totalReceitas - $totalDespesas;
        $porCategoria   = $this->despesasPorCategoria($uid, $ano, $mes);
        $porCartao      = $this->despesasPorCartao($uid, $ano, $mes);
        $ultimosLanc    = $this->ultimosLancamentos($uid, $ano, $mes);
        $parcelasFut    = $this->parcelasFuturas($uid);
        $evolucao       = $this->evolucaoMensal($uid, $ano, $mes);

        Response::view('dashboard/index', [
            'titulo'          => 'Dashboard',
            'ano'             => $ano,
            'mes'             => $mes,
            'totalReceitas'   => $totalReceitas,
            'totalDespesas'   => $totalDespesas,
            'saldo'           => $saldo,
            'porCategoria'    => $porCategoria,
            'porCartao'       => $porCartao,
            'ultimosLanc'     => $ultimosLanc,
            'parcelasFut'     => $parcelasFut,
            'evolucao'        => $evolucao,
        ]);
    }

    // ──────────────────────────────────────────────
    // Queries privadas
    // ──────────────────────────────────────────────

    private function totalReceitas(int $uid, int $ano, int $mes): float
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(valor), 0) FROM receitas
             WHERE usuario_id = :uid AND YEAR(data) = :ano AND MONTH(data) = :mes"
        );
        $stmt->execute([':uid' => $uid, ':ano' => $ano, ':mes' => $mes]);
        return (float) $stmt->fetchColumn();
    }

    private function totalDespesas(int $uid, int $ano, int $mes): float
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(d.valor), 0)
             FROM despesas d
             LEFT JOIN cartoes c ON c.id = d.cartao_id
             WHERE d.usuario_id = :uid
               AND YEAR(CASE
                    WHEN d.tipo = 'parcelada' AND d.pago = 1 AND d.data_pagamento IS NOT NULL
                        THEN d.data_pagamento
                    WHEN d.tipo != 'parcelada' AND d.cartao_id IS NOT NULL AND IFNULL(c.dia_fechamento, 32) >= IFNULL(c.dia_vencimento, 32)
                        THEN DATE_ADD(d.data, INTERVAL 1 MONTH)
                    WHEN d.tipo != 'parcelada' AND d.cartao_id IS NOT NULL AND DAY(d.data) >= IFNULL(c.dia_fechamento, 32)
                        THEN DATE_ADD(d.data, INTERVAL 1 MONTH)
                    ELSE d.data
                END) = :ano
               AND MONTH(CASE
                    WHEN d.tipo = 'parcelada' AND d.pago = 1 AND d.data_pagamento IS NOT NULL
                        THEN d.data_pagamento
                    WHEN d.tipo != 'parcelada' AND d.cartao_id IS NOT NULL AND IFNULL(c.dia_fechamento, 32) >= IFNULL(c.dia_vencimento, 32)
                        THEN DATE_ADD(d.data, INTERVAL 1 MONTH)
                    WHEN d.tipo != 'parcelada' AND d.cartao_id IS NOT NULL AND DAY(d.data) >= IFNULL(c.dia_fechamento, 32)
                        THEN DATE_ADD(d.data, INTERVAL 1 MONTH)
                    ELSE d.data
                END) = :mes"
        );
        $stmt->execute([':uid' => $uid, ':ano' => $ano, ':mes' => $mes]);
        return (float) $stmt->fetchColumn();
    }

    private function despesasPorCategoria(int $uid, int $ano, int $mes): array
    {
        $stmt = $this->db->prepare(
            "SELECT cat.nome, cat.cor, COALESCE(SUM(d.valor), 0) AS total
             FROM despesas d
             LEFT JOIN categorias cat ON cat.id = d.categoria_id
             LEFT JOIN cartoes    c   ON c.id   = d.cartao_id
             WHERE d.usuario_id = :uid
               AND YEAR(CASE
                    WHEN d.tipo = 'parcelada' AND d.pago = 1 AND d.data_pagamento IS NOT NULL
                        THEN d.data_pagamento
                    WHEN d.tipo != 'parcelada' AND d.cartao_id IS NOT NULL AND IFNULL(c.dia_fechamento, 32) >= IFNULL(c.dia_vencimento, 32)
                        THEN DATE_ADD(d.data, INTERVAL 1 MONTH)
                    WHEN d.tipo != 'parcelada' AND d.cartao_id IS NOT NULL AND DAY(d.data) >= IFNULL(c.dia_fechamento, 32)
                        THEN DATE_ADD(d.data, INTERVAL 1 MONTH)
                    ELSE d.data
                END) = :ano
               AND MONTH(CASE
                    WHEN d.tipo = 'parcelada' AND d.pago = 1 AND d.data_pagamento IS NOT NULL
                        THEN d.data_pagamento
                    WHEN d.tipo != 'parcelada' AND d.cartao_id IS NOT NULL AND IFNULL(c.dia_fechamento, 32) >= IFNULL(c.dia_vencimento, 32)
                        THEN DATE_ADD(d.data, INTERVAL 1 MONTH)
                    WHEN d.tipo != 'parcelada' AND d.cartao_id IS NOT NULL AND DAY(d.data) >= IFNULL(c.dia_fechamento, 32)
                        THEN DATE_ADD(d.data, INTERVAL 1 MONTH)
                    ELSE d.data
                END) = :mes
             GROUP BY d.categoria_id, cat.nome, cat.cor
             ORDER BY total DESC
             LIMIT 10"
        );
        $stmt->execute([':uid' => $uid, ':ano' => $ano, ':mes' => $mes]);
        return $stmt->fetchAll();
    }

    private function despesasPorCartao(int $uid, int $ano, int $mes): array
    {
        $stmt = $this->db->prepare(
            "SELECT c.nome, c.cor, c.limite,
                    COALESCE(SUM(d.valor), 0) AS total_gasto,
                    c.limite - COALESCE(SUM(d.valor), 0) AS limite_disponivel
             FROM cartoes c
             LEFT JOIN despesas d
                ON d.cartao_id = c.id
                AND YEAR(CASE
                         WHEN d.tipo = 'parcelada' AND d.pago = 1 AND d.data_pagamento IS NOT NULL
                             THEN d.data_pagamento
                         WHEN d.tipo != 'parcelada' AND DAY(d.data) >= c.dia_fechamento
                             THEN DATE_ADD(d.data, INTERVAL 1 MONTH)
                         ELSE d.data
                    END) = :ano
                AND MONTH(CASE
                          WHEN d.tipo = 'parcelada' AND d.pago = 1 AND d.data_pagamento IS NOT NULL
                              THEN d.data_pagamento
                          WHEN d.tipo != 'parcelada' AND DAY(d.data) >= c.dia_fechamento
                              THEN DATE_ADD(d.data, INTERVAL 1 MONTH)
                          ELSE d.data
                    END) = :mes
             WHERE c.usuario_id = :uid AND c.ativo = 1
             GROUP BY c.id, c.nome, c.cor, c.limite
             ORDER BY total_gasto DESC"
        );
        $stmt->execute([':uid' => $uid, ':ano' => $ano, ':mes' => $mes]);
        return $stmt->fetchAll();
    }

    private function ultimosLancamentos(int $uid, int $ano, int $mes): array
    {
        $stmt = $this->db->prepare(
            "SELECT 'receita' AS tipo_lancamento, r.descricao, r.valor, r.data,
                    cat.nome AS categoria, cat.cor AS categoria_cor
             FROM receitas r
             LEFT JOIN categorias cat ON cat.id = r.categoria_id
             WHERE r.usuario_id = :uid AND YEAR(r.data) = :ano AND MONTH(r.data) = :mes

             UNION ALL

             SELECT 'despesa' AS tipo_lancamento, d.descricao, d.valor, d.data,
                    cat2.nome AS categoria, cat2.cor AS categoria_cor
             FROM despesas d
             LEFT JOIN categorias cat2 ON cat2.id = d.categoria_id
             LEFT JOIN cartoes    c    ON c.id    = d.cartao_id
             WHERE d.usuario_id = :uid2
               AND YEAR(CASE
                    WHEN d.tipo = 'parcelada' AND d.pago = 1 AND d.data_pagamento IS NOT NULL
                        THEN d.data_pagamento
                    WHEN d.tipo != 'parcelada' AND d.cartao_id IS NOT NULL AND IFNULL(c.dia_fechamento, 32) >= IFNULL(c.dia_vencimento, 32)
                        THEN DATE_ADD(d.data, INTERVAL 1 MONTH)
                    WHEN d.tipo != 'parcelada' AND d.cartao_id IS NOT NULL AND DAY(d.data) >= IFNULL(c.dia_fechamento, 32)
                        THEN DATE_ADD(d.data, INTERVAL 1 MONTH)
                    ELSE d.data
                END) = :ano2
               AND MONTH(CASE
                    WHEN d.tipo = 'parcelada' AND d.pago = 1 AND d.data_pagamento IS NOT NULL
                        THEN d.data_pagamento
                    WHEN d.tipo != 'parcelada' AND d.cartao_id IS NOT NULL AND IFNULL(c.dia_fechamento, 32) >= IFNULL(c.dia_vencimento, 32)
                        THEN DATE_ADD(d.data, INTERVAL 1 MONTH)
                    WHEN d.tipo != 'parcelada' AND d.cartao_id IS NOT NULL AND DAY(d.data) >= IFNULL(c.dia_fechamento, 32)
                        THEN DATE_ADD(d.data, INTERVAL 1 MONTH)
                    ELSE d.data
                END) = :mes2

             ORDER BY data DESC
             LIMIT 10"
        );
        $stmt->execute([
            ':uid'  => $uid,
            ':ano'  => $ano,
            ':mes'  => $mes,
            ':uid2' => $uid,
            ':ano2' => $ano,
            ':mes2' => $mes,
        ]);
        return $stmt->fetchAll();
    }

    private function parcelasFuturas(int $uid): array
    {
        $stmt = $this->db->prepare(
            "SELECT par.descricao, p.numero_parcela, par.numero_parcelas,
                    p.valor, p.data_vencimento, c.nome AS cartao, c.cor AS cartao_cor
             FROM parcelas p
             JOIN parcelamentos par ON par.id = p.parcelamento_id
             LEFT JOIN cartoes c ON c.id = par.cartao_id
             WHERE par.usuario_id = :uid AND p.pago = 0
               AND p.data_vencimento >= CURDATE()
             ORDER BY p.data_vencimento ASC
             LIMIT 8"
        );
        $stmt->execute([':uid' => $uid]);
        return $stmt->fetchAll();
    }

    private function evolucaoMensal(int $uid, int $ano, int $mes): array
    {
        // Últimos 6 meses incluindo o atual
        $resultado = [];
        $data = new DateTimeImmutable("{$ano}-{$mes}-01");

        for ($i = 5; $i >= 0; $i--) {
            $ref  = $data->modify("-{$i} months");
            $a    = (int) $ref->format('Y');
            $m    = (int) $ref->format('n');

            $stmtR = $this->db->prepare(
                "SELECT COALESCE(SUM(valor),0) FROM receitas
                 WHERE usuario_id=:uid AND YEAR(data)=:a AND MONTH(data)=:m"
            );
            $stmtR->execute([':uid' => $uid, ':a' => $a, ':m' => $m]);
            $receitas = (float)$stmtR->fetchColumn();

            $stmtD = $this->db->prepare(
                "SELECT COALESCE(SUM(d.valor),0)
                 FROM despesas d
                 LEFT JOIN cartoes c ON c.id = d.cartao_id
                 WHERE d.usuario_id = :uid
                   AND YEAR(CASE
                        WHEN d.tipo = 'parcelada' AND d.pago = 1 AND d.data_pagamento IS NOT NULL
                            THEN d.data_pagamento
                        WHEN d.tipo != 'parcelada' AND d.cartao_id IS NOT NULL AND IFNULL(c.dia_fechamento, 32) >= IFNULL(c.dia_vencimento, 32)
                            THEN DATE_ADD(d.data, INTERVAL 1 MONTH)
                        WHEN d.tipo != 'parcelada' AND d.cartao_id IS NOT NULL AND DAY(d.data) >= IFNULL(c.dia_fechamento, 32)
                            THEN DATE_ADD(d.data, INTERVAL 1 MONTH)
                        ELSE d.data
                    END) = :ano
                   AND MONTH(CASE
                        WHEN d.tipo = 'parcelada' AND d.pago = 1 AND d.data_pagamento IS NOT NULL
                            THEN d.data_pagamento
                        WHEN d.tipo != 'parcelada' AND d.cartao_id IS NOT NULL AND IFNULL(c.dia_fechamento, 32) >= IFNULL(c.dia_vencimento, 32)
                            THEN DATE_ADD(d.data, INTERVAL 1 MONTH)
                        WHEN d.tipo != 'parcelada' AND d.cartao_id IS NOT NULL AND DAY(d.data) >= IFNULL(c.dia_fechamento, 32)
                            THEN DATE_ADD(d.data, INTERVAL 1 MONTH)
                        ELSE d.data
                    END) = :mes"
            );
            $stmtD->execute([':uid' => $uid, ':ano' => $a, ':mes' => $m]);
            $despesas = (float)$stmtD->fetchColumn();

            $resultado[] = [
                'mes'      => nomeMes($m) . '/' . substr((string)$a, 2),
                'receitas' => $receitas,
                'despesas' => $despesas,
            ];
        }

        return $resultado;
    }

    private function gerarRecorrenciasMes(int $uid, int $ano, int $mes): void
    {
        $primeiroDia = sprintf('%04d-%02d-01', $ano, $mes);
        $ultimoDia   = date('Y-m-t', strtotime($primeiroDia));

        // Busca apenas templates que ainda não foram gerados neste mês
        $stmt = $this->db->prepare(
            "SELECT * FROM despesas_fixas
             WHERE usuario_id = :uid AND ativo = 1
               AND data_inicio <= :ultimo
               AND (data_fim IS NULL OR data_fim >= :primeiro)
               AND NOT EXISTS (
                   SELECT 1 FROM despesas d2
                   WHERE d2.usuario_id = :uid2
                     AND d2.despesa_fixa_id = despesas_fixas.id
                     AND YEAR(d2.data) = :ano AND MONTH(d2.data) = :mes
               )"
        );
        $stmt->execute([
            ':uid'      => $uid,
            ':uid2'     => $uid,
            ':primeiro' => $primeiroDia,
            ':ultimo'   => $ultimoDia,
            ':ano'      => $ano,
            ':mes'      => $mes,
        ]);
        $templates = $stmt->fetchAll();

        if (empty($templates)) return;

        $ins = $this->db->prepare(
            "INSERT INTO despesas
                (usuario_id, categoria_id, despesa_fixa_id, descricao,
                 valor, data, tipo, forma_pagamento)
             VALUES (:uid, :cat, :df_id, :desc, :valor, :data, 'fixa', 'boleto')"
        );

        foreach ($templates as $t) {
            $diasNoMes = (int) date('t', strtotime($primeiroDia));
            $dia  = min($t['dia_vencimento'], $diasNoMes);
            $data = sprintf('%04d-%02d-%02d', $ano, $mes, $dia);

            $ins->execute([
                ':uid'   => $uid,
                ':cat'   => $t['categoria_id'],
                ':df_id' => $t['id'],
                ':desc'  => $t['descricao'],
                ':valor' => $t['valor'],
                ':data'  => $data,
            ]);
        }
    }
}
