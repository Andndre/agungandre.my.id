import fs from 'node:fs/promises';
import path from 'node:path';
export const pause = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
export async function connect(fresh = false) {
    const targets = await (
        await fetch('http://127.0.0.1:9223/json/list')
    ).json();
    const target = fresh
        ? await (
              await fetch('http://127.0.0.1:9223/json/new?about:blank', {
                  method: 'PUT',
              })
          ).json()
        : targets.find((item) => item.type === 'page');

    if (!target) {
        throw new Error(
            'Start isolated Chrome on remote-debugging-port=9223 first.',
        );
    }

    const socket = new WebSocket(target.webSocketDebuggerUrl);
    await new Promise((resolve, reject) => {
        socket.onopen = resolve;
        socket.onerror = reject;
    });
    let id = 0;
    const waiting = new Map();
    const events = [];
    socket.onmessage = (event) => {
        const message = JSON.parse(event.data);

        if (message.id) {
            const entry = waiting.get(message.id);
            waiting.delete(message.id);

            if (message.error) {
                entry?.reject(new Error(message.error.message));
            } else {
                entry?.resolve(message.result);
            }
        } else {
            events.push(message);
        }
    };
    const call = (method, params = {}) =>
        new Promise((resolve, reject) => {
            const current = ++id;
            waiting.set(current, { resolve, reject });
            socket.send(JSON.stringify({ id: current, method, params }));
        });
    const evaluate = async (expression) => {
        const result = await call('Runtime.evaluate', {
            expression,
            returnByValue: true,
            awaitPromise: true,
        });

        if (result.exceptionDetails) {
            throw new Error(JSON.stringify(result.exceptionDetails));
        }

        return result.result.value;
    };
    await call('Page.enable');
    await call('Runtime.enable');
    await call('Network.enable');

    return { call, evaluate, events, close: () => socket.close() };
}
export async function waitFor(client, expression, timeout = 12000) {
    const start = Date.now();

    while (Date.now() - start < timeout) {
        if (await client.evaluate(expression)) {
            return;
        }

        await pause(100);
    }

    throw new Error('Timed out: ' + expression);
}
export async function navigate(client, url) {
    await client.call('Page.navigate', { url });
    await waitFor(
        client,
        "document.readyState === 'complete' && Boolean(document.querySelector('h1'))",
    );
    await client.evaluate('document.fonts.ready');
    await pause(150);
}
export async function screenshot(client, file, full = true) {
    const metrics = await client.call('Page.getLayoutMetrics');
    const clip = metrics.cssContentSize;
    const result = await client.call('Page.captureScreenshot', {
        format: 'png',
        captureBeyondViewport: full,
        ...(full
            ? {
                  clip: {
                      x: 0,
                      y: 0,
                      width: clip.width,
                      height: clip.height,
                      scale: 1,
                  },
              }
            : {}),
    });
    await fs.mkdir(path.dirname(file), { recursive: true });
    await fs.writeFile(file, Buffer.from(result.data, 'base64'));
}
export const layoutAudit = `JSON.stringify({
width:innerWidth,scrollWidth:document.documentElement.scrollWidth,
title:document.querySelector('h1')?.textContent.trim(),
overflow:[...document.querySelectorAll('body *')].filter(el => { const r=el.getBoundingClientRect(); const s=getComputedStyle(el); return s.position!=='fixed' && r.width>0 && r.left < -2 || s.position!=='fixed' && r.width>0 && r.right>innerWidth+2; }).slice(0,12).map(el=>({tag:el.tagName,class:el.className,left:el.getBoundingClientRect().left,right:el.getBoundingClientRect().right})),
images:[...document.images].map(img=>({src:img.getAttribute('src'),width:img.getAttribute('width'),height:img.getAttribute('height'),loaded:img.complete && img.naturalWidth>0})),
errors:window.__qaErrors??[],metrics:window.__qaMetrics??{}
})`;
export const observerScript = `
window.__qaErrors=[]; window.addEventListener('error',e=>window.__qaErrors.push(e.message));
window.addEventListener('unhandledrejection',e=>window.__qaErrors.push(String(e.reason)));
window.__qaMetrics={lcp:0,cls:0,events:[]};
new PerformanceObserver(list=>list.getEntries().forEach(e=>window.__qaMetrics.lcp=e.startTime)).observe({type:'largest-contentful-paint',buffered:true});
new PerformanceObserver(list=>list.getEntries().forEach(e=>{if(!e.hadRecentInput)window.__qaMetrics.cls+=e.value;})).observe({type:'layout-shift',buffered:true});
new PerformanceObserver(list=>list.getEntries().forEach(e=>window.__qaMetrics.events.push({name:e.name,duration:e.duration,id:e.interactionId}))).observe({type:'event',buffered:true,durationThreshold:16});
`;

if (process.argv[1] && path.basename(process.argv[1]) === 'cdp.mjs') {
    const [
        url = 'http://127.0.0.1:8010/',
        width = '1440',
        theme = 'light',
        file = 'storage/app/qa/screen.png',
    ] = process.argv.slice(2);
    const client = await connect(true);

    try {
        await client.call('Emulation.setDeviceMetricsOverride', {
            width: Number(width),
            height: 900,
            deviceScaleFactor: 1,
            mobile: Number(width) < 768,
        });
        await client.call('Page.addScriptToEvaluateOnNewDocument', {
            source:
                observerScript +
                "localStorage.setItem('appearance'," +
                JSON.stringify(theme) +
                ');',
        });
        await navigate(client, url);
        await pause(1300);
        await screenshot(client, file);
        console.log(await client.evaluate(layoutAudit));
    } finally {
        client.close();
    }
}
