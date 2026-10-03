import fs from 'node:fs/promises';
import {
    connect,
    navigate,
    pause,
    waitFor,
    observerScript,
    screenshot,
} from './cdp.mjs';

const client = await connect(true);
const base = 'http://127.0.0.1:8010';
const results = [];
const eventSamples = [];
let injection;
async function check(name, expression) {
    const detail = await client.evaluate(expression);
    const passed = typeof detail === 'boolean' ? detail : detail.pass;
    results.push({ name, passed, detail });
    const events = await client.evaluate('window.__qaMetrics?.events ?? []');
    eventSamples.push({ name, events });
    console.log(
        (passed ? 'PASS ' : 'FAIL ') + name + ' ' + JSON.stringify(detail),
    );
}
async function boot(extra = '') {
    if (injection) {
        await client.call('Page.removeScriptToEvaluateOnNewDocument', {
            identifier: injection,
        });
    }

    injection = (
        await client.call('Page.addScriptToEvaluateOnNewDocument', {
            source: observerScript + extra,
        })
    ).identifier;
}
async function click(selector) {
    const p = await client.evaluate(
        `(() => { const e=document.querySelector(${JSON.stringify(selector)}); if(!e) throw new Error('Missing element'); e.scrollIntoView({block:'center'}); const r=e.getBoundingClientRect(); return {x:r.x+r.width/2,y:r.y+r.height/2}; })()`,
    );
    await client.call('Input.dispatchMouseEvent', {
        type: 'mousePressed',
        button: 'left',
        clickCount: 1,
        ...p,
    });
    await client.call('Input.dispatchMouseEvent', {
        type: 'mouseReleased',
        button: 'left',
        clickCount: 1,
        ...p,
    });
    await pause(100);
}
async function button(text) {
    await client.evaluate(
        `(() => { const e=[...document.querySelectorAll('button')].find(e=>e.textContent.trim()===${JSON.stringify(text)}); if(!e) throw new Error('Missing button ${text}'); e.dataset.qaButton='selected'; })()`,
    );
    await click('[data-qa-button="selected"]');
    await client.evaluate(
        "document.querySelector('[data-qa-button]')?.removeAttribute('data-qa-button')",
    );
}
async function key(key, modifiers = 0) {
    await client.call('Input.dispatchKeyEvent', {
        type: 'keyDown',
        key,
        modifiers,
        code: key,
    });
    await client.call('Input.dispatchKeyEvent', {
        type: 'keyUp',
        key,
        modifiers,
        code: key,
    });
    await pause(40);
}
async function input(selector, value) {
    await client.evaluate(
        `(() => { const e=document.querySelector(${JSON.stringify(selector)}); e.value=${JSON.stringify(value)}; e.dispatchEvent(new Event('input',{bubbles:true})); e.dispatchEvent(new Event('change',{bubbles:true})); })()`,
    );
}

try {
    await client.call('Network.clearBrowserCookies');
    await client.call('Emulation.setDeviceMetricsOverride', {
        width: 390,
        height: 844,
        deviceScaleFactor: 1,
        mobile: true,
    });
    await boot(
        "localStorage.setItem('appearance','light');localStorage.removeItem('portfolio:intro:v1');",
    );
    await navigate(client, base + '/');
    await check(
        'first visit intro, content and CTA already visible',
        "Boolean(document.querySelector('.intro-frame')) && getComputedStyle(document.querySelector('h1')).opacity==='1' && Boolean(document.querySelector('a[href=\"#work\"]'))",
    );
    await key('Escape');
    await check('Escape ends intro', "!document.querySelector('.intro-frame')");
    await boot();
    await navigate(client, base + '/');
    await check(
        'repeat visit skips intro',
        "!document.querySelector('.intro-frame')",
    );
    await boot("localStorage.removeItem('portfolio:intro:v1');");
    await navigate(client, base + '/');
    await button('Skip intro →');
    await check(
        'Skip ends intro without moving focus',
        "!document.querySelector('.intro-frame') && !document.body.style.overflow",
    );
    await client.call('Emulation.setEmulatedMedia', {
        features: [{ name: 'prefers-reduced-motion', value: 'reduce' }],
    });
    await navigate(client, base + '/');
    await check(
        'reduced motion starts at final state',
        "!document.querySelector('.intro-frame')",
    );
    await client.call('Emulation.setEmulatedMedia', { features: [] });
    await boot("localStorage.setItem('portfolio:intro:v1','seen');");
    await navigate(client, base + '/');
    await click('button[aria-label="Open navigation"]');
    await waitFor(client, "Boolean(document.querySelector('[role=dialog]'))");
    await check(
        'mobile menu is labeled',
        "Boolean(document.querySelector('[role=dialog]').getAttribute('aria-labelledby'))",
    );

    for (let i = 0; i < 12; i++) {
        await key('Tab');
    }

    await check(
        'mobile menu traps keyboard focus',
        "Boolean(document.activeElement.closest('[role=dialog]'))",
    );
    await key('Escape');
    await pause(300);
    await check(
        'mobile menu returns focus',
        "document.activeElement.getAttribute('aria-label')==='Open navigation'",
    );

    await click('[data-project-source="work-1"]');
    await waitFor(client, "location.pathname==='/projects/qa-orbit-workspace'");
    await pause(600);
    await check(
        'detail has one shared screenshot transition name',
        "[...document.querySelectorAll('body *')].filter(e=>getComputedStyle(e).viewTransitionName==='project-cover').length===1",
    );
    await client.evaluate("document.querySelector('a.studio-link').click()");
    await waitFor(client, "location.pathname==='/'");
    await pause(700);
    await check(
        'return restores project focus and scroll',
        "({pass:document.activeElement.dataset.projectSource==='work-1' && scrollY>300,focus:document.activeElement.dataset.projectSource,scrollY})",
    );
    await check(
        'return navigation does not restart intro',
        "!document.querySelector('.intro-frame')",
    );
    await boot(
        "Object.defineProperty(Document.prototype,'startViewTransition',{value:undefined,configurable:true});localStorage.setItem('portfolio:intro:v1','seen');",
    );
    await navigate(client, base + '/');
    await click('[data-project-source="hero"]');
    await waitFor(client, "location.pathname==='/projects/qa-orbit-workspace'");
    await check(
        'no View Transitions API navigation fallback',
        "document.querySelector('h1').textContent.includes('QA')",
    );
    await boot(
        "Storage.prototype.getItem=function(){throw new DOMException('Denied','SecurityError')};Storage.prototype.setItem=function(){throw new DOMException('Denied','SecurityError')};",
    );
    await navigate(client, base + '/');
    await pause(1300);
    await click('button[aria-label="Dark theme"]');
    await check(
        'storage denied still supports theme and visible content',
        "document.documentElement.classList.contains('dark') && !document.querySelector('.intro-frame') && window.__qaErrors.length===0",
    );
    await click('[data-project-source="hero"]');
    await waitFor(client, "location.pathname==='/projects/qa-orbit-workspace'");
    await client.evaluate("document.querySelector('a.studio-link').click()");
    await waitFor(client, "location.pathname==='/'");
    await check(
        'storage denied in-memory intro guard',
        "!document.querySelector('.intro-frame')",
    );
    await boot(
        "localStorage.setItem('appearance','system');localStorage.setItem('portfolio:intro:v1','seen');",
    );
    await client.call('Emulation.setEmulatedMedia', {
        features: [{ name: 'prefers-color-scheme', value: 'dark' }],
    });
    await navigate(client, base + '/');
    await check(
        'system theme follows dark preference before app paint',
        "document.documentElement.classList.contains('dark')",
    );
    await client.call('Emulation.setEmulatedMedia', {
        features: [{ name: 'prefers-color-scheme', value: 'light' }],
    });
    await pause(150);
    await check(
        'system theme follows live preference change',
        "!document.documentElement.classList.contains('dark')",
    );
    await click('button[aria-label="Dark theme"]');
    await client.call('Emulation.setEmulatedMedia', {
        features: [{ name: 'prefers-color-scheme', value: 'light' }],
    });
    await check(
        'explicit theme ignores system changes',
        "document.documentElement.classList.contains('dark')",
    );
    await boot(
        "localStorage.setItem('appearance','light');localStorage.setItem('portfolio:intro:v1','seen');",
    );
    await client.call('Emulation.setEmulatedMedia', { features: [] });
    await navigate(client, base + '/projects/qa-missing-image');
    await check(
        'missing cover has readable fallback',
        "document.body.textContent.includes('Screenshot unavailable')",
    );
    await navigate(client, base + '/');
    await pause(1400);
    await check(
        'idle has no running animation loop',
        "document.getAnimations().filter(a=>a.playState==='running').length===0",
    );
    await client.call('Emulation.setDeviceMetricsOverride', {
        width: 720,
        height: 450,
        deviceScaleFactor: 1,
        mobile: false,
    });
    await check(
        '200% desktop equivalent layout has no overflow',
        'document.documentElement.scrollWidth<=innerWidth',
    );
    await screenshot(client, 'storage/app/qa/zoom-200-equivalent.png');

    await navigate(client, base + '/login');
    await input('#email', 'qa-owner@example.test');
    await input('#password', 'qa-preview-password');
    await click('[data-test="login-button"]');
    await waitFor(client, "location.pathname==='/admin'");
    await navigate(client, base + '/admin/projects');
    await input('#project-search', 'no-matching-qa-project');
    await check(
        'filter empty state differs from no content',
        "document.body.textContent.includes('Tidak ada hasil')",
    );
    await navigate(client, base + '/admin/projects/1/edit');
    await input('#project-title', 'QA unsaved title');
    await input(
        '#project-description',
        '<script>window.qaUnsafe=true</script>\nQA escaped description',
    );
    await button('Preview');
    await waitFor(client, "Boolean(document.querySelector('[role=dialog]'))");
    await check(
        'local preview escapes description',
        "document.querySelector('[role=dialog]').textContent.includes('<script>') && !window.qaUnsafe",
    );

    for (let i = 0; i < 12; i++) {
        await key('Tab');
    }

    await check(
        'project preview traps focus',
        "Boolean(document.activeElement.closest('[role=dialog]'))",
    );
    await key('Escape');
    await pause(300);
    await check(
        'project preview returns focus',
        "document.activeElement.textContent.trim()==='Preview'",
    );
    await input('#project-slug', 'UPPER_INVALID');
    await client.evaluate(
        "document.querySelector('form').noValidate=true;document.querySelector('form').requestSubmit()",
    );
    await waitFor(
        client,
        "document.querySelector('#project-slug').getAttribute('aria-invalid')==='true'",
    );
    await check(
        'validation error keeps unsaved values and association',
        "document.querySelector('#project-title').value==='QA unsaved title' && Boolean(document.querySelector('#project-slug-error')?.textContent.trim())",
    );
    await navigate(client, base + '/admin/blog/1/edit');
    await waitFor(
        client,
        "Boolean(document.querySelector('.ProseMirror'))",
        20000,
    );
    await check(
        'editor initially clean',
        "document.querySelector('.cms-actionbar [role=status]').textContent.includes('Tidak ada perubahan')",
    );
    await input('#post-title', 'QA unsaved article title');
    await button('Preview');
    await waitFor(
        client,
        "Boolean(document.querySelector('[role=dialog] .studio-prose'))",
    );
    await check(
        'server article preview renders current form',
        "document.querySelector('[role=dialog]').textContent.includes('QA unsaved article title') && Boolean(document.querySelector('[role=dialog] .studio-prose').textContent.trim())",
    );
    await key('Escape');
    await pause(300);
    await check(
        'article preview returns focus and keeps dirty state',
        "document.activeElement.textContent.trim()==='Preview' && document.querySelector('.cms-actionbar [role=status]').textContent.includes('Perubahan belum disimpan')",
    );
    await navigate(client, base + '/admin');
    await click('[data-sidebar="trigger"]');
    await waitFor(client, "Boolean(document.querySelector('[role=dialog]'))");
    await key('Escape');
    await pause(300);
    await check(
        'CMS mobile sidebar returns focus',
        "document.activeElement.getAttribute('data-sidebar')==='trigger'",
    );
    await check(
        'no runtime errors in final CMS navigation',
        'window.__qaErrors.length===0',
    );
    await client.call('Emulation.setDeviceMetricsOverride', {
        width: 1440,
        height: 900,
        deviceScaleFactor: 1,
        mobile: false,
    });
    await navigate(client, base + '/');
    const contrast = [];

    for (const theme of ['light', 'dark']) {
        await click(
            'button[aria-label="' +
                (theme === 'light' ? 'Light theme' : 'Dark theme') +
                '"]',
        );
        const pairs = await client.evaluate(
            `(() => { const el=document.createElement('div');document.body.append(el);const rgb=name=>{el.style.color='var(--'+name+')';return getComputedStyle(el).color.match(/[\\d.]+/g).slice(0,3).map(Number)};const l=c=>c.map(v=>{v/=255;return v<=.04045?v/12.92:((v+.055)/1.055)**2.4}).reduce((s,v,i)=>s+v*[.2126,.7152,.0722][i],0);const pairs=[['surface','on-surface'],['surface','on-surface-variant'],['surface-container','on-surface-variant'],['primary','on-primary'],['primary-container','on-primary-container'],['secondary-container','on-secondary-container'],['tertiary-container','on-tertiary-container'],['error-container','error'],['success-container','success'],['warning-container','warning']];const result=pairs.map(([bg,fg])=>{const a=l(rgb(bg)),b=l(rgb(fg));return {bg,fg,ratio:(Math.max(a,b)+.05)/(Math.min(a,b)+.05)}});el.remove();return result})()`,
        );
        contrast.push({ theme, pairs });
    }

    results.push({
        name: 'normal text role pairs contrast >=4.5:1',
        passed: contrast.every((row) => row.pairs.every((p) => p.ratio >= 4.5)),
        detail: contrast,
    });
    await fs.mkdir('storage/app/qa', { recursive: true });
    await fs.writeFile(
        'storage/app/qa/interactions.json',
        JSON.stringify(results, null, 2),
    );
    await fs.writeFile(
        'storage/app/qa/event-timing.json',
        JSON.stringify(
            {
                samples: eventSamples,
                maxObservedDuration: Math.max(
                    0,
                    ...eventSamples.flatMap((sample) =>
                        sample.events.map((event) => event.duration),
                    ),
                ),
                note: 'Event Timing durations from trusted input in scripted lab interactions; not field INP.',
            },
            null,
            2,
        ),
    );
    console.log(
        JSON.stringify({
            checks: results.length,
            failed: results.filter((r) => !r.passed),
        }),
    );

    if (results.some((r) => !r.passed)) {
        process.exitCode = 1;
    }
} finally {
    if (injection) {
        await client.call('Page.removeScriptToEvaluateOnNewDocument', {
            identifier: injection,
        });
    }

    await client.call('Emulation.setEmulatedMedia', { features: [] });
    client.close();
}
