import { Link, router, useForm } from '@inertiajs/react';
import type { FormEvent, ReactNode } from 'react';
import { InvestorAuthFrame } from '@/components/investor/auth/auth-frame';
import { MethodMark } from '@/components/investor/wallet/funding';
import { ErrorBanner } from '@/components/rozine/form';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import type {
    IdDocument,
    InvestorSignUpProps,
    PayoutAccountKind,
    SignUpStep,
} from '@/types/investor';

const STEPS: SignUpStep[] = [
    'identity',
    'address',
    'contact',
    'security',
    'payment',
    'agree',
];

const LABEL = 'mb-1.5 block text-xs font-semibold text-rz-label uppercase';
const FIELD =
    'w-full rounded-xl border border-rz-border bg-rz-field px-3.5 py-[13px] text-sm text-rz-ink outline-none placeholder:text-rz-faint focus:border-rz-focus-border';

function Chip({
    on,
    onClick,
    children,
}: {
    on: boolean;
    onClick: () => void;
    children: ReactNode;
}) {
    return (
        <button
            type="button"
            role="radio"
            aria-checked={on}
            onClick={onClick}
            className={cn(
                'shrink-0 rounded-[10px] border px-3.5 py-[9px] text-[13px] font-semibold whitespace-nowrap',
                on
                    ? 'border-[#d6e4ff] bg-rz-accent-fill text-white dark:border-rz-investor'
                    : 'border-rz-border bg-rz-surface text-rz-slate',
            )}
        >
            {children}
        </button>
    );
}

function Field({
    id,
    label,
    children,
    hint,
}: {
    id: string;
    label: string;
    children: ReactNode;
    hint?: string;
}) {
    return (
        <div className="min-w-0 flex-1">
            <label htmlFor={id} className={LABEL}>
                {label}
            </label>
            {children}
            {hint !== undefined && (
                <p className="mt-1.5 text-[11.5px] text-rz-secondary">{hint}</p>
            )}
        </div>
    );
}

function Check({
    checked,
    onChange,
    children,
}: {
    checked: boolean;
    onChange: (checked: boolean) => void;
    children: ReactNode;
}) {
    return (
        <label className="flex cursor-pointer items-start gap-3 rounded-xl border border-rz-border bg-rz-surface p-[15px]">
            <input
                type="checkbox"
                checked={checked}
                onChange={(event) => onChange(event.target.checked)}
                className="peer sr-only"
            />
            <span
                aria-hidden
                className="mt-px flex size-[22px] shrink-0 items-center justify-center rounded-[10px] border-2 border-[#d3dae6] bg-rz-surface text-[13px] text-white peer-checked:border-rz-accent-fill peer-checked:bg-rz-accent-fill peer-focus-visible:ring-2 peer-focus-visible:ring-rz-focus-border dark:border-rz-border"
            >
                {checked ? '✓' : ''}
            </span>
            <span className="text-[13px] leading-normal text-rz-slate">
                {children}
            </span>
        </label>
    );
}

/**
 * Investor sign-up (design L3361–3440): six steps — identity, address, contact, security, payment
 * method and agreement. Each step is saved to the server, which answers with the next one, so a
 * reload resumes. Institutions give their RDB company code; no tax identifier is collected
 * (BRS AC-9), replacing the design's "REGISTRATION / TIN" field.
 */
export default function InvestorSignUp({
    step,
    draft,
    countries,
    documents,
    links,
    actions,
}: InvestorSignUpProps) {
    const { t } = useTranslation();
    const index = STEPS.indexOf(step);
    const institution = draft.investor_type === 'institution';
    const form = useForm({
        ...draft,
        pin: '',
        password: '',
        otp: '',
        terms: false,
        privacy: false,
    });
    const failure = Object.values(form.errors)[0];
    const percent = Math.round((index / 5) * 100);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.transform((data) => ({ ...data, step }));
        form.post(step === 'agree' ? actions.finish.url : actions.save.url, {
            preserveScroll: true,
        });
    };

    const sendOtp = () =>
        router.post(
            actions.send_otp.url,
            { method: form.data.payment_method },
            { preserveScroll: true, only: ['draft'] },
        );

    return (
        <InvestorAuthFrame
            title={t(`investor.signup.${step}.head_title`)}
            layout="column"
        >
            <form
                onSubmit={submit}
                className="relative flex min-h-svh flex-1 flex-col lg:min-h-[694px]"
            >
                <div className="flex-1 px-[22px] pt-[calc(env(safe-area-inset-top)+14px)] pb-[110px] lg:pt-[54px]">
                    <div className="flex items-center gap-[13px]">
                        <Link
                            href={links.back}
                            aria-label={t('investor.common.back')}
                            className="flex size-[38px] shrink-0 items-center justify-center rounded-[10px] border border-rz-border bg-rz-surface text-lg text-rz-ink"
                        >
                            <span aria-hidden>←</span>
                        </Link>
                        <div className="flex-1">
                            <div
                                role="progressbar"
                                aria-label={t('investor.signup.progress')}
                                aria-valuemin={0}
                                aria-valuemax={100}
                                aria-valuenow={percent}
                                className="h-1.5 overflow-hidden rounded-[3px] bg-rz-border"
                            >
                                <div
                                    className="h-full bg-[linear-gradient(90deg,#1e3aff,#17795a)] transition-[width] duration-300"
                                    style={{ width: `${percent}%` }}
                                />
                            </div>
                            <p className="mt-1.5 text-[11.5px] font-semibold text-rz-secondary">
                                {t('investor.signup.step_label', {
                                    step: index + 1,
                                    count: STEPS.length,
                                    type: t(
                                        `investor.auth.type.${draft.investor_type}`,
                                    ),
                                })}
                            </p>
                        </div>
                    </div>

                    <h1 className="mt-[22px] text-[21px] font-semibold text-rz-ink">
                        {step === 'identity'
                            ? t(
                                  institution
                                      ? 'investor.signup.identity.title_institution'
                                      : 'investor.signup.identity.title',
                              )
                            : t(`investor.signup.${step}.title`)}
                    </h1>

                    {step === 'identity' && (
                        <>
                            <p className="mt-3.5 text-[11.5px] font-bold tracking-[.05em] text-rz-slate uppercase">
                                {t('investor.signup.identity.account_type')}
                            </p>
                            <div
                                role="radiogroup"
                                aria-label={t(
                                    'investor.signup.identity.account_type',
                                )}
                                className="mt-2 flex gap-[9px]"
                            >
                                {(['individual', 'institution'] as const).map(
                                    (type) => (
                                        <button
                                            key={type}
                                            type="button"
                                            role="radio"
                                            aria-checked={
                                                form.data.investor_type === type
                                            }
                                            onClick={() =>
                                                form.setData(
                                                    'investor_type',
                                                    type,
                                                )
                                            }
                                            className={cn(
                                                'flex-1 rounded-xl px-3 py-[11px] text-[13px] font-semibold',
                                                form.data.investor_type === type
                                                    ? 'border-[1.5px] border-rz-accent-fill bg-rz-accent-soft text-rz-accent-app-text'
                                                    : 'border border-rz-border bg-rz-surface text-rz-slate',
                                            )}
                                        >
                                            {t(
                                                `investor.signup.identity.type_${type}`,
                                            )}
                                        </button>
                                    ),
                                )}
                            </div>
                            <div className="mt-3.5 flex flex-col gap-[13px]">
                                <div className="flex gap-3">
                                    <Field
                                        id="first_name"
                                        label={t(
                                            form.data.investor_type ===
                                                'institution'
                                                ? 'investor.signup.identity.institution_name'
                                                : 'investor.auth.first_name',
                                        )}
                                    >
                                        <input
                                            id="first_name"
                                            value={form.data.first_name}
                                            onChange={(event) =>
                                                form.setData(
                                                    'first_name',
                                                    event.target.value,
                                                )
                                            }
                                            placeholder={t(
                                                form.data.investor_type ===
                                                    'institution'
                                                    ? 'investor.auth.entity_placeholder'
                                                    : 'investor.auth.first_placeholder',
                                            )}
                                            className={FIELD}
                                        />
                                    </Field>
                                    <Field
                                        id="last_name"
                                        label={t(
                                            form.data.investor_type ===
                                                'institution'
                                                ? 'investor.signup.identity.contact_person'
                                                : 'investor.auth.last_name',
                                        )}
                                    >
                                        <input
                                            id="last_name"
                                            value={form.data.last_name}
                                            onChange={(event) =>
                                                form.setData(
                                                    'last_name',
                                                    event.target.value,
                                                )
                                            }
                                            placeholder={t(
                                                form.data.investor_type ===
                                                    'institution'
                                                    ? 'investor.auth.representative_placeholder'
                                                    : 'investor.auth.last_placeholder',
                                            )}
                                            className={FIELD}
                                        />
                                    </Field>
                                </div>
                                {form.data.investor_type === 'institution' ? (
                                    <>
                                        <Field
                                            id="address"
                                            label={t(
                                                'investor.signup.identity.address',
                                            )}
                                        >
                                            <input
                                                id="address"
                                                value={form.data.address}
                                                onChange={(event) =>
                                                    form.setData(
                                                        'address',
                                                        event.target.value,
                                                    )
                                                }
                                                placeholder={t(
                                                    'investor.signup.identity.address_placeholder',
                                                )}
                                                className={FIELD}
                                            />
                                        </Field>
                                        <Field
                                            id="company_code"
                                            label={t(
                                                'investor.signup.identity.company_code',
                                            )}
                                            hint={t(
                                                'investor.signup.identity.company_code_hint',
                                            )}
                                        >
                                            <input
                                                id="company_code"
                                                value={form.data.company_code}
                                                onChange={(event) =>
                                                    form.setData(
                                                        'company_code',
                                                        event.target.value.replace(
                                                            /[^0-9]/gu,
                                                            '',
                                                        ),
                                                    )
                                                }
                                                inputMode="numeric"
                                                maxLength={9}
                                                placeholder="103847291"
                                                className={FIELD}
                                            />
                                        </Field>
                                    </>
                                ) : (
                                    <>
                                        <div>
                                            <p className={LABEL}>
                                                {t(
                                                    'investor.signup.identity.id_type',
                                                )}
                                            </p>
                                            <div
                                                role="radiogroup"
                                                aria-label={t(
                                                    'investor.signup.identity.id_type',
                                                )}
                                                className="rz-hscroll flex gap-2 overflow-x-auto"
                                            >
                                                {(
                                                    [
                                                        'national_id',
                                                        'passport',
                                                        'drivers_license',
                                                    ] as IdDocument[]
                                                ).map((document) => (
                                                    <Chip
                                                        key={document}
                                                        on={
                                                            form.data
                                                                .id_type ===
                                                            document
                                                        }
                                                        onClick={() =>
                                                            form.setData(
                                                                'id_type',
                                                                document,
                                                            )
                                                        }
                                                    >
                                                        {t(
                                                            `investor.signup.id.${document}`,
                                                        )}
                                                    </Chip>
                                                ))}
                                            </div>
                                        </div>
                                        <Field
                                            id="id_number"
                                            label={t(
                                                'investor.signup.identity.id_number',
                                            )}
                                            hint={t(
                                                'investor.signup.identity.id_hint',
                                            )}
                                        >
                                            <input
                                                id="id_number"
                                                value={form.data.id_number}
                                                onChange={(event) =>
                                                    form.setData(
                                                        'id_number',
                                                        event.target.value,
                                                    )
                                                }
                                                placeholder={t(
                                                    `investor.signup.id_placeholder.${form.data.id_type}`,
                                                )}
                                                className={FIELD}
                                            />
                                        </Field>
                                    </>
                                )}
                            </div>
                        </>
                    )}

                    {step === 'address' && (
                        <div className="mt-[18px] flex flex-col gap-[13px]">
                            <div>
                                <p className={LABEL}>
                                    {t('investor.signup.address.country')}
                                </p>
                                <div
                                    role="radiogroup"
                                    aria-label={t(
                                        'investor.signup.address.country',
                                    )}
                                    className="rz-hscroll flex gap-2 overflow-x-auto"
                                >
                                    {countries.map((country) => (
                                        <Chip
                                            key={country}
                                            on={form.data.country === country}
                                            onClick={() =>
                                                form.setData('country', country)
                                            }
                                        >
                                            {country}
                                        </Chip>
                                    ))}
                                </div>
                            </div>
                            {(
                                [
                                    ['province', 'district'],
                                    ['sector', 'cell'],
                                ] as const
                            ).map((pair) => (
                                <div key={pair[0]} className="flex gap-3">
                                    {pair.map((key) => (
                                        <Field
                                            key={key}
                                            id={key}
                                            label={t(
                                                `investor.signup.address.${key}`,
                                            )}
                                        >
                                            <input
                                                id={key}
                                                value={form.data[key]}
                                                onChange={(event) =>
                                                    form.setData(
                                                        key,
                                                        event.target.value,
                                                    )
                                                }
                                                placeholder={t(
                                                    `investor.signup.address.${key}_placeholder`,
                                                )}
                                                className={FIELD}
                                            />
                                        </Field>
                                    ))}
                                </div>
                            ))}
                        </div>
                    )}

                    {step === 'contact' && (
                        <div className="mt-[18px] flex flex-col gap-[13px]">
                            <Field
                                id="email"
                                label={t('investor.signup.contact.email')}
                            >
                                <input
                                    id="email"
                                    type="email"
                                    value={form.data.email}
                                    onChange={(event) =>
                                        form.setData(
                                            'email',
                                            event.target.value,
                                        )
                                    }
                                    inputMode="email"
                                    placeholder={t(
                                        'investor.auth.email_placeholder',
                                    )}
                                    className={FIELD}
                                />
                            </Field>
                            <div>
                                <label htmlFor="phone" className={LABEL}>
                                    {t('investor.signup.contact.phone')}
                                </label>
                                <div className="flex gap-2.5">
                                    <input
                                        aria-label={t(
                                            'investor.signup.contact.country_code',
                                        )}
                                        value={form.data.phone_country}
                                        onChange={(event) =>
                                            form.setData(
                                                'phone_country',
                                                event.target.value,
                                            )
                                        }
                                        maxLength={5}
                                        className={cn(
                                            FIELD,
                                            'w-[74px] shrink-0 px-3 text-center',
                                        )}
                                    />
                                    <input
                                        id="phone"
                                        value={form.data.phone}
                                        onChange={(event) =>
                                            form.setData(
                                                'phone',
                                                event.target.value.replace(
                                                    /[^0-9]/gu,
                                                    '',
                                                ),
                                            )
                                        }
                                        inputMode="numeric"
                                        maxLength={9}
                                        placeholder="788 123 456"
                                        className={FIELD}
                                    />
                                </div>
                            </div>
                        </div>
                    )}

                    {step === 'security' && (
                        <>
                            <p className="mt-1.5 text-[13px] text-rz-secondary">
                                {t('investor.signup.security.subtitle')}
                            </p>
                            <div
                                role="radiogroup"
                                aria-label={t('investor.signup.security.title')}
                                className="mt-4 flex gap-2"
                            >
                                {(['pin', 'password'] as const).map(
                                    (secret) => (
                                        <Chip
                                            key={secret}
                                            on={form.data.secret === secret}
                                            onClick={() =>
                                                form.setData('secret', secret)
                                            }
                                        >
                                            {t(
                                                `investor.signup.security.${secret}`,
                                            )}
                                        </Chip>
                                    ),
                                )}
                            </div>
                            <div className="mt-4">
                                {form.data.secret === 'pin' ? (
                                    <Field
                                        id="pin"
                                        label={t('investor.auth.login.pin')}
                                    >
                                        <input
                                            id="pin"
                                            type="password"
                                            value={form.data.pin}
                                            onChange={(event) =>
                                                form.setData(
                                                    'pin',
                                                    event.target.value.replace(
                                                        /[^0-9]/gu,
                                                        '',
                                                    ),
                                                )
                                            }
                                            inputMode="numeric"
                                            maxLength={4}
                                            autoComplete="new-password"
                                            placeholder="••••"
                                            className={cn(
                                                FIELD,
                                                'text-lg tracking-[.5em]',
                                            )}
                                        />
                                    </Field>
                                ) : (
                                    <Field
                                        id="password"
                                        label={t(
                                            'investor.auth.login.password',
                                        )}
                                    >
                                        <input
                                            id="password"
                                            type="password"
                                            value={form.data.password}
                                            onChange={(event) =>
                                                form.setData(
                                                    'password',
                                                    event.target.value,
                                                )
                                            }
                                            autoComplete="new-password"
                                            placeholder={t(
                                                'investor.signup.security.password_placeholder',
                                            )}
                                            className={FIELD}
                                        />
                                    </Field>
                                )}
                            </div>
                        </>
                    )}

                    {step === 'payment' && (
                        <>
                            <p className="mt-1.5 text-[13px] text-rz-secondary">
                                {t('investor.signup.payment.subtitle')}
                            </p>
                            <div
                                role="radiogroup"
                                aria-label={t('investor.signup.payment.title')}
                                className="mt-4 flex flex-col gap-2.5"
                            >
                                {(
                                    [
                                        'mtn',
                                        'airtel',
                                        'bank',
                                    ] as PayoutAccountKind[]
                                ).map((method) => {
                                    const on =
                                        form.data.payment_method === method;

                                    return (
                                        <button
                                            key={method}
                                            type="button"
                                            role="radio"
                                            aria-checked={on}
                                            onClick={() =>
                                                form.setData(
                                                    'payment_method',
                                                    method,
                                                )
                                            }
                                            className={cn(
                                                'flex items-center gap-[13px] rounded-xl border bg-rz-surface p-3.5',
                                                on
                                                    ? 'border-[#d6e4ff] dark:border-rz-investor'
                                                    : 'border-rz-border',
                                            )}
                                        >
                                            <MethodMark kind={method} />
                                            <span className="flex-1 text-left text-sm font-semibold text-rz-ink">
                                                {t(
                                                    `investor.signup.payment.${method}`,
                                                )}
                                            </span>
                                            <span
                                                className={cn(
                                                    'flex size-5 items-center justify-center rounded-full border-2',
                                                    on
                                                        ? 'border-[#d6e4ff]'
                                                        : 'border-rz-border',
                                                )}
                                            >
                                                {on && (
                                                    <span className="size-2.5 rounded-full bg-rz-accent-fill" />
                                                )}
                                            </span>
                                        </button>
                                    );
                                })}
                            </div>
                            <div className="mt-3.5 flex gap-2.5">
                                <input
                                    aria-label={t(
                                        'investor.signup.payment.otp',
                                    )}
                                    value={form.data.otp}
                                    onChange={(event) =>
                                        form.setData(
                                            'otp',
                                            event.target.value.replace(
                                                /[^0-9]/gu,
                                                '',
                                            ),
                                        )
                                    }
                                    inputMode="numeric"
                                    maxLength={6}
                                    autoComplete="one-time-code"
                                    placeholder={t(
                                        'investor.signup.payment.otp_placeholder',
                                    )}
                                    className={cn(FIELD, 'tracking-[.3em]')}
                                />
                                <button
                                    type="button"
                                    disabled={form.data.payment_method === null}
                                    onClick={sendOtp}
                                    className="shrink-0 rounded-xl border border-rz-accent-fill bg-rz-surface px-4 text-[13px] font-semibold text-rz-accent-app-text disabled:opacity-50"
                                >
                                    {t('investor.signup.payment.send_otp')}
                                </button>
                            </div>
                            {draft.otp_sent && (
                                <p
                                    role="status"
                                    className="mt-2.5 text-xs font-semibold text-[#17795a] dark:text-[#3fcda0]"
                                >
                                    {t('investor.signup.payment.otp_sent')}
                                </p>
                            )}
                        </>
                    )}

                    {step === 'agree' && (
                        <div className="mt-[18px] flex flex-col gap-3">
                            <Check
                                checked={form.data.terms}
                                onChange={(checked) =>
                                    form.setData('terms', checked)
                                }
                            >
                                {t('investor.signup.agree.terms_before')}{' '}
                                <a
                                    href={documents.terms.url}
                                    className="font-semibold text-rz-accent-app-text"
                                >
                                    {t('investor.signup.agree.terms')}
                                </a>
                                {t('investor.signup.agree.terms_after')}
                            </Check>
                            <Check
                                checked={form.data.privacy}
                                onChange={(checked) =>
                                    form.setData('privacy', checked)
                                }
                            >
                                {t('investor.signup.agree.privacy_before')}{' '}
                                <a
                                    href={documents.privacy.url}
                                    className="font-semibold text-rz-accent-app-text"
                                >
                                    {t('investor.signup.agree.privacy')}
                                </a>
                                {t('investor.signup.agree.privacy_after')}
                            </Check>
                        </div>
                    )}

                    {failure !== undefined && (
                        <div className="mt-3.5">
                            <ErrorBanner>{failure}</ErrorBanner>
                        </div>
                    )}
                </div>
                <div className="fixed inset-x-0 bottom-0 bg-[linear-gradient(180deg,rgba(255,255,255,0),var(--rz-surface)_28%)] px-[22px] pt-3.5 pb-[calc(env(safe-area-inset-bottom)+30px)] lg:absolute">
                    <button
                        type="submit"
                        disabled={form.processing}
                        aria-busy={form.processing || undefined}
                        className="h-[52px] w-full rounded-2xl bg-rz-accent-fill text-[15px] font-semibold text-white disabled:opacity-70"
                    >
                        {form.processing
                            ? t('investor.auth.please_wait')
                            : t(
                                  step === 'agree'
                                      ? 'investor.signup.create'
                                      : 'investor.auth.continue',
                              )}
                    </button>
                </div>
            </form>
        </InvestorAuthFrame>
    );
}
