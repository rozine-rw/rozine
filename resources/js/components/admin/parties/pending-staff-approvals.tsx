import { useForm } from '@inertiajs/react';
import { FieldError } from '@/components/rozine/form';
import { useTranslation } from '@/hooks/use-translation';
import type { StaffApproval } from '@/types/admin';

export function PendingStaffApprovals({
    requests,
}: {
    requests: StaffApproval[];
}) {
    const { t } = useTranslation();

    if (requests.length === 0) {
        return null;
    }

    return (
        <section
            aria-label={t('admin.staff.approvals.title')}
            className="mb-4 rounded-[15px] border border-rz-hairline bg-rz-surface p-[18px]"
        >
            <h2 className="text-[15px] font-bold text-rz-ink">
                {t('admin.staff.approvals.title')}
            </h2>
            <p className="mt-1 text-[12.5px] text-rz-muted">
                {t('admin.staff.approvals.body')}
            </p>
            {requests.map((request) => (
                <Approval key={request.id} request={request} />
            ))}
        </section>
    );
}

function Approval({ request }: { request: StaffApproval }) {
    const { t } = useTranslation();
    const form = useForm({ request_id: crypto.randomUUID(), reason: '' });
    const action = request.approve;

    return (
        <div className="mt-3 border-t border-rz-hairline pt-3">
            <p className="text-[13px] font-semibold text-rz-ink">
                {request.recipient} · {request.email}
            </p>
            <p className="mt-1 text-[12.5px] text-rz-muted">
                {t(`admin.staff.approvals.${request.operation}`, {
                    requester: request.requester,
                })}
            </p>
            <p className="mt-1 text-[12.5px] text-rz-body">{request.reason}</p>
            {action ? (
                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.post(action.url, { preserveScroll: true });
                    }}
                    aria-label={t('admin.staff.approvals.form', {
                        recipient: request.recipient,
                    })}
                    className="mt-2"
                >
                    <label
                        className="block text-[12px] font-semibold text-rz-muted"
                        htmlFor={`approval-${request.id}`}
                    >
                        {t('admin.stage.reason_label')}
                    </label>
                    <textarea
                        id={`approval-${request.id}`}
                        required
                        value={form.data.reason}
                        onChange={(event) =>
                            form.setData('reason', event.target.value)
                        }
                        aria-describedby={`approval-error-${request.id}`}
                        aria-invalid={
                            form.errors.reason !== undefined || undefined
                        }
                        className="mt-1 w-full rounded-[10px] border border-rz-hairline bg-rz-surface p-2 text-[13px] text-rz-ink"
                    />
                    <FieldError id={`approval-error-${request.id}`}>
                        {form.errors.reason}
                    </FieldError>
                    <button
                        type="submit"
                        disabled={
                            form.processing || form.data.reason.trim() === ''
                        }
                        className="mt-2 rounded-[10px] bg-[#1e3aff] px-3.5 py-2 text-[12.5px] font-bold text-white disabled:opacity-50"
                    >
                        {t('admin.staff.approvals.submit')}
                    </button>
                </form>
            ) : (
                <p className="mt-2 text-[12px] text-rz-muted">
                    {t('admin.staff.approvals.waiting')}
                </p>
            )}
        </div>
    );
}
