<?php
$mesAnterior = $mes === 1  ? ['mes' => 12, 'ano' => $ano - 1] : ['mes' => $mes - 1, 'ano' => $ano];
$mesProximo  = $mes === 12 ? ['mes' => 1,  'ano' => $ano + 1] : ['mes' => $mes + 1, 'ano' => $ano];
$ehAtual     = ($mes == date('n') && $ano == date('Y'));
$baseUrl     = '/despesas';
?>

<!-- Cabeçalho -->
<div class="d-flex justify-between align-center mb-3">
    <div class="d-flex align-center gap-1">
        <a href="<?= $baseUrl ?>?mes=<?= $mesAnterior['mes'] ?>&ano=<?= $mesAnterior['ano'] ?>" class="btn btn-secondary btn-sm">‹</a>
        <span style="font-weight:600;min-width:150px;text-align:center;"><?= nomeMesAno($mes, $ano) ?></span>
        <a href="<?= $baseUrl ?>?mes=<?= $mesProximo['mes'] ?>&ano=<?= $mesProximo['ano'] ?>" class="btn btn-secondary btn-sm">›</a>
        <?php if (!$ehAtual): ?>
            <a href="<?= $baseUrl ?>" class="btn btn-outline btn-sm">Hoje</a>
        <?php endif; ?>
    </div>
    <a href="/despesas/criar" class="btn btn-primary">+ Nova Despesa</a>
</div>

<!-- Cards de Resumo -->
<div class="stats-grid mb-3">
    <div class="stat-card despesas">
        <div class="stat-label">💸 Total do Mês</div>
        <div class="stat-value negative"><?= moeda($total) ?></div>
        <div class="stat-meta"><?= $count ?> lançamento<?= $count !== 1 ? 's' : '' ?></div>
    </div>
    <div class="stat-card" style="border-left-color:#d97706;">
        <div class="stat-label">🔁 Fixas</div>
        <div class="stat-value"><?= moeda($porTipo['fixa']) ?></div>
    </div>
    <div class="stat-card" style="border-left-color:#0891b2;">
        <div class="stat-label">🛒 Variáveis</div>
        <div class="stat-value"><?= moeda($porTipo['variavel']) ?></div>
    </div>
    <div class="stat-card" style="border-left-color:#9b59b6;">
        <div class="stat-label">📋 Parceladas</div>
        <div class="stat-value"><?= moeda($porTipo['parcelada']) ?></div>
    </div>
</div>

<!-- Filtros -->
<form method="GET" action="/despesas" class="card mb-3" style="padding:16px;">
    <input type="hidden" name="mes" value="<?= $mes ?>">
    <input type="hidden" name="ano" value="<?= $ano ?>">
    <div class="form-row" style="grid-template-columns:1fr 1fr 1fr 1fr auto;align-items:flex-end;gap:12px;">
        <div class="form-group mb-0">
            <label>Tipo</label>
            <select name="tipo" class="form-control" onchange="this.form.submit()">
                <option value="">Todos</option>
                <option value="fixa"      <?= $tipoFiltro === 'fixa'      ? 'selected' : '' ?>>Fixa</option>
                <option value="variavel"  <?= $tipoFiltro === 'variavel'  ? 'selected' : '' ?>>Variável</option>
                <option value="parcelada" <?= $tipoFiltro === 'parcelada' ? 'selected' : '' ?>>Parcelada</option>
            </select>
        </div>
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
            <label>Cartão</label>
            <select name="cartao" class="form-control" onchange="this.form.submit()">
                <option value="">Todos</option>
                <option value="-1" <?= $cartaoFiltro == -1 ? 'selected' : '' ?>>Nenhum</option>
                <?php foreach ($cartoes as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $cartaoFiltro == $c['id'] ? 'selected' : '' ?>><?= e($c['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group mb-0">
            <button type="submit" class="btn btn-secondary w-100">Filtrar</button>
        </div>
    </div>
</form>

<!-- Tabela -->
<?php if (empty($despesas)): ?>
    <div class="card">
        <div class="card-body empty-state">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/><polyline points="17 18 23 18 23 12"/></svg>
            <h3>Nenhuma despesa neste período</h3>
            <p>Adicione sua primeira despesa do mês.</p>
            <div class="mt-2"><a href="/despesas/criar" class="btn btn-primary">+ Nova Despesa</a></div>
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
                    <th>Pagamento</th>
                    <th>Status</th>
                    <th class="text-right">Valor</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($despesas as $d): ?>
                <tr>
                    <td style="white-space:nowrap;"><?= dataFmt($d['data']) ?></td>
                    <td>
                        <div class="fw-600"><?= e(truncate($d['descricao'], 30)) ?></div>
                        <?php if ($d['cartao_nome']): ?>
                            <div class="fs-sm text-muted">💳 <?= e($d['cartao_nome']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?= badgeTipo($d['tipo']) ?></td>
                    <td>
                        <?php if ($d['categoria_nome']): ?>
                            <div class="d-flex align-center gap-1">
                                <div class="color-dot" style="background:<?= e($d['categoria_cor'] ?: '#6c757d') ?>"></div>
                                <?= e($d['categoria_nome']) ?>
                            </div>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php
                        $pagLabels = ['dinheiro'=>'Dinheiro','debito'=>'Débito','credito'=>'Crédito','pix'=>'PIX','ted'=>'TED','boleto'=>'Boleto','outro'=>'Outro'];
                        echo e($pagLabels[$d['forma_pagamento']] ?? $d['forma_pagamento']);
                        ?>
                    </td>
                    <td>
                        <?= badgePago((bool)$d['pago']) ?>
                    </td>
                    <td class="text-right fw-700 text-danger"><?= moeda($d['valor']) ?></td>
                    <td>
                        <div class="td-actions">
                            <!-- Marcar como pago/não pago -->
                            <form method="POST" action="/despesas/pagar" style="display:inline;">
                                <?= CsrfHelper::field() ?>
                                <input type="hidden" name="id" value="<?= $d['id'] ?>">
                                <input type="hidden" name="pago" value="<?= $d['pago'] ? 0 : 1 ?>">
                                <input type="hidden" name="mes" value="<?= $mes ?>">
                                <input type="hidden" name="ano" value="<?= $ano ?>">
                                <?php if ($catFiltro):    ?><input type="hidden" name="cat"    value="<?= $catFiltro ?>">   <?php endif; ?>
                                <?php if ($tipoFiltro):   ?><input type="hidden" name="tipo"   value="<?= e($tipoFiltro) ?>"><?php endif; ?>
                                <?php if ($cartaoFiltro): ?><input type="hidden" name="cartao" value="<?= $cartaoFiltro ?>"><?php endif; ?>
                                <button type="submit" class="btn btn-sm <?= $d['pago'] ? 'btn-warning' : 'btn-success' ?>" title="<?= $d['pago'] ? 'Marcar como pendente' : 'Marcar como pago' ?>">
                                    <?= $d['pago'] ? '↩️' : '✅' ?>
                                </button>
                            </form>
                            <?php if ($d['tipo'] !== 'fixa'): ?>
                                <a href="/despesas/editar?id=<?= $d['id'] ?>" class="btn btn-secondary btn-sm">✏️</a>
                            <?php endif; ?>
                            <?php if ($d['tipo'] !== 'parcelada'): ?>
                                <form method="POST" action="/despesas/excluir" style="display:inline;">
                                    <?= CsrfHelper::field() ?>
                                    <input type="hidden" name="id" value="<?= $d['id'] ?>">
                                    <input type="hidden" name="mes" value="<?= $mes ?>">
                                    <input type="hidden" name="ano" value="<?= $ano ?>">
                                    <?php if ($catFiltro):    ?><input type="hidden" name="cat"    value="<?= $catFiltro ?>">   <?php endif; ?>
                                    <?php if ($tipoFiltro):   ?><input type="hidden" name="tipo"   value="<?= e($tipoFiltro) ?>"><?php endif; ?>
                                    <?php if ($cartaoFiltro): ?><input type="hidden" name="cartao" value="<?= $cartaoFiltro ?>"><?php endif; ?>
                                    <button type="submit" class="btn btn-danger btn-sm"
                                            data-confirm="Excluir a despesa '<?= addslashes(e($d['descricao'])) ?>'?">🗑️</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="6" class="fw-600 text-right" style="padding:12px 16px;">Total:</td>
                    <td class="fw-700 text-danger text-right" style="padding:12px 16px;"><?= moeda($total) ?></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
<?php endif; ?>
