import { existsSync, readFileSync } from 'node:fs';
import path from 'node:path';
import { describe, expect, it } from 'vite-plus/test';
import coverage from '../../../resources/fixtures/ui-state-coverage.json';

/*
 * The Phase 2 screen/state evidence matrix, kept honest: every named MVP state in the crosswalk
 * must map to at least one preview fixture, or carry an explicit disposition explaining why not.
 * A new state in the crosswalk, a renamed fixture or an undocumented gap fails this test.
 * `deferred` marks a state that belongs to an out-of-MVP deferral (e.g. D-61) and must not be
 * fabricated as a live outcome.
 */

type Entry =
    | { name: string; fixtures: string[] }
    | {
          name: string;
          disposition:
              | 'missing'
              | 'partial'
              | 'tested-without-fixture'
              | 'phase-3'
              | 'deferred';
          note: string;
      };

const root = path.resolve(__dirname, '../../..');
const entries = coverage as Record<string, Entry>;
const crosswalk = readFileSync(
    path.join(root, 'docs/phase-0/mvp-crosswalk.md'),
    'utf8',
);
const named = [
    ...new Set(crosswalk.match(/MVP-[A-Z]+-SCR-\d{2}-ST-\d{2}/gu) ?? []),
].sort();

describe('The screen-state evidence matrix', () => {
    it('accounts for every named MVP state in the crosswalk, and nothing else', () => {
        expect(named.length).toBeGreaterThan(0);
        expect(Object.keys(entries).sort()).toEqual(named);
    });

    it('points each covered state at fixtures that exist', () => {
        const absent = Object.entries(entries).flatMap(([id, entry]) =>
            'fixtures' in entry
                ? entry.fixtures
                      .filter(
                          (fixture) =>
                              !existsSync(
                                  path.join(
                                      root,
                                      'resources/fixtures/ui',
                                      `${fixture}.json`,
                                  ),
                              ),
                      )
                      .map((fixture) => `${id} → ${fixture}`)
                : [],
        );

        expect(absent).toEqual([]);
    });

    it('explains every state it cannot yet preview', () => {
        const unexplained = Object.entries(entries)
            .filter(
                ([, entry]) =>
                    !('fixtures' in entry) &&
                    (entry.note.trim() === '' ||
                        ![
                            'missing',
                            'partial',
                            'tested-without-fixture',
                            'phase-3',
                            'deferred',
                        ].includes(entry.disposition)),
            )
            .map(([id]) => id);

        expect(unexplained).toEqual([]);
    });
});
