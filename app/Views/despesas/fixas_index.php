<div class="d-flex justify-between align-center mb-3">
    <div>
        <div class="stat-label">Custo fixo mensal estimado</div>
        <div style="font-size:1.4rem;font-weight:700;color:var(--danger);"><?= moeda($total) ?></div>
    </div>
    <a href="/despesas-fixas/criar" class="btn btn-primary">+ Nova Despesa Fixa</a>
</div>

<?php if (empty($fixas)): ?>
    <div class="card">
        <div class="card-body empty-state">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/></svg>
            <h3>Nenhuma despesa fixa cadastrada</h3>
            <p>Cadastre contas recorrentes como aluguel, internet, energia e assinaturas.</p>
            <div class="mt-2"><a href="/despesas-fixas/criar" class="btn btn-primary">+ Nova Despesa Fixa</a></div>
        </div>
    </div>
<?php else: ?>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Descrição</th>
                    <th>Categoria</th>
                    <th>Vencimento</th>
                    <th>Pagamento</th>
                    <th>Vigência</th>
                    <th>Status</th>
                    <th class="text-right">Valor Mensal</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($fixas as $f): ?>
                <tr style="<?= !$f['ativo'] ? 'opacity:.5;' : '' ?>">
                    <td>
                        <div class="fw-600"><?= e($f['descricao']) ?></div>
                        <?php if ($f['observacoes']): ?>
                            <div class="fs-sm text-muted"><?= e(truncate($f['observacoes'], 35)) ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($f['categoria_nome']): ?>
                            <div class="d-flex align-center gap-1">
                                <div class="color-dot" style="background:<?= e($f['categoria_cor'] ?: '#6c757d') ?>"></div>
                                <?= e($f['categoria_nome']) ?>
                            </div>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td><?= e(diaVencimentoFmt((int) $f['dia_vencimento'], (bool) $f['dia_vencimento_ultimo_dia'])) ?></td>
                    <td>
                        <?php
                        $pagLabels = ['dinheiro'=>'Dinheiro','debito'=>'Débito','credito'=>'Crédito','pix'=>'PIX','ted'=>'TED','boleto'=>'Boleto','outro'=>'Outro'];
                        echo e($pagLabels[$f['forma_pagamento']] ?? $f['forma_pagamento']);
                        ?>
                        <?php if ($f['forma_pagamento'] === 'credito' && $f['cartao_nome']): ?>
                            <div class="fs-sm text-muted">💳 <?= e($f['cartao_nome']) ?></div>
                            <?php if (!empty($f['proxima_data_debito'])): ?>
                                <div class="fs-sm text-muted">Próx. débito: <?= dataFmt($f['proxima_data_debito']) ?></div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                    <td class="fs-sm">
                        <?= dataFmt($f['data_inicio']) ?> →
                        <?= $f['data_fim'] ? dataFmt($f['data_fim']) : 'Sem fim' ?>
                    </td>
                    <td>
                        <?php if ($f['ativo']): ?>
                            <span class="badge badge-success">Ativa</span>
                        <?php else: ?>
                            <span class="badge badge-secondary">Inativa</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-right fw-700 text-danger"><?= moeda($f['valor']) ?></td>
                    <td>
                        <div class="td-actions">
                            <?php if ($f['ativo']): ?>
                                <a href="/despesas-fixas/editar?id=<?= $f['id'] ?>" class="btn btn-secondary btn-sm" title="Editar">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                </a>
                                <button type="button" class="btn btn-warning btn-sm" title="Finalizar"
                                        onclick="abrirFinalizar(<?= $f['id'] ?>, '<?= addslashes(e($f['descricao'])) ?>', '<?= e($f['data_inicio']) ?>')">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>
                                </button>
                            <?php else: ?>
                                <form method="POST" action="/despesas-fixas/ativar" style="display:inline;">
                                    <?= CsrfHelper::field() ?>
                                    <input type="hidden" name="id" value="<?= $f['id'] ?>">
                                    <button type="submit" class="btn btn-success btn-sm" title="Reativar">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polygon points="10 8 16 12 10 16 10 8"/></svg>
                                    </button>
                                </form>
                            <?php endif; ?>
                            <form method="POST" action="/despesas-fixas/deletar" style="display:inline;">
                                <?= CsrfHelper::field() ?>
                                <input type="hidden" name="id" value="<?= $f['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm" title="Excluir permanentemente"
                                        data-confirm-danger="Excluir '<?= addslashes(e($f['descricao'])) ?>' e todos os lançamentos vinculados? Esta ação não pode ser desfeita.">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<!-- Modal: Finalizar Despesa Fixa -->
<div class="modal-overlay" id="modal-finalizar">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title">Finalizar Despesa Fixa</span>
            <button class="modal-close" type="button">×</button>
        </div>
        <form method="POST" action="/despesas-fixas/finalizar">
            <?= CsrfHelper::field() ?>
            <input type="hidden" name="id" id="finalizar-id">
            <div class="modal-body">
                <p>Finalizar <strong id="finalizar-descricao"></strong>? Os lançamentos já gerados para meses fora do novo período de vigência serão excluídos.</p>
                <div class="form-group">
                    <label for="finalizar-data">Data de finalização *</label>
                    <input type="date" id="finalizar-data" name="data_fim" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary modal-close">Cancelar</button>
                <button type="submit" class="btn btn-warning">Finalizar</button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirFinalizar(id, descricao, dataInicio) {
    document.getElementById('finalizar-id').value = id;
    document.getElementById('finalizar-descricao').textContent = descricao;
    const campoData = document.getElementById('finalizar-data');
    campoData.min = dataInicio;
    campoData.value = new Date().toISOString().slice(0, 10);
    openModal('modal-finalizar');
}
</script>
