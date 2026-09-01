<div style="max-width:720px;">
    <div class="mb-3"><a href="/parcelamentos" class="btn btn-secondary btn-sm">← Voltar</a></div>

    <?php if (!empty($errors['geral'])): ?>
        <div class="alert alert-error mb-3"><?= e($errors['geral']) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header"><span class="card-title">Nova Compra Parcelada</span></div>
        <div class="card-body">

            <!-- Preview em tempo real -->
            <div id="preview-parcela" style="background:var(--primary-light);border-radius:var(--radius-sm);padding:14px;margin-bottom:20px;display:none;">
                <div class="fs-sm text-muted">Resumo do Parcelamento</div>
                <div style="font-size:1.1rem;font-weight:700;color:var(--primary);margin-top:4px;" id="preview-texto">
                    —
                </div>
                <div class="fs-sm text-muted mt-1">
                    Primeira parcela: <strong id="preview-primeira">—</strong>
                    &nbsp;·&nbsp;
                    Última parcela: <strong id="preview-ultima">—</strong>
                </div>
            </div>

            <form method="POST" action="/parcelamentos/criar" novalidate id="form-parc">
                <?= CsrfHelper::field() ?>

                <!-- Descrição e Valor -->
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label for="descricao">Descrição da Compra *</label>
                        <input type="text" id="descricao" name="descricao"
                               class="form-control <?= !empty($errors['descricao']) ? 'is-invalid' : '' ?>"
                               value="<?= e($old['descricao'] ?? '') ?>"
                               placeholder="Ex: iPhone 16 Pro" required maxlength="200">
                        <?php if (!empty($errors['descricao'])): ?>
                            <div class="invalid-feedback"><?= e($errors['descricao']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="valor_total">Valor Total (R$) *</label>
                        <input type="text" id="valor_total" name="valor_total" data-mask="money"
                               class="form-control <?= !empty($errors['valor_total']) ? 'is-invalid' : '' ?>"
                               value="<?= isset($old['valor_total']) && $old['valor_total'] > 0 ? number_format($old['valor_total'], 2, ',', '.') : '' ?>"
                               placeholder="0,00" required oninput="atualizarPreview()">
                        <?php if (!empty($errors['valor_total'])): ?>
                            <div class="invalid-feedback"><?= e($errors['valor_total']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Parcelas, Data da Compra e Cartão -->
                <div class="form-row cols-3">
                    <div class="form-group">
                        <label for="numero_parcelas">Número de Parcelas *</label>
                        <div style="position:relative;">
                            <input type="number" id="numero_parcelas" name="numero_parcelas"
                                   class="form-control <?= !empty($errors['numero_parcelas']) ? 'is-invalid' : '' ?>"
                                   value="<?= (int)($old['numero_parcelas'] ?? 2) ?>"
                                   min="2" max="96" required oninput="atualizarPreview()">
                        </div>
                        <?php if (!empty($errors['numero_parcelas'])): ?>
                            <div class="invalid-feedback"><?= e($errors['numero_parcelas']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="data_compra">Data da Compra *</label>
                        <input type="date" id="data_compra" name="data_compra"
                               class="form-control <?= !empty($errors['data_compra']) ? 'is-invalid' : '' ?>"
                               value="<?= e($old['data_compra'] ?? date('Y-m-d')) ?>"
                               required oninput="atualizarPreview()">
                        <?php if (!empty($errors['data_compra'])): ?>
                            <div class="invalid-feedback"><?= e($errors['data_compra']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="cartao_id">Cartão de Crédito</label>
                        <select id="cartao_id" name="cartao_id" class="form-control" onchange="atualizarPreview()">
                            <option value="">Sem cartão (à vista parcelado)</option>
                            <?php foreach ($cartoes as $c): ?>
                                <option value="<?= $c['id'] ?>"
                                        data-fechamento="<?= $c['dia_fechamento'] ?>"
                                        data-vencimento="<?= $c['dia_vencimento'] ?>"
                                    <?= ($old['cartao_id'] ?? 0) == $c['id'] ? 'selected' : '' ?>>
                                    <?= e($c['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($cartoes)): ?>
                            <div class="fs-sm text-muted mt-1"><a href="/cartoes/criar">+ Cadastrar cartão</a></div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Data da Primeira Parcela (opcional, calculada automaticamente) -->
                <div class="form-group">
                    <label for="data_primeira_parcela">
                        Data da Primeira Parcela
                        <span class="fs-sm text-muted">(calculada automaticamente se não preenchida)</span>
                    </label>
                    <input type="date" id="data_primeira_parcela" name="data_primeira_parcela"
                           class="form-control"
                           value="<?= e($old['data_primeira_parcela'] ?? '') ?>"
                           oninput="atualizarPreview()">
                    <div class="fs-sm text-muted mt-1" id="data-auto-info">
                        💡 Se um cartão for selecionado, a data é calculada com base no dia de fechamento.
                    </div>
                </div>

                <!-- Categoria e Observações -->
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
                    <div class="form-group">
                        <label for="observacoes">Observações</label>
                        <input type="text" id="observacoes" name="observacoes" class="form-control"
                               value="<?= e($old['observacoes'] ?? '') ?>" placeholder="Informações adicionais...">
                    </div>
                </div>

                <div class="d-flex gap-2 mt-2">
                    <button type="submit" class="btn btn-primary">Gerar Parcelas Automaticamente</button>
                    <a href="/parcelamentos" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Formata moeda BR
function parseMoedaBR(str) {
    if (!str) return 0;
    return parseFloat(str.replace(/\./g, '').replace(',', '.')) || 0;
}

function formatMoedaBR(val) {
    return 'R$ ' + val.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// Adiciona meses a uma data no formato YYYY-MM-DD
function addMonths(dateStr, months) {
    if (!dateStr) return null;
    const [y, m, d] = dateStr.split('-').map(Number);
    const dt  = new Date(y, m - 1 + months, 1);
    const dia = Math.min(d, new Date(dt.getFullYear(), dt.getMonth() + 1, 0).getDate());
    return `${dt.getFullYear()}-${String(dt.getMonth()+1).padStart(2,'0')}-${String(dia).padStart(2,'0')}`;
}

function formatDataBR(dateStr) {
    if (!dateStr) return '—';
    const [y,m,d] = dateStr.split('-');
    return `${d}/${m}/${y}`;
}

// Calcula data da primeira parcela client-side — espelha CartaoModel::calcularDataPrimeiraParcela
function calcularPrimeiraParcela(dataCompra, cartaoSelect) {
    if (!dataCompra) return null;
    const override = document.getElementById('data_primeira_parcela').value;
    if (override) return override;

    const opt = cartaoSelect.options[cartaoSelect.selectedIndex];
    const diaFechamento = parseInt(opt?.dataset?.fechamento || '0');
    const diaVencimento = parseInt(opt?.dataset?.vencimento || '0');

    const [y, m, d] = dataCompra.split('-').map(Number);
    const diaCompra = d;

    if (!diaFechamento) {
        // Sem cartão: próximo mês, mesmo dia
        const next = new Date(y, m, 1);
        return `${next.getFullYear()}-${String(next.getMonth()+1).padStart(2,'0')}-01`;
    }

    // Etapa 1: em qual ciclo a compra entra (se diaCompra < diaFechamento → mesmo mês; senão → mês seguinte)
    let refMes = diaCompra < diaFechamento ? new Date(y, m - 1, 1) : new Date(y, m, 1);

    // Etapa 2: em que mês esse ciclo vence (se fechamento >= vencimento → vence no mês seguinte ao fechamento)
    if (diaFechamento >= diaVencimento) {
        refMes = new Date(refMes.getFullYear(), refMes.getMonth() + 1, 1);
    }

    const diasRefMes = new Date(refMes.getFullYear(), refMes.getMonth() + 1, 0).getDate();
    const dia = Math.min(diaVencimento, diasRefMes);
    return `${refMes.getFullYear()}-${String(refMes.getMonth()+1).padStart(2,'0')}-${String(dia).padStart(2,'0')}`;
}

function atualizarPreview() {
    const total    = parseMoedaBR(document.getElementById('valor_total').value);
    const nParc    = parseInt(document.getElementById('numero_parcelas').value) || 0;
    const compra   = document.getElementById('data_compra').value;
    const cartaoSel= document.getElementById('cartao_id');
    const preview  = document.getElementById('preview-parcela');
    const previewTx= document.getElementById('preview-texto');
    const prevPrim = document.getElementById('preview-primeira');
    const prevUlt  = document.getElementById('preview-ultima');

    if (!total || !nParc || nParc < 2) {
        preview.style.display = 'none';
        return;
    }

    const valorParc = Math.floor((total / nParc) * 100) / 100;
    const valorUlt  = Math.round((total - valorParc * (nParc - 1)) * 100) / 100;

    let previewTxt = `${nParc}x de ${formatMoedaBR(valorParc)}`;
    if (valorUlt !== valorParc) {
        previewTxt += ` (última: ${formatMoedaBR(valorUlt)})`;
    }
    previewTxt += ` = ${formatMoedaBR(total)}`;
    previewTx.textContent = previewTxt;

    // Calcula datas
    const primeira = calcularPrimeiraParcela(compra, cartaoSel);
    if (primeira) {
        prevPrim.textContent = formatDataBR(primeira);
        const ultima = addMonths(primeira, nParc - 1);
        prevUlt.textContent  = formatDataBR(ultima);
    } else {
        prevPrim.textContent = '—';
        prevUlt.textContent  = '—';
    }

    preview.style.display = '';
}

document.addEventListener('DOMContentLoaded', atualizarPreview);
</script>
