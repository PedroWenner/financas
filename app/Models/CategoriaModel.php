<?php
/**
 * CategoriaModel — CRUD de categorias por usuário.
 */

declare(strict_types=1);

class CategoriaModel extends BaseModel
{
    protected string $table = 'categorias';

    /** Lista todas as categorias do usuário. */
    public function listar(int $usuarioId, ?string $tipo = null): array
    {
        $sql = "SELECT * FROM categorias WHERE usuario_id = :uid AND ativo = 1";
        $params = [':uid' => $usuarioId];

        if ($tipo !== null) {
            $sql .= " AND (tipo = :tipo OR tipo = 'ambos')";
            $params[':tipo'] = $tipo;
        }

        $sql .= " ORDER BY nome ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Cria nova categoria. */
    public function criar(int $usuarioId, array $dados): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO categorias (usuario_id, nome, tipo, icone, cor)
             VALUES (:uid, :nome, :tipo, :icone, :cor)"
        );
        $stmt->execute([
            ':uid'   => $usuarioId,
            ':nome'  => trim($dados['nome']),
            ':tipo'  => $dados['tipo']  ?? 'ambos',
            ':icone' => $dados['icone'] ?? null,
            ':cor'   => $dados['cor']   ?? '#6c757d',
        ]);
        return $this->lastId();
    }

    /** Atualiza categoria (valida dono). */
    public function atualizar(int $id, int $usuarioId, array $dados): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE categorias
             SET nome = :nome, tipo = :tipo, icone = :icone, cor = :cor
             WHERE id = :id AND usuario_id = :uid"
        );
        $stmt->execute([
            ':nome'  => trim($dados['nome']),
            ':tipo'  => $dados['tipo']  ?? 'ambos',
            ':icone' => $dados['icone'] ?? null,
            ':cor'   => $dados['cor']   ?? '#6c757d',
            ':id'    => $id,
            ':uid'   => $usuarioId,
        ]);
        return $stmt->rowCount() > 0;
    }

    /** Soft delete (desativa a categoria). */
    public function desativar(int $id, int $usuarioId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE categorias SET ativo = 0 WHERE id = :id AND usuario_id = :uid"
        );
        $stmt->execute([':id' => $id, ':uid' => $usuarioId]);
        return $stmt->rowCount() > 0;
    }

    /** Verifica se a categoria está em uso (tem receitas ou despesas vinculadas). */
    public function emUso(int $id): bool
    {
        $tabelas = ['receitas', 'despesas', 'despesas_fixas', 'parcelamentos'];
        foreach ($tabelas as $tabela) {
            $stmt = $this->db->prepare(
                "SELECT 1 FROM {$tabela} WHERE categoria_id = :id LIMIT 1"
            );
            $stmt->execute([':id' => $id]);
            if ($stmt->fetchColumn()) return true;
        }
        return false;
    }
}
