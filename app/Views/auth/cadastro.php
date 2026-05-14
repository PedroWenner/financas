<div class="auth-card">
    <h2>Criar sua conta</h2>
    <p class="sub">Comece a controlar suas finanças hoje</p>

    <form method="POST" action="/cadastro" novalidate>
        <?= CsrfHelper::field() ?>

        <div class="form-group">
            <label for="nome">Nome completo</label>
            <input type="text" id="nome" name="nome" class="form-control <?= !empty($errors['nome']) ? 'is-invalid' : '' ?>"
                   value="<?= e($old['nome'] ?? '') ?>" placeholder="Pedro Wenner" required autofocus>
            <?php if (!empty($errors['nome'])): ?>
                <div class="invalid-feedback"><?= e($errors['nome']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" class="form-control <?= !empty($errors['email']) ? 'is-invalid' : '' ?>"
                   value="<?= e($old['email'] ?? '') ?>" placeholder="seu@email.com" required>
            <?php if (!empty($errors['email'])): ?>
                <div class="invalid-feedback"><?= e($errors['email']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="senha">Senha <small style="color:var(--text-muted)">(mínimo 6 caracteres)</small></label>
            <div style="position:relative;">
                <input type="password" id="senha" name="senha" class="form-control <?= !empty($errors['senha']) ? 'is-invalid' : '' ?>"
                       placeholder="••••••••" required minlength="6">
                <button type="button" onclick="toggleSenha('senha')" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-muted);">👁</button>
            </div>
            <?php if (!empty($errors['senha'])): ?>
                <div class="invalid-feedback"><?= e($errors['senha']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="senha_confirmacao">Confirmar senha</label>
            <div style="position:relative;">
                <input type="password" id="senha_confirmacao" name="senha_confirmacao"
                       class="form-control <?= !empty($errors['senha_confirmacao']) ? 'is-invalid' : '' ?>"
                       placeholder="••••••••" required>
                <button type="button" onclick="toggleSenha('senha_confirmacao')" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-muted);">👁</button>
            </div>
            <?php if (!empty($errors['senha_confirmacao'])): ?>
                <div class="invalid-feedback"><?= e($errors['senha_confirmacao']) ?></div>
            <?php endif; ?>
        </div>

        <div class="mt-2">
            <button type="submit" class="btn btn-primary btn-block btn-lg">
                Criar conta
            </button>
        </div>
    </form>
</div>

<div class="auth-footer">
    Já tem conta? <a href="/login">Entrar</a>
</div>

<script>
function toggleSenha(id) {
    const input = document.getElementById(id);
    input.type = input.type === 'password' ? 'text' : 'password';
}
</script>
