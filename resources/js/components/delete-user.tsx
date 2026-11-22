import { Form, Link } from '@inertiajs/react';
import { useRef, useState } from 'react';
import AccountClosureController from '@/actions/App/Http/Controllers/Settings/AccountClosureController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Icon } from '@/components/rozine/icon';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/hooks/use-translation';
import type { MessageCode } from '@/lib/i18n/types';
import { edit } from '@/routes/profile';
import type { RouteLink } from '@/types';

/** The fragment that opens the Close account dialog on the profile settings page. */
export const CLOSE_ACCOUNT_FRAGMENT = 'close-account';

export type ClosureBlocker = {
    code: string;
    role: 'investor' | 'business' | 'auditor' | 'staff';
    link: RouteLink | null;
};

export type AccountClosure = {
    roles: string[];
    blockers: ClosureBlocker[];
};

const CONSEQUENCES: MessageCode[] = [
    'settings.close.consequence.sign_in',
    'settings.close.consequence.records',
    'settings.close.consequence.reopen',
];

/**
 * Close account (design: Security, Danger zone, "Delete account", with its confirmation sheet). The
 * account is soft deleted: it can no longer sign in, and its records stay for the period the law
 * requires. While anything is still open (money in a wallet, notes being repaid, a live raise, an
 * open audit) the dialog lists each item with a link to settle it, and closing is not offered.
 */
export default function DeleteUser({ closure }: { closure: AccountClosure }) {
    const { t } = useTranslation();
    const passwordInput = useRef<HTMLInputElement>(null);
    const [open, setOpen] = useState(
        () => window.location.hash === `#${CLOSE_ACCOUNT_FRAGMENT}`,
    );
    const [confirmation, setConfirmation] = useState('');
    const confirmed = confirmation.trim().toUpperCase() === 'DELETE';
    const blocked = closure.blockers.length > 0;

    return (
        <div id={CLOSE_ACCOUNT_FRAGMENT} className="space-y-6">
            <Heading
                variant="small"
                title={t('settings.close.title')}
                description={t('settings.close.description')}
            />
            <div className="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10">
                <div className="relative space-y-0.5 text-red-600 dark:text-red-100">
                    <p className="font-medium">
                        {t('settings.close.danger_zone')}
                    </p>
                    <p className="text-sm">
                        {t(
                            blocked
                                ? 'settings.close.blocked_hint'
                                : 'settings.close.ready_hint',
                        )}
                    </p>
                </div>

                <Dialog
                    open={open}
                    onOpenChange={(next) => {
                        setOpen(next);
                        setConfirmation('');
                    }}
                >
                    <DialogTrigger asChild>
                        <Button
                            variant="destructive"
                            data-test="delete-user-button"
                        >
                            {t('settings.close.title')}
                        </Button>
                    </DialogTrigger>
                    <DialogContent className="max-h-[90dvh] overflow-y-auto">
                        {blocked ? (
                            <>
                                <DialogTitle>
                                    {t('settings.close.blocked_title')}
                                </DialogTitle>
                                <DialogDescription>
                                    {t('settings.close.blocked_description')}
                                </DialogDescription>
                                <ul
                                    className="space-y-2.5"
                                    data-test="closure-blockers"
                                >
                                    {closure.blockers.map((blocker) => (
                                        <li
                                            key={`${blocker.code}-${blocker.link?.url ?? ''}`}
                                            className="flex items-start gap-3 rounded-xl border border-red-100 bg-red-50/60 p-3 text-sm dark:border-red-200/10 dark:bg-red-700/10"
                                        >
                                            <span
                                                aria-hidden
                                                className="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-red-100 text-xs text-red-700 dark:bg-red-700/30 dark:text-red-100"
                                            >
                                                !
                                            </span>
                                            <span className="min-w-0 flex-1">
                                                <span className="block text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                                    {t(
                                                        `settings.close.role.${blocker.role}` as MessageCode,
                                                    )}
                                                </span>
                                                <span className="block text-foreground">
                                                    {t(
                                                        `settings.close.blocker.${blocker.code}` as MessageCode,
                                                    )}
                                                </span>
                                                {blocker.link && (
                                                    <Link
                                                        href={blocker.link.url}
                                                        className="mt-1 inline-block font-semibold text-foreground underline underline-offset-4"
                                                    >
                                                        {t(
                                                            'settings.close.settle',
                                                        )}
                                                    </Link>
                                                )}
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                                <DialogFooter>
                                    <DialogClose asChild>
                                        <Button
                                            type="button"
                                            variant="secondary"
                                        >
                                            {t('settings.close.keep')}
                                        </Button>
                                    </DialogClose>
                                </DialogFooter>
                            </>
                        ) : (
                            <>
                                <DialogTitle>
                                    {t('settings.close.confirm_title')}
                                </DialogTitle>
                                <DialogDescription>
                                    {t('settings.close.confirm_description')}
                                </DialogDescription>
                                <ul className="space-y-2">
                                    {CONSEQUENCES.map((code) => (
                                        <li
                                            key={code}
                                            className="flex items-start gap-2.5 text-sm text-muted-foreground"
                                        >
                                            <span
                                                aria-hidden
                                                className="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-red-100 text-xs text-red-700 dark:bg-red-700/30 dark:text-red-100"
                                            >
                                                ✕
                                            </span>
                                            <span>{t(code)}</span>
                                        </li>
                                    ))}
                                </ul>
                                <p className="rounded-xl border border-red-100 bg-red-50 p-3 text-xs leading-relaxed text-red-700 dark:border-red-200/10 dark:bg-red-700/10 dark:text-red-100">
                                    {t('settings.close.warning')}
                                </p>

                                <Form
                                    {...AccountClosureController.store.form()}
                                    options={{
                                        preserveScroll: true,
                                    }}
                                    onError={() =>
                                        passwordInput.current?.focus()
                                    }
                                    resetOnError={['password']}
                                    className="space-y-4"
                                >
                                    {({
                                        resetAndClearErrors,
                                        processing,
                                        errors,
                                    }) => (
                                        <>
                                            <div className="grid gap-2">
                                                <Label htmlFor="closure-reason">
                                                    {t('settings.close.reason')}
                                                </Label>
                                                <Input
                                                    id="closure-reason"
                                                    name="reason"
                                                    maxLength={500}
                                                    autoComplete="off"
                                                />
                                                <InputError
                                                    message={errors.reason}
                                                />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor="password">
                                                    {t(
                                                        'settings.close.password',
                                                    )}
                                                </Label>
                                                <PasswordInput
                                                    id="password"
                                                    name="password"
                                                    ref={passwordInput}
                                                    autoComplete="current-password"
                                                />
                                                <InputError
                                                    message={errors.password}
                                                />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor="closure-confirmation">
                                                    {t(
                                                        'settings.close.type_delete',
                                                    )}
                                                </Label>
                                                <Input
                                                    id="closure-confirmation"
                                                    name="confirmation"
                                                    value={confirmation}
                                                    onChange={(event) =>
                                                        setConfirmation(
                                                            event.target.value,
                                                        )
                                                    }
                                                    placeholder="DELETE"
                                                    autoComplete="off"
                                                    className="font-semibold tracking-widest uppercase"
                                                />
                                                <InputError
                                                    message={
                                                        errors.confirmation
                                                    }
                                                />
                                            </div>

                                            <DialogFooter className="gap-2">
                                                <DialogClose asChild>
                                                    <Button
                                                        type="button"
                                                        variant="secondary"
                                                        onClick={() =>
                                                            resetAndClearErrors()
                                                        }
                                                    >
                                                        {t(
                                                            'settings.close.keep',
                                                        )}
                                                    </Button>
                                                </DialogClose>

                                                <Button
                                                    variant="destructive"
                                                    disabled={
                                                        processing || !confirmed
                                                    }
                                                    asChild
                                                >
                                                    <button
                                                        type="submit"
                                                        data-test="confirm-delete-user-button"
                                                    >
                                                        {t(
                                                            processing
                                                                ? 'settings.close.processing'
                                                                : 'settings.close.submit',
                                                        )}
                                                    </button>
                                                </Button>
                                            </DialogFooter>
                                        </>
                                    )}
                                </Form>
                            </>
                        )}
                    </DialogContent>
                </Dialog>
            </div>
        </div>
    );
}

/**
 * The Danger zone row the Investor, Business and Auditor security centers end with (design
 * L4009–4013). It opens the Close account dialog on the profile settings, where the account is
 * closed for every role it holds.
 */
export function DeleteAccountRow() {
    const { t } = useTranslation();

    return (
        <div className="mt-[22px] overflow-hidden rounded-2xl border border-[#f4d4d4] bg-rz-surface dark:border-red-200/20">
            <div className="px-[15px] pt-[15px] pb-1 text-[11px] font-bold tracking-[.05em] text-[#c0392b] dark:text-red-300">
                {t('settings.close.danger_zone').toUpperCase()}
            </div>
            <Link
                href={{
                    ...edit(),
                    url: `${edit().url}#${CLOSE_ACCOUNT_FRAGMENT}`,
                }}
                className="flex w-full items-center gap-[13px] px-[15px] pt-[13px] pb-[15px] text-left"
                data-test="delete-account-row"
            >
                <span className="flex size-[34px] shrink-0 items-center justify-center rounded-[10px] bg-[rgba(229,72,77,.1)]">
                    <Icon name="trash" tone="red" />
                </span>
                <span className="min-w-0 flex-1">
                    <span className="block text-sm font-semibold text-[#c0392b] dark:text-red-300">
                        {t('settings.close.title')}
                    </span>
                    <span className="mt-px block text-xs text-rz-secondary">
                        {t('settings.close.description')}
                    </span>
                </span>
                <span aria-hidden className="text-[17px] text-rz-secondary">
                    ›
                </span>
            </Link>
        </div>
    );
}
