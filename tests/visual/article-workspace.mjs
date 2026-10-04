import assert from 'node:assert/strict';
import fs from 'node:fs/promises';
import { connect, navigate, pause, waitFor, observerScript } from './cdp.mjs';

const client = await connect(true);
const base = 'http://127.0.0.1:8010';
const results = [];
const content = [
    '## Round trip',
    '',
    '**Bold outside** and *italic* and [link](https://example.com).',
    '',
    '![QA image](/.qa/projects/qa-orbit.png "QA caption")',
    '',
    '````callout:info',
    '**Note bold** and *note italic* and [trusted](https://example.com).',
    '',
    'Second paragraph.',
    '',
    '- One',
    '- Two',
    '',
    '```js',
    'const value = 1;',
    '```',
    '````',
    '',
    '```callout:warning',
    '**Warning bold**',
    '',
    'Warning paragraph.',
    '```',
    '',
    '| Name | Value |',
    '| --- | --- |',
    '| QA | 1 |',
    '',
    '```embed',
    'https://youtu.be/dQw4w9WgXcQ',
    '```',
    '',
].join('\n');

async function verify(name, expression) {
    const passed = await client.evaluate(expression);
    results.push({ name, passed });
    assert.equal(passed, true, name);
    console.log('PASS ' + name);
}

async function input(selector, value) {
    await client.evaluate(
        `(() => { const el=document.querySelector(${JSON.stringify(selector)}); el.value=${JSON.stringify(value)}; el.dispatchEvent(new Event('input', {bubbles:true})); el.dispatchEvent(new Event('change', {bubbles:true})); })()`,
    );
}

async function button(label) {
    await client.evaluate(
        `(() => { const el=[...document.querySelectorAll('button')].find(el=>el.textContent.trim()===${JSON.stringify(label)} && !el.disabled); if(!el) throw Error('Missing button: ' + ${JSON.stringify(label)}); el.focus(); el.click(); })()`,
    );
    await pause(100);
}

async function escape() {
    await client.call('Input.dispatchKeyEvent', {
        type: 'keyDown',
        key: 'Escape',
        code: 'Escape',
    });
    await client.call('Input.dispatchKeyEvent', {
        type: 'keyUp',
        key: 'Escape',
        code: 'Escape',
    });
    await waitFor(client, "!document.querySelector('[role=dialog]')");
    await pause(150);
}

async function pendingRequest(from) {
    for (let i = 0; i < 120; i++) {
        const event = client.events
            .slice(from)
            .find((event) => event.method === 'Fetch.requestPaused');

        if (event) {
            return event.params;
        }

        await pause(100);
    }

    throw Error('Request was not intercepted');
}

async function intercept(pattern) {
    await client.call('Fetch.enable', {
        patterns: [{ urlPattern: pattern, requestStage: 'Request' }],
    });

    return client.events.length;
}

try {
    await client.call('Page.addScriptToEvaluateOnNewDocument', {
        source: observerScript,
    });
    await client.call('Emulation.setDeviceMetricsOverride', {
        width: 1440,
        height: 900,
        deviceScaleFactor: 1,
        mobile: false,
    });
    await navigate(client, base + '/login');

    if (await client.evaluate("Boolean(document.querySelector('#email'))")) {
        await client.evaluate(
            "document.querySelector('#email').value='qa-owner@example.test';document.querySelector('#password').value='qa-preview-password';document.querySelector('form').requestSubmit();",
        );
        await waitFor(client, "location.pathname === '/admin'");
    }

    await navigate(client, base + '/admin/blog/1/edit');
    await waitFor(
        client,
        "Boolean(document.querySelector('.ProseMirror'))",
        20000,
    );
    await verify(
        'initial editor stays clean after Markdown normalization',
        "document.querySelector('.article-actionbar [role=status]').textContent.includes('Tidak ada perubahan')",
    );
    await verify(
        'original callout has formatted bold, not Markdown markers',
        "document.querySelector('[data-callout-content] strong').textContent==='QA note:' && !document.querySelector('[data-callout-content]').textContent.includes('**')",
    );

    await button('Markdown');
    await input('#post-markdown', content);
    await button('Visual');
    await verify(
        'callout preserves marks, multiple paragraphs, lists, and code',
        "Boolean(document.querySelector('.blog-callout-info [data-callout-content] strong')) && Boolean(document.querySelector('.blog-callout-info em')) && document.querySelectorAll('.blog-callout-info li').length===2 && document.querySelector('.blog-callout-info').textContent.includes('const value = 1;') && Boolean(document.querySelector('.blog-callout-warning [data-callout-content] strong'))",
    );
    await verify(
        'image, table, and YouTube survive source import',
        "Boolean(document.querySelector('.ProseMirror img[src=\"/.qa/projects/qa-orbit.png\"]')) && Boolean(document.querySelector('.ProseMirror table')) && Boolean(document.querySelector('.ProseMirror iframe'))",
    );
    await button('Markdown');
    await verify(
        'source serialization retains custom fences and formatting',
        "['callout:info','callout:warning','**Note bold**','*note italic*','Second paragraph.','const value = 1;','```embed','QA caption','![QA image]'].every(text=>document.querySelector('#post-markdown').value.includes(text))",
    );
    const sourceBefore = await client.evaluate(
        "document.querySelector('#post-markdown').value",
    );
    await button('Visual');
    await button('Markdown');
    assert.equal(
        await client.evaluate("document.querySelector('#post-markdown').value"),
        sourceBefore,
        'repeated mode switches stabilize',
    );
    await verify(
        'mode switching retains unsaved state',
        "document.querySelector('.article-actionbar [role=status]').textContent.includes('Perubahan belum disimpan')",
    );

    await button('Visual');

    for (const [label, variant] of [
        ['Catatan', 'info'],
        ['Peringatan', 'warning'],
    ]) {
        const count = await client.evaluate(
            `document.querySelectorAll('.blog-callout-${variant}').length`,
        );
        await button('Tambah blok');
        await waitFor(
            client,
            "Boolean(document.querySelector('.milkdown-slash-menu [data-index]'))",
        );
        await verify(
            label + ' menu icon keeps its Lucide outline',
            `(() => { const el=[...document.querySelectorAll('.milkdown-slash-menu [data-index]')].find(el=>el.textContent.trim()===${JSON.stringify(label)}); const s=getComputedStyle(el.querySelector('svg')); return s.fill==='none' && s.stroke!=='none'; })()`,
        );
        await client.evaluate(
            `(() => { const el=[...document.querySelectorAll('.milkdown-slash-menu [data-index]')].find(el=>el.textContent.trim()===${JSON.stringify(label)}); el.dispatchEvent(new PointerEvent('pointerup', {bubbles:true})); })()`,
        );
        await waitFor(
            client,
            `document.querySelectorAll('.blog-callout-${variant}').length===${count + 1}`,
        );
        await verify(
            label + ' inserts an editable, padded container',
            `(() => { const el=[...document.querySelectorAll('.blog-callout-${variant}')].at(-1); return Boolean(el.querySelector('[data-callout-content] p')) && parseFloat(getComputedStyle(el).paddingLeft)>=16 && document.activeElement.classList.contains('ProseMirror'); })()`,
        );
    }

    await button('Tambah blok');
    await waitFor(
        client,
        "Boolean(document.querySelector('.milkdown-slash-menu [data-index]'))",
    );
    await verify(
        'custom blocks share built-in block menu',
        "['Catatan','Peringatan','Sematan','Gambar','Tabel'].every(text=>[...document.querySelectorAll('.milkdown-slash-menu [data-index]')].some(el=>el.textContent.trim()===text))",
    );
    await client.evaluate(
        "document.querySelector('.milkdown-slash-menu .menu-group:last-child').scrollIntoView({block:'nearest'})",
    );
    await pause(150);
    await verify(
        'block menu remains clickable above the sticky actionbar',
        "(() => { const menu=document.querySelector('.milkdown-slash-menu'); const r=menu.getBoundingClientRect(); return Boolean(document.elementFromPoint(r.x+r.width/2, Math.max(0,r.y)+30)?.closest('.milkdown-slash-menu')); })()",
    );
    await client.evaluate(
        "(() => { const el=[...document.querySelectorAll('.milkdown-slash-menu [data-index]')].find(el=>el.textContent.trim()==='Sematan'); el.dispatchEvent(new PointerEvent('pointerup', {bubbles:true})); })()",
    );
    await waitFor(client, "Boolean(document.querySelector('#embed-url'))");
    await input('#embed-url', 'http://example.com');
    await button('Tambahkan');
    await verify(
        'embed rejects HTTP inline without closing dialog',
        "document.querySelector('#embed-url').getAttribute('aria-invalid')==='true' && Boolean(document.querySelector('#embed-url-error'))",
    );
    await input('#embed-url', 'https://example.com/page');
    await button('Tambahkan');
    await waitFor(client, "!document.querySelector('#embed-url')");
    await verify(
        'embed restores document focus and inserts at selection',
        "Boolean(document.activeElement.closest('.ProseMirror')) && Boolean(document.querySelector('.ProseMirror a[href=\"https://example.com/page\"]'))",
    );
    await verify(
        'embed uses a trusted Lucide SVG with an escaped link',
        "(() => { const link=document.querySelector('.ProseMirror a[href=\"https://example.com/page\"]'); const icon=link.querySelector('svg'); return icon instanceof SVGElement && icon.getAttribute('aria-hidden')==='true' && icon.querySelectorAll('path').length===2 && !link.textContent.includes('↗'); })()",
    );

    await button('Tambah blok');
    await waitFor(
        client,
        "Boolean(document.querySelector('.milkdown-slash-menu [data-index]'))",
    );
    await client.evaluate(
        "(() => { const el=[...document.querySelectorAll('.milkdown-slash-menu [data-index]')].find(el=>el.textContent.trim()==='Gambar'); el.dispatchEvent(new PointerEvent('pointerup', {bubbles:true})); })()",
    );
    await waitFor(
        client,
        "Boolean(document.querySelector('.milkdown-image-block input[type=file]'))",
    );
    await client.call('DOM.enable');
    const documentNode = await client.call('DOM.getDocument');
    const fileInput = await client.call('DOM.querySelector', {
        nodeId: documentNode.root.nodeId,
        selector: '.milkdown-image-block input[type=file]',
    });
    const uploadFile = process.cwd() + '/public/.qa/projects/qa-orbit.png';
    let from = await intercept('*8010/admin/blog/images');
    await client.call('DOM.setFileInputFiles', {
        nodeId: fileInput.nodeId,
        files: [uploadFile],
    });
    let request = await pendingRequest(from);
    await client.call('Fetch.fulfillRequest', {
        requestId: request.requestId,
        responseCode: 422,
        responseHeaders: [{ name: 'Content-Type', value: 'application/json' }],
        body: Buffer.from(
            JSON.stringify({
                message: 'QA upload rejected',
                errors: { image: ['QA invalid file'] },
            }),
        ).toString('base64'),
    });
    await waitFor(
        client,
        "document.querySelector('.article-editor').textContent.includes('Gambar gagal diunggah')",
    );
    await verify(
        'upload failure keeps document and allows selecting same file again',
        "Boolean(document.querySelector('.blog-callout-info [data-callout-content] strong')) && document.querySelector('.milkdown-image-block input[type=file]').value===''",
    );
    from = client.events.length;
    await client.call('DOM.setFileInputFiles', {
        nodeId: fileInput.nodeId,
        files: [uploadFile],
    });
    request = await pendingRequest(from);
    await client.call('Fetch.continueRequest', {
        requestId: request.requestId,
    });
    await client.call('Fetch.disable');
    await waitFor(
        client,
        'Boolean(document.querySelector(\'.ProseMirror img[src*="/.qa/blog/images/"]\'))',
    );
    await verify(
        'upload retry inserts image and clears error',
        "!document.querySelector('.article-editor').textContent.includes('Gambar gagal diunggah') && document.querySelectorAll('.ProseMirror img').length===2",
    );

    from = await intercept('*8010/admin/blog/preview');
    await button('Preview');
    request = await pendingRequest(from);
    await client.call('Fetch.fulfillRequest', {
        requestId: request.requestId,
        responseCode: 422,
        responseHeaders: [{ name: 'Content-Type', value: 'application/json' }],
        body: Buffer.from(
            JSON.stringify({
                message: 'QA preview error',
                errors: { content: ['QA preview rejected'] },
            }),
        ).toString('base64'),
    });
    await waitFor(
        client,
        "document.querySelector('[role=dialog]').textContent.includes('QA preview rejected')",
    );
    await client.call('Fetch.disable');
    await button('Coba lagi');
    await waitFor(
        client,
        "Boolean(document.querySelector('[role=dialog] .studio-prose'))",
    );
    await verify(
        'server preview renders rich callout and supports retry',
        "Boolean(document.querySelector('[role=dialog] .blog-callout-info strong')) && Boolean(document.querySelector('[role=dialog] .blog-callout-info ul'))",
    );
    await escape();
    await verify(
        'preview returns focus and preserves changes',
        "document.activeElement.textContent.trim()==='Preview' && document.querySelector('.article-actionbar [role=status]').textContent.includes('Perubahan belum disimpan')",
    );

    await client.call('Emulation.setDeviceMetricsOverride', {
        width: 390,
        height: 844,
        deviceScaleFactor: 1,
        mobile: true,
    });
    await pause(150);
    await client.evaluate(
        'document.querySelector(\'button[aria-label="Pengaturan artikel"]\').click()',
    );
    await waitFor(
        client,
        "Boolean(document.querySelector('[role=dialog] #post-excerpt'))",
    );
    await input('#post-excerpt', 'QA excerpt retained after closing settings');
    await escape();
    await verify(
        'settings closes with focus return',
        "document.activeElement.getAttribute('aria-label')==='Pengaturan artikel'",
    );
    await input('#post-title', 'QA submitted article title');
    from = await intercept('*8010/admin/blog/1');
    await button('Simpan artikel');
    request = await pendingRequest(from);
    const payload = JSON.parse(request.request.postData);
    assert.equal(
        payload.excerpt,
        'QA excerpt retained after closing settings',
        'closed drawer retains payload',
    );
    assert.ok(
        payload.content.includes('**Note bold**'),
        'saved payload preserves callout formatting',
    );
    await input('#post-title', 'QA title typed during saving');
    await client.call('Fetch.continueRequest', {
        requestId: request.requestId,
    });
    await client.call('Fetch.disable');
    await waitFor(
        client,
        "Boolean([...document.querySelectorAll('button')].find(el=>el.textContent.trim()==='Simpan artikel' && !el.disabled))",
    );
    await verify(
        'update remains in editor and preserves edits made during saving',
        "location.pathname==='/admin/blog/1/edit' && document.querySelector('#post-title').value==='QA title typed during saving' && document.querySelector('.article-actionbar [role=status]').textContent.includes('Perubahan belum disimpan')",
    );
    await button('Simpan artikel');
    await waitFor(
        client,
        "document.querySelector('.article-actionbar [role=status]').textContent.includes('Artikel tersimpan')",
    );
    await client.call('Emulation.setDeviceMetricsOverride', {
        width: 720,
        height: 450,
        deviceScaleFactor: 2,
        mobile: false,
    });
    await pause(150);
    await verify(
        '200% desktop zoom reflows without overflow and retains settings access',
        "document.documentElement.scrollWidth<=innerWidth && Boolean(document.querySelector('button[aria-label=\"Pengaturan artikel\"]')) && document.querySelector('.article-title').getBoundingClientRect().width>0",
    );
    await navigate(client, base + '/admin/blog/1/edit');
    await waitFor(
        client,
        "Boolean(document.querySelector('.ProseMirror'))",
        20000,
    );
    await verify(
        'saved content reopens with formatting, blocks, and latest title',
        "document.querySelector('#post-title').value==='QA title typed during saving' && Boolean(document.querySelector('.blog-callout-info [data-callout-content] strong')) && Boolean(document.querySelector('.blog-callout-warning')) && Boolean(document.querySelector('.ProseMirror table')) && Boolean(document.querySelector('.ProseMirror img')) && Boolean(document.querySelector('.ProseMirror iframe'))",
    );
    await input('#post-title', 'QA retry title');
    from = await intercept('*8010/admin/blog/1');
    await button('Simpan artikel');
    request = await pendingRequest(from);
    await client.call('Fetch.fulfillRequest', {
        requestId: request.requestId,
        responseCode: 500,
        responseHeaders: [{ name: 'Content-Type', value: 'application/json' }],
        body: Buffer.from(
            JSON.stringify({ message: 'QA server failure' }),
        ).toString('base64'),
    });
    await waitFor(
        client,
        "document.body.textContent.includes('Artikel gagal disimpan')",
    );
    await client.call('Fetch.disable');
    await verify(
        'server failure retains input and unsaved status',
        "document.querySelector('#post-title').value==='QA retry title' && document.querySelector('.article-actionbar [role=status]').textContent.includes('Perubahan belum disimpan')",
    );
    from = await intercept('*8010/admin/blog/1');
    await button('Simpan artikel');
    request = await pendingRequest(from);
    await client.call('Fetch.failRequest', {
        requestId: request.requestId,
        errorReason: 'InternetDisconnected',
    });
    await waitFor(
        client,
        "document.body.textContent.includes('Koneksi terputus')",
    );
    await client.call('Fetch.disable');
    await verify(
        'network failure retains document for retry',
        "document.querySelector('#post-title').value==='QA retry title' && Boolean(document.querySelector('.blog-callout-info [data-callout-content] strong'))",
    );
    await input('#post-title', '');
    await button('Simpan artikel');
    await waitFor(
        client,
        "document.querySelector('#post-title').getAttribute('aria-invalid')==='true'",
    );
    await verify(
        'validation retains document and associates title error',
        "Boolean(document.querySelector('#post-title-error').textContent.trim()) && Boolean(document.querySelector('.blog-callout-info [data-callout-content] strong'))",
    );
    await client.evaluate(
        'document.querySelector(\'a[aria-label="Kembali ke daftar tulisan"]\').click()',
    );
    await waitFor(client, "Boolean(document.querySelector('[role=dialog]'))");
    await button('Tetap menulis');
    await verify(
        'unsaved navigation can be cancelled',
        "location.pathname==='/admin/blog/1/edit' && document.querySelector('#post-title').value===''",
    );

    await input('#post-title', 'QA title typed during saving');
    await navigate(client, base + '/admin/blog/create');
    await waitFor(
        client,
        "Boolean(document.querySelector('.ProseMirror'))",
        20000,
    );
    await input('#post-title', 'QA new article');
    await button('Markdown');
    await input('#post-markdown', '**New draft**');
    await client.evaluate(
        'document.querySelector(\'button[aria-label="Pengaturan artikel"]\').click()',
    );
    await waitFor(client, "Boolean(document.querySelector('#post-excerpt'))");
    await input('#post-excerpt', 'QA new article excerpt');
    await escape();
    await button('Simpan artikel');
    await waitFor(
        client,
        '/^\\/admin\\/blog\\/\\d+\\/edit$/.test(location.pathname)',
    );
    await waitFor(
        client,
        "Boolean(document.querySelector('.ProseMirror'))",
        20000,
    );
    await verify(
        'create opens saved article in shared editor',
        "document.querySelector('#post-title').value==='QA new article' && document.querySelector('.ProseMirror strong').textContent==='New draft' && document.querySelector('.article-actionbar [role=status]').textContent.includes('Tidak ada perubahan')",
    );
    await client.evaluate(
        'document.querySelector(\'button[aria-label="Pengaturan artikel"]\').click()',
    );
    await waitFor(client, "Boolean(document.querySelector('#post-excerpt'))");
    await client.evaluate(
        "document.querySelector('input[value=scheduled]').click()",
    );
    await waitFor(client, "Boolean(document.querySelector('#post-schedule'))");
    await input('#post-schedule', '2020-01-01T10:00');
    await escape();
    await button('Simpan artikel');
    await waitFor(
        client,
        "Boolean(document.querySelector('[role=dialog] #schedule-error')) && document.querySelector('#post-schedule').getAttribute('aria-invalid')==='true'",
    );
    await verify(
        'hidden settings errors open drawer and retain article',
        "Boolean(document.querySelector('#schedule-error').textContent.trim()) && document.querySelector('#post-title').value==='QA new article'",
    );

    for (let i = 0; i < 12; i++) {
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
    }

    await verify(
        'settings contains keyboard focus',
        "Boolean(document.activeElement.closest('[role=dialog]'))",
    );
    await client.evaluate(
        "document.querySelector('input[value=draft]').click()",
    );
    await escape();
    await button('Simpan artikel');
    await waitFor(
        client,
        "document.querySelector('.article-actionbar [role=status]').textContent.includes('Artikel tersimpan')",
    );
    assert.deepEqual(
        await client.evaluate('window.__qaErrors'),
        [],
        'no workspace runtime errors',
    );
} finally {
    await client.call('Fetch.disable');
    await fs.mkdir('storage/app/qa', { recursive: true });
    await fs.writeFile(
        'storage/app/qa/article-workspace-checks.json',
        JSON.stringify(results, null, 2),
    );
    client.close();
}
