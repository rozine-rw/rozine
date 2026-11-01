import type {
    StatementClassification,
    StatementMonth,
    StatementRail,
} from '@/types/business';

/**
 * The statement-figures form (#396, contract initial-statement-audit-1 §5) as plain data, and the
 * checks the server's reconciliation will make, so the page can say what is missing before saving.
 * The server stays the authority: it reconciles the saved figures again and verifies nothing.
 */

export type Direction = 'in' | 'out';

export type TransactionRow = {
    key: string;
    reference: string;
    date: string;
    direction: Direction;
    /** Whole francs, digits only. */
    amount: string;
    classification: StatementClassification | '';
    /** `month/accountKey/rowKey` of the matching transfer or of the owner withdrawal repaid. */
    link: string | null;
};

export type Sheet = {
    opening: string;
    closing: string;
    sources: string[];
    rows: TransactionRow[];
};

export type Account = {
    key: string;
    name: string;
    from: string;
    until: string | null;
};

export type TranscriptionForm = {
    first: string;
    last: string;
    accounts: Account[];
    /** Keyed `month/accountKey`. */
    sheets: Record<string, Sheet>;
};

export type Problem = {
    code:
        | 'months'
        | 'too_many_months'
        | 'no_accounts'
        | 'account_name'
        | 'account_duplicate'
        | 'account_dates'
        | 'sources'
        | 'balance'
        | 'row_amount'
        | 'row_date'
        | 'row_reference'
        | 'row_type'
        | 'row_link'
        | 'difference'
        | 'closed_balance';
    month?: string;
    account?: string;
};

export const MAX_MONTHS = 36;

export const CLASSIFICATIONS: Record<Direction, StatementClassification[]> = {
    in: ['operating_inflow', 'financing', 'transfer', 'owner_return'],
    out: ['operating_outflow', 'debt_service', 'owner_draw', 'transfer'],
};

const IDENTIFIER = /^[a-zA-Z0-9_-]{1,128}$/u;
const WHOLE = /^(0|-?[1-9][0-9]{0,63})$/u;
const MONTH = /^\d{4}-(0[1-9]|1[0-2])$/u;

let sequence = 0;

/** A key that only this page uses to keep rows and accounts apart while they are edited. */
export const newKey = (prefix: string): string => `${prefix}${++sequence}`;

/** Letters, digits, `-` and `_` only, as the server accepts for account names and references. */
export const cleanIdentifier = (value: string): string =>
    value.replace(/\s+/gu, '-').replace(/[^a-zA-Z0-9_-]/gu, '');

/**
 * A franc amount as typed or pasted, as digits (with a leading minus when `signed`). Thousands
 * separators, a trailing separator and a `.00` or `,00` tail are dropped, so `1,250.00`, `1.250`,
 * `1 250` and `1250.` all read 1250. Anything that is not plainly whole francs, such as `1,250.50`
 * or `12,50`, is returned as typed, so it is flagged rather than read as 125050 or 1250. The form
 * keeps what was typed and reads every figure through this, tidying a field only on blur, so
 * typing 1250.50 one key at a time is never read as 125050 either.
 */
export function cleanFrancs(value: string, signed: boolean): string {
    const text = value.replace(/[^0-9.,\s'’-]/gu, '').trim();
    const match = (
        signed ? /^(-?)([0-9.,\s'’]*)$/u : /^()([0-9.,\s'’]*)$/u
    ).exec(text);

    if (match === null) {
        return text;
    }

    const [, sign, typed] = match;
    const body = typed.replace(/[.,'’]$/u, '');
    const cents = /^(.*)([.,])00$/u.exec(body);
    const whole =
        cents !== null && !cents[1].includes(cents[2]) ? cents[1] : body;

    if (/^[0-9]*$/u.test(whole)) {
        return sign + whole;
    }

    return /^[0-9]{1,3}([.,\s'’][0-9]{3})+$/u.test(whole)
        ? sign + whole.replace(/[^0-9]/gu, '')
        : text;
}

/** Every month from `first` to `last`, or none when the range is not a range. */
export function monthsBetween(first: string, last: string): string[] {
    if (!MONTH.test(first) || !MONTH.test(last) || first > last) {
        return [];
    }

    const months: string[] = [];
    let [year, month] = first.split('-').map(Number);

    for (let current = first; current <= last && months.length <= MAX_MONTHS;) {
        months.push(current);
        month += 1;

        if (month > 12) {
            month = 1;
            year += 1;
        }

        current = `${year}-${String(month).padStart(2, '0')}`;
    }

    return months;
}

export const sheetKey = (month: string, account: string): string =>
    `${month}/${account}`;

export const isActive = (account: Account, month: string): boolean =>
    month >= account.from && (account.until === null || month <= account.until);

export const blankSheet = (): Sheet => ({
    opening: '',
    closing: '',
    sources: [],
    rows: [],
});

export const sheetOf = (
    form: TranscriptionForm,
    month: string,
    account: string,
): Sheet => form.sheets[sheetKey(month, account)] ?? blankSheet();

/** The reference sent for a row: the one typed, or its place in the month. */
export const referenceOf = (row: TransactionRow, index: number): string =>
    row.reference === '' ? `row-${index + 1}` : row.reference;

/** A row's signed amount in francs, or null while it is not a whole number. */
export function signedAmount(row: TransactionRow): bigint | null {
    const digits = cleanFrancs(row.amount, false);

    if (!/^[0-9]{1,64}$/u.test(digits)) {
        return null;
    }

    const amount = BigInt(digits);

    return row.direction === 'out' ? -amount : amount;
}

function balanceOf(typed: string): bigint | null {
    const value = cleanFrancs(typed, true);

    return WHOLE.test(value) ? BigInt(value) : null;
}

/**
 * The opening balance a month must start from: 0 in the month an account opened after the first
 * month, the previous month's closing balance while the account was open then, otherwise as typed.
 */
export function openingOf(
    form: TranscriptionForm,
    months: string[],
    account: Account,
    month: string,
): { value: string; fixed: boolean } {
    const index = months.indexOf(month);

    if (account.from === month && index > 0) {
        return { value: '0', fixed: true };
    }

    const previous = months[index - 1];

    if (previous !== undefined && isActive(account, previous)) {
        return {
            value: sheetOf(form, previous, account.key).closing,
            fixed: true,
        };
    }

    return { value: sheetOf(form, month, account.key).opening, fixed: false };
}

/** Opening balance plus the month's transactions, or null while a figure is incomplete. */
export function computedClosing(
    opening: string,
    rows: TransactionRow[],
): bigint | null {
    let total = balanceOf(opening);

    for (const row of rows) {
        const amount = signedAmount(row);

        if (total === null || amount === null) {
            return null;
        }

        total += amount;
    }

    return total;
}

/** Owner withdrawals a put-back in `month` may repay: that month or earlier, any account. */
export function drawsBefore(
    form: TranscriptionForm,
    months: string[],
    month: string,
): { link: string; month: string; account: Account; row: TransactionRow }[] {
    return months
        .filter((candidate) => candidate <= month)
        .flatMap((candidate) =>
            form.accounts.flatMap((account) =>
                sheetOf(form, candidate, account.key)
                    .rows.filter((row) => row.classification === 'owner_draw')
                    .map((row) => ({
                        link: `${candidate}/${account.key}/${row.key}`,
                        month: candidate,
                        account,
                        row,
                    })),
            ),
        );
}

/** Transfers on another account in the same month that could be this one's other side. */
export function transferCandidates(
    form: TranscriptionForm,
    month: string,
    account: string,
): { link: string; account: Account; row: TransactionRow }[] {
    return form.accounts
        .filter((other) => other.key !== account && isActive(other, month))
        .flatMap((other) =>
            sheetOf(form, month, other.key)
                .rows.filter((row) => row.classification === 'transfer')
                .map((row) => ({
                    link: `${month}/${other.key}/${row.key}`,
                    account: other,
                    row,
                })),
        );
}

const findRow = (
    form: TranscriptionForm,
    link: string,
): { month: string; account: string; row: TransactionRow } | null => {
    const [month, account, key] = link.split('/');
    const row = sheetOf(form, month, account).rows.find(
        (candidate) => candidate.key === key,
    );

    return row === undefined ? null : { month, account, row };
};

/** Replaces one row of one month, leaving the rest of the form as it was. */
export function updateRow(
    form: TranscriptionForm,
    month: string,
    account: string,
    key: string,
    change: Partial<TransactionRow>,
): TranscriptionForm {
    const sheet = sheetOf(form, month, account);

    return {
        ...form,
        sheets: {
            ...form.sheets,
            [sheetKey(month, account)]: {
                ...sheet,
                rows: sheet.rows.map((row) =>
                    row.key === key ? { ...row, ...change } : row,
                ),
            },
        },
    };
}

/**
 * Pairs two transfers both ways, as the server requires, and lets go of whatever either of them
 * was paired with before.
 */
export function pairTransfer(
    form: TranscriptionForm,
    month: string,
    account: string,
    key: string,
    link: string | null,
): TranscriptionForm {
    const self = `${month}/${account}/${key}`;
    const before = findRow(form, self)?.row.link ?? null;
    let next = updateRow(form, month, account, key, { link });

    for (const other of [before, link]) {
        const found = other === null ? null : findRow(next, other);

        if (found !== null) {
            next = updateRow(next, found.month, found.account, found.row.key, {
                link: other === link ? self : null,
            });
        }
    }

    return next;
}

/** Reads a saved transcription back into the form, or starts an empty one. */
export function formFrom(
    saved: {
        rails: StatementRail[];
        months: string[];
        statements: StatementMonth[];
    } | null,
    defaultMonth: string,
): TranscriptionForm {
    if (saved === null || saved.months.length === 0) {
        return {
            first: defaultMonth,
            last: defaultMonth,
            accounts: [
                { key: newKey('a'), name: '', from: defaultMonth, until: null },
            ],
            sheets: {},
        };
    }

    const months = [...saved.months].sort();
    const accounts = saved.rails.map((rail) => ({
        key: newKey('a'),
        name: rail.id,
        from: rail.active_from,
        until: rail.active_until,
    }));
    const accountKeys = new Map(
        accounts.map((account) => [account.name, account.key]),
    );
    const accountKey = (id: string): string => accountKeys.get(id) as string;
    const rowKeys = new Map<string, string>();
    const sheets: Record<string, Sheet> = {};

    for (const statement of saved.statements) {
        statement.transactions.forEach((transaction) => {
            rowKeys.set(
                `${statement.month}/${statement.rail_id}/${transaction.reference}`,
                newKey('r'),
            );
        });
    }

    const linkOf = (
        reference: { rail_id: string; month: string; reference: string } | null,
    ): string | null => {
        const key =
            reference &&
            rowKeys.get(
                `${reference.month}/${reference.rail_id}/${reference.reference}`,
            );

        return key
            ? `${reference.month}/${accountKey(reference.rail_id)}/${key}`
            : null;
    };

    for (const statement of saved.statements) {
        sheets[sheetKey(statement.month, accountKey(statement.rail_id))] = {
            opening: statement.opening_balance,
            closing: statement.closing_balance,
            sources: statement.source_ids,
            rows: statement.transactions.map((transaction) => ({
                key: rowKeys.get(
                    `${statement.month}/${statement.rail_id}/${transaction.reference}`,
                ) as string,
                reference: transaction.reference,
                date: transaction.date,
                direction: transaction.amount.startsWith('-') ? 'out' : 'in',
                amount: transaction.amount.replace(/^-/u, ''),
                classification: transaction.classification,
                link: linkOf(transaction.transfer_of ?? transaction.return_of),
            })),
        };
    }

    return {
        first: months[0],
        last: months[months.length - 1],
        accounts,
        sheets,
    };
}

/** Everything the server's reconciliation would refuse that the form can see for itself. */
export function problemsOf(form: TranscriptionForm): Problem[] {
    const months = monthsBetween(form.first, form.last);

    if (months.length === 0) {
        return [{ code: 'months' }];
    }

    if (months.length > MAX_MONTHS) {
        return [{ code: 'too_many_months' }];
    }

    if (form.accounts.length === 0) {
        return [{ code: 'no_accounts' }];
    }

    const problems: Problem[] = [];
    const names = form.accounts.map((account) => account.name);

    for (const account of form.accounts) {
        if (!IDENTIFIER.test(account.name)) {
            problems.push({ code: 'account_name', account: account.key });
        } else if (
            names.indexOf(account.name) !== names.lastIndexOf(account.name)
        ) {
            problems.push({ code: 'account_duplicate', account: account.key });
        }

        if (account.until !== null && account.until < account.from) {
            problems.push({ code: 'account_dates', account: account.key });
        }
    }

    for (const month of months) {
        for (const account of form.accounts.filter((candidate) =>
            isActive(candidate, month),
        )) {
            problems.push(
                ...sheetProblems(form, months, account, month).map((code) => ({
                    code,
                    month,
                    account: account.key,
                })),
            );
        }
    }

    return problems;
}

/** The problems on one account's month, in the order the form shows its fields. */
export function sheetProblems(
    form: TranscriptionForm,
    months: string[],
    account: Account,
    month: string,
): Problem['code'][] {
    const sheet = sheetOf(form, month, account.key);
    const opening = openingOf(form, months, account, month).value;
    const codes = new Set<Problem['code']>();
    const references = sheet.rows.map(referenceOf);

    if (sheet.sources.length === 0) {
        codes.add('sources');
    }

    if (balanceOf(opening) === null || balanceOf(sheet.closing) === null) {
        codes.add('balance');
    }

    sheet.rows.forEach((row, index) => {
        if (signedAmount(row) === null) {
            codes.add('row_amount');
        }

        if (
            !row.date.startsWith(`${month}-`) ||
            !/^\d{4}-\d{2}-\d{2}$/u.test(row.date)
        ) {
            codes.add('row_date');
        }

        if (
            !IDENTIFIER.test(references[index]) ||
            references.indexOf(references[index]) !== index
        ) {
            codes.add('row_reference');
        }

        if (
            row.classification === '' ||
            !CLASSIFICATIONS[row.direction].includes(row.classification)
        ) {
            codes.add('row_type');
        } else if (
            ['transfer', 'owner_return'].includes(row.classification) &&
            (row.link === null || findRow(form, row.link) === null)
        ) {
            codes.add('row_link');
        }
    });

    const computed = computedClosing(opening, sheet.rows);
    const closing = balanceOf(sheet.closing);

    if (computed !== null && closing !== null && computed !== closing) {
        codes.add('difference');
    }

    if (account.until === month && closing !== null && closing !== 0n) {
        codes.add('closed_balance');
    }

    return [...codes];
}

/** The saved figures as the server's `rails`, `months` and `statements`. */
export function payloadOf(form: TranscriptionForm): {
    rails: StatementRail[];
    months: string[];
    statements: StatementMonth[];
} {
    const months = monthsBetween(form.first, form.last);
    const names = new Map(
        form.accounts.map((account) => [account.key, account.name]),
    );
    const referenceAt = (link: string) => {
        const [month, account, key] = link.split('/');
        const rows = sheetOf(form, month, account).rows;
        const index = rows.findIndex((row) => row.key === key);

        return {
            rail_id: names.get(account) as string,
            month,
            reference: referenceOf(rows[index], index),
        };
    };

    return {
        rails: form.accounts.map((account) => ({
            id: account.name,
            active_from: account.from,
            active_until: account.until,
        })),
        months,
        statements: months.flatMap((month) =>
            form.accounts
                .filter((account) => isActive(account, month))
                .map((account) => {
                    const sheet = sheetOf(form, month, account.key);

                    return {
                        rail_id: account.name,
                        month,
                        opening_balance: cleanFrancs(
                            openingOf(form, months, account, month).value,
                            true,
                        ),
                        closing_balance: cleanFrancs(sheet.closing, true),
                        source_ids: sheet.sources,
                        transactions: sheet.rows.map((row, index) => ({
                            reference: referenceOf(row, index),
                            date: row.date,
                            amount: String(signedAmount(row)),
                            classification:
                                row.classification as StatementClassification,
                            source_ids: sheet.sources,
                            return_of:
                                row.classification === 'owner_return'
                                    ? referenceAt(row.link as string)
                                    : null,
                            transfer_of:
                                row.classification === 'transfer'
                                    ? referenceAt(row.link as string)
                                    : null,
                            exception_id: null,
                        })),
                    };
                }),
        ),
    };
}
