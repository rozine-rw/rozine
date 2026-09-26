import { http } from '@inertiajs/core';
import type { HttpRequestConfig } from '@inertiajs/core';
import type * as InertiaReact from '@inertiajs/react';
import {
    fireEvent,
    render,
    screen,
    waitFor,
    within,
} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ComponentProps } from 'react';
import { afterEach, describe, expect, it, vi } from 'vite-plus/test';
import BusinessAuditCosign from '@/pages/business/audit-cosign';
import type {
    AuditCosignOperationResource,
    BusinessAuditCosignPageProps,
} from '@/types/business-audit';
import n6OpenFixture from '../../../resources/fixtures/ui/business-audit-cosign-n6-open.json';

/**
 * The N6 dispute over the real `useHttp` transport, with only the client swapped: a dispute
 * carrying proof files leaves as multipart form data holding exactly the contract's fields, and a
 * lost one is looked up by its command and identity context.
 */
const inertia = vi.hoisted(() => ({ visit: vi.fn(), reload: vi.fn() }));

vi.mock('@inertiajs/react', async (importOriginal) => ({
    ...(await importOriginal<typeof InertiaReact>()),
    Head: ({ title }: { title: string }) => <span>{title}</span>,
    Link: ({
        href,
        children,
        ...props
    }: Omit<ComponentProps<'a'>, 'href'> & { href: { url: string } }) => (
        <a href={href.url} {...props}>
            {children}
        </a>
    ),
    router: { visit: inertia.visit, reload: inertia.reload },
}));

vi.setConfig({ testTimeout: 30_000 });

const sent: HttpRequestConfig[] = [];

const disputed: AuditCosignOperationResource = {
    operation_id: 'op-dispute',
    status: 'completed',
    code: 'REPORT_DISPUTED',
    data: {
        next: {
            url: '/preview/business-audit-cosign-n6-dispute-under-review',
            method: 'get',
        },
    },
    revision: 8,
    policy_version: 'monthly-review-2026-09-26',
    recorded_at: '2026-09-03T11:20:00+02:00',
    server_time: '2026-09-03T11:20:01+02:00',
    allowed_actions: [],
    field_errors: {},
};

const page = () =>
    structuredClone(n6OpenFixture.props) as BusinessAuditCosignPageProps;

const submitDispute = async (files: File[]) => {
    const user = userEvent.setup();

    render(<BusinessAuditCosign {...page()} />);
    await user.click(screen.getByRole('button', { name: 'Submit a dispute' }));

    const sheet = within(
        screen.getByRole('dialog', { name: 'Submit a dispute' }),
    );

    await user.type(
        sheet.getByLabelText('Supporting text'),
        'Refund slip 0142 matches the till roll.',
    );
    fireEvent.change(
        sheet.getByLabelText('Proof files', { selector: 'input' }),
        {
            target: { files },
        },
    );
    await user.click(sheet.getByRole('button', { name: 'Submit dispute' }));
};

afterEach(() => {
    sent.length = 0;
    inertia.visit.mockReset();
});

describe('Business audit dispute over the real transport', () => {
    it('sends the dispute as multipart with exactly the contract fields and follows data.next', async () => {
        http.setClient({
            request: (config) => {
                sent.push(config);

                return Promise.resolve({
                    status: 200,
                    data: JSON.stringify(disputed),
                    headers: {},
                });
            },
        });
        const refund = new File(['%PDF'], 'refund-slip.pdf', {
            type: 'application/pdf',
        });
        const till = new File(['jpg'], 'till-roll.jpg', { type: 'image/jpeg' });

        await submitDispute([refund, till]);
        await waitFor(() =>
            expect(inertia.visit).toHaveBeenCalledWith(disputed.data!.next),
        );

        const body = sent[0].data as FormData;

        expect(sent[0]).toMatchObject({
            method: 'post',
            url: '/preview/business-audit-cosign-disputes',
        });
        expect(body).toBeInstanceOf(FormData);
        expect([...new Set(body.keys())]).toEqual([
            'request_id',
            'identity_context_revision',
            'expected_revision',
            'report_revision',
            'mandate_version',
            'digest',
            'supporting_text',
            'proof_files[]',
        ]);
        expect(body.get('request_id')).toMatch(/^[0-9a-f-]{36}$/u);
        expect(body.get('identity_context_revision')).toBe('4');
        expect(body.get('expected_revision')).toBe('7');
        expect(body.get('report_revision')).toBe('3');
        expect(body.get('mandate_version')).toBe('2');
        expect(body.get('digest')).toBe(n6OpenFixture.props.report.digest);
        expect(body.get('supporting_text')).toBe(
            'Refund slip 0142 matches the till roll.',
        );
        expect(
            (body.getAll('proof_files[]') as File[]).map((file) => file.name),
        ).toEqual(['refund-slip.pdf', 'till-roll.jpg']);
    });

    it('looks a lost dispute up with command=report.dispute and the identity context', async () => {
        const replies = [
            () => Promise.reject(new Error('Network down')),
            () =>
                Promise.resolve({
                    status: 200,
                    data: JSON.stringify(disputed),
                    headers: {},
                }),
        ];

        http.setClient({
            request: (config) => {
                sent.push(config);

                return replies.shift()!();
            },
        });

        await submitDispute([
            new File(['%PDF'], 'refund-slip.pdf', { type: 'application/pdf' }),
        ]);
        await waitFor(() => expect(sent).toHaveLength(2));

        const requestId = (sent[0].data as FormData).get(
            'request_id',
        ) as string;
        const lookup = new URL(sent[1].url, 'http://localhost');

        expect(sent[1].method).toBe('get');
        expect(lookup.pathname).toBe(
            `/preview/business-audit-cosign-operation-${requestId}`,
        );
        expect(Object.fromEntries(lookup.searchParams)).toEqual({
            identity_context_revision: '4',
            command: 'report.dispute',
        });
        await waitFor(() =>
            expect(inertia.visit).toHaveBeenCalledWith(disputed.data!.next),
        );
    });
});
