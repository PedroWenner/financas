<?php
$aba    = $aba ?? 'dados';
$errors = $errors ?? [];
?>

<div style="max-width:640px;">

    <!-- Abas -->
    <div class="d-flex gap-1 mb-3">
        <button type="button"
                class="btn btn-sm <?= $aba === 'dados' ? 'btn-primary' : 'btn-secondary' ?>"
                onclick="mostrarAba('dados')">
            👤 Dados Pessoais
        </button>
        <button type="button"
                class="btn btn-sm <?= $aba === 'senha' ? 'btn-primary' : 'btn-secondary' ?>"
                onclick="mostrarAba('senha')">
            🔒 Alterar Senha
        </button>
    </div>

    <!-- ── Aba: Dados Pessoais ── -->
    <div id="aba-dados" class="card" <?= $aba !== 'dados' ? 'style="display:none;"' : '' ?>>
        <div class="card-header">
            <span class="card-title">Dados Pessoais</span>
        </div>
        <div class="card-body">

            <!-- Avatar -->
            <div style="display:flex;align-items:center;gap:16px;margin-bottom:24px;">
                <div style="width:64px;height:64px;border-radius:50%;
                            background:var(--primary);color:#fff;
                            display:flex;align-items:center;justify-content:center;
                            font-size:1.8rem;font-weight:700;flex-shrink:0;">
                    <?= strtoupper(mb_substr($usuario['nome'] ?? 'U', 0, 1)) ?>
                </div>
                <div>
                    <div class="fw-700" style="font-size:1.1rem;"><?= e($usuario['nome'] ?? '') ?></div>
                    <div class="fs-sm text-muted"><?= e($usuario['email'] ?? '') ?></div>
                    <?php if (!empty($usuario['ultimo_login'])): ?>
                        <div class="fs-sm text-muted">Último acesso: <?= dataHoraFmt($usuario['ultimo_login']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <form method="POST" action="/perfil/atualizar" novalidate>
                <?= CsrfHelper::field() ?>

                <div class="form-group">
                    <label for="nome">Nome completo *</label>
                    <input type="text" id="nome" name="nome"
                           class="form-control <?= !empty($errors['nome']) ? 'is-invalid' : '' ?>"
                           value="<?= e($usuario['nome'] ?? '') ?>"
                           required maxlength="100" autocomplete="name">
                    <?php if (!empty($errors['nome'])): ?>
                        <div class="invalid-feedback"><?= e($errors['nome']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="email">E-mail *</label>
                    <input type="email" id="email" name="email"
                           class="form-control <?= !empty($errors['email']) ? 'is-invalid' : '' ?>"
                           value="<?= e($usuario['email'] ?? '') ?>"
                           required maxlength="150" autocomplete="email">
                    <?php if (!empty($errors['email'])): ?>
                        <div class="invalid-feedback"><?= e($errors['email']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group fs-sm text-muted">
                    Membro desde: <?= dataFmt($usuario['created_at'] ?? '') ?>
                </div>

                <button type="submit" class="btn btn-primary">Salvar Alterações</button>
            </form>
        </div>
    </div>

    <!-- ── Aba: Alterar Senha ── -->
    <div id="aba-senha" class="card" <?= $aba !== 'senha' ? 'style="display:none;"' : '' ?>>
        <div class="card-header">
            <span class="card-title">Alterar Senha</span>
        </div>
        <div class="card-body">
            <form method="POST" action="/perfil/senha" novalidate autocomplete="off">
                <?= CsrfHelper::field() ?>

                <div class="form-group">
                    <label for="senha_atual">Senha atual *</label>
                    <input type="password" id="senha_atual" name="senha_atual"
                           class="form-control <?= !empty($errors['senha_atual']) ? 'is-invalid' : '' ?>"
                           required autocomplete="current-password">
                    <?php if (!empty($errors['senha_atual'])): ?>
                        <div class="invalid-feedback"><?= e($errors['senha_atual']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="nova_senha">Nova senha *</label>
                    <input type="password" id="nova_senha" name="nova_senha"
                           class="form-control <?= !empty($errors['nova_senha']) ? 'is-invalid' : '' ?>"
                           required minlength="6" autocomplete="new-password">
                    <div class="fs-sm text-muted mt-1">Mínimo de 6 caracteres.</div>
                    <?php if (!empty($errors['nova_senha'])): ?>
                        <div class="invalid-feedback"><?= e($errors['nova_senha']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="confirmacao_senha">Confirmar nova senha *</label>
                    <input type="password" id="confirmacao_senha" name="confirmacao_senha"
                           class="form-control <?= !empty($errors['confirmacao_senha']) ? 'is-invalid' : '' ?>"
                           required autocomplete="new-password">
                    <?php if (!empty($errors['confirmacao_senha'])): ?>
                        <div class="invalid-feedback"><?= e($errors['confirmacao_senha']) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Indicador de força da senha -->
                <div class="form-group">
                    <div class="fs-sm text-muted mb-1">Força da senha</div>
                    <div style="height:6px;background:var(--border);border-radius:3px;overflow:hidden;">
                        <div id="forca-barra" style="height:100%;width:0;transition:width .3s,background .3s;border-radius:3px;"></div>
                    </div>
                    <div id="forca-texto" class="fs-sm text-muted mt-1"></div>
                </div>

                <button type="submit" class="btn btn-primary">Alterar Senha</button>
            </form>
        </div>
    </div>

</div>

<script>
function mostrarAba(aba) {
    document.getElementById('aba-dados').style.display = aba === 'dados' ? '' : 'none';
    document.getElementById('aba-senha').style.display = aba === 'senha' ? '' : 'none';

    // Atualiza aparência dos botões
    document.querySelectorAll('[onclick^="mostrarAba"]').forEach(btn => {
        const isAtivo = btn.getAttribute('onclick').includes("'" + aba + "'");
        btn.classList.toggle('btn-primary', isAtivo);
        btn.classList.toggle('btn-secondary', !isAtivo);
    });
}

// Indicador de força da senha
const novaSenhaInput = document.getElementById('nova_senha');
if (novaSenhaInput) {
    novaSenhaInput.addEventListener('input', function () {
        const val   = this.value;
        const barra = document.getElementById('forca-barra');
        const texto = document.getElementById('forca-texto');

        let score = 0;
        if (val.length >= 6)  score++;
        if (val.length >= 10) score++;
        if (/[A-Z]/.test(val)) score++;
        if (/[0-9]/.test(val)) score++;
        if (/[^A-Za-z0-9]/.test(val)) score++;

        const niveis = [
            { w: '0%',   cor: 'transparent',    label: '' },
            { w: '25%',  cor: 'var(--danger)',   label: 'Muito fraca' },
            { w: '50%',  cor: 'var(--warning)',  label: 'Fraca' },
            { w: '75%',  cor: '#f39c12',         label: 'Moderada' },
            { w: '90%',  cor: 'var(--success)',  label: 'Forte' },
            { w: '100%', cor: '#16a085',         label: 'Muito forte' },
        ];

        const n = val.length === 0 ? 0 : Math.min(score, 5);
        barra.style.width      = niveis[n].w;
        barra.style.background = niveis[n].cor;
        texto.textContent      = niveis[n].label;
    });
}
</script>
