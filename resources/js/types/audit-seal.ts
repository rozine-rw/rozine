/**
 * The public audit seal verification (rozine-rw/rozine#115 review): exactly the minimal facts the
 * public verification endpoint returns as JSON, rendered for a person. Nothing about the Business,
 * its signers, the note, the findings or any document is part of it.
 */
export type AuditSealVerification = {
    report_id: string;
    digest: string;
    seal_status: 'valid' | 'unavailable';
    /** The report this one amends, when it is an amendment. */
    amends_id: string | null;
    /** The report that amends this one, when it has been amended. */
    amended_by: string | null;
};

export type AuditVerifySealProps = {
    seal: AuditSealVerification;
};
