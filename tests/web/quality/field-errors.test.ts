import { readdirSync, readFileSync } from 'node:fs';
import path from 'node:path';
import { describe, expect, it } from 'vite-plus/test';

const root = path.resolve(__dirname, '../../../resources/js');

const sources = readdirSync(root, { recursive: true, encoding: 'utf8' })
    .filter((file) => file.endsWith('.tsx'))
    .map((file) => ({
        file,
        source: readFileSync(path.join(root, file), 'utf8'),
    }));

/** Every `aria-describedby` value in a file, as written: a string or a `{…}` expression. */
const describedBy = (source: string) =>
    [
        ...source.matchAll(
            /aria-describedby=(?:"([^"]*)"|\{((?:[^{}]|\{[^{}]*\})*)\})/gu,
        ),
    ].map(([, literal, expression]) => literal ?? expression);

describe('Field errors are tied to their fields (WCAG 2.2 SC 1.3.1, 3.3.1)', () => {
    const errors = sources.flatMap(({ file, source }) =>
        [
            ...source.matchAll(
                /<FieldError\s+id=(?:"([^"]+)"|\{((?:[^{}]|\{[^{}]*\})*)\})/gu,
            ),
        ].map(([, literal, expression]) => ({
            file,
            id: literal ?? expression,
            literal: literal !== undefined,
            references: describedBy(source),
        })),
    );

    it('finds the field errors it checks', () => {
        expect(errors.length).toBeGreaterThan(25);
    });

    it.each(errors.map((error) => [`${error.file} ${error.id}`, error]))(
        'names %s in a control’s aria-describedby',
        (_, { id, literal, references }) => {
            const tied = references.some((reference) =>
                literal
                    ? reference.split(/[\s'"`]+/u).includes(id)
                    : reference.includes(
                          id.replace(/^`|`$/gu, '').replace(/-error$/u, ''),
                      ),
            );

            expect(tied).toBe(true);
        },
    );
});
