# Delivery board sync proposal

This implementation is for review. It does not create/install an App, generate a
key, enter secrets, enable sync, change the board, merge or deploy. It targets
[Rozine Delivery #1](https://github.com/orgs/rozine-rw/projects/1) in
`rozine-rw/rozine`, using the existing [coordination protocol](agent-coordination.md).
The supplied plan contains 82 cards across Phases 0–8: 26 linked issues/PRs and
56 drafts. This worker has not queried Projects to reconfirm that snapshot.
The known cloud `GH_PROJECTS_TOKEN` returns 403; this implementation never uses it.

## Proposed mapping and boundaries

| Trigger / current state | Proposed result |
| --- | --- |
| Same-repository PR targeting `dev`, `ready_for_review`, exact approved head and existing card, current Status `In progress` | `Review`, only after separate write enablement |
| The same approved event, current `Review` | No change (replay is idempotent) |
| Backlog, Ready, Blocked, Done, unset/unknown Status, archived card | Preserve; unset/unknown fails closed |
| Issue open/close/reopen, PR close/merge/reopen, pushes, comments, PR synchronization, draft changes | No subscribed event and no mapping |
| Fork PR or another repository/base/project | Refuse; forks are filtered before App token generation |
| Manual dispatch on `dev` | Read-only preflight/dry-run, even when event writes are enabled |

`Review` records a handoff, not approval, acceptance or deployment. There is no
automatic `Done`, including for a merged PR or a closed issue. Financial
acceptance, #180 signoff, deployment and policy adoption retain their existing
human gates. A draft PR can still be manually moved to Review with its exact-head
handoff under the coordination protocol.

Only existing PR cards explicitly approved in
[the allowlist](../.github/board-sync-allowlist.json) are eligible. The shipped
allowlist is empty. No issue/PR/card is created, imported, deleted, converted,
archived or reassigned. Only the existing Status field can be written; Phase,
Delivery notes, Agent, Priority, Assignees and Reviewers stay intact. Original
cards #219, #220 and #177 have no implicit authorization. No owner is inferred.
Agents continue discussing work in linked issues/PRs and using #96 for cross-task
history; this adds no messaging service and does not wake agent sessions.

The allowlist approves **one exact PR head and card identity**, not a standing
right to overwrite manual decisions. Before adding a row, the accepted human
owner must approve the handoff and its transition from In progress to Review in
the linked issue/PR. Do not enroll a task with an unresolved manual hold. A
reviewed row has these keys (replace all placeholders with verified identities):

```json
{
    "number": 230,
    "node_id": "PR_VERIFIED_NODE_ID",
    "item_id": "PVTI_VERIFIED_EXISTING_CARD_ID",
    "head_sha": "FULL_40_CHARACTER_REVIEWED_PR_HEAD_SHA"
}
```

Add rows through a separate reviewed `feat/*` → `dev` change. Verify one existing
card for that PR; never add a second card when an issue already represents the
task. After a head changes, the old row cannot write; renew it only with explicit
human approval. To revoke a row, set writes off first, remove it through review,
and update the trusted implementation pin. Editing a manifest on `dev` alone
does not change the pinned manifest used by the workflow.

## App setup — operator steps requiring separate approval

GitHub's [Projects Actions guide](https://docs.github.com/en/issues/planning-and-tracking-with-projects/automating-your-project/automating-projects-using-actions)
states that the ordinary `GITHUB_TOKEN` cannot access Projects and recommends a
GitHub App for organization Projects. Use a **dedicated organization-owned App**,
not a personal token or an existing deployment App.

1. After separate approval, register the App under `rozine-rw` → Settings →
   Developer settings → GitHub Apps. Use the organization homepage as the
   homepage URL. Choose **Only on this account**. Disable Active webhooks;
   Actions supplies the events. No webhook endpoint, callback, OAuth user
   authorization, client secret, device flow or external service is needed.
2. Grant these exact permissions and leave all other optional permissions off:

   | Permission | Level | Purpose |
   | --- | --- | --- |
   | Organization **Projects** | Read and write | Read Project v2 and update its Status field |
   | Repository **Pull requests** | Read-only | Verify current PR identity/state/head |
   | Repository **Issues** | Read-only | Read linked issue identities in the complete board census |
   | Repository **Metadata** | Read-only (mandatory) | Verify repository identity/token audience |

   Repository Projects is not the organization Projects permission. The App
   needs no Contents, Actions, Workflows, Administration, Members, Secrets,
   Checks, Deployments or discussion/comment write permissions. Checkout uses
   the workflow `GITHUB_TOKEN` with Contents read; the App never reads code.
3. Install it on **only `rozine-rw/rozine`** using selected repositories. Keep its
   installation dedicated to this purpose. The workflow requests a token for
   exactly that repository and rejects a token exposing more than one repository.
   Organization Projects permission is organization-wide: GitHub does **not**
   restrict it to Project #1. The script's fixed IDs/URL/number/organization and
   fixed mutation enforce the Project #1 write audience, but a stolen App key
   could access other organization projects. The operator must accept that
   permission boundary before configuration.
4. The authorized human generates and holds the private key, then enters it
   directly into the repository Actions secret `BOARD_SYNC_PRIVATE_KEY`.
   Do not send the key through chat, issue/PR comments or an agent; keep any
   recovery copy in the operator's protected credential store. If using an
   organization secret, restrict its repository audience to `rozine` only.
5. Set repository Actions variables `BOARD_SYNC_CLIENT_ID` (the App's `Iv…`
   client ID), `BOARD_SYNC_INSTALLATION_ID` (numeric organization installation ID,
   not App ID), and `BOARD_SYNC_APP_SLUG` (exact slug from App settings). Set
   `BOARD_SYNC_TRUSTED_SHA` to a **full immutable reviewed commit already merged
   into `dev`** containing this script and its reviewed allowlist. Verify its
   ancestry and diff before pinning; never select a PR head or feature commit.
   Before executing any checked-out script or minting an App token, the workflow
   verifies checkout identity and requires that commit to be an ancestor of the
   fetched `origin/dev`; missing history or an unmerged pin fails closed.
   Set `BOARD_SYNC_WRITES_ENABLED=false`; leave `BOARD_SYNC_ENABLED` unset until
   the read-only stage is separately authorized.

See GitHub's [App registration instructions](https://docs.github.com/en/apps/creating-github-apps/registering-a-github-app/registering-a-github-app)
and the pinned official [token action](https://github.com/actions/create-github-app-token/tree/bcd2ba49218906704ab6c1aa796996da409d3eb1).

## Read-only preflight, then explicit enablement

After review and a separately authorized merge into `dev`, enable **reads only**
with `BOARD_SYNC_ENABLED=true` and `BOARD_SYNC_WRITES_ENABLED=false`. These two
variables must be reviewed independently; neither is set by this PR.

The repository currently defaults to `main`. GitHub requires a dispatchable
workflow to exist on the default branch before it can be manually dispatched.
This does **not** authorize promoting the change to `main` or deploying it. Until
it reaches the default branch through the normal separately authorized promotion
chain, perform read-only validation using an allowlisted same-repository PR's
`ready_for_review` event against `dev`, with writes still false. Do not change the
default branch to bypass rollout policy. Once dispatch is available, run the
workflow explicitly on `dev`: blank PR number validates configuration/project;
an allowlisted PR number previews its transition. Dispatch always requests
Projects **read-only**, regardless of the write-enable variable.

Preflight validates installation ID and slug, token audience, organization ID,
private/open project ID/number/URL/title, Status field/options, full paginated
membership, unique linked card identity, and the current PR/head. A 403, missing
configuration, hidden/incomplete content or schema change fails closed. Do not
retry an authorization failure with the existing cloud token or broaden access
to make the check pass. Stop and reconcile the dedicated App configuration.
Script logs contain fixed outcomes/refusal messages, never tokens, titles, bodies,
board contents, raw API errors or private-card identifiers.

After successful read-only evidence and **separate human approval of the mapping,
App audience, allowlist and race limitation below**, set
`BOARD_SYNC_WRITES_ENABLED=true`. This permits the sole proposed status transition
on future `ready_for_review` events. Past events are not replayed automatically.
This PR's empty manifest remains inert even if both enable variables are true.

## Draft linking and operational limits

Draft Project items do not emit issue/PR events. Keep the 56 drafts and their
metadata; no automatic conversion is provided. When work needs an issue, a human
may separately authorize converting the existing draft in Projects (preserving
and verifying its fields), or create/link a task issue/PR and reconcile the plan
card manually. Record acceptance/dependencies in the linked discussion. If the
issue is the canonical card, keep PR handoff state human controlled; this version
does not follow closing keywords or infer an issue→PR mapping. Enroll a PR card
only after it exists uniquely and replacing/linking any draft has been explicitly
reconciled. No labels, issue text or titles confer write authority.

The workflow serializes board writes and does not cancel a running mutation.
GitHub's default concurrency queue may replace a pending run; do not interpret
the workflow as a durable event ledger. Inspect canceled/missed runs and use a
read-only preflight before a separately authorized rerun or manual reconciliation.
There is no schedule. Bounded retries apply only to transient reads (429/network/
5xx); 401/403, schema and identity failures are not retried. Mutations are never
automatically retried: a timeout may mean the update succeeded, so inspect state
first. Replays read the current status and preserve an already-Review card.

**Projects offers no conditional Status mutation / compare-and-swap.** The script
rereads the board and current PR immediately before writing and preserves a
Blocked/manual state it observes. Workflow serialization cannot lock a human
edit in the remaining read→write window. For a strict guarantee against concurrent
manual edits, keep writes disabled and use the read-only proposal. Before a human
manual override or hold on an enrolled card, disable writes, cancel/wait for
in-flight runs, then change the card and remove/renew its approval as appropriate.
This limitation must be explicitly accepted before enabling writes.

## Disable and rollback

Set `BOARD_SYNC_WRITES_ENABLED=false` immediately to stop new write-enabled runs;
set `BOARD_SYNC_ENABLED=false` (or unset it) to stop future sync jobs entirely.
Variable changes do not interrupt already running jobs: cancel/wait for them
before editing cards. For credential compromise, suspend/uninstall the dedicated
App or revoke its key/installation tokens, then remove the repository secret.
The official token action revokes its short-lived installation token in its post
step during normal runs. No other service needs shutdown.

There is no automatic board rollback: a human verifies the affected card and
restores its previous Status from project activity/evidence if necessary. Never
bulk-reset the 82-card plan or clear its metadata. An implementation rollback
uses a reviewed revert and a reviewed trusted-pin update, with writes off first.

## Offline validation

Run `python3 -B -m unittest discover -s .github/scripts -p 'test_project_board_sync.py' -v`.
The tests simulate hostile event data, forks, wrong projects/App audience,
duplicate/missing cards, pagination, blocked/manual states, stale heads,
ambiguous closure/merge acceptance, read retries, uncertain writes and read-only
dispatch. The `tests` workflow runs these tests with no App secrets alongside
the existing repository gates. Validate the workflow with `actionlint` before
review. These simulations do not establish live private Projects access; a
separately authorized App preflight is still required before enablement.
