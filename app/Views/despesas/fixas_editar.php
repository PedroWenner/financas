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
                        <?php
                        $ultimoDiaAtual = $old['dia_vencimento_ultimo_dia'] ?? $fixa['dia_vencimento_ultimo_dia'] ?? false;
                        $diaSelecionado = $ultimoDiaAtual ? 'ultimo' : (string) ($old['dia_vencimento'] ?? $fixa['dia_vencimento']);
                        ?>
                        <div class="dia-picker">
                            <input type="hidden" id="dia_vencimento" name="dia_vencimento" value="<?= e($diaSelecionado) ?>">
                            <button type="button" class="dia-picker-trigger form-control <?= !empty($errors['dia_vencimento']) ? 'is-invalid' : '' ?>">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                <span class="dia-picker-label"><?= e($diaSelecionado === 'ultimo' ? 'Último dia do mês' : 'Dia ' . $diaSelecionado) ?></span>
                            </button>
                            <div class="dia-picker-panel" hidden>
                                <div class="dia-picker-grid">
                                    <?php for ($i = 1; $i <= 31; $i++): ?>
                                        <button type="button" class="dia-picker-day" data-dia="<?= $i ?>"><?= $i ?></button>
                                    <?php endfor; ?>
                                </div>
                                <button type="button" class="dia-picker-ultimo" data-dia="ultimo">Último dia do mês</button>
                            </div>
                        </div>
                        <?php if (!empty($errors['dia_vencimento'])): ?>
                            <div class="invalid-feedback"><?= e($errors['dia_vencimento']) ?></div>
                        <?php endif; ?>
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

                <div class="form-row cols-2">
                    <div class="form-group">
                        <label for="forma_pagamento">Forma de Pagamento</label>
                        <?php $formaAtual = $old['forma_pagamento'] ?? $fixa['forma_pagamento'] ?? 'boleto'; ?>
                        <select id="forma_pagamento" name="forma_pagamento" class="form-control" onchange="toggleCartao(this.value)">
                            <?php
                            $formas = ['dinheiro'=>'Dinheiro','debito'=>'Débito','credito'=>'Crédito','pix'=>'PIX','ted'=>'TED','boleto'=>'Boleto','outro'=>'Outro'];
                            foreach ($formas as $val => $label):
                            ?>
                                <option value="<?= $val ?>" <?= $formaAtual === $val ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" id="grupo-cartao" style="<?= $formaAtual !== 'credito' ? 'display:none;' : '' ?>">
                        <label for="cartao_id">Cartão de Crédito <?= !empty($errors['cartao_id']) ? '*' : '' ?></label>
                        <select id="cartao_id" name="cartao_id" class="form-control <?= !empty($errors['cartao_id']) ? 'is-invalid' : '' ?>">
                            <option value="">Selecione o cartão</option>
                            <?php foreach ($cartoes as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= ($old['cartao_id'] ?? $fixa['cartao_id'] ?? 0) == $c['id'] ? 'selected' : '' ?>><?= e($c['nome']) ?></option>
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

<script>
function toggleCartao(forma) {
    const grupo = document.getElementById('grupo-cartao');
    if (grupo) grupo.style.display = forma === 'credito' ? '' : 'none';
}

document.addEventListener('DOMContentLoaded', () => {
    const sel = document.getElementById('forma_pagamento');
    if (sel) toggleCartao(sel.value);
});
</script>
