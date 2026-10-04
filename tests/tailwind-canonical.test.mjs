import assert from 'node:assert/strict';
import { test } from 'node:test';
import { ESLint } from 'eslint';

const filePath = 'resources/js/pages/TailwindCanonicalFixture.svelte';
const ruleId = 'tailwind-canonical-classes/tailwind-canonical-classes';

test('Svelte classes reject arbitrary equivalents of canonical utilities', async () => {
    const eslint = new ESLint();
    const [result] = await eslint.lintText(
        '<div class="mt-[16px] [&>*]:p-4"></div>',
        { filePath },
    );
    assert.deepEqual(
        result.messages
            .filter((message) => message.ruleId === ruleId)
            .map((message) => message.message),
        [
            "Class 'mt-[16px]' should be 'mt-4'",
            "Class '[&>*]:p-4' should be '*:p-4'",
        ],
    );
});

test('autofix preserves quotes and converts Svelte utility expressions', async () => {
    const eslint = new ESLint({ fix: true });
    const fixtures = [
        [
            '<div class="mt-[16px] [&>*]:p-4"></div>',
            '<div class="mt-4 *:p-4"></div>',
        ],
        ["<div class='mt-[16px]'></div>", "<div class='mt-4'></div>"],
        ['<div class=mt-[16px]></div>', '<div class="mt-4"></div>'],
        [
            '<div class={cn("mt-[16px]")}></div>',
            '<div class={cn("mt-4")}></div>',
        ],
    ];

    for (const [source, expected] of fixtures) {
        const [result] = await eslint.lintText(source, { filePath });
        assert.equal(result.output, expected);
    }
});

test('canonical classes and custom design system classes remain unchanged', async () => {
    const eslint = new ESLint({ fix: true });
    const [result] = await eslint.lintText(
        '<div class="cms-panel mt-4 *:p-4 text-muted-foreground"></div>',
        { filePath },
    );
    assert.equal(result.output, undefined);
    assert.equal(
        result.messages.filter((message) => message.ruleId === ruleId).length,
        0,
    );
});
