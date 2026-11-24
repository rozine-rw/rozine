/** One of the three legal documents, with the version and digest a consent binds. */
export type LegalDocumentLink = {
    key: 'terms' | 'privacy' | 'disclaimers';
    title: string;
    version: string;
    sha256: string;
    url: string;
};

/** One legal document's current wording, rendered from the server's Markdown with raw HTML stripped. */
export type LegalDocumentText = Omit<LegalDocumentLink, 'url'> & {
    html: string;
};

export type LegalDocumentPageProps = {
    document: LegalDocumentText;
    documents: LegalDocumentLink[];
    /** Where a signed-in person who has not agreed to the current wording goes to agree; otherwise null. */
    review: string | null;
};

export type LegalReviewProps = {
    documents: LegalDocumentLink[];
    accept: string;
};
