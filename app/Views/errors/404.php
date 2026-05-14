<div style="text-align:center;padding:80px 20px;">
    <div style="font-size:5rem;margin-bottom:16px;line-height:1;">🔍</div>
    <h1 style="font-size:2rem;font-weight:700;margin-bottom:8px;color:var(--text);">404 — Página não encontrada</h1>
    <p style="color:var(--text-muted);margin-bottom:32px;max-width:400px;margin-inline:auto;">
        A página que você procura não existe, foi movida ou o endereço está incorreto.
    </p>
    <?php if (Auth::check()): ?>
        <a href="/dashboard" class="btn btn-primary">← Voltar ao Dashboard</a>
    <?php else: ?>
        <a href="/login" class="btn btn-primary">← Ir para o Login</a>
    <?php endif; ?>
</div>
