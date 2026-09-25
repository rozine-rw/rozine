<?php

declare(strict_types=1);

use App\Application\Auditor\ListAuditJobs;
use App\Application\Auditor\SetAuditorAvailability;
use App\Application\Business\CreateBusinessApplication;
use App\Application\Business\GetAuditApplication;
use App\Application\Business\SaveBusinessApplication;
use App\Application\Evidence\GetAuditStatements;
use App\Domain\Identity\IdentityViolation;
use App\Domain\Operations\CommandRejection;
use App\Models\AuditAssignment;
use App\Models\BusinessApplication;
use App\Models\RoleMembership;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\Support\AuditAssignmentFixture as Fixture;
use Tests\Support\AuditorFixture;
use Tests\Support\BusinessApplicationFixture;
use Tests\Support\BusinessAuthorityFixture;

beforeEach(function (): void {
    $this->freezeTime();
});

it('lists current offers with real application inputs while keeping originals and private application prose unavailable', function (): void {
    $fixture = Fixture::make(1);
    $partner = $fixture['partners'][0];
    $owner = $fixture['authority']['users'][0];
    $created = app(CreateBusinessApplication::class)->handle($owner->id, 1, $fixture['business'], 0, (string) Str::uuid());
    $id = $created['data']['application']['id'];
    app(SaveBusinessApplication::class)->handle($owner->id, 1, $fixture['business'], $id, 1,
        BusinessApplicationFixture::fields(), 'raise', (string) Str::uuid());
    $assignment = Fixture::request($fixture);
    $case = app(GetAuditApplication::class)->handle($partner['user']->id, 1, $assignment->id);
    expect($case['application'])->toBe(['id' => $id, 'revision' => 2, 'title' => 'Synthetic equipment purchase', 'target' => '8000000',
        'term_months' => 6, 'use_of_funds' => ['equipment']])
        ->and($case['work']['business'])->toBe(['name' => 'Synthetic business', 'industry' => 'Retail', 'district' => 'Gasabo'])
        ->and($case['work']['assignment']['allowed_actions'])->toContain('assignment.accept', 'assignment.decline', 'conflict.declare')
        ->and($case['work']['assignment']['accept_by'])->toBe($assignment->state['accept_by'])
        ->and($case['work']['assignment']['complete_by'])->toBe($assignment->state['complete_by'])
        ->and($case['work']['distance_upper_bound_m'])->toBe(0)
        ->and(app(ListAuditJobs::class)->handle($partner['user']->id, 1))->toBe(['data' => [$case], 'next_cursor' => null])
        ->and(json_encode($case, JSON_THROW_ON_ERROR))->not->toContain('story', 'Test-only business plan', 'licence', 'mandate', 'party_id', 'selection_basis');
    expect(fn () => app(GetAuditStatements::class)->handle($partner['user']->id, 1, $assignment->id))->toThrow(CommandRejection::class, 'ASSIGNMENT_NOT_ACCEPTED');
});

it('preserves unavailable application inputs and current per-record action restrictions', function (): void {
    $fixture = Fixture::make(1);
    $partner = $fixture['partners'][0];
    $assignment = Fixture::request($fixture);
    expect(app(GetAuditApplication::class)->handle($partner['user']->id, 1, $assignment->id)['application'])->toBeNull();
    $owner = $fixture['authority']['users'][0];
    app(CreateBusinessApplication::class)->handle($owner->id, 1, $fixture['business'], 0, (string) Str::uuid());
    app(SetAuditorAvailability::class)->handle($partner['user']->id, 1, 3, false, (string) Str::uuid());
    $case = app(ListAuditJobs::class)->handle($partner['user']->id, 1)['data'][0];
    expect($case['application']['target'])->toBeNull()->and($case['application']['term_months'])->toBeNull()
        ->and($case['work']['assignment']['allowed_actions'])->toBe(['conflict.declare', 'assignment.decline']);
});

it('returns accepted work with its original Flash or Routine clock even when authorized remediation is late', function (string $kind): void {
    $fixture = Fixture::make(1);
    $partner = $fixture['partners'][0];
    $assignment = Fixture::request($fixture, $kind);
    Fixture::respond($partner['user'], $assignment);
    $assignment->refresh();
    $this->travel(8)->days();
    $case = app(ListAuditJobs::class)->handle($partner['user']->id, 1)['data'][0];
    expect($case['work']['assignment']['kind'])->toBe($kind)->and($case['work']['assignment']['status'])->toBe('accepted')
        ->and($case['work']['assignment']['allowed_actions'])->toBe(['conflict.declare'])
        ->and($case['work']['assignment']['complete_by'])->toBe($assignment->state['complete_by'])
        ->and($case['work']['assignment']['visit_by'])->toBe($assignment->state['visit_by']);
})->with(['flash', 'routine']);

it('omits expired conflicted or withdrawn offers without admitting unrelated assignments', function (string $fault): void {
    $fixture = Fixture::make(1);
    $partner = $fixture['partners'][0];
    $assignment = Fixture::request($fixture);
    $unrelated = AuditorFixture::make();
    expect(app(ListAuditJobs::class)->handle($unrelated['user']->id, 1))->toBe(['data' => [], 'next_cursor' => null]);
    expect(fn () => app(GetAuditApplication::class)->handle($unrelated['user']->id, 1, $assignment->id))->toThrow(CommandRejection::class, 'ASSIGNMENT_NOT_FOUND');
    if ($fault === 'expiry') {
        $this->travel(2)->hours();
    } elseif ($fault === 'conflict') {
        Fixture::respond($partner['user'], $assignment, 'conflict', 'Financial interest.', 'financial_interest');
    } elseif ($fault === 'standing') {
        AuditorFixture::review($partner['staff'], $partner['party']->id, 3, 'suspend');
    } elseif ($fault === 'entity') {
        $fixture['authority']['people'][0]->forceFill(['verified_at' => null])->save();
    } else {
        $authority = $fixture['authority'];
        $authority['terms']['status'] = 'revoked';
        BusinessAuthorityFixture::configure($authority, 1);
    }
    expect(app(ListAuditJobs::class)->handle($partner['user']->id, 1))->toBe(['data' => [], 'next_cursor' => null]);
})->with(['expiry', 'conflict', 'standing', 'mandate', 'entity']);

it('does not convert an unexpected case failure into an apparently empty jobs list', function (): void {
    $fixture = Fixture::make(1);
    $partner = $fixture['partners'][0];
    Fixture::request($fixture);
    app(CreateBusinessApplication::class)->handle($fixture['authority']['users'][0]->id, 1, $fixture['business'], 0, (string) Str::uuid());
    $event = 'eloquent.retrieved: '.BusinessApplication::class;
    Event::listen($event, function (): never {
        throw new CommandRejection('SYNTHETIC_CASE_UNAVAILABLE', 503);
    });
    try {
        expect(fn () => app(ListAuditJobs::class)->handle($partner['user']->id, 1))->toThrow(CommandRejection::class, 'SYNTHETIC_CASE_UNAVAILABLE');
    } finally {
        Event::forget($event);
    }
});

it('rechecks assignment ownership after discovery and omits an offer reassigned during the list read', function (): void {
    $fixture = Fixture::make();
    $assignment = Fixture::request($fixture);
    $partner = Fixture::recipient($fixture, $assignment);
    $event = 'eloquent.retrieved: '.AuditAssignment::class;
    $changed = false;
    Event::listen($event, function (AuditAssignment $record) use ($partner, $assignment, &$changed): void {
        if (! $changed && array_keys($record->getAttributes()) === ['id']) {
            $changed = true;
            Fixture::respond($partner['user'], $assignment, 'decline', 'No longer available.');
        }
    });
    try {
        expect(app(ListAuditJobs::class)->handle($partner['user']->id, 1))->toBe(['data' => [], 'next_cursor' => null]);
    } finally {
        Event::forget($event);
    }
    expect($changed)->toBeTrue()->and($assignment->refresh()->party_id)->not->toBe($partner['party']->id);
});

it('paginates only the current Auditors work using opaque assignment cursors', function (): void {
    $fixture = Fixture::make(1);
    $partner = $fixture['partners'][0];
    $ids = [Fixture::request($fixture)->id];
    for ($index = 0; $index < 2; $index++) {
        $next = Fixture::make(0);
        Fixture::independence($next['staff'], $next['business'], $partner['party']->id);
        $ids[] = Fixture::request($next)->id;
    }
    rsort($ids);
    $list = app(ListAuditJobs::class);
    $first = $list->handle($partner['user']->id, 1, limit: 2);
    expect(array_column(array_column(array_column($first['data'], 'work'), 'assignment'), 'id'))->toBe(array_slice($ids, 0, 2))
        ->and($first['next_cursor'])->toBe($ids[1]);
    $second = $list->handle($partner['user']->id, 1, $first['next_cursor'], 2);
    expect($second['data'][0]['work']['assignment']['id'])->toBe($ids[2])->and($second['next_cursor'])->toBeNull()
        ->and($list->handle($partner['user']->id, 1, $ids[2]))->toBe(['data' => [], 'next_cursor' => null]);
});

it('requires a current Auditor role for discovery and case access', function (string $fault, string $code): void {
    $fixture = Fixture::make(1);
    $partner = $fixture['partners'][0];
    $assignment = Fixture::request($fixture);
    $context = 1;
    if ($fault === 'mfa') {
        $partner['user']->forceFill(['two_factor_confirmed_at' => null])->save();
    } elseif ($fault === 'context') {
        $context = 0;
    } else {
        RoleMembership::query()->where('party_id', $partner['party']->id)->update(['status' => 'suspended']);
    }
    expect(fn () => app(ListAuditJobs::class)->handle($partner['user']->id, $context))->toThrow(IdentityViolation::class, $code)
        ->and(fn () => app(GetAuditApplication::class)->handle($partner['user']->id, $context, $assignment->id))->toThrow(IdentityViolation::class, $code);
})->with([['mfa', 'MFA_REQUIRED'], ['context', 'ACTIVE_ROLE_REVISION_CONFLICT'], ['membership', 'ROLE_MEMBERSHIP_REQUIRED']]);

it('rejects invalid Jobs pagination rather than reading unbounded work', function (?string $cursor, int $limit): void {
    $partner = AuditorFixture::make();
    expect(fn () => app(ListAuditJobs::class)->handle($partner['user']->id, 1, $cursor, $limit))->toThrow(CommandRejection::class, 'AUDIT_JOBS_PAGE_INVALID');
})->with([[null, 0], [null, 51], ['unknown', 25]]);
