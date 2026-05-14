<div style="max-width:620px;">
    <div class="mb-3"><a href="/cartoes" class="btn btn-secondary btn-sm">← Voltar</a></div>

    <div class="card">
        <div class="card-header"><span class="card-title">Novo Cartão de Crédito</span></div>
        <div class="card-body">
            <form method="POST" action="/cartoes/criar" novalidate>
                <?= CsrfHelper::field() ?>

                <div class="form-row cols-2">
                    <div class="form-group">
                        <label for="nome">Nome do Cartão *</label>
                        <input type="text" id="nome" name="nome"
                               class="form-control <?= !empty($errors['nome']) ? 'is-invalid' : '' ?>"
                               value="<?= e($old['nome'] ?? '') ?>" placeholder="Ex: Nubank" required maxlength="80">
                        <?php if (!empty($errors['nome'])): ?>
                            <div class="invalid-feedback"><?= e($errors['nome']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="bandeira">Bandeira</label>
                        <select id="bandeira" name="bandeira" class="form-control">
                            <?php foreach (['visa'=>'Visa','mastercard'=>'Mastercard','elo'=>'Elo','amex'=>'Amex','hipercard'=>'Hipercard','outro'=>'Outro'] as $v => $l): ?>
                                <option value="<?= $v ?>" <?= ($old['bandeira'] ?? 'outro') === $v ? 'selected' : '' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="limite">Limite de Crédito (R$)</label>
                    <input type="text" id="limite" name="limite" data-mask="money"
                           class="form-control <?= !empty($errors['limite']) ? 'is-invalid' : '' ?>"
                           value="<?= isset($old['limite']) ? number_format($old['limite'], 2, ',', '.') : '' ?>"
                           placeholder="0,00">
                    <?php if (!empty($errors['limite'])): ?>
                        <div class="invalid-feedback"><?= e($errors['limite']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-row cols-2">
                    <div class="form-group">
                        <label for="dia_fechamento">Dia de Fechamento *</label>
                        <input type="number" id="dia_fechamento" name="dia_fechamento"
                               class="form-control <?= !empty($errors['dia_fechamento']) ? 'is-invalid' : '' ?>"
                               value="<?= e($old['dia_fechamento'] ?? '1') ?>" min="1" max="28" required>
                        <div class="fs-sm text-muted mt-1">Dia em que a fatura fecha (1-28)</div>
                        <?php if (!empty($errors['dia_fechamento'])): ?>
                            <div class="invalid-feedback"><?= e($errors['dia_fechamento']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="dia_vencimento">Dia de Vencimento *</label>
                        <input type="number" id="dia_vencimento" name="dia_vencimento"
                               class="form-control <?= !empty($errors['dia_vencimento']) ? 'is-invalid' : '' ?>"
                               value="<?= e($old['dia_vencimento'] ?? '10') ?>" min="1" max="28" required>
                        <div class="fs-sm text-muted mt-1">Dia em que a fatura vence (1-28)</div>
                        <?php if (!empty($errors['dia_vencimento'])): ?>
                            <div class="invalid-feedback"><?= e($errors['dia_vencimento']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label for="cor">Cor do Cartão</label>
                    <input type="color" id="cor" name="cor" class="form-control"
                           value="<?= e($old['cor'] ?? '#2563eb') ?>" style="height:42px;padding:4px 8px;cursor:pointer;">
                </div>

                <div class="d-flex gap-2 mt-2">
                    <button type="submit" class="btn btn-primary">Salvar Cartão</button>
                    <a href="/cartoes" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
