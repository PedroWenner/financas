<div style="max-width:680px;">
    <div class="mb-3"><a href="/despesas-fixas" class="btn btn-secondary btn-sm">← Voltar</a></div>

    <div class="card">
        <div class="card-header"><span class="card-title">Editar Despesa Fixa</span></div>
        <div class="card-body">
            <form method="POST" action="/despesas-fixas/editar" novalidate>
                <?= CsrfHelper::field() ?>
                <input type="hidden" name="id" value="<?= $fixa['id'] ?>">

                <div class="form-row cols-2">
                    <div class="form-group">
                        <label for="descricao">Descrição *</label>
                        <input type="text" id="descricao" name="descricao"
                               class="form-control <?= !empty($errors['descricao']) ? 'is-invalid' : '' ?>"
                               value="<?= e($old['descricao'] ?? $fixa['descricao']) ?>" required maxlength="200">
                        <?php if (!empty($errors['descricao'])): ?>
                            <div class="invalid-feedback"><?= e($errors['descricao']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="valor">Valor Mensal (R$) *</label>
                        <?php $valFmt = number_format((float)($old['valor'] ?? $fixa['valor']), 2, ',', '.'); ?>
                        <input type="text" id="valor" name="valor" data-mask="money"
                               class="form-control <?= !empty($errors['valor']) ? 'is-invalid' : '' ?>"
                               value="<?= e($valFmt) ?>" required>
                        <?php if (!empty($errors['valor'])): ?>
                            <div class="invalid-feedback"><?= e($errors['valor']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-row cols-3">
                    <div class="form-group">
                        <label for="dia_vencimento">Dia de Vencimento *</label>
                        <input type="number" id="dia_vencimento" name="dia_vencimento"
                               class="form-control" value="<?= e($old['dia_vencimento'] ?? $fixa['dia_vencimento']) ?>"
                               min="1" max="28" required>
                    </div>
                    <div class="form-group">
                        <label for="data_inicio">Data de Início *</label>
                        <input type="date" id="data_inicio" name="data_inicio"
                               class="form-control" value="<?= e($old['data_inicio'] ?? $fixa['data_inicio']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="data_fim">Data de Fim</label>
                        <input type="date" id="data_fim" name="data_fim"
                               class="form-control" value="<?= e($old['data_fim'] ?? $fixa['data_fim']) ?>">
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
                            <option value="<?= $c['id'] ?>" <?= ($old['categoria_id'] ?? $fixa['categoria_id']) == $c['id'] ? 'selected' : '' ?>><?= e($c['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="observacoes">Observações</label>
                    <textarea id="observacoes" name="observacoes" class="form-control" rows="2"><?= e($old['observacoes'] ?? $fixa['observacoes'] ?? '') ?></textarea>
                </div>

                <div class="d-flex gap-2 mt-2">
                    <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                    <a href="/despesas-fixas" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
