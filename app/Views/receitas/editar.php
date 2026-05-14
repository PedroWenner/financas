<div style="max-width:680px;">
    <div class="mb-3">
        <a href="/receitas" class="btn btn-secondary btn-sm">← Voltar</a>
    </div>

    <div class="card">
        <div class="card-header">
            <span class="card-title">Editar Receita</span>
        </div>
        <div class="card-body">
            <form method="POST" action="/receitas/editar" novalidate>
                <?= CsrfHelper::field() ?>
                <input type="hidden" name="id" value="<?= $receita['id'] ?>">

                <div class="form-row cols-2">
                    <div class="form-group">
                        <label for="descricao">Descrição *</label>
                        <input type="text" id="descricao" name="descricao"
                               class="form-control <?= !empty($errors['descricao']) ? 'is-invalid' : '' ?>"
                               value="<?= e($old['descricao'] ?? $receita['descricao']) ?>" required maxlength="200">
                        <?php if (!empty($errors['descricao'])): ?>
                            <div class="invalid-feedback"><?= e($errors['descricao']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="valor">Valor (R$) *</label>
                        <?php $valorFmt = number_format((float)($old['valor'] ?? $receita['valor']), 2, ',', '.'); ?>
                        <input type="text" id="valor" name="valor" data-mask="money"
                               class="form-control <?= !empty($errors['valor']) ? 'is-invalid' : '' ?>"
                               value="<?= e($valorFmt) ?>" required>
                        <?php if (!empty($errors['valor'])): ?>
                            <div class="invalid-feedback"><?= e($errors['valor']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-row cols-2">
                    <div class="form-group">
                        <label for="data">Data *</label>
                        <input type="date" id="data" name="data"
                               class="form-control <?= !empty($errors['data']) ? 'is-invalid' : '' ?>"
                               value="<?= e($old['data'] ?? $receita['data']) ?>" required>
                        <?php if (!empty($errors['data'])): ?>
                            <div class="invalid-feedback"><?= e($errors['data']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="tipo_receita">Tipo de Receita</label>
                        <select id="tipo_receita" name="tipo_receita" class="form-control">
                            <?php $tipos = ['salario'=>'Salário','salario_conjuge'=>'Salário do Cônjuge','renda_extra'=>'Renda Extra','investimento'=>'Investimento','outro'=>'Outro']; ?>
                            <?php foreach ($tipos as $val => $label): ?>
                                <option value="<?= $val ?>" <?= ($old['tipo_receita'] ?? $receita['tipo_receita']) === $val ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="categoria_id">Categoria</label>
                    <select id="categoria_id" name="categoria_id" class="form-control">
                        <option value="">Sem categoria</option>
                        <?php foreach ($categorias as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= ($old['categoria_id'] ?? $receita['categoria_id']) == $c['id'] ? 'selected' : '' ?>><?= e($c['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="observacoes">Observações</label>
                    <textarea id="observacoes" name="observacoes" class="form-control" rows="3"><?= e($old['observacoes'] ?? $receita['observacoes'] ?? '') ?></textarea>
                </div>

                <div class="d-flex gap-2 mt-2">
                    <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                    <a href="/receitas" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
