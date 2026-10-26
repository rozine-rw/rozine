import { act, render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { StatementUpload } from '@/components/business/apply/statement-upload';
import { StepBusiness } from '@/components/business/apply/step-business';
import type { BusinessApplyProps, BusinessStatements } from '@/types/business';
import ineligibleStep from '../../../resources/fixtures/ui/business-apply-ineligible.json';

type Visit = {
    onStart?: () => void;
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
    router: { post: inertia.post },
}));

const page = () =>
    structuredClone(ineligibleStep.props) as unknown as BusinessApplyProps;

const statements = (): BusinessStatements =>
    page().statements as BusinessStatements;

const pdf = () =>
    new File(['%PDF-1.4'], 'bank-august.pdf', { type: 'application/pdf' });

beforeEach(() => {
    inertia.post.mockClear();
});

describe('Statement upload on Apply step 1', () => {
    it('lists each received statement with how far reading has got, linking its original', () => {
        render(
            <StatementUpload
                statements={statements()}
                identityContextRevision={4}
                canUpload
            />,
        );

        const list = screen.getByRole('list');
        expect(within(list).getAllByRole('listitem')).toHaveLength(3);
        expect(
            screen.getByRole('link', {
                name: 'statement-01k6p4c1a2b3c4d5e6f7g8h9j0.pdf',
            }),
        ).toHaveAttribute(
            'href',
            '/business/01k6p4b7r2c9d3f8g1h5j0k6m2/statements/01k6p4c1a2b3c4d5e6f7g8h9j0',
        );
        expect(
            screen.getByText('Read · awaiting verification'),
        ).toBeInTheDocument();
        expect(screen.getByText('Reading')).toBeInTheDocument();
        expect(screen.getByText('Our team will check it')).toBeInTheDocument();
        expect(
            screen.getByText(/^Received 23 Sept? 2026$/u),
        ).toBeInTheDocument();
    });

    it('says when nothing has arrived yet', () => {
        render(
            <StatementUpload
                statements={{ ...statements(), revision: 0, documents: [] }}
                identityContextRevision={4}
                canUpload
            />,
        );

        expect(
            screen.getByText('No statements received yet.'),
        ).toBeInTheDocument();
    });

    it('posts the original file with its envelope and shows the progress until the page is drawn again', async () => {
        const user = userEvent.setup();
        render(
            <StatementUpload
                statements={statements()}
                identityContextRevision={4}
                canUpload
            />,
        );

        await user.upload(screen.getByLabelText('Upload a statement'), pdf());

        expect(inertia.post).toHaveBeenCalledTimes(1);
        const [url, data, options] = inertia.post.mock.calls[0];
        expect(url).toBe('/business/01k6p4b7r2c9d3f8g1h5j0k6m2/statements');
        expect(data).toMatchObject({
            identity_context_revision: 4,
            expected_revision: 3,
        });
        expect(data.file).toBeInstanceOf(File);
        expect(String(data.request_id)).toMatch(/^[0-9a-f-]{36}$/u);
        expect(options).toMatchObject({
            forceFormData: true,
            preserveScroll: true,
            preserveState: true,
        });

        act(() => options.onStart?.());
        expect(screen.getByText('Uploading…')).toBeInTheDocument();
        expect(screen.getByLabelText('Upload a statement')).toBeDisabled();
        act(() => options.onFinish?.());
        expect(screen.getByText('Upload a statement')).toBeInTheDocument();
    });

    it('shows the server’s refusal of the file, or a general one, and clears it on the next try', async () => {
        const user = userEvent.setup();
        render(
            <StatementUpload
                statements={statements()}
                identityContextRevision={4}
                canUpload
            />,
        );

        await user.upload(screen.getByLabelText('Upload a statement'), pdf());
        act(() =>
            inertia.post.mock.calls[0][2].onError?.({
                file: 'Upload an original PDF or a UTF-8 CSV export.',
            }),
        );
        expect(screen.getByRole('alert')).toHaveTextContent(
            'Upload an original PDF or a UTF-8 CSV export.',
        );

        await user.upload(screen.getByLabelText('Upload a statement'), pdf());
        expect(screen.queryByRole('alert')).not.toBeInTheDocument();
        act(() => inertia.post.mock.calls[1][2].onError?.({}));
        expect(screen.getByRole('alert')).toHaveTextContent(
            'We could not keep this statement. Try again.',
        );
    });

    it('sends nothing when the picker closes without a file', () => {
        render(
            <StatementUpload
                statements={statements()}
                identityContextRevision={4}
                canUpload
            />,
        );

        const input = screen.getByLabelText('Upload a statement');
        Object.defineProperty(input, 'files', { value: [] });
        act(() => {
            input.dispatchEvent(new Event('change', { bubbles: true }));
        });

        expect(inertia.post).not.toHaveBeenCalled();
    });

    it('offers no upload to a member who may not save the application', () => {
        render(
            <StatementUpload
                statements={statements()}
                identityContextRevision={4}
                canUpload={false}
            />,
        );

        expect(
            screen.queryByLabelText('Upload a statement'),
        ).not.toBeInTheDocument();
        expect(screen.getAllByRole('listitem')).toHaveLength(3);
    });

    it('leaves the statements out of step 1 when the page has none to show', () => {
        render(
            <StepBusiness
                evidence={page().evidence}
                identityContextRevision={4}
                canUpload
            />,
        );

        expect(
            screen.queryByText('Bank & Mobile Money statements'),
        ).not.toBeInTheDocument();
        expect(
            screen.getByText('Not eligible to raise yet'),
        ).toBeInTheDocument();
    });
});
