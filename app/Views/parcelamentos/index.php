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
    <a href="/parcelamentos?filtro=ativos"     class="btn <?= $filtro === 'ativos'     ? 'btn-primary'  : 'btn-secondary' ?> btn-sm">Em andamento</a>
    <a href="/parcelamentos?filtro=concluidos" class="btn <?= $filtro === 'concluidos' ? 'btn-primary'  : 'btn-secondary' ?> btn-sm">Concluídos</a>
    <a href="/parcelamentos?filtro=bloqueados" class="btn <?= $filtro === 'bloqueados' ? 'btn-warning'  : 'btn-secondary' ?> btn-sm">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:3px;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>Bloqueados
    </a>
    <a href="/parcelamentos?filtro=todos"      class="btn <?= $filtro === 'todos'      ? 'btn-primary'  : 'btn-secondary' ?> btn-sm">Todos</a>
</div>

<!-- Listagem -->
<?php if (empty($parcelamentos)): ?>
    <div class="card">
        <div class="card-body empty-state">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
            <h3>Nenhum parcelamento <?= match($filtro) { 'ativos' => 'em andamento', 'concluidos' => 'concluído', 'bloqueados' => 'bloqueado', default => 'cadastrado' } ?></h3>
            <p><?= $filtro === 'bloqueados' ? 'Nenhum parcelamento foi bloqueado ainda.' : 'Registre uma compra parcelada para acompanhar as parcelas mês a mês.' ?></p>
            <?php if ($filtro !== 'bloqueados'): ?>
            <div class="mt-2"><a href="/parcelamentos/criar" class="btn btn-primary">+ Nova Compra Parcelada</a></div>
            <?php endif; ?>
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

                <?php if ($p['bloqueado_a_partir']): ?>
                    <div class="fs-sm mt-2" style="color:var(--warning);display:flex;align-items:center;gap:4px;">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        Bloqueado a partir de <?= dataFmt($p['bloqueado_a_partir']) ?>
                    </div>
                <?php endif; ?>

                <!-- Ações -->
                <div class="d-flex gap-1 mt-3">
                    <a href="/parcelamentos/detalhe?id=<?= $p['id'] ?>" class="btn btn-outline btn-sm" style="flex:1;justify-content:center;">
                        Ver Parcelas
                    </a>
                    <?php if ($p['bloqueado_a_partir']): ?>
                        <form method="POST" action="/parcelamentos/desbloquear" style="display:inline;">
                            <?= CsrfHelper::field() ?>
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                            <button type="submit" class="btn btn-success btn-sm" title="Remover bloqueio"
                                    data-confirm="Remover o bloqueio de '<?= addslashes(e($p['descricao'])) ?>'? As parcelas voltarão a aparecer nas despesas.">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 9.9-1"/></svg>
                            </button>
                        </form>
                    <?php else: ?>
                        <button type="button" class="btn btn-warning btn-sm btn-abrir-bloqueio" title="Bloquear a partir de..."
                                data-id="<?= $p['id'] ?>"
                                data-nome="<?= addslashes(e($p['descricao'])) ?>">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        </button>
                    <?php endif; ?>
                    <form method="POST" action="/parcelamentos/excluir" style="display:inline;">
                        <?= CsrfHelper::field() ?>
                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm" title="Excluir"
                                data-confirm="Excluir '<?= addslashes(e($p['descricao'])) ?>' e todas as suas parcelas?">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Modal: Bloquear Parcelamento -->
<div class="modal-overlay" id="modal-bloquear">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title">Bloquear Parcelamento</span>
            <button class="modal-close" type="button">×</button>
        </div>
        <form method="POST" action="/parcelamentos/bloquear">
            <?= CsrfHelper::field() ?>
            <input type="hidden" name="id" id="bloquear-id">
            <div class="modal-body">
                <p class="fs-sm text-muted mb-3">
                    As parcelas de <strong id="bloquear-nome"></strong>
                    a partir do mês selecionado não serão mais exibidas nas despesas.
                </p>
                <div class="form-group">
                    <label for="bloquear-mes" class="form-label">Bloquear a partir de</label>
                    <input type="month" name="bloqueado_a_partir" id="bloquear-mes" class="form-control" required>
                    <div class="fs-sm text-muted mt-1">Parcelas deste mês em diante serão ocultadas nas despesas.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary modal-close">Cancelar</button>
                <button type="submit" class="btn btn-warning">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    Bloquear
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.querySelectorAll('.btn-abrir-bloqueio').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('bloquear-id').value   = btn.dataset.id;
        document.getElementById('bloquear-nome').textContent = btn.dataset.nome;
        const d  = new Date();
        d.setMonth(d.getMonth() + 1);
        const yy = d.getFullYear();
        const mm = String(d.getMonth() + 1).padStart(2, '0');
        document.getElementById('bloquear-mes').value = yy + '-' + mm;
        openModal('modal-bloquear');
    });
});
</script>
