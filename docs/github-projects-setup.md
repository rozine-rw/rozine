# GitHub Projects setup for each operator

The private [Rozine Delivery board](https://github.com/orgs/rozine-rw/projects/1) is configured and linked to `rozine-rw/rozine`; see [shared coordination instructions](agent-coordination.md) for verified fields, views and three pilot PR cards. The Delivery board is verified as grouped by Status, showing two Review cards and one Blocked card. This PR installs no MCP connection and contains no credentials.

## Codex operator

1. In your own Codex environment, inspect the available GitHub tools and existing connection. Repository access alone does not establish Projects access. This cloud environment's `chatgpt-codex-connector` lacks organization Projects access. An empty initial list did not prove project read access; subsequent project-node reads and creation were denied. The Mac worker's user-approved personal `gh` Projects scope works only in that authorized environment and does not grant access here.
2. If you already use the official GitHub MCP server, explicitly include `projects` in its enabled toolsets, preserving other needed toolsets and existing restrictions. Otherwise follow the [official Codex installation guide](https://github.com/github/github-mcp-server/blob/main/docs/installation-guides/install-codex.md) in your own client. Connection or permission changes require your own decision; this repository does not configure them.
3. Refresh the session's tool discovery. Verify read access by listing the organization's Projects, then reading the selected project's fields and items. Only test a task status update when that task is yours and the update is authorized; reread it to verify persistence.
4. Start/resume Codex with the task URL and ask it to read `AGENTS.md` and the shared coordination instructions. It must check current ownership and head before starting.

## Claude Code operator

1. In your own Claude Code environment, inspect its existing GitHub MCP connection and tool availability. The other operator's connection and permissions do not transfer to you.
2. Follow the [official Claude installation guide](https://github.com/github/github-mcp-server/blob/main/docs/installation-guides/install-claude.md) for your client if setup is needed. Explicitly enable the `projects` toolset in your own server configuration, preserving existing restrictions and required toolsets. Keep authentication outside the repository; do not copy another operator's credentials.
3. Refresh tool discovery and independently list the organization's Projects, read fields and items, and verify any authorized update to your own task. If access is denied, report the exact operation to your human operator instead of broadening access.
4. Start/resume Claude Code with the task URL and ask it to read `CLAUDE.md` and the shared coordination instructions. Project changes alone do not start an agent session.

## Server configuration reference

The official server's default toolsets omit Projects. For a remote server, the documented configuration mechanism is an `X-MCP-Toolsets` header; a local server uses `--toolsets` or `GITHUB_TOOLSETS`. An example toolset list is `context,issues,pull_requests,repos,users,projects`. Merge it with the operator's existing selection rather than replacing unrelated settings. Enabling tools does not grant GitHub permissions, and read-only mode still prevents writes.

Use the [official server configuration guide](https://github.com/github/github-mcp-server/blob/main/docs/server-configuration.md) and [toolset reference](https://github.com/github/github-mcp-server#available-toolsets) for current supported settings. These are server concepts, not a configuration file to paste unchanged into either client. Never commit credentials or either operator's private MCP configuration.

## Complete each environment's access check

Reuse the existing private board; do not create another. The authorized Mac worker has verified its URL, private/open state, repository link, fields, views and exactly three PR cards: #219 Review, #220 Review and #177 Blocked. Delivery uses `BOARD_LAYOUT` and its verified `verticalGroupByFields` is Status; board display setup is complete. No assignments or priorities have been selected.

The operator-approved personal `gh` Projects scope on the Mac is separate from both agents' MCP connections and the cloud connector. No credentials were copied between environments. Before an agent reads or updates the board, its operator must verify that environment's connection and authorization. If access is unavailable, report the blocker and continue authorized work through the existing task issues and PRs; do not retry denied writes, switch credentials, or change permissions on the agent's own initiative.

For the cloud connector, the verified missing grant is organization Projects. If its provider requests a supported permission update, an organization owner can review it. If the app does not request Projects, an organization owner cannot add that capability independently. Enabling the official MCP server's `projects` toolset alone does not fix this separate connector grant. See [GitHub's permission-update guidance](https://docs.github.com/en/apps/using-github-apps/approving-updated-permissions-for-a-github-app).

Keep existing access boundaries. Do not infer assignments from PR authorship. Check #96 before adding its single board index link, and leave its history intact. Board changes do not automatically wake either agent.
