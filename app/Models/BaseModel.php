<?php
/**
 * BaseModel — Classe base para todos os Models.
 * Fornece métodos CRUD comuns e acesso ao PDO.
 */

declare(strict_types=1);

abstract class BaseModel
{
    protected PDO    $db;
    protected string $table  = '';
    protected string $pk     = 'id';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /** Busca registro por ID, garantindo que pertence ao usuário. */
    public function findById(int $id, ?int $usuarioId = null): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->pk} = :id";
        $params = [':id' => $id];

        if ($usuarioId !== null) {
            $sql .= ' AND usuario_id = :uid';
            $params[':uid'] = $usuarioId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch() ?: null;
    }

    /** Lista todos os registros do usuário. */
    public function findAllByUser(int $usuarioId, string $orderBy = 'id DESC'): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE usuario_id = :uid ORDER BY {$orderBy}"
        );
        $stmt->execute([':uid' => $usuarioId]);
        return $stmt->fetchAll();
    }

    /** Conta registros do usuário. */
    public function countByUser(int $usuarioId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM {$this->table} WHERE usuario_id = :uid"
        );
        $stmt->execute([':uid' => $usuarioId]);
        return (int) $stmt->fetchColumn();
    }

    /** Deleta registro por ID, validando o dono. */
    public function deleteByIdAndUser(int $id, int $usuarioId): bool
    {
        $stmt = $this->db->prepare(
            "DELETE FROM {$this->table} WHERE {$this->pk} = :id AND usuario_id = :uid"
        );
        $stmt->execute([':id' => $id, ':uid' => $usuarioId]);
        return $stmt->rowCount() > 0;
    }

    /** Retorna o último ID inserido. */
    protected function lastId(): int
    {
        return (int) $this->db->lastInsertId();
    }

    /** Inicia uma transação. */
    protected function beginTransaction(): void
    {
        $this->db->beginTransaction();
    }

    /** Confirma a transação. */
    protected function commit(): void
    {
        $this->db->commit();
    }

    /** Desfaz a transação. */
    protected function rollBack(): void
    {
        $this->db->rollBack();
    }
}
