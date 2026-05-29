<?php
$mesesNomes = [
    1=>'Janeiro',2=>'Fevereiro',3=>'Março',4=>'Abril',
    5=>'Maio',6=>'Junho',7=>'Julho',8=>'Agosto',
    9=>'Setembro',10=>'Outubro',11=>'Novembro',12=>'Dezembro',
];
$anosDisponiveis = range(date('Y') - 5, date('Y') + 1);
?>

<!-- ── Painel de Filtros ── -->
<form method="GET" action="/relatorios" id="form-relatorio">

    <!-- Seletor de Modo -->
    <div class="card mb-2">
        <div class="card-body" style="padding:1rem 1.25rem;">
            <div class="d-flex align-center gap-1 flex-wrap" style="gap:.75rem;">

                <!-- Modo -->
                <div class="d-flex align-center gap-1" style="flex-shrink:0;">
                    <span class="fs-sm fw-600 text-muted" style="margin-right:.25rem;">Período:</span>
                    <?php foreach (['anual' => 'Anual', 'mensal' => 'Mensal', 'periodo' => 'Intervalo'] as $val => $label): ?>
                        <button type="button"
                            class="btn btn-sm <?= $modo === $val ? 'btn-primary' : 'btn-secondary' ?>"
                            onclick="setModo('<?= $val ?>')"
                        ><?= $label ?></button>
                    <?php endforeach; ?>
                    <input type="hidden" name="modo" id="inp-modo" value="<?= e($modo) ?>">
                </div>

                <!-- Anual -->
                <div id="filtro-anual" class="d-flex align-center gap-1" style="<?= $modo !== 'anual' ? 'display:none!important' : '' ?>">
                    <label class="fs-sm fw-600 text-muted">Ano:</label>
                    <select name="ano" class="form-select" style="width:auto;">
                        <?php foreach ($anosDisponiveis as $a): ?>
                            <option value="<?= $a ?>" <?= $a == $anoIni ? 'selected' : '' ?>><?= $a ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Mensal -->
                <div id="filtro-mensal" class="d-flex align-center gap-1" style="<?= $modo !== 'mensal' ? 'display:none!important' : '' ?>">
                    <label class="fs-sm fw-600 text-muted">Mês:</label>
                    <select name="mes" class="form-select" style="width:auto;">
                        <?php foreach ($mesesNomes as $n => $nome): ?>
                            <option value="<?= $n ?>" <?= $n == $mesIni ? 'selected' : '' ?>><?= $nome ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="ano" class="form-select" style="width:auto;">
                        <?php foreach ($anosDisponiveis as $a): ?>
                            <option value="<?= $a ?>" <?= $a == $anoIni ? 'selected' : '' ?>><?= $a ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Período -->
                <div id="filtro-periodo" class="d-flex align-center gap-1 flex-wrap" style="<?= $modo !== 'periodo' ? 'display:none!important' : '' ?>">
                    <label class="fs-sm fw-600 text-muted">De:</label>
                    <select name="mes_inicio" class="form-select" style="width:auto;">
                        <?php foreach ($mesesNomes as $n => $nome): ?>
                            <option value="<?= $n ?>" <?= $n == $mesIni ? 'selected' : '' ?>><?= $nome ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="ano_inicio" class="form-select" style="width:auto;">
                        <?php foreach ($anosDisponiveis as $a): ?>
                            <option value="<?= $a ?>" <?= $a == $anoIni ? 'selected' : '' ?>><?= $a ?></option>
                        <?php endforeach; ?>
                    </select>
                    <span class="fs-sm text-muted">até</span>
                    <select name="mes_fim" class="form-select" style="width:auto;">
                        <?php foreach ($mesesNomes as $n => $nome): ?>
                            <option value="<?= $n ?>" <?= $n == $mesFim ? 'selected' : '' ?>><?= $nome ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="ano_fim" class="form-select" style="width:auto;">
                        <?php foreach ($anosDisponiveis as $a): ?>
                            <option value="<?= $a ?>" <?= $a == $anoFim ? 'selected' : '' ?>><?= $a ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="flex:1;min-width:0;"></div>
                <button type="submit" class="btn btn-primary btn-sm" style="flex-shrink:0;">Aplicar</button>
            </div>

            <!-- Segunda linha: tipo e cartão -->
            <div class="d-flex align-center flex-wrap gap-2 mt-2" style="gap:.5rem 1.5rem;border-top:1px solid var(--border);padding-top:.75rem;margin-top:.75rem;">

                <!-- Tipos -->
                <div class="d-flex align-center gap-1 flex-wrap">
                    <span class="fs-sm fw-600 text-muted" style="margin-right:.25rem;">Tipos:</span>
                    <?php foreach (['fixa' => 'Fixas', 'variavel' => 'Variáveis', 'parcelada' => 'Parceladas'] as $val => $label): ?>
                        <label class="d-flex align-center gap-1 fs-sm" style="cursor:pointer;">
                            <input type="checkbox" name="tipos[]" value="<?= $val ?>"
                                <?= in_array($val, $tipos) ? 'checked' : '' ?>>
                            <?= $label ?>
                        </label>
                    <?php endforeach; ?>
                </div>

                <!-- Cartão -->
                <div class="d-flex align-center gap-1 flex-wrap">
                    <span class="fs-sm fw-600 text-muted" style="margin-right:.25rem;">Cartão:</span>
                    <?php foreach (['todos' => 'Todos', 'apenas_cartao' => 'Apenas cartão', 'sem_cartao' => 'Excluir cartão'] as $val => $label): ?>
                        <label class="d-flex align-center gap-1 fs-sm" style="cursor:pointer;">
                            <input type="radio" name="cartao" value="<?= $val ?>"
                                <?= $modoCartao === $val ? 'checked' : '' ?>>
                            <?= $label ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</form>

<?php
// Describe current period for display
if ($modo === 'anual') {
    $periodoLabel = "Ano de {$anoIni}";
} elseif ($modo === 'mensal') {
    $periodoLabel = nomeMesAno($mesIni, $anoIni);
} else {
    $periodoLabel = nomeMesAno($mesIni, $anoIni) . ' – ' . nomeMesAno($mesFim, $anoFim);
}
?>

<!-- ── Cards de Resumo ── -->
<div class="stats-grid mb-2">
    <div class="stat-card receitas">
        <div class="stat-label">💰 Receitas</div>
        <div class="stat-value positive"><?= moeda($totalReceitas) ?></div>
        <div class="stat-meta"><?= e($periodoLabel) ?></div>
    </div>

    <div class="stat-card despesas">
        <div class="stat-label">💸 Despesas</div>
        <div class="stat-value negative"><?= moeda($totalDespesas) ?></div>
        <div class="stat-meta"><?= e($periodoLabel) ?></div>
    </div>

    <div class="stat-card saldo">
        <div class="stat-label">📊 Saldo</div>
        <div class="stat-value <?= $saldo >= 0 ? 'positive' : 'negative' ?>">
            <?= moeda($saldo) ?>
        </div>
        <div class="stat-meta"><?= $saldo >= 0 ? 'Positivo' : 'Negativo' ?></div>
    </div>

    <?php
    $economia = ($totalReceitas > 0) ? ($saldo / $totalReceitas) * 100 : 0;
    ?>
    <div class="stat-card">
        <div class="stat-label">📈 Taxa de Economia</div>
        <div class="stat-value <?= $economia >= 0 ? 'positive' : 'negative' ?>">
            <?= number_format($economia, 1, ',', '.') ?>%
        </div>
        <div class="stat-meta">das receitas poupadas</div>
    </div>
</div>

<!-- ── Gráfico Principal: Receitas x Despesas ── -->
<div class="card mb-2">
    <div class="card-header">
        <span class="card-title">Receitas × Despesas por Mês</span>
        <span class="fs-sm text-muted"><?= e($periodoLabel) ?></span>
    </div>
    <div class="card-body">
        <?php if (empty($series)): ?>
            <div class="empty-state"><p>Nenhum dado para o período selecionado.</p></div>
        <?php else: ?>
            <div class="chart-container" style="height:300px;">
                <canvas id="chart-comparativo"></canvas>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ── Gráficos Secundários ── -->
<div class="dashboard-grid mb-2">

    <!-- Despesas por Categoria -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">Despesas por Categoria</span>
        </div>
        <div class="card-body">
            <?php if (empty($porCategoria)): ?>
                <div class="empty-state"><p>Nenhuma despesa no período.</p></div>
            <?php else: ?>
                <div class="chart-container">
                    <canvas id="chart-categorias"></canvas>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Despesas por Tipo -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">Despesas por Tipo</span>
        </div>
        <div class="card-body">
            <?php
            $totalTipo = $porTipo['fixa'] + $porTipo['variavel'] + $porTipo['parcelada'];
            ?>
            <?php if ($totalTipo == 0): ?>
                <div class="empty-state"><p>Nenhuma despesa no período.</p></div>
            <?php else: ?>
                <div class="chart-container">
                    <canvas id="chart-tipos"></canvas>
                </div>
                <div class="mt-2" style="display:flex;flex-direction:column;gap:.4rem;">
                    <?php foreach (['fixa' => ['Fixas','#f59e0b'], 'variavel' => ['Variáveis','#3b82f6'], 'parcelada' => ['Parceladas','#8b5cf6']] as $t => [$label, $cor]): ?>
                        <?php if ($porTipo[$t] > 0): ?>
                        <div class="d-flex justify-between align-center">
                            <div class="d-flex align-center gap-1">
                                <div class="color-dot" style="background:<?= $cor ?>"></div>
                                <span class="fs-sm"><?= $label ?></span>
                            </div>
                            <div class="text-right">
                                <span class="fs-sm fw-600"><?= moeda($porTipo[$t]) ?></span>
                                <span class="fs-sm text-muted" style="margin-left:.5rem;">
                                    <?= number_format($porTipo[$t] / $totalTipo * 100, 1, ',', '.') ?>%
                                </span>
                            </div>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ── Tabela Mensal ── -->
<?php if (count($series) > 1): ?>
<div class="card mb-2">
    <div class="card-header">
        <span class="card-title">Evolução Mensal</span>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Mês</th>
                    <th class="text-right">Receitas</th>
                    <th class="text-right">Despesas</th>
                    <th class="text-right">Saldo</th>
                    <th class="text-right">Economia</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($series as $row): ?>
                <?php $eco = $row['receitas'] > 0 ? ($row['saldo'] / $row['receitas']) * 100 : 0; ?>
                <tr>
                    <td class="fw-600"><?= e($row['label']) ?></td>
                    <td class="text-right text-success fw-600"><?= moeda($row['receitas']) ?></td>
                    <td class="text-right text-danger fw-600"><?= moeda($row['despesas']) ?></td>
                    <td class="text-right fw-600 <?= $row['saldo'] >= 0 ? 'text-success' : 'text-danger' ?>">
                        <?= moeda($row['saldo']) ?>
                    </td>
                    <td class="text-right fs-sm <?= $eco >= 0 ? 'text-success' : 'text-danger' ?>">
                        <?= number_format($eco, 1, ',', '.') ?>%
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="font-weight:700;border-top:2px solid var(--border);">
                    <td>Total</td>
                    <td class="text-right text-success"><?= moeda($totalReceitas) ?></td>
                    <td class="text-right text-danger"><?= moeda($totalDespesas) ?></td>
                    <td class="text-right <?= $saldo >= 0 ? 'text-success' : 'text-danger' ?>"><?= moeda($saldo) ?></td>
                    <td class="text-right fs-sm <?= $economia >= 0 ? 'text-success' : 'text-danger' ?>">
                        <?= number_format($economia, 1, ',', '.') ?>%
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- ── Tabela de Despesas por Categoria ── -->
<?php if (!empty($porCategoria)): ?>
<div class="card mb-2">
    <div class="card-header">
        <span class="card-title">Detalhamento por Categoria</span>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Categoria</th>
                    <th class="text-right">Total</th>
                    <th class="text-right">% do Total</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($porCategoria as $cat): ?>
                <tr>
                    <td>
                        <div class="d-flex align-center gap-1">
                            <div class="color-dot" style="background:<?= e($cat['cor']) ?>"></div>
                            <?= e($cat['nome']) ?>
                        </div>
                    </td>
                    <td class="text-right fw-600"><?= moeda($cat['total']) ?></td>
                    <td class="text-right fs-sm text-muted">
                        <?= $totalDespesas > 0 ? number_format($cat['total'] / $totalDespesas * 100, 1, ',', '.') . '%' : '—' ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- ── Chart.js e Scripts ── -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const isDark    = () => document.documentElement.getAttribute('data-theme') === 'dark';
const gridColor = () => isDark() ? 'rgba(255,255,255,.08)' : 'rgba(0,0,0,.06)';
const textColor = () => isDark() ? '#94a3b8' : '#64748b';
const brlFmt    = v => 'R$ ' + parseFloat(v).toLocaleString('pt-BR', {minimumFractionDigits:2});

// ── Comparativo Receitas × Despesas ──
<?php if (!empty($series)): ?>
(function(){
    const ctx = document.getElementById('chart-comparativo')?.getContext('2d');
    if (!ctx) return;
    const data = <?= json_encode($series) ?>;
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.map(d => d.label),
            datasets: [
                {
                    label: 'Receitas',
                    data: data.map(d => d.receitas),
                    backgroundColor: 'rgba(22,163,74,.75)',
                    borderRadius: 4,
                    order: 2,
                },
                {
                    label: 'Despesas',
                    data: data.map(d => d.despesas),
                    backgroundColor: 'rgba(220,38,38,.75)',
                    borderRadius: 4,
                    order: 2,
                },
                {
                    label: 'Saldo',
                    data: data.map(d => d.saldo),
                    type: 'line',
                    borderColor: 'rgba(59,130,246,1)',
                    backgroundColor: 'rgba(59,130,246,.15)',
                    pointBackgroundColor: 'rgba(59,130,246,1)',
                    borderWidth: 2,
                    pointRadius: 4,
                    fill: false,
                    tension: 0.3,
                    order: 1,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { labels: { color: textColor(), font: { size: 12 } } },
                tooltip: {
                    callbacks: { label: ctx => ' ' + ctx.dataset.label + ': ' + brlFmt(ctx.parsed.y) }
                }
            },
            scales: {
                x: { ticks: { color: textColor() }, grid: { color: gridColor() } },
                y: {
                    beginAtZero: true,
                    ticks: { color: textColor(), callback: v => 'R$ ' + v.toLocaleString('pt-BR') },
                    grid: { color: gridColor() }
                }
            }
        }
    });
})();
<?php endif; ?>

// ── Despesas por Categoria (Doughnut) ──
<?php if (!empty($porCategoria)): ?>
(function(){
    const ctx = document.getElementById('chart-categorias')?.getContext('2d');
    if (!ctx) return;
    const labels = <?= json_encode(array_column($porCategoria, 'nome')) ?>;
    const values = <?= json_encode(array_column($porCategoria, 'total')) ?>;
    const colors = <?= json_encode(array_map(fn($c) => $c['cor'] ?: '#6c757d', $porCategoria)) ?>;
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{
                data: values,
                backgroundColor: colors,
                borderWidth: 3,
                borderColor: isDark() ? '#1e293b' : '#ffffff',
                hoverOffset: 8,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'right', labels: { color: textColor(), padding: 12, boxWidth: 12, font: { size: 11 } } },
                tooltip: { callbacks: { label: ctx => ' ' + ctx.label + ': ' + brlFmt(ctx.parsed) } }
            }
        }
    });
})();
<?php endif; ?>

// ── Despesas por Tipo (Doughnut) ──
<?php if (($porTipo['fixa'] + $porTipo['variavel'] + $porTipo['parcelada']) > 0): ?>
(function(){
    const ctx = document.getElementById('chart-tipos')?.getContext('2d');
    if (!ctx) return;
    const tipo = <?= json_encode($porTipo) ?>;
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Fixas', 'Variáveis', 'Parceladas'],
            datasets: [{
                data: [tipo.fixa, tipo.variavel, tipo.parcelada],
                backgroundColor: ['#f59e0b', '#3b82f6', '#8b5cf6'],
                borderWidth: 3,
                borderColor: isDark() ? '#1e293b' : '#ffffff',
                hoverOffset: 8,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'right', labels: { color: textColor(), padding: 12, boxWidth: 12, font: { size: 11 } } },
                tooltip: { callbacks: { label: ctx => ' ' + ctx.label + ': ' + brlFmt(ctx.parsed) } }
            }
        }
    });
})();
<?php endif; ?>

// ── Filtro: troca de modo ──
function setModo(modo) {
    document.getElementById('inp-modo').value = modo;
    ['anual','mensal','periodo'].forEach(m => {
        document.getElementById('filtro-' + m).style.display = m === modo ? '' : 'none';
    });
    document.querySelectorAll('[onclick^="setModo"]').forEach(btn => {
        const bModo = btn.getAttribute('onclick').replace(/[^']+'/,'').replace(/'.*/,'');
        btn.className = btn.className.replace(/btn-primary|btn-secondary/g,'');
        btn.className += ' ' + (bModo === modo ? 'btn-primary' : 'btn-secondary');
    });
}
</script>
