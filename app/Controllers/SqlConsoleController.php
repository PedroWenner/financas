<?php
/**
 * SqlConsoleController — Console SQL (/admin/sql): conecta a um banco
 * MySQL informado na hora (host/porta/banco/usuário/senha) e roda SQL
 * livre ou os arquivos de migrations/. Acesso restrito a e-mails listados
 * em SQL_CONSOLE_ADMINS (ver podeAcessarSqlConsole() em FormatHelper.php)
 * — Auth::requireLogin() sozinho não basta porque o /cadastro do sistema é
 * público.
 */

declare(strict_types=1);

class SqlConsoleController
{
    private const SESSION_KEY = 'sql_console_conn';

    private SqlConsoleService $service;
    private SqlConsoleLogModel $logModel;
    private SqlConsoleConsultaModel $consultaModel;

    public function __construct()
    {
        Auth::requireLogin();

        if (!podeAcessarSqlConsole()) {
            http_response_code(403);
            die('Acesso restrito.');
        }

        $this->service       = new SqlConsoleService();
        $this->logModel      = new SqlConsoleLogModel();
        $this->consultaModel = new SqlConsoleConsultaModel();
    }

    // ──────────────────────────────────────────────────────────
    // GET /admin/sql
    // ──────────────────────────────────────────────────────────

    public function index(): void
    {
        $this->renderConsole();
    }

    // ──────────────────────────────────────────────────────────
    // POST /admin/sql/conectar
    // ──────────────────────────────────────────────────────────

    public function conectar(): void
    {
        CsrfHelper::check();

        $host    = trim((string) Request::post('host', ''));
        $porta   = (int) Request::post('porta', '3306');
        $banco   = trim((string) Request::post('banco', ''));
        $usuario = trim((string) Request::post('usuario', ''));
        $senha   = (string) Request::post('senha', '');

        $erroConexao = null;

        if ($host === '' || $banco === '' || $usuario === '') {
            $erroConexao = 'Preencha host, banco e usuário.';
        } else {
            try {
                $this->service->conectar($host, $porta, $banco, $usuario, $senha);
                $_SESSION[self::SESSION_KEY] = [
                    'host' => $host, 'porta' => $porta, 'banco' => $banco, 'usuario' => $usuario, 'senha' => $senha,
                ];
            } catch (PDOException $e) {
                $erroConexao = 'Falha ao conectar: ' . $e->getMessage();
            }
        }

        $this->renderConsole([
            'erroConexao' => $erroConexao,
            'defaults'    => ['host' => $host, 'porta' => $porta, 'banco' => $banco, 'usuario' => $usuario],
        ]);
    }

    // ──────────────────────────────────────────────────────────
    // POST /admin/sql/desconectar
    // ──────────────────────────────────────────────────────────

    public function desconectar(): void
    {
        CsrfHelper::check();
        unset($_SESSION[self::SESSION_KEY]);
        Response::redirect('/admin/sql');
    }

    // ──────────────────────────────────────────────────────────
    // POST /admin/sql/executar  — roda o SQL colado na caixa de texto
    // ──────────────────────────────────────────────────────────

    public function executar(): void
    {
        CsrfHelper::check();
        $sql = (string) Request::post('sql', '');
        $this->executarERenderizar($sql);
    }

    // ──────────────────────────────────────────────────────────
    // POST /admin/sql/consulta — construtor de consulta (somente leitura)
    // ──────────────────────────────────────────────────────────

    public function executarConsulta(): void
    {
        CsrfHelper::check();
        $sql = (string) Request::post('sql', '');
        $this->executarERenderizar($sql, null, true);
    }

    // ──────────────────────────────────────────────────────────
    // POST /admin/sql/consulta/salvar — salva o script montado
    // ──────────────────────────────────────────────────────────

    public function salvarConsulta(): void
    {
        CsrfHelper::check();

        $descricao = trim((string) Request::post('descricao', ''));
        $script    = (string) Request::post('script', '');

        if ($descricao === '' || trim($script) === '') {
            $this->renderConsole([
                'erroConsulta' => 'Informe a descrição e monte o script antes de salvar.',
                'abaAtiva'     => 'consulta',
            ]);
            return;
        }

        $this->consultaModel->salvar(Auth::id(), $descricao, $script);
        $this->renderConsole(['abaAtiva' => 'consulta']);
    }

    // ──────────────────────────────────────────────────────────
    // POST /admin/sql/consulta/excluir — remove um script salvo
    // ──────────────────────────────────────────────────────────

    public function excluirConsulta(): void
    {
        CsrfHelper::check();
        $id = (int) Request::post('id', 0);
        $this->consultaModel->excluir($id, Auth::id());
        $this->renderConsole(['abaAtiva' => 'consulta']);
    }

    // ──────────────────────────────────────────────────────────
    // POST /admin/sql/migration  — roda um arquivo de migrations/
    // ──────────────────────────────────────────────────────────

    public function executarMigration(): void
    {
        CsrfHelper::check();

        $arquivo = trim((string) Request::post('arquivo', ''));

        if (!in_array($arquivo, $this->service->listarMigrations(), true)) {
            $this->renderConsole(['erroConexao' => 'Arquivo de migration não encontrado.']);
            return;
        }

        try {
            $sql = $this->service->lerMigration($arquivo);
        } catch (InvalidArgumentException $e) {
            $this->renderConsole(['erroConexao' => $e->getMessage()]);
            return;
        }

        $this->executarERenderizar($sql, $arquivo);
    }

    // ──────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────

    private function executarERenderizar(string $sql, ?string $origemMigration = null, bool $somenteLeitura = false): void
    {
        $conn           = $_SESSION[self::SESSION_KEY] ?? null;
        $abaAtiva       = $somenteLeitura ? 'consulta' : 'livre';
        $chaveErro      = $somenteLeitura ? 'erroConsulta' : 'erroConexao';
        $chaveResultado = $somenteLeitura ? 'resultadoConsulta' : 'resultado';

        if ($conn === null) {
            $this->renderConsole([$chaveErro => 'Conecte a um banco antes de executar comandos.', 'abaAtiva' => $abaAtiva]);
            return;
        }

        if (trim($sql) === '') {
            $this->renderConsole([$chaveErro => 'Nenhum comando SQL informado.', 'abaAtiva' => $abaAtiva]);
            return;
        }

        if ($somenteLeitura) {
            foreach ($this->service->dividirStatements($sql) as $stmt) {
                if (!$this->service->somenteLeitura($stmt)) {
                    $this->renderConsole([
                        $chaveErro => 'Somente comandos de consulta (SELECT/SHOW/DESCRIBE/EXPLAIN) são permitidos no construtor de consulta.',
                        'abaAtiva' => $abaAtiva,
                    ]);
                    return;
                }
            }
        }

        try {
            $pdo       = $this->service->conectar($conn['host'], (int) $conn['porta'], $conn['banco'], $conn['usuario'], $conn['senha']);
            $resultado = $this->service->executarLote($pdo, $sql);

            $preview = $origemMigration !== null ? "[migration: {$origemMigration}]\n" . $sql : $sql;

            $this->logSeguro(
                $conn['host'],
                $conn['banco'],
                $preview,
                $resultado['ok'] ? 'sucesso' : 'erro',
                $resultado['ok']
                    ? "{$resultado['executados']}/{$resultado['total']} statement(s) executado(s)."
                    : $this->mensagemErro($resultado)
            );

            $this->renderConsole([$chaveResultado => $resultado, 'abaAtiva' => $abaAtiva]);
        } catch (PDOException $e) {
            $this->logSeguro($conn['host'], $conn['banco'], $sql, 'erro', $e->getMessage());
            $this->renderConsole([$chaveErro => 'Falha ao conectar: ' . $e->getMessage(), 'abaAtiva' => $abaAtiva]);
        }
    }

    private function mensagemErro(array $resultado): string
    {
        $ultimo = end($resultado['statements']);
        return $ultimo !== false ? (string) ($ultimo['erro'] ?? 'Erro desconhecido.') : 'Erro desconhecido.';
    }

    /** Grava a auditoria sem nunca deixar uma falha de log derrubar a página. */
    private function logSeguro(string $host, string $banco, string $sqlPreview, string $status, ?string $mensagem): void
    {
        try {
            $this->logModel->registrar(Auth::id(), $host, $banco, $sqlPreview, $status, $mensagem);
        } catch (Throwable) {
            // Tabela de log pode ainda não existir (ver migrations/004) — não impede o console de funcionar.
        }
    }

    private function renderConsole(array $overrides = []): void
    {
        $conn = $_SESSION[self::SESSION_KEY] ?? null;

        $dados = array_merge([
            'titulo'            => 'Console SQL',
            'conn'              => $conn,
            'migrations'        => $this->service->listarMigrations(),
            'historico'         => $this->safeHistorico(),
            'esquema'           => $this->safeEsquema($conn),
            'consultasSalvas'   => $this->safeConsultasSalvas(),
            'resultado'         => null,
            'resultadoConsulta' => null,
            'erroConexao'       => null,
            'erroConsulta'      => null,
            'abaAtiva'          => 'livre',
            'defaults'          => [
                'host' => DB_HOST, 'porta' => (int) DB_PORT, 'banco' => DB_NAME, 'usuario' => DB_USER,
            ],
            'scripts'           => ['/assets/js/sql-console.js'],
        ], $overrides);

        Response::view('admin/sql', $dados);
    }

    private function safeHistorico(): array
    {
        try {
            return $this->logModel->historico(Auth::id());
        } catch (Throwable) {
            return [];
        }
    }

    private function safeConsultasSalvas(): array
    {
        try {
            return $this->consultaModel->listar(Auth::id());
        } catch (Throwable) {
            return [];
        }
    }

    /** Introspecta tabelas/colunas/FKs do banco conectado para o construtor de consulta. */
    private function safeEsquema(?array $conn): array
    {
        if ($conn === null) {
            return ['tabelas' => [], 'fks' => []];
        }

        try {
            $pdo = $this->service->conectar($conn['host'], (int) $conn['porta'], $conn['banco'], $conn['usuario'], $conn['senha']);
            return $this->service->obterEsquema($pdo, $conn['banco']);
        } catch (Throwable) {
            return ['tabelas' => [], 'fks' => []];
        }
    }
}
