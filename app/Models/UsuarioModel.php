<?php
/**
 * UsuarioModel — Gerenciamento de usuários e autenticação.
 */

declare(strict_types=1);

class UsuarioModel extends BaseModel
{
    protected string $table = 'usuarios';

    /** Busca usuário pelo e-mail. */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM usuarios WHERE email = :email AND ativo = 1 LIMIT 1"
        );
        $stmt->execute([':email' => mb_strtolower(trim($email))]);
        return $stmt->fetch() ?: null;
    }

    /** Cria novo usuário. Retorna o ID ou 0 em caso de e-mail duplicado. */
    public function criar(string $nome, string $email, string $senha): int
    {
        // Verifica se e-mail já existe
        if ($this->findByEmail($email)) {
            return 0;
        }

        $hash = password_hash($senha, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost'   => 4,
            'threads'     => 1,
        ]);

        $stmt = $this->db->prepare(
            "INSERT INTO usuarios (nome, email, senha_hash) VALUES (:nome, :email, :hash)"
        );
        $stmt->execute([
            ':nome'  => trim($nome),
            ':email' => mb_strtolower(trim($email)),
            ':hash'  => $hash,
        ]);

        return $this->lastId();
    }

    /** Verifica a senha e retorna o usuário ou null. */
    public function autenticar(string $email, string $senha): ?array
    {
        $usuario = $this->findByEmail($email);

        if (!$usuario) {
            return null;
        }

        if (!password_verify($senha, $usuario['senha_hash'])) {
            return null;
        }

        // Faz rehash se o algoritmo mudou
        if (password_needs_rehash($usuario['senha_hash'], PASSWORD_ARGON2ID)) {
            $this->atualizarSenha($usuario['id'], $senha);
        }

        // Registra último login
        $this->db->prepare(
            "UPDATE usuarios SET ultimo_login = NOW() WHERE id = :id"
        )->execute([':id' => $usuario['id']]);

        return $usuario;
    }

    /** Verifica se a senha fornecida confere com o hash armazenado. */
    public function verificarSenha(int $id, string $senha): bool
    {
        $stmt = $this->db->prepare(
            "SELECT senha_hash FROM usuarios WHERE id = :id AND ativo = 1 LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row && password_verify($senha, $row['senha_hash']);
    }

    /** Atualiza a senha hash do usuário. */
    public function atualizarSenha(int $id, string $novaSenha): void
    {
        $hash = password_hash($novaSenha, PASSWORD_ARGON2ID);
        $this->db->prepare(
            "UPDATE usuarios SET senha_hash = :hash WHERE id = :id"
        )->execute([':hash' => $hash, ':id' => $id]);
    }

    /** Busca usuário pelo ID. */
    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id, nome, email, created_at, ultimo_login FROM usuarios WHERE id = :id AND ativo = 1 LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /** Atualiza nome e e-mail do usuário. Retorna false se o e-mail já estiver em uso por outro. */
    public function atualizarPerfil(int $id, string $nome, string $email): bool
    {
        $email = mb_strtolower(trim($email));

        // Verifica se o e-mail já pertence a outro usuário
        $stmt = $this->db->prepare(
            "SELECT id FROM usuarios WHERE email = :email AND id != :id AND ativo = 1 LIMIT 1"
        );
        $stmt->execute([':email' => $email, ':id' => $id]);
        if ($stmt->fetch()) {
            return false; // e-mail já em uso
        }

        $this->db->prepare(
            "UPDATE usuarios SET nome = :nome, email = :email WHERE id = :id"
        )->execute([':nome' => trim($nome), ':email' => $email, ':id' => $id]);

        // Atualiza sessão imediatamente para refletir o novo nome/e-mail
        $_SESSION['user_nome']  = trim($nome);
        $_SESSION['user_email'] = $email;

        return true;
    }

    /** Insere as categorias padrão para um novo usuário. */
    public function inserirCategoriasPadrao(int $usuarioId): void
    {
        $categorias = [
            ['Moradia',       'despesa', 'home',           '#e74c3c'],
            ['Alimentação',   'despesa', 'utensils',       '#e67e22'],
            ['Transporte',    'despesa', 'car',            '#f39c12'],
            ['Saúde',         'despesa', 'heart-pulse',    '#e91e63'],
            ['Lazer',         'despesa', 'gamepad-2',      '#9b59b6'],
            ['Compras',       'despesa', 'shopping-bag',   '#3498db'],
            ['Educação',      'despesa', 'graduation-cap', '#1abc9c'],
            ['Assinaturas',   'despesa', 'tv',             '#34495e'],
            ['Contas',        'despesa', 'file-text',      '#7f8c8d'],
            ['Salário',       'receita', 'briefcase',      '#27ae60'],
            ['Investimentos', 'receita', 'trending-up',    '#2ecc71'],
            ['Renda Extra',   'receita', 'plus-circle',    '#16a085'],
        ];

        $stmt = $this->db->prepare(
            "INSERT INTO categorias (usuario_id, nome, tipo, icone, cor)
             VALUES (:uid, :nome, :tipo, :icone, :cor)"
        );

        foreach ($categorias as [$nome, $tipo, $icone, $cor]) {
            $stmt->execute([
                ':uid'   => $usuarioId,
                ':nome'  => $nome,
                ':tipo'  => $tipo,
                ':icone' => $icone,
                ':cor'   => $cor,
            ]);
        }
    }
}
