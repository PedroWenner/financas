<div style="max-width:680px;">
    <div class="mb-3"><a href="/despesas-fixas" class="btn btn-secondary btn-sm">← Voltar</a></div>

    <div class="card">
        <div class="card-header"><span class="card-title">Nova Despesa Fixa Recorrente</span></div>
        <div class="card-body">
            <div class="alert alert-info mb-3">
                💡 Despesas fixas são geradas automaticamente todo mês conforme o dia de vencimento configurado.
            </div>

            <form method="POST" action="/despesas-fixas/criar" novalidate>
                <?= CsrfHelper::field() ?>

                <div class="form-row cols-2">
                    <div class="form-group">
                        <label for="descricao">Descrição *</label>
                        <input type="text" id="descricao" name="descricao"
                               class="form-control <?= !empty($errors['descricao']) ? 'is-invalid' : '' ?>"
                               value="<?= e($old['descricao'] ?? '') ?>" placeholder="Ex: Conta de Luz" required maxlength="200">
                        <?php if (!empty($errors['descricao'])): ?>
                            <div class="invalid-feedback"><?= e($errors['descricao']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="valor">Valor Mensal (R$) *</label>
                        <input type="text" id="valor" name="valor" data-mask="money"
                               class="form-control <?= !empty($errors['valor']) ? 'is-invalid' : '' ?>"
                               value="<?= isset($old['valor']) && $old['valor'] > 0 ? number_format($old['valor'], 2, ',', '.') : '' ?>"
                               placeholder="0,00" required>
                        <?php if (!empty($errors['valor'])): ?>
                            <div class="invalid-feedback"><?= e($errors['valor']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-row cols-3">
                    <div class="form-group">
                        <label for="dia_vencimento">Dia de Vencimento *</label>
                        <input type="number" id="dia_vencimento" name="dia_vencimento"
                               class="form-control <?= !empty($errors['dia_vencimento']) ? 'is-invalid' : '' ?>"
                               value="<?= e($old['dia_vencimento'] ?? '10') ?>" min="1" max="28" required>
                        <div class="fs-sm text-muted mt-1">Dia do mês (1-28)</div>
                        <?php if (!empty($errors['dia_vencimento'])): ?>
                            <div class="invalid-feedback"><?= e($errors['dia_vencimento']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="data_inicio">Data de Início *</label>
                        <input type="date" id="data_inicio" name="data_inicio"
                               class="form-control <?= !empty($errors['data_inicio']) ? 'is-invalid' : '' ?>"
                               value="<?= e($old['data_inicio'] ?? date('Y-m-01')) ?>" required>
                        <?php if (!empty($errors['data_inicio'])): ?>
                            <div class="invalid-feedback"><?= e($errors['data_inicio']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="data_fim">Data de Fim</label>
                        <input type="date" id="data_fim" name="data_fim"
                               class="form-control <?= !empty($errors['data_fim']) ? 'is-invalid' : '' ?>"
                               value="<?= e($old['data_fim'] ?? '') ?>">
                        <div class="fs-sm text-muted mt-1">Deixe em branco para sem fim</div>
                        <?php if (!empty($errors['data_fim'])): ?>
                            <div class="invalid-feedback"><?= e($errors['data_fim']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label for="categoria_id">Categoria</label>
                    <select id="categoria_id" name="categoria_id" class="form-control">
                        <option value="">Sem categoria</option>
                        <?php foreach ($categorias as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= ($old['categoria_id'] ?? 0) == $c['id'] ? 'selected' : '' ?>><?= e($c['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="observacoes">Observações</label>
                    <textarea id="observacoes" name="observacoes" class="form-control" rows="2"
                              placeholder="Ex: Conta da CEMIG"><?= e($old['observacoes'] ?? '') ?></textarea>
                </div>

                <div class="d-flex gap-2 mt-2">
                    <button type="submit" class="btn btn-primary">Salvar Despesa Fixa</button>
                    <a href="/despesas-fixas" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
