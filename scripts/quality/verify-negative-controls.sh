#!/usr/bin/env bash
#
# Negative controls for the PHP quality gates.
#
# A green gate only means something if it can go red. Each control plants a
# violation the gate is supposed to catch, runs the gate, requires it to fail,
# then removes the violation. The suite ends by confirming the gate is green
# again, so a control that "passed" because the gate was already broken is not
# mistaken for evidence.
#
# The client gates have their own negative controls as ordinary Vitest cases in
# tests/web/quality/client-coverage-policy.test.ts; nothing here duplicates
# them.
#
# Usage: bash scripts/quality/verify-negative-controls.sh [control ...]
#        with no arguments every control runs.

set -uo pipefail

cd "$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"

PLANTED=()
pass=0
fail=0
skipped=0

cleanup() {
  for file in "${PLANTED[@]:-}"; do
    [ -n "${file}" ] && rm -f "${file}"
  done
}
trap cleanup EXIT

plant() {
  if [ -e "$1" ] || [ -L "$1" ]; then
    echo "refusing to overwrite an existing negative-control fixture: $1" >&2
    exit 1
  fi
  mkdir -p "$(dirname "$1")"
  cat > "$1"
  PLANTED+=("$1")
}

# Runs a gate and reports whether it failed, without leaking its output unless
# something needs explaining.
gate_fails() {
  local log="$1"
  shift
  "$@" > "${log}" 2>&1
  [ $? -ne 0 ]
}

control() {
  ran=$((ran + 1))
  echo "--> $1: $2"
}

report() {
  local name="$1" outcome="$2" detail="${3:-}"

  case "${outcome}" in
    pass) echo "    caught. ${detail}"; pass=$((pass + 1)) ;;
    fail) echo "    NOT CAUGHT. ${detail}"; fail=$((fail + 1)) ;;
    skip) echo "    skipped: ${detail}"; skipped=$((skipped + 1)) ;;
  esac
  echo
}

ALL_CONTROLS=(strict-types domain-purity transport-boundary identity-boundary operation-boundary business-boundary evidence-boundary auditor-boundary audit-signing-boundary adapter-leak php-coverage phpstan-tests)

selected() {
  local wanted="$1" name
  for name in "${REQUESTED[@]}"; do
    [ "${name}" = "${wanted}" ] && return 0
  done
  return 1
}

LOG_DIR="$(mktemp -d)"
ran=0

# An empty array does not expand to zero arguments under `set -u` idioms, so
# resolve the selection here rather than at each call site. Getting this wrong
# is silent: every control deselects itself and the run reports success having
# proved nothing.
if [ "$#" -eq 0 ]; then
  REQUESTED=("${ALL_CONTROLS[@]}")
else
  REQUESTED=("$@")
  for requested in "${REQUESTED[@]}"; do
    if ! printf '%s\n' "${ALL_CONTROLS[@]}" | grep -qx -- "${requested}"; then
      echo "unknown control '${requested}'. Known: ${ALL_CONTROLS[*]}" >&2
      exit 64
    fi
  done
fi

# A control only means something if the gate was green to begin with. A gate
# that is broken, unavailable, or already red would "catch" every violation and
# catch nothing at all.
gate_is_green() {
  local log="$1"
  shift
  "$@" > "${log}" 2>&1
}

echo "--> precondition: the gates are green before anything is planted"
ARCHITECTURE_GREEN=false
STATIC_GREEN=false

if gate_is_green "${LOG_DIR}/pre-arch.log" vendor/bin/pest --ci --no-tia --testsuite=Architecture --compact; then
  ARCHITECTURE_GREEN=true
  echo "    architecture suite green"
else
  echo "    architecture suite is already red; its controls cannot be attributed and will be skipped"
fi

if gate_is_green "${LOG_DIR}/pre-static.log" vendor/bin/phpstan analyse --no-progress --error-format=raw; then
  STATIC_GREEN=true
  echo "    static analysis green"
else
  echo "    static analysis is not green on a clean tree; its control cannot be attributed and will be skipped"
fi
echo

# ---------------------------------------------------------------------------
# Strict types: include standalone PHP files, not only autoloaded classes.
# ---------------------------------------------------------------------------
if selected strict-types; then
  control strict-types "missing or disabled declarations, including hidden files, in every approved directory must fail"

  if [ "${ARCHITECTURE_GREEN}" != true ]; then
    report strict-types fail "the architecture suite must be green before testing its strict-types rule"
  else
    for directory in app bootstrap config database routes tests; do
      for mode in missing disabled hidden; do
        fixture="${directory}/NegativeControlStrictTypes.php"
        if [ "${mode}" = hidden ]; then
          fixture="${directory}/.NegativeControlStrictTypes.php"
        fi
        if [ "${mode}" != disabled ]; then
          plant "${fixture}" <<'VIOLATION'
<?php

// declare(strict_types=1); is documentation, not an executable declaration.
return [];
VIOLATION
        else
          plant "${fixture}" <<'VIOLATION'
<?php

declare(strict_types=0);

return [];
VIOLATION
        fi

        log="${LOG_DIR}/strict-types-${directory}-${mode}.log"
        if gate_fails "${log}" vendor/bin/pest --ci --no-tia tests/Architecture/StrictTypesTest.php --filter='requires strict types' --compact \
          && grep -Fq "${fixture} must start with declare(strict_types=1)" "${log}"; then
          report strict-types pass "${directory}: ${mode} declaration rejected by the file-scope rule"
        else
          report strict-types fail "${directory}: ${mode} declaration did not produce its expected diagnostic"
          cat "${log}"
        fi
        rm -f "${fixture}"
      done
    done
  fi
fi

# ---------------------------------------------------------------------------
# Architecture: a domain class that reaches for the framework.
# ---------------------------------------------------------------------------
if selected domain-purity; then
  control domain-purity "a Domain class importing Illuminate must fail the architecture suite"

  plant app/Domain/NegativeControl/ReachesForTheFramework.php <<'VIOLATION'
<?php

declare(strict_types=1);

namespace App\Domain\NegativeControl;

use Illuminate\Support\Facades\DB;

final class ReachesForTheFramework
{
    public function total(): int
    {
        return (int) DB::table('pulse_signups')->count();
    }
}
VIOLATION

  if [ "${ARCHITECTURE_GREEN}" != true ]; then
    report domain-purity skip "the architecture suite was not green beforehand"
  elif gate_fails "${LOG_DIR}/domain.log" vendor/bin/pest --ci --no-tia --testsuite=Architecture --compact; then
    report domain-purity pass "the architecture suite rejected it"
  else
    report domain-purity fail "the architecture suite accepted a Domain class using Illuminate"
  fi
  rm -f app/Domain/NegativeControl/ReachesForTheFramework.php
  rmdir app/Domain/NegativeControl 2>/dev/null || true
fi

# ---------------------------------------------------------------------------
# Architecture: only the identity adapter may access protected Party records.
# ---------------------------------------------------------------------------
if selected identity-boundary; then
  control identity-boundary "application actions writing protected identity records directly must fail the identity rule"
  for identity_model in Party VerifiedOrganizationIdentity ConsentRelease; do
  echo "    checking ${identity_model}"

  plant app/Application/Identity/NegativeControlIdentityWrite.php <<VIOLATION
<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\\Models\\${identity_model};

final class NegativeControlIdentityWrite
{
    public function handle(): ${identity_model}
    {
        return ${identity_model}::query()->create([]);
    }
}
VIOLATION

  if [ "${ARCHITECTURE_GREEN}" != true ]; then
    report identity-boundary fail "the architecture suite must be green beforehand"
  elif gate_fails "${LOG_DIR}/identity.log" vendor/bin/pest --ci --no-tia tests/Architecture/ArchitectureTest.php --filter='identity records are only accessed' --compact; then
    report identity-boundary pass "the identity persistence rule rejected it"
  else
    report identity-boundary fail "the identity persistence rule accepted a direct ${identity_model} write"
    cat "${LOG_DIR}/identity.log"
  fi
  rm -f app/Application/Identity/NegativeControlIdentityWrite.php
  done
fi

# ---------------------------------------------------------------------------
# Architecture: a controller that queries the database itself.
# ---------------------------------------------------------------------------
if selected operation-boundary; then
  control operation-boundary "bypassing the journal to write an operation outcome must fail the protected rule"
  plant app/Application/Operations/NegativeControlOperationWrite.php <<'VIOLATION'
<?php

declare(strict_types=1);

namespace App\Application\Operations;

use App\Models\CommandOperation;

final class NegativeControlOperationWrite
{
    public function handle(): CommandOperation
    {
        return CommandOperation::query()->create([]);
    }
}
VIOLATION
  if [ "${ARCHITECTURE_GREEN}" != true ]; then
    report operation-boundary fail "the architecture suite must be green beforehand"
  elif gate_fails "${LOG_DIR}/operation.log" vendor/bin/pest --ci --no-tia tests/Architecture/ArchitectureTest.php --filter='command outcomes are only accessed' --compact; then
    report operation-boundary pass "the command journal boundary rejected it"
  else
    report operation-boundary fail "the command journal boundary accepted an external write"
    cat "${LOG_DIR}/operation.log"
  fi
  rm -f app/Application/Operations/NegativeControlOperationWrite.php
fi

if selected business-boundary; then
  control business-boundary "bypassing the business adapter to write authority or application records must fail the protected rule"
  for business_model in BusinessMandate BusinessApplication BusinessApplicationVersion BusinessCreditSnapshot BusinessApplicationQuote BusinessApplicationSignature BusinessApplicationSubmission BusinessExposureReservation; do
  echo "    checking ${business_model}"
  plant app/Application/Business/NegativeControlBusinessWrite.php <<VIOLATION
<?php

declare(strict_types=1);

namespace App\Application\Business;

use App\\Models\\${business_model};

final class NegativeControlBusinessWrite
{
    public function handle(): ${business_model}
    {
        return ${business_model}::query()->create([]);
    }
}
VIOLATION
  if [ "${ARCHITECTURE_GREEN}" != true ]; then
    report business-boundary fail "the architecture suite must be green beforehand"
  elif gate_fails "${LOG_DIR}/business.log" vendor/bin/pest --ci --no-tia tests/Architecture/ArchitectureTest.php --filter='business authority records are only accessed' --compact; then
    report business-boundary pass "the business authority boundary rejected it"
  else
    report business-boundary fail "the business authority boundary accepted an external write"
    cat "${LOG_DIR}/business.log"
  fi
  rm -f app/Application/Business/NegativeControlBusinessWrite.php
  done
fi

if selected evidence-boundary; then
  control evidence-boundary "bypassing the evidence adapter to write statement sources or reconciliations must fail"
  for evidence_model in StatementEvidence StatementOriginal StatementExtraction StatementTranscription StatementVerification; do
  echo "    checking ${evidence_model}"
  plant app/Application/Evidence/NegativeControlEvidenceWrite.php <<VIOLATION
<?php

declare(strict_types=1);

namespace App\\Application\\Evidence;

use App\\Models\\${evidence_model};

final class NegativeControlEvidenceWrite
{
    public function handle(): ${evidence_model}
    {
        return ${evidence_model}::query()->create([]);
    }
}
VIOLATION
  if [ "${ARCHITECTURE_GREEN}" != true ]; then
    report evidence-boundary fail "the architecture suite must be green beforehand"
  elif gate_fails "${LOG_DIR}/evidence.log" vendor/bin/pest --ci --no-tia tests/Architecture/ArchitectureTest.php --filter='statement evidence records are only accessed' --compact; then
    report evidence-boundary pass "the immutable evidence boundary rejected it"
  else
    report evidence-boundary fail "the immutable evidence boundary accepted an external write"
    cat "${LOG_DIR}/evidence.log"
  fi
  rm -f app/Application/Evidence/NegativeControlEvidenceWrite.php
  done
fi

if selected auditor-boundary; then
  control auditor-boundary "bypassing the auditor adapter to write accreditation, assignment or report history must fail"
  for auditor_model in AuditorProfile AuditorProfileVersion AuditorCertificate AuditLocation AuditLocationVersion AuditorIndependenceReview AuditorIndependenceVersion AuditAssignment AuditAssignmentVersion AuditConflictDeclaration AuditReport AuditReportVersion AuditLedgerOriginal AuditLedgerExtraction AuditSourceSnapshot AuditEngagementRelease AuditEngagementAcceptance AuditPublicationEvent AuditDisputeProof AuditReportPublication AuditReportSignature AuditReportSeal AuditSigningKey AuditSigningKeyRevocation AuditStepUpProof; do
  echo "    checking ${auditor_model}"
  plant app/Application/Auditor/NegativeControlAuditorWrite.php <<VIOLATION
<?php

declare(strict_types=1);

namespace App\\Application\\Auditor;

use App\\Models\\${auditor_model};

final class NegativeControlAuditorWrite
{
    public function handle(): ${auditor_model}
    {
        return ${auditor_model}::query()->create([]);
    }
}
VIOLATION
  if [ "${ARCHITECTURE_GREEN}" != true ]; then
    report auditor-boundary fail "the architecture suite must be green beforehand"
  elif gate_fails "${LOG_DIR}/auditor.log" vendor/bin/pest --ci --no-tia tests/Architecture/ArchitectureTest.php --filter='auditor accreditation records are only accessed' --compact; then
    report auditor-boundary pass "the auditor accreditation boundary rejected it"
  else
    report auditor-boundary fail "the auditor accreditation boundary accepted an external write"
    cat "${LOG_DIR}/auditor.log"
  fi
  rm -f app/Application/Auditor/NegativeControlAuditorWrite.php
  done
fi

if selected audit-signing-boundary; then
  control audit-signing-boundary "direct calls around authorized signing entry points must fail"
  for signing_symbol in 'App\Application\Auditor\Contracts\AuditReportCryptography' 'App\Application\Auditor\Contracts\AuditStepUp' 'App\Application\Identity\Contracts\Authenticator' 'Jose\Component\Core\JWK'; do
    plant app/Application/Auditor/NegativeControlSigningBypass.php <<VIOLATION
<?php

declare(strict_types=1);

namespace App\\Application\\Auditor;

final class NegativeControlSigningBypass
{
    public function __construct(private \\${signing_symbol} \$signer) {}
}
VIOLATION
    if [ "${ARCHITECTURE_GREEN}" != true ]; then
      report audit-signing-boundary fail "the architecture suite must be green beforehand"
    elif gate_fails "${LOG_DIR}/signing.log" vendor/bin/pest --ci --no-tia tests/Architecture/ArchitectureTest.php --filter='audit signing' --compact \
      && grep -Fq 'NegativeControlSigningBypass' "${LOG_DIR}/signing.log"; then
      report audit-signing-boundary pass "${signing_symbol} cannot be called outside its authorized boundary"
    else
      report audit-signing-boundary fail "a signing entry point accepted an unauthorized caller"
      cat "${LOG_DIR}/signing.log"
    fi
    rm -f app/Application/Auditor/NegativeControlSigningBypass.php
  done
fi

if selected transport-boundary; then
  control transport-boundary "a controller querying persistence directly must fail the architecture suite"

  plant app/Http/Controllers/NegativeControlController.php <<'VIOLATION'
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PulseSignup;

final class NegativeControlController extends Controller
{
    public function __invoke(): int
    {
        return PulseSignup::query()->count();
    }
}
VIOLATION

  if [ "${ARCHITECTURE_GREEN}" != true ]; then
    report transport-boundary skip "the architecture suite was not green beforehand"
  elif gate_fails "${LOG_DIR}/transport.log" vendor/bin/pest --ci --no-tia --testsuite=Architecture --compact; then
    report transport-boundary pass "the architecture suite rejected it"
  else
    report transport-boundary fail "the architecture suite accepted a controller using an Eloquent model"
  fi
  rm -f app/Http/Controllers/NegativeControlController.php
fi

# ---------------------------------------------------------------------------
# Architecture: an adapter named outside the provider that binds it.
# ---------------------------------------------------------------------------
if selected adapter-leak; then
  control adapter-leak "an application class naming a concrete adapter must fail the architecture suite"

  plant app/Application/NegativeControl/NamesAnAdapter.php <<'VIOLATION'
<?php

declare(strict_types=1);

namespace App\Application\NegativeControl;

use App\Infrastructure\Pulse\EloquentPulseSignupRepository;

final class NamesAnAdapter
{
    public function __construct(private EloquentPulseSignupRepository $signups) {}
}
VIOLATION

  if [ "${ARCHITECTURE_GREEN}" != true ]; then
    report adapter-leak skip "the architecture suite was not green beforehand"
  elif gate_fails "${LOG_DIR}/adapter.log" vendor/bin/pest --ci --no-tia --testsuite=Architecture --compact; then
    report adapter-leak pass "the architecture suite rejected it"
  else
    report adapter-leak fail "the architecture suite accepted an adapter named outside its provider"
  fi
  rm -f app/Application/NegativeControl/NamesAnAdapter.php
  rmdir app/Application/NegativeControl 2>/dev/null || true
fi

# ---------------------------------------------------------------------------
# Coverage: a first-party line no test reaches.
# ---------------------------------------------------------------------------
if selected php-coverage; then
  control php-coverage "an uncovered first-party line must fail the 100% coverage gate"

  if ! php -r 'exit(extension_loaded("xdebug") || extension_loaded("pcov") ? 0 : 1);'; then
    report php-coverage skip "no coverage driver on this runtime; the hosted PHP lane is the authority"
  else
    plant app/Support/NegativeControlUncovered.php <<'VIOLATION'
<?php

declare(strict_types=1);

namespace App\Support;

final class NegativeControlUncovered
{
    public function neverCalled(): string
    {
        return 'no test reaches this line';
    }
}
VIOLATION

    if gate_fails "${LOG_DIR}/coverage.log" vendor/bin/pest --ci --no-tia --coverage --min=100 --compact; then
      report php-coverage pass "the coverage gate rejected it"
    else
      report php-coverage fail "the coverage gate reported 100% with an unreached file present"
    fi
    rm -f app/Support/NegativeControlUncovered.php
    rmdir app/Support 2>/dev/null || true
  fi
fi

# ---------------------------------------------------------------------------
# Static analysis: proves tests/ is analysed, and analysed by the Pest plugin.
# ---------------------------------------------------------------------------
if selected phpstan-tests; then
  control phpstan-tests "an invalid construct inside tests/ must fail static analysis"

  plant tests/Feature/NegativeControlAnalysisTest.php <<'VIOLATION'
<?php

declare(strict_types=1);

// A plain level-7 type error, proving tests/ is inside the analysed paths.
it('adds a string to an integer', function (): void {
    $sum = 1 + 'not a number';

    expect($sum)->toBeInt();
});

// A covers() naming a class that does not exist, proving the Pest plugin is
// registered rather than Larastan alone.
it('covers a class that was never written', function (): void {
    expect(true)->toBeTrue();
})->covers(App\NegativeControl\NeverWritten::class);
VIOLATION

  if [ "${STATIC_GREEN}" != true ]; then
    report phpstan-tests skip "static analysis was not green beforehand, so a failure now proves nothing"
  elif gate_fails "${LOG_DIR}/phpstan.log" vendor/bin/phpstan analyse --no-progress --error-format=raw; then
    report phpstan-tests pass "static analysis rejected it"
  else
    report phpstan-tests fail "static analysis accepted an invalid construct inside tests/"
  fi
  rm -f tests/Feature/NegativeControlAnalysisTest.php
fi

# ---------------------------------------------------------------------------
# Every violation is gone; the gates must be green again.
# ---------------------------------------------------------------------------
echo "--> teardown: every violation is removed and the suite is green again"
if vendor/bin/pest --ci --no-tia --testsuite=Architecture --compact > "${LOG_DIR}/teardown.log" 2>&1; then
  report teardown pass "architecture suite green"
else
  report teardown fail "a planted violation was left behind"
  cat "${LOG_DIR}/teardown.log"
fi

echo "${pass} caught, ${fail} not caught, ${skipped} skipped"

# A run that executed no control is not a pass. Without this the harness
# reports success for having done nothing, which is worse than no harness.
if [ "${ran}" -ne "${#REQUESTED[@]}" ]; then
  echo "expected ${#REQUESTED[@]} controls to run, ${ran} did; the selection is broken" >&2
  exit 1
fi

[ "${fail}" -eq 0 ]
