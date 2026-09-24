export type MarketplaceRole = 'investor' | 'business' | 'auditor';
export type MembershipStatus = 'pending' | 'active' | 'suspended' | 'revoked';

export type IdentityContext = {
    contract_version: 'identity-v2';
    policy_version: 'engineering-2026-09-23.4';
    code:
        | 'EMAIL_VERIFICATION_REQUIRED'
        | 'IDENTITY_NOT_LINKED'
        | 'IDENTITY_VERIFICATION_REQUIRED'
        | 'PARTY_AUTHORITY_REQUIRED'
        | 'ROLE_MEMBERSHIP_INVALID'
        | 'ROLE_MEMBERSHIP_CONFLICT'
        | 'ROLE_MEMBERSHIP_REQUIRED'
        | 'IDENTITY_READY';
    party: {
        id: string;
        kind: 'person' | 'organization';
        verification_status: 'verified' | 'unverified';
    } | null;
    available_roles: MarketplaceRole[];
    active_role: MarketplaceRole | null;
    context_revision: number;
    allowed_actions: ('identity.select_role' | 'identity.view_role')[];
};

export type IdentityMutation = {
    contract_version: 'identity-management-v1';
    policy_version: 'engineering-2026-09-23.4';
    code: 'VERIFIED_PERSON_RESOLVED' | 'MEMBERSHIP_UPDATED';
    user_id: number | null;
    party_id: string | null;
    membership: {
        id: string;
        role: MarketplaceRole;
        status: MembershipStatus;
        revision: number;
    } | null;
};

export type SelectActiveRoleInput = {
    role: MarketplaceRole;
    expected_revision: number;
    request_id: string;
};

export type ChangeMembershipInput = {
    party_id: string;
    role: MarketplaceRole;
    status: MembershipStatus;
    expected_revision: number;
    evidence_reference: string;
    reason: string;
    request_id: string;
};

export type ResolveVerifiedPersonInput = {
    user_id: number;
    identity_reference: string;
    evidence_reference: string;
    reason: string;
    request_id: string;
};

export type StaffAccess = {
    contract_version: 'staff-access-v1';
    can_open_admin: boolean;
    allowed_actions: 'admin.open'[];
};

export type RoleBookmark = {
    contract_version: 'role-bookmark-v1';
    role: MarketplaceRole;
    context_revision: number;
    route: `${MarketplaceRole}.home`;
    parameters: Record<string, never>;
    query: { section?: 'overview' | 'access' };
    url: string;
};

export type SaveRoleBookmarkInput = {
    role: MarketplaceRole;
    route: `${MarketplaceRole}.home`;
    parameters: Record<string, never>;
    query: { section: 'overview' | 'access' };
    expected_revision: number;
    request_id: string;
};
