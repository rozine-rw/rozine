export type SwitchRole = 'business' | 'auditor' | 'institution';

export type RoleApplicationStatus =
    | 'none'
    | 'draft'
    | 'submitted'
    | 'changes_requested'
    | 'approved'
    | 'rejected';

export type KycStatus =
    | 'none'
    | 'draft'
    | 'submitted'
    | 'approved'
    | 'rejected';

export type RoleApplicationPerson = {
    full_name: string;
    id_type: 'national_id' | 'passport';
    id_number: string;
    nationality: string;
    ownership_pct?: string;
    pep?: boolean;
};

export type RoleApplicationCompany = {
    legal_name: string;
    rdb_code: string;
    tin: string;
    company_type: string;
    incorporation_date: string;
    industry: string;
    province: string;
    district: string;
    sector: string;
    cell: string;
    /** Empty where the premises has no street name. */
    street: string;
    phone: string;
};

/** A registered address in Rwanda, as the forms collect it (Erastus, 24 Nov 2026). */
export type RoleApplicationAddress = {
    province: string;
    district: string;
    sector: string;
    cell: string;
    street: string;
};

export type RoleApplicationInstitution = {
    type: string;
    /** Whole RWF, as digits. */
    monthly_commitment: string;
    bank_name: string;
    account_name: string;
    account_number: string;
    automates: boolean;
    own_rules: boolean;
};

export type RoleApplicationAnswers = {
    company?: RoleApplicationCompany;
    representative?: { title: string; phone: string; authorised: boolean };
    directors?: RoleApplicationPerson[];
    owners?: RoleApplicationPerson[];
    licence?: {
        membership_number: string;
        licence_number: string;
        expires_on: string;
        firm: string;
    };
    address?: RoleApplicationAddress;
    coverage?: {
        districts: string[];
        no_conflicts: boolean;
        will_declare: boolean;
        ethics: boolean;
    };
    /** An institutional investor's commitment and account (26 Nov 2026). */
    institution?: RoleApplicationInstitution;
    declared: boolean;
};

export type RoleApplicationProps = {
    role: SwitchRole | null;
    application: {
        status: RoleApplicationStatus;
        revision: number;
        role: SwitchRole | null;
        step: string | null;
        answers: RoleApplicationAnswers | null;
        note: string | null;
        /** What Compliance asked to change when it sent the application back (24 Nov 2026). */
        requested_changes?: {
            note: string | null;
            items: { key: string; label: string; note: string }[];
            requested_at: string;
            /** What the answers and documents held when Compliance sent it back. */
            before?: {
                answers: RoleApplicationAnswers;
                documents: Record<string, string>;
            };
        } | null;
        /** `id` names the stored document, so a replaced file counts as changed even with the same name. */
        uploads: Record<
            string,
            { id?: string; filename: string; media_type: string }
        >;
    };
    kyc: KycStatus;
    blockers: string[];
    two_factor: boolean;
    identity_context_revision: number;
    catalog: {
        institution_types: string[];
        min_monthly_commitment: number;
        industries: string[];
        districts: string[];
        /** Each province's districts, for the address's district list. */
        provinces: Record<string, string[]>;
    };
};

/** One application in the Businesses or Auditors page's review tab. */
export type RoleApplicationRow = {
    id: string;
    revision: number;
    role: SwitchRole;
    status: RoleApplicationStatus;
    name: string;
    email: string;
    company: string | null;
    company_code: string | null;
    submitted_at: string | null;
    kyc: KycStatus;
};

export type RoleApplicationDocument = {
    id: string;
    slot: string;
    filename: string;
    media_type: string;
    size_bytes: number;
    uploaded_at: string;
    current: boolean;
};

/** One application opened in the review drawer, with everything Compliance decides on. */
export type RoleApplicationReview = {
    id: string;
    revision: number;
    role: SwitchRole;
    status: RoleApplicationStatus;
    submitted_at: string | null;
    decided_at: string | null;
    account: { name: string; email: string; two_factor: boolean };
    kyc: KycStatus;
    kyc_verification_id: string | null;
    state: {
        role: SwitchRole;
        step: string;
        answers: RoleApplicationAnswers;
        documents: Record<string, string>;
        note: string | null;
        /** The last time Compliance asked for changes, kept after the applicant sends it again. */
        revision?: {
            note: string | null;
            items: { key: string; label: string; note: string }[];
            requested_at: string;
            before?: {
                answers: RoleApplicationAnswers;
                documents: Record<string, string>;
            };
        };
    };
    people: Record<string, { full_name: string; roles: string[] }>;
    documents: RoleApplicationDocument[];
    history: {
        revision: number;
        status: string;
        command: string;
        reason: string | null;
        at: string;
    }[];
    blockers: string[];
    /** Plain-language reasons the answers can't be approved yet; empty unless the application is submitted. */
    problems: string[];
    business_id: string | null;
    allowed_actions: string[];
    /** The answers and documents Compliance may ask the applicant to change, key to label. */
    revisable: Record<string, string>;
};

export type RoleApplicationQueue = {
    /** An institution's KYB is reviewed in the Businesses queue. */
    role: Exclude<SwitchRole, 'institution'>;
    tab: 'submitted' | 'changes_requested' | 'approved' | 'rejected';
    entries: RoleApplicationRow[];
    counts: Partial<Record<RoleApplicationStatus, number>>;
};
