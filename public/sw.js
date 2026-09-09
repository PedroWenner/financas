const CACHE_VERSION = 'v7';
const SHELL_CACHE   = 'shell-' + CACHE_VERSION;
const STATIC_ASSETS = [
    '/assets/css/app.css',
    '/assets/js/app.js',
    '/assets/js/chat-widget.js',
    '/assets/icons/icon-192.png',
    '/assets/icons/icon-512.png',
    '/manifest.json',
    '/offline'
];

// Instala e cacheia o app shell
self.addEventListener('install', function (e) {
    e.waitUntil(
        caches.open(SHELL_CACHE).then(function (cache) {
            return cache.addAll(STATIC_ASSETS.filter(function (url) {
                // Ignora assets que podem não existir ainda
                return !url.includes('/offline');
            }));
        }).then(function () {
            return self.skipWaiting();
        })
    );
});

// Remove caches antigos ao ativar
self.addEventListener('activate', function (e) {
    e.waitUntil(
        caches.keys().then(function (keys) {
            return Promise.all(
                keys.filter(function (key) {
                    return key !== SHELL_CACHE;
                }).map(function (key) {
                    return caches.delete(key);
                })
            );
        }).then(function () {
            return self.clients.claim();
        })
    );
});

// Estratégia: Network First para HTML, Cache First para assets estáticos
self.addEventListener('fetch', function (e) {
    var req = e.request;
    var url = new URL(req.url);

    // Ignora requisições não-GET e cross-origin
    if (req.method !== 'GET' || url.origin !== location.origin) return;

    // Assets estáticos (CSS, JS, imagens): Cache First
    if (url.pathname.startsWith('/assets/') || url.pathname === '/manifest.json') {
        e.respondWith(
            caches.match(req).then(function (cached) {
                return cached || fetch(req).then(function (res) {
                    var clone = res.clone();
                    caches.open(SHELL_CACHE).then(function (c) { c.put(req, clone); });
                    return res;
                });
            })
        );
        return;
    }

    // Páginas HTML: Network First com fallback para offline
    e.respondWith(
        fetch(req).catch(function () {
            return caches.match('/offline') || new Response(
                '<html><body style="font-family:sans-serif;text-align:center;padding:60px">' +
                '<h2>Sem conexão</h2><p>Verifique sua internet e tente novamente.</p></body></html>',
                { headers: { 'Content-Type': 'text/html; charset=utf-8' } }
            );
        })
    );
});
