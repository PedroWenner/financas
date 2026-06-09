<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?= e($titulo ?? 'AlcatechFinanças') ?> — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#2563eb">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Alcatech Finanças">
    <link rel="apple-touch-icon" href="/assets/icons/icon-192.png">
    <style>
        body { display: flex; align-items: center; justify-content: center; min-height: 100vh; background: var(--bg); }
        .auth-wrapper { width: 100%; max-width: 420px; padding: 20px; }
        .auth-logo { text-align: center; margin-bottom: 32px; }
        .auth-logo h1 { font-size: 1.8rem; font-weight: 800; color: var(--text); }
        .auth-logo h1 span { color: var(--primary); }
        .auth-logo p { color: var(--text-muted); font-size: .9rem; margin-top: 4px; }
        .auth-card { background: var(--surface); border-radius: var(--radius-lg); padding: 32px; box-shadow: var(--shadow-lg); border: 1px solid var(--border); }
        .auth-card h2 { font-size: 1.2rem; margin-bottom: 6px; }
        .auth-card .sub { color: var(--text-muted); font-size: .875rem; margin-bottom: 24px; }
        .auth-footer { text-align: center; margin-top: 20px; font-size: .875rem; color: var(--text-muted); }
    </style>
    <script>
        (function(){
            const t = localStorage.getItem('tema') || 'light';
            document.documentElement.setAttribute('data-theme', t);
        })();
    </script>
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-logo">
        <h1>Alcatech<span>Finanças</span></h1>
        <p>Controle financeiro inteligente</p>
    </div>

    <?php if ($flash = Response::getFlash()): ?>
        <div class="alert alert-<?= e($flash['tipo']) ?>">
            <?= e($flash['mensagem']) ?>
            <button class="alert-close" type="button">×</button>
        </div>
    <?php endif; ?>

    <?php echo $content; ?>
</div>
<script src="/assets/js/app.js"></script>
<script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js');
    }
</script>
</body>
</html>
