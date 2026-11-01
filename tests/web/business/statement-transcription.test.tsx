import { act, render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ComponentProps } from 'react';
import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vite-plus/test';
import BusinessStatementTranscription from '@/pages/business/statement-transcription';
import type { BusinessStatementTranscriptionProps } from '@/types/business';
import savedFixture from '../../../resources/fixtures/ui/business-statement-transcription-saved.json';
import viewOnlyFixture from '../../../resources/fixtures/ui/business-statement-transcription-view-only.json';
import emptyFixture from '../../../resources/fixtures/ui/business-statement-transcription.json';

type Visit = {
    onStart?: () => void;
    onSuccess?: () => void;
    onError?: (errors: Record<string, string>) => void;
    onFinish?: () => void;
};

const inertia = vi.hoisted(() => ({
    post: vi.fn(
        (url: string, data: Record<string, unknown>, options: Visit) => {
            void url;
            void data;
            void options;
        },
    ),
}));

vi.mock('@inertiajs/react', () => ({
    Head: () => null,
    Link: ({
        href,
        children,
        ...props
    }: Omit<ComponentProps<'a'>, 'href'> & { href: { url: string } }) => (
        <a href={href.url} {...props}>
            {children}
        </a>
    ),
    router: { post: inertia.post, visit: vi.fn() },
    useHttp: () => ({ submit: () => new Promise(() => undefined) }),
}));

const props = (fixture: { props: unknown }) =>
    structuredClone(fixture.props) as BusinessStatementTranscriptionProps;

const dialog = () =>
    screen.getByRole('dialog', { name: 'Enter your statement figures' });

beforeEach(() => {
    vi.useFakeTimers({ toFake: ['Date'] });
    vi.setSystemTime(new Date('2026-10-15T09:00:00Z'));
    inertia.post.mockClear();
});

afterEach(() => {
    vi.useRealTimers();
});

describe('Enter your statement figures', () => {
    it('starts on last month with one account and says what is missing before it can save', () => {
        render(<BusinessStatementTranscription {...props(emptyFixture)} />);

        const page = within(dialog());
        expect(
            page.getByRole('heading', {
                level: 1,
                name: 'Enter your statement figures',
            }),
        ).toBeInTheDocument();
        expect(page.getByLabelText('From')).toHaveValue('2026-09');
        expect(page.getByLabelText('To')).toHaveValue('2026-09');
        expect(page.getByText('1 month (at most 36)')).toBeInTheDocument();
        expect(page.getByRole('link', { name: 'Back' })).toHaveAttribute(
            'href',
            '/preview/business-apply-business',
        );
        expect(
            page.queryByText('Figures saved · not yet verified'),
        ).not.toBeInTheDocument();
        expect(page.getByRole('status')).toHaveTextContent(
            '3 things to fix before saving',
        );
        expect(
            page.getByRole('button', { name: 'Save figures' }),
        ).toBeDisabled();
        expect(
            page.getByText('No transactions this month.'),
        ).toBeInTheDocument();
        expect(
            page.getByRole('heading', {
                level: 3,
                name: /^Account 1 · Sept? 2026$/u,
            }),
        ).toBeInTheDocument();
    });

    it('takes a month of figures, then saves it with everything the server binds the save to', async () => {
        const user = userEvent.setup();
        render(<BusinessStatementTranscription {...props(emptyFixture)} />);
        const page = within(dialog());

        await user.type(page.getByLabelText('Account name'), 'BK current!');
        expect(page.getByLabelText('Account name')).toHaveValue('BK-current');
        await user.click(
            page.getByLabelText('statement-01k6p4c1a2b3c4d5e6f7g8h9j0.pdf'),
        );
        await user.type(page.getByLabelText('Opening balance (RWF)'), '1,000');
        await user.click(
            page.getByRole('button', { name: 'Add a transaction' }),
        );

        const transaction = within(
            page.getByRole('listitem', { name: 'Transaction 1' }),
        );
        await user.type(transaction.getByLabelText('Date'), '2026-09-04');
        await user.type(transaction.getByLabelText('Amount (RWF)'), '25o0');
        await user.selectOptions(
            transaction.getByLabelText('What it was'),
            'operating_inflow',
        );
        await user.type(
            transaction.getByLabelText('Reference on the statement'),
            'INV 7',
        );
        expect(
            page.getByText('Opening balance plus transactions: RWF 1,250'),
        ).toBeInTheDocument();

        await user.type(
            page.getByLabelText('Closing balance on the statement (RWF)'),
            '1300',
        );
        expect(
            page.getByText(
                'This month does not add up: the opening balance plus transactions must equal the closing balance.',
            ),
        ).toBeInTheDocument();
        await user.clear(
            page.getByLabelText('Closing balance on the statement (RWF)'),
        );
        await user.type(
            page.getByLabelText('Closing balance on the statement (RWF)'),
            '1250',
        );
        expect(page.queryByRole('status')).not.toBeInTheDocument();

        const save = page.getByRole('button', { name: 'Save figures' });
        expect(save).toBeDisabled();
        await user.click(
            page.getByLabelText(
                'I checked every figure against my original statements.',
            ),
        );
        expect(save).toBeEnabled();
        await user.click(save);

        expect(inertia.post).toHaveBeenCalledTimes(1);
        const [url, data, options] = inertia.post.mock.calls[0];
        expect(url).toBe(
            '/business/01k6p4b7r2c9d3f8g1h5j0k6m2/statements/transcription',
        );
        expect(data).toMatchObject({
            identity_context_revision: 4,
            expected_business_revision: 3,
            expected_mandate_version: 1,
            expected_evidence_revision: 2,
            expected_transcription_id: null,
            expected_transcription_sha256: null,
            reviewed_against_originals: true,
            rails: [
                {
                    id: 'BK-current',
                    active_from: '2026-09',
                    active_until: null,
                },
            ],
            months: ['2026-09'],
            statements: [
                {
                    rail_id: 'BK-current',
                    month: '2026-09',
                    opening_balance: '1000',
                    closing_balance: '1250',
                    source_ids: ['01k6p4c1a2b3c4d5e6f7g8h9j0'],
                    transactions: [
                        {
                            reference: 'INV-7',
                            date: '2026-09-04',
                            amount: '250',
                            classification: 'operating_inflow',
                            source_ids: ['01k6p4c1a2b3c4d5e6f7g8h9j0'],
                            return_of: null,
                            transfer_of: null,
                            exception_id: null,
                        },
                    ],
                },
            ],
        });
        expect(data.source_binding).toEqual(emptyFixture.props.draft.evidence);
        expect(String(data.request_id)).toMatch(/^[0-9a-f-]{36}$/u);
        expect(options).toMatchObject({
            preserveScroll: true,
            preserveState: true,
        });

        act(() => options.onStart?.());
        expect(page.getByRole('button', { name: 'Saving…' })).toBeDisabled();
        act(() =>
            options.onError?.({
                transcription:
                    'Your statements changed while you were entering figures. Reload the page and check again.',
            }),
        );
        act(() => options.onFinish?.());
        expect(page.getByRole('alert')).toHaveTextContent(
            'Your statements changed while you were entering figures.',
        );
        act(() =>
            options.onError?.({ months: 'The months field is required.' }),
        );
        expect(page.getByRole('alert')).toHaveTextContent(
            'The months field is required.',
        );
        act(() => options.onError?.({}));
        expect(page.getByRole('alert')).toHaveTextContent(
            'We could not save these figures. Try again.',
        );
        act(() => options.onSuccess?.());
        expect(
            page.getByRole('button', { name: 'Save figures' }),
        ).toBeDisabled();

        await user.click(
            page.getByLabelText('statement-01k6p4c1a2b3c4d5e6f7g8h9j0.pdf'),
        );
        expect(page.queryByRole('alert')).not.toBeInTheDocument();
        expect(
            page.getByText('Tick the statement these figures are from.'),
        ).toBeInTheDocument();
    });

    it('reopens saved figures, carries balances forward and links transfers and pay-backs', async () => {
        const user = userEvent.setup();
        render(<BusinessStatementTranscription {...props(savedFixture)} />);
        const page = within(dialog());

        expect(
            page.getByText('Figures saved · not yet verified'),
        ).toBeInTheDocument();
        expect(page.getByLabelText('From')).toHaveValue('2026-07');
        expect(page.getByLabelText('To')).toHaveValue('2026-08');
        expect(
            page
                .getAllByLabelText('Account name')
                .map((input) => (input as HTMLInputElement).value),
        ).toEqual(['BK-current', 'MoMo-0788']);
        expect(
            page.getByRole('button', { name: /^Jul 2026 ✓$/u }),
        ).toHaveAttribute('aria-pressed', 'true');
        expect(page.getByLabelText('Opening balance (RWF)')).toBeEnabled();

        await user.click(page.getByRole('button', { name: /^Aug 2026 ✓$/u }));
        const bank = within(
            page.getByRole('region', { name: 'BK-current · Aug 2026' }),
        );
        const momo = within(
            page.getByRole('region', { name: 'MoMo-0788 · Aug 2026' }),
        );
        expect(bank.getByLabelText('Opening balance (RWF)')).toBeDisabled();
        expect(bank.getByLabelText('Opening balance (RWF)')).toHaveValue(
            '1650000',
        );
        expect(
            bank.getByText(
                'Carried from the month before, or 0 in the month the account opened.',
            ),
        ).toBeInTheDocument();
        expect(momo.getByLabelText('Opening balance (RWF)')).toHaveValue('0');

        const transfer = within(
            bank.getByRole('listitem', { name: 'Transaction 1' }),
        );
        const link = transfer.getByLabelText(
            'The same transfer on your other account',
        );
        expect(
            within(link)
                .getAllByRole('option')
                .map((option) => option.textContent),
        ).toEqual(['Choose…', 'MoMo-0788 · 2026-08-02 · 200000']);
        await user.selectOptions(link, '');
        expect(page.getByRole('status')).toHaveTextContent(
            '2 things to fix before saving',
        );
        expect(
            page.getByRole('button', { name: /^Aug 2026 · 2$/u }),
        ).toBeInTheDocument();
        await user.selectOptions(link, within(link).getAllByRole('option')[1]);
        expect(page.queryByRole('status')).not.toBeInTheDocument();

        const payBack = within(
            bank.getByRole('listitem', { name: 'Transaction 2' }),
        );
        expect(
            within(payBack.getByLabelText('The owner withdrawal it pays back'))
                .getAllByRole('option')
                .map((option) => option.textContent),
        ).toEqual(['Choose…', 'Jul 2026 · BK-current · 50000']);
        await user.selectOptions(
            payBack.getByLabelText('The owner withdrawal it pays back'),
            '',
        );
        expect(page.getByRole('status')).toHaveTextContent(
            '1 thing to fix before saving',
        );

        await user.selectOptions(payBack.getByLabelText('Money'), 'out');
        expect(payBack.getByLabelText('What it was')).toHaveValue('');
        await user.selectOptions(
            payBack.getByLabelText('What it was'),
            'transfer',
        );
        expect(
            payBack.getByLabelText('The same transfer on your other account'),
        ).toBeInTheDocument();

        await user.click(
            bank.getByRole('button', { name: 'Remove Transaction 1' }),
        );
        expect(
            within(
                momo.getByRole('listitem', { name: 'Transaction 1' }),
            ).getByLabelText('The same transfer on your other account'),
        ).toHaveValue('');
        expect(
            bank.getAllByRole('listitem', { name: /^Transaction \d$/u }),
        ).toHaveLength(2);
    });

    it('adds, renames, dates and removes accounts', async () => {
        const user = userEvent.setup();
        render(<BusinessStatementTranscription {...props(savedFixture)} />);
        const page = within(dialog());

        await user.click(page.getByRole('button', { name: 'Add an account' }));
        const names = page.getAllByLabelText('Account name');
        expect(names).toHaveLength(3);
        expect(
            page.getByRole('region', { name: 'Account 3 · Jul 2026' }),
        ).toBeInTheDocument();
        await user.type(names[2], 'Cash');
        await user.selectOptions(
            page.getAllByLabelText('Opened in')[2],
            '2026-08',
        );
        expect(
            page.queryByRole('region', { name: 'Cash · Jul 2026' }),
        ).not.toBeInTheDocument();
        await user.selectOptions(
            page.getAllByLabelText('Closed in')[2],
            '2026-08',
        );
        await user.selectOptions(page.getAllByLabelText('Closed in')[2], '');
        await user.click(page.getByRole('button', { name: 'Remove Cash' }));
        expect(page.getAllByLabelText('Account name')).toHaveLength(2);

        await user.clear(page.getByLabelText('From'));
        expect(
            page.getByText(
                'Choose the first and last month your statements cover.',
            ),
        ).toBeInTheDocument();
        expect(page.queryByText('Monthly figures')).not.toBeInTheDocument();
        expect(
            within(page.getAllByLabelText('Opened in')[0]).getByRole('option', {
                name: '2026-07',
            }),
        ).toBeInTheDocument();
    });

    it('extends the range to more months and then too many', async () => {
        const user = userEvent.setup();
        render(<BusinessStatementTranscription {...props(savedFixture)} />);
        const page = within(dialog());

        await user.clear(page.getByLabelText('To'));
        await user.type(page.getByLabelText('To'), '2026-09');
        expect(page.getByText('3 months (at most 36)')).toBeInTheDocument();
        await user.click(
            page.getByRole('button', { name: /^Sept? 2026 · \d+$/u }),
        );
        expect(
            page.getByRole('region', { name: /^BK-current · Sept? 2026$/u }),
        ).toBeInTheDocument();

        await user.clear(page.getByLabelText('From'));
        await user.type(page.getByLabelText('From'), '2020-01');
        expect(page.getByText('Choose 36 months at most.')).toBeInTheDocument();
    });

    it('explains why figures cannot be entered yet', () => {
        const viewOnly = props(viewOnlyFixture);
        const { unmount } = render(
            <BusinessStatementTranscription {...viewOnly} />,
        );

        expect(within(dialog()).getByRole('status')).toHaveTextContent(
            'You can see your statements, but only someone who can save the application can enter their figures.',
        );
        expect(
            within(dialog()).queryByRole('button', { name: 'Save figures' }),
        ).not.toBeInTheDocument();
        unmount();

        render(
            <BusinessStatementTranscription
                {...viewOnly}
                statements={{ ...viewOnly.statements, documents: [] }}
            />,
        );
        expect(within(dialog()).getByRole('status')).toHaveTextContent(
            'Upload at least one bank or Mobile Money statement on step 1 first.',
        );
    });
});
