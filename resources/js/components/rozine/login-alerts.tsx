import { Form } from '@inertiajs/react';
import { Icon } from '@/components/rozine/icon';
import { cn } from '@/lib/utils';

/**
 * The design's Login alerts row (Investor L3960, Business L1760): a switch that turns the e-mail
 * for a new device's sign-in on or off. The in-app notice is recorded either way.
 */
export function LoginAlerts({
    enabled,
    action,
    title,
    hint,
    onClass,
    glyph,
}: {
    enabled: boolean;
    action: { url: string; method: 'put' };
    title: string;
    hint: string;
    onClass: string;
    glyph: string;
}) {
    return (
        <Form
            action={action.url}
            method={action.method}
            options={{ preserveScroll: true }}
            className="flex items-center gap-3 border-t border-rz-border p-4"
        >
            <input type="hidden" name="enabled" value={enabled ? '0' : '1'} />
            <span className={glyph}>
                <Icon name="mail" />
            </span>
            <span className="min-w-0 flex-1">
                <span className="block text-sm font-semibold text-rz-ink">
                    {title}
                </span>
                <span className="block text-xs text-rz-secondary">{hint}</span>
            </span>
            <button
                type="submit"
                role="switch"
                aria-checked={enabled}
                aria-label={title}
                className={cn(
                    'relative h-6 w-11 shrink-0 rounded-xl',
                    enabled ? onClass : 'bg-[#5b6578]',
                )}
            >
                <span
                    className={cn(
                        'absolute top-0.5 left-0.5 size-5 rounded-full bg-white shadow-[0_1px_3px_rgba(0,0,0,.25)] transition-transform',
                        enabled && 'translate-x-5',
                    )}
                />
            </button>
        </Form>
    );
}
