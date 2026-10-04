import js from '@eslint/js';
import stylistic from '@stylistic/eslint-plugin';
import prettier from 'eslint-config-prettier/flat';
import importPlugin from 'eslint-plugin-import';
import svelte from 'eslint-plugin-svelte';
import tailwindCanonical from 'eslint-plugin-tailwind-canonical-classes';
import ts from 'typescript-eslint';

const canonicalRule = tailwindCanonical.rules['tailwind-canonical-classes'];
const svelteTailwindCanonical = {
    rules: {
        'tailwind-canonical-classes': {
            ...canonicalRule,
            create(context) {
                const visitors = canonicalRule.create(context);

                return {
                    ...visitors,
                    SvelteAttribute(node) {
                        if (
                            node.key.name !== 'class' ||
                            node.value.length !== 1
                        ) {
                            return;
                        }

                        const value = node.value[0];
                        let jsxValue;

                        if (value.type === 'SvelteLiteral') {
                            const source = context.sourceCode.text;
                            const [start, end] = value.range;
                            const quote = source[start - 1];
                            const isQuoted =
                                (quote === '"' || quote === "'") &&
                                source[end] === quote;
                            jsxValue = {
                                ...value,
                                type: 'Literal',
                                range: isQuoted
                                    ? [start - 1, end + 1]
                                    : value.range,
                            };
                        } else if (value.type === 'SvelteMustacheTag') {
                            jsxValue = {
                                ...value,
                                type: 'JSXExpressionContainer',
                            };
                        } else {
                            return;
                        }

                        visitors.JSXAttribute?.({
                            ...node,
                            type: 'JSXAttribute',
                            name: { type: 'JSXIdentifier', name: 'className' },
                            value: jsxValue,
                        });
                    },
                };
            },
        },
    },
};

const controlStatements = [
    'if',
    'return',
    'for',
    'while',
    'do',
    'switch',
    'try',
    'throw',
];
const paddingAroundControl = [
    ...controlStatements.flatMap((stmt) => [
        { blankLine: 'always', prev: '*', next: stmt },
        { blankLine: 'always', prev: stmt, next: '*' },
    ]),
];

export default ts.config(
    js.configs.recommended,
    ...ts.configs.recommended,
    ...svelte.configs['flat/recommended'],
    {
        files: ['**/*.svelte'],
        languageOptions: {
            parserOptions: {
                parser: ts.parser,
            },
        },
        plugins: {
            'tailwind-canonical-classes': svelteTailwindCanonical,
        },
        rules: {
            'tailwind-canonical-classes/tailwind-canonical-classes': [
                'error',
                {
                    cssPath: './resources/css/app.css',
                },
            ],
        },
    },
    {
        files: ['**/*.svelte.ts'],
        languageOptions: {
            parser: ts.parser,
        },
    },
    {
        plugins: {
            import: importPlugin,
        },
        settings: {
            'import/resolver': {
                typescript: {
                    alwaysTryTypes: true,
                    project: './tsconfig.json',
                },
                node: true,
            },
        },
        rules: {
            'no-undef': 'off',
            '@typescript-eslint/no-explicit-any': 'off',
            '@typescript-eslint/no-unused-vars': [
                'error',
                {
                    argsIgnorePattern: '^_',
                    varsIgnorePattern: '^_',
                },
            ],
            '@typescript-eslint/consistent-type-imports': [
                'error',
                {
                    prefer: 'type-imports',
                    fixStyle: 'separate-type-imports',
                },
            ],
            'import/order': [
                'error',
                {
                    groups: [
                        'builtin',
                        'external',
                        'internal',
                        'parent',
                        'sibling',
                        'index',
                    ],
                    alphabetize: {
                        order: 'asc',
                        caseInsensitive: true,
                    },
                },
            ],
            'import/consistent-type-specifier-style': [
                'error',
                'prefer-top-level',
            ],
        },
    },
    {
        plugins: {
            '@stylistic': stylistic,
        },
        rules: {
            '@stylistic/brace-style': [
                'error',
                '1tbs',
                { allowSingleLine: false },
            ],
            '@stylistic/padding-line-between-statements': [
                'error',
                ...paddingAroundControl,
            ],
        },
    },
    {
        ignores: [
            '.agents/**',
            '.codex/**',
            '.claude/**',
            '.gemini/**',
            '.github/skills/**',
            'vendor',
            'node_modules',
            'public',
            'bootstrap/ssr',
            'storage/**',
            'tailwind.config.js',
            'vite.config.ts',
            'resources/js/actions/**',
            'resources/js/components/ui/*',
            'resources/js/routes/**',
            'resources/js/wayfinder/**',
        ],
    },
    prettier, // Turn off all rules that might conflict with Prettier
    {
        plugins: {
            '@stylistic': stylistic,
        },
        rules: {
            curly: ['error', 'all'],
            '@stylistic/brace-style': [
                'error',
                '1tbs',
                { allowSingleLine: false },
            ],
        },
    },
);
