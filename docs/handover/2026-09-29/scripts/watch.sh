#!/usr/bin/env bash
S=/private/tmp/claude-501/-Users-engineersticity-Documents-projects-rozine--claude-worktrees-rozine-mvp-handover-f8c717/39184b4e-2867-4960-913f-072766a49b6e/scratchpad/watch-state
mkdir -p $S; R=rozine-rw/rozine
[ -f $S/last ] || date -u +%Y-%m-%dT%H:%M:%SZ > $S/last
while true; do
  last=$(cat $S/last); now=$(date -u +%Y-%m-%dT%H:%M:%SZ)
  gh api "repos/$R/issues/comments?since=$last&per_page=100" --jq '.[] | select(.user.login!="Engineersticity") | "\(.html_url | capture("/(issues|pull)/(?<n>[0-9]+)").n | "#"+.) \(.user.login) \(.html_url): \(.body[0:300] | gsub("\n";" "))"' 2>/dev/null && echo "$now" > $S/last
  cur=$( (for p in $(gh pr list --repo $R --state open --json number --jq '.[].number' 2>/dev/null); do gh pr checks $p --repo $R --json name,bucket --jq '.[] | select(.bucket!="pending") | "PR'$p' \(.name): \(.bucket)"' 2>/dev/null; gh pr view $p --repo $R --json state,isDraft,headRefOid,author --jq '"PR'$p' \(.author.login) state \(.state) draft=\(.isDraft) head=\(.headRefOid[0:8])"' 2>/dev/null; done; git -C ~/Documents/projects/rozine ls-remote origin 'refs/heads/codex/*' 2>/dev/null | awk '{print "BRANCH " $2 " " substr($1,1,8)}') | sort)
  if [ -n "$cur" ]; then [ -f $S/prev ] && comm -13 $S/prev <(echo "$cur"); echo "$cur" > $S/prev; fi
  sleep 60
done
