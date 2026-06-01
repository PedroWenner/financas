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
                    <th>Dia Venc.</th>
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
                    <td>Dia <?= $f['dia_vencimento'] ?></td>
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
                                <a href="/despesas-fixas/editar?id=<?= $f['id'] ?>" class="btn btn-secondary btn-sm">✏️</a>
                                <form method="POST" action="/despesas-fixas/excluir" style="display:inline;">
                                    <?= CsrfHelper::field() ?>
                                    <input type="hidden" name="id" value="<?= $f['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm"
                                            data-confirm="Desativar '<?= addslashes(e($f['descricao'])) ?>'? Os lançamentos existentes não serão removidos.">
                                        ⏹️
                                    </button>
                                </form>
                            <?php else: ?>
                                <form method="POST" action="/despesas-fixas/ativar" style="display:inline;">
                                    <?= CsrfHelper::field() ?>
                                    <input type="hidden" name="id" value="<?= $f['id'] ?>">
                                    <button type="submit" class="btn btn-success btn-sm" title="Reativar despesa fixa">
                                        ▶️
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
