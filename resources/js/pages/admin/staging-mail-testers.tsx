import { usePage } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { AdminFrame } from '@/components/admin/admin-frame';
import { formatTimestamp } from '@/components/admin/format';
import { ReasonStage } from '@/components/admin/reason-stage';
import { SearchEmpty } from '@/components/admin/search-empty';
import {
    EmptyState,
    EXPLAIN,
    HeadCell,
    LABEL,
    ROW_RULE,
    TABLE_HEAD,
    TableCard,
} from '@/components/admin/ui';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type {
    AdminStagingMailTestersProps,
    StagingMailTester,
} from '@/types/admin';

const GRID =
    'grid grid-cols-[minmax(0,2fr)_minmax(0,1.2fr)_minmax(0,1fr)_auto] gap-3 px-5';

type Stage =
    | { kind: 'add'; email: string }
    | { kind: 'remove'; tester: StagingMailTester };

/**
 * Superadmin's named staging mail testers (`staging.mail.testers.manage`), on staging only.
 * Staging emails only these addresses and the server's own recipients, which are shown but
 * never edited here. Adding and removing both go through the reason stage; the server rechecks
 * the permission and keeps the reason in the audit trail.
 */
export default function AdminStagingMailTesters(
    props: AdminStagingMailTestersProps,
) {
    const { t } = useTranslation();
    const { errors } = usePage<{ errors: Record<string, string> }>().props;
    const [email, setEmail] = useState('');
    const [stage, setStage] = useState<Stage | null>(null);
    const [requestId, setRequestId] = useState(() => crypto.randomUUID());

    /** Each staged change is a new command, so it gets its own request id. */
    const open = (next: Stage) => {
        setRequestId(crypto.randomUUID());
        setStage(next);
    };

    const stageAdd = (event: FormEvent) => {
        event.preventDefault();
        open({ kind: 'add', email: email.trim() });
    };

    return (
        <AdminFrame section="mail_testers" {...props}>
            <p className={cn('mb-4 max-w-[720px] text-[13px]', EXPLAIN)}>
                {t('admin.mail_testers.intro')}
            </p>
            <section
                aria-label={t('admin.mail_testers.server.title')}
                className="mb-4 max-w-[720px] rounded-[13px] border border-rz-hairline bg-rz-surface px-[18px] py-3.5"
            >
                <h2
                    className={cn(
                        'text-[12px] font-bold tracking-[.05em] uppercase',
                        LABEL,
                    )}
                >
                    {t('admin.mail_testers.server.title')}
                </h2>
                <ul className="mt-2 flex flex-wrap gap-2">
                    {props.server_recipients.map((recipient) => (
                        <li
                            key={recipient}
                            className="rounded-[8px] bg-[#f2f5fa] px-2.5 py-1 font-mono text-[12px] text-rz-ink dark:bg-rz-surface-sunken"
                        >
                            {recipient}
                        </li>
                    ))}
                </ul>
                <p className={cn('mt-2 text-[12px]', EXPLAIN)}>
                    {t('admin.mail_testers.server.body')}
                </p>
            </section>
            {errors.form && (
                <p
                    role="alert"
                    className="mb-4 max-w-[720px] rounded-[11px] bg-[rgba(229,72,77,.1)] px-3.5 py-2.5 text-[12.5px] font-semibold text-[#c2292e]"
                >
                    {errors.form}
                </p>
            )}
            {stage ? (
                <div className="mb-4 max-w-[720px]">
                    <ReasonStage
                        title={t(
                            `admin.mail_testers.stage.${stage.kind}.title`,
                            {
                                email:
                                    stage.kind === 'add'
                                        ? stage.email
                                        : stage.tester.email,
                            },
                        )}
                        body={t(`admin.mail_testers.stage.${stage.kind}.body`)}
                        cta={t(`admin.mail_testers.stage.${stage.kind}.cta`)}
                        placeholder={t(
                            `admin.mail_testers.stage.${stage.kind}.placeholder`,
                        )}
                        tone={stage.kind === 'add' ? 'blue' : 'red'}
                        action={
                            stage.kind === 'add'
                                ? props.add
                                : stage.tester.remove
                        }
                        viewer={props.viewer}
                        onCancel={() => setStage(null)}
                        payload={
                            stage.kind === 'add'
                                ? { request_id: requestId, email: stage.email }
                                : { request_id: requestId }
                        }
                    />
                </div>
            ) : (
                <form
                    onSubmit={stageAdd}
                    aria-label={t('admin.mail_testers.add.label')}
                    className="mb-4 flex max-w-[720px] flex-wrap items-start gap-2.5"
                >
                    <div className="min-w-[240px] flex-1">
                        <label htmlFor="tester-email" className="sr-only">
                            {t('admin.mail_testers.add.email')}
                        </label>
                        <input
                            id="tester-email"
                            type="email"
                            value={email}
                            onChange={(event) => setEmail(event.target.value)}
                            required
                            placeholder={t(
                                'admin.mail_testers.add.placeholder',
                            )}
                            aria-invalid={
                                errors.email !== undefined || undefined
                            }
                            aria-describedby={
                                errors.email ? 'tester-email-error' : undefined
                            }
                            className="h-[42px] w-full rounded-[11px] border border-rz-hairline bg-[#f7f9fd] px-[13px] text-[13px] text-rz-ink outline-none placeholder:text-rz-faint focus:border-rz-focus-border focus:shadow-[0_0_0_3.5px_var(--rz-focus-ring)] dark:bg-rz-field"
                        />
                        {errors.email && (
                            <p
                                id="tester-email-error"
                                role="alert"
                                className="mt-1.5 text-[12px] font-semibold text-[#c2292e]"
                            >
                                {errors.email}
                            </p>
                        )}
                    </div>
                    <button
                        type="submit"
                        disabled={email.trim() === ''}
                        className="h-[42px] rounded-[11px] bg-rz-accent-fill px-[18px] text-[13.5px] font-bold text-white disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {t('admin.mail_testers.add.cta')}
                    </button>
                </form>
            )}
            {props.testers.length === 0 && props.search !== '' ? (
                <SearchEmpty section="mail_testers" term={props.search} />
            ) : (
                <TableCard
                    label={t('admin.mail_testers.table')}
                    minWidth="min-w-[560px]"
                    empty={
                        props.testers.length === 0 && (
                            <EmptyState title={t('admin.mail_testers.empty')} />
                        )
                    }
                >
                    <div role="row" className={cn(GRID, TABLE_HEAD)}>
                        <HeadCell>{t('admin.mail_testers.col.email')}</HeadCell>
                        <HeadCell>
                            {t('admin.mail_testers.col.added_by')}
                        </HeadCell>
                        <HeadCell>
                            {t('admin.mail_testers.col.added_at')}
                        </HeadCell>
                        <HeadCell>
                            <span className="sr-only">
                                {t('admin.mail_testers.col.actions')}
                            </span>
                        </HeadCell>
                    </div>
                    {props.testers.map((tester) => (
                        <div
                            key={tester.id}
                            role="row"
                            className={cn(GRID, ROW_RULE, 'items-center py-3')}
                        >
                            <div
                                role="cell"
                                className="truncate text-[13px] font-semibold text-rz-ink"
                            >
                                {tester.email}
                            </div>
                            <div
                                role="cell"
                                className="truncate text-[12.5px] text-rz-body"
                            >
                                {tester.added_by}
                            </div>
                            <div
                                role="cell"
                                className="text-[12px] text-rz-muted tabular-nums"
                            >
                                <time dateTime={tester.added_at}>
                                    {formatTimestamp(tester.added_at)}
                                </time>
                            </div>
                            <div role="cell">
                                <button
                                    type="button"
                                    onClick={() =>
                                        open({ kind: 'remove', tester })
                                    }
                                    aria-label={t(
                                        'admin.mail_testers.remove_label',
                                        { email: tester.email },
                                    )}
                                    className="h-[32px] rounded-[9px] border border-[#e5484d] px-3 text-[12.5px] font-bold text-[#c2292e]"
                                >
                                    {t('admin.mail_testers.remove')}
                                </button>
                            </div>
                        </div>
                    ))}
                </TableCard>
            )}
        </AdminFrame>
    );
}
