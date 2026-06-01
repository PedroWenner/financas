<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titulo ?? 'Dashboard') ?> — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script>
        (function(){
            const t = localStorage.getItem('tema') || 'light';
            document.documentElement.setAttribute('data-theme', t);
        })();
    </script>
</head>
<body>

<div class="sidebar-overlay"></div>

<div class="app-layout">

    <!-- ── Sidebar ── -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                Alcatech<span>Finanças</span>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section-title">Principal</div>

            <a href="/dashboard" class="nav-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                Dashboard
            </a>

            <div class="nav-section-title">Movimentações</div>

            <a href="/receitas" class="nav-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                Receitas
            </a>

            <a href="/despesas" class="nav-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/><polyline points="17 18 23 18 23 12"/></svg>
                Despesas
            </a>

            <a href="/despesas-fixas" class="nav-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/></svg>
                Despesas Fixas
            </a>

            <div class="nav-section-title">Crédito</div>

            <a href="/cartoes" class="nav-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                Cartões
            </a>

            <a href="/parcelamentos" class="nav-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                Parcelamentos
            </a>

            <div class="nav-section-title">Análise</div>

            <a href="/relatorios" class="nav-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/><line x1="2" y1="20" x2="22" y2="20"/></svg>
                Relatórios
            </a>

            <div class="nav-section-title">Configurações</div>

            <a href="/categorias" class="nav-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                Categorias
            </a>

            <a href="/perfil" class="nav-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                Meu Perfil
            </a>
        </nav>

        <div class="sidebar-footer">
            <a href="/perfil" class="sidebar-user" style="text-decoration:none;color:inherit;" title="Meu Perfil">
                <div class="user-avatar">
                    <?= strtoupper(substr(Auth::nome(), 0, 1)) ?>
                </div>
                <div class="user-info">
                    <div class="user-name"><?= e(Auth::nome()) ?></div>
                    <div class="user-email"><?= e(Auth::email()) ?></div>
                </div>
            </a>
        </div>
    </aside>

    <!-- ── Conteúdo Principal ── -->
    <div class="main-content">

        <!-- Topbar -->
        <header class="topbar">
            <div class="topbar-left">
                <button id="hamburger" class="hamburger" aria-label="Menu">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
                <span class="page-title"><?= e($titulo ?? '') ?></span>
            </div>
            <div class="topbar-right">
                <button onclick="toggleTema()" class="btn btn-secondary btn-icon" title="Alternar tema" style="font-size:1.1rem;">
                    <span id="tema-icon">🌙</span>
                </button>
                <a href="/sair" class="btn btn-secondary btn-sm" style="gap:6px;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    Sair
                </a>
            </div>
        </header>

        <!-- Corpo da Página -->
        <main class="page-body">

            <?php if ($flash = Response::getFlash()): ?>
                <script>window.__flash = <?= json_encode(['tipo' => $flash['tipo'], 'msg' => $flash['mensagem']], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
            <?php endif; ?>

            <?= $content ?>

        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<script src="/assets/js/app.js"></script>
<?php if (!empty($scripts)): ?>
    <?php foreach ($scripts as $script): ?>
        <script src="<?= e($script) ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>
<?php if (!empty($inlineJs)): ?>
    <script><?= $inlineJs ?></script>
<?php endif; ?>
</body>
</html>
