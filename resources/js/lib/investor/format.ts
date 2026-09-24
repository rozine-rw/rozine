import { formatRwf } from '@/lib/rozine/format';
import type { Money } from '@/types';

/**
 * Investor display formatting, exactly as the Investor design writes figures. Presentation only:
 * each helper turns one server value into text and nothing it returns feeds a decision.
 */
const francs = (money: Money): number => Number(money.amount);

const trimZero = (value: string): string => value.replace(/\.0$/u, '');

/** Digits without currency, compact: "950", "581K", "1.5M". `keepDecimal` keeps "6.0M". */
export const formatCompactBare = (
    money: Money,
    keepDecimal = false,
): string => {
    const value = Math.abs(francs(money));

    if (value >= 1e6) {
        const millions = (value / 1e6).toFixed(1);

        return `${keepDecimal ? millions : trimZero(millions)}M`;
    }

    if (value >= 1e3) {
        return `${Math.round(value / 1e3)}K`;
    }

    return String(value);
};

/** "RWF 581K", "RWF 1.5M". */
export const formatCompact = (money: Money, keepDecimal = false): string =>
    `RWF ${formatCompactBare(money, keepDecimal)}`;

/** A signed server amount with an explicit sign: "+RWF 106,667", "-RWF 5,000". */
export const formatSigned = (money: Money): string => {
    const negative = money.amount.startsWith('-');
    const magnitude = {
        ...money,
        amount: negative ? money.amount.slice(1) : money.amount,
    };

    return `${negative ? '-' : '+'}${formatRwf(magnitude)}`;
};

/** A signed compact amount: "+RWF 3.1M", "-RWF 250K". */
export const formatSignedCompact = (
    money: Money,
    keepDecimal = false,
): string =>
    `${money.amount.startsWith('-') ? '-' : '+'}${formatCompact(money, keepDecimal)}`;

/** Whether a signed server amount is below zero (for colour only). */
export const isNegative = (money: Money): boolean =>
    money.amount.startsWith('-');

/** The Intl tag for an app locale: the design writes day-first English, so English is en-GB. */
export const intlTag = (locale: string): string =>
    locale === 'en' ? 'en-GB' : locale;

/** A signed one-decimal percentage from the server ("13.3", "-2.0") with its sign: "+13.3%". */
export const formatSignedPct = (pct: string): string =>
    pct.startsWith('-') ? `${pct}%` : `+${pct}%`;
