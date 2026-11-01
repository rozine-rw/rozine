import { describe, expect, it } from 'vite-plus/test';
import type {
    Account,
    TransactionRow,
    TranscriptionForm,
} from '@/lib/rozine/statement-transcription';
import {
    cleanBalance,
    cleanIdentifier,
    computedClosing,
    drawsBefore,
    formFrom,
    monthsBetween,
    openingOf,
    pairTransfer,
    payloadOf,
    problemsOf,
    referenceOf,
    sheetProblems,
    signedAmount,
    transferCandidates,
} from '@/lib/rozine/statement-transcription';
import type { StatementTranscriptionDraft } from '@/types/business';
import saved from '../../../resources/fixtures/ui/business-statement-transcription-saved.json';

const payload = () =>
    structuredClone(
        (saved.props.draft as unknown as StatementTranscriptionDraft)
            .current_transcription!.payload,
    );

const row = (change: Partial<TransactionRow> = {}): TransactionRow => ({
    key: 'r',
    reference: '',
    date: '2026-07-04',
    direction: 'in',
    amount: '100',
    classification: 'operating_inflow',
    link: null,
    ...change,
});

const single = (account: Partial<Account> = {}): TranscriptionForm => ({
    first: '2026-07',
    last: '2026-07',
    accounts: [
        { key: 'a', name: 'BK', from: '2026-07', until: null, ...account },
    ],
    sheets: {
        '2026-07/a': {
            opening: '0',
            closing: '100',
            sources: ['doc'],
            rows: [row()],
        },
    },
});

describe('The statement figures form', () => {
    it('lists the months of a range, and none for a range that is not one', () => {
        expect(monthsBetween('2025-11', '2026-02')).toEqual([
            '2025-11',
            '2025-12',
            '2026-01',
            '2026-02',
        ]);
        expect(monthsBetween('2026-03', '2026-02')).toEqual([]);
        expect(monthsBetween('', '2026-02')).toEqual([]);
        expect(monthsBetween('2026-01', '2026-13')).toEqual([]);
        expect(monthsBetween('2020-01', '2026-01')).toHaveLength(37);
    });

    it('keeps names, references and balances to what the server accepts', () => {
        expect(cleanIdentifier('Bank of Kigali / 12')).toBe(
            'Bank-of-Kigali--12',
        );
        expect(cleanBalance('-1,250 000')).toBe('-1250000');
        expect(cleanBalance('12-3')).toBe('123');
        expect(referenceOf(row(), 2)).toBe('row-3');
        expect(referenceOf(row({ reference: 'INV-1' }), 2)).toBe('INV-1');
        expect(signedAmount(row({ direction: 'out', amount: '40' }))).toBe(
            -40n,
        );
        expect(signedAmount(row({ amount: '1.5' }))).toBeNull();
        expect(
            computedClosing('10', [
                row(),
                row({ direction: 'out', amount: '5' }),
            ]),
        ).toBe(105n);
        expect(computedClosing('x', [row()])).toBeNull();
        expect(computedClosing('10', [row({ amount: '' })])).toBeNull();
    });

    it('starts an empty form on one month with one unnamed account', () => {
        const form = formFrom(null, '2026-09');

        expect(form.first).toBe('2026-09');
        expect(form.last).toBe('2026-09');
        expect(form.accounts).toHaveLength(1);
        expect(form.accounts[0]).toMatchObject({
            name: '',
            from: '2026-09',
            until: null,
        });
        expect(problemsOf(form).map((problem) => problem.code)).toEqual([
            'account_name',
            'sources',
            'balance',
        ]);
        expect(
            formFrom({ rails: [], months: [], statements: [] }, '2026-09')
                .accounts,
        ).toHaveLength(1);
    });

    it('reads a saved transcription back, links included, and saves it again unchanged', () => {
        const original = payload();
        const form = formFrom(original, '2026-09');

        expect(form.first).toBe('2026-07');
        expect(form.last).toBe('2026-08');
        expect(problemsOf(form)).toEqual([]);
        expect(payloadOf(form)).toEqual(original);
    });

    it('carries balances from month to month and starts a later account at 0', () => {
        const form = formFrom(payload(), '2026-09');
        const [bank, momo] = form.accounts;
        const months = ['2026-07', '2026-08'];

        expect(openingOf(form, months, bank, '2026-07')).toEqual({
            value: '1200000',
            fixed: false,
        });
        expect(openingOf(form, months, bank, '2026-08')).toEqual({
            value: '1650000',
            fixed: true,
        });
        expect(openingOf(form, months, momo, '2026-08')).toEqual({
            value: '0',
            fixed: true,
        });
    });

    it('offers the other side of a transfer and the withdrawals a put-back may repay', () => {
        const form = formFrom(payload(), '2026-09');
        const [bank, momo] = form.accounts;
        const months = ['2026-07', '2026-08'];

        expect(
            transferCandidates(form, '2026-08', bank.key).map(
                (candidate) => candidate.account.name,
            ),
        ).toEqual(['MoMo-0788']);
        expect(transferCandidates(form, '2026-07', bank.key)).toEqual([]);
        expect(
            drawsBefore(form, months, '2026-07').map(
                (draw) => draw.row.reference,
            ),
        ).toEqual(['DRW-1']);
        expect(drawsBefore(form, months, '2026-08')).toHaveLength(1);
        expect(momo.from).toBe('2026-08');
    });

    it('pairs transfers both ways and lets go of the old partner', () => {
        const form = formFrom(payload(), '2026-09');
        const [bank, momo] = form.accounts;
        const bankTransfer = form.sheets[`2026-08/${bank.key}`].rows[0];
        const momoTransfer = form.sheets[`2026-08/${momo.key}`].rows[0];

        const unpaired = pairTransfer(
            form,
            '2026-08',
            bank.key,
            bankTransfer.key,
            null,
        );
        expect(unpaired.sheets[`2026-08/${bank.key}`].rows[0].link).toBeNull();
        expect(unpaired.sheets[`2026-08/${momo.key}`].rows[0].link).toBeNull();
        expect(problemsOf(unpaired).map((problem) => problem.code)).toEqual([
            'row_link',
            'row_link',
        ]);

        const paired = pairTransfer(
            unpaired,
            '2026-08',
            momo.key,
            momoTransfer.key,
            `2026-08/${bank.key}/${bankTransfer.key}`,
        );
        expect(paired.sheets[`2026-08/${bank.key}`].rows[0].link).toBe(
            `2026-08/${momo.key}/${momoTransfer.key}`,
        );
        expect(problemsOf(paired)).toEqual([]);
        expect(pairTransfer(form, '2026-08', bank.key, 'gone', null)).toEqual({
            ...form,
            sheets: {
                ...form.sheets,
                [`2026-08/${bank.key}`]: form.sheets[`2026-08/${bank.key}`],
            },
        });
    });

    it('names everything the server would refuse, month by month', () => {
        expect(problemsOf({ ...single(), last: '2026-06' })).toEqual([
            { code: 'months' },
        ]);
        expect(problemsOf({ ...single(), first: '2020-01' })).toEqual([
            { code: 'too_many_months' },
        ]);
        expect(problemsOf({ ...single(), accounts: [] })).toEqual([
            { code: 'no_accounts' },
        ]);

        const twins = single();
        twins.accounts.push({
            key: 'b',
            name: 'BK',
            from: '2026-07',
            until: '2026-06',
        });
        expect(problemsOf(twins)).toEqual([
            { code: 'account_duplicate', account: 'a' },
            { code: 'account_duplicate', account: 'b' },
            { code: 'account_dates', account: 'b' },
        ]);

        const form = single({ until: '2026-07' });
        const months = ['2026-07'];
        const account = form.accounts[0];
        form.sheets['2026-07/a'] = {
            opening: '0',
            closing: '90',
            sources: [],
            rows: [
                row({ amount: 'x', date: '2026-08-01', reference: 'a b' }),
                row({ classification: '' }),
                row({ direction: 'out', classification: 'operating_inflow' }),
                row({ classification: 'owner_return', reference: 'r' }),
                row({
                    classification: 'owner_return',
                    reference: 'r',
                    link: '2026-07/a/none',
                }),
            ],
        };
        expect(sheetProblems(form, months, account, '2026-07')).toEqual([
            'sources',
            'row_amount',
            'row_date',
            'row_reference',
            'row_type',
            'row_link',
            'closed_balance',
        ]);

        form.sheets['2026-07/a'] = {
            opening: '0',
            closing: '90',
            sources: ['doc'],
            rows: [row()],
        };
        expect(sheetProblems(form, months, account, '2026-07')).toEqual([
            'difference',
            'closed_balance',
        ]);
        form.sheets['2026-07/a'].closing = '0';
        form.sheets['2026-07/a'].rows = [];
        expect(sheetProblems(form, months, account, '2026-07')).toEqual([]);
    });

    it('sends a transfer and a put-back with the references of their other sides', () => {
        const form = single();
        form.accounts.push({
            key: 'b',
            name: 'MoMo',
            from: '2026-07',
            until: null,
        });
        form.sheets['2026-07/a'].rows = [
            row({
                key: 'd',
                direction: 'out',
                classification: 'owner_draw',
                amount: '30',
            }),
            row({
                key: 'r',
                classification: 'owner_return',
                amount: '30',
                link: '2026-07/a/d',
            }),
            row({
                key: 't',
                direction: 'out',
                classification: 'transfer',
                amount: '20',
                link: '2026-07/b/u',
                reference: 'T-1',
            }),
        ];
        form.sheets['2026-07/a'].closing = '-20';
        form.sheets['2026-07/b'] = {
            opening: '0',
            closing: '20',
            sources: ['doc'],
            rows: [
                row({
                    key: 'u',
                    classification: 'transfer',
                    amount: '20',
                    link: '2026-07/a/t',
                }),
            ],
        };

        expect(problemsOf(form)).toEqual([]);
        const [bank, momo] = payloadOf(form).statements;
        expect(bank.transactions[1].return_of).toEqual({
            rail_id: 'BK',
            month: '2026-07',
            reference: 'row-1',
        });
        expect(bank.transactions[2].transfer_of).toEqual({
            rail_id: 'MoMo',
            month: '2026-07',
            reference: 'row-1',
        });
        expect(momo.transactions[0].transfer_of).toEqual({
            rail_id: 'BK',
            month: '2026-07',
            reference: 'T-1',
        });
        expect(bank.transactions[0]).toMatchObject({
            amount: '-30',
            return_of: null,
            transfer_of: null,
            exception_id: null,
        });
    });
});
