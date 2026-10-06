import { screen } from '@testing-library/react';
import { describe, expect, it } from 'vite-plus/test';
import PasswordInput from '@/components/password-input';
import { renderWithUser } from './helpers/render-with-user';

describe('PasswordInput', () => {
    it('reveals and hides a password only when the user requests it', async () => {
        const { user } = renderWithUser(
            <PasswordInput
                aria-label="Account password"
                defaultValue="correct horse battery staple"
            />,
        );
        const password = screen.getByLabelText('Account password');

        expect(password).toHaveAttribute('type', 'password');

        await user.click(screen.getByRole('button', { name: 'Show password' }));

        expect(password).toHaveAttribute('type', 'text');
        expect(
            screen.getByRole('button', { name: 'Hide password' }),
        ).toBeInTheDocument();

        await user.click(screen.getByRole('button', { name: 'Hide password' }));

        expect(password).toHaveAttribute('type', 'password');
    });
});
