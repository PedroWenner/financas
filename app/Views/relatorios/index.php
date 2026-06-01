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
                <button type="button" id="btn-pdf" onclick="gerarPdf()" class="btn btn-secondary btn-sm" style="flex-shrink:0;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Gerar PDF
                </button>
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

<div id="pdf-content">

<!-- Cabeçalho do PDF (oculto na tela, visível apenas no PDF) -->
<div id="pdf-header" style="display:none;margin-bottom:20px;padding-bottom:12px;border-bottom:2px solid #2563eb;">
    <div style="display:flex;align-items:center;justify-content:space-between;">
        <div>
            <div style="font-size:1.3rem;font-weight:700;color:#1e293b;">AlcatechFinanças</div>
            <div style="font-size:.85rem;color:#64748b;">Relatório Financeiro — <?= e($periodoLabel) ?></div>
        </div>
        <div style="font-size:.78rem;color:#94a3b8;text-align:right;">
            Gerado em <?= date('d/m/Y H:i') ?>
        </div>
    </div>
</div>

<!-- ── Cards de Resumo ── -->
<div class="stats-grid mb-2">
    <div class="stat-card receitas stat-card-clicavel" onclick="abrirModalItens('receitas')" title="Ver receitas do período">
        <div class="stat-label">💰 Receitas</div>
        <div class="stat-value positive"><?= moeda($totalReceitas) ?></div>
        <div class="stat-meta"><?= e($periodoLabel) ?></div>
    </div>

    <div class="stat-card despesas stat-card-clicavel" onclick="abrirModalItens('despesas')" title="Ver despesas do período">
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

<?php if ($porTipo['fixa'] + $porTipo['variavel'] + $porTipo['parcelada'] > 0): ?>
<div class="stats-grid mb-2">
    <div class="stat-card stat-card-clicavel" style="border-left-color:#f59e0b;" onclick="abrirModalItens('fixa')" title="Ver despesas fixas">
        <div class="stat-label">🔁 Fixas</div>
        <div class="stat-value"><?= moeda($porTipo['fixa']) ?></div>
        <div class="stat-meta"><?= $totalDespesas > 0 ? number_format($porTipo['fixa'] / $totalDespesas * 100, 1, ',', '.') . '% das despesas' : '—' ?></div>
    </div>
    <div class="stat-card stat-card-clicavel" style="border-left-color:#3b82f6;" onclick="abrirModalItens('variavel')" title="Ver despesas variáveis">
        <div class="stat-label">🛒 Variáveis</div>
        <div class="stat-value"><?= moeda($porTipo['variavel']) ?></div>
        <div class="stat-meta"><?= $totalDespesas > 0 ? number_format($porTipo['variavel'] / $totalDespesas * 100, 1, ',', '.') . '% das despesas' : '—' ?></div>
    </div>
    <div class="stat-card stat-card-clicavel" style="border-left-color:#8b5cf6;" onclick="abrirModalItens('parcelada')" title="Ver despesas parceladas">
        <div class="stat-label">📋 Parceladas</div>
        <div class="stat-value"><?= moeda($porTipo['parcelada']) ?></div>
        <div class="stat-meta"><?= $totalDespesas > 0 ? number_format($porTipo['parcelada'] / $totalDespesas * 100, 1, ',', '.') . '% das despesas' : '—' ?></div>
    </div>
    <?php if (!empty($porCartaoFiltrado)): ?>
    <div class="stat-card" style="border-left-color:#0891b2;">
        <div class="stat-label">💳 Total Cartões</div>
        <div class="stat-value"><?= moeda(array_sum(array_column($porCartaoFiltrado, 'total'))) ?></div>
        <div class="stat-meta"><?= count($porCartaoFiltrado) ?> cartão<?= count($porCartaoFiltrado) !== 1 ? 'ões' : '' ?> no período</div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

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
            <tfoot>
                <tr style="font-weight:700;border-top:2px solid var(--border);">
                    <td><?= count($porCategoria) ?> categori<?= count($porCategoria) !== 1 ? 'as' : 'a' ?></td>
                    <td class="text-right text-danger"><?= moeda($totalDespesas) ?></td>
                    <td class="text-right">100%</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- ── Tabela de Despesas por Cartão ── -->
<?php
$porCartaoFiltrado = array_filter($porCartao, fn($r) => $r['nome'] !== 'Sem cartão');
if (!empty($porCartaoFiltrado)):
?>
<div class="card mb-2">
    <div class="card-header">
        <span class="card-title">Gastos por Cartão</span>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Cartão</th>
                    <th class="text-right">Total</th>
                    <th class="text-right">% do Total</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($porCartaoFiltrado as $row): ?>
                <tr>
                    <td>
                        <div class="d-flex align-center gap-1">
                            <div class="color-dot" style="background:<?= e($row['cor']) ?>"></div>
                            <?= e($row['nome']) ?>
                        </div>
                    </td>
                    <td class="text-right fw-600"><?= moeda($row['total']) ?></td>
                    <td class="text-right fs-sm text-muted">
                        <?= $totalDespesas > 0 ? number_format($row['total'] / $totalDespesas * 100, 1, ',', '.') . '%' : '—' ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <?php $totalCartoes = array_sum(array_column($porCartaoFiltrado, 'total')); ?>
            <tfoot>
                <tr style="font-weight:700;border-top:2px solid var(--border);">
                    <td><?= count($porCartaoFiltrado) ?> cartão<?= count($porCartaoFiltrado) !== 1 ? 'ões' : '' ?></td>
                    <td class="text-right text-danger"><?= moeda($totalCartoes) ?></td>
                    <td class="text-right"><?= $totalDespesas > 0 ? number_format($totalCartoes / $totalDespesas * 100, 1, ',', '.') . '%' : '—' ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<?php endif; ?>

</div><!-- /#pdf-content -->

<!-- ── Modal: Itens do Card ── -->
<div class="modal-overlay" id="modal-itens-card">
    <div class="modal" style="max-width:680px;width:100%;">
        <div class="modal-header">
            <span class="modal-title" id="modal-itens-titulo">Itens</span>
            <button class="modal-close" type="button" onclick="closeModal('modal-itens-card')">×</button>
        </div>
        <div class="modal-body" style="padding:0;max-height:520px;overflow-y:auto;">
            <div id="modal-itens-vazio" class="empty-state" style="display:none;padding:2rem;">
                <p>Nenhum item encontrado para este período.</p>
            </div>
            <table id="modal-itens-table" style="width:100%;border-collapse:collapse;">
                <thead id="modal-itens-thead" style="position:sticky;top:0;background:var(--surface);z-index:1;border-bottom:1px solid var(--border);"></thead>
                <tbody id="modal-itens-tbody"></tbody>
            </table>
        </div>
        <div class="modal-footer" style="justify-content:space-between;">
            <span id="modal-itens-rodape" class="fs-sm text-muted"></span>
            <button type="button" class="btn btn-secondary" onclick="closeModal('modal-itens-card')">Fechar</button>
        </div>
    </div>
</div>

<!-- ── Chart.js e Scripts ── -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/html2pdf.js@0.10.1/dist/html2pdf.bundle.min.js"></script>
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

// ── Gerar PDF ──
function gerarPdf() {
    const btn    = document.getElementById('btn-pdf');
    const header = document.getElementById('pdf-header');
    const svgDown = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>';

    btn.disabled = true;
    btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> Gerando...';
    header.style.display = 'block';

    // Converte cada <canvas> em <img> para garantir captura correta pelo html2canvas
    const snapshots = [];
    document.querySelectorAll('#pdf-content canvas').forEach(canvas => {
        const img = document.createElement('img');
        img.src = canvas.toDataURL('image/png');
        img.style.width  = canvas.offsetWidth  + 'px';
        img.style.height = canvas.offsetHeight + 'px';
        img.style.display = 'block';
        canvas.parentNode.insertBefore(img, canvas);
        canvas.style.display = 'none';
        snapshots.push({ canvas, img });
    });

    const periodo = <?= json_encode($periodoLabel) ?>;
    const filename = 'relatorio-' + periodo.toLowerCase().replace(/[^a-z0-9]+/g, '-') + '.pdf';

    html2pdf()
        .set({
            margin: [8, 8, 8, 8],
            filename,
            image: { type: 'jpeg', quality: 0.96 },
            html2canvas: {
                scale: 1.5,
                useCORS: true,
                backgroundColor: '#ffffff',
                logging: false,
                windowWidth: 1100,
            },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' },
            pagebreak: { mode: 'css', avoid: '.card' },
        })
        .from(document.getElementById('pdf-content'))
        .save()
        .then(() => {
            // Restaura os canvases
            snapshots.forEach(({ canvas, img }) => {
                canvas.style.display = '';
                img.remove();
            });
            header.style.display = 'none';
            btn.disabled = false;
            btn.innerHTML = svgDown + ' Gerar PDF';
        });
}

// ── Modal: Itens do Card ──
(function() {
    const _listaReceitas = <?= json_encode($listaReceitas, JSON_UNESCAPED_UNICODE) ?>;
    const _listaDespesas = <?= json_encode($listaDespesas, JSON_UNESCAPED_UNICODE) ?>;
    const _tiposFiltro   = <?= json_encode($tipos) ?>;

    const _titulos = {
        receitas:  '💰 Receitas do período',
        despesas:  '💸 Despesas do período',
        fixa:      '🔁 Despesas Fixas',
        variavel:  '🛒 Despesas Variáveis',
        parcelada: '📋 Despesas Parceladas',
    };

    const brl     = v => 'R$ ' + parseFloat(v).toLocaleString('pt-BR', { minimumFractionDigits: 2 });
    const fmtDate = d => { const p = String(d).split('-'); return p[2] + '/' + p[1] + '/' + p[0]; };
    const esc     = s => String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');

    window.abrirModalItens = function(tipo) {
        const titulo  = document.getElementById('modal-itens-titulo');
        const thead   = document.getElementById('modal-itens-thead');
        const tbody   = document.getElementById('modal-itens-tbody');
        const vazio   = document.getElementById('modal-itens-vazio');
        const table   = document.getElementById('modal-itens-table');
        const rodape  = document.getElementById('modal-itens-rodape');

        titulo.textContent = _titulos[tipo] || tipo;

        let itens;
        if (tipo === 'receitas') {
            itens = _listaReceitas;
        } else if (tipo === 'despesas') {
            itens = _listaDespesas.filter(d => _tiposFiltro.includes(d.tipo));
        } else {
            itens = _listaDespesas.filter(d => d.tipo === tipo);
        }

        if (itens.length === 0) {
            table.style.display = 'none';
            vazio.style.display = '';
            rodape.textContent  = '';
        } else {
            table.style.display = '';
            vazio.style.display = 'none';

            const th = `<tr>
                <th style="padding:10px 16px;text-align:left;font-weight:600;font-size:.8rem;color:var(--text-muted);">Data</th>
                <th style="padding:10px 16px;text-align:left;font-weight:600;font-size:.8rem;color:var(--text-muted);">Descrição</th>
                <th style="padding:10px 16px;text-align:left;font-weight:600;font-size:.8rem;color:var(--text-muted);">Categoria</th>
                <th style="padding:10px 16px;text-align:right;font-weight:600;font-size:.8rem;color:var(--text-muted);">Valor</th>
            </tr>`;
            thead.innerHTML = th;

            tbody.innerHTML = itens.map((item, i) => {
                const bg = i % 2 === 0 ? '' : 'background:var(--surface-2);';
                return `<tr style="${bg}">
                    <td style="padding:9px 16px;white-space:nowrap;font-size:.85rem;">${fmtDate(item.data)}</td>
                    <td style="padding:9px 16px;font-size:.85rem;">${esc(item.descricao) || '<span style="color:var(--text-muted)">—</span>'}</td>
                    <td style="padding:9px 16px;font-size:.85rem;">
                        <div style="display:flex;align-items:center;gap:6px;">
                            <div style="width:8px;height:8px;border-radius:50%;background:${esc(item.cor_categoria)};flex-shrink:0;"></div>
                            ${esc(item.categoria)}
                        </div>
                    </td>
                    <td style="padding:9px 16px;text-align:right;font-weight:600;font-size:.85rem;">${brl(item.valor)}</td>
                </tr>`;
            }).join('');

            const total = itens.reduce((s, i) => s + parseFloat(i.valor), 0);
            rodape.textContent = itens.length + ' item' + (itens.length !== 1 ? 's' : '') + ' · Total: ' + brl(total);
        }

        openModal('modal-itens-card');
    };
})();

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
