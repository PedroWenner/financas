<div class="auth-card">
    <h2>Bem-vindo de volta</h2>
    <p class="sub">Entre com sua conta para continuar</p>

    <?php if (!empty($errors['geral'])): ?>
        <div class="alert alert-error mb-2">
            <?= e($errors['geral']) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="/login" novalidate>
        <?= CsrfHelper::field() ?>

        <div class="form-group">
            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" class="form-control <?= !empty($errors['email']) ? 'is-invalid' : '' ?>"
                   value="<?= e($old['email'] ?? '') ?>" placeholder="seu@email.com" required autofocus>
            <?php if (!empty($errors['email'])): ?>
                <div class="invalid-feedback"><?= e($errors['email']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="senha">Senha</label>
            <div style="position:relative;">
                <input type="password" id="senha" name="senha" class="form-control <?= !empty($errors['senha']) ? 'is-invalid' : '' ?>"
                       placeholder="••••••••" required>
                <button type="button" onclick="toggleSenha('senha')" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-muted);font-size:.85rem;">
                    👁
                </button>
            </div>
            <?php if (!empty($errors['senha'])): ?>
                <div class="invalid-feedback"><?= e($errors['senha']) ?></div>
            <?php endif; ?>
        </div>

        <div class="mt-2">
            <button type="submit" class="btn btn-primary btn-block btn-lg">
                Entrar
            </button>
        </div>
    </form>
</div>

<div class="auth-footer">
    Não tem conta? <a href="/cadastro">Criar conta grátis</a>
</div>

<script>
function toggleSenha(id) {
    const input = document.getElementById(id);
    input.type = input.type === 'password' ? 'text' : 'password';
}
</script>
