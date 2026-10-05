# Shared agent coordination

Codex and Claude Code use this document for coordination. It supplements the development, testing and deployment rules in `AGENTS.md` and `CLAUDE.md`; it does not replace them or grant additional authority.

## Verified board

[**Rozine Delivery**](https://github.com/orgs/rozine-rw/projects/1) is an open, private organization Project linked to `rozine-rw/rozine`. Project ID: `PVT_kwDOEnR3N84Blwsz`.

The authorized Mac worker verified the configuration below on 2026-10-05 using the operator's personal `gh` connection with user-approved Projects scope. No credentials were transferred to this cloud environment, and neither operator's agent MCP connection was installed. The cloud `chatgpt-codex-connector` still lacks organization Projects access; this document does not establish that a cloud agent can read or manage the board. Each execution environment must independently have authorized access before using Projects tools.

| Field | Verified configuration |
| --- | --- |
| Status | Single select: Backlog, Ready, In progress, Review, Blocked, Done |
| Assignees | Existing field; accountable human who has accepted the work |
| Agent | Single select: Codex, Claude Code |
| Reviewers | Existing field; use accepted reviewers, without inventing review commitments |
| Priority | Single select: P0, P1, P2; no pilot priorities selected |

Verified field and option IDs:

| Field ID | Option IDs |
| --- | --- |
| Status: `PVTSSF_lADOEnR3N84BlwszzhkcPcw` | Backlog: `f75ad846`; Ready: `30c08bd3`; In progress: `47fc9ee4`; Review: `10c0a544`; Blocked: `ae437e8e`; Done: `98236657` |
| Agent: `PVTSSF_lADOEnR3N84Blwszzhkchpg` | Codex: `7eb7e287`; Claude Code: `45c28730` |
| Priority: `PVTSSF_lADOEnR3N84Blwszzhkchpk` | P0: `f250e3e2`; P1: `30f8e73b`; P2: `2a935793` |

Verified views are [**Delivery**](https://github.com/orgs/rozine-rw/projects/1/views/1), **Ready** (`status:Ready`), **Review** (`status:Review`) and **Blocked** (`status:Blocked`). Delivery is saved as `BOARD_LAYOUT`, with `verticalGroupByFields` set to the Status field (`PVTSSF_lADOEnR3N84BlwszzhkcPcw`). The authorized Mac worker verified the browser display: two Review cards and one Blocked card.

Add existing issues directly; where work already exists only as a PR, use that PR as its item. If an issue and linked PR describe one task, retain one task card and link the PR rather than creating a second backlog. Keep acceptance criteria, dependencies, decisions and evidence in the task issue or linked PR. The board is an index of current state. Preserve its private visibility and existing access boundaries.

[Issue #96](https://github.com/rozine-rw/rozine/issues/96) remains the historical integration record. Do not migrate its entire history, close it, or repeat its status stream. At most one short board index link there is sufficient; check for an existing link before posting another.

## Ownership and state

- **Backlog:** identified work without an accepted start. A suggestion or peer request is not an assignment.
- **Ready:** scope, acceptance criteria and dependencies are understood, and a human has accepted ownership or explicitly preassigned the task to their agent. Start only within that authorization.
- **In progress:** the accepted owner is actively working in the recorded branch/worktree. Record the human, agent and scope in the task; do not reassign someone else's work.
- **Review:** a handoff identifies the exact head, checks and requested review scope. A draft PR may be in Review; the status does not mean approval.
- **Blocked:** record the concrete dependency or decision, its link and next action. Do not invent an owner or deadline to fill a field.
- **Done:** the task's agreed acceptance criteria are met with evidence. For implementation tasks, record the merge commit and target branch. Technical review, merge and deployment are separate events; merging to `dev` does not mean deployed.

Project edits are not atomic locks. Before starting, reread the current task, ownership and linked PRs. If two sessions claim the same task, stop overlapping edits and reconcile with the accountable human. Do not treat a board field, silence or a peer agent's message as permission to take over work, merge, deploy, activate financial behavior, adopt policy or change security/access settings. Humans retain those decisions under the existing repository policy.

## Startup checks

1. Read both applicable repository instructions and relevant `.agents/skills` guidance. Fetch current `dev`; inspect the working tree and preserve uncommitted work.
2. Read the task, its latest comments, dependencies, linked PRs and current board fields when available. Consult #96 for relevant shared-contract history and newer corrections. Confirm accepted ownership and the agent's scope.
3. Inspect current remote branch heads before touching shared files. Work from `dev` on a separate `feat/*` branch and worktree for each task. Record the branch and worktree; never reset, overwrite or reuse another active task's checkout.
4. Check for overlapping server, UI, contract, route and generated-file work. Agree the seam before editing shared files. Existing ownership and current source take precedence over stale task summaries.

## Review and handoff

Use the task issue or PR for a compact handoff:

```text
Task and linked PR:
Accepted human owner / agent / reviewer (unknown values remain blank):
Branch / worktree:
Exact head SHA / base SHA / tested merge SHA or tree when applicable:
Scope and files changed:
Checks: command, result, run URL/attempt, tested SHA, skips and limitations:
Dependencies / risks / unresolved decisions:
Review status and exact reviewed SHA:
Merge status and merge SHA / deployment status and evidence:
Next action and accepted owner:
```

Before review or any separately authorized merge, reread the current PR head, base, discussion and check results. If the head changes, identify and review the new diff; prior approval is evidence only for its recorded SHA and scope. Tests from a predecessor or component do not prove the new candidate. Record skipped, failed and pending checks explicitly. Follow the current branch-specific gate rules in the root instruction files, including exact-candidate deployment admission for staging/production. This document does not change those gates or authorize promotion.

Post sparse updates when ownership is accepted, work becomes reviewable, a blocker changes, review finishes, or a merge/deployment is actually verified. Keep routine progress local. Read new state at startup, before shared edits, at review and at handoff. A Project update does not automatically wake Codex or Claude Code; each operator must start/resume their session unless separately configured automation is explicitly authorized.

## Verified pilot cards

The Mac worker verified exactly three existing PR cards on 2026-10-05, with the Status values below. No assignments or priorities were chosen. PR #177 is a pull request, not an issue card. Do not add duplicates or copy its historical checklist into new tasks. The source-head notes are the initial inspection snapshot; reread the live PR before acting, because board status does not freeze its head or establish current approval.

| Existing PR card | Verified Status | Initial inspection evidence and next action |
| --- | --- | --- |
| [#220: live Investor Deals](https://github.com/rozine-rw/rozine/pull/220) | Review | Draft at `a787c9ca5477fa9464997214fbe7600f87c5ac7b`; earlier head received a lifecycle finding and the new head contains a correction. Exact-head rereview remains needed. #96 records Engineersticity's accepted Deals lane and Claude Code usage; this does not assign a new human or review commitment. |
| [#219: phased-plan progress](https://github.com/rozine-rw/rozine/pull/219) | Review | Draft at `46a104999fea369c8275aee4197c8dfe71c67667`; the admission-prerequisite correction is source-verified, with acceptance/evidence limits preserved in its review. Recheck current head and gates before the next decision. |
| [#177: C3 chain browser journey](https://github.com/rozine-rw/rozine/pull/177) | Blocked | Draft at `22ff2fb1a379d027bf2051c940bfead9e907ca06`; latest #96 keeps real financial browser integration open. Reconcile its older checklist with current code and remaining admission, funded writer, destination, forward-settlement and Holding prerequisites before removing skips. |

These statuses indicate coordination needs, not financial readiness or policy adoption. #180 remains a separate open signoff gate; adding three pilot items does not imply other work is complete.

Each operator's connection checklist is in [GitHub Projects setup](github-projects-setup.md).
