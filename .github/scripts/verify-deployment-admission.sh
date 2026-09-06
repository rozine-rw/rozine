#!/usr/bin/env bash
#
# D-68 deployment admission gate.
#
# Refuses to admit a deployment unless every one of these holds for the exact
# candidate SHA:
#
#   1. The SHA is a full 40-hex immutable Git object.
#   2. The SHA is the current tip of the target branch (no stale redeploys).
#   3. The authoritative `tests` workflow ran on that exact SHA and succeeded.
#   4. Every required quality-gate job inside that run succeeded; a skipped or
#      cancelled job is not a pass.
#   5. The SHA reached the target branch through a merged pull request.
#   6. That pull request carries an APPROVED review from someone other than its
#      author, and that review names the pull request's final head SHA.
#
# Missing, failed, in-progress, stale, or mismatched evidence all exit non-zero.
# There is no branch that admits a deployment on absent evidence.
#
# Required environment:
#   GH_TOKEN         token with actions:read and pull-requests:read
#   GITHUB_REPOSITORY  owner/repo
#   CANDIDATE_SHA    the SHA being deployed
#   TARGET_BRANCH    uat | main
#   EVIDENCE_WORKFLOW  workflow file name holding the authoritative gates
#   REQUIRED_JOBS    newline-separated job names that must have succeeded
#   WAIT_TIMEOUT_SECONDS  how long to wait for an in-progress evidence run

set -euo pipefail

: "${GH_TOKEN:?GH_TOKEN is required}"
: "${GITHUB_REPOSITORY:?GITHUB_REPOSITORY is required}"
: "${CANDIDATE_SHA:?CANDIDATE_SHA is required}"
: "${TARGET_BRANCH:?TARGET_BRANCH is required}"

EVIDENCE_WORKFLOW="${EVIDENCE_WORKFLOW:-tests.yml}"
WAIT_TIMEOUT_SECONDS="${WAIT_TIMEOUT_SECONDS:-2700}"
POLL_INTERVAL_SECONDS="${POLL_INTERVAL_SECONDS:-20}"
REQUIRED_JOBS="${REQUIRED_JOBS:-PHP 8.4 quality gate
PHP 8.5 quality gate
TypeScript/React quality gate}"

refuse() {
  echo "::error::Deployment admission REFUSED — $1"
  echo
  echo "D-68 requires complete hosted evidence for the exact candidate SHA plus"
  echo "an approval of that SHA by the developer who did not author it."
  echo "Fix the underlying condition and re-run; do not bypass this gate."
  exit 1
}

echo "Candidate SHA : ${CANDIDATE_SHA}"
echo "Target branch : ${TARGET_BRANCH}"
echo "Repository    : ${GITHUB_REPOSITORY}"
echo

# ---------------------------------------------------------------------------
# 1. The candidate must be a full immutable SHA.
# ---------------------------------------------------------------------------
if ! [[ "${CANDIDATE_SHA}" =~ ^[0-9a-f]{40}$ ]]; then
  refuse "candidate '${CANDIDATE_SHA}' is not a full 40-character Git SHA."
fi

# ---------------------------------------------------------------------------
# 2. The candidate must still be the tip of the target branch. This is what
#    stops a manual dispatch from shipping a commit that newer work replaced.
# ---------------------------------------------------------------------------
branch_tip="$(gh api "repos/${GITHUB_REPOSITORY}/git/ref/heads/${TARGET_BRANCH}" --jq '.object.sha')"
if [ "${branch_tip}" != "${CANDIDATE_SHA}" ]; then
  refuse "stale candidate. ${TARGET_BRANCH} is at ${branch_tip}, not ${CANDIDATE_SHA}."
fi
echo "OK  candidate is the current tip of ${TARGET_BRANCH}."

# ---------------------------------------------------------------------------
# 3. The authoritative gate workflow must have run on this exact SHA. An
#    in-progress run is waited out; it is never treated as a pass.
# ---------------------------------------------------------------------------
deadline=$(( $(date +%s) + WAIT_TIMEOUT_SECONDS ))
run_id=""
run_conclusion=""

while :; do
  run_json="$(gh api \
    "repos/${GITHUB_REPOSITORY}/actions/workflows/${EVIDENCE_WORKFLOW}/runs?head_sha=${CANDIDATE_SHA}&per_page=100" \
    --jq '[.workflow_runs[] | {id, status, conclusion, created_at}] | sort_by(.created_at) | last // empty')"

  if [ -z "${run_json}" ]; then
    status_line="no run yet"
  else
    run_id="$(echo "${run_json}" | jq -r '.id')"
    run_status="$(echo "${run_json}" | jq -r '.status')"
    run_conclusion="$(echo "${run_json}" | jq -r '.conclusion // "null"')"
    status_line="run ${run_id} status=${run_status} conclusion=${run_conclusion}"

    if [ "${run_status}" = "completed" ]; then
      break
    fi
  fi

  if [ "$(date +%s)" -ge "${deadline}" ]; then
    refuse "timed out after ${WAIT_TIMEOUT_SECONDS}s waiting for ${EVIDENCE_WORKFLOW} evidence on ${CANDIDATE_SHA} (${status_line})."
  fi

  echo "... waiting for ${EVIDENCE_WORKFLOW} evidence on ${CANDIDATE_SHA} (${status_line})"
  sleep "${POLL_INTERVAL_SECONDS}"
done

if [ "${run_conclusion}" != "success" ]; then
  refuse "${EVIDENCE_WORKFLOW} run ${run_id} concluded '${run_conclusion}' for ${CANDIDATE_SHA}."
fi
echo "OK  ${EVIDENCE_WORKFLOW} run ${run_id} succeeded on the exact candidate SHA."

# ---------------------------------------------------------------------------
# 4. Every required job inside that run must have succeeded in its own right.
#    A run can conclude 'success' while a job was skipped; that is not evidence.
# ---------------------------------------------------------------------------
jobs_json="$(gh api --paginate "repos/${GITHUB_REPOSITORY}/actions/runs/${run_id}/jobs?per_page=100" \
  --jq '.jobs[] | {name, conclusion}' | jq -s '.')"

while IFS= read -r required_job; do
  [ -n "${required_job}" ] || continue
  conclusion="$(echo "${jobs_json}" | jq -r --arg n "${required_job}" \
    'map(select(.name == $n)) | .[0].conclusion // "missing"')"
  if [ "${conclusion}" != "success" ]; then
    refuse "required job '${required_job}' is '${conclusion}' in run ${run_id}."
  fi
  echo "OK  required job '${required_job}' succeeded."
done <<< "${REQUIRED_JOBS}"

# ---------------------------------------------------------------------------
# 5 & 6. The SHA must have arrived through a merged pull request that the
#        non-author developer approved at its final head SHA.
# ---------------------------------------------------------------------------
pulls_json="$(gh api "repos/${GITHUB_REPOSITORY}/commits/${CANDIDATE_SHA}/pulls" \
  --header 'Accept: application/vnd.github+json' \
  --jq "[.[] | select(.base.ref == \"${TARGET_BRANCH}\" and .merged_at != null)]")"

pull_count="$(echo "${pulls_json}" | jq 'length')"
if [ "${pull_count}" -eq 0 ]; then
  refuse "no merged pull request into ${TARGET_BRANCH} contains ${CANDIDATE_SHA}. A direct push cannot deploy; revert it and re-land through a reviewed pull request."
fi

pull_number="$(echo "${pulls_json}" | jq -r 'sort_by(.merged_at) | last | .number')"
pull_author="$(echo "${pulls_json}" | jq -r 'sort_by(.merged_at) | last | .user.login')"
pull_head_sha="$(echo "${pulls_json}" | jq -r 'sort_by(.merged_at) | last | .head.sha')"
echo "OK  candidate arrived through merged pull request #${pull_number} (author: ${pull_author}, head: ${pull_head_sha})."

approver="$(gh api --paginate "repos/${GITHUB_REPOSITORY}/pulls/${pull_number}/reviews?per_page=100" \
  --jq "[.[] | select(.state == \"APPROVED\" and .commit_id == \"${pull_head_sha}\" and .user.login != \"${pull_author}\")] | sort_by(.submitted_at) | last | .user.login // empty")"

if [ -z "${approver}" ]; then
  refuse "pull request #${pull_number} has no APPROVED review of its final head SHA ${pull_head_sha} from a developer other than its author ${pull_author}."
fi
echo "OK  non-author approval of the final candidate SHA recorded by '${approver}'."

# ---------------------------------------------------------------------------
# Attestation. This is the record the phase gate archives.
# ---------------------------------------------------------------------------
mkdir -p "$(dirname "${ATTESTATION_PATH:-deployment-admission.json}")"
jq -n \
  --arg repository "${GITHUB_REPOSITORY}" \
  --arg candidate_sha "${CANDIDATE_SHA}" \
  --arg target_branch "${TARGET_BRANCH}" \
  --arg evidence_workflow "${EVIDENCE_WORKFLOW}" \
  --arg evidence_run_id "${run_id}" \
  --arg pull_request "${pull_number}" \
  --arg pull_author "${pull_author}" \
  --arg pull_head_sha "${pull_head_sha}" \
  --arg non_author_approver "${approver}" \
  --arg admitted_at "$(date -u +%Y-%m-%dT%H:%M:%SZ)" \
  --argjson required_jobs "$(printf '%s' "${REQUIRED_JOBS}" | jq -R -s 'split("\n") | map(select(length > 0))')" \
  '{
    decision: "ADMITTED",
    governing_decision: "D-68",
    repository: $repository,
    candidate_sha: $candidate_sha,
    target_branch: $target_branch,
    evidence: {
      workflow: $evidence_workflow,
      run_id: $evidence_run_id,
      required_jobs: $required_jobs
    },
    review: {
      pull_request: $pull_request,
      author: $pull_author,
      head_sha: $pull_head_sha,
      non_author_approver: $non_author_approver
    },
    admitted_at: $admitted_at
  }' > "${ATTESTATION_PATH:-deployment-admission.json}"

echo
echo "Deployment admission GRANTED for ${CANDIDATE_SHA} -> ${TARGET_BRANCH}."
