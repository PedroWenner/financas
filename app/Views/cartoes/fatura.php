<?php
$mesAnterior = $mes === 1  ? ['mes' => 12, 'ano' => $ano - 1] : ['mes' => $mes - 1, 'ano' => $ano];
$mesProximo  = $mes === 12 ? ['mes' => 1,  'ano' => $ano + 1] : ['mes' => $mes + 1, 'ano' => $ano];
$cor = $cartao['cor'] ?: '#2563eb';
?>

<div class="mb-3 d-flex justify-between align-center">
    <a href="/cartoes" class="btn btn-secondary btn-sm">← Todos os Cartões</a>
    <div class="d-flex align-center gap-1">
        <a href="/cartoes/fatura?id=<?= $cartao['id'] ?>&mes=<?= $mesAnterior['mes'] ?>&ano=<?= $mesAnterior['ano'] ?>" class="btn btn-secondary btn-sm">‹</a>
        <span style="font-weight:600;min-width:150px;text-align:center;"><?= nomeMesAno($mes, $ano) ?></span>
        <a href="/cartoes/fatura?id=<?= $cartao['id'] ?>&mes=<?= $mesProximo['mes'] ?>&ano=<?= $mesProximo['ano'] ?>" class="btn btn-secondary btn-sm">›</a>
    </div>
</div>

<!-- Card Visual do Cartão -->
<div style="max-width:420px;margin-bottom:24px;">
    <div class="card-visual" style="background:linear-gradient(135deg, <?= e($cor) ?>, <?= e($cor) ?>cc);">
        <div style="font-size:.75rem;opacity:.8;margin-bottom:6px;"><?= ucfirst($cartao['bandeira']) ?></div>
        <div style="font-size:1.4rem;font-weight:700;"><?= e($cartao['nome']) ?></div>
        <div style="margin-top:16px;display:flex;justify-content:space-between;font-size:.85rem;">
            <span>Fecha: dia <?= $cartao['dia_fechamento'] ?></span>
            <span>Vence: dia <?= $cartao['dia_vencimento'] ?></span>
        </div>
    </div>
</div>

<!-- Resumo da Fatura -->
<div class="stats-grid mb-3" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));">
    <div class="stat-card despesas">
        <div class="stat-label">Total da Fatura</div>
        <div class="stat-value negative"><?= moeda($total) ?></div>
    </div>
    <div class="stat-card" style="border-left-color:var(--success);">
        <div class="stat-label">Limite Total</div>
        <div class="stat-value"><?= moeda($cartao['limite']) ?></div>
    </div>
    <div class="stat-card" style="border-left-color:var(--info);">
        <div class="stat-label">Disponível</div>
        <div class="stat-value"><?= moeda(max(0, $cartao['limite'] - $total)) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Lançamentos</div>
        <div class="stat-value"><?= count($despesas) ?></div>
    </div>
</div>

<!-- Tabela de Lançamentos -->
<?php if (empty($despesas)): ?>
    <div class="card">
        <div class="card-body empty-state">
            <p>Nenhum lançamento nesta fatura.</p>
        </div>
    </div>
<?php else: ?>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Descrição</th>
                    <th>Categoria</th>
                    <th>Tipo</th>
                    <th>Status</th>
                    <th class="text-right">Valor</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($despesas as $d): ?>
                <tr>
                    <td><?= dataFmt($d['data']) ?></td>
                    <td class="fw-600"><?= e(truncate($d['descricao'], 35)) ?></td>
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
                    <td><?= badgeTipo($d['tipo']) ?></td>
                    <td><?= badgePago((bool)$d['pago']) ?></td>
                    <td class="text-right fw-700 text-danger"><?= moeda($d['valor']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" class="fw-600 text-right" style="padding:12px 16px;">Total da Fatura:</td>
                    <td class="fw-700 text-danger text-right" style="padding:12px 16px;"><?= moeda($total) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
<?php endif; ?>
