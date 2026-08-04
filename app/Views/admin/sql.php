<?php
$conn              = $conn ?? null;
$migrations        = $migrations ?? [];
$historico         = $historico ?? [];
$esquema           = $esquema ?? ['tabelas' => [], 'fks' => []];
$resultado         = $resultado ?? null;
$resultadoConsulta = $resultadoConsulta ?? null;
$erroConexao       = $erroConexao ?? null;
$erroConsulta      = $erroConsulta ?? null;
$abaAtiva          = $abaAtiva ?? 'livre';
$defaults          = $defaults ?? ['host' => '', 'porta' => 3306, 'banco' => '', 'usuario' => ''];
$consultasSalvas   = $consultasSalvas ?? [];

/** Renderiza o bloco de resultado (usado tanto pelo SQL livre quanto pelo construtor de consulta). */
function renderResultadoSqlConsole(array $resultado, string $prefixoId): void
{
    ?>
    <div class="card mb-3">
        <div class="card-header">
            <span class="card-title">
                Resultado — <?= (int) $resultado['executados'] ?>/<?= (int) $resultado['total'] ?> statement(s)
                <?= $resultado['ok'] ? '<span class="stmt-ok">✓ concluído</span>' : '<span class="stmt-err">✗ interrompido por erro</span>' ?>
            </span>
        </div>
        <div class="card-body">
            <?php foreach ($resultado['statements'] as $i => $item): ?>
                <div class="stmt-block">
                    <div class="stmt-head">#<?= $i + 1 ?> — <?= e(truncate($item['sql'], 200)) ?></div>
                    <div class="stmt-body">
                        <?php if ($item['erro']): ?>
                            <span class="stmt-err">Erro: <?= e($item['erro']) ?></span>
                        <?php elseif ($item['tipo'] === 'select'): ?>
                            <?php if (empty($item['linhas'])): ?>
                                <span class="text-muted">0 linha(s).</span>
                            <?php else: ?>
                                <?php $tableId = $prefixoId . '-tabela-' . $i; ?>
                                <button type="button" class="btn btn-secondary btn-sm qb-exportar-excel" data-table-id="<?= $tableId ?>" style="margin-bottom:.5rem;">
                                    Exportar Excel
                                </button>
                                <div class="table-wrapper">
                                    <table id="<?= $tableId ?>">
                                        <thead>
                                            <tr><?php foreach ($item['colunas'] as $col): ?><th><?= e((string) $col) ?></th><?php endforeach; ?></tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($item['linhas'] as $linha): ?>
                                                <tr>
                                                    <?php foreach ($item['colunas'] as $col): ?>
                                                        <td><?= $linha[$col] !== null ? e((string) $linha[$col]) : '<span class="text-muted">NULL</span>' ?></td>
                                                    <?php endforeach; ?>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="stmt-ok"><?= (int) $item['afetadas'] ?> linha(s) afetada(s).</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}
?>

<style>
.sql-console-warn {
    background: #fef3c7; border: 1px solid #f59e0b; color: #92400e;
    border-radius: 8px; padding: .75rem 1rem; font-size: .85rem; margin-bottom: 1.25rem;
}
.sql-conn-banner {
    display: flex; align-items: center; justify-content: space-between; gap: 1rem;
    background: var(--surface-2); border-radius: 8px; padding: .75rem 1rem; margin-bottom: 1.25rem;
    font-size: .85rem;
}
.sql-textarea {
    width: 100%; min-height: 160px; font-family: ui-monospace, "SF Mono", Consolas, monospace;
    font-size: .82rem; padding: .75rem; border-radius: 8px; border: 1px solid var(--border);
    background: var(--surface); color: inherit; resize: vertical;
}
.migration-row {
    display: flex; align-items: center; justify-content: space-between; gap: .75rem;
    padding: .6rem .9rem; border-bottom: 1px solid var(--border); font-size: .85rem;
}
.migration-row:last-child { border-bottom: none; }
.migration-row code { font-size: .8rem; }
.stmt-block { border: 1px solid var(--border); border-radius: 8px; margin-bottom: .9rem; overflow: hidden; }
.stmt-head {
    background: var(--surface-2); padding: .5rem .75rem; font-family: ui-monospace, monospace;
    font-size: .78rem; white-space: pre-wrap; word-break: break-word;
}
.stmt-body { padding: .6rem .75rem; font-size: .85rem; }
.stmt-ok  { color: var(--success); font-weight: 600; }
.stmt-err { color: var(--danger); font-weight: 600; }

/* ── Abas ── */
.sql-tabs { display: flex; gap: 4px; border-bottom: 1px solid var(--border); margin-bottom: 1.25rem; }
.sql-tab-btn {
    background: none; border: none; padding: .65rem 1rem; font-size: .9rem; font-weight: 600;
    color: var(--text-muted); cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -1px;
}
.sql-tab-btn:hover { color: var(--text); }
.sql-tab-btn.active { color: var(--primary); border-bottom-color: var(--primary); }
.sql-tab-panel { display: none; }
.sql-tab-panel.active { display: block; }

/* ── Construtor de Consulta ── */
.qb-layout { display: flex; gap: 1rem; align-items: flex-start; flex-wrap: wrap; }
.qb-tree-card { flex: 1 1 320px; max-width: 380px; }
.qb-script-card { flex: 2 1 420px; min-width: 320px; }
.qb-tree { max-height: 560px; overflow-y: auto; }
.qb-table { border-bottom: 1px solid var(--border); }
.qb-table:last-child { border-bottom: none; }
.qb-table summary {
    padding: .6rem .9rem; cursor: pointer; font-size: .85rem; font-weight: 600;
    display: flex; align-items: center; gap: 8px; list-style: none;
}
.qb-table summary::-webkit-details-marker { display: none; }
.qb-fk-count { font-size: .7rem; font-weight: 400; color: var(--text-muted); }
.qb-colunas { padding: 0 .9rem .6rem 2rem; display: flex; flex-direction: column; gap: 6px; }
.qb-coluna { display: flex; align-items: center; gap: 6px; font-size: .8rem; cursor: pointer; }
.qb-tipo { color: var(--text-light); font-size: .72rem; margin-left: auto; }
.qb-tag { font-size: .65rem; font-weight: 600; padding: 1px 6px; border-radius: 10px; white-space: nowrap; }
.qb-tag-pk { background: var(--warning-light); color: var(--warning); }
.qb-tag-fk { background: var(--info-light); color: var(--info); }
.qb-referenciado-por { padding: 0 .9rem .7rem 2rem; font-size: .72rem; color: var(--text-muted); }
</style>

<div class="sql-console-warn">
    ⚠️ Esse console executa SQL diretamente no banco conectado, sem confirmação além do navegador. Use com cuidado — não há desfazer.
</div>

<div id="sql-console-app" data-aba-ativa="<?= e($abaAtiva) ?>">

    <div class="sql-tabs">
        <button type="button" class="sql-tab-btn" data-aba="livre">SQL Livre</button>
        <button type="button" class="sql-tab-btn" data-aba="consulta">Construtor de Consulta</button>
    </div>

    <!-- ══════════════════════════════════════════════ -->
    <!-- Aba: SQL Livre (conteúdo original do console)   -->
    <!-- ══════════════════════════════════════════════ -->
    <div class="sql-tab-panel" data-aba="livre">

        <?php if ($erroConexao): ?>
            <div class="alert alert-error mb-3"><?= e($erroConexao) ?></div>
        <?php endif; ?>

        <?php if ($conn === null): ?>

            <div class="card" style="max-width:520px;">
                <div class="card-header"><span class="card-title">Conectar a um banco</span></div>
                <div class="card-body">
                    <form method="POST" action="/admin/sql/conectar" autocomplete="off">
                        <?= CsrfHelper::field() ?>

                        <div class="d-flex gap-2">
                            <div class="form-group" style="flex:2;">
                                <label for="host">Host</label>
                                <input type="text" id="host" name="host" class="form-control" value="<?= e($defaults['host']) ?>" required>
                            </div>
                            <div class="form-group" style="flex:1;">
                                <label for="porta">Porta</label>
                                <input type="number" id="porta" name="porta" class="form-control" value="<?= (int) $defaults['porta'] ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="banco">Banco</label>
                            <input type="text" id="banco" name="banco" class="form-control" value="<?= e($defaults['banco']) ?>" required>
                        </div>

                        <div class="d-flex gap-2">
                            <div class="form-group" style="flex:1;">
                                <label for="usuario">Usuário</label>
                                <input type="text" id="usuario" name="usuario" class="form-control" value="<?= e($defaults['usuario']) ?>" autocomplete="off" required>
                            </div>
                            <div class="form-group" style="flex:1;">
                                <label for="senha">Senha</label>
                                <input type="password" id="senha" name="senha" class="form-control" autocomplete="new-password">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width:100%;margin-top:.5rem;">Conectar</button>
                    </form>
                </div>
            </div>

        <?php else: ?>

            <div class="sql-conn-banner">
                <span>🔌 Conectado a <strong><?= e($conn['usuario']) ?>@<?= e($conn['host']) ?>/<?= e($conn['banco']) ?></strong></span>
                <form method="POST" action="/admin/sql/desconectar" onsubmit="return confirm('Desconectar deste banco?')">
                    <?= CsrfHelper::field() ?>
                    <button type="submit" class="btn btn-secondary btn-sm">Desconectar</button>
                </form>
            </div>

            <div class="card mb-3">
                <div class="card-header"><span class="card-title">Migrations</span></div>
                <div class="card-body" style="padding:0;">
                    <?php if (empty($migrations)): ?>
                        <div class="empty-state" style="padding:1.5rem;">Nenhum arquivo em migrations/.</div>
                    <?php else: ?>
                        <?php foreach ($migrations as $arquivo): ?>
                            <div class="migration-row">
                                <code><?= e($arquivo) ?></code>
                                <form method="POST" action="/admin/sql/migration" onsubmit="return confirm('Executar <?= e($arquivo) ?> em <?= e($conn['banco']) ?>?')">
                                    <?= CsrfHelper::field() ?>
                                    <input type="hidden" name="arquivo" value="<?= e($arquivo) ?>">
                                    <button type="submit" class="btn btn-primary btn-sm">Executar</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><span class="card-title">SQL livre</span></div>
                <div class="card-body">
                    <form method="POST" action="/admin/sql/executar" onsubmit="return confirm('Executar este SQL em <?= e($conn['banco']) ?>?')">
                        <?= CsrfHelper::field() ?>
                        <textarea name="sql" class="sql-textarea" placeholder="-- cole aqui um ou mais comandos SQL, separados por ;"><?= isset($_POST['sql']) ? e($_POST['sql']) : '' ?></textarea>
                        <button type="submit" class="btn btn-primary" style="margin-top:.75rem;">Executar SQL</button>
                    </form>
                </div>
            </div>

            <?php if ($resultado !== null): ?>
                <?php renderResultadoSqlConsole($resultado, 'livre'); ?>
            <?php endif; ?>

        <?php endif; ?>

        <div class="card">
            <div class="card-header"><span class="card-title">Histórico de execuções</span></div>
            <div class="card-body" style="padding:0;">
                <?php if (empty($historico)): ?>
                    <div class="empty-state" style="padding:1.5rem;">Nenhuma execução registrada ainda.</div>
                <?php else: ?>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Quando</th>
                                    <th>Host / Banco</th>
                                    <th>Status</th>
                                    <th>Mensagem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($historico as $h): ?>
                                    <tr>
                                        <td class="text-muted"><?= dataHoraFmt($h['created_at']) ?></td>
                                        <td><?= e($h['host']) ?> / <?= e($h['banco']) ?></td>
                                        <td class="<?= $h['status'] === 'sucesso' ? 'stmt-ok' : 'stmt-err' ?>"><?= $h['status'] === 'sucesso' ? 'Sucesso' : 'Erro' ?></td>
                                        <td class="text-muted"><?= !empty($h['mensagem']) ? e($h['mensagem']) : '—' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- ══════════════════════════════════════════════ -->
    <!-- Aba: Construtor de Consulta (novo)              -->
    <!-- ══════════════════════════════════════════════ -->
    <div class="sql-tab-panel" data-aba="consulta">

        <?php if ($erroConsulta): ?>
            <div class="alert alert-error mb-3"><?= e($erroConsulta) ?></div>
        <?php endif; ?>

        <?php if ($conn === null): ?>
            <div class="empty-state" style="padding:1.5rem;">Conecte a um banco na aba "SQL Livre" para montar consultas.</div>
        <?php elseif (empty($esquema['tabelas'])): ?>
            <div class="empty-state" style="padding:1.5rem;">Não foi possível ler as tabelas deste banco.</div>
        <?php else: ?>

            <div class="qb-layout">
                <div class="card qb-tree-card">
                    <div class="card-header"><span class="card-title">Tabelas</span></div>
                    <div class="card-body" style="padding:0;">
                        <div id="qb-tree" class="qb-tree"></div>
                    </div>
                </div>

                <div class="qb-script-card">
                    <div class="card mb-3">
                        <div class="card-header"><span class="card-title">Consultas Salvas</span></div>
                        <div class="card-body" style="padding:0;">
                            <?php if (empty($consultasSalvas)): ?>
                                <div class="empty-state" style="padding:1.5rem;">Nenhum script salvo ainda.</div>
                            <?php else: ?>
                                <?php foreach ($consultasSalvas as $c): ?>
                                    <div class="migration-row">
                                        <span><?= e($c['descricao']) ?></span>
                                        <div class="d-flex gap-1">
                                            <button type="button" class="btn btn-secondary btn-sm qb-carregar-script" data-script="<?= e($c['script']) ?>">
                                                Carregar
                                            </button>
                                            <form method="POST" action="/admin/sql/consulta/excluir" onsubmit="return confirm('Excluir o script &quot;<?= e($c['descricao']) ?>&quot;?')" style="display:inline;">
                                                <?= CsrfHelper::field() ?>
                                                <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">Excluir</button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header"><span class="card-title">Script gerado</span></div>
                        <div class="card-body">
                            <p class="text-muted" style="font-size:.8rem;margin-top:0;">
                                Marque tabelas e colunas ao lado. O script é montado automaticamente
                                (JOINs entre tabelas com relação direta) e pode ser editado antes de executar.
                            </p>
                            <textarea id="qb-sql" class="sql-textarea" placeholder="Selecione tabelas e colunas ao lado…"></textarea>

                            <div class="d-flex gap-2" style="margin-top:.75rem;flex-wrap:wrap;">
                                <form method="POST" action="/admin/sql/consulta"
                                      onsubmit="document.getElementById('qb-sql-executar').value = document.getElementById('qb-sql').value; return confirm('Executar esta consulta em <?= e($conn['banco']) ?>?');">
                                    <?= CsrfHelper::field() ?>
                                    <input type="hidden" name="sql" id="qb-sql-executar">
                                    <button type="submit" class="btn btn-primary">Executar Consulta</button>
                                </form>

                                <form method="POST" action="/admin/sql/consulta/salvar" class="d-flex gap-1"
                                      onsubmit="document.getElementById('qb-sql-salvar').value = document.getElementById('qb-sql').value;">
                                    <?= CsrfHelper::field() ?>
                                    <input type="text" name="descricao" class="form-control" placeholder="Descrição da consulta" maxlength="150" required>
                                    <input type="hidden" name="script" id="qb-sql-salvar">
                                    <button type="submit" class="btn btn-secondary">Salvar Script</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <?php if ($resultadoConsulta !== null): ?>
                        <?php renderResultadoSqlConsole($resultadoConsulta, 'consulta'); ?>
                    <?php endif; ?>
                </div>
            </div>

        <?php endif; ?>

    </div>

</div>

<script>
    window.__sqlEsquema = <?= json_encode($esquema, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>
