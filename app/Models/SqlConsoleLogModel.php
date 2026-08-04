<?php
/**
 * SqlConsoleLogModel — Auditoria de execuções do console SQL (/admin/sql).
 * Nunca grava a senha do banco-alvo, só host/banco/preview do SQL e o
 * resultado.
 */

declare(strict_types=1);

class SqlConsoleLogModel extends BaseModel
{
    protected string $table = 'sql_console_log';

    public function registrar(int $usuarioId, string $host, string $banco, string $sqlPreview, string $status, ?string $mensagem): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO sql_console_log (usuario_id, host, banco, sql_preview, status, mensagem)
             VALUES (:uid, :host, :banco, :sql, :status, :msg)"
        );
        $stmt->execute([
            ':uid'    => $usuarioId,
            ':host'   => $host,
            ':banco'  => $banco,
            ':sql'    => mb_substr($sqlPreview, 0, 2000),
            ':status' => $status,
            ':msg'    => $mensagem !== null ? mb_substr($mensagem, 0, 500) : null,
        ]);
    }

    public function historico(int $usuarioId, int $limite = 30): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM sql_console_log WHERE usuario_id = :uid ORDER BY id DESC LIMIT :lim"
        );
        $stmt->bindValue(':uid', $usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
