/** Awaiting Rozine's acknowledgement, answered by staff, or closed by staff's resolution. */
export type ComplaintStatus = 'awaiting' | 'answered' | 'resolved';

/**
 * One complaint (BRS v2.0 CMP-R03) with its reference, its 4-hour acknowledgement and 48-hour
 * resolution clocks and its whole conversation; `reply` is null once it is resolved.
 */
export type SupportComplaint = {
    id: string;
    reference: string;
    status: ComplaintStatus;
    opened_at: string;
    acknowledge_by: string;
    resolve_by: string;
    messages: {
        id: string;
        author: 'customer' | 'staff';
        body: string;
        resolves: boolean;
        at: string;
    }[];
    reply: { url: string; method: 'post' } | null;
};

/**
 * A support center's chat: the customer's complaints, newest first, and where a first message
 * opens a new one.
 */
export type SupportCenter = {
    open: { url: string; method: 'post' };
    complaints: SupportComplaint[];
};
