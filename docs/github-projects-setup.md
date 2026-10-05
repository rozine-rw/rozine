# GitHub Projects setup for each operator

The board is currently pending; see [shared coordination instructions](agent-coordination.md) for the verified blocker and intended fields. This PR installs no MCP connection and contains no credentials.

## Codex operator

1. In your own Codex environment, inspect the available GitHub tools and existing connection. Repository access alone does not establish Projects access. This preparation environment could read Projects but its integration could not create one.
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

## Finish the board setup

An operator with existing authorized Projects access should recheck organization and repository Projects, reuse a suitable private board or create **Rozine Delivery**, and link `rozine-rw/rozine`. Configure the intended fields in the shared document and a board view grouped by Status. Preserve existing access; do not make the board public or invite additional collaborators as part of this setup.

Read back the actual URL, privacy, repository link, field names/options and pilot items. Record verified values in the shared document, replacing its pending setup section. Add up to the three existing pilot PRs only after checking their latest state. Do not infer assignments from PR authorship or request new work in #96. Once verified, a single board index link in #96 is enough. If permissions are still missing, leave setup pending and ask the human operator to resolve it; do not generate credentials or change security settings.
