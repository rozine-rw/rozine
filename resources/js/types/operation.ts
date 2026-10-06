/**
 * The shared operation Resource every JSON command and the operation lookup return
 * (business-application-v1 points 2 and 7, auditor-filing-v1 point 7). `status` says whether the
 * operation ran; `code` is its specific outcome or a persisted denial's own domain code. `data`
 * carries the authorized updated record(s) and the page to continue to.
 */
export type OperationResource<Data> = {
    operation_id: string;
    status: 'completed' | 'pending' | 'rejected';
    code: string;
    data: Data | null;
    revision: number | null;
    policy_version: string | null;
    /** When the outcome was recorded, ISO 8601. It never changes across lookups and replays. */
    recorded_at: string;
    /** When the server sent this response, ISO 8601. It describes the response, not a clock. */
    server_time: string;
    allowed_actions: string[];
    field_errors: Record<string, string | string[]>;
};

/**
 * A command exactly as sent, kept whole so an uncertain outcome is looked up and retried
 * unchanged: same body, same `request_id`. `name` is also the operation lookup's `command` query.
 */
export type OperationCommand<Name extends string = string> = {
    name: Name;
    payload: Record<string, unknown> & { request_id: string };
};
