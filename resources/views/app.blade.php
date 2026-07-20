<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Theme bootstrap: apply saved/preferred theme before paint (no FOUC) -->
        <script>
            (function () {
                try {
                    var t = localStorage.getItem('theme');
                    var dark = t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches);
                    if (dark) document.documentElement.classList.add('dark');
                } catch (e) {}
            })();
        </script>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @viteReactRefresh
        @vite('resources/js/app.tsx')
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        
        <!-- QHSSE Assistant: Floating button + modal chatbot-ui (same-origin /chatbot) -->
        <div id="qhsse-assistant" style="position:fixed;bottom:1.5rem;right:1.5rem;z-index:9999;font-family:figtree,system-ui,sans-serif;">
            <button id="qa-toggle" aria-label="Buka Asisten QHSSE"
                style="width:3.5rem;height:3.5rem;border-radius:9999px;border:none;cursor:pointer;
                       background:#2563eb;color:#fff;font-size:1.5rem;box-shadow:0 4px 14px rgba(0,0,0,.35);
                       display:flex;align-items:center;justify-content:center;transition:transform .15s;">
                &#128172;
            </button>
            <div id="qa-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:10000;
                 align-items:flex-end;justify-content:flex-end;" role="dialog" aria-modal="true">
                <div style="position:absolute;inset:0;" id="qa-backdrop"></div>
                <div style="position:relative;width:min(480px,94vw);height:min(80vh,720px);margin:1.5rem;
                            background:#fff;border-radius:0.75rem;overflow:hidden;box-shadow:0 10px 40px rgba(0,0,0,.4);
                            display:flex;flex-direction:column;">
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:.75rem 1rem;
                                background:#1e3a8a;color:#fff;">
                        <span style="font-weight:600;">Asisten QHSSE</span>
                        <button id="qa-close" aria-label="Tutup"
                            style="background:transparent;border:none;color:#fff;font-size:1.25rem;cursor:pointer;">&#10005;</button>
                    </div>
                    <iframe src="/chatbot/" title="QHSSE Assistant"
                        style="flex:1;border:none;width:100%;height:100%;"></iframe>
                </div>
            </div>
        </div>
        <script>
            (function () {
                var btn = document.getElementById('qa-toggle');
                var modal = document.getElementById('qa-modal');
                var close = document.getElementById('qa-close');
                var backdrop = document.getElementById('qa-backdrop');
                function openModal() { modal.style.display = 'flex'; btn.style.transform = 'scale(.9)'; }
                function hideModal() { modal.style.display = 'none'; btn.style.transform = 'scale(1)'; }
                btn.addEventListener('click', openModal);
                close.addEventListener('click', hideModal);
                backdrop.addEventListener('click', hideModal);
                document.addEventListener('keydown', function (e) { if (e.key === 'Escape') hideModal(); });
            })();
        </script>

        @inertia
    </body>

</html>
