# Initial staff setup and recovery

Ordinary superadmin grants still require two current `staff.manage` holders. The
separate `identity:staff-recovery` command can establish the first pair or restore
a pair when fewer than two managers remain. It is inactive by default:
`config/staff-recovery.php` contains no enrolled operators. Shipping this source
does not enroll anyone or authorize an operational grant.

The owner must independently authorize and review operator enrollment before any
operational use. Enrollment maps dedicated account IDs to their current verified
staff-person identity digests in configuration. Account IDs or self-declared
names alone are insufficient. Use existing identity provisioning and verified
staff-person recording, with ordinary staff roles, to prepare eligible accounts;
no superadmin is needed for that existing identity-operator process. Do not put
passwords, authenticator secrets or recovery codes in configuration, arguments,
environment variables, tickets or logs. No actual identities are supplied here.

Two enrolled operators and two recipients must represent **four distinct verified
people**. Neither operator may receive the grant or approve their own request.
All four accounts need verified email, confirmed authenticator MFA, a dedicated
account with no participant party and a current verified staff-person record.
The operators' authority comes from independently approved enrollment, not from
existing `staff.manage` roles. Disabled ordinary staff access does not remove
that separately enrolled recovery authority; revoke enrollment explicitly.

An operator interactively runs `identity:staff-recovery request` with `--operator`,
`--kind=genesis` or `--kind=recovery`, exactly two `--recipient` values, an audit
`--reason` and optional UUID `--request-id`. The command prompts privately for the
current password and authenticator code. Review the returned proposal ID,
recipient IDs and payload SHA-256 through an independently trusted channel.
No privileges change at this stage. The second operator independently reviews
that exact request, then runs `identity:staff-recovery approve` with their own
operator ID, the proposal ID, `--payload-sha256`, their independently understood
`--kind` and two `--recipient` IDs, their audit reason and request UUID,
authenticates privately, and confirms. The expected kind and recipients must
match the immutable proposal; a digest relayed with misleading target details
is refused. Independently identify those accounts rather than blindly copying
the requester's receipt. Requests expire after ten minutes.
Noninteractive invocation is refused.

Genesis is available only before any historical superadmin grant. Revoking or
disabling everyone does not reopen genesis. Recovery requires prior superadmin
history and fewer than two currently effective managers. If one manager remains,
that manager must be one of the approved recipient pair. Existing roles are
retained. Two effective managers close this recovery path; use ordinary approvals
thereafter. A changed identity revision, credential, recipient state, manager
roster or enrollment configuration requires a fresh request.

The proposal, both authenticated actors, policy version, reviewed payload digest,
two child grant events and completion are retained in immutable audit records.
The two grants and completion commit atomically. Unique completion and genesis
indexes plus serialized, locked eligibility checks prevent duplicate execution.
Reusing the same request UUID and payload returns its receipt without another
grant; changing its payload is refused. A later approval UUID cannot consume an
already completed proposal. Failed password/MFA attempts are throttled. A TOTP
may be consumed even if a later database failure rolls back: obtain a new current
code, reconcile the receipt, and retry the same request UUID when appropriate.

Operator enrollment, credential custody and any actual execution require the
owner's separate operational authorization. There is no one-person, raw account-ID
or console-only exemption from the two-person policy.
