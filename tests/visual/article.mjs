import assert from 'node:assert/strict';
import fs from 'node:fs/promises';
import { connect, navigate, waitFor, observerScript } from './cdp.mjs';

const client = await connect(true);
const base = 'http://127.0.0.1:8010';
const results = [];
const contrast = `(rootSelector) => {
    const root = document.querySelector(rootSelector);
    const canvas = document.createElement('canvas'); canvas.width = canvas.height = 1;
    const context = canvas.getContext('2d');
    const rgba = value => { context.clearRect(0, 0, 1, 1); context.fillStyle = value; context.fillRect(0, 0, 1, 1); return [...context.getImageData(0, 0, 1, 1).data]; };
    const luminance = color => color.slice(0, 3).map(v => { v /= 255; return v <= .04045 ? v / 12.92 : ((v + .055) / 1.055) ** 2.4; }).reduce((sum, v, i) => sum + v * [.2126, .7152, .0722][i], 0);
    return [...root.querySelectorAll('h1,h2,h3,p,strong,a,li,code,th,td,blockquote,button,input')].filter(el => el.getClientRects().length && el.textContent.trim()).map(el => {
        const style = getComputedStyle(el);
        const layers = []; let parent = el;
        while (parent) { layers.unshift(rgba(getComputedStyle(parent).backgroundColor)); parent = parent.parentElement; }
        const bg = layers.reduce((background, layer) => background.map((v, i) => layer[i] * layer[3] / 255 + v * (1 - layer[3] / 255)), [255, 255, 255]);
        const a = luminance(rgba(style.color)), b = luminance(bg);
        const ratio = (Math.max(a, b) + .05) / (Math.min(a, b) + .05);
        const large = parseFloat(style.fontSize) >= 24 || (parseFloat(style.fontSize) >= 18.66 && parseInt(style.fontWeight) >= 700);
        return { text: el.textContent.trim().slice(0, 50), tag: el.tagName, foreground: style.color, background: bg, ratio, minimum: large ? 3 : 4.5 };
    });
}`;

async function checkContrast(name, selector) {
    const rows = await client.evaluate(
        `(${contrast})(${JSON.stringify(selector)})`,
    );
    const failed = rows.filter((row) => row.ratio < row.minimum);
    results.push({ name, rows, failed });
    assert.ok(rows.length > 0, name + ' has content');
    assert.deepEqual(failed, [], name + ' computed contrast');
    console.log('PASS ' + name + ' (' + rows.length + ' elements)');
}

try {
    await client.call('Page.addScriptToEvaluateOnNewDocument', {
        source:
            observerScript +
            "localStorage.setItem('portfolio:intro:v1','seen');",
    });

    for (const theme of ['light', 'dark']) {
        await navigate(client, base + '/blog/qa-published-notes');
        await client.evaluate(
            `document.documentElement.classList.toggle('dark', ${theme === 'dark'})`,
        );
        await checkContrast('article ' + theme, '.studio-prose');
    }

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

    for (const theme of ['light', 'dark']) {
        await client.evaluate(
            `document.documentElement.classList.toggle('dark', ${theme === 'dark'})`,
        );
        await checkContrast('editor ' + theme, '.blog-editor');
        await client.evaluate(
            "[...document.querySelectorAll('button')].find(e => e.textContent.trim() === 'Preview').click()",
        );
        await waitFor(
            client,
            "Boolean(document.querySelector('[role=dialog] .studio-prose'))",
        );
        await checkContrast('preview ' + theme, '[role=dialog] .studio-prose');
        await client.evaluate(
            'document.querySelector(\'[role=dialog] button[aria-label="Tutup"]\').click()',
        );
        await waitFor(client, "!document.querySelector('[role=dialog]')");
    }

    assert.deepEqual(
        await client.evaluate('window.__qaErrors'),
        [],
        'no editor runtime errors',
    );
} finally {
    await fs.mkdir('storage/app/qa', { recursive: true });
    await fs.writeFile(
        'storage/app/qa/article-checks.json',
        JSON.stringify(results, null, 2),
    );
    client.close();
}
