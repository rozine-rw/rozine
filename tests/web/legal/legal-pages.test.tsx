// eslint-disable-next-line testing-library/no-manual-cleanup
import { cleanup, render, screen, within } from '@testing-library/react';
import type { ComponentProps, ReactNode } from 'react';
import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vite-plus/test';
import { RiskWarning } from '@/components/investor/deals/risk-warning';
import { LegalDocumentBody } from '@/components/legal/legal-document-body';
import LegalDocumentPage from '@/pages/legal/document';
import LegalReview from '@/pages/legal/review';
import type { LegalDocumentLink } from '@/types/legal';

type FormState = { processing: boolean; errors: Record<string, string> };

const inertia = vi.hoisted(() => ({
    state: { processing: false, errors: {} } as FormState,
    actions: [] as unknown[],
}));

vi.mock('@inertiajs/react', () => ({
    Form: ({
        children,
        action,
    }: {
        children: (state: FormState) => ReactNode;
        action: unknown;
    }) => {
        inertia.actions.push(action);

        return <form>{children(inertia.state)}</form>;
    },
    Head: ({ title }: { title: string }) => (
        <div data-testid="head">{title}</div>
    ),
    Link: ({
        children,
        href,
        as: _as,
        ...props
    }: Omit<ComponentProps<'a'>, 'href'> & {
        href: string | { url: string };
        as?: string;
    }) => (
        <a href={typeof href === 'string' ? href : href.url} {...props}>
            {children}
        </a>
    ),
}));

const documents: LegalDocumentLink[] = [
    {
        key: 'terms',
        title: 'Terms and Conditions',
        version: 'terms-2026-11-23',
        sha256: 'c'.repeat(64),
        url: '/terms',
    },
    {
        key: 'privacy',
        title: 'Privacy Notice',
        version: 'privacy-2026-11-23',
        sha256: 'd'.repeat(64),
        url: '/privacy',
    },
    {
        key: 'disclaimers',
        title: 'Disclaimers',
        version: 'disclaimers-2026-11-23',
        sha256: 'e'.repeat(64),
        url: '/disclaimers',
    },
];

const terms = {
    key: 'terms' as const,
    title: 'Terms and Conditions',
    version: 'terms-2026-11-23',
    sha256: 'c'.repeat(64),
    html: '<h1>Rozine Terms and Conditions</h1><h2>The short version</h2><p>Rozine is a marketplace.</p>',
};

describe('The public legal documents and the review page', () => {
    beforeEach(() => {
        // The tick boxes measure themselves inside a form.
        vi.stubGlobal(
            'ResizeObserver',
            class {
                observe() {}
                unobserve() {}
                disconnect() {}
            },
        );
    });

    afterEach(() => {
        cleanup();
        vi.unstubAllGlobals();
        inertia.state = { processing: false, errors: {} };
        inertia.actions = [];
    });

    it('shows a document as issued, with its version and fingerprint, and links to the other two', () => {
        render(
            <LegalDocumentPage
                document={terms}
                documents={documents}
                review={null}
            />,
        );

        expect(screen.getByTestId('head')).toHaveTextContent(
            'Terms and Conditions',
        );
        expect(
            screen.getByRole('heading', {
                name: 'Rozine Terms and Conditions',
            }),
        ).toBeInTheDocument();
        expect(
            screen.getByText('Rozine is a marketplace.'),
        ).toBeInTheDocument();
        expect(
            screen.getByText(
                `Version terms-2026-11-23 · Fingerprint (SHA-256) ${'c'.repeat(64)}`,
            ),
        ).toBeInTheDocument();

        const nav = screen.getByRole('navigation', { name: 'Legal documents' });

        expect(within(nav).getAllByRole('link')).toHaveLength(3);
        expect(
            screen.getByRole('link', { name: 'Terms and Conditions' }),
        ).toHaveAttribute('aria-current', 'page');
        expect(
            screen.getByRole('link', { name: 'Disclaimers' }),
        ).not.toHaveAttribute('aria-current');
        expect(screen.queryByRole('status')).not.toBeInTheDocument();
        expect(
            screen.getAllByRole('link', { name: 'Back to Rozine' })[0],
        ).toHaveAttribute('href', '/');
    });

    it('points a signed-in person who has not agreed to the current wording to the review page', () => {
        render(
            <LegalDocumentPage
                document={terms}
                documents={documents}
                review="/legal/review"
            />,
        );

        expect(screen.getByRole('status')).toHaveTextContent(
            'These documents are new or have changed.',
        );
        expect(
            screen.getByRole('link', { name: 'Review and agree' }),
        ).toHaveAttribute('href', '/legal/review');
    });

    it('asks for a required tick on each document, sending the digest of the wording behind each link', () => {
        render(<LegalReview documents={documents} accept="/legal/review" />);

        expect(inertia.actions).toContain('/legal/review');
        expect(screen.getByTestId('head')).toHaveTextContent(
            'Agree to continue',
        );

        for (const document of documents) {
            expect(
                screen.getByRole('checkbox', {
                    name: new RegExp(document.title),
                }),
            ).toBeRequired();
            expect(
                screen.getByRole('link', { name: document.title }),
            ).toHaveAttribute('target', '_blank');
            expect(screen.getByDisplayValue(document.sha256)).toHaveAttribute(
                'name',
                `legal_documents[${document.key}]`,
            );
        }

        expect(
            screen.getByRole('button', { name: 'Agree and continue' }),
        ).toBeEnabled();
        expect(screen.getByRole('link', { name: 'Sign out' })).toHaveAttribute(
            'href',
            '/logout',
        );
    });

    it('shows the refusals and the saving state', () => {
        inertia.state = {
            processing: true,
            errors: {
                legal_agreements: 'Tick each box to agree before you continue.',
            },
        };
        render(<LegalReview documents={documents} accept="/legal/review" />);

        expect(
            screen.getByText('Tick each box to agree before you continue.'),
        ).toBeInTheDocument();
        expect(screen.getByRole('button', { name: /Saving/ })).toBeDisabled();
    });

    it('falls back to the field-level refusal when the digests were not sent at all', () => {
        inertia.state = {
            processing: false,
            errors: { legal_documents: 'These documents have changed.' },
        };
        render(<LegalReview documents={documents} accept="/legal/review" />);

        expect(
            screen.getByText('These documents have changed.'),
        ).toBeInTheDocument();
    });

    it('carries the risk warning with a link to the Disclaimers', () => {
        render(<RiskWarning />);

        expect(screen.getByRole('note')).toHaveTextContent(
            'Investing carries risk, including loss of capital. Returns are not guaranteed.',
        );
        expect(
            screen.getByRole('link', { name: 'Read the disclaimers' }),
        ).toHaveAttribute('href', '/disclaimers');
    });
});

describe('A legal document read inside the app', () => {
    const body = {
        key: 'terms' as const,
        title: 'Terms and Conditions',
        version: 'terms-test',
        sha256: 'a'.repeat(64),
        html: '<h1>Terms</h1><p>Read the <a href="/privacy">Privacy Notice</a>, the <a href="/disclaimers">Disclaimers</a> and <a href="#plain">this note</a>.</p><p id="plain">Plain text.</p>',
    };

    it('points only the documents it has a target for, and opens only a closed section', () => {
        render(
            <>
                <LegalDocumentBody
                    document={body}
                    targets={{ privacy: '#privacy-section' }}
                />
                <details id="privacy-section">
                    <summary>Privacy</summary>
                    <p>Inside</p>
                </details>
            </>,
        );
        const article = screen.getByRole('article', {
            name: 'Terms and Conditions',
        });

        expect(
            within(article).getByRole('link', { name: 'Disclaimers' }),
        ).toHaveAttribute('href', '/disclaimers');
        within(article).getByText('Plain text.').click();
        within(article).getByRole('link', { name: 'this note' }).click();
        expect(screen.getByText('Inside')).not.toBeVisible();
        within(article).getByRole('link', { name: 'Privacy Notice' }).click();
        expect(screen.getByText('Inside')).toBeVisible();
    });
});
