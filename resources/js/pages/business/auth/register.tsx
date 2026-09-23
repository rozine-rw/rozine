import { Form, Link, router } from '@inertiajs/react';
import { BusinessAuthFrame } from '@/components/business/business-auth-frame';
import {
    ErrorBanner,
    FieldError,
    FieldLabel,
    PrimaryButton,
    TextAction,
    TextField,
} from '@/components/rozine/form';
import { Icon } from '@/components/rozine/icon';
import { useTranslation } from '@/hooks/use-translation';
import type { RouteAction, RouteLink } from '@/types';

/** What the registry returned for the entered company code. Contacts arrive already masked. */
export type RegistryMatch = {
    company_code: string;
    registered_name: string;
    masked_phone: string;
    masked_email: string;
};

type BusinessRegisterProps = {
    lookup: RegistryMatch | null;
    status?: string;
    links: { login: RouteLink };
    actions: { lookup: RouteAction; verify: RouteAction; resend: RouteAction };
};

/**
 * "Register your business" (design L1538–1555). The company is identified by its RDB company
 * code, never a tax identifier (BRS AC-9); the registry match and the one-time code are both
 * server decisions, so this page only renders what the server has confirmed.
 */
export default function BusinessRegister({
    lookup,
    status,
    links,
    actions,
}: BusinessRegisterProps) {
    const { t } = useTranslation();
    const target = lookup === null ? actions.lookup : actions.verify;

    return (
        <BusinessAuthFrame
            headTitle={t('business.auth.register.head_title')}
            title={t('business.auth.register.title')}
            subtitle={t('business.auth.register.subtitle')}
        >
            <Form
                action={target.url}
                method={target.method}
                className="flex flex-col"
            >
                {({ processing, errors }) => {
                    const failure =
                        errors.company_code ?? errors.registered_name;

                    return (
                        <>
                            <div className="mt-6 flex flex-col gap-[13px]">
                                <div>
                                    <FieldLabel htmlFor="company_code">
                                        {t('business.auth.register.code_label')}
                                    </FieldLabel>
                                    <TextField
                                        id="company_code"
                                        name="company_code"
                                        variant="code"
                                        inputMode="numeric"
                                        maxLength={9}
                                        required
                                        defaultValue={lookup?.company_code}
                                        placeholder={t(
                                            'business.auth.register.code_placeholder',
                                        )}
                                        invalid={
                                            errors.company_code !== undefined
                                        }
                                    />
                                </div>
                                <p className="flex items-start gap-[7px] text-[11.5px] leading-normal text-rz-secondary">
                                    <span className="shrink-0">
                                        <Icon name="shield" />
                                    </span>
                                    <span>
                                        {t('business.auth.register.rdb_note')}
                                    </span>
                                </p>

                                {lookup !== null && (
                                    <>
                                        <div className="animate-[rz-fade_.3s_ease] rounded-2xl border border-rz-border bg-rz-surface p-4">
                                            <span className="inline-flex items-center gap-1.5 rounded-[10px] bg-rz-accent-soft px-2.5 py-[5px] text-[11.5px] font-semibold text-rz-accent-app-text">
                                                {t(
                                                    'business.auth.register.matched',
                                                )}
                                            </span>
                                            <div className="mt-3">
                                                <FieldLabel htmlFor="registered_name">
                                                    {t(
                                                        'business.auth.register.name_label',
                                                    )}
                                                </FieldLabel>
                                                <TextField
                                                    id="registered_name"
                                                    name="registered_name"
                                                    variant="soft"
                                                    required
                                                    defaultValue={
                                                        lookup.registered_name
                                                    }
                                                    placeholder={t(
                                                        'business.auth.register.name_placeholder',
                                                    )}
                                                    invalid={
                                                        errors.registered_name !==
                                                        undefined
                                                    }
                                                />
                                            </div>
                                            <p className="mt-3 text-[12.5px] leading-[1.55] text-rz-secondary">
                                                {t(
                                                    'business.auth.register.code_sent',
                                                )}
                                            </p>
                                            <div className="mt-3 flex flex-col gap-[9px]">
                                                <MaskedContact
                                                    icon="phone"
                                                    value={lookup.masked_phone}
                                                />
                                                <MaskedContact
                                                    icon="mail"
                                                    value={lookup.masked_email}
                                                />
                                            </div>
                                        </div>
                                        <div>
                                            <FieldLabel htmlFor="code">
                                                {t(
                                                    'business.auth.register.otp_label',
                                                )}
                                            </FieldLabel>
                                            <TextField
                                                id="code"
                                                name="code"
                                                variant="otp"
                                                inputMode="numeric"
                                                autoComplete="one-time-code"
                                                maxLength={6}
                                                required
                                                placeholder={t(
                                                    'business.auth.register.otp_placeholder',
                                                )}
                                                invalid={
                                                    errors.code !== undefined
                                                }
                                                aria-describedby="code-error"
                                            />
                                            <FieldError id="code-error">
                                                {errors.code}
                                            </FieldError>
                                        </div>
                                        <div className="text-center">
                                            <TextAction
                                                className="text-[12.5px]"
                                                onClick={() =>
                                                    router.post(
                                                        actions.resend.url,
                                                        {
                                                            company_code:
                                                                lookup.company_code,
                                                        },
                                                    )
                                                }
                                            >
                                                {t(
                                                    'business.auth.register.resend',
                                                )}
                                            </TextAction>
                                            {status && (
                                                <p
                                                    role="status"
                                                    className="mt-2 text-[12.5px] font-semibold text-rz-accent-app-text"
                                                >
                                                    {status}
                                                </p>
                                            )}
                                        </div>
                                    </>
                                )}
                            </div>

                            {failure && (
                                <div className="mt-[13px]">
                                    <ErrorBanner>{failure}</ErrorBanner>
                                </div>
                            )}

                            <div className="mt-[22px]">
                                <PrimaryButton busy={processing}>
                                    {primaryLabel(
                                        t,
                                        lookup !== null,
                                        processing,
                                    )}
                                </PrimaryButton>
                            </div>
                        </>
                    );
                }}
            </Form>

            <p className="mt-[18px] text-center text-[13px] text-rz-secondary">
                {t('business.auth.register.switch_prompt')}{' '}
                <Link
                    href={links.login}
                    className="font-semibold text-rz-accent-app-text"
                >
                    {t('business.auth.register.switch_action')}
                </Link>
            </p>
        </BusinessAuthFrame>
    );
}

function primaryLabel(
    t: ReturnType<typeof useTranslation>['t'],
    matched: boolean,
    busy: boolean,
): string {
    if (matched) {
        return busy
            ? t('business.auth.register.verifying_code')
            : t('business.auth.register.verify_code');
    }

    return busy
        ? t('business.auth.register.verifying_company')
        : t('business.auth.register.verify_company');
}

function MaskedContact({
    icon,
    value,
}: {
    icon: 'phone' | 'mail';
    value: string;
}) {
    return (
        <div className="flex items-center gap-2.5 rounded-[10px] border border-rz-field-soft-border bg-rz-field-soft px-[13px] py-[11px]">
            <span className="text-[15px]">
                <Icon name={icon} />
            </span>
            <span className="text-[13.5px] font-semibold tracking-[.02em] text-rz-ink">
                {value}
            </span>
        </div>
    );
}
