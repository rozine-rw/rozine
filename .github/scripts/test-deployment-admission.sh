#!/usr/bin/env bash
#
# Negative-control suite for the D-68 deployment admission gate.
#
# Every case here is a way the gate must refuse. They run against a stubbed
# `gh` so the suite is hermetic and can exercise states that are impractical to
# stage against live GitHub — a failed evidence run, a skipped required job, an
# author approving their own pull request, an approval that names a superseded
# commit. The one positive case proves the gate still admits a good candidate,
# so a refusal is evidence of the condition and not of a broken script.

set -uo pipefail

HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
GATE="${HERE}/verify-deployment-admission.sh"
WORKDIR="$(mktemp -d)"
trap 'rm -rf "${WORKDIR}"' EXIT

GOOD_SHA="1111111111111111111111111111111111111111"
HEAD_SHA="2222222222222222222222222222222222222222"
OLD_SHA="3333333333333333333333333333333333333333"

# --- stub gh -----------------------------------------------------------------
# Dispatches on the API path and applies the caller's --jq filter exactly as gh
# would, so the gate under test is not modified for testability.
mkdir -p "${WORKDIR}/bin"
cat > "${WORKDIR}/bin/gh" <<'STUB'
#!/usr/bin/env bash
set -euo pipefail
path=""; filter=""
while [ $# -gt 0 ]; do
  case "$1" in
    api|--paginate) shift ;;
    --jq) filter="$2"; shift 2 ;;
    --header) shift 2 ;;
    *) [ -z "$path" ] && path="$1"; shift ;;
  esac
done
case "$path" in
  *git/ref/heads/*) body="$(cat "$FIXTURES/ref.json")" ;;
  *actions/workflows/*) body="$(cat "$FIXTURES/runs.json")" ;;
  *actions/runs/*/jobs*) body="$(cat "$FIXTURES/jobs.json")" ;;
  *commits/*/pulls) body="$(cat "$FIXTURES/pulls.json")" ;;
  *pulls/*/reviews*) body="$(cat "$FIXTURES/reviews.json")" ;;
  *) echo "stub gh: unexpected path $path" >&2; exit 64 ;;
esac
if [ -n "$filter" ]; then printf '%s' "$body" | jq -r "$filter"; else printf '%s' "$body"; fi
STUB
chmod +x "${WORKDIR}/bin/gh"

# --- fixture builders --------------------------------------------------------
fixtures() { FIXTURES="${WORKDIR}/fx"; rm -rf "${FIXTURES}"; mkdir -p "${FIXTURES}"; export FIXTURES; }

write_ref()  { echo "{\"object\":{\"sha\":\"$1\"}}" > "${FIXTURES}/ref.json"; }
write_runs() { echo "{\"workflow_runs\":[{\"id\":99,\"status\":\"$1\",\"conclusion\":$2,\"created_at\":\"2026-09-06T10:00:00Z\"}]}" > "${FIXTURES}/runs.json"; }
write_jobs() {
  echo "{\"jobs\":[{\"name\":\"PHP 8.5 quality gate\",\"conclusion\":\"$1\"},
                   {\"name\":\"TypeScript/React quality gate\",\"conclusion\":\"$2\"},
                   {\"name\":\"PostgreSQL concurrency lane\",\"conclusion\":\"success\"},
                   {\"name\":\"PHP gate negative controls\",\"conclusion\":\"success\"},
                   {\"name\":\"Deployment admission negative controls\",\"conclusion\":\"success\"}]}" > "${FIXTURES}/jobs.json"
}
write_pulls() { echo "$1" > "${FIXTURES}/pulls.json"; }
write_reviews() { echo "$1" > "${FIXTURES}/reviews.json"; }

merged_pr() {
  echo "[{\"number\":42,\"base\":{\"ref\":\"main\"},\"merged_at\":\"2026-09-06T11:00:00Z\",
         \"user\":{\"login\":\"erastus\"},\"head\":{\"sha\":\"${HEAD_SHA}\"}}]"
}

# Default: a candidate that should be admitted.
baseline() {
  fixtures
  write_ref "${GOOD_SHA}"
  write_runs completed '"success"'
  write_jobs success success
  write_pulls "$(merged_pr)"
  write_reviews "[{\"state\":\"APPROVED\",\"commit_id\":\"${HEAD_SHA}\",\"user\":{\"login\":\"aminu\"},\"submitted_at\":\"2026-09-06T11:30:00Z\"}]"
}

# --- runner ------------------------------------------------------------------
pass=0; fail=0

check() {
  local name="$1" expect="$2" want="$3"
  local out code
  out="$(PATH="${WORKDIR}/bin:${PATH}" \
        GH_TOKEN=stub GITHUB_REPOSITORY=rozine-rw/rozine \
        CANDIDATE_SHA="${GOOD_SHA}" TARGET_BRANCH=main \
        WAIT_TIMEOUT_SECONDS=1 POLL_INTERVAL_SECONDS=1 \
        ATTESTATION_PATH="${WORKDIR}/attestation.json" \
        bash "${GATE}" 2>&1)"
  code=$?

  if [ "${expect}" = "admit" ] && [ "${code}" -ne 0 ]; then
    echo "FAIL ${name}: expected admission, exited ${code}"; echo "${out}" | sed 's/^/     /'; fail=$((fail+1)); return
  fi
  if [ "${expect}" = "refuse" ] && [ "${code}" -eq 0 ]; then
    echo "FAIL ${name}: expected refusal, gate admitted"; fail=$((fail+1)); return
  fi
  if [ -n "${want}" ] && ! echo "${out}" | grep -qF "${want}"; then
    echo "FAIL ${name}: refusal did not mention '${want}'"; echo "${out}" | sed 's/^/     /'; fail=$((fail+1)); return
  fi
  echo "ok   ${name}"
  pass=$((pass+1))
}

echo "D-68 deployment admission negative controls"
echo

baseline
check "admits a fully evidenced, non-author-approved candidate" admit ""

baseline; write_ref "${OLD_SHA}"
check "refuses a candidate that is no longer the branch tip" refuse "stale candidate"

baseline; write_runs completed '"failure"'
check "refuses a failed evidence run" refuse "concluded 'failure'"

baseline; write_runs in_progress 'null'
check "refuses an in-progress evidence run" refuse "timed out"

baseline; echo '{"workflow_runs":[]}' > "${FIXTURES}/runs.json"
check "refuses a candidate with no evidence run at all" refuse "timed out"

baseline; write_jobs skipped success
check "refuses a skipped required job inside a green run" refuse "'PHP 8.5 quality gate' is 'skipped'"

baseline; write_jobs success failure
check "refuses a failed client gate inside the run" refuse "'TypeScript/React quality gate' is 'failure'"

baseline; write_pulls '[]'
check "refuses a direct push with no merged pull request" refuse "direct push cannot deploy"

baseline; write_reviews "[{\"state\":\"APPROVED\",\"commit_id\":\"${HEAD_SHA}\",\"user\":{\"login\":\"erastus\"},\"submitted_at\":\"2026-09-06T11:30:00Z\"}]"
check "refuses an author approving their own pull request" refuse "no APPROVED review"

baseline; write_reviews "[{\"state\":\"APPROVED\",\"commit_id\":\"${OLD_SHA}\",\"user\":{\"login\":\"aminu\"},\"submitted_at\":\"2026-09-06T11:30:00Z\"}]"
check "refuses an approval that names a superseded commit" refuse "no APPROVED review"

baseline; write_reviews "[{\"state\":\"CHANGES_REQUESTED\",\"commit_id\":\"${HEAD_SHA}\",\"user\":{\"login\":\"aminu\"},\"submitted_at\":\"2026-09-06T11:30:00Z\"}]"
check "refuses when the non-author requested changes instead of approving" refuse "no APPROVED review"

echo
echo "${pass} passed, ${fail} failed"
[ "${fail}" -eq 0 ]
