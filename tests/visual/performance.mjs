import fs from 'node:fs/promises';
import { connect, navigate, pause, observerScript } from './cdp.mjs';
const client = await connect(true);
const url = process.argv[2] || 'http://127.0.0.1:8010/';
const output = process.argv[3] || 'storage/app/qa/performance.json';
const report = [];
let injection;

try {
    await client.call('Emulation.setDeviceMetricsOverride', {
        width: 390,
        height: 844,
        deviceScaleFactor: 1,
        mobile: true,
    });
    await client.call('Emulation.setCPUThrottlingRate', { rate: 4 });
    await client.call('Network.emulateNetworkConditions', {
        offline: false,
        latency: 150,
        downloadThroughput: 200000,
        uploadThroughput: 95000,
        connectionType: 'cellular4g',
    });
    await client.call('Network.setCacheDisabled', { cacheDisabled: true });
    injection = (
        await client.call('Page.addScriptToEvaluateOnNewDocument', {
            source:
                observerScript +
                "localStorage.setItem('appearance','light'); localStorage.removeItem('portfolio:intro:v1');",
        })
    ).identifier;

    for (let run = 1; run <= 3; run++) {
        await client.call('Network.clearBrowserCache');
        await navigate(client, url);
        await pause(2500);
        const sample = await client.evaluate(
            `JSON.stringify({metrics:window.__qaMetrics,paints:performance.getEntriesByType('paint').map(e=>({name:e.name,startTime:e.startTime})),navigation:performance.getEntriesByType('navigation').map(e=>({ttfb:e.responseStart,dcl:e.domContentLoadedEventEnd})),resources:performance.getEntriesByType('resource').filter(e=>e.initiatorType==='script'||e.initiatorType==='css'||e.initiatorType==='link'||e.initiatorType==='img').map(e=>({name:e.name,transferSize:e.transferSize,decodedBodySize:e.decodedBodySize,duration:e.duration}))})`,
        );
        const compression = client.events
            .filter(
                (e) =>
                    e.method === 'Network.responseReceived' &&
                    e.params.response.url.startsWith(new URL(url).origin),
            )
            .map((e) => ({
                url: e.params.response.url,
                encoding:
                    e.params.response.headers['Content-Encoding'] ??
                    e.params.response.headers['content-encoding'] ??
                    'identity',
            }));
        report.push({ run, ...JSON.parse(sample), compression });
        console.log('Measured mobile cold load ' + run);
    }

    await fs.mkdir('storage/app/qa', { recursive: true });
    await fs.writeFile(
        output,
        JSON.stringify(
            {
                environment: {
                    width: 390,
                    cpu: 4,
                    latency: 150,
                    downloadBytesPerSecond: 200000,
                    url,
                    server:
                        new URL(url).protocol === 'https:'
                            ? 'EnvKit HTTPS; inspect recorded response encoding'
                            : 'PHP local; no response compression',
                },
                samples: report,
            },
            null,
            2,
        ),
    );
    console.log(
        JSON.stringify(
            report.map((sample) => ({
                run: sample.run,
                lcp: sample.metrics.lcp,
                cls: sample.metrics.cls,
                ttfb: sample.navigation[0].ttfb,
                fcp: sample.paints.find(
                    (item) => item.name === 'first-contentful-paint',
                )?.startTime,
                bytes: sample.resources.reduce(
                    (total, item) => total + item.transferSize,
                    0,
                ),
            })),
        ),
    );
} finally {
    await client.call('Emulation.setCPUThrottlingRate', { rate: 1 });
    await client.call('Network.emulateNetworkConditions', {
        offline: false,
        latency: 0,
        downloadThroughput: -1,
        uploadThroughput: -1,
    });
    await client.call('Network.setCacheDisabled', { cacheDisabled: false });

    if (injection) {
        await client.call('Page.removeScriptToEvaluateOnNewDocument', {
            identifier: injection,
        });
    }

    client.close();
}
