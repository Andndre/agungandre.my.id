import fs from 'node:fs/promises';
import {
    connect,
    navigate,
    pause,
    screenshot,
    layoutAudit,
    observerScript,
    waitFor,
} from './cdp.mjs';
const base = process.argv[3] || 'http://127.0.0.1:8010';
const phase = process.argv[2] || 'public';
const client = await connect(true);
const report = [];
let boot;
const widths = [360, 390, 768, 1024, 1440];
async function appearance(theme) {
    if (boot) {
        await client.call('Page.removeScriptToEvaluateOnNewDocument', {
            identifier: boot,
        });
    }

    boot = (
        await client.call('Page.addScriptToEvaluateOnNewDocument', {
            source:
                observerScript +
                "localStorage.setItem('appearance'," +
                JSON.stringify(theme) +
                ");localStorage.setItem('portfolio:intro:v1','seen');",
        })
    ).identifier;
}
async function capture(name, url) {
    for (const theme of ['light', 'dark']) {
        await appearance(theme);

        for (const width of widths) {
            await client.call('Emulation.setDeviceMetricsOverride', {
                width,
                height: 900,
                deviceScaleFactor: 1,
                mobile: width < 768,
            });
            await navigate(client, base + url);
            await pause(300);
            await client.evaluate(
                'new Promise(async resolve => { for (let y=0; y<document.documentElement.scrollHeight; y+=750) { window.scrollTo(0,y); await new Promise(r=>setTimeout(r,90)); } window.scrollTo(0,0); resolve(); })',
            );
            await pause(350);
            const audit = JSON.parse(await client.evaluate(layoutAudit));

            if (
                audit.title?.includes('Exception') ||
                audit.title?.includes('Server Error')
            ) {
                throw new Error('Unexpected error page: ' + name);
            }

            const file =
                'storage/app/qa/' + name + '-' + theme + '-' + width + '.png';
            await screenshot(client, file);
            report.push({ name, theme, width, ...audit, file });

            if (width === 390 || width === 1440) {
                await fs.mkdir('docs/screenshots', { recursive: true });
                await fs.copyFile(
                    file,
                    'docs/screenshots/' +
                        name +
                        '-' +
                        theme +
                        '-' +
                        width +
                        '.png',
                );
            }
        }
    }

    console.log('Captured ' + name);
}

try {
    await client.call('Network.setCacheDisabled', { cacheDisabled: false });

    if (phase === 'public') {
        await client.call('Network.clearBrowserCookies');
        await capture('qa-home', '/');
        await capture('login', '/login');
        await capture('qa-project', '/projects/qa-orbit-workspace');
        await capture('qa-writing', '/blog');
        await capture('qa-article', '/blog/qa-published-notes');
        await capture('qa-long-title', '/projects/qa-scheduling-workspace');
        await capture('qa-missing-image', '/projects/qa-missing-image');
    } else if (phase === 'article') {
        await capture('qa-article', '/blog/qa-published-notes');
    } else if (phase === 'empty') {
        await client.call('Network.clearBrowserCookies');
        await capture('empty-home', '/');
        await capture('empty-writing', '/blog');
    } else if (phase === 'cms' || phase === 'editor') {
        await appearance('light');
        await navigate(client, base + '/login');

        if (
            await client.evaluate("Boolean(document.querySelector('#email'))")
        ) {
            await client.evaluate(
                "document.querySelector('#email').value='qa-owner@example.test';document.querySelector('#password').value='qa-preview-password';document.querySelector('form').requestSubmit();",
            );
            await waitFor(client, "location.pathname === '/admin'");
        }

        if (phase === 'cms') {
            await capture('qa-dashboard', '/admin');
            await capture('qa-admin-projects', '/admin/projects');
            await capture('qa-project-editor', '/admin/projects/1/edit');
            await capture('qa-admin-writing', '/admin/blog');
        }

        await capture('qa-article-editor', '/admin/blog/1/edit');
    } else {
        throw new Error('Unknown phase');
    }

    await fs.writeFile(
        'storage/app/qa/' + phase + '-matrix.json',
        JSON.stringify(report, null, 2),
    );
    console.log(
        JSON.stringify({
            screens: report.length,
            overflow: report
                .filter((row) => row.scrollWidth > row.width)
                .map((row) => ({
                    name: row.name,
                    theme: row.theme,
                    width: row.width,
                    scrollWidth: row.scrollWidth,
                    overflow: row.overflow,
                })),
            errors: report
                .filter((row) => row.errors.length)
                .map((row) => ({ name: row.name, errors: row.errors })),
        }),
    );
} finally {
    if (boot) {
        await client.call('Page.removeScriptToEvaluateOnNewDocument', {
            identifier: boot,
        });
    }

    client.close();
}
