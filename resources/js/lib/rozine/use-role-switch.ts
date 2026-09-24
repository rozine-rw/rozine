import { router, useHttp } from '@inertiajs/react';
import { useState } from 'react';
import { store as selectActiveRole } from '@/routes/identity/active-role';
import type {
    IdentityContext,
    RoleApp,
    RouteLink,
    SelectActiveRoleInput,
} from '@/types';

/** Why the last switch did not open the app, so the launcher can say what to do next. */
export type RoleSwitchProblem =
    | { kind: 'changed'; role: RoleApp }
    | { kind: 'mfa'; role: RoleApp }
    | { kind: 'unavailable'; role: RoleApp }
    | { kind: 'reused'; role: RoleApp }
    | { kind: 'failed'; role: RoleApp }
    | { kind: 'offline'; role: RoleApp; command: SelectActiveRoleInput };

const PROBLEM_BY_CODE: Record<string, RoleSwitchProblem['kind']> = {
    ACTIVE_ROLE_REVISION_CONFLICT: 'changed',
    MFA_REQUIRED: 'mfa',
    ROLE_NOT_AVAILABLE: 'unavailable',
    ACTIVE_ROLE_REQUIRED: 'unavailable',
    IDEMPOTENCY_KEY_REUSED: 'reused',
};

/** These answers mean the launcher's identity is stale: it is refreshed before the next choice. */
const REFRESH: RoleSwitchProblem['kind'][] = ['changed', 'unavailable'];

const codeOf = (body: string): string | null => {
    try {
        const parsed: unknown = JSON.parse(body);

        return typeof parsed === 'object' &&
            parsed !== null &&
            'code' in parsed &&
            typeof parsed.code === 'string'
            ? parsed.code
            : null;
    } catch {
        return null;
    }
};

/**
 * Selects the active role through the identity-v2 command before opening its app (#96). Each new
 * choice is a new command with a fresh request ID and the current context revision; "Try again"
 * after a lost connection resends that same command unchanged, so it cannot switch twice. The app
 * opens only once the server confirms the requested role is active.
 */
export function useRoleSwitch(
    identity: IdentityContext,
    destination: (role: RoleApp) => RouteLink | string,
) {
    const http = useHttp<SelectActiveRoleInput, { data: IdentityContext }>({
        role: 'investor',
        expected_revision: 0,
        request_id: '',
    });
    const [problem, setProblem] = useState<RoleSwitchProblem | null>(null);

    const send = async (command: SelectActiveRoleInput) => {
        setProblem(null);
        http.transform(() => command);

        let refused: RoleSwitchProblem['kind'] | null = null;

        try {
            const result = await http.post(selectActiveRole().url, {
                onHttpException: (response) => {
                    refused =
                        PROBLEM_BY_CODE[codeOf(response.data) ?? ''] ??
                        'failed';
                },
            });

            if (
                result === undefined ||
                result.data.active_role !== command.role
            ) {
                setProblem({ kind: 'failed', role: command.role });

                return;
            }

            router.visit(destination(command.role));
        } catch {
            const kind = refused ?? 'offline';

            setProblem(
                kind === 'offline'
                    ? { kind, role: command.role, command }
                    : { kind, role: command.role },
            );

            if (REFRESH.includes(kind)) {
                router.reload({ only: ['identity'] });
            }
        }
    };

    return {
        processing: http.processing,
        problem,
        open: (role: RoleApp) =>
            send({
                role,
                expected_revision: identity.context_revision,
                request_id: crypto.randomUUID(),
            }),
        /** Resends the very command a lost connection interrupted: same request ID, same payload. */
        retry: (command: SelectActiveRoleInput) => send(command),
    };
}
