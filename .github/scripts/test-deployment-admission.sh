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

set -euo pipefail

HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
GATE="${HERE}/verify-deployment-admission.sh"
WORKDIR="$(mktemp -d)"
trap 'rm -rf "${WORKDIR}"' EXIT

GOOD_SHA="1111111111111111111111111111111111111111"
HEAD_SHA="2222222222222222222222222222222222222222"
OLD_SHA="3333333333333333333333333333333333333333"
REAL_GH="$(command -v gh)"
export REAL_GH

# --- stub gh -----------------------------------------------------------------
# Dispatches on the API path and applies the caller's --jq filter exactly as gh
# would, so the gate under test is not modified for testability.
mkdir -p "${WORKDIR}/bin"
cat > "${WORKDIR}/bin/gh" <<'STUB'
#!/usr/bin/env bash
set -euo pipefail
original=("$@")
path=""; filter=""
while [ $# -gt 0 ]; do
  case "$1" in
    api|--paginate) shift ;;
    --slurp) slurp=true; shift ;;
    --jq) filter="$2"; shift 2 ;;
    --header) shift 2 ;;
    *) [ -z "$path" ] && path="$1"; shift ;;
  esac
done
if [ "${slurp:-false}" = true ] && [ -n "$filter" ]; then
  echo 'the --slurp option is not supported with --jq' >&2
  exit 1
fi
if [[ "$path" == *pulls/*/reviews* ]] && [ -n "${REAL_GH_REVIEW_URL:-}" ]; then
  for index in "${!original[@]}"; do
    if [ "${original[$index]}" = "$path" ]; then original[$index]="$REAL_GH_REVIEW_URL"; fi
  done
  exec "$REAL_GH" "${original[@]}"
fi
case "$path" in
  *git/ref/heads/*) body="$(cat "$FIXTURES/ref.json")" ;;
  *actions/workflows/tests.yml) body='{"id":123}' ;;
  *actions/workflows/*/runs*)
    if [ -f "$FIXTURES/runs-after.json" ] && [ -f "$FIXTURES/runs-read" ]; then
      cp "$FIXTURES/runs-after.json" "$FIXTURES/runs.json"
    fi
    touch "$FIXTURES/runs-read"
    [[ "$path" == *'event=push&branch=main&per_page=100'* || "$path" == *'&branch=uat&per_page=100'* ]] || exit 64
    body="$(cat "$FIXTURES/runs.json")" ;;
  *actions/runs/*/jobs*)
    [[ "$path" == *'/attempts/2/jobs?per_page=100' ]] || exit 64
    body="$(cat "$FIXTURES/jobs.json")" ;;
  *commits/*/pulls) body="$(cat "$FIXTURES/pulls.json")" ;;
  *pulls/*/reviews*) body="$(cat "$FIXTURES/reviews.json")" ;;
  *) echo "stub gh: unexpected path $path" >&2; exit 64 ;;
esac
if [[ "$path" == *actions/workflows/*/runs* ]] && [ -f "$FIXTURES/runs-error" ]; then
  printf '%s' "$body"
  exit 1
fi
if [[ "$path" == *actions/runs/*/jobs* ]] && [ -f "$FIXTURES/jobs-error" ]; then
  printf '%s' "$body"
  exit 1
fi
if [[ "$path" == *pulls/*/reviews* ]] && [ -f "$FIXTURES/reviews-error" ]; then
  printf '%s' "$body"
  exit 1
fi
if [ "${slurp:-false}" = true ]; then body="[$body]"; fi
if [ -n "$filter" ]; then printf '%s' "$body" | jq -r "$filter"; else printf '%s' "$body"; fi
STUB
chmod +x "${WORKDIR}/bin/gh"

# --- fixture builders --------------------------------------------------------
fixtures() { FIXTURES="${WORKDIR}/fx"; rm -rf "${FIXTURES}"; mkdir -p "${FIXTURES}"; export FIXTURES; }

write_ref()  { echo "{\"object\":{\"sha\":\"$1\"}}" > "${FIXTURES}/ref.json"; }
write_runs() {
  cat > "${FIXTURES}/runs.json" <<JSON
{"workflow_runs":[{"id":99,"workflow_id":123,"path":".github/workflows/tests.yml","repository":{"full_name":"rozine-rw/rozine"},"head_repository":{"full_name":"rozine-rw/rozine"},"run_attempt":2,"status":"$1","conclusion":$2,"created_at":"2026-09-06T10:00:00Z","event":"push","head_branch":"main","head_sha":"${GOOD_SHA}"}]}
JSON
}
write_jobs() {
  echo "{\"jobs\":[{\"name\":\"PHP 8.5 quality gate\",\"conclusion\":\"$1\"},
                   {\"name\":\"TypeScript/React quality gate\",\"conclusion\":\"$2\"},
                   {\"name\":\"PostgreSQL concurrency lane\",\"conclusion\":\"success\"},
                   {\"name\":\"PHP gate negative controls\",\"conclusion\":\"success\"},
                   {\"name\":\"Deployment admission negative controls\",\"conclusion\":\"success\"}]}" > "${FIXTURES}/jobs.json"
  jq --arg sha "$GOOD_SHA" '.jobs += [
    {name:"Board sync offline tests",conclusion:"success"},
    {name:"Identify CI validation tree",conclusion:"success"},
    {name:"Select CI scope",conclusion:"success"},
    {name:"Record validation tree",conclusion:"success"}
  ] | .jobs |= map(. + {status:"completed",head_sha:$sha,run_id:99,run_attempt:2})' \
    "$FIXTURES/jobs.json" > "$FIXTURES/complete-jobs.json"
  mv "$FIXTURES/complete-jobs.json" "$FIXTURES/jobs.json"
}
write_pulls() { echo "$1" > "${FIXTURES}/pulls.json"; }
write_reviews() { echo "$1" > "${FIXTURES}/reviews.json"; }

merged_pr() {
  echo "[{\"number\":42,\"base\":{\"ref\":\"main\"},\"merged_at\":\"2026-09-06T11:00:00Z\",\"merge_commit_sha\":\"${GOOD_SHA}\",
         \"user\":{\"login\":\"erastus\"},\"head\":{\"sha\":\"${HEAD_SHA}\"}}]"
}

# Default: a candidate that should be admitted.
baseline() {
  fixtures
  write_ref "${GOOD_SHA}"
  write_runs completed '"success"'
  write_jobs success success
  write_pulls "$(merged_pr)"
  write_reviews "[{\"state\":\"APPROVED\",\"commit_id\":\"${HEAD_SHA}\",\"user\":{\"login\":\"aminu\",\"type\":\"User\"},\"submitted_at\":\"2026-09-06T11:30:00Z\"}]"
}

# --- runner ------------------------------------------------------------------
pass=0; fail=0

check() {
  local name="$1" expect="$2" want="$3"
  local out code
  rm -f "${WORKDIR}/attestation.json"
  out="$(PATH="${WORKDIR}/bin:${PATH}" \
        GH_TOKEN=stub GITHUB_REPOSITORY=rozine-rw/rozine \
        CANDIDATE_SHA="${TEST_CANDIDATE_SHA:-${GOOD_SHA}}" TARGET_BRANCH="${TEST_TARGET_BRANCH:-main}" \
        WAIT_TIMEOUT_SECONDS=1 POLL_INTERVAL_SECONDS=1 \
        ATTESTATION_PATH="${WORKDIR}/attestation.json" \
        bash "${GATE}" 2>&1)" && code=0 || code=$?

  if [ "${expect}" = "admit" ] && [ "${code}" -ne 0 ]; then
    echo "FAIL ${name}: expected admission, exited ${code}"; echo "${out}" | sed 's/^/     /'; fail=$((fail+1)); return
  fi
  if [ "${expect}" = "refuse" ] && [ "${code}" -eq 0 ]; then
    echo "FAIL ${name}: expected refusal, gate admitted"; fail=$((fail+1)); return
  fi
  if [ -n "${want}" ] && ! echo "${out}" | grep -qF "${want}"; then
    echo "FAIL ${name}: refusal did not mention '${want}'"; echo "${out}" | sed 's/^/     /'; fail=$((fail+1)); return
  fi
  if [ "${expect}" = refuse ] && [ -e "${WORKDIR}/attestation.json" ]; then
    echo "FAIL ${name}: refused candidate left an admission attestation"; fail=$((fail+1)); return
  fi
  if [ "${expect}" = admit ] && ! jq -e --arg sha "$GOOD_SHA" '
    .candidate_sha == $sha and .evidence.run_id == "99" and .evidence.run_attempt == "2"
  ' "${WORKDIR}/attestation.json" >/dev/null; then
    echo "FAIL ${name}: attestation is not bound to the candidate/attempt"; fail=$((fail+1)); return
  fi
  echo "ok   ${name}"
  pass=$((pass+1))
}

echo "D-68 deployment admission negative controls"
echo

baseline
check "admits a fully evidenced, non-author-approved candidate" admit ""

for candidate in main 1111111 111111111111111111111111111111111111111G; do
  baseline
  export TEST_CANDIDATE_SHA="$candidate"
  check "refuses mutable or malformed candidate $candidate" refuse "not a full 40-character Git SHA"
  unset TEST_CANDIDATE_SHA
done

baseline; write_ref "${OLD_SHA}"
check "refuses a candidate that is no longer the branch tip" refuse "stale candidate"

baseline; write_runs completed '"failure"'
check "refuses a failed evidence run" refuse "concluded 'failure'"

baseline; write_runs in_progress 'null'
check "refuses an in-progress evidence run" refuse "timed out"

baseline; echo '{"workflow_runs":[]}' > "${FIXTURES}/runs.json"
check "refuses a candidate with no evidence run at all" refuse "timed out"

baseline
jq '.workflow_runs[0].event = "pull_request"' "${FIXTURES}/runs.json" > "${FIXTURES}/changed.json"
mv "${FIXTURES}/changed.json" "${FIXTURES}/runs.json"
check "refuses PR evidence whose API head SHA is not its tested merge" refuse "timed out"

baseline
jq '.workflow_runs[0].head_branch = "dev"' "${FIXTURES}/runs.json" > "${FIXTURES}/changed.json"
mv "${FIXTURES}/changed.json" "${FIXTURES}/runs.json"
check "refuses push evidence from another branch" refuse "timed out"

baseline
jq --arg sha "${OLD_SHA}" '.workflow_runs[0].head_sha = $sha' "${FIXTURES}/runs.json" > "${FIXTURES}/changed.json"
mv "${FIXTURES}/changed.json" "${FIXTURES}/runs.json"
check "refuses push evidence for another candidate" refuse "timed out"

baseline
jq '.workflow_runs += [(.workflow_runs[0] | .id = 100 | .event = "pull_request" | .created_at = "2026-09-06T12:00:00Z")]' "${FIXTURES}/runs.json" > "${FIXTURES}/changed.json"
mv "${FIXTURES}/changed.json" "${FIXTURES}/runs.json"
check "keeps exact push evidence when a newer PR run exists" admit "run 99 succeeded"

baseline
jq '.workflow_runs += [(.workflow_runs[0] | .id = 98 | .run_started_at = "2026-09-06T13:00:00Z" | .conclusion = "failure")]' "${FIXTURES}/runs.json" > "${FIXTURES}/changed.json"
mv "${FIXTURES}/changed.json" "${FIXTURES}/runs.json"
check "refuses a newer failed rerun of an older run ID" refuse "concluded 'failure'"

baseline; write_jobs skipped success
check "refuses a skipped required job inside a green run" refuse "'PHP 8.5 quality gate' is 'skipped'"

baseline; write_jobs success failure
check "refuses a failed client gate inside the run" refuse "'TypeScript/React quality gate' is 'failure'"

baseline; write_pulls '[]'
check "refuses a direct push with no merged pull request" refuse "direct push cannot deploy"

baseline; write_reviews "[{\"state\":\"APPROVED\",\"commit_id\":\"${HEAD_SHA}\",\"user\":{\"login\":\"erastus\"},\"submitted_at\":\"2026-09-06T11:30:00Z\"}]"
check "refuses an author approving their own pull request" refuse "no APPROVED review"

baseline; write_reviews "[{\"state\":\"APPROVED\",\"commit_id\":\"${OLD_SHA}\",\"user\":{\"login\":\"aminu\",\"type\":\"User\"},\"submitted_at\":\"2026-09-06T11:30:00Z\"}]"
check "refuses an approval that names a superseded commit" refuse "no APPROVED review"

baseline; write_reviews "[{\"state\":\"CHANGES_REQUESTED\",\"commit_id\":\"${HEAD_SHA}\",\"user\":{\"login\":\"aminu\",\"type\":\"User\"},\"submitted_at\":\"2026-09-06T11:30:00Z\"}]"
check "refuses when the non-author requested changes instead of approving" refuse "no APPROVED review"

baseline
jq '.workflow_runs[0].event = "workflow_dispatch"' "${FIXTURES}/runs.json" > "${FIXTURES}/changed.json"
mv "${FIXTURES}/changed.json" "${FIXTURES}/runs.json"
check "production refuses dispatched evidence instead of its push gates" refuse "timed out"

baseline
jq '.[0].user.type = "Bot"' "${FIXTURES}/reviews.json" > "${FIXTURES}/changed.json"
mv "${FIXTURES}/changed.json" "${FIXTURES}/reviews.json"
check "refuses automated approval instead of a genuine non-author review" refuse "no APPROVED review"

baseline
jq '. += [(.[0] | .state = "CHANGES_REQUESTED" | .submitted_at = "2026-09-06T12:00:00Z")]' "${FIXTURES}/reviews.json" > "${FIXTURES}/changed.json"
mv "${FIXTURES}/changed.json" "${FIXTURES}/reviews.json"
check "refuses superseded approval after changes requested" refuse "no APPROVED review"

baseline
printf '\n[{"state":"CHANGES_REQUESTED","commit_id":"%s","user":{"login":"aminu","type":"User"},"submitted_at":"2026-09-06T12:00:00Z"}]' "$HEAD_SHA" >> "${FIXTURES}/reviews.json"
check "refuses an approval superseded on a later review page" refuse "no APPROVED review"

baseline; write_reviews '[]'
printf '\n[{"state":"APPROVED","commit_id":"%s","user":{"login":"aminu","type":"User"},"submitted_at":"2026-09-06T12:00:00Z"}]' "$HEAD_SHA" >> "${FIXTURES}/reviews.json"
check "admits a genuine exact-head approval on a later review page" admit "non-author approval"

baseline; touch "${FIXTURES}/reviews-error"
check "refuses partial review output when the API fails" refuse "could not read complete"

baseline; write_reviews 'not-json'
check "refuses non-JSON review output" refuse "could not read complete"

baseline; write_reviews '{"unexpected":"shape"}'
check "refuses malformed review-page structure" refuse "could not parse complete"

# Exercise the installed CLI against a loopback fixture, preserving its option
# parser and Link pagination. The remaining gate API calls stay hermetic.
cat > "${WORKDIR}/review-server.py" <<'PY'
import http.server
import json
import os
from pathlib import Path
from urllib.parse import parse_qs, urlparse

class Handler(http.server.BaseHTTPRequestHandler):
    def do_GET(self):
        remaining = Path(os.environ["FIXTURES"], "reviews.json").read_text().strip()
        documents = []
        while remaining:
            document, end = json.JSONDecoder().raw_decode(remaining)
            documents.append(document)
            remaining = remaining[end:].strip()
        page = int(parse_qs(urlparse(self.path).query).get("page", ["1"])[0])
        body = json.dumps(documents[page - 1]).encode()
        self.send_response(200)
        self.send_header("Content-Type", "application/json")
        if page < len(documents):
            self.send_header("Link", f'<http://127.0.0.1:{self.server.server_port}/reviews?page={page + 1}>; rel="next"')
        self.end_headers()
        self.wfile.write(body)

    def log_message(self, *_args):
        pass

server = http.server.HTTPServer(("127.0.0.1", 0), Handler)
Path(os.environ["REVIEW_PORT_PATH"]).write_text(str(server.server_port))
server.serve_forever()
PY
baseline
export REVIEW_PORT_PATH="${WORKDIR}/review-port"
python3 "${WORKDIR}/review-server.py" &
review_server_pid=$!
trap 'kill "$review_server_pid" 2>/dev/null || true; rm -rf "${WORKDIR}"' EXIT
for attempt in {1..30}; do
  [ -s "$REVIEW_PORT_PATH" ] && break
  sleep 0.1
done
export REAL_GH_REVIEW_URL="http://127.0.0.1:$(cat "$REVIEW_PORT_PATH")/reviews?page=1"
write_reviews '[]'
printf '\n[{"state":"APPROVED","commit_id":"%s","user":{"login":"aminu","type":"User"},"submitted_at":"2026-09-06T12:00:00Z"}]' "$HEAD_SHA" >> "${FIXTURES}/reviews.json"
check "real gh CLI admits approval from a later paginated response" admit "non-author approval"

printf '\n[{"state":"CHANGES_REQUESTED","commit_id":"%s","user":{"login":"aminu","type":"User"},"submitted_at":"2026-09-06T13:00:00Z"}]' "$HEAD_SHA" >> "${FIXTURES}/reviews.json"
check "real gh CLI refuses superseded approval across pages" refuse "no APPROVED review"
unset REAL_GH_REVIEW_URL
kill "$review_server_pid"
wait "$review_server_pid" 2>/dev/null || true
trap 'rm -rf "${WORKDIR}"' EXIT

baseline
jq '.[0].merge_commit_sha = "3333333333333333333333333333333333333333"' "${FIXTURES}/pulls.json" > "${FIXTURES}/changed.json"
mv "${FIXTURES}/changed.json" "${FIXTURES}/pulls.json"
check "refuses direct descendant of a previously reviewed merge" refuse "direct push cannot deploy"

# Production run provenance and complete evidence must fail closed.
for field in workflow_id path repository.full_name head_repository.full_name run_attempt; do
  baseline
  jq "del(.workflow_runs[0].$field)" "$FIXTURES/runs.json" > "$FIXTURES/changed.json"
  mv "$FIXTURES/changed.json" "$FIXTURES/runs.json"
  check "refuses missing run $field" refuse "inappropriate workflow provenance or attempt"
done

for change in '.workflow_id = 456' '.path = ".github/workflows/other.yml"' '.head_repository.full_name = "fork/rozine"'; do
  baseline
  jq ".workflow_runs[0] |= ($change)" "$FIXTURES/runs.json" > "$FIXTURES/changed.json"
  mv "$FIXTURES/changed.json" "$FIXTURES/runs.json"
  check "refuses inappropriate provenance: $change" refuse "inappropriate workflow provenance or attempt"
done

for state in cancelled skipped neutral timed_out; do
  baseline; write_runs completed "\"$state\""
  check "refuses $state workflow evidence" refuse "concluded '$state'"
done
for state in queued waiting pending; do
  baseline; write_runs "$state" null
  check "refuses $state workflow evidence" refuse "timed out"
done
for state in cancelled skipped failure; do
  baseline; write_jobs "$state" success
  check "refuses $state required PHP job" refuse "'$state'"
done

for change in 'map(select(.name != "Record validation tree"))' '. + [.[0]]' '.[0].status = "in_progress"' '.[0].head_sha = "wrong"' '.[0].run_attempt = 1' '.[0].run_id = 98'; do
  baseline
  jq ".jobs |= ($change)" "$FIXTURES/jobs.json" > "$FIXTURES/changed.json"
  mv "$FIXTURES/changed.json" "$FIXTURES/jobs.json"
  check "refuses incomplete or mismatched job evidence: $change" refuse ""
done

baseline; touch "$FIXTURES/runs-error"
check "refuses partial run inventory after API error" refuse "could not read complete workflow"
baseline; touch "$FIXTURES/jobs-error"
check "refuses partial job inventory after API error" refuse "could not read complete job"

baseline
printf '\n{"workflow_runs":[{"id":100,"status":"completed","conclusion":"failure","created_at":"2026-09-06T12:00:00Z","event":"push","head_branch":"main","head_sha":"%s"}]}' "$GOOD_SHA" >> "$FIXTURES/runs.json"
check "refuses newer failed run from a later inventory page" refuse "concluded 'failure'"

baseline
jq '.workflow_runs[0].run_attempt = 3 | .workflow_runs[0].status = "queued" | .workflow_runs[0].conclusion = null' "$FIXTURES/runs.json" > "$FIXTURES/runs-after.json"
check "refuses a rerun started during approval validation" refuse "workflow evidence changed"

baseline
export REQUIRED_JOBS="irrelevant"
check "production cannot narrow the required check set" admit "Record validation tree"
unset REQUIRED_JOBS
baseline
export EVIDENCE_WORKFLOW="other.yml"
check "production cannot substitute another workflow" refuse "requires tests.yml"
unset EVIDENCE_WORKFLOW

baseline
jq '.workflow_runs[0].event = "workflow_dispatch" | .workflow_runs[0].head_branch = "uat"' "${FIXTURES}/runs.json" > "${FIXTURES}/changed.json"
mv "${FIXTURES}/changed.json" "${FIXTURES}/runs.json"
export TEST_TARGET_BRANCH=uat
jq '.workflow_runs[0].head_branch = "uat"' "${FIXTURES}/runs.json" > "${FIXTURES}/changed.json"
mv "${FIXTURES}/changed.json" "${FIXTURES}/runs.json"
jq '.[0].base.ref = "uat"' "${FIXTURES}/pulls.json" > "${FIXTURES}/changed.json"
mv "${FIXTURES}/changed.json" "${FIXTURES}/pulls.json"
check "staging refuses missing original scope-bound evidence" refuse "staging scoped evidence/provenance validation failed"
unset TEST_TARGET_BRANCH

echo
echo "${pass} passed, ${fail} failed"
[ "${fail}" -eq 0 ]
