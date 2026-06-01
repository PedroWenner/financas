<?php
$p          = $parcelamento;
$concluido  = (int)$p['parcelas_pendentes'] === 0;
$percPago   = $p['total_parcelas_count'] > 0
    ? ($p['parcelas_pagas'] / $p['total_parcelas_count']) * 100
    : 0;
$corBarra   = $concluido ? 'success' : ($percPago >= 70 ? 'warning' : 'primary');
?>

<div class="mb-3 d-flex justify-between align-center">
    <a href="/parcelamentos" class="btn btn-secondary btn-sm">← Parcelamentos</a>
    <form method="POST" action="/parcelamentos/excluir">
        <?= CsrfHelper::field() ?>
        <input type="hidden" name="id" value="<?= $p['id'] ?>">
        <button type="submit" class="btn btn-danger btn-sm" title="Excluir parcelamento"
                data-confirm="Excluir este parcelamento e todas as parcelas?">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
            Excluir tudo
        </button>
    </form>
</div>

<!-- Cabeçalho do Parcelamento -->
<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex justify-between align-center mb-3">
            <div>
                <h2 style="font-size:1.2rem;font-weight:700;"><?= e($p['descricao']) ?></h2>
                <div class="fs-sm text-muted">Comprado em <?= dataFmt($p['data_compra']) ?></div>
            </div>
            <?php if ($concluido): ?>
                <span class="badge badge-success" style="font-size:.85rem;padding:6px 14px;">✅ Quitado</span>
            <?php else: ?>
                <span class="badge badge-warning" style="font-size:.85rem;padding:6px 14px;">Em andamento</span>
            <?php endif; ?>
        </div>

        <!-- Progresso -->
        <div class="d-flex justify-between fs-sm text-muted mb-1">
            <span><?= (int)$p['parcelas_pagas'] ?> de <?= (int)$p['numero_parcelas'] ?> parcelas pagas</span>
            <span><?= number_format($percPago, 0) ?>% concluído</span>
        </div>
        <div class="progress mb-3" style="height:12px;">
            <div class="progress-bar <?= $corBarra ?>" style="width:<?= number_format($percPago, 1) ?>%"></div>
        </div>

        <!-- Grid de Valores -->
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;">
            <div style="text-align:center;padding:12px;background:var(--surface-2);border-radius:var(--radius-sm);">
                <div class="fs-sm text-muted">Valor Total</div>
                <div class="fw-700" style="font-size:1.1rem;"><?= moeda($p['valor_total']) ?></div>
            </div>
            <div style="text-align:center;padding:12px;background:var(--surface-2);border-radius:var(--radius-sm);">
                <div class="fs-sm text-muted">Valor da Parcela</div>
                <div class="fw-700" style="font-size:1.1rem;"><?= moeda($p['valor_parcela']) ?></div>
            </div>
            <div style="text-align:center;padding:12px;background:var(--success-light);border-radius:var(--radius-sm);">
                <div class="fs-sm" style="color:var(--success)">Já pago</div>
                <div class="fw-700 text-success" style="font-size:1.1rem;"><?= moeda($p['valor_pago']) ?></div>
            </div>
            <div style="text-align:center;padding:12px;background:var(--danger-light);border-radius:var(--radius-sm);">
                <div class="fs-sm" style="color:var(--danger)">Pendente</div>
                <div class="fw-700 text-danger" style="font-size:1.1rem;"><?= moeda($p['valor_pendente']) ?></div>
            </div>
        </div>

        <?php if ($p['cartao_nome']): ?>
            <div class="d-flex align-center gap-1 mt-3 fs-sm text-muted">
                <div class="color-dot" style="background:<?= e($p['cartao_cor'] ?: '#6c757d') ?>"></div>
                💳 <?= e($p['cartao_nome']) ?>
                <?php if ($p['categoria_nome']): ?>
                    &nbsp;·&nbsp;
                    <div class="color-dot" style="background:<?= e($p['categoria_cor'] ?: '#6c757d') ?>"></div>
                    <?= e($p['categoria_nome']) ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($p['observacoes']): ?>
            <div class="fs-sm text-muted mt-2">📝 <?= e($p['observacoes']) ?></div>
        <?php endif; ?>
    </div>
</div>

<!-- Linha do Tempo de Parcelas -->
<div class="card">
    <div class="card-header">
        <span class="card-title">Parcelas</span>
        <span class="badge badge-secondary"><?= count($parcelas) ?> total</span>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Parcela</th>
                    <th>Vencimento</th>
                    <th>Status</th>
                    <th>Data Pagamento</th>
                    <th class="text-right">Valor</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($parcelas as $parc): ?>
                <?php
                $hoje        = date('Y-m-d');
                $vencido     = !$parc['pago'] && $parc['data_vencimento'] < $hoje;
                $venceHoje   = !$parc['pago'] && $parc['data_vencimento'] === $hoje;
                $rowStyle    = $parc['pago'] ? 'opacity:.6;' : ($vencido ? 'background:var(--danger-light);' : ($venceHoje ? 'background:var(--warning-light);' : ''));
                ?>
                <tr style="<?= $rowStyle ?>">
                    <td>
                        <div class="fw-600">
                            <?= $parc['numero_parcela'] ?>/<?= $p['numero_parcelas'] ?>
                            <?php if ($vencido):  ?> <span class="badge badge-danger fs-sm">Vencida</span><?php endif; ?>
                            <?php if ($venceHoje): ?> <span class="badge badge-warning fs-sm">Vence hoje</span><?php endif; ?>
                        </div>
                    </td>
                    <td><?= dataFmt($parc['data_vencimento']) ?></td>
                    <td><?= badgePago((bool)$parc['pago']) ?></td>
                    <td class="fs-sm text-muted">
                        <?= $parc['data_pagamento'] ? dataFmt($parc['data_pagamento']) : '—' ?>
                    </td>
                    <td class="text-right fw-700 <?= $parc['pago'] ? 'text-success' : 'text-danger' ?>">
                        <?= moeda($parc['valor']) ?>
                    </td>
                    <td>
                        <form method="POST" action="/parcelamentos/pagar" style="display:inline;">
                            <?= CsrfHelper::field() ?>
                            <input type="hidden" name="parcela_id"      value="<?= $parc['id'] ?>">
                            <input type="hidden" name="parcelamento_id" value="<?= $p['id'] ?>">
                            <input type="hidden" name="pago"            value="<?= $parc['pago'] ? 0 : 1 ?>">
                            <button type="submit" class="btn btn-sm <?= $parc['pago'] ? 'btn-warning' : 'btn-success' ?>"
                                    title="<?= $parc['pago'] ? 'Marcar como pendente' : 'Marcar como paga' ?>">
                                <?php if ($parc['pago']): ?>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4"/></svg> Desfazer
                                <?php else: ?>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Pagar
                                <?php endif; ?>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Mini Gráfico de Progresso Visual -->
<div class="card mt-3">
    <div class="card-header"><span class="card-title">Linha do Tempo</span></div>
    <div class="card-body">
        <div style="display:flex;gap:6px;flex-wrap:wrap;">
            <?php foreach ($parcelas as $parc): ?>
                <?php
                $hoje    = date('Y-m-d');
                $vencido = !$parc['pago'] && $parc['data_vencimento'] < $hoje;
                $bg      = $parc['pago'] ? 'var(--success)' : ($vencido ? 'var(--danger)' : 'var(--border)');
                $textCor = $parc['pago'] || $vencido ? '#fff' : 'var(--text-muted)';
                ?>
                <div title="Parcela <?= $parc['numero_parcela'] ?>/<?= $p['numero_parcelas'] ?> — <?= dataFmt($parc['data_vencimento']) ?> — <?= moeda($parc['valor']) ?>"
                     style="width:42px;height:42px;border-radius:var(--radius-sm);background:<?= $bg ?>;
                            display:flex;align-items:center;justify-content:center;
                            font-weight:700;font-size:.8rem;color:<?= $textCor ?>;
                            cursor:default;border:2px solid transparent;">
                    <?= $parc['numero_parcela'] ?>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="d-flex gap-2 mt-2 fs-sm text-muted">
            <div class="d-flex align-center gap-1"><div style="width:12px;height:12px;background:var(--success);border-radius:2px;"></div> Paga</div>
            <div class="d-flex align-center gap-1"><div style="width:12px;height:12px;background:var(--danger);border-radius:2px;"></div> Vencida</div>
            <div class="d-flex align-center gap-1"><div style="width:12px;height:12px;background:var(--border);border-radius:2px;"></div> Pendente</div>
        </div>
    </div>
</div>
