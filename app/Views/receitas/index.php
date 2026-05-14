<?php
$mesAnterior = $mes === 1  ? ['mes' => 12, 'ano' => $ano - 1] : ['mes' => $mes - 1, 'ano' => $ano];
$mesProximo  = $mes === 12 ? ['mes' => 1,  'ano' => $ano + 1] : ['mes' => $mes + 1, 'ano' => $ano];
$ehAtual     = ($mes == date('n') && $ano == date('Y'));
$baseUrl     = '/receitas';
?>

<!-- Cabeçalho + Ações -->
<div class="d-flex justify-between align-center mb-3">
    <!-- Navegação de Mês -->
    <div class="d-flex align-center gap-1">
        <a href="<?= $baseUrl ?>?mes=<?= $mesAnterior['mes'] ?>&ano=<?= $mesAnterior['ano'] ?>" class="btn btn-secondary btn-sm">‹</a>
        <span style="font-weight:600;min-width:150px;text-align:center;"><?= nomeMesAno($mes, $ano) ?></span>
        <?php if (!$ehAtual): ?>
            <a href="<?= $baseUrl ?>?mes=<?= $mesProximo['mes'] ?>&ano=<?= $mesProximo['ano'] ?>" class="btn btn-secondary btn-sm">›</a>
        <?php else: ?>
            <span class="btn btn-secondary btn-sm" style="opacity:.3;cursor:default;">›</span>
        <?php endif; ?>
        <?php if (!$ehAtual): ?>
            <a href="<?= $baseUrl ?>" class="btn btn-outline btn-sm">Hoje</a>
        <?php endif; ?>
    </div>
    <a href="/receitas/criar" class="btn btn-primary">+ Nova Receita</a>
</div>

<!-- Card Total do Mês -->
<div class="stats-grid mb-3" style="grid-template-columns: repeat(auto-fit, minmax(180px,1fr));">
    <div class="stat-card receitas">
        <div class="stat-label">Total de Receitas</div>
        <div class="stat-value positive"><?= moeda($total) ?></div>
        <div class="stat-meta"><?= $count ?> lançamento<?= $count !== 1 ? 's' : '' ?></div>
    </div>
</div>

<!-- Filtros -->
<form method="GET" action="/receitas" class="card mb-3" style="padding:16px;">
    <input type="hidden" name="mes" value="<?= $mes ?>">
    <input type="hidden" name="ano" value="<?= $ano ?>">
    <div class="form-row cols-3" style="align-items:flex-end;gap:12px;">
        <div class="form-group mb-0">
            <label>Categoria</label>
            <select name="cat" class="form-control" onchange="this.form.submit()">
                <option value="">Todas</option>
                <?php foreach ($categorias as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $catFiltro == $c['id'] ? 'selected' : '' ?>><?= e($c['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group mb-0">
            <label>Tipo</label>
            <select name="tipo" class="form-control" onchange="this.form.submit()">
                <option value="">Todos</option>
                <option value="salario"         <?= $tipoFiltro === 'salario'         ? 'selected' : '' ?>>Salário</option>
                <option value="salario_conjuge" <?= $tipoFiltro === 'salario_conjuge' ? 'selected' : '' ?>>Salário do Cônjuge</option>
                <option value="renda_extra"     <?= $tipoFiltro === 'renda_extra'     ? 'selected' : '' ?>>Renda Extra</option>
                <option value="investimento"    <?= $tipoFiltro === 'investimento'    ? 'selected' : '' ?>>Investimento</option>
                <option value="outro"           <?= $tipoFiltro === 'outro'           ? 'selected' : '' ?>>Outro</option>
            </select>
        </div>
        <div class="form-group mb-0">
            <button type="submit" class="btn btn-secondary w-100">Filtrar</button>
        </div>
    </div>
</form>

<!-- Tabela -->
<?php if (empty($receitas)): ?>
    <div class="card">
        <div class="card-body empty-state">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
            <h3>Nenhuma receita neste período</h3>
            <p>Adicione sua primeira receita do mês.</p>
            <div class="mt-2"><a href="/receitas/criar" class="btn btn-primary">+ Nova Receita</a></div>
        </div>
    </div>
<?php else: ?>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Descrição</th>
                    <th>Tipo</th>
                    <th>Categoria</th>
                    <th class="text-right">Valor</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($receitas as $r): ?>
                <tr>
                    <td style="white-space:nowrap;"><?= dataFmt($r['data']) ?></td>
                    <td>
                        <div class="fw-600"><?= e(truncate($r['descricao'], 35)) ?></div>
                        <?php if ($r['observacoes']): ?>
                            <div class="fs-sm text-muted"><?= e(truncate($r['observacoes'], 40)) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge badge-success"><?= tipoReceitaLabel($r['tipo_receita']) ?></span></td>
                    <td>
                        <?php if ($r['categoria_nome']): ?>
                            <div class="d-flex align-center gap-1">
                                <div class="color-dot" style="background:<?= e($r['categoria_cor'] ?: '#6c757d') ?>"></div>
                                <?= e($r['categoria_nome']) ?>
                            </div>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-right fw-700 text-success"><?= moeda($r['valor']) ?></td>
                    <td>
                        <div class="td-actions">
                            <a href="/receitas/editar?id=<?= $r['id'] ?>" class="btn btn-secondary btn-sm">✏️</a>
                            <form method="POST" action="/receitas/excluir" style="display:inline;">
                                <?= CsrfHelper::field() ?>
                                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm"
                                        data-confirm="Excluir a receita '<?= addslashes(e($r['descricao'])) ?>'?">🗑️</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="fw-600 text-right" style="padding:12px 16px;">Total:</td>
                    <td class="fw-700 text-success text-right" style="padding:12px 16px;"><?= moeda($total) ?></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
<?php endif; ?>
