/** The parts of a registered address, as the KYB company step captures them (24 Nov 2026). */
const ADDRESS_PARTS = [
    'province',
    'district',
    'sector',
    'cell',
    'street',
] as const;

/**
 * Item keys that name the company's whole registered address. `company.registered_address` is the
 * key a send-back recorded before the address was split into parts (QA #771).
 */
const ADDRESS_KEYS = ['company.address', 'company.registered_address'];

/** The key an item is flagged under on the form: an old address or district item flags the address. */
export function flagKey(key: string): string {
    return ADDRESS_KEYS.includes(key) || key === 'company.district'
        ? 'company.address'
        : key;
}

/**
 * The answer at a dotted key such as `company.tin`. The registered address has no single answer, so
 * its key reads the five parts together; comparing that before and after tells whether any changed.
 */
export function answerAt(answers: object | undefined, key: string): unknown {
    if (ADDRESS_KEYS.includes(key)) {
        const company = (
            answers as { company?: Record<string, unknown> } | undefined
        )?.company;

        return company === undefined
            ? undefined
            : Object.fromEntries(
                  ADDRESS_PARTS.map((part) => [part, company[part] ?? '']),
              );
    }

    return key
        .split('.')
        .reduce<unknown>(
            (value, part) =>
                value !== null && typeof value === 'object'
                    ? (value as Record<string, unknown>)[part]
                    : undefined,
            answers,
        );
}
