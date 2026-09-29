#!/usr/bin/env bash
# Merge our UI PR once its fast-lane checks pass (dev policy #150). Usage: fastmerge.sh <pr>
# Fast lane = TypeScript/React quality gate + Deployment admission negative controls + every
# "PHP negative controls (<group>)" job (split per group by #155).
P=$1; R=rozine-rw/rozine
H=$(gh pr view $P --repo $R --json headRefOid --jq .headRefOid)
SEL='select(.name=="TypeScript/React quality gate" or .name=="Deployment admission negative controls" or (.name|startswith("PHP negative controls")))'
while true; do
  s=$(gh pr checks $P --repo $R --json name,bucket 2>/dev/null)
  if jq -e "[.[]|$SEL|select(.bucket==\"fail\")]|length>0" <<<"$s" >/dev/null; then echo "PR$P fast lane FAILED: $(jq -r "[.[]|$SEL|select(.bucket==\"fail\")|.name]|join(\", \")" <<<"$s")"; exit 1; fi
  total=$(jq "[.[]|$SEL]|length" <<<"${s:-[]}" 2>/dev/null || echo 0); ok=$(jq "[.[]|$SEL|select(.bucket==\"pass\")]|length" <<<"${s:-[]}" 2>/dev/null || echo 0); total=${total:-0}; ok=${ok:-0}
  [ "$total" -ge 6 ] && [ "$ok" = "$total" ] && break
  [ "$(gh pr view $P --repo $R --json headRefOid --jq .headRefOid)" = "$H" ] || { echo "PR$P head moved; not merging"; exit 1; }
  sleep 30
done
[ "$(gh pr view $P --repo $R --json headRefOid --jq .headRefOid)" = "$H" ] || { echo "PR$P head moved; not merging"; exit 1; }
gh pr merge $P --repo $R --merge --match-head-commit $H 2>&1 | tail -1
gh pr view $P --repo $R --json state,mergeCommit,mergeStateStatus --jq '"PR'$P' \(.state) \(.mergeCommit.oid[0:8]) \(.mergeStateStatus)"'
