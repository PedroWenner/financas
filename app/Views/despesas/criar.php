<div style="max-width:720px;">
    <div class="mb-3">
        <a href="<?= e($ref ?: '/despesas') ?>" class="btn btn-secondary btn-sm">← Voltar</a>
    </div>

    <div class="card">
        <div class="card-header">
            <span class="card-title">Nova Despesa</span>
        </div>
        <div class="card-body">
            <form method="POST" action="/despesas/criar" novalidate id="form-despesa">
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
                        <label for="valor">Valor (R$) *</label>
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
                        <label for="data">Data *</label>
                        <input type="date" id="data" name="data"
                               class="form-control <?= !empty($errors['data']) ? 'is-invalid' : '' ?>"
                               value="<?= e($old['data'] ?? date('Y-m-d')) ?>" required>
                        <?php if (!empty($errors['data'])): ?>
                            <div class="invalid-feedback"><?= e($errors['data']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="tipo">Tipo</label>
                        <select id="tipo" name="tipo" class="form-control">
                            <option value="variavel" <?= ($old['tipo'] ?? 'variavel') === 'variavel' ? 'selected' : '' ?>>Variável</option>
                            <option value="fixa"     <?= ($old['tipo'] ?? '') === 'fixa'     ? 'selected' : '' ?>>Fixa</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="forma_pagamento">Forma de Pagamento</label>
                        <select id="forma_pagamento" name="forma_pagamento" class="form-control" onchange="toggleCartao(this.value)">
                            <?php
                            $formas = ['dinheiro'=>'Dinheiro','debito'=>'Débito','credito'=>'Crédito','pix'=>'PIX','ted'=>'TED','boleto'=>'Boleto','outro'=>'Outro'];
                            foreach ($formas as $val => $label):
                            ?>
                                <option value="<?= $val ?>" <?= ($old['forma_pagamento'] ?? 'outro') === $val ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row cols-2">
                    <div class="form-group">
                        <label for="categoria_id">Categoria</label>
                        <select id="categoria_id" name="categoria_id" class="form-control">
                            <option value="">Sem categoria</option>
                            <?php foreach ($categorias as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= ($old['categoria_id'] ?? 0) == $c['id'] ? 'selected' : '' ?>><?= e($c['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" id="grupo-cartao" style="<?= ($old['forma_pagamento'] ?? '') !== 'credito' ? 'display:none;' : '' ?>">
                        <label for="cartao_id">Cartão de Crédito <?= !empty($errors['cartao_id']) ? '*' : '' ?></label>
                        <select id="cartao_id" name="cartao_id" class="form-control <?= !empty($errors['cartao_id']) ? 'is-invalid' : '' ?>">
                            <option value="">Selecione o cartão</option>
                            <?php foreach ($cartoes as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= ($old['cartao_id'] ?? 0) == $c['id'] ? 'selected' : '' ?>><?= e($c['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!empty($errors['cartao_id'])): ?>
                            <div class="invalid-feedback"><?= e($errors['cartao_id']) ?></div>
                        <?php endif; ?>
                        <?php if (empty($cartoes)): ?>
                            <div class="fs-sm text-muted mt-1">
                                Nenhum cartão cadastrado. <a href="/cartoes/criar">Cadastrar cartão</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Status de Pagamento -->
                <div class="form-group">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                        <input type="checkbox" name="pago" value="1" <?= !empty($old['pago']) ? 'checked' : '' ?>>
                        <span>Marcar como paga</span>
                    </label>
                </div>

                <div class="form-group">
                    <label for="observacoes">Observações</label>
                    <textarea id="observacoes" name="observacoes" class="form-control" rows="2"
                              placeholder="Informações adicionais..."><?= e($old['observacoes'] ?? '') ?></textarea>
                </div>

                <input type="hidden" name="ref" value="<?= e($ref ?? '') ?>">
                <div class="d-flex gap-2 mt-2">
                    <button type="submit" class="btn btn-primary">Salvar Despesa</button>
                    <a href="<?= e($ref ?: '/despesas') ?>" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleCartao(forma) {
    const grupo = document.getElementById('grupo-cartao');
    if (grupo) grupo.style.display = forma === 'credito' ? '' : 'none';
}

// Verifica estado inicial ao carregar
document.addEventListener('DOMContentLoaded', () => {
    const sel = document.getElementById('forma_pagamento');
    if (sel) toggleCartao(sel.value);
});
</script>
