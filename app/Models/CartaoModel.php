<?php
/**
 * CartaoModel — Gerenciamento de cartões de crédito.
 */

declare(strict_types=1);

class CartaoModel extends BaseModel
{
    protected string $table = 'cartoes';

    /**
     * Lista cartões ativos do usuário com totais de gastos do mês.
     */
    public function listarComGastos(int $usuarioId, int $ano, int $mes): array
    {
        $stmt = $this->db->prepare("
            SELECT
                c.*,
                COALESCE(SUM(d.valor), 0) AS total_gasto,
                c.limite - COALESCE(SUM(d.valor), 0) AS limite_disponivel,
                CASE WHEN c.limite > 0
                     THEN ROUND((COALESCE(SUM(d.valor),0) / c.limite) * 100, 1)
                     ELSE 0 END AS percentual_uso
            FROM cartoes c
            LEFT JOIN despesas d
                ON d.cartao_id = c.id
                AND YEAR(d.data) = :ano
                AND MONTH(d.data) = :mes
            WHERE c.usuario_id = :uid AND c.ativo = 1
            GROUP BY c.id
            ORDER BY c.nome ASC
        ");
        $stmt->execute([':uid' => $usuarioId, ':ano' => $ano, ':mes' => $mes]);
        return $stmt->fetchAll();
    }

    /**
     * Lista apenas os cartões ativos (para selects de formulários).
     */
    public function listar(int $usuarioId): array
    {
        return $this->findAllByUser($usuarioId, 'nome ASC');
    }

    /**
     * Busca cartão por ID validando o dono.
     */
    public function buscarPorId(int $id, int $usuarioId): ?array
    {
        return $this->findById($id, $usuarioId);
    }

    /**
     * Cria novo cartão.
     */
    public function criar(int $usuarioId, array $dados): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO cartoes
                (usuario_id, nome, bandeira, limite, dia_fechamento, dia_vencimento, cor)
            VALUES
                (:uid, :nome, :bandeira, :limite, :fechamento, :vencimento, :cor)
        ");
        $stmt->execute([
            ':uid'        => $usuarioId,
            ':nome'       => trim($dados['nome']),
            ':bandeira'   => $dados['bandeira']  ?? 'outro',
            ':limite'     => $dados['limite'],
            ':fechamento' => $dados['dia_fechamento'],
            ':vencimento' => $dados['dia_vencimento'],
            ':cor'        => $dados['cor'] ?? '#2563eb',
        ]);
        return $this->lastId();
    }

    /**
     * Atualiza cartão (valida dono).
     */
    public function atualizar(int $id, int $usuarioId, array $dados): bool
    {
        $stmt = $this->db->prepare("
            UPDATE cartoes
            SET nome           = :nome,
                bandeira       = :bandeira,
                limite         = :limite,
                dia_fechamento = :fechamento,
                dia_vencimento = :vencimento,
                cor            = :cor
            WHERE id = :id AND usuario_id = :uid
        ");
        $stmt->execute([
            ':nome'       => trim($dados['nome']),
            ':bandeira'   => $dados['bandeira']  ?? 'outro',
            ':limite'     => $dados['limite'],
            ':fechamento' => $dados['dia_fechamento'],
            ':vencimento' => $dados['dia_vencimento'],
            ':cor'        => $dados['cor'] ?? '#2563eb',
            ':id'         => $id,
            ':uid'        => $usuarioId,
        ]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Desativa cartão (soft delete).
     */
    public function desativar(int $id, int $usuarioId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE cartoes SET ativo = 0 WHERE id = :id AND usuario_id = :uid"
        );
        $stmt->execute([':id' => $id, ':uid' => $usuarioId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Retorna as despesas da fatura de um cartão em um mês/ano.
     */
    public function getFatura(int $cartaoId, int $usuarioId, int $ano, int $mes): array
    {
        $stmt = $this->db->prepare("
            SELECT d.*, c.nome AS categoria_nome, c.cor AS categoria_cor
            FROM despesas d
            LEFT JOIN categorias c ON c.id = d.categoria_id
            WHERE d.cartao_id = :cid
              AND d.usuario_id = :uid
              AND YEAR(d.data) = :ano
              AND MONTH(d.data) = :mes
            ORDER BY d.data DESC
        ");
        $stmt->execute([':cid' => $cartaoId, ':uid' => $usuarioId, ':ano' => $ano, ':mes' => $mes]);
        return $stmt->fetchAll();
    }

    /**
     * Calcula a data da primeira parcela com base no dia de fechamento do cartão.
     */
    public function calcularDataPrimeiraParcela(int $cartaoId, string $dataCompra): string
    {
        $cartao = $this->db->prepare("SELECT dia_fechamento, dia_vencimento FROM cartoes WHERE id = :id");
        $cartao->execute([':id' => $cartaoId]);
        $c = $cartao->fetch();

        if (!$c) return date('Y-m-d');

        $compra        = new DateTimeImmutable($dataCompra);
        $diaCompra     = (int) $compra->format('d');
        $diaFechamento = (int) $c['dia_fechamento'];
        $diaVencimento = (int) $c['dia_vencimento'];

        // Se comprou antes do fechamento → vence no mês seguinte
        // Se comprou no/após fechamento → vence em dois meses
        $refMes = $diaCompra < $diaFechamento
            ? $compra->modify('first day of next month')
            : $compra->modify('first day of +2 months');

        // Limita ao último dia do mês de vencimento
        $ultimoDia = (int) $refMes->format('t');
        $dia       = min($diaVencimento, $ultimoDia);

        return $refMes->format('Y-m-') . str_pad((string)$dia, 2, '0', STR_PAD_LEFT);
    }
}
