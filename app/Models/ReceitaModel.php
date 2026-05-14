<?php
/**
 * ReceitaModel — Gerenciamento de receitas financeiras.
 */

declare(strict_types=1);

class ReceitaModel extends BaseModel
{
    protected string $table = 'receitas';

    /**
     * Lista receitas do usuário com filtros opcionais de mês/ano.
     */
    public function listar(
        int $usuarioId,
        int $ano,
        int $mes,
        ?int $categoriaId = null,
        ?string $tipo = null,
        int $pagina = 1,
        int $porPagina = 20
    ): array {
        $params = [':uid' => $usuarioId, ':ano' => $ano, ':mes' => $mes];
        $where  = "r.usuario_id = :uid AND YEAR(r.data) = :ano AND MONTH(r.data) = :mes";

        if ($categoriaId) {
            $where .= " AND r.categoria_id = :cat";
            $params[':cat'] = $categoriaId;
        }

        if ($tipo) {
            $where .= " AND r.tipo_receita = :tipo";
            $params[':tipo'] = $tipo;
        }

        $offset = ($pagina - 1) * $porPagina;

        $stmt = $this->db->prepare("
            SELECT r.*, c.nome AS categoria_nome, c.cor AS categoria_cor
            FROM receitas r
            LEFT JOIN categorias c ON c.id = r.categoria_id
            WHERE {$where}
            ORDER BY r.data DESC, r.id DESC
            LIMIT {$porPagina} OFFSET {$offset}
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Conta total de registros para paginação.
     */
    public function contar(int $usuarioId, int $ano, int $mes): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM receitas
            WHERE usuario_id = :uid AND YEAR(data) = :ano AND MONTH(data) = :mes
        ");
        $stmt->execute([':uid' => $usuarioId, ':ano' => $ano, ':mes' => $mes]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Soma total de receitas do mês.
     */
    public function totalMes(int $usuarioId, int $ano, int $mes): float
    {
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(valor), 0) FROM receitas
            WHERE usuario_id = :uid AND YEAR(data) = :ano AND MONTH(data) = :mes
        ");
        $stmt->execute([':uid' => $usuarioId, ':ano' => $ano, ':mes' => $mes]);
        return (float) $stmt->fetchColumn();
    }

    /**
     * Cria nova receita.
     */
    public function criar(int $usuarioId, array $dados): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO receitas
                (usuario_id, categoria_id, descricao, valor, data, tipo_receita, observacoes)
            VALUES
                (:uid, :cat, :desc, :valor, :data, :tipo, :obs)
        ");
        $stmt->execute([
            ':uid'   => $usuarioId,
            ':cat'   => $dados['categoria_id'] ?: null,
            ':desc'  => trim($dados['descricao']),
            ':valor' => $dados['valor'],
            ':data'  => $dados['data'],
            ':tipo'  => $dados['tipo_receita'],
            ':obs'   => $dados['observacoes'] ?: null,
        ]);
        return $this->lastId();
    }

    /**
     * Atualiza receita (valida dono).
     */
    public function atualizar(int $id, int $usuarioId, array $dados): bool
    {
        $stmt = $this->db->prepare("
            UPDATE receitas
            SET categoria_id = :cat,
                descricao    = :desc,
                valor        = :valor,
                data         = :data,
                tipo_receita = :tipo,
                observacoes  = :obs
            WHERE id = :id AND usuario_id = :uid
        ");
        $stmt->execute([
            ':cat'   => $dados['categoria_id'] ?: null,
            ':desc'  => trim($dados['descricao']),
            ':valor' => $dados['valor'],
            ':data'  => $dados['data'],
            ':tipo'  => $dados['tipo_receita'],
            ':obs'   => $dados['observacoes'] ?: null,
            ':id'    => $id,
            ':uid'   => $usuarioId,
        ]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Exclui receita (valida dono).
     */
    public function excluir(int $id, int $usuarioId): bool
    {
        return $this->deleteByIdAndUser($id, $usuarioId);
    }

    /**
     * Evolução mensal de receitas (últimos N meses).
     */
    public function evolucaoMensal(int $usuarioId, int $meses = 12): array
    {
        $stmt = $this->db->prepare("
            SELECT
                YEAR(data)  AS ano,
                MONTH(data) AS mes,
                SUM(valor)  AS total
            FROM receitas
            WHERE usuario_id = :uid
              AND data >= DATE_SUB(CURDATE(), INTERVAL :meses MONTH)
            GROUP BY YEAR(data), MONTH(data)
            ORDER BY ano, mes
        ");
        $stmt->execute([':uid' => $usuarioId, ':meses' => $meses]);
        return $stmt->fetchAll();
    }
}
