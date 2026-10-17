import { fireEvent, render, screen } from '@testing-library/react';
import type { ComponentProps } from 'react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { DetailSheet as AuditorDetailSheet } from '@/components/auditor/detail-sheet';
import { DetailSheet as BusinessDetailSheet } from '@/components/business/detail-sheet';
import { ColumnSheet } from '@/components/rozine/column-sheet';

const visit = vi.hoisted(() => vi.fn());

vi.mock('@inertiajs/react', () => ({
    Link: ({
        href,
        children,
        ...props
    }: Omit<ComponentProps<'a'>, 'href'> & { href: { url: string } }) => (
        <a href={href.url} {...props}>
            {children}
        </a>
    ),
    router: { visit },
}));

const link = (url: string) => ({ url, method: 'get' as const });
const escape = () => fireEvent.keyDown(window, { key: 'Escape' });

beforeEach(() => {
    visit.mockReset();
});

describe('Modal sheets on the keyboard (WCAG 2.2 SC 2.1.1, 2.4.3)', () => {
    it('closes a client-state column sheet on Escape, holding focus while open', () => {
        const close = vi.fn();
        render(
            <ColumnSheet label="Receipt" close={close} fraction={0.72}>
                <p>Body</p>
            </ColumnSheet>,
        );

        expect(screen.getByRole('dialog', { name: 'Receipt' })).toHaveFocus();

        escape();

        expect(close).toHaveBeenCalledOnce();
        expect(visit).not.toHaveBeenCalled();
    });

    it('visits the page beneath when a linked column sheet gets Escape', () => {
        render(
            <ColumnSheet label="Publish" close={link('/home')} fraction={0.8}>
                <p>Body</p>
            </ColumnSheet>,
        );

        escape();

        expect(visit).toHaveBeenCalledWith('/home', { preserveScroll: true });
    });

    it('lets Escape close the Auditor and Business detail sheets only while they are dismissible', () => {
        const { unmount } = render(
            <AuditorDetailSheet
                label="Review"
                close={link('/audits')}
                dismissible
                header={<h2>Review</h2>}
            >
                <p>Body</p>
            </AuditorDetailSheet>,
        );

        expect(screen.getByRole('dialog', { name: 'Review' })).toHaveFocus();
        escape();
        expect(visit).toHaveBeenLastCalledWith('/audits', {
            preserveScroll: true,
        });
        unmount();

        const view = render(
            <BusinessDetailSheet
                label="Apply"
                close={link('/business')}
                closeLabel="Back to Home"
            >
                <p>Body</p>
            </BusinessDetailSheet>,
        );

        escape();
        expect(visit).toHaveBeenLastCalledWith('/business', {
            preserveScroll: true,
        });
        view.unmount();
        visit.mockReset();

        render(
            <BusinessDetailSheet
                label="Busy"
                close={link('/business')}
                closeLabel="Back to Home"
                dismissible={false}
            >
                <p>Body</p>
            </BusinessDetailSheet>,
        );

        expect(screen.getByRole('dialog', { name: 'Busy' })).toHaveFocus();
        escape();
        expect(visit).not.toHaveBeenCalled();
    });
});
