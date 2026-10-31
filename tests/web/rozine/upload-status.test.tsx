import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vite-plus/test';
import { UploadStatus } from '@/components/rozine/upload-status';

describe('UploadStatus', () => {
    it('says uploading until the browser reports progress, then the share sent', () => {
        const { rerender } = render(<UploadStatus percent={null} />);

        expect(screen.getByRole('status')).toHaveTextContent('Uploading…');
        expect(screen.getByRole('status')).toHaveClass('flex', 'items-center');

        rerender(<UploadStatus percent={42.6} className="mt-2" />);

        expect(screen.getByRole('status')).toHaveTextContent('Uploading… 43%');
        expect(screen.getByRole('status')).toHaveClass('mt-2');
    });

    it('says the file is being checked once every byte is out, covering its tile as an overlay', () => {
        render(<UploadStatus percent={100} overlay />);

        expect(screen.getByRole('status')).toHaveTextContent(
            'Checking the file…',
        );
        expect(screen.getByRole('status')).toHaveClass('absolute', 'inset-0');
    });
});
