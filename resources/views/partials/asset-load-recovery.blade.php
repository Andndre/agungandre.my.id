<style>
    #asset-load-recovery {
        --recovery-surface: var(--surface-container-lowest, #fff);
        --recovery-text: var(--on-surface, #20212a);
        --recovery-muted: var(--on-surface-variant, #60616d);
        --recovery-primary: var(--primary, #5044bd);
        --recovery-on-primary: var(--on-primary, #fff);
        --recovery-outline: var(--outline-variant, #d9d7df);
        position: fixed;
        inset: 0;
        z-index: 100;
        display: grid;
        align-content: center;
        justify-items: center;
        padding: 24px;
        overflow: auto;
        background: var(--surface, #f9f8f5);
        color: var(--recovery-text);
        font: 400 16px/1.5 var(--font-sans, ui-sans-serif, system-ui, sans-serif);
    }
    .dark #asset-load-recovery {
        --recovery-surface: var(--surface-container-lowest, #191920);
        --recovery-text: var(--on-surface, #eeeef2);
        --recovery-muted: var(--on-surface-variant, #b6b5c4);
        --recovery-primary: var(--primary, #c2b6ff);
        --recovery-on-primary: var(--on-primary, #2e2073);
        --recovery-outline: var(--outline-variant, #3b3a46);
        background: var(--surface, #15151b);
    }
    #asset-load-recovery[hidden],
    #asset-load-recovery [hidden] { display: none; }
    #asset-load-recovery[data-mode="notice"] {
        inset: 16px 16px auto auto;
        max-width: calc(100vw - 32px);
        max-height: calc(100dvh - 32px);
        padding: 0;
        background: transparent;
    }
    #asset-load-recovery .asset-recovery-panel {
        box-sizing: border-box;
        width: min(100%, 30rem);
        padding: 24px;
        border: 1px solid var(--recovery-outline);
        border-radius: 20px;
        background: var(--recovery-surface);
        overflow-wrap: anywhere;
    }
    #asset-load-recovery h2 {
        margin: 0 0 8px;
        font-size: 24px;
        line-height: 1.25;
        font-weight: 600;
        letter-spacing: -0.03em;
    }
    #asset-load-recovery p { margin: 0; color: var(--recovery-muted); }
    #asset-load-recovery #asset-recovery-unsaved { margin-top: 16px; }
    #asset-load-recovery .asset-recovery-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 24px;
    }
    #asset-load-recovery button {
        min-height: 44px;
        padding: 10px 16px;
        border: 1px solid var(--recovery-outline);
        border-radius: 12px;
        background: var(--recovery-surface);
        color: var(--recovery-text);
        font: inherit;
        font-weight: 600;
        cursor: pointer;
    }
    #asset-load-recovery #asset-recovery-reload {
        border-color: transparent;
        background: var(--recovery-primary);
        color: var(--recovery-on-primary);
    }
    #asset-load-recovery button:hover { filter: brightness(0.94); }
    #asset-load-recovery button:active { transform: translateY(1px); }
    #asset-load-recovery button:focus-visible {
        outline: 2px solid var(--recovery-primary);
        outline-offset: 4px;
    }
    #asset-load-recovery button:disabled { opacity: 0.6; cursor: wait; }
</style>
<script>
    (() => {
        const errors = new Set();
        let ready = false;
        let language = document.documentElement.lang;
        let pending = false;
        let previousFocus = null;

        const panel = () => document.getElementById('asset-load-recovery');
        const hide = () => {
            pending = false;
            const element = panel();
            if (!element) return;
            const restoreFocus = element.contains(document.activeElement);
            element.hidden = true;
            if (restoreFocus && previousFocus?.isConnected) previousFocus.focus();
        };
        const show = () => {
            pending = true;
            const element = panel();
            if (!element) return;
            const component = element.dataset.pageComponent;
            const initialPublic = component === 'Welcome' || component.startsWith('blog/') || component.startsWith('projects/');
            const english = ready ? language === 'en' : initialPublic;
            const copy = english ? {
                title: 'This page could not load',
                message: 'Wait a moment, then reload the page.',
                reload: 'Reload page',
                close: 'Dismiss',
                label: 'Page recovery',
            } : {
                title: 'Halaman belum bisa dimuat',
                message: 'Tunggu sebentar, lalu muat ulang halaman.',
                reload: 'Muat ulang',
                close: 'Tutup',
                label: 'Pemulihan halaman',
            };
            const wasHidden = element.hidden;
            element.dataset.mode = ready ? 'notice' : 'initial';
            element.setAttribute('aria-label', copy.label);
            document.getElementById('asset-recovery-title').textContent = copy.title;
            document.getElementById('asset-recovery-message').textContent = copy.message;
            document.getElementById('asset-recovery-reload').textContent = copy.reload;
            document.getElementById('asset-recovery-close').textContent = copy.close;
            const unsaved = document.getElementById('asset-recovery-unsaved');
            unsaved.hidden = !ready || !location.pathname.startsWith('/admin');
            unsaved.textContent = 'Salin perubahan yang belum disimpan sebelum memuat ulang.';
            element.hidden = false;
            if (wasHidden) previousFocus = document.activeElement;
            if (!ready && wasHidden) document.getElementById('asset-recovery-reload').focus();
        };
        window.__assetLoadRecovery = {
            hasError: (error) => errors.has(error),
            ready: () => { ready = true; language = document.documentElement.lang; hide(); },
            reset: () => { language = document.documentElement.lang; hide(); },
        };
        window.addEventListener('vite:preloadError', (event) => {
            errors.add(event.payload);
            show();
        });
        const isAppScript = (element) => {
            if (!(element instanceof HTMLScriptElement) || element.type !== 'module') return false;
            const src = element.src;
            if (!src) return false;
            try {
                const url = new URL(src, window.location.href);
                if (url.protocol !== 'http:' && url.protocol !== 'https:') return false;
                if (url.origin === window.location.origin) {
                    return url.pathname.includes('/build/') || url.pathname.includes('/@' + 'vite/');
                }
                return url.pathname.includes('/@' + 'vite/') || url.pathname.includes('/resources/');
            } catch (_) {
                return false;
            }
        };
        window.addEventListener('error', (event) => {
            if (isAppScript(event.target)) show();
        }, true);
        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('asset-recovery-reload').addEventListener('click', () => location.reload());
            document.getElementById('asset-recovery-close').addEventListener('click', hide);
            if (pending) show();
        }, { once: true });
    })();
</script>
