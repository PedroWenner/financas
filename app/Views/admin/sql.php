<?php
$conectado   = $conectado ?? false;
$conn        = $conn ?? null;
$migrations  = $migrations ?? [];
$historico   = $historico ?? [];
$resultado   = $resultado ?? null;
$erroConexao = $erroConexao ?? null;
$defaults    = $defaults ?? ['host' => '', 'porta' => 3306, 'banco' => '', 'usuario' => ''];
?>

<style>
.sql-console-warn {
    background: #fef3c7; border: 1px solid #f59e0b; color: #92400e;
    border-radius: 8px; padding: .75rem 1rem; font-size: .85rem; margin-bottom: 1.25rem;
}
.sql-conn-banner {
    display: flex; align-items: center; justify-content: space-between; gap: 1rem;
    background: var(--bg-secondary); border-radius: 8px; padding: .75rem 1rem; margin-bottom: 1.25rem;
    font-size: .85rem;
}
.sql-textarea {
    width: 100%; min-height: 160px; font-family: ui-monospace, "SF Mono", Consolas, monospace;
    font-size: .82rem; padding: .75rem; border-radius: 8px; border: 1px solid var(--border-color);
    background: var(--surface, #fff); color: inherit; resize: vertical;
}
.migration-row {
    display: flex; align-items: center; justify-content: space-between; gap: .75rem;
    padding: .6rem .9rem; border-bottom: 1px solid var(--border-color); font-size: .85rem;
}
.migration-row:last-child { border-bottom: none; }
.migration-row code { font-size: .8rem; }
.stmt-block { border: 1px solid var(--border-color); border-radius: 8px; margin-bottom: .9rem; overflow: hidden; }
.stmt-head {
    background: var(--bg-secondary); padding: .5rem .75rem; font-family: ui-monospace, monospace;
    font-size: .78rem; white-space: pre-wrap; word-break: break-word;
}
.stmt-body { padding: .6rem .75rem; font-size: .85rem; }
.stmt-ok  { color: var(--success-color, #16a34a); font-weight: 600; }
.stmt-err { color: var(--danger); font-weight: 600; }
</style>

<div class="sql-console-warn">
    ⚠️ Esse console executa SQL diretamente no banco conectado, sem confirmação além do navegador. Use com cuidado — não há desfazer.
</div>

<?php if ($erroConexao): ?>
    <div class="alert alert-danger mb-3"><?= e($erroConexao) ?></div>
<?php endif; ?>

<?php if (!$conectado): ?>

    <div class="card" style="max-width:520px;">
        <div class="card-header"><span class="card-title">Conectar a um banco</span></div>
        <div class="card-body">
            <form method="POST" action="/admin/sql/conectar" autocomplete="off">
                <?= CsrfHelper::field() ?>

                <div class="d-flex gap-2">
                    <div class="form-group" style="flex:2;">
                        <label class="form-label" for="host">Host</label>
                        <input type="text" id="host" name="host" class="form-control" value="<?= e($defaults['host']) ?>" required>
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label class="form-label" for="porta">Porta</label>
                        <input type="number" id="porta" name="porta" class="form-control" value="<?= (int) $defaults['porta'] ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="banco">Banco</label>
                    <input type="text" id="banco" name="banco" class="form-control" value="<?= e($defaults['banco']) ?>" required>
                </div>

                <div class="d-flex gap-2">
                    <div class="form-group" style="flex:1;">
                        <label class="form-label" for="usuario">Usuário</label>
                        <input type="text" id="usuario" name="usuario" class="form-control" value="<?= e($defaults['usuario']) ?>" autocomplete="off" required>
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label class="form-label" for="senha">Senha</label>
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
                                    <span style="color:var(--text-muted);">0 linha(s).</span>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr><?php foreach ($item['colunas'] as $col): ?><th><?= e((string) $col) ?></th><?php endforeach; ?></tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($item['linhas'] as $linha): ?>
                                                    <tr>
                                                        <?php foreach ($item['colunas'] as $col): ?>
                                                            <td><?= $linha[$col] !== null ? e((string) $linha[$col]) : '<span style="color:var(--text-muted);">NULL</span>' ?></td>
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
    <?php endif; ?>

<?php endif; ?>

<div class="card">
    <div class="card-header"><span class="card-title">Histórico de execuções</span></div>
    <div class="card-body" style="padding:0;">
        <?php if (empty($historico)): ?>
            <div class="empty-state" style="padding:1.5rem;">Nenhuma execução registrada ainda.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
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
                                <td style="color:var(--text-muted);font-size:.85rem;"><?= dataHoraFmt($h['created_at']) ?></td>
                                <td style="font-size:.85rem;"><?= e($h['host']) ?> / <?= e($h['banco']) ?></td>
                                <td class="<?= $h['status'] === 'sucesso' ? 'stmt-ok' : 'stmt-err' ?>"><?= $h['status'] === 'sucesso' ? 'Sucesso' : 'Erro' ?></td>
                                <td style="color:var(--text-muted);font-size:.85rem;"><?= !empty($h['mensagem']) ? e($h['mensagem']) : '—' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
