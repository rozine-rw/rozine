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
    allowed_actions: (
        | 'identity.select_role'
        | 'identity.view_role'
        | 'identity.add_investor'
    )[];
};

/**
 * What a verified Business member accepts to add Investor access to their own Party, and where
 * the launcher sends that acceptance. The server sends it only while the person may add it.
 */
export type InvestorAccessOffer = {
    terms: { version: string; text: string; sha256: string };
    link: { url: string; method: 'post' };
};

/** The marketplace roles a person asks Rozine's staff for from the launcher (#616). */
export type RequestableRole = 'business' | 'auditor';

/**
 * Which roles the person may ask for from the launcher, when they already asked (ISO time, per
 * role), and where the request is sent. Granting the role stays with staff.
 */
export type RoleAccessOffer = {
    requestable: RequestableRole[];
    /** Empty roles are left out (an empty map arrives as `[]`). */
    sent: Partial<Record<RequestableRole, string>>;
    /**
     * Why a role that is neither requestable nor sent cannot be asked for (#622). Empty roles are
     * left out (an empty map arrives as `[]`).
     */
    unavailable: Partial<
        Record<
            RequestableRole,
            'ROLE_ON_RECORD' | 'AUDITOR_ONLY_ROLE' | 'AUDITOR_ACCOUNT'
        >
    >;
    link: { url: string; method: 'post' };
};

export type AddInvestorAccessInput = {
    expected_revision: number;
    terms_version: string;
    terms_sha256: string;
    accepted: boolean;
    request_id: string;
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
    allowed_actions: (
        | 'admin.open'
        | 'businesses.view'
        | 'businesses.verify'
        | 'applications.review'
        | 'audit.partners.verify'
        | 'audit.assignments.manage'
        | 'audit.reports.view'
        | 'consent.documents.record'
        | 'staging.mail.testers.manage'
    )[];
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

/** UI aliases derive from the server identity contract. */
export type RoleApp = MarketplaceRole;
export type IdentityCode = IdentityContext['code'];
export type IdentityParty = NonNullable<IdentityContext['party']>;

/**
 * Where a person's own KYC and their application to switch from Investor to Business or Auditor
 * stand (role switch, 23 Nov 2026). `status` is `none` before anything is saved.
 */
export type RoleApplicationOffer = {
    kyc: 'none' | 'draft' | 'submitted' | 'approved' | 'rejected';
    status:
        | 'none'
        | 'draft'
        | 'submitted'
        | 'changes_requested'
        | 'approved'
        | 'rejected';
    role: 'business' | 'auditor' | 'institution' | null;
};

/** One answer or document Compliance asked to change (24 Nov 2026), with its optional word on what to fix. */
export type RevisionItem = { key: string; label: string; note: string };

/** The dashboard's progress hub (24 Nov 2026). */
export type HubStep = {
    key: 'account' | 'email' | 'kyc' | 'role' | 'open';
    state:
        | 'done'
        | 'attention'
        | 'rejected'
        | 'review'
        | 'current'
        | 'upcoming';
    role: 'investor' | 'business' | 'auditor' | 'institution' | null;
    at: string | null;
};

export type HubAction = {
    id: string;
    kind:
        | 'kyc_changes'
        | 'role_changes'
        | 'role_rejected'
        | 'kyc_start'
        | 'kyc_continue'
        | 'role_continue'
        | 'two_factor';
    urgent: boolean;
    role: 'business' | 'auditor' | 'institution' | null;
    company: string | null;
    note: string | null;
    items: RevisionItem[];
    at: string | null;
};

export type HubNext =
    | 'kyc_send'
    | 'kyc_resend'
    | 'kyc_review'
    | 'role_send'
    | 'role_resend'
    | 'role_review'
    | 'role_reapply'
    | 'role_switch'
    | 'investor_ready'
    | 'business_ready'
    | 'auditor_ready'
    | 'institution_ready';

export type HubDone = {
    key:
        | 'account'
        | 'email'
        | 'two_factor'
        | 'kyc_sent'
        | 'kyc_approved'
        | 'role_sent'
        | 'role_approved';
    role: 'business' | 'auditor' | 'institution' | null;
    at: string | null;
};

export type HubNotice = {
    id: string;
    kind: string;
    created_at: string;
    read: boolean;
};

export type OnboardingHub = {
    name: string;
    steps: HubStep[];
    actions: HubAction[];
    next: HubNext[];
    done: HubDone[];
    role: 'business' | 'auditor' | null;
    company: string | null;
    recent: HubNotice[];
};
