import { useState } from 'react';
import { formatTimestamp } from '@/components/admin/format';
import { ReasonStage } from '@/components/admin/reason-stage';
import {
    CARD,
    CARD_SHADOW,
    CardTitle,
    EmptyState,
    EXPLAIN,
    HeadCell,
    ROW_RULE,
    TABLE_HEAD,
    TableCard,
} from '@/components/admin/ui';
import { useTranslation } from '@/hooks/use-translation';
import { formatRwf } from '@/lib/rozine/format';
import { cn } from '@/lib/utils';
import type { RouteAction } from '@/types';
import type {
    MarketHalt,
    MarketListing,
    MarketNote,
    MarketOversight as Oversight,
    StaffViewer,
} from '@/types/admin';

const GRID =
    'grid grid-cols-[minmax(0,2fr)_minmax(0,0.8fr)_minmax(0,1fr)_minmax(0,1.2fr)_auto] gap-3 px-5';

const NOTE_GRID = 'grid grid-cols-[minmax(0,1fr)_auto] gap-3 px-5';

const DANGER_BUTTON =
    'h-[32px] rounded-[9px] border border-[#e5484d] px-3 text-[12.5px] font-bold whitespace-nowrap text-[#c2292e] dark:text-rz-danger-text';

const QUIET_BUTTON =
    'h-[32px] rounded-[9px] border border-rz-hairline px-3 text-[12.5px] font-semibold whitespace-nowrap text-rz-slate';

/** The command waiting for its written reason. */
type Stage =
    | { kind: 'halt_market' }
    | { kind: 'halt_note'; note: MarketNote }
    | { kind: 'lift'; halt: MarketHalt }
    | { kind: 'cancel'; listing: MarketListing };

/**
 * Compliance's controls on the Secondary Market page (BRS v3.0 SEC-R08, SEC-R09): halt the whole
 * market or one note (listed or not), lift a halt in force, and cancel an open listing. Every command goes through
 * the reason stage, and the server records the reason and refuses what no longer applies.
 */
export function MarketOversight({
    oversight,
    viewer,
}: {
    oversight: Oversight;
    viewer: StaffViewer;
}) {
    const { t } = useTranslation();
    const [stage, setStage] = useState<Stage | null>(null);
    const [requestId, setRequestId] = useState(() => crypto.randomUUID());

    const open = (next: Stage) => {
        setRequestId(crypto.randomUUID());
        setStage(next);
    };

    const staged = stage === null ? null : stageOf(stage, oversight);

    return (
        <section
            aria-label={t('admin.market.title')}
            className="mb-[18px] flex flex-col gap-[18px]"
        >
            <div className={cn(CARD, CARD_SHADOW, 'rounded-[20px] p-5')}>
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div className="min-w-0 flex-1">
                        <CardTitle>{t('admin.market.title')}</CardTitle>
                        <p
                            className={cn(
                                'mt-1 text-[12.5px] leading-[1.55]',
                                EXPLAIN,
                            )}
                        >
                            {t(
                                oversight.market_halted
                                    ? 'admin.market.state.halted'
                                    : oversight.mode === 'live'
                                      ? 'admin.market.state.open'
                                      : oversight.mode === 'simulated'
                                        ? 'admin.market.state.simulated'
                                        : 'admin.market.state.off',
                            )}
                        </p>
                    </div>
                    {oversight.halt !== null && (
                        <button
                            type="button"
                            onClick={() => open({ kind: 'halt_market' })}
                            className={DANGER_BUTTON}
                        >
                            {t('admin.market.halt_market')}
                        </button>
                    )}
                </div>
                {staged !== null && stage !== null && (
                    <ReasonStage
                        key={requestId}
                        title={staged.title}
                        body={t(`admin.market.stage.${stage.kind}.body`)}
                        cta={t(`admin.market.stage.${stage.kind}.cta`)}
                        placeholder={t('admin.market.stage.placeholder')}
                        tone={stage.kind === 'lift' ? 'green' : 'red'}
                        action={staged.action}
                        viewer={viewer}
                        onCancel={() => setStage(null)}
                        payload={{ request_id: requestId, ...staged.payload }}
                    />
                )}
            </div>
            <TableCard
                label={t('admin.market.halts.table')}
                minWidth="min-w-[560px]"
                header={
                    <div className="px-5 pt-4 pb-2">
                        <CardTitle>{t('admin.market.halts.title')}</CardTitle>
                    </div>
                }
                empty={
                    oversight.halts.length === 0 && (
                        <EmptyState title={t('admin.market.halts.empty')} />
                    )
                }
            >
                {oversight.halts.length > 0 && (
                    <div
                        role="row"
                        className={cn(
                            'grid grid-cols-[minmax(0,1.4fr)_minmax(0,2fr)_minmax(0,1fr)_auto] gap-3 px-5',
                            TABLE_HEAD,
                        )}
                    >
                        <HeadCell>{t('admin.market.col.scope')}</HeadCell>
                        <HeadCell>{t('admin.market.col.reason')}</HeadCell>
                        <HeadCell>{t('admin.market.col.halted_at')}</HeadCell>
                        <HeadCell>
                            <span className="sr-only">
                                {t('admin.market.col.actions')}
                            </span>
                        </HeadCell>
                    </div>
                )}
                {oversight.halts.map((halt) => (
                    <div
                        key={halt.halt_id}
                        role="row"
                        className={cn(
                            'grid grid-cols-[minmax(0,1.4fr)_minmax(0,2fr)_minmax(0,1fr)_auto] gap-3 px-5',
                            ROW_RULE,
                            'items-center py-3',
                        )}
                    >
                        <div
                            role="cell"
                            className="truncate text-[13px] font-semibold text-rz-ink"
                        >
                            {halt.deal_name ?? t('admin.market.whole_market')}
                        </div>
                        <div
                            role="cell"
                            className="text-[12.5px] break-words text-rz-body"
                        >
                            {halt.reason}
                        </div>
                        <div
                            role="cell"
                            className="text-[12px] text-rz-muted tabular-nums"
                        >
                            <time dateTime={halt.halted_at}>
                                {formatTimestamp(halt.halted_at)}
                            </time>
                        </div>
                        <div role="cell">
                            <button
                                type="button"
                                onClick={() => open({ kind: 'lift', halt })}
                                aria-label={t('admin.market.lift_label', {
                                    scope:
                                        halt.deal_name ??
                                        t('admin.market.whole_market'),
                                })}
                                className={QUIET_BUTTON}
                            >
                                {t('admin.market.lift')}
                            </button>
                        </div>
                    </div>
                ))}
            </TableCard>
            <TableCard
                label={t('admin.market.listings.table')}
                minWidth="min-w-[720px]"
                header={
                    <div className="px-5 pt-4 pb-2">
                        <CardTitle>
                            {t('admin.market.listings.title')}
                        </CardTitle>
                    </div>
                }
                empty={
                    oversight.listings.length === 0 && (
                        <EmptyState title={t('admin.market.listings.empty')} />
                    )
                }
            >
                {oversight.listings.length > 0 && (
                    <div role="row" className={cn(GRID, TABLE_HEAD)}>
                        <HeadCell>{t('admin.market.col.note')}</HeadCell>
                        <HeadCell end>{t('admin.market.col.units')}</HeadCell>
                        <HeadCell end>{t('admin.market.col.price')}</HeadCell>
                        <HeadCell>{t('admin.market.col.expires_at')}</HeadCell>
                        <HeadCell>
                            <span className="sr-only">
                                {t('admin.market.col.actions')}
                            </span>
                        </HeadCell>
                    </div>
                )}
                {oversight.listings.map((listing) => (
                    <div
                        key={listing.listing_id}
                        role="row"
                        className={cn(GRID, ROW_RULE, 'items-center py-3')}
                    >
                        <div
                            role="cell"
                            className="truncate text-[13px] font-semibold text-rz-ink"
                        >
                            {listing.deal_name}
                        </div>
                        <div
                            role="cell"
                            className="text-right text-[12.5px] text-rz-body tabular-nums"
                        >
                            {listing.remaining_units}
                        </div>
                        <div
                            role="cell"
                            className="text-right text-[12.5px] text-rz-body tabular-nums"
                        >
                            {formatRwf(listing.price)}
                        </div>
                        <div
                            role="cell"
                            className="text-[12px] text-rz-muted tabular-nums"
                        >
                            <time dateTime={listing.expires_at}>
                                {formatTimestamp(listing.expires_at)}
                            </time>
                        </div>
                        <div role="cell" className="flex gap-2">
                            {listing.halt_note !== null && (
                                <button
                                    type="button"
                                    onClick={() =>
                                        open({
                                            kind: 'halt_note',
                                            note: {
                                                note_id: listing.note_id,
                                                deal_name: listing.deal_name,
                                                halt: listing.halt_note,
                                            },
                                        })
                                    }
                                    aria-label={t(
                                        'admin.market.halt_note_label',
                                        { note: listing.deal_name },
                                    )}
                                    className={QUIET_BUTTON}
                                >
                                    {t('admin.market.halt_note')}
                                </button>
                            )}
                            {listing.cancel !== null && (
                                <button
                                    type="button"
                                    onClick={() =>
                                        open({ kind: 'cancel', listing })
                                    }
                                    aria-label={t('admin.market.cancel_label', {
                                        note: listing.deal_name,
                                    })}
                                    className={DANGER_BUTTON}
                                >
                                    {t('admin.market.cancel')}
                                </button>
                            )}
                        </div>
                    </div>
                ))}
            </TableCard>
            <TableCard
                label={t('admin.market.notes.table')}
                minWidth="min-w-[420px]"
                header={
                    <div className="px-5 pt-4 pb-2">
                        <CardTitle>{t('admin.market.notes.title')}</CardTitle>
                        <p
                            className={cn(
                                'mt-1 text-[12.5px] leading-[1.55]',
                                EXPLAIN,
                            )}
                        >
                            {t('admin.market.notes.explain')}
                        </p>
                    </div>
                }
                empty={
                    oversight.notes.length === 0 && (
                        <EmptyState title={t('admin.market.notes.empty')} />
                    )
                }
            >
                {oversight.notes.length > 0 && (
                    <div role="row" className={cn(NOTE_GRID, TABLE_HEAD)}>
                        <HeadCell>{t('admin.market.col.note')}</HeadCell>
                        <HeadCell>
                            <span className="sr-only">
                                {t('admin.market.col.actions')}
                            </span>
                        </HeadCell>
                    </div>
                )}
                {oversight.notes.map((note) => (
                    <div
                        key={note.note_id}
                        role="row"
                        className={cn(NOTE_GRID, ROW_RULE, 'items-center py-3')}
                    >
                        <div
                            role="cell"
                            className="truncate text-[13px] font-semibold text-rz-ink"
                        >
                            {note.deal_name}
                        </div>
                        <div role="cell">
                            {note.halt === null ? (
                                <span className="text-[12px] font-semibold text-rz-muted">
                                    {t('admin.market.notes.halted')}
                                </span>
                            ) : (
                                <button
                                    type="button"
                                    onClick={() =>
                                        open({ kind: 'halt_note', note })
                                    }
                                    aria-label={t(
                                        'admin.market.halt_note_label',
                                        { note: note.deal_name },
                                    )}
                                    className={QUIET_BUTTON}
                                >
                                    {t('admin.market.halt_note')}
                                </button>
                            )}
                        </div>
                    </div>
                ))}
            </TableCard>
        </section>
    );

    function stageOf(
        current: Stage,
        board: Oversight,
    ): {
        title: string;
        action: RouteAction;
        payload: Record<string, string>;
    } | null {
        switch (current.kind) {
            case 'halt_market':
                return board.halt === null
                    ? null
                    : {
                          title: t('admin.market.stage.halt_market.title'),
                          action: board.halt,
                          payload: {},
                      };
            case 'halt_note':
                return current.note.halt === null
                    ? null
                    : {
                          title: t('admin.market.stage.halt_note.title', {
                              note: current.note.deal_name,
                          }),
                          action: current.note.halt,
                          payload: { note_id: current.note.note_id },
                      };
            case 'lift':
                return {
                    title: t('admin.market.stage.lift.title', {
                        scope:
                            current.halt.deal_name ??
                            t('admin.market.whole_market'),
                    }),
                    action: current.halt.lift,
                    payload:
                        current.halt.note_id === null
                            ? {}
                            : { note_id: current.halt.note_id },
                };
            case 'cancel':
                return current.listing.cancel === null
                    ? null
                    : {
                          title: t('admin.market.stage.cancel.title', {
                              note: current.listing.deal_name,
                          }),
                          action: current.listing.cancel,
                          payload: {},
                      };
        }
    }
}
