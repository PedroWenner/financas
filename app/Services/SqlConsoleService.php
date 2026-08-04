<?php
/**
 * SqlConsoleService — Conecta a um banco MySQL arbitrário (host/porta/
 * banco/usuário/senha informados na hora, não necessariamente o banco do
 * próprio app) e executa lotes de comandos SQL colados ou lidos de um
 * arquivo de migrations/. Usado exclusivamente por SqlConsoleController,
 * que já restringe o acesso a essa funcionalidade.
 */

declare(strict_types=1);

class SqlConsoleService
{
    /** Abre uma conexão PDO independente da Database::getInstance() do app. */
    public function conectar(string $host, int $porta, string $banco, string $usuario, string $senha): PDO
    {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $porta, $banco);

        return new PDO($dsn, $usuario, $senha, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => 8,
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
        ]);
    }

    /**
     * Separa um bloco de SQL colado em statements individuais por ";",
     * ciente de aspas simples/duplas/backtick (não quebra ";" dentro de
     * strings) e ignorando comentários de linha "-- ...". Não é um parser
     * SQL completo, mas cobre bem os arquivos de migrations/ do projeto e
     * SQL colado manualmente.
     */
    public function dividirStatements(string $sqlBruto): array
    {
        $statements = [];
        $atual      = '';
        $emAspas    = null; // null | "'" | '"' | '`'
        $len        = strlen($sqlBruto);

        for ($i = 0; $i < $len; $i++) {
            $c = $sqlBruto[$i];

            if ($emAspas !== null) {
                if ($c === '\\' && $emAspas !== '`') {
                    $atual .= $c;
                    if ($i + 1 < $len) {
                        $atual .= $sqlBruto[$i + 1];
                        $i++;
                    }
                    continue;
                }

                $atual .= $c;

                if ($c === $emAspas) {
                    if ($i + 1 < $len && $sqlBruto[$i + 1] === $emAspas) {
                        $atual .= $sqlBruto[$i + 1]; // aspas duplicadas dentro da string ('' escapando ')
                        $i++;
                    } else {
                        $emAspas = null;
                    }
                }
                continue;
            }

            if ($c === "'" || $c === '"' || $c === '`') {
                $emAspas = $c;
                $atual  .= $c;
                continue;
            }

            if ($c === '-' && ($sqlBruto[$i + 1] ?? '') === '-') {
                while ($i < $len && $sqlBruto[$i] !== "\n") {
                    $i++;
                }
                continue;
            }

            if ($c === ';') {
                $trim = trim($atual);
                if ($trim !== '') {
                    $statements[] = $trim;
                }
                $atual = '';
                continue;
            }

            $atual .= $c;
        }

        $trim = trim($atual);
        if ($trim !== '') {
            $statements[] = $trim;
        }

        return $statements;
    }

    /**
     * Executa cada statement em sequência e para no primeiro erro.
     *
     * @return array{ok:bool,statements:array,executados:int,total:int}
     */
    public function executarLote(PDO $pdo, string $sqlBruto): array
    {
        $statements = $this->dividirStatements($sqlBruto);

        $resultado = [
            'ok'         => true,
            'statements' => [],
            'executados' => 0,
            'total'      => count($statements),
        ];

        foreach ($statements as $stmt) {
            $item = [
                'sql'      => $stmt,
                'tipo'     => null,
                'colunas'  => null,
                'linhas'   => null,
                'afetadas' => null,
                'erro'     => null,
            ];

            try {
                if (preg_match('/^\s*(SELECT|SHOW|DESCRIBE|DESC|EXPLAIN)\b/i', $stmt)) {
                    $item['tipo'] = 'select';
                    $pdoStmt      = $pdo->query($stmt);
                    $linhas       = $pdoStmt->fetchAll(PDO::FETCH_ASSOC);
                    $item['linhas']  = $linhas;
                    $item['colunas'] = $linhas !== [] ? array_keys($linhas[0]) : [];
                } else {
                    $item['tipo']     = 'exec';
                    $item['afetadas'] = $pdo->exec($stmt);
                }

                $resultado['executados']++;
                $resultado['statements'][] = $item;
            } catch (PDOException $e) {
                $item['erro'] = $e->getMessage();
                $resultado['statements'][] = $item;
                $resultado['ok'] = false;
                return $resultado;
            }
        }

        return $resultado;
    }

    /** Nomes dos arquivos em migrations/, ordenados. */
    public function listarMigrations(): array
    {
        $arquivos = glob(BASE_PATH . '/migrations/*.sql') ?: [];
        $nomes    = array_map('basename', $arquivos);
        sort($nomes);
        return $nomes;
    }

    /** Lê o conteúdo de um arquivo de migrations/, validando o nome contra path traversal. */
    public function lerMigration(string $arquivo): string
    {
        if (!preg_match('/^[\w.-]+\.sql$/', $arquivo)) {
            throw new InvalidArgumentException('Nome de arquivo inválido.');
        }

        $dir  = realpath(BASE_PATH . '/migrations');
        $path = realpath(BASE_PATH . '/migrations/' . $arquivo);

        if ($dir === false || $path === false || !str_starts_with($path, $dir)) {
            throw new InvalidArgumentException('Arquivo de migration não encontrado.');
        }

        return (string) file_get_contents($path);
    }
}
