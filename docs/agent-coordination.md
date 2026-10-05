# Shared agent coordination

Codex and Claude Code use this document for coordination. It supplements the development, testing and deployment rules in `AGENTS.md` and `CLAUDE.md`; it does not replace them or grant additional authority.

## Board setup status

**Pending as of 2026-10-05.** Board URL: **PENDING — no project created**. Field IDs and repository link: **PENDING — not verified**.

Inspection found zero Projects in `rozine-rw` and zero linked to `rozine-rw/rozine`. Although `viewerCanCreateProjects` was true, `createProjectV2` failed with `Resource not accessible by integration`. A follow-up read still found zero organization Projects. No credentials, permissions or access settings were changed.

The intended private organization project is **Rozine Delivery**, linked to this repository. The table below is the requested setup, not a claim that fields exist. An operator with existing authorized access must complete and verify it, then replace the pending values here. Recheck existing Projects before creating one to avoid a duplicate. Keep existing access boundaries; repository linking does not authorize expanding project access.

| Field | Intended configuration |
| --- | --- |
| Status | Single select: Backlog, Ready, In progress, Review, Blocked, Done |
| Assignees | Built-in field; accountable human who has accepted the work |
| Agent | Single select: Codex, Claude Code |
| Reviewer | Text; accepted reviewer's GitHub login, otherwise blank |
| Priority | Single select: High, Medium, Low; leave blank until a human sets it |

Use a board view grouped by Status. Add existing issues directly; where work already exists only as a PR, use that PR as its item. If an issue and linked PR describe one task, retain one task card and link the PR rather than creating a second backlog. Keep acceptance criteria, dependencies, decisions and evidence in the task issue or linked PR. The board is an index of current state.

[Issue #96](https://github.com/rozine-rw/rozine/issues/96) remains the historical integration record. Do not migrate its entire history, close it, or repeat its status stream. After the board and repository link are verified, one short index link there is sufficient. Until then, use the existing issues and PRs.

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

## Pilot candidates, not a second backlog

This is a one-time setup proposal observed on 2026-10-05. **No items or assignments were changed.** Recheck the live PRs before adding them; all three had no GitHub assignees. Leave Assignees, Reviewer and Priority blank until accepted. Do not copy the historical #177 checklist into new tasks.

| Existing item | Proposed Status | Evidence and next action |
| --- | --- | --- |
| [#220: live Investor Deals](https://github.com/rozine-rw/rozine/pull/220) | Review | Draft at `a787c9ca5477fa9464997214fbe7600f87c5ac7b`; earlier head received a lifecycle finding and the new head contains a correction. Exact-head rereview remains needed. #96 records Engineersticity's accepted Deals lane and Claude Code usage; this does not assign a new human or review commitment. |
| [#219: phased-plan progress](https://github.com/rozine-rw/rozine/pull/219) | Review | Draft at `46a104999fea369c8275aee4197c8dfe71c67667`; the admission-prerequisite correction is source-verified, with acceptance/evidence limits preserved in its review. Recheck current head and gates before the next decision. |
| [#177: C3 chain browser journey](https://github.com/rozine-rw/rozine/pull/177) | Blocked | Draft at `22ff2fb1a379d027bf2051c940bfead9e907ca06`; latest #96 keeps real financial browser integration open. Reconcile its older checklist with current code and remaining admission, funded writer, destination, forward-settlement and Holding prerequisites before removing skips. |

These statuses indicate coordination needs, not financial readiness or policy adoption. #180 remains a separate open signoff gate; adding three pilot items does not imply other work is complete.

Each operator's connection checklist is in [GitHub Projects setup](github-projects-setup.md).
