<?php
$mesAnterior = $mes === 1  ? ['mes' => 12, 'ano' => $ano - 1] : ['mes' => $mes - 1, 'ano' => $ano];
$mesProximo  = $mes === 12 ? ['mes' => 1,  'ano' => $ano + 1] : ['mes' => $mes + 1, 'ano' => $ano];
?>

<div class="d-flex justify-between align-center mb-3">
    <div class="d-flex align-center gap-1">
        <a href="/cartoes?mes=<?= $mesAnterior['mes'] ?>&ano=<?= $mesAnterior['ano'] ?>" class="btn btn-secondary btn-sm">‹</a>
        <span style="font-weight:600;min-width:150px;text-align:center;"><?= nomeMesAno($mes, $ano) ?></span>
        <a href="/cartoes?mes=<?= $mesProximo['mes'] ?>&ano=<?= $mesProximo['ano'] ?>" class="btn btn-secondary btn-sm">›</a>
    </div>
    <a href="/cartoes/criar" class="btn btn-primary">+ Novo Cartão</a>
</div>

<?php if (empty($cartoes)): ?>
    <div class="card">
        <div class="card-body empty-state">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
            <h3>Nenhum cartão cadastrado</h3>
            <p>Adicione seus cartões de crédito para controlar os gastos.</p>
            <div class="mt-2"><a href="/cartoes/criar" class="btn btn-primary">+ Novo Cartão</a></div>
        </div>
    </div>
<?php else: ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:20px;margin-bottom:24px;">
    <?php foreach ($cartoes as $c): ?>
        <?php
        $cor       = $c['cor'] ?: '#2563eb';
        $perc      = $c['limite'] > 0 ? min(100, ($c['total_gasto'] / $c['limite']) * 100) : 0;
        $corBarra  = corProgresso($perc);
        $bandeiras = ['visa'=>'Visa','mastercard'=>'Mastercard','elo'=>'Elo','amex'=>'Amex','hipercard'=>'Hipercard','outro'=>'Outro'];
        ?>
        <div class="card" style="overflow:visible;">
            <!-- Visual do Cartão -->
            <div class="card-visual" style="background:linear-gradient(135deg, <?= e($cor) ?>, <?= e($cor) ?>cc);margin:0;border-radius:var(--radius-md) var(--radius-md) 0 0;">
                <div style="font-size:.75rem;opacity:.8;margin-bottom:8px;"><?= e($bandeiras[$c['bandeira']] ?? 'Cartão') ?></div>
                <div style="font-size:1.2rem;font-weight:700;"><?= e($c['nome']) ?></div>
                <div style="margin-top:12px;font-size:.8rem;opacity:.8;">
                    Fecha dia <?= $c['dia_fechamento'] ?> · Vence dia <?= $c['dia_vencimento'] ?>
                </div>
            </div>

            <!-- Info -->
            <div class="card-body">
                <div class="d-flex justify-between mb-1">
                    <span class="fs-sm text-muted">Limite Total</span>
                    <span class="fw-600"><?= moeda($c['limite']) ?></span>
                </div>
                <div class="d-flex justify-between mb-1">
                    <span class="fs-sm text-muted">Utilizado em <?= nomeMes($mes) ?></span>
                    <span class="fw-600 text-danger"><?= moeda($c['total_gasto']) ?></span>
                </div>
                <div class="d-flex justify-between mb-2">
                    <span class="fs-sm text-muted">Disponível</span>
                    <span class="fw-600 text-success"><?= moeda(max(0, $c['limite_disponivel'])) ?></span>
                </div>
                <div class="progress mb-1">
                    <div class="progress-bar <?= $corBarra ?>" style="width:<?= number_format($perc, 1) ?>%"></div>
                </div>
                <div class="fs-sm text-muted text-right"><?= number_format($perc, 1) ?>% utilizado</div>

                <!-- Ações -->
                <div class="d-flex gap-1 mt-2">
                    <a href="/cartoes/fatura?id=<?= $c['id'] ?>&mes=<?= $mes ?>&ano=<?= $ano ?>" class="btn btn-outline btn-sm" style="flex:1;justify-content:center;">
                        Ver Fatura
                    </a>
                    <a href="/cartoes/editar?id=<?= $c['id'] ?>" class="btn btn-secondary btn-sm">✏️</a>
                    <form method="POST" action="/cartoes/excluir" style="display:inline;">
                        <?= CsrfHelper::field() ?>
                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm"
                                data-confirm="Remover o cartão '<?= addslashes(e($c['nome'])) ?>'?">🗑️</button>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
<?php endif; ?>
