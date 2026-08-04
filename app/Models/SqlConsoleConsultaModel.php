<?php
/**
 * SqlConsoleConsultaModel — Scripts salvos do construtor de consulta
 * (/admin/sql, aba "Construtor de Consulta"): só descrição + script.
 */

declare(strict_types=1);

class SqlConsoleConsultaModel extends BaseModel
{
    protected string $table = 'sql_console_consultas';

    public function listar(int $usuarioId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM sql_console_consultas WHERE usuario_id = :uid ORDER BY descricao ASC"
        );
        $stmt->execute([':uid' => $usuarioId]);
        return $stmt->fetchAll();
    }

    public function salvar(int $usuarioId, string $descricao, string $script): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO sql_console_consultas (usuario_id, descricao, script) VALUES (:uid, :desc, :script)"
        );
        $stmt->execute([
            ':uid'    => $usuarioId,
            ':desc'   => $descricao,
            ':script' => $script,
        ]);
        return $this->lastId();
    }

    public function excluir(int $id, int $usuarioId): bool
    {
        return $this->deleteByIdAndUser($id, $usuarioId);
    }
}
