/**
 * Client view of `App\Http\Resources\IdentityContextResource` (contract `identity-v1`), the
 * launcher's only source of truth for which role apps the signed-in Party may open. Role
 * availability is not permission to transact: every action is still authorized server-side.
 */
export type RoleApp = 'investor' | 'business' | 'auditor';

export type IdentityCode =
    | 'EMAIL_VERIFICATION_REQUIRED'
    | 'IDENTITY_NOT_LINKED'
    | 'IDENTITY_VERIFICATION_REQUIRED'
    | 'PARTY_AUTHORITY_REQUIRED'
    | 'ROLE_MEMBERSHIP_INVALID'
    | 'ROLE_MEMBERSHIP_CONFLICT'
    | 'ROLE_MEMBERSHIP_REQUIRED'
    | 'IDENTITY_READY';

export type IdentityParty = {
    id: string;
    kind: 'person' | 'organization';
    verification_status: 'verified' | 'unverified';
};

export type IdentityContext = {
    contract_version: string;
    policy_version: string;
    code: IdentityCode;
    party: IdentityParty | null;
    available_roles: RoleApp[];
    allowed_actions: string[];
};
