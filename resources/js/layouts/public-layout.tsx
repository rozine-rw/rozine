import type { ReactNode } from 'react';
import NonLiveNotice from '@/components/non-live-notice';

export default function PublicLayout({ children }: { children: ReactNode }) {
    return (
        <>
            <NonLiveNotice />
            {children}
        </>
    );
}
