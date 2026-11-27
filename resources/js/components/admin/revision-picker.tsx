import { FieldError } from '@/components/rozine/form';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';

export type PickedItem = { key: string; note: string };

type Group =
    | 'company'
    | 'people'
    | 'licence'
    | 'coverage'
    | 'documents'
    | 'institution'
    | 'details';

const groupOf = (key: string): Group => {
    const prefix = key.split('.')[0];

    if (
        prefix === 'company' ||
        prefix === 'licence' ||
        prefix === 'coverage' ||
        prefix === 'documents' ||
        prefix === 'institution'
    ) {
        return prefix;
    }

    if (
        prefix === 'representative' ||
        prefix === 'directors' ||
        prefix === 'owners'
    ) {
        return 'people';
    }

    return 'details';
};

const ORDER: Group[] = [
    'details',
    'company',
    'people',
    'licence',
    'coverage',
    'institution',
    'documents',
];

/**
 * Compliance ticks each answer or document the applicant must change, with an optional word on
 * what to fix (Erastus, 24 Nov 2026; design "Compliance picks what to revise"). The applicant sees
 * them flagged on their form and in the e-mail.
 */
export function RevisionPicker({
    revisable,
    value,
    onChange,
    error,
    idPrefix,
}: {
    revisable: Record<string, string>;
    value: PickedItem[];
    onChange: (items: PickedItem[]) => void;
    error?: string;
    idPrefix: string;
}) {
    const { t } = useTranslation();
    const picked = new Map(value.map((item) => [item.key, item.note]));
    const groups = ORDER.map((group) => ({
        group,
        keys: Object.keys(revisable).filter((key) => groupOf(key) === group),
    })).filter((entry) => entry.keys.length > 0);
    const toggle = (key: string) =>
        onChange(
            picked.has(key)
                ? value.filter((item) => item.key !== key)
                : [...value, { key, note: '' }],
        );
    const write = (key: string, note: string) =>
        onChange(
            value.map((item) => (item.key === key ? { key, note } : item)),
        );

    return (
        <fieldset className="mt-3 flex flex-col gap-3">
            <legend className="text-[13px] font-semibold text-rz-slate">
                {t('admin.revision.legend')}
            </legend>
            {groups.map(({ group, keys }) => (
                <div key={group} className="flex flex-col gap-2">
                    <p className="text-[11px] font-bold tracking-[.06em] text-rz-muted uppercase">
                        {t(`admin.revision.group.${group}`)}
                    </p>
                    {keys.map((key) => {
                        const checked = picked.has(key);
                        const id = `${idPrefix}-${key.replace(/[^a-z0-9]+/gi, '-')}`;

                        return (
                            <div
                                key={key}
                                className={cn(
                                    'rounded-xl border px-3 py-2.5',
                                    checked
                                        ? 'border-2 border-rz-accent-fill bg-rz-accent-soft'
                                        : 'border-rz-hairline',
                                )}
                            >
                                <label
                                    htmlFor={id}
                                    className="flex items-center gap-2.5 text-[13.5px] text-rz-ink"
                                >
                                    <input
                                        id={id}
                                        type="checkbox"
                                        aria-describedby={`${idPrefix}-error`}
                                        checked={checked}
                                        onChange={() => toggle(key)}
                                        className="size-[18px] accent-rz-accent-fill"
                                    />
                                    <span
                                        className={cn(
                                            'flex-1',
                                            checked && 'font-semibold',
                                        )}
                                    >
                                        {revisable[key]}
                                    </span>
                                </label>
                                {checked && (
                                    <input
                                        type="text"
                                        maxLength={300}
                                        value={picked.get(key) ?? ''}
                                        onChange={(event) =>
                                            write(key, event.target.value)
                                        }
                                        aria-label={t(
                                            'admin.revision.note_label',
                                            {
                                                item: revisable[key],
                                            },
                                        )}
                                        placeholder={t(
                                            'admin.revision.note_placeholder',
                                        )}
                                        className="mt-2 h-10 w-full rounded-[10px] border border-rz-field-border bg-rz-field px-2.5 text-[13px] text-rz-ink outline-none placeholder:text-rz-placeholder focus:border-rz-focus-border"
                                    />
                                )}
                            </div>
                        );
                    })}
                </div>
            ))}
            <FieldError id={`${idPrefix}-error`}>{error}</FieldError>
        </fieldset>
    );
}
