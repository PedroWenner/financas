<?php
// Navegação de mês
$mesAnterior = $mes === 1 ? ['mes' => 12, 'ano' => $ano - 1] : ['mes' => $mes - 1, 'ano' => $ano];
$mesProximo  = $mes === 12 ? ['mes' => 1,  'ano' => $ano + 1] : ['mes' => $mes + 1, 'ano' => $ano];
$ehMesAtual  = ($mes == date('n') && $ano == date('Y'));
?>

<!-- Navegação de Mês -->
<div class="d-flex align-center justify-between mb-3">
    <div class="d-flex align-center gap-1">
        <a href="/dashboard?mes=<?= $mesAnterior['mes'] ?>&ano=<?= $mesAnterior['ano'] ?>" class="btn btn-secondary btn-sm">‹</a>
        <h2 style="font-size:1.1rem;font-weight:600;min-width:160px;text-align:center;">
            <?= nomeMesAno($mes, $ano) ?>
        </h2>
        <?php if (!$ehMesAtual): ?>
            <a href="/dashboard?mes=<?= $mesProximo['mes'] ?>&ano=<?= $mesProximo['ano'] ?>" class="btn btn-secondary btn-sm">›</a>
        <?php else: ?>
            <span class="btn btn-secondary btn-sm" style="opacity:.3;cursor:default;">›</span>
        <?php endif; ?>
    </div>
    <?php if (!$ehMesAtual): ?>
        <a href="/dashboard" class="btn btn-outline btn-sm">Hoje</a>
    <?php endif; ?>
</div>

<!-- Cards de Resumo -->
<div class="stats-grid">
    <div class="stat-card receitas">
        <div class="stat-label">💰 Receitas</div>
        <div class="stat-value positive"><?= moeda($totalReceitas) ?></div>
        <div class="stat-meta"><?= nomeMes($mes) ?> <?= $ano ?></div>
    </div>

    <div class="stat-card despesas">
        <div class="stat-label">💸 Despesas</div>
        <div class="stat-value negative"><?= moeda($totalDespesas) ?></div>
        <div class="stat-meta"><?= nomeMes($mes) ?> <?= $ano ?></div>
    </div>

    <div class="stat-card saldo">
        <div class="stat-label">📊 Saldo do Mês</div>
        <div class="stat-value <?= $saldo >= 0 ? 'positive' : 'negative' ?>">
            <?= moeda($saldo) ?>
        </div>
        <div class="stat-meta"><?= $saldo >= 0 ? '✅ Positivo' : '⚠️ Negativo' ?></div>
    </div>

    <div class="stat-card cartoes">
        <div class="stat-label">💳 Parcelas Futuras</div>
        <div class="stat-value"><?= count($parcelasFut) ?></div>
        <div class="stat-meta">lançamentos pendentes</div>
    </div>
</div>

<!-- Gráficos Principais -->
<div class="dashboard-grid mb-2">
    <!-- Gráfico: Despesas por Categoria -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">Despesas por Categoria</span>
        </div>
        <div class="card-body">
            <?php if (empty($porCategoria)): ?>
                <div class="empty-state">
                    <p>Nenhuma despesa neste mês.</p>
                </div>
            <?php else: ?>
                <div class="chart-container">
                    <canvas id="chart-categorias"></canvas>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Gráfico: Evolução Mensal -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">Evolução dos Últimos 6 Meses</span>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="chart-evolucao"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Cartões e Parcelas Futuras -->
<div class="dashboard-grid mb-2">
    <!-- Gastos por Cartão -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">Gastos por Cartão</span>
            <a href="/cartoes" class="btn btn-outline btn-sm">Ver todos</a>
        </div>
        <div class="card-body">
            <?php if (empty($porCartao)): ?>
                <div class="empty-state"><p>Nenhum cartão cadastrado.</p></div>
            <?php else: ?>
                <?php foreach ($porCartao as $c): ?>
                    <?php
                        $perc = $c['limite'] > 0 ? min(100, ($c['total_gasto'] / $c['limite']) * 100) : 0;
                        $cor  = corProgresso($perc);
                    ?>
                    <div class="mb-2">
                        <div class="d-flex justify-between align-center mb-1">
                            <div class="d-flex align-center gap-1">
                                <div class="color-dot" style="background:<?= e($c['cor'] ?: '#6c757d') ?>"></div>
                                <span class="fw-600 fs-sm"><?= e($c['nome']) ?></span>
                            </div>
                            <span class="fs-sm"><?= moeda($c['total_gasto']) ?> / <?= moeda($c['limite']) ?></span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar <?= $cor ?>" style="width:<?= number_format($perc, 1) ?>%"></div>
                        </div>
                        <div class="fs-sm text-muted mt-1">Disponível: <?= moeda(max(0, $c['limite_disponivel'])) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Parcelas Futuras -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">Próximas Parcelas</span>
            <a href="/parcelamentos" class="btn btn-outline btn-sm">Ver todas</a>
        </div>
        <div class="card-body" style="padding:0;">
            <?php if (empty($parcelasFut)): ?>
                <div class="empty-state"><p>Nenhuma parcela pendente.</p></div>
            <?php else: ?>
                <table>
                    <tbody>
                    <?php foreach ($parcelasFut as $p): ?>
                        <tr>
                            <td>
                                <div class="fw-600 fs-sm"><?= e(truncate($p['descricao'], 25)) ?></div>
                                <div class="fs-sm text-muted"><?= $p['numero_parcela'] ?>/<?= $p['numero_parcelas'] ?></div>
                            </td>
                            <td class="text-right">
                                <div class="fw-600 fs-sm"><?= moeda($p['valor']) ?></div>
                                <div class="fs-sm text-muted"><?= dataFmt($p['data_vencimento']) ?></div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Últimos Lançamentos -->
<div class="card">
    <div class="card-header">
        <span class="card-title">Últimos Lançamentos</span>
        <div class="d-flex gap-1">
            <a href="/receitas?mes=<?= $mes ?>&ano=<?= $ano ?>" class="btn btn-secondary btn-sm">Receitas</a>
            <a href="/despesas?mes=<?= $mes ?>&ano=<?= $ano ?>" class="btn btn-secondary btn-sm">Despesas</a>
        </div>
    </div>
    <?php if (empty($ultimosLanc)): ?>
        <div class="card-body">
            <div class="empty-state"><p>Nenhum lançamento neste mês.</p></div>
        </div>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Descrição</th>
                        <th>Categoria</th>
                        <th>Data</th>
                        <th class="text-right">Valor</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($ultimosLanc as $l): ?>
                    <tr>
                        <td>
                            <?php if ($l['tipo_lancamento'] === 'receita'): ?>
                                <span class="badge badge-success">Receita</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Despesa</span>
                            <?php endif; ?>
                        </td>
                        <td><?= e(truncate($l['descricao'], 30)) ?></td>
                        <td>
                            <?php if ($l['categoria']): ?>
                                <div class="d-flex align-center gap-1">
                                    <div class="color-dot" style="background:<?= e($l['categoria_cor'] ?: '#6c757d') ?>"></div>
                                    <?= e($l['categoria']) ?>
                                </div>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td><?= dataFmt($l['data']) ?></td>
                        <td class="text-right fw-600 <?= $l['tipo_lancamento'] === 'receita' ? 'text-success' : 'text-danger' ?>">
                            <?= $l['tipo_lancamento'] === 'receita' ? '+' : '-' ?><?= moeda($l['valor']) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Chart.js e Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const isDark = () => document.documentElement.getAttribute('data-theme') === 'dark';

// Cores para gráficos adaptadas ao tema
function getGridColor() { return isDark() ? 'rgba(255,255,255,.08)' : 'rgba(0,0,0,.06)'; }
function getTextColor()  { return isDark() ? '#94a3b8' : '#64748b'; }

// ── Gráfico: Categorias (Doughnut) ──
<?php if (!empty($porCategoria)): ?>
const ctxCat = document.getElementById('chart-categorias')?.getContext('2d');
if (ctxCat) {
    new Chart(ctxCat, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_column($porCategoria, 'nome')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($porCategoria, 'total')) ?>,
                backgroundColor: <?= json_encode(array_map(fn($c) => $c['cor'] ?: '#6c757d', $porCategoria)) ?>,
                borderWidth: 3,
                borderColor: isDark() ? '#1e293b' : '#ffffff',
                hoverOffset: 8,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: { color: getTextColor(), padding: 14, boxWidth: 12, font: { size: 12 } }
                },
                tooltip: {
                    callbacks: {
                        label: ctx => ' ' + ctx.label + ': R$ ' + ctx.parsed.toLocaleString('pt-BR', { minimumFractionDigits: 2 })
                    }
                }
            }
        }
    });
}
<?php endif; ?>

// ── Gráfico: Evolução Mensal (Bar) ──
const ctxEv = document.getElementById('chart-evolucao')?.getContext('2d');
if (ctxEv) {
    const evolucao = <?= json_encode($evolucao) ?>;
    new Chart(ctxEv, {
        type: 'bar',
        data: {
            labels: evolucao.map(d => d.mes),
            datasets: [
                {
                    label: 'Receitas',
                    data: evolucao.map(d => d.receitas),
                    backgroundColor: 'rgba(22,163,74,.75)',
                    borderRadius: 5,
                },
                {
                    label: 'Despesas',
                    data: evolucao.map(d => d.despesas),
                    backgroundColor: 'rgba(220,38,38,.75)',
                    borderRadius: 5,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { labels: { color: getTextColor(), font: { size: 12 } } },
                tooltip: {
                    callbacks: {
                        label: ctx => ' ' + ctx.dataset.label + ': R$ ' + ctx.parsed.y.toLocaleString('pt-BR', { minimumFractionDigits: 2 })
                    }
                }
            },
            scales: {
                x: { ticks: { color: getTextColor() }, grid: { color: getGridColor() } },
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: getTextColor(),
                        callback: v => 'R$ ' + v.toLocaleString('pt-BR')
                    },
                    grid: { color: getGridColor() }
                }
            }
        }
    });
}
</script>
