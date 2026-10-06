/*
 * Localization policy gate.
 *
 * Four checks, all fail-closed:
 *
 *   1. Key parity      — every locale carries exactly the canonical English key set.
 *   2. Shape parity    — a code that is plural in English is plural everywhere, with `other` present.
 *   3. Placeholder parity — a translation may not drop or invent an interpolation slot.
 *   4. Hard-coded strings — a file declared externalized may not contain user-visible literals.
 *
 * Catalogs are read by parsing their TypeScript rather than executing it, so the gate cannot be
 * subverted by a catalog that computes its own contents at load time.
 */

import { readdirSync, readFileSync } from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import ts from 'typescript';

const REPO_ROOT = process.cwd();
const CATALOG_DIR = 'resources/js/lib/i18n/catalogs';
const CANONICAL = 'en';

/* Derived from the catalogs actually on disk, so adding a locale file is enough to bring it under
 * every check below. A hard-coded list here would be a third source of truth to drift against. */
const LOCALES = readdirSync(path.join(REPO_ROOT, CATALOG_DIR))
    .filter((entry) => entry.endsWith('.ts'))
    .map((entry) => entry.replace(/\.ts$/u, ''))
    .sort();
const MANIFEST = 'config/i18n-externalized.json';
const SERVER_CONFIG = 'config/i18n.php';

const failures = [];

const fail = (message) => failures.push(message);

const parse = (relativePath) => {
    const absolute = path.join(REPO_ROOT, relativePath);

    return ts.createSourceFile(
        absolute,
        readFileSync(absolute, 'utf8'),
        ts.ScriptTarget.Latest,
        true,
        ts.ScriptKind.TSX,
    );
};

const literalName = (node) =>
    ts.isStringLiteral(node.name) || ts.isNumericLiteral(node.name)
        ? node.name.text
        : node.name.getText();

/** Extracts `{ 'code': 'text' | { one: '…', other: '…' } }` from a catalog module. */
const readCatalog = (locale) => {
    const source = parse(`${CATALOG_DIR}/${locale}.ts`);
    const entries = new Map();

    const visit = (node) => {
        if (
            ts.isObjectLiteralExpression(node) &&
            node.properties.length > 0 &&
            entries.size === 0
        ) {
            for (const property of node.properties) {
                if (!ts.isPropertyAssignment(property)) {
                    continue;
                }

                const key = literalName(property);
                const value = property.initializer;

                if (
                    ts.isStringLiteral(value) ||
                    ts.isNoSubstitutionTemplateLiteral(value)
                ) {
                    entries.set(key, { kind: 'string', text: value.text });
                } else if (ts.isObjectLiteralExpression(value)) {
                    const forms = new Map();

                    for (const form of value.properties) {
                        if (
                            ts.isPropertyAssignment(form) &&
                            ts.isStringLiteral(form.initializer)
                        ) {
                            forms.set(literalName(form), form.initializer.text);
                        }
                    }

                    entries.set(key, { kind: 'plural', forms });
                } else {
                    fail(
                        `${locale}: message "${key}" is neither a string nor a plural object.`,
                    );
                }
            }

            return;
        }

        ts.forEachChild(node, visit);
    };

    visit(source);

    if (entries.size === 0) {
        fail(
            `${locale}: no messages could be read from ${CATALOG_DIR}/${locale}.ts.`,
        );
    }

    return entries;
};

const placeholders = (text) =>
    [...new Set([...text.matchAll(/\{(\w+)\}/gu)].map((match) => match[1]))]
        .sort()
        .join(',');

/**
 * Every plural form interpolates the same variables, so the canonical slot set is the union across
 * the canonical forms. Checking each translated form against that union — rather than comparing two
 * concatenated blobs — catches a slot dropped from a single form and names which one.
 */
const expectedSlots = (message) =>
    placeholders(
        message.kind === 'string'
            ? message.text
            : [...message.forms.values()].join(' '),
    );

const catalogs = new Map(
    LOCALES.map((locale) => [locale, readCatalog(locale)]),
);
const canonical = catalogs.get(CANONICAL);

for (const locale of LOCALES.filter((l) => l !== CANONICAL)) {
    const catalog = catalogs.get(locale);

    for (const code of canonical.keys()) {
        if (!catalog.has(code)) {
            fail(`${locale}: missing message code "${code}".`);
        }
    }

    for (const code of catalog.keys()) {
        if (!canonical.has(code)) {
            fail(
                `${locale}: message code "${code}" is not in the canonical ${CANONICAL} catalog.`,
            );
        }
    }

    for (const [code, expected] of canonical) {
        const actual = catalog.get(code);

        if (!actual) {
            continue;
        }

        if (actual.kind !== expected.kind) {
            fail(
                `${locale}: "${code}" is ${actual.kind} but ${CANONICAL} declares it ${expected.kind}.`,
            );

            continue;
        }

        if (actual.kind === 'plural' && !actual.forms.has('other')) {
            fail(`${locale}: plural message "${code}" has no "other" form.`);
        }

        const expectedForCode = expectedSlots(expected);
        const actualForms =
            actual.kind === 'string'
                ? [['', actual.text]]
                : [...actual.forms.entries()];

        for (const [category, text] of actualForms) {
            if (placeholders(text) === expectedForCode) {
                continue;
            }

            const where = category
                ? `"${code}" form "${category}"`
                : `"${code}"`;

            fail(
                `${locale}: ${where} interpolates {${placeholders(text)}} ` +
                    `but ${CANONICAL} requires {${expectedForCode}}.`,
            );
        }
    }
}

/* --------------------------------------------- server and client agree on the locale set */

/*
 * The server negotiates locales from config/i18n.php and the browser renders them from the TypeScript
 * catalogs. Two lists mean two chances to drift, so the gate holds them to each other: a locale the
 * server can select but the client cannot render would serve message codes to a user.
 */
const serverConfig = readFileSync(path.join(REPO_ROOT, SERVER_CONFIG), 'utf8');
const supportedMatch = serverConfig.match(/'supported'\s*=>\s*\[(.*?)\]/su);

if (!supportedMatch) {
    fail(`${SERVER_CONFIG}: could not read the 'supported' locale list.`);
} else {
    const serverLocales = [...supportedMatch[1].matchAll(/'([\w-]+)'/gu)]
        .map((match) => match[1])
        .sort();
    const clientLocales = [...LOCALES].sort();

    if (serverLocales.join(',') !== clientLocales.join(',')) {
        fail(
            `${SERVER_CONFIG} supports [${serverLocales.join(', ')}] but the client catalogs ` +
                `provide [${clientLocales.join(', ')}].`,
        );
    }
}

/* ------------------------------------------------- hard-coded user-visible strings */

const manifest = JSON.parse(
    readFileSync(path.join(REPO_ROOT, MANIFEST), 'utf8'),
);
const textProps = new Set(manifest.textBearingProps);

/** Punctuation, entities, and whitespace carry no meaning to translate. */
const isTranslatable = (text) => /\p{L}{2,}/u.test(text);

for (const relativePath of manifest.externalizedPaths) {
    const source = parse(relativePath);

    const report = (node, what, text) => {
        const { line } = source.getLineAndCharacterOfPosition(node.getStart());

        fail(
            `${relativePath}:${line + 1}: hard-coded ${what} "${text.trim()}" — move it to the catalog.`,
        );
    };

    const visit = (node) => {
        if (ts.isJsxText(node) && isTranslatable(node.text)) {
            report(node, 'text', node.text);
        }

        if (
            ts.isJsxAttribute(node) &&
            node.initializer &&
            textProps.has(node.name.getText())
        ) {
            const value = node.initializer;

            if (ts.isStringLiteral(value) && isTranslatable(value.text)) {
                report(node, `${node.name.getText()} value`, value.text);
            }
        }

        ts.forEachChild(node, visit);
    };

    visit(source);
}

if (failures.length > 0) {
    console.error(`I18N POLICY: ${failures.length} failure(s).\n`);

    for (const failure of failures) {
        console.error(`  - ${failure}`);
    }

    process.exit(1);
}

console.log(
    `I18N POLICY: passed. ${canonical.size} message codes across ${LOCALES.length} locales; ` +
        `${manifest.externalizedPaths.length} externalized file(s) carry no hard-coded strings.`,
);
