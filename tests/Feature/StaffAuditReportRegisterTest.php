<?php

declare(strict_types=1);

use App\Application\Auditor\Contracts\MonthlyAuditBusinesses;
use App\Application\Auditor\DecideAuditReport;
use App\Application\Auditor\RequestMonthlyAudits;
use App\Application\Identity\ConfigureStaffAccess;
use App\Application\Identity\Contracts\PartyNotices;
use App\Domain\Auditor\AuditReportWindow;
use App\Domain\Identity\PartyNoticeKind;
use App\Models\AuditAssignment;
use App\Models\AuditReportPublication;
use App\Models\AuditReportSeal;
use App\Models\PartyNotice;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\AuditAssignmentFixture;
use Tests\Support\AuditSealingFixture;

use function Pest\Laravel\actingAs;

/*
 * The design's Reports oversight: every audit with the state of its report, a sealed report's PDF
 * and a reminder to whoever owes the next step, once a Kigali day.
 */

beforeEach(function (): void {
    // Early in the month, while last month's window (sealed by the 7th) is still open.
    $this->travelTo(now('UTC')->startOfMonth()->addDays(3)->setTime(10, 0));
    $this->withoutVite();
});

/** @param  list<string>  $roles */
function registerStaff(array $roles): User
{
    $user = User::factory()->withTwoFactor()->create();
    app(ConfigureStaffAccess::class)->handle($user->id, true, 'Audit report oversight.', (string) Str::uuid(), $roles);

    return $user;
}

/**
 * @param  array<string, string>  $query
 * @return array{stats: list<array{key: string, value: mixed}>, chips: list<array{key: string, count: int, link: mixed}>, rows: list<array<string, mixed>>, field: list<array<string, mixed>>, published: list<array<string, mixed>>} the register page's props
 */
function auditRegister(User $user, array $query = []): array
{
    $props = [];
    actingAs($user)->get(route('staff.reports.index', $query))->assertOk()->assertInertia(function (Assert $page) use (&$props): Assert {
        $props = $page->toArray()['props'];

        return $page->component('admin/audit-reports')->where('contract_version', 'staff-audit-report-register-v1');
    });

    return $props;
}

it('lists every audit with its state for any staff member, and reminds the partner who owes a monthly report once a day', function (): void {
    $fixture = AuditAssignmentFixture::make(1);
    $partner = $fixture['partners'][0];
    $assignment = AuditAssignmentFixture::request($fixture, 'routine');
    $analyst = registerStaff(['analyst']);
    expect(auditRegister($analyst)['rows'])->toBe([]);

    AuditAssignmentFixture::respond($partner['user'], $assignment);
    $period = now('Africa/Kigali')->startOfMonth()->subMonth()->format('Y-m');
    $due = (string) app(AuditReportWindow::class)->dueAt($period);
    $row = ['id' => $assignment->id, 'business' => 'Synthetic business', 'kind' => 'monthly', 'period' => $period.'-01',
        'auditor' => $partner['user']->name, 'licence' => 'SYNTHETIC-CPA', 'status' => 'not_started',
        'due_at' => now()->parse($due)->utc()->format('Y-m-d\TH:i:s\Z'), 'cosign_by' => null, 'sealed_at' => null, 'digest' => null,
        'variance_pct' => null, 'variance_flagged' => false, 'owes' => 'auditor', 'reminded_at' => null, 'links' => ['pdf' => null, 'remind' => null]];

    $page = auditRegister($analyst);
    expect($page['rows'])->toBe([$row])->and($page['field'])->toBe([])
        ->and(array_column($page['stats'], 'value', 'key'))->toBe(['published' => ['kind' => 'count', 'value' => 0],
            'awaiting_cosign' => ['kind' => 'count', 'value' => 0], 'overdue' => ['kind' => 'count', 'value' => 0], 'on_time' => ['kind' => 'text', 'value' => '—']])
        ->and(array_column($page['chips'], 'count', 'key'))->toBe(['all' => 1, 'owed' => 1, 'awaiting_cosign' => 0, 'published' => 0, 'disputed' => 0])
        ->and($page['chips'][1]['link'])->toBe(['url' => '/admin/reports?chip=owed', 'method' => 'get'])
        ->and(auditRegister($analyst, ['chip' => 'published'])['rows'])->toBe([]);
    actingAs($analyst)->post(route('staff.reports.remind', $assignment->id))->assertForbidden();
    actingAs($analyst)->get(route('staff.reports.index', ['chip' => 'late']))->assertSessionHasErrors('chip');

    $remind = ['url' => '/admin/reports/'.$assignment->id.'/remind', 'method' => 'post'];
    expect(auditRegister($fixture['staff'])['rows'][0]['links']['remind'])->toBe($remind);
    actingAs($fixture['staff'])->from('/admin/reports?chip=owed')->post($remind['url'])->assertRedirect('/admin/reports?chip=owed');
    actingAs($fixture['staff'])->post($remind['url'])->assertRedirect();

    $notice = PartyNotice::query()->where('kind', 'auditor.report_reminder')->sole();
    expect($notice->party_id)->toBe($partner['party']->id)
        ->and($notice->facts)->toEqualCanonicalizing(['business' => 'Synthetic business', 'kind' => 'monthly', 'period' => $period, 'due_at' => $row['due_at'], 'assignment_id' => $assignment->id]);
    $reminded = auditRegister($fixture['staff'])['rows'][0];
    expect($reminded['reminded_at'])->toBe($notice->created_at->utc()->format('Y-m-d\TH:i:s\Z'))->and($reminded['links']['remind'])->toBeNull();
    actingAs($partner['user'])->get(route('staff.reports.index'))->assertForbidden();
    auth()->logout();
    $this->get(route('staff.reports.index'))->assertRedirect(route('login'));
    // The notice opens the partner's file from their bell.
    actingAs($partner['user'])->getJson('/api/v1/auditor/notices')->assertOk()
        ->assertJsonPath('data.notices.0.kind', 'auditor.report_reminder')->assertJsonPath('data.notices.0.link.url', '/api/v1/auditor/jobs/'.$assignment->id);

    // The next Kigali day it may be sent again; past the 7th the audit reads overdue.
    $this->travelTo(now()->parse($due)->addSecond());
    $overdue = auditRegister($fixture['staff']);
    expect($overdue['rows'][0]['status'])->toBe('overdue')->and($overdue['rows'][0]['links']['remind'])->toBe($remind)
        ->and($overdue['stats'][2]['value'])->toBe(['kind' => 'count', 'value' => 1]);
    actingAs($fixture['staff'])->post($remind['url'])->assertRedirect();
    expect(PartyNotice::query()->where('kind', 'auditor.report_reminder')->count())->toBe(2);

});

it('reminds the Business while a sealed monthly report waits for it, offers no PDF link until it is published, and stops once it is disputed', function (): void {
    $fixture = AuditSealingFixture::ready(kind: 'monthly', findings: true);
    $staff = $fixture['audit']['staff'];
    $report = $fixture['report'];
    $assignment = $report->assignment_id;
    expect(auditRegister($staff)['rows'][0])->toMatchArray(['status' => 'in_progress', 'owes' => 'auditor', 'links' => ['pdf' => null,
        'remind' => ['url' => '/admin/reports/'.$assignment.'/remind', 'method' => 'post']]]);
    actingAs($staff)->get(route('staff.reports.pdf', $report->id))->assertNotFound();
    actingAs($staff)->post(route('staff.reports.remind', $assignment))->assertRedirect();
    expect(auditRegister($staff)['rows'][0]['links']['remind'])->toBeNull();

    AuditSealingFixture::seal($fixture);
    $seal = AuditReportSeal::query()->where('audit_report_id', $report->id)->sole();
    $row = auditRegister($staff)['rows'][0];
    expect($row)->toMatchArray(['status' => 'awaiting_cosign', 'owes' => 'business', 'licence' => $seal->payload['report']['licence'],
        'sealed_at' => $seal->payload['sealed_at'], 'digest' => substr($seal->digest, 0, 12), 'variance_pct' => '0.0', 'variance_flagged' => true])
        ->and($row['cosign_by'])->not->toBeNull()->and($row['reminded_at'])->toBeNull()
        ->and($row['links']['remind'])->toBe(['url' => '/admin/reports/'.$assignment.'/remind', 'method' => 'post'])
        // The Business may still co-sign or dispute it, so no PDF is offered yet.
        ->and($row['links']['pdf'])->toBeNull();

    actingAs($staff)->post(route('staff.reports.remind', $assignment))->assertRedirect();
    $notice = PartyNotice::query()->where('kind', 'business.report_review_closing')->sole();
    expect($notice->facts)->toEqualCanonicalizing(['due_at' => $row['cosign_by'], 'business_id' => $fixture['audit']['business'], 'report_id' => $report->id]);

    $pdf = actingAs(registerStaff(['analyst']))->get(route('staff.reports.pdf', $report->id))->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')->assertHeader('Content-Disposition', 'attachment; filename="rozine-audit-report-'.$report->id.'.pdf"');
    expect($pdf->getContent())->toStartWith('%PDF-1.4')->toContain('Rozine - Sealed Audit Report')->toContain('Synthetic business')
        ->toContain($seal->digest)->toContain('Cash \\(RWF\\)')->toContain('108000001')->toContain('Weighted variance: 0.0%')
        ->toContain('Observed difference retained for review.');
    actingAs(registerStaff(['analyst']))->get(route('staff.reports.pdf', (string) Str::ulid()))->assertNotFound();

    actingAs($fixture['audit']['authority']['users'][0])->post(route('business.audit-reports.dispute', ['business' => $fixture['audit']['business'], 'report' => $report->id]), [
        'request_id' => (string) Str::uuid(), 'identity_context_revision' => 1, 'expected_revision' => 1,
        'report_revision' => $report->revision + 1, 'mandate_version' => 1, 'digest' => $fixture['fields']['digest'],
        'proof_files' => [UploadedFile::fake()->createWithContent('proof.pdf', "%PDF-1.4\nSynthetic monthly proof\n%%EOF")],
    ], ['Accept' => 'application/json'])->assertOk()->assertJsonPath('code', 'REPORT_DISPUTED');

    $disputed = auditRegister($staff, ['chip' => 'disputed']);
    expect($disputed['rows'][0])->toMatchArray(['status' => 'disputed', 'owes' => null, 'cosign_by' => null])
        ->and($disputed['rows'][0]['links']['remind'])->toBeNull()->and($disputed['chips'][4]['count'])->toBe(1);
    actingAs($staff)->postJson(route('staff.reports.remind', $assignment))->assertConflict();
    actingAs($staff)->postJson(route('staff.reports.remind', (string) Str::ulid()))->assertNotFound();
});

it('gives a sealed Flash Audit the 24-hour co-sign deadline of the Business', function (): void {
    $fixture = AuditSealingFixture::ready();
    AuditSealingFixture::seal($fixture);

    $row = auditRegister($fixture['audit']['staff'])['rows'][0];
    expect($row)->toMatchArray(['kind' => 'flash', 'status' => 'awaiting_cosign', 'owes' => 'business',
        'cosign_by' => AuditReportPublication::query()->sole()->due_at->utc()->format('Y-m-d\TH:i:s\Z')]);
});

it('counts an earlier sealed Flash Audit on time while it waits for the Business, with no co-sign deadline to remind', function (): void {
    $fixture = AuditSealingFixture::ready();
    AuditSealingFixture::sealLegacy($fixture);

    $page = auditRegister($fixture['audit']['staff']);
    expect($page['rows'][0])->toMatchArray(['kind' => 'flash', 'period' => null, 'status' => 'awaiting_cosign', 'owes' => null, 'cosign_by' => null])
        ->and($page['rows'][0]['links']['remind'])->toBeNull()->and($page['published'])->toBe([])
        ->and($page['field'])->toBe([$page['rows'][0]])
        ->and($page['stats'][3]['value'])->toBe(['kind' => 'percent', 'value' => '100']);
    actingAs($fixture['audit']['staff'])->postJson(route('staff.reports.remind', $fixture['report']->assignment_id))->assertConflict();
});

it('reminds the partner of a report returned to them', function (): void {
    $fixture = AuditSealingFixture::ready(kind: 'monthly');
    app(DecideAuditReport::class)->handle($fixture['user']->id, 1, $fixture['report']->id, $fixture['report']->revision, false,
        'missing_originals', 'Missing original.', (string) Str::uuid());

    $row = auditRegister($fixture['audit']['staff'], ['chip' => 'owed'])['rows'][0];
    expect($row)->toMatchArray(['status' => 'returned', 'owes' => 'auditor'])
        ->and($row['links']['remind'])->not->toBeNull();
    actingAs($fixture['audit']['staff'])->post($row['links']['remind']['url'])->assertRedirect();
    expect(PartyNotice::query()->where('kind', 'auditor.report_reminder')->count())->toBe(1);
});

it('lists the replacement partner\'s unopened work instead of the withdrawn predecessor report', function (): void {
    $fixture = AuditSealingFixture::ready(kind: 'monthly');
    $assignment = AuditAssignment::query()->whereKey($fixture['report']->assignment_id)->firstOrFail();
    $replacement = AuditAssignmentFixture::make(1)['partners'][0];
    AuditAssignmentFixture::independence($fixture['audit']['staff'], $fixture['audit']['business'], $replacement['party']->id);

    AuditAssignmentFixture::respond($fixture['user'], $assignment, 'conflict', 'New family relationship.', 'family_or_business');
    expect($fixture['report']->refresh()->status)->toBe('withdrawn')
        ->and($assignment->refresh()->party_id)->toBe($replacement['party']->id);
    AuditAssignmentFixture::respond($replacement['user'], $assignment);

    $row = auditRegister($fixture['audit']['staff'], ['chip' => 'owed'])['rows'][0];
    expect($row)->toMatchArray(['id' => $assignment->id, 'auditor' => $replacement['user']->name,
        'status' => 'not_started', 'owes' => 'auditor', 'sealed_at' => null, 'digest' => null, 'licence' => 'SYNTHETIC-CPA'])
        ->and($row['links']['pdf'])->toBeNull()->and($row['links']['remind'])->not->toBeNull();
    actingAs($fixture['audit']['staff'])->post($row['links']['remind']['url'])->assertRedirect();
    expect(PartyNotice::query()->where('kind', 'auditor.report_reminder')->sole()->party_id)->toBe($replacement['party']->id);
});

it('remembers legacy reminders for their actual recipient and can remind a replacement on the same day', function (): void {
    $fixture = AuditAssignmentFixture::make(2);
    $assignment = AuditAssignmentFixture::request($fixture, 'routine');
    $original = AuditAssignmentFixture::recipient($fixture, $assignment);
    AuditAssignmentFixture::respond($original['user'], $assignment);
    $url = route('staff.reports.remind', $assignment->id);
    $row = auditRegister($fixture['staff'])['rows'][0];
    app(PartyNotices::class)->record($original['party']->id, PartyNoticeKind::AuditorReportReminder,
        ['business' => $row['business'], 'kind' => $row['kind'], 'period' => $row['period'], 'due_at' => $row['due_at'], 'assignment_id' => $assignment->id],
        'staff-audit-reminder:'.$assignment->id.':auditor:'.now('Africa/Kigali')->format('Y-m-d'));
    expect(auditRegister($fixture['staff'])['rows'][0]['links']['remind'])->toBeNull();
    actingAs($fixture['staff'])->post($url)->assertRedirect();
    expect(PartyNotice::query()->where('kind', 'auditor.report_reminder')->count())->toBe(1);

    AuditAssignmentFixture::respond($original['user'], $assignment->refresh(), 'conflict', 'New financial interest.', 'financial_interest');
    $replacement = AuditAssignmentFixture::recipient($fixture, $assignment->refresh());
    expect($replacement['party']->id)->not->toBe($original['party']->id);
    AuditAssignmentFixture::respond($replacement['user'], $assignment);
    $row = auditRegister($fixture['staff'])['rows'][0];
    expect($row['reminded_at'])->toBeNull()->and($row['links']['remind'])->not->toBeNull();

    actingAs($fixture['staff'])->post($url)->assertRedirect();
    actingAs($fixture['staff'])->post($url)->assertRedirect();
    expect(PartyNotice::query()->where('kind', 'auditor.report_reminder')->orderBy('id')->pluck('party_id')->all())
        ->toBe([$original['party']->id, $replacement['party']->id]);
});

it('refuses a Business reminder once its pending review window has closed', function (int $secondsAfter): void {
    $fixture = AuditSealingFixture::ready(kind: 'monthly');
    AuditSealingFixture::seal($fixture);
    $staff = $fixture['audit']['staff'];
    $row = auditRegister($staff)['rows'][0];
    expect($row['owes'])->toBe('business')->and($row['links']['remind'])->not->toBeNull();

    $this->travelTo(now()->parse($row['cosign_by'])->addSeconds($secondsAfter));
    $closed = auditRegister($staff)['rows'][0];
    expect($closed)->toMatchArray(['status' => 'awaiting_cosign', 'owes' => null, 'cosign_by' => $row['cosign_by']])
        ->and($closed['links']['remind'])->toBeNull();
    actingAs($staff)->postJson(route('staff.reports.remind', $fixture['report']->assignment_id))->assertConflict();
    expect(PartyNotice::query()->where('kind', 'business.report_review_closing')->count())->toBe(0);
})->with([0, 1]);

it('retains the original published report when the next audit completes its assignment', function (bool $monthly): void {
    $fixture = AuditSealingFixture::ready();
    AuditSealingFixture::seal($fixture);
    expect(AuditSealingFixture::cosign($fixture)['code'])->toBe('REPORT_PUBLISHED');
    $report = $fixture['report'];
    $staff = $fixture['audit']['staff'];
    $seal = AuditReportSeal::query()->where('audit_report_id', $report->id)->sole();
    if ($monthly) {
        $this->travelTo(now('Africa/Kigali')->addMonthNoOverflow()->startOfMonth()->addHours(6));
        app()->instance(MonthlyAuditBusinesses::class, new readonly class($fixture['audit']['business']) implements MonthlyAuditBusinesses
        {
            public function __construct(private string $businessId) {}

            public function due(DateTimeImmutable $now): array
            {
                return [$this->businessId];
            }
        });
        expect(app(RequestMonthlyAudits::class)->handle(now()->toDateTimeImmutable()))->toBe(1);
    } else {
        AuditAssignmentFixture::request($fixture['audit'], 'routine');
    }
    expect($fixture['assignment']->refresh()->status)->toBe('completed')
        ->and($fixture['assignment']->revision)->toBe($report->assignment_revision + 1);
    $page = auditRegister($staff);
    $row = collect($page['rows'])->firstWhere('id', $report->assignment_id);
    expect($row)->toMatchArray(['status' => 'published', 'owes' => null, 'digest' => substr($seal->digest, 0, 12),
        'links' => ['pdf' => ['url' => '/admin/reports/'.$report->id.'/pdf', 'method' => 'get'], 'remind' => null]])
        ->and($page['chips'][3]['count'])->toBe(1)
        ->and($page['stats'][0]['value'])->toBe(['kind' => 'count', 'value' => 1]);
    actingAs($staff)->get(route('staff.reports.pdf', $report->id))->assertOk()->assertHeader('Content-Type', 'application/pdf');
    actingAs($staff)->postJson(route('staff.reports.remind', $report->assignment_id))->assertConflict();
    expect(PartyNotice::query()->where('kind', 'auditor.report_reminder')->count())->toBe(0);
})->with(['manual request' => false, 'monthly scheduler' => true]);
