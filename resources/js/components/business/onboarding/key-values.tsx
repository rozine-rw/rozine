import type { ReactNode } from 'react';

/** The design's key–value card: label left, value right, hairline between rows (L1582–1590). */
export function KeyValues({
    rows,
}: {
    rows: { label: string; value: ReactNode }[];
}) {
    return (
        <dl className="rounded-2xl border border-rz-border bg-rz-surface px-4 py-1">
            {rows.map((row, index) => (
                <div
                    key={row.label}
                    className={
                        index < rows.length - 1
                            ? 'flex items-center justify-between gap-3 border-b border-[#eef2f9] py-[13px] dark:border-rz-divider'
                            : 'flex items-center justify-between gap-3 py-[13px]'
                    }
                >
                    <dt className="shrink-0 text-[13px] text-rz-secondary">
                        {row.label}
                    </dt>
                    <dd className="text-right text-[13px] font-semibold text-rz-ink">
                        {row.value}
                    </dd>
                </div>
            ))}
        </dl>
    );
}
