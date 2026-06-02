<?php
/**
 * ParcelamentoModel — Gerenciamento de compras parceladas.
 *
 * Integração com a tabela `despesas`:
 * - Ao criar um parcelamento, cada parcela gera um registro em `despesas`
 *   (tipo='parcelada', parcela_id=<id>) para aparecer em faturas e listagens
 * - Ao marcar parcela paga/pendente, sincroniza o status na tabela `despesas`
 * - Ao excluir o parcelamento, remove as despesas vinculadas primeiro
 */

declare(strict_types=1);

class ParcelamentoModel extends BaseModel
{
    protected string $table = 'parcelamentos';

    // ──────────────────────────────────────────────
    // Leitura
    // ──────────────────────────────────────────────

    /**
     * Lista todos os parcelamentos do usuário com resumo de progresso.
     */
    public function listar(int $usuarioId, string $filtro = 'ativos'): array
    {
        $whereExtra   = $filtro === 'bloqueados' ? 'AND par.bloqueado_a_partir IS NOT NULL' : '';
        $havingClause = match ($filtro) {
            'concluidos' => 'HAVING parcelas_pendentes = 0',
            'ativos'     => 'HAVING parcelas_pendentes > 0',
            default      => '',
        };

        $stmt = $this->db->prepare("
            SELECT
                par.*,
                c.nome    AS cartao_nome,
                c.cor     AS cartao_cor,
                c.bandeira,
                cat.nome  AS categoria_nome,
                cat.cor   AS categoria_cor,
                COUNT(p.id)                                        AS total_parcelas_count,
                SUM(CASE WHEN p.pago = 1 THEN 1 ELSE 0 END)       AS parcelas_pagas,
                SUM(CASE WHEN p.pago = 0 THEN 1 ELSE 0 END)       AS parcelas_pendentes,
                SUM(CASE WHEN p.pago = 1 THEN p.valor ELSE 0 END) AS valor_pago,
                SUM(CASE WHEN p.pago = 0 THEN p.valor ELSE 0 END) AS valor_pendente,
                MIN(CASE WHEN p.pago = 0 THEN p.data_vencimento END) AS proxima_parcela
            FROM parcelamentos par
            LEFT JOIN parcelas   p   ON p.parcelamento_id = par.id
            LEFT JOIN cartoes    c   ON c.id  = par.cartao_id
            LEFT JOIN categorias cat ON cat.id = par.categoria_id
            WHERE par.usuario_id = :uid {$whereExtra}
            GROUP BY par.id
            {$havingClause}
            ORDER BY proxima_parcela ASC, par.created_at DESC
        ");
        $stmt->execute([':uid' => $usuarioId]);
        return $stmt->fetchAll();
    }

    /**
     * Busca parcelamento por ID com progresso completo.
     */
    public function buscarComProgresso(int $id, int $usuarioId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                par.*,
                c.nome    AS cartao_nome,
                c.cor     AS cartao_cor,
                c.bandeira,
                c.dia_fechamento,
                c.dia_vencimento  AS cartao_dia_venc,
                cat.nome  AS categoria_nome,
                cat.cor   AS categoria_cor,
                COUNT(p.id)                                        AS total_parcelas_count,
                SUM(CASE WHEN p.pago = 1 THEN 1 ELSE 0 END)       AS parcelas_pagas,
                SUM(CASE WHEN p.pago = 0 THEN 1 ELSE 0 END)       AS parcelas_pendentes,
                SUM(CASE WHEN p.pago = 1 THEN p.valor ELSE 0 END) AS valor_pago,
                SUM(CASE WHEN p.pago = 0 THEN p.valor ELSE 0 END) AS valor_pendente
            FROM parcelamentos par
            LEFT JOIN parcelas   p   ON p.parcelamento_id = par.id
            LEFT JOIN cartoes    c   ON c.id  = par.cartao_id
            LEFT JOIN categorias cat ON cat.id = par.categoria_id
            WHERE par.id = :id AND par.usuario_id = :uid
            GROUP BY par.id
        ");
        $stmt->execute([':id' => $id, ':uid' => $usuarioId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Lista as parcelas individuais de um parcelamento.
     */
    public function listarParcelas(int $parcelamentoId): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM parcelas
            WHERE parcelamento_id = :pid
            ORDER BY numero_parcela ASC
        ");
        $stmt->execute([':pid' => $parcelamentoId]);
        return $stmt->fetchAll();
    }

    /**
     * Total pendente em todas as parcelas ativas.
     */
    public function totalPendente(int $usuarioId): float
    {
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(p.valor), 0)
            FROM parcelas p
            JOIN parcelamentos par ON par.id = p.parcelamento_id
            WHERE par.usuario_id = :uid AND p.pago = 0
        ");
        $stmt->execute([':uid' => $usuarioId]);
        return (float) $stmt->fetchColumn();
    }

    /**
     * Parcelas que vencem no mês especificado.
     */
    public function parcelasDoMes(int $usuarioId, int $ano, int $mes): array
    {
        $stmt = $this->db->prepare("
            SELECT
                p.*,
                par.descricao,
                par.numero_parcelas,
                c.nome AS cartao_nome,
                c.cor  AS cartao_cor
            FROM parcelas p
            JOIN parcelamentos par ON par.id = p.parcelamento_id
            LEFT JOIN cartoes   c  ON c.id   = par.cartao_id
            WHERE par.usuario_id = :uid
              AND YEAR(p.data_vencimento)  = :ano
              AND MONTH(p.data_vencimento) = :mes
            ORDER BY p.data_vencimento ASC
        ");
        $stmt->execute([':uid' => $usuarioId, ':ano' => $ano, ':mes' => $mes]);
        return $stmt->fetchAll();
    }

    // ──────────────────────────────────────────────
    // Escrita
    // ──────────────────────────────────────────────

    /**
     * Cria o parcelamento, todas as parcelas e os registros em `despesas`,
     * tudo em uma única transação atômica.
     *
     * Lógica de valores:
     * - Trunca a 2 casas decimais por divisão (evita acúmulo de centavos)
     * - A diferença vai para a última parcela
     *
     * @return int ID do parcelamento criado
     * @throws \Exception em caso de erro de banco (faz rollback automático)
     */
    public function criarComParcelas(int $usuarioId, array $dados): int
    {
        $valorTotal   = round((float) $dados['valor_total'], 2);
        $nParcelas    = max(2, (int) $dados['numero_parcelas']);
        $valorParcela = floor(($valorTotal / $nParcelas) * 100) / 100;
        $valorUltima  = round($valorTotal - ($valorParcela * ($nParcelas - 1)), 2);

        $this->beginTransaction();

        try {
            // ── 1. Cabeçalho do parcelamento ──────────────
            $stmtP = $this->db->prepare("
                INSERT INTO parcelamentos
                    (usuario_id, cartao_id, categoria_id, descricao,
                     valor_total, numero_parcelas, valor_parcela,
                     data_compra, data_primeira_parcela, observacoes)
                VALUES
                    (:uid, :cartao, :cat, :desc,
                     :total, :n_parc, :v_parc,
                     :compra, :primeira, :obs)
            ");
            $stmtP->execute([
                ':uid'      => $usuarioId,
                ':cartao'   => $dados['cartao_id']            ?: null,
                ':cat'      => $dados['categoria_id']         ?: null,
                ':desc'     => trim($dados['descricao']),
                ':total'    => $valorTotal,
                ':n_parc'   => $nParcelas,
                ':v_parc'   => $valorParcela,
                ':compra'   => $dados['data_compra'],
                ':primeira' => $dados['data_primeira_parcela'],
                ':obs'      => $dados['observacoes']          ?: null,
            ]);
            $parcelamentoId = $this->lastId();

            // ── 2. Prepared statements reutilizáveis ──────
            $stmtParcela = $this->db->prepare("
                INSERT INTO parcelas
                    (parcelamento_id, numero_parcela, valor, data_vencimento)
                VALUES
                    (:pid, :num, :valor, :data_venc)
            ");

            $stmtDespesa = $this->db->prepare("
                INSERT INTO despesas
                    (usuario_id, categoria_id, cartao_id, parcela_id,
                     descricao, valor, data, tipo, forma_pagamento, pago)
                VALUES
                    (:uid, :cat, :cartao, :parcela_id,
                     :desc, :valor, :data, 'parcelada', 'credito', 0)
            ");

            $dataBase = new DateTimeImmutable($dados['data_primeira_parcela']);

            for ($i = 1; $i <= $nParcelas; $i++) {
                $isUltima = ($i === $nParcelas);
                $valor    = $isUltima ? $valorUltima : $valorParcela;
                $dataVenc = $dataBase->modify('+' . ($i - 1) . ' months');
                $dataStr  = $dataVenc->format('Y-m-d');

                // ── 3. Inserir parcela ──────────────────────
                $stmtParcela->execute([
                    ':pid'       => $parcelamentoId,
                    ':num'       => $i,
                    ':valor'     => $valor,
                    ':data_venc' => $dataStr,
                ]);
                $parcelaId = (int) $this->db->lastInsertId();

                // ── 4. Inserir despesa vinculada à parcela ──
                $desc = trim($dados['descricao']) . " ({$i}/{$nParcelas})";

                $stmtDespesa->execute([
                    ':uid'       => $usuarioId,
                    ':cat'       => $dados['categoria_id'] ?: null,
                    ':cartao'    => $dados['cartao_id']    ?: null,
                    ':parcela_id'=> $parcelaId,
                    ':desc'      => $desc,
                    ':valor'     => $valor,
                    ':data'      => $dataStr,
                ]);
            }

            $this->commit();
            return $parcelamentoId;

        } catch (\Exception $e) {
            $this->rollBack();
            throw $e;
        }
    }

    /**
     * Marca uma parcela como paga/pendente e sincroniza o status
     * no registro correspondente da tabela `despesas`.
     */
    public function marcarParcelaPaga(int $parcelaId, int $usuarioId, bool $pago): bool
    {
        $this->beginTransaction();
        try {
            // Atualiza a parcela
            $stmt = $this->db->prepare("
                UPDATE parcelas p
                JOIN parcelamentos par ON par.id = p.parcelamento_id
                SET p.pago           = :pago,
                    p.data_pagamento = :data_pag
                WHERE p.id = :pid AND par.usuario_id = :uid
            ");
            $stmt->execute([
                ':pago'    => (int) $pago,
                ':data_pag'=> $pago ? date('Y-m-d') : null,
                ':pid'     => $parcelaId,
                ':uid'     => $usuarioId,
            ]);

            // Sincroniza o status na tabela despesas
            $stmtD = $this->db->prepare("
                UPDATE despesas
                SET pago           = :pago,
                    data_pagamento = :data_pag
                WHERE parcela_id = :pid AND usuario_id = :uid
            ");
            $stmtD->execute([
                ':pago'    => (int) $pago,
                ':data_pag'=> $pago ? date('Y-m-d') : null,
                ':pid'     => $parcelaId,
                ':uid'     => $usuarioId,
            ]);

            $this->commit();
            return $stmt->rowCount() > 0;

        } catch (\Exception $e) {
            $this->rollBack();
            throw $e;
        }
    }

    /**
     * Exclui o parcelamento, suas parcelas e as despesas vinculadas.
     * Ordem: despesas → parcelas → parcelamento (respeita FKs).
     */
    public function excluir(int $id, int $usuarioId): bool
    {
        // Valida dono antes de qualquer delete
        $stmt = $this->db->prepare(
            "SELECT id FROM parcelamentos WHERE id = :id AND usuario_id = :uid"
        );
        $stmt->execute([':id' => $id, ':uid' => $usuarioId]);
        if (!$stmt->fetch()) return false;

        $this->beginTransaction();
        try {
            // 1. Remove despesas vinculadas às parcelas deste parcelamento
            $this->db->prepare("
                DELETE d FROM despesas d
                JOIN parcelas p ON p.id = d.parcela_id
                WHERE p.parcelamento_id = :pid
            ")->execute([':pid' => $id]);

            // 2. Remove o parcelamento (cascata remove as parcelas via FK)
            $this->db->prepare(
                "DELETE FROM parcelamentos WHERE id = :id AND usuario_id = :uid"
            )->execute([':id' => $id, ':uid' => $usuarioId]);

            $this->commit();
            return true;

        } catch (\Exception $e) {
            $this->rollBack();
            throw $e;
        }
    }

    /**
     * Bloqueia a exibição de parcelas a partir de uma data (primeiro dia do mês informado).
     * Parcelas com data_vencimento >= $data não aparecerão mais nas despesas.
     */
    public function bloquear(int $id, int $usuarioId, string $data): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE parcelamentos SET bloqueado_a_partir = :data WHERE id = :id AND usuario_id = :uid"
        );
        $stmt->execute([':data' => $data, ':id' => $id, ':uid' => $usuarioId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Remove o bloqueio, fazendo as parcelas voltarem a aparecer nas despesas.
     */
    public function desbloquear(int $id, int $usuarioId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE parcelamentos SET bloqueado_a_partir = NULL WHERE id = :id AND usuario_id = :uid"
        );
        $stmt->execute([':id' => $id, ':uid' => $usuarioId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Calcula a data da primeira parcela.
     * Delega para CartaoModel se um cartão for informado.
     */
    public function calcularDataPrimeiraParcela(
        string $dataCompra,
        ?int   $cartaoId = null
    ): string {
        if ($cartaoId) {
            $cartaoModel = new CartaoModel();
            return $cartaoModel->calcularDataPrimeiraParcela($cartaoId, $dataCompra);
        }
        // Sem cartão: primeiro dia do mês seguinte
        $compra = new DateTimeImmutable($dataCompra);
        return $compra->modify('first day of next month')->format('Y-m-d');
    }
}
