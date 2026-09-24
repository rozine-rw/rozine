import { router } from '@inertiajs/react';
import { AdminFrame } from '@/components/admin/admin-frame';
import { DirectoryTable } from '@/components/admin/parties/directory-table';
import { PartyDrawer } from '@/components/admin/parties/party-drawer';
import { SearchEmpty } from '@/components/admin/search-empty';
import {
    KpiTile,
    PolicyPeek,
    SegmentedChips,
    useStatFormatter,
} from '@/components/admin/ui';
import { useTranslation } from '@/hooks/use-translation';
import type { AdminPartiesProps, AdminSection, PartyKind } from '@/types/admin';

const SECTION: Record<PartyKind, AdminSection> = {
    business: 'businesses',
    investor: 'investors',
    auditor: 'auditors',
    staff: 'staff',
};

/**
 * Parties (MVP-ADMIN-SCR-08): the four party types — businesses, investors, Audit Partners and
 * staff — each in its design directory (Business T419–502, Investor T504–564, CPA registry
 * T708–736, Operators T766–791), opening the party 360 with its history and reasoned controls.
 * Filtering and sorting are server queries; the page never re-sorts or re-counts rows itself.
 */
export default function AdminParties(props: AdminPartiesProps) {
    const { t } = useTranslation();
    const format = useStatFormatter();
    const section = SECTION[props.kind];

    return (
        <AdminFrame
            section={section}
            {...props}
            overlay={
                props.party && (
                    <PartyDrawer
                        key={props.party.id}
                        party={props.party}
                        viewer={props.viewer}
                    />
                )
            }
        >
            {props.kind === 'business' && (
                <PolicyPeek
                    title={t('admin.parties.policy_title')}
                    items={props.policy}
                />
            )}
            {props.stats.length > 0 && (
                <div className="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4 lg:gap-4">
                    {props.stats.map((stat, index) => (
                        <KpiTile
                            key={stat.key}
                            index={index}
                            label={t(`admin.parties.stats.${stat.key}`)}
                            value={format(stat.value)}
                        />
                    ))}
                </div>
            )}
            <div className="mb-3.5 flex flex-wrap items-center justify-between gap-3">
                <SegmentedChips
                    label={t('admin.parties.filter')}
                    chips={props.chips.map((chip) => ({
                        ...chip,
                        label: t(`admin.parties.chip.${chip.key}`),
                    }))}
                />
                {props.filters.length > 0 && (
                    <div className="flex flex-wrap items-center gap-2.5">
                        {props.filters.map((filter) => (
                            <select
                                key={filter.key}
                                aria-label={t(
                                    `admin.parties.select.${filter.key}`,
                                )}
                                value={filter.value}
                                onChange={(event) =>
                                    router.reload({
                                        data: {
                                            [filter.key]: event.target.value,
                                        },
                                    })
                                }
                                className="cursor-pointer rounded-[10px] border border-rz-hairline bg-rz-surface px-3 py-[9px] text-[12.5px] font-semibold text-rz-ink outline-none"
                            >
                                {filter.options.map((option) => (
                                    <option
                                        key={option.value}
                                        value={option.value}
                                    >
                                        {option.label}
                                    </option>
                                ))}
                            </select>
                        ))}
                    </div>
                )}
            </div>
            <p className="mb-2.5 text-[12px] font-semibold text-rz-muted">
                {props.shown < props.total
                    ? t(`admin.parties.showing.${props.kind}`, {
                          shown: props.shown,
                          total: props.total,
                      })
                    : t(`admin.parties.count.${props.kind}`, {
                          count: props.total,
                      })}
            </p>
            {props.directory.rows.length === 0 && props.search !== '' && (
                <SearchEmpty section={section} term={props.search} />
            )}
            <DirectoryTable directory={props.directory} />
        </AdminFrame>
    );
}
