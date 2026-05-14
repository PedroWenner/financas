<!-- Cabeçalho -->
<div class="d-flex justify-between align-center mb-3">
    <div>
        <div class="stat-label">Total pendente em todas as parcelas</div>
        <div style="font-size:1.4rem;font-weight:700;color:var(--danger);"><?= moeda($totalPendente) ?></div>
    </div>
    <a href="/parcelamentos/criar" class="btn btn-primary">+ Nova Compra Parcelada</a>
</div>

<!-- Filtros -->
<div class="d-flex gap-1 mb-3">
    <a href="/parcelamentos?filtro=ativos"     class="btn <?= $filtro === 'ativos'     ? 'btn-primary' : 'btn-secondary' ?> btn-sm">Em andamento</a>
    <a href="/parcelamentos?filtro=concluidos" class="btn <?= $filtro === 'concluidos' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">Concluídos</a>
    <a href="/parcelamentos?filtro=todos"      class="btn <?= $filtro === 'todos'      ? 'btn-primary' : 'btn-secondary' ?> btn-sm">Todos</a>
</div>

<!-- Listagem -->
<?php if (empty($parcelamentos)): ?>
    <div class="card">
        <div class="card-body empty-state">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
            <h3>Nenhum parcelamento <?= $filtro === 'ativos' ? 'em andamento' : ($filtro === 'concluidos' ? 'concluído' : 'cadastrado') ?></h3>
            <p>Registre uma compra parcelada para acompanhar as parcelas mês a mês.</p>
            <div class="mt-2"><a href="/parcelamentos/criar" class="btn btn-primary">+ Nova Compra Parcelada</a></div>
        </div>
    </div>
<?php else: ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:16px;">
    <?php foreach ($parcelamentos as $p): ?>
        <?php
        $percPago    = $p['total_parcelas_count'] > 0
            ? ($p['parcelas_pagas'] / $p['total_parcelas_count']) * 100
            : 0;
        $concluido   = (int)$p['parcelas_pendentes'] === 0;
        $corBarra    = $concluido ? 'success' : ($percPago >= 70 ? 'warning' : 'primary');
        $corCartao   = $p['cartao_cor'] ?: '#6c757d';
        ?>
        <div class="card">
            <div class="card-header" style="border-bottom:1px solid var(--border);">
                <div>
                    <div class="fw-600" style="font-size:.95rem;"><?= e(truncate($p['descricao'], 35)) ?></div>
                    <div class="fs-sm text-muted"><?= dataFmt($p['data_compra']) ?></div>
                </div>
                <?php if ($concluido): ?>
                    <span class="badge badge-success">Quitado ✅</span>
                <?php else: ?>
                    <span class="badge badge-warning">Em andamento</span>
                <?php endif; ?>
            </div>

            <div class="card-body">
                <!-- Progresso das Parcelas -->
                <div class="d-flex justify-between fs-sm text-muted mb-1">
                    <span><?= (int)$p['parcelas_pagas'] ?> de <?= (int)$p['numero_parcelas'] ?> parcelas pagas</span>
                    <span><?= number_format($percPago, 0) ?>%</span>
                </div>
                <div class="progress mb-3">
                    <div class="progress-bar <?= $corBarra ?>" style="width:<?= number_format($percPago, 1) ?>%"></div>
                </div>

                <!-- Valores -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:12px;">
                    <div style="background:var(--surface-2);border-radius:var(--radius-sm);padding:10px;text-align:center;">
                        <div class="fs-sm text-muted">Valor Total</div>
                        <div class="fw-700"><?= moeda($p['valor_total']) ?></div>
                    </div>
                    <div style="background:var(--surface-2);border-radius:var(--radius-sm);padding:10px;text-align:center;">
                        <div class="fs-sm text-muted">Parcela</div>
                        <div class="fw-700"><?= moeda($p['valor_parcela']) ?></div>
                    </div>
                    <div style="background:var(--success-light);border-radius:var(--radius-sm);padding:10px;text-align:center;">
                        <div class="fs-sm" style="color:var(--success)">Pago</div>
                        <div class="fw-700 text-success"><?= moeda($p['valor_pago']) ?></div>
                    </div>
                    <div style="background:var(--danger-light);border-radius:var(--radius-sm);padding:10px;text-align:center;">
                        <div class="fs-sm" style="color:var(--danger)">Pendente</div>
                        <div class="fw-700 text-danger"><?= moeda($p['valor_pendente']) ?></div>
                    </div>
                </div>

                <!-- Cartão e próxima parcela -->
                <div class="d-flex justify-between align-center fs-sm text-muted mb-3">
                    <?php if ($p['cartao_nome']): ?>
                        <div class="d-flex align-center gap-1">
                            <div class="color-dot" style="background:<?= e($corCartao) ?>"></div>
                            💳 <?= e($p['cartao_nome']) ?>
                        </div>
                    <?php else: ?>
                        <span>Sem cartão</span>
                    <?php endif; ?>
                    <?php if ($p['proxima_parcela'] && !$concluido): ?>
                        <span>Próxima: <?= dataFmt($p['proxima_parcela']) ?></span>
                    <?php endif; ?>
                </div>

                <!-- Ações -->
                <div class="d-flex gap-1">
                    <a href="/parcelamentos/detalhe?id=<?= $p['id'] ?>" class="btn btn-outline btn-sm" style="flex:1;justify-content:center;">
                        Ver Parcelas
                    </a>
                    <form method="POST" action="/parcelamentos/excluir" style="display:inline;">
                        <?= CsrfHelper::field() ?>
                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm"
                                data-confirm="Excluir '<?= addslashes(e($p['descricao'])) ?>' e todas as suas parcelas?">🗑️</button>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
<?php endif; ?>
