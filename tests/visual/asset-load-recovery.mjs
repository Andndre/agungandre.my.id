import assert from 'node:assert/strict';
import fs from 'node:fs/promises';
import { connect, pause, waitFor } from './cdp.mjs';

const base = 'http://127.0.0.1:8010';
const manifest = JSON.parse(
    await fs.readFile('public/build/manifest.json', 'utf8'),
);
const asset = (key) => base + '/build/' + manifest[key].file;
const visible =
    "Boolean(document.getElementById('asset-load-recovery')) && !document.getElementById('asset-load-recovery').hidden";
const results = [];

async function check(client, name, expression) {
    const passed = await client.evaluate(expression);

    if (!passed) {
        console.log(
            await client.evaluate(
                "({url:location.pathname,title:document.querySelector('#asset-recovery-title')?.textContent,mode:document.querySelector('#asset-load-recovery')?.dataset.mode})",
            ),
        );
    }

    assert.equal(passed, true, name);
    results.push(name);
    console.log('PASS ' + name);
}

async function intercept(client, urlPattern, failure) {
    await client.call('Fetch.enable', {
        patterns: [{ urlPattern, requestStage: 'Request' }],
    });
    const handled = new Set();
    let running = true;
    const worker = (async () => {
        while (running) {
            for (const event of client.events) {
                if (
                    event.method !== 'Fetch.requestPaused' ||
                    handled.has(event.params.requestId)
                ) {
                    continue;
                }

                const requestId = event.params.requestId;
                handled.add(requestId);

                if (failure === 'network') {
                    await client.call('Fetch.failRequest', {
                        requestId,
                        errorReason: 'InternetDisconnected',
                    });
                } else {
                    await client.call('Fetch.fulfillRequest', {
                        requestId,
                        responseCode: failure,
                        body: '',
                    });
                }
            }

            await pause(20);
        }
    })();

    return async () => {
        running = false;
        await worker;
        await client.call('Fetch.disable');

        return handled.size;
    };
}

async function fresh(run) {
    const client = await connect(true);
    await client.call('Network.setCacheDisabled', { cacheDisabled: true });
    await client.call('Network.clearBrowserCookies');
    await client.call('Emulation.setDeviceMetricsOverride', {
        width: 1440,
        height: 900,
        deviceScaleFactor: 1,
        mobile: false,
    });

    try {
        await run(client);
    } finally {
        await client.call('Page.close');
        client.close();
    }
}

async function screenshot(client, name) {
    const capture = await client.call('Page.captureScreenshot', {
        format: 'png',
    });
    await fs.writeFile(
        'storage/app/qa/asset-recovery-' + name + '.png',
        Buffer.from(capture.data, 'base64'),
    );
}

async function noAutomaticReload(client, name) {
    const documents = () =>
        client.events.filter(
            (e) =>
                e.method === 'Network.requestWillBeSent' &&
                e.params.type === 'Document',
        ).length;
    const before = documents();
    await pause(1100);
    assert.equal(documents(), before, name + ': no automatic reload');
    results.push(name + ': no automatic reload');
}

await fs.mkdir('storage/app/qa', { recursive: true });

if (process.argv[2] !== 'navigation') {
    for (const [name, key] of [
        ['entry', 'resources/js/app.ts'],
        ['login', 'resources/js/pages/auth/Login.svelte'],
        ['layout', 'resources/js/layouts/AuthLayout.svelte'],
    ]) {
        for (const failure of [429, 404, 'network']) {
            await fresh(async (client) => {
                const release = await intercept(client, asset(key), failure);

                try {
                    await client.call('Page.navigate', {
                        url: base + '/login',
                    });
                    await waitFor(client, visible);
                    assert.ok(
                        client.events.some(
                            (event) =>
                                event.method === 'Fetch.requestPaused' &&
                                event.params.request.url === asset(key),
                        ),
                    );
                    await check(
                        client,
                        name + ' ' + failure + ' shows initial recovery',
                        "document.querySelector('#asset-load-recovery').dataset.mode==='initial' && document.querySelector('#asset-recovery-title').textContent==='Halaman belum bisa dimuat'",
                    );
                    await noAutomaticReload(client, name + ' ' + failure);
                } finally {
                    await release();
                }

                await client.evaluate(
                    "document.getElementById('asset-recovery-reload').click()",
                );
                await waitFor(
                    client,
                    "Boolean(document.querySelector('#email')) && document.getElementById('asset-load-recovery').hidden",
                );
                await check(
                    client,
                    name + ' ' + failure + ' recovers after manual reload',
                    "document.querySelectorAll('#asset-load-recovery').length===1 && document.querySelector('#password')!==null",
                );
            });
        }
    }

    await fresh(async (client) => {
        const release = await intercept(client, base + '/build/*', 429);

        try {
            await client.call('Page.navigate', { url: base + '/login' });
            await waitFor(client, visible);
            await check(
                client,
                'recovery remains styled when all bundled assets fail',
                "getComputedStyle(document.querySelector('.asset-recovery-panel')).padding==='24px' && getComputedStyle(document.querySelector('#asset-recovery-reload')).minHeight==='44px' && document.querySelector('#asset-recovery-title').textContent==='Halaman belum bisa dimuat'",
            );
        } finally {
            await release();
        }
    });

    for (const width of [390, 1440]) {
        for (const theme of ['light', 'dark']) {
            await fresh(async (client) => {
                await client.call('Emulation.setDeviceMetricsOverride', {
                    width,
                    height: 900,
                    deviceScaleFactor: 1,
                    mobile: width < 768,
                });
                await client.call('Page.addScriptToEvaluateOnNewDocument', {
                    source:
                        "localStorage.setItem('appearance'," +
                        JSON.stringify(theme) +
                        ')',
                });
                const release = await intercept(
                    client,
                    asset('resources/js/app.ts'),
                    429,
                );

                try {
                    await client.call('Page.navigate', { url: base + '/' });
                    await waitFor(client, visible);
                    await check(
                        client,
                        width + ' ' + theme + ' public recovery uses English',
                        "document.querySelector('#asset-recovery-title').textContent==='This page could not load' && document.querySelector('#asset-recovery-reload').textContent==='Reload page'",
                    );
                    await check(
                        client,
                        width +
                            ' ' +
                            theme +
                            ' controls fit with touch targets',
                        "document.documentElement.scrollWidth<=innerWidth && [...document.querySelectorAll('#asset-load-recovery button')].every(el=>el.getBoundingClientRect().height>=44)",
                    );
                    await screenshot(client, width + '-' + theme);
                    await check(
                        client,
                        'initial recovery focuses reload for keyboard users',
                        "document.activeElement.id==='asset-recovery-reload'",
                    );
                    await client.call('Input.dispatchKeyEvent', {
                        type: 'keyDown',
                        key: 'Tab',
                        code: 'Tab',
                    });
                    await client.call('Input.dispatchKeyEvent', {
                        type: 'keyUp',
                        key: 'Tab',
                        code: 'Tab',
                    });
                    await check(
                        client,
                        'dismiss is keyboard accessible',
                        "document.activeElement.id==='asset-recovery-close'",
                    );
                    // A halved CSS viewport at doubled DPR reproduces the layout at 200% desktop zoom.
                    await client.call('Emulation.setDeviceMetricsOverride', {
                        width: Math.round(width / 2),
                        height: 450,
                        deviceScaleFactor: 2,
                        mobile: false,
                    });
                    await check(
                        client,
                        width +
                            ' ' +
                            theme +
                            ' 200% zoom stays within viewport',
                        "document.querySelector('.asset-recovery-panel').getBoundingClientRect().right<=innerWidth && document.documentElement.scrollWidth<=innerWidth",
                    );
                } finally {
                    await release();
                }
            });
        }
    }

    await fresh(async (client) => {
        await client.call('Page.navigate', { url: base + '/login' });
        await waitFor(client, "Boolean(document.querySelector('#email'))");
        await check(
            client,
            'normal login hides recovery',
            '!(' + visible + ')',
        );
        await client.evaluate(`(() => {
        for (const [tag, attributes] of [['img', {}], ['link', {rel:'preload', as:'font'}], ['script', {}]]) {
            const element = document.createElement(tag);
            Object.assign(element, attributes);
            document.head.append(element);
            element.dispatchEvent(new Event('error'));
            element.remove();
        }
    })()`);
        await check(
            client,
            'image font and analytics failures do not show recovery',
            '!(' + visible + ')',
        );
        await check(
            client,
            'unrelated Inertia errors keep default handling',
            "document.dispatchEvent(new CustomEvent('inertia:networkError', {cancelable:true,detail:{error:new Error('unrelated')}}))",
        );
        await client.evaluate(`(() => {
        const error = new Error('QA module failure');
        const event = new Event('vite:preloadError'); event.payload=error; window.dispatchEvent(event);
        window.__qaHandledError = !document.dispatchEvent(new CustomEvent('inertia:networkError', {cancelable:true,detail:{error}}));
        window.dispatchEvent(event);
    })()`);
        await check(
            client,
            'handled imports cancel duplicate Inertia errors and show one panel',
            "window.__qaHandledError && document.querySelectorAll('#asset-load-recovery').length===1",
        );
        await client.evaluate(
            "document.getElementById('asset-recovery-close').click()",
        );
        await check(
            client,
            'dismiss hides notice without reloading login',
            '!(' + visible + ') && Boolean(document.querySelector("#email"))',
        );
        await client.evaluate(
            "window.dispatchEvent(new Event('vite:preloadError'))",
        );
        await check(client, 'a later failure can show recovery again', visible);
        await client.evaluate(
            'document.querySelector(\'a[href$="/forgot-password"]\').click()',
        );
        await waitFor(client, "location.pathname==='/forgot-password'");
        await check(
            client,
            'successful navigation hides recovery',
            '!(' + visible + ')',
        );
    });
}

for (const failure of [429, 404, 'network']) {
    await fresh(async (client) => {
        await client.call('Page.navigate', { url: base + '/login' });
        await waitFor(client, "Boolean(document.querySelector('#email'))");
        await client.evaluate(
            "document.querySelector('#email').value='qa-owner@example.test';document.querySelector('#password').value='qa-preview-password';document.querySelector('form').requestSubmit()",
        );
        await waitFor(
            client,
            "location.pathname==='/admin' && Boolean(document.querySelector('h1'))",
        );
        await client.call('Page.navigate', {
            url: base + '/admin/blog/1/edit',
        });
        await waitFor(
            client,
            "Boolean(document.querySelector('.ProseMirror'))",
            20000,
        );
        await client.evaluate(
            "document.querySelector('#post-title').value='Unsaved QA title';document.querySelector('#post-title').dispatchEvent(new Event('input',{bubbles:true}))",
        );
        const release = await intercept(
            client,
            asset('resources/js/pages/Admin/Projects/Index.svelte'),
            failure,
        );

        try {
            await client.evaluate(
                'document.querySelector(\'a[href$="/admin/projects"]\').click()',
            );
            await waitFor(
                client,
                "[...document.querySelectorAll('[role=dialog] button')].some(el=>el.textContent.trim()==='Tinggalkan')",
            );
            await client.evaluate(
                "[...document.querySelectorAll('[role=dialog] button')].find(el=>el.textContent.trim()==='Tinggalkan').click()",
            );
            await waitFor(client, visible);
            assert.ok(
                client.events.some(
                    (event) =>
                        event.method === 'Fetch.requestPaused' &&
                        event.params.request.url ===
                            asset(
                                'resources/js/pages/Admin/Projects/Index.svelte',
                            ),
                ),
            );
            await check(
                client,
                'navigation ' + failure + ' retains editor and unsaved input',
                "Boolean(document.querySelector('.ProseMirror')) && document.querySelector('#post-title').value==='Unsaved QA title' && document.querySelector('#asset-load-recovery').dataset.mode==='notice' && !document.querySelector('#asset-recovery-unsaved').hidden",
            );
            await check(
                client,
                'navigation ' + failure + ' has no extra error modal',
                "document.querySelectorAll('#asset-load-recovery').length===1 && !document.querySelector('[role=dialog]') && !document.querySelector('iframe')",
            );
            await noAutomaticReload(client, 'navigation ' + failure);

            if (failure === 429) {
                for (const width of [390, 1440]) {
                    for (const theme of ['light', 'dark']) {
                        await client.call(
                            'Emulation.setDeviceMetricsOverride',
                            {
                                width,
                                height: 900,
                                deviceScaleFactor: 1,
                                mobile: width < 768,
                            },
                        );
                        await client.evaluate(
                            "document.documentElement.classList.toggle('dark'," +
                                (theme === 'dark') +
                                ')',
                        );
                        await screenshot(
                            client,
                            'notice-' + width + '-' + theme,
                        );
                        await check(
                            client,
                            'notice fits ' + width + ' ' + theme,
                            "document.querySelector('.asset-recovery-panel').getBoundingClientRect().right<=innerWidth && document.querySelector('#post-title').value==='Unsaved QA title'",
                        );
                    }
                }
            }

            await client.evaluate(
                "document.querySelector('#asset-recovery-close').click()",
            );
            await check(
                client,
                'navigation ' + failure + ' dismisses without losing input',
                "document.querySelector('#asset-load-recovery').hidden && document.querySelector('#post-title').value==='Unsaved QA title'",
            );
        } finally {
            await release();
        }

        await client.evaluate(
            "[...document.querySelectorAll('a')].find(el=>el.textContent.trim()==='Ringkasan').click()",
        );
        await waitFor(
            client,
            "location.pathname==='/admin' || location.pathname==='/admin/'",
        );
        await check(
            client,
            'navigation ' + failure + ' can still visit another page',
            '!(' + visible + ')',
        );
    });
}

await fs.writeFile(
    'storage/app/qa/asset-load-recovery-results.json',
    JSON.stringify({ passed: results.length, results }, null, 2),
);
console.log('Asset recovery: ' + results.length + ' checks passed.');
