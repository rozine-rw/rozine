import { useOnline } from '@/hooks/use-online';
import { useTranslation } from '@/hooks/use-translation';
import type { MessageCode } from '@/lib/i18n/types';

/**
 * Says out loud when the browser drops offline (Phase 2: offline and reconnect on every screen).
 * It floats above the page so no screen's layout moves, and disappears once the connection returns;
 * pages then refresh their facts on reconnect as they already do. A page may pass its own, more
 * specific wording, as the Auditor audit does for the capture app.
 */
export function ConnectivityNotice({
    message = 'app.connectivity.offline',
}: {
    message?: MessageCode;
}) {
    const { t } = useTranslation();
    const online = useOnline();

    if (online) {
        return null;
    }

    return (
        <div className="pointer-events-none fixed inset-x-0 top-0 z-[60] flex justify-center px-4 pt-[calc(env(safe-area-inset-top)+8px)]">
            <p
                role="status"
                className="pointer-events-auto max-w-[560px] rounded-xl border border-[#f2d69a] bg-rz-surface px-3.5 py-2.5 text-[12px] leading-[1.5] text-[#8a6d2b] shadow-[0_6px_20px_rgba(15,23,42,.12)] dark:border-[rgba(240,160,96,.3)] dark:text-[#e3b56a]"
            >
                {t(message)}
            </p>
        </div>
    );
}
