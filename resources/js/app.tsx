import { createInertiaApp } from '@inertiajs/react';
import I18nProvider from '@/components/i18n-provider';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import { initializeTheme } from '@/hooks/use-appearance';
import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';
import PublicLayout from '@/layouts/public-layout';
import SettingsLayout from '@/layouts/settings/layout';

/*
 * The product's name, not an environment setting: APP_NAME (and VITE_APP_NAME from it) still reads
 * the starter kit's "Laravel" in some .env files, and it must never reach a tab title.
 */
const appName = 'Rozine';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name, page) => {
        switch (true) {
            // Public pages, audit seal verification among them: no session, no role shell. The
            // error page's not-found answer too, which a visitor with no session reaches from a
            // public link.
            case name === 'home':
            case name === 'welcome':
            case name === 'pulse':
            case name.startsWith('audit/'):
            case name === 'identity/access-denied' && page.props.status === 404:
                return PublicLayout;
            // The Suite launcher and the role apps draw their own shells.
            case name === 'dashboard':
            case /^(investor|business|auditor|admin)\//.test(name):
                return undefined;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    strictMode: true,
    withApp(app) {
        return (
            // The lang attribute is the one locale the document is already
            // committed to, so the provider reads that rather than a prop and
            // cannot disagree with what the page was rendered as.
            <I18nProvider locale={document.documentElement.lang}>
                <TooltipProvider delayDuration={0}>
                    {app}
                    <Toaster />
                </TooltipProvider>
            </I18nProvider>
        );
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();
